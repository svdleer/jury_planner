<?php
require_once 'config/database.php';
require_once 'optimization_interface.php';

// Test Python optimization availability
// $db is already created in database.php
$optimizer = new OptimizationInterface($db);

echo "=== Python Optimization Engine Status Test ===\n\n";

$availability = $optimizer->isPythonOptimizationAvailable();

echo "Available: " . ($availability['available'] ? 'YES' : 'NO') . "\n";

if (!$availability['available']) {
    echo "Reason: " . $availability['reason'] . "\n";
    echo "Suggestion: " . $availability['suggestion'] . "\n";
} else {
    echo "Python Command: " . $availability['python_command'] . "\n";
    echo "Script Path: " . $availability['script_path'] . "\n";
}

echo "\n=== Additional Diagnostics ===\n";

// Check if shell_exec is available
echo "shell_exec available: " . (function_exists('shell_exec') ? 'YES' : 'NO') . "\n";
echo "exec available: " . (function_exists('exec') ? 'YES' : 'NO') . "\n";

// Check if wrapper script exists
$wrapperScript = __DIR__ . '/../run_python_optimization.sh';
echo "Wrapper script exists: " . (file_exists($wrapperScript) ? 'YES' : 'NO') . "\n";
if (file_exists($wrapperScript)) {
    echo "Wrapper script executable: " . (is_executable($wrapperScript) ? 'YES' : 'NO') . "\n";
}

// Check if venv exists
$venvPython = __DIR__ . '/../venv/bin/python3';
echo "Virtual environment exists: " . (file_exists($venvPython) ? 'YES' : 'NO') . "\n";

// Check if Python script exists
$pythonScript = __DIR__ . '/../planning_engine/enhanced_optimizer.py';
echo "Python script exists: " . (file_exists($pythonScript) ? 'YES' : 'NO') . "\n";

// Test simple Python command
if (function_exists('shell_exec')) {
    echo "\n=== Python Version Test ===\n";
    $pythonVersion = shell_exec('python3 --version 2>&1');
    echo "Python3 version: " . ($pythonVersion ? trim($pythonVersion) : 'NOT FOUND') . "\n";
    
    $whichPython = shell_exec('which python3 2>&1');
    echo "Python3 location: " . ($whichPython ? trim($whichPython) : 'NOT FOUND') . "\n";
}

echo "\n=== Test Complete ===\n";
?>
