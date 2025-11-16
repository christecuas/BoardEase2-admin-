<?php
// simple_qr_detector.php - Simple QR Code detection for image validation

// Disable error display to prevent HTML output
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Set content type to JSON
header('Content-Type: application/json');

// Log the request for debugging
error_log("Simple QR Code detection request received at " . date('Y-m-d H:i:s'));

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
    
    error_log("Processing uploaded file: " . $originalName);

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

    // Check file size (max 5MB)
    if ($uploadedFile['size'] > 5 * 1024 * 1024) {
        $response = array(
            "success" => false,
            "message" => "File too large. Please upload an image smaller than 5MB."
        );
        echo json_encode($response);
        exit;
    }

    // Detect QR code using image analysis
    $qrDetected = detectQRCode($tempPath);
    
    if ($qrDetected['success']) {
        $response = array(
            "success" => true,
            "message" => "QR code detected successfully!",
            "confidence" => $qrDetected['confidence'],
            "file_info" => array(
                "name" => $originalName,
                "type" => $fileType,
                "size" => $uploadedFile['size']
            )
        );
        error_log("QR Code validation SUCCESS for file: " . $originalName . " (confidence: " . $qrDetected['confidence'] . "%)");
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
 * Detect QR code in image using basic image analysis
 */
function detectQRCode($imagePath) {
    try {
        // Get image info
        $imageInfo = getimagesize($imagePath);
        if (!$imageInfo) {
            return array('success' => false, 'confidence' => 0);
        }
        
        $width = $imageInfo[0];
        $height = $imageInfo[1];
        
        // Load image
        $image = loadImage($imagePath, $imageInfo[2]);
        if (!$image) {
            return array('success' => false, 'confidence' => 0);
        }
        
        // Analyze image for QR code patterns
        $confidence = analyzeQRPatterns($image, $width, $height);
        
        // Clean up
        imagedestroy($image);
        
        // Consider it a QR code if confidence is above 30%
        if ($confidence > 30) {
            return array('success' => true, 'confidence' => round($confidence));
        } else {
            return array('success' => false, 'confidence' => round($confidence));
        }
        
    } catch (Exception $e) {
        error_log("QR detection error: " . $e->getMessage());
        return array('success' => false, 'confidence' => 0);
    }
}

/**
 * Load image based on type
 */
function loadImage($path, $type) {
    switch ($type) {
        case IMAGETYPE_JPEG:
            return imagecreatefromjpeg($path);
        case IMAGETYPE_PNG:
            return imagecreatefrompng($path);
        case IMAGETYPE_GIF:
            return imagecreatefromgif($path);
        default:
            return false;
    }
}

/**
 * Analyze image for QR code patterns
 */
function analyzeQRPatterns($image, $width, $height) {
    $totalScore = 0;
    $checks = 0;
    
    // Check 1: High contrast areas (QR codes have high contrast)
    $contrastScore = checkContrast($image, $width, $height);
    $totalScore += $contrastScore * 0.3;
    $checks++;
    
    // Check 2: Square patterns (QR codes have square modules)
    $squareScore = checkSquarePatterns($image, $width, $height);
    $totalScore += $squareScore * 0.3;
    $checks++;
    
    // Check 3: Edge density (QR codes have many edges)
    $edgeScore = checkEdgeDensity($image, $width, $height);
    $totalScore += $edgeScore * 0.2;
    $checks++;
    
    // Check 4: Color distribution (QR codes are mostly black and white)
    $colorScore = checkColorDistribution($image, $width, $height);
    $totalScore += $colorScore * 0.2;
    $checks++;
    
    return $totalScore;
}

/**
 * Check contrast in image
 */
function checkContrast($image, $width, $height) {
    $sampleSize = max(1, min($width, $height) / 50);
    $highContrastPixels = 0;
    $totalPixels = 0;
    
    for ($x = 0; $x < $width; $x += $sampleSize) {
        for ($y = 0; $y < $height; $y += $sampleSize) {
            $totalPixels++;
            
            $pixel = imagecolorat($image, $x, $y);
            $r = ($pixel >> 16) & 0xFF;
            $g = ($pixel >> 8) & 0xFF;
            $b = $pixel & 0xFF;
            $brightness = ($r + $g + $b) / 3;
            
            // Check contrast with surrounding pixels
            $maxContrast = 0;
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
                        $maxContrast = max($maxContrast, $contrast);
                    }
                }
            }
            
            if ($maxContrast > 0.5) {
                $highContrastPixels++;
            }
        }
    }
    
    return $totalPixels > 0 ? ($highContrastPixels / $totalPixels) * 100 : 0;
}

/**
 * Check for square patterns
 */
