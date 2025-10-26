<?php
require_once 'dbConfig.php';

echo "<h2>Cleaning Up Registration Duplicates</h2>";

if ($conn->connect_error) {
    echo "<p style='color: red;'>Connection failed: " . $conn->connect_error . "</p>";
    exit;
}

// Start transaction
$conn->begin_transaction();

try {
    // Get all duplicate emails in registrations
    $result = $conn->query("SELECT email, COUNT(*) as count FROM registrations GROUP BY email HAVING COUNT(*) > 1");
    
    if ($result && $result->num_rows > 0) {
        echo "<h3>Found duplicate emails in registrations:</h3>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Email</th><th>Count</th><th>Action</th></tr>";
        
        while ($row = $result->fetch_assoc()) {
            $email = $row['email'];
            $count = $row['count'];
            
            echo "<tr>";
            echo "<td>" . $email . "</td>";
            echo "<td>" . $count . "</td>";
            
            // Keep the latest registration, delete the rest
            $sql = "SELECT id FROM registrations WHERE email = ? ORDER BY created_at DESC LIMIT 1";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $keep_result = $stmt->get_result();
            $keep_reg = $keep_result->fetch_assoc();
            $keep_id = $keep_reg['id'];
            
            // Delete all other records for this email
            $sql = "DELETE FROM registrations WHERE email = ? AND id != ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $email, $keep_id);
            $stmt->execute();
            $deleted_count = $stmt->affected_rows;
            
            echo "<td>Kept ID: " . $keep_id . ", Deleted: " . $deleted_count . "</td>";
            echo "</tr>";
            
            $stmt->close();
        }
        echo "</table>";
        
        // Commit transaction
        $conn->commit();
        echo "<p style='color: green;'><strong>Registration cleanup completed successfully!</strong></p>";
        
    } else {
        echo "<p>No duplicate emails found in registrations table.</p>";
    }
    
    // Show final counts
    $result = $conn->query("SELECT COUNT(*) as count FROM registrations WHERE status = 'pending'");
    $row = $result->fetch_assoc();
    echo "<p><strong>Pending registrations after cleanup:</strong> " . $row['count'] . "</p>";
    
    $result = $conn->query("SELECT COUNT(*) as count FROM users WHERE status = 'Active'");
    $row = $result->fetch_assoc();
    echo "<p><strong>Active users:</strong> " . $row['count'] . "</p>";
    
} catch (Exception $e) {
    $conn->rollback();
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

$conn->close();
?>







