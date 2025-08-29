<?php
try {
    $pdo = new PDO('mysql:host=vps.serial.nl;dbname=mnc_jury', 'mnc_jury', '5j51_hE9r');
    echo "✅ Database connection successful!\n";
    
    // Check teams table structure
    echo "\n📋 Teams table structure:\n";
    $stmt = $pdo->query("DESCRIBE teams");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        echo "  - {$col['Field']} ({$col['Type']})\n";
    }
    
    // Check assignments table structure
    echo "\n📋 Assignments table structure:\n";
    $stmt = $pdo->query("DESCRIBE jury_assignments");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        echo "  - {$col['Field']} ({$col['Type']})\n";
    }
    
    // Check matches table structure
    echo "\n📋 Matches table structure:\n";
    $stmt = $pdo->query("DESCRIBE matches");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        echo "  - {$col['Field']} ({$col['Type']})\n";
    }
    
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
}
?>
