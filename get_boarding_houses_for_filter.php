<?php
/**
 * Get boarding houses for filter dropdowns
 * Returns a simple list of boarding houses for use in report filters
 */

require_once 'dbConfig.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    $connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($connection->connect_error) {
        throw new Exception("Connection failed: " . $connection->connect_error);
    }
    
    $query = "
        SELECT 
            bh.bh_id,
            bh.bh_name
        FROM boarding_houses bh
        WHERE bh.status = 'Active'
        ORDER BY bh.bh_name ASC
    ";
    
    $result = $connection->query($query);
    
    if (!$result) {
        throw new Exception("Query failed: " . $connection->error);
    }
    
    $boardingHouses = [];
    while ($row = $result->fetch_assoc()) {
        $boardingHouses[] = [
            'id' => $row['bh_id'],
            'name' => $row['bh_name']
        ];
    }
    
    $connection->close();
    
    echo json_encode([
        'success' => true,
        'data' => $boardingHouses
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>


