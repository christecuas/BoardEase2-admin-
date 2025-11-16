<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'php_errors.log');

try {
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'regions':
            getRegions();
            break;
        case 'provinces':
            getProvinces();
            break;
        case 'municipalities':
            getMunicipalities();
            break;
        case 'barangays':
            getBarangays();
            break;
        default:
            throw new Exception('Invalid action. Use: regions, provinces, municipalities, or barangays');
    }
    
} catch (Exception $e) {
    error_log("Error in philippine_address_api.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

function fetchFromAPI($url) {
    // Use cURL for more reliable API calls
    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'BoardEaseApp/1.0');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
            'Content-Type: application/json'
        ]);
        
        // Add verbose logging for debugging
        curl_setopt($ch, CURLOPT_VERBOSE, false);
        $verbose = fopen('php://temp', 'w+');
        curl_setopt($ch, CURLOPT_STDERR, $verbose);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        $curlInfo = curl_getinfo($ch);
        
        // Check DNS resolution first
        if (strpos($error, 'Could not resolve host') !== false) {
            $host = parse_url($url, PHP_URL_HOST);
            error_log("================================================");
            error_log("DNS RESOLUTION FAILED for: " . $host);
            error_log("================================================");
            error_log("");
            error_log("DIAGNOSIS:");
            
            // Try to get the hostname from URL
            if ($host) {
                $dnsCheck = gethostbyname($host);
                if ($dnsCheck === $host) {
                    error_log("❌ DNS resolution failed: $host could not be resolved");
                    error_log("");
                    error_log("ROOT CAUSE: Server cannot resolve domain name to IP address");
                    error_log("");
                    error_log("REQUIRED ACTIONS:");
                    error_log("1. Check server internet connectivity: ping 8.8.8.8");
                    error_log("2. Check DNS configuration: cat /etc/resolv.conf");
                    error_log("3. Verify DNS servers are external (like 8.8.8.8), not just localhost");
                    error_log("4. Test DNS resolution: nslookup $host");
                    error_log("5. Ensure firewall allows DNS queries (UDP port 53)");
                    error_log("");
                    error_log("QUICK FIX - Linux/Unix:");
                    error_log("  sudo nano /etc/resolv.conf");
                    error_log("  Add: nameserver 8.8.8.8");
                    error_log("  Add: nameserver 8.8.4.4");
                    error_log("");
                    error_log("QUICK FIX - cPanel/WHM:");
                    error_log("  WHM → Server Configuration → Resolver Configuration");
                    error_log("  Set Primary DNS: 8.8.8.8");
                    error_log("  Set Secondary DNS: 8.8.4.4");
                    error_log("");
                    error_log("DETAILED GUIDE:");
                    error_log("  See: BoardEase2/DNS_FIX_GUIDE.md for complete instructions");
                    error_log("");
                    error_log("TEST SCRIPT:");
                    error_log("  Run: php BoardEase2/test_api_connectivity_buonzz.php");
                    error_log("================================================");
                } else {
                    error_log("DNS resolved to IP: $dnsCheck");
                    error_log("But cURL still failed with: $error");
                    error_log("This might be a firewall or SSL issue, not DNS");
                }
            }
        }
        
        fclose($verbose);
        curl_close($ch);
        
        if ($error) {
            error_log("cURL error for URL $url: " . $error);
            error_log("cURL info: " . json_encode($curlInfo));
            return false;
        }
        
        if ($httpCode !== 200) {
            error_log("HTTP error for URL $url: Code $httpCode");
            error_log("Response: " . substr($response, 0, 500));
            return false;
        }
        
        error_log("API call successful: $url (HTTP $httpCode, " . strlen($response) . " bytes)");
        return $response;
    } else {
        // Fallback to file_get_contents if cURL is not available
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => [
                    'User-Agent: BoardEaseApp/1.0',
                    'Accept: application/json',
                    'Content-Type: application/json'
                ],
                'timeout' => 30,
                'ignore_errors' => true
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false
            ]
        ]);
        
        $response = @file_get_contents($url, false, $context);
        if ($response === false) {
            $error = error_get_last();
            error_log("file_get_contents failed for URL $url: " . ($error ? $error['message'] : 'Unknown error'));
        }
        return $response;
    }
}

function getRegions() {
    // Use PSGC Cloud API (Official Philippine Government API) as primary
    $apiUrl = 'https://psgc.cloud/api/regions';
    $response = fetchFromAPI($apiUrl);
    
    if ($response === false) {
        error_log("Both APIs failed for regions");
        $regions = [];
    } else {
        $data = json_decode($response, true);
        
        // Handle different possible response formats
        if (isset($data['data']) && is_array($data['data'])) {
            // Response has 'data' key
            $rawData = $data['data'];
        } elseif (is_array($data)) {
            // Response is directly an array
            $rawData = $data;
        } else {
            $rawData = [];
        }
        
        $regions = [];
        if (!empty($rawData)) {
            foreach ($rawData as $region) {
                $regions[] = [
                    'code' => $region['id'] ?? $region['code'] ?? '',
                    'name' => $region['name'] ?? $region['region_name'] ?? ''
                ];
            }
        }
    }
    
    // Sort regions alphabetically
    usort($regions, function($a, $b) {
        return strcmp($a['name'], $b['name']);
    });
    
    error_log("Returning " . count($regions) . " regions");
    
    echo json_encode([
        'success' => true,
        'data' => $regions,
        'debug' => [
                'api_used' => 'PSGC Cloud API (Official Philippine Government)',
            'count' => count($regions)
        ]
    ]);
}

