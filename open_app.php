<?php
// open_app.php
$scheme = "boardease://update-profile";
// Fallback URL (Play Store or Web Dashboard)
$fallback = "https://boardease.calapebohol.com";

// Auto-redirect via JS
?>
<!DOCTYPE html>
<html>
<head>
    <title>Opening BoardEase...</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { font-family: 'Poppins', sans-serif; text-align: center; padding: 20px; background-color: #f5f5f5; }
        .btn { display: inline-block; background-color: #6d4c41; color: white; padding: 12px 24px; text-decoration: none; border-radius: 8px; font-weight: bold; margin-top: 20px; }
        .logo { width: 100px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <img src="logo_final.png" alt="BoardEase Logo" class="logo">
    <h3>Opening BoardEase App...</h3>
    <p>If the app doesn't open automatically, click the button below:</p>
    <a href="<?php echo $scheme; ?>" class="btn">Open App</a>
    
    <script>
        setTimeout(function() {
            window.location.href = "<?php echo $scheme; ?>";
        }, 1000);
    </script>
</body>
</html>
