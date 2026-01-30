<?php
// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, ngrok-skip-browser-warning, User-Agent, Accept');
    header('Access-Control-Max-Age: 86400');
    http_response_code(200);
    exit;
}

// Turn off error reporting to prevent HTML output
error_reporting(E_ALL);
ini_set('display_errors', 0);

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
        echo json_encode(array('success' => false, 'error' => 'Database connection failed: ' . $conn->connect_error));
        exit;
    }
} catch (Exception $e) {
    echo json_encode(array('success' => false, 'error' => 'Database connection error: ' . $e->getMessage()));
    exit;
}

// Get JSON input or POST/GET data
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
} else {
    $inputData = $_GET;
}

$user_id = isset($inputData['user_id']) ? intval($inputData['user_id']) : 0;
$user_type = isset($inputData['user_type']) ? trim($inputData['user_type']) : 'owner'; // 'owner' or 'boarder'
$status_filter = isset($inputData['status']) ? trim($inputData['status']) : 'all'; // 'pending', 'in_progress', 'resolved', 'all'
$priority = isset($inputData['priority']) ? trim($inputData['priority']) : 'all';
$type = isset($inputData['type']) ? trim($inputData['type']) : 'all';

if ($user_id <= 0) {
    echo json_encode(array('success' => false, 'error' => 'Invalid user_id'));
    $conn->close();
    exit;
}

