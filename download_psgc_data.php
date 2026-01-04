<?php
/**
 * Download PSGC data files from GitHub
 * This script downloads the JSON data files from the pcofilada/psgc repository
 */

$dataDir = __DIR__ . '/psgc_data';
$baseUrl = 'https://raw.githubusercontent.com/pcofilada/psgc/master/src/data/';

// Create data directory if it doesn't exist
if (!is_dir($dataDir)) {
    mkdir($dataDir, 0755, true);
    echo "Created directory: $dataDir\n";
}

$files = [
    'regions.json',
    'provinces.json',
    'municipalities.json',
    'barangays.json'
];

echo "Downloading PSGC data files...\n\n";

foreach ($files as $file) {
    $url = $baseUrl . $file;
    $filePath = $dataDir . '/' . $file;
    
    echo "Downloading $file... ";
    
    // Use cURL to download
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        $fp = fopen($filePath, 'w');
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        fclose($fp);
        
        if ($httpCode === 200) {
            $size = filesize($filePath);
            echo "✓ Success ({$size} bytes)\n";
        } else {
            echo "✗ Failed (HTTP $httpCode)\n";
        }
    } else {
        // Fallback to file_get_contents
        $content = @file_get_contents($url);
        if ($content !== false) {
            file_put_contents($filePath, $content);
            $size = strlen($content);
            echo "✓ Success ({$size} bytes)\n";
        } else {
            echo "✗ Failed\n";
        }
    }
}

echo "\nDone! Data files are now in: $dataDir\n";
echo "You can now use the philippine_address_api.php with local data.\n";
?>

