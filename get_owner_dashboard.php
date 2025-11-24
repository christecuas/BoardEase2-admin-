<?php
// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, ngrok-skip-browser-warning, User-Agent, Accept');
    header('Access-Control-Max-Age: 86400');
    http_response_code(200);
    exit;
}

header("Content-Type: application/json");
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, ngrok-skip-browser-warning, User-Agent, Accept');

include 'dbConfig.php';

// Enable error logging
error_log("=== OWNER DASHBOARD API CALL ===");
error_log("Request Method: " . $_SERVER['REQUEST_METHOD']);
error_log("POST Data: " . json_encode($_POST));

$response = [];

// --- Validate user_id ---
$user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
error_log("User ID received: " . $user_id);
if ($user_id <= 0) {
    error_log("ERROR: Invalid user_id - " . $user_id);
    echo json_encode(["error" => "Invalid user_id"]);
    exit;
}

// Test database connection
if (!$conn) {
    error_log("ERROR: Database connection failed");
    echo json_encode(["error" => "Database connection failed"]);
    exit;
}
error_log("Database connection: OK");

// --- Owner name (JOIN users + registrations) ---
$sqlOwner = "SELECT CONCAT(r.first_name, ' ', r.last_name, 
                           CASE WHEN r.suffix IS NOT NULL AND r.suffix != '' THEN CONCAT(' ', r.suffix) ELSE '' END) AS fullname
             FROM users u
             INNER JOIN registrations r ON u.reg_id = r.id
             WHERE u.user_id = ?";
$stmtOwner = $conn->prepare($sqlOwner);
$stmtOwner->bind_param("i", $user_id);
$stmtOwner->execute();
$resOwner = $stmtOwner->get_result();
$ownerRow = $resOwner->fetch_assoc();
$response["owner_name"] = $ownerRow ? $ownerRow["fullname"] : "Owner";

// --- Count listings ---
$sqlCount = "SELECT COUNT(*) AS total FROM boarding_houses WHERE user_id = ?";
$stmtCount = $conn->prepare($sqlCount);
$stmtCount->bind_param("i", $user_id);
$stmtCount->execute();
$resCount = $stmtCount->get_result();
$rowCount = $resCount->fetch_assoc();
$response["listings_count"] = $rowCount ? intval($rowCount["total"]) : 0;

// --- Count boarders (confirmed bookings in owner's boarding houses) ---
$sqlBoarders = "SELECT COUNT(DISTINCT b.user_id) AS total 
                FROM bookings b 
                JOIN room_units ru ON b.room_id = ru.room_id 
                JOIN boarding_house_rooms bhr ON ru.bhr_id = bhr.bhr_id 
                JOIN boarding_houses bh ON bhr.bh_id = bh.bh_id 
                WHERE bh.user_id = ? AND b.booking_status = 'Confirmed'";
$stmtBoarders = $conn->prepare($sqlBoarders);
$stmtBoarders->bind_param("i", $user_id);
$stmtBoarders->execute();
$resBoarders = $stmtBoarders->get_result();
$rowBoarders = $resBoarders->fetch_assoc();
$response["boarders_count"] = $rowBoarders ? intval($rowBoarders["total"]) : 0;

// --- Today's Bookings: Count bookings created today for all listings owned by user ---
// This represents the number of new bookings received today
$sqlViews = "SELECT COUNT(*) AS total
             FROM bookings b
             JOIN room_units ru ON b.room_id = ru.room_id
             JOIN boarding_house_rooms bhr ON ru.bhr_id = bhr.bhr_id
             JOIN boarding_houses bh ON bhr.bh_id = bh.bh_id
             WHERE bh.user_id = ?
             AND DATE(b.booking_date) = CURDATE()";
$stmtViews = $conn->prepare($sqlViews);
$stmtViews->bind_param("i", $user_id);
$stmtViews->execute();
$resViews = $stmtViews->get_result();
$rowViews = $resViews->fetch_assoc();
$response["views_count"] = $rowViews ? intval($rowViews["total"]) : 0;

// --- Popular Listing: Find the boarding house with the most confirmed bookings ---
$sqlPopular = "SELECT 
                    bh.bh_id,
                    bh.bh_name,
                    COUNT(DISTINCT b.booking_id) AS total_bookings,
                    (SELECT bhi.image_path 
                     FROM boarding_house_images AS bhi 
                     WHERE bhi.bh_id = bh.bh_id 
                     ORDER BY bhi.image_id ASC 
                     LIMIT 1) AS image_path
               FROM boarding_houses bh
               LEFT JOIN boarding_house_rooms bhr ON bh.bh_id = bhr.bh_id
               LEFT JOIN room_units ru ON bhr.bhr_id = ru.bhr_id
               LEFT JOIN bookings b ON ru.room_id = b.room_id AND b.booking_status = 'Confirmed'
               WHERE bh.user_id = ?
               GROUP BY bh.bh_id, bh.bh_name
               HAVING total_bookings > 0
               ORDER BY total_bookings DESC
               LIMIT 1";

$stmtPopular = $conn->prepare($sqlPopular);
$stmtPopular->bind_param("i", $user_id);
$stmtPopular->execute();
$resPopular = $stmtPopular->get_result();
$popularRow = $resPopular->fetch_assoc();

if ($popularRow && $popularRow["bh_name"]) {
    // Build full image URL using ngrok URL to match Android app
    $baseUrl = 'https://reflective-perkily-jakobe.ngrok-free.dev/BoardEase2/';
    $rawImagePath = $popularRow["image_path"] ?? "";
    
    // Format image path properly
    if (!empty($rawImagePath)) {
        // If image path already starts with http, use as is
        if (strpos($rawImagePath, 'http://') === 0 || strpos($rawImagePath, 'https://') === 0) {
            $imagePath = $rawImagePath;
        } else {
            // Remove leading slash if present
            $cleanPath = ltrim($rawImagePath, '/');
            // If path already contains 'uploads/', use as is, otherwise prepend it
            if (strpos($cleanPath, 'uploads/') !== 0) {
                $cleanPath = 'uploads/' . $cleanPath;
            }
            $imagePath = $baseUrl . $cleanPath;
        }
    } else {
        $imagePath = "";
    }
    
    $response["popular_listing"] = [
        "bh_id"      => intval($popularRow["bh_id"]),
        "bh_name"    => $popularRow["bh_name"],
        "visits"     => intval($popularRow["total_bookings"]),  // Keep key as "visits" for Android compatibility
        "image_path" => $imagePath
    ];
} else {
    // If no bookings found, get the first active listing as fallback
    $sqlFallback = "SELECT 
                        bh.bh_id,
                        bh.bh_name,
                        (SELECT bhi.image_path 
                         FROM boarding_house_images AS bhi 
                         WHERE bhi.bh_id = bh.bh_id 
                         ORDER BY bhi.image_id ASC 
                         LIMIT 1) AS image_path
                   FROM boarding_houses bh
                   WHERE bh.user_id = ? AND bh.status = 'Active'
                   ORDER BY bh.bh_created_at DESC
                   LIMIT 1";
    
    $stmtFallback = $conn->prepare($sqlFallback);
    $stmtFallback->bind_param("i", $user_id);
    $stmtFallback->execute();
    $resFallback = $stmtFallback->get_result();
    $fallbackRow = $resFallback->fetch_assoc();

    if ($fallbackRow && $fallbackRow["bh_name"]) {
        // Build full image URL using ngrok URL to match Android app
        $baseUrl = 'https://reflective-perkily-jakobe.ngrok-free.dev/BoardEase2/';
        $rawImagePath = $fallbackRow["image_path"] ?? "";
        
        // Format image path properly
        if (!empty($rawImagePath)) {
            // If image path already starts with http, use as is
            if (strpos($rawImagePath, 'http://') === 0 || strpos($rawImagePath, 'https://') === 0) {
                $imagePath = $rawImagePath;
            } else {
                // Remove leading slash if present
                $cleanPath = ltrim($rawImagePath, '/');
                // If path already contains 'uploads/', use as is, otherwise prepend it
                if (strpos($cleanPath, 'uploads/') !== 0) {
                    $cleanPath = 'uploads/' . $cleanPath;
                }
                $imagePath = $baseUrl . $cleanPath;
            }
        } else {
            $imagePath = "";
        }
        
        $response["popular_listing"] = [
            "bh_id"      => intval($fallbackRow["bh_id"]),
            "bh_name"    => $fallbackRow["bh_name"],
            "visits"     => 0,
            "image_path" => $imagePath
        ];
    } else {
        // No listings at all
        $response["popular_listing"] = [
            "bh_id"      => 0,
            "bh_name"    => "N/A",
            "visits"     => 0,
            "image_path" => ""
        ];
    }
}

