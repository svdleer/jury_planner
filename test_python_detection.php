<?php
// Simple test of Python detection without full initialization

function isPythonAvailable() {
    if (!function_exists('shell_exec')) {
        return ['available' => false, 'reason' => 'shell_exec disabled'];
    }
    
    // Check wrapper script
    $wrapperScript = __DIR__ . '/run_python_optimization.sh';
    if (file_exists($wrapperScript)) {
        $output = shell_exec("cd " . escapeshellarg(__DIR__) . " && ./run_python_optimization.sh -c 'import sys; print(sys.version)' 2>&1");
        if ($output && strpos($output, 'Error:') === false) {
            return [
                'available' => true,
                'method' => 'wrapper_script',
                'python_version' => trim($output)
            ];
        }
    }
    
    // Check venv python
    $venvPython = __DIR__ . '/venv/bin/python3';
    if (file_exists($venvPython)) {
        $output = shell_exec("$venvPython -c 'import sys; print(sys.version)' 2>&1");
        if ($output && strpos($output, 'Error:') === false) {
            return [
                'available' => true, 
                'method' => 'venv_direct',
                'python_version' => trim($output)
            ];
        }
    }
    
    return ['available' => false, 'reason' => 'No Python found'];
}

$result = isPythonAvailable();
echo "Python Detection Test:\n";
print_r($result);
?>
