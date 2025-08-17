<?php
require_once 'config/database.php';

try {
    $pdo = Database::getInstance()->getConnection();
    
    // Check match assignment status
    $sql = "SELECT 
                COUNT(*) as total_matches,
                SUM(CASE WHEN ja.match_id IS NOT NULL THEN 1 ELSE 0 END) as assigned_matches,
                SUM(CASE WHEN ja.match_id IS NULL THEN 1 ELSE 0 END) as unassigned_matches
            FROM home_matches m
            LEFT JOIN jury_assignments ja ON m.id = ja.match_id
            WHERE m.date_time >= CURDATE()";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<h3>Match Assignment Status:</h3>";
    echo "Total upcoming matches: " . $stats['total_matches'] . "<br>";
    echo "Assigned matches: " . $stats['assigned_matches'] . "<br>";
    echo "Unassigned matches: " . $stats['unassigned_matches'] . "<br><br>";
    
    // Check excluded teams
    $stmt = $pdo->query("SELECT COUNT(*) FROM excluded_teams");
    $excludedCount = $stmt->fetchColumn();
    echo "Excluded teams: " . $excludedCount . "<br><br>";
    
    // Check jury teams
    $stmt = $pdo->query("SELECT COUNT(*) FROM jury_teams");
    $juryTeamCount = $stmt->fetchColumn();
    echo "Total jury teams: " . $juryTeamCount . "<br>";
    
    echo "<h3>System Status: All working!</h3>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
