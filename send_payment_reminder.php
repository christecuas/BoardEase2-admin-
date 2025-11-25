<?php
// Send payment reminder to boarder for overdue payment

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, ngrok-skip-browser-warning, User-Agent, Accept');
    header('Access-Control-Max-Age: 86400');
    http_response_code(200);
    exit;
}

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, ngrok-skip-browser-warning, User-Agent, Accept');

// Database configuration
$host = 'localhost';
$dbname = 'boardease2';
$username = 'boardease';
$password = 'boardease';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_TIMEOUT, 10); // 10 second timeout
    
    // Get JSON input
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    if ($data === null) {
        echo json_encode([
            'success' => false,
            'error' => 'Invalid JSON data'
        ]);
        exit;
    }
    
    $paymentId = isset($data['payment_id']) ? intval($data['payment_id']) : 0;
    $boarderUserId = isset($data['boarder_user_id']) ? intval($data['boarder_user_id']) : 0;
    
    if ($paymentId == 0 || $boarderUserId == 0) {
        echo json_encode([
            'success' => false,
            'error' => 'Payment ID and Boarder User ID are required'
        ]);
        exit;
    }
    
    // Get payment details - try to find from payments table first, then from payment_breakdowns
    // For overdue payments, we might need to query payment_breakdowns directly
    $paymentSql = "
        SELECT 
            p.payment_id,
            p.booking_id,
            p.payment_amount,
            p.payment_status,
            p.payment_date,
            pb.breakdown_id,
            pb.amount as breakdown_amount,
            pb.due_date,
            pb.period_start_date,
            CONCAT(r.first_name, ' ', r.last_name,
                   CASE WHEN r.suffix IS NOT NULL AND r.suffix != '' THEN CONCAT(' ', r.suffix) ELSE '' END) as boarder_name,
            ru.room_number,
            bh.bh_name
        FROM payments p
        LEFT JOIN payment_breakdowns pb ON p.payment_id = pb.payment_id OR (p.payment_id IS NULL AND pb.booking_id = p.booking_id)
        LEFT JOIN bookings b ON p.booking_id = b.booking_id
        LEFT JOIN users u ON b.user_id = u.user_id
        LEFT JOIN registrations r ON u.reg_id = r.id
        LEFT JOIN room_units ru ON b.room_id = ru.room_id
        LEFT JOIN boarding_house_rooms bhr ON ru.bhr_id = bhr.bhr_id
        LEFT JOIN boarding_houses bh ON bhr.bh_id = bh.bh_id
        WHERE p.payment_id = :payment_id
        AND b.user_id = :boarder_user_id
        LIMIT 1
    ";
    
    $paymentStmt = $pdo->prepare($paymentSql);
    $paymentStmt->execute([
        ':payment_id' => $paymentId,
        ':boarder_user_id' => $boarderUserId
    ]);
    $paymentDetails = $paymentStmt->fetch(PDO::FETCH_ASSOC);
    
    // If not found in payments table, try to find from payment_breakdowns (for overdue payments)
    // This handles cases where payment_id is actually a breakdown_id
    if (!$paymentDetails) {
        $breakdownSql = "
            SELECT 
                pb.breakdown_id,
                pb.booking_id,
                pb.payment_id,
                pb.amount as breakdown_amount,
                COALESCE(pb.due_date, pb.period_start_date) as due_date,
                pb.period_start_date,
                pb.payment_status,
                CONCAT(r.first_name, ' ', r.last_name,
                       CASE WHEN r.suffix IS NOT NULL AND r.suffix != '' THEN CONCAT(' ', r.suffix) ELSE '' END) as boarder_name,
                ru.room_number,
                bh.bh_name,
                p.payment_id as payment_id_from_payments,
                p.payment_amount,
                p.payment_date
            FROM payment_breakdowns pb
            LEFT JOIN bookings b ON pb.booking_id = b.booking_id
            LEFT JOIN users u ON b.user_id = u.user_id
            LEFT JOIN registrations r ON u.reg_id = r.id
            LEFT JOIN room_units ru ON b.room_id = ru.room_id
            LEFT JOIN boarding_house_rooms bhr ON ru.bhr_id = bhr.bhr_id
            LEFT JOIN boarding_houses bh ON bhr.bh_id = bh.bh_id
            LEFT JOIN payments p ON pb.payment_id = p.payment_id OR (pb.payment_id IS NULL AND p.booking_id = pb.booking_id)
            WHERE (pb.breakdown_id = :payment_id OR pb.payment_id = :payment_id OR p.payment_id = :payment_id)
            AND b.user_id = :boarder_user_id
            AND pb.is_paid = 0
            ORDER BY COALESCE(pb.due_date, pb.period_start_date) ASC
            LIMIT 1
        ";
        
        $breakdownStmt = $pdo->prepare($breakdownSql);
        $breakdownStmt->execute([
            ':payment_id' => $paymentId,
            ':boarder_user_id' => $boarderUserId
        ]);
        $paymentDetails = $breakdownStmt->fetch(PDO::FETCH_ASSOC);
    }
    
    if (!$paymentDetails) {
        echo json_encode([
            'success' => false,
            'error' => 'Payment not found'
        ]);
        exit;
    }
    
    // Calculate due amount
    $dueAmount = floatval($paymentDetails['breakdown_amount'] ?? $paymentDetails['payment_amount'] ?? 0);
    $dueDate = $paymentDetails['due_date'] ?? $paymentDetails['period_start_date'] ?? $paymentDetails['payment_date'] ?? '';
    $roomNumber = $paymentDetails['room_number'] ?? 'room';
    $boarderName = $paymentDetails['boarder_name'] ?? 'Boarder';
    $bhName = $paymentDetails['bh_name'] ?? 'Boarding House';
    
    // Check if payment is overdue (past due date)
    $isOverdue = false;
    $daysOverdue = 0;
    if (!empty($dueDate)) {
        try {
            $dueDateObj = new DateTime($dueDate);
            $today = new DateTime();
            $today->setTime(0, 0, 0);
            $dueDateObj->setTime(0, 0, 0);
            
            if ($dueDateObj < $today) {
                $isOverdue = true;
                $daysOverdue = $today->diff($dueDateObj)->days;
            }
        } catch (Exception $e) {
            // If date parsing fails, check payment status
            $paymentStatus = strtolower($paymentDetails['payment_status'] ?? '');
            $isOverdue = ($paymentStatus === 'overdue');
        }
    } else {
        // If no due date, check payment status
        $paymentStatus = strtolower($paymentDetails['payment_status'] ?? '');
        $isOverdue = ($paymentStatus === 'overdue');
    }
    
    // Create reminder message - distinguish between due and overdue
    if ($isOverdue) {
        $reminderTitle = "Payment Reminder - Overdue";
        $reminderMessage = "Hello " . $boarderName . ",\n\n" .
                          "This is a reminder that your payment of ₱" . number_format($dueAmount, 2) . 
                          " for " . $roomNumber . " is overdue.\n\n" .
                          "Due Date: " . $dueDate . 
                          ($daysOverdue > 0 ? " (" . $daysOverdue . " day" . ($daysOverdue > 1 ? "s" : "") . " overdue)" : "") . "\n" .
                          "Please make payment as soon as possible.\n\n" .
                          "Thank you.";
    } else {
        $reminderTitle = "Payment Reminder";
        $reminderMessage = "Hello " . $boarderName . ",\n\n" .
                          "This is a reminder that your payment of ₱" . number_format($dueAmount, 2) . 
                          " for " . $roomNumber . " is due.\n\n" .
                          "Due Date: " . $dueDate . "\n" .
                          "Please make payment on or before the due date.\n\n" .
                          "Thank you.";
    }
    
    // Insert notification into database (ONLY ONE notification)
    $insertNotifSql = "INSERT INTO notifications (user_id, notif_title, notif_message, notif_type, notif_status) 
                      VALUES (:user_id, :title, :message, 'payment', 'unread')";
    $insertNotifStmt = $pdo->prepare($insertNotifSql);
    $insertNotifStmt->execute([
        ':user_id' => $boarderUserId,
        ':title' => $reminderTitle,
        ':message' => $reminderMessage
    ]);
    
    // Send push notification (FCM) - use the same message we already created
    // Use sendFCMNotification directly to avoid duplicate notifications
    $fcmFile = __DIR__ . '/send_fcm_notification.php';
    if (file_exists($fcmFile)) {
        require_once $fcmFile;
        if (function_exists('sendFCMNotification')) {
            // Use the correct status based on overdue status
            $notificationStatus = $isOverdue ? 'Overdue' : 'Due';
            sendFCMNotification($boarderUserId, $reminderTitle, $reminderMessage, [
                'type' => 'payment_reminder',
                'payment_id' => $paymentId,
                'status' => $notificationStatus,
                'is_reminder' => true,
                'is_overdue' => $isOverdue,
                'amount' => $dueAmount,
                'room_name' => $roomNumber,
                'due_date' => $dueDate
            ]);
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Payment reminder sent successfully to ' . $boarderName
    ], JSON_PRETTY_PRINT);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ], JSON_PRETTY_PRINT);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error: ' . $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
?>

