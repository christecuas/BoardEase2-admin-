<?php
// Increase upload limits
ini_set('upload_max_filesize', '10M');
ini_set('post_max_size', '10M');
ini_set('max_execution_time', 300);
ini_set('memory_limit', '256M');

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

// Database configuration
$servername = "localhost";
$username = "boardease";
$password = "boardease";
$database = "boardease2";

$conn = new mysqli($servername, $username, $password, $database);

if ($conn->connect_error) {
    die(json_encode(["success" => false, "error" => "Connection failed: " . $conn->connect_error]));
}

// Get parameters from POST request
$user_id = $_POST["user_id"] ?? null;
$profile_picture = $_POST["profile_picture"] ?? null;

// Debug logging
error_log("DEBUG: Upload profile picture parameters:");
error_log("user_id: " . $user_id);
error_log("profile_picture length: " . ($profile_picture ? strlen($profile_picture) : "null"));
error_log("POST size: " . strlen(file_get_contents('php://input')));
error_log("Memory usage: " . memory_get_usage(true) / 1024 / 1024 . " MB");

if (!$user_id || !$profile_picture) {
    echo json_encode(["success" => false, "error" => "User ID and profile picture are required"]);
    exit;
}

// Check if image data is too large (limit to 5MB base64)
if (strlen($profile_picture) > 5 * 1024 * 1024) {
    echo json_encode(["success" => false, "error" => "Image is too large. Please select a smaller image."]);
    exit;
}

try {
    // Decode base64 image
    $imageData = base64_decode($profile_picture);
    
    if ($imageData === false) {
        error_log("DEBUG: Failed to decode base64 image data");
        echo json_encode(["success" => false, "error" => "Invalid image data"]);
        exit;
    }
    
    error_log("DEBUG: Successfully decoded image data, size: " . strlen($imageData) . " bytes");
    
    // Create uploads directory if it doesn't exist
    $uploadDir = "uploads/profile_pictures/";
    if (!file_exists($uploadDir)) {
        if (!mkdir($uploadDir, 0777, true)) {
            error_log("DEBUG: Failed to create upload directory: " . $uploadDir);
            echo json_encode(["success" => false, "error" => "Failed to create upload directory"]);
            exit;
        }
        error_log("DEBUG: Created upload directory: " . $uploadDir);
    }
    
    // Generate unique filename
    $filename = "user_" . $user_id . "_" . uniqid() . ".jpg";
    $filepath = $uploadDir . $filename;
    
    // Save image file
    error_log("DEBUG: Attempting to save file to: " . $filepath);
    if (file_put_contents($filepath, $imageData)) {
        error_log("DEBUG: Successfully saved image file");
        
        // Update database with new profile picture path in users table
        $sql = "UPDATE users SET profile_picture = ? WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $filepath, $user_id);
        
        if ($stmt->execute()) {
            error_log("DEBUG: Successfully updated database with profile picture path");
            
            // Get user information for response
            $userSql = "SELECT r.first_name, r.last_name, r.email 
                       FROM users u 
                       JOIN registrations r ON u.reg_id = r.id 
                       WHERE u.user_id = ?";
            $userStmt = $conn->prepare($userSql);
            $userStmt->bind_param("i", $user_id);
            $userStmt->execute();
            $userResult = $userStmt->get_result();
            $userData = $userResult->fetch_assoc();
            $userStmt->close();
            
            echo json_encode([
                "success" => true,
                "message" => "Profile picture uploaded successfully",
                "profile_picture_path" => $filepath,
                "user_info" => [
                    "user_id" => $user_id,
                    "name" => ($userData ? $userData['first_name'] . " " . $userData['last_name'] : "Unknown"),
                    "email" => ($userData ? $userData['email'] : "Unknown")
                ]
            ]);
        } else {
            // Delete the uploaded file if database update fails
            unlink($filepath);
            error_log("DEBUG: Failed to update database: " . $stmt->error);
            echo json_encode(["success" => false, "error" => "Failed to update database: " . $stmt->error]);
        }
        
        $stmt->close();
    } else {
        error_log("DEBUG: Failed to save image file to: " . $filepath);
        echo json_encode(["success" => false, "error" => "Failed to save image file"]);
    }
    
} catch (Exception $e) {
    echo json_encode(["success" => false, "error" => "Upload error: " . $e->getMessage()]);
}

$conn->close();
?>