function getProvinces() {
    // Use PSGC Cloud API (Official Philippine Government API) as primary
    $apiUrl = 'https://psgc.cloud/api/provinces';
    $response = fetchFromAPI($apiUrl);
    
    if ($response === false) {
        error_log("ERROR: All APIs failed - cannot fetch provinces");
        error_log("Both Buonzz API and PSGC Cloud API failed");
        error_log("Please check server connectivity and DNS configuration");
        
        // Return error instead of fallback
        http_response_code(503); // Service Unavailable
        echo json_encode([
            'success' => false,
            'error' => 'API service unavailable. Please check server connectivity.',
            'message' => 'Unable to fetch provinces from any API. Please ensure the server has internet access and DNS is properly configured.',
            'debug' => [
                'buonzz_api' => 'https://ph-locations-api.buonzz.com/v1/provinces',
                'psgc_api' => 'https://psgc.cloud/api/provinces',
                'suggestion' => 'Check DNS resolution and ensure firewall allows HTTPS connections'
            ]
        ]);
        exit;
    } else {
        $data = json_decode($response, true);
        
        // Handle different possible response formats
        if (isset($data['data']) && is_array($data['data'])) {
            // Response has 'data' key
            $rawData = $data['data'];
        } elseif (is_array($data)) {
            // Response is directly an array
            $rawData = $data;
        } else {
            error_log("Invalid Buonzz API response format, using fallback data");
            $provinces = getAllPhilippineProvinces();
        }
        
        if (isset($rawData)) {
            $provinces = [];
            foreach ($rawData as $province) {
                $provinces[] = [
                    'code' => $province['id'] ?? $province['code'] ?? $province['psgcCode'] ?? '',
                    'name' => $province['name'] ?? $province['provinceName'] ?? ''
                ];
            }
        }
        
        if (empty($provinces)) {
            error_log("No provinces found in API response, using fallback data");
            $provinces = getAllPhilippineProvinces();
        }
    }
    
    // Sort provinces alphabetically
    usort($provinces, function($a, $b) {
        return strcmp($a['name'], $b['name']);
    });
    
    error_log("Returning " . count($provinces) . " provinces");
    
    echo json_encode([
        'success' => true,
        'data' => $provinces,
        'debug' => [
                'api_used' => 'PSGC Cloud API (Official Philippine Government)',
            'fallback_used' => $response === false,
            'count' => count($provinces)
        ]
    ]);
}

function getProvinceCodeByName($provinceName) {
    // Map province names to their codes
    $provinceMap = [
        'Abra' => '1400100000',
        'Agusan del Norte' => '1600200000',
        'Agusan del Sur' => '1600300000',
        'Aklan' => '0600400000',
        'Albay' => '0500500000',
        'Antique' => '0600600000',
        'Apayao' => '1408100000',
        'Aurora' => '0307700000',
        'Basilan' => '1900700000',
        'Bataan' => '0300800000',
        'Batanes' => '0200900000',
        'Batangas' => '0401000000',
        'Benguet' => '1401100000',
        'Biliran' => '0807800000',
        'Bohol' => '0701200000',
        'Bukidnon' => '1001300000',
        'Bulacan' => '0301400000',
        'Cagayan' => '0201500000',
        'Camarines Norte' => '0501600000',
        'Camarines Sur' => '0501700000',
        'Camiguin' => '1001800000',
        'Capiz' => '0601900000',
        'Catanduanes' => '0502000000',
        'Cavite' => '0402100000',
        'Cebu' => '0702200000',
        'Cotabato' => '1204700000',
        'Davao Occidental' => '1108600000',
        'Davao Oriental' => '1102500000',
        'Davao de Oro' => '1108200000',
        'Davao del Norte' => '1102300000',
        'Davao del Sur' => '1102400000',
        'Dinagat Islands' => '1608500000',
        'Eastern Samar' => '0802600000',
        'Guimaras' => '0607900000',
        'Ifugao' => '1402700000',
        'Ilocos Norte' => '0102800000',
        'Ilocos Sur' => '0102900000',
        'Iloilo' => '0603000000',
        'Isabela' => '0203100000',
        'Kalinga' => '1403200000',
        'La Union' => '0103300000',
        'Laguna' => '0403400000',
        'Lanao del Norte' => '1003500000',
        'Lanao del Sur' => '1903600000',
        'Leyte' => '0803700000',
        'Maguindanao del Norte' => '1908700000',
        'Maguindanao del Sur' => '1908800000',
        'Marinduque' => '1704000000',
        'Masbate' => '0504100000',
        'Misamis Occidental' => '1004200000',
        'Misamis Oriental' => '1004300000',
        'Mountain Province' => '1404400000',
        'Negros Occidental' => '0604500000',
        'Negros Oriental' => '0704600000',
        'Northern Samar' => '0804800000',
        'Nueva Ecija' => '0304900000',
        'Nueva Vizcaya' => '0205000000',
        'Occidental Mindoro' => '1705100000',
        'Oriental Mindoro' => '1705200000',
        'Palawan' => '1705300000',
        'Pampanga' => '0305400000',
        'Pangasinan' => '0105500000',
        'Quezon' => '0405600000',
        'Quirino' => '0205700000',
        'Rizal' => '0405800000',
        'Romblon' => '1705900000',
        'Samar' => '0806000000',
        'Sarangani' => '1208000000',
        'Siquijor' => '0706100000',
        'Sorsogon' => '0506200000',
        'South Cotabato' => '1206300000',
        'Southern Leyte' => '0806400000',
        'Sultan Kudarat' => '1206500000',
        'Sulu' => '1906600000',
        'Surigao del Norte' => '1606700000',
        'Surigao del Sur' => '1606800000',
        'Tarlac' => '0306900000',
        'Tawi-Tawi' => '1907000000',
        'Zambales' => '0307100000',
        'Zamboanga Sibugay' => '0908300000',
        'Zamboanga del Norte' => '0907200000',
        'Zamboanga del Sur' => '0907300000'
    ];
    
    return $provinceMap[$provinceName] ?? '';
}

function getMunicipalityCodeByName($municipalityName) {
    // This is a simplified approach - in a real implementation, you'd want to cache this data
    // For now, we'll use a basic mapping for common municipalities
    $municipalityMap = [
        'Calape' => '0701200000',
        'Tagbilaran City' => '0701200000',
        'Cebu City' => '0702200000',
        'Manila City' => '8206000000',
        'Quezon City' => '8206000000',
        'Makati City' => '8206000000',
        'Taguig City' => '8206000000',
        'Pasig City' => '8206000000',
        'Marikina City' => '8206000000',
        'Muntinlupa City' => '8206000000',
        'Las Piñas City' => '8206000000',
        'Parañaque City' => '8206000000',
        'Pasay City' => '8206000000',
        'Valenzuela City' => '8206000000',
        'Caloocan City' => '8206000000',
        'Malabon City' => '8206000000',
        'Navotas City' => '8206000000',
        'Mandaluyong City' => '8206000000',
        'San Juan City' => '8206000000',
        'Pateros' => '8206000000'
    ];
    
    return $municipalityMap[$municipalityName] ?? '';
}

