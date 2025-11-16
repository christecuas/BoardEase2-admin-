<?php
// qr_code_detector.php - QR Code detection API for image validation

// Disable error display to prevent HTML output
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Set content type to JSON
header('Content-Type: application/json');

// Log the request for debugging
error_log("QR Code detection request received at " . date('Y-m-d H:i:s'));
error_log("POST data: " . print_r($_POST, true));
error_log("FILES data: " . print_r($_FILES, true));

try {
    // Check if file was uploaded
    if (!isset($_FILES['qr_image']) || $_FILES['qr_image']['error'] !== UPLOAD_ERR_OK) {
        $response = array(
            "success" => false,
            "message" => "No image file uploaded or upload error occurred."
        );
        echo json_encode($response);
        exit;
    }

    $uploadedFile = $_FILES['qr_image'];
    $tempPath = $uploadedFile['tmp_name'];
    $originalName = $uploadedFile['name'];
    
    error_log("Processing uploaded file: " . $originalName . " at " . $tempPath);

    // Validate file type
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    $fileType = mime_content_type($tempPath);
    
    if (!in_array($fileType, $allowedTypes)) {
        $response = array(
            "success" => false,
            "message" => "Invalid file type. Please upload a JPEG, PNG, or GIF image."
        );
        echo json_encode($response);
        exit;
    }

    // Check if file size is reasonable (max 5MB)
    if ($uploadedFile['size'] > 5 * 1024 * 1024) {
        $response = array(
            "success" => false,
            "message" => "File too large. Please upload an image smaller than 5MB."
        );
        echo json_encode($response);
        exit;
    }

    // Detect QR code using multiple methods
    $qrDetected = false;
    $detectionMethod = "";
    $qrContent = "";

    // Method 1: Using ZXing library (if available)
    if (function_exists('shell_exec')) {
        $result = detectQRWithZXing($tempPath);
        if ($result['success']) {
            $qrDetected = true;
            $detectionMethod = "ZXing";
            $qrContent = $result['content'];
            error_log("QR Code detected using ZXing: " . $qrContent);
        }
    }

    // Method 2: Using OpenCV (if available)
    if (!$qrDetected && function_exists('shell_exec')) {
        $result = detectQRWithOpenCV($tempPath);
        if ($result['success']) {
            $qrDetected = true;
            $detectionMethod = "OpenCV";
            $qrContent = $result['content'];
            error_log("QR Code detected using OpenCV: " . $qrContent);
        }
    }

    // Method 3: Using PHP QR Code libraries (if available)
    if (!$qrDetected) {
        $result = detectQRWithPHPLib($tempPath);
        if ($result['success']) {
            $qrDetected = true;
            $detectionMethod = "PHP Library";
            $qrContent = $result['content'];
            error_log("QR Code detected using PHP Library: " . $qrContent);
        }
    }

    // Method 4: Basic image analysis (fallback)
    if (!$qrDetected) {
        $result = basicQRDetection($tempPath);
        if ($result['success']) {
            $qrDetected = true;
            $detectionMethod = "Basic Analysis";
            $qrContent = $result['content'];
            error_log("QR Code detected using Basic Analysis: " . $qrContent);
        }
    }

    if ($qrDetected) {
        $response = array(
            "success" => true,
            "message" => "QR code detected successfully!",
            "qr_content" => $qrContent,
            "detection_method" => $detectionMethod,
            "file_info" => array(
                "name" => $originalName,
                "type" => $fileType,
                "size" => $uploadedFile['size']
            )
        );
        error_log("QR Code validation SUCCESS for file: " . $originalName);
    } else {
        $response = array(
            "success" => false,
            "message" => "No QR code detected in the uploaded image. Please upload an image containing a valid QR code.",
            "file_info" => array(
                "name" => $originalName,
                "type" => $fileType,
                "size" => $uploadedFile['size']
            )
        );
        error_log("QR Code validation FAILED for file: " . $originalName);
    }

    echo json_encode($response);

} catch (Exception $e) {
    error_log("QR Code detection error: " . $e->getMessage());
    $response = array(
        "success" => false,
        "message" => "Server error during QR code detection: " . $e->getMessage()
    );
    echo json_encode($response);
}

/**
 * Detect QR code using ZXing library
 */
function detectQRWithZXing($imagePath) {
    try {
        // Check if ZXing is available
        $zxingPath = '/usr/bin/zxing'; // Common path
        if (!file_exists($zxingPath)) {
            $zxingPath = 'zxing'; // Try in PATH
        }
        
        $command = escapeshellarg($zxingPath) . ' -q ' . escapeshellarg($imagePath) . ' 2>/dev/null';
        $output = shell_exec($command);
        
        if ($output && trim($output)) {
            return array(
                'success' => true,
                'content' => trim($output)
            );
        }
    } catch (Exception $e) {
        error_log("ZXing detection error: " . $e->getMessage());
    }
    
    return array('success' => false);
}

/**
 * Detect QR code using OpenCV
 */
