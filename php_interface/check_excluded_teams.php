<?php
require_once 'config/database.php';

try {
    $sql = "DESCRIBE excluded_teams";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>excluded_teams table structure:</h3>";
    foreach ($columns as $column) {
        echo "- " . $column['Field'] . " (" . $column['Type'] . ")<br>";
    }
    
    echo "<br><h3>Sample data:</h3>";
    $sql = "SELECT * FROM excluded_teams LIMIT 3";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($data as $row) {
        echo json_encode($row) . "<br>";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
