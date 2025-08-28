<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Create custom_constraints table
    $sql = "CREATE TABLE IF NOT EXISTS custom_constraints (
        id INT AUTO_INCREMENT PRIMARY KEY,
        constraint_type ENUM('team_exclusion', 'team_team_conflict', 'date_restriction', 'capacity_override', 'assignment_limit') NOT NULL,
        source_team VARCHAR(255),
        target_team VARCHAR(255),
        constraint_date DATE,
        constraint_value DECIMAL(3,2),
        reason TEXT,
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_constraint_type (constraint_type),
        INDEX idx_source_team (source_team),
        INDEX idx_constraint_date (constraint_date),
        INDEX idx_is_active (is_active)
    )";
    
    $pdo->exec($sql);
    echo "✅ custom_constraints table created successfully!\n";
    
    // Add some sample constraints
    $sampleConstraints = [
        [
            'constraint_type' => 'team_team_conflict',
            'source_team' => 'AZ&PC',
            'target_team' => 'De Zaan',
            'reason' => 'Historical rivalry - teams prefer not to jury each other',
            'is_active' => 1
        ],
        [
            'constraint_type' => 'capacity_override',
            'source_team' => 'Polar Bears',
            'constraint_value' => 0.5,
            'reason' => 'Team has limited availability during August',
            'is_active' => 1
        ],
        [
            'constraint_type' => 'date_restriction',
            'source_team' => 'PSV',
            'constraint_date' => '2025-08-24',
            'reason' => 'Team tournament - unavailable for jury duty',
            'is_active' => 1
        ]
    ];
    
    $stmt = $pdo->prepare("INSERT INTO custom_constraints (constraint_type, source_team, target_team, constraint_date, constraint_value, reason, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)");
    
    foreach ($sampleConstraints as $constraint) {
        $stmt->execute([
            $constraint['constraint_type'],
            $constraint['source_team'] ?? null,
            $constraint['target_team'] ?? null,
            $constraint['constraint_date'] ?? null,
            $constraint['constraint_value'] ?? null,
            $constraint['reason'],
            $constraint['is_active']
        ]);
    }
    
    echo "✅ Sample constraints added!\n";
    echo "Custom constraints system ready!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
