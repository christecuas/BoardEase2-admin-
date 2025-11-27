<?php
/**
 * Payment Report PDF Generator
 * Generates comprehensive payment reports in PDF format for system administrators
 * Shows Payment Summary (from payments table) and Breakdown Details (from payment_breakdowns table)
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

// Function to generate payment report PDF
function generatePaymentReportPDF($startDate = null, $endDate = null, $boardingHouseId = null, $preview = false) {
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
        
        // Build WHERE conditions for payments table
        $paymentWhereConditions = [];
        $paymentParams = [];
        $paymentParamTypes = '';
        
        if ($startDate) {
            $paymentWhereConditions[] = "(p.payment_date >= ? OR p.payment_date IS NULL)";
            $paymentParams[] = $startDate;
            $paymentParamTypes .= 's';
        }
        
        if ($endDate) {
            $paymentWhereConditions[] = "(p.payment_date <= ? OR p.payment_date IS NULL)";
            $paymentParams[] = $endDate;
            $paymentParamTypes .= 's';
        }
        
        if ($boardingHouseId) {
            $paymentWhereConditions[] = "bh.bh_id = ?";
            $paymentParams[] = $boardingHouseId;
            $paymentParamTypes .= 'i';
        }
        
        // Build WHERE conditions for payment_breakdowns
        // NOTE: We don't apply date filters to breakdowns when showing payment-level statuses
        // because we want to show ALL breakdowns (pending, paid, overdue) for those payments
        // Date filters are only applied when filtering by breakdown-level status (Paid, Overdue)
        // Initialize breakdown params - these will be populated when building the query
        $breakdownParams = [];
        $breakdownParamTypes = '';
        
        // Query for summary statistics - all based on payment_breakdowns table
        // Get total boarders and payments from payments table, but amounts from breakdowns
        $summaryQuery = "
            SELECT 
                COUNT(DISTINCT u.user_id) as total_boarders,
                COUNT(DISTINCT p.payment_id) as total_payments_made
            FROM payments p
            LEFT JOIN users u ON p.user_id = u.user_id
            LEFT JOIN bookings b ON p.booking_id = b.booking_id
            LEFT JOIN room_units ru ON b.room_id = ru.room_id
            LEFT JOIN boarding_house_rooms bhr ON ru.bhr_id = bhr.bhr_id
            LEFT JOIN boarding_houses bh ON bhr.bh_id = bh.bh_id
            " . (!empty($paymentWhereConditions) ? "WHERE " . implode(" AND ", $paymentWhereConditions) : "") . "
        ";
        
        // Note: We'll calculate these after getting payment summaries to ensure we only count
        // breakdowns from bookings that are in the filtered payment summary
        // This ensures consistency between the summary and breakdown details
        
        // Execute summary query
        $stmt = $connection->prepare($summaryQuery);
        if (!empty($paymentParams)) {
            $stmt->bind_param($paymentParamTypes, ...$paymentParams);
        }
        $stmt->execute();
        $summaryResult = $stmt->get_result();
        $summary = $summaryResult->fetch_assoc();
        
        // Query for Payment Summary (from payments table)
        $paymentSummaryQuery = "
            SELECT 
                p.payment_id,
                p.booking_id,
                CONCAT(COALESCE(r.first_name, ''), ' ', COALESCE(r.last_name, ''), 
                       CASE WHEN r.suffix IS NOT NULL AND r.suffix != '' THEN CONCAT(' ', r.suffix) ELSE '' END) as boarder_name,
                COALESCE(bh.bh_name, 'N/A') as bh_name,
                CONCAT(
                    COALESCE(bhr.room_category, 'N/A'),
                    CASE WHEN bhr.room_name IS NOT NULL AND bhr.room_name != '' THEN CONCAT(' - ', bhr.room_name) ELSE '' END,
                    CASE WHEN ru.room_number IS NOT NULL AND ru.room_number != '' THEN CONCAT(CHAR(10), ru.room_number) ELSE '' END
                ) as room,
                COALESCE(SUM(pb.amount), COALESCE(p.payment_amount, 0)) as total_amount,
                COALESCE(SUM(CASE WHEN pb.is_paid = 1 THEN pb.amount ELSE 0 END), 0) as amount_paid,
                GREATEST(0, COALESCE(SUM(pb.amount), COALESCE(p.payment_amount, 0)) - COALESCE(SUM(CASE WHEN pb.is_paid = 1 THEN pb.amount ELSE 0 END), 0)) as amount_balance,
                CASE 
                    WHEN COUNT(pb.breakdown_id) = 0 OR COUNT(pb.breakdown_id) IS NULL THEN 
                        CASE 
                            WHEN COALESCE(p.payment_status, '') IN ('Pending', 'For Approval', '') THEN 'Pending'
                            ELSE COALESCE(p.payment_status, 'Pending')
                        END
                    WHEN SUM(CASE WHEN pb.is_paid = 1 THEN 1 ELSE 0 END) = COUNT(pb.breakdown_id) AND COUNT(pb.breakdown_id) > 0 THEN 'Fully Paid'
                    WHEN SUM(CASE WHEN pb.is_paid = 1 THEN 1 ELSE 0 END) > 0 AND SUM(CASE WHEN pb.is_paid = 1 THEN 1 ELSE 0 END) < COUNT(pb.breakdown_id) THEN 'Partially Paid'
                    ELSE 'Pending'
                END as payment_status
            FROM payments p
            LEFT JOIN users u ON p.user_id = u.user_id
            LEFT JOIN registrations r ON u.reg_id = r.id
            LEFT JOIN bookings b ON p.booking_id = b.booking_id
            LEFT JOIN room_units ru ON b.room_id = ru.room_id
            LEFT JOIN boarding_house_rooms bhr ON ru.bhr_id = bhr.bhr_id
            LEFT JOIN boarding_houses bh ON bhr.bh_id = bh.bh_id
            LEFT JOIN payment_breakdowns pb ON p.booking_id = pb.booking_id AND (pb.payment_status IS NULL OR pb.payment_status != 'Cancelled')
            " . (!empty($paymentWhereConditions) ? "WHERE " . implode(" AND ", $paymentWhereConditions) : "") . "
            GROUP BY p.payment_id, p.payment_amount, p.payment_status, p.booking_id, r.first_name, r.last_name, r.suffix, 
                     bh.bh_name, bhr.room_category, bhr.room_name, ru.room_number
            ORDER BY COALESCE(p.payment_date, p.created_at, NOW()) DESC, p.payment_id DESC
        ";
        
        // Status filter removed - show all payments
        
        $stmt = $connection->prepare($paymentSummaryQuery);
        if (!empty($paymentParams)) {
            $stmt->bind_param($paymentParamTypes, ...$paymentParams);
        }
        $stmt->execute();
        $paymentSummaryResult = $stmt->get_result();
        $paymentSummaries = [];
        while ($row = $paymentSummaryResult->fetch_assoc()) {
            $paymentSummaries[] = $row;
        }
        
        // Get booking IDs from payment summaries for breakdown query
        // CRITICAL: Get ALL booking IDs to fetch ALL breakdowns (pending, paid, overdue)
        $bookingIds = [];
        if (!empty($paymentSummaries)) {
            foreach ($paymentSummaries as $payment) {
                if (!empty($payment['booking_id'])) {
                    $bookingIds[] = $payment['booking_id'];
                }
            }
            $bookingIds = array_unique($bookingIds);
        }
        
        // Calculate summary metrics from breakdowns for bookings in the payment summary
        // This ensures consistency - we only count breakdowns from payments shown in the report
        $summary['total_amount_collected'] = 0;
        $summary['total_pending_payments'] = 0;
        $summary['total_overdue_payments'] = 0;
        
        if (!empty($bookingIds)) {
            $placeholders = implode(',', array_fill(0, count($bookingIds), '?'));
            
            // Query for amount collected (Paid status)
            $amountCollectedQuery = "
                SELECT COALESCE(SUM(pb.amount), 0) as total_amount_collected
                FROM payment_breakdowns pb
                LEFT JOIN bookings b ON pb.booking_id = b.booking_id
                LEFT JOIN room_units ru ON b.room_id = ru.room_id
                LEFT JOIN boarding_house_rooms bhr ON ru.bhr_id = bhr.bhr_id
                LEFT JOIN boarding_houses bh ON bhr.bh_id = bh.bh_id
                WHERE (pb.payment_status IS NULL OR pb.payment_status != 'Cancelled')
                    AND pb.is_paid = 1
                    AND pb.booking_id IN ($placeholders)
            ";
            
            // Query for pending payments (Pending status)
            $pendingQuery = "
                SELECT COALESCE(SUM(pb.amount), 0) as total_pending_payments
                FROM payment_breakdowns pb
                LEFT JOIN bookings b ON pb.booking_id = b.booking_id
                LEFT JOIN room_units ru ON b.room_id = ru.room_id
                LEFT JOIN boarding_house_rooms bhr ON ru.bhr_id = bhr.bhr_id
                LEFT JOIN boarding_houses bh ON bhr.bh_id = bh.bh_id
                WHERE (pb.payment_status IS NULL OR pb.payment_status != 'Cancelled')
                    AND pb.is_paid = 0
                    AND (pb.due_date IS NULL OR pb.due_date >= CURDATE())
                    AND pb.booking_id IN ($placeholders)
            ";
            
            // Query for overdue payments (Overdue status)
            $overdueQuery = "
                SELECT COALESCE(SUM(pb.amount), 0) as total_overdue_payments
                FROM payment_breakdowns pb
                LEFT JOIN bookings b ON pb.booking_id = b.booking_id
                LEFT JOIN room_units ru ON b.room_id = ru.room_id
                LEFT JOIN boarding_house_rooms bhr ON ru.bhr_id = bhr.bhr_id
                LEFT JOIN boarding_houses bh ON bhr.bh_id = bh.bh_id
                WHERE (pb.payment_status IS NULL OR pb.payment_status != 'Cancelled')
                    AND pb.is_paid = 0
                    AND pb.due_date IS NOT NULL
                    AND pb.due_date < CURDATE()
                    AND pb.booking_id IN ($placeholders)
            ";
            
            // Add boarding house filter if specified
            if ($boardingHouseId) {
                $amountCollectedQuery .= " AND bh.bh_id = ?";
                $pendingQuery .= " AND bh.bh_id = ?";
                $overdueQuery .= " AND bh.bh_id = ?";
            }
            
            // Execute amount collected query
            $amountCollectedStmt = $connection->prepare($amountCollectedQuery);
            $params = $bookingIds;
            $paramTypes = str_repeat('i', count($bookingIds));
            if ($boardingHouseId) {
                $params[] = $boardingHouseId;
                $paramTypes .= 'i';
            }
            $amountCollectedStmt->bind_param($paramTypes, ...$params);
            $amountCollectedStmt->execute();
            $amountCollectedResult = $amountCollectedStmt->get_result();
            $amountCollectedRow = $amountCollectedResult->fetch_assoc();
            $summary['total_amount_collected'] = $amountCollectedRow['total_amount_collected'] ?? 0;
            
            // Execute pending query
            $pendingStmt = $connection->prepare($pendingQuery);
            $pendingStmt->bind_param($paramTypes, ...$params);
            $pendingStmt->execute();
            $pendingResult = $pendingStmt->get_result();
            $pendingRow = $pendingResult->fetch_assoc();
            $summary['total_pending_payments'] = $pendingRow['total_pending_payments'] ?? 0;
            
            // Execute overdue query
            $overdueStmt = $connection->prepare($overdueQuery);
            $overdueStmt->bind_param($paramTypes, ...$params);
            $overdueStmt->execute();
            $overdueResult = $overdueStmt->get_result();
            $overdueRow = $overdueResult->fetch_assoc();
            $summary['total_overdue_payments'] = $overdueRow['total_overdue_payments'] ?? 0;
        }
        
        // Query for Payment Breakdown Details (from payment_breakdowns table)
        // CRITICAL: This query must include ALL breakdowns (pending, paid, overdue) for the bookings
        // IMPORTANT: Include breakdowns even if they don't have a payment_id yet (pending breakdowns)
        // We MUST get ALL breakdowns for each booking to show the complete payment schedule
        $breakdownDetailsQuery = "
            SELECT 
                pb.breakdown_id,
                pb.payment_id,
                pb.booking_id,
                pb.period_start_date,
                pb.period_number,
                DATE_FORMAT(COALESCE(pb.due_date, pb.period_start_date), '%Y-%m-%d') as due_date,
                pb.amount,
                pb.is_paid,
                CASE 
                    WHEN pb.is_paid = 1 AND p.payment_date IS NOT NULL THEN DATE_FORMAT(p.payment_date, '%Y-%m-%d')
                    ELSE ''
                END as payment_date,
                CASE 
                    WHEN pb.is_paid = 1 THEN 'Paid'
                    WHEN pb.is_paid = 0 AND pb.due_date IS NOT NULL AND pb.due_date < CURDATE() THEN 'Overdue'
                    WHEN pb.is_paid = 0 THEN 'Pending'
                    ELSE 'Pending'
                END as status
            FROM payment_breakdowns pb
            LEFT JOIN bookings b ON pb.booking_id = b.booking_id
            LEFT JOIN payments p ON pb.payment_id = p.payment_id
            LEFT JOIN room_units ru ON b.room_id = ru.room_id
            LEFT JOIN boarding_house_rooms bhr ON ru.bhr_id = bhr.bhr_id
            LEFT JOIN boarding_houses bh ON bhr.bh_id = bh.bh_id
            WHERE (pb.payment_status IS NULL OR pb.payment_status != 'Cancelled')
        ";
        
        // CRITICAL: Filter breakdowns by booking IDs FIRST - this ensures we get ALL breakdowns for payments in summary
        // This includes pending, paid, and overdue breakdowns, even if they don't have payment_id yet
        if (!empty($bookingIds)) {
            $placeholders = implode(',', array_fill(0, count($bookingIds), '?'));
            $breakdownDetailsQuery .= " AND pb.booking_id IN ($placeholders)";
            // Merge booking IDs into breakdown params
            $breakdownParams = array_merge($breakdownParams, $bookingIds);
            $breakdownParamTypes .= str_repeat('i', count($bookingIds));
        } else {
            // If no booking IDs, we can't show breakdowns
            $breakdownDetailsQuery .= " AND 1=0"; // Return no results
        }
        
        // Add boarding house filter if it exists (but NOT date filters - we want ALL breakdowns)
        // This ensures breakdowns are filtered by boarding house if specified
        if ($boardingHouseId) {
            $breakdownDetailsQuery .= " AND bh.bh_id = ?";
            $breakdownParams[] = $boardingHouseId;
            $breakdownParamTypes .= 'i';
        }
        
        // Status filter removed - show all breakdowns (pending, paid, overdue)
        
        // Order by booking_id, then by period_start_date to show breakdowns in chronological order
        // This ensures paid breakdowns come first, followed by pending ones in sequence
        $breakdownDetailsQuery .= " ORDER BY pb.booking_id, COALESCE(pb.period_start_date, pb.due_date) ASC, pb.breakdown_id ASC";
        
        $stmt = $connection->prepare($breakdownDetailsQuery);
        if (!empty($breakdownParams)) {
            $stmt->bind_param($breakdownParamTypes, ...$breakdownParams);
        }
        $stmt->execute();
        $breakdownResult = $stmt->get_result();
        $breakdownDetails = [];
        while ($row = $breakdownResult->fetch_assoc()) {
            $breakdownDetails[] = $row;
        }
        
        // Group breakdowns by payment_id (map booking_id to payment_id)
        $bookingToPaymentMap = [];
        if (!empty($paymentSummaries)) {
            foreach ($paymentSummaries as $payment) {
                if (!empty($payment['booking_id']) && !empty($payment['payment_id'])) {
                    $bookingToPaymentMap[$payment['booking_id']] = $payment['payment_id'];
                }
            }
        }
        
        // Group breakdowns by payment_id
        // CRITICAL: Include ALL breakdowns for each booking, even if they don't have payment_id yet
        // This ensures pending breakdowns are included and displayed after paid ones
        $breakdownsByPayment = [];
        foreach ($breakdownDetails as $breakdown) {
            $bookingId = $breakdown['booking_id'] ?? null;
            $paymentId = $breakdown['payment_id'] ?? null;
            
            // Use payment_id if available, otherwise map from booking_id
            if (!$paymentId && $bookingId && isset($bookingToPaymentMap[$bookingId])) {
                $paymentId = $bookingToPaymentMap[$bookingId];
            }
            
            // If still no payment_id, try to find it from payment summaries
            // This ensures pending breakdowns (without payment_id) are still grouped with their payment
            if (!$paymentId && $bookingId) {
                foreach ($paymentSummaries as $ps) {
                    if (isset($ps['booking_id']) && $ps['booking_id'] == $bookingId) {
                        $paymentId = $ps['payment_id'];
                        break;
                    }
                }
            }
            
            // Group by payment_id, or by booking_id if payment_id is not available
            // This ensures ALL breakdowns (paid, pending, overdue) are grouped together
            $groupKey = $paymentId ? $paymentId : ($bookingId ? 'booking_' . $bookingId : 'unassigned');
            
            if (!isset($breakdownsByPayment[$groupKey])) {
                $breakdownsByPayment[$groupKey] = [];
            }
            $breakdownsByPayment[$groupKey][] = $breakdown;
        }
        
        // Sort breakdowns within each group by period_start_date to ensure correct order
        // This ensures paid breakdowns come first, followed by pending ones in chronological order
        foreach ($breakdownsByPayment as $key => $breakdowns) {
            usort($breakdownsByPayment[$key], function($a, $b) {
                // Sort by period_start_date (raw date) or due_date (formatted string)
                // Use period_start_date if available, otherwise use due_date
                $dateA = '';
                if (isset($a['period_start_date']) && $a['period_start_date']) {
                    $dateA = $a['period_start_date'];
                } elseif (isset($a['due_date']) && $a['due_date']) {
                    // due_date is already formatted as YYYY-MM-DD string, so we can use it directly
                    $dateA = $a['due_date'];
                } else {
                    $dateA = '9999-12-31';
                }
                
                $dateB = '';
                if (isset($b['period_start_date']) && $b['period_start_date']) {
                    $dateB = $b['period_start_date'];
                } elseif (isset($b['due_date']) && $b['due_date']) {
                    $dateB = $b['due_date'];
                } else {
                    $dateB = '9999-12-31';
                }
                
                if ($dateA != $dateB) {
                    return strcmp($dateA, $dateB);
                }
                // If dates are equal, sort by breakdown_id
                $idA = isset($a['breakdown_id']) ? intval($a['breakdown_id']) : 999999;
                $idB = isset($b['breakdown_id']) ? intval($b['breakdown_id']) : 999999;
                return $idA - $idB;
            });
        }
        
        // Create PDF
        $pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        
        // Set document information
        $pdf->SetCreator('BoardEase System');
        $pdf->SetAuthor('System Administrator');
        $pdf->SetTitle('BoardEase Payment Report');
        $pdf->SetSubject('Payment Transaction Report');
        
        // Remove default header/footer
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        
        // Set margins
        $pdf->SetMargins(10, 15, 10);
        $pdf->SetAutoPageBreak(TRUE, 10);
        
        // Add a page
        $pdf->AddPage();
        
        // Set font
        $pdf->SetFont('helvetica', 'B', 18);
        
        // Header
        $pdf->Cell(0, 8, 'BoardEase Payment Report', 0, 1, 'C');
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
        
        // Summary Section
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 8, 'Summary', 0, 1, 'L');
        $pdf->Ln(2);
        
        $pdf->SetFont('helvetica', '', 9);
        $summaryData = [
            ['Total Boarders', number_format($summary['total_boarders'] ?? 0)],
            ['Total Payments Made', number_format($summary['total_payments_made'] ?? 0)],
            ['Total Amount Collected', 'PHP ' . number_format($summary['total_amount_collected'] ?? 0, 2)],
            ['Total Pending Payments', 'PHP ' . number_format($summary['total_pending_payments'] ?? 0, 2)],
            ['Total Overdue Payments', 'PHP ' . number_format($summary['total_overdue_payments'] ?? 0, 2)]
        ];
        
        $pdf->SetFillColor(245, 245, 245);
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell(90, 6, 'Metric', 1, 0, 'L', true);
        $pdf->Cell(90, 6, 'Value', 1, 1, 'R', true);
        
        $pdf->SetFont('helvetica', '', 9);
        foreach ($summaryData as $row) {
            $pdf->Cell(90, 6, $row[0], 1, 0, 'L');
            $pdf->Cell(90, 6, $row[1], 1, 1, 'R');
        }
        
        $pdf->Ln(8);
        
        // ========== SECTION A: PAYMENT SUMMARY ==========
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 8, 'A. Payment Summary', 0, 1, 'L');
        $pdf->SetFont('helvetica', '', 9);
        $pdf->Cell(0, 4, '(From payments table - Overall payment status per boarder)', 0, 1, 'L');
        $pdf->Ln(3);
        
        if (!empty($paymentSummaries)) {
            $pdf->SetFont('helvetica', 'B', 8);
            $pdf->SetFillColor(245, 245, 245);
            
            // Table header
            $columnWidths = [
                'payment_id' => 16,
                'boarder' => 35,
                'bh' => 26,
                'room' => 29,
                'total' => 20,
                'paid' => 20,
                'balance' => 20,
                'status' => 24
            ];
            
            $pdf->Cell($columnWidths['payment_id'], 6, 'Payment ID', 1, 0, 'C', true);
            $pdf->Cell($columnWidths['boarder'], 6, 'Boarder', 1, 0, 'C', true);
            $pdf->Cell($columnWidths['bh'], 6, 'Boarding House', 1, 0, 'C', true);
            $pdf->Cell($columnWidths['room'], 6, 'Room', 1, 0, 'C', true);
            $pdf->Cell($columnWidths['total'], 6, 'Total Amount', 1, 0, 'C', true);
            $pdf->Cell($columnWidths['paid'], 6, 'Amount Paid', 1, 0, 'C', true);
            $pdf->Cell($columnWidths['balance'], 6, 'Balance', 1, 0, 'C', true);
            $pdf->Cell($columnWidths['status'], 6, 'Status', 1, 1, 'C', true);
            
            $pdf->SetFont('helvetica', '', 7);
            foreach ($paymentSummaries as $payment) {
                $boarderName = $payment['boarder_name'] ?? 'N/A';
                $bhName = $payment['bh_name'] ?? 'N/A';
                $room = $payment['room'] ?? 'N/A';
                $status = $payment['payment_status'] ?? 'Pending';
                
                $totalAmount = $payment['total_amount'] ?? 0;
                $amountPaid = $payment['amount_paid'] ?? 0;
                $balance = max(0, $totalAmount - $amountPaid);
                
                $boarderHeight = $pdf->getStringHeight($columnWidths['boarder'], $boarderName);
                $bhHeight = $pdf->getStringHeight($columnWidths['bh'], $bhName);
                $roomHeight = $pdf->getStringHeight($columnWidths['room'], $room);
                $rowHeight = max(7, $boarderHeight, $bhHeight, $roomHeight);
                
                $pdf->MultiCell($columnWidths['payment_id'], $rowHeight, $payment['payment_id'] ?? 'N/A', 1, 'C', false, 0, '', '', true, 0, false, true, $rowHeight, 'M');
                $pdf->MultiCell($columnWidths['boarder'], $rowHeight, $boarderName, 1, 'L', false, 0, '', '', true, 0, false, true, $rowHeight, 'M');
                $pdf->MultiCell($columnWidths['bh'], $rowHeight, $bhName, 1, 'L', false, 0, '', '', true, 0, false, true, $rowHeight, 'M');
                $pdf->MultiCell($columnWidths['room'], $rowHeight, $room, 1, 'L', false, 0, '', '', true, 0, false, true, $rowHeight, 'M');
                $pdf->MultiCell($columnWidths['total'], $rowHeight, 'PHP ' . number_format($totalAmount, 2), 1, 'R', false, 0, '', '', true, 0, false, true, $rowHeight, 'M');
                $pdf->MultiCell($columnWidths['paid'], $rowHeight, 'PHP ' . number_format($amountPaid, 2), 1, 'R', false, 0, '', '', true, 0, false, true, $rowHeight, 'M');
                $pdf->MultiCell($columnWidths['balance'], $rowHeight, 'PHP ' . number_format($balance, 2), 1, 'R', false, 0, '', '', true, 0, false, true, $rowHeight, 'M');
                $pdf->MultiCell($columnWidths['status'], $rowHeight, $status, 1, 'C', false, 1, '', '', true, 0, false, true, $rowHeight, 'M');
            }
        } else {
            $pdf->SetFont('helvetica', '', 9);
            $pdf->Cell(0, 8, 'No payment summaries found for the selected criteria.', 0, 1, 'L');
        }
        
        $pdf->Ln(10);
        
        // ========== SECTION B: PAYMENT BREAKDOWN DETAILS ==========
        $pdf->AddPage();
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 8, 'B. Payment Breakdown Details', 0, 1, 'L');
        $pdf->SetFont('helvetica', '', 9);
        $pdf->Cell(0, 4, '(From payment_breakdowns table - Each installment/due payment)', 0, 1, 'L');
        $pdf->Ln(5);
        
        if (!empty($breakdownsByPayment)) {
            foreach ($paymentSummaries as $payment) {
                $paymentId = $payment['payment_id'];
                $bookingId = $payment['booking_id'] ?? null;
                
                // Try to get breakdowns by payment_id first
                $breakdowns = $breakdownsByPayment[$paymentId] ?? [];
                
                // If no breakdowns found by payment_id, try by booking_id
                if (empty($breakdowns) && $bookingId) {
                    $bookingKey = 'booking_' . $bookingId;
                    $breakdowns = $breakdownsByPayment[$bookingKey] ?? [];
                }
                
                // If still no breakdowns, try to find any breakdowns for this booking_id
                if (empty($breakdowns) && $bookingId) {
                    foreach ($breakdownDetails as $bd) {
                        if (($bd['booking_id'] ?? null) == $bookingId) {
                            $breakdowns[] = $bd;
                        }
                    }
                }
                
                if (empty($breakdowns)) {
                    continue;
                }
                
                // Payment ID header
                $pdf->SetFont('helvetica', 'B', 10);
                $boarderName = mb_substr($payment['boarder_name'] ?? 'N/A', 0, 30);
                $pdf->Cell(0, 6, 'Payment ID: ' . $paymentId . ' (' . $boarderName . ')', 0, 1, 'L');
                $pdf->Ln(2);
                
                $pdf->SetFont('helvetica', 'B', 8);
                $pdf->SetFillColor(240, 248, 255);
                
                // Breakdown table header
                $pdf->Cell(25, 6, 'Breakdown ID', 1, 0, 'C', true);
                $pdf->Cell(25, 6, 'Payment ID', 1, 0, 'C', true);
                $pdf->Cell(30, 6, 'Due Date', 1, 0, 'C', true);
                $pdf->Cell(30, 6, 'Amount', 1, 0, 'C', true);
                $pdf->Cell(30, 6, 'Payment Date', 1, 0, 'C', true);
                $pdf->Cell(30, 6, 'Status', 1, 1, 'C', true);
                
                $pdf->SetFont('helvetica', '', 7);
                foreach ($breakdowns as $breakdown) {
                    $pdf->Cell(25, 6, 'B' . ($breakdown['breakdown_id'] ?? 'N/A'), 1, 0, 'C');
                    $pdf->Cell(25, 6, $breakdown['payment_id'] ?: 'N/A', 1, 0, 'C');
                    $pdf->Cell(30, 6, $breakdown['due_date'] ?? 'N/A', 1, 0, 'C');
                    $pdf->Cell(30, 6, 'PHP ' . number_format($breakdown['amount'] ?? 0, 2), 1, 0, 'R');
                    $pdf->Cell(30, 6, $breakdown['payment_date'] ?: '—', 1, 0, 'C');
                    $pdf->Cell(30, 6, $breakdown['status'] ?? 'Pending', 1, 1, 'C');
                }
                
                $pdf->Ln(5);
            }
        } else {
            $pdf->SetFont('helvetica', '', 9);
            $pdf->Cell(0, 8, 'No breakdown details found for the selected criteria.', 0, 1, 'L');
        }
        
        // Remarks / Notes Section
        $remainingSpace = $pdf->getPageHeight() - $pdf->getBreakMargin() - $pdf->GetY();
        $estimatedRemarksHeight = 45; // header + a few lines
        if ($remainingSpace < $estimatedRemarksHeight) {
            $pdf->AddPage();
        } else {
            $pdf->Ln(5);
        }
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 8, 'Remarks / Notes', 0, 1, 'L');
        $pdf->Ln(5);
        
        $pdf->SetFont('helvetica', '', 9);
        $remarks = [];
        $overdueAmount = $summary['total_overdue_payments'] ?? 0;
        $pendingAmount = $summary['total_pending_payments'] ?? 0;
        $totalCollected = $summary['total_amount_collected'] ?? 0;
        $collectionBase = $totalCollected + $pendingAmount + $overdueAmount;
        $collectionRate = $collectionBase > 0 ? ($totalCollected / $collectionBase) * 100 : null;
        
        // Count breakdown statuses
        $overdueCount = 0;
        $pendingCount = 0;
        foreach ($breakdownDetails as $bd) {
            if (($bd['status'] ?? '') === 'Overdue') {
                $overdueCount++;
            }
            if (($bd['status'] ?? '') === 'Pending') {
                $pendingCount++;
            }
        }
        
        // Count payments that currently have overdue breakdowns
        $overduePaymentsCount = 0;
        foreach ($paymentSummaries as $payment) {
            $pid = $payment['payment_id'] ?? null;
            $bookingId = $payment['booking_id'] ?? null;
            $paymentBreakdowns = $pid ? ($breakdownsByPayment[$pid] ?? []) : [];
            if (empty($paymentBreakdowns) && $bookingId) {
                $paymentBreakdowns = $breakdownsByPayment['booking_' . $bookingId] ?? [];
            }
            
            foreach ($paymentBreakdowns as $bd) {
                if (($bd['status'] ?? '') === 'Overdue') {
                    $overduePaymentsCount++;
                    break;
                }
            }
        }
        
        if ($overdueAmount > 0) {
            $remarks[] = "Total overdue amount: PHP " . number_format($overdueAmount, 2) . " requires immediate attention.";
        }
        
        if ($pendingAmount > 0) {
            $remarks[] = "• Total pending payments: PHP " . number_format($pendingAmount, 2) . " are awaiting payment.";
        }
        
        if ($overduePaymentsCount > 0) {
            $remarks[] = "• " . $overduePaymentsCount . " payment(s) are currently overdue and require follow-up.";
        }
        
        if ($overdueCount > 0) {
            $remarks[] = $overdueCount . " breakdown(s) are overdue and require immediate attention.";
        }
        
        if ($pendingCount > 0) {
            $remarks[] = "• " . $pendingCount . " breakdown(s) are pending payment.";
        }
        
        if (!is_null($collectionRate)) {
            $remarks[] = "• Collection rate: " . number_format($collectionRate, 2) . "%";
        }
        
        if (empty($remarks)) {
            $remarks[] = "No significant issues or patterns identified in the payment data.";
        }
        
        foreach ($remarks as $remark) {
            $pdf->Cell(0, 5, $remark, 0, 1, 'L');
        }
        
        $connection->close();
        
        return $pdf;
        
    } catch (Exception $e) {
        throw new Exception("Error generating report: " . $e->getMessage());
    }
}

// Handle the report request
if (isset($_GET['action'])) {
    if ($_GET['action'] === 'payment_report_pdf' || $_GET['action'] === 'payment_report_preview') {
        try {
            // Clear any previous output
            ob_end_clean();
            
            $startDate = isset($_GET['start_date']) ? $_GET['start_date'] : null;
            $endDate = isset($_GET['end_date']) ? $_GET['end_date'] : null;
            $boardingHouseId = isset($_GET['boarding_house_id']) && $_GET['boarding_house_id'] != '' ? intval($_GET['boarding_house_id']) : null;
            $preview = ($_GET['action'] === 'payment_report_preview');
            
            $pdf = generatePaymentReportPDF($startDate, $endDate, $boardingHouseId, $preview);
            
            // Output PDF
            if ($preview) {
                // For preview, output as inline
                $filename = 'payment_report_preview.pdf';
                $pdf->Output($filename, 'I');
            } else {
                // For download
                $filename = 'payment_report_' . date('Y-m-d_H-i-s') . '.pdf';
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
}

// If accessed directly without action parameter, return error
header('Content-Type: application/json');
echo json_encode(['success' => false, 'message' => 'Invalid request']);
?>
