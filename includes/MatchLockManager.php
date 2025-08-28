<?php
/**
 * Match Lock Manager
 * Handles match locking and assignment reset functionality
 */
class MatchLockManager {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
        $this->ensureSchemaExists();
    }
    
    /**
     * Ensure the required database schema exists
     */
    private function ensureSchemaExists() {
        // Add locked column to home_matches if it doesn't exist
        $sql = "SHOW COLUMNS FROM home_matches LIKE 'locked'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        if ($stmt->rowCount() == 0) {
            $sql = "ALTER TABLE home_matches ADD COLUMN locked BOOLEAN DEFAULT FALSE";
            $this->db->exec($sql);
        }
        
        // Add locked_at and locked_by columns for tracking
        $sql = "SHOW COLUMNS FROM home_matches LIKE 'locked_at'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        if ($stmt->rowCount() == 0) {
            $sql = "ALTER TABLE home_matches ADD COLUMN locked_at TIMESTAMP NULL";
            $this->db->exec($sql);
        }
        
        $sql = "SHOW COLUMNS FROM home_matches LIKE 'locked_by'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        if ($stmt->rowCount() == 0) {
            $sql = "ALTER TABLE home_matches ADD COLUMN locked_by VARCHAR(255) NULL";
            $this->db->exec($sql);
        }
    }
    
    /**
     * Lock a match to prevent changes to its jury assignments
     */
    public function lockMatch($matchId, $lockedBy = 'System') {
        $sql = "UPDATE home_matches 
                SET locked = TRUE, locked_at = NOW(), locked_by = ? 
                WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$lockedBy, $matchId]);
    }
    
    /**
     * Unlock a match to allow changes to its jury assignments
     */
    public function unlockMatch($matchId) {
        $sql = "UPDATE home_matches 
                SET locked = FALSE, locked_at = NULL, locked_by = NULL 
                WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$matchId]);
    }
    
    /**
     * Check if a match is locked
     */
    public function isMatchLocked($matchId) {
        $sql = "SELECT locked FROM home_matches WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$matchId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result && $result['locked'];
    }
    
    /**
     * Get lock information for a match
     */
    public function getMatchLockInfo($matchId) {
        $sql = "SELECT locked, locked_at, locked_by FROM home_matches WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$matchId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Reset jury assignments for a specific match
     */
    public function resetMatchAssignments($matchId) {
        // Check if match is locked
        if ($this->isMatchLocked($matchId)) {
            throw new Exception('Cannot reset assignments for a locked match. Unlock the match first.');
        }
        
        $sql = "DELETE FROM jury_assignments WHERE match_id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$matchId]);
    }
    
    /**
     * Reset jury assignments for all matches
     */
    public function resetAllAssignments($forceIncludeLocked = false) {
        if ($forceIncludeLocked) {
            // Reset everything, including locked matches
            $sql = "DELETE FROM jury_assignments";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute();
        } else {
            // Only reset unlocked matches
            $sql = "DELETE ja FROM jury_assignments ja
                    JOIN home_matches m ON ja.match_id = m.id
                    WHERE (m.locked IS NULL OR m.locked = FALSE)";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute();
        }
    }
    
    /**
     * Get all locked matches
     */
    public function getLockedMatches() {
        $sql = "SELECT m.*, 
                       m.home_team as home_team_name, 
                       m.away_team as away_team_name,
                       DATE(m.date_time) as match_date,
                       TIME(m.date_time) as match_time
                FROM home_matches m 
                WHERE m.locked = TRUE 
                ORDER BY m.date_time";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get all unlocked matches
     */
    public function getUnlockedMatches() {
        $sql = "SELECT m.*, 
                       m.home_team as home_team_name, 
                       m.away_team as away_team_name,
                       DATE(m.date_time) as match_date,
                       TIME(m.date_time) as match_time
                FROM home_matches m 
                WHERE (m.locked IS NULL OR m.locked = FALSE)
                ORDER BY m.date_time";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Bulk lock/unlock matches
     */
    public function bulkLockMatches($matchIds, $lock = true, $lockedBy = 'System') {
        if (empty($matchIds)) {
            return false;
        }
        
        $placeholders = str_repeat('?,', count($matchIds) - 1) . '?';
        
        if ($lock) {
            $sql = "UPDATE home_matches 
                    SET locked = TRUE, locked_at = NOW(), locked_by = ? 
                    WHERE id IN ($placeholders)";
            $params = array_merge([$lockedBy], $matchIds);
        } else {
            $sql = "UPDATE home_matches 
                    SET locked = FALSE, locked_at = NULL, locked_by = NULL 
                    WHERE id IN ($placeholders)";
            $params = $matchIds;
        }
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }
    
    /**
     * Get match assignment statistics
     */
    public function getAssignmentStats() {
        $sql = "SELECT 
                    COUNT(DISTINCT m.id) as total_matches,
                    COUNT(DISTINCT CASE WHEN m.locked = TRUE THEN m.id END) as locked_matches,
                    COUNT(DISTINCT CASE WHEN (m.locked IS NULL OR m.locked = FALSE) THEN m.id END) as unlocked_matches,
                    COUNT(DISTINCT ja.match_id) as matches_with_assignments,
                    COUNT(DISTINCT CASE WHEN m.locked = TRUE THEN ja.match_id END) as locked_matches_with_assignments
                FROM home_matches m
                LEFT JOIN jury_assignments ja ON m.id = ja.match_id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>
