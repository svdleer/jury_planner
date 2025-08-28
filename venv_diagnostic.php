<?php
// Detailed Virtual Environment Diagnostic Script
// This script will show exactly where it's looking and what it finds

echo "<h1>🔍 Virtual Environment Diagnostic</h1>";

// Show current environment
echo "<h2>📍 Current Environment</h2>";
echo "<p><strong>Script location:</strong> " . __FILE__ . "</p>";
echo "<p><strong>Script directory (__DIR__):</strong> " . __DIR__ . "</p>";
echo "<p><strong>Parent directory:</strong> " . dirname(__DIR__) . "</p>";
echo "<p><strong>Current working directory:</strong> " . getcwd() . "</p>";

// Define all possible venv locations
$venvLocations = [
    __DIR__ . '/venv',
    dirname(__DIR__) . '/venv',
    '/home/httpd/vhosts/jury2025.useless.nl/httpdocs/venv'
];

echo "<h2>🔍 Checking Virtual Environment Locations</h2>";
echo "<table border='1' cellpadding='5' cellspacing='0'>";
echo "<tr><th>Location</th><th>Directory Exists</th><th>Python3 Executable</th><th>Activate Script</th><th>Contents</th></tr>";

foreach ($venvLocations as $location) {
    echo "<tr>";
    echo "<td>" . $location . "</td>";
    
    // Check if directory exists
    $dirExists = is_dir($location);
    echo "<td>" . ($dirExists ? "✅ YES" : "❌ NO") . "</td>";
    
    // Check for python3 executable
    $python3Path = $location . '/bin/python3';
    $python3Exists = file_exists($python3Path);
    echo "<td>" . ($python3Exists ? "✅ YES" : "❌ NO") . "</td>";
    
    // Check for activate script
    $activatePath = $location . '/bin/activate';
    $activateExists = file_exists($activatePath);
    echo "<td>" . ($activateExists ? "✅ YES" : "❌ NO") . "</td>";
    
    // Show directory contents if it exists
    if ($dirExists) {
        $contents = scandir($location);
        $contents = array_diff($contents, ['.', '..']);
        echo "<td>" . implode(', ', $contents) . "</td>";
    } else {
        echo "<td>N/A</td>";
    }
    
    echo "</tr>";
}

echo "</table>";

// Check what the optimization interface is actually detecting
echo "<h2>🧪 Optimization Interface Test</h2>";
try {
    require_once __DIR__ . '/config/database.php';
    require_once __DIR__ . '/optimization_interface.php';
    
    $optimizer = new OptimizationInterface($db);
    $availability = $optimizer->isPythonOptimizationAvailable();
    
    echo "<p><strong>Available:</strong> " . ($availability['available'] ? 'YES' : 'NO') . "</p>";
    if (!$availability['available']) {
        echo "<p><strong>Reason:</strong> " . $availability['reason'] . "</p>";
        echo "<p><strong>Suggestion:</strong> " . $availability['suggestion'] . "</p>";
    } else {
        echo "<p><strong>Python command:</strong> " . $availability['python_command'] . "</p>";
        echo "<p><strong>Script path:</strong> " . $availability['script_path'] . "</p>";
    }
} catch (Exception $e) {
    echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
}

// Manual shell check
echo "<h2>🐚 Manual Shell Checks</h2>";
if (function_exists('shell_exec')) {
    echo "<h3>Finding Virtual Environments:</h3>";
    $findCmd = "find /home/httpd/vhosts/jury2025.useless.nl/ -name 'python3' -type f 2>/dev/null | grep venv";
    $result = shell_exec($findCmd);
    echo "<pre>Command: $findCmd\nResult:\n" . ($result ?: "No venv python3 found") . "</pre>";
    
    echo "<h3>Directory Listings:</h3>";
    foreach ([dirname(__DIR__), '/home/httpd/vhosts/jury2025.useless.nl/httpdocs'] as $dir) {
        $lsCmd = "ls -la $dir 2>/dev/null | grep venv";
        $result = shell_exec($lsCmd);
        echo "<pre>Directory: $dir\nCommand: $lsCmd\nResult:\n" . ($result ?: "No venv directory found") . "</pre>";
    }
}

echo "<h2>📋 Summary</h2>";
echo "<p>This diagnostic should help identify exactly where the virtual environment is and why it might not be detected.</p>";
?>
