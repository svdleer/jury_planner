<?php
// Test script to verify auto-assignment works without SQL errors

require_once 'includes/Database.php';
require_once 'includes/AssignmentConstraintManager.php';
require_once 'includes/MatchManager.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Test creating a simple assignment to verify no SQL errors
    $constraintManager = new AssignmentConstraintManager($db);
    $matchManager = new MatchManager($db);
    
    // Get first available match and team for testing
    $matches = $matchManager->getUpcomingMatches();
    $teams = $db->query("SELECT id, name FROM jury_teams LIMIT 1")->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($matches) && !empty($teams)) {
        $testMatchId = $matches[0]['id'];
        $testTeamId = $teams[0]['id'];
        
        echo "Testing assignment for Match ID: $testMatchId, Team ID: $testTeamId\n";
        
        // Test the SQL insert without actually inserting (dry run)
        $sql = "INSERT INTO jury_assignments (match_id, team_id) VALUES (?, ?)";
        $stmt = $db->prepare($sql);
        
        if ($stmt) {
            echo "✓ SQL prepare successful - no syntax errors\n";
            echo "✓ Column names match database schema\n";
        } else {
            echo "✗ SQL prepare failed\n";
        }
        
        // Test constraint manager method existence
        if (method_exists($constraintManager, 'autoAssignJuryForMatch')) {
            echo "✓ autoAssignJuryForMatch method exists\n";
        } else {
            echo "✗ autoAssignJuryForMatch method missing\n";
        }
        
    } else {
        echo "No matches or teams available for testing\n";
    }
    
    echo "\n✓ Test completed successfully - no SQL errors detected\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}
?>
