<?php
require_once 'config/database.php';

/**
 * Debug script to check what data is available for autoplanning
 */

echo "<h2>🔍 Autoplan Data Debug</h2>\n";

// Check environment variables
echo "<h3>🔧 Environment Check</h3>\n";
echo "<p>.env file exists: " . (file_exists(__DIR__ . '/.env') ? 'Yes' : 'No') . "</p>\n";
echo "<p>DB_HOST: " . ($_ENV['DB_HOST'] ?? 'Not set') . "</p>\n";
echo "<p>DB_USER: " . ($_ENV['DB_USER'] ?? 'Not set') . "</p>\n";
echo "<p>DB_NAME: " . ($_ENV['DB_NAME'] ?? 'Not set') . "</p>\n";
echo "<p>DB_PASSWORD: " . (isset($_ENV['DB_PASSWORD']) ? '[SET]' : 'Not set') . "</p>\n";

// Check if database instance exists
if (!isset($database)) {
    echo "<p>❌ No database instance available</p>\n";
    exit;
}

// Check if PDO connection exists  
if (!isset($pdo)) {
    // Try to get it from the database instance or global $db
    if (isset($db)) {
        $pdo = $db;
        echo "<p>✅ Using global \$db connection</p>\n";
    } else {
        try {
            $pdo = $database->getConnection();
            echo "<p>✅ Database connection retrieved from instance</p>\n";
        } catch (Exception $e) {
            echo "<p>❌ Database connection failed: " . htmlspecialchars($e->getMessage()) . "</p>\n";
            exit;
        }
    }
} else {
    echo "<p>✅ PDO connection available</p>\n";
}

