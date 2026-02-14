<?php
// update_profile.php - Handle profile completion and document uploads
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
header('Content-Type: application/json');

require_once 'dbConfig.php';

try {
    if ($conn->connect_error) {
        throw new Exception("Database connection failed");
    }

    $registrationId = $_POST['reg_id'] ?? $_POST['registration_id'] ?? null;
    $gcashNum = $_POST['gcashNum'] ?? null;
    $idType = $_POST['idType'] ?? null;
    $idNumber = $_POST['idNumber'] ?? null;

    if (!$registrationId) {
        throw new Exception("Registration ID is required");
    }

    // Check if user exists
    $stmt = $conn->prepare("SELECT role, status FROM registrations WHERE id = ?");
    $stmt->bind_param("i", $registrationId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    if (!$user) {
        throw new Exception("User not found");
    }
    $role = $user['role'];
    $stmt->close();

    // Handle file uploads
    $regUploadDir = "uploads/registrations/";
    $permitUploadDir = "uploads/business_permits/";
    if (!is_dir($regUploadDir)) mkdir($regUploadDir, 0777, true);
    if (!is_dir($permitUploadDir)) mkdir($permitUploadDir, 0777, true);

    function saveFile($fileKey, $uploadDir) {
        if (!isset($_FILES[$fileKey]) || $_FILES[$fileKey]['error'] !== UPLOAD_ERR_OK) return null;
        $fileName = uniqid() . "_" . basename($_FILES[$fileKey]['name']);
        $filePath = $uploadDir . $fileName;
        return move_uploaded_file($_FILES[$fileKey]['tmp_name'], $filePath) ? $filePath : null;
    }

    $idFrontPath = saveFile("idFrontFile", $regUploadDir);
    $idBackPath  = saveFile("idBackFile", $regUploadDir);
    $gcashQRPath = saveFile("gcash_qr", $regUploadDir) ?? saveFile("qrFile", $regUploadDir);

    // Business Permits - handle both individual and array formats
    $permitFiles = [];
    if (isset($_FILES['business_permits']) && is_array($_FILES['business_permits']['name'])) {
        foreach ($_FILES['business_permits']['name'] as $i => $name) {
            if ($_FILES['business_permits']['error'][$i] === UPLOAD_ERR_OK) {
                $fileName = uniqid() . "_" . basename($name);
                $filePath = $permitUploadDir . $fileName;
                if (move_uploaded_file($_FILES['business_permits']['tmp_name'][$i], $filePath)) {
                    $permitFiles[] = $filePath;
                }
            }
        }
    } else {
        // Fallback to permitFile1, permitFile2...
        for ($i = 1; $i <= 3; $i++) {
            $pPath = saveFile("permitFile" . $i, $permitUploadDir);
            if ($pPath) $permitFiles[] = $pPath;
        }
    }

    // Update query construction
    $updateFields = [];
    $types = "";
    $params = [];

    // Address handling
    $province = $_POST['province'] ?? '';
    $municipality = $_POST['municipality'] ?? '';
    $barangay = $_POST['barangay'] ?? '';
    $detailed_address = $_POST['detailed_address'] ?? '';

    // Only update address if at least one field is provided
    if ($province || $municipality || $barangay || $detailed_address) {
        $fullAddress = trim("$detailed_address, $barangay, $municipality, $province", ", ");
        $updateFields[] = "address = ?"; 
        $types .= "s"; 
        $params[] = $fullAddress; 
    }

    if ($gcashNum) { $updateFields[] = "gcash_num = ?"; $types .= "s"; $params[] = $gcashNum; }
    if ($idType) { $updateFields[] = "valid_id_type = ?"; $types .= "s"; $params[] = $idType; }
    if ($idNumber) { $updateFields[] = "id_number = ?"; $types .= "s"; $params[] = $idNumber; }
    if ($idFrontPath) { $updateFields[] = "idFrontFile = ?"; $types .= "s"; $params[] = $idFrontPath; }
    if ($idBackPath) { $updateFields[] = "idBackFile = ?"; $types .= "s"; $params[] = $idBackPath; }
    if ($gcashQRPath) { $updateFields[] = "gcash_qr = ?"; $types .= "s"; $params[] = $gcashQRPath; }

    // Address handling
    $province = $_POST['province'] ?? '';
    $municipality = $_POST['municipality'] ?? '';
    $barangay = $_POST['barangay'] ?? '';
    $detailed_address = $_POST['detailed_address'] ?? '';

    // Only update address if at least one field is provided
    if ($province || $municipality || $barangay || $detailed_address) {
        $fullAddress = trim("$detailed_address, $barangay, $municipality, $province", ", ");
        $updateFields[] = "address = ?"; 
        $types .= "s"; 
        $params[] = $fullAddress; 
    }

    // Logic: If all required docs are present (or if it's a Boarder and they uploaded ID), 
    // set status to 'pending_admin_review'.
    // For now, let's assume ANY update from the user in this screen moves them to pending review.
    $updateFields[] = "status = ?"; $types .= "s"; $params[] = 'pending_admin_review';

    if (empty($updateFields)) {
        throw new Exception("No fields to update");
    }

    $sql = "UPDATE registrations SET " . implode(", ", $updateFields) . " WHERE id = ?";
    $types .= "i";
    $params[] = $registrationId;

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    
    if ($stmt->execute()) {
        // Handle permits if any
        if (!empty($permitFiles)) {
            $pSql = "INSERT INTO bs_permits (reg_id, permit_file, permit_number, created_at) VALUES (?, ?, ?, NOW())";
            foreach ($permitFiles as $path) {
                $pStmt = $conn->prepare($pSql);
                $pNum = ""; // Or some value if needed
                $pStmt->bind_param("iss", $registrationId, $path, $pNum);
                $pStmt->execute();
                $pStmt->close();
            }
        }

        echo json_encode([
            "success" => true,
            "message" => "Profile details and documents submitted for review.",
            "status" => "pending_admin_review"
        ]);
    } else {
        throw new Exception("Update failed: " . $stmt->error);
    }

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
} finally {
    if (isset($conn)) $conn->close();
}
?>
