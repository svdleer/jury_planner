<?php
/**
 * Pure Python Autoplanning Service Interface
 * Clean API layer between PHP frontend and Python OR-Tools optimizer
 */

class PurePythonAutoplannerService {
    private string $pythonExecutable;
    private string $scriptPath;
    private string $tempDir;
    private int $timeoutSeconds;
    
    public function __construct(
        ?string $pythonExecutable = null, 
        ?string $scriptPath = null,
        int $timeoutSeconds = 600
    ) {
        $this->pythonExecutable = $pythonExecutable ?? $this->findPythonExecutable();
        $this->scriptPath = $scriptPath ?? __DIR__ . '/../planning_engine/pure_autoplanner.py';
        $this->tempDir = sys_get_temp_dir();
        $this->timeoutSeconds = $timeoutSeconds;
    }
    
    /**
     * Generate optimal jury assignments using pure Python OR-Tools
     */
    public function generateAutoplan(
        array $teams,
        array $matches,
        array $constraints,
        array $config = []
    ): array {
        try {
            // Prepare optimization request
            $request = $this->buildOptimizationRequest($teams, $matches, $constraints, $config);
            
            // Create temporary files for communication
            $inputFile = $this->createTempFile('autoplanner_input_', '.json');
            $outputFile = $this->createTempFile('autoplanner_output_', '.json');
            
            // Write request to input file
            file_put_contents($inputFile, json_encode($request, JSON_PRETTY_PRINT));
            
            // Build and execute Python command
            $command = $this->buildPythonCommand($inputFile, $outputFile, $config);
            $output = $this->executePythonScript($command);
            
            // Read and parse results
            $result = $this->parseOptimizationResult($outputFile);
            
            // Cleanup
            $this->cleanupTempFiles([$inputFile, $outputFile]);
            
            return [
                'success' => true,
                'assignments' => $result['assignments'] ?? [],
                'metadata' => $result['metadata'] ?? [],
                'solver_info' => [
                    'objective_value' => $result['objective_value'] ?? 0,
                    'constraints_satisfied' => $result['constraints_satisfied'] ?? 0,
                    'total_constraints' => $result['total_constraints'] ?? 0,
                    'solver_time_seconds' => $result['solver_time_seconds'] ?? 0,
                    'solver_status' => $result['solver_status'] ?? 'UNKNOWN'
                ],
                'python_output' => $output
            ];
            
        } catch (Exception $e) {
            error_log("Python Autoplanner Error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'assignments' => []
            ];
        }
    }
    
    /**
     * Build optimization request in Python-expected format
     */
    private function buildOptimizationRequest(
        array $teams,
        array $matches,
        array $constraints,
        array $config
    ): array {
        return [
            'teams' => $this->formatTeamsForPython($teams),
            'matches' => $this->formatMatchesForPython($matches),
            'constraints' => $this->formatConstraintsForPython($constraints),
            'solver_config' => $config['solver_config'] ?? [],
            'time_limit_seconds' => $config['time_limit_seconds'] ?? 300
        ];
    }
    
    /**
     * Format teams data for Python
     */
    private function formatTeamsForPython(array $teams): array {
        $formatted = [];
        foreach ($teams as $team) {
            $formatted[] = [
                'id' => (int)$team['id'],
                'name' => (string)$team['name'],
                'capacity_weight' => (float)($team['capacity_weight'] ?? 1.0),
                'is_active' => (bool)($team['is_active'] ?? true),
                'dedicated_to_team' => $team['dedicated_to_team'] ?? null
            ];
        }
        return $formatted;
    }
    
    /**
     * Format matches data for Python
     */
    private function formatMatchesForPython(array $matches): array {
        $formatted = [];
        foreach ($matches as $match) {
            $formatted[] = [
                'id' => (int)$match['id'],
                'date_time' => (string)$match['date_time'],
                'home_team' => (string)$match['home_team'],
                'away_team' => (string)$match['away_team'],
                'location' => (string)$match['location'],
                'competition' => (string)$match['competition'],
                'required_duties' => $this->formatDutiesForPython($match['required_duties'] ?? []),
                'importance_multiplier' => (float)($match['importance_multiplier'] ?? 1.0),
                'is_locked' => (bool)($match['is_locked'] ?? false)
            ];
        }
        return $formatted;
    }
    
    /**
     * Format duties data for Python
     */
    private function formatDutiesForPython(array $duties): array {
        $formatted = [];
        foreach ($duties as $duty) {
            $formatted[] = [
                'type' => (string)$duty['type'],
                'count' => (int)($duty['count'] ?? 1),
                'weight' => (float)($duty['weight'] ?? 1.0)
            ];
        }
        return $formatted;
    }
    
