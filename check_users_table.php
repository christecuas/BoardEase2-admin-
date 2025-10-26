<?php
require_once 'dbConfig.php';

echo "<h2>Users Table Structure Check</h2>";

if ($conn->connect_error) {
    echo "<p style='color: red;'>Connection failed: " . $conn->connect_error . "</p>";
    exit;
}

echo "<p style='color: green;'>Database connected successfully!</p>";

// Check users table structure
echo "<h3>Users Table Structure:</h3>";
$result = $conn->query("DESCRIBE users");
if ($result) {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . $row['Default'] . "</td>";
        echo "<td>" . $row['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Test the exact query from admin dashboard
echo "<h3>Testing Admin Dashboard Query:</h3>";
$sql = "SELECT u.user_id, u.reg_id, u.status, u.profile_picture, u.created_at,
               r.role, r.first_name, r.middle_name, r.last_name, r.phone, r.email
        FROM users u
        JOIN registrations r ON u.reg_id = r.id
        WHERE u.status = 'Active'
        ORDER BY u.created_at DESC";

echo "<p><strong>Query:</strong> " . $sql . "</p>";

$result = $conn->query($sql);

if ($result) {
    echo "<p style='color: green;'>Query executed successfully!</p>";
    echo "<p><strong>Number of rows:</strong> " . $result->num_rows . "</p>";
    
    if ($result->num_rows > 0) {
        echo "<h4>Results:</h4>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>User ID</th><th>Reg ID</th><th>Status</th><th>Name</th><th>Role</th><th>Email</th><th>Created At</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['user_id'] . "</td>";
            echo "<td>" . $row['reg_id'] . "</td>";
            echo "<td>" . $row['status'] . "</td>";
            echo "<td>" . $row['first_name'] . " " . $row['last_name'] . "</td>";
            echo "<td>" . $row['role'] . "</td>";
            echo "<td>" . $row['email'] . "</td>";
            echo "<td>" . ($row['created_at'] ?? 'NULL') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: orange;'>No results found</p>";
    }
} else {
    echo "<p style='color: red;'>Query failed: " . $conn->error . "</p>";
}

$conn->close();
?>







