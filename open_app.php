<?php
// open_app.php
// Redirects to app only if user is NOT verified

require_once 'dbConfig.php';

$idParam = isset($_GET['id']) ? $_GET['id'] : null;
$errorMsg = null;

if ($idParam) {
    // Decode ID
    $userId = base64_decode($idParam);

    // Database connection
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    if ($conn->connect_error) {
        $errorMsg = "System Error: " . $conn->connect_error;
    } else {
        $stmt = $conn->prepare("SELECT status FROM registrations WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $status = $row['status'];
            // Check if user is already verified/completed
            if (in_array($status, ['pending_admin_review', 'approved', 'active', 'verified'])) {
                $errorMsg = "Link Expired. Your profile has already been completed or verified.";
            }
        } else {
            $errorMsg = "Invalid Link. User not found.";
        }
        $stmt->close();
        $conn->close();
    }
} else {
    // No ID provided - proceed blindly (or could show error, but let's allow legacy behavior if needed)
    // Actually, for security of this feature, we should probably default to open OR error.
    // Let's assume without ID, it's a generic open request, so we allow it but it won't be specific.
}

$scheme = "boardease://update-profile";
// Fallback URL (Play Store or Web Dashboard)
$fallback = "https://boardease.calapebohol.com";

?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo $errorMsg ? "Link Expired" : "Opening BoardEase..."; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { font-family: 'Poppins', sans-serif; text-align: center; padding: 20px; background-color: #f5f5f5; }
        .btn { display: inline-block; background-color: #6d4c41; color: white; padding: 12px 24px; text-decoration: none; border-radius: 8px; font-weight: bold; margin-top: 20px; }
        .error-box { background-color: #ffebee; color: #c62828; padding: 20px; border-radius: 8px; border: 1px solid #ef9a9a; display: inline-block; margin-top: 20px; }
        .logo { width: 100px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <img src="logo_final.png" alt="BoardEase Logo" class="logo">
    
    <?php if ($errorMsg): ?>
        <div class="error-box">
            <h3>Link Expired</h3>
            <p><?php echo $errorMsg; ?></p>
            <p>You can close this window.</p>
        </div>
    <?php else: ?>
        <h3>Opening BoardEase App...</h3>
        <p>If the app doesn't open automatically, click the button below:</p>
        <a href="<?php echo $scheme; ?>" class="btn">Open App</a>
        
        <script>
            setTimeout(function() {
                window.location.href = "<?php echo $scheme; ?>";
            }, 1000);
        </script>
    <?php endif; ?>
</body>
</html>
