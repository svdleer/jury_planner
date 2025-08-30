<?php
/**
 * Test Python executable detection for autoplanner service
 */

require_once 'includes/PurePythonAutoplannerService.php';

// Test the Python executable detection
function testPythonExecutableDetection() {
    $service = new PurePythonAutoplannerService();
    
    // Use reflection to access the private method
    $reflection = new ReflectionClass($service);
    $method = $reflection->getMethod('findPythonExecutable');
    $method->setAccessible(true);
    
    $pythonPath = $method->invoke($service);
    
    // Test if OR-Tools can be imported with this Python executable
    $testCommand = escapeshellarg($pythonPath) . " -c \"from ortools.sat.python import cp_model; print('SUCCESS')\" 2>&1";
    $output = shell_exec($testCommand);
    
    return [
        'python_executable' => $pythonPath,
        'ortools_test' => [
            'command' => $testCommand,
            'output' => trim($output),
            'success' => strpos($output, 'SUCCESS') !== false
        ]
    ];
}

$result = testPythonExecutableDetection();

header('Content-Type: application/json');
echo json_encode($result, JSON_PRETTY_PRINT);