function getAllPhilippineProvinces() {
    // Complete list of all 82 Philippine provinces - Alphabetically sorted
    return [
        ['code' => '01', 'name' => 'Abra'],
        ['code' => '02', 'name' => 'Agusan del Norte'],
        ['code' => '03', 'name' => 'Agusan del Sur'],
        ['code' => '04', 'name' => 'Aklan'],
        ['code' => '05', 'name' => 'Albay'],
        ['code' => '06', 'name' => 'Antique'],
        ['code' => '07', 'name' => 'Apayao'],
        ['code' => '08', 'name' => 'Aurora'],
        ['code' => '09', 'name' => 'Basilan'],
        ['code' => '10', 'name' => 'Bataan'],
        ['code' => '11', 'name' => 'Batanes'],
        ['code' => '12', 'name' => 'Batangas'],
        ['code' => '13', 'name' => 'Benguet'],
        ['code' => '14', 'name' => 'Biliran'],
        ['code' => '15', 'name' => 'Bohol'],
        ['code' => '16', 'name' => 'Bukidnon'],
        ['code' => '17', 'name' => 'Bulacan'],
        ['code' => '18', 'name' => 'Cagayan'],
        ['code' => '19', 'name' => 'Camarines Norte'],
        ['code' => '20', 'name' => 'Camarines Sur'],
        ['code' => '21', 'name' => 'Camiguin'],
        ['code' => '22', 'name' => 'Capiz'],
        ['code' => '23', 'name' => 'Catanduanes'],
        ['code' => '24', 'name' => 'Cavite'],
        ['code' => '25', 'name' => 'Cebu'],
        ['code' => '26', 'name' => 'Cotabato'],
        ['code' => '27', 'name' => 'Davao del Norte'],
        ['code' => '28', 'name' => 'Davao del Sur'],
        ['code' => '29', 'name' => 'Davao Oriental'],
        ['code' => '30', 'name' => 'Davao de Oro'],
        ['code' => '31', 'name' => 'Davao Occidental'],
        ['code' => '32', 'name' => 'Dinagat Islands'],
        ['code' => '33', 'name' => 'Eastern Samar'],
        ['code' => '34', 'name' => 'Guimaras'],
        ['code' => '35', 'name' => 'Ifugao'],
        ['code' => '36', 'name' => 'Ilocos Norte'],
        ['code' => '37', 'name' => 'Ilocos Sur'],
        ['code' => '38', 'name' => 'Iloilo'],
        ['code' => '39', 'name' => 'Isabela'],
        ['code' => '40', 'name' => 'Kalinga'],
        ['code' => '41', 'name' => 'Laguna'],
        ['code' => '42', 'name' => 'Lanao del Norte'],
        ['code' => '43', 'name' => 'Lanao del Sur'],
        ['code' => '44', 'name' => 'La Union'],
        ['code' => '45', 'name' => 'Leyte'],
        ['code' => '46', 'name' => 'Maguindanao'],
        ['code' => '47', 'name' => 'Marinduque'],
        ['code' => '48', 'name' => 'Masbate'],
        ['code' => '49', 'name' => 'Misamis Occidental'],
        ['code' => '50', 'name' => 'Misamis Oriental'],
        ['code' => '51', 'name' => 'Mountain Province'],
        ['code' => '52', 'name' => 'Negros Occidental'],
        ['code' => '53', 'name' => 'Negros Oriental'],
        ['code' => '54', 'name' => 'Northern Samar'],
        ['code' => '55', 'name' => 'Nueva Ecija'],
        ['code' => '56', 'name' => 'Nueva Vizcaya'],
        ['code' => '57', 'name' => 'Occidental Mindoro'],
        ['code' => '58', 'name' => 'Oriental Mindoro'],
        ['code' => '59', 'name' => 'Palawan'],
        ['code' => '60', 'name' => 'Pampanga'],
        ['code' => '61', 'name' => 'Pangasinan'],
        ['code' => '62', 'name' => 'Quezon'],
        ['code' => '63', 'name' => 'Quirino'],
        ['code' => '64', 'name' => 'Rizal'],
        ['code' => '65', 'name' => 'Romblon'],
        ['code' => '66', 'name' => 'Samar'],
        ['code' => '67', 'name' => 'Sarangani'],
        ['code' => '68', 'name' => 'Siquijor'],
        ['code' => '69', 'name' => 'Sorsogon'],
        ['code' => '70', 'name' => 'South Cotabato'],
        ['code' => '71', 'name' => 'Southern Leyte'],
        ['code' => '72', 'name' => 'Sultan Kudarat'],
        ['code' => '73', 'name' => 'Sulu'],
        ['code' => '74', 'name' => 'Surigao del Norte'],
        ['code' => '75', 'name' => 'Surigao del Sur'],
        ['code' => '76', 'name' => 'Tarlac'],
        ['code' => '77', 'name' => 'Tawi-Tawi'],
        ['code' => '78', 'name' => 'Zambales'],
        ['code' => '79', 'name' => 'Zamboanga del Norte'],
        ['code' => '80', 'name' => 'Zamboanga del Sur'],
        ['code' => '81', 'name' => 'Zamboanga Sibugay'],
        ['code' => '82', 'name' => 'Metro Manila']
    ];
}

