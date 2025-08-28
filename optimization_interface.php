<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/PythonConstraintBridge.php';
require_once __DIR__ . '/includes/ConstraintManager.php';
require_once __DIR__ . '/includes/SimplePhpOptimizer.php';

/**
 * PHP interface for Python optimization integration
 */
class OptimizationInterface {
    private $db;
    private $bridge;
    private $pythonScriptPath;
    private $phpOptimizer;
    
    public function __construct($database) {
        $this->db = $database;
        $this->bridge = new PythonConstraintBridge($database);
        
        // Use relative paths to work within open_basedir restrictions
        $possibleScriptPaths = [
            __DIR__ . '/planning_engine/enhanced_optimizer.py',    // Current directory
            './planning_engine/enhanced_optimizer.py',            // Relative to current working directory
            '../planning_engine/enhanced_optimizer.py'            // Parent directory (relative)
        ];
        
        $this->pythonScriptPath = null;
        foreach ($possibleScriptPaths as $path) {
            if (file_exists($path)) {
                $this->pythonScriptPath = $path;
                break;
            }
        }
        
        // If not found, default to the expected relative location
        if (!$this->pythonScriptPath) {
            $this->pythonScriptPath = '../planning_engine/enhanced_optimizer.py';
        }
        
        $this->phpOptimizer = new SimplePhpOptimizer($database);
    }
    
