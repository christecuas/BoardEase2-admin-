<?php
// verify_deep_link.php - Handle deep link verification and redirect

// Disable error display to prevent HTML output
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Log the request for debugging
error_log("Deep link verification request received at " . date('Y-m-d H:i:s'));
error_log("GET data: " . print_r($_GET, true));

// Set content type to HTML
header('Content-Type: text/html; charset=UTF-8');

try {
    // Database connection
    // Database configuration
define('DB_HOST', '');
define('DB_USER', 'u223444398_userboardease');
define('DB_PASS', '!Boardease2026');
define('DB_NAME', 'u223444398_boardease');

$servername = DB_HOST;
$username = DB_USER;
$password = DB_PASS;
$dbname = DB_NAME;

    error_log("Attempting database connection...");
    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        error_log("DB Connection failed: " . $conn->connect_error);
        showErrorPage("Database connection failed: " . $conn->connect_error);
        exit;
    }
    
    error_log("Database connection successful");

    $email = $_GET['email'] ?? null;
    error_log("Email parameter: " . ($email ?: "NULL"));

    if (!$email) {
        error_log("No email parameter provided");
        showErrorPage("Email parameter is required.");
        exit;
    }

    // Sanitize email
    $email = trim($email);
    $email = filter_var($email, FILTER_SANITIZE_EMAIL);
    error_log("Sanitized email: " . $email);

    // Check if user exists and get verification status
    error_log("Preparing database query...");
    $stmt = $conn->prepare("
        SELECT r.id, r.first_name, r.status, r.email_verified, 
               ev.verification_code, ev.expiry_time, ev.created_at
        FROM registrations r 
        LEFT JOIN email_verifications ev ON r.email = ev.email 
        WHERE r.email = ?
    ");
    
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        showErrorPage("Database query preparation failed.");
        exit;
    }
    
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    error_log("Query executed, rows found: " . $result->num_rows);

    if ($result->num_rows === 0) {
        error_log("No account found for email: " . $email);
        showErrorPage("No account found with this email address.");
        exit;
    }

    $user = $result->fetch_assoc();
    error_log("User found - ID: " . $user['id'] . ", Status: " . $user['status'] . ", Email Verified: " . $user['email_verified']);

    // Check if email is already verified
    if ($user['email_verified'] == 1) {
        showAlreadyVerifiedPage($user['first_name']);
        exit;
    }

    // Check if verification code exists and is not expired
    if (!$user['verification_code'] || !$user['expiry_time']) {
        showExpiredPage("No verification code found for this email.");
        exit;
    }

    // Check if verification code is expired
    if (strtotime($user['expiry_time']) < time()) {
        showExpiredPage("Verification code has expired. Please register again.");
        exit;
    }

    // Check if user status is rejected
    if ($user['status'] === 'rejected') {
        showRejectedPage("Your account was rejected. Please contact support or register with a different email.");
        exit;
    }

    // If we reach here, verification is still valid
    // Redirect to Android app with deep link
    $deepLink = "boardease://verify?email=" . urlencode($email);
    
    // Also provide fallback for web browsers
    showRedirectPage($deepLink, $email, $user['first_name']);

} catch (Exception $e) {
    error_log("Deep link verification error: " . $e->getMessage());
    showErrorPage("Server error occurred.");
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}

