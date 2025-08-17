<?php
require_once 'config/database.php';

try {
    // Create excluded_teams table
    $sql = "CREATE TABLE IF NOT EXISTS excluded_teams (
        id INT AUTO_INCREMENT PRIMARY KEY,
        team_id INT NOT NULL,
        excluded_team VARCHAR(100) NOT NULL,
        reason TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (team_id) REFERENCES jury_teams(id) ON DELETE CASCADE,
        UNIQUE KEY unique_exclusion (team_id, excluded_team)
    ) COMMENT='Teams excluded from jury duty for specific matches'";
    
    $db->exec($sql);
    echo "✅ Successfully created excluded_teams table\n";
    
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'already exists') !== false) {
        echo "ℹ️ Table excluded_teams already exists\n";
    } else {
        echo "❌ Error: " . $e->getMessage() . "\n";
    }
}
?>
