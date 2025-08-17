<?php

class CustomConstraintManager {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    /**
     * Add a new custom constraint
     */
    public function addConstraint($type, $sourceTeam, $targetTeam = null, $date = null, $value = null, $reason = '') {
        $sql = "INSERT INTO custom_constraints (constraint_type, source_team, target_team, constraint_date, constraint_value, reason) 
                VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$type, $sourceTeam, $targetTeam, $date, $value, $reason]);
    }
    
    /**
     * Get all active constraints
     */
    public function getAllConstraints($activeOnly = true) {
        $sql = "SELECT * FROM custom_constraints";
        if ($activeOnly) {
            $sql .= " WHERE is_active = 1";
        }
        $sql .= " ORDER BY constraint_type, source_team";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get constraints by type
     */
    public function getConstraintsByType($type, $activeOnly = true) {
        $sql = "SELECT * FROM custom_constraints WHERE constraint_type = ?";
        if ($activeOnly) {
            $sql .= " AND is_active = 1";
        }
        $sql .= " ORDER BY source_team";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$type]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Check if team has conflicts with another team
     */
    public function hasTeamConflict($team1, $team2) {
        $sql = "SELECT COUNT(*) FROM custom_constraints 
                WHERE constraint_type = 'team_team_conflict' 
                AND is_active = 1
                AND ((source_team = ? AND target_team = ?) OR (source_team = ? AND target_team = ?))";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$team1, $team2, $team2, $team1]);
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Check if team has date restrictions
     */
    public function hasDateRestriction($team, $date) {
        $sql = "SELECT COUNT(*) FROM custom_constraints 
                WHERE constraint_type = 'date_restriction'
                AND is_active = 1
                AND source_team = ?
                AND constraint_date = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$team, $date]);
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Get capacity override for team
     */
    public function getCapacityOverride($team) {
        $sql = "SELECT constraint_value FROM custom_constraints 
                WHERE constraint_type = 'capacity_override'
                AND is_active = 1
                AND source_team = ?
                ORDER BY created_at DESC
                LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$team]);
        $result = $stmt->fetchColumn();
        return $result ?: null;
    }
    
    /**
     * Update constraint status
     */
    public function updateConstraintStatus($id, $isActive) {
        $sql = "UPDATE custom_constraints SET is_active = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$isActive, $id]);
    }
    
    /**
     * Delete constraint
     */
    public function deleteConstraint($id) {
        $sql = "DELETE FROM custom_constraints WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$id]);
    }
    
    /**
     * Get constraint types with descriptions
     */
    public function getConstraintTypes() {
        return [
            'team_exclusion' => [
                'name' => 'Team Exclusion',
                'description' => 'Exclude a team from all jury duties',
                'fields' => ['source_team', 'reason']
            ],
            'team_team_conflict' => [
                'name' => 'Team vs Team Conflict',
                'description' => 'Prevent one team from being jury for another specific team',
                'fields' => ['source_team', 'target_team', 'reason']
            ],
            'date_restriction' => [
                'name' => 'Date Restriction',
                'description' => 'Prevent a team from jury duty on a specific date',
                'fields' => ['source_team', 'constraint_date', 'reason']
            ],
            'capacity_override' => [
                'name' => 'Capacity Override',
                'description' => 'Override the default capacity factor for a team',
                'fields' => ['source_team', 'constraint_value', 'reason']
            ],
            'assignment_limit' => [
                'name' => 'Assignment Limit',
                'description' => 'Limit maximum assignments per period for a team',
                'fields' => ['source_team', 'constraint_value', 'reason']
            ]
        ];
    }
    
    /**
     * Check all constraints for a team assignment
     */
    public function checkAssignmentConstraints($juryTeam, $homeTeam, $awayTeam, $matchDate) {
        $violations = [];
        
        // Check team conflicts
        if ($this->hasTeamConflict($juryTeam, $homeTeam)) {
            $violations[] = "Conflict between {$juryTeam} and {$homeTeam}";
        }
        if ($this->hasTeamConflict($juryTeam, $awayTeam)) {
            $violations[] = "Conflict between {$juryTeam} and {$awayTeam}";
        }
        
        // Check date restrictions
        if ($this->hasDateRestriction($juryTeam, $matchDate)) {
            $violations[] = "{$juryTeam} is not available on {$matchDate}";
        }
        
        return $violations;
    }
}

?>
