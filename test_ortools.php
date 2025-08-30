<?php
/**
 * Test OR-Tools installation on server
 */

// Test the Python environment and OR-Tools installation
function testOrToolsInstallation() {
    $command = "cd /home/httpd/vhosts/jury2025.useless.nl/httpdocs && .venv/bin/python3 -c \"from ortools.sat.python import cp_model; print('SUCCESS: OR-Tools imported successfully')\" 2>&1";
    
    $output = shell_exec($command);
    
    return [
        'success' => strpos($output, 'SUCCESS') !== false,
        'output' => trim($output),
        'command' => $command
    ];
}

$result = testOrToolsInstallation();

header('Content-Type: application/json');
echo json_encode($result);