// --- Revenue Analytics: Total Revenue (all-time from completed payments) ---
error_log("=== REVENUE CALCULATION START ===");

// First, check what payment statuses exist for this owner
$sqlCheckPayments = "SELECT 
                        p.payment_status, 
                        COUNT(*) as count,
                        SUM(p.payment_amount) as total_amount
                     FROM payments p
                     INNER JOIN bookings b ON p.booking_id = b.booking_id
                     INNER JOIN room_units ru ON b.room_id = ru.room_id
                     INNER JOIN boarding_house_rooms bhr ON ru.bhr_id = bhr.bhr_id
                     INNER JOIN boarding_houses bh ON bhr.bh_id = bh.bh_id
                     WHERE bh.user_id = ?
                     GROUP BY p.payment_status";
$stmtCheckPayments = $conn->prepare($sqlCheckPayments);
if ($stmtCheckPayments) {
    $stmtCheckPayments->bind_param("i", $user_id);
    $stmtCheckPayments->execute();
    $resCheckPayments = $stmtCheckPayments->get_result();
    error_log("=== PAYMENT STATUS CHECK FOR USER $user_id ===");
    $hasAnyPayments = false;
    while ($row = $resCheckPayments->fetch_assoc()) {
        $hasAnyPayments = true;
        error_log("Payment Status: '" . $row["payment_status"] . "', Count: " . $row["count"] . ", Amount: " . $row["total_amount"]);
    }
    if (!$hasAnyPayments) {
        error_log("WARNING: No payments found for this owner at all!");
    }
}

// Also check if owner has any boarding houses
$sqlCheckBH = "SELECT COUNT(*) as bh_count FROM boarding_houses WHERE user_id = ?";
$stmtCheckBH = $conn->prepare($sqlCheckBH);
if ($stmtCheckBH) {
    $stmtCheckBH->bind_param("i", $user_id);
    $stmtCheckBH->execute();
    $resCheckBH = $stmtCheckBH->get_result();
    $rowCheckBH = $resCheckBH->fetch_assoc();
    error_log("Owner has " . ($rowCheckBH ? $rowCheckBH["bh_count"] : 0) . " boarding house(s)");
}

// Use payment_breakdowns table like the rest of the system (where is_paid = 1)
// This matches how superadmin calculates revenue
// IMPORTANT: First get ALL booking IDs that belong to this owner's boarding houses
// Then only count payment_breakdowns from those specific bookings
// This ensures we ONLY count revenue from THIS owner's boarding houses
error_log("=== CALCULATING REVENUE FOR OWNER USER_ID: " . $user_id . " ===");

// Initialize booking IDs array - will be populated with owner's booking IDs only
$bookingIds = [];

// First, verify which boarding houses belong to this owner
$sqlCheckBH = "SELECT bh_id, bh_name, user_id FROM boarding_houses WHERE user_id = ?";
$stmtCheckBH = $conn->prepare($sqlCheckBH);
$ownerBhIds = [];
$ownerBhNames = [];
if ($stmtCheckBH) {
    $stmtCheckBH->bind_param("i", $user_id);
    $stmtCheckBH->execute();
    $resCheckBH = $stmtCheckBH->get_result();
    error_log("=== OWNER'S BOARDING HOUSES ===");
    while ($row = $resCheckBH->fetch_assoc()) {
        $ownerBhIds[] = intval($row["bh_id"]);
        $ownerBhNames[] = $row["bh_name"];
        error_log("Owner's BH ID: " . $row["bh_id"] . ", Name: " . $row["bh_name"] . ", User ID: " . $row["user_id"]);
    }
    error_log("Total boarding houses for owner: " . count($ownerBhIds));
    error_log("BH IDs: " . implode(', ', $ownerBhIds));
}

// Now verify which bookings belong to this owner's boarding houses ONLY
$sqlCheckBookings = "SELECT DISTINCT b.booking_id, bh.bh_id, bh.bh_name, bh.user_id as owner_id
                     FROM bookings b
                     INNER JOIN room_units ru ON b.room_id = ru.room_id
                     INNER JOIN boarding_house_rooms bhr ON ru.bhr_id = bhr.bhr_id
                     INNER JOIN boarding_houses bh ON bhr.bh_id = bh.bh_id
                     WHERE bh.user_id = ?";
error_log("=== GETTING OWNER'S BOOKINGS ===");
error_log("SQL: " . $sqlCheckBookings);
$stmtCheckBookings = $conn->prepare($sqlCheckBookings);
if ($stmtCheckBookings) {
    $stmtCheckBookings->bind_param("i", $user_id);
    $stmtCheckBookings->execute();
    $resCheckBookings = $stmtCheckBookings->get_result();
    $bookingBhMap = []; // Track which booking belongs to which BH
    while ($row = $resCheckBookings->fetch_assoc()) {
        $bookingId = intval($row["booking_id"]);
        $bookingIds[] = $bookingId;
        $bookingBhMap[$bookingId] = ["bh_id" => intval($row["bh_id"]), "bh_name" => $row["bh_name"]];
        error_log("Owner's Booking ID: " . $row["booking_id"] . ", BH: " . $row["bh_name"] . " (ID: " . $row["bh_id"] . "), Owner ID: " . $row["owner_id"]);
    }
    error_log("Total bookings found for owner user_id " . $user_id . ": " . count($bookingIds));
    if (count($bookingIds) > 0) {
        error_log("Booking IDs: " . implode(', ', $bookingIds));
    }
} else {
    error_log("ERROR: Failed to prepare check bookings statement: " . $conn->error);
}