function getFallbackProvinces() {
    // Fallback data if API is unavailable
    return [
        ['code' => '01', 'name' => 'Abra'],
        ['code' => '02', 'name' => 'Agusan del Norte'],
        ['code' => '03', 'name' => 'Agusan del Sur'],
        ['code' => '04', 'name' => 'Aklan'],
        ['code' => '05', 'name' => 'Albay'],
        ['code' => '06', 'name' => 'Antique'],
        ['code' => '07', 'name' => 'Apayao'],
        ['code' => '08', 'name' => 'Aurora'],
        ['code' => '09', 'name' => 'Basilan'],
        ['code' => '10', 'name' => 'Bataan'],
        ['code' => '11', 'name' => 'Batanes'],
        ['code' => '12', 'name' => 'Batangas'],
        ['code' => '13', 'name' => 'Benguet'],
        ['code' => '14', 'name' => 'Biliran'],
        ['code' => '15', 'name' => 'Bohol'],
        ['code' => '16', 'name' => 'Bukidnon'],
        ['code' => '17', 'name' => 'Bulacan'],
        ['code' => '18', 'name' => 'Cagayan'],
        ['code' => '19', 'name' => 'Camarines Norte'],
        ['code' => '20', 'name' => 'Camarines Sur'],
        ['code' => '21', 'name' => 'Camiguin'],
        ['code' => '22', 'name' => 'Capiz'],
        ['code' => '23', 'name' => 'Catanduanes'],
        ['code' => '24', 'name' => 'Cavite'],
        ['code' => '25', 'name' => 'Cebu'],
        ['code' => '26', 'name' => 'Cotabato'],
        ['code' => '27', 'name' => 'Davao del Norte'],
        ['code' => '28', 'name' => 'Davao del Sur'],
        ['code' => '29', 'name' => 'Davao Oriental'],
        ['code' => '30', 'name' => 'Davao de Oro'],
        ['code' => '31', 'name' => 'Davao Occidental'],
        ['code' => '32', 'name' => 'Dinagat Islands'],
        ['code' => '33', 'name' => 'Eastern Samar'],
        ['code' => '34', 'name' => 'Guimaras'],
        ['code' => '35', 'name' => 'Ifugao'],
        ['code' => '36', 'name' => 'Ilocos Norte'],
        ['code' => '37', 'name' => 'Ilocos Sur'],
        ['code' => '38', 'name' => 'Iloilo'],
        ['code' => '39', 'name' => 'Isabela'],
        ['code' => '40', 'name' => 'Kalinga'],
        ['code' => '41', 'name' => 'Laguna'],
        ['code' => '42', 'name' => 'Lanao del Norte'],
        ['code' => '43', 'name' => 'Lanao del Sur'],
        ['code' => '44', 'name' => 'La Union'],
        ['code' => '45', 'name' => 'Leyte'],
        ['code' => '46', 'name' => 'Maguindanao'],
        ['code' => '47', 'name' => 'Marinduque'],
        ['code' => '48', 'name' => 'Masbate'],
        ['code' => '49', 'name' => 'Misamis Occidental'],
        ['code' => '50', 'name' => 'Misamis Oriental'],
        ['code' => '51', 'name' => 'Mountain Province'],
        ['code' => '52', 'name' => 'Negros Occidental'],
        ['code' => '53', 'name' => 'Negros Oriental'],
        ['code' => '54', 'name' => 'Northern Samar'],
        ['code' => '55', 'name' => 'Nueva Ecija'],
        ['code' => '56', 'name' => 'Nueva Vizcaya'],
        ['code' => '57', 'name' => 'Occidental Mindoro'],
        ['code' => '58', 'name' => 'Oriental Mindoro'],
        ['code' => '59', 'name' => 'Palawan'],
        ['code' => '60', 'name' => 'Pampanga'],
        ['code' => '61', 'name' => 'Pangasinan'],
        ['code' => '62', 'name' => 'Quezon'],
        ['code' => '63', 'name' => 'Quirino'],
        ['code' => '64', 'name' => 'Rizal'],
        ['code' => '65', 'name' => 'Romblon'],
        ['code' => '66', 'name' => 'Samar'],
        ['code' => '67', 'name' => 'Sarangani'],
        ['code' => '68', 'name' => 'Siquijor'],
        ['code' => '69', 'name' => 'Sorsogon'],
        ['code' => '70', 'name' => 'South Cotabato'],
        ['code' => '71', 'name' => 'Southern Leyte'],
        ['code' => '72', 'name' => 'Sultan Kudarat'],
        ['code' => '73', 'name' => 'Sulu'],
        ['code' => '74', 'name' => 'Surigao del Norte'],
        ['code' => '75', 'name' => 'Surigao del Sur'],
        ['code' => '76', 'name' => 'Tarlac'],
        ['code' => '77', 'name' => 'Tawi-Tawi'],
        ['code' => '78', 'name' => 'Zambales'],
        ['code' => '79', 'name' => 'Zamboanga del Norte'],
        ['code' => '80', 'name' => 'Zamboanga del Sur'],
        ['code' => '81', 'name' => 'Zamboanga Sibugay'],
        ['code' => '82', 'name' => 'Metro Manila']
    ];
}

