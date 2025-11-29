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
                b.booking_id,
                MAX(p.payment_id) as payment_id,
                MAX(pb_agg.total_amount) as payment_amount,
                GROUP_CONCAT(DISTINCT p.payment_method ORDER BY p.payment_date DESC SEPARATOR ', ') as payment_method,
                MAX(p.payment_status) as payment_status,
                MAX(p.payment_date) as payment_date,
                MAX(p.payment_month) as payment_month,
                MAX(p.payment_year) as payment_year,
                MAX(p.payment_month_number) as payment_month_number,
                MAX(p.is_monthly_payment) as is_monthly_payment,
                MAX(p.months_paid) as months_paid,
                MAX(p.total_months_required) as total_months_required,
                MAX(p.notes) as notes,
                MAX(p.receipt_url) as receipt_url,
                MAX(p.payment_proof) as payment_proof,
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
                MAX(pb_agg.total_breakdowns) as total_breakdowns,
                MAX(pb_agg.paid_breakdowns) as paid_breakdowns,
                MAX(pb_agg.unpaid_breakdowns) as unpaid_breakdowns,
                MAX(pb_agg.amount_paid) as paid_amount,
                MAX(pb_agg.amount_unpaid) as unpaid_amount,
                COALESCE(MAX(pb_agg.periods), 'N/A') as periods,
                -- Calculate actual payment status from breakdowns (source of truth)
                CASE 
                    WHEN MAX(pb_agg.total_breakdowns) = 0 OR MAX(pb_agg.total_breakdowns) IS NULL THEN COALESCE(MAX(p.payment_status), 'Pending')
                    WHEN MAX(pb_agg.paid_breakdowns) >= MAX(pb_agg.total_breakdowns) THEN 'Fully Paid'
                    WHEN MAX(pb_agg.paid_breakdowns) > 0 THEN 'Completed/Partially'
                    ELSE 'Pending'
                END as actual_payment_status
            FROM bookings b
            LEFT JOIN payments p ON b.booking_id = p.booking_id
            LEFT JOIN users u ON b.user_id = u.user_id
            LEFT JOIN registrations r ON u.reg_id = r.id
            LEFT JOIN users u2 ON p.owner_id = u2.user_id
            LEFT JOIN registrations r2 ON u2.reg_id = r2.id
            LEFT JOIN room_units ru ON b.room_id = ru.room_id
            LEFT JOIN boarding_house_rooms bhr ON ru.bhr_id = bhr.bhr_id
            LEFT JOIN boarding_houses bh ON bhr.bh_id = bh.bh_id
            LEFT JOIN (
                SELECT 
                    booking_id,
                    COUNT(breakdown_id) as total_breakdowns,
                    SUM(CASE WHEN is_paid = 1 THEN 1 ELSE 0 END) as paid_breakdowns,
                    SUM(CASE WHEN is_paid = 0 THEN 1 ELSE 0 END) as unpaid_breakdowns,
                    SUM(amount) as total_amount,
                    SUM(CASE WHEN is_paid = 1 THEN amount ELSE 0 END) as amount_paid,
                    SUM(CASE WHEN is_paid = 0 THEN amount ELSE 0 END) as amount_unpaid,
                    GROUP_CONCAT(DISTINCT period_label ORDER BY period_start_date SEPARATOR ', ') as periods
                FROM payment_breakdowns
                GROUP BY booking_id
            ) pb_agg ON b.booking_id = pb_agg.booking_id
            WHERE p.payment_id IS NOT NULL
            GROUP BY b.booking_id, b.start_date, b.end_date, b.booking_status, b.booking_date,
                     r.first_name, r.middle_name, r.last_name, r.suffix, r.email, r.phone,
                     r2.first_name, r2.last_name, r2.email, r2.phone,
                     bh.bh_name, bhr.room_category, bhr.room_name, bhr.price, ru.room_number
            ORDER BY MAX(p.payment_date) DESC
        ";
        
        $result = $connection->query($query);
        
        if (!$result) {
            throw new Exception("Query failed: " . $connection->error);
        }
        
        // Start CSV content with payment breakdown information
        $csv = "Booking ID,Payment ID,Amount,Method,Status (Actual),Status (Payment Table),Date,Month,Year,Month Number,Monthly Payment,Months Paid,Total Months,Notes,Receipt URL,Payment Proof,Customer Name,Email,Phone,Owner Name,Owner Email,Owner Phone,Booking Start Date,Booking End Date,Booking Status,Booking Date,Boarding House,Room Category,Room Name,Room Number,Room Price,Total Breakdowns,Paid Breakdowns,Unpaid Breakdowns,Paid Amount,Unpaid Amount,Periods Covered\n";
        
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
                $row['booking_id'] ?? '',
                $row['payment_id'] ?? '',
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
