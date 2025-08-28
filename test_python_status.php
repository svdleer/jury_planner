<?php
require_once 'config/database.php';
require_once 'optimization_interface.php';

// Test Python optimization availability
// $db is already created in database.php
$optimizer = new OptimizationInterface($db);

$output = "=== Python Optimization Engine Status Test ===\n\n";

$availability = $optimizer->isPythonOptimizationAvailable();

$output .= "Available: " . ($availability['available'] ? 'YES' : 'NO') . "\n";

if (!$availability['available']) {
    $output .= "Reason: " . $availability['reason'] . "\n";
    $output .= "Suggestion: " . $availability['suggestion'] . "\n";
} else {
    $output .= "Python Command: " . $availability['python_command'] . "\n";
    $output .= "Script Path: " . $availability['script_path'] . "\n";
}

$output .= "\n=== Additional Diagnostics ===\n";

// Check if shell_exec is available
$output .= "shell_exec available: " . (function_exists('shell_exec') ? 'YES' : 'NO') . "\n";
$output .= "exec available: " . (function_exists('exec') ? 'YES' : 'NO') . "\n";

// Check if wrapper script exists
$wrapperScript = __DIR__ . '/run_python_optimization.sh';
$output .= "Wrapper script exists: " . (file_exists($wrapperScript) ? 'YES' : 'NO') . "\n";
if (file_exists($wrapperScript)) {
    $output .= "Wrapper script executable: " . (is_executable($wrapperScript) ? 'YES' : 'NO') . "\n";
}

// Check if venv exists
$venvPython = __DIR__ . '/venv/bin/python3';
$output .= "Virtual environment exists: " . (file_exists($venvPython) ? 'YES' : 'NO') . "\n";

// Check if Python script exists
$pythonScript = __DIR__ . '/planning_engine/enhanced_optimizer.py';
$output .= "Python script exists: " . (file_exists($pythonScript) ? 'YES' : 'NO') . "\n";

// Test simple Python command
if (function_exists('shell_exec')) {
    $output .= "\n=== Python Version Test ===\n";
    $pythonVersion = shell_exec('python3 --version 2>&1');
    $output .= "Python3 version: " . ($pythonVersion ? trim($pythonVersion) : 'NOT FOUND') . "\n";
    
    $whichPython = shell_exec('which python3 2>&1');
    $output .= "Python3 location: " . ($whichPython ? trim($whichPython) : 'NOT FOUND') . "\n";
}

$output .= "\n=== Test Complete ===\n";

// Output as both text and HTML
header('Content-Type: text/plain');
echo $output;

// Also save to file for debugging
file_put_contents('/tmp/python_test_results.txt', $output);
?>