// Now calculate revenue using only these booking IDs (ensuring only owner's boarding houses)
if (!empty($bookingIds)) {
    $placeholders = implode(',', array_fill(0, count($bookingIds), '?'));
    
    // First, get detailed breakdown to verify what we're counting
    // CRITICAL: Only count payment_breakdowns where is_paid = 1 AND payment_status is Completed/Paid/Partially Paid
    $sqlDetailedBreakdown = "SELECT 
                                pb.breakdown_id,
                                pb.booking_id,
                                pb.amount,
                                pb.payment_id,
                                pb.is_paid,
                                COALESCE(p.payment_status, 'Pending') as payment_status,
                                b.room_id,
                                ru.bhr_id,
                                bhr.bh_id,
                                bh.bh_name,
                                bh.user_id
                             FROM payment_breakdowns pb
                             INNER JOIN bookings b ON pb.booking_id = b.booking_id
                             LEFT JOIN payments p ON pb.payment_id = p.payment_id
                             INNER JOIN room_units ru ON b.room_id = ru.room_id
                             INNER JOIN boarding_house_rooms bhr ON ru.bhr_id = bhr.bhr_id
                             INNER JOIN boarding_houses bh ON bhr.bh_id = bh.bh_id
                             WHERE pb.is_paid = 1
                             AND COALESCE(p.payment_status, 'Pending') IN ('Completed', 'Partially Paid', 'Fully Paid', 'Paid')
                             AND pb.booking_id IN ($placeholders)
                             AND bh.user_id = ?";
    $stmtDetailed = $conn->prepare($sqlDetailedBreakdown);
    if ($stmtDetailed) {
        $types = str_repeat('i', count($bookingIds)) . 'i';
        $params = array_merge($bookingIds, [$user_id]);
        $stmtDetailed->bind_param($types, ...$params);
        $stmtDetailed->execute();
        $resDetailed = $stmtDetailed->get_result();
        error_log("=== DETAILED PAYMENT BREAKDOWN VERIFICATION ===");
        $totalByBH = [];
        $totalAmount = 0;
        while ($row = $resDetailed->fetch_assoc()) {
            $bhId = intval($row["bh_id"]);
            $bhName = $row["bh_name"];
            $amount = floatval($row["amount"]);
            $paymentStatus = $row["payment_status"];
            $totalAmount += $amount;
            if (!isset($totalByBH[$bhId])) {
                $totalByBH[$bhId] = ["name" => $bhName, "total" => 0, "count" => 0];
            }
            $totalByBH[$bhId]["total"] += $amount;
            $totalByBH[$bhId]["count"]++;
            error_log("Breakdown ID: " . $row["breakdown_id"] . ", Booking: " . $row["booking_id"] . ", BH: " . $bhName . " (ID: " . $bhId . "), Amount: " . $amount . ", Payment Status: " . $paymentStatus . ", Owner ID: " . $row["user_id"]);
        }
        error_log("=== REVENUE BY BOARDING HOUSE (ONLY PAID) ===");
        foreach ($totalByBH as $bhId => $data) {
            error_log("BH: " . $data["name"] . " (ID: " . $bhId . ") - Total: ₱" . number_format($data["total"], 2) . ", Breakdowns: " . $data["count"]);
        }
        error_log("=== TOTAL CALCULATED REVENUE (ONLY PAID): ₱" . number_format($totalAmount, 2) . " ===");
    }
    
    // Now calculate total revenue
    // CRITICAL: Only count where is_paid = 1 AND payment_status is NOT Pending
    $sqlTotalRevenue = "SELECT 
                            COALESCE(SUM(pb.amount), 0) AS total_revenue,
                            COUNT(DISTINCT pb.payment_id) AS total_payments
                        FROM payment_breakdowns pb
                        INNER JOIN bookings b ON pb.booking_id = b.booking_id
                        LEFT JOIN payments p ON pb.payment_id = p.payment_id
                        INNER JOIN room_units ru ON b.room_id = ru.room_id
                        INNER JOIN boarding_house_rooms bhr ON ru.bhr_id = bhr.bhr_id
                        INNER JOIN boarding_houses bh ON bhr.bh_id = bh.bh_id
                        WHERE pb.is_paid = 1
                        AND COALESCE(p.payment_status, 'Pending') IN ('Completed', 'Partially Paid', 'Fully Paid', 'Paid')
                        AND pb.booking_id IN ($placeholders)
                        AND bh.user_id = ?";
    
    $stmtTotalRevenue = $conn->prepare($sqlTotalRevenue);
    if (!$stmtTotalRevenue) {
        error_log("ERROR: Failed to prepare total revenue statement: " . $conn->error);
        $totalRevenueValue = 0;
        $totalPaymentsValue = 0;
    } else {
        // Bind all booking IDs plus user_id
        $types = str_repeat('i', count($bookingIds)) . 'i';
        $params = array_merge($bookingIds, [$user_id]);
        $stmtTotalRevenue->bind_param($types, ...$params);
        
        if (!$stmtTotalRevenue->execute()) {
            error_log("ERROR: Failed to execute total revenue query: " . $stmtTotalRevenue->error);
            $totalRevenueValue = 0;
            $totalPaymentsValue = 0;
        } else {
            $resTotalRevenue = $stmtTotalRevenue->get_result();
            $rowTotalRevenue = $resTotalRevenue->fetch_assoc();
            error_log("Total Revenue Query Result: " . json_encode($rowTotalRevenue));
            $totalRevenueValue = 0;
            $totalPaymentsValue = 0;
            if ($rowTotalRevenue) {
                $totalRevenueValue = is_numeric($rowTotalRevenue["total_revenue"]) ? floatval($rowTotalRevenue["total_revenue"]) : 0;
                $totalPaymentsValue = is_numeric($rowTotalRevenue["total_payments"]) ? intval($rowTotalRevenue["total_payments"]) : 0;
                error_log("Parsed - Total Revenue: " . $totalRevenueValue . ", Total Payments: " . $totalPaymentsValue);
            } else {
                error_log("WARNING: No rows returned for total revenue query");
            }
        }
    }
    $response["revenue"] = [
        "total_revenue" => $totalRevenueValue,
        "total_payments_count" => $totalPaymentsValue
    ];
    error_log("Revenue object created: " . json_encode($response["revenue"]));
} else {
    error_log("WARNING: No bookings found for this owner!");
    $response["revenue"] = [
        "total_revenue" => 0,
        "total_payments_count" => 0
    ];
}

// --- Monthly Revenue (current month) ---
// Use the same booking IDs list from owner's boarding houses
// CRITICAL: Only count where is_paid = 1 AND payment_status is NOT Pending
if (!empty($bookingIds)) {
    $placeholders = implode(',', array_fill(0, count($bookingIds), '?'));
    $sqlMonthlyRevenue = "SELECT 
                              COALESCE(SUM(pb.amount), 0) AS monthly_revenue,
                              COUNT(DISTINCT pb.payment_id) AS monthly_payments
                          FROM payment_breakdowns pb
                          INNER JOIN payments p ON pb.payment_id = p.payment_id
                          WHERE pb.is_paid = 1
                          AND COALESCE(p.payment_status, 'Pending') IN ('Completed', 'Partially Paid', 'Fully Paid', 'Paid')
                          AND YEAR(p.payment_date) = YEAR(CURDATE())
                          AND MONTH(p.payment_date) = MONTH(CURDATE())
                          AND pb.booking_id IN ($placeholders)";
    $stmtMonthlyRevenue = $conn->prepare($sqlMonthlyRevenue);
    if (!$stmtMonthlyRevenue) {
        error_log("ERROR: Failed to prepare monthly revenue statement: " . $conn->error);
        $response["revenue"]["monthly_revenue"] = 0;
        $response["revenue"]["monthly_payments_count"] = 0;
    } else {
        $types = str_repeat('i', count($bookingIds));
        $stmtMonthlyRevenue->bind_param($types, ...$bookingIds);
        if (!$stmtMonthlyRevenue->execute()) {
            error_log("ERROR: Failed to execute monthly revenue query: " . $stmtMonthlyRevenue->error);
            $response["revenue"]["monthly_revenue"] = 0;
            $response["revenue"]["monthly_payments_count"] = 0;
        } else {
            $resMonthlyRevenue = $stmtMonthlyRevenue->get_result();
            $rowMonthlyRevenue = $resMonthlyRevenue->fetch_assoc();
            error_log("Monthly Revenue Query Result: " . json_encode($rowMonthlyRevenue));
            $monthlyRevenueValue = 0;
            $monthlyPaymentsValue = 0;
            if ($rowMonthlyRevenue) {
                $monthlyRevenueValue = is_numeric($rowMonthlyRevenue["monthly_revenue"]) ? floatval($rowMonthlyRevenue["monthly_revenue"]) : 0;
                $monthlyPaymentsValue = is_numeric($rowMonthlyRevenue["monthly_payments"]) ? intval($rowMonthlyRevenue["monthly_payments"]) : 0;
            }
            $response["revenue"]["monthly_revenue"] = $monthlyRevenueValue;
            $response["revenue"]["monthly_payments_count"] = $monthlyPaymentsValue;
            error_log("Monthly Revenue: " . $monthlyRevenueValue . ", Count: " . $monthlyPaymentsValue);
        }
    }
} else {
    $response["revenue"]["monthly_revenue"] = 0;
    $response["revenue"]["monthly_payments_count"] = 0;
}

