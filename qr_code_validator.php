<?php
// qr_code_validator.php - QR Code detection and validation

/**
 * Check if an image file contains a QR code
 * @param string $filePath Path to the image file
 * @return bool True if QR code is detected, false otherwise
 */
function containsQRCode($filePath) {
    if (!file_exists($filePath)) {
        error_log("QR validation failed: File does not exist - " . $filePath);
        return false;
    }
    
    // Get file info
    $fileInfo = pathinfo($filePath);
    $extension = strtolower($fileInfo['extension']);
    
    // Check if it's a supported image format
    $supportedFormats = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];
    if (!in_array($extension, $supportedFormats)) {
        error_log("QR validation failed: Unsupported file format - " . $extension);
        return false;
    }
    
    // Method 1: Try using ZXing library (if available)
    if (function_exists('shell_exec') && is_executable('/usr/bin/zxing')) {
        $result = shell_exec("/usr/bin/zxing -q " . escapeshellarg($filePath) . " 2>/dev/null");
        if (!empty(trim($result))) {
            error_log("QR validation success: ZXing detected QR code in " . $filePath);
            return true;
        }
    }
    
    // Method 2: Try using qrdecode command (if available)
    if (function_exists('shell_exec') && is_executable('/usr/bin/qrdecode')) {
        $result = shell_exec("/usr/bin/qrdecode " . escapeshellarg($filePath) . " 2>/dev/null");
        if (!empty(trim($result))) {
            error_log("QR validation success: qrdecode detected QR code in " . $filePath);
            return true;
        }
    }
    
    // Method 3: Basic image analysis (fallback method)
    // This is a simple heuristic - look for square patterns that might indicate QR codes
    $image = null;
    
    switch ($extension) {
        case 'jpg':
        case 'jpeg':
            $image = imagecreatefromjpeg($filePath);
            break;
        case 'png':
            $image = imagecreatefrompng($filePath);
            break;
        case 'gif':
            $image = imagecreatefromgif($filePath);
            break;
        case 'bmp':
            $image = imagecreatefrombmp($filePath);
            break;
        case 'webp':
            if (function_exists('imagecreatefromwebp')) {
                $image = imagecreatefromwebp($filePath);
            }
            break;
    }
    
    if (!$image) {
        error_log("QR validation failed: Could not create image resource from " . $filePath);
        return false;
    }
    
    // Get image dimensions
    $width = imagesx($image);
    $height = imagesy($image);
    
    // QR codes are typically square, so check aspect ratio
    $aspectRatio = $width / $height;
    $isSquareish = ($aspectRatio >= 0.8 && $aspectRatio <= 1.25);
    
    // QR codes have specific size requirements (minimum 21x21 modules)
    $minSize = 21;
    $isLargeEnough = ($width >= $minSize && $height >= $minSize);
    
    // Basic pattern detection - look for high contrast areas
    $hasHighContrast = false;
    $sampleSize = min(50, $width, $height);
    $step = max(1, min($width, $height) / $sampleSize);
    
    $blackPixels = 0;
    $whitePixels = 0;
    $totalPixels = 0;
    
    for ($x = 0; $x < $width; $x += $step) {
        for ($y = 0; $y < $height; $y += $step) {
            $rgb = imagecolorat($image, $x, $y);
            $r = ($rgb >> 16) & 0xFF;
            $g = ($rgb >> 8) & 0xFF;
            $b = $rgb & 0xFF;
            
            // Convert to grayscale
            $gray = ($r + $g + $b) / 3;
            
            if ($gray < 128) {
                $blackPixels++;
            } else {
                $whitePixels++;
            }
            $totalPixels++;
        }
    }
    
    $blackRatio = $blackPixels / $totalPixels;
    $hasHighContrast = ($blackRatio > 0.1 && $blackRatio < 0.9);
    
    imagedestroy($image);
    
    // Combine heuristics
    $likelyQRCode = $isSquareish && $isLargeEnough && $hasHighContrast;
    
    if ($likelyQRCode) {
        error_log("QR validation success: Heuristic analysis suggests QR code in " . $filePath);
        error_log("  - Aspect ratio: " . round($aspectRatio, 2) . " (squareish: " . ($isSquareish ? 'yes' : 'no') . ")");
        error_log("  - Size: {$width}x{$height} (large enough: " . ($isLargeEnough ? 'yes' : 'no') . ")");
        error_log("  - Contrast: " . round($blackRatio, 2) . " (good: " . ($hasHighContrast ? 'yes' : 'no') . ")");
    } else {
        error_log("QR validation failed: Heuristic analysis suggests no QR code in " . $filePath);
        error_log("  - Aspect ratio: " . round($aspectRatio, 2) . " (squareish: " . ($isSquareish ? 'yes' : 'no') . ")");
        error_log("  - Size: {$width}x{$height} (large enough: " . ($isLargeEnough ? 'yes' : 'no') . ")");
        error_log("  - Contrast: " . round($blackRatio, 2) . " (good: " . ($hasHighContrast ? 'yes' : 'no') . ")");
    }
    
    return $likelyQRCode;
}

/**
 * Validate QR code file with detailed error messages
 * @param string $filePath Path to the image file
 * @return array Array with 'valid' boolean and 'message' string
 */
function validateQRCodeFile($filePath) {
    if (!file_exists($filePath)) {
        return [
            'valid' => false,
            'message' => 'File does not exist.'
        ];
    }
    
    // Check file size (allow up to 10MB)
    $maxSize = 10 * 1024 * 1024; // 10MB
    $fileSize = filesize($filePath);
    
    if ($fileSize > $maxSize) {
        return [
            'valid' => false,
            'message' => 'File is too large. Maximum size is 10MB.'
        ];
    }
    
    if ($fileSize < 1024) { // Less than 1KB
        return [
            'valid' => false,
            'message' => 'File is too small. Please upload a valid image.'
        ];
    }
    
    // Check if it's a valid image
    $imageInfo = getimagesize($filePath);
    if ($imageInfo === false) {
        return [
            'valid' => false,
            'message' => 'File is not a valid image format.'
        ];
    }
    
    // Check image dimensions
    $width = $imageInfo[0];
    $height = $imageInfo[1];
    
    if ($width < 50 || $height < 50) {
        return [
            'valid' => false,
            'message' => 'Image is too small. Minimum size is 50x50 pixels.'
        ];
    }
    
    // Check for QR code
    if (!containsQRCode($filePath)) {
        return [
            'valid' => false,
            'message' => 'No QR code detected in the image. Please upload an image containing a valid QR code.'
        ];
    }
    
    return [
        'valid' => true,
        'message' => 'QR code file is valid.'
    ];
}

/**
 * Test QR code validation with sample files
 */
function testQRValidation() {
    echo "=== QR CODE VALIDATION TEST ===\n\n";
    
    $testFiles = [
        'test_qr_valid.jpg' => 'Should detect QR code',
        'test_no_qr.jpg' => 'Should reject (no QR code)',
        'test_small.jpg' => 'Should reject (too small)',
        'test_large.jpg' => 'Should reject (too large)'
    ];
    
    foreach ($testFiles as $file => $description) {
        echo "Testing: $file - $description\n";
        if (file_exists($file)) {
            $result = validateQRCodeFile($file);
            echo "  Result: " . ($result['valid'] ? 'VALID' : 'INVALID') . "\n";
            echo "  Message: " . $result['message'] . "\n";
        } else {
            echo "  File not found\n";
        }
        echo "---\n";
    }
}
?>
