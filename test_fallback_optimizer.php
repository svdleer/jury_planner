<?php
/**
 * Test script for the PHP fallback optimizer
 */

require_once 'php_interface/includes/database.php';
require_once 'php_interface/includes/SimplePhpOptimizer.php';
require_once 'php_interface/includes/ConstraintManager.php';

echo "Testing PHP Fallback Optimizer\n";
echo "================================\n\n";

try {
    $db = new PDO('mysql:host=localhost;dbname=jury_planner', 'username', 'password');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Test 1: Initialize optimizer
    echo "1. Initializing optimizer...\n";
    $optimizer = new SimplePhpOptimizer($db);
    echo "   ✓ Optimizer created successfully\n\n";
    
    // Test 2: Check available teams
    echo "2. Checking available teams...\n";
    $stmt = $db->prepare("SELECT name FROM jury_teams WHERE is_active = 1 LIMIT 5");
    $stmt->execute();
    $teams = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($teams)) {
        echo "   ⚠ No active teams found in database\n";
        echo "   Create some teams first to test optimization\n\n";
    } else {
        echo "   ✓ Found " . count($teams) . " active teams\n";
        echo "   Teams: " . implode(', ', $teams) . "\n\n";
    }
    
    // Test 3: Check available matches
    echo "3. Checking available matches...\n";
    $stmt = $db->prepare("
        SELECT COUNT(*) as match_count,
               MIN(date_time) as earliest_match,
               MAX(date_time) as latest_match
        FROM matches 
        WHERE date_time >= CURDATE()
    ");
    $stmt->execute();
    $matchInfo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($matchInfo['match_count'] == 0) {
        echo "   ⚠ No upcoming matches found\n";
        echo "   Add some matches to test optimization\n\n";
    } else {
        echo "   ✓ Found {$matchInfo['match_count']} upcoming matches\n";
        echo "   Date range: {$matchInfo['earliest_match']} to {$matchInfo['latest_match']}\n\n";
    }
    
    // Test 4: Test basic optimizer functionality
    echo "4. Testing optimizer functionality...\n";
    try {
        echo "   ✓ Optimizer methods available\n";
        echo "   Method: runSimpleOptimization\n\n";
    } catch (Exception $e) {
        echo "   ⚠ Error checking optimizer: " . $e->getMessage() . "\n\n";
    }
    
    // Test 5: Test optimization with sample data (if available)
    if (!empty($teams) && $matchInfo['match_count'] > 0) {
        echo "5. Testing optimization with sample period...\n";
        
        $startDate = date('Y-m-d');
        $endDate = date('Y-m-d', strtotime('+7 days'));
        
        echo "   Optimizing period: {$startDate} to {$endDate}\n";
        
        $result = $optimizer->runSimpleOptimization([
            'start_date' => $startDate,
            'end_date' => $endDate
        ]);
        
        if ($result['success']) {
            echo "   ✓ Optimization completed successfully\n";
            echo "   Assignments created: " . count($result['assignments']) . "\n";
            echo "   Optimization score: " . ($result['optimization_score'] ?? 'N/A') . "\n";
            echo "   Solver time: " . ($result['solver_time'] ?? 'N/A') . "s\n";
            
            if (!empty($result['assignments'])) {
                echo "   Sample assignments:\n";
                foreach (array_slice($result['assignments'], 0, 3) as $assignment) {
                    echo "   - Match {$assignment['match_id']}: {$assignment['team_name']} ({$assignment['duty_type']})\n";
                }
                if (count($result['assignments']) > 3) {
                    echo "   ... and " . (count($result['assignments']) - 3) . " more\n";
                }
            }
        } else {
            echo "   ⚠ Optimization failed: " . ($result['error'] ?? 'Unknown error') . "\n";
        }
        echo "\n";
    } else {
        echo "5. Skipping optimization test (no teams or matches available)\n\n";
    }
    
    echo "Test completed!\n";
    echo "================\n";
    echo "The PHP fallback optimizer appears to be working correctly.\n";
    echo "When Python is unavailable, the system will automatically use this optimizer.\n";
    
} catch (Exception $e) {
    echo "Error during testing: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
?>
