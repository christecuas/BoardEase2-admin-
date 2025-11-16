<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Disable error display to prevent HTML output
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Log all requests
error_log("Philippine ID Detection API called at " . date('Y-m-d H:i:s'));

function logError($message) {
    error_log("Philippine ID API Error: " . $message);
}

function sendResponse($success, $data = null, $error = null) {
    $response = [
        'success' => $success,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    if ($success) {
        $response['data'] = $data;
    } else {
        $response['error'] = $error;
    }
    
    echo json_encode($response);
    exit;
}

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(false, null, 'Only POST method allowed');
}

// Get JSON input
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    sendResponse(false, null, 'Invalid JSON input');
}

// Validate required fields
if (!isset($data['image']) || !isset($data['side'])) {
    sendResponse(false, null, 'Missing required fields: image and side');
}

$base64Image = $data['image'];
$side = $data['side']; // 'front' or 'back'

error_log("Processing " . $side . " ID image, base64 length: " . strlen($base64Image));

// Validate base64 image
if (!preg_match('/^data:image\/(jpeg|jpg|png);base64,/', $base64Image)) {
    sendResponse(false, null, 'Invalid image format. Only JPEG and PNG allowed.');
}

// Extract image data
$imageData = base64_decode(preg_replace('/^data:image\/(jpeg|jpg|png);base64,/', '', $base64Image));
if ($imageData === false) {
    sendResponse(false, null, 'Failed to decode base64 image');
}

// Save temporary image
$tempFile = tempnam(sys_get_temp_dir(), 'ph_id_') . '.jpg';
if (file_put_contents($tempFile, $imageData) === false) {
    sendResponse(false, null, 'Failed to save temporary image');
}

try {
    // Perform OCR using Google Vision API or similar
    $extractedText = performOCR($tempFile);
    error_log("Extracted text: " . substr($extractedText, 0, 200) . "...");
    
    // Detect ID type
    $idType = detectPhilippineIdType($extractedText);
    error_log("Detected ID type: " . $idType);
    
    // Extract fields based on ID type and side
    $extractedFields = extractIdFields($extractedText, $idType, $side);
    error_log("Extracted fields: " . json_encode($extractedFields));
    
    // Validate if it's a valid ID
    $isValid = validateIdDocument($extractedText, $idType, $side);
    error_log("ID validation result: " . ($isValid ? 'VALID' : 'INVALID'));
    
    $result = [
        'isValid' => $isValid,
        'idType' => $idType,
        'side' => $side,
        'extractedText' => $extractedText,
        'extractedFields' => $extractedFields,
        'confidence' => calculateConfidence($extractedFields, $idType, $side)
    ];
    
    sendResponse(true, $result);
    
} catch (Exception $e) {
    logError("Exception: " . $e->getMessage());
    sendResponse(false, null, 'Internal server error: ' . $e->getMessage());
} finally {
    // Clean up temporary file
    if (file_exists($tempFile)) {
        unlink($tempFile);
    }
}

function performOCR($imagePath) {
    // Try to use Tesseract OCR if available
    if (function_exists('shell_exec') && !empty(shell_exec('which tesseract'))) {
        // Use Tesseract OCR
        $command = "tesseract " . escapeshellarg($imagePath) . " stdout 2>/dev/null";
        $result = shell_exec($command);
        if ($result && trim($result) !== '') {
            error_log("Tesseract OCR result: " . substr($result, 0, 200));
            return trim($result);
        }
    }
    
    // Fallback: Basic image analysis to determine if it's likely an ID
    $imageSize = getimagesize($imagePath);
    if (!$imageSize) {
        error_log("Failed to get image size");
        return "";
    }
    
    $width = $imageSize[0];
    $height = $imageSize[1];
    $aspectRatio = $width / $height;
    
    error_log("Image analysis - Width: $width, Height: $height, Aspect Ratio: $aspectRatio");
    
    // Check if image is too small
    if ($width < 200 || $height < 200) {
        error_log("Image too small for analysis");
        return "";
    }
    
    // Check if image is likely a document (landscape orientation)
    if ($aspectRatio < 1.2) {
        error_log("Image not landscape - likely not an ID document");
        return "";
    }
    
    // For now, return empty string to force fallback to local detection
    // This will make the system use local OCR instead of mock data
    error_log("No OCR available, returning empty for local fallback");
    return "";
}

