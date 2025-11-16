<?php
// Get all boarding houses for public/guest view (no user_id required)
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors in output, only log them
ini_set('log_errors', 1);
ini_set('error_log', 'php_errors.log');

// Start output buffering
ob_start();

require_once 'db_helper.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, ngrok-skip-browser-warning');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit;
}

error_log("=== GET_ALL_BOARDING_HOUSES_PUBLIC.PHP START ===");
error_log("Request method: " . $_SERVER['REQUEST_METHOD']);

try {
    $db = getDB();
    error_log("Database connection successful");
    
    // Get all boarding houses (for guests/public view)
    $sql = "
        SELECT DISTINCT
            bh.bh_id,
            bh.bh_name,
            bh.bh_address,
            bh.bh_description,
            bh.bh_rules,
            bh.number_of_bathroom,
            bh.area,
            bh.build_year,
            (SELECT image_path FROM boarding_house_images WHERE bh_id = bh.bh_id LIMIT 1) as image_path
        FROM boarding_houses bh
        ORDER BY bh.bh_id DESC
    ";
    
    error_log("SQL Query: " . $sql);
    $stmt = $db->prepare($sql);
    $stmt->execute();
    
    $boarding_houses = $stmt->fetchAll();
    error_log("Found " . count($boarding_houses) . " boarding houses");
    
    // Format response
    $formatted_houses = [];
    foreach ($boarding_houses as $house) {
        // Get all images for this boarding house
        $images_sql = "SELECT image_path FROM boarding_house_images WHERE bh_id = ?";
        $images_stmt = $db->prepare($images_sql);
        $images_stmt->execute([$house['bh_id']]);
        $images = $images_stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $formatted_house = [
            'bh_id' => (int)$house['bh_id'],
            'bh_name' => $house['bh_name'] ?? '',
            'bh_address' => $house['bh_address'] ?? '',
            'bh_description' => $house['bh_description'] ?? '',
            'bh_rules' => $house['bh_rules'] ?? '',
            'number_of_bathroom' => $house['number_of_bathroom'] ?? '',
            'area' => $house['area'] ?? '',
            'build_year' => $house['build_year'] ?? '',
            'image_path' => $house['image_path'] ?? '',
            'images' => $images
        ];
        
        $formatted_houses[] = $formatted_house;
    }
    
    // Return as JSON array (for compatibility with existing Android code)
    error_log("Returning " . count($formatted_houses) . " boarding houses");
    
    // Ensure output is clean
    ob_clean();
    echo json_encode($formatted_houses);
    
} catch (PDOException $e) {
    error_log("PDO EXCEPTION: " . $e->getMessage());
    error_log("Exception trace: " . $e->getTraceAsString());
    
    ob_clean();
    // Return empty array on error (for compatibility)
    echo json_encode([]);
} catch (Exception $e) {
    error_log("EXCEPTION: " . $e->getMessage());
    error_log("Exception trace: " . $e->getTraceAsString());
    
    ob_clean();
    // Return empty array on error (for compatibility)
    echo json_encode([]);
}

error_log("=== GET_ALL_BOARDING_HOUSES_PUBLIC.PHP END ===");
exit;
?>

