<?php
header('Content-Type: application/json');
require_once 'dbConfig.php';

$response = [
    'connection' => 'OK',
    'table_exists' => false,
    'admin_count' => 0,
    'test_query' => 'FAILED'
];

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'error' => 'Connection failed: ' . $conn->connect_error]);
    exit;
}

$result = $conn->query("SHOW TABLES LIKE 'admin_accounts'");
if ($result && $result->num_rows > 0) {
    $response['table_exists'] = true;
    
    $countResult = $conn->query("SELECT COUNT(*) as count FROM admin_accounts");
    if ($countResult) {
        $row = $countResult->fetch_assoc();
        $response['admin_count'] = (int)$row['count'];
        $response['test_query'] = 'SUCCESS';
    }
}

echo json_encode(['success' => true, 'data' => $response]);
?>
