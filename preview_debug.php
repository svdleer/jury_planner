<?php
// Preview Optimization Debug Script
// Test the preview functionality and show detailed error information

echo "<h1>🧪 Preview Optimization Debug</h1>";

try {
    require_once __DIR__ . '/config/database.php';
    require_once __DIR__ . '/optimization_interface.php';
    
    echo "<h2>📋 Setup Test</h2>";
    $optimizer = new OptimizationInterface($db);
    echo "<p>✅ OptimizationInterface created successfully</p>";
    
    // Test Python availability
    echo "<h2>🐍 Python Availability Test</h2>";
    $availability = $optimizer->isPythonOptimizationAvailable();
    echo "<p><strong>Available:</strong> " . ($availability['available'] ? 'YES' : 'NO') . "</p>";
    if (!$availability['available']) {
        echo "<p><strong>Reason:</strong> " . $availability['reason'] . "</p>";
        echo "<p><strong>Suggestion:</strong> " . $availability['suggestion'] . "</p>";
    } else {
        echo "<p><strong>Python command:</strong> " . $availability['python_command'] . "</p>";
        echo "<p><strong>Script path:</strong> " . $availability['script_path'] . "</p>";
    }
    
    // Test preview optimization
    echo "<h2>🔍 Preview Optimization Test</h2>";
    echo "<p>Testing preview optimization with basic parameters...</p>";
    
    $previewOptions = [
        'timeout' => 30,
        'solver_type' => 'auto'
    ];
    
    echo "<p><strong>Options:</strong> " . json_encode($previewOptions) . "</p>";
    
    $result = $optimizer->previewOptimization($previewOptions);
    
    echo "<h3>📊 Result:</h3>";
    echo "<pre style='background: #f0f0f0; padding: 10px; border-radius: 5px;'>";
    echo json_encode($result, JSON_PRETTY_PRINT);
    echo "</pre>";
    
    if (!$result['success']) {
        echo "<h3>❌ Error Details:</h3>";
        echo "<p><strong>Error:</strong> " . ($result['error'] ?? 'Unknown error') . "</p>";
        echo "<p><strong>Step:</strong> " . ($result['step'] ?? 'Unknown step') . "</p>";
        
        if (isset($result['debug_info'])) {
            echo "<p><strong>Debug Info:</strong></p>";
            echo "<pre>" . json_encode($result['debug_info'], JSON_PRETTY_PRINT) . "</pre>";
        }
        
        if (isset($result['output'])) {
            echo "<p><strong>Output:</strong></p>";
            echo "<pre>" . htmlspecialchars($result['output']) . "</pre>";
        }
        
        if (isset($result['command'])) {
            echo "<p><strong>Command:</strong> " . htmlspecialchars($result['command']) . "</p>";
        }
    } else {
        echo "<h3>✅ Success!</h3>";
        if (isset($result['fallback_used']) && $result['fallback_used']) {
            echo "<p>⚠️ Used PHP fallback optimization</p>";
            echo "<p><strong>Reason:</strong> " . ($result['fallback_reason'] ?? 'Unknown') . "</p>";
        } else {
            echo "<p>✅ Used Python optimization</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<h2>❌ Exception Caught</h2>";
    echo "<p><strong>Message:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>File:</strong> " . $e->getFile() . "</p>";
    echo "<p><strong>Line:</strong> " . $e->getLine() . "</p>";
    echo "<p><strong>Trace:</strong></p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<h2>🔗 Next Steps</h2>";
echo "<ul>";
echo "<li><a href='test_python_status.php'>🐍 Check Python Status</a></li>";
echo "<li><a href='open_basedir_diagnostic.php'>🚫 Check Open Basedir</a></li>";
echo "<li><a href='venv_diagnostic.php'>🔍 Check Virtual Environment</a></li>";
echo "<li><a href='constraint_editor.php'>📝 Back to Constraint Editor</a></li>";
echo "</ul>";

?>
