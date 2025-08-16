<?php
/**
 * MNC Team Manager Class
 * Manages teams from the existing MNC jury database structure
 */

class MncTeamManager {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Get all jury teams
     */
    public function getAllJuryTeams() {
        $stmt = $this->pdo->prepare("SELECT * FROM jury_teams ORDER BY name");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get all MNC teams with Sportlink integration
     */
    public function getAllMncTeams() {
        $stmt = $this->pdo->prepare("SELECT * FROM mnc_teams ORDER BY name");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get excluded teams
     */
    public function getExcludedTeams() {
        $stmt = $this->pdo->prepare("SELECT * FROM excluded_teams ORDER BY name");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Add new jury team
     */
    public function addJuryTeam($name) {
        $stmt = $this->pdo->prepare("INSERT INTO jury_teams (name) VALUES (?)");
        return $stmt->execute([$name]);
    }
    
    /**
     * Add new MNC team
     */
    public function addMncTeam($sportlink_team_id, $name) {
        $stmt = $this->pdo->prepare("INSERT INTO mnc_teams (sportlink_team_id, name) VALUES (?, ?)");
        return $stmt->execute([$sportlink_team_id, $name]);
    }
    
    /**
     * Add excluded team
     */
    public function addExcludedTeam($name) {
        $stmt = $this->pdo->prepare("INSERT INTO excluded_teams (name) VALUES (?)");
        return $stmt->execute([$name]);
    }
    
    /**
     * Update jury team
     */
    public function updateJuryTeam($id, $name) {
        $stmt = $this->pdo->prepare("UPDATE jury_teams SET name = ? WHERE id = ?");
        return $stmt->execute([$name, $id]);
    }
    
    /**
     * Update MNC team
     */
    public function updateMncTeam($id, $sportlink_team_id, $name) {
        $stmt = $this->pdo->prepare("UPDATE mnc_teams SET sportlink_team_id = ?, name = ? WHERE id = ?");
        return $stmt->execute([$sportlink_team_id, $name, $id]);
    }
    
    /**
     * Delete jury team
     */
    public function deleteJuryTeam($id) {
        $stmt = $this->pdo->prepare("DELETE FROM jury_teams WHERE id = ?");
        return $stmt->execute([$id]);
    }
    
    /**
     * Delete MNC team
     */
    public function deleteMncTeam($id) {
        $stmt = $this->pdo->prepare("DELETE FROM mnc_teams WHERE id = ?");
        return $stmt->execute([$id]);
    }
    
    /**
     * Delete excluded team
     */
    public function deleteExcludedTeam($id) {
        $stmt = $this->pdo->prepare("DELETE FROM excluded_teams WHERE id = ?");
        return $stmt->execute([$id]);
    }
    
    /**
     * Get team statistics
     */
    public function getTeamStats() {
        $stats = [];
        
        // Jury teams count
        $stmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM jury_teams");
        $stmt->execute();
        $stats['jury_teams'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // MNC teams count
        $stmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM mnc_teams");
        $stmt->execute();
        $stats['mnc_teams'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // Excluded teams count
        $stmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM excluded_teams");
        $stmt->execute();
        $stats['excluded_teams'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // Static assignments count
        $stmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM static_assignments");
        $stmt->execute();
        $stats['static_assignments'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        return $stats;
    }
    
    /**
     * Get static assignments
     */
    public function getStaticAssignments() {
        $stmt = $this->pdo->prepare("SELECT * FROM static_assignments ORDER BY home_team, jury_team");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Add static assignment
     */
    public function addStaticAssignment($home_team, $jury_team, $points) {
        $stmt = $this->pdo->prepare("INSERT INTO static_assignments (home_team, jury_team, points) VALUES (?, ?, ?)");
        return $stmt->execute([$home_team, $jury_team, $points]);
    }
    
    /**
     * Delete static assignment
     */
    public function deleteStaticAssignment($id) {
        $stmt = $this->pdo->prepare("DELETE FROM static_assignments WHERE id = ?");
        return $stmt->execute([$id]);
    }
    
    /**
     * Get team points
     */
    public function getTeamPoints() {
        $stmt = $this->pdo->prepare("
            SELECT tp.*, jt.name as team_name 
            FROM team_points tp 
            JOIN jury_teams jt ON tp.team_id = jt.id 
            ORDER BY tp.points DESC, jt.name
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get available jury teams (not excluded)
     */
    public function getAvailableJuryTeams() {
        $stmt = $this->pdo->prepare("
            SELECT jt.* FROM jury_teams jt 
            WHERE jt.name NOT IN (SELECT name FROM excluded_teams) 
            ORDER BY jt.name
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
