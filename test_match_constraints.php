<?php
require_once 'config/database.php';

try {
    echo "Testing MatchConstraintManager...\n";
    
    require_once 'includes/MatchConstraintManager.php';
    $manager = new MatchConstraintManager($db);
    
    echo "MatchConstraintManager created successfully!\n";
    
    // Test constraint types
    $types = $manager->getConstraintTypes();
    echo "Constraint types: " . count($types) . "\n";
    
    foreach ($types as $key => $type) {
        echo "- {$key}: {$type['name']} ({$type['severity']})\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
