<?php
// verify.php - Handle verification link validation
// This endpoint validates verification links before allowing access

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Skip ngrok browser warning
header('ngrok-skip-browser-warning: true');

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

    // VALIDATION: Check if email is already verified
    // If verified, show appropriate message based on account status and DO NOT open app
    if ($user['email_verified'] == 1) {
        error_log("Email already verified for: " . $email . " - Status: " . $user['status'] . " - Blocking app redirect");
        
        // Show different message based on account status
        $status = $user['status'] ?? 'pending';
        if ($status === 'approved') {
            showErrorPage("Your account has already been verified and approved. You can now log in to your account.", true);
        } else if ($status === 'pending') {
            showErrorPage("Your email has already been verified. Your account is currently pending admin approval. Please wait for approval before logging in.");
        } else if ($status === 'rejected') {
            showErrorPage("Your account has been rejected by the admin. Please contact support for assistance.");
        } else {
            showErrorPage("This email address has already been verified. You can log in to your account now.", true);
        }
        exit; // Exit here - app will NOT open
    }

    // VALIDATION: Check if verification code exists and is not expired
    // If no code or expired, show error page and DO NOT open app
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
        error_log("No verification code found for email: " . $email . " - Blocking app redirect");
        showErrorPage("No verification code found for this email. Please request a new verification code.");
        exit; // Exit here - app will NOT open
    }

    $verification = $verifyResult->fetch_assoc();
    $verifyStmt->close();

    // VALIDATION: Check if verification code has expired
    // If expired, show error page and DO NOT open app
    if (strtotime($verification['expiry_time']) < time()) {
        error_log("Verification code expired for email: " . $email . " (Expiry: " . $verification['expiry_time'] . ") - Blocking app redirect");
        showErrorPage("This verification link has expired. Please request a new verification code. Verification codes expire after 30 minutes.");
        exit; // Exit here - app will NOT open
    }

    // All validations passed - link is valid
    // Only now will we redirect to Android app
    error_log("Verification link is valid for email: " . $email . " - Allowing app redirect");
    
    // Deep links for Android app - try multiple methods to trigger app chooser
    $customScheme = "boardease://verify?email=" . urlencode($email);
    $httpsScheme = "https://boardease.app/verify?email=" . urlencode($email);
    // Intent URL format that triggers Android app chooser with "Just once" or "Always" options
    $intentUrl = "intent://boardease.app/verify?email=" . urlencode($email) . "#Intent;scheme=https;package=com.example.mock;end";
    
    // Detect if mobile device
    $isMobile = false;
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    if (preg_match('/(android|iphone|ipad|ipod|mobile)/i', $userAgent)) {
        $isMobile = true;
    }
    
    // Show redirect page with immediate app opening
    showRedirectPage($email, $customScheme, $httpsScheme, $intentUrl, $isMobile);

} catch (Exception $e) {
    error_log("Verification link error: " . $e->getMessage());
    showErrorPage("An error occurred while processing your verification link. Please try again later.");
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}

/**
 * Show error/success page when verification link is invalid or account is already verified
 * @param string $message The message to display
 * @param bool $isSuccess If true, shows success styling (for approved accounts)
 */
function showErrorPage($message, $isSuccess = false) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo $isSuccess ? 'Account Verified - BoardEase' : 'Verification Link Invalid - BoardEase'; ?></title>
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
                background-color: <?php echo $isSuccess ? '#4CAF50' : '#ff5722'; ?>; 
                color: white; 
                padding: 20px; 
                text-align: center; 
                margin: -40px -40px 30px -40px;
                border-radius: 10px 10px 0 0;
            }
            .content { 
                padding: 20px 0; 
            }
            .icon {
                text-align: center;
                font-size: 64px;
                color: <?php echo $isSuccess ? '#4CAF50' : '#ff5722'; ?>;
                margin: 20px 0;
            }
            .message {
                background-color: <?php echo $isSuccess ? '#e8f5e9' : '#ffebee'; ?>;
                border-left: 4px solid <?php echo $isSuccess ? '#4CAF50' : '#ff5722'; ?>;
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
                <h1><?php echo $isSuccess ? 'Account Verified' : 'Verification Link Invalid'; ?></h1>
            </div>
            <div class="content">
                <div class="icon"><?php echo $isSuccess ? '✅' : '⚠️'; ?></div>
                <div class="message">
                    <strong><?php echo htmlspecialchars($message); ?></strong>
                </div>
                <?php if (!$isSuccess): ?>
                    <p>If you need assistance, please contact our support team or try requesting a new verification code from the BoardEase app.</p>
                <?php endif; ?>
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
 * Show redirect page that attempts to open Android app immediately
 */
function showRedirectPage($email, $customScheme, $httpsScheme, $intentUrl, $isMobile) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Opening BoardEase...</title>
        <?php if ($isMobile): ?>
        <!-- Immediate redirect to app - no delay -->
        <meta http-equiv="refresh" content="0;url=<?php echo htmlspecialchars($customScheme); ?>">
        <?php endif; ?>
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
                    <h2>Opening BoardEase...</h2>
                    <p>Redirecting you to the app to verify your email.</p>
                    <?php if ($isMobile): ?>
                        <p style="font-size: 12px; color: #999;">Opening the BoardEase app...</p>
                    <?php else: ?>
                        <p style="font-size: 12px; color: #999;">Please open this link on your mobile device to verify your email in the BoardEase app.</p>
                    <?php endif; ?>
                </div>
                <div class="fallback-link">
                    <a href="<?php echo htmlspecialchars($customScheme); ?>" id="appLink" style="display: none;">Open BoardEase App</a>
                </div>
            </div>
        </div>
        <script>
            // Immediately try to open app - no delays
            (function() {
                <?php if ($isMobile): ?>
                // On mobile, immediately redirect to app (no delay)
                // Try custom scheme first (fastest, opens app directly)
                window.location.href = "<?php echo $customScheme; ?>";
                
                // If still on page after 300ms, try HTTPS scheme
                setTimeout(function() {
                    window.location.href = "<?php echo $httpsScheme; ?>";
                    
                    // If still on page after another 300ms, try Intent URL
                    setTimeout(function() {
                        window.location.href = "<?php echo $intentUrl; ?>";
                        
                        // If all methods failed, show manual button after 800ms
                        setTimeout(function() {
                            document.getElementById('appLink').style.display = 'block';
                        }, 800);
                    }, 300);
                }, 300);
                <?php else: ?>
                // On desktop, just show the button
                document.getElementById('appLink').style.display = 'block';
                <?php endif; ?>
            })();
        </script>
    </body>
    </html>
    <?php
}
?>










