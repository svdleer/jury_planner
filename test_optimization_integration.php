<?php
// Test script for optimization integration

require_once 'php_interface/config/database.php';
require_once 'php_interface/optimization_interface.php';

echo "🧪 Testing Optimization Integration\n\n";

try {
    $optimizer = new OptimizationInterface($db);
    
    // Test 1: Validate constraints
    echo "1️⃣ Testing constraint validation...\n";
    $validation = $optimizer->validateConstraints();
    echo "   Valid: " . ($validation['valid'] ? '✅' : '❌') . "\n";
    echo "   Active constraints: " . $validation['active_constraints'] . "\n";
    echo "   Total constraints: " . $validation['total_constraints'] . "\n";
    
    if (!empty($validation['errors'])) {
        echo "   Errors found:\n";
        foreach ($validation['errors'] as $error) {
            echo "   - " . $error['constraint'] . ": " . implode(', ', $error['errors']) . "\n";
        }
    }
    echo "\n";
    
    // Test 2: Get recommendations
    echo "2️⃣ Getting constraint recommendations...\n";
    $recommendations = $optimizer->getConstraintRecommendations();
    
    if (!empty($recommendations['missing_constraints'])) {
        echo "   Missing constraints:\n";
        foreach ($recommendations['missing_constraints'] as $rec) {
            echo "   - " . $rec['type'] . " (Priority: " . $rec['priority'] . ")\n";
            echo "     " . $rec['description'] . "\n";
        }
    }
    
    if (!empty($recommendations['load_balancing'])) {
        echo "   Load balancing suggestions:\n";
        foreach ($recommendations['load_balancing'] as $rec) {
            echo "   - " . $rec['description'] . "\n";
        }
    }
    echo "\n";
    
    // Test 3: Check optimization stats
    echo "3️⃣ Checking optimization statistics...\n";
    $stats = $optimizer->getOptimizationHistory();
    echo "   Total runs: " . ($stats['total_runs'] ?? 0) . "\n";
    echo "   Average score: " . number_format($stats['avg_score'] ?? 0, 2) . "\n";
    echo "   Satisfaction rate: " . number_format($stats['avg_satisfaction_rate'] ?? 0, 1) . "%\n";
    echo "   Average time: " . number_format($stats['avg_solver_time'] ?? 0, 2) . "s\n";
    echo "\n";
    
    // Test 4: Export constraints to Python format (dry run)
    echo "4️⃣ Testing constraint export...\n";
    $bridge = new PythonConstraintBridge($db);
    $exportData = $bridge->exportConstraintsToPython();
    $export = json_decode($exportData, true);
    
    echo "   Export version: " . ($export['version'] ?? 'unknown') . "\n";
    echo "   Teams exported: " . count($export['teams'] ?? []) . "\n";
    echo "   Matches exported: " . count($export['matches'] ?? []) . "\n";
    echo "   Constraints exported: " . count($export['constraints'] ?? []) . "\n";
    echo "\n";
    
    echo "✅ Integration test completed successfully!\n";
    echo "\n📖 Next steps:\n";
    echo "   1. Run: ./setup_optimization.sh\n";
    echo "   2. Visit: http://your-domain/constraint_editor.php\n";
    echo "   3. Create constraints and run optimization\n";
    
} catch (Exception $e) {
    echo "❌ Test failed: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
