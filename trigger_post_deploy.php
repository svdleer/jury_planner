<?php
// Manual Post-Deploy Trigger
// This script allows you to manually trigger the post-deployment setup via browser

// Security check - only allow from localhost or specific IPs if needed
$allowed_ips = ['127.0.0.1', '::1']; // Add your IP if needed
// Uncomment next line if you want IP restriction
// if (!in_array($_SERVER['REMOTE_ADDR'], $allowed_ips)) die('Access denied');

echo "<h1>🚀 Manual Post-Deploy Trigger</h1>";
echo "<p>This will run the post-deployment script manually.</p>";

if (isset($_GET['run'])) {
    echo "<h2>📋 Execution Log:</h2>";
    echo "<pre style='background: #f0f0f0; padding: 10px; border-radius: 5px;'>";
    
    $script_path = __DIR__ . '/post_deploy.sh';
    
    if (!file_exists($script_path)) {
        echo "❌ Post-deploy script not found: $script_path\n";
        echo "Current directory: " . __DIR__ . "\n";
        echo "Files in directory: " . implode(', ', scandir(__DIR__)) . "\n";
    } else {
        echo "✅ Found post-deploy script: $script_path\n";
        echo "Making script executable...\n";
        chmod($script_path, 0755);
        
        echo "Executing post-deploy script...\n";
        echo "=================================\n";
        
        // Change to the script directory and run it
        $old_dir = getcwd();
        chdir(__DIR__);
        
        $output = shell_exec("bash ./post_deploy.sh 2>&1");
        echo $output;
        
        chdir($old_dir);
        
        echo "\n=================================\n";
        echo "Post-deploy script execution completed.\n";
    }
    
    echo "</pre>";
    
    echo "<h2>🔄 Next Steps:</h2>";
    echo "<ul>";
    echo "<li><a href='test_python_status.php'>🐍 Check Python Status</a></li>";
    echo "<li><a href='deployment_status.json'>📊 View Deployment Status</a></li>";
    echo "<li><a href='debug_file_structure.php'>🔍 Check File Structure</a></li>";
    echo "</ul>";
    
} else {
    echo "<p><strong>⚠️ WARNING:</strong> This will run the post-deployment setup script which will:</p>";
    echo "<ul>";
    echo "<li>Set file permissions</li>";
    echo "<li>Create Python virtual environment</li>";
    echo "<li>Install required packages</li>";
    echo "<li>Test the optimization engine</li>";
    echo "</ul>";
    
    echo "<p><a href='?run=1' style='background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🚀 Run Post-Deploy Script</a></p>";
    
    echo "<h2>📋 Current Status:</h2>";
    echo "<ul>";
    echo "<li>Script directory: " . __DIR__ . "</li>";
    echo "<li>Post-deploy script exists: " . (file_exists(__DIR__ . '/post_deploy.sh') ? '✅ YES' : '❌ NO') . "</li>";
    echo "<li>Virtual environment exists: " . (is_dir(__DIR__ . '/venv') ? '✅ YES' : '❌ NO') . "</li>";
    echo "<li>Deployment status exists: " . (file_exists(__DIR__ . '/deployment_status.json') ? '✅ YES' : '❌ NO') . "</li>";
    echo "</ul>";
}
?>
