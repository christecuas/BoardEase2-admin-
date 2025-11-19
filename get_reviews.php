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

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, ngrok-skip-browser-warning, User-Agent, Accept');

// Database configuration
$host = 'localhost';
$dbname = 'boardease2';
$username = 'boardease';
$password = 'boardease';

try {
    $conn = new mysqli($host, $username, $password, $dbname);

    if ($conn->connect_error) {
        echo json_encode(array('success' => false, 'error' => 'Database connection failed: ' . $conn->connect_error));
        exit;
    }
} catch (Exception $e) {
    echo json_encode(array('success' => false, 'error' => 'Database connection error: ' . $e->getMessage()));
    exit;
}

// Get parameters from GET or POST
$boardingHouseId = isset($_GET['boarding_house_id']) ? intval($_GET['boarding_house_id']) : (isset($_POST['boarding_house_id']) ? intval($_POST['boarding_house_id']) : 0);
$ownerId = isset($_GET['owner_id']) ? intval($_GET['owner_id']) : (isset($_POST['owner_id']) ? intval($_POST['owner_id']) : 0);
$boarderId = isset($_GET['boarder_id']) ? intval($_GET['boarder_id']) : (isset($_POST['boarder_id']) ? intval($_POST['boarder_id']) : 0);
$ratingFilter = isset($_GET['rating']) ? trim($_GET['rating']) : (isset($_POST['rating']) ? trim($_POST['rating']) : 'all');
$statusFilter = isset($_GET['status']) ? trim($_GET['status']) : (isset($_POST['status']) ? trim($_POST['status']) : 'published');
$sortBy = isset($_GET['sort_by']) ? trim($_GET['sort_by']) : (isset($_POST['sort_by']) ? trim($_POST['sort_by']) : 'newest');
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : (isset($_POST['limit']) ? intval($_POST['limit']) : 50);
$offset = isset($_GET['offset']) ? intval($_GET['offset']) : (isset($_POST['offset']) ? intval($_POST['offset']) : 0);

// Base URL for profile pictures
$baseUrl = 'https://reflective-perkily-jakobe.ngrok-free.dev/BoardEase2/';

