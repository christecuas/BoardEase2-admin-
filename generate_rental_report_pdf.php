<?php
/**
 * Rental Report PDF Generator
 * Generates comprehensive rental/occupancy reports in PDF format for system administrators
 */

// Start output buffering to prevent any output before PDF
ob_start();

// Suppress warnings and errors for clean PDF output
error_reporting(0);
ini_set('display_errors', 0);

require_once 'dbConfig.php';
require_once 'vendor/autoload.php';

// Ensure timestamps display in Manila local time
date_default_timezone_set('Asia/Manila');

// Function to generate rental report PDF
function generateRentalReportPDF($startDate = null, $endDate = null, $boardingHouseId = null, $preview = false) {
    try {
        $connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if ($connection->connect_error) {
            throw new Exception("Connection failed: " . $connection->connect_error);
        }
        
        // Build WHERE conditions
        $whereConditions = [];
        $params = [];
        $paramTypes = '';
        
        if ($startDate) {
            $whereConditions[] = "b.start_date >= ?";
            $params[] = $startDate;
            $paramTypes .= 's';
        }
        
        if ($endDate) {
            $whereConditions[] = "b.end_date <= ?";
            $params[] = $endDate;
            $paramTypes .= 's';
        }
        
        if ($boardingHouseId) {
            $whereConditions[] = "bh.bh_id = ?";
            $params[] = $boardingHouseId;
            $paramTypes .= 'i';
        }
        
        // Status filter removed - show all rentals
        
        $whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";
        
        // Query for summary statistics
        $summaryQuery = "
            SELECT 
                COUNT(DISTINCT ru.room_id) as total_rooms_available,
                COUNT(DISTINCT CASE WHEN b.booking_status = 'Confirmed' AND (b.end_date IS NULL OR b.end_date >= CURDATE()) THEN b.booking_id END) as total_active_rentals,
                COUNT(DISTINCT CASE WHEN b.booking_status = 'Completed' THEN b.booking_id END) as total_completed_rentals,
                COUNT(DISTINCT CASE WHEN b.booking_status = 'Cancelled' THEN b.booking_id END) as total_canceled_rentals,
                COUNT(DISTINCT ru.room_id) as total_rooms,
                COUNT(DISTINCT CASE WHEN ru.status = 'Occupied' THEN ru.room_id END) as occupied_rooms
            FROM room_units ru
            LEFT JOIN boarding_house_rooms bhr ON ru.bhr_id = bhr.bhr_id
            LEFT JOIN boarding_houses bh ON bhr.bh_id = bh.bh_id
            LEFT JOIN bookings b ON ru.room_id = b.room_id
            $whereClause
        ";
        
        $stmt = $connection->prepare($summaryQuery);
        if (!empty($params)) {
            $stmt->bind_param($paramTypes, ...$params);
        }
        $stmt->execute();
        $summaryResult = $stmt->get_result();
        $summary = $summaryResult->fetch_assoc();
        
        // Calculate occupancy rate
        $totalRooms = $summary['total_rooms_available'] ?? 0;
        $occupiedRooms = $summary['occupied_rooms'] ?? 0;
        $occupancyRate = $totalRooms > 0 ? ($occupiedRooms / $totalRooms) * 100 : 0;
        
        // Query for rental details
        $detailsQuery = "
            SELECT 
                b.booking_id as rental_id,
                CONCAT(COALESCE(r.first_name, ''), ' ', COALESCE(r.middle_name, ''), ' ', COALESCE(r.last_name, ''), 
                       CASE WHEN r.suffix IS NOT NULL AND r.suffix != '' THEN CONCAT(' ', r.suffix) ELSE '' END) as boarder_name,
                COALESCE(bh.bh_name, 'N/A') as bh_name,
                CONCAT(COALESCE(bhr.room_name, 'N/A'), 
                       CASE WHEN ru.room_number IS NOT NULL AND ru.room_number != '' THEN CONCAT(' - Room ', ru.room_number) ELSE '' END) as room,
                DATE_FORMAT(b.start_date, '%Y-%m-%d') as rental_start,
                DATE_FORMAT(b.end_date, '%Y-%m-%d') as rental_end,
                COALESCE(bhr.price, 0) as monthly_rate,
                b.booking_status as status
            FROM bookings b
            LEFT JOIN users u ON b.user_id = u.user_id
            LEFT JOIN registrations r ON u.reg_id = r.id
            LEFT JOIN room_units ru ON b.room_id = ru.room_id
            LEFT JOIN boarding_house_rooms bhr ON ru.bhr_id = bhr.bhr_id
            LEFT JOIN boarding_houses bh ON bhr.bh_id = bh.bh_id
            $whereClause
            ORDER BY b.start_date DESC, b.booking_date DESC
        ";
        
        $stmt = $connection->prepare($detailsQuery);
        if (!empty($params)) {
            $stmt->bind_param($paramTypes, ...$params);
        }
        $stmt->execute();
        $detailsResult = $stmt->get_result();
        $rentalDetails = [];
        while ($row = $detailsResult->fetch_assoc()) {
            $rentalDetails[] = $row;
        }
        
        // Query for upcoming expirations (rentals ending within 30 days)
        $expirationQuery = "
            SELECT 
                CONCAT(COALESCE(r.first_name, ''), ' ', COALESCE(r.middle_name, ''), ' ', COALESCE(r.last_name, ''), 
                       CASE WHEN r.suffix IS NOT NULL AND r.suffix != '' THEN CONCAT(' ', r.suffix) ELSE '' END) as boarder_name,
                COALESCE(bh.bh_name, 'N/A') as bh_name,
                CONCAT(COALESCE(bhr.room_name, 'N/A'), 
                       CASE WHEN ru.room_number IS NOT NULL AND ru.room_number != '' THEN CONCAT(' - Room ', ru.room_number) ELSE '' END) as room,
                DATE_FORMAT(b.end_date, '%Y-%m-%d') as rental_end,
                DATEDIFF(b.end_date, CURDATE()) as days_remaining
            FROM bookings b
            LEFT JOIN users u ON b.user_id = u.user_id
            LEFT JOIN registrations r ON u.reg_id = r.id
            LEFT JOIN room_units ru ON b.room_id = ru.room_id
            LEFT JOIN boarding_house_rooms bhr ON ru.bhr_id = bhr.bhr_id
            LEFT JOIN boarding_houses bh ON bhr.bh_id = bh.bh_id
            WHERE b.booking_status = 'Confirmed'
                AND b.end_date IS NOT NULL
                AND b.end_date >= CURDATE()
                AND b.end_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
        ";
        
        $expirationConditions = [];
        $expirationParams = [];
        $expirationParamTypes = '';
        
        if ($boardingHouseId) {
            $expirationConditions[] = "bh.bh_id = ?";
            $expirationParams[] = $boardingHouseId;
            $expirationParamTypes .= 'i';
        }
        
        if (!empty($expirationConditions)) {
            $expirationQuery .= " AND " . implode(" AND ", $expirationConditions);
        }
        
        $expirationQuery .= " ORDER BY b.end_date ASC";
        
        $stmt = $connection->prepare($expirationQuery);
        if (!empty($expirationParams)) {
            $stmt->bind_param($expirationParamTypes, ...$expirationParams);
        }
        $stmt->execute();
        $expirationResult = $stmt->get_result();
        $upcomingExpirations = [];
        while ($row = $expirationResult->fetch_assoc()) {
            $upcomingExpirations[] = $row;
        }
        
        // Create PDF
        $pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        
        // Set document information
        $pdf->SetCreator('BoardEase System');
        $pdf->SetAuthor('System Administrator');
        $pdf->SetTitle('BoardEase Rental Report');
        $pdf->SetSubject('Rental/Occupancy Report');
        
        // Remove default header/footer
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        
        // Set margins
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(TRUE, 15);
        
        // Add a page
        $pdf->AddPage();
        
        // Set font
        $pdf->SetFont('helvetica', 'B', 20);
        
        // Header
        $pdf->Cell(0, 10, 'BoardEase Rental Report', 0, 1, 'C');
        $pdf->Ln(5);
        
        $pdf->SetFont('helvetica', '', 10);
        $reportingPeriod = '';
        if ($startDate && $endDate) {
            $reportingPeriod = date('F d, Y', strtotime($startDate)) . ' - ' . date('F d, Y', strtotime($endDate));
        } elseif ($startDate) {
            $reportingPeriod = 'From ' . date('F d, Y', strtotime($startDate));
        } elseif ($endDate) {
            $reportingPeriod = 'Until ' . date('F d, Y', strtotime($endDate));
        } else {
            $reportingPeriod = 'All Time';
        }
        
        $pdf->Cell(0, 5, 'Reporting Period: ' . $reportingPeriod, 0, 1, 'C');
        $pdf->Cell(0, 5, 'Generated By: System Administrator', 0, 1, 'C');
        $pdf->Cell(0, 5, 'Date Generated: ' . date('F d, Y h:i A'), 0, 1, 'C');
        $pdf->Ln(10);
        
        // Summary Section
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 10, 'Summary', 0, 1, 'L');
        $pdf->Ln(2);
        
        $pdf->SetFont('helvetica', '', 10);
        $summaryData = [
            ['Total Rooms Available', number_format($totalRooms)],
            ['Total Active Rentals', number_format($summary['total_active_rentals'] ?? 0)],
            ['Total Completed Rentals', number_format($summary['total_completed_rentals'] ?? 0)],
            ['Total Canceled Rentals', number_format($summary['total_canceled_rentals'] ?? 0)],
            ['Occupancy Rate', number_format($occupancyRate, 2) . '%']
        ];
        
        $pdf->SetFillColor(245, 245, 245);
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(100, 8, 'Metric', 1, 0, 'L', true);
        $pdf->Cell(80, 8, 'Value', 1, 1, 'R', true);
        
        $pdf->SetFont('helvetica', '', 10);
        foreach ($summaryData as $row) {
            $pdf->Cell(100, 7, $row[0], 1, 0, 'L');
            $pdf->Cell(80, 7, $row[1], 1, 1, 'R');
        }
        
        $pdf->Ln(10);
        
        // Rental Details Table
        if (!empty($rentalDetails)) {
            $pdf->SetFont('helvetica', 'B', 14);
            $pdf->Cell(0, 10, 'Rental Details', 0, 1, 'L');
            $pdf->Ln(2);
            
            $pdf->SetFont('helvetica', '', 8);
            $pdf->SetFillColor(245, 245, 245);
            
            // Table header
            $pdf->Cell(25, 7, 'Rental ID', 1, 0, 'C', true);
            $pdf->Cell(40, 7, 'Boarder Name', 1, 0, 'L', true);
            $pdf->Cell(30, 7, 'Boarding House', 1, 0, 'L', true);
            $pdf->Cell(25, 7, 'Room', 1, 0, 'L', true);
            $pdf->Cell(25, 7, 'Rental Start', 1, 0, 'C', true);
            $pdf->Cell(25, 7, 'Rental End', 1, 0, 'C', true);
            $pdf->Cell(25, 7, 'Monthly Rate', 1, 0, 'R', true);
            $pdf->Cell(20, 7, 'Status', 1, 1, 'C', true);
            
            $pdf->SetFont('helvetica', '', 7);
            foreach ($rentalDetails as $rental) {
                $pdf->Cell(25, 6, $rental['rental_id'] ?? 'N/A', 1, 0, 'C');
                $pdf->Cell(40, 6, substr($rental['boarder_name'] ?? 'N/A', 0, 20), 1, 0, 'L');
                $pdf->Cell(30, 6, substr($rental['bh_name'] ?? 'N/A', 0, 15), 1, 0, 'L');
                $pdf->Cell(25, 6, substr($rental['room'] ?? 'N/A', 0, 12), 1, 0, 'L');
                $pdf->Cell(25, 6, $rental['rental_start'] ?? 'N/A', 1, 0, 'C');
                $pdf->Cell(25, 6, $rental['rental_end'] ?? 'N/A', 1, 0, 'C');
                $pdf->Cell(25, 6, '₱' . number_format($rental['monthly_rate'] ?? 0, 2), 1, 0, 'R');
                $pdf->Cell(20, 6, $rental['status'] ?? 'N/A', 1, 1, 'C');
            }
        } else {
            $pdf->SetFont('helvetica', '', 10);
            $pdf->Cell(0, 10, 'No rental details found for the selected criteria.', 0, 1, 'L');
        }
        
        $pdf->Ln(10);
        
        // Upcoming Expirations Table
        if (!empty($upcomingExpirations)) {
            $pdf->AddPage();
            $pdf->SetFont('helvetica', 'B', 14);
            $pdf->Cell(0, 10, 'Upcoming Expirations', 0, 1, 'L');
            $pdf->Ln(2);
            
            $pdf->SetFont('helvetica', '', 8);
            $pdf->SetFillColor(255, 250, 240);
            
            // Table header
            $pdf->Cell(50, 7, 'Boarder Name', 1, 0, 'L', true);
            $pdf->Cell(40, 7, 'Boarding House', 1, 0, 'L', true);
            $pdf->Cell(30, 7, 'Room', 1, 0, 'L', true);
            $pdf->Cell(30, 7, 'Rental End', 1, 0, 'C', true);
            $pdf->Cell(30, 7, 'Days Remaining', 1, 1, 'C', true);
            
            $pdf->SetFont('helvetica', '', 7);
            foreach ($upcomingExpirations as $expiration) {
                $daysRemaining = $expiration['days_remaining'] ?? 0;
                $pdf->Cell(50, 6, substr($expiration['boarder_name'] ?? 'N/A', 0, 25), 1, 0, 'L');
                $pdf->Cell(40, 6, substr($expiration['bh_name'] ?? 'N/A', 0, 20), 1, 0, 'L');
                $pdf->Cell(30, 6, substr($expiration['room'] ?? 'N/A', 0, 15), 1, 0, 'L');
                $pdf->Cell(30, 6, $expiration['rental_end'] ?? 'N/A', 1, 0, 'C');
                $pdf->Cell(30, 6, $daysRemaining . ' days', 1, 1, 'C');
            }
        } else {
            $pdf->SetFont('helvetica', '', 10);
            $pdf->Cell(0, 10, 'No upcoming expirations found (within 30 days).', 0, 1, 'L');
        }
        
        $pdf->Ln(10);
        
        // Remarks / Notes Section
        $pdf->AddPage();
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 10, 'Remarks / Notes', 0, 1, 'L');
        $pdf->Ln(5);
        
        $pdf->SetFont('helvetica', '', 10);
        $remarks = [];
        
        if ($occupancyRate > 0) {
            if ($occupancyRate >= 90) {
                $remarks[] = "• High occupancy rate (" . number_format($occupancyRate, 2) . "%) indicates strong demand. Consider expanding capacity if possible.";
            } elseif ($occupancyRate < 50) {
                $remarks[] = "• Low occupancy rate (" . number_format($occupancyRate, 2) . "%) may require marketing efforts or pricing adjustments.";
            } else {
                $remarks[] = "• Occupancy rate: " . number_format($occupancyRate, 2) . "% is within acceptable range.";
            }
        }
        
        $expirationCount = count($upcomingExpirations);
        if ($expirationCount > 0) {
            $remarks[] = "• " . $expirationCount . " rental(s) will expire within the next 30 days. Follow up with boarders for renewal.";
        }
        
        $canceledCount = $summary['total_canceled_rentals'] ?? 0;
        if ($canceledCount > 0) {
            $remarks[] = "• " . $canceledCount . " rental(s) were canceled. Review cancellation patterns to identify potential issues.";
        }
        
        $activeCount = $summary['total_active_rentals'] ?? 0;
        $completedCount = $summary['total_completed_rentals'] ?? 0;
        if ($activeCount > 0 && $completedCount > 0) {
            $completionRate = ($completedCount / ($activeCount + $completedCount)) * 100;
            $remarks[] = "• Completion rate: " . number_format($completionRate, 2) . "% of rentals have been completed successfully.";
        }
        
        if (empty($remarks)) {
            $remarks[] = "• No significant issues or patterns identified in the rental data.";
        }
        
        foreach ($remarks as $remark) {
            $pdf->Cell(0, 6, $remark, 0, 1, 'L');
        }
        
        $connection->close();
        
        return $pdf;
        
    } catch (Exception $e) {
        throw new Exception("Error generating report: " . $e->getMessage());
    }
}

// Handle the report request
if (isset($_GET['action']) && in_array($_GET['action'], ['rental_report_pdf', 'rental_report_preview'], true)) {
    try {
        // Clear any previous output
        ob_end_clean();
        
        $startDate = isset($_GET['start_date']) ? $_GET['start_date'] : null;
        $endDate = isset($_GET['end_date']) ? $_GET['end_date'] : null;
        $boardingHouseId = isset($_GET['boarding_house_id']) && $_GET['boarding_house_id'] != '' ? intval($_GET['boarding_house_id']) : null;
        $preview = ($_GET['action'] === 'rental_report_preview');
        
        $pdf = generateRentalReportPDF($startDate, $endDate, $boardingHouseId, $preview);
        
        // Output PDF
        if ($preview) {
            $filename = 'rental_report_preview.pdf';
            $pdf->Output($filename, 'I');
        } else {
            $filename = 'rental_report_' . date('Y-m-d_H-i-s') . '.pdf';
            $pdf->Output($filename, 'D');
        }
        exit;
        
    } catch (Exception $e) {
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

// If accessed directly without action parameter, return error
header('Content-Type: application/json');
echo json_encode(['success' => false, 'message' => 'Invalid request']);
?>