// --- Weekly Revenue (current week, Sunday to Saturday) ---
// Use the same booking IDs list
// CRITICAL: Only count where is_paid = 1 AND payment_status is NOT Pending
if (!empty($bookingIds)) {
    $placeholders = implode(',', array_fill(0, count($bookingIds), '?'));
    $sqlWeeklyRevenue = "SELECT 
                             COALESCE(SUM(pb.amount), 0) AS weekly_revenue,
                             COUNT(DISTINCT pb.payment_id) AS weekly_payments
                         FROM payment_breakdowns pb
                         INNER JOIN payments p ON pb.payment_id = p.payment_id
                         WHERE pb.is_paid = 1
                         AND COALESCE(p.payment_status, 'Pending') IN ('Completed', 'Partially Paid', 'Fully Paid', 'Paid')
                         AND YEARWEEK(p.payment_date, 1) = YEARWEEK(CURDATE(), 1)
                         AND pb.booking_id IN ($placeholders)";
    $stmtWeeklyRevenue = $conn->prepare($sqlWeeklyRevenue);
    if (!$stmtWeeklyRevenue) {
        error_log("ERROR: Failed to prepare weekly revenue statement: " . $conn->error);
        $response["revenue"]["weekly_revenue"] = 0;
        $response["revenue"]["weekly_payments_count"] = 0;
    } else {
        $types = str_repeat('i', count($bookingIds));
        $stmtWeeklyRevenue->bind_param($types, ...$bookingIds);
        if (!$stmtWeeklyRevenue->execute()) {
            error_log("ERROR: Failed to execute weekly revenue query: " . $stmtWeeklyRevenue->error);
            $response["revenue"]["weekly_revenue"] = 0;
            $response["revenue"]["weekly_payments_count"] = 0;
        } else {
            $resWeeklyRevenue = $stmtWeeklyRevenue->get_result();
            $rowWeeklyRevenue = $resWeeklyRevenue->fetch_assoc();
            error_log("Weekly Revenue Query Result: " . json_encode($rowWeeklyRevenue));
            $weeklyRevenueValue = 0;
            $weeklyPaymentsValue = 0;
            if ($rowWeeklyRevenue) {
                $weeklyRevenueValue = is_numeric($rowWeeklyRevenue["weekly_revenue"]) ? floatval($rowWeeklyRevenue["weekly_revenue"]) : 0;
                $weeklyPaymentsValue = is_numeric($rowWeeklyRevenue["weekly_payments"]) ? intval($rowWeeklyRevenue["weekly_payments"]) : 0;
            }
            $response["revenue"]["weekly_revenue"] = $weeklyRevenueValue;
            $response["revenue"]["weekly_payments_count"] = $weeklyPaymentsValue;
            error_log("Weekly Revenue: " . $weeklyRevenueValue . ", Count: " . $weeklyPaymentsValue);
        }
    }
} else {
    $response["revenue"]["weekly_revenue"] = 0;
    $response["revenue"]["weekly_payments_count"] = 0;
}

// --- Unpaid/Pending Payments (count and total amount) ---
// Count payment_breakdowns where is_paid = 0 OR payment_status = 'Pending'
// CRITICAL: Include ALL payment_breakdowns that are not paid, not just is_selected = 1
// Use the same booking IDs list
if (!empty($bookingIds)) {
    $placeholders = implode(',', array_fill(0, count($bookingIds), '?'));
    
    // First, get detailed breakdown of unpaid items for debugging
    $sqlUnpaidDetail = "SELECT 
                           pb.breakdown_id,
                           pb.booking_id,
                           pb.amount,
                           pb.is_paid,
                           pb.is_selected,
                           pb.payment_id,
                           COALESCE(p.payment_status, 'Pending') as payment_status
                       FROM payment_breakdowns pb
                       LEFT JOIN payments p ON pb.payment_id = p.payment_id
                       WHERE (pb.is_paid = 0 OR pb.is_paid IS NULL OR COALESCE(p.payment_status, 'Pending') = 'Pending')
                       AND pb.booking_id IN ($placeholders)";
    $stmtUnpaidDetail = $conn->prepare($sqlUnpaidDetail);
    if ($stmtUnpaidDetail) {
        $types = str_repeat('i', count($bookingIds));
        $stmtUnpaidDetail->bind_param($types, ...$bookingIds);
        $stmtUnpaidDetail->execute();
        $resUnpaidDetail = $stmtUnpaidDetail->get_result();
        error_log("=== UNPAID BREAKDOWNS DETAIL ===");
        $unpaidTotal = 0;
        $unpaidCount = 0;
        while ($row = $resUnpaidDetail->fetch_assoc()) {
            $amount = floatval($row["amount"]);
            $unpaidTotal += $amount;
            $unpaidCount++;
            error_log("Unpaid Breakdown ID: " . $row["breakdown_id"] . ", Booking: " . $row["booking_id"] . ", Amount: " . $amount . ", is_paid: " . $row["is_paid"] . ", payment_status: " . $row["payment_status"] . ", is_selected: " . $row["is_selected"]);
        }
        error_log("=== UNPAID TOTAL FROM DETAIL: ₱" . number_format($unpaidTotal, 2) . ", Count: " . $unpaidCount . " ===");
    }
    
    // Now get the aggregated result
    $sqlUnpaid = "SELECT 
                      COALESCE(SUM(pb.amount), 0) AS unpaid_amount,
                      COUNT(pb.breakdown_id) AS unpaid_count
                  FROM payment_breakdowns pb
                  LEFT JOIN payments p ON pb.payment_id = p.payment_id
                  WHERE (pb.is_paid = 0 OR pb.is_paid IS NULL OR COALESCE(p.payment_status, 'Pending') = 'Pending')
                  AND pb.booking_id IN ($placeholders)";
    error_log("=== UNPAID QUERY ===");
    error_log("SQL: " . $sqlUnpaid);
    $stmtUnpaid = $conn->prepare($sqlUnpaid);
    if (!$stmtUnpaid) {
        error_log("ERROR: Failed to prepare unpaid statement: " . $conn->error);
        $response["revenue"]["unpaid_amount"] = 0;
        $response["revenue"]["unpaid_count"] = 0;
    } else {
        $types = str_repeat('i', count($bookingIds));
        $stmtUnpaid->bind_param($types, ...$bookingIds);
        $stmtUnpaid->execute();
        $resUnpaid = $stmtUnpaid->get_result();
        $rowUnpaid = $resUnpaid->fetch_assoc();
        error_log("Unpaid Query Result: " . json_encode($rowUnpaid));
        $unpaidAmountValue = 0;
        $unpaidCountValue = 0;
        if ($rowUnpaid) {
            $unpaidAmountValue = is_numeric($rowUnpaid["unpaid_amount"]) ? floatval($rowUnpaid["unpaid_amount"]) : 0;
            $unpaidCountValue = is_numeric($rowUnpaid["unpaid_count"]) ? intval($rowUnpaid["unpaid_count"]) : 0;
        }
        $response["revenue"]["unpaid_amount"] = $unpaidAmountValue;
        $response["revenue"]["unpaid_count"] = $unpaidCountValue;
        error_log("=== UNPAID FINAL RESULT: ₱" . number_format($unpaidAmountValue, 2) . ", Count: " . $unpaidCountValue . " ===");
    }
} else {
    $response["revenue"]["unpaid_amount"] = 0;
    $response["revenue"]["unpaid_count"] = 0;
}

