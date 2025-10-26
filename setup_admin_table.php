<?php
require_once 'dbConfig.php';

try {
    $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create admin_accounts table
    $sql = "CREATE TABLE IF NOT EXISTS admin_accounts (
        admin_id INT PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        role ENUM('super_admin') DEFAULT 'super_admin',
        status ENUM('active', 'inactive') DEFAULT 'active',
        last_login TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    $conn->exec($sql);
    echo "✅ Admin accounts table created successfully!\n";
    
    // Check if table is empty
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM admin_accounts");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['count'] == 0) {
        // Insert initial admin accounts
        $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("INSERT INTO admin_accounts (name, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->execute(['Super Admin', 'admin@boardease.com', $hashedPassword, 'super_admin']);
        $stmt->execute(['Your Partner', 'partner@boardease.com', $hashedPassword, 'super_admin']);
        
        echo "✅ Initial admin accounts created!\n";
        echo "📧 Admin 1: admin@boardease.com (password: admin123)\n";
        echo "📧 Admin 2: partner@boardease.com (password: admin123)\n";
        echo "⚠️  Please change these passwords after first login!\n";
    } else {
        echo "ℹ️  Admin accounts table already has data.\n";
    }
    
    echo "\n🎉 Database setup complete! You can now use the Account Management system.\n";
    
} catch(PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>


