<?php
// Manual Python Virtual Environment Setup Trigger
// This script can be run via web browser to manually set up the Python venv

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🐍 Python Virtual Environment Setup</h1>";
echo "<p>This script will manually create the Python virtual environment.</p>";

if (isset($_GET['run']) && $_GET['run'] === 'setup') {
    echo "<h2>🔧 Setting up Virtual Environment...</h2>";
    echo "<pre>";
    
    // Make scripts executable
    chmod('setup_python_venv.sh', 0755);
    chmod('run_python_optimization.sh', 0755);
    echo "✅ Made scripts executable\n";
    
    // Check if venv already exists
    if (is_dir('venv')) {
        echo "⚠️  Virtual environment already exists, removing old one...\n";
        exec('rm -rf venv 2>&1', $output, $returnCode);
        foreach ($output as $line) {
            echo "   $line\n";
        }
    }
    
    // Run the setup script
    echo "\n🚀 Running setup_python_venv.sh...\n";
    exec('./setup_python_venv.sh 2>&1', $output, $returnCode);
    
    foreach ($output as $line) {
        echo "$line\n";
    }
    
    echo "\n📊 Setup completed with return code: $returnCode\n";
    
    if ($returnCode === 0) {
        echo "\n✅ Virtual environment setup successful!\n";
        echo "\n🧪 Testing Python optimization availability...\n";
        
        // Test the optimization interface
        try {
            require_once 'config/database.php';
            require_once 'optimization_interface.php';
            
            $optimizer = new OptimizationInterface($db);
            $availability = $optimizer->isPythonOptimizationAvailable();
            
            if ($availability['available']) {
                echo "🎉 Python optimization engine is now AVAILABLE!\n";
                echo "Python command: " . $availability['python_command'] . "\n";
            } else {
                echo "❌ Python optimization still not available:\n";
                echo "Reason: " . $availability['reason'] . "\n";
            }
        } catch (Exception $e) {
            echo "❌ Error testing optimization: " . $e->getMessage() . "\n";
        }
    } else {
        echo "\n❌ Virtual environment setup failed!\n";
        echo "Please check the error messages above.\n";
    }
    
    echo "</pre>";
    echo "<p><a href='?'>← Back</a> | <a href='test_python_status.php'>Test Status</a> | <a href='constraint_editor.php'>Constraint Editor</a></p>";
    
} else {
    echo "<h2>📋 Current Status</h2>";
    echo "<ul>";
    echo "<li>Wrapper script exists: " . (file_exists('run_python_optimization.sh') ? '✅ YES' : '❌ NO') . "</li>";
    echo "<li>Setup script exists: " . (file_exists('setup_python_venv.sh') ? '✅ YES' : '❌ NO') . "</li>";
    echo "<li>Virtual environment exists: " . (is_dir('venv') ? '✅ YES' : '❌ NO') . "</li>";
    echo "<li>Python script exists: " . (file_exists('planning_engine/enhanced_optimizer.py') ? '✅ YES' : '❌ NO') . "</li>";
    echo "</ul>";
    
    echo "<h2>🚀 Manual Setup</h2>";
    echo "<p><strong>⚠️ Warning:</strong> This will create a Python virtual environment and install required packages. This may take a few minutes.</p>";
    echo "<p><a href='?run=setup' class='button' style='background: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🔧 Run Python Setup</a></p>";
    
    echo "<h2>🔗 Quick Links</h2>";
    echo "<ul>";
    echo "<li><a href='test_python_status.php'>🧪 Test Python Status</a></li>";
    echo "<li><a href='constraint_editor.php'>⚙️ Constraint Editor</a></li>";
    echo "<li><a href='mnc_dashboard.php'>📊 Dashboard</a></li>";
    echo "</ul>";
}
?>