function getMunicipalities() {
    $provinceCode = $_GET['province_code'] ?? '';
    $provinceName = $_GET['province_name'] ?? '';
    $provinceId = $_GET['province_id'] ?? '';
    
    if (empty($provinceCode) && empty($provinceName) && empty($provinceId)) {
        throw new Exception('Province code, name, or id is required');
    }
    
    // Try to get province ID/code by name if not provided
    if (empty($provinceId) && empty($provinceCode) && !empty($provinceName)) {
        $provinceId = getProvinceIdByName($provinceName);
        error_log("Looking up province ID for: " . $provinceName . ", found ID: " . ($provinceId ?: 'none'));
        if (!empty($provinceId)) {
            $provinceCode = $provinceId; // Use province ID as code for matching
        }
    }
    
    error_log("Filtering municipalities for province: name='" . $provinceName . "', code='" . $provinceCode . "', id='" . $provinceId . "'");
    
    // Use PSGC Cloud API (Official Philippine Government API) - fetch cities and municipalities
    $municipalities = [];
    
    // Fetch cities from PSGC Cloud API
    $citiesUrl = 'https://psgc.cloud/api/cities';
    $citiesResponse = fetchFromAPI($citiesUrl);
    
    // Fetch municipalities from PSGC Cloud API
    $municipalitiesUrl = 'https://psgc.cloud/api/municipalities';
    $municipalitiesResponse = fetchFromAPI($municipalitiesUrl);
    
    // Merge cities and municipalities
    $allMunicipalities = [];
    
    if ($citiesResponse !== false) {
        $citiesData = json_decode($citiesResponse, true);
        if (is_array($citiesData)) {
            $allMunicipalities = array_merge($allMunicipalities, $citiesData);
        }
    }
    
    if ($municipalitiesResponse !== false) {
        $municipalitiesData = json_decode($municipalitiesResponse, true);
        if (is_array($municipalitiesData)) {
            $allMunicipalities = array_merge($allMunicipalities, $municipalitiesData);
        }
    }
    
    if (empty($allMunicipalities)) {
        error_log("Failed to fetch cities/municipalities from PSGC Cloud API");
    } else {
        error_log("Fetched " . count($allMunicipalities) . " total cities/municipalities from PSGC Cloud API");
        
        // Filter by province
        $filteredCount = 0;
        foreach ($allMunicipalities as $city) {
            $cityProvinceId = $city['provinceCode'] ?? $city['province']['code'] ?? '';
            $cityProvinceName = $city['provinceName'] ?? $city['province']['name'] ?? '';
            $cityName = $city['name'] ?? '';
            $cityCode = $city['code'] ?? $city['psgcCode'] ?? '';
            
            $shouldInclude = false;
            
            // Filter by province_code if available (most reliable)
            // PSGC code structure:
            // - Province: 0701200000 (first 5 digits = province identifier)
            // - City/Municipality: 0701242000 (first 5 digits should match province)
            // For matching: check if city code starts with province code prefix (first 5 digits)
            if (!empty($provinceCode)) {
                // Extract province identifier (first 5 digits of province code)
                $provincePrefix = substr($provinceCode, 0, 5);
                
                // Try to get province code from city data
                if (!empty($cityProvinceId)) {
                    $cityProvincePrefix = substr($cityProvinceId, 0, 5);
                    if ($cityProvinceId == $provinceCode || $cityProvincePrefix == $provincePrefix) {
                        $shouldInclude = true;
                        error_log("Matched by provinceCode field: " . $cityName . " (province code: " . $cityProvinceId . " == " . $provinceCode . ")");
                    }
                }
                
                // Also check if city code itself starts with province prefix
                // e.g., province 0701200000, city 0701242000 - first 5 digits should match province identifier
                if (!$shouldInclude && !empty($cityCode)) {
                    $cityCodePrefix = substr($cityCode, 0, 5);
                    if ($cityCodePrefix == $provincePrefix) {
                        $shouldInclude = true;
                        error_log("Matched by city code prefix: " . $cityName . " (city code: " . $cityCode . " starts with province prefix: " . $provincePrefix . ")");
                    }
                }
            }
            
            // Filter by province_id if available and not already matched
            if (!$shouldInclude && !empty($provinceId) && !empty($cityProvinceId)) {
                // Check exact match or if provinceId is contained in cityProvinceId
                if ($cityProvinceId == $provinceId || strpos($cityProvinceId, $provinceId) === 0) {
                    $shouldInclude = true;
                    error_log("Matched by ID: " . $cityName . " (province ID: " . $cityProvinceId . " == " . $provinceId . ")");
                }
            }
            
            // Filter by province name if not already matched by code/ID (must be strict)
            if (!$shouldInclude && !empty($provinceName) && !empty($cityProvinceName)) {
                $normalizedProvinceName = trim(str_ireplace(['Province', 'Prov.'], '', $provinceName));
                $normalizedCityProvince = trim(str_ireplace(['Province', 'Prov.'], '', $cityProvinceName));
                
                // Exact match or partial match (strict)
                if (strcasecmp($normalizedProvinceName, $normalizedCityProvince) === 0 || 
                    stripos($normalizedCityProvince, $normalizedProvinceName) !== false || 
                    stripos($normalizedProvinceName, $normalizedCityProvince) !== false) {
                    $shouldInclude = true;
                    error_log("Matched by name: " . $cityName . " (province: " . $cityProvinceName . " == " . $provinceName . ")");
                } else {
                    error_log("Did NOT match by name: " . $cityName . " (province: '" . $cityProvinceName . "' != '" . $provinceName . "')");
                }
            }
            
            // Only include if matched (strict filtering - no else clause)
            if ($shouldInclude) {
                $municipalities[] = [
                    'code' => $cityCode,
                    'name' => $cityName
                ];
                $filteredCount++;
            }
        }
        error_log("Filtered municipalities count: " . $filteredCount . " out of " . count($allMunicipalities) . " total");
    }
    
    
    // If still empty, return error (no fallback data as requested)
    if (empty($municipalities)) {
        error_log("ERROR: PSGC Cloud API failed or returned no municipalities for province: " . $provinceName);
        
        // Return error response
        http_response_code(503); // Service Unavailable
        echo json_encode([
            'success' => false,
            'error' => 'API service unavailable. Please check server connectivity.',
            'message' => 'Unable to fetch municipalities from PSGC Cloud API. Please ensure the server has internet access and DNS is properly configured.',
            'debug' => [
                'province' => $provinceName,
                'api_used' => 'psgc.cloud',
                'suggestion' => 'Check DNS resolution for psgc.cloud and ensure firewall allows HTTPS connections'
            ]
        ]);
        exit;
    }
    
    // Sort municipalities alphabetically
    usort($municipalities, function($a, $b) {
        return strcmp($a['name'], $b['name']);
    });
    
    error_log("Final municipalities count: " . count($municipalities));
    
    echo json_encode([
        'success' => true,
        'data' => $municipalities,
        'debug' => [
                'api_used' => 'PSGC Cloud API (Official Philippine Government)',
            'province' => $provinceName,
            'province_id' => $provinceId ?: 'not found',
            'count' => count($municipalities)
        ]
    ]);
}

