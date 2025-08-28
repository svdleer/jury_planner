<?php
// Deployment Test - Trigger Post-Deploy Script
// Generated at: <?= date('Y-m-d H:i:s') ?>

echo "<h1>🚀 Deployment Test Successful!</h1>";
echo "<p>✅ Files are deployed to httpdocs root</p>";
echo "<p>📅 Deployment time: " . date('Y-m-d H:i:s') . "</p>";
echo "<p>📁 Current directory: " . __DIR__ . "</p>";

// Check if post-deploy script ran
if (file_exists(__DIR__ . '/deployment_status.json')) {
    echo "<p>✅ Post-deployment script executed</p>";
    $status = json_decode(file_get_contents(__DIR__ . '/deployment_status.json'), true);
    echo "<pre>" . json_encode($status, JSON_PRETTY_PRINT) . "</pre>";
} else {
    echo "<p>⚠️  Post-deployment script not yet executed</p>";
}

// Check Python environment
if (is_dir(__DIR__ . '/venv')) {
    echo "<p>✅ Python virtual environment created</p>";
} else {
    echo "<p>⚠️  Python virtual environment not found</p>";
}

echo "<hr>";
echo "<p><a href='debug_file_structure.php'>🔍 Check File Structure</a></p>";
echo "<p><a href='test_python_status.php'>🐍 Test Python Status</a></p>";
echo "<p><a href='index.php'>🏠 Main Application</a></p>";
?>
