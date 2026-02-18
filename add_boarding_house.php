<?php
// Start output buffering to prevent accidental output
ob_start();

header('Content-Type: application/json');
include 'dbConfig.php'; // $conn is defined here

// Enable mysqli exceptions for better error handling
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn->set_charset("utf8mb4");

try {
    // Get POST data safely
    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    $bh_name = isset($_POST['bh_name']) ? trim($_POST['bh_name']) : '';
    $bh_address = isset($_POST['bh_address']) ? trim($_POST['bh_address']) : '';
    $bh_description = isset($_POST['bh_description']) ? trim($_POST['bh_description']) : '';
    $bh_rules = isset($_POST['bh_rules']) ? trim($_POST['bh_rules']) : '';
    $number_of_bathroom = isset($_POST['number_of_bathroom']) ? intval($_POST['number_of_bathroom']) : 0;
    $area = isset($_POST['area']) ? trim($_POST['area']) : '';
    $build_year = isset($_POST['build_year']) ? trim($_POST['build_year']) : '';
    $status = isset($_POST['status']) ? trim($_POST['status']) : 'Active';

    // Basic validation
    if ($user_id <= 0 || empty($bh_name) || empty($bh_address) || $number_of_bathroom <= 0) {
        echo json_encode(["error" => "Missing required fields."]);
        exit;
    }

    // Prepare SQL statement
    $sql = "INSERT INTO boarding_houses 
        (user_id, bh_name, bh_address, bh_description, bh_rules, number_of_bathroom, area, build_year, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "issssisss", 
        $user_id, 
        $bh_name, 
        $bh_address, 
        $bh_description, 
        $bh_rules, 
        $number_of_bathroom, 
        $area, 
        $build_year, 
        $status
    );

    // Execute and return result
    $stmt->execute();
    $bh_id = $stmt->insert_id;
    // =================================================================================
    // AUTO-CREATE COMMUNITY CHAT
    // =================================================================================
    try {
        // 1. Check if Group Chat already exists for this BH
        $checkStmt = $conn->prepare("SELECT gc_id FROM chat_groups WHERE bh_id = ?");
        $checkStmt->bind_param("i", $bh_id);
        $checkStmt->execute();
        $checkStmt->store_result();
        
        if ($checkStmt->num_rows == 0) {
            // 2. Create Group Chat
            $groupName = $bh_name . " – Community Chat";
            $createGroupSql = "INSERT INTO chat_groups (bh_id, gc_name, gc_created_by, gc_created_at) VALUES (?, ?, ?, NOW())";
            $groupStmt = $conn->prepare($createGroupSql);
            $groupStmt->bind_param("isi", $bh_id, $groupName, $user_id);
            
            if ($groupStmt->execute()) {
                $groupId = $groupStmt->insert_id;
                
                // 3. Add Owner as Member (Admin/Creator)
                $addMemberSql = "INSERT INTO group_members (gc_id, user_id, gm_role, status, gm_joined_at) VALUES (?, ?, 'admin', 'Active', NOW())";
                $memberStmt = $conn->prepare($addMemberSql);
                $memberStmt->bind_param("ii", $groupId, $user_id);
                $memberStmt->execute();
                
                // 4. Insert ONLY the Welcome Message (other messages handled by UI/Modal)
                $welcomeMsg = "Welcome to " . $groupName . ".";
                
                $msgSql = "INSERT INTO group_messages (gc_id, sender_id, groupmessage_text, groupmessage_timestamp, groupmessage_status) VALUES (?, ?, ?, NOW(), 'Sent')";
                $msgStmt = $conn->prepare($msgSql);
                $msgStmt->bind_param("iis", $groupId, $user_id, $welcomeMsg);
                $msgStmt->execute();
            }
        }
    } catch (Exception $e) {
        // Log error but don't fail the BH creation
        error_log("Error auto-creating community chat: " . $e->getMessage());
    }

    // Clean any prior output (notices, warnings, etc.)
    ob_clean();
    // Output success response at the VERY END
    echo json_encode(["success" => $bh_id]);

} catch (mysqli_sql_exception $e) {
    // Catch SQL errors and return as JSON
    ob_clean();
    echo json_encode(["error" => "Database error: " . $e->getMessage()]);
}