function getProvinceIdByName($provinceName) {
    // Cache province lookup by fetching provinces once
    static $provinceCache = null;
    
    if ($provinceCache === null) {
        // Use PSGC Cloud API directly
        $apiUrl = 'https://psgc.cloud/api/provinces';
        $response = fetchFromAPI($apiUrl);
        
        if ($response !== false) {
            $data = json_decode($response, true);
            if (isset($data['data']) && is_array($data['data'])) {
                $provinceCache = $data['data'];
                error_log("Province cache loaded with " . count($provinceCache) . " provinces from API");
            } elseif (is_array($data)) {
                $provinceCache = $data;
                error_log("Province cache loaded with " . count($provinceCache) . " provinces (direct array)");
            } else {
                error_log("Failed to decode province data. Response preview: " . substr($response, 0, 200));
                // Use fallback provinces for lookup
                $provinceCache = getAllPhilippineProvinces();
                error_log("Using fallback provinces for cache: " . count($provinceCache) . " provinces");
            }
        } else {
            error_log("Failed to fetch provinces for cache, using fallback");
            // Use fallback provinces for lookup
            $provinceCache = getAllPhilippineProvinces();
            error_log("Using fallback provinces for cache: " . count($provinceCache) . " provinces");
        }
    }
    
    if (is_array($provinceCache)) {
        // Normalize the search name
        $normalizedSearchName = trim(str_ireplace(['Province', 'Prov.'], '', $provinceName));
        
        foreach ($provinceCache as $province) {
            $name = $province['name'] ?? '';
            $code = $province['code'] ?? $province['psgcCode'] ?? '';
            $normalizedName = trim(str_ireplace(['Province', 'Prov.'], '', $name));
            
            // Try exact match first
            if (strcasecmp($normalizedSearchName, $normalizedName) === 0 || 
                strcasecmp($provinceName, $name) === 0) {
                if (!empty($code)) {
                    error_log("Exact match found for province: " . $provinceName . " -> Code: " . $code);
                    return $code;
                }
            }
            
            // Try partial match
            if (stripos($normalizedName, $normalizedSearchName) !== false || 
                stripos($normalizedSearchName, $normalizedName) !== false ||
                stripos($name, $provinceName) !== false || 
                stripos($provinceName, $name) !== false) {
                if (!empty($code)) {
                    error_log("Partial match found for province: " . $provinceName . " (matched with: " . $name . ") -> Code: " . $code);
                    return $code;
                }
            }
        }
    }
    
    error_log("No province ID found for: " . $provinceName);
    return '';
}

