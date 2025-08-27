<?php
// Database Migration for Constraint Editor
// This script creates the required tables if they don't exist

require_once 'config/database.php';

function createTableIfNotExists($db, $tableName, $createSql) {
    try {
        // Check if table exists
        $stmt = $db->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$tableName]);
        
        if ($stmt->rowCount() == 0) {
            // Table doesn't exist, create it
            $db->exec($createSql);
            echo "✅ Created table: $tableName<br>";
            return true;
        } else {
            echo "✅ Table already exists: $tableName<br>";
            return false;
        }
    } catch (PDOException $e) {
        echo "❌ Error with table $tableName: " . $e->getMessage() . "<br>";
        return false;
    }
}

function insertSampleData($db, $tableName, $insertSql, $description) {
    try {
        $db->exec($insertSql);
        echo "✅ Inserted sample data: $description<br>";
        return true;
    } catch (PDOException $e) {
        echo "⚠️  Sample data already exists or error: $description - " . $e->getMessage() . "<br>";
        return false;
    }
}

echo "<h2>🔄 Database Migration for Constraint Editor</h2>";

// Create teams table
$teamsTable = "
CREATE TABLE teams (
    id INTEGER PRIMARY KEY AUTO_INCREMENT,
    team_name VARCHAR(100) NOT NULL UNIQUE,
    weight DECIMAL(3,2) DEFAULT 1.00 NOT NULL,
    is_active BOOLEAN DEFAULT TRUE NOT NULL,
    dedicated_to_team VARCHAR(100) NULL,
    contact_person VARCHAR(100),
    email VARCHAR(100),
    phone VARCHAR(20),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_teams_active (is_active),
    INDEX idx_teams_weight (weight)
)";

$teamsCreated = createTableIfNotExists($db, 'teams', $teamsTable);

// Create planning_rules table
$planningRulesTable = "
CREATE TABLE planning_rules (
    id INTEGER PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    rule_type ENUM('forbidden', 'not_preferred', 'less_preferred', 'most_preferred') NOT NULL,
    weight DECIMAL(8,2) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE NOT NULL,
    parameters JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_rules_type (rule_type),
    INDEX idx_rules_active (is_active),
    INDEX idx_rules_weight (weight)
)";

$planningRulesCreated = createTableIfNotExists($db, 'planning_rules', $planningRulesTable);

// Create matches table if it doesn't exist
$matchesTable = "
CREATE TABLE matches (
    id INTEGER PRIMARY KEY AUTO_INCREMENT,
    date_time DATETIME NOT NULL,
    home_team VARCHAR(100) NOT NULL,
    away_team VARCHAR(100) NOT NULL,
    location VARCHAR(200),
    competition VARCHAR(100),
    round_info VARCHAR(50),
    is_locked BOOLEAN DEFAULT FALSE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_matches_date (date_time),
    INDEX idx_matches_home_team (home_team),
    INDEX idx_matches_away_team (away_team),
    INDEX idx_matches_competition (competition)
)";

$matchesCreated = createTableIfNotExists($db, 'matches', $matchesTable);

// Create jury_assignments table if it doesn't exist
$juryAssignmentsTable = "
CREATE TABLE jury_assignments (
    id INTEGER PRIMARY KEY AUTO_INCREMENT,
    match_id INTEGER NOT NULL,
    jury_team_name VARCHAR(100) NOT NULL,
    assignment_type ENUM('clock', 'score', 'general') DEFAULT 'general',
    points_awarded DECIMAL(5,2) DEFAULT 0,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    assigned_by VARCHAR(100),
    
    INDEX idx_assignments_match (match_id),
    INDEX idx_assignments_team (jury_team_name),
    INDEX idx_assignments_type (assignment_type),
    UNIQUE KEY unique_match_team (match_id, jury_team_name)
)";

createTableIfNotExists($db, 'jury_assignments', $juryAssignmentsTable);

// Insert sample data if tables were created
if ($teamsCreated) {
    $sampleTeams = "
    INSERT INTO teams (team_name, dedicated_to_team) VALUES
    ('MNC Dordrecht H1', 'MNC Dordrecht H1'),
    ('MNC Dordrecht H3', 'MNC Dordrecht H3'),
    ('MNC Dordrecht H7', 'MNC Dordrecht H7'),
    ('MNC Dordrecht Da1', 'MNC Dordrecht Da1'),
    ('MNC Dordrecht Da3', 'MNC Dordrecht Da3'),
    ('Pool Sharks', NULL),
    ('Wave Riders', NULL),
    ('Water Warriors', NULL)
    ";
    insertSampleData($db, 'teams', $sampleTeams, 'Sample teams');
}

if ($matchesCreated) {
    $sampleMatches = "
    INSERT INTO matches (date_time, home_team, away_team, location, competition) VALUES
    ('2025-09-01 10:00:00', 'MNC Dordrecht H1', 'Opponent A', 'MNC Pool Complex', 'League'),
    ('2025-09-01 11:30:00', 'MNC Dordrecht H3', 'Opponent B', 'MNC Pool Complex', 'League'),
    ('2025-09-08 14:00:00', 'Opponent C', 'MNC Dordrecht H7', 'Away Pool', 'League'),
    ('2025-09-15 16:00:00', 'MNC Dordrecht Da1', 'Opponent D', 'MNC Pool Complex', 'League')
    ";
    insertSampleData($db, 'matches', $sampleMatches, 'Sample matches');
}

echo "<br><h3>🎉 Migration completed!</h3>";
echo "<p>You can now access the constraint editor at: <a href='constraint_editor.php'>constraint_editor.php</a></p>";
?>
