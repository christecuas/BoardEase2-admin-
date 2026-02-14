<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/dbConfig.php';

// Get POST data
$email = isset($_POST['email']) ? trim($_POST['email']) : '';

// Validate input
if (empty($email)) {
    echo json_encode([
        'success' => false,
        'exists' => false,
        'message' => 'Email is required'
    ]);
    exit;
}

try {
    // Check if email exists in registrations table (pending or approved)
    $stmt = $conn->prepare("SELECT registration_id, email FROM registrations WHERE email = ? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $registration = $result->fetch_assoc();
        echo json_encode([
            'success' => true,
            'exists' => true,
            'registration_id' => $registration['registration_id'],
            'message' => 'Email already exists in registrations'
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'exists' => false,
            'message' => 'Email is available'
        ]);
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'exists' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>