    /**
     * Format constraints data for Python
     */
    private function formatConstraintsForPython(array $constraints): array {
        $formatted = [];
        foreach ($constraints as $constraint) {
            $parameters = $constraint['parameters'] ?? [];
            
            // Decode JSON parameters if needed
            if (is_string($parameters)) {
                $parameters = json_decode($parameters, true) ?? [];
            }
            
            $formatted[] = [
                'id' => (int)$constraint['id'],
                'name' => (string)$constraint['name'],
                'constraint_type' => (string)$constraint['constraint_type'],
                'rule_type' => (string)($constraint['rule_type'] ?? 'forbidden'),
                'weight' => (float)($constraint['weight'] ?? 1.0),
                'parameters' => $parameters,
                'is_active' => (bool)($constraint['is_active'] ?? true)
            ];
        }
        return $formatted;
    }
    
    /**
     * Build Python command with proper arguments
     */
    private function buildPythonCommand(
        string $inputFile,
        string $outputFile,
        array $config
    ): string {
        $args = [
            escapeshellarg($this->pythonExecutable),
            escapeshellarg($this->scriptPath),
            '--input', escapeshellarg($inputFile),
            '--output', escapeshellarg($outputFile)
        ];
        
        // Add solver type if specified
        if (!empty($config['solver_type'])) {
            $args[] = '--solver';
            $args[] = escapeshellarg($config['solver_type']);
        }
        
        // Add time limit if specified
        if (!empty($config['time_limit_seconds'])) {
            $args[] = '--time-limit';
            $args[] = escapeshellarg((string)$config['time_limit_seconds']);
        }
        
        // Add verbose flag if needed
        if (!empty($config['verbose'])) {
            $args[] = '--verbose';
        }
        
        return implode(' ', $args);
    }
    
    /**
     * Execute Python script with timeout
     */
    private function executePythonScript(string $command): array {
        $descriptorspec = [
            0 => ["pipe", "r"],  // stdin
            1 => ["pipe", "w"],  // stdout
            2 => ["pipe", "w"]   // stderr
        ];
        
        $process = proc_open($command, $descriptorspec, $pipes);
        
        if (!is_resource($process)) {
            throw new Exception("Failed to start Python process");
        }
        
        // Close stdin
        fclose($pipes[0]);
        
        // Set timeout
        $timeout = time() + $this->timeoutSeconds;
        
        // Read output with timeout
        $stdout = '';
        $stderr = '';
        
        while (time() < $timeout) {
            $status = proc_get_status($process);
            if (!$status['running']) {
                break;
            }
            usleep(100000); // 0.1 second
        }
        
        // Read remaining output
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        
        fclose($pipes[1]);
        fclose($pipes[2]);
        
        $returnCode = proc_close($process);
        
        if ($returnCode !== 0) {
            throw new Exception("Python script failed (exit code: $returnCode): $stderr");
        }
        
        return [
            'stdout' => $stdout,
            'stderr' => $stderr,
            'return_code' => $returnCode
        ];
    }
    
    /**
     * Parse optimization result from output file
     */
    private function parseOptimizationResult(string $outputFile): array {
        if (!file_exists($outputFile)) {
            throw new Exception("Python script output file not found");
        }
        
        $content = file_get_contents($outputFile);
        if ($content === false) {
            throw new Exception("Failed to read Python script output");
        }
        
        $result = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Invalid JSON in Python script output: " . json_last_error_msg());
        }
        
        if (!$result['success']) {
            $errors = $result['errors'] ?? ['Unknown optimization error'];
            throw new Exception("Python optimization failed: " . implode(', ', $errors));
        }
        
        return $result;
    }
    
    /**
     * Find Python executable
     */
    private function findPythonExecutable(): string {
        // Try common Python executables
        $pythonCommands = ['python3', 'python'];
        
        foreach ($pythonCommands as $cmd) {
            $output = shell_exec("which $cmd 2>/dev/null");
            if (!empty($output)) {
                return trim($output);
            }
        }
        
        // Default fallback
        return 'python3';
    }
    
    /**
     * Create temporary file
     */
    private function createTempFile(string $prefix, string $suffix): string {
        $tempFile = tempnam($this->tempDir, $prefix);
        if ($tempFile === false) {
            throw new Exception("Failed to create temporary file");
        }
        
        // Rename with proper suffix
        $newTempFile = $tempFile . $suffix;
        rename($tempFile, $newTempFile);
        
        return $newTempFile;
    }
    
    /**
     * Clean up temporary files
     */
    private function cleanupTempFiles(array $files): void {
        foreach ($files as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }
    
    /**
     * Test connection to Python service
     */
    public function testConnection(): array {
        try {
            // Simple test with minimal data
            $testTeams = [['id' => 1, 'name' => 'Test Team', 'is_active' => true]];
            $testMatches = [[
                'id' => 1,
                'date_time' => '2024-01-01 10:00:00',
                'home_team' => 'Team A',
                'away_team' => 'Team B',
                'location' => 'Test Location',
                'competition' => 'Test Competition',
                'required_duties' => [['type' => 'referee', 'count' => 1]]
            ]];
            $testConstraints = [];
            
            $result = $this->generateAutoplan($testTeams, $testMatches, $testConstraints, [
                'time_limit_seconds' => 30
            ]);
            
            return [
                'success' => true,
                'message' => 'Python autoplanner service is working',
                'test_result' => $result
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Python autoplanner service test failed',
                'error' => $e->getMessage()
            ];
        }
    }
}