function getAllPhilippineMunicipalities($provinceName) {
    // Comprehensive municipalities data for all provinces
    $municipalitiesData = [
        'Bohol' => [
            ['code' => '1501', 'name' => 'Alburquerque'], ['code' => '1502', 'name' => 'Alicia'], ['code' => '1503', 'name' => 'Anda'], ['code' => '1504', 'name' => 'Antequera'],
            ['code' => '1505', 'name' => 'Baclayon'], ['code' => '1506', 'name' => 'Balilihan'], ['code' => '1507', 'name' => 'Batuan'], ['code' => '1508', 'name' => 'Bien Unido'],
            ['code' => '1509', 'name' => 'Bilar'], ['code' => '1510', 'name' => 'Buenavista'], ['code' => '1511', 'name' => 'Calape'], ['code' => '1512', 'name' => 'Candijay'],
            ['code' => '1513', 'name' => 'Carmen'], ['code' => '1514', 'name' => 'Catigbian'], ['code' => '1515', 'name' => 'Clarin'], ['code' => '1516', 'name' => 'Corella'],
            ['code' => '1517', 'name' => 'Cortes'], ['code' => '1518', 'name' => 'Dagohoy'], ['code' => '1519', 'name' => 'Danao'], ['code' => '1520', 'name' => 'Dauis'],
            ['code' => '1521', 'name' => 'Dimiao'], ['code' => '1522', 'name' => 'Duero'], ['code' => '1523', 'name' => 'Garcia Hernandez'], ['code' => '1524', 'name' => 'Getafe'],
            ['code' => '1525', 'name' => 'Guindulman'], ['code' => '1526', 'name' => 'Inabanga'], ['code' => '1527', 'name' => 'Jagna'], ['code' => '1528', 'name' => 'Lila'],
            ['code' => '1529', 'name' => 'Loay'], ['code' => '1530', 'name' => 'Loboc'], ['code' => '1531', 'name' => 'Loon'], ['code' => '1532', 'name' => 'Mabini'],
            ['code' => '1533', 'name' => 'Maribojoc'], ['code' => '1534', 'name' => 'Panglao'], ['code' => '1535', 'name' => 'Pilar'], ['code' => '1536', 'name' => 'Pres. Carlos P. Garcia'],
            ['code' => '1537', 'name' => 'Sagbayan'], ['code' => '1538', 'name' => 'San Isidro'], ['code' => '1539', 'name' => 'San Miguel'], ['code' => '1540', 'name' => 'Sevilla'],
            ['code' => '1541', 'name' => 'Sierra Bullones'], ['code' => '1542', 'name' => 'Sikatuna'], ['code' => '1543', 'name' => 'Tagbilaran City'], ['code' => '1544', 'name' => 'Talibon'],
            ['code' => '1545', 'name' => 'Trinidad'], ['code' => '1546', 'name' => 'Tubigon'], ['code' => '1547', 'name' => 'Ubay'], ['code' => '1548', 'name' => 'Valencia']
        ],
        'Cebu' => [
            ['code' => '2501', 'name' => 'Alcantara'], ['code' => '2502', 'name' => 'Alcoy'], ['code' => '2503', 'name' => 'Alegria'], ['code' => '2504', 'name' => 'Aloguinsan'],
            ['code' => '2505', 'name' => 'Argao'], ['code' => '2506', 'name' => 'Asturias'], ['code' => '2507', 'name' => 'Badian'], ['code' => '2508', 'name' => 'Balamban'],
            ['code' => '2509', 'name' => 'Bantayan'], ['code' => '2510', 'name' => 'Barili'], ['code' => '2511', 'name' => 'Boljoon'], ['code' => '2512', 'name' => 'Borbon'],
            ['code' => '2513', 'name' => 'Carmen'], ['code' => '2514', 'name' => 'Catmon'], ['code' => '2515', 'name' => 'Compostela'], ['code' => '2516', 'name' => 'Consolacion'],
            ['code' => '2517', 'name' => 'Cordova'], ['code' => '2518', 'name' => 'Daanbantayan'], ['code' => '2519', 'name' => 'Dalaguete'], ['code' => '2520', 'name' => 'Danao City'],
            ['code' => '2521', 'name' => 'Dumanjug'], ['code' => '2522', 'name' => 'Ginatilan'], ['code' => '2523', 'name' => 'Lapu-Lapu City'], ['code' => '2524', 'name' => 'Liloan'],
            ['code' => '2525', 'name' => 'Madridejos'], ['code' => '2526', 'name' => 'Malabuyoc'], ['code' => '2527', 'name' => 'Mandaue City'], ['code' => '2528', 'name' => 'Medellin'],
            ['code' => '2529', 'name' => 'Minglanilla'], ['code' => '2530', 'name' => 'Moalboal'], ['code' => '2531', 'name' => 'Naga City'], ['code' => '2532', 'name' => 'Oslob'],
            ['code' => '2533', 'name' => 'Pilar'], ['code' => '2534', 'name' => 'Pinamungahan'], ['code' => '2535', 'name' => 'Poro'], ['code' => '2536', 'name' => 'Ronda'],
            ['code' => '2537', 'name' => 'Samboan'], ['code' => '2538', 'name' => 'San Fernando'], ['code' => '2539', 'name' => 'San Francisco'], ['code' => '2540', 'name' => 'San Remigio'],
            ['code' => '2541', 'name' => 'Santa Fe'], ['code' => '2542', 'name' => 'Santander'], ['code' => '2543', 'name' => 'Sibonga'], ['code' => '2544', 'name' => 'Sogod'],
            ['code' => '2545', 'name' => 'Tabogon'], ['code' => '2546', 'name' => 'Tabuelan'], ['code' => '2547', 'name' => 'Talisay City'], ['code' => '2548', 'name' => 'Toledo City'],
            ['code' => '2549', 'name' => 'Tuburan'], ['code' => '2550', 'name' => 'Tudela'], ['code' => '2551', 'name' => 'Cebu City']
        ],
        'Metro Manila' => [
            ['code' => '8201', 'name' => 'Caloocan City'], ['code' => '8202', 'name' => 'Las Piñas City'], ['code' => '8203', 'name' => 'Makati City'], ['code' => '8204', 'name' => 'Malabon City'],
            ['code' => '8205', 'name' => 'Mandaluyong City'], ['code' => '8206', 'name' => 'Manila City'], ['code' => '8207', 'name' => 'Marikina City'], ['code' => '8208', 'name' => 'Muntinlupa City'],
            ['code' => '8209', 'name' => 'Navotas City'], ['code' => '8210', 'name' => 'Parañaque City'], ['code' => '8211', 'name' => 'Pasay City'], ['code' => '8212', 'name' => 'Pasig City'],
            ['code' => '8213', 'name' => 'Pateros'], ['code' => '8214', 'name' => 'Quezon City'], ['code' => '8215', 'name' => 'San Juan City'], ['code' => '8216', 'name' => 'Taguig City'],
            ['code' => '8217', 'name' => 'Valenzuela City']
        ],
        'Laguna' => [
            ['code' => '4101', 'name' => 'Alaminos'], ['code' => '4102', 'name' => 'Bay'], ['code' => '4103', 'name' => 'Biñan City'], ['code' => '4104', 'name' => 'Cabuyao City'],
            ['code' => '4105', 'name' => 'Calamba City'], ['code' => '4106', 'name' => 'Calauan'], ['code' => '4107', 'name' => 'Cavinti'], ['code' => '4108', 'name' => 'Famy'],
            ['code' => '4109', 'name' => 'Kalayaan'], ['code' => '4110', 'name' => 'Liliw'], ['code' => '4111', 'name' => 'Los Baños'], ['code' => '4112', 'name' => 'Luisiana'],
            ['code' => '4113', 'name' => 'Lumban'], ['code' => '4114', 'name' => 'Mabitac'], ['code' => '4115', 'name' => 'Magdalena'], ['code' => '4116', 'name' => 'Majayjay'],
            ['code' => '4117', 'name' => 'Nagcarlan'], ['code' => '4118', 'name' => 'Paete'], ['code' => '4119', 'name' => 'Pagsanjan'], ['code' => '4120', 'name' => 'Pakil'],
            ['code' => '4121', 'name' => 'Pangil'], ['code' => '4122', 'name' => 'Pila'], ['code' => '4123', 'name' => 'Rizal'], ['code' => '4124', 'name' => 'San Pablo City'],
            ['code' => '4125', 'name' => 'San Pedro City'], ['code' => '4126', 'name' => 'Santa Cruz'], ['code' => '4127', 'name' => 'Santa Maria'], ['code' => '4128', 'name' => 'Santa Rosa City'],
            ['code' => '4129', 'name' => 'Siniloan'], ['code' => '4130', 'name' => 'Victoria']
        ],
        'Albay' => [
            ['code' => '0501', 'name' => 'Bacacay'], ['code' => '0502', 'name' => 'Camalig'], ['code' => '0503', 'name' => 'Daraga'], ['code' => '0504', 'name' => 'Guinobatan'],
            ['code' => '0505', 'name' => 'Jovellar'], ['code' => '0506', 'name' => 'Libon'], ['code' => '0507', 'name' => 'Ligao City'], ['code' => '0508', 'name' => 'Malilipot'],
            ['code' => '0509', 'name' => 'Malinao'], ['code' => '0510', 'name' => 'Manito'], ['code' => '0511', 'name' => 'Oas'], ['code' => '0512', 'name' => 'Pio Duran'],
            ['code' => '0513', 'name' => 'Polangui'], ['code' => '0514', 'name' => 'Rapu-Rapu'], ['code' => '0515', 'name' => 'Santo Domingo'], ['code' => '0516', 'name' => 'Tabaco City'],
            ['code' => '0517', 'name' => 'Tiwi'], ['code' => '0518', 'name' => 'Legazpi City']
        ],
        'Tagbilaran City' => [
            ['code' => '158801', 'name' => 'Agoho'], ['code' => '158802', 'name' => 'Baclayon'], ['code' => '158803', 'name' => 'Booy'], ['code' => '158804', 'name' => 'Cabawan'],
            ['code' => '158805', 'name' => 'Cogon'], ['code' => '158806', 'name' => 'Dampas'], ['code' => '158807', 'name' => 'Dao'], ['code' => '158808', 'name' => 'Mansasa'],
            ['code' => '158809', 'name' => 'Poblacion I'], ['code' => '158810', 'name' => 'Poblacion II'], ['code' => '158811', 'name' => 'Poblacion III'], ['code' => '158812', 'name' => 'San Isidro'],
            ['code' => '158813', 'name' => 'Taloto'], ['code' => '158814', 'name' => 'Tiptip'], ['code' => '158815', 'name' => 'Ubujan']
        ]
    ];
    
    // Find municipalities by province name
    foreach ($municipalitiesData as $province => $municipalityList) {
        if (stripos($province, $provinceName) !== false) {
            return $municipalityList;
        }
    }
    
    // Return empty array instead of sample data if not found
    return [];
}

