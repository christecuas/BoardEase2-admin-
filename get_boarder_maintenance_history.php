<?php
// Turn off error reporting to prevent HTML errors
error_reporting(0);
ini_set('display_errors', 0);

// Prevent any output before JSON
if (ob_get_level()) {
    ob_end_clean();
}
ob_start();

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    ob_clean();
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, ngrok-skip-browser-warning, User-Agent, Accept');
    header('Access-Control-Max-Age: 86400');
    http_response_code(200);
    ob_end_flush();
    exit;
}

// Clean any output that might have been generated
ob_clean();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, ngrok-skip-browser-warning, User-Agent, Accept');

// Database configuration
define('DB_HOST', '');
define('DB_USER', 'u223444398_userboardease');
define('DB_PASS', '!Boardease2026');
define('DB_NAME', 'u223444398_boardease');

$host = DB_HOST;
$dbname = DB_NAME;
$username = DB_USER;
$password = DB_PASS;

try {
    $conn = new mysqli($host, $username, $password, $dbname);
    
    if ($conn->connect_error) {
        ob_clean();
        echo json_encode(array('success' => false, 'error' => 'Database connection failed'));
        ob_end_flush();
        exit;
    }
} catch (Exception $e) {
    ob_clean();
    echo json_encode(array('success' => false, 'error' => 'Database connection failed'));
    ob_end_flush();
    exit;
}

$response = array('success' => false, 'maintenance_requests' => array());

try {
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
    $filter_status = isset($inputData['filter_status']) ? $inputData['filter_status'] : (isset($_GET['filter_status']) ? $_GET['filter_status'] : 'all');
    $sort_by = isset($inputData['sort_by']) ? $inputData['sort_by'] : (isset($_GET['sort_by']) ? $_GET['sort_by'] : 'newest');
    
    if ($user_id <= 0) {
        ob_clean();
        echo json_encode(array('success' => false, 'error' => 'Invalid user_id'));
        ob_end_flush();
        exit;
    }

    // Build SQL query based on filter status
    $sql = "SELECT 
                mr.request_id,
                mr.user_id,
                mr.room_id,
                mr.subject,
                mr.area_for_maintenance,
                mr.mr_description,
                mr.mr_status,
                DATE_FORMAT(mr.mr_created_at, '%Y-%m-%d %H:%i:%s') as mr_created_at,
                DATE_FORMAT(mr.mr_approved_at, '%Y-%m-%d %H:%i:%s') as mr_approved_at,
                DATE_FORMAT(mr.mr_completed_at, '%Y-%m-%d %H:%i:%s') as mr_completed_at,
                ru.room_number,
                bhr.room_name,
                bh.bh_id,
                bh.bh_name as boarding_house_name,
                (SELECT image_path FROM boarding_house_images WHERE bh_id = bh.bh_id LIMIT 1) as bh_image_path
            FROM maintenance_requests mr
            LEFT JOIN room_units ru ON mr.room_id = ru.room_id
            LEFT JOIN boarding_house_rooms bhr ON ru.bhr_id = bhr.bhr_id
            LEFT JOIN boarding_houses bh ON bhr.bh_id = bh.bh_id
            WHERE mr.user_id = ?";
    
    // Add status filter
    if ($filter_status !== 'all') {
        // Normalize status values
        $status_mapping = array(
            'pending' => 'Pending',
            'in_progress' => 'In Progress',
            'in progress' => 'In Progress',
            'resolved' => 'Resolved',
            'completed' => 'Resolved',
            'declined' => 'Declined'
        );
        
        $status_value = isset($status_mapping[strtolower($filter_status)]) 
            ? $status_mapping[strtolower($filter_status)] 
            : ucfirst($filter_status);
        
        $sql .= " AND mr.mr_status = ?";
    }

    // Add sorting
    switch ($sort_by) {
        case 'oldest':
            $sql .= " ORDER BY mr.mr_created_at ASC";
            break;
        case 'newest':
        default:
            $sql .= " ORDER BY mr.mr_created_at DESC";
            break;
    }

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        $errorMsg = $conn->error ? $conn->error : 'Unknown error';
        throw new Exception('Database query preparation failed: ' . $errorMsg);
    }

    // Bind parameters
    if ($filter_status !== 'all') {
        $stmt->bind_param("is", $user_id, $status_value);
    } else {
        $stmt->bind_param("i", $user_id);
    }

    if (!$stmt->execute()) {
        $errorMsg = $stmt->error ? $stmt->error : 'Unknown error';
        $stmt->close();
        throw new Exception('Database query execution failed: ' . $errorMsg);
    }

    $result = $stmt->get_result();
    if (!$result) {
        $stmt->close();
        throw new Exception('Failed to get query result');
    }
    
    $maintenance_requests = array();
    $total_rows = $result->num_rows;
    
    // Log for debugging (can be removed in production)
    error_log("Maintenance History Query - Total rows found: " . $total_rows . " for user_id: " . $user_id . " with filter: " . $filter_status);
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            // Build room info
            $roomInfo = '';
            if (!empty($row['room_name'])) {
                $roomInfo = $row['room_name'];
                if (!empty($row['room_number'])) {
                    $roomInfo .= ' - ' . $row['room_number'];
                }
            } else if (!empty($row['room_number'])) {
                $roomInfo = $row['room_number'];
            }

            // Build image URL
            $bhImageUrl = '';
            if (!empty($row['bh_image_path'])) {
                $bhImageUrl = $row['bh_image_path'];
            }

            $maintenance_requests[] = array(
                'request_id' => (int)$row['request_id'],
                'user_id' => (int)$row['user_id'],
                'room_id' => isset($row['room_id']) && $row['room_id'] !== null ? (int)$row['room_id'] : null,
                'subject' => $row['subject'] ?? '',
                'area_for_maintenance' => $row['area_for_maintenance'] ?? '',
                'description' => $row['mr_description'] ?? '',
                'status' => $row['mr_status'] ?? 'Pending',
                'created_at' => $row['mr_created_at'] ?? '',
                'approved_at' => $row['mr_approved_at'] ?? '',
                'completed_at' => $row['mr_completed_at'] ?? '',
                'room_number' => $row['room_number'] ?? '',
                'room_name' => $row['room_name'] ?? '',
                'room_info' => $roomInfo,
                'boarding_house_name' => $row['boarding_house_name'] ?? '',
                'bh_image_url' => $bhImageUrl
            );
        }
    }
    $stmt->close();
    
    // Log for debugging
    error_log("Maintenance History - Returning " . count($maintenance_requests) . " requests");

    $response = array(
        'success' => true,
        'maintenance_requests' => $maintenance_requests
    );

    ob_clean();
    echo json_encode($response, JSON_UNESCAPED_SLASHES);
    ob_end_flush();

} catch (Exception $e) {
    ob_clean();
    echo json_encode(array('success' => false, 'error' => 'Error: ' . $e->getMessage()));
    ob_end_flush();
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>

