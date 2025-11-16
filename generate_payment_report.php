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
        
        // Query to get payment data with user, booking, and payment breakdown information
        // CRITICAL: payment_breakdowns is the source of truth for payment status
        // We need to show breakdown periods and their status
        $query = "
            SELECT 
                p.payment_id,
                p.booking_id,
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
                -- Boarder information
                COALESCE(r.first_name, '') as first_name,
                COALESCE(r.middle_name, '') as middle_name,
                COALESCE(r.last_name, '') as last_name,
                COALESCE(r.suffix, '') as suffix,
                COALESCE(r.email, '') as email,
                COALESCE(r.phone, '') as phone,
                -- Owner information
                COALESCE(r2.first_name, '') as owner_first_name,
                COALESCE(r2.last_name, '') as owner_last_name,
                COALESCE(r2.email, '') as owner_email,
                COALESCE(r2.phone, '') as owner_phone,
                -- Booking information
                b.start_date,
                b.end_date,
                b.booking_status,
                b.booking_date,
                -- Room and boarding house information
                COALESCE(bh.bh_name, 'N/A') as bh_name,
                COALESCE(bhr.room_category, 'N/A') as room_category,
                COALESCE(bhr.room_name, 'N/A') as room_name,
                COALESCE(bhr.price, 0) as price,
                COALESCE(ru.room_number, '') as room_number,
                -- Payment breakdown information (aggregated)
                -- NOTE: We join on booking_id to get ALL breakdowns for the booking, not just ones linked to this payment
                -- This gives a complete picture of payment progress for the booking
                COUNT(DISTINCT pb.breakdown_id) as total_breakdowns,
                COALESCE(SUM(CASE WHEN pb.is_paid = 1 THEN 1 ELSE 0 END), 0) as paid_breakdowns,
                COALESCE(SUM(CASE WHEN pb.is_paid = 0 THEN 1 ELSE 0 END), 0) as unpaid_breakdowns,
                COALESCE(SUM(CASE WHEN pb.is_paid = 1 THEN pb.amount ELSE 0 END), 0) as paid_amount,
                COALESCE(SUM(CASE WHEN pb.is_paid = 0 THEN pb.amount ELSE 0 END), 0) as unpaid_amount,
                COALESCE(GROUP_CONCAT(DISTINCT pb.period_label ORDER BY pb.period_start_date SEPARATOR ', '), 'N/A') as periods,
                -- Calculate actual payment status from breakdowns (source of truth)
                -- If no breakdowns exist, use payment table status
                -- Otherwise, calculate based on breakdown payment progress
                CASE 
                    WHEN COUNT(DISTINCT pb.breakdown_id) = 0 THEN COALESCE(p.payment_status, 'Pending')
                    WHEN COALESCE(SUM(CASE WHEN pb.is_paid = 1 THEN 1 ELSE 0 END), 0) >= COUNT(DISTINCT pb.breakdown_id) THEN 'Fully Paid'
                    WHEN COALESCE(SUM(CASE WHEN pb.is_paid = 1 THEN 1 ELSE 0 END), 0) > 0 THEN 'Completed/Partially'
                    ELSE 'Pending'
                END as actual_payment_status
            FROM payments p
            LEFT JOIN users u ON p.user_id = u.user_id
            LEFT JOIN registrations r ON u.reg_id = r.id
            LEFT JOIN users u2 ON p.owner_id = u2.user_id
            LEFT JOIN registrations r2 ON u2.reg_id = r2.id
            LEFT JOIN bookings b ON p.booking_id = b.booking_id
            LEFT JOIN room_units ru ON b.room_id = ru.room_id
            LEFT JOIN boarding_house_rooms bhr ON ru.bhr_id = bhr.bhr_id
            LEFT JOIN boarding_houses bh ON bhr.bh_id = bh.bh_id
            LEFT JOIN payment_breakdowns pb ON p.booking_id = pb.booking_id
            GROUP BY p.payment_id, p.booking_id, p.payment_amount, p.payment_method, p.payment_status, 
                     p.payment_date, p.payment_month, p.payment_year, p.payment_month_number, 
                     p.is_monthly_payment, p.months_paid, p.total_months_required, p.notes, 
                     p.receipt_url, p.payment_proof, r.first_name, r.middle_name, r.last_name, 
                     r.suffix, r.email, r.phone, r2.first_name, r2.last_name, r2.email, r2.phone,
                     b.start_date, b.end_date, b.booking_status, b.booking_date, 
                     bh.bh_name, bhr.room_category, bhr.room_name, bhr.price, ru.room_number
            ORDER BY p.payment_date DESC
        ";
        
        $result = $connection->query($query);
        
        if (!$result) {
            throw new Exception("Query failed: " . $connection->error);
        }
        
        // Start CSV content with payment breakdown information
        $csv = "Payment ID,Booking ID,Amount,Method,Status (Actual),Status (Payment Table),Date,Month,Year,Month Number,Monthly Payment,Months Paid,Total Months,Notes,Receipt URL,Payment Proof,Customer Name,Email,Phone,Owner Name,Owner Email,Owner Phone,Booking Start Date,Booking End Date,Booking Status,Booking Date,Boarding House,Room Category,Room Name,Room Number,Room Price,Total Breakdowns,Paid Breakdowns,Unpaid Breakdowns,Paid Amount,Unpaid Amount,Periods Covered\n";
        
        while ($row = $result->fetch_assoc()) {
            // Build boarder full name
            $fullName = trim($row['first_name'] . ' ' . $row['middle_name'] . ' ' . $row['last_name']);
            if (!empty($row['suffix'])) {
                $fullName .= ' ' . $row['suffix'];
            }
            $fullName = trim($fullName);
            
            // Build owner full name
            $ownerName = trim($row['owner_first_name'] . ' ' . $row['owner_last_name']);
            $ownerName = trim($ownerName);
            
            // Get actual payment status (from breakdowns - source of truth)
            $actualStatus = $row['actual_payment_status'] ?? $row['payment_status'];
            
            // Format amounts
            $paidAmount = number_format($row['paid_amount'] ?? 0, 2);
            $unpaidAmount = number_format($row['unpaid_amount'] ?? 0, 2);
            
            // Format room display
            $roomDisplay = $row['room_name'];
            if (!empty($row['room_number'])) {
                $roomDisplay .= ' - Room ' . $row['room_number'];
            }
            
            $csv .= sprintf(
                "%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s\n",
                $row['payment_id'] ?? '',
                $row['booking_id'] ?? '',
                number_format($row['payment_amount'] ?? 0, 2),
                $row['payment_method'] ?? 'N/A',
                $actualStatus, // Actual status from breakdowns
                $row['payment_status'] ?? 'N/A', // Status from payments table
                $row['payment_date'] ?? '',
                $row['payment_month'] ?? '',
                $row['payment_year'] ?? '',
                $row['payment_month_number'] ?? '',
                $row['is_monthly_payment'] ? 'Yes' : 'No',
                $row['months_paid'] ?? 0,
                $row['total_months_required'] ?? 0,
                '"' . str_replace('"', '""', $row['notes'] ?? '') . '"',
                $row['receipt_url'] ?? '',
                $row['payment_proof'] ?? '',
                '"' . str_replace('"', '""', $fullName) . '"',
                $row['email'] ?? '',
                $row['phone'] ?? '',
                '"' . str_replace('"', '""', $ownerName) . '"',
                $row['owner_email'] ?? '',
                $row['owner_phone'] ?? '',
                $row['start_date'] ?? '',
                $row['end_date'] ?? '',
                $row['booking_status'] ?? '',
                $row['booking_date'] ?? '',
                '"' . str_replace('"', '""', $row['bh_name'] ?? 'N/A') . '"',
                $row['room_category'] ?? 'N/A',
                '"' . str_replace('"', '""', $roomDisplay) . '"',
                $row['room_number'] ?? '',
                number_format($row['price'] ?? 0, 2),
                $row['total_breakdowns'] ?? 0,
                $row['paid_breakdowns'] ?? 0,
                $row['unpaid_breakdowns'] ?? 0,
                $paidAmount,
                $unpaidAmount,
                '"' . str_replace('"', '""', $row['periods'] ?? 'N/A') . '"'
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
