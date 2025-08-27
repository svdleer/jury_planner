<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/PythonConstraintBridge.php';
require_once __DIR__ . '/includes/ConstraintManager.php';

/**
 * PHP interface for Python optimization integration
 */
class OptimizationInterface {
    private $db;
    private $bridge;
    private $pythonScriptPath;
    
    public function __construct($database) {
        $this->db = $database;
        $this->bridge = new PythonConstraintBridge($database);
        $this->pythonScriptPath = __DIR__ . '/../planning_engine/enhanced_optimizer.py';
    }
    
    /**
     * Run full optimization using Python solver
     */
    public function runOptimization($options = []) {
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
        $timeout = $options['timeout'] ?? 300; // 5 minutes default
        $solver_type = $options['solver_type'] ?? 'auto';
        
        // Build command
        $pythonCmd = $this->getPythonCommand();
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
        
        return [
            'success' => $returnCode === 0,
            'output' => implode("\n", $output),
            'return_code' => $returnCode,
            'execution_time' => $execution_time,
            'command' => $command
        ];
    }
    
    /**
     * Import optimization results
     */
    private function importOptimizationResult($solutionPath) {
        $solutionJson = file_get_contents($solutionPath);
        return $this->bridge->importPythonSolution($solutionJson);
    }
    
    /**
     * Get appropriate Python command
     */
    private function getPythonCommand() {
        // Try different Python commands
        $pythonCmds = ['python3', 'python', '/usr/bin/python3', '/usr/local/bin/python3'];
        
        foreach ($pythonCmds as $cmd) {
            $output = shell_exec("which $cmd 2>/dev/null");
            if ($output && trim($output)) {
                return trim($output);
            }
        }
        
        return 'python3'; // Fallback
    }
    
    /**
     * Preview optimization without saving results
     */
    public function previewOptimization($options = []) {
        $options['preview_only'] = true;
        return $this->runOptimization($options);
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
                'description' => 'Add team unavailability constraints for planned absences'
            ];
        }
        
        if (!$hasRestBetween) {
            $recommendations['missing_constraints'][] = [
                'type' => 'rest_between_matches',
                'priority' => 'medium',
                'description' => 'Add rest period constraints to prevent team burnout'
            ];
        }
        
        if (!$hasMaxAssignments) {
            $recommendations['missing_constraints'][] = [
                'type' => 'max_assignments_per_day',
                'priority' => 'high', 
                'description' => 'Limit daily assignments to ensure quality'
            ];
        }
        
        // Load balancing recommendations
        if (count($teams) > 5) {
            $recommendations['load_balancing'][] = [
                'description' => 'Consider adding workload distribution constraints',
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