try {
    // Build the WHERE clause
    $whereConditions = array();
    $bindTypes = "";
    $bindValues = array();

    // Filter by owner_id (get reviews for all boarding houses owned by this owner)
    if ($ownerId > 0) {
        $whereConditions[] = "bh.user_id = ?";
        $bindTypes .= "i";
        $bindValues[] = $ownerId;
    }

    // Filter by boarding_house_id
    if ($boardingHouseId > 0) {
        $whereConditions[] = "r.bh_id = ?";
        $bindTypes .= "i";
        $bindValues[] = $boardingHouseId;
    }

    // Filter by boarder_id (user who wrote the review)
    if ($boarderId > 0) {
        $whereConditions[] = "r.user_id = ?";
        $bindTypes .= "i";
        $bindValues[] = $boarderId;
    }

    // Filter by rating
    if ($ratingFilter !== 'all' && is_numeric($ratingFilter) && $ratingFilter >= 1 && $ratingFilter <= 5) {
        $whereConditions[] = "r.rating = ?";
        $bindTypes .= "i";
        $bindValues[] = intval($ratingFilter);
    }

    // Build WHERE clause
    $whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";

    // Build ORDER BY clause
    $orderByClause = "ORDER BY r.review_created_at DESC"; // Default: newest first
    if ($sortBy === 'oldest') {
        $orderByClause = "ORDER BY r.review_created_at ASC";
    } elseif ($sortBy === 'highest_rating') {
        $orderByClause = "ORDER BY r.rating DESC, r.review_created_at DESC";
    } elseif ($sortBy === 'lowest_rating') {
        $orderByClause = "ORDER BY r.rating ASC, r.review_created_at DESC";
    }

    // Build LIMIT clause
    $limitClause = "LIMIT ? OFFSET ?";
    $bindTypes .= "ii";
    $bindValues[] = $limit;
    $bindValues[] = $offset;

    // Main query to get reviews with boarder and boarding house information
    $sql = "SELECT 
                r.review_id,
                r.user_id as boarder_user_id,
                r.bh_id,
                r.rating,
                r.comment,
                DATE_FORMAT(r.review_created_at, '%Y-%m-%d %H:%i:%s') as created_at,
                -- Boarder information
                CONCAT(
                    COALESCE(reg.first_name, ''), ' ',
                    COALESCE(reg.middle_name, ''), ' ',
                    COALESCE(reg.last_name, ''),
                    CASE 
                        WHEN reg.suffix IS NOT NULL AND reg.suffix != '' 
                        THEN CONCAT(' ', reg.suffix) 
                        ELSE '' 
                    END
                ) as boarder_name,
                COALESCE(u.profile_picture, '') as boarder_profile_picture,
                -- Boarding house information
                bh.bh_name as boarding_house_name,
                bh.bh_address as boarding_house_address,
                -- Owner information
                CONCAT(
                    COALESCE(owner_reg.first_name, ''), ' ',
                    COALESCE(owner_reg.middle_name, ''), ' ',
                    COALESCE(owner_reg.last_name, ''),
                    CASE 
                        WHEN owner_reg.suffix IS NOT NULL AND owner_reg.suffix != '' 
                        THEN CONCAT(' ', owner_reg.suffix) 
                        ELSE '' 
                    END
                ) as owner_name,
                -- Room information (get from most recent booking)
                COALESCE((
                    SELECT ru.room_number 
                    FROM bookings b2
                    INNER JOIN room_units ru ON b2.room_id = ru.room_id
                    INNER JOIN boarding_house_rooms bhr2 ON ru.bhr_id = bhr2.bhr_id
                    WHERE b2.user_id = r.user_id 
                        AND bhr2.bh_id = r.bh_id
                        AND b2.booking_status IN ('Confirmed', 'Completed')
                    ORDER BY b2.booking_date DESC
                    LIMIT 1
                ), '') as room_number,
                COALESCE((
                    SELECT bhr3.room_name 
                    FROM bookings b3
                    INNER JOIN room_units ru2 ON b3.room_id = ru2.room_id
                    INNER JOIN boarding_house_rooms bhr3 ON ru2.bhr_id = bhr3.bhr_id
                    WHERE b3.user_id = r.user_id 
                        AND bhr3.bh_id = r.bh_id
                        AND b3.booking_status IN ('Confirmed', 'Completed')
                    ORDER BY b3.booking_date DESC
                    LIMIT 1
                ), '') as room_name
            FROM reviews r
            INNER JOIN boarding_houses bh ON r.bh_id = bh.bh_id
            INNER JOIN users u ON r.user_id = u.user_id
            INNER JOIN registrations reg ON u.reg_id = reg.id
            LEFT JOIN users owner_user ON bh.user_id = owner_user.user_id
            LEFT JOIN registrations owner_reg ON owner_user.reg_id = owner_reg.id
            $whereClause
            $orderByClause
            $limitClause";

    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    // Bind parameters if any
    if (!empty($bindValues)) {
        $stmt->bind_param($bindTypes, ...$bindValues);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $reviews = array();
    while ($row = $result->fetch_assoc()) {
        // Format profile picture URL
        $profilePicture = $row['boarder_profile_picture'];
        if (!empty($profilePicture)) {
            if (strpos($profilePicture, 'http://') === 0 || strpos($profilePicture, 'https://') === 0) {
                // Already a full URL
            } else {
                $cleanPath = ltrim($profilePicture, '/');
                if (strpos($cleanPath, 'uploads/') !== 0) {
                    $cleanPath = 'uploads/' . $cleanPath;
                }
                $profilePicture = $baseUrl . $cleanPath;
            }
        }

        // Format review date
        $createdAt = $row['created_at'];
        if (!empty($createdAt)) {
            $date = new DateTime($createdAt);
            $createdAt = $date->format('M d, Y');
        }

        // Format room info: "Room Name: Room Number"
        $roomName = $row['room_name'] ? trim($row['room_name']) : '';
        $roomNumber = $row['room_number'] ? trim($row['room_number']) : '';
        $roomInfo = '';
        
        if (!empty($roomName) && !empty($roomNumber)) {
            // Format: "Private Room 01: Room PR0-7"
            $roomInfo = $roomName . ': Room ' . $roomNumber;
        } elseif (!empty($roomName)) {
            // Only room name available
            $roomInfo = $roomName;
        } elseif (!empty($roomNumber)) {
            // Only room number available
            $roomInfo = 'Room ' . $roomNumber;
        }

        // Build review object matching Android app expectations
        $review = array(
            'review_id' => intval($row['review_id']),
            'boarder_name' => $row['boarder_name'] ? trim($row['boarder_name']) : 'Anonymous',
            'boarding_house_name' => $row['boarding_house_name'] ? $row['boarding_house_name'] : '',
            'room_name' => $roomInfo, // Send formatted room info
            'room_number' => $roomNumber, // Keep original for reference if needed
            'overall_rating' => intval($row['rating']),
            'review_text' => $row['comment'] ? $row['comment'] : '',
            'created_at' => $createdAt,
            'boarder_profile_picture' => $profilePicture,
            // Additional fields (set defaults for fields not in current database)
            'title' => '',
            'cleanliness_rating' => 0,
            'location_rating' => 0,
            'value_rating' => 0,
            'amenities_rating' => 0,
            'safety_rating' => 0,
            'management_rating' => 0,
            'average_rating' => floatval($row['rating']),
            'images' => '',
            'would_recommend' => false,
            'stay_duration' => '',
            'visit_type' => '',
            'status' => $statusFilter,
            'helpful_count' => 0,
            'owner_response' => '',
            'owner_response_date' => '',
            'university' => '',
            'student_id' => '',
            'boarding_house_address' => $row['boarding_house_address'] ? $row['boarding_house_address'] : '',
            'owner_name' => $row['owner_name'] ? trim($row['owner_name']) : ''
        );

        $reviews[] = $review;
    }

    $stmt->close();

    // Get total count for pagination
    $countSql = "SELECT COUNT(*) as total
                 FROM reviews r
                 INNER JOIN boarding_houses bh ON r.bh_id = bh.bh_id
                 $whereClause";
    
    $countStmt = $conn->prepare($countSql);
    if ($countStmt) {
        // Remove limit and offset from bind values for count query
        $countBindValues = array();
        $countBindTypes = "";
        if ($ownerId > 0) {
            $countBindTypes .= "i";
            $countBindValues[] = $ownerId;
        }
        if ($boardingHouseId > 0) {
            $countBindTypes .= "i";
            $countBindValues[] = $boardingHouseId;
        }
        if ($boarderId > 0) {
            $countBindTypes .= "i";
            $countBindValues[] = $boarderId;
        }
        if ($ratingFilter !== 'all' && is_numeric($ratingFilter) && $ratingFilter >= 1 && $ratingFilter <= 5) {
            $countBindTypes .= "i";
            $countBindValues[] = intval($ratingFilter);
        }
        
        if (!empty($countBindValues)) {
            $countStmt->bind_param($countBindTypes, ...$countBindValues);
        }
        $countStmt->execute();
        $countResult = $countStmt->get_result();
        $countRow = $countResult->fetch_assoc();
        $totalCount = $countRow ? intval($countRow['total']) : 0;
        $countStmt->close();
    } else {
        $totalCount = count($reviews);
    }

    // Return response in format expected by Android app
    echo json_encode(array(
        'success' => true,
        'data' => array(
            'reviews' => $reviews,
            'total' => $totalCount,
            'limit' => $limit,
            'offset' => $offset
        )
    ), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    error_log("Error in get_reviews.php: " . $e->getMessage());
    echo json_encode(array(
        'success' => false,
        'error' => 'Failed to retrieve reviews: ' . $e->getMessage()
    ));
} finally {
    $conn->close();
}
?>