// --- Paid/Completed Payments (count and total amount) ---
// This is already calculated in total_revenue (is_paid = 1 AND payment_status NOT Pending), but we include it separately for clarity
$response["revenue"]["paid_amount"] = $totalRevenueValue;
$response["revenue"]["paid_count"] = $totalPaymentsValue;
error_log("Paid Amount (only Completed/Paid/Partially Paid/Fully Paid): " . $totalRevenueValue . ", Count: " . $totalPaymentsValue);

// --- Revenue by Boarding House ---
$response["revenue"]["boarding_houses"] = [];
if (!empty($bookingIds)) {
    $placeholders = implode(',', array_fill(0, count($bookingIds), '?'));
    $sqlBhRevenue = "SELECT 
                        bh.bh_id,
                        bh.bh_name,
                        bh.bh_address AS full_address,
                        COALESCE(SUM(pb.amount), 0) AS total_revenue,
                        COUNT(DISTINCT pb.payment_id) AS payment_count,
                        COALESCE(SUM(CASE WHEN pb.is_paid = 0 OR pb.is_paid IS NULL OR COALESCE(p.payment_status, 'Pending') = 'Pending' THEN pb.amount ELSE 0 END), 0) AS unpaid_amount,
                        COUNT(CASE WHEN pb.is_paid = 0 OR pb.is_paid IS NULL OR COALESCE(p.payment_status, 'Pending') = 'Pending' THEN 1 END) AS unpaid_count
                     FROM boarding_houses bh
                     INNER JOIN boarding_house_rooms bhr ON bh.bh_id = bhr.bh_id
                     INNER JOIN room_units ru ON bhr.bhr_id = ru.bhr_id
                     INNER JOIN bookings b ON ru.room_id = b.room_id
                     INNER JOIN payment_breakdowns pb ON b.booking_id = pb.booking_id
                     LEFT JOIN payments p ON pb.payment_id = p.payment_id
                     WHERE bh.user_id = ?
                     AND pb.is_paid = 1
                     AND COALESCE(p.payment_status, 'Pending') IN ('Completed', 'Partially Paid', 'Fully Paid', 'Paid')
                     AND pb.booking_id IN ($placeholders)
                     GROUP BY bh.bh_id, bh.bh_name, bh.bh_address
                     ORDER BY total_revenue DESC";
    
    $stmtBhRevenue = $conn->prepare($sqlBhRevenue);
    if ($stmtBhRevenue) {
        $types = str_repeat('i', count($bookingIds)) . 'i';
        $params = array_merge([$user_id], $bookingIds);
        $stmtBhRevenue->bind_param($types, ...$params);
        $stmtBhRevenue->execute();
        $resBhRevenue = $stmtBhRevenue->get_result();
        
        error_log("=== REVENUE BY BOARDING HOUSE ===");
        while ($row = $resBhRevenue->fetch_assoc()) {
            $bhId = intval($row["bh_id"]);
            
            // Get unpaid amount for this specific boarding house
            $sqlBhUnpaid = "SELECT 
                               COALESCE(SUM(pb.amount), 0) AS unpaid_amount,
                               COUNT(pb.breakdown_id) AS unpaid_count
                           FROM payment_breakdowns pb
                           INNER JOIN bookings b ON pb.booking_id = b.booking_id
                           INNER JOIN room_units ru ON b.room_id = ru.room_id
                           INNER JOIN boarding_house_rooms bhr ON ru.bhr_id = bhr.bhr_id
                           LEFT JOIN payments p ON pb.payment_id = p.payment_id
                           WHERE bhr.bh_id = ?
                           AND (pb.is_paid = 0 OR pb.is_paid IS NULL OR COALESCE(p.payment_status, 'Pending') = 'Pending')
                           AND pb.booking_id IN ($placeholders)";
            $stmtBhUnpaid = $conn->prepare($sqlBhUnpaid);
            if ($stmtBhUnpaid) {
                $typesUnpaid = str_repeat('i', count($bookingIds)) . 'i';
                $paramsUnpaid = array_merge([$bhId], $bookingIds);
                $stmtBhUnpaid->bind_param($typesUnpaid, ...$paramsUnpaid);
                $stmtBhUnpaid->execute();
                $resBhUnpaid = $stmtBhUnpaid->get_result();
                $rowUnpaid = $resBhUnpaid->fetch_assoc();
                $unpaidAmount = $rowUnpaid ? (is_numeric($rowUnpaid["unpaid_amount"]) ? floatval($rowUnpaid["unpaid_amount"]) : 0) : 0;
                $unpaidCount = $rowUnpaid ? (is_numeric($rowUnpaid["unpaid_count"]) ? intval($rowUnpaid["unpaid_count"]) : 0) : 0;
            } else {
                $unpaidAmount = 0;
                $unpaidCount = 0;
            }
            
            $bhRevenue = [
                "bh_id" => $bhId,
                "bh_name" => $row["bh_name"],
                "address" => trim($row["full_address"]),
                "total_revenue" => is_numeric($row["total_revenue"]) ? floatval($row["total_revenue"]) : 0,
                "payment_count" => is_numeric($row["payment_count"]) ? intval($row["payment_count"]) : 0,
                "unpaid_amount" => $unpaidAmount,
                "unpaid_count" => $unpaidCount
            ];
            $response["revenue"]["boarding_houses"][] = $bhRevenue;
            error_log("BH Revenue - ID: " . $bhId . ", Name: " . $bhRevenue["bh_name"] . ", Revenue: ₱" . number_format($bhRevenue["total_revenue"], 2) . ", Payments: " . $bhRevenue["payment_count"] . ", Unpaid: ₱" . number_format($unpaidAmount, 2) . ", Unpaid Count: " . $unpaidCount);
        }
        
        error_log("Total boarding houses with revenue: " . count($response["revenue"]["boarding_houses"]));
    } else {
        error_log("ERROR: Failed to prepare boarding house revenue statement: " . $conn->error);
    }
} else {
    // No bookings but show boarding houses with 0 revenue
    if (!empty($ownerBhIds)) {
        foreach ($ownerBhIds as $index => $bhId) {
            $bhName = $ownerBhNames[$index];
            $sqlBhInfo = "SELECT bh_address FROM boarding_houses WHERE bh_id = ?";
            $stmtBhInfo = $conn->prepare($sqlBhInfo);
            if ($stmtBhInfo) {
                $stmtBhInfo->bind_param("i", $bhId);
                $stmtBhInfo->execute();
                $resBhInfo = $stmtBhInfo->get_result();
                $bhInfo = $resBhInfo->fetch_assoc();
                
                $fullAddress = $bhInfo["bh_address"] ?? "";
                
                $response["revenue"]["boarding_houses"][] = [
                    "bh_id" => $bhId,
                    "bh_name" => $bhName,
                    "address" => trim($fullAddress),
                    "total_revenue" => 0,
                    "payment_count" => 0,
                    "unpaid_amount" => 0,
                    "unpaid_count" => 0
                ];
            }
        }
    }
}

