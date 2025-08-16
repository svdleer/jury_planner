<?php
/**
 * MNC Match Manager Class
 * Manages matches from the existing MNC jury database structure
 */

class MncMatchManager {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Get all home matches (matches requiring jury)
     */
    public function getAllHomeMatches($limit = null) {
        $sql = "SELECT * FROM home_matches ORDER BY date_time DESC";
        if ($limit) {
            $sql .= " LIMIT " . intval($limit);
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get all matches from previous seasons
     */
    public function getAllMatches($limit = null) {
        $sql = "SELECT * FROM all_matches ORDER BY date_time DESC";
        if ($limit) {
            $sql .= " LIMIT " . intval($limit);
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get current season matches (if any)
     */
    public function getCurrentMatches($limit = null) {
        $sql = "SELECT * FROM matches ORDER BY date_time DESC";
        if ($limit) {
            $sql .= " LIMIT " . intval($limit);
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get home matches with jury assignments
     */
    public function getHomeMatchesWithJury() {
        $sql = "SELECT hm.*, ja.id as assignment_id, ja.team_id as jury_team_id, 
                       ja.locked, jt.name as jury_team_name
                FROM home_matches hm
                LEFT JOIN jury_assignments ja ON hm.id = ja.match_id
                LEFT JOIN jury_teams jt ON ja.team_id = jt.id
                ORDER BY hm.date_time DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get matches by competition
     */
    public function getMatchesByCompetition($competition) {
        $stmt = $this->pdo->prepare("SELECT * FROM home_matches WHERE competition = ? ORDER BY date_time");
        $stmt->execute([$competition]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get matches by class
     */
    public function getMatchesByClass($class) {
        $stmt = $this->pdo->prepare("SELECT * FROM home_matches WHERE `class` = ? ORDER BY date_time");
        $stmt->execute([$class]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get matches by date range
     */
    public function getMatchesByDateRange($start_date, $end_date) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM home_matches 
            WHERE date_time BETWEEN ? AND ? 
            ORDER BY date_time
        ");
        $stmt->execute([$start_date, $end_date]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get upcoming home matches
     */
    public function getUpcomingMatches($days = 30) {
        $future_date = date('Y-m-d H:i:s', strtotime("+{$days} days"));
        $stmt = $this->pdo->prepare("
            SELECT * FROM home_matches 
            WHERE date_time > NOW() AND date_time <= ? 
            ORDER BY date_time
        ");
        $stmt->execute([$future_date]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get matches without jury assignments
     */
    public function getMatchesWithoutJury() {
        $stmt = $this->pdo->prepare("
            SELECT hm.* FROM home_matches hm
            LEFT JOIN jury_assignments ja ON hm.id = ja.match_id
            WHERE ja.id IS NULL
            ORDER BY hm.date_time
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Add new current season match
     */
    public function addMatch($data) {
        $stmt = $this->pdo->prepare("
            INSERT INTO matches (date_time, competition, `class`, home_team, away_team, location, match_id, sportlink_id) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        return $stmt->execute([
            $data['date_time'],
            $data['competition'],
            $data['class'],
            $data['home_team'],
            $data['away_team'],
            $data['location'],
            $data['match_id'],
            $data['sportlink_id']
        ]);
    }
    
    /**
     * Add new home match
     */
    public function addHomeMatch($data) {
        $stmt = $this->pdo->prepare("
            INSERT INTO home_matches (date_time, competition, `class`, home_team, away_team, location, match_id, sportlink_id) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        return $stmt->execute([
            $data['date_time'],
            $data['competition'],
            $data['class'],
            $data['home_team'],
            $data['away_team'],
            $data['location'],
            $data['match_id'],
            $data['sportlink_id']
        ]);
    }
    
    /**
     * Update home match
     */
    public function updateHomeMatch($id, $data) {
        $stmt = $this->pdo->prepare("
            UPDATE home_matches 
            SET date_time = ?, competition = ?, `class` = ?, home_team = ?, 
                away_team = ?, location = ?, match_id = ?, sportlink_id = ?
            WHERE id = ?
        ");
        return $stmt->execute([
            $data['date_time'],
            $data['competition'],
            $data['class'],
            $data['home_team'],
            $data['away_team'],
            $data['location'],
            $data['match_id'],
            $data['sportlink_id'],
            $id
        ]);
    }
    
    /**
     * Delete match
     */
    public function deleteMatch($id) {
        $stmt = $this->pdo->prepare("DELETE FROM home_matches WHERE id = ?");
        return $stmt->execute([$id]);
    }
    
    /**
     * Get match statistics
     */
    public function getMatchStats() {
        $stats = [];
        
        // Total home matches
        $stmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM home_matches");
        $stmt->execute();
        $stats['total_home_matches'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // Total all matches
        $stmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM all_matches");
        $stmt->execute();
        $stats['total_all_matches'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // Current season matches
        $stmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM matches");
        $stmt->execute();
        $stats['current_matches'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // Matches with jury
        $stmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM jury_assignments");
        $stmt->execute();
        $stats['assigned_matches'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // Unique competitions
        $stmt = $this->pdo->prepare("SELECT COUNT(DISTINCT competition) as count FROM home_matches");
        $stmt->execute();
        $stats['competitions'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // Unique classes
        $stmt = $this->pdo->prepare("SELECT COUNT(DISTINCT `class`) as count FROM home_matches");
        $stmt->execute();
        $stats['classes'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        return $stats;
    }
    
    /**
     * Get competition list
     */
    public function getCompetitions() {
        $stmt = $this->pdo->prepare("SELECT DISTINCT competition FROM home_matches WHERE competition IS NOT NULL ORDER BY competition");
        $stmt->execute();
        return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'competition');
    }
    
    /**
     * Get class list
     */
    public function getClasses() {
        $stmt = $this->pdo->prepare("SELECT DISTINCT `class` FROM home_matches WHERE `class` IS NOT NULL ORDER BY `class`");
        $stmt->execute();
        return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'class');
    }
    
    /**
     * Get location list
     */
    public function getLocations() {
        $stmt = $this->pdo->prepare("SELECT DISTINCT location FROM home_matches WHERE location IS NOT NULL ORDER BY location");
        $stmt->execute();
        return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'location');
    }
    
    /**
     * Assign jury to match
     */
    public function assignJury($match_id, $team_id, $locked = false) {
        $stmt = $this->pdo->prepare("
            INSERT INTO jury_assignments (match_id, team_id, locked) 
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE team_id = VALUES(team_id), locked = VALUES(locked)
        ");
        return $stmt->execute([$match_id, $team_id, $locked ? 1 : 0]);
    }
    
    /**
     * Remove jury assignment
     */
    public function removeJuryAssignment($match_id) {
        $stmt = $this->pdo->prepare("DELETE FROM jury_assignments WHERE match_id = ?");
        return $stmt->execute([$match_id]);
    }
}
