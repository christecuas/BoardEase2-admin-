<?php
// Standalone QR Code Validation Functions
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

function validateGcashQrCode($filePath) {
    try {
        error_log("=== QR CODE VALIDATION STARTED ===");
        error_log("File path: " . $filePath);
        
        // Check if file exists
        if (!file_exists($filePath)) {
            error_log("QR VALIDATION FAILED: File not found");
            return array('isValid' => false, 'reason' => 'QR code file not found');
        }
        
        // Get image information
        $imageInfo = getimagesize($filePath);
        if (!$imageInfo) {
            error_log("QR VALIDATION FAILED: Invalid image format");
            return array('isValid' => false, 'reason' => 'Invalid image format');
        }
        
        $width = $imageInfo[0];
        $height = $imageInfo[1];
        $mimeType = $imageInfo['mime'];
        
        error_log("Image info - Width: $width, Height: $height, MIME: $mimeType");
        
        // Basic size requirements
        if ($width < 100 || $height < 100) {
            error_log("QR VALIDATION FAILED: Image too small");
            return array('isValid' => false, 'reason' => 'Image too small for QR code (minimum 100x100 pixels)');
        }
        
        if ($width > 5000 || $height > 5000) {
            error_log("QR VALIDATION FAILED: Image too large");
            return array('isValid' => false, 'reason' => 'Image too large for QR code (maximum 5000x5000 pixels)');
        }
        
        // Check file size
        $fileSize = filesize($filePath);
        if ($fileSize > 10 * 1024 * 1024) { // 10MB limit
            error_log("QR VALIDATION FAILED: File too large");
            return array('isValid' => false, 'reason' => 'Image file too large (maximum 10MB)');
        }
        
        error_log("File size: " . ($fileSize / 1024) . " KB");
        
        // Try QR detection APIs
        $apiResults = array();
        $successfulApis = 0;
        
        error_log("=== STARTING API TESTS ===");
        
        // Try QR Server API
        error_log("Testing QR Server API...");
        $result1 = tryQrServerAPI($filePath);
        $apiResults[] = $result1;
        if ($result1['hasQrCode']) {
            $successfulApis++;
            error_log("QR Server API SUCCESS: " . $result1['qrCodeContent']);
        } else {
            error_log("QR Server API FAILED: " . ($result1['reason'] ?? 'Unknown error'));
        }
        
        // Try QuickChart API
        error_log("Testing QuickChart API...");
        $result2 = tryQuickChartAPI($filePath);
        $apiResults[] = $result2;
        if ($result2['hasQrCode']) {
            $successfulApis++;
            error_log("QuickChart API SUCCESS: " . $result2['qrCodeContent']);
        } else {
            error_log("QuickChart API FAILED: " . ($result2['reason'] ?? 'Unknown error'));
        }
        
        // Try ZXing API
        error_log("Testing ZXing API...");
        $result3 = tryZxingAPI($filePath);
        $apiResults[] = $result3;
        if ($result3['hasQrCode']) {
            $successfulApis++;
            error_log("ZXing API SUCCESS: " . $result3['qrCodeContent']);
        } else {
            error_log("ZXing API FAILED: " . ($result3['reason'] ?? 'Unknown error'));
        }
        
        error_log("TOTAL SUCCESSFUL APIs: " . $successfulApis . " out of 3");
        
        // REASONABLE REQUIREMENT - At least 2 out of 3 APIs must succeed
        // But if all APIs fail due to network issues, fall back to basic validation
        if ($successfulApis < 2) {
            error_log("QR VALIDATION FAILED: Not enough APIs succeeded ($successfulApis/3)");
            
            // Check if all APIs failed due to network/API issues (not content issues)
            $allNetworkErrors = true;
            foreach ($apiResults as $result) {
                if (!isset($result['reason']) || 
                    (strpos($result['reason'], 'cURL Error') === false && 
                     strpos($result['reason'], 'HTTP Error') === false &&
                     strpos($result['reason'], 'Exception') === false)) {
                    $allNetworkErrors = false;
                    break;
                }
            }
            
            if ($allNetworkErrors && $successfulApis == 0) {
                error_log("All APIs failed due to network issues - falling back to basic validation");
                // Fall back to basic image validation
                if ($width >= 200 && $height >= 200 && $width <= 2000 && $height <= 2000) {
                    error_log("Basic validation passed - accepting image due to API unavailability");
                    return array('isValid' => true, 'reason' => 'QR code accepted - API services temporarily unavailable');
                } else {
                    error_log("Basic validation failed - image dimensions not suitable for QR code");
                    return array('isValid' => false, 'reason' => 'Image dimensions not suitable for QR code');
                }
            }
            
            return array('isValid' => false, 'reason' => 'QR code validation failed - image does not contain a valid QR code');
        }
        
        error_log("API requirement passed: $successfulApis APIs succeeded");
        
        // Check content from successful APIs
        error_log("=== CHECKING CONTENT VALIDATION ===");
        foreach ($apiResults as $index => $result) {
            if ($result['hasQrCode'] && isset($result['qrCodeContent']) && !empty($result['qrCodeContent'])) {
                $content = $result['qrCodeContent'];
                error_log("API " . ($index + 1) . " content: " . $content);
                
                // Check for payment-related keywords
                $paymentKeywords = array('gcash', 'paymaya', 'paypal', 'payment', 'qr', 'code', 'cash', 'money', 'transfer', 'send', 'receive');
                $contentLower = strtolower($content);
                
                foreach ($paymentKeywords as $keyword) {
                    if (strpos($contentLower, $keyword) !== false) {
                        error_log("QR VALIDATION SUCCESS: Valid payment QR code detected by API " . ($index + 1));
                        return array('isValid' => true, 'reason' => 'Valid QR code detected and verified by ' . $successfulApis . ' APIs');
                    }
                }
                
                // Check for URLs
                if (filter_var($content, FILTER_VALIDATE_URL)) {
                    error_log("QR VALIDATION SUCCESS: Valid URL QR code detected by API " . ($index + 1));
                    return array('isValid' => true, 'reason' => 'Valid QR code detected and verified by ' . $successfulApis . ' APIs');
                }
                
                // Check for JSON
                if (json_decode($content) !== null) {
                    error_log("QR VALIDATION SUCCESS: Valid JSON QR code detected by API " . ($index + 1));
                    return array('isValid' => true, 'reason' => 'Valid QR code detected and verified by ' . $successfulApis . ' APIs');
                }
            }
        }
        
        error_log("QR VALIDATION FAILED: No valid content found in QR codes");
        return array('isValid' => false, 'reason' => 'QR code content does not appear to be payment-related');
        
    } catch (Exception $e) {
        error_log("QR VALIDATION ERROR: " . $e->getMessage());
        return array('isValid' => false, 'reason' => 'QR validation error: ' . $e->getMessage());
    }
}

