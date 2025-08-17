<?php
require_once 'config/database.php';

try {
    $pdo = Database::getConnection();
    
    echo "<h3>All data in excluded_teams table:</h3>";
    $stmt = $pdo->query("SELECT * FROM excluded_teams");
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<pre>";
    print_r($results);
    echo "</pre>";
    
    echo "<h3>Count: " . count($results) . "</h3>";
    
    // Check if this relates to jury_teams
    echo "<h3>Jury teams for reference:</h3>";
    $stmt = $pdo->query("SELECT id, name FROM jury_teams ORDER BY name");
    $teams = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<pre>";
    print_r($teams);
    echo "</pre>";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