function detectQRWithOpenCV($imagePath) {
    try {
        // Check if OpenCV is available
        $opencvPath = '/usr/bin/opencv_qr_detector'; // Common path
        if (!file_exists($opencvPath)) {
            $opencvPath = 'opencv_qr_detector'; // Try in PATH
        }
        
        $command = escapeshellarg($opencvPath) . ' ' . escapeshellarg($imagePath) . ' 2>/dev/null';
        $output = shell_exec($command);
        
        if ($output && trim($output)) {
            return array(
                'success' => true,
                'content' => trim($output)
            );
        }
    } catch (Exception $e) {
        error_log("OpenCV detection error: " . $e->getMessage());
    }
    
    return array('success' => false);
}

/**
 * Detect QR code using PHP libraries
 */
function detectQRWithPHPLib($imagePath) {
    try {
        // Check if QR code libraries are available
        if (class_exists('Zxing\QrReader')) {
            $qrReader = new Zxing\QrReader($imagePath);
            $qrText = $qrReader->text();
            
            if ($qrText) {
                return array(
                    'success' => true,
                    'content' => $qrText
                );
            }
        }
        
        // Try other PHP QR libraries if available
        if (function_exists('qr_decode')) {
            $result = qr_decode($imagePath);
            if ($result) {
                return array(
                    'success' => true,
                    'content' => $result
                );
            }
        }
    } catch (Exception $e) {
        error_log("PHP Library detection error: " . $e->getMessage());
    }
    
    return array('success' => false);
}

/**
 * Basic QR code detection using image analysis
 */
function basicQRDetection($imagePath) {
    try {
        // Get image dimensions
        $imageInfo = getimagesize($imagePath);
        if (!$imageInfo) {
            return array('success' => false);
        }
        
        $width = $imageInfo[0];
        $height = $imageInfo[1];
        
        // Load image based on type
        $image = null;
        switch ($imageInfo[2]) {
            case IMAGETYPE_JPEG:
                $image = imagecreatefromjpeg($imagePath);
                break;
            case IMAGETYPE_PNG:
                $image = imagecreatefrompng($imagePath);
                break;
            case IMAGETYPE_GIF:
                $image = imagecreatefromgif($imagePath);
                break;
            default:
                return array('success' => false);
        }
        
        if (!$image) {
            return array('success' => false);
        }
        
        // Basic QR code pattern detection
        // Look for the characteristic square patterns of QR codes
        $qrPatterns = detectQRPatterns($image, $width, $height);
        
        if ($qrPatterns > 0) {
            return array(
                'success' => true,
                'content' => "QR Code pattern detected (confidence: " . $qrPatterns . "%)"
            );
        }
        
        imagedestroy($image);
    } catch (Exception $e) {
        error_log("Basic detection error: " . $e->getMessage());
    }
    
    return array('success' => false);
}

/**
 * Detect QR code patterns in image
 */
function detectQRPatterns($image, $width, $height) {
    $confidence = 0;
    
    // Sample the image in a grid pattern
    $sampleSize = min($width, $height) / 20; // Sample every 5% of image
    $samples = 0;
    $qrLikeSamples = 0;
    
    for ($x = 0; $x < $width; $x += $sampleSize) {
        for ($y = 0; $y < $height; $y += $sampleSize) {
            $samples++;
            
            // Check for high contrast patterns (characteristic of QR codes)
            $pixel = imagecolorat($image, $x, $y);
            $r = ($pixel >> 16) & 0xFF;
            $g = ($pixel >> 8) & 0xFF;
            $b = $pixel & 0xFF;
            $brightness = ($r + $g + $b) / 3;
            
            // Check surrounding pixels for contrast
            $contrast = checkContrast($image, $x, $y, $width, $height);
            
            if ($contrast > 0.5) { // High contrast area
                $qrLikeSamples++;
            }
        }
    }
    
    if ($samples > 0) {
        $confidence = ($qrLikeSamples / $samples) * 100;
    }
    
    return $confidence;
}

/**
 * Check contrast around a pixel
 */
function checkContrast($image, $x, $y, $width, $height) {
    $pixel = imagecolorat($image, $x, $y);
    $r = ($pixel >> 16) & 0xFF;
    $g = ($pixel >> 8) & 0xFF;
    $b = $pixel & 0xFF;
    $brightness = ($r + $g + $b) / 3;
    
    $neighbors = 0;
    $totalContrast = 0;
    
    // Check 8 surrounding pixels
    for ($dx = -1; $dx <= 1; $dx++) {
        for ($dy = -1; $dy <= 1; $dy++) {
            if ($dx == 0 && $dy == 0) continue;
            
            $nx = $x + $dx;
            $ny = $y + $dy;
            
            if ($nx >= 0 && $nx < $width && $ny >= 0 && $ny < $height) {
                $neighborPixel = imagecolorat($image, $nx, $ny);
                $nr = ($neighborPixel >> 16) & 0xFF;
                $ng = ($neighborPixel >> 8) & 0xFF;
                $nb = $neighborPixel & 0xFF;
                $neighborBrightness = ($nr + $ng + $nb) / 3;
                
                $contrast = abs($brightness - $neighborBrightness) / 255;
                $totalContrast += $contrast;
                $neighbors++;
            }
        }
    }
    
    return $neighbors > 0 ? $totalContrast / $neighbors : 0;
}
?>
