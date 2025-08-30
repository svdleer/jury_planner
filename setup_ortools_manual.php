<?php
/**
 * Manual OR-Tools installation trigger
 */

// Try to manually run the setup script
function triggerOrToolsSetup() {
    $commands = [
        // Check if venv exists
        "ls -la /home/httpd/vhosts/jury2025.useless.nl/httpdocs/.venv/ 2>&1",
        
        // Check Python version
        "python3 --version 2>&1",
        
        // Try to run the setup script
        "cd /home/httpd/vhosts/jury2025.useless.nl/httpdocs && bash setup_python.sh 2>&1",
    ];
    
    $results = [];
    
    foreach ($commands as $i => $command) {
        $output = shell_exec($command);
        $results["command_$i"] = [
            'command' => $command,
            'output' => $output
        ];
    }
    
    return $results;
}

$result = triggerOrToolsSetup();

header('Content-Type: application/json');
echo json_encode($result, JSON_PRETTY_PRINT);
