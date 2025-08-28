<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';
require_once 'optimization_interface.php';

try {
    $optimizationInterface = new OptimizationInterface($db);
    $pythonAvailability = $optimizationInterface->isPythonOptimizationAvailable();
    
    echo "<h2>Python Availability Check Result:</h2>";
    echo "<pre>";
    print_r($pythonAvailability);
    echo "</pre>";
    
    echo "<h2>Raw Debug Info:</h2>";
    echo "Available: " . ($pythonAvailability['available'] ? 'TRUE' : 'FALSE') . "<br>";
    
    if (!$pythonAvailability['available']) {
        echo "Reason: " . htmlspecialchars($pythonAvailability['reason'] ?? 'No reason provided') . "<br>";
        echo "Suggestion: " . htmlspecialchars($pythonAvailability['suggestion'] ?? 'No suggestion provided') . "<br>";
    }
    
} catch (Exception $e) {
    echo "<h2>Error occurred:</h2>";
    echo "<pre>";
    echo htmlspecialchars($e->getMessage());
    echo "\n\nStack trace:\n";
    echo htmlspecialchars($e->getTraceAsString());
    echo "</pre>";
}
?>
