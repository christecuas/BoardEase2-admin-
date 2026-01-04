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

// Path to local PSGC data files
define('PSGC_DATA_PATH', __DIR__ . '/psgc_data/');

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

/**
 * Load JSON data from local file
 */
function loadLocalJSON($filename) {
    $filepath = PSGC_DATA_PATH . $filename;
    
    if (!file_exists($filepath)) {
        error_log("Local JSON file not found: " . $filepath);
        return false;
    }
    
    $content = @file_get_contents($filepath);
    if ($content === false) {
        error_log("Failed to read local JSON file: " . $filepath);
        return false;
    }
    
    $data = json_decode($content, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("JSON decode error for " . $filename . ": " . json_last_error_msg());
        return false;
    }
    
    return $data;
}

// NOTE: This API now uses ONLY local JSON files from psgc_data directory
// No external API calls or fallback data - JSON files only

function getRegions() {
    // Load from local JSON file
    $data = loadLocalJSON('regions.json');
    
    if ($data === false || !is_array($data)) {
        error_log("Failed to load regions from local JSON file");
        http_response_code(503);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to load regions data from local file',
            'message' => 'Please ensure psgc_data/regions.json exists and is valid JSON'
        ]);
        exit;
    }
    
    $regions = [];
    foreach ($data as $region) {
        // Try multiple possible field names for code
        $code = $region['code'] ?? $region['designation'] ?? $region['id'] ?? $region['regionCode'] ?? '';
        // Try multiple possible field names for name
        $name = $region['name'] ?? $region['regionName'] ?? '';
        
        if (!empty($name)) {
            $regions[] = [
                'code' => $code,
                'name' => $name
            ];
        }
    }
    
    // Sort regions alphabetically
    usort($regions, function($a, $b) {
        return strcmp($a['name'], $b['name']);
    });
    
    error_log("Returning " . count($regions) . " regions from local file");
    
    echo json_encode([
        'success' => true,
        'data' => $regions,
        'debug' => [
            'source' => 'Local JSON file (psgc_data/regions.json)',
            'count' => count($regions)
        ]
    ]);
}

function getProvinces() {
    // Load from local JSON file
    $data = loadLocalJSON('provinces.json');
    
    if ($data === false || !is_array($data)) {
        error_log("Failed to load provinces from local JSON file");
        http_response_code(503);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to load provinces data from local file',
            'message' => 'Please ensure psgc_data/provinces.json exists and is valid JSON'
        ]);
        exit;
    }
    
    $provinces = [];
    foreach ($data as $province) {
        // Try multiple possible field names for code
        $code = $province['code'] ?? $province['id'] ?? $province['psgcCode'] ?? $province['region'] ?? $province['regionCode'] ?? '';
        // Try multiple possible field names for name
        $name = $province['name'] ?? $province['provinceName'] ?? '';
        
        if (!empty($name)) {
            $provinces[] = [
                'code' => $code,
                'name' => $name
            ];
        }
    }
    
    // Sort provinces alphabetically
    usort($provinces, function($a, $b) {
        return strcmp($a['name'], $b['name']);
    });
    
    error_log("Returning " . count($provinces) . " provinces from local file");
    
    echo json_encode([
        'success' => true,
        'data' => $provinces,
        'debug' => [
            'source' => 'Local JSON file (psgc_data/provinces.json)',
            'count' => count($provinces)
        ]
    ]);
}

// Removed: All hardcoded fallback data functions
// This API now uses ONLY JSON files - no fallback data

