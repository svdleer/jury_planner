<?php
// Direct database test to identify the exact source of the notes column error
require_once 'includes/Database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "=== Debugging Notes Column Error ===\n\n";
    
    // First, let's see the actual jury_assignments table structure
    echo "1. Checking jury_assignments table structure:\n";
    $stmt = $db->query("DESCRIBE jury_assignments");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Columns in jury_assignments:\n";
    foreach ($columns as $col) {
        echo "  - " . $col['Field'] . " (" . $col['Type'] . ")\n";
    }
    
    echo "\n2. Testing simple INSERT (this should work):\n";
    $sql = "INSERT INTO jury_assignments (match_id, team_id) VALUES (?, ?)";
    $stmt = $db->prepare($sql);
    if ($stmt) {
        echo "✓ Basic INSERT prepared successfully\n";
    } else {
        echo "✗ Basic INSERT failed\n";
        print_r($db->errorInfo());
    }
    
    echo "\n3. Testing INSERT with notes (this should fail):\n";
    $sql_bad = "INSERT INTO jury_assignments (match_id, team_id, notes) VALUES (?, ?, ?)";
    $stmt_bad = $db->prepare($sql_bad);
    if ($stmt_bad) {
        echo "✗ Bad INSERT prepared (this is wrong!)\n";
    } else {
        echo "✓ Bad INSERT failed as expected\n";
        $error = $db->errorInfo();
        echo "   Error: " . $error[2] . "\n";
    }
    
    echo "\n4. Checking which files might still reference notes in jury_assignments:\n";
    // This would be where the error trace would help us identify the source
    
    echo "\n=== Test Complete ===\n";
    echo "If you're still getting the notes error, it means:\n";
    echo "1. The deployed code hasn't been updated yet, or\n";
    echo "2. There's a cached version somewhere, or\n";
    echo "3. Some code is still using an old method\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    if (strpos($e->getMessage(), 'notes') !== false) {
        echo "\n*** FOUND THE NOTES ERROR! ***\n";
        echo "Stack trace:\n";
        echo $e->getTraceAsString() . "\n";
    }
}
?>