function detectPhilippineIdType($text) {
    $textLower = strtolower($text);
    
    // National ID / PhilID patterns
    if (strpos($textLower, 'philippine national id') !== false || 
        strpos($textLower, 'philid') !== false ||
        strpos($textLower, 'national id') !== false) {
        return 'NATIONAL_ID';
    }
    
    // Driver's License patterns
    if (strpos($textLower, 'driver') !== false && 
        strpos($textLower, 'license') !== false) {
        return 'DRIVERS_LICENSE';
    }
    
    // Passport patterns
    if (strpos($textLower, 'passport') !== false && 
        strpos($textLower, 'philippine') !== false) {
        return 'PASSPORT';
    }
    
    // UMID patterns
    if (strpos($textLower, 'umid') !== false || 
        strpos($textLower, 'unified multi-purpose') !== false) {
        return 'UMID';
    }
    
    // SSS patterns
    if (strpos($textLower, 'sss') !== false && 
        strpos($textLower, 'social security') !== false) {
        return 'SSS';
    }
    
    // GSIS patterns
    if (strpos($textLower, 'gsis') !== false) {
        return 'GSIS';
    }
    
    // PhilHealth patterns
    if (strpos($textLower, 'philhealth') !== false) {
        return 'PHILHEALTH';
    }
    
    // TIN patterns
    if (strpos($textLower, 'tin') !== false && 
        strpos($textLower, 'tax identification') !== false) {
        return 'TIN';
    }
    
    // Voter's ID patterns
    if (strpos($textLower, 'voter') !== false) {
        return 'VOTERS_ID';
    }
    
    // Postal ID patterns
    if (strpos($textLower, 'postal') !== false) {
        return 'POSTAL_ID';
    }
    
    return 'UNKNOWN';
}

function extractIdFields($text, $idType, $side) {
    $fields = [];
    $textLower = strtolower($text);
    
    if ($idType === 'NATIONAL_ID') {
        if ($side === 'front') {
            $fields = extractNationalIdFrontFields($text);
        } else {
            $fields = extractNationalIdBackFields($text);
        }
    } elseif ($idType === 'DRIVERS_LICENSE') {
        if ($side === 'front') {
            $fields = extractDriversLicenseFrontFields($text);
        } else {
            $fields = extractDriversLicenseBackFields($text);
        }
    } elseif ($idType === 'PASSPORT') {
        if ($side === 'front') {
            $fields = extractPassportFrontFields($text);
        } else {
            $fields = extractPassportBackFields($text);
        }
    } else {
        // Generic extraction for other ID types
        $fields = extractGenericIdFields($text, $side);
    }
    
    return $fields;
}

function extractNationalIdFrontFields($text) {
    $fields = [];
    
    // Extract ID Number (16 digits with or without hyphens)
    if (preg_match('/\b(\d{4}[- ]?\d{4}[- ]?\d{4}[- ]?\d{4})\b/', $text, $matches)) {
        $fields['id_number'] = preg_replace('/[^0-9]/', '', $matches[1]);
    }
    
    // Extract Last Name (usually appears after "SURNAME" or similar)
    if (preg_match('/(?:surname|lastname|last name)[\s:]*([A-Z][A-Z\s]+)/i', $text, $matches)) {
        $fields['last_name'] = trim($matches[1]);
    }
    
    // Extract Given Name (usually appears after "GIVEN NAME" or similar)
    if (preg_match('/(?:given name|first name|givenname)[\s:]*([A-Z][A-Z\s]+)/i', $text, $matches)) {
        $fields['given_name'] = trim($matches[1]);
    }
    
    // Extract Middle Name
    if (preg_match('/(?:middle name|middlename)[\s:]*([A-Z][A-Z\s]+)/i', $text, $matches)) {
        $fields['middle_name'] = trim($matches[1]);
    }
    
    // Extract Date of Birth
    if (preg_match('/(?:date of birth|birth|dob)[\s:]*(\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4})/i', $text, $matches)) {
        $fields['date_of_birth'] = $matches[1];
    }
    
    // Extract Address (look for common address keywords)
    if (preg_match('/(?:address|residence)[\s:]*([A-Z0-9\s,.-]+?)(?:\n|$)/i', $text, $matches)) {
        $fields['address'] = trim($matches[1]);
    }
    
    return $fields;
}

