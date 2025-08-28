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

// Show current working directory and paths
$output .= "Current script directory: " . __DIR__ . "\n";
$output .= "Current working directory: " . getcwd() . "\n";

// Check for deployment status
$statusFiles = [
    __DIR__ . '/deployment_status.json',
    dirname(__DIR__) . '/deployment_status.json'
];

foreach ($statusFiles as $statusFile) {
    if (file_exists($statusFile)) {
        $output .= "Deployment status file: " . $statusFile . "\n";
        $statusContent = file_get_contents($statusFile);
        $status = json_decode($statusContent, true);
        if ($status) {
            $output .= "Last deployment: " . ($status['deployment_time'] ?? 'unknown') . "\n";
            $output .= "Python available: " . ($status['python_available'] ?? 'unknown') . "\n";
            $output .= "Venv created: " . ($status['venv_created'] ?? 'unknown') . "\n";
            $output .= "Post-deploy executed: " . ($status['post_deploy_script'] ?? 'unknown') . "\n";
        }
        break;
    }
}

// Check if shell_exec is available
$output .= "shell_exec available: " . (function_exists('shell_exec') ? 'YES' : 'NO') . "\n";
$output .= "exec available: " . (function_exists('exec') ? 'YES' : 'NO') . "\n";

// Check if wrapper script exists (check multiple locations)
$wrapperLocations = [
    __DIR__ . '/run_python_optimization.sh',
    dirname(__DIR__) . '/run_python_optimization.sh'
];

$wrapperFound = false;
foreach ($wrapperLocations as $wrapperScript) {
    if (file_exists($wrapperScript)) {
        $wrapperFound = true;
        $output .= "Wrapper script exists: YES - " . $wrapperScript . "\n";
        $output .= "Wrapper script executable: " . (is_executable($wrapperScript) ? 'YES' : 'NO') . "\n";
        break;
    }
}

if (!$wrapperFound) {
    $output .= "Wrapper script exists: NO (checked multiple locations)\n";
}

// Check if venv exists (check multiple possible locations)
$venvLocations = [
    __DIR__ . '/venv/bin/python3',
    __DIR__ . '/venv/bin/python',
    dirname(__DIR__) . '/venv/bin/python3',  // In case we're in php_interface
    dirname(__DIR__) . '/venv/bin/python'
];

$venvFound = false;
$venvPath = '';
foreach ($venvLocations as $location) {
    if (file_exists($location)) {
        $venvFound = true;
        $venvPath = $location;
        break;
    }
}

$output .= "Virtual environment exists: " . ($venvFound ? 'YES' : 'NO') . "\n";
if ($venvFound) {
    $output .= "Virtual environment path: " . $venvPath . "\n";
    
    // Test if venv is working
    if (function_exists('shell_exec')) {
        $venvTest = shell_exec("$venvPath --version 2>&1");
        $output .= "Virtual environment test: " . ($venvTest ? trim($venvTest) : 'FAILED') . "\n";
    }
}

// Check if venv directory exists
$venvDirs = [
    __DIR__ . '/venv',
    dirname(__DIR__) . '/venv'
];
foreach ($venvDirs as $dir) {
    if (is_dir($dir)) {
        $output .= "Virtual environment directory: " . $dir . "\n";
        $files = scandir($dir);
        $output .= "Venv contents: " . implode(', ', array_diff($files, ['.', '..'])) . "\n";
        break;
    }
}

// Check if Python script exists (check multiple locations)
$scriptLocations = [
    __DIR__ . '/planning_engine/enhanced_optimizer.py',
    dirname(__DIR__) . '/planning_engine/enhanced_optimizer.py'
];

$scriptFound = false;
$scriptPath = '';
foreach ($scriptLocations as $location) {
    if (file_exists($location)) {
        $scriptFound = true;
        $scriptPath = $location;
        break;
    }
}

$output .= "Python script exists: " . ($scriptFound ? 'YES' : 'NO') . "\n";
if ($scriptFound) {
    $output .= "Python script path: " . $scriptPath . "\n";
}

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
