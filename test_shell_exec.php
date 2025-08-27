<?php
echo "<h2>Shell Exec Test</h2>\n";

// Test 1: Check if function exists
if (function_exists('shell_exec')) {
    echo "✓ shell_exec() function is available<br>\n";
    
    // Test 2: Try to execute a simple command
    $output = shell_exec('echo "Hello from shell_exec"');
    if ($output) {
        echo "✓ shell_exec() is working: " . trim($output) . "<br>\n";
    } else {
        echo "⚠ shell_exec() exists but may be restricted<br>\n";
    }
    
    // Test 3: Check Python availability
    $pythonCheck = shell_exec('which python3 2>/dev/null');
    if ($pythonCheck) {
        echo "✓ Python3 is available at: " . trim($pythonCheck) . "<br>\n";
    } else {
        echo "⚠ Python3 not found in PATH<br>\n";
    }
    
} else {
    echo "❌ shell_exec() function is disabled<br>\n";
}

// Show current disabled functions
$disabled = ini_get('disable_functions');
echo "<br><strong>Currently disabled functions:</strong><br>\n";
echo $disabled ? $disabled : "None";
?>
