<?php
session_start();
require_once 'config/database.php';

echo "✅ Database connection successful<br>";
echo "✅ Basic PHP script working<br>";

try {
    $sql = "SELECT COUNT(*) FROM jury_teams";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $count = $stmt->fetchColumn();
    echo "✅ Found $count jury teams<br>";
    
    $sql = "SELECT COUNT(*) FROM excluded_teams";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $count = $stmt->fetchColumn();
    echo "✅ Found $count exclusions<br>";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}
?>
