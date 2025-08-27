<?php
require_once 'php_interface/config/database.php';

echo "Checking matches table structure...\n";

try {
    // Get table structure
    $stmt = $db->query("SHOW COLUMNS FROM matches");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Matches table columns:\n";
    foreach ($columns as $column) {
        echo "  - " . $column['Field'] . " (" . $column['Type'] . ")\n";
    }
    
    // Check if is_locked column exists
    $hasIsLocked = false;
    foreach ($columns as $column) {
        if ($column['Field'] === 'is_locked') {
            $hasIsLocked = true;
            break;
        }
    }
    
    echo "\nis_locked column exists: " . ($hasIsLocked ? "Yes" : "No") . "\n";
    
    if (!$hasIsLocked) {
        echo "\nTo add is_locked column, run:\n";
        echo "ALTER TABLE matches ADD COLUMN is_locked BOOLEAN DEFAULT FALSE;\n";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
