<?php
// Set proper headers first
header('Content-Type: application/json');

// Simple error handling
try {
    // Database connection
   $servername = "localhost";
$username = "boardease";
$password = "boardease";
$database = "boardease2";
    
    $conn = new mysqli($servername, $username, $password, $database);
    
    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    // Check if POST request
    if ($_SERVER["REQUEST_METHOD"] != "POST") {
        throw new Exception("Only POST method allowed");
    }
    
    // Get parameters
    $bh_id = isset($_POST['bh_id']) ? $_POST['bh_id'] : '';
    $image_paths_json = isset($_POST['image_paths']) ? $_POST['image_paths'] : '';
    
    // Validate parameters
    if (empty($bh_id) || empty($image_paths_json)) {
        throw new Exception("Missing required parameters");
    }
    
    // Parse JSON
    $image_paths = json_decode($image_paths_json, true);
    if (!is_array($image_paths)) {
        throw new Exception("Invalid image paths format");
    }
    
    $deleted_count = 0;
    
    // Delete each image
    foreach ($image_paths as $image_path) {
        // Delete from database
        $sql = "DELETE FROM boarding_house_images WHERE bh_id = ? AND image_path = ?";
        $stmt = $conn->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param("is", $bh_id, $image_path);
            if ($stmt->execute()) {
                $deleted_count++;
            }
            $stmt->close();
        }
        
        // Delete physical file
        $full_path = $_SERVER['DOCUMENT_ROOT'] . "/BoardEase2/" . $image_path;
        if (file_exists($full_path)) {
            unlink($full_path);
        }
    }
    
    // Success response
    echo json_encode([
        "success" => true,
        "message" => "Successfully deleted $deleted_count images",
        "deleted_count" => $deleted_count
    ]);
    
} catch (Exception $e) {
    // Error response
    echo json_encode([
        "error" => $e->getMessage()
    ]);
} finally {
    // Close connection if it exists
    if (isset($conn)) {
        $conn->close();
    }
}
?>