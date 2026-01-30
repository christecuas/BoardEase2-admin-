<?php
// Automatically mark payments as overdue when due date passes
// This script should be run daily via cron job or called when calendar loads

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
// Database configuration
require_once 'dbConfig.php';

// define('DB_HOST', '');
// define('DB_USER', 'u223444398_userboardease');
// define('DB_PASS', '!Boardease2026');
// define('DB_NAME', 'u223444398_boardease');

$host = DB_HOST;
$dbname = DB_NAME;
$username = DB_USER;
$password = DB_PASS;

// Create logs directory if it doesn't exist
$logDir = __DIR__ . '/logs';
if (!file_exists($logDir)) {
    mkdir($logDir, 0755, true);
}
$logFile = $logDir . '/auto_mark_overdue.log';

function logMessage($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message" . PHP_EOL;
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $updatedCount = 0;
    $errors = [];
    
    // Update payment_breakdowns that are past due date and not paid
    // Fix: Simplify WHERE clause to properly match Pending status and past due dates
    $updateBreakdownsSql = "
        UPDATE payment_breakdowns pb
        INNER JOIN bookings b ON pb.booking_id = b.booking_id
        SET pb.payment_status = 'Overdue',
            pb.updated_at = NOW()
        WHERE pb.is_paid = 0
        AND pb.payment_status = 'Pending'
        AND COALESCE(pb.due_date, pb.period_start_date) < CURDATE()
    ";
    
    $updateBreakdownsStmt = $pdo->prepare($updateBreakdownsSql);
    $updateBreakdownsStmt->execute();
    $breakdownsUpdated = $updateBreakdownsStmt->rowCount();
    $updatedCount += $breakdownsUpdated;
    
    // Also update payments table for payments that are past due date
    $updatePaymentsSql = "
        UPDATE payments p
        INNER JOIN bookings b ON p.booking_id = b.booking_id
        SET p.payment_status = 'Overdue',
            p.updated_at = NOW()
        WHERE p.payment_status = 'Pending'
        AND DATE(p.payment_date) < CURDATE()
        AND p.payment_status != 'Overdue'
        AND p.payment_status != 'Cancelled'
        AND p.payment_status != 'Completed'
    ";
    
    $updatePaymentsStmt = $pdo->prepare($updatePaymentsSql);
    $updatePaymentsStmt->execute();
    $paymentsUpdated = $updatePaymentsStmt->rowCount();
    $updatedCount += $paymentsUpdated;
    
    // Send notifications for overdue payments
    // Only send notifications once per day per payment
    // Notify for all overdue payments that haven't been paid yet
    // This ensures notifications are sent daily until payment is made
    // We check all overdue payments, not just newly overdue ones
    $notifSql = "
                SELECT DISTINCT
                    pb.breakdown_id,
                    pb.booking_id,
                    b.user_id as boarder_user_id,
                    CONCAT(r.first_name, ' ', r.last_name) as boarder_name,
                    ru.room_number,
                    bh.bh_name,
                    pb.amount,
                    COALESCE(pb.due_date, pb.period_start_date) as due_date,
                    pb.updated_at
                FROM payment_breakdowns pb
                INNER JOIN bookings b ON pb.booking_id = b.booking_id
                INNER JOIN users u ON b.user_id = u.user_id
                INNER JOIN registrations r ON u.reg_id = r.id
                INNER JOIN room_units ru ON b.room_id = ru.room_id
                INNER JOIN boarding_house_rooms bhr ON ru.bhr_id = bhr.bhr_id
                INNER JOIN boarding_houses bh ON bhr.bh_id = bh.bh_id
                WHERE pb.payment_status = 'Overdue'
                AND pb.is_paid = 0
                AND COALESCE(pb.due_date, pb.period_start_date) < CURDATE()
    ";

    $notifStmt = $pdo->prepare($notifSql);
    $notifStmt->execute();
    $overduePayments = $notifStmt->fetchAll(PDO::FETCH_ASSOC);

    $notificationsSent = 0;
    $notificationsSkipped = 0;

    foreach ($overduePayments as $payment) {
                $boarderUserId = intval($payment['boarder_user_id']);
                $amount = floatval($payment['amount']);
                $roomNumber = $payment['room_number'] ?? 'room';
                $breakdownId = intval($payment['breakdown_id'] ?? 0);

                // Check for duplicate notification - ensure we only send once per day per payment
                // Check if notification was already sent today for this specific payment breakdown
                // We check by user_id, amount, and room number to be more specific
                $checkDuplicateSql = "
                    SELECT notif_id 
                    FROM notifications 
                    WHERE user_id = ? 
                    AND notif_type = 'payment' 
                    AND notif_title = 'Payment Overdue'
                    AND notif_message LIKE ?
                    AND notif_message LIKE ?
                    AND DATE(notif_created_at) = CURDATE()
                    LIMIT 1
                ";
                $checkDuplicateStmt = $pdo->prepare($checkDuplicateSql);
                $amountPattern = "%₱" . number_format($amount, 2) . "%";
                $roomPattern = "%" . $roomNumber . "%";
                $checkDuplicateStmt->execute([$boarderUserId, $amountPattern, $roomPattern]);
                $duplicate = $checkDuplicateStmt->fetch(PDO::FETCH_ASSOC);

                if (!$duplicate) {
                    // 1. Insert notification into database for in-app notification center
                    $notifTitle = "Payment Overdue";
                    $notifMessage = "Your payment of ₱" . number_format($amount, 2) . " for " . $roomNumber . " is overdue. Please make payment as soon as possible.";
                    
                    $insertNotifSql = "INSERT INTO notifications (user_id, notif_title, notif_message, notif_type, notif_status) 
                                      VALUES (:user_id, :title, :message, 'payment', 'unread')";
                    $insertNotifStmt = $pdo->prepare($insertNotifSql);
                    $insertNotifStmt->execute([
                        ':user_id' => $boarderUserId,
                        ':title' => $notifTitle,
                        ':message' => $notifMessage
                    ]);
                    
                    // 2. Send push notification (FCM) for system notification
                    // Try to use ActivityNotifications if available (for FCM push notification)
                    if (file_exists(__DIR__ . '/activity_notifications.php')) {
                        require_once __DIR__ . '/activity_notifications.php';
                        if (class_exists('ActivityNotifications')) {
                            ActivityNotifications::notifyPaymentOverdue($boarderUserId, [
                                'amount' => $amount,
                                'payment_id' => $payment['breakdown_id'] ?? null,
                                'room_name' => $roomNumber,
                                'bh_name' => $payment['bh_name'] ?? 'Boarding House'
                            ]);
                        }
                    } else {
                        // Fallback: Send FCM notification directly if ActivityNotifications doesn't exist
                        $fcmFile = __DIR__ . '/send_fcm_notification.php';
                        if (file_exists($fcmFile)) {
                            require_once $fcmFile;
                            if (function_exists('sendFCMNotification')) {
                                sendFCMNotification($boarderUserId, $notifTitle, $notifMessage, [
                                    'type' => 'payment',
                                    'payment_id' => $payment['breakdown_id'] ?? null,
                                    'status' => 'Overdue',
                                    'amount' => $amount,
                                    'room_name' => $roomNumber
                                ]);
                            }
                        }
                    }
                    
                    // Log notification sent
                    $notificationsSent++;
                    logMessage("Notification sent to user_id: $boarderUserId, amount: ₱" . number_format($amount, 2) . ", room: $roomNumber");
                } else {
                    // Log notification skipped (already sent today)
                    $notificationsSkipped++;
                    logMessage("Notification skipped (already sent today) for user_id: $boarderUserId, amount: ₱" . number_format($amount, 2) . ", room: $roomNumber");
                }
    }
    
    // Log summary
    logMessage("Summary: $notificationsSent notifications sent, $notificationsSkipped notifications skipped (already sent today)");

    echo json_encode([
        'success' => true,
        'message' => "Successfully updated overdue payments",
        'breakdowns_updated' => $breakdownsUpdated,
        'payments_updated' => $paymentsUpdated,
        'total_updated' => $updatedCount,
        'notifications_sent' => $notificationsSent,
        'notifications_skipped' => $notificationsSkipped
    ], JSON_PRETTY_PRINT);
    
} catch (PDOException $e) {
    $errorMessage = 'Database error: ' . $e->getMessage();
    logMessage($errorMessage);
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $errorMessage
    ], JSON_PRETTY_PRINT);
} catch (Exception $e) {
    $errorMessage = 'Error: ' . $e->getMessage();
    logMessage($errorMessage);
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $errorMessage
    ], JSON_PRETTY_PRINT);
}
?>

