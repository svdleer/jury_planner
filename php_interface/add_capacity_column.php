<?php
require_once 'config/database.php';

try {
    $sql = "ALTER TABLE jury_teams ADD COLUMN capacity_factor DECIMAL(3,2) DEFAULT 1.0 COMMENT 'Team capacity factor for assignments (1.0 = standard)'";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    echo "✅ Successfully added capacity_factor column to jury_teams table\n";
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "ℹ️ Column capacity_factor already exists\n";
    } else {
        echo "❌ Error: " . $e->getMessage() . "\n";
    }
}
?>
