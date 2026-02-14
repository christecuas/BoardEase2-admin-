<?php
require_once 'dbConfig.php';

header('Content-Type: application/json');

// Get POST data
$idToken = $_POST['idToken'] ?? '';
$email = $_POST['email'] ?? '';

if (empty($idToken) || empty($email)) {
    echo json_encode(['success' => false, 'message' => 'Missing token or email']);
    exit;
}

// Verify ID token with Google
// Note: In production, it's safer to use the Google PHP library, 
// but this API call is a standard way to verify tokens.
$verifyUrl = "https://oauth2.googleapis.com/tokeninfo?id_token=" . $idToken;

// Using curl for better error handling and compatibility
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $verifyUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$response = curl_exec($ch);
$curlError = curl_error($ch);
curl_close($ch);

if ($response === false) {
    echo json_encode(['success' => false, 'message' => 'Failed to verify token with Google: ' . $curlError]);
    exit;
}

$tokenData = json_decode($response, true);
if (isset($tokenData['error'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid Google token: ' . ($tokenData['error_description'] ?? $tokenData['error'])]);
    exit;
}

// Verify email match
if ($tokenData['email'] !== $email) {
    echo json_encode(['success' => false, 'message' => 'Email mismatch']);
    exit;
}

// Token is valid, now check if user exists in BoardEase database
// Join with users table to get the consistent user_id used in the app
$stmt = $conn->prepare("SELECT r.*, u.user_id 
                        FROM registrations r 
                        LEFT JOIN users u ON r.id = u.reg_id 
                        WHERE r.email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    
    // Check status
    $allowedStatuses = ['approved', 'profile_incomplete', 'pending_admin_review', 'pending'];
    if (in_array($user['status'], $allowedStatuses)) {
        // Success - Login
        $fullName = trim($user['first_name'] . ' ' . $user['middle_name'] . ' ' . $user['last_name']);
        if (!empty($user['suffix']) && $user['suffix'] !== 'None') {
            $fullName .= ' ' . $user['suffix'];
        }
        
        $response = [
            "success" => true,
            "message" => "Login successful",
            "user" => [
                "id" => $user['user_id'] ? $user['user_id'] : $user['id'],
                "role" => $user['role'],
                "firstName" => $user['first_name'],
                "middleName" => $user['middle_name'],
                "lastName" => $user['last_name'],
                "suffix" => $user['suffix'],
                "fullName" => $fullName,
                "email" => $user['email'],
                "phone" => $user['phone'],
                "birthDate" => $user['birth_date'],
                "address" => $user['address'],
                "gcashNumber" => $user['gcash_num'] ?? "",
                "status" => $user['status'] === 'pending' ? 'pending_admin_review' : $user['status']
            ]
        ];
    } else if ($user['status'] === 'email_unverified' || $user['status'] === 'unverified') {
        $response = [
            "success" => false,
            "message" => "Please verify your email address before logging in.",
            "requires_verification" => true
        ];
    } else if ($user['status'] === 'pending') {
        $response = [
            "success" => false,
            "message" => "Your account is still pending admin approval."
        ];
    } else if ($user['status'] === 'rejected') {
        $response = [
            "success" => false,
            "message" => "Your registration was rejected. Please contact support."
        ];
    } else {
        $response = [
            "success" => false,
            "message" => "Your account status is: " . $user['status']
        ];
    }
} else {
    // User does not exist, need to register
    $response = [
        "success" => false,
        "message" => "Account not found. Please sign up.",
        "needs_registration" => true
    ];
}

echo json_encode($response);
$stmt->close();
$conn->close();
?>