function tryQrServerAPI($filePath) {
    try {
        error_log("QR Server API: Starting request for " . $filePath);
        
        $apiUrl = 'https://api.qrserver.com/v1/read-qr-code/';
        
        $postData = array(
            'file' => new CURLFile($filePath, mime_content_type($filePath), basename($filePath))
        );
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        error_log("QR Server API: HTTP Code: $httpCode, Error: $error");
        error_log("QR Server API: Response: " . substr($response, 0, 200));
        
        if ($error) {
            error_log("QR Server API: cURL Error: $error");
            return array('hasQrCode' => false, 'reason' => "cURL Error: $error");
        }
        
        if ($httpCode != 200) {
            error_log("QR Server API: HTTP Error: $httpCode");
            return array('hasQrCode' => false, 'reason' => "HTTP Error: $httpCode");
        }
        
        if (!$response) {
            error_log("QR Server API: Empty response");
            return array('hasQrCode' => false, 'reason' => 'Empty response');
        }
        
        $result = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("QR Server API: JSON Error: " . json_last_error_msg());
            return array('hasQrCode' => false, 'reason' => 'JSON Error: ' . json_last_error_msg());
        }
        
        if (isset($result[0]['symbol'][0]['data'])) {
            $qrCodeContent = $result[0]['symbol'][0]['data'];
            error_log("QR Server API: SUCCESS - Content: $qrCodeContent");
            return array(
                'hasQrCode' => true,
                'qrCodeContent' => $qrCodeContent,
                'method' => 'qrserver_api'
            );
        } else {
            error_log("QR Server API: No QR code found in response");
            return array('hasQrCode' => false, 'reason' => 'No QR code found');
        }
        
    } catch (Exception $e) {
        error_log("QR Server API: Exception: " . $e->getMessage());
        return array('hasQrCode' => false, 'reason' => 'Exception: ' . $e->getMessage());
    }
}

