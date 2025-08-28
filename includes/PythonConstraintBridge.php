<?php

/**
 * Bridge between PHP constraint editor and Python optimization engine
 * Exports PHP constraints to Python-compatible format and imports solutions
 */
class PythonConstraintBridge {
    private $db;
    private $constraintManager;
    private $matchesColumns = []; // Cache for table structure
    
    public function __construct($database) {
        $this->db = $database;
        $this->constraintManager = new ConstraintManager($database);
        $this->cacheTableStructure();
    }
    
    /**
     * Cache table structure to handle missing columns gracefully
     */
    private function cacheTableStructure() {
        try {
            $stmt = $this->db->query("SHOW COLUMNS FROM matches");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($columns as $column) {
                $this->matchesColumns[] = $column['Field'];
            }
        } catch (PDOException $e) {
            error_log("Error caching table structure: " . $e->getMessage());
            // Set default columns if query fails
            $this->matchesColumns = ['id', 'date_time', 'home_team', 'away_team', 'location', 'competition'];
        }
    }
    
    /**
     * Check if a column exists in matches table
     */
    private function columnExists($columnName) {
        return in_array($columnName, $this->matchesColumns);
    }
    
    /**
     * Export all active constraints to Python-compatible JSON format
     */
    public function exportConstraintsToPython() {
        $constraints = $this->constraintManager->getAllConstraints();
        $teams = $this->constraintManager->getAllTeams();
        $matches = $this->getAllMatches();
        
        $pythonConfig = [
            'version' => '1.0',
            'exported_at' => date('Y-m-d H:i:s'),
            'teams' => $this->formatTeamsForPython($teams),
            'matches' => $this->formatMatchesForPython($matches),
            'constraints' => $this->formatConstraintsForPython($constraints),
            'weight_multipliers' => $this->getWeightMultipliers()
        ];
        
        return json_encode($pythonConfig, JSON_PRETTY_PRINT);
    }
    
    /**
     * Format teams for Python solver
     */
    private function formatTeamsForPython($teams) {
        $pythonTeams = [];
        foreach ($teams as $team) {
            $pythonTeams[] = [
                'id' => $team['id'],
                'name' => $team['team_name'],
                'capacity_weight' => $team['weight'] ?? 1.0,
                'is_active' => $team['is_active'] ?? true,
                'dedicated_to_team' => $team['dedicated_to_team'] ?? null
            ];
        }
        return $pythonTeams;
    }
    
    /**
     * Format matches for Python solver
     */
    private function formatMatchesForPython($matches) {
        $pythonMatches = [];
        foreach ($matches as $match) {
            $pythonMatches[] = [
                'id' => $match['id'],
                'date_time' => $match['date_time'],
                'home_team' => $match['home_team'],
                'away_team' => $match['away_team'],
                'location' => $match['location'] ?? '',
                'competition' => $match['competition'] ?? '',
                'is_locked' => $match['is_locked'] ?? false, // Use default if column doesn't exist
                'required_duties' => $this->getRequiredDuties($match),
                'importance_multiplier' => $this->calculateMatchImportance($match)
            ];
        }
        return $pythonMatches;
    }
    
    /**
     * Format constraints for Python solver with template mapping
     */
    private function formatConstraintsForPython($constraints) {
        $pythonConstraints = [];
        
        foreach ($constraints as $constraint) {
            if (!$constraint['is_active']) continue;
            
            $parameters = json_decode($constraint['parameters'], true);
            $pythonTemplate = $this->mapToPythonTemplate($parameters['constraint_type'], $parameters);
            
            if ($pythonTemplate) {
                $pythonConstraints[] = [
                    'id' => $constraint['id'],
                    'name' => $constraint['name'],
                    'description' => $constraint['description'],
                    'template' => $pythonTemplate['template'],
                    'rule_type' => $this->mapRuleTypeToPython($constraint['rule_type']),
                    'weight' => floatval($constraint['weight']),
                    'parameters' => $pythonTemplate['parameters'],
                    'priority' => $this->calculateConstraintPriority($constraint)
                ];
            }
        }
        
        return $pythonConstraints;
    }
    