// --- Total Bookings Analytics ---
$response["bookings"] = [];
if (!empty($bookingIds)) {
    $placeholders = implode(',', array_fill(0, count($bookingIds), '?'));
    
    // Total bookings (all time)
    $sqlTotalBookings = "SELECT COUNT(DISTINCT b.booking_id) AS total_bookings
                          FROM bookings b
                          WHERE b.booking_id IN ($placeholders)";
    $stmtTotalBookings = $conn->prepare($sqlTotalBookings);
    if ($stmtTotalBookings) {
        $types = str_repeat('i', count($bookingIds));
        $stmtTotalBookings->bind_param($types, ...$bookingIds);
        $stmtTotalBookings->execute();
        $resTotalBookings = $stmtTotalBookings->get_result();
        $rowTotalBookings = $resTotalBookings->fetch_assoc();
        $response["bookings"]["total_bookings"] = $rowTotalBookings ? intval($rowTotalBookings["total_bookings"]) : 0;
    }
    
    // Bookings this month
    $sqlMonthlyBookings = "SELECT COUNT(DISTINCT b.booking_id) AS monthly_bookings
                          FROM bookings b
                          WHERE YEAR(b.booking_date) = YEAR(CURDATE())
                          AND MONTH(b.booking_date) = MONTH(CURDATE())
                          AND b.booking_id IN ($placeholders)";
    $stmtMonthlyBookings = $conn->prepare($sqlMonthlyBookings);
    if ($stmtMonthlyBookings) {
        $types = str_repeat('i', count($bookingIds));
        $stmtMonthlyBookings->bind_param($types, ...$bookingIds);
        $stmtMonthlyBookings->execute();
        $resMonthlyBookings = $stmtMonthlyBookings->get_result();
        $rowMonthlyBookings = $resMonthlyBookings->fetch_assoc();
        $response["bookings"]["monthly_bookings"] = $rowMonthlyBookings ? intval($rowMonthlyBookings["monthly_bookings"]) : 0;
    }
} else {
    $response["bookings"]["total_bookings"] = 0;
    $response["bookings"]["monthly_bookings"] = 0;
}

// --- Occupancy Rate Analytics ---
$response["occupancy"] = [];
if (!empty($ownerBhIds)) {
    // Overall occupancy (all boarding houses combined)
    $sqlTotalRooms = "SELECT 
                         COUNT(DISTINCT ru.room_id) AS total_rooms,
                         COUNT(DISTINCT CASE WHEN ru.status = 'Occupied' THEN ru.room_id END) AS occupied_rooms
                      FROM room_units ru
                      INNER JOIN boarding_house_rooms bhr ON ru.bhr_id = bhr.bhr_id
                      INNER JOIN boarding_houses bh ON bhr.bh_id = bh.bh_id
                      WHERE bh.user_id = ?";
    $stmtTotalRooms = $conn->prepare($sqlTotalRooms);
    if ($stmtTotalRooms) {
        $stmtTotalRooms->bind_param("i", $user_id);
        $stmtTotalRooms->execute();
        $resTotalRooms = $stmtTotalRooms->get_result();
        $rowTotalRooms = $resTotalRooms->fetch_assoc();
        $totalRooms = $rowTotalRooms ? intval($rowTotalRooms["total_rooms"]) : 0;
        $occupiedRooms = $rowTotalRooms ? intval($rowTotalRooms["occupied_rooms"]) : 0;
        $occupancyRate = $totalRooms > 0 ? round(($occupiedRooms / $totalRooms) * 100, 1) : 0;
        $response["occupancy"]["overall_rate"] = $occupancyRate;
        $response["occupancy"]["total_rooms"] = $totalRooms;
        $response["occupancy"]["occupied_rooms"] = $occupiedRooms;
        $response["occupancy"]["available_rooms"] = $totalRooms - $occupiedRooms;
    }
    
    // Occupancy per boarding house
    $response["occupancy"]["by_boarding_house"] = [];
    $placeholders = implode(',', array_fill(0, count($ownerBhIds), '?'));
    $sqlBhOccupancy = "SELECT 
                          bh.bh_id,
                          bh.bh_name,
                          COUNT(DISTINCT ru.room_id) AS total_rooms,
                          COUNT(DISTINCT CASE WHEN ru.status = 'Occupied' THEN ru.room_id END) AS occupied_rooms
                       FROM boarding_houses bh
                       INNER JOIN boarding_house_rooms bhr ON bh.bh_id = bhr.bh_id
                       INNER JOIN room_units ru ON bhr.bhr_id = ru.bhr_id
                       WHERE bh.user_id = ?
                       AND bh.bh_id IN ($placeholders)
                       GROUP BY bh.bh_id, bh.bh_name";
    $stmtBhOccupancy = $conn->prepare($sqlBhOccupancy);
    if ($stmtBhOccupancy) {
        $types = str_repeat('i', count($ownerBhIds)) . 'i';
        $params = array_merge([$user_id], $ownerBhIds);
        $stmtBhOccupancy->bind_param($types, ...$params);
        $stmtBhOccupancy->execute();
        $resBhOccupancy = $stmtBhOccupancy->get_result();
        while ($row = $resBhOccupancy->fetch_assoc()) {
            $bhId = intval($row["bh_id"]);
            $bhTotalRooms = intval($row["total_rooms"]);
            $bhOccupiedRooms = intval($row["occupied_rooms"]);
            $bhOccupancyRate = $bhTotalRooms > 0 ? round(($bhOccupiedRooms / $bhTotalRooms) * 100, 1) : 0;
            $response["occupancy"]["by_boarding_house"][] = [
                "bh_id" => $bhId,
                "bh_name" => $row["bh_name"],
                "occupancy_rate" => $bhOccupancyRate,
                "total_rooms" => $bhTotalRooms,
                "occupied_rooms" => $bhOccupiedRooms,
                "available_rooms" => $bhTotalRooms - $bhOccupiedRooms
            ];
        }
    }
} else {
    $response["occupancy"]["overall_rate"] = 0;
    $response["occupancy"]["total_rooms"] = 0;
    $response["occupancy"]["occupied_rooms"] = 0;
    $response["occupancy"]["available_rooms"] = 0;
    $response["occupancy"]["by_boarding_house"] = [];
}

// --- Payment Status Summary ---
$response["payment_status"] = [];
if (!empty($bookingIds)) {
    $placeholders = implode(',', array_fill(0, count($bookingIds), '?'));
    
    // Paid payments (already calculated in revenue)
    $response["payment_status"]["paid_count"] = $totalPaymentsValue;
    $response["payment_status"]["paid_amount"] = $totalRevenueValue;
    
    // Pending payments
    // Count all payment_breakdowns that are pending, not just distinct payment_ids
    $sqlPendingPayments = "SELECT 
                              COUNT(pb.breakdown_id) AS pending_count,
                              COALESCE(SUM(pb.amount), 0) AS pending_amount
                          FROM payment_breakdowns pb
                          LEFT JOIN payments p ON pb.payment_id = p.payment_id
                          WHERE (pb.is_paid = 0 OR pb.is_paid IS NULL OR COALESCE(p.payment_status, 'Pending') = 'Pending')
                          AND pb.booking_id IN ($placeholders)";
    $stmtPendingPayments = $conn->prepare($sqlPendingPayments);
    if ($stmtPendingPayments) {
        $types = str_repeat('i', count($bookingIds));
        $stmtPendingPayments->bind_param($types, ...$bookingIds);
        $stmtPendingPayments->execute();
        $resPendingPayments = $stmtPendingPayments->get_result();
        $rowPendingPayments = $resPendingPayments->fetch_assoc();
        $response["payment_status"]["pending_count"] = $rowPendingPayments ? intval($rowPendingPayments["pending_count"]) : 0;
        $response["payment_status"]["pending_amount"] = $rowPendingPayments ? (is_numeric($rowPendingPayments["pending_amount"]) ? floatval($rowPendingPayments["pending_amount"]) : 0) : 0;
    }
    
    // Overdue payments
    $sqlOverduePayments = "SELECT 
                              COUNT(DISTINCT pb.breakdown_id) AS overdue_count,
                              COALESCE(SUM(pb.amount), 0) AS overdue_amount
                          FROM payment_breakdowns pb
                          LEFT JOIN payments p ON pb.payment_id = p.payment_id
                          WHERE (pb.is_paid = 0 OR pb.is_paid IS NULL)
                          AND (pb.due_date < CURDATE() OR pb.period_start_date < CURDATE())
                          AND (COALESCE(p.payment_status, pb.payment_status, 'Pending') = 'Pending' OR pb.payment_status = 'Overdue')
                          AND pb.booking_id IN ($placeholders)";
    $stmtOverduePayments = $conn->prepare($sqlOverduePayments);
    if ($stmtOverduePayments) {
        $types = str_repeat('i', count($bookingIds));
        $stmtOverduePayments->bind_param($types, ...$bookingIds);
        $stmtOverduePayments->execute();
        $resOverduePayments = $stmtOverduePayments->get_result();
        $rowOverduePayments = $resOverduePayments->fetch_assoc();
        $response["payment_status"]["overdue_count"] = $rowOverduePayments ? intval($rowOverduePayments["overdue_count"]) : 0;
        $response["payment_status"]["overdue_amount"] = $rowOverduePayments ? (is_numeric($rowOverduePayments["overdue_amount"]) ? floatval($rowOverduePayments["overdue_amount"]) : 0) : 0;
    }
} else {
    $response["payment_status"]["paid_count"] = 0;
    $response["payment_status"]["paid_amount"] = 0;
    $response["payment_status"]["pending_count"] = 0;
    $response["payment_status"]["pending_amount"] = 0;
    $response["payment_status"]["overdue_count"] = 0;
    $response["payment_status"]["overdue_amount"] = 0;
}