function getMunicipalities() {
    $provinceCode = $_GET['province_code'] ?? '';
    $provinceName = $_GET['province_name'] ?? '';
    $provinceId = $_GET['province_id'] ?? '';
    
    if (empty($provinceCode) && empty($provinceName) && empty($provinceId)) {
        throw new Exception('Province code, name, or id is required');
    }
    
    error_log("Filtering municipalities for province: name='" . $provinceName . "', code='" . $provinceCode . "', id='" . $provinceId . "'");
    
    // Load from local JSON file
    $data = loadLocalJSON('municipalities.json');
    
    if ($data === false || !is_array($data)) {
        error_log("Failed to load municipalities from local JSON file");
        http_response_code(503);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to load municipalities data from local file',
            'message' => 'Please ensure psgc_data/municipalities.json exists and is valid JSON'
        ]);
        exit;
    }
    
    $municipalities = [];
    
    // Normalize province name for matching
    $normalizedProvinceName = !empty($provinceName) ? trim(str_ireplace(['Province', 'Prov.'], '', $provinceName)) : '';
    
    foreach ($data as $municipality) {
        // Try multiple possible field names for province
        $munProvinceName = $municipality['province'] ?? $municipality['provinceName'] ?? 
                          (isset($municipality['province']) && is_array($municipality['province']) ? 
                           ($municipality['province']['name'] ?? '') : '') ?? '';
        $munProvinceCode = $municipality['provinceCode'] ?? 
                          (isset($municipality['province']) && is_array($municipality['province']) ? 
                           ($municipality['province']['code'] ?? '') : '') ?? '';
        $munName = $municipality['name'] ?? '';
        $munCode = $municipality['code'] ?? $municipality['id'] ?? $municipality['psgcCode'] ?? '';
        
        if (empty($munName)) {
            continue;
        }
        
        $shouldInclude = false;
        
        // Filter by province name (primary method)
        if (!empty($provinceName) && !empty($munProvinceName)) {
            $normalizedMunProvince = trim(str_ireplace(['Province', 'Prov.'], '', $munProvinceName));
            
            // Exact match or partial match
            if (strcasecmp($normalizedProvinceName, $normalizedMunProvince) === 0 || 
                stripos($normalizedMunProvince, $normalizedProvinceName) !== false || 
                stripos($normalizedProvinceName, $normalizedMunProvince) !== false ||
                strcasecmp($provinceName, $munProvinceName) === 0) {
                $shouldInclude = true;
            }
        }
        
        // Filter by province code if provided and name didn't match
        if (!$shouldInclude && !empty($provinceCode)) {
            // Match by province code
            if (!empty($munProvinceCode) && 
                ($provinceCode == $munProvinceCode || 
                 strpos($munProvinceCode, $provinceCode) === 0 ||
                 strpos($provinceCode, $munProvinceCode) === 0)) {
                $shouldInclude = true;
            }
            // Also try matching municipality code prefix (first few digits often match province)
            if (!$shouldInclude && !empty($munCode) && !empty($provinceCode)) {
                $provincePrefix = substr($provinceCode, 0, min(5, strlen($provinceCode)));
                $munPrefix = substr($munCode, 0, min(5, strlen($munCode)));
                if ($provincePrefix == $munPrefix) {
                    $shouldInclude = true;
                }
            }
        }
        
        if ($shouldInclude) {
            // Add "City" suffix if it's a city
            $displayName = $munName;
            if ((isset($municipality['city']) && $municipality['city'] === true) || 
                stripos($munName, 'City') !== false) {
                if (stripos($munName, 'City') === false) {
                    $displayName = $munName . ' City';
                }
            }
            
            $municipalities[] = [
                'code' => $munCode,
                'name' => $displayName
            ];
        }
    }
    
    // Remove duplicates based on name
    $uniqueMunicipalities = [];
    $seenNames = [];
    foreach ($municipalities as $mun) {
        $nameKey = strtolower($mun['name']);
        if (!isset($seenNames[$nameKey])) {
            $uniqueMunicipalities[] = $mun;
            $seenNames[$nameKey] = true;
        }
    }
    $municipalities = $uniqueMunicipalities;
    
    if (empty($municipalities)) {
        error_log("No municipalities found for province: " . $provinceName);
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'No municipalities found for the specified province',
            'message' => 'Please check the province name and try again',
            'debug' => [
                'province' => $provinceName,
                'source' => 'Local JSON file (psgc_data/municipalities.json)'
            ]
        ]);
        exit;
    }
    
    // Sort municipalities alphabetically
    usort($municipalities, function($a, $b) {
        return strcmp($a['name'], $b['name']);
    });
    
    error_log("Returning " . count($municipalities) . " municipalities from local file for province: " . $provinceName);
    
    echo json_encode([
        'success' => true,
        'data' => $municipalities,
        'debug' => [
            'source' => 'Local JSON file (psgc_data/municipalities.json)',
            'province' => $provinceName,
            'count' => count($municipalities)
        ]
    ]);
}

