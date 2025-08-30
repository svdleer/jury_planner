<?php
require_once 'config/database.php';

/**
 * Debug script to check what data is available for autoplanning
 */

echo "<h2>🔍 Autoplan Data Debug</h2>\n";

try {
    // Check teams
    $teamsStmt = $pdo->prepare("SELECT COUNT(*) as total_teams, COUNT(CASE WHEN is_active = 1 THEN 1 END) as active_teams FROM teams");
    $teamsStmt->execute();
    $teamStats = $teamsStmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<h3>📊 Teams</h3>\n";
    echo "<p>Total teams: {$teamStats['total_teams']}</p>\n";
    echo "<p>Active teams: {$teamStats['active_teams']}</p>\n";
    
    if ($teamStats['active_teams'] > 0) {
        $teamsStmt = $pdo->prepare("SELECT id, team_name, is_active FROM teams WHERE is_active = 1 LIMIT 5");
        $teamsStmt->execute();
        $teams = $teamsStmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h4>Sample active teams:</h4>\n";
        echo "<ul>\n";
        foreach ($teams as $team) {
            echo "<li>ID: {$team['id']}, Name: {$team['team_name']}</li>\n";
        }
        echo "</ul>\n";
    }
    
    // Check matches
    $matchesStmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_matches,
            COUNT(CASE WHEN date_time >= CURDATE() THEN 1 END) as future_matches,
            COUNT(CASE WHEN date_time >= CURDATE() AND date_time <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 1 END) as next_30_days,
            MIN(date_time) as earliest_match,
            MAX(date_time) as latest_match
        FROM matches
    ");
    $matchesStmt->execute();
    $matchStats = $matchesStmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<h3>⚽ Matches</h3>\n";
    echo "<p>Total matches: {$matchStats['total_matches']}</p>\n";
    echo "<p>Future matches: {$matchStats['future_matches']}</p>\n";
    echo "<p>Matches in next 30 days: {$matchStats['next_30_days']}</p>\n";
    echo "<p>Earliest match: {$matchStats['earliest_match']}</p>\n";
    echo "<p>Latest match: {$matchStats['latest_match']}</p>\n";
    echo "<p>Current date: " . date('Y-m-d H:i:s') . "</p>\n";
    
    if ($matchStats['next_30_days'] > 0) {
        $upcomingStmt = $pdo->prepare("
            SELECT id, date_time, home_team, away_team, location
            FROM matches 
            WHERE date_time >= CURDATE() AND date_time <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
            ORDER BY date_time 
            LIMIT 5
        ");
        $upcomingStmt->execute();
        $upcoming = $upcomingStmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h4>Sample upcoming matches:</h4>\n";
        echo "<ul>\n";
        foreach ($upcoming as $match) {
            echo "<li>ID: {$match['id']}, {$match['date_time']}: {$match['home_team']} vs {$match['away_team']} @ {$match['location']}</li>\n";
        }
        echo "</ul>\n";
    }
    
    // Check constraints
    $constraintsStmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_constraints,
            COUNT(CASE WHEN is_active = 1 THEN 1 END) as active_constraints
        FROM constraints
    ");
    $constraintsStmt->execute();
    $constraintStats = $constraintsStmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<h3>🚫 Constraints</h3>\n";
    echo "<p>Total constraints: {$constraintStats['total_constraints']}</p>\n";
    echo "<p>Active constraints: {$constraintStats['active_constraints']}</p>\n";
    
    // Summary
    echo "<h3>📝 Summary</h3>\n";
    if ($teamStats['active_teams'] == 0) {
        echo "<p>❌ <strong>No active teams</strong> - Add teams and set them as active</p>\n";
    }
    
    if ($matchStats['next_30_days'] == 0) {
        echo "<p>❌ <strong>No upcoming matches</strong> - Add matches for the next 30 days</p>\n";
    }
    
    if ($teamStats['active_teams'] > 0 && $matchStats['next_30_days'] > 0) {
        echo "<p>✅ <strong>Data looks good</strong> - Should be able to generate assignments</p>\n";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Error checking data: " . htmlspecialchars($e->getMessage()) . "</p>\n";
}
?>