function tryQuickChartAPI($filePath) {
    try {
        error_log("QuickChart API: Starting request for " . $filePath);
        
        // Convert image to base64 for QuickChart API
        $imageData = file_get_contents($filePath);
        $base64Image = base64_encode($imageData);
        
        $apiUrl = 'https://quickchart.io/qr/decode';
        
        $postData = array(
            'data' => $base64Image
        );
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        error_log("QuickChart API: HTTP Code: $httpCode, Error: $error");
        error_log("QuickChart API: Response: " . substr($response, 0, 200));
        
        if ($error) {
            error_log("QuickChart API: cURL Error: $error");
            return array('hasQrCode' => false, 'reason' => "cURL Error: $error");
        }
        
        if ($httpCode != 200) {
            error_log("QuickChart API: HTTP Error: $httpCode");
            return array('hasQrCode' => false, 'reason' => "HTTP Error: $httpCode");
        }
        
        if (!$response) {
            error_log("QuickChart API: Empty response");
            return array('hasQrCode' => false, 'reason' => 'Empty response');
        }
        
        $result = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("QuickChart API: JSON Error: " . json_last_error_msg());
            return array('hasQrCode' => false, 'reason' => 'JSON Error: ' . json_last_error_msg());
        }
        
        if (isset($result['data']) && !empty($result['data'])) {
            $qrCodeContent = $result['data'];
            error_log("QuickChart API: SUCCESS - Content: $qrCodeContent");
            return array(
                'hasQrCode' => true,
                'qrCodeContent' => $qrCodeContent,
                'method' => 'quickchart_api'
            );
        } else {
            error_log("QuickChart API: No QR code found in response");
            return array('hasQrCode' => false, 'reason' => 'No QR code found');
        }
        
    } catch (Exception $e) {
        error_log("QuickChart API: Exception: " . $e->getMessage());
        return array('hasQrCode' => false, 'reason' => 'Exception: ' . $e->getMessage());
    }
}

function tryZxingAPI($filePath) {
    try {
        error_log("ZXing API: Starting request for " . $filePath);
        
        $apiUrl = 'https://zxing.org/w/decode';
        
        $postData = array(
            'f' => new CURLFile($filePath, mime_content_type($filePath), basename($filePath))
        );
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        error_log("ZXing API: HTTP Code: $httpCode, Error: $error");
        error_log("ZXing API: Response: " . substr($response, 0, 200));
        
        if ($error) {
            error_log("ZXing API: cURL Error: $error");
            return array('hasQrCode' => false, 'reason' => "cURL Error: $error");
        }
        
        if ($httpCode != 200) {
            error_log("ZXing API: HTTP Error: $httpCode");
            return array('hasQrCode' => false, 'reason' => "HTTP Error: $httpCode");
        }
        
        if (!$response) {
            error_log("ZXing API: Empty response");
            return array('hasQrCode' => false, 'reason' => 'Empty response');
        }
        
        // Parse HTML response for QR code content
        if (preg_match('/<pre[^>]*>(.*?)<\/pre>/s', $response, $matches)) {
            $qrCodeContent = trim($matches[1]);
            if (!empty($qrCodeContent)) {
                error_log("ZXing API: SUCCESS - Content: $qrCodeContent");
                return array(
                    'hasQrCode' => true,
                    'qrCodeContent' => $qrCodeContent,
                    'method' => 'zxing_api'
                );
            }
        }
        
        error_log("ZXing API: No QR code found in response");
        return array('hasQrCode' => false, 'reason' => 'No QR code found');
        
    } catch (Exception $e) {
        error_log("ZXing API: Exception: " . $e->getMessage());
        return array('hasQrCode' => false, 'reason' => 'Exception: ' . $e->getMessage());
    }
}
?>