// --- Room Availability ---
$response["room_availability"] = [];
if (!empty($ownerBhIds)) {
    $sqlRoomAvailability = "SELECT 
                               COUNT(DISTINCT CASE WHEN ru.status = 'Occupied' THEN ru.room_id END) AS occupied_rooms,
                               COUNT(DISTINCT CASE WHEN ru.status = 'Available' THEN ru.room_id END) AS available_rooms
                            FROM room_units ru
                            INNER JOIN boarding_house_rooms bhr ON ru.bhr_id = bhr.bhr_id
                            INNER JOIN boarding_houses bh ON bhr.bh_id = bh.bh_id
                            WHERE bh.user_id = ?";
    $stmtRoomAvailability = $conn->prepare($sqlRoomAvailability);
    if ($stmtRoomAvailability) {
        $stmtRoomAvailability->bind_param("i", $user_id);
        $stmtRoomAvailability->execute();
        $resRoomAvailability = $stmtRoomAvailability->get_result();
        $rowRoomAvailability = $resRoomAvailability->fetch_assoc();
        $response["room_availability"]["occupied_rooms"] = $rowRoomAvailability ? intval($rowRoomAvailability["occupied_rooms"]) : 0;
        $response["room_availability"]["available_rooms"] = $rowRoomAvailability ? intval($rowRoomAvailability["available_rooms"]) : 0;
    }
} else {
    $response["room_availability"]["occupied_rooms"] = 0;
    $response["room_availability"]["available_rooms"] = 0;
}

// --- New Applications (Bookings Status) ---
$response["applications"] = [];
if (!empty($bookingIds)) {
    $placeholders = implode(',', array_fill(0, count($bookingIds), '?'));
    
    // Pending applications
    $sqlPendingApps = "SELECT COUNT(DISTINCT b.booking_id) AS pending_count
                      FROM bookings b
                      WHERE b.booking_status = 'Pending'
                      AND b.booking_id IN ($placeholders)";
    $stmtPendingApps = $conn->prepare($sqlPendingApps);
    if ($stmtPendingApps) {
        $types = str_repeat('i', count($bookingIds));
        $stmtPendingApps->bind_param($types, ...$bookingIds);
        $stmtPendingApps->execute();
        $resPendingApps = $stmtPendingApps->get_result();
        $rowPendingApps = $resPendingApps->fetch_assoc();
        $response["applications"]["pending_count"] = $rowPendingApps ? intval($rowPendingApps["pending_count"]) : 0;
    }
    
    // Approved applications (Confirmed bookings)
    $sqlApprovedApps = "SELECT COUNT(DISTINCT b.booking_id) AS approved_count
                       FROM bookings b
                       WHERE b.booking_status = 'Confirmed'
                       AND b.booking_id IN ($placeholders)";
    $stmtApprovedApps = $conn->prepare($sqlApprovedApps);
    if ($stmtApprovedApps) {
        $types = str_repeat('i', count($bookingIds));
        $stmtApprovedApps->bind_param($types, ...$bookingIds);
        $stmtApprovedApps->execute();
        $resApprovedApps = $stmtApprovedApps->get_result();
        $rowApprovedApps = $resApprovedApps->fetch_assoc();
        $response["applications"]["approved_count"] = $rowApprovedApps ? intval($rowApprovedApps["approved_count"]) : 0;
    }
    
    // Rejected applications (Cancelled bookings)
    $sqlRejectedApps = "SELECT COUNT(DISTINCT b.booking_id) AS rejected_count
                       FROM bookings b
                       WHERE b.booking_status = 'Cancelled'
                       AND b.booking_id IN ($placeholders)";
    $stmtRejectedApps = $conn->prepare($sqlRejectedApps);
    if ($stmtRejectedApps) {
        $types = str_repeat('i', count($bookingIds));
        $stmtRejectedApps->bind_param($types, ...$bookingIds);
        $stmtRejectedApps->execute();
        $resRejectedApps = $stmtRejectedApps->get_result();
        $rowRejectedApps = $resRejectedApps->fetch_assoc();
        $response["applications"]["rejected_count"] = $rowRejectedApps ? intval($rowRejectedApps["rejected_count"]) : 0;
    }
} else {
    $response["applications"]["pending_count"] = 0;
    $response["applications"]["approved_count"] = 0;
    $response["applications"]["rejected_count"] = 0;
}

// Initialize charts array
error_log("=== CHARTS CALCULATION START ===");
$response["charts"] = [];

// --- Monthly Revenue Chart Data (last 6 months with all months included) ---
// Use the same booking IDs list
// CRITICAL: Only count where is_paid = 1 AND payment_status is NOT Pending
$monthlyChartData = [];
// First, generate last 6 months structure
for ($i = 5; $i >= 0; $i--) {
    $monthDate = date('Y-m', strtotime("-$i months"));
    $monthLabel = date('M Y', strtotime("-$i months"));
    $monthlyChartData[$monthDate] = [
        "month" => $monthDate,
        "month_label" => $monthLabel,
        "revenue" => 0
    ];
}

if (!empty($bookingIds)) {
    $placeholders = implode(',', array_fill(0, count($bookingIds), '?'));
    $sqlMonthlyChart = "SELECT 
                           DATE_FORMAT(p.payment_date, '%Y-%m') AS month,
                           DATE_FORMAT(p.payment_date, '%b %Y') AS month_label,
                           COALESCE(SUM(pb.amount), 0) AS revenue
                       FROM payment_breakdowns pb
                       INNER JOIN payments p ON pb.payment_id = p.payment_id
                       WHERE pb.is_paid = 1
                       AND COALESCE(p.payment_status, 'Pending') IN ('Completed', 'Partially Paid', 'Fully Paid', 'Paid')
                       AND p.payment_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                       AND pb.booking_id IN ($placeholders)
                       GROUP BY DATE_FORMAT(p.payment_date, '%Y-%m'), DATE_FORMAT(p.payment_date, '%b %Y')
                       ORDER BY DATE_FORMAT(p.payment_date, '%Y-%m') ASC";
    error_log("Monthly Chart SQL: " . $sqlMonthlyChart);
    $stmtMonthlyChart = $conn->prepare($sqlMonthlyChart);
    if (!$stmtMonthlyChart) {
        error_log("ERROR: Failed to prepare monthly chart statement: " . $conn->error);
    } else {
        $types = str_repeat('i', count($bookingIds));
        $stmtMonthlyChart->bind_param($types, ...$bookingIds);
        if ($stmtMonthlyChart->execute()) {
            $resMonthlyChart = $stmtMonthlyChart->get_result();
            while ($row = $resMonthlyChart->fetch_assoc()) {
                $monthKey = $row["month"];
                $revenueValue = is_numeric($row["revenue"]) ? floatval($row["revenue"]) : 0;
                if (isset($monthlyChartData[$monthKey])) {
                    $monthlyChartData[$monthKey]["revenue"] = $revenueValue;
                    $monthlyChartData[$monthKey]["month_label"] = $row["month_label"];
                }
            }
        } else {
            error_log("ERROR: Failed to execute monthly chart query: " . $stmtMonthlyChart->error);
        }
    }
}