    /**
     * Map PHP constraint types to Python templates
     */
    private function mapToPythonTemplate($constraintType, $parameters) {
        $mapping = [
            'team_unavailable' => [
                'template' => 'team_unavailable',
                'parameters' => [
                    'team_id' => $parameters['team_id'] ?? null,
                    'date' => $parameters['date'] ?? null,
                    'reason' => $parameters['reason'] ?? ''
                ]
            ],
            'wrong_team_dedication' => [
                'template' => 'dedicated_team_assignment',
                'parameters' => [
                    'applies_to_all_teams' => $parameters['applies_to_all_teams'] ?? true,
                    'allow_last_match_exception' => false
                ]
            ],
            'own_match' => [
                'template' => 'forbidden_self_assignment',
                'parameters' => [
                    'applies_to_all_teams' => true
                ]
            ],
            'away_match_same_day' => [
                'template' => 'conflict_prevention',
                'parameters' => [
                    'conflict_type' => 'away_match_same_day',
                    'applies_to_all_teams' => true
                ]
            ],
            'consecutive_weekends' => [
                'template' => 'avoid_consecutive_matches',
                'parameters' => [
                    'max_consecutive' => 1,
                    'time_unit' => 'weekend',
                    'applies_to_all_teams' => $parameters['applies_to_all_teams'] ?? true
                ]
            ],
            'recent_assignments' => [
                'template' => 'max_duties_per_period',
                'parameters' => [
                    'max_duties' => 3,
                    'period_days' => 14,
                    'applies_to_all_teams' => $parameters['applies_to_all_teams'] ?? true
                ]
            ],
            'previous_week_assignment' => [
                'template' => 'rest_between_matches',
                'parameters' => [
                    'min_rest_days' => 7,
                    'applies_to_all_teams' => $parameters['applies_to_all_teams'] ?? true
                ]
            ],
            'preferred_duty' => [
                'template' => 'preferred_duty_assignment',
                'parameters' => [
                    'team_id' => $parameters['team_id'] ?? null,
                    'duty_type' => $parameters['duty_type'] ?? 'any',
                    'strength' => 1.0
                ]
            ],
            'rest_between_matches' => [
                'template' => 'rest_between_matches',
                'parameters' => [
                    'min_rest_days' => $parameters['min_rest_days'] ?? 1,
                    'applies_to_all_teams' => $parameters['applies_to_all_teams'] ?? true
                ]
            ],
            'max_assignments_per_day' => [
                'template' => 'max_duties_per_period',
                'parameters' => [
                    'max_duties' => $parameters['max_assignments'] ?? 2,
                    'period_days' => 1,
                    'applies_to_all_teams' => $parameters['applies_to_all_teams'] ?? true
                ]
            ],
            'time_preference' => [
                'template' => 'preferred_match_dates',
                'parameters' => [
                    'team_id' => $parameters['team_id'] ?? null,
                    'preferred_start_time' => $parameters['preferred_start_time'] ?? null,
                    'preferred_end_time' => $parameters['preferred_end_time'] ?? null
                ]
            ]
        ];
        
        return $mapping[$constraintType] ?? null;
    }
    
    /**
     * Map PHP rule types to Python enums
     */
    private function mapRuleTypeToPython($ruleType) {
        $mapping = [
            'forbidden' => 'FORBIDDEN',
            'not_preferred' => 'NOT_PREFERRED', 
            'less_preferred' => 'LESS_PREFERRED',
            'most_preferred' => 'MOST_PREFERRED'
        ];
        
        return $mapping[$ruleType] ?? 'NOT_PREFERRED';
    }
    
    /**
     * Calculate constraint priority for solver
     */
    private function calculateConstraintPriority($constraint) {
        $priority = 1;
        
        // Higher priority for hard constraints
        if ($constraint['rule_type'] === 'forbidden') {
            $priority = 10;
        } elseif (abs($constraint['weight']) > 100) {
            $priority = 5;
        } elseif (abs($constraint['weight']) > 50) {
            $priority = 3;
        }
        
        return $priority;
    }
    
    /**
     * Get weight multipliers for different constraint categories
     */
    private function getWeightMultipliers() {
        return [
            'hard_constraints' => 1000.0,
            'soft_constraints' => 1.0,
            'preferences' => 0.5,
            'load_balancing' => 2.0,
            'efficiency_bonus' => 1.5
        ];
    }
    
    /**
     * Get required duties for a match
     */
    private function getRequiredDuties($match) {
        // Default duties required - can be customized per competition/match type
        return [
            ['type' => 'clock', 'count' => 1, 'required' => true],
            ['type' => 'score', 'count' => 1, 'required' => true]
        ];
    }
    
    /**
     * Calculate match importance multiplier
     */
    private function calculateMatchImportance($match) {
        $multiplier = 1.0;
        
        // Higher importance for finals, playoffs
        if (stripos($match['competition'] ?? '', 'final') !== false) {
            $multiplier = 1.5;
        } elseif (stripos($match['competition'] ?? '', 'playoff') !== false) {
            $multiplier = 1.3;
        }
        
        // Season opener/closer bonus
        $matchDate = date('Y-m-d', strtotime($match['date_time']));
        if ($this->isSeasonOpenerOrCloser($matchDate)) {
            $multiplier *= 1.2;
        }
        
        return $multiplier;
    }
    
