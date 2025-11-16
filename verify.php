<?php
// verify.php - Handle verification link validation
// This endpoint validates verification links before allowing access

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

try {
    // Database connection
    $servername = "localhost";
    $username   = "boardease";
    $password   = "boardease";
    $dbname     = "boardease2";

    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        error_log("DB Connection failed: " . $conn->connect_error);
        showErrorPage("Database connection failed. Please try again later.");
        exit;
    }

    // Get email from GET parameter
    $email = $_GET['email'] ?? null;

    if (!$email) {
        showErrorPage("Invalid verification link. Email parameter is missing.");
        exit;
    }

    // Sanitize email
    $email = trim($email);
    $email = filter_var($email, FILTER_SANITIZE_EMAIL);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        showErrorPage("Invalid email address in verification link.");
        exit;
    }

    error_log("Verification link accessed for email: " . $email);

    // Check if user exists and email verification status
    $stmt = $conn->prepare("
        SELECT id, email_verified, status 
        FROM registrations 
        WHERE email = ?
    ");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        error_log("No user found with email: " . $email);
        showErrorPage("No account found with this email address. The verification link is invalid.");
        exit;
    }

    $user = $result->fetch_assoc();
    $stmt->close();

    // Check if email is already verified
    if ($user['email_verified'] == 1) {
        error_log("Email already verified for: " . $email);
        showErrorPage("This email address has already been verified. You can log in to your account now.");
        exit;
    }

    // Check if verification code exists and is not expired
    $verifyStmt = $conn->prepare("
        SELECT ev.id, ev.verification_code, ev.expiry_time 
        FROM email_verifications ev
        JOIN registrations r ON ev.user_id = r.id
        WHERE ev.email = ?
        ORDER BY ev.created_at DESC
        LIMIT 1
    ");
    $verifyStmt->bind_param("s", $email);
    $verifyStmt->execute();
    $verifyResult = $verifyStmt->get_result();

    if ($verifyResult->num_rows === 0) {
        error_log("No verification code found for email: " . $email);
        showErrorPage("No verification code found for this email. Please request a new verification code.");
        exit;
    }

    $verification = $verifyResult->fetch_assoc();
    $verifyStmt->close();

    // Check if verification code has expired
    if (strtotime($verification['expiry_time']) < time()) {
        error_log("Verification code expired for email: " . $email . " (Expiry: " . $verification['expiry_time'] . ")");
        showErrorPage("This verification link has expired. Please request a new verification code. Verification codes expire after 30 minutes.");
        exit;
    }

    // Link is valid - redirect to Android app deep link
    error_log("Verification link is valid for email: " . $email);
    
    // Redirect to Android app using deep link
    // Try both custom scheme and HTTPS scheme
    $deepLink = "boardease://verify?email=" . urlencode($email);
    $httpsLink = "https://boardease.app/verify?email=" . urlencode($email);
    
    // Show redirect page with JavaScript fallback
    showRedirectPage($email, $deepLink, $httpsLink);

} catch (Exception $e) {
    error_log("Verification link error: " . $e->getMessage());
    showErrorPage("An error occurred while processing your verification link. Please try again later.");
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}

/**
 * Show error page when verification link is invalid
 */
function showErrorPage($message) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Verification Link Invalid - BoardEase</title>
        <style>
            body { 
                font-family: Arial, sans-serif; 
                line-height: 1.6; 
                color: #333; 
                margin: 0; 
                padding: 0; 
                background-color: #f4f4f4;
                display: flex;
                justify-content: center;
                align-items: center;
                min-height: 100vh;
            }
            .container { 
                max-width: 600px; 
                background-color: #ffffff; 
                padding: 40px;
                border-radius: 10px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            }
            .header { 
                background-color: #ff5722; 
                color: white; 
                padding: 20px; 
                text-align: center; 
                margin: -40px -40px 30px -40px;
                border-radius: 10px 10px 0 0;
            }
            .content { 
                padding: 20px 0; 
            }
            .error-icon {
                text-align: center;
                font-size: 64px;
                color: #ff5722;
                margin: 20px 0;
            }
            .message {
                background-color: #ffebee;
                border-left: 4px solid #ff5722;
                padding: 15px;
                margin: 20px 0;
                border-radius: 4px;
            }
            .footer { 
                text-align: center; 
                padding: 20px 0; 
                color: #666; 
                font-size: 12px; 
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>Verification Link Invalid</h1>
            </div>
            <div class="content">
                <div class="error-icon">⚠️</div>
                <div class="message">
                    <strong><?php echo htmlspecialchars($message); ?></strong>
                </div>
                <p>If you need assistance, please contact our support team or try requesting a new verification code from the BoardEase app.</p>
            </div>
            <div class="footer">
                <p>This is an automated message from BoardEase. Please do not reply to this email.</p>
            </div>
        </div>
    </body>
    </html>
    <?php
}

/**
 * Show redirect page that attempts to open Android app
 */
function showRedirectPage($email, $deepLink, $httpsLink) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Opening BoardEase App - Verification</title>
        <style>
            body { 
                font-family: Arial, sans-serif; 
                line-height: 1.6; 
                color: #333; 
                margin: 0; 
                padding: 0; 
                background-color: #f4f4f4;
                display: flex;
                justify-content: center;
                align-items: center;
                min-height: 100vh;
            }
            .container { 
                max-width: 600px; 
                background-color: #ffffff; 
                padding: 40px;
                border-radius: 10px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                text-align: center;
            }
            .header { 
                background-color: #2196F3; 
                color: white; 
                padding: 20px; 
                text-align: center; 
                margin: -40px -40px 30px -40px;
                border-radius: 10px 10px 0 0;
            }
            .content { 
                padding: 20px 0; 
            }
            .loading {
                font-size: 48px;
                margin: 20px 0;
            }
            .message {
                margin: 20px 0;
                font-size: 16px;
            }
            .fallback-link {
                margin-top: 30px;
                padding: 15px;
                background-color: #f8f9fa;
                border: 1px solid #2196F3;
                border-radius: 5px;
            }
            .fallback-link a {
                color: #2196F3;
                text-decoration: none;
                font-weight: bold;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>Opening BoardEase App</h1>
            </div>
            <div class="content">
                <div class="loading">⏳</div>
                <div class="message">
                    <p>Redirecting to BoardEase app...</p>
                    <p>If the app doesn't open automatically, click the link below:</p>
                </div>
                <div class="fallback-link">
                    <a href="<?php echo htmlspecialchars($deepLink); ?>" id="appLink">Open in BoardEase App</a>
                </div>
            </div>
        </div>
        <script>
            // Try to open the app immediately
            window.location.href = <?php echo json_encode($deepLink); ?>;
            
            // Fallback: if app doesn't open within 2 seconds, show manual link
            setTimeout(function() {
                document.getElementById('appLink').style.display = 'block';
            }, 2000);
        </script>
    </body>
    </html>
    <?php
}
?>

