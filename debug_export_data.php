<?php
/**
 * Debug script to check what data is being exported to Python optimizer
 * This will help diagnose why 0 assignments are imported
 */

// Include necessary files
require_once 'config/database.php';
require_once 'includes/ConstraintManager.php';
require_once 'includes/PythonConstraintBridge.php';

try {
    // Use the global database instance from config/database.php
    global $database;
    $db = $database->getConnection();
    
    echo "<h1>🔍 Python Export Data Debug</h1>";
    echo "<style>body { font-family: Arial, sans-serif; } pre { background: #f5f5f5; padding: 10px; border-radius: 5px; }</style>";
    
    // Check database connection
    echo "<h2>Database Connection</h2>";
    if ($db) {
        echo "<p>✅ Database connected successfully</p>";
    } else {
        echo "<p>❌ Database connection failed</p>";
        exit;
    }
    
    // Check matches table
    echo "<h2>Matches Table</h2>";
    try {
        $stmt = $db->query("SELECT COUNT(*) as total FROM matches");
        $totalMatches = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        echo "<p>Total matches in database: <strong>$totalMatches</strong></p>";
        
        // Check future matches
        $stmt = $db->query("SELECT COUNT(*) as future FROM matches WHERE date_time >= CURDATE()");
        $futureMatches = $stmt->fetch(PDO::FETCH_ASSOC)['future'];
        echo "<p>Future matches (date_time >= CURDATE()): <strong>$futureMatches</strong></p>";
        
        // Check today's matches
        $stmt = $db->query("SELECT COUNT(*) as today FROM matches WHERE DATE(date_time) = CURDATE()");
        $todayMatches = $stmt->fetch(PDO::FETCH_ASSOC)['today'];
        echo "<p>Today's matches: <strong>$todayMatches</strong></p>";
        
        // Show first few matches
        echo "<h3>Sample Matches:</h3>";
        $stmt = $db->query("SELECT id, date_time, home_team, away_team FROM matches ORDER BY date_time LIMIT 5");
        $sampleMatches = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<pre>" . print_r($sampleMatches, true) . "</pre>";
        
        // Show date range
        $stmt = $db->query("SELECT MIN(date_time) as earliest, MAX(date_time) as latest FROM matches");
        $dateRange = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p>Date range: <strong>{$dateRange['earliest']}</strong> to <strong>{$dateRange['latest']}</strong></p>";
        echo "<p>Current date: <strong>" . date('Y-m-d H:i:s') . "</strong></p>";
        
    } catch (PDOException $e) {
        echo "<p>❌ Error querying matches: " . $e->getMessage() . "</p>";
    }
    
    // Check teams table
    echo "<h2>Teams Table</h2>";
    try {
        $stmt = $db->query("SELECT COUNT(*) as total FROM teams");
        $totalTeams = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        echo "<p>Total teams in database: <strong>$totalTeams</strong></p>";
        
        // Show first few teams
        echo "<h3>Sample Teams:</h3>";
        $stmt = $db->query("SELECT id, team_name, is_active FROM teams LIMIT 5");
        $sampleTeams = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<pre>" . print_r($sampleTeams, true) . "</pre>";
        
    } catch (PDOException $e) {
        echo "<p>❌ Error querying teams: " . $e->getMessage() . "</p>";
    }
    
    // Check constraints table
    echo "<h2>Constraints Table</h2>";
    try {
        $stmt = $db->query("SELECT COUNT(*) as total FROM constraints");
        $totalConstraints = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        echo "<p>Total constraints in database: <strong>$totalConstraints</strong></p>";
        
        // Show first few constraints
        echo "<h3>Sample Constraints:</h3>";
        $stmt = $db->query("SELECT id, rule_type, team_id, is_active FROM constraints LIMIT 5");
        $sampleConstraints = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<pre>" . print_r($sampleConstraints, true) . "</pre>";
        
    } catch (PDOException $e) {
        echo "<p>❌ Error querying constraints table: " . $e->getMessage() . "</p>";
        echo "<p>🔧 The constraints table might not exist. <a href='create_constraints_table_complete.php'>Click here to create it</a></p>";
    }
    
    // Test Python export
    echo "<h2>Python Export Data</h2>";
    try {
        $bridge = new PythonConstraintBridge($database);
        $exportJson = $bridge->exportConstraintsToPython();
        $exportData = json_decode($exportJson, true);
        
        if ($exportData) {
            echo "<p>✅ Export successful</p>";
            echo "<p>Teams exported: <strong>" . count($exportData['teams']) . "</strong></p>";
            echo "<p>Matches exported: <strong>" . count($exportData['matches']) . "</strong></p>";
            echo "<p>Constraints exported: <strong>" . count($exportData['constraints']) . "</strong></p>";
            
            if (count($exportData['matches']) > 0) {
                echo "<h3>Sample Matches:</h3>";
                echo "<pre>" . print_r(array_slice($exportData['matches'], 0, 3), true) . "</pre>";
            }
            
            if (count($exportData['teams']) > 0) {
                echo "<h3>Sample Teams:</h3>";
                echo "<pre>" . print_r(array_slice($exportData['teams'], 0, 5), true) . "</pre>";
            }
            
            echo "<h3>Full Export Summary:</h3>";
            echo "<pre>" . json_encode([
                'version' => $exportData['version'],
                'exported_at' => $exportData['exported_at'],
                'teams_count' => count($exportData['teams']),
                'matches_count' => count($exportData['matches']),
                'constraints_count' => count($exportData['constraints']),
                'weight_multipliers' => $exportData['weight_multipliers']
            ], JSON_PRETTY_PRINT) . "</pre>";
        } else {
            echo "<p>❌ Export failed - invalid JSON</p>";
            echo "<pre>Raw export: $exportJson</pre>";
        }
        
    } catch (Exception $e) {
        echo "<p>❌ Error during Python export: " . $e->getMessage() . "</p>";
        echo "<p>This might be due to missing tables. Check that all database tables exist.</p>";
    }
    
} catch (Exception $e) {
    echo "<h2>❌ Fatal Error</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>