    /**
     * Run full optimization using Python solver or PHP fallback
     */
    public function runOptimization($options = []) {
        // Check if Python optimization is available
        $availability = $this->isPythonOptimizationAvailable();
        
        if (!$availability['available']) {
            // Use PHP fallback if Python is not available
            if ($options['force_python'] ?? false) {
                return [
                    'success' => false,
                    'error' => $availability['reason'],
                    'suggestion' => $availability['suggestion'],
                    'step' => 'availability_check'
                ];
            }
            
            // Run PHP optimization fallback
            $result = $this->phpOptimizer->runSimpleOptimization($options);
            $result['fallback_used'] = true;
            $result['fallback_reason'] = $availability['reason'];
            
            // If successful, import results to database
            if ($result['success'] && !($options['preview_only'] ?? false)) {
                $importResult = $this->importPhpOptimizationResult($result);
                $result = array_merge($result, $importResult);
            }
            
            return $result;
        }
        
        try {
            // Export current constraints to temporary file
            $configPath = $this->exportConstraintsForPython();
            $solutionPath = tempnam(sys_get_temp_dir(), 'jury_solution_');
            
            // Run Python optimization
            $result = $this->executePythonOptimizer($configPath, $solutionPath, $options);
            
            // Import results back to PHP
            if ($result['success'] && file_exists($solutionPath)) {
                $importResult = $this->importOptimizationResult($solutionPath);
                $result = array_merge($result, $importResult);
            }
            
            // Cleanup temporary files
            @unlink($configPath);
            @unlink($solutionPath);
            
            return $result;
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'step' => 'optimization_interface'
            ];
        }
    }
    
    /**
     * Export current constraints for Python consumption
     */
    private function exportConstraintsForPython() {
        $configJson = $this->bridge->exportConstraintsToPython();
        $configPath = tempnam(sys_get_temp_dir(), 'jury_config_');
        file_put_contents($configPath, $configJson);
        return $configPath;
    }
    
    /**
     * Execute Python optimizer
     */
    private function executePythonOptimizer($configPath, $solutionPath, $options) {
        try {
            $timeout = $options['timeout'] ?? 300; // 5 minutes default
            $solver_type = $options['solver_type'] ?? 'auto';
            
            // Get Python command (this might throw an exception)
            $pythonCmd = $this->getPythonCommand();
            
            // Build command
            $command = sprintf(
                '%s %s %s %s 2>&1',
                escapeshellcmd($pythonCmd),
                escapeshellarg($this->pythonScriptPath),
                escapeshellarg($configPath),
                escapeshellarg($solutionPath)
            );
            
            // Set environment for Python
            $env = [
                'PYTHONPATH' => dirname($this->pythonScriptPath),
                'SOLVER_TYPE' => $solver_type
            ];
            
            // Execute with timeout
            $output = [];
            $returnCode = 0;
            
            $start_time = microtime(true);
            exec($command, $output, $returnCode);
            $execution_time = microtime(true) - $start_time;
            
            $outputStr = implode("\n", $output);
            
            // Check for specific error conditions
            if ($returnCode !== 0) {
                $errorMsg = "Python optimization failed (exit code: $returnCode)";
                if (!empty($outputStr)) {
                    $errorMsg .= "\nOutput: " . $outputStr;
                }
                
                return [
                    'success' => false,
                    'error' => $errorMsg,
                    'output' => $outputStr,
                    'return_code' => $returnCode,
                    'execution_time' => $execution_time,
                    'command' => $command,
                    'step' => 'python_execution'
                ];
            }
            
            return [
                'success' => true,
                'output' => $outputStr,
                'return_code' => $returnCode,
                'execution_time' => $execution_time,
                'command' => $command
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Failed to execute Python optimizer: ' . $e->getMessage(),
                'step' => 'python_setup',
                'debug_info' => [
                    'exception_type' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]
            ];
        }
    }
    
    /**
     * Import optimization results
     */
    private function importOptimizationResult($solutionPath) {
        $solutionJson = file_get_contents($solutionPath);
        return $this->bridge->importPythonSolution($solutionJson);
    }
    
    /**
     * Import PHP optimization results to database
     */
    private function importPhpOptimizationResult($result) {
        if (!$result['success'] || empty($result['assignments'])) {
            return ['imported_assignments' => 0];
        }
        
        try {
            $this->db->beginTransaction();
            
            // Clear existing assignments for the period
            if (isset($result['period']) && !empty($result['period'])) {
                $this->clearExistingAssignments($result['period']);
            }
            
            // Import new assignments
            $importedCount = 0;
            foreach ($result['assignments'] as $assignment) {
                if ($this->saveAssignment($assignment)) {
                    $importedCount++;
                }
            }
            
            // Save optimization metadata
            $this->saveOptimizationMetadata($result);
            
            $this->db->commit();
            
            return [
                'imported_assignments' => $importedCount
            ];
            
        } catch (Exception $e) {
            $this->db->rollback();
            return [
                'imported_assignments' => 0,
                'import_error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Clear existing assignments for the optimization period
     */
    private function clearExistingAssignments($period) {
        try {
            // Check if period has required date fields
            if (empty($period) || !isset($period['start_date']) || !isset($period['end_date'])) {
                error_log("Warning: Period data missing or incomplete, skipping assignment cleanup");
                return;
            }
            
            $stmt = $this->db->prepare("
                DELETE FROM jury_assignments 
                WHERE match_id IN (
                    SELECT id FROM matches 
                    WHERE date_time BETWEEN ? AND ?
                )
            ");
            $stmt->execute([$period['start_date'], $period['end_date']]);
        } catch (PDOException $e) {
            error_log("Error clearing existing assignments: " . $e->getMessage());
        }
    }
    
    /**
     * Save individual assignment
     */
    private function saveAssignment($assignment) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO jury_assignments 
                (match_id, team_id)
                VALUES (?, ?)
            ");
            
            // Get team_id from team name
            $teamId = $this->getTeamIdByName($assignment['team_name']);
            if (!$teamId) {
                return false;
            }
            
            return $stmt->execute([
                $assignment['match_id'],
                $teamId
            ]);
        } catch (PDOException $e) {
            error_log("Error saving assignment: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get team ID by team name
     */
    private function getTeamIdByName($teamName) {
        try {
            $stmt = $this->db->prepare("SELECT id FROM jury_teams WHERE name = ?");
            $stmt->execute([$teamName]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? $result['id'] : null;
        } catch (PDOException $e) {
            error_log("Error getting team ID: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Save optimization metadata
     */
    private function saveOptimizationMetadata($result) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO optimization_runs 
                (run_date, optimization_score, constraints_satisfied, total_constraints, 
                 solver_time, solution_metadata)
                VALUES (NOW(), ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $result['optimization_score'] ?? 0,
                $result['constraints_satisfied'] ?? 0,
                $result['total_constraints'] ?? 0,
                $result['solver_time'] ?? 0,
                json_encode($result['metadata'] ?? [])
            ]);
        } catch (PDOException $e) {
            // Create table if it doesn't exist
            $this->createOptimizationTable();
        }
    }
    
    /**
     * Create optimization runs table
     */
    private function createOptimizationTable() {
        $sql = "
        CREATE TABLE IF NOT EXISTS optimization_runs (
            id INTEGER PRIMARY KEY AUTO_INCREMENT,
            run_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            optimization_score DECIMAL(10,2),
            constraints_satisfied INTEGER,
            total_constraints INTEGER,
            solver_time DECIMAL(8,3),
            solution_metadata JSON,
            INDEX idx_optimization_date (run_date)
        )";
        
        try {
            $this->db->exec($sql);
        } catch (PDOException $e) {
            error_log("Error creating optimization_runs table: " . $e->getMessage());
        }
    }
    
    /**
     * Get appropriate Python command
     */
    private function getPythonCommand() {
        // Check if shell functions are available
        if (!function_exists('shell_exec') || !function_exists('exec')) {
            throw new Exception('Shell execution functions are disabled on this server. Python optimization is not available.');
        }
        
        // Use relative paths to work within open_basedir restrictions
        $wrapperLocations = [
            __DIR__ . '/run_python_optimization.sh',              // Current directory
            './run_python_optimization.sh',                       // Relative to current working directory
            '../run_python_optimization.sh'                       // Parent directory (relative)
        ];
        
        foreach ($wrapperLocations as $wrapperScript) {
            if (file_exists($wrapperScript) && is_executable($wrapperScript)) {
                // Check if virtual environment exists in corresponding location
                $scriptDir = dirname($wrapperScript);
                $venvPython = $scriptDir . '/venv/bin/python3';
                if (file_exists($venvPython)) {
                    return $wrapperScript;
                }
            }
        }
        
        // Check for virtual environment directly using relative paths
        $venvLocations = [
            __DIR__ . '/venv/bin/python3',                        // Current directory
            './venv/bin/python3',                                 // Relative to current working directory
            '../venv/bin/python3'                                 // Parent directory (relative)
        ];
        
        foreach ($venvLocations as $venvPython) {
            if (file_exists($venvPython)) {
                return $venvPython;
            }
        }
        
        throw new Exception('Virtual environment is required for Python optimization. Please run setup_python_venv.sh to create the virtual environment.');
    }    /**
     * Check if Python optimization is available on this server
     */
    public function isPythonOptimizationAvailable() {
        try {
            // Check if required functions exist
            if (!function_exists('shell_exec') || !function_exists('exec')) {
                return [
                    'available' => false,
                    'reason' => 'Shell execution functions are disabled for security',
                    'suggestion' => 'Contact your hosting provider to enable exec() functions or use a different server'
                ];
            }
            
            // Check if Python is available
            $pythonCmd = $this->getPythonCommand();
            if (!$pythonCmd) {
                return [
                    'available' => false,
                    'reason' => 'Python is not installed or not in PATH',
                    'suggestion' => 'Install Python 3 on your server'
                ];
            }
            
            // Check if optimization script exists
            if (!file_exists($this->pythonScriptPath)) {
                return [
                    'available' => false,
                    'reason' => 'Python optimization script not found',
                    'suggestion' => 'Ensure enhanced_optimizer.py is uploaded to the server'
                ];
            }
            
            return [
                'available' => true,
                'python_command' => $pythonCmd,
                'script_path' => $this->pythonScriptPath
            ];
            
        } catch (Exception $e) {
            return [
                'available' => false,
                'reason' => $e->getMessage(),
                'suggestion' => 'Check server configuration and permissions'
            ];
        }
    }
    
    /**
     * Preview optimization without saving results
     */
    public function previewOptimization($options = []) {
        try {
            $options['preview_only'] = true;
            
            // Check if Python optimization is available
            $availability = $this->isPythonOptimizationAvailable();
            
            if ($availability['available']) {
                // Use Python optimization
                return $this->runOptimization($options);
            } else {
                // Use PHP fallback optimizer for preview
                $result = $this->phpOptimizer->runSimpleOptimization($options);
                $result['fallback_used'] = true;
                $result['fallback_reason'] = $availability['reason'];
                return $result;
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Preview optimization failed: ' . $e->getMessage(),
                'step' => 'preview_optimization',
                'debug_info' => [
                    'exception_type' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString()
                ]
            ];
        }
    }
    
    /**
     * Get optimization statistics and history
     */
    public function getOptimizationHistory($days = 30) {
        return $this->bridge->getOptimizationStats();
    }
    
    /**
     * Validate constraints before optimization
     */
    public function validateConstraints() {
        $constraintManager = new ConstraintManager($this->db);
        $constraints = $constraintManager->getAllConstraints();
        
        $validation = [
            'valid' => true,
            'errors' => [],
            'warnings' => [],
            'total_constraints' => count($constraints),
            'active_constraints' => 0,
            'conflict_checks' => []
        ];
        
        foreach ($constraints as $constraint) {
            if (!$constraint['is_active']) continue;
            
            $validation['active_constraints']++;
            
            // Validate parameters
            $parameters = json_decode($constraint['parameters'], true);
            $paramValidation = $this->validateConstraintParameters($constraint['name'], $parameters);
            
            if (!$paramValidation['valid']) {
                $validation['valid'] = false;
                $validation['errors'][] = [
                    'constraint' => $constraint['name'],
                    'errors' => $paramValidation['errors']
                ];
            }
            
            // Check for potential conflicts
            $conflicts = $this->checkConstraintConflicts($constraint, $constraints);
            if (!empty($conflicts)) {
                $validation['warnings'][] = [
                    'constraint' => $constraint['name'],
                    'conflicts' => $conflicts
                ];
            }
        }
        
        return $validation;
    }
    
    /**
     * Validate individual constraint parameters
     */
    private function validateConstraintParameters($name, $parameters) {
        $validation = ['valid' => true, 'errors' => []];
        
        // Basic parameter validation
        if (empty($parameters['constraint_type'])) {
            $validation['valid'] = false;
            $validation['errors'][] = 'Missing constraint type';
        }
        
        // Type-specific validation
        $constraintType = $parameters['constraint_type'] ?? '';
        
        switch ($constraintType) {
            case 'team_unavailable':
                if (empty($parameters['team_id'])) {
                    $validation['valid'] = false;
                    $validation['errors'][] = 'Team ID required for team unavailable constraint';
                }
                if (empty($parameters['date'])) {
                    $validation['valid'] = false;
                    $validation['errors'][] = 'Date required for team unavailable constraint';
                }
                break;
                
            case 'rest_between_matches':
                if (!isset($parameters['min_rest_days']) || $parameters['min_rest_days'] < 0) {
                    $validation['valid'] = false;
                    $validation['errors'][] = 'Valid minimum rest days required';
                }
                break;
                
            case 'max_assignments_per_day':
                if (!isset($parameters['max_assignments']) || $parameters['max_assignments'] < 1) {
                    $validation['valid'] = false;
                    $validation['errors'][] = 'Valid maximum assignments required';
                }
                break;
        }
        
        return $validation;
    }
    
    /**
     * Check for potential constraint conflicts
     */
    private function checkConstraintConflicts($constraint, $allConstraints) {
        $conflicts = [];
        $parameters = json_decode($constraint['parameters'], true);
        
        foreach ($allConstraints as $otherConstraint) {
            if ($constraint['id'] == $otherConstraint['id'] || !$otherConstraint['is_active']) {
                continue;
            }
            
            $otherParameters = json_decode($otherConstraint['parameters'], true);
            
            // Check for conflicting team unavailability
            if ($parameters['constraint_type'] == 'team_unavailable' && 
                $otherParameters['constraint_type'] == 'team_unavailable') {
                
                if ($parameters['team_id'] == $otherParameters['team_id'] &&
                    $parameters['date'] == $otherParameters['date']) {
                    $conflicts[] = "Duplicate unavailability with: {$otherConstraint['name']}";
                }
            }
            
            // Check for conflicting weights (extreme opposites)
            if (abs($constraint['weight'] + $otherConstraint['weight']) < 0.1 &&
                abs($constraint['weight']) > 50) {
                $conflicts[] = "Opposing strong weights with: {$otherConstraint['name']}";
            }
        }
        
        return $conflicts;
    }
    
    /**
     * Get constraint recommendations based on current setup
     */
    public function getConstraintRecommendations() {
        $teams = $this->getAllTeams();
        $matches = $this->getUpcomingMatches();
        $existingConstraints = $this->getActiveConstraints();
        
        $recommendations = [
            'load_balancing' => [],
            'fairness' => [],
            'efficiency' => [],
            'missing_constraints' => []
        ];
        
        // Check for missing basic constraints
        $hasTeamUnavailable = $this->hasConstraintType($existingConstraints, 'team_unavailable');
        $hasRestBetween = $this->hasConstraintType($existingConstraints, 'rest_between_matches');
        $hasMaxAssignments = $this->hasConstraintType($existingConstraints, 'max_assignments_per_day');
        
        if (!$hasTeamUnavailable) {
            $recommendations['missing_constraints'][] = [
                'type' => 'team_unavailable',
                'priority' => 'high',
                'description' => t('rec_team_unavailability_desc')
            ];
        }
        
        if (!$hasRestBetween) {
            $recommendations['missing_constraints'][] = [
                'type' => 'rest_between_matches',
                'priority' => 'medium',
                'description' => t('rec_rest_between_matches_desc')
            ];
        }
        
        if (!$hasMaxAssignments) {
            $recommendations['missing_constraints'][] = [
                'type' => 'max_assignments_per_day',
                'priority' => 'high', 
                'description' => t('rec_max_assignments_desc')
            ];
        }
        
        // Load balancing recommendations
        if (count($teams) > 5) {
            $recommendations['load_balancing'][] = [
                'description' => t('rec_workload_distribution_desc'),
                'suggested_constraint' => 'max_duties_per_period',
                'parameters' => ['max_duties' => 3, 'period_days' => 14]
            ];
        }
        
        return $recommendations;
    }
    
    /**
     * Helper methods
     */
    private function getAllTeams() {
        try {
            $stmt = $this->db->prepare("SELECT * FROM jury_teams WHERE is_active = 1");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
    
    private function getUpcomingMatches() {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM matches 
                WHERE date_time >= CURDATE() 
                ORDER BY date_time 
                LIMIT 100
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
    
    private function getActiveConstraints() {
        $constraintManager = new ConstraintManager($this->db);
        return array_filter(
            $constraintManager->getAllConstraints(),
            function($c) { return $c['is_active']; }
        );
    }
    
    private function hasConstraintType($constraints, $type) {
        foreach ($constraints as $constraint) {
            $parameters = json_decode($constraint['parameters'], true);
            if (($parameters['constraint_type'] ?? '') === $type) {
                return true;
            }
        }
        return false;
    }
}
