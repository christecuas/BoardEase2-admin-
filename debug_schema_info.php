<?php
require_once 'dbConfig.php';

function getTableColumns($tableName) {
    global $conn;
    $sql = "SHOW COLUMNS FROM " . $tableName;
    $result = $conn->query($sql);
    
    echo "Table: $tableName\n";
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            echo "  " . $row['Field'] . " (" . $row['Type'] . ")";
            if ($row['Default'] !== null) echo " Default: " . $row['Default'];
            echo "\n";
        }
    } else {
        echo "  Error: " . $conn->error . "\n";
    }
    echo "\n";
}

getTableColumns('group_members');
getTableColumns('chat_groups');
?>
