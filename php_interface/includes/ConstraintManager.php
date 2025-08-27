<?php

class ConstraintManager {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    /**
     * Get all planning rules/constraints
     */
    public function getAllConstraints() {
        try {
            $stmt = $this->db->prepare("
                SELECT id, name, description, rule_type, weight, is_active, parameters, created_at, updated_at
                FROM planning_rules 
                ORDER BY created_at DESC
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting constraints: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get all teams for constraint parameters
     */
    public function getAllTeams() {
        try {
            $stmt = $this->db->prepare("
                SELECT id, team_name 
                FROM teams 
                ORDER BY team_name
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting teams: " . $e->getMessage());
            return [];
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
