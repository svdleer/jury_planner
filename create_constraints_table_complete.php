<?php
/**
 * Create the constraints table for the optimization system
 */

require_once 'config/database.php';

try {
    global $database;
    $db = $database->getConnection();
    
    echo "<h1>🔧 Creating Constraints Table</h1>";
    echo "<style>body { font-family: Arial, sans-serif; }</style>";
    
    // Create constraints table
    $sql = "CREATE TABLE IF NOT EXISTS `constraints` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `rule_type` VARCHAR(50) NOT NULL COMMENT 'Type of constraint rule',
        `team_id` INT NULL COMMENT 'Team ID if constraint applies to specific team',
        `target_value` TEXT NULL COMMENT 'Target value or configuration for the constraint',
        `weight` DECIMAL(5,2) DEFAULT 1.0 COMMENT 'Weight/importance of this constraint',
        `is_active` BOOLEAN DEFAULT TRUE COMMENT 'Whether this constraint is active',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        `description` TEXT NULL COMMENT 'Human-readable description of the constraint',
        INDEX `idx_rule_type` (`rule_type`),
        INDEX `idx_team_id` (`team_id`),
        INDEX `idx_is_active` (`is_active`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    COMMENT='Optimization constraints and rules for jury assignment'";
    
    $db->exec($sql);
    echo "<p>✅ Successfully created constraints table</p>";
    
    // Check if table has any data
    $stmt = $db->query("SELECT COUNT(*) as count FROM constraints");
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($count == 0) {
        echo "<p>ℹ️ Constraints table is empty. Adding some default constraints...</p>";
        
        // Add some default constraints
        $defaultConstraints = [
            [
                'rule_type' => 'max_assignments_per_period',
                'team_id' => NULL,
                'target_value' => '3',
                'weight' => 1.0,
                'description' => 'Maximum assignments per team per period'
            ],
            [
                'rule_type' => 'fairness_distribution',
                'team_id' => NULL,
                'target_value' => 'balanced',
                'weight' => 1.5,
                'description' => 'Ensure fair distribution of assignments across teams'
            ],
            [
                'rule_type' => 'team_exclusion',
                'team_id' => NULL,
                'target_value' => 'check_excluded_teams',
                'weight' => 2.0,
                'description' => 'Respect team exclusions and conflicts'
            ]
        ];
        
        $insertStmt = $db->prepare("
            INSERT INTO constraints (rule_type, team_id, target_value, weight, description) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        foreach ($defaultConstraints as $constraint) {
            $insertStmt->execute([
                $constraint['rule_type'],
                $constraint['team_id'],
                $constraint['target_value'],
                $constraint['weight'],
                $constraint['description']
            ]);
        }
        
        echo "<p>✅ Added " . count($defaultConstraints) . " default constraints</p>";
    } else {
        echo "<p>ℹ️ Constraints table already has $count constraints</p>";
    }
    
    // Show current constraints
    echo "<h2>Current Constraints:</h2>";
    $stmt = $db->query("SELECT * FROM constraints ORDER BY id");
    $constraints = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($constraints)) {
        echo "<p>No constraints found</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Rule Type</th><th>Team ID</th><th>Target Value</th><th>Weight</th><th>Active</th><th>Description</th></tr>";
        
        foreach ($constraints as $constraint) {
            echo "<tr>";
            echo "<td>{$constraint['id']}</td>";
            echo "<td>{$constraint['rule_type']}</td>";
            echo "<td>{$constraint['team_id']}</td>";
            echo "<td>{$constraint['target_value']}</td>";
            echo "<td>{$constraint['weight']}</td>";
            echo "<td>" . ($constraint['is_active'] ? '✅' : '❌') . "</td>";
            echo "<td>{$constraint['description']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<p><strong>✅ Constraints table is ready!</strong></p>";
    echo "<p><a href='debug_export_data.php'>→ Go to Debug Export Data</a></p>";
    
} catch (PDOException $e) {
    echo "<h2>❌ Error</h2>";
    echo "<p>Error creating constraints table: " . $e->getMessage() . "</p>";
}
?>
