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
        
        $teams = $stmt->fetchAll();
        
        // Special case for H1/H2 jury team - they can serve both H1 and H2
        foreach ($teams as &$team) {
            if ($team['name'] === 'H1/H2') {
                $team['dedicated_to_team_name'] = 'H1 & H2';
                $team['is_h1h2_special'] = true;
            }
        }
        
        return $teams;
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
     * Check if a jury team can serve a specific match based on dedication rules
     * @param string $juryTeamName Name of the jury team
     * @param string $homeTeamName Home team name
     * @param string $awayTeamName Away team name
     * @param bool $isLastMatchOfDay Whether this is the last match of the day (overrides dedication rules)
     */
    public function canJuryTeamServeMatch($juryTeamName, $homeTeamName, $awayTeamName, $isLastMatchOfDay = false) {
        // Exception: Last match of the day - any team can serve if needed
        if ($isLastMatchOfDay) {
            return true;
        }
        
        // Special case: H1/H2 jury team can serve both H1 and H2 matches
        if ($juryTeamName === 'H1/H2') {
            return (strpos($homeTeamName, 'H1') !== false || strpos($homeTeamName, 'H2') !== false ||
                    strpos($awayTeamName, 'H1') !== false || strpos($awayTeamName, 'H2') !== false);
        }
        
        // Get the team's dedication
        $sql = "SELECT mt.name as dedicated_team_name 
                FROM jury_teams jt
                LEFT JOIN mnc_teams mt ON jt.dedicated_to_team_id = mt.id
                WHERE jt.name = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$juryTeamName]);
        $result = $stmt->fetch();
        
        // If not dedicated to any team, can serve any match
        if (!$result || !$result['dedicated_team_name']) {
            return true;
        }
        
        // If dedicated, can only serve matches involving their dedicated team
        $dedicatedTeam = $result['dedicated_team_name'];
        return ($homeTeamName === $dedicatedTeam || $awayTeamName === $dedicatedTeam);
    }
    
    /**
     * Check if a match is the last match of the day
     * @param string $matchDateTime DateTime in YYYY-MM-DD HH:MM:SS format
     */
    public function isLastMatchOfDay($matchDateTime) {
        $matchDate = date('Y-m-d', strtotime($matchDateTime));
        
        $sql = "SELECT MAX(date_time) as latest_datetime 
                FROM matches 
                WHERE DATE(date_time) = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$matchDate]);
        $result = $stmt->fetch();
        
        if (!$result || !$result['latest_datetime']) {
            return false;
        }
        
        return $matchDateTime >= $result['latest_datetime'];
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
