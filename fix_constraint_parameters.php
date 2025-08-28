<?php
// Add parameters column to constraints table and fix constraint types

ini_set('display_errors', 1);
error_reporting(E_ALL);

// Load database configuration
require_once 'config/database.php';

try {
    // Use the global $db connection from database.php
    global $db;
    $connection = $db;
    
    echo "Connected to database successfully.\n";
    
    // First, let's see the current table structure
    echo "\n=== Current constraints table structure ===\n";
    $stmt = $connection->query("DESCRIBE constraints");
    while ($row = $stmt->fetch()) {
        echo "Column: {$row['Field']}, Type: {$row['Type']}, Null: {$row['Null']}, Default: {$row['Default']}\n";
    }
    
    // Add parameters column if it doesn't exist
    echo "\n=== Adding parameters column ===\n";
    try {
        $connection->exec("ALTER TABLE constraints ADD COLUMN parameters JSON DEFAULT NULL");
        echo "✓ Added parameters column to constraints table\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "ℹ Parameters column already exists\n";
        } else {
            throw $e;
        }
    }
    
    // Now let's see what we currently have
    echo "\n=== Current constraints ===\n";
    $stmt = $connection->query("SELECT id, rule_type, team_id, parameters FROM constraints");
    while ($row = $stmt->fetch()) {
        echo "ID: {$row['id']}, Rule Type: {$row['rule_type']}, Team ID: {$row['team_id']}\n";
        echo "Parameters: " . ($row['parameters'] ?: 'NULL') . "\n\n";
    }
    
    // Update constraints to have the correct constraint_type in their parameters
    echo "\n=== Updating constraint parameters and rule types ===\n";
    
    // 1. Update max_assignments_per_period to max_duties_per_period
    $updateResult = $connection->exec("
        UPDATE constraints 
        SET rule_type = 'max_duties_per_period',
            parameters = JSON_OBJECT(
                'constraint_type', 'max_duties_per_period',
                'max_duties', 3,
                'period_days', 7,
                'applies_to_all_teams', true
            )
        WHERE rule_type = 'max_assignments_per_period'
    ");
    echo "✓ Updated {$updateResult} max_assignments_per_period constraint(s) to max_duties_per_period\n";
    
    // 2. Update fairness_distribution to rest_between_matches
    $updateResult = $connection->exec("
        UPDATE constraints 
        SET rule_type = 'rest_between_matches',
            parameters = JSON_OBJECT(
                'constraint_type', 'rest_between_matches',
                'min_rest_days', 1,
                'applies_to_all_teams', true
            )
        WHERE rule_type = 'fairness_distribution'
    ");
    echo "✓ Updated {$updateResult} fairness_distribution constraint(s) to rest_between_matches\n";
    
    // 3. Update team_exclusion to a valid constraint type
    $updateResult = $connection->exec("
        UPDATE constraints 
        SET rule_type = 'avoid_consecutive_matches',
            parameters = JSON_OBJECT(
                'constraint_type', 'avoid_consecutive_matches',
                'applies_to_all_teams', true
            )
        WHERE rule_type = 'team_exclusion'
    ");
    echo "✓ Updated {$updateResult} team_exclusion constraint(s) to avoid_consecutive_matches\n";
    
    // Show updated constraints
    echo "\n=== Updated constraints ===\n";
    $stmt = $connection->query("SELECT id, rule_type, team_id, parameters FROM constraints");
    while ($row = $stmt->fetch()) {
        echo "ID: {$row['id']}, Rule Type: {$row['rule_type']}, Team ID: {$row['team_id']}\n";
        if ($row['parameters']) {
            $params = json_decode($row['parameters'], true);
            echo "Parameters: " . json_encode($params, JSON_PRETTY_PRINT) . "\n\n";
        } else {
            echo "Parameters: NULL\n\n";
        }
    }
    
    echo "Constraint types and parameters fixed successfully!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
