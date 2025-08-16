<?php
/**
 * Database Connection Test Script
 * Use this to verify the database connection is working properly
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Database Connection Test</h1>\n";
echo "<p>Testing connection to: vps.serial.nl</p>\n";

try {
    // Include the database configuration
    require_once 'config/database.php';
    
    echo "<div style='color: green;'>✓ Database connection successful!</div>\n";
    
    // Test basic query
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM teams");
    $stmt->execute();
    $result = $stmt->fetch();
    
    echo "<div style='color: green;'>✓ Teams table accessible</div>\n";
    echo "<p>Found " . $result['count'] . " teams in database</p>\n";
    
    // Test matches table
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM matches");
    $stmt->execute();
    $result = $stmt->fetch();
    
    echo "<div style='color: green;'>✓ Matches table accessible</div>\n";
    echo "<p>Found " . $result['count'] . " matches in database</p>\n";
    
    // Test jury assignments table
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM jury_assignments");
    $stmt->execute();
    $result = $stmt->fetch();
    
    echo "<div style='color: green;'>✓ Jury assignments table accessible</div>\n";
    echo "<p>Found " . $result['count'] . " jury assignments in database</p>\n";
    
    echo "<h2 style='color: green;'>All tests passed!</h2>\n";
    echo "<p>You can now safely use the PHP interface.</p>\n";
    echo "<p><a href='index.php'>Go to Dashboard</a></p>\n";
    
} catch (Exception $e) {
    echo "<div style='color: red; background: #ffe6e6; padding: 10px; border: 1px solid red;'>\n";
    echo "<strong>Database Connection Failed:</strong><br>\n";
    echo htmlspecialchars($e->getMessage()) . "\n";
    echo "</div>\n";
    
    echo "<h3>Troubleshooting:</h3>\n";
    echo "<ul>\n";
    echo "<li>Check that the database server (vps.serial.nl) is accessible</li>\n";
    echo "<li>Verify the database credentials are correct</li>\n";
    echo "<li>Ensure the database schema has been created</li>\n";
    echo "<li>Check firewall settings allow connections to port 3306</li>\n";
    echo "</ul>\n";
}

echo "<hr>\n";
echo "<p><em>Database: mnc_jury @ vps.serial.nl</em></p>\n";
echo "<p><em>Test run at: " . date('Y-m-d H:i:s') . "</em></p>\n";
?>
