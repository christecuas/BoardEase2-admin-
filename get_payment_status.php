<?php
// Get Payment Status API - Returns payment status for bills
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

require_once 'db_helper.php';

$response = [];

try {
    // Get user_id from request (GET or POST)
    $owner_id = isset($_GET['owner_id']) ? intval($_GET['owner_id']) : 0;
    $status = isset($_GET['status']) ? $_GET['status'] : 'all';
    
    // If not in GET, try POST
    if ($owner_id <= 0) {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            $input = $_POST;
        }
        $owner_id = isset($input['owner_id']) ? intval($input['owner_id']) : 0;
        $status = isset($input['status']) ? $input['status'] : 'all';
    }
    
    if ($owner_id <= 0) {
        throw new Exception("Invalid owner_id");
    }
    
    $db = getDB();
    
    // Build the query based on status filter
    // Note: Since we don't have boarding house info in active_boarders, we'll get all bills for now
    // You may need to add a boarding_house_id field to active_boarders table for proper filtering
    $whereClause = "WHERE 1=1"; // Get all bills for now
    $params = [];
    
    if ($status !== 'all') {
        $whereClause .= " AND b.status = ?";
        $params[] = ucfirst($status); // Convert to proper case (Unpaid, Paid, Overdue)
    }
    
    $sql = "
        SELECT 
            b.bill_id,
            b.active_id,
            b.amount_due,
            b.due_date,
            b.status,
            b.created_at,
            CONCAT(r.f_name, ' ', r.l_name) as boarder_name,
            r.email as boarder_email,
            r.phone_number as boarder_phone,
            'Room Info Not Available' as room_name,
            'N/A' as room_type,
            'N/A' as rent_type,
            0.00 as room_price,
            'Boarding House Info Not Available' as boarding_house_name,
            'N/A' as boarding_house_address,
            ab.status as boarder_status,
            u.profile_picture
        FROM bills b
        INNER JOIN active_boarders ab ON b.active_id = ab.active_id
        INNER JOIN users u ON ab.user_id = u.user_id
        INNER JOIN registration r ON u.reg_id = r.reg_id
        $whereClause
        ORDER BY b.created_at DESC
    ";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    
    $payments = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Format amount
        $amount = "P" . number_format($row['amount_due'], 2);
        
        // Format dates
        $due_date = date('Y-m-d', strtotime($row['due_date']));
        $created_date = date('Y-m-d', strtotime($row['created_at']));
        
        $payments[] = [
            'payment_id' => intval($row['bill_id']),
            'booking_id' => null, // Not applicable in your schema
            'user_id' => intval($row['active_id']),
            'boarder_name' => $row['boarder_name'],
            'boarder_email' => $row['boarder_email'],
            'boarder_phone' => $row['boarder_phone'],
            'room' => $row['room_name'] . ' - ' . $row['room_type'],
            'room_name' => $row['room_name'],
            'boarding_house_name' => $row['boarding_house_name'],
            'boarding_house_address' => $row['boarding_house_address'],
            'rent_type' => $row['rent_type'],
            'amount_paid' => $row['status'] === 'Paid' ? $amount : 'P0.00',
            'total_amount' => $amount,
            'payment_status' => $row['status'],
            'rental_status' => $row['boarder_status'],
            'payment_date' => $row['status'] === 'Paid' ? $created_date : 'Not paid',
            'due_date' => $due_date,
            'payment_method' => 'Cash', // Default value
            'notes' => 'Monthly rent payment',
            'created_at' => $created_date,
            'updated_at' => $created_date,
            'profile_image' => $row['profile_picture'] ?? ''
        ];
    }
    
    $response = [
        'success' => true,
        'data' => [
            'payments' => $payments,
            'total_count' => count($payments)
        ]
    ];
    
} catch (Exception $e) {
    $response = [
        'success' => false,
        'error' => $e->getMessage()
    ];
}

echo json_encode($response, JSON_UNESCAPED_SLASHES);
?>