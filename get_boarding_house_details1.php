<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Database configuration
define('DB_HOST', '');
define('DB_USER', 'u223444398_userboardease');
define('DB_PASS', '!Boardease2026');
define('DB_NAME', 'u223444398_boardease');

$host = DB_HOST;
$dbname = DB_NAME;
$username = DB_USER;
$password = DB_PASS;

try {
    // Create PDO connection
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get boarding house ID from request
    $bhId = isset($_GET['bh_id']) ? (int)$_GET['bh_id'] : 0;

    if ($bhId === 0) {
        echo json_encode(array('success' => false, 'error' => 'Boarding house ID is required.'));
        exit();
    }

    // First, get boarding house details
    $bhSql = "SELECT * FROM boarding_houses WHERE bh_id = ?";
    $bhStmt = $pdo->prepare($bhSql);
    $bhStmt->execute([$bhId]);
    $boardingHouse = $bhStmt->fetch(PDO::FETCH_ASSOC);

    if (!$boardingHouse) {
        echo json_encode(array('success' => false, 'error' => 'Boarding house not found.'));
        exit();
    }

    // Debug: Log database and table info
    error_log("DEBUG: Connected to database: " . $dbname);
    error_log("DEBUG: Querying boarding_houses table for bh_id: " . $bhId);
    error_log("DEBUG: Found boarding house: " . $boardingHouse['bh_name']);
    error_log("DEBUG: Boarding house user_id: " . $boardingHouse['user_id']);
    error_log("DEBUG: Boarding house user_id type: " . gettype($boardingHouse['user_id']));
    
    // Verify the user_id value
    if (isset($boardingHouse['user_id'])) {
        error_log("DEBUG: user_id value: " . var_export($boardingHouse['user_id'], true));
    } else {
        error_log("DEBUG: ERROR - user_id key does not exist in boarding house data!");
    }
    error_log("DEBUG: bh_rules value: '" . $boardingHouse['bh_rules'] . "'");
    error_log("DEBUG: bh_rules is null: " . (is_null($boardingHouse['bh_rules']) ? 'true' : 'false'));
    error_log("DEBUG: bh_rules is empty: " . (empty($boardingHouse['bh_rules']) ? 'true' : 'false'));
    
    // Now get owner information separately to avoid any JOIN issues
    $ownerData = array(
        'first_name' => null,
        'middle_name' => null,
        'last_name' => null,
        'phone' => null,
        'email' => null,
        'role' => null,
        'profile_picture' => null,
        'gcash_qr' => null,
        'gcash_num' => null
    );
    
    if (!empty($boardingHouse['user_id'])) {
        // Use the correct join structure: boarding_houses.user_id -> users.user_id -> users.reg_id -> registrations.id
        $userId = (int)$boardingHouse['user_id'];
        error_log("DEBUG: Querying owner info via users table for user_id (as int): " . $userId);
        
        $ownerSql = "SELECT 
                        u.user_id,
                        u.profile_picture,
                        r.id as reg_id,
                        r.first_name,
                        r.middle_name,
                        r.last_name,
                        r.phone,
                        r.email,
                        r.role,
                        r.gcash_qr,
                        r.gcash_num
                    FROM users u
                    JOIN registrations r ON u.reg_id = r.id
                    WHERE u.user_id = ?";
        $ownerStmt = $pdo->prepare($ownerSql);
        $ownerStmt->execute([$userId]);
        $ownerResult = $ownerStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($ownerResult) {
            error_log("DEBUG: Owner found via users table for user_id: " . $userId);
            error_log("DEBUG: Owner data from database: " . json_encode($ownerResult));
            
            // Check if fields are actually null or empty
            if (!empty($ownerResult['first_name']) || !empty($ownerResult['phone']) || !empty($ownerResult['email'])) {
                error_log("DEBUG: Owner has data - first_name: '" . $ownerResult['first_name'] . "', phone: '" . $ownerResult['phone'] . "', email: '" . $ownerResult['email'] . "'");
            } else {
                error_log("DEBUG: WARNING - Owner record exists but all fields are null/empty!");
                error_log("DEBUG: Raw values - first_name: " . var_export($ownerResult['first_name'], true) . 
                         ", phone: " . var_export($ownerResult['phone'], true) . 
                         ", email: " . var_export($ownerResult['email'], true));
            }
            
            $ownerData = array(
                'first_name' => !empty($ownerResult['first_name']) ? $ownerResult['first_name'] : null,
                'middle_name' => !empty($ownerResult['middle_name']) ? $ownerResult['middle_name'] : null,
                'last_name' => !empty($ownerResult['last_name']) ? $ownerResult['last_name'] : null,
                'phone' => !empty($ownerResult['phone']) ? $ownerResult['phone'] : null,
                'email' => !empty($ownerResult['email']) ? $ownerResult['email'] : null,
                'role' => !empty($ownerResult['role']) ? $ownerResult['role'] : null,
                'profile_picture' => !empty($ownerResult['profile_picture']) ? $ownerResult['profile_picture'] : null,
                'gcash_qr' => !empty($ownerResult['gcash_qr']) && $ownerResult['gcash_qr'] !== 'null' ? $ownerResult['gcash_qr'] : null,
                'gcash_num' => !empty($ownerResult['gcash_num']) && $ownerResult['gcash_num'] !== 'null' ? $ownerResult['gcash_num'] : null
            );
        } else {
            error_log("DEBUG: WARNING - No owner found via users table for user_id: " . $userId);
            
            // Debug: Check if user exists in users table
            $checkUserSql = "SELECT user_id, reg_id FROM users WHERE user_id = ?";
            $checkStmt = $pdo->prepare($checkUserSql);
            $checkStmt->execute([$userId]);
            $userCheck = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($userCheck) {
                error_log("DEBUG: User found in users table with reg_id: " . ($userCheck['reg_id'] ?? 'null'));
                
                // Try to get registration directly if reg_id exists
                if (!empty($userCheck['reg_id'])) {
                    $regId = (int)$userCheck['reg_id'];
                    $regSql = "SELECT first_name, middle_name, last_name, phone, email, role, gcash_qr, gcash_num FROM registrations WHERE id = ?";
                    $regStmt = $pdo->prepare($regSql);
                    $regStmt->execute([$regId]);
                    $regResult = $regStmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($regResult) {
                        error_log("DEBUG: Found registration directly with reg_id: " . $regId);
                        $ownerData = array(
                            'first_name' => !empty($regResult['first_name']) ? $regResult['first_name'] : null,
                            'middle_name' => !empty($regResult['middle_name']) ? $regResult['middle_name'] : null,
                            'last_name' => !empty($regResult['last_name']) ? $regResult['last_name'] : null,
                            'phone' => !empty($regResult['phone']) ? $regResult['phone'] : null,
                            'email' => !empty($regResult['email']) ? $regResult['email'] : null,
                            'role' => !empty($regResult['role']) ? $regResult['role'] : null,
                            'profile_picture' => null,
                            'gcash_qr' => !empty($regResult['gcash_qr']) && $regResult['gcash_qr'] !== 'null' ? $regResult['gcash_qr'] : null,
                            'gcash_num' => !empty($regResult['gcash_num']) && $regResult['gcash_num'] !== 'null' ? $regResult['gcash_num'] : null
                        );
                    } else {
                        error_log("DEBUG: Registration not found for reg_id: " . $regId);
                    }
                } else {
                    error_log("DEBUG: User has no reg_id set!");
                }
            } else {
                error_log("DEBUG: User not found in users table for user_id: " . $userId);
            }
        }
    } else {
        error_log("DEBUG: WARNING - Boarding house has no user_id set!");
    }
    
    // Log final owner data
    error_log("DEBUG: Final owner data being sent: " . json_encode($ownerData));
    
    // Additional debugging: Check if any registrations exist at all
    $allUsersSql = "SELECT id, first_name, last_name FROM registrations LIMIT 5";
    $allUsersStmt = $pdo->prepare($allUsersSql);
    $allUsersStmt->execute();
    $allUsers = $allUsersStmt->fetchAll(PDO::FETCH_ASSOC);
    error_log("DEBUG: Sample users in registrations table: " . json_encode($allUsers));
    
    // Debug: Check bh_rules specifically
    $bhRulesValue = $boardingHouse['bh_rules'];
    $finalBhRules = !empty($bhRulesValue) ? $bhRulesValue : 'No specific rules';
    error_log("DEBUG: Raw bh_rules from DB: '" . $bhRulesValue . "'");
    error_log("DEBUG: Final bh_rules value being sent: '" . $finalBhRules . "'");
    error_log("DEBUG: bh_rules is null: " . (is_null($bhRulesValue) ? 'true' : 'false'));
    error_log("DEBUG: bh_rules is empty: " . (empty($bhRulesValue) ? 'true' : 'false'));

    // Fetch images for this boarding house
    $imagesSql = "
        SELECT image_path
        FROM boarding_house_images
        WHERE bh_id = ?
        ORDER BY image_id ASC
    ";
    $imagesStmt = $pdo->prepare($imagesSql);
    $imagesStmt->execute([$bhId]);
    $images = $imagesStmt->fetchAll(PDO::FETCH_COLUMN);

    // FIXED: Use ngrok URL to match Android app base URL
    // This ensures images are accessible from the Android device
    $baseUrl = 'https://boardease.calapebohol.com/';

    // Format image URLs
    $formattedImages = array();
    foreach ($images as $imagePath) {
        if (!empty($imagePath)) {
            // Remove any leading slashes or paths that might already include base URL
            $cleanPath = ltrim($imagePath, '/');
            // If image_path already contains 'uploads/', use it as is, otherwise prepend 'uploads/'
            if (strpos($cleanPath, 'uploads/') === 0) {
                $formattedImages[] = $baseUrl . $cleanPath;
            } else {
                $formattedImages[] = $baseUrl . 'uploads/' . $cleanPath;
            }
        }
    }

    // If no images, add placeholder
    if (empty($formattedImages)) {
        $formattedImages[] = 'https://via.placeholder.com/400x300?text=No+Image+Available';
    }

    // Debug: Log image URLs
    error_log("DEBUG: Image URLs being sent: " . json_encode($formattedImages));

    // Fetch room categories for this boarding house
    $roomsSql = "
        SELECT DISTINCT room_category
        FROM boarding_house_rooms
        WHERE bh_id = ?
        ORDER BY room_category ASC
    ";
    $roomsStmt = $pdo->prepare($roomsSql);
    $roomsStmt->execute([$bhId]);
    $roomCategories = $roomsStmt->fetchAll(PDO::FETCH_COLUMN);

    // Fetch detailed room information
    $roomDetailsSql = "
        SELECT
            bhr_id,
            room_category,
            room_name,
            price,
            capacity,
            room_description,
            total_rooms,
            created_at
        FROM boarding_house_rooms
        WHERE bh_id = ?
        ORDER BY room_category, price ASC
    ";
    $roomDetailsStmt = $pdo->prepare($roomDetailsSql);
    $roomDetailsStmt->execute([$bhId]);
    $roomDetails = $roomDetailsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Debug: Log room details query results
    error_log("DEBUG: get_boarding_house_details.php - Querying rooms for bh_id: " . $bhId);
    error_log("DEBUG: get_boarding_house_details.php - Found " . count($roomDetails) . " rooms");
    if (count($roomDetails) > 0) {
        error_log("DEBUG: get_boarding_house_details.php - First room: " . json_encode($roomDetails[0]));
        error_log("DEBUG: get_boarding_house_details.php - All rooms: " . json_encode($roomDetails));
    } else {
        error_log("DEBUG: get_boarding_house_details.php - WARNING: No rooms found for bh_id: " . $bhId);
        // Double-check by querying directly
        $checkSql = "SELECT COUNT(*) as count FROM boarding_house_rooms WHERE bh_id = ?";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute([$bhId]);
        $checkResult = $checkStmt->fetch(PDO::FETCH_ASSOC);
        error_log("DEBUG: get_boarding_house_details.php - Direct count query result: " . $checkResult['count']);
    }

    // Calculate price range
    $priceRangeSql = "
        SELECT
            MIN(price) as min_price,
            MAX(price) as max_price
        FROM boarding_house_rooms
        WHERE bh_id = ?
    ";
    $priceRangeStmt = $pdo->prepare($priceRangeSql);
    $priceRangeStmt->execute([$bhId]);
    $priceRange = $priceRangeStmt->fetch(PDO::FETCH_ASSOC);

    // Format the response
    $response = array(
        'success' => true,
        'data' => array(
            'boarding_house' => array(
                'bh_id' => (int)$boardingHouse['bh_id'],
                'bh_name' => $boardingHouse['bh_name'],
                'bh_address' => $boardingHouse['bh_address'],
                'bh_description' => $boardingHouse['bh_description'],
                'bh_rules' => $boardingHouse['bh_rules'] ?? 'No specific rules',
                'number_of_bathroom' => (int)$boardingHouse['number_of_bathroom'],
                'area' => (float)$boardingHouse['area'],
                'build_year' => (int)$boardingHouse['build_year'],
                'status' => $boardingHouse['status'],
                'bh_created_at' => $boardingHouse['bh_created_at'],
                'images' => $formattedImages,
                'room_categories' => $roomCategories,
                'room_details' => $roomDetails,
                'min_price' => $priceRange['min_price'] ? (int)$priceRange['min_price'] : null,
                'max_price' => $priceRange['max_price'] ? (int)$priceRange['max_price'] : null,
                'gcash_qr' => !empty($ownerData['gcash_qr']) && $ownerData['gcash_qr'] !== 'null' ? 
                    (strpos($ownerData['gcash_qr'], 'http://') === 0 || strpos($ownerData['gcash_qr'], 'https://') === 0 ? 
                        str_replace('reflective-perkily-jakobe.ngrok-free.dev', 'boardease.calapebohol.com', $ownerData['gcash_qr']) : 
                        $baseUrl . (strpos($ownerData['gcash_qr'], 'uploads/') === 0 ? $ownerData['gcash_qr'] : 'uploads/' . $ownerData['gcash_qr'])) 
                    : null, // Include GCash QR code with full URL
                'gcash_number' => !empty($ownerData['gcash_num']) && $ownerData['gcash_num'] !== 'null' ? $ownerData['gcash_num'] : null, // Include GCash number
                'owner' => array(
                    'first_name' => $ownerData['first_name'],
                    'middle_name' => $ownerData['middle_name'],
                    'last_name' => $ownerData['last_name'],
                    'phone' => $ownerData['phone'],
                    'email' => $ownerData['email'],
                    'role' => $ownerData['role'],
                    'profile_picture' => $ownerData['profile_picture'],
                    'gcash_number' => !empty($ownerData['gcash_num']) && $ownerData['gcash_num'] !== 'null' ? $ownerData['gcash_num'] : null,
                    // Add full name for convenience
                    'full_name' => trim(($ownerData['first_name'] ?? '') . ' ' . 
                                       ($ownerData['middle_name'] ?? '') . ' ' . 
                                       ($ownerData['last_name'] ?? ''))
                )
            )
        )
    );

    // Debug: Log the final response
    error_log("DEBUG: Final JSON response: " . json_encode($response));
    
    // Debug: Check if bh_rules is in the response
    if (isset($response['data']['boarding_house']['bh_rules'])) {
        error_log("DEBUG: bh_rules IS in the response: " . $response['data']['boarding_house']['bh_rules']);
    } else {
        error_log("DEBUG: bh_rules is NOT in the response!");
    }
    
    // Debug: Check owner info in response
    if (isset($response['data']['boarding_house']['owner'])) {
        $owner = $response['data']['boarding_house']['owner'];
        error_log("DEBUG: Owner object IS in response: " . json_encode($owner));
        error_log("DEBUG: Owner info in response - Name: " . ($owner['full_name'] ?? 'null') . 
                  ", Phone: " . ($owner['phone'] ?? 'null') . 
                  ", Email: " . ($owner['email'] ?? 'null'));
        error_log("DEBUG: Owner first_name: " . ($owner['first_name'] ?? 'null'));
        error_log("DEBUG: Owner last_name: " . ($owner['last_name'] ?? 'null'));
    } else {
        error_log("DEBUG: Owner info is NOT in the response!");
    }
    
    // Also output owner data directly before encoding
    error_log("DEBUG: Owner data array before encoding: " . json_encode($ownerData));
    
    // Debug: Check room_details
    if (isset($response['data']['boarding_house']['room_details'])) {
        $roomCount = count($response['data']['boarding_house']['room_details']);
        error_log("DEBUG: room_details IS in the response with " . $roomCount . " rooms");
        if ($roomCount > 0) {
            error_log("DEBUG: First room: " . json_encode($response['data']['boarding_house']['room_details'][0]));
        }
    } else {
        error_log("DEBUG: room_details is NOT in the response!");
    }
    
    echo json_encode($response);

} catch (PDOException $e) {
    echo json_encode(array(
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ));
} catch (Exception $e) {
    echo json_encode(array(
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ));
}
?>

