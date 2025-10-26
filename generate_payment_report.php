<?php
// Payment Report Generation
// This file generates payment reports in CSV format for download

require_once 'dbConfig.php';

// Function to generate payment report
function generatePaymentReport() {
    try {
        $connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if ($connection->connect_error) {
            throw new Exception("Connection failed: " . $connection->connect_error);
        }
        
        // Query to get payment data with user and booking information
        $query = "
            SELECT 
                p.payment_id,
                p.payment_amount,
                p.payment_method,
                p.payment_status,
                p.payment_date,
                p.payment_month,
                p.payment_year,
                p.payment_month_number,
                p.is_monthly_payment,
                p.months_paid,
                p.total_months_required,
                p.notes,
                p.receipt_url,
                p.payment_proof,
                r.f_name,
                r.m_name,
                r.l_name,
                r.email,
                r.phone_number,
                b.start_date,
                b.end_date,
                b.booking_status,
                b.booking_date,
                bh.bh_name,
                bhr.room_category,
                bhr.room_name,
                bhr.price
            FROM payments p
            LEFT JOIN users u ON p.user_id = u.user_id
            LEFT JOIN registration r ON u.reg_id = r.reg_id
            LEFT JOIN users u2 ON p.owner_id = u2.user_id
            LEFT JOIN registration r2 ON u2.reg_id = r2.reg_id
            LEFT JOIN bookings b ON p.booking_id = b.booking_id
            LEFT JOIN room_units ru ON b.room_id = ru.room_id
            LEFT JOIN boarding_house_rooms bhr ON ru.bhr_id = bhr.bhr_id
            LEFT JOIN boarding_houses bh ON bhr.bh_id = bh.bh_id
            ORDER BY p.payment_date DESC
        ";
        
        $result = $connection->query($query);
        
        if (!$result) {
            throw new Exception("Query failed: " . $connection->error);
        }
        
        // Start CSV content
        $csv = "Payment ID,Amount,Method,Status,Date,Month,Year,Month Number,Monthly Payment,Months Paid,Total Months,Notes,Receipt URL,Payment Proof,Customer Name,Email,Phone,Booking Start Date,Booking End Date,Booking Status,Booking Date,Boarding House,Room Category,Room Name,Room Price\n";
        
        while ($row = $result->fetch_assoc()) {
            $fullName = trim($row['f_name'] . ' ' . $row['m_name'] . ' ' . $row['l_name']);
            $csv .= sprintf(
                "%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s\n",
                $row['payment_id'],
                $row['payment_amount'],
                $row['payment_method'],
                $row['payment_status'],
                $row['payment_date'],
                $row['payment_month'],
                $row['payment_year'],
                $row['payment_month_number'],
                $row['is_monthly_payment'] ? 'Yes' : 'No',
                $row['months_paid'],
                $row['total_months_required'],
                '"' . str_replace('"', '""', $row['notes']) . '"',
                $row['receipt_url'],
                $row['payment_proof'],
                '"' . str_replace('"', '""', $fullName) . '"',
                $row['email'],
                $row['phone_number'],
                $row['start_date'],
                $row['end_date'],
                $row['booking_status'],
                $row['booking_date'],
                '"' . str_replace('"', '""', $row['bh_name']) . '"',
                $row['room_category'],
                '"' . str_replace('"', '""', $row['room_name']) . '"',
                $row['price']
            );
        }
        
        $connection->close();
        return $csv;
        
    } catch (Exception $e) {
        return "Error: " . $e->getMessage();
    }
}

// Handle the report request
if (isset($_GET['action']) && $_GET['action'] === 'payment_report') {
    // Generate report
    $report = generatePaymentReport();
    
    if (strpos($report, 'Error:') === 0) {
        // If there's an error, return JSON response
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $report]);
        exit;
    }
    
    // Set headers for file download
    $filename = 'payment_report_' . date('Y-m-d_H-i-s') . '.csv';
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($report));
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
    
    // Output the report
    echo $report;
    exit;
}

// If accessed directly without action parameter, return error
header('Content-Type: application/json');
echo json_encode(['success' => false, 'message' => 'Invalid request']);
?>
