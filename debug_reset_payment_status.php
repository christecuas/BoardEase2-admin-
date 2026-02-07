<?php
// debug_reset_payment_status.php
// USE FOR TESTING ONLY: Resets payment status for a booking so checkboxes appear again

require_once 'dbConfig.php';

// If booking_id is provided, perform the reset and return JSON
if (isset($_REQUEST['booking_id'])) {
    header('Content-Type: application/json');
    
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $bookingId = intval($_REQUEST['booking_id']);
        
        if ($bookingId == 0) {
             throw new Exception("Invalid Booking ID");
        }
        
        // 1. Delete the pending payments
        $stmt = $pdo->prepare("DELETE FROM payments WHERE booking_id = ? AND payment_status = 'Pending'");
        $stmt->execute([$bookingId]);
        $deletedPayments = $stmt->rowCount();
        
        // 2. Reset the breakdowns
        $stmt = $pdo->prepare("UPDATE payment_breakdowns SET payment_id = NULL, payment_status = NULL WHERE booking_id = ? AND is_paid = 0");
        $stmt->execute([$bookingId]);
        $updatedBreakdowns = $stmt->rowCount();
        
        echo json_encode([
            'success' => true,
            'message' => "Reset complete. Deleted $deletedPayments pending payments and reset $updatedBreakdowns breakdowns.",
            'booking_id' => $bookingId
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// If no booking_id, show HTML list of recent bookings
?>
<!DOCTYPE html>
<html>
<head>
    <title>Debug: Reset Payment Status</title>
    <style>
        body { font-family: sans-serif; padding: 20px; }
        table { border-collapse: collapse; width: 100%; max-width: 1000px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .btn { background-color: #f44336; color: white; padding: 6px 12px; text-decoration: none; border-radius: 4px; cursor: pointer; }
        .btn:hover { background-color: #d32f2f; }
        .success { color: green; font-weight: bold; margin-bottom: 20px; }
    </style>
    <script>
        function resetBooking(id) {
            if(!confirm('Reset payments for Booking ID ' + id + '?')) return;
            
            fetch('debug_reset_payment_status.php?booking_id=' + id)
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        alert(data.message);
                        location.reload();
                    } else {
                        alert('Error: ' + data.error);
                    }
                })
                .catch(err => alert('Network error: ' + err));
        }
    </script>
</head>
<body>
    <h1>Reset Payment Status (Debug)</h1>
    <p>Select a booking to delete its pending payments and reset checkbox visibility.</p>
    
    <table>
        <thead>
            <tr>
                <th>Booking ID</th>
                <th>User</th>
                <th>Room</th>
                <th>Status</th>
                <th>Date</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php
            try {
                $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
                $stmt = $pdo->query("
                    SELECT 
                        b.booking_id, 
                        b.booking_status, 
                        b.booking_date,
                        CONCAT(r.first_name, ' ', r.last_name) as user_name,
                        bhr.room_name
                    FROM bookings b
                    LEFT JOIN users u ON b.user_id = u.user_id
                    LEFT JOIN registrations r ON u.reg_id = r.id
                    LEFT JOIN room_units ru ON b.room_id = ru.room_id
                    LEFT JOIN boarding_house_rooms bhr ON ru.bhr_id = bhr.bhr_id
                    ORDER BY b.booking_id DESC
                    LIMIT 20
                ");
                
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    echo "<tr>";
                    echo "<td>" . $row['booking_id'] . "</td>";
                    echo "<td>" . htmlspecialchars($row['user_name'] ?? 'Unknown') . "</td>";
                    echo "<td>" . htmlspecialchars($row['room_name'] ?? 'Unknown') . "</td>";
                    echo "<td>" . $row['booking_status'] . "</td>";
                    echo "<td>" . $row['booking_date'] . "</td>";
                    echo "<td><button class='btn' onclick='resetBooking(" . $row['booking_id'] . ")'>RESET</button></td>";
                    echo "</tr>";
                }
            } catch (Exception $e) {
                echo "<tr><td colspan='6'>Error loading bookings: " . $e->getMessage() . "</td></tr>";
            }
            ?>
        </tbody>
    </table>
</body>
</html>
