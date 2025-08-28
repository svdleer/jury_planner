<?php
// Fix constraint types in the database to match what the Python optimizer expects

ini_set('display_errors', 1);
error_reporting(E_ALL);

// Load database configuration
require_once 'config/database.php';

try {
    // Use the global $db connection from database.php
    global $db;
    $connection = $db;
    
    echo "Connected to database successfully.\n";
    
    // First, let's see what we currently have
    echo "\n=== Current constraints ===\n";
    $stmt = $connection->query("SELECT id, rule_type, parameters FROM constraints");
    while ($row = $stmt->fetch()) {
        echo "ID: {$row['id']}, Rule Type: {$row['rule_type']}\n";
        echo "Parameters: {$row['parameters']}\n\n";
    }
    
    // Update constraints to have the correct constraint_type in their parameters
    echo "\n=== Updating constraint parameters ===\n";
    
    // 1. Update max_assignments_per_period to max_duties_per_period
    $updateResult = $connection->exec("
        UPDATE constraints 
        SET rule_type = 'max_duties_per_period',
            parameters = JSON_SET(
                COALESCE(parameters, '{}'), 
                '$.constraint_type', 'max_duties_per_period'
            )
        WHERE rule_type = 'max_assignments_per_period'
    ");
    
    echo "✓ Updated {$updateResult} max_assignments_per_period constraint(s) to max_duties_per_period\n";
    
    // 2. Update rest_between_assignments to rest_between_matches
    $updateResult = $connection->exec("
        UPDATE constraints 
        SET rule_type = 'rest_between_matches',
            parameters = JSON_SET(
                COALESCE(parameters, '{}'), 
                '$.constraint_type', 'rest_between_matches'
            )
        WHERE rule_type = 'rest_between_assignments'
    ");
    
    echo "✓ Updated {$updateResult} rest_between_assignments constraint(s) to rest_between_matches\n";
    
    // 3. Update other constraints to have correct constraint_type in parameters
    $constraintTypeMap = [
        'team_unavailable' => 'team_unavailable',
        'dedicated_team_assignment' => 'dedicated_team_restriction',
        'preferred_duty_assignment' => 'preferred_duty',
        'avoid_duty_assignment' => 'avoid_duty',
        'preferred_dates' => 'preferred_dates',
        'avoid_dates' => 'avoid_dates',
        'avoid_opponent' => 'avoid_opponent',
        'avoid_consecutive_matches' => 'avoid_consecutive_matches'
    ];
    
    foreach ($constraintTypeMap as $ruleType => $constraintType) {
        $updateResult = $connection->exec("
            UPDATE constraints 
            SET parameters = JSON_SET(
                COALESCE(parameters, '{}'), 
                '$.constraint_type', '{$constraintType}'
            )
            WHERE rule_type = '{$ruleType}'
        ");
        
        echo "✓ Updated {$updateResult} {$ruleType} constraint(s) with constraint_type {$constraintType}\n";
    }
    
    // Show updated constraints
    echo "\n=== Updated constraints ===\n";
    $stmt = $connection->query("SELECT id, rule_type, parameters FROM constraints");
    while ($row = $stmt->fetch()) {
        echo "ID: {$row['id']}, Rule Type: {$row['rule_type']}\n";
        $params = json_decode($row['parameters'], true);
        echo "Parameters: " . json_encode($params, JSON_PRETTY_PRINT) . "\n\n";
    }
    
    echo "Constraint types fixed successfully!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
