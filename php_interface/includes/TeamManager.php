<?php
/**
 * Team     public function getAllTeams() {
        $sql = "SELECT * FROM jury_teams ORDER BY name";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }lass
 * Handles all team-related database operations
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/translations.php';

class TeamManager {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    /**
     * Get all teams with optional filtering
     */
    public function getAllTeams($activeOnly = false) {
        $sql = "SELECT jt.*, mt.name as dedicated_to_team_name 
                FROM jury_teams jt
                LEFT JOIN mnc_teams mt ON jt.dedicated_to_team_id = mt.id
                ORDER BY jt.name";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    /**
     * Get a specific team by ID
     */
    public function getTeamById($id) {
        $sql = "SELECT jt.*, mt.name as dedicated_to_team_name 
                FROM jury_teams jt
                LEFT JOIN mnc_teams mt ON jt.dedicated_to_team_id = mt.id
                WHERE jt.id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        
        return $stmt->fetch();
    }
    
    /**
     * Create a new team
     */
    public function createTeam($data) {
        $sql = "INSERT INTO jury_teams (name, weight, dedicated_to_team_id, notes) 
                VALUES (?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute([
            $data['name'],
            $data['weight'] ?? 1.0,
            $data['dedicated_to_team_id'] ?? null,
            $data['notes'] ?? ''
        ]);
    }
    
    /**
     * Update an existing team
     */
    public function updateTeam($id, $data) {
        $sql = "UPDATE jury_teams SET name = ?, weight = ?, dedicated_to_team_id = ?, notes = ?
                WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute([
            $data['name'],
            $data['weight'] ?? 1.0,
            $data['dedicated_to_team_id'] ?? null,
            $data['notes'] ?? '',
            $id
        ]);
    }
    
    /**
     * Get all MNC teams for dedicated team selection (excluding those not available for jury duty)
     * Special case: H1 and H2 teams are included because they can be served by the H1/H2 jury team
     */
    public function getAllMncTeams() {
        $sql = "SELECT mt.id, mt.name 
                FROM mnc_teams mt
                WHERE NOT EXISTS (
                    SELECT 1 FROM excluded_teams et 
                    WHERE LOWER(mt.name) LIKE CONCAT('%', LOWER(et.name), '%')
                    AND et.name NOT IN ('h1', 'h2')  -- H1 and H2 are special case
                )
                OR mt.name LIKE '%H1%' OR mt.name LIKE '%H2%'  -- Always include H1/H2 teams
                ORDER BY mt.name";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll();
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
            throw new Exception(t('cannot_delete_team_with_assignments'));
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