try {
    // Build SQL query based on user type
    if (strtolower($user_type) === 'boarder') {
        // For boarders: show their own maintenance requests
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
                bhr.bhr_id,
                bh.bh_id,
                bh.bh_name as boarding_house_name,
                CONCAT(COALESCE(r.first_name, ''), ' ', COALESCE(r.middle_name, ''), ' ', COALESCE(r.last_name, ''), ' ', COALESCE(r.suffix, '')) as boarder_name
            FROM maintenance_requests mr
            LEFT JOIN users u ON mr.user_id = u.user_id
            LEFT JOIN registrations r ON u.reg_id = r.id
            LEFT JOIN room_units ru ON mr.room_id = ru.room_id
            LEFT JOIN boarding_house_rooms bhr ON ru.bhr_id = bhr.bhr_id
            LEFT JOIN boarding_houses bh ON bhr.bh_id = bh.bh_id
            WHERE mr.user_id = ?";
        
        $bind_params = "i";
        $bind_values = array($user_id);
    } else {
        // For owners: show maintenance requests for their boarding houses
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
                bhr.bhr_id,
                bh.bh_id,
                bh.bh_name as boarding_house_name,
                CONCAT(COALESCE(r.first_name, ''), ' ', COALESCE(r.middle_name, ''), ' ', COALESCE(r.last_name, ''), ' ', COALESCE(r.suffix, '')) as boarder_name
            FROM maintenance_requests mr
            LEFT JOIN users u ON mr.user_id = u.user_id
            LEFT JOIN registrations r ON u.reg_id = r.id
            LEFT JOIN room_units ru ON mr.room_id = ru.room_id
            LEFT JOIN boarding_house_rooms bhr ON ru.bhr_id = bhr.bhr_id
            LEFT JOIN boarding_houses bh ON bhr.bh_id = bh.bh_id
            WHERE bh.user_id = ?";
        
        $bind_params = "i";
        $bind_values = array($user_id);
    }

    // Add status filter
    if ($status_filter !== 'all') {
        // Normalize status values
        $status_mapping = array(
            'pending' => 'Pending',
            'in_progress' => 'In Progress',
            'in progress' => 'In Progress',
            'resolved' => 'Resolved',
            'completed' => 'Resolved',
            'declined' => 'Declined',
            'rejected' => 'Declined'
        );
        
        $status_value = isset($status_mapping[strtolower($status_filter)]) 
            ? $status_mapping[strtolower($status_filter)] 
            : ucfirst($status_filter);
        
        $sql .= " AND mr.mr_status = ?";
        $bind_params .= "s";
        $bind_values[] = $status_value;
    }

    $sql .= " ORDER BY mr.mr_created_at DESC";

    // Prepare and execute query
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception('SQL prepare failed: ' . $conn->error);
    }

    // Bind parameters dynamically using call_user_func_array
    if (count($bind_values) > 0) {
        $params = array($bind_params);
        // Create references for bind_param
        foreach ($bind_values as $key => $value) {
            $params[] = &$bind_values[$key];
        }
        call_user_func_array(array($stmt, 'bind_param'), $params);
    }

    if (!$stmt->execute()) {
        throw new Exception('SQL execution failed: ' . $stmt->error);
    }

    $result = $stmt->get_result();

    if (!$result) {
        throw new Exception('SQL get result failed: ' . $conn->error);
    }

    $maintenanceRequests = $result->fetch_all(MYSQLI_ASSOC);

    // Format maintenance requests for response
    $formattedRequests = array();
    foreach ($maintenanceRequests as $mr) {
        // Normalize status for display
        $status = $mr['mr_status'];
        $statusDisplay = $status; // Keep original format
        
        // Build description
        $description = $mr['mr_description'] ?? '';
        if (!empty($mr['area_for_maintenance'])) {
            $description = $mr['area_for_maintenance'] . " - " . $description;
        }
        if (empty($description)) {
            $description = $mr['subject'] ?? 'Maintenance request';
        }
        
        // Get approved date (when status changed to "In Progress")
        // Check if mr_approved_at column exists and use it, otherwise fallback to created_at
        $approved_date = '';
        if (isset($mr['mr_approved_at']) && !empty($mr['mr_approved_at'])) {
            $approved_date = $mr['mr_approved_at'];
        } elseif (($status === 'In Progress' || $statusDisplay === 'In Progress')) {
            // Fallback: If status is In Progress but no approved_at, use created_at
            $approved_date = $mr['mr_created_at'] ?? '';
        }
        
        // Get completed date (when status changed to "Resolved")
        $completed_date = '';
        if (isset($mr['mr_completed_at']) && !empty($mr['mr_completed_at'])) {
            $completed_date = $mr['mr_completed_at'];
        }
        
        $formattedRequests[] = array(
            'request_id' => (int)$mr['request_id'],
            'maintenance_id' => (int)$mr['request_id'], // For compatibility
            'user_id' => (int)$mr['user_id'],
            'room_id' => isset($mr['room_id']) ? (int)$mr['room_id'] : null,
            'boarder_name' => $mr['boarder_name'] ?? '',
            'boarding_house_name' => $mr['boarding_house_name'] ?? '',
            'room_number' => $mr['room_number'] ?? '',
            'maintenance_type' => $mr['subject'] ?? '',
            'title' => $mr['subject'] ?? '',
            'description' => $description,
            'area_for_maintenance' => $mr['area_for_maintenance'] ?? '',
            'request_date' => $mr['mr_created_at'] ?? '',
            'created_at' => $mr['mr_created_at'] ?? '',
            'approved_date' => $approved_date,
            'completed_date' => $completed_date,
            'work_completed_date' => $completed_date, // For compatibility with Android
            'status' => $statusDisplay,
            'priority' => '', // Not in table structure, keep empty
            'location' => $mr['area_for_maintenance'] ?? ''
        );
    }

    $response = array(
        'success' => true,
        'maintenance_requests' => $formattedRequests
    );

    echo json_encode($response, JSON_UNESCAPED_SLASHES);

    $stmt->close();
    $conn->close();

} catch (Exception $e) {
    // Catch any exceptions and return as JSON
    if (isset($stmt) && $stmt) {
        $stmt->close();
    }
    if (isset($conn) && $conn) {
        $conn->close();
    }
    echo json_encode(array('success' => false, 'error' => 'Error: ' . $e->getMessage()));
}
?>

