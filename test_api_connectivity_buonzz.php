<?php
/**
 * Test script to diagnose API connectivity issues
 * Run this from command line: php test_api_connectivity_buonzz.php
 */

echo "=== Testing Buonzz API Connectivity ===\n\n";

$apiBase = 'https://ph-locations-api.buonzz.com';

// Test 1: DNS Resolution
echo "1. Testing DNS Resolution...\n";
$host = 'ph-locations-api.buonzz.com';
$ip = gethostbyname($host);
if ($ip === $host) {
    echo "   ❌ FAILED: Cannot resolve host '$host'\n";
    echo "   → Check DNS configuration or server internet connectivity\n\n";
} else {
    echo "   ✅ SUCCESS: Host resolves to IP: $ip\n\n";
}

// Test 2: Basic connectivity
echo "2. Testing basic connectivity...\n";
if (function_exists('curl_init')) {
    $ch = curl_init($apiBase . '/v1/provinces');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        echo "   ❌ FAILED: cURL error - $error\n";
        if (strpos($error, 'Could not resolve host') !== false) {
            echo "   → DNS issue: Check if server can access external DNS servers\n";
            echo "   → Try: ping 8.8.8.8 (to test internet connectivity)\n";
            echo "   → Try: nslookup ph-locations-api.buonzz.com\n";
        } elseif (strpos($error, 'Connection timed out') !== false) {
            echo "   → Connection timeout: Check firewall allows HTTPS outbound\n";
        }
    } elseif ($httpCode === 200) {
        echo "   ✅ SUCCESS: API is accessible (HTTP $httpCode)\n";
        $data = json_decode($response, true);
        if ($data) {
            $count = isset($data['data']) ? count($data['data']) : (is_array($data) ? count($data) : 0);
            echo "   → Response contains $count items\n";
            echo "   → Sample response: " . substr($response, 0, 200) . "...\n";
        }
    } else {
        echo "   ❌ FAILED: HTTP error code $httpCode\n";
        echo "   → Response: " . substr($response, 0, 200) . "\n";
    }
} else {
    echo "   ❌ cURL extension not available\n";
}
echo "\n";

// Test 3: Test file_get_contents as fallback
echo "3. Testing file_get_contents fallback...\n";
$context = stream_context_create([
    'http' => [
        'timeout' => 10,
        'method' => 'GET',
        'header' => 'User-Agent: BoardEaseApp/1.0'
    ],
    'ssl' => [
        'verify_peer' => false,
        'verify_peer_name' => false
    ]
]);

$response = @file_get_contents($apiBase . '/v1/provinces', false, $context);
if ($response === false) {
    $error = error_get_last();
    echo "   ❌ FAILED: file_get_contents error\n";
    echo "   → " . ($error ? $error['message'] : 'Unknown error') . "\n";
} else {
    echo "   ✅ SUCCESS: file_get_contents works\n";
    $data = json_decode($response, true);
    if ($data) {
        $count = isset($data['data']) ? count($data['data']) : (is_array($data) ? count($data) : 0);
        echo "   → Response contains $count items\n";
    }
}
echo "\n";

// Test 4: Check server network settings
echo "4. Network Configuration Check...\n";
if (function_exists('shell_exec')) {
    $dnsServers = @shell_exec('cat /etc/resolv.conf 2>/dev/null | grep nameserver');
    if ($dnsServers) {
        echo "   DNS Servers configured:\n";
        echo "   $dnsServers\n";
    }
    
    $pingTest = @shell_exec('ping -c 1 8.8.8.8 2>&1');
    if ($pingTest && strpos($pingTest, '1 received') !== false) {
        echo "   ✅ Internet connectivity: OK\n";
    } else {
        echo "   ⚠️  Cannot verify internet connectivity\n";
    }
} else {
    echo "   ⚠️  shell_exec not available for network checks\n";
}
echo "\n";

// Recommendations
echo "=== Recommendations ===\n";
echo "\n";
echo "📖 For detailed instructions, see: DNS_FIX_GUIDE.md\n";
echo "\n";
echo "Quick fixes if DNS resolution fails:\n";
echo "\n";
echo "1. Check server has internet access:\n";
echo "   → ping 8.8.8.8\n";
echo "\n";
echo "2. Check current DNS configuration:\n";
echo "   → cat /etc/resolv.conf\n";
echo "   → Should show external DNS servers like 8.8.8.8 (not just 127.0.0.1)\n";
echo "\n";
echo "3. Fix DNS on Linux/Unix:\n";
echo "   → sudo nano /etc/resolv.conf\n";
echo "   → Add: nameserver 8.8.8.8\n";
echo "   → Add: nameserver 8.8.4.4\n";
echo "   → Add: nameserver 1.1.1.1\n";
echo "\n";
echo "4. Fix DNS on cPanel/WHM:\n";
echo "   → Login to WHM\n";
echo "   → Server Configuration → Resolver Configuration\n";
echo "   → Set Primary DNS: 8.8.8.8\n";
echo "   → Set Secondary DNS: 8.8.4.4\n";
echo "\n";
echo "5. Test DNS resolution:\n";
echo "   → nslookup ph-locations-api.buonzz.com\n";
echo "   → Should return an IP address\n";
echo "\n";
echo "6. Check firewall:\n";
echo "   → Ensure DNS (UDP 53) and HTTPS (TCP 443) are allowed\n";
echo "\n";
echo "7. For detailed step-by-step guide:\n";
echo "   → See DNS_FIX_GUIDE.md in this directory\n";
echo "\n";

?>

