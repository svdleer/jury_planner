<?php
/**
 * Team Management Class
 * Handles all team-related database operations
 */

require_once __DIR__ . '/../config/database.php';

class TeamManager {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    /**
     * Get all teams with optional filtering
     */
    public function getAllTeams($activeOnly = false) {
        $sql = "SELECT t.*, dt.name as dedicated_to_name 
                FROM teams t 
                LEFT JOIN teams dt ON t.dedicated_to_team_id = dt.id";
        
        if ($activeOnly) {
            $sql .= " WHERE t.is_active = 1";
        }
        
        $sql .= " ORDER BY t.name";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    /**
     * Get a specific team by ID
     */
    public function getTeamById($id) {
        $sql = "SELECT t.*, dt.name as dedicated_to_name 
                FROM teams t 
                LEFT JOIN teams dt ON t.dedicated_to_team_id = dt.id 
                WHERE t.id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        
        return $stmt->fetch();
    }
    
    /**
     * Create a new team
     */
    public function createTeam($data) {
        $sql = "INSERT INTO teams (name, weight, contact_person, email, phone, 
                dedicated_to_team_id, notes, is_active) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute([
            $data['name'],
            $data['weight'] ?? 1.0,
            $data['contact_person'] ?? null,
            $data['email'] ?? null,
            $data['phone'] ?? null,
            $data['dedicated_to_team_id'] ?? null,
            $data['notes'] ?? null,
            $data['is_active'] ?? 1
        ]);
    }
    
    /**
     * Update an existing team
     */
    public function updateTeam($id, $data) {
        $sql = "UPDATE teams SET name = ?, weight = ?, contact_person = ?, 
                email = ?, phone = ?, dedicated_to_team_id = ?, notes = ?, 
                is_active = ?, updated_at = CURRENT_TIMESTAMP 
                WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute([
            $data['name'],
            $data['weight'],
            $data['contact_person'],
            $data['email'],
            $data['phone'],
            $data['dedicated_to_team_id'],
            $data['notes'],
            $data['is_active'],
            $id
        ]);
    }
    
    /**
     * Delete a team
     */
    public function deleteTeam($id) {
        // Check if team has any assignments
        $checkSql = "SELECT COUNT(*) FROM jury_assignments WHERE jury_team_id = ?";
        $checkStmt = $this->db->prepare($checkSql);
        $checkStmt->execute([$id]);
        
        if ($checkStmt->fetchColumn() > 0) {
            throw new Exception("Cannot delete team with existing assignments");
        }
        
        $sql = "DELETE FROM teams WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute([$id]);
    }
    
    /**
     * Get overall team statistics
     */
    public function getOverallStats() {
        $sql = "SELECT 
                    COUNT(*) as total_teams,
                    COUNT(CASE WHEN is_active = 1 THEN 1 END) as active_teams,
                    COUNT(CASE WHEN is_active = 0 THEN 1 END) as inactive_teams,
                    AVG(weight) as average_weight
                FROM teams";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get specific team statistics
     */
    public function getTeamStats($id, $startDate = null, $endDate = null) {
        $sql = "SELECT 
                    COUNT(ja.id) as total_assignments,
                    COUNT(DISTINCT ja.match_id) as matches_worked,
                    GROUP_CONCAT(DISTINCT ja.duty_type) as duties_performed
                FROM jury_assignments ja
                JOIN matches m ON ja.match_id = m.id
                WHERE ja.jury_team_id = ?";
        
        $params = [$id];
        
        if ($startDate) {
            $sql .= " AND m.date >= ?";
            $params[] = $startDate;
        }
        
        if ($endDate) {
            $sql .= " AND m.date <= ?";
            $params[] = $endDate;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetch();
    }
    
    /**
     * Get team availability
     */
    public function getTeamAvailability($id) {
        $sql = "SELECT * FROM team_availability WHERE team_id = ? ORDER BY date";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Set team availability
     */
    public function setTeamAvailability($teamId, $date, $available, $reason = null) {
        $sql = "INSERT INTO team_availability (team_id, date, is_available, reason)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                is_available = VALUES(is_available), 
                reason = VALUES(reason)";
        
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute([$teamId, $date, $available ? 1 : 0, $reason]);
    }
}
?>
