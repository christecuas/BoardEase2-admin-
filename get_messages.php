<?php
// Get messages for a conversation
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'php_errors.log');
ob_start();

require_once 'db_helper.php';

header('Content-Type: application/json');

// Enable detailed logging
error_log("=== GET_MESSAGES.PHP DEBUG START ===");
error_log("Request URI: " . $_SERVER['REQUEST_URI']);
error_log("GET parameters: " . print_r($_GET, true));

try {
    $user1_id = $_GET['user1_id'] ?? null;
    $user2_id = $_GET['user2_id'] ?? null;
    $limit = (int)($_GET['limit'] ?? 50);
    $offset = (int)($_GET['offset'] ?? 0);
    
    error_log("Parsed parameters - user1_id: $user1_id, user2_id: $user2_id, limit: $limit, offset: $offset");
    
    if (!$user1_id || !$user2_id) {
        error_log("ERROR: Missing required parameters");
        throw new Exception('Missing required parameters: user1_id, user2_id');
    }
    
    $db = getDB();
    error_log("Database connection successful");
    
    // Get messages between two users (get most recent messages first)
    $sql = "
        SELECT 
            m.message_id,
            m.sender_id,
            m.receiver_id,
            m.msg_text as message,
            m.msg_timestamp as timestamp,
            m.msg_status as status
        FROM messages m
        WHERE (m.sender_id = ? AND m.receiver_id = ?) 
           OR (m.sender_id = ? AND m.receiver_id = ?)
        AND m.msg_status != 'Deleted'
        ORDER BY m.msg_timestamp DESC
        LIMIT " . $limit . " OFFSET " . $offset;
    
    error_log("SQL Query: " . $sql);
    error_log("Query parameters: user1_id=$user1_id, user2_id=$user2_id, user2_id=$user2_id, user1_id=$user1_id");
    
    $stmt = $db->prepare($sql);
    if (!$stmt) {
        error_log("ERROR: SQL prepare failed - " . print_r($db->errorInfo(), true));
        throw new Exception("SQL prepare failed");
    }
    
    $stmt->execute([$user1_id, $user2_id, $user2_id, $user1_id]);
    $messages = $stmt->fetchAll();
    
    error_log("Raw messages count: " . count($messages));
    error_log("Raw messages data: " . print_r($messages, true));
    
    // Get total count
    $count_sql = "
        SELECT COUNT(*) as total_count
        FROM messages m
        WHERE (m.sender_id = ? AND m.receiver_id = ?)
           OR (m.sender_id = ? AND m.receiver_id = ?)
        AND m.msg_status != 'Deleted'
    ";
    
    error_log("Count SQL: " . $count_sql);
    $stmt = $db->prepare($count_sql);
    $stmt->execute([$user1_id, $user2_id, $user2_id, $user1_id]);
    $total_count = $stmt->fetch()['total_count'];
    
    error_log("Total message count: " . $total_count);
    
    // Format messages for response
    $formatted_messages = [];
    error_log("Starting to format " . count($messages) . " messages");
    
    foreach ($messages as $index => $message) {
        error_log("Processing message $index: " . print_r($message, true));
        
        // Get sender name
        $sender_sql = "
            SELECT r.first_name, r.last_name 
            FROM users u 
            JOIN registrations r ON u.reg_id = r.id 
            WHERE u.user_id = ?
        ";
        error_log("Sender SQL: " . $sender_sql . " with sender_id: " . $message['sender_id']);
        
        $stmt = $db->prepare($sender_sql);
        $stmt->execute([$message['sender_id']]);
        $sender = $stmt->fetch();
        $sender_name = 'Unknown';
        if ($sender) {
            $sender_name = trim($sender['first_name'] . ' ' . $sender['last_name']);
            error_log("Sender found: " . $sender_name);
        } else {
            error_log("Sender NOT found for sender_id: " . $message['sender_id']);
        }
        
        // Get receiver name
        error_log("Getting receiver name for receiver_id: " . $message['receiver_id']);
        $stmt->execute([$message['receiver_id']]);
        $receiver = $stmt->fetch();
        $receiver_name = 'Unknown';
        if ($receiver) {
            $receiver_name = trim($receiver['first_name'] . ' ' . $receiver['last_name']);
            error_log("Receiver found: " . $receiver_name);
        } else {
            error_log("Receiver NOT found for receiver_id: " . $message['receiver_id']);
        }
        
        // Format timestamp to include date and time (e.g., Oct 5, 1:20 PM)
        $formatted_timestamp = date('M j, g:i A', strtotime($message['timestamp']));
        error_log("Formatted timestamp: " . $formatted_timestamp . " from original: " . $message['timestamp']);
        
        $formatted_message = [
            'message_id' => $message['message_id'],
            'sender_id' => $message['sender_id'],
            'receiver_id' => $message['receiver_id'],
            'message' => $message['message'],
            'timestamp' => $formatted_timestamp, // Use formatted timestamp for Android app
            'original_timestamp' => $message['timestamp'], // Keep original for reference
            'status' => $message['status'],
            'sender_name' => $sender_name,
            'receiver_name' => $receiver_name,
            'is_from_current_user' => $message['sender_id'] == $user1_id
        ];
        
        error_log("Formatted message: " . print_r($formatted_message, true));
        $formatted_messages[] = $formatted_message;
    }
    
    error_log("Total formatted messages: " . count($formatted_messages));
    
    // Reverse the array to show messages in chronological order (oldest first)
    $formatted_messages = array_reverse($formatted_messages);
    error_log("Reversed messages for chronological display. Final count: " . count($formatted_messages));
    
    $response = [
        'success' => true,
        'data' => [
            'messages' => $formatted_messages,
            'total_count' => $total_count,
            'limit' => $limit,
            'offset' => $offset
        ]
    ];
    
    error_log("Final response: " . print_r($response, true));
    
} catch (Exception $e) {
    error_log("EXCEPTION CAUGHT: " . $e->getMessage());
    error_log("Exception trace: " . $e->getTraceAsString());
    
    $response = [
        'success' => false,
        'message' => 'Error: ' . $e->getMessage(),
        'data' => null
    ];
}

error_log("=== GET_MESSAGES.PHP DEBUG END ===");

ob_clean();
echo json_encode($response);
exit;
?>