function getFallbackMunicipalities($provinceName) {
    // Fallback data for common provinces
    $municipalitiesData = [
        'Bohol' => [
            ['code' => '1501', 'name' => 'Alburquerque'],
            ['code' => '1502', 'name' => 'Alicia'],
            ['code' => '1503', 'name' => 'Anda'],
            ['code' => '1504', 'name' => 'Antequera'],
            ['code' => '1505', 'name' => 'Baclayon'],
            ['code' => '1506', 'name' => 'Balilihan'],
            ['code' => '1507', 'name' => 'Batuan'],
            ['code' => '1508', 'name' => 'Bien Unido'],
            ['code' => '1509', 'name' => 'Bilar'],
            ['code' => '1510', 'name' => 'Buenavista'],
            ['code' => '1511', 'name' => 'Calape'],
            ['code' => '1512', 'name' => 'Candijay'],
            ['code' => '1513', 'name' => 'Carmen'],
            ['code' => '1514', 'name' => 'Catigbian'],
            ['code' => '1515', 'name' => 'Clarin'],
            ['code' => '1516', 'name' => 'Corella'],
            ['code' => '1517', 'name' => 'Cortes'],
            ['code' => '1518', 'name' => 'Dagohoy'],
            ['code' => '1519', 'name' => 'Danao'],
            ['code' => '1520', 'name' => 'Dauis'],
            ['code' => '1521', 'name' => 'Dimiao'],
            ['code' => '1522', 'name' => 'Duero'],
            ['code' => '1523', 'name' => 'Garcia Hernandez'],
            ['code' => '1524', 'name' => 'Getafe'],
            ['code' => '1525', 'name' => 'Guindulman'],
            ['code' => '1526', 'name' => 'Inabanga'],
            ['code' => '1527', 'name' => 'Jagna'],
            ['code' => '1528', 'name' => 'Lila'],
            ['code' => '1529', 'name' => 'Loay'],
            ['code' => '1530', 'name' => 'Loboc'],
            ['code' => '1531', 'name' => 'Loon'],
            ['code' => '1532', 'name' => 'Mabini'],
            ['code' => '1533', 'name' => 'Maribojoc'],
            ['code' => '1534', 'name' => 'Panglao'],
            ['code' => '1535', 'name' => 'Pilar'],
            ['code' => '1536', 'name' => 'Pres. Carlos P. Garcia'],
            ['code' => '1537', 'name' => 'Sagbayan'],
            ['code' => '1538', 'name' => 'San Isidro'],
            ['code' => '1539', 'name' => 'San Miguel'],
            ['code' => '1540', 'name' => 'Sevilla'],
            ['code' => '1541', 'name' => 'Sierra Bullones'],
            ['code' => '1542', 'name' => 'Sikatuna'],
            ['code' => '1543', 'name' => 'Tagbilaran City'],
            ['code' => '1544', 'name' => 'Talibon'],
            ['code' => '1545', 'name' => 'Trinidad'],
            ['code' => '1546', 'name' => 'Tubigon'],
            ['code' => '1547', 'name' => 'Ubay'],
            ['code' => '1548', 'name' => 'Valencia']
        ]
    ];
    
    // Find municipalities by province name
    foreach ($municipalitiesData as $province => $municipalityList) {
        if (stripos($province, $provinceName) !== false) {
            return $municipalityList;
        }
    }
    
    // Default fallback
    return [
        ['code' => '0001', 'name' => 'Sample Municipality 1'],
        ['code' => '0002', 'name' => 'Sample Municipality 2'],
        ['code' => '0003', 'name' => 'Sample Municipality 3']
    ];
}

function getBarangays() {
    // Barangay data is not available from PSGC Cloud API (endpoint returns 500/404 errors)
    // Return success response with empty array to indicate manual input is required
    $municipalityName = $_GET['municipality_name'] ?? '';
    
    error_log("Barangays endpoint called for: " . $municipalityName);
    error_log("Returning empty array - barangay should be manually input");
    
    // Return success response with empty data array
    // This tells the Android app that barangay should be manually input
    echo json_encode([
        'success' => true,
        'data' => [],
        'message' => 'Please type your barangay manually in the detailed address field below.',
        'manual_input' => true,
        'count' => 0
    ]);
}

function getCityIdByName($cityName) {
    // Cache city lookup by fetching cities once (limited to avoid memory issues)
    // For better performance, this should be optimized with proper caching
    // Use PSGC Cloud API - fetch both cities and municipalities
    $apiUrl = 'https://psgc.cloud/api/cities';
    $response = fetchFromAPI($apiUrl);
    
    // Also fetch municipalities
    $municipalitiesUrl = 'https://psgc.cloud/api/municipalities';
    $municipalitiesResponse = fetchFromAPI($municipalitiesUrl);
    
    // Merge cities and municipalities
    $allPlaces = [];
    if ($response !== false) {
        $citiesData = json_decode($response, true);
        if (is_array($citiesData)) {
            $allPlaces = array_merge($allPlaces, $citiesData);
        }
    }
    if ($municipalitiesResponse !== false) {
        $municipalitiesData = json_decode($municipalitiesResponse, true);
        if (is_array($municipalitiesData)) {
            $allPlaces = array_merge($allPlaces, $municipalitiesData);
        }
    }
    
    $response = !empty($allPlaces) ? json_encode($allPlaces) : false;
    
    if ($response !== false) {
        $data = json_decode($response, true);
        $cities = [];
        
        if (isset($data['data']) && is_array($data['data'])) {
            $cities = $data['data'];
        } elseif (is_array($data)) {
            $cities = $data;
        }
        
        foreach ($cities as $city) {
            $name = $city['name'] ?? '';
            $code = $city['code'] ?? $city['psgcCode'] ?? '';
            
            if (stripos($name, $cityName) !== false || stripos($cityName, $name) !== false) {
                if (!empty($code)) {
                    return $code;
                }
            }
        }
    }
    
    return '';
}

?>
