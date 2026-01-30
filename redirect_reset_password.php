<?php
// redirect_reset_password.php
// Web redirect page that opens Android app via deep link

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Skip ngrok browser warning
header('ngrok-skip-browser-warning: true');

// Get token from URL
$token = $_GET['token'] ?? null;

if (!$token) {
    die("Invalid or missing reset token.");
}

// Validate token first (quick check)
// Database configuration
define('DB_HOST', '');
define('DB_USER', 'u223444398_userboardease');
define('DB_PASS', '!Boardease2026');
define('DB_NAME', 'u223444398_boardease');

$servername = DB_HOST;
$username = DB_USER;
$password = DB_PASS;
$dbname = DB_NAME;

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Database connection failed");
}

// Quick validation
$stmt = $conn->prepare("SELECT expires_at, used FROM password_resets WHERE token = ?");
if ($stmt) {
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $conn->close();
        showErrorPage("Invalid or expired reset token.");
        exit;
    }
    
    $resetData = $result->fetch_assoc();
    $stmt->close();
    
    // Check if expired
    if (strtotime($resetData['expires_at']) < time()) {
        $conn->close();
        showErrorPage("This reset link has expired. Please request a new one.");
        exit;
    }
    
    // Check if used
    if ($resetData['used'] == 1) {
        $conn->close();
        showErrorPage("This reset link has already been used. Please request a new one.");
        exit;
    }
}

$conn->close();

// Deep links for Android app - try multiple methods to trigger app chooser
$customScheme = "boardease://reset-password?token=" . urlencode($token);
$httpsScheme = "https://boardease.app/reset-password?token=" . urlencode($token);
// Intent URL format that triggers Android app chooser with "Just once" or "Always" options
$intentUrl = "intent://boardease.app/reset-password?token=" . urlencode($token) . "#Intent;scheme=https;package=com.example.mock;end";

// Detect if mobile device
$isMobile = false;
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
if (preg_match('/(android|iphone|ipad|ipod|mobile)/i', $userAgent)) {
    $isMobile = true;
}

// Show redirect page
showRedirectPage($token, $customScheme, $httpsScheme, $intentUrl, $isMobile);

function showRedirectPage($token, $customScheme, $httpsScheme, $intentUrl, $isMobile) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Opening BoardEase...</title>
        <?php if ($isMobile): ?>
        <!-- Immediate redirect to app - no delay -->
        <meta http-equiv="refresh" content="0;url=<?php echo htmlspecialchars($customScheme); ?>">
        <?php endif; ?>
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            body {
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                display: flex;
                justify-content: center;
                align-items: center;
                padding: 20px;
            }
            .container {
                background: white;
                border-radius: 10px;
                box-shadow: 0 10px 40px rgba(0,0,0,0.2);
                padding: 40px;
                max-width: 450px;
                width: 100%;
                text-align: center;
            }
            .spinner {
                border: 4px solid #f3f3f3;
                border-top: 4px solid #A18167;
                border-radius: 50%;
                width: 50px;
                height: 50px;
                animation: spin 1s linear infinite;
                margin: 0 auto 20px;
            }
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
            h1 {
                color: #333;
                margin-bottom: 10px;
            }
            p {
                color: #666;
                margin-bottom: 20px;
            }
            .button {
                display: inline-block;
                padding: 12px 30px;
                background-color: #A18167;
                color: white;
                text-decoration: none;
                border-radius: 5px;
                margin: 10px 5px;
                font-size: 14px;
            }
            .button:hover {
                background-color: #8a6f56;
            }
            .fallback {
                margin-top: 30px;
                padding-top: 20px;
                border-top: 1px solid #e0e0e0;
            }
            .fallback a {
                color: #666;
                text-decoration: underline;
                font-size: 14px;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="spinner"></div>
            <h1>Opening BoardEase...</h1>
            <p>Redirecting you to the app to reset your password.</p>
            <?php if ($isMobile): ?>
                <p style="font-size: 12px; color: #999;">Opening the BoardEase app...</p>
            <?php else: ?>
                <p style="font-size: 12px; color: #999;">Please open this link on your mobile device to reset your password in the BoardEase app.</p>
            <?php endif; ?>
            <a href="<?php echo htmlspecialchars($customScheme); ?>" class="button" id="deepLinkBtn" style="display: none;">Open BoardEase App</a>
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
                            document.getElementById('deepLinkBtn').style.display = 'inline-block';
                        }, 800);
                    }, 300);
                }, 300);
                <?php else: ?>
                // On desktop, just show the button
                document.getElementById('deepLinkBtn').style.display = 'inline-block';
                <?php endif; ?>
            })();
        </script>
    </body>
    </html>
    <?php
}

function showErrorPage($message) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Error - BoardEase</title>
        <style>
            body {
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                display: flex;
                justify-content: center;
                align-items: center;
                padding: 20px;
            }
            .error-container {
                background: white;
                border-radius: 10px;
                box-shadow: 0 10px 40px rgba(0,0,0,0.2);
                padding: 40px;
                max-width: 450px;
                text-align: center;
            }
            h1 { color: #d32f2f; margin-bottom: 20px; }
            p { color: #666; margin-bottom: 30px; }
            a {
                display: inline-block;
                padding: 12px 30px;
                background-color: #A18167;
                color: white;
                text-decoration: none;
                border-radius: 5px;
            }
        </style>
    </head>
    <body>
        <div class="error-container">
            <h1>⚠️ Error</h1>
            <p><?php echo htmlspecialchars($message); ?></p>
        </div>
    </body>
    </html>
    <?php
}
?>

