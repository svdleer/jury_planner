<?php

class ConstraintManager {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    /**
     * Get all planning rules/constraints for editor (including inactive)
     */
    public function getAllConstraintsForEditor() {
        try {
            $stmt = $this->db->prepare("
                SELECT id, name, description, rule_type, weight, is_active, parameters, created_at, updated_at
                FROM planning_rules 
                ORDER BY is_active DESC, rule_type, created_at DESC
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting constraints for editor: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get all planning rules/constraints
     */
    public function getAllConstraints() {
        try {
            // First try planning_rules table (main constraint system)
            $stmt = $this->db->prepare("
                SELECT id, name, description, rule_type, weight, is_active, parameters, created_at, updated_at
                FROM planning_rules 
                ORDER BY is_active DESC, rule_type, created_at DESC
            ");
            $stmt->execute();
            $constraints = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // If no active constraints found in planning_rules, try the constraints table as fallback
            if (empty($constraints)) {
                $stmt = $this->db->prepare("
                    SELECT id, rule_type as name, description, rule_type, weight, is_active, parameters, created_at, updated_at
                    FROM constraints 
                    WHERE is_active = 1
                    ORDER BY created_at DESC
                ");
                $stmt->execute();
                $constraints = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
            // Parse JSON parameters if they exist
            foreach ($constraints as &$constraint) {
                if (isset($constraint['parameters']) && is_string($constraint['parameters'])) {
                    $constraint['parameters'] = json_decode($constraint['parameters'], true);
                }
            }
            
            return $constraints;
        } catch (PDOException $e) {
            error_log("Error getting constraints: " . $e->getMessage());
            // Check if it's a "table doesn't exist" error
            if (strpos($e->getMessage(), "doesn't exist") !== false) {
                // Return empty array with a note that migration is needed
                return [];
            }
            return [];
        }
    }
    
    /**
     * Get all teams for constraint parameters
     */
    public function getAllTeams() {
        try {
            // First try the jury_teams table (actual table name)
            $stmt = $this->db->prepare("
                SELECT id, name as team_name 
                FROM jury_teams 
                ORDER BY name
            ");
            $stmt->execute();
            $teams = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // If no teams found, try teams table as fallback
            if (empty($teams)) {
                $stmt = $this->db->prepare("
                    SELECT id, team_name 
                    FROM teams 
                    ORDER BY team_name
                ");
                $stmt->execute();
                $teams = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
            // If still no teams found, return hardcoded list for now
            if (empty($teams)) {
                return [
                    ['id' => 1, 'team_name' => 'MNC Dordrecht H1'],
                    ['id' => 2, 'team_name' => 'MNC Dordrecht H3'],
                    ['id' => 3, 'team_name' => 'MNC Dordrecht H7'],
                    ['id' => 4, 'team_name' => 'MNC Dordrecht Da1'],
                    ['id' => 5, 'team_name' => 'MNC Dordrecht Da3'],
                    ['id' => 6, 'team_name' => 'Pool Sharks'],
                    ['id' => 7, 'team_name' => 'Wave Riders'],
                    ['id' => 8, 'team_name' => 'Water Warriors']
                ];
            }
            
            return $teams;
        } catch (PDOException $e) {
            error_log("Error getting teams: " . $e->getMessage());
            // Return hardcoded list as fallback
            return [
                ['id' => 1, 'team_name' => 'MNC Dordrecht H1'],
                ['id' => 2, 'team_name' => 'MNC Dordrecht H3'],
                ['id' => 3, 'team_name' => 'MNC Dordrecht H7'],
                ['id' => 4, 'team_name' => 'MNC Dordrecht Da1'],
                ['id' => 5, 'team_name' => 'MNC Dordrecht Da3'],
                ['id' => 6, 'team_name' => 'Pool Sharks'],
                ['id' => 7, 'team_name' => 'Wave Riders'],
                ['id' => 8, 'team_name' => 'Water Warriors']
            ];
        }
    }
    
    /**
     * Create a new constraint
     */
    public function createConstraint($data) {
        try {
            // Validate required fields
            $requiredFields = ['name', 'rule_type', 'weight', 'constraint_type'];
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    return ['success' => false, 'error' => "Missing required field: $field"];
                }
            }
            
            // Build parameters JSON
            $parameters = $this->buildParametersFromFormData($data);
            
            $stmt = $this->db->prepare("
                INSERT INTO planning_rules (name, description, rule_type, weight, parameters, is_active)
                VALUES (?, ?, ?, ?, ?, 1)
            ");
            
            $result = $stmt->execute([
                $data['name'],
                $data['description'] ?? '',
                $data['rule_type'],
                floatval($data['weight']),
                json_encode($parameters)
            ]);
            
            if ($result) {
                return ['success' => true, 'id' => $this->db->lastInsertId()];
            } else {
                return ['success' => false, 'error' => 'Failed to create constraint'];
            }
            
        } catch (PDOException $e) {
            error_log("Error creating constraint: " . $e->getMessage());
            return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Update an existing constraint
     */
    public function updateConstraint($id, $data) {
        try {
            // Validate required fields
            $requiredFields = ['name', 'rule_type', 'weight', 'constraint_type'];
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    return ['success' => false, 'error' => "Missing required field: $field"];
                }
            }
            
            // Build parameters JSON
            $parameters = $this->buildParametersFromFormData($data);
            
            $stmt = $this->db->prepare("
                UPDATE planning_rules 
                SET name = ?, description = ?, rule_type = ?, weight = ?, parameters = ?, updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            
            $result = $stmt->execute([
                $data['name'],
                $data['description'] ?? '',
                $data['rule_type'],
                floatval($data['weight']),
                json_encode($parameters),
                $id
            ]);
            
            if ($result) {
                return ['success' => true];
            } else {
                return ['success' => false, 'error' => 'Failed to update constraint'];
            }
            
        } catch (PDOException $e) {
            error_log("Error updating constraint: " . $e->getMessage());
            return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Delete a constraint
     */
    public function deleteConstraint($id) {
        try {
            $stmt = $this->db->prepare("DELETE FROM planning_rules WHERE id = ?");
            $result = $stmt->execute([$id]);
            
            if ($result) {
                return ['success' => true];
            } else {
                return ['success' => false, 'error' => 'Failed to delete constraint'];
            }
            
        } catch (PDOException $e) {
            error_log("Error deleting constraint: " . $e->getMessage());
            return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Toggle constraint active status
     */
    public function toggleConstraint($id) {
        try {
            $stmt = $this->db->prepare("
                UPDATE planning_rules 
                SET is_active = NOT is_active, updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            $result = $stmt->execute([$id]);
            
            if ($result) {
                return ['success' => true];
            } else {
                return ['success' => false, 'error' => 'Failed to toggle constraint'];
            }
            
        } catch (PDOException $e) {
            error_log("Error toggling constraint: " . $e->getMessage());
            return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Build parameters JSON from form data
     */
    private function buildParametersFromFormData($data) {
        $parameters = [
            'constraint_type' => $data['constraint_type']
        ];
        
        // Extract parameter fields (fields starting with param_)
        foreach ($data as $key => $value) {
            if (strpos($key, 'param_') === 0) {
                $paramName = substr($key, 6); // Remove 'param_' prefix
                
                // Handle different data types
                if ($paramName === 'team_id' || $paramName === 'max_consecutive' || 
                    $paramName === 'min_rest_days' || $paramName === 'max_assignments') {
                    $parameters[$paramName] = intval($value);
                } elseif ($paramName === 'applies_to_all_teams') {
                    $parameters[$paramName] = !empty($value);
                } else {
                    $parameters[$paramName] = $value;
                }
            }
        }
        
        return $parameters;
    }
    
    /**
     * Import existing hardcoded constraints into the database
     */
    public function importExistingConstraints() {
        $hardcodedConstraints = [
            [
                'name' => 'Wrong Team Dedication',
                'description' => 'Teams dedicated to specific teams cannot jury matches not involving their dedicated team',
                'rule_type' => 'forbidden',
                'weight' => -1000,
                'parameters' => json_encode([
                    'constraint_type' => 'wrong_team_dedication',
                    'applies_to_all_teams' => true
                ])
            ],
            [
                'name' => 'Own Match Restriction',
                'description' => 'Teams cannot jury their own matches',
                'rule_type' => 'forbidden',
                'weight' => -1000,
                'parameters' => json_encode([
                    'constraint_type' => 'own_match',
                    'applies_to_all_teams' => true
                ])
            ],
            [
                'name' => 'Away Match Same Day',
                'description' => 'Teams cannot jury when they have an away match on the same day',
                'rule_type' => 'forbidden',
                'weight' => -1000,
                'parameters' => json_encode([
                    'constraint_type' => 'away_match_same_day',
                    'applies_to_all_teams' => true
                ])
            ],
            [
                'name' => 'Consecutive Weekends Preference',
                'description' => 'Prefer not to assign teams to consecutive weekends',
                'rule_type' => 'not_preferred',
                'weight' => -40,
                'parameters' => json_encode([
                    'constraint_type' => 'consecutive_weekends',
                    'applies_to_all_teams' => true
                ])
            ],
            [
                'name' => 'Recent Assignments Balance',
                'description' => 'Prefer teams with fewer recent assignments for load balancing',
                'rule_type' => 'most_preferred',
                'weight' => 30,
                'parameters' => json_encode([
                    'constraint_type' => 'recent_assignments',
                    'applies_to_all_teams' => true
                ])
            ],
            [
                'name' => 'Previous Week Assignment',
                'description' => 'Prefer teams that did not have jury duty in the previous week',
                'rule_type' => 'less_preferred',
                'weight' => -25,
                'parameters' => json_encode([
                    'constraint_type' => 'previous_week_assignment',
                    'applies_to_all_teams' => true
                ])
            ]
        ];
        
        $imported = 0;
        $skipped = 0;
        
        foreach ($hardcodedConstraints as $constraint) {
            try {
                // Check if constraint already exists
                $stmt = $this->db->prepare("
                    SELECT id FROM planning_rules 
                    WHERE name = ? OR JSON_EXTRACT(parameters, '$.constraint_type') = ?
                ");
                $params = json_decode($constraint['parameters'], true);
                $stmt->execute([$constraint['name'], $params['constraint_type']]);
                
                if ($stmt->fetch()) {
                    $skipped++;
                    continue;
                }
                
                // Insert new constraint
                $stmt = $this->db->prepare("
                    INSERT INTO planning_rules (name, description, rule_type, weight, parameters, is_active)
                    VALUES (?, ?, ?, ?, ?, 1)
                ");
                
                $stmt->execute([
                    $constraint['name'],
                    $constraint['description'],
                    $constraint['rule_type'],
                    $constraint['weight'],
                    $constraint['parameters']
                ]);
                
                $imported++;
                
            } catch (PDOException $e) {
                error_log("Error importing constraint '{$constraint['name']}': " . $e->getMessage());
            }
        }
        
        return [
            'success' => true,
            'imported' => $imported,
            'skipped' => $skipped
        ];
    }
    
    /**
     * Import Python template constraints as predefined options
     */
    public function importPythonTemplateConstraints() {
        $pythonTemplates = [
            [
                'name' => 'Team Unavailable (Template)',
                'description' => 'Template for marking teams unavailable on specific dates',
                'rule_type' => 'forbidden',
                'weight' => -1000,
                'parameters' => json_encode([
                    'constraint_type' => 'team_unavailable',
                    'team_id' => null,
                    'date' => null,
                    'reason' => 'Template - configure specific values'
                ]),
                'is_active' => false // Templates start inactive
            ],
            [
                'name' => 'Max Duties Per Period (Template)',
                'description' => 'Template for limiting duties within a time period',
                'rule_type' => 'not_preferred',
                'weight' => -50,
                'parameters' => json_encode([
                    'constraint_type' => 'max_duties_per_period',
                    'max_duties' => 3,
                    'period_days' => 14,
                    'applies_to_all_teams' => true
                ]),
                'is_active' => false
            ],
            [
                'name' => 'Rest Between Matches (Template)',
                'description' => 'Template for ensuring rest periods between assignments',
                'rule_type' => 'not_preferred',
                'weight' => -30,
                'parameters' => json_encode([
                    'constraint_type' => 'rest_between_matches',
                    'min_rest_days' => 1,
                    'applies_to_all_teams' => true
                ]),
                'is_active' => false
            ],
            [
                'name' => 'Dedicated Team Assignment (Template)',
                'description' => 'Template for dedicated team restrictions',
                'rule_type' => 'forbidden',
                'weight' => -1000,
                'parameters' => json_encode([
                    'constraint_type' => 'dedicated_team_assignment',
                    'team_id' => null,
                    'dedicated_to_team_id' => null,
                    'allow_last_match_exception' => false
                ]),
                'is_active' => false
            ],
            [
                'name' => 'Preferred Duty Assignment (Template)',
                'description' => 'Template for team duty preferences',
                'rule_type' => 'most_preferred',
                'weight' => 20,
                'parameters' => json_encode([
                    'constraint_type' => 'preferred_duty_assignment',
                    'team_id' => null,
                    'duty_type' => 'clock',
                    'strength' => 1.0
                ]),
                'is_active' => false
            ],
            [
                'name' => 'Avoid Duty Assignment (Template)',
                'description' => 'Template for duty avoidance preferences',
                'rule_type' => 'less_preferred',
                'weight' => -15,
                'parameters' => json_encode([
                    'constraint_type' => 'avoid_duty_assignment',
                    'team_id' => null,
                    'duty_type' => 'score',
                    'strength' => 1.0
                ]),
                'is_active' => false
            ],
            [
                'name' => 'Preferred Match Dates (Template)',
                'description' => 'Template for date preferences',
                'rule_type' => 'most_preferred',
                'weight' => 10,
                'parameters' => json_encode([
                    'constraint_type' => 'preferred_match_dates',
                    'team_id' => null,
                    'dates' => [],
                    'reason' => 'Template - configure specific dates'
                ]),
                'is_active' => false
            ],
            [
                'name' => 'Avoid Match Dates (Template)',
                'description' => 'Template for date avoidance',
                'rule_type' => 'less_preferred',
                'weight' => -25,
                'parameters' => json_encode([
                    'constraint_type' => 'avoid_match_dates',
                    'team_id' => null,
                    'dates' => [],
                    'reason' => 'Template - configure specific dates'
                ]),
                'is_active' => false
            ],
            [
                'name' => 'Avoid Opponent Team (Template)',
                'description' => 'Template for opponent avoidance',
                'rule_type' => 'less_preferred',
                'weight' => -20,
                'parameters' => json_encode([
                    'constraint_type' => 'avoid_opponent_team',
                    'team_id' => null,
                    'opponent_team_id' => null,
                    'reason' => 'Template - configure specific teams'
                ]),
                'is_active' => false
            ],
            [
                'name' => 'Avoid Consecutive Matches (Template)',
                'description' => 'Template for consecutive match avoidance',
                'rule_type' => 'not_preferred',
                'weight' => -40,
                'parameters' => json_encode([
                    'constraint_type' => 'avoid_consecutive_matches',
                    'max_consecutive' => 1,
                    'applies_to_all_teams' => true
                ]),
                'is_active' => false
            ]
        ];
        
        $imported = 0;
        $skipped = 0;
        
        foreach ($pythonTemplates as $template) {
            try {
                // Check if template already exists
                $stmt = $this->db->prepare("
                    SELECT id FROM planning_rules 
                    WHERE name = ?
                ");
                $stmt->execute([$template['name']]);
                
                if ($stmt->fetch()) {
                    $skipped++;
                    continue;
                }
                
                // Insert new template
                $stmt = $this->db->prepare("
                    INSERT INTO planning_rules (name, description, rule_type, weight, parameters, is_active)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                
                $stmt->execute([
                    $template['name'],
                    $template['description'],
                    $template['rule_type'],
                    $template['weight'],
                    $template['parameters'],
                    $template['is_active'] ? 1 : 0
                ]);
                
                $imported++;
                
            } catch (PDOException $e) {
                error_log("Error importing Python template '{$template['name']}': " . $e->getMessage());
            }
        }
        
        return [
            'success' => true,
            'imported' => $imported,
            'skipped' => $skipped,
            'type' => 'python_templates'
        ];
    }
    
    /**
     * Import all constraints (both hardcoded PHP and Python templates)
     */
    public function importAllConstraints() {
        $phpResult = $this->importExistingConstraints();
        $pythonResult = $this->importPythonTemplateConstraints();
        
        return [
            'success' => true,
            'php_imported' => $phpResult['imported'],
            'php_skipped' => $phpResult['skipped'],
            'python_imported' => $pythonResult['imported'],
            'python_skipped' => $pythonResult['skipped'],
            'total_imported' => $phpResult['imported'] + $pythonResult['imported'],
            'total_skipped' => $phpResult['skipped'] + $pythonResult['skipped']
        ];
    }
    
    /**
     * Get constraint by ID
     */
    public function getConstraintById($id) {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM planning_rules WHERE id = ?
            ");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting constraint: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Validate constraint parameters
     */
    public function validateConstraintParameters($constraintType, $parameters) {
        $errors = [];
        
        switch ($constraintType) {
            case 'team_unavailable':
                if (empty($parameters['team_id'])) {
                    $errors[] = 'Team is required';
                }
                if (empty($parameters['date'])) {
                    $errors[] = 'Date is required';
                }
                break;
                
            case 'preferred_duty':
                if (empty($parameters['team_id'])) {
                    $errors[] = 'Team is required';
                }
                if (empty($parameters['duty_type'])) {
                    $errors[] = 'Duty type is required';
                }
                break;
                
            case 'time_preference':
                if (empty($parameters['team_id'])) {
                    $errors[] = 'Team is required';
                }
                if (empty($parameters['preferred_start_time']) && empty($parameters['preferred_end_time'])) {
                    $errors[] = 'At least one time preference is required';
                }
                break;
        }
        
        return $errors;
    }
}
