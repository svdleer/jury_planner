<?php
// Open Basedir Diagnostic Script
// Check PHP restrictions and allowed paths

echo "<h1>🚫 Open Basedir Diagnostic</h1>";

echo "<h2>📋 PHP Configuration</h2>";
echo "<p><strong>open_basedir:</strong> " . ini_get('open_basedir') . "</p>";
echo "<p><strong>Current script:</strong> " . __FILE__ . "</p>";
echo "<p><strong>Script directory:</strong> " . __DIR__ . "</p>";
echo "<p><strong>Parent directory:</strong> " . dirname(__DIR__) . "</p>";

echo "<h2>🔍 Path Testing</h2>";
$testPaths = [
    __DIR__,
    dirname(__DIR__),
    '/home/httpd/vhosts/jury2025.useless.nl/httpdocs',
    '/home/httpd/vhosts/jury2025.useless.nl',
    '/tmp'
];

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Path</th><th>Accessible</th><th>Readable</th><th>Exists</th></tr>";

foreach ($testPaths as $path) {
    echo "<tr>";
    echo "<td>$path</td>";
    
    // Test if we can access this path
    $accessible = true;
    $readable = false;
    $exists = false;
    
    try {
        $exists = file_exists($path);
        $readable = is_readable($path);
    } catch (Exception $e) {
        $accessible = false;
    }
    
    echo "<td>" . ($accessible ? "✅" : "❌") . "</td>";
    echo "<td>" . ($readable ? "✅" : "❌") . "</td>";
    echo "<td>" . ($exists ? "✅" : "❌") . "</td>";
    echo "</tr>";
}

echo "</table>";

echo "<h2>📁 Relative Path Testing</h2>";
// Test relative paths that should work within open_basedir
$relativePaths = [
    './venv',
    './planning_engine',
    './run_python_optimization.sh',
    '../venv',
    '../planning_engine', 
    '../run_python_optimization.sh'
];

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Relative Path</th><th>Resolved Path</th><th>Exists</th><th>Readable</th></tr>";

foreach ($relativePaths as $relPath) {
    $fullPath = realpath($relPath) ?: 'Cannot resolve';
    $exists = file_exists($relPath);
    $readable = is_readable($relPath);
    
    echo "<tr>";
    echo "<td>$relPath</td>";
    echo "<td>$fullPath</td>";
    echo "<td>" . ($exists ? "✅" : "❌") . "</td>";
    echo "<td>" . ($readable ? "✅" : "❌") . "</td>";
    echo "</tr>";
}

echo "</table>";

echo "<h2>🗂️ Directory Listing</h2>";
try {
    $currentDir = scandir('.');
    echo "<h3>Current Directory (.):</h3>";
    echo "<pre>" . implode("\n", $currentDir) . "</pre>";
    
    if (is_readable('..')) {
        $parentDir = scandir('..');
        echo "<h3>Parent Directory (..):</h3>";
        echo "<pre>" . implode("\n", $parentDir) . "</pre>";
    } else {
        echo "<h3>Parent Directory (..):</h3>";
        echo "<p>❌ Not readable due to open_basedir restriction</p>";
    }
} catch (Exception $e) {
    echo "<p>Error reading directories: " . $e->getMessage() . "</p>";
}

echo "<h2>🐍 Virtual Environment Search</h2>";
// Look for venv within allowed paths
$venvSearchPaths = [
    './venv/bin/python3',
    './venv/bin/python',
    './venv/bin/activate',
    '../venv/bin/python3',
    '../venv/bin/python', 
    '../venv/bin/activate'
];

foreach ($venvSearchPaths as $path) {
    $exists = file_exists($path);
    $readable = is_readable($path);
    echo "<p><strong>$path:</strong> " . ($exists ? "✅ EXISTS" : "❌ NOT FOUND") . 
         ($exists && $readable ? " (readable)" : "") . "</p>";
}

echo "<h2>💡 Recommendations</h2>";
$openBasedir = ini_get('open_basedir');
if ($openBasedir) {
    echo "<p>Open basedir is active. PHP can only access files in: <code>$openBasedir</code></p>";
    echo "<p>We need to use relative paths or ensure files are within the allowed directories.</p>";
} else {
    echo "<p>Open basedir is not restricted.</p>";
}

?>