// Convert to indexed array and reorder
$monthlyChartData = array_values($monthlyChartData);
error_log("Monthly Chart - Months: " . count($monthlyChartData) . ", Data: " . json_encode($monthlyChartData));
$response["charts"]["monthly_revenue"] = $monthlyChartData;

// --- Top-Paying Rooms (top 10 rooms by total revenue) ---
// Use the same booking IDs list to ensure only owner's bookings
if (!empty($bookingIds)) {
    $placeholders = implode(',', array_fill(0, count($bookingIds), '?'));
    $sqlTopRooms = "SELECT 
                       CONCAT(LEFT(bh.bh_name, 15), ' - R', ru.room_number) AS room_label,
                       bhr.room_name,
                       ru.room_number,
                       bh.bh_name,
                       COALESCE(SUM(pb.amount), 0) AS total_revenue,
                       COUNT(DISTINCT pb.payment_id) AS payment_count
                   FROM payment_breakdowns pb
                   INNER JOIN bookings b ON pb.booking_id = b.booking_id
                   INNER JOIN room_units ru ON b.room_id = ru.room_id
                   INNER JOIN boarding_house_rooms bhr ON ru.bhr_id = bhr.bhr_id
                   INNER JOIN boarding_houses bh ON bhr.bh_id = bh.bh_id
                   LEFT JOIN payments p ON pb.payment_id = p.payment_id
                   WHERE bh.user_id = ?
                   AND pb.is_paid = 1
                   AND COALESCE(p.payment_status, 'Pending') IN ('Completed', 'Partially Paid', 'Fully Paid', 'Paid')
                   AND pb.booking_id IN ($placeholders)
                   GROUP BY bh.bh_id, bhr.bhr_id, ru.room_id, bh.bh_name, bhr.room_name, ru.room_number
                   HAVING total_revenue > 0
                   ORDER BY total_revenue DESC
                   LIMIT 10";
    $stmtTopRooms = $conn->prepare($sqlTopRooms);
    if (!$stmtTopRooms) {
        error_log("ERROR: Failed to prepare top rooms statement: " . $conn->error);
        $response["charts"]["top_paying_rooms"] = [];
    } else {
        // Bind user_id first, then all booking IDs
        $types = 'i' . str_repeat('i', count($bookingIds));
        $params = array_merge([$user_id], $bookingIds);
        $stmtTopRooms->bind_param($types, ...$params);
        if (!$stmtTopRooms->execute()) {
            error_log("ERROR: Failed to execute top rooms query: " . $stmtTopRooms->error);
            $response["charts"]["top_paying_rooms"] = [];
        } else {
            $resTopRooms = $stmtTopRooms->get_result();
            $topRoomsData = [];
            $rowCount = 0;
            while ($row = $resTopRooms->fetch_assoc()) {
                $rowCount++;
                $roomRevenueValue = is_numeric($row["total_revenue"]) ? floatval($row["total_revenue"]) : 0;
                $roomPaymentCountValue = is_numeric($row["payment_count"]) ? intval($row["payment_count"]) : 0;
                $topRoomsData[] = [
                    "room_label" => $row["room_label"],
                    "room_name" => $row["room_name"],
                    "room_number" => $row["room_number"],
                    "bh_name" => $row["bh_name"],
                    "total_revenue" => $roomRevenueValue,
                    "payment_count" => $roomPaymentCountValue
                ];
            }
            error_log("Top Rooms Chart - Rows found: " . $rowCount . ", Data: " . json_encode($topRoomsData));
            $response["charts"]["top_paying_rooms"] = $topRoomsData;
        }
    }
} else {
    $response["charts"]["top_paying_rooms"] = [];
}

// --- Payment Activity Over Time (last 30 days daily activity) ---
// Use the same booking IDs list
// CRITICAL: Only count where is_paid = 1 AND payment_status is NOT Pending
if (!empty($bookingIds)) {
    $placeholders = implode(',', array_fill(0, count($bookingIds), '?'));
    $sqlPaymentActivity = "SELECT 
                              DATE(p.payment_date) AS payment_day,
                              DATE_FORMAT(p.payment_date, '%b %d') AS day_label,
                              COUNT(DISTINCT pb.payment_id) AS payment_count,
                              COALESCE(SUM(pb.amount), 0) AS daily_revenue
                          FROM payment_breakdowns pb
                          INNER JOIN payments p ON pb.payment_id = p.payment_id
                          WHERE pb.is_paid = 1
                          AND COALESCE(p.payment_status, 'Pending') IN ('Completed', 'Partially Paid', 'Fully Paid', 'Paid')
                          AND p.payment_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                          AND pb.booking_id IN ($placeholders)
                          GROUP BY DATE(p.payment_date), DATE_FORMAT(p.payment_date, '%b %d')
                          ORDER BY DATE(p.payment_date) ASC";
    $stmtPaymentActivity = $conn->prepare($sqlPaymentActivity);
    if (!$stmtPaymentActivity) {
        error_log("ERROR: Failed to prepare payment activity statement: " . $conn->error);
        $response["charts"]["payment_activity"] = [];
    } else {
        $types = str_repeat('i', count($bookingIds));
        $stmtPaymentActivity->bind_param($types, ...$bookingIds);
        if (!$stmtPaymentActivity->execute()) {
            error_log("ERROR: Failed to execute payment activity query: " . $stmtPaymentActivity->error);
            $response["charts"]["payment_activity"] = [];
        } else {
            $resPaymentActivity = $stmtPaymentActivity->get_result();
            $paymentActivityData = [];
            $rowCount = 0;
            while ($row = $resPaymentActivity->fetch_assoc()) {
                $rowCount++;
                $activityRevenueValue = is_numeric($row["daily_revenue"]) ? floatval($row["daily_revenue"]) : 0;
                $activityCountValue = is_numeric($row["payment_count"]) ? intval($row["payment_count"]) : 0;
                $paymentActivityData[] = [
                    "date" => $row["payment_day"],
                    "day_label" => $row["day_label"],
                    "payment_count" => $activityCountValue,
                    "revenue" => $activityRevenueValue
                ];
            }
            error_log("Payment Activity Chart - Rows found: " . $rowCount . ", Data: " . json_encode($paymentActivityData));
            $response["charts"]["payment_activity"] = $paymentActivityData;
        }
    }
} else {
    $response["charts"]["payment_activity"] = [];
}

error_log("=== FINAL RESPONSE ===");
error_log("Revenue object: " . json_encode($response["revenue"] ?? []));
error_log("Charts object: " . json_encode($response["charts"] ?? []));
error_log("Total response size: " . strlen(json_encode($response)) . " bytes");

// --- Output JSON ---
$jsonOutput = json_encode($response, JSON_UNESCAPED_SLASHES);
if ($jsonOutput === false) {
    error_log("ERROR: JSON encoding failed: " . json_last_error_msg());
    error_log("Response data: " . print_r($response, true));
    echo json_encode(["error" => "Failed to encode response", "json_error" => json_last_error_msg()]);
} else {
    error_log("JSON output length: " . strlen($jsonOutput) . " bytes");
    echo $jsonOutput;
}
?>
