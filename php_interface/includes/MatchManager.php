<?php
/**
 * Match Management Class
 * Handles all match-related database operations
 */

require_once __DIR__ . '/../config/database.php';

class MatchManager {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    /**
     * Get all matches with basic details
     */
    public function getAllMatches() {
        $stmt = $this->db->prepare("
            SELECT m.*, 
                   m.home_team as home_team_name, 
                   m.away_team as away_team_name
            FROM home_matches m
            ORDER BY m.date_time DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get matches with filtering and jury assignment details
     */
    public function getMatchesWithDetails($statusFilter = 'all', $teamFilter = 'all', $dateFilter = 'all') {
        $sql = "
            SELECT m.*, 
                   m.home_team as home_team_name, 
                   m.away_team as away_team_name
            FROM home_matches m
            WHERE 1=1
        ";
        
        $params = [];
        
        // Status filter
        if ($statusFilter !== 'all') {
            $sql .= " AND m.status = :status";
            $params['status'] = $statusFilter;
        }
        
        // Team filter
        if ($teamFilter !== 'all') {
            $sql .= " AND (m.home_team = :team_name OR m.away_team = :team_name)";
            $params['team_name'] = $teamFilter;
        }
        
        // Date filter
        if ($dateFilter !== 'all') {
            switch ($dateFilter) {
                case 'today':
                    $sql .= " AND DATE(m.date_time) = CURDATE()";
                    break;
                case 'upcoming':
                    $sql .= " AND m.date_time >= CURDATE()";
                    break;
                case 'this_week':
                    $sql .= " AND YEARWEEK(m.date_time, 1) = YEARWEEK(CURDATE(), 1)";
                    break;
                case 'this_month':
                    $sql .= " AND YEAR(m.date_time) = YEAR(CURDATE()) AND MONTH(m.date_time) = MONTH(CURDATE())";
                    break;
            }
        }
        
        $sql .= " ORDER BY m.date_time ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $matches = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get jury assignments for each match
        foreach ($matches as &$match) {
            $match['jury_assignments'] = $this->getJuryAssignments($match['id']);
        }
        
        return $matches;
    }
    
    /**
     * Get a specific match by ID
     */
    public function getMatchById($id) {
        $sql = "SELECT m.*, 
                    ht.name as home_team_name,
                    at.name as away_team_name
                FROM matches m
                LEFT JOIN teams ht ON m.home_team_id = ht.id
                LEFT JOIN teams at ON m.away_team_id = at.id
                WHERE m.id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get jury assignments for a specific match
     */
    public function getJuryAssignments($matchId) {
        $sql = "SELECT ja.id as assignment_id, ja.*, t.name as jury_team_name
                FROM jury_assignments ja
                JOIN jury_teams t ON ja.team_id = t.id
                WHERE ja.match_id = ?
                ORDER BY ja.id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$matchId]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Create a new match
     */
    public function createMatch($data) {
        $sql = "INSERT INTO matches (match_date, match_time, home_team_id, away_team_id, 
                location, pool_name, competition, status, notes) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute([
            $data['match_date'],
            $data['match_time'],
            $data['home_team_id'],
            $data['away_team_id'],
            $data['location'] ?? null,
            $data['pool_name'] ?? null,
            $data['competition'] ?? null,
            $data['status'] ?? 'scheduled',
            $data['notes'] ?? null
        ]);
    }
    
    /**
     * Update an existing match
     */
    public function updateMatch($id, $data) {
        $sql = "UPDATE matches SET match_date = ?, match_time = ?, home_team_id = ?, 
                away_team_id = ?, location = ?, pool_name = ?, competition = ?, 
                status = ?, notes = ?, updated_at = CURRENT_TIMESTAMP 
                WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute([
            $data['match_date'],
            $data['match_time'],
            $data['home_team_id'],
            $data['away_team_id'],
            $data['location'],
            $data['pool_name'],
            $data['competition'],
            $data['status'],
            $data['notes'],
            $id
        ]);
    }
    
    /**
     * Delete a match and its assignments
     */
    public function deleteMatch($id) {
        $this->db->beginTransaction();
        
        try {
            // Delete assignments first
            $deleteAssignments = "DELETE FROM jury_assignments WHERE match_id = ?";
            $stmt = $this->db->prepare($deleteAssignments);
            $stmt->execute([$id]);
            
            // Delete match
            $deleteMatch = "DELETE FROM matches WHERE id = ?";
            $stmt = $this->db->prepare($deleteMatch);
            $stmt->execute([$id]);
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    /**
     * Get matches for a specific date range
     */
    public function getMatchesByDateRange($startDate, $endDate) {
        $sql = "SELECT m.*, 
                    ht.name as home_team_name,
                    at.name as away_team_name,
                    COUNT(ja.id) as assignment_count
                FROM matches m
                LEFT JOIN teams ht ON m.home_team_id = ht.id
                LEFT JOIN teams at ON m.away_team_id = at.id
                LEFT JOIN jury_assignments ja ON m.id = ja.match_id
                WHERE m.match_date BETWEEN ? AND ?
                GROUP BY m.id
                ORDER BY m.match_date, m.match_time";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$startDate, $endDate]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get upcoming matches with assignment counts
     */
    public function getUpcomingMatches($limit = 10) {
        $sql = "SELECT m.id, m.date_time, m.home_team, m.away_team,
                    COUNT(ja.id) as assignment_count
                FROM home_matches m
                LEFT JOIN jury_assignments ja ON m.id = ja.match_id
                WHERE m.date_time >= NOW()
                GROUP BY m.id, m.date_time, m.home_team, m.away_team
                ORDER BY m.date_time
                LIMIT ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$limit]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get match statistics
     */
    public function getMatchStats() {
        $sql = "SELECT 
                    COUNT(*) as total_matches,
                    COUNT(CASE WHEN status = 'scheduled' THEN 1 END) as scheduled_matches,
                    COUNT(CASE WHEN match_date >= CURDATE() THEN 1 END) as upcoming_matches,
                    COUNT(CASE WHEN match_date < CURDATE() THEN 1 END) as past_matches
                FROM matches";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Assign jury team to a match
     */
    public function assignJuryTeam($matchId, $teamId, $notes = null) {
        $sql = "INSERT INTO jury_assignments (match_id, team_id)
                VALUES (?, ?)";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$matchId, $teamId]);
    }
    
    /**
     * Remove jury assignment by assignment ID
     */
    public function removeJuryAssignment($assignmentId) {
        $sql = "DELETE FROM jury_assignments WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$assignmentId]);
    }
}
?>
