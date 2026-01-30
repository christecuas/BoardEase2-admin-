<?php
// Update Maintenance Status API - Updates maintenance request status and details
// Handle preflight OPTIONS request for CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, ngrok-skip-browser-warning, User-Agent, Accept');
    header('Access-Control-Max-Age: 86400');
    http_response_code(200);
    exit();
}

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, ngrok-skip-browser-warning, User-Agent, Accept");

// Turn off error reporting to prevent HTML output
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Database configuration
define('DB_HOST', '');
define('DB_USER', 'u223444398_userboardease');
define('DB_PASS', '!Boardease2026');
define('DB_NAME', 'u223444398_boardease');

$host = DB_HOST;
$dbname = DB_NAME;
$username = DB_USER;
$password = DB_PASS;

$response = [];

try {
    // Create database connection
    $conn = new mysqli($host, $username, $password, $dbname);
    
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }
    
    // Get JSON input
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true);
    
    // If JSON decode failed, try to get from POST
    if ($input === null && !empty($_POST)) {
        $input = $_POST;
    }
    
    // Get parameters - handle both maintenance_id (from Android) and request_id
    $maintenance_id = isset($input['maintenance_id']) ? intval($input['maintenance_id']) : 0;
    $request_id = isset($input['request_id']) ? intval($input['request_id']) : 0;
    $status = isset($input['status']) ? trim($input['status']) : '';
    $assigned_to = isset($input['assigned_to']) ? (empty($input['assigned_to']) ? null : intval($input['assigned_to'])) : null;
    $estimated_cost = isset($input['estimated_cost']) ? (empty($input['estimated_cost']) ? null : floatval($input['estimated_cost'])) : null;
    $actual_cost = isset($input['actual_cost']) ? (empty($input['actual_cost']) ? null : floatval($input['actual_cost'])) : null;
    $work_started_date = isset($input['work_started_date']) ? trim($input['work_started_date']) : null;
    $work_completed_date = isset($input['work_completed_date']) ? trim($input['work_completed_date']) : null;
    $notes = isset($input['notes']) ? trim($input['notes']) : '';
    $updated_by = isset($input['updated_by']) ? intval($input['updated_by']) : 0;
    
    // Use maintenance_id if provided, otherwise use request_id
    $request_id_to_use = $maintenance_id > 0 ? $maintenance_id : $request_id;
    
    if ($request_id_to_use <= 0) {
        throw new Exception("Invalid maintenance_id or request_id");
    }
    
    // Map status values to database enum values
    // Database has: 'Declined', 'Pending', 'In Progress', 'Resolved'
    // Android sends: 'In Progress', 'Rejected', etc.
    $status_mapping = array(
        'Pending' => 'Pending',
        'pending' => 'Pending',
        'In Progress' => 'In Progress',
        'in progress' => 'In Progress',
        'in_progress' => 'In Progress',
        'Completed' => 'Resolved',
        'completed' => 'Resolved',
        'Resolved' => 'Resolved',
        'resolved' => 'Resolved',
        'Rejected' => 'Declined', // Map Rejected to Declined
        'rejected' => 'Declined',
        'Declined' => 'Declined',
        'declined' => 'Declined',
        'Cancelled' => 'Declined', // Map Cancelled to Declined
        'cancelled' => 'Declined',
        'On Hold' => 'In Progress', // Map On Hold to In Progress
        'on hold' => 'In Progress'
    );
    
    $db_status = '';
    $valid_statuses = array('Declined', 'Pending', 'In Progress', 'Resolved');
    
    if (!empty($status)) {
        $status_lower = strtolower(trim($status));
        if (isset($status_mapping[$status])) {
            $db_status = $status_mapping[$status];
        } elseif (isset($status_mapping[$status_lower])) {
            $db_status = $status_mapping[$status_lower];
        } else {
            // Default to In Progress if status is provided but not recognized
            $db_status = 'In Progress';
        }
        
        // Validate that the mapped status is in the valid enum values
        if (!in_array($db_status, $valid_statuses)) {
            throw new Exception("Invalid status: " . $db_status . ". Must be one of: " . implode(', ', $valid_statuses));
        }
    }
    
    if (empty($db_status) && empty($notes) && $assigned_to === null && $estimated_cost === null && $actual_cost === null && $work_started_date === null && $work_completed_date === null) {
        throw new Exception("No fields to update");
    }
    
    // Start transaction
    $conn->begin_transaction();
    $response_sent = false; // Track if response was already sent
    
    try {
        // First, get maintenance request details
        $stmt = $conn->prepare("
            SELECT 
                mr.request_id,
                mr.user_id,
                mr.room_id,
                mr.subject,
                mr.area_for_maintenance,
                mr.mr_description,
                mr.mr_status as current_status,
                CONCAT(COALESCE(r.first_name, ''), ' ', COALESCE(r.middle_name, ''), ' ', COALESCE(r.last_name, ''), 
                       CASE WHEN r.suffix IS NOT NULL AND r.suffix != '' THEN CONCAT(' ', r.suffix) ELSE '' END) as boarder_name,
                COALESCE(ru.room_number, '') as room_number,
                COALESCE(bh.bh_name, '') as boarding_house_name
            FROM maintenance_requests mr
            LEFT JOIN users u ON mr.user_id = u.user_id
            LEFT JOIN registrations r ON u.reg_id = r.id
            LEFT JOIN room_units ru ON mr.room_id = ru.room_id
            LEFT JOIN boarding_house_rooms bhr ON ru.bhr_id = bhr.bhr_id
            LEFT JOIN boarding_houses bh ON bhr.bh_id = bh.bh_id
            WHERE mr.request_id = ?
        ");
        
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param("i", $request_id_to_use);
        $stmt->execute();
        $result = $stmt->get_result();
        $maintenance = $result->fetch_assoc();
        $stmt->close();
        
        if (!$maintenance) {
            throw new Exception("Maintenance request not found");
        }
        
        // Build update query dynamically based on provided fields
        $update_fields = [];
        $update_values = [];
        $types = '';
        
        // Get current status before update
        $current_status = $maintenance['current_status'];
        
        if (!empty($db_status)) {
            $update_fields[] = "mr_status = ?";
            $update_values[] = $db_status;
            $types .= "s";
            
            // Track when status changes to "In Progress" (approved)
            if ($db_status === 'In Progress' && $current_status !== 'In Progress') {
                // Check if mr_approved_at column exists
                $check_column = $conn->query("SHOW COLUMNS FROM maintenance_requests LIKE 'mr_approved_at'");
                if ($check_column && $check_column->num_rows > 0) {
                    $update_fields[] = "mr_approved_at = NOW()";
                }
            }
            
            // Track when status changes to "Resolved" (completed)
            if ($db_status === 'Resolved' && $current_status !== 'Resolved') {
                // Check if mr_completed_at column exists
                $check_column = $conn->query("SHOW COLUMNS FROM maintenance_requests LIKE 'mr_completed_at'");
                if ($check_column && $check_column->num_rows > 0) {
                    $update_fields[] = "mr_completed_at = NOW()";
                }
            }
        }
        
        // Note: The current table structure doesn't have these fields, but we'll prepare for them
        // If you need these fields, you'll need to alter the table
        // For now, we'll just update the status and add notes to description if needed
        
        if (!empty($notes)) {
            // Append notes to description with timestamp
            $timestamped_note = "\n[" . date('Y-m-d H:i:s') . "] " . $notes;
            $update_fields[] = "mr_description = CONCAT(COALESCE(mr_description, ''), ?)";
            $update_values[] = $timestamped_note;
            $types .= "s";
        }
        
        if (empty($update_fields)) {
            throw new Exception("No fields to update");
        }
        
        // Separate parameterized fields from direct SQL fields (like NOW())
        $param_fields = [];
        $direct_fields = [];
        $param_values = [];
        $param_types = '';
        
        foreach ($update_fields as $field) {
            if (strpos($field, 'NOW()') !== false) {
                // Direct SQL field (like mr_approved_at = NOW())
                $direct_fields[] = $field;
            } else {
                // Parameterized field
                $param_fields[] = $field;
            }
        }
        
        // Add parameterized values (only for fields that need parameters)
        foreach ($update_values as $value) {
            $param_values[] = $value;
        }
        
        // Build types string for parameters only
        $param_types = $types;
        
        // Combine all fields
        $all_fields = array_merge($param_fields, $direct_fields);
        
        // Add WHERE clause parameter
        $param_values[] = $request_id_to_use;
        $param_types .= "i";
        
        $sql = "UPDATE maintenance_requests SET " . implode(', ', $all_fields) . " WHERE request_id = ?";
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        // Bind parameters dynamically (only if we have parameters)
        if (!empty($param_values)) {
            $params = array($param_types);
            foreach ($param_values as $key => $value) {
                $params[] = &$param_values[$key];
            }
            call_user_func_array(array($stmt, 'bind_param'), $params);
        }
        
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        
        $stmt->close();
        
        // Commit transaction
        $conn->commit();
        
        // Prepare response first (before notifications to avoid timeout)
        $response = [
            'success' => true,
            'message' => 'Maintenance request updated successfully',
            'data' => [
                'maintenance_id' => $request_id_to_use,
                'request_id' => $request_id_to_use,
                'status' => $db_status,
                'previous_status' => $maintenance['current_status'],
                'boarder_name' => $maintenance['boarder_name'],
                'room_number' => $maintenance['room_number'],
                'boarding_house_name' => $maintenance['boarding_house_name'],
                'notification_sent' => false
            ]
        ];
        
        // Send response immediately to avoid timeout
        // Then send notifications asynchronously (don't wait for them)
        if (!empty($db_status)) {
            // Try to send notifications in background (non-blocking)
            $notification_file = __DIR__ . '/activity_notifications.php';
            if (file_exists($notification_file)) {
                // Use fastcgi_finish_request if available for async notifications
                if (function_exists('fastcgi_finish_request')) {
                    // Send response first
                    echo json_encode($response, JSON_UNESCAPED_SLASHES);
                    $response_sent = true;
                    fastcgi_finish_request();
                    
                    // Then send notifications (won't block response)
                    try {
                        require_once $notification_file;
                        if (class_exists('ActivityNotifications')) {
                            if ($db_status === 'Resolved') {
                                ActivityNotifications::notifyMaintenanceCompleted($maintenance['user_id'], [
                                    'maintenance_id' => $request_id_to_use,
                                    'title' => $maintenance['subject'],
                                    'room_name' => $maintenance['room_number'],
                                    'actual_cost' => $actual_cost
                                ]);
                            } elseif ($db_status === 'Declined') {
                                ActivityNotifications::notifyMaintenanceStatusUpdated($maintenance['user_id'], [
                                    'maintenance_id' => $request_id_to_use,
                                    'status' => $db_status,
                                    'title' => $maintenance['subject'],
                                    'room_name' => $maintenance['room_number'],
                                    'notes' => $notes
                                ]);
                            } else {
                                ActivityNotifications::notifyMaintenanceStatusUpdated($maintenance['user_id'], [
                                    'maintenance_id' => $request_id_to_use,
                                    'status' => $db_status,
                                    'title' => $maintenance['subject'],
                                    'room_name' => $maintenance['room_number'],
                                    'notes' => $notes
                                ]);
                            }
                        }
                    } catch (Exception $e) {
                        error_log("Notification error: " . $e->getMessage());
                    }
                    exit; // Exit after fastcgi_finish_request
                } else {
                    // Fallback: send notifications quickly with timeout protection
                    $start_time = microtime(true);
                    $max_notification_time = 2; // Max 2 seconds for notifications
                    
                    try {
                        require_once $notification_file;
                        if (class_exists('ActivityNotifications')) {
                            if ($db_status === 'Resolved') {
                                $notification_result = ActivityNotifications::notifyMaintenanceCompleted($maintenance['user_id'], [
                                    'maintenance_id' => $request_id_to_use,
                                    'title' => $maintenance['subject'],
                                    'room_name' => $maintenance['room_number'],
                                    'actual_cost' => $actual_cost
                                ]);
                                $response['data']['notification_sent'] = $notification_result && isset($notification_result['success']) && $notification_result['success'];
                            } elseif ($db_status === 'Declined') {
                                $notification_result = ActivityNotifications::notifyMaintenanceStatusUpdated($maintenance['user_id'], [
                                    'maintenance_id' => $request_id_to_use,
                                    'status' => $db_status,
                                    'title' => $maintenance['subject'],
                                    'room_name' => $maintenance['room_number'],
                                    'notes' => $notes
                                ]);
                                $response['data']['notification_sent'] = $notification_result && isset($notification_result['success']) && $notification_result['success'];
                            } else {
                                $notification_result = ActivityNotifications::notifyMaintenanceStatusUpdated($maintenance['user_id'], [
                                    'maintenance_id' => $request_id_to_use,
                                    'status' => $db_status,
                                    'title' => $maintenance['subject'],
                                    'room_name' => $maintenance['room_number'],
                                    'notes' => $notes
                                ]);
                                $response['data']['notification_sent'] = $notification_result && isset($notification_result['success']) && $notification_result['success'];
                            }
                            
                            // Check if we're taking too long
                            $elapsed = microtime(true) - $start_time;
                            if ($elapsed > $max_notification_time) {
                                error_log("Notification took too long: " . $elapsed . " seconds");
                            }
                        }
                    } catch (Exception $e) {
                        error_log("Notification error: " . $e->getMessage());
                    }
                }
            }
        }
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        throw $e;
    }
    
    $conn->close();
    
    // Send response if not already sent (for fastcgi_finish_request case)
    if (!isset($response_sent) || !$response_sent) {
        echo json_encode($response, JSON_UNESCAPED_SLASHES);
    }
    
} catch (Exception $e) {
    // Rollback transaction on error if still in transaction
    if (isset($conn)) {
        try {
            if ($conn->in_transaction) {
                $conn->rollback();
            }
            $conn->close();
        } catch (Exception $closeError) {
            // Ignore close errors
        }
    }
    
    error_log("Update maintenance status error: " . $e->getMessage());
    $response = [
        'success' => false,
        'error' => $e->getMessage()
    ];
    echo json_encode($response, JSON_UNESCAPED_SLASHES);
}
?>