function getProvinceIdByName($provinceName) {
    // Cache province lookup by loading from local file once
    static $provinceCache = null;
    
    if ($provinceCache === null) {
        // Load from local JSON file
        $data = loadLocalJSON('provinces.json');
        
        if ($data !== false && is_array($data)) {
            $provinceCache = $data;
            error_log("Province cache loaded with " . count($provinceCache) . " provinces from local file");
        } else {
            error_log("Failed to load provinces for cache from local file");
            $provinceCache = [];
        }
    }
    
    if (is_array($provinceCache) && !empty($provinceCache)) {
        // Normalize the search name
        $normalizedSearchName = trim(str_ireplace(['Province', 'Prov.'], '', $provinceName));
        
        foreach ($provinceCache as $province) {
            // Try multiple possible field names
            $name = $province['name'] ?? $province['provinceName'] ?? '';
            $code = $province['code'] ?? $province['id'] ?? $province['psgcCode'] ?? 
                   $province['region'] ?? $province['regionCode'] ?? '';
            
            if (empty($name)) {
                continue;
            }
            
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

// Removed: All hardcoded fallback municipality data functions
// This API now uses ONLY JSON files - no fallback data

function getBarangays() {
    $municipalityName = $_GET['municipality_name'] ?? '';
    
    if (empty($municipalityName)) {
        throw new Exception('Municipality name is required');
    }
    
    error_log("Filtering barangays for municipality: " . $municipalityName);
    
    // Load from local JSON file
    $data = loadLocalJSON('barangays.json');
    
    if ($data === false || !is_array($data)) {
        error_log("Failed to load barangays from local JSON file");
        http_response_code(503);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to load barangays data from local file',
            'message' => 'Please ensure psgc_data/barangays.json exists and is valid JSON'
        ]);
        exit;
    }
    
    $barangays = [];
    
    // Normalize municipality name for matching (remove "City" suffix if present)
    $normalizedMunicipalityName = trim(str_ireplace([' City', 'City'], '', $municipalityName));
    
    foreach ($data as $barangay) {
        $brgyCityMun = $barangay['citymun'] ?? '';
        $brgyName = $barangay['name'] ?? '';
        $brgyCode = $barangay['code'] ?? '';
        
        if (empty($brgyCityMun) || empty($brgyName)) {
            continue;
        }
        
        // Normalize the municipality name from barangay data
        $normalizedBrgyCityMun = trim(str_ireplace([' City', 'City'], '', $brgyCityMun));
        
        // Match municipality name
        if (strcasecmp($normalizedMunicipalityName, $normalizedBrgyCityMun) === 0 || 
            stripos($normalizedBrgyCityMun, $normalizedMunicipalityName) !== false || 
            stripos($normalizedMunicipalityName, $normalizedBrgyCityMun) !== false ||
            strcasecmp($municipalityName, $brgyCityMun) === 0) {
            $barangays[] = [
                'code' => $brgyCode,
                'name' => $brgyName
            ];
        }
    }
    
    // Remove duplicates based on name
    $uniqueBarangays = [];
    $seenNames = [];
    foreach ($barangays as $brgy) {
        $nameKey = strtolower($brgy['name']);
        if (!isset($seenNames[$nameKey])) {
            $uniqueBarangays[] = $brgy;
            $seenNames[$nameKey] = true;
        }
    }
    $barangays = $uniqueBarangays;
    
    // Sort barangays alphabetically
    usort($barangays, function($a, $b) {
        return strcmp($a['name'], $b['name']);
    });
    
    error_log("Returning " . count($barangays) . " barangays from local file for municipality: " . $municipalityName);
    
    echo json_encode([
        'success' => true,
        'data' => $barangays,
        'debug' => [
            'source' => 'Local JSON file (psgc_data/barangays.json)',
            'municipality' => $municipalityName,
            'count' => count($barangays)
        ]
    ]);
}

function getCityIdByName($cityName) {
    // Use local JSON data instead of API calls
    static $cityCache = [];
    $cacheKey = strtolower(trim($cityName));
    
    if (isset($cityCache[$cacheKey])) {
        return $cityCache[$cacheKey];
    }
    
    // Load municipalities data (includes cities)
    $data = loadLocalJSON('municipalities.json');
    
    if ($data !== false && is_array($data)) {
        foreach ($data as $municipality) {
            $name = $municipality['name'] ?? '';
            $code = $municipality['code'] ?? $municipality['id'] ?? $municipality['psgcCode'] ?? '';
            
            // Check if name matches (case-insensitive partial match)
            if (stripos($name, $cityName) !== false || stripos($cityName, $name) !== false) {
                if (!empty($code)) {
                    $cityCache[$cacheKey] = $code;
                    return $code;
                }
            }
        }
    }
    
    // Not found
    $cityCache[$cacheKey] = '';
    return '';
}

?>
