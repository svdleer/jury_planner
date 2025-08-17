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
        $sql = "SELECT * FROM jury_teams ORDER BY name";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    /**
     * Get a specific team by ID
     */
    public function getTeamById($id) {
        $sql = "SELECT * FROM jury_teams WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        
        return $stmt->fetch();
    }
    
    /**
     * Create a new team
     */
    public function createTeam($data) {
        $sql = "INSERT INTO jury_teams (name) 
                VALUES (?)";
        
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute([
            $data['name']
        ]);
    }
    
    /**
     * Update an existing team
     */
    public function updateTeam($id, $data) {
        $sql = "UPDATE jury_teams SET name = ?
                WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute([
            $data['name'],
            $id
        ]);
    }
    
    /**
     * Delete a team
     */
    public function deleteTeam($id) {
        // Check if team has any assignments
        $checkSql = "SELECT COUNT(*) FROM jury_assignments WHERE team_id = ?";
        $checkStmt = $this->db->prepare($checkSql);
        $checkStmt->execute([$id]);
        
        if ($checkStmt->fetchColumn() > 0) {
            throw new Exception("Cannot delete team with existing assignments");
        }
        
        $sql = "DELETE FROM jury_teams WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute([$id]);
    }
    
    /**
     * Get overall team statistics
     */
    public function getOverallStats() {
        $sql = "SELECT 
                    COUNT(*) as total_teams
                FROM jury_teams";
        
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
                    COUNT(DISTINCT ja.match_id) as matches_worked
                FROM jury_assignments ja
                JOIN home_matches m ON ja.match_id = m.id
                WHERE ja.team_id = ?";
        
        $params = [$id];
        
        if ($startDate) {
            $sql .= " AND m.date_time >= ?";
            $params[] = $startDate;
        }
        
        if ($endDate) {
            $sql .= " AND m.date_time <= ?";
            $params[] = $endDate;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetch();
    }
    
    /**
     * Get team availability - simplified for current structure
     */
    public function getTeamAvailability($id) {
        // This functionality may not be implemented yet
        return [];
    }
    
    /**
     * Set team availability - simplified for current structure
     */
    public function setTeamAvailability($teamId, $date, $available, $reason = null) {
        // This functionality may not be implemented yet
        return true;
    }
}
?>