function extractNationalIdBackFields($text) {
    $fields = [];
    
    // Extract QR Code (look for QR code indicators)
    if (preg_match('/(?:qr|quick response|code)[\s:]*([A-Z0-9\s]+)/i', $text, $matches)) {
        $fields['qr_code'] = trim($matches[1]);
    }
    
    // Extract Date of Issue
    if (preg_match('/(?:date of issue|issue date|issued)[\s:]*(\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4})/i', $text, $matches)) {
        $fields['date_of_issue'] = $matches[1];
    }
    
    // Extract Sex/Gender
    if (preg_match('/(?:sex|gender)[\s:]*([MF|MALE|FEMALE]+)/i', $text, $matches)) {
        $fields['sex'] = strtoupper($matches[1]);
    }
    
    // Extract Blood Type
    if (preg_match('/(?:blood type|blood)[\s:]*([ABO][+-]?)/i', $text, $matches)) {
        $fields['blood_type'] = strtoupper($matches[1]);
    }
    
    // Extract Marital Status
    if (preg_match('/(?:marital status|civil status)[\s:]*([A-Z\s]+)/i', $text, $matches)) {
        $fields['marital_status'] = trim($matches[1]);
    }
    
    // Extract Place of Birth
    if (preg_match('/(?:place of birth|birthplace)[\s:]*([A-Z\s,.-]+)/i', $text, $matches)) {
        $fields['place_of_birth'] = trim($matches[1]);
    }
    
    return $fields;
}

function extractDriversLicenseFrontFields($text) {
    $fields = [];
    
    // Extract License Number
    if (preg_match('/(?:license no|license number|lic no)[\s:]*([A-Z0-9\s-]+)/i', $text, $matches)) {
        $fields['license_number'] = trim($matches[1]);
    }
    
    // Extract Name
    if (preg_match('/(?:name|full name)[\s:]*([A-Z\s,.-]+)/i', $text, $matches)) {
        $fields['full_name'] = trim($matches[1]);
    }
    
    // Extract Date of Birth
    if (preg_match('/(?:date of birth|birth|dob)[\s:]*(\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4})/i', $text, $matches)) {
        $fields['date_of_birth'] = $matches[1];
    }
    
    // Extract Address
    if (preg_match('/(?:address|residence)[\s:]*([A-Z0-9\s,.-]+?)(?:\n|$)/i', $text, $matches)) {
        $fields['address'] = trim($matches[1]);
    }
    
    return $fields;
}

function extractDriversLicenseBackFields($text) {
    $fields = [];
    
    // Extract Restrictions
    if (preg_match('/(?:restrictions|restriction)[\s:]*([A-Z0-9\s,.-]+)/i', $text, $matches)) {
        $fields['restrictions'] = trim($matches[1]);
    }
    
    // Extract Conditions
    if (preg_match('/(?:conditions|condition)[\s:]*([A-Z0-9\s,.-]+)/i', $text, $matches)) {
        $fields['conditions'] = trim($matches[1]);
    }
    
    // Extract Expiry Date
    if (preg_match('/(?:expiry|expires|valid until)[\s:]*(\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4})/i', $text, $matches)) {
        $fields['expiry_date'] = $matches[1];
    }
    
    return $fields;
}