    /**
     * Check if match is season opener or closer
     */
    private function isSeasonOpenerOrCloser($matchDate) {
        // Logic to determine if this is first/last match of season
        // Can be enhanced with season configuration
        return false;
    }
    
    /**
     * Get all matches for export
     */
    private function getAllMatches() {
        try {
            // Build dynamic query based on available columns
            $baseColumns = "id, date_time, home_team, away_team, location, competition";
            $isLockedColumn = $this->columnExists('is_locked') ? ", is_locked" : "";
            
            $stmt = $this->db->prepare("
                SELECT {$baseColumns}{$isLockedColumn}
                FROM matches 
                WHERE date_time >= CURDATE()
                ORDER BY date_time
            ");
            $stmt->execute();
            $matches = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Add default is_locked value if column doesn't exist
            if (!$this->columnExists('is_locked')) {
                foreach ($matches as &$match) {
                    $match['is_locked'] = false;
                }
            }
            
            return $matches;
        } catch (PDOException $e) {
            error_log("Error getting matches for Python export: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Save Python solver results back to PHP system
     */
    public function importPythonSolution($solutionJson) {
        $solution = json_decode($solutionJson, true);
        
        if (!$solution || !isset($solution['assignments'])) {
            return ['success' => false, 'error' => 'Invalid solution format'];
        }
        
        try {
            $this->db->beginTransaction();
            
            // Clear existing assignments for the period
            $this->clearExistingAssignments($solution['period']);
            
            // Import new assignments
            $importedCount = 0;
            foreach ($solution['assignments'] as $assignment) {
                if ($this->saveAssignment($assignment)) {
                    $importedCount++;
                }
            }
            
            // Save optimization metadata
            $this->saveOptimizationMetadata($solution);
            
            $this->db->commit();
            
            return [
                'success' => true,
                'imported_assignments' => $importedCount,
                'optimization_score' => $solution['optimization_score'] ?? 0,
                'constraints_satisfied' => $solution['constraints_satisfied'] ?? 0,
                'total_constraints' => $solution['total_constraints'] ?? 0
            ];
            
        } catch (Exception $e) {
            $this->db->rollback();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Clear existing assignments for the optimization period
     */
    private function clearExistingAssignments($period) {
        try {
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
            // Continue execution even if clearing fails
        }
    }
    
    /**
     * Save individual assignment from Python solution
     */
    private function saveAssignment($assignment) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO jury_assignments 
                (match_id, jury_team_name, assignment_type, points_awarded, assigned_by)
                VALUES (?, ?, ?, ?, ?)
            ");
            
            return $stmt->execute([
                $assignment['match_id'],
                $assignment['team_name'],
                $assignment['duty_type'] ?? 'general',
                $assignment['points'] ?? 10,
                'Python Optimizer'
            ]);
        } catch (PDOException $e) {
            error_log("Error saving assignment: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Save optimization metadata and statistics
     */
    private function saveOptimizationMetadata($solution) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO optimization_runs 
                (run_date, optimization_score, constraints_satisfied, total_constraints, 
                 solver_time, solution_metadata)
                VALUES (NOW(), ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $solution['optimization_score'] ?? 0,
                $solution['constraints_satisfied'] ?? 0,
                $solution['total_constraints'] ?? 0,
                $solution['solver_time'] ?? 0,
                json_encode($solution['metadata'] ?? [])
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
        CREATE TABLE optimization_runs (
            id INTEGER PRIMARY KEY AUTO_INCREMENT,
            run_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            optimization_score DECIMAL(10,2),
            constraints_satisfied INTEGER,
            total_constraints INTEGER,
            solver_time DECIMAL(8,3),
            solution_metadata JSON,
            INDEX idx_optimization_date (run_date)
        )";
        
        $this->db->exec($sql);
    }
    
    /**
     * Get optimization statistics
     */
    public function getOptimizationStats() {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(*) as total_runs,
                    AVG(optimization_score) as avg_score,
                    AVG(constraints_satisfied / total_constraints * 100) as avg_satisfaction_rate,
                    AVG(solver_time) as avg_solver_time,
                    MAX(run_date) as last_run
                FROM optimization_runs
                WHERE run_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ");
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [
                'total_runs' => 0,
                'avg_score' => 0,
                'avg_satisfaction_rate' => 0,
                'avg_solver_time' => 0,
                'last_run' => null
            ];
        }
    }
}
