<?php
// Debug script to check actual file structure on server

echo "<h1>🔍 File Structure Debug</h1>";
echo "<h2>Current Directory (__DIR__):</h2>";
echo "<pre>" . __DIR__ . "</pre>";

echo "<h2>Files in Current Directory:</h2>";
echo "<pre>";
$files = scandir(__DIR__);
foreach ($files as $file) {
    if ($file !== '.' && $file !== '..') {
        $fullPath = __DIR__ . '/' . $file;
        $type = is_dir($fullPath) ? '[DIR]' : '[FILE]';
        $size = is_file($fullPath) ? ' (' . filesize($fullPath) . ' bytes)' : '';
        echo "$type $file$size\n";
    }
}
echo "</pre>";

echo "<h2>Checking Specific Python Files:</h2>";
echo "<ul>";

$filesToCheck = [
    'setup_python_venv.sh',
    'run_python_optimization.sh',
    'planning_engine/enhanced_optimizer.py',
    'venv/bin/python3',
    'venv/bin/activate'
];

foreach ($filesToCheck as $file) {
    $fullPath = __DIR__ . '/' . $file;
    $exists = file_exists($fullPath);
    $status = $exists ? '✅ EXISTS' : '❌ NOT FOUND';
    
    if ($exists && is_file($fullPath)) {
        $size = filesize($fullPath);
        $status .= " ($size bytes)";
    }
    
    echo "<li><strong>$file:</strong> $status</li>";
}

echo "</ul>";

echo "<h2>Planning Engine Directory Contents:</h2>";
$planningDir = __DIR__ . '/planning_engine';
if (is_dir($planningDir)) {
    echo "<pre>";
    $planningFiles = scandir($planningDir);
    foreach ($planningFiles as $file) {
        if ($file !== '.' && $file !== '..') {
            echo "$file\n";
        }
    }
    echo "</pre>";
} else {
    echo "<p>❌ Planning engine directory not found</p>";
}

echo "<h2>Python Command Detection:</h2>";
echo "<pre>";
try {
    require_once 'config/database.php';
    require_once 'optimization_interface.php';
    
    $optimizer = new OptimizationInterface($db);
    $availability = $optimizer->isPythonOptimizationAvailable();
    
    echo "Available: " . ($availability['available'] ? 'YES' : 'NO') . "\n";
    if (!$availability['available']) {
        echo "Reason: " . $availability['reason'] . "\n";
        echo "Suggestion: " . $availability['suggestion'] . "\n";
    } else {
        echo "Python Command: " . $availability['python_command'] . "\n";
        echo "Script Path: " . $availability['script_path'] . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
echo "</pre>";
?>