function checkSquarePatterns($image, $width, $height) {
    $squarePatterns = 0;
    $totalChecks = 0;
    
    $moduleSize = max(1, min($width, $height) / 100);
    
    for ($x = 0; $x < $width - $moduleSize; $x += $moduleSize) {
        for ($y = 0; $y < $height - $moduleSize; $y += $moduleSize) {
            $totalChecks++;
            
            // Check if this area has square-like patterns
            if (hasSquarePattern($image, $x, $y, $moduleSize, $width, $height)) {
                $squarePatterns++;
            }
        }
    }
    
    return $totalChecks > 0 ? ($squarePatterns / $totalChecks) * 100 : 0;
}

/**
 * Check if area has square pattern
 */
function hasSquarePattern($image, $x, $y, $size, $width, $height) {
    $blackPixels = 0;
    $whitePixels = 0;
    
    for ($px = $x; $px < $x + $size && $px < $width; $px++) {
        for ($py = $y; $py < $y + $size && $py < $height; $py++) {
            $pixel = imagecolorat($image, $px, $py);
            $r = ($pixel >> 16) & 0xFF;
            $g = ($pixel >> 8) & 0xFF;
            $b = $pixel & 0xFF;
            $brightness = ($r + $g + $b) / 3;
            
            if ($brightness < 128) {
                $blackPixels++;
            } else {
                $whitePixels++;
            }
        }
    }
    
    // Check if there's a good mix of black and white (characteristic of QR codes)
    $totalPixels = $blackPixels + $whitePixels;
    if ($totalPixels == 0) return false;
    
    $blackRatio = $blackPixels / $totalPixels;
    return $blackRatio > 0.2 && $blackRatio < 0.8; // Not too black, not too white
}

/**
 * Check edge density
 */
function checkEdgeDensity($image, $width, $height) {
    $edges = 0;
    $totalPixels = 0;
    
    $sampleSize = max(1, min($width, $height) / 100);
    
    for ($x = 0; $x < $width - 1; $x += $sampleSize) {
        for ($y = 0; $y < $height - 1; $y += $sampleSize) {
            $totalPixels++;
            
            $pixel = imagecolorat($image, $x, $y);
            $r = ($pixel >> 16) & 0xFF;
            $g = ($pixel >> 8) & 0xFF;
            $b = $pixel & 0xFF;
            $brightness = ($r + $g + $b) / 3;
            
            // Check right neighbor
            $rightPixel = imagecolorat($image, $x + 1, $y);
            $rr = ($rightPixel >> 16) & 0xFF;
            $rg = ($rightPixel >> 8) & 0xFF;
            $rb = $rightPixel & 0xFF;
            $rightBrightness = ($rr + $rg + $rb) / 3;
            
            // Check bottom neighbor
            $bottomPixel = imagecolorat($image, $x, $y + 1);
            $br = ($bottomPixel >> 16) & 0xFF;
            $bg = ($bottomPixel >> 8) & 0xFF;
            $bb = $bottomPixel & 0xFF;
            $bottomBrightness = ($br + $bg + $bb) / 3;
            
            if (abs($brightness - $rightBrightness) > 50 || abs($brightness - $bottomBrightness) > 50) {
                $edges++;
            }
        }
    }
    
    return $totalPixels > 0 ? ($edges / $totalPixels) * 100 : 0;
}

/**
 * Check color distribution (QR codes are mostly black and white)
 */
function checkColorDistribution($image, $width, $height) {
    $blackPixels = 0;
    $whitePixels = 0;
    $colorPixels = 0;
    $totalPixels = 0;
    
    $sampleSize = max(1, min($width, $height) / 50);
    
    for ($x = 0; $x < $width; $x += $sampleSize) {
        for ($y = 0; $y < $height; $y += $sampleSize) {
            $totalPixels++;
            
            $pixel = imagecolorat($image, $x, $y);
            $r = ($pixel >> 16) & 0xFF;
            $g = ($pixel >> 8) & 0xFF;
            $b = $pixel & 0xFF;
            
            $brightness = ($r + $g + $b) / 3;
            $colorVariance = max($r, $g, $b) - min($r, $g, $b);
            
            if ($brightness < 80) {
                $blackPixels++;
            } else if ($brightness > 175 && $colorVariance < 30) {
                $whitePixels++;
            } else {
                $colorPixels++;
            }
        }
    }
    
    if ($totalPixels == 0) return 0;
    
    $blackWhiteRatio = ($blackPixels + $whitePixels) / $totalPixels;
    $colorRatio = $colorPixels / $totalPixels;
    
    // Higher score for more black/white, less color
    return ($blackWhiteRatio * 100) - ($colorRatio * 50);
}
?>
