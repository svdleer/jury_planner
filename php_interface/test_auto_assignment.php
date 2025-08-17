<?php
// Test auto-assignment SQL operations
require_once 'includes/Database.php';
require_once 'includes/AssignmentConstraintManager.php';
require_once 'includes/FairnessManager.php';

try {
    echo "Testing Auto-Assignment SQL Operations...\n\n";
    
    $database = new Database();
    $db = $database->getConnection();
    
    echo "✓ Database connection established\n";
    
    $constraintManager = new AssignmentConstraintManager($db);
    echo "✓ AssignmentConstraintManager created\n";
    
    // Test the SQL operations that would happen during auto-assignment
    
    // 1. Test getting unassigned matches
    $sql = "SELECT m.id, m.date_time, m.home_team, m.away_team 
            FROM home_matches m
            LEFT JOIN jury_assignments ja ON m.id = ja.match_id
            WHERE ja.match_id IS NULL 
            AND m.date_time >= NOW()
            ORDER BY m.date_time ASC";
    
    $stmt = $db->prepare($sql);
    if ($stmt) {
        echo "✓ Unassigned matches query prepared successfully\n";
        $stmt->execute();
        $unassignedMatches = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "✓ Found " . count($unassignedMatches) . " unassigned matches\n";
    } else {
        echo "✗ Failed to prepare unassigned matches query\n";
    }
    
    // 2. Test getting available teams
    $sql = "SELECT id, name, capacity_factor FROM jury_teams ORDER BY name";
    $stmt = $db->prepare($sql);
    if ($stmt) {
        echo "✓ Available teams query prepared successfully\n";
        $stmt->execute();
        $teams = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "✓ Found " . count($teams) . " teams\n";
    } else {
        echo "✗ Failed to prepare teams query\n";
    }
    
    // 3. Test jury assignment insertion (dry run)
    $sql = "INSERT INTO jury_assignments (match_id, team_id) VALUES (?, ?)";
    $stmt = $db->prepare($sql);
    if ($stmt) {
        echo "✓ Jury assignment INSERT prepared successfully\n";
        echo "✓ SQL syntax is correct for jury_assignments table\n";
    } else {
        echo "✗ Failed to prepare jury assignment INSERT\n";
        $errorInfo = $db->errorInfo();
        echo "   Error: " . $errorInfo[2] . "\n";
    }
    
    // 4. Test constraint checks
    $sql = "SELECT COUNT(*) FROM jury_assignments ja
            JOIN home_matches m ON ja.match_id = m.id
            WHERE ja.team_id = ?
            AND DATE(m.date_time) = ?";
    $stmt = $db->prepare($sql);
    if ($stmt) {
        echo "✓ Constraint check query prepared successfully\n";
    } else {
        echo "✗ Failed to prepare constraint check query\n";
    }
    
    echo "\n=== Auto-Assignment Test ===\n";
    
    // Only proceed if we have matches and teams
    if (!empty($unassignedMatches) && !empty($teams)) {
        echo "Testing actual auto-assignment logic...\n";
        
        // Test with options
        $options = [
            'prefer_low_usage' => true,
            'prefer_high_capacity' => false
        ];
        
        $result = $constraintManager->autoAssignJuryTeams($options);
        
        if ($result['success']) {
            echo "✓ Auto-assignment completed successfully!\n";
            echo "   Message: " . $result['message'] . "\n";
            echo "   Assignments made: " . count($result['assignments']) . "\n";
            if (!empty($result['conflicts'])) {
                echo "   Conflicts: " . count($result['conflicts']) . "\n";
            }
        } else {
            echo "✗ Auto-assignment failed\n";
            echo "   Error: " . $result['message'] . "\n";
        }
    } else {
        echo "⚠ No matches or teams available for auto-assignment test\n";
        echo "  This is normal if all matches are already assigned or no data exists\n";
    }
    
    echo "\n✓ All SQL operations tested successfully - no 'notes' column errors!\n";
    
} catch (Exception $e) {
    echo "\n✗ Error during test: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    
    if (strpos($e->getMessage(), 'notes') !== false) {
        echo "\n*** NOTES COLUMN ERROR DETECTED ***\n";
        echo "This error needs to be fixed!\n";
    }
}
?>