function extractPassportFrontFields($text) {
    $fields = [];
    
    // Extract Passport Number
    if (preg_match('/(?:passport no|passport number|pass no)[\s:]*([A-Z0-9\s-]+)/i', $text, $matches)) {
        $fields['passport_number'] = trim($matches[1]);
    }
    
    // Extract Name
    if (preg_match('/(?:name|full name)[\s:]*([A-Z\s,.-]+)/i', $text, $matches)) {
        $fields['full_name'] = trim($matches[1]);
    }
    
    // Extract Date of Birth
    if (preg_match('/(?:date of birth|birth|dob)[\s:]*(\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4})/i', $text, $matches)) {
        $fields['date_of_birth'] = $matches[1];
    }
    
    // Extract Nationality
    if (preg_match('/(?:nationality)[\s:]*([A-Z\s]+)/i', $text, $matches)) {
        $fields['nationality'] = trim($matches[1]);
    }
    
    return $fields;
}

function extractPassportBackFields($text) {
    $fields = [];
    
    // Extract Issue Date
    if (preg_match('/(?:issue date|issued|date of issue)[\s:]*(\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4})/i', $text, $matches)) {
        $fields['issue_date'] = $matches[1];
    }
    
    // Extract Expiry Date
    if (preg_match('/(?:expiry|expires|valid until)[\s:]*(\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4})/i', $text, $matches)) {
        $fields['expiry_date'] = $matches[1];
    }
    
    // Extract Place of Issue
    if (preg_match('/(?:place of issue|issued at)[\s:]*([A-Z\s,.-]+)/i', $text, $matches)) {
        $fields['place_of_issue'] = trim($matches[1]);
    }
    
    return $fields;
}

function extractGenericIdFields($text, $side) {
    $fields = [];
    
    // Generic extraction for any ID type
    if (preg_match('/\b(\d{4,20})\b/', $text, $matches)) {
        $fields['id_number'] = $matches[1];
    }
    
    if (preg_match('/(?:name|full name)[\s:]*([A-Z\s,.-]+)/i', $text, $matches)) {
        $fields['name'] = trim($matches[1]);
    }
    
    return $fields;
}

function validateIdDocument($text, $idType, $side) {
    if (empty($text)) {
        return false;
    }
    
    $textLower = strtolower($text);
    
    // Basic validation - must contain some ID-related keywords
    $idKeywords = [
        'republic of the philippines',
        'philippine',
        'identification',
        'id',
        'license',
        'passport',
        'national',
        'government'
    ];
    
    $hasIdKeywords = false;
    foreach ($idKeywords as $keyword) {
        if (strpos($textLower, $keyword) !== false) {
            $hasIdKeywords = true;
            break;
        }
    }
    
    if (!$hasIdKeywords) {
        return false;
    }
    
    // Additional validation based on ID type and side
    if ($idType === 'NATIONAL_ID') {
        if ($side === 'front') {
            // Front should have ID number and name
            return preg_match('/\b\d{4,20}\b/', $text) && 
                   (strpos($textLower, 'name') !== false || strpos($textLower, 'surname') !== false);
        } else {
            // Back should have some identifying information
            return strpos($textLower, 'qr') !== false || 
                   strpos($textLower, 'issue') !== false || 
                   strpos($textLower, 'sex') !== false;
        }
    }
    
    return true; // Default to valid if basic criteria met
}

function calculateConfidence($fields, $idType, $side) {
    $confidence = 0;
    $totalFields = 0;
    
    if ($idType === 'NATIONAL_ID') {
        if ($side === 'front') {
            $expectedFields = ['id_number', 'last_name', 'given_name', 'date_of_birth'];
            $totalFields = count($expectedFields);
            foreach ($expectedFields as $field) {
                if (!empty($fields[$field])) {
                    $confidence += 25;
                }
            }
        } else {
            $expectedFields = ['sex', 'blood_type', 'marital_status', 'place_of_birth'];
            $totalFields = count($expectedFields);
            foreach ($expectedFields as $field) {
                if (!empty($fields[$field])) {
                    $confidence += 25;
                }
            }
        }
    } else {
        // Generic confidence calculation
        $confidence = min(100, count($fields) * 20);
    }
    
    return min(100, $confidence);
}
?>
