<?php
// Start output buffering to catch any unwanted output
ob_start();

// Turn off error reporting to prevent HTML output
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    ob_clean();
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, ngrok-skip-browser-warning, User-Agent, Accept');
    header('Access-Control-Max-Age: 86400');
    http_response_code(200);
    exit;
}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, ngrok-skip-browser-warning, User-Agent, Accept');

$response = array('success' => false, 'maintenance' => array());

try {
    // Database configuration
    $host = 'localhost';
    $dbname = 'boardease2';
    $username = 'boardease';
    $password = 'boardease';

    $conn = new mysqli($host, $username, $password, $dbname);

    if ($conn->connect_error) {
        throw new Exception('Database connection failed: ' . $conn->connect_error);
    }

    // Get JSON input or POST data
    $inputData = [];
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!empty($_POST)) {
            $inputData = $_POST;
        } else {
            $jsonInput = file_get_contents('php://input');
            if (!empty($jsonInput)) {
                $decoded = json_decode($jsonInput, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $inputData = $decoded;
                }
            }
        }
    }

    $user_id = isset($inputData['user_id']) ? intval($inputData['user_id']) : (isset($_GET['user_id']) ? intval($_GET['user_id']) : 0);
    if ($user_id <= 0) {
        throw new Exception('Invalid user_id');
    }

    // Get all maintenance requests for boarding houses owned by this owner (including Declined status)
    $sql = "SELECT 
                mr.request_id,
                mr.subject as issue_type,
                mr.area_for_maintenance,
                mr.mr_description as description,
                mr.mr_status,
                DATE_FORMAT(mr.mr_created_at, '%Y-%m-%d %H:%i:%s') as created_at,
                bh.bh_name as boarding_house_name,
                (SELECT image_path FROM boarding_house_images WHERE bh_id = bh.bh_id LIMIT 1) as boarding_house_image,
                ru.room_number,
                CONCAT(COALESCE(r.first_name, ''), ' ', COALESCE(r.middle_name, ''), ' ', COALESCE(r.last_name, ''), ' ', COALESCE(r.suffix, '')) as boarder_name
            FROM maintenance_requests mr
            LEFT JOIN users u ON mr.user_id = u.user_id
            LEFT JOIN registrations r ON u.reg_id = r.id
            LEFT JOIN room_units ru ON mr.room_id = ru.room_id
            LEFT JOIN boarding_house_rooms bhr ON ru.bhr_id = bhr.bhr_id
            LEFT JOIN boarding_houses bh ON bhr.bh_id = bh.bh_id
            WHERE bh.user_id = ?
            ORDER BY mr.mr_created_at DESC";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception('Database query preparation failed: ' . $conn->error);
    }

    $stmt->bind_param("i", $user_id);
    if (!$stmt->execute()) {
        throw new Exception('Database query execution failed: ' . $stmt->error);
    }

    $result = $stmt->get_result();
    $maintenanceRequests = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Format maintenance requests
    $formattedMaintenance = array();
    foreach ($maintenanceRequests as $mr) {
        // Determine status display
        $status = $mr['mr_status'];
        $statusDisplay = $status; // Keep original status format
        
        $description = $mr['description'] ?? $mr['issue_type'] ?? "Maintenance request";
        if (!empty($mr['area_for_maintenance'])) {
            $description = $mr['area_for_maintenance'] . " - " . $description;
        }
        
        // Get boarding house image URL
        $bh_image = $mr['boarding_house_image'] ?? '';
        $image_url = '';
        if (!empty($bh_image)) {
            // Construct full URL if image path is relative
            if (strpos($bh_image, 'http') === 0) {
                $image_url = $bh_image;
            } else {
                // Format image path similar to other files
                $base_url = 'https://hookiest-unprotecting-cher.ngrok-free.dev/BoardEase2/';
                $cleanPath = ltrim($bh_image, '/');
                // If image_path already contains 'uploads/', use it as is, otherwise prepend 'uploads/'
                if (strpos($cleanPath, 'uploads/') === 0) {
                    $image_url = $base_url . $cleanPath;
                } else {
                    $image_url = $base_url . 'uploads/' . $cleanPath;
                }
            }
        }
        
        $formattedMaintenance[] = array(
            'request_id' => (int)$mr['request_id'],
            'boarding_house_name' => $mr['boarding_house_name'] ?? '',
            'boarding_house_image' => $image_url,
            'boarder_name' => $mr['boarder_name'] ?? '',
            'room_number' => $mr['room_number'] ?? '',
            'issue_type' => $mr['issue_type'] ?? 'Maintenance',
            'created_at' => $mr['created_at'] ?? '',
            'status' => $statusDisplay,
            'description' => $description
        );
    }

    $response['success'] = true;
    $response['maintenance'] = $formattedMaintenance;

    // Close database connection
    $conn->close();

} catch (Exception $e) {
    // Log error
    error_log("Error in get_maintenance_logs.php: " . $e->getMessage());
    
    // Close connection if it exists
    if (isset($conn) && $conn) {
        $conn->close();
    }
    
    $response['success'] = false;
    $response['error'] = 'Server error: ' . $e->getMessage();
}

// Clear any output buffer and output JSON response
ob_clean();
echo json_encode($response, JSON_UNESCAPED_SLASHES);
exit;
?>

