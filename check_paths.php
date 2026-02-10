<?php
require_once 'dbConfig.php';

try {
    echo "Checking payment proof paths...\n\n";
    
    $sql = "SELECT payment_id, payment_proof, receipt_url, created_at, payment_status 
            FROM payments 
            ORDER BY created_at DESC 
            LIMIT 20";
    
    $stmt = $pdo->query($sql);
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($payments as $p) {
        echo "ID: " . $p['payment_id'] . "\n";
        echo "Proof: " . ($p['payment_proof'] ?: 'NULL') . "\n";
        echo "Receipt: " . ($p['receipt_url'] ?: 'NULL') . "\n";
        echo "Status: " . $p['payment_status'] . "\n";
        echo "Created: " . $p['created_at'] . "\n";
        echo "-------------------\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
