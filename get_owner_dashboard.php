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

$response = [];

// --- Validate user_id ---
$user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
if ($user_id <= 0) {
    echo json_encode(["error" => "Invalid user_id"]);
    exit;
}

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

// --- Output JSON ---
echo json_encode($response, JSON_UNESCAPED_SLASHES);
?>
