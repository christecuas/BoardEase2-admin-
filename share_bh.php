<?php
// share_bh.php - Handle deep link for sharing boarding houses

// Disable error display to prevent HTML output
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Database configuration
require_once 'dbConfig.php';

$bhId = isset($_GET['bh_id']) ? (int)$_GET['bh_id'] : 0;
$boardingHouse = null;
$imageUrl = '';
$title = 'Check out this Boarding House on BoardEase!';
$description = 'Find your perfect stay with BoardEase.';

// Default redirect URL for the app (using custom scheme)
$appDeepLink = "boardease://details?id=" . $bhId;
// Play Store URL (or download link) - Replace with actual link when available
$playStoreLink = "https://play.google.com/store/apps/details?id=com.example.mock"; 
// Direct APK download link if not on Play Store yet
$apkDownloadLink = "https://boardease.calapebohol.com/BoardEase.apk"; 

if ($bhId > 0) {
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if ($conn->connect_error) {
            error_log("Connection failed: " . $conn->connect_error);
        } else {
            // Get boarding house details
            $stmt = $conn->prepare("SELECT bh_name, bh_description, bh_address, price, status FROM boarding_houses LEFT JOIN (SELECT bh_id, MIN(price) as price FROM boarding_house_rooms GROUP BY bh_id) as prices ON boarding_houses.bh_id = prices.bh_id WHERE boarding_houses.bh_id = ?");
            
            if ($stmt) {
                $stmt->bind_param("i", $bhId);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    $boardingHouse = $result->fetch_assoc();
                    $title = $boardingHouse['bh_name'] . " - BoardEase";
                    $description = "Check out this boarding house at " . $boardingHouse['bh_address'] . ".";
                    if (isset($boardingHouse['price'])) {
                        $description .= " Starting at ₱" . number_format($boardingHouse['price'], 0);
                    }
                    
                    // Get first image
                    $imgStmt = $conn->prepare("SELECT image_path FROM boarding_house_images WHERE bh_id = ? ORDER BY image_id ASC LIMIT 1");
                    $imgStmt->bind_param("i", $bhId);
                    $imgStmt->execute();
                    $imgResult = $imgStmt->get_result();
                    
                    if ($imgResult->num_rows > 0) {
                        $imgRow = $imgResult->fetch_assoc();
                        $path = $imgRow['image_path'];
                        // Fix path if needed
                        $baseUrl = 'https://boardease.calapebohol.com/';
                         $cleanPath = ltrim($path, '/');
                        if (strpos($cleanPath, 'uploads/') === 0) {
                             $imageUrl = $baseUrl . $cleanPath;
                        } else {
                             $imageUrl = $baseUrl . 'uploads/' . $cleanPath;
                        }
                    } else {
                        // Default image
                        $imageUrl = "https://boardease.calapebohol.com/uploads/logo_v2.png"; // Replace with actual default image
                    }
                }
                $stmt->close();
            }
            $conn->close();
        }
    } catch (Exception $e) {
        error_log("Error in share_bh.php: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://boardease.calapebohol.com/share_bh.php?bh_id=<?php echo $bhId; ?>">
    <meta property="og:title" content="<?php echo htmlspecialchars($title); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($description); ?>">
    <meta property="og:image" content="<?php echo htmlspecialchars($imageUrl); ?>">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="https://boardease.calapebohol.com/share_bh.php?bh_id=<?php echo $bhId; ?>">
    <meta property="twitter:title" content="<?php echo htmlspecialchars($title); ?>">
    <meta property="twitter:description" content="<?php echo htmlspecialchars($description); ?>">
    <meta property="twitter:image" content="<?php echo htmlspecialchars($imageUrl); ?>">

    <title><?php echo htmlspecialchars($title); ?></title>
    
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
            color: #333;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            text-align: center;
        }
        .container {
            background-color: white;
            padding: 2rem;
            border-radius: 1rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            max-width: 90%;
            width: 400px;
        }
        .logo {
            width: 80px;
            height: 80px;
            margin-bottom: 1rem;
            border-radius: 50%; /* Optional circle style */
             object-fit: cover;
        }
        .bh-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
            background-color: #eee;
        }
        h1 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            color: #2c3e50;
        }
        p {
            color: #7f8c8d;
            margin-bottom: 2rem;
        }
        .btn {
            display: block;
            width: 100%;
            padding: 12px 0;
            margin-bottom: 1rem;
            border: none;
            border-radius: 25px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: transform 0.1s;
        }
        .btn:active {
            transform: scale(0.98);
        }
        .btn-primary {
            background-color: #A18167; /* Brown color from app */
            color: white;
        }
        .btn-secondary {
            background-color: #ecf0f1;
            color: #2c3e50;
            border: 1px solid #bdc3c7;
        }
        .download-notice {
            font-size: 0.8rem;
            color: #95a5a6;
            margin-top: 1rem;
        }
    </style>
    
    <script>
        // Attempt to open the app automatically
        window.onload = function() {
            var bhId = "<?php echo $bhId; ?>";
            if (bhId > 0) {
                 // Try to open app custom scheme
                window.location.href = "boardease://details?id=" + bhId;
                
                // Optional: Fallback logic using timeouts can be added here, 
                // but usually user interaction is better for fallbacks to avoid error loops
            }
        };
    </script>
</head>
<body>
    <div class="container">
        <!-- App Logo (Placeholder if not available) -->
         <!-- You might want to upload an app icon to uploads/ folder -->
        <img src="https://boardease.calapebohol.com/uploads/logo_v2.png" alt="BoardEase Logo" class="logo" onerror="this.src='https://via.placeholder.com/80?text=App'">
        
        <?php if ($boardingHouse && $imageUrl): ?>
            <img src="<?php echo htmlspecialchars($imageUrl); ?>" alt="Boarding House" class="bh-image">
        <?php endif; ?>

        <h1><?php echo htmlspecialchars($boardingHouse ? $boardingHouse['bh_name'] : 'BoardEase App'); ?></h1>
        
        <p><?php echo htmlspecialchars($boardingHouse ? $description : 'Download BoardEase to find the best boarding houses.'); ?></p>

        <a href="<?php echo $appDeepLink; ?>" class="btn btn-primary">Open in App</a>
        
        <!-- Link to download APK or Play Store -->
        <a href="<?php echo $apkDownloadLink; ?>" class="btn btn-secondary">Download App</a>
        
        <p class="download-notice">If you don't have the app installed, please download it first.</p>
    </div>
</body>
</html>
