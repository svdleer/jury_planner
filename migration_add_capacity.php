<?php
/**
 * Migration script to add capacity_factor column to jury_teams table
 * Safe to run multiple times - will not fail if column already exists
 */

require_once 'config/database.php';

echo "<h2>Database Migration: Adding capacity_factor column</h2>";

try {
    // Check if column exists first
    $checkSQL = "SHOW COLUMNS FROM jury_teams LIKE 'capacity_factor'";
    $stmt = $db->prepare($checkSQL);
    $stmt->execute();
    $columnExists = $stmt->fetch();
    
    if ($columnExists) {
        echo "<p style='color: blue;'>✅ Column 'capacity_factor' already exists in jury_teams table</p>";
    } else {
        // Add the column
        $sql = "ALTER TABLE jury_teams ADD COLUMN capacity_factor DECIMAL(3,2) DEFAULT 1.0 COMMENT 'Team capacity factor for assignments (1.0 = standard)'";
        $stmt = $db->prepare($sql);
        $stmt->execute();
        echo "<p style='color: green;'>✅ Successfully added capacity_factor column to jury_teams table</p>";
    }
    
    // Verify the column exists and show current data
    $verifySQL = "SELECT name, COALESCE(capacity_factor, 1.0) as capacity_factor FROM jury_teams ORDER BY name LIMIT 5";
    $stmt = $db->prepare($verifySQL);
    $stmt->execute();
    $results = $stmt->fetchAll();
    
    echo "<h3>Current team data (first 5 teams):</h3>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Team Name</th><th>Capacity Factor</th></tr>";
    foreach ($results as $row) {
        echo "<tr><td>" . htmlspecialchars($row['name']) . "</td><td>" . $row['capacity_factor'] . "</td></tr>";
    }
    echo "</table>";
    
    echo "<p style='color: green;'><strong>Migration completed successfully!</strong></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error during migration: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>This error has been handled gracefully. The system should continue to work with default capacity factors.</p>";
}
?>
