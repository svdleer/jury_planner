<?php
require_once 'config/database.php';

try {
    echo "Testing FairnessManager...\n";
    
    require_once 'includes/FairnessManager.php';
    $manager = new FairnessManager($db);
    
    echo "✅ FairnessManager created successfully!\n";
    
    // Test basic functionality
    $metrics = $manager->calculateFairnessMetrics();
    echo "✅ Fairness metrics calculated!\n";
    echo "- Teams count: " . $metrics['teams_count'] . "\n";
    echo "- Points spread: " . $metrics['points_difference'] . "\n";
    echo "- Fairness score: " . round($metrics['fairness_score'], 1) . "%\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "❌ File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
}
?>
