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
    
    // Check multiple possible locations for the post-deploy script
    $possible_locations = [
        __DIR__ . '/post_deploy.sh',                    // Same directory as this script
        dirname(__DIR__) . '/post_deploy.sh',           // Parent directory (httpdocs root)
        '/home/httpd/vhosts/jury2025.useless.nl/httpdocs/post_deploy.sh'  // Absolute path
    ];
    
    $script_path = null;
    $script_dir = null;
    
    foreach ($possible_locations as $location) {
        if (file_exists($location)) {
            $script_path = $location;
            $script_dir = dirname($location);
            break;
        }
    }
    
    if (!$script_path) {
        echo "❌ Post-deploy script not found in any of these locations:\n";
        foreach ($possible_locations as $location) {
            echo "   - $location\n";
        }
        echo "Current directory: " . __DIR__ . "\n";
        echo "Parent directory: " . dirname(__DIR__) . "\n";
        echo "Files in current directory: " . implode(', ', scandir(__DIR__)) . "\n";
        if (is_dir(dirname(__DIR__))) {
            echo "Files in parent directory: " . implode(', ', scandir(dirname(__DIR__))) . "\n";
        }
    } else {
        echo "✅ Found post-deploy script: $script_path\n";
        echo "Script directory: $script_dir\n";
        echo "Making script executable...\n";
        chmod($script_path, 0755);
        
        echo "Executing post-deploy script...\n";
        echo "=================================\n";
        
        // Change to the script directory and run it
        $old_dir = getcwd();
        chdir($script_dir);
        
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
    echo "<li><a href='deployment_status.json'>📊 View Deployment Status (current dir)</a></li>";
    echo "<li><a href='../deployment_status.json'>📊 View Deployment Status (parent dir)</a></li>";
    echo "<li><a href='debug_file_structure.php'>🔍 Check File Structure</a></li>";
    echo "<li><a href='../index.php'>🏠 Main Application (parent dir)</a></li>";
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
    echo "<li>Current script directory: " . __DIR__ . "</li>";
    echo "<li>Parent directory (httpdocs root): " . dirname(__DIR__) . "</li>";
    
    // Check for post-deploy script in multiple locations
    $script_locations = [
        __DIR__ . '/post_deploy.sh',
        dirname(__DIR__) . '/post_deploy.sh',
        '/home/httpd/vhosts/jury2025.useless.nl/httpdocs/post_deploy.sh'
    ];
    
    $script_found = false;
    foreach ($script_locations as $location) {
        if (file_exists($location)) {
            echo "<li>Post-deploy script found: ✅ $location</li>";
            $script_found = true;
            break;
        }
    }
    if (!$script_found) {
        echo "<li>Post-deploy script: ❌ NOT FOUND in any location</li>";
    }
    
    // Check for virtual environment in multiple locations
    $venv_locations = [
        __DIR__ . '/venv',
        dirname(__DIR__) . '/venv',
        '/home/httpd/vhosts/jury2025.useless.nl/httpdocs/venv'
    ];
    
    $venv_found = false;
    foreach ($venv_locations as $location) {
        if (is_dir($location)) {
            echo "<li>Virtual environment found: ✅ $location</li>";
            $venv_found = true;
            break;
        }
    }
    if (!$venv_found) {
        echo "<li>Virtual environment: ❌ NOT FOUND in any location</li>";
    }
    
    // Check for deployment status in multiple locations
    $status_locations = [
        __DIR__ . '/deployment_status.json',
        dirname(__DIR__) . '/deployment_status.json',
        '/home/httpd/vhosts/jury2025.useless.nl/httpdocs/deployment_status.json'
    ];
    
    $status_found = false;
    foreach ($status_locations as $location) {
        if (file_exists($location)) {
            echo "<li>Deployment status found: ✅ $location</li>";
            $status_found = true;
            break;
        }
    }
    if (!$status_found) {
        echo "<li>Deployment status: ❌ NOT FOUND in any location</li>";
    }
    
    echo "</ul>";
}
?>
