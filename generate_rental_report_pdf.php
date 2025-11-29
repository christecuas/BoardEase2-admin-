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

// Function to determine balance status
function getBalanceStatus($totalPeriods, $paidPeriods, $overduePeriods) {
    // User requested to change "Overdue" to "Partially Paid"
    if ($overduePeriods > 0) {
        return 'Partially Paid'; 
    } elseif ($totalPeriods > 0 && $paidPeriods >= $totalPeriods) {
        return 'Fully Paid';
    } elseif ($paidPeriods > 0) {
        return 'Partially Paid';
    } else {
        return 'Unpaid / Pending';
    }
}

// Function to generate rental report PDF
function generateRentalReportPDF($startDate = null, $endDate = null, $boardingHouseId = null, $preview = false) {
    try {
        $connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if ($connection->connect_error) {
            throw new Exception("Connection failed: " . $connection->connect_error);
        }
        
        // Determine report context (boarding house name if specific BH selected)
        $reportContext = null;
        if ($boardingHouseId) {
            $bhStmt = $connection->prepare("SELECT bh_name FROM boarding_houses WHERE bh_id = ? LIMIT 1");
            if ($bhStmt) {
                $bhStmt->bind_param('i', $boardingHouseId);
                $bhStmt->execute();
                $bhResult = $bhStmt->get_result();
                if ($bhRow = $bhResult->fetch_assoc()) {
                    $reportContext = 'of ' . ($bhRow['bh_name'] ?? 'Selected Boarding House');
                }
                $bhStmt->close();
            }
        } else {
            $reportContext = 'All Boarding Houses Overview';
        }
        
        // Build WHERE conditions for main rental query
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
        
        $whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";
        
        // --- 1. Summary Section Data ---
        
        // Correct approach for Room Counts:
        $roomCountsQuery = "
            SELECT 
                COUNT(DISTINCT ru.room_id) as total_rooms,
                COUNT(DISTINCT CASE WHEN ru.status = 'Occupied' THEN ru.room_id END) as occupied_rooms,
                COUNT(DISTINCT CASE WHEN ru.status = 'Vacant' THEN ru.room_id END) as vacant_rooms,
                COUNT(DISTINCT CASE WHEN ru.status = 'Available' THEN ru.room_id END) as available_rooms
            FROM room_units ru
            LEFT JOIN boarding_house_rooms bhr ON ru.bhr_id = bhr.bhr_id
            LEFT JOIN boarding_houses bh ON bhr.bh_id = bh.bh_id
            " . ($boardingHouseId ? "WHERE bh.bh_id = $boardingHouseId" : "") . "
        ";
        $roomResult = $connection->query($roomCountsQuery);
        $roomStats = $roomResult->fetch_assoc();
        
        // Correct approach for Rental Counts:
        $rentalCountsQuery = "
            SELECT 
                COUNT(booking_id) as total_rentals,
                COUNT(CASE WHEN booking_status = 'Confirmed' AND (end_date IS NULL OR end_date >= CURDATE()) THEN 1 END) as active_rentals,
                COUNT(CASE WHEN booking_status = 'Completed' THEN 1 END) as completed_rentals,
                COUNT(CASE WHEN booking_status = 'Cancelled' THEN 1 END) as canceled_rentals
            FROM bookings b
            LEFT JOIN room_units ru ON b.room_id = ru.room_id
            LEFT JOIN boarding_house_rooms bhr ON ru.bhr_id = bhr.bhr_id
            LEFT JOIN boarding_houses bh ON bhr.bh_id = bh.bh_id
            $whereClause
        ";
        
        $stmt = $connection->prepare($rentalCountsQuery);
        if (!empty($params)) {
            $stmt->bind_param($paramTypes, ...$params);
        }
        $stmt->execute();
        $rentalStats = $stmt->get_result()->fetch_assoc();
        
        // Calculate occupancy rate
        $totalRooms = $roomStats['total_rooms'] ?? 0;
        $occupiedRooms = $roomStats['occupied_rooms'] ?? 0;
        $availableRooms = ($roomStats['vacant_rooms'] ?? 0) + ($roomStats['available_rooms'] ?? 0);
        $roomsAvailable = $totalRooms - $occupiedRooms;
        
        $occupancyRate = $totalRooms > 0 ? ($occupiedRooms / $totalRooms) * 100 : 0;
        
        // --- 2. Rental Details Table Data ---
        
        $detailsQuery = "
            SELECT 
                b.booking_id as rental_id,
                CONCAT(COALESCE(r.first_name, ''), ' ', COALESCE(r.middle_name, ''), ' ', COALESCE(r.last_name, ''), 
                       CASE WHEN r.suffix IS NOT NULL AND r.suffix != '' THEN CONCAT(' ', r.suffix) ELSE '' END) as boarder_name,
                COALESCE(bh.bh_name, 'N/A') as bh_name,
                             CONCAT(
    COALESCE(bhr.room_category, 'N/A'),       -- e.g., Private Room
    CASE WHEN bhr.room_name IS NOT NULL AND bhr.room_name != '' THEN CONCAT(' - ', bhr.room_name) ELSE '' END,
    CASE WHEN ru.room_number IS NOT NULL AND ru.room_number != '' THEN CONCAT(' (', ru.room_number, ')') ELSE '' END
) as room
,
                DATE_FORMAT(b.start_date, '%Y-%m-%d') as rental_start,
                DATE_FORMAT(b.end_date, '%Y-%m-%d') as rental_end,
                COALESCE(bhr.price, 0) as monthly_rate,
                b.booking_status as status,
                -- Payment Stats Subqueries
                (SELECT COUNT(*) FROM payment_breakdowns pb WHERE pb.booking_id = b.booking_id) as total_periods,
                (SELECT COUNT(*) FROM payment_breakdowns pb WHERE pb.booking_id = b.booking_id AND pb.is_paid = 1) as paid_periods,
                (SELECT COUNT(*) FROM payment_breakdowns pb WHERE pb.booking_id = b.booking_id AND (pb.is_paid = 0 OR pb.is_paid IS NULL) AND pb.due_date < CURDATE()) as overdue_periods
            FROM bookings b
            LEFT JOIN users u ON b.user_id = u.user_id
            LEFT JOIN registrations r ON u.reg_id = r.id
            LEFT JOIN room_units ru ON b.room_id = ru.room_id
            LEFT JOIN boarding_house_rooms bhr ON ru.bhr_id = bhr.bhr_id
            LEFT JOIN boarding_houses bh ON bhr.bh_id = bh.bh_id
            $whereClause
            ORDER BY b.booking_id ASC
        ";
        
        $stmt = $connection->prepare($detailsQuery);
        if (!empty($params)) {
            $stmt->bind_param($paramTypes, ...$params);
        }
        $stmt->execute();
        $detailsResult = $stmt->get_result();
        $rentalDetails = [];
        while ($row = $detailsResult->fetch_assoc()) {
            $row['balance_status'] = getBalanceStatus($row['total_periods'], $row['paid_periods'], $row['overdue_periods']);
            $rentalDetails[] = $row;
        }
        
        // --- 3. Upcoming Expirations Data ---
        
        $expirationQuery = "
            SELECT 
                b.booking_id,
                CONCAT(COALESCE(r.first_name, ''), ' ', COALESCE(r.middle_name, ''), ' ', COALESCE(r.last_name, ''), 
                       CASE WHEN r.suffix IS NOT NULL AND r.suffix != '' THEN CONCAT(' ', r.suffix) ELSE '' END) as boarder_name,
                COALESCE(bh.bh_name, 'N/A') as bh_name,
                CONCAT(
                    COALESCE(bhr.room_category, 'N/A'),
                    CASE WHEN bhr.room_name IS NOT NULL AND bhr.room_name != '' THEN CONCAT(' - ', bhr.room_name) ELSE '' END,
                    CASE WHEN ru.room_number IS NOT NULL AND ru.room_number != '' THEN CONCAT(' ', ru.room_number) ELSE '' END
                ) as room,
                DATE_FORMAT(b.end_date, '%Y-%m-%d') as rental_end,
                DATEDIFF(b.end_date, CURDATE()) as days_remaining,
                -- Payment Stats
                (SELECT COUNT(*) FROM payment_breakdowns pb WHERE pb.booking_id = b.booking_id) as total_periods,
                (SELECT COUNT(*) FROM payment_breakdowns pb WHERE pb.booking_id = b.booking_id AND pb.is_paid = 1) as paid_periods,
                (SELECT COUNT(*) FROM payment_breakdowns pb WHERE pb.booking_id = b.booking_id AND (pb.is_paid = 0 OR pb.is_paid IS NULL) AND pb.due_date < CURDATE()) as overdue_periods
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
        
        if ($boardingHouseId) {
            $expirationQuery .= " AND bh.bh_id = $boardingHouseId";
        }
        
        $expirationQuery .= " ORDER BY b.booking_id ASC";
        
        $expirationResult = $connection->query($expirationQuery);
        $upcomingExpirations = [];
        while ($row = $expirationResult->fetch_assoc()) {
            $row['balance_status'] = getBalanceStatus($row['total_periods'], $row['paid_periods'], $row['overdue_periods']);
            $upcomingExpirations[] = $row;
        }
        
        // --- 4. Newly Started Rentals Data ---
        
        $newRentalsQuery = "
            SELECT 
                b.booking_id,
                CONCAT(COALESCE(r.first_name, ''), ' ', COALESCE(r.middle_name, ''), ' ', COALESCE(r.last_name, ''), 
                       CASE WHEN r.suffix IS NOT NULL AND r.suffix != '' THEN CONCAT(' ', r.suffix) ELSE '' END) as boarder_name,
                COALESCE(bh.bh_name, 'N/A') as bh_name,
                CONCAT(
                    COALESCE(bhr.room_category, 'N/A'),
                    CASE WHEN bhr.room_name IS NOT NULL AND bhr.room_name != '' THEN CONCAT(' - ', bhr.room_name) ELSE '' END,
                    CASE WHEN ru.room_number IS NOT NULL AND ru.room_number != '' THEN CONCAT(' ', ru.room_number) ELSE '' END
                ) as room,
                DATE_FORMAT(b.start_date, '%Y-%m-%d') as start_date,
                COALESCE(bhr.price, 0) as monthly_rate,
                -- Payment Stats
                (SELECT COUNT(*) FROM payment_breakdowns pb WHERE pb.booking_id = b.booking_id) as total_periods,
                (SELECT COUNT(*) FROM payment_breakdowns pb WHERE pb.booking_id = b.booking_id AND pb.is_paid = 1) as paid_periods,
                (SELECT COUNT(*) FROM payment_breakdowns pb WHERE pb.booking_id = b.booking_id AND (pb.is_paid = 0 OR pb.is_paid IS NULL) AND pb.due_date < CURDATE()) as overdue_periods
            FROM bookings b
            LEFT JOIN users u ON b.user_id = u.user_id
            LEFT JOIN registrations r ON u.reg_id = r.id
            LEFT JOIN room_units ru ON b.room_id = ru.room_id
            LEFT JOIN boarding_house_rooms bhr ON ru.bhr_id = bhr.bhr_id
            LEFT JOIN boarding_houses bh ON bhr.bh_id = bh.bh_id
            WHERE 1=1
        ";
        
        if ($startDate) {
            $newRentalsQuery .= " AND b.start_date >= '$startDate'";
        } else {
            // Default to last 30 days if no start date
            $newRentalsQuery .= " AND b.start_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
        }
        
        if ($endDate) {
            $newRentalsQuery .= " AND b.start_date <= '$endDate'";
        }
        
        if ($boardingHouseId) {
            $newRentalsQuery .= " AND bh.bh_id = $boardingHouseId";
        }
        
        $newRentalsQuery .= " ORDER BY b.booking_id ASC";
        
        $newRentalsResult = $connection->query($newRentalsQuery);
        $newRentals = [];
        while ($row = $newRentalsResult->fetch_assoc()) {
            $row['balance_status'] = getBalanceStatus($row['total_periods'], $row['paid_periods'], $row['overdue_periods']);
            $newRentals[] = $row;
        }
        
        // --- 5. Vacant & Available Rooms Data ---
        
        $vacantRoomsQuery = "
            SELECT 
                COALESCE(bh.bh_name, 'N/A') as bh_name,
                CONCAT(
                    COALESCE(bhr.room_category, 'N/A'),
                    CASE WHEN bhr.room_name IS NOT NULL AND bhr.room_name != '' THEN CONCAT(' - ', bhr.room_name) ELSE '' END,
                    CASE WHEN ru.room_number IS NOT NULL AND ru.room_number != '' THEN CONCAT(' ', ru.room_number) ELSE '' END
                ) as room,
                ru.status,
                COALESCE(bhr.price, 0) as monthly_rate
            FROM room_units ru
            LEFT JOIN boarding_house_rooms bhr ON ru.bhr_id = bhr.bhr_id
            LEFT JOIN boarding_houses bh ON bhr.bh_id = bh.bh_id
            WHERE ru.status IN ('Vacant', 'Available')
        ";
        
        if ($boardingHouseId) {
            $vacantRoomsQuery .= " AND bh.bh_id = $boardingHouseId";
        }
        
        $vacantRoomsQuery .= " ORDER BY ru.room_id ASC";
        
        $vacantRoomsResult = $connection->query($vacantRoomsQuery);
        $vacantRooms = [];
        while ($row = $vacantRoomsResult->fetch_assoc()) {
            $vacantRooms[] = $row;
        }
        
        // --- Generate PDF ---
        
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
        $pdf->SetFont('helvetica', 'B', 18);
        
        // Header (Matched to Payment Report)
        $pdf->Cell(0, 8, 'BoardEase Rental Report', 0, 1, 'C');
        if (!empty($reportContext)) {
            $pdf->SetFont('helvetica', '', 12);
            $pdf->Cell(0, 6, $reportContext, 0, 1, 'C');
        }
        $pdf->Ln(3);
        
        $pdf->SetFont('helvetica', '', 9);
        $reportingPeriod = '';
        if ($startDate && $endDate) {
            $reportingPeriod = date('M d, Y', strtotime($startDate)) . ' - ' . date('M d, Y', strtotime($endDate));
        } elseif ($startDate) {
            $reportingPeriod = 'From ' . date('M d, Y', strtotime($startDate));
        } elseif ($endDate) {
            $reportingPeriod = 'Until ' . date('M d, Y', strtotime($endDate));
        } else {
            $reportingPeriod = 'All Time';
        }
        
        $pdf->Cell(0, 4, 'Reporting Period: ' . $reportingPeriod, 0, 1, 'C');
        $pdf->Cell(0, 4, 'Generated By: System Administrator', 0, 1, 'C');
        $pdf->Cell(0, 4, 'Date Generated: ' . date('M d, Y h:i A'), 0, 1, 'C');
        $pdf->Ln(8);
        
        // 2. Summary Section
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 10, 'Summary', 0, 1, 'L');
        $pdf->Ln(2);
        
        $pdf->SetFont('helvetica', '', 10);
        $summaryData = [
            ['Total Rentals (All-Time)', number_format($rentalStats['total_rentals'] ?? 0)],
            ['Active Rentals', number_format($rentalStats['active_rentals'] ?? 0)],
            ['Completed Rentals', number_format($rentalStats['completed_rentals'] ?? 0)],
            ['Canceled Rentals', number_format($rentalStats['canceled_rentals'] ?? 0)],
            ['Total Rooms Across System', number_format($totalRooms)],
            ['Rooms Occupied', number_format($occupiedRooms)],
            ['Rooms Available', number_format($roomsAvailable)],
            ['System-Wide Occupancy Rate', number_format($occupancyRate, 2) . '%']
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
        
        // 3. Rental Details Table (Main Table)
        if (!empty($rentalDetails)) {
            $pdf->SetFont('helvetica', 'B', 14);
            $pdf->Cell(0, 10, 'Rental Details', 0, 1, 'L');
            $pdf->Ln(2);
            
            $pdf->SetFont('helvetica', '', 8);
            $pdf->SetFillColor(245, 245, 245);
            
            // Table header function for reuse
            $printRentalDetailsHeader = function($pdf) {
                $pdf->SetFont('helvetica', 'B', 8);
                $pdf->SetFillColor(245, 245, 245);
                $pdf->Cell(12, 7, 'ID', 1, 0, 'C', true);
                $pdf->Cell(40, 7, 'Boarder Name', 1, 0, 'L', true);
                $pdf->Cell(20, 7, 'BH', 1, 0, 'L', true);
                $pdf->Cell(15, 7, 'Room', 1, 0, 'L', true);
                $pdf->Cell(18, 7, 'Start', 1, 0, 'C', true);
                $pdf->Cell(18, 7, 'End', 1, 0, 'C', true);
                $pdf->Cell(18, 7, 'Rate', 1, 0, 'R', true);
                $pdf->Cell(15, 7, 'Status', 1, 0, 'C', true);
                $pdf->Cell(24, 7, 'Payment Status', 1, 1, 'C', true);
                $pdf->SetFont('helvetica', '', 7);
            };
            
            $printRentalDetailsHeader($pdf);
            
            $pdf->SetFont('helvetica', '', 7);
            foreach ($rentalDetails as $rental) {
                // Determine row height based on content
                $boarderName = $rental['boarder_name'] ?? 'N/A';
                $bhName = $rental['bh_name'] ?? 'N/A';
                $roomName = $rental['room'] ?? 'N/A';
                
                // Use MultiCell for text fields to handle wrapping and avoid truncation
                $nbLinesBoarder = $pdf->getNumLines($boarderName, 40);
                $nbLinesBH = $pdf->getNumLines($bhName, 20);
                $nbLinesRoom = $pdf->getNumLines($roomName, 15);
                
                $maxLines = max($nbLinesBoarder, $nbLinesBH, $nbLinesRoom);
                $rowHeight = $maxLines * 4; // 4mm per line approximately
                
                // Ensure minimum height
                if ($rowHeight < 6) $rowHeight = 6;
                
                // Check for page break
                if ($pdf->GetY() + $rowHeight > $pdf->getPageHeight() - $pdf->getBreakMargin()) {
                    $pdf->AddPage();
                    $printRentalDetailsHeader($pdf);
                }
                
                // Print cells
                $pdf->MultiCell(12, $rowHeight, $rental['rental_id'] ?? 'N/A', 1, 'C', false, 0, '', '', true, 0, false, true, $rowHeight, 'M');
                $pdf->MultiCell(40, $rowHeight, $boarderName, 1, 'L', false, 0, '', '', true, 0, false, true, $rowHeight, 'M');
                $pdf->MultiCell(20, $rowHeight, $bhName, 1, 'L', false, 0, '', '', true, 0, false, true, $rowHeight, 'M');
                $pdf->MultiCell(15, $rowHeight, $roomName, 1, 'L', false, 0, '', '', true, 0, false, true, $rowHeight, 'M');
                $pdf->MultiCell(18, $rowHeight, $rental['rental_start'] ?? 'N/A', 1, 'C', false, 0, '', '', true, 0, false, true, $rowHeight, 'M');
                $pdf->MultiCell(18, $rowHeight, $rental['rental_end'] ?? 'N/A', 1, 'C', false, 0, '', '', true, 0, false, true, $rowHeight, 'M');
                $pdf->MultiCell(18, $rowHeight, 'P' . number_format($rental['monthly_rate'] ?? 0, 0), 1, 'R', false, 0, '', '', true, 0, false, true, $rowHeight, 'M');
                $pdf->MultiCell(15, $rowHeight, $rental['status'] ?? 'N/A', 1, 'C', false, 0, '', '', true, 0, false, true, $rowHeight, 'M');
                $pdf->MultiCell(24, $rowHeight, $rental['balance_status'], 1, 'C', false, 1, '', '', true, 0, false, true, $rowHeight, 'M');
            }
        } else {
            $pdf->SetFont('helvetica', '', 10);
            $pdf->Cell(0, 10, 'No rental details found for the selected criteria.', 0, 1, 'L');
        }
        
        $pdf->Ln(10);
        
        // 4. Upcoming Rental Expirations
        if (!empty($upcomingExpirations)) {
            $pdf->AddPage();
            $pdf->SetFont('helvetica', 'B', 14);
            $pdf->Cell(0, 10, 'Upcoming Rental Expirations', 0, 1, 'L');
            $pdf->Ln(2);
            
            $pdf->SetFont('helvetica', '', 8);
            $pdf->SetFillColor(255, 250, 240);
            
            // Header function
            $printExpirationHeader = function($pdf) {
                $pdf->SetFont('helvetica', 'B', 8);
                $pdf->SetFillColor(255, 250, 240);
                $pdf->Cell(45, 7, 'Boarder Name', 1, 0, 'L', true);
                $pdf->Cell(35, 7, 'Boarding House', 1, 0, 'L', true);
                $pdf->Cell(25, 7, 'Room', 1, 0, 'L', true);
                $pdf->Cell(25, 7, 'End Date', 1, 0, 'C', true);
                $pdf->Cell(20, 7, 'Days Left', 1, 0, 'C', true);
                $pdf->Cell(30, 7, 'Payment Status', 1, 1, 'C', true);
                $pdf->SetFont('helvetica', '', 7);
            };
            
            $printExpirationHeader($pdf);
            
            $pdf->SetFont('helvetica', '', 7);
            foreach ($upcomingExpirations as $expiration) {
                $daysRemaining = $expiration['days_remaining'] ?? 0;
                $boarderName = $expiration['boarder_name'] ?? 'N/A';
                $bhName = $expiration['bh_name'] ?? 'N/A';
                $roomName = $expiration['room'] ?? 'N/A';
                
                // Use MultiCell for text fields
                $nbLinesBoarder = $pdf->getNumLines($boarderName, 45);
                $nbLinesBH = $pdf->getNumLines($bhName, 35);
                $nbLinesRoom = $pdf->getNumLines($roomName, 25);
                
                $maxLines = max($nbLinesBoarder, $nbLinesBH, $nbLinesRoom);
                $rowHeight = $maxLines * 4;
                if ($rowHeight < 6) $rowHeight = 6;
                
                // Check for page break
                if ($pdf->GetY() + $rowHeight > $pdf->getPageHeight() - $pdf->getBreakMargin()) {
                    $pdf->AddPage();
                    $printExpirationHeader($pdf);
                }
                
                $pdf->MultiCell(45, $rowHeight, $boarderName, 1, 'L', false, 0, '', '', true, 0, false, true, $rowHeight, 'M');
                $pdf->MultiCell(35, $rowHeight, $bhName, 1, 'L', false, 0, '', '', true, 0, false, true, $rowHeight, 'M');
                $pdf->MultiCell(25, $rowHeight, $roomName, 1, 'L', false, 0, '', '', true, 0, false, true, $rowHeight, 'M');
                $pdf->MultiCell(25, $rowHeight, $expiration['rental_end'] ?? 'N/A', 1, 'C', false, 0, '', '', true, 0, false, true, $rowHeight, 'M');
                $pdf->MultiCell(20, $rowHeight, $daysRemaining . ' days', 1, 'C', false, 0, '', '', true, 0, false, true, $rowHeight, 'M');
                $pdf->MultiCell(30, $rowHeight, $expiration['balance_status'], 1, 'C', false, 1, '', '', true, 0, false, true, $rowHeight, 'M');
            }
        } else {
            $pdf->SetFont('helvetica', '', 10);
            $pdf->Cell(0, 10, 'No upcoming expirations found (within 30 days).', 0, 1, 'L');
        }
        
        $pdf->Ln(10);
        
        // 5. Newly Started Rentals
        if (!empty($newRentals)) {
            $pdf->SetFont('helvetica', 'B', 14);
            $pdf->Cell(0, 10, 'Newly Started Rentals', 0, 1, 'L');
            $pdf->Ln(2);
            
            $pdf->SetFont('helvetica', '', 8);
            $pdf->SetFillColor(240, 255, 240);
            
            // Header function
            $printNewRentalsHeader = function($pdf) {
                $pdf->SetFont('helvetica', 'B', 8);
                $pdf->SetFillColor(240, 255, 240);
                $pdf->Cell(45, 7, 'Boarder Name', 1, 0, 'L', true);
                $pdf->Cell(35, 7, 'Boarding House', 1, 0, 'L', true);
                $pdf->Cell(25, 7, 'Room', 1, 0, 'L', true);
                $pdf->Cell(25, 7, 'Start Date', 1, 0, 'C', true);
                $pdf->Cell(25, 7, 'Monthly Rate', 1, 0, 'R', true);
                $pdf->Cell(25, 7, 'Payment Status', 1, 1, 'C', true);
                $pdf->SetFont('helvetica', '', 7);
            };
            
            $printNewRentalsHeader($pdf);
            
            $pdf->SetFont('helvetica', '', 7);
            foreach ($newRentals as $newRental) {
                $boarderName = $newRental['boarder_name'] ?? 'N/A';
                $bhName = $newRental['bh_name'] ?? 'N/A';
                $roomName = $newRental['room'] ?? 'N/A';
                
                // Use MultiCell for text fields
                $nbLinesBoarder = $pdf->getNumLines($boarderName, 45);
                $nbLinesBH = $pdf->getNumLines($bhName, 35);
                $nbLinesRoom = $pdf->getNumLines($roomName, 25);
                
                $maxLines = max($nbLinesBoarder, $nbLinesBH, $nbLinesRoom);
                $rowHeight = $maxLines * 4;
                if ($rowHeight < 6) $rowHeight = 6;
                
                // Check for page break
                if ($pdf->GetY() + $rowHeight > $pdf->getPageHeight() - $pdf->getBreakMargin()) {
                    $pdf->AddPage();
                    $printNewRentalsHeader($pdf);
                }
                
                $pdf->MultiCell(45, $rowHeight, $boarderName, 1, 'L', false, 0, '', '', true, 0, false, true, $rowHeight, 'M');
                $pdf->MultiCell(35, $rowHeight, $bhName, 1, 'L', false, 0, '', '', true, 0, false, true, $rowHeight, 'M');
                $pdf->MultiCell(25, $rowHeight, $roomName, 1, 'L', false, 0, '', '', true, 0, false, true, $rowHeight, 'M');
                $pdf->MultiCell(25, $rowHeight, $newRental['start_date'] ?? 'N/A', 1, 'C', false, 0, '', '', true, 0, false, true, $rowHeight, 'M');
                $pdf->MultiCell(25, $rowHeight, 'P' . number_format($newRental['monthly_rate'] ?? 0, 2), 1, 'R', false, 0, '', '', true, 0, false, true, $rowHeight, 'M');
                $pdf->MultiCell(25, $rowHeight, $newRental['balance_status'], 1, 'C', false, 1, '', '', true, 0, false, true, $rowHeight, 'M');
            }
        } else {
            $pdf->SetFont('helvetica', '', 10);
            $pdf->Cell(0, 10, 'No newly started rentals found in the selected period.', 0, 1, 'L');
        }
        
        $pdf->Ln(10);
        
        // 6. Vacant & Available Rooms
        if (!empty($vacantRooms)) {
            $pdf->AddPage();
            $pdf->SetFont('helvetica', 'B', 14);
            $pdf->Cell(0, 10, 'Vacant & Available Rooms', 0, 1, 'L');
            $pdf->Ln(2);
            
            $pdf->SetFont('helvetica', '', 8);
            $pdf->SetFillColor(240, 240, 255);
            
            // Header function
            $printVacantRoomsHeader = function($pdf) {
                $pdf->SetFont('helvetica', 'B', 8);
                $pdf->SetFillColor(240, 240, 255);
                $pdf->Cell(60, 7, 'Boarding House', 1, 0, 'L', true);
                $pdf->Cell(40, 7, 'Room', 1, 0, 'L', true);
                $pdf->Cell(30, 7, 'Status', 1, 0, 'C', true);
                $pdf->Cell(30, 7, 'Monthly Rate', 1, 1, 'R', true);
                $pdf->SetFont('helvetica', '', 7);
            };
            
            $printVacantRoomsHeader($pdf);
            
            $pdf->SetFont('helvetica', '', 7);
            foreach ($vacantRooms as $room) {
                $bhName = $room['bh_name'] ?? 'N/A';
                $roomName = $room['room'] ?? 'N/A';
                
                // Use MultiCell for text fields
                $nbLinesBH = $pdf->getNumLines($bhName, 60);
                $nbLinesRoom = $pdf->getNumLines($roomName, 40);
                
                $maxLines = max($nbLinesBH, $nbLinesRoom);
                $rowHeight = $maxLines * 4;
                if ($rowHeight < 6) $rowHeight = 6;
                
                // Check for page break
                if ($pdf->GetY() + $rowHeight > $pdf->getPageHeight() - $pdf->getBreakMargin()) {
                    $pdf->AddPage();
                    $printVacantRoomsHeader($pdf);
                }
                
                $pdf->MultiCell(60, $rowHeight, $bhName, 1, 'L', false, 0, '', '', true, 0, false, true, $rowHeight, 'M');
                $pdf->MultiCell(40, $rowHeight, $roomName, 1, 'L', false, 0, '', '', true, 0, false, true, $rowHeight, 'M');
                $pdf->MultiCell(30, $rowHeight, $room['status'] ?? 'Vacant', 1, 'C', false, 0, '', '', true, 0, false, true, $rowHeight, 'M');
                $pdf->MultiCell(30, $rowHeight, 'P' . number_format($room['monthly_rate'] ?? 0, 2), 1, 'R', false, 1, '', '', true, 0, false, true, $rowHeight, 'M');
            }
        } else {
            $pdf->SetFont('helvetica', '', 10);
            $pdf->Cell(0, 10, 'No vacant rooms found.', 0, 1, 'L');
        }
        
        $pdf->Ln(10);
        
        // 7. Remarks / Notes
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 10, 'Remarks / Notes', 0, 1, 'L');
        $pdf->Ln(5);
        
        $pdf->SetFont('helvetica', '', 10);
        $remarks = [];
        
        // Canceled rentals
        $canceledCount = $rentalStats['canceled_rentals'] ?? 0;
        if ($canceledCount > 0) {
            $remarks[] = "• $canceledCount rental(s) were canceled. Review cancellation patterns to identify potential issues.";
        }
        
        // Occupancy
        if ($occupancyRate >= 90) {
            $remarks[] = "• High occupancy rate (" . number_format($occupancyRate, 2) . "%) indicates strong demand. Consider expanding capacity if possible.";
        } elseif ($occupancyRate < 50) {
            $remarks[] = "• Low occupancy rate (" . number_format($occupancyRate, 2) . "%) may require marketing efforts or pricing adjustments.";
        }
        
        // Expirations
        $expirationCount = count($upcomingExpirations);
        if ($expirationCount > 0) {
            $remarks[] = "• $expirationCount rental(s) will expire within the next 30 days. Follow up with boarders for renewal.";
        }
        
        // Vacancies
        $vacantCount = count($vacantRooms);
        if ($vacantCount > 0) {
            $remarks[] = "• There are currently $vacantCount vacant room(s) available for rent.";
        }
        
        if (empty($remarks)) {
            $remarks[] = "• No significant irregularities detected.";
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