echo "<p>✅ Database connection established</p>\n";

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
    
    // Check matches - look at ALL matches first
    $matchesStmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_matches,
            COUNT(CASE WHEN date_time >= CURDATE() THEN 1 END) as future_matches,
            COUNT(CASE WHEN date_time >= CURDATE() AND date_time <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 1 END) as next_30_days,
            COUNT(CASE WHEN date_time >= CURDATE() AND date_time <= DATE_ADD(CURDATE(), INTERVAL 365 DAY) THEN 1 END) as next_year,
            MIN(date_time) as earliest_match,
            MAX(date_time) as latest_match,
            COUNT(CASE WHEN date_time < CURDATE() THEN 1 END) as past_matches
        FROM matches
    ");
    $matchesStmt->execute();
    $matchStats = $matchesStmt->fetch(PDO::FETCH_ASSOC);
    
    // Also check other match tables
    $otherMatchTables = ['all_matches', 'home_matches'];
    $otherMatchData = [];
    
    foreach ($otherMatchTables as $tableName) {
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM {$tableName}");
            $stmt->execute();
            $otherMatchData[$tableName] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        } catch (Exception $e) {
            $otherMatchData[$tableName] = "Error: " . $e->getMessage();
        }
    }
    
    // Check home_matches specifically for upcoming matches (what autoplanner should use)
    try {
        $homeMatchesStmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_home_matches,
                COUNT(CASE WHEN date_time >= CURDATE() THEN 1 END) as future_home_matches,
                COUNT(CASE WHEN date_time >= CURDATE() AND date_time <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 1 END) as next_30_days_home,
                MIN(date_time) as earliest_home_match,
                MAX(date_time) as latest_home_match
            FROM home_matches
        ");
        $homeMatchesStmt->execute();
        $homeMatchStats = $homeMatchesStmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $homeMatchStats = null;
    }
    
    echo "<h3>⚽ Matches</h3>\n";
    echo "<p><strong>Main 'matches' table:</strong></p>\n";
    echo "<p>Total matches: {$matchStats['total_matches']}</p>\n";
    echo "<p>Past matches: {$matchStats['past_matches']}</p>\n";
    echo "<p>Future matches: {$matchStats['future_matches']}</p>\n";
    echo "<p>Matches in next 30 days: {$matchStats['next_30_days']}</p>\n";
    echo "<p>Matches in next year: {$matchStats['next_year']}</p>\n";
    echo "<p>Earliest match: {$matchStats['earliest_match']}</p>\n";
    echo "<p>Latest match: {$matchStats['latest_match']}</p>\n";
    echo "<p>Current date: " . date('Y-m-d H:i:s') . "</p>\n";
    echo "<p>Search range: " . date('Y-m-d') . " to " . date('Y-m-d', strtotime('+30 days')) . "</p>\n";
    
    // Show other match tables
    echo "<p><strong>Other match tables:</strong></p>\n";
    foreach ($otherMatchData as $table => $count) {
        echo "<p>{$table}: {$count} records</p>\n";
    }
    
    // Show home_matches analysis (what autoplanner should use)
    if ($homeMatchStats) {
        echo "<p><strong>HOME MATCHES Analysis (for jury assignments):</strong></p>\n";
        echo "<p>Total home matches: {$homeMatchStats['total_home_matches']}</p>\n";
        echo "<p>Future home matches: {$homeMatchStats['future_home_matches']}</p>\n";
        echo "<p>Home matches in next 30 days: {$homeMatchStats['next_30_days_home']}</p>\n";
        echo "<p>Earliest home match: {$homeMatchStats['earliest_home_match']}</p>\n";
        echo "<p>Latest home match: {$homeMatchStats['latest_home_match']}</p>\n";
        
        if ($homeMatchStats['next_30_days_home'] > 0) {
            echo "<p>✅ <strong>FOUND {$homeMatchStats['next_30_days_home']} upcoming home matches!</strong></p>\n";
            
            // Show sample upcoming home matches
            $sampleHomeStmt = $pdo->prepare("
                SELECT id, date_time, home_team, away_team, location
                FROM home_matches 
                WHERE date_time >= CURDATE() AND date_time <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
                ORDER BY date_time 
                LIMIT 5
            ");
            $sampleHomeStmt->execute();
            $sampleHomeMatches = $sampleHomeStmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<h4>Sample upcoming home matches (next 30 days):</h4>\n";
            echo "<ul>\n";
            foreach ($sampleHomeMatches as $match) {
                echo "<li style='color: green;'>ID: {$match['id']}, {$match['date_time']}: {$match['home_team']} vs {$match['away_team']} @ {$match['location']}</li>\n";
            }
            echo "</ul>\n";
        }
    }
    
    if ($matchStats['total_matches'] > 0 && $matchStats['next_30_days'] == 0) {
        echo "<p>⚠️ <strong>Matches exist but none in next 30 days!</strong> The autoplanner only looks 30 days ahead.</p>\n";
        
        // Show some sample matches to understand the dates
        $sampleStmt = $pdo->prepare("
            SELECT id, date_time, home_team, away_team, location
            FROM matches 
            ORDER BY date_time 
            LIMIT 10
        ");
        $sampleStmt->execute();
        $sampleMatches = $sampleStmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h4>Sample matches (showing actual dates):</h4>\n";
        echo "<ul>\n";
        foreach ($sampleMatches as $match) {
            $dateClass = strtotime($match['date_time']) < time() ? 'style="color: red;"' : 
                        (strtotime($match['date_time']) <= strtotime('+30 days') ? 'style="color: green;"' : 'style="color: orange;"');
            echo "<li $dateClass>ID: {$match['id']}, {$match['date_time']}: {$match['home_team']} vs {$match['away_team']} @ {$match['location']}</li>\n";
        }
        echo "</ul>\n";
        echo "<p><span style='color: red;'>Red = Past</span>, <span style='color: green;'>Green = Next 30 days</span>, <span style='color: orange;'>Orange = Future (>30 days)</span></p>\n";
    }
    
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
        if ($matchStats['total_matches'] == 0) {
            echo "<p>❌ <strong>No matches in 'matches' table</strong> - But this is OK, autoplanner should use 'home_matches'</p>\n";
        } else {
            echo "<p>⚠️ <strong>No upcoming matches in 'matches' table in next 30 days</strong> - But autoplanner should use 'home_matches'</p>\n";
        }
    }
    
    if ($homeMatchStats && $homeMatchStats['next_30_days_home'] > 0) {
        echo "<p>✅ <strong>PERFECT!</strong> Found {$homeMatchStats['next_30_days_home']} upcoming home matches that need jury assignments</p>\n";
        echo "<p>� <strong>ACTION:</strong> Autoplanner should now work correctly after fixing to use 'home_matches' table</p>\n";
    } elseif ($homeMatchStats && $homeMatchStats['total_home_matches'] > 0) {
        echo "<p>⚠️ <strong>Home matches exist but none in next 30 days</strong> - Check if matches are scheduled further out</p>\n";
    } else {
        echo "<p>❌ <strong>No home matches found</strong> - These are needed for jury assignments</p>\n";
    }
    
    if ($teamStats['active_teams'] > 0 && $homeMatchStats && $homeMatchStats['next_30_days_home'] > 0) {
        echo "<p>🎉 <strong>Ready for autoplanning!</strong> Teams: {$teamStats['active_teams']}, Home matches: {$homeMatchStats['next_30_days_home']}</p>\n";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Error checking data: " . htmlspecialchars($e->getMessage()) . "</p>\n";
    echo "<p>Stack trace: " . htmlspecialchars($e->getTraceAsString()) . "</p>\n";
}

// Also check if the tables exist
try {
    echo "<h3>🗄️ Database Tables</h3>\n";
    $tablesStmt = $pdo->query("SHOW TABLES");
    $tables = $tablesStmt->fetchAll(PDO::FETCH_COLUMN);
    echo "<p>Available tables: " . implode(', ', $tables) . "</p>\n";
    
    $requiredTables = ['teams', 'matches', 'constraints'];
    foreach ($requiredTables as $table) {
        if (in_array($table, $tables)) {
            echo "<p>✅ Table '$table' exists</p>\n";
        } else {
            echo "<p>❌ Table '$table' missing</p>\n";
        }
    }
} catch (Exception $e) {
    echo "<p>❌ Error checking tables: " . htmlspecialchars($e->getMessage()) . "</p>\n";
}
?>
