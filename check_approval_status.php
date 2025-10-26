<?php
// check_approval_status.php - Check if user account has been approved by admin

// Disable error display to prevent HTML output
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Set content type to JSON
header('Content-Type: application/json');

// Log the request for debugging
error_log("Approval status check request received at " . date('Y-m-d H:i:s'));
error_log("POST data: " . print_r($_POST, true));

try {
    // Database connection
    $servername = "localhost";
    $username   = "boardease";
    $password   = "boardease";
    $dbname     = "boardease2";

    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        die("DB Connection failed: " . $conn->connect_error);
    }

    $email = $_POST['email'] ?? null;

    if (!$email) {
        $response = array(
            "approved" => false,
            "message" => "Email address is required."
        );
        echo json_encode($response);
        exit;
    }

    // Check user status
    $stmt = $conn->prepare("SELECT id, status, first_name, last_name FROM registrations WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $response = array(
            "approved" => false,
            "message" => "Account not found."
        );
        echo json_encode($response);
        exit;
    }

    $user = $result->fetch_assoc();

    if ($user['status'] === 'approved') {
        $response = array(
            "approved" => true,
            "message" => "Your account has been approved! You can now login.",
            "user_name" => $user['first_name'] . " " . $user['last_name']
        );
    } else if ($user['status'] === 'pending') {
        $response = array(
            "approved" => false,
            "message" => "Your account is still pending admin approval. Please wait for the admin to review your application.",
            "status" => "pending"
        );
    } else if ($user['status'] === 'rejected') {
        $response = array(
            "approved" => false,
            "message" => "Your account has been rejected. Please contact support for more information.",
            "status" => "rejected"
        );
    } else if ($user['status'] === 'unverified') {
        $response = array(
            "approved" => false,
            "message" => "Your email is not verified yet. Please complete email verification first.",
            "status" => "unverified"
        );
    } else {
        $response = array(
            "approved" => false,
            "message" => "Unknown account status. Please contact support.",
            "status" => "unknown"
        );
    }

    echo json_encode($response);

} catch (Exception $e) {
    error_log("Approval status check error: " . $e->getMessage());
    $response = array(
        "approved" => false,
        "message" => "Server error: " . $e->getMessage()
    );
    echo json_encode($response);
}

$conn->close();
?>