function showErrorPage($message) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Verification Link Error - BoardEase</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 20px; background-color: #f4f4f4; }
            .container { max-width: 600px; margin: 0 auto; background-color: #ffffff; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            .header { text-align: center; margin-bottom: 30px; }
            .error-icon { font-size: 48px; color: #dc3545; margin-bottom: 20px; }
            .error-message { background-color: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; border: 1px solid #f5c6cb; margin: 20px 0; }
            .footer { text-align: center; margin-top: 30px; color: #666; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <div class="error-icon">❌</div>
                <h1>Verification Link Error</h1>
            </div>
            <div class="error-message">
                <strong>Error:</strong> <?php echo htmlspecialchars($message); ?>
            </div>
            <p>Please try registering again or contact support if the problem persists.</p>
            <div class="footer">
                <p>This is an automated message from BoardEase.</p>
            </div>
        </div>
    </body>
    </html>
    <?php
}

function showAlreadyVerifiedPage($firstName) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Email Already Verified - BoardEase</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 20px; background-color: #f4f4f4; }
            .container { max-width: 600px; margin: 0 auto; background-color: #ffffff; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            .header { text-align: center; margin-bottom: 30px; }
            .success-icon { font-size: 48px; color: #28a745; margin-bottom: 20px; }
            .success-message { background-color: #d4edda; color: #155724; padding: 15px; border-radius: 5px; border: 1px solid #c3e6cb; margin: 20px 0; }
            .footer { text-align: center; margin-top: 30px; color: #666; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <div class="success-icon">✅</div>
                <h1>Email Already Verified</h1>
            </div>
            <div class="success-message">
                <strong>Hello <?php echo htmlspecialchars($firstName); ?>!</strong><br>
                Your email has already been verified. You can now log in to the BoardEase app.
            </div>
            <p>Please open the BoardEase app and log in with your credentials.</p>
            <div class="footer">
                <p>This is an automated message from BoardEase.</p>
            </div>
        </div>
    </body>
    </html>
    <?php
}

function showExpiredPage($message) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Verification Expired - BoardEase</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 20px; background-color: #f4f4f4; }
            .container { max-width: 600px; margin: 0 auto; background-color: #ffffff; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            .header { text-align: center; margin-bottom: 30px; }
            .expired-icon { font-size: 48px; color: #ffc107; margin-bottom: 20px; }
            .expired-message { background-color: #fff3cd; color: #856404; padding: 15px; border-radius: 5px; border: 1px solid #ffeaa7; margin: 20px 0; }
            .footer { text-align: center; margin-top: 30px; color: #666; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <div class="expired-icon">⏰</div>
                <h1>Verification Expired</h1>
            </div>
            <div class="expired-message">
                <strong>Notice:</strong> <?php echo htmlspecialchars($message); ?>
            </div>
            <p>Please register again to get a new verification code.</p>
            <div class="footer">
                <p>This is an automated message from BoardEase.</p>
            </div>
        </div>
    </body>
    </html>
    <?php
}

function showRejectedPage($message) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Account Rejected - BoardEase</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 20px; background-color: #f4f4f4; }
            .container { max-width: 600px; margin: 0 auto; background-color: #ffffff; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            .header { text-align: center; margin-bottom: 30px; }
            .rejected-icon { font-size: 48px; color: #dc3545; margin-bottom: 20px; }
            .rejected-message { background-color: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; border: 1px solid #f5c6cb; margin: 20px 0; }
            .footer { text-align: center; margin-top: 30px; color: #666; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <div class="rejected-icon">🚫</div>
                <h1>Account Rejected</h1>
            </div>
            <div class="rejected-message">
                <strong>Notice:</strong> <?php echo htmlspecialchars($message); ?>
            </div>
            <p>Please contact support for more information.</p>
            <div class="footer">
                <p>This is an automated message from BoardEase.</p>
            </div>
        </div>
    </body>
    </html>
    <?php
}

function showRedirectPage($deepLink, $email, $firstName) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Opening BoardEase App - BoardEase</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 20px; background-color: #f4f4f4; }
            .container { max-width: 600px; margin: 0 auto; background-color: #ffffff; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            .header { text-align: center; margin-bottom: 30px; }
            .loading-icon { font-size: 48px; color: #007bff; margin-bottom: 20px; animation: spin 2s linear infinite; }
            @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
            .success-message { background-color: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 5px; border: 1px solid #bee5eb; margin: 20px 0; }
            .manual-link { margin: 20px 0; }
            .manual-link a { display: inline-block; background-color: #007bff; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold; }
            .footer { text-align: center; margin-top: 30px; color: #666; font-size: 12px; }
        </style>
        <script>
            // Try to open the app immediately
            window.location.href = "<?php echo $deepLink; ?>";
            
            // Fallback: if app doesn't open, show manual link after 3 seconds
            setTimeout(function() {
                document.getElementById('manual-link').style.display = 'block';
            }, 3000);
        </script>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <div class="loading-icon">📱</div>
                <h1>Opening BoardEase App</h1>
            </div>
            <div class="success-message">
                <strong>Hello <?php echo htmlspecialchars($firstName); ?>!</strong><br>
                We're opening the BoardEase app for you to complete email verification.
            </div>
            <p>If the app doesn't open automatically, click the button below:</p>
            <div class="manual-link" id="manual-link" style="display: none;">
                <a href="<?php echo $deepLink; ?>">Open BoardEase App</a>
            </div>
            <div class="footer">
                <p>This is an automated message from BoardEase.</p>
            </div>
        </div>
    </body>
    </html>
    <?php
}
?>
