<?php
require_once 'AdvancedConstraintManager.php';

/**
 * Constraint Validation Engine
 * Validates jury assignments against configured constraints
 */
class ConstraintValidationEngine {
    private $db;
    private $constraintManager;
    private $enabledConstraints;
    
    public function __construct($database) {
        $this->db = $database;
        $this->constraintManager = new AdvancedConstraintManager($database);
        $this->loadEnabledConstraints();
    }
    
    private function loadEnabledConstraints() {
        $this->enabledConstraints = $this->constraintManager->getEnabledConstraints();
    }
    
    /**
     * Validate a potential assignment against all enabled constraints
     */
    public function validateAssignment($matchId, $teamId, $existingAssignments = []) {
        $result = [
            'valid' => true,
            'violations' => [],
            'warnings' => [],
            'total_penalty' => 0,
            'hard_violations' => 0,
            'soft_violations' => 0
        ];
        
        foreach ($this->enabledConstraints as $constraint) {
            $violation = $this->checkConstraint($constraint, $matchId, $teamId, $existingAssignments);
            
            if ($violation) {
                if ($constraint['constraint_type'] === 'hard') {
                    $result['valid'] = false;
                    $result['hard_violations']++;
                    $result['violations'][] = $violation;
                } else {
                    $result['soft_violations']++;
                    $result['warnings'][] = $violation;
                    $result['total_penalty'] += $constraint['penalty_points'] * $constraint['weight'];
                }
            }
        }
        
        return $result;
    }
    
    /**
     * Check a specific constraint
     */
    private function checkConstraint($constraint, $matchId, $teamId, $existingAssignments) {
        switch ($constraint['constraint_code']) {
            case 'SINGLE_SHIFT_PER_DAY':
                return $this->checkSingleShiftPerDay($constraint, $matchId, $teamId);
                
            case 'ONE_SHIFT_PER_WEEKEND':
                return $this->checkOneShiftPerWeekend($constraint, $matchId, $teamId);
                
            case 'NO_JURY_WHEN_AWAY':
                return $this->checkNoJuryWhenAway($constraint, $matchId, $teamId);
                
            case 'PRESERVE_FREE_WEEKENDS':
                return $this->checkPreserveFreeWeekends($constraint, $matchId, $teamId);
                
            case 'HISTORICAL_POINT_THRESHOLD':
                return $this->checkHistoricalPointThreshold($constraint, $matchId, $teamId);
                
            case 'MAX_TWO_MATCHES_DEFAULT':
                return $this->checkMaxTwoMatchesDefault($constraint, $matchId, $teamId, $existingAssignments);
                
            case 'SIMULTANEOUS_SAME_TEAM':
                return $this->checkSimultaneousSameTeam($constraint, $matchId, $teamId);
                
            case 'NO_GAPS_IN_SHIFT':
                return $this->checkNoGapsInShift($constraint, $matchId, $teamId, $existingAssignments);
                
            // Add more constraint checks as needed
            default:
                return null; // Constraint not implemented yet
        }
    }
    
    private function checkSingleShiftPerDay($constraint, $matchId, $teamId) {
        // Get the match date
        $matchDate = $this->getMatchDate($matchId);
        
        // Check if team already has assignments on this date
        $sql = "SELECT COUNT(DISTINCT ja.id) as assignment_count,
                       GROUP_CONCAT(m.id) as match_ids
                FROM jury_assignments ja
                JOIN home_matches m ON ja.match_id = m.id
                WHERE ja.team_id = ? AND DATE(m.date_time) = DATE(?)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$teamId, $matchDate]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['assignment_count'] > 0) {
            // Check if assignments are contiguous
            $matchIds = explode(',', $result['match_ids']);
            if (!$this->areMatchesContiguous($matchIds)) {
                return [
                    'constraint' => $constraint['constraint_name'],
                    'message' => 'Team already has non-contiguous assignments on this day',
                    'severity' => $constraint['constraint_type']
                ];
            }
        }
        
        return null;
    }
    
    private function checkOneShiftPerWeekend($constraint, $matchId, $teamId) {
        $matchDate = $this->getMatchDate($matchId);
        $weekendStart = date('Y-m-d', strtotime('last saturday', strtotime($matchDate . ' +1 day')));
        $weekendEnd = date('Y-m-d', strtotime('next sunday', strtotime($weekendStart)));
        
        $sql = "SELECT COUNT(*) as weekend_assignments
                FROM jury_assignments ja
                JOIN home_matches m ON ja.match_id = m.id
                WHERE ja.team_id = ? 
                AND DATE(m.date_time) BETWEEN ? AND ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$teamId, $weekendStart, $weekendEnd]);
        $count = $stmt->fetchColumn();
        
        if ($count > 0) {
            return [
                'constraint' => $constraint['constraint_name'],
                'message' => 'Team already has jury assignments this weekend',
                'severity' => $constraint['constraint_type']
            ];
        }
        
        return null;
    }
    
    private function checkNoJuryWhenAway($constraint, $matchId, $teamId) {
        $matchDate = $this->getMatchDate($matchId);
        
        // Get team name
        $teamName = $this->getTeamName($teamId);
        
        // Check if team has away matches on this date
        $sql = "SELECT COUNT(*) as away_matches
                FROM home_matches m
                WHERE m.away_team = ? AND DATE(m.date_time) = DATE(?)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$teamName, $matchDate]);
        $awayMatches = $stmt->fetchColumn();
        
        if ($awayMatches > 0) {
            return [
                'constraint' => $constraint['constraint_name'],
                'message' => 'Team has away matches on this date',
                'severity' => $constraint['constraint_type']
            ];
        }
        
        return null;
    }
    
    private function checkMaxTwoMatchesDefault($constraint, $matchId, $teamId, $existingAssignments) {
        $matchDate = $this->getMatchDate($matchId);
        
        $sql = "SELECT COUNT(*) as daily_assignments
                FROM jury_assignments ja
                JOIN home_matches m ON ja.match_id = m.id
                WHERE ja.team_id = ? AND DATE(m.date_time) = DATE(?)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$teamId, $matchDate]);
        $currentAssignments = $stmt->fetchColumn();
        
        if ($currentAssignments >= 2) {
            return [
                'constraint' => $constraint['constraint_name'],
                'message' => 'Team already has 2 or more assignments on this day',
                'severity' => $constraint['constraint_type']
            ];
        }
        
        return null;
    }
    
    private function checkHistoricalPointThreshold($constraint, $matchId, $teamId) {
        // Get current team points and compare with others
        $sql = "SELECT jt1.id, jt1.name,
                       COALESCE(SUM(CASE WHEN jt1.id = ? THEN 1 ELSE 0 END), 0) as target_points,
                       COALESCE(AVG(CASE WHEN jt2.id != ? THEN jury_counts.assignment_count ELSE NULL END), 0) as avg_other_points
                FROM jury_teams jt1
                CROSS JOIN jury_teams jt2
                LEFT JOIN (
                    SELECT ja.team_id, COUNT(*) as assignment_count
                    FROM jury_assignments ja
                    GROUP BY ja.team_id
                ) jury_counts ON jt2.id = jury_counts.team_id
                WHERE jt1.id = ?
                GROUP BY jt1.id, jt1.name";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$teamId, $teamId, $teamId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result && ($result['target_points'] - $result['avg_other_points']) > 4) {
            return [
                'constraint' => $constraint['constraint_name'],
                'message' => 'Team points significantly above average (threshold: 4 points)',
                'severity' => $constraint['constraint_type']
            ];
        }
        
        return null;
    }
    
    private function checkPreserveFreeWeekends($constraint, $matchId, $teamId) {
        $matchDate = $this->getMatchDate($matchId);
        $weekendStart = date('Y-m-d', strtotime('last saturday', strtotime($matchDate . ' +1 day')));
        $weekendEnd = date('Y-m-d', strtotime('next sunday', strtotime($weekendStart)));
        
        // Get total assignments for this team this weekend
        $sql = "SELECT COUNT(*) as weekend_assignments
                FROM jury_assignments ja
                JOIN home_matches m ON ja.match_id = m.id
                WHERE ja.team_id = ? 
                AND DATE(m.date_time) BETWEEN ? AND ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$teamId, $weekendStart, $weekendEnd]);
        $weekendCount = $stmt->fetchColumn();
        
        // Check if this would be their first assignment this weekend
        if ($weekendCount == 0) {
            // Check how many free weekends they've had recently
            $sql = "SELECT COUNT(DISTINCT weekend_dates.weekend_start) as free_weekends
                    FROM (
                        SELECT DATE_SUB(DATE(m.date_time), INTERVAL WEEKDAY(DATE(m.date_time)) + 2 DAY) as weekend_start
                        FROM home_matches m
                        WHERE DATE(m.date_time) >= DATE_SUB(?, INTERVAL 8 WEEK)
                        AND DATE(m.date_time) < ?
                        GROUP BY weekend_start
                    ) weekend_dates
                    LEFT JOIN (
                        SELECT DISTINCT DATE_SUB(DATE(m2.date_time), INTERVAL WEEKDAY(DATE(m2.date_time)) + 2 DAY) as assigned_weekend
                        FROM jury_assignments ja2
                        JOIN home_matches m2 ON ja2.match_id = m2.id
                        WHERE ja2.team_id = ?
                        AND DATE(m2.date_time) >= DATE_SUB(?, INTERVAL 8 WEEK)
                        AND DATE(m2.date_time) < ?
                    ) assigned_weekends ON weekend_dates.weekend_start = assigned_weekends.assigned_weekend
                    WHERE assigned_weekends.assigned_weekend IS NULL";
        
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$matchDate, $weekendStart, $teamId, $matchDate, $weekendStart]);
            $freeWeekends = $stmt->fetchColumn();
            
            if ($freeWeekends < 2) {
                return [
                    'constraint' => $constraint['constraint_name'],
                    'message' => 'Team has had fewer than 2 free weekends in last 8 weeks',
                    'severity' => $constraint['constraint_type']
                ];
            }
        }
        
        return null;
    }
    
    private function checkSimultaneousSameTeam($constraint, $matchId, $teamId) {
        $matchDateTime = $this->getMatchDate($matchId);
        
        // Check if the same team is already assigned to a match at the exact same time
        $sql = "SELECT COUNT(*) as simultaneous_assignments
                FROM jury_assignments ja
                JOIN home_matches m ON ja.match_id = m.id
                WHERE ja.team_id = ? 
                AND m.date_time = ?
                AND m.id != ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$teamId, $matchDateTime, $matchId]);
        $count = $stmt->fetchColumn();
        
        if ($count > 0) {
            return [
                'constraint' => $constraint['constraint_name'],
                'message' => 'Team already assigned to another match at the same time',
                'severity' => $constraint['constraint_type']
            ];
        }
        
        return null;
    }
    
    private function checkNoGapsInShift($constraint, $matchId, $teamId, $existingAssignments) {
        $matchDate = $this->getMatchDate($matchId);
        $matchTime = date('H:i:s', strtotime($matchDate));
        $dayDate = date('Y-m-d', strtotime($matchDate));
        
        // Get all assignments for this team on this day, including the proposed one
        $sql = "SELECT m.id, m.date_time, TIME(m.date_time) as match_time
                FROM jury_assignments ja
                JOIN home_matches m ON ja.match_id = m.id
                WHERE ja.team_id = ? 
                AND DATE(m.date_time) = ?
                ORDER BY m.date_time";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$teamId, $dayDate]);
        $dayAssignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Add the proposed assignment
        $dayAssignments[] = [
            'id' => $matchId,
            'date_time' => $matchDate,
            'match_time' => $matchTime
        ];
        
        // Sort by time
        usort($dayAssignments, function($a, $b) {
            return strcmp($a['match_time'], $b['match_time']);
        });
        
        // Check for gaps larger than 2 hours between consecutive assignments
        for ($i = 0; $i < count($dayAssignments) - 1; $i++) {
            $currentEnd = strtotime($dayAssignments[$i]['match_time']) + (90 * 60); // 90 min match duration
            $nextStart = strtotime($dayAssignments[$i + 1]['match_time']);
            $gapMinutes = ($nextStart - $currentEnd) / 60;
            
            if ($gapMinutes > 120) { // 2 hour gap
                return [
                    'constraint' => $constraint['constraint_name'],
                    'message' => 'Assignment would create a gap larger than 2 hours in shift',
                    'severity' => $constraint['constraint_type']
                ];
            }
        }
        
        return null;
    }
    
    // Helper methods
    private function getMatchDate($matchId) {
        $sql = "SELECT date_time FROM home_matches WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$matchId]);
        return $stmt->fetchColumn();
    }
    
    private function getTeamName($teamId) {
        $sql = "SELECT name FROM jury_teams WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$teamId]);
        return $stmt->fetchColumn();
    }
    
    private function areMatchesContiguous($matchIds) {
        if (count($matchIds) <= 1) return true;
        
        // Get match times and check if they're consecutive
        $placeholders = str_repeat('?,', count($matchIds) - 1) . '?';
        $sql = "SELECT date_time FROM home_matches WHERE id IN ($placeholders) ORDER BY date_time";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($matchIds);
        $times = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Simple check: if times are within reasonable range (e.g., 4 hours)
        $firstTime = strtotime($times[0]);
        $lastTime = strtotime(end($times));
        $hoursDiff = ($lastTime - $firstTime) / 3600;
        
        return $hoursDiff <= 4; // Assume max 4 hours for a contiguous shift
    }
    
    /**
     * Get constraint recommendations for better assignments
     */
    public function getConstraintRecommendations($matchId) {
        $recommendations = [];
        
        // Get all teams and their constraint scores
        $sql = "SELECT id, name FROM jury_teams ORDER BY name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $teams = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($teams as $team) {
            $validation = $this->validateAssignment($matchId, $team['id']);
            $teams_with_scores[] = [
                'team' => $team,
                'validation' => $validation,
                'score' => $this->calculateConstraintScore($validation)
            ];
        }
        
        // Sort by best constraint score
        usort($teams_with_scores, function($a, $b) {
            return $a['score'] <=> $b['score'];
        });
        
        return array_slice($teams_with_scores, 0, 5); // Top 5 recommendations
    }
    
    private function calculateConstraintScore($validation) {
        $score = 0;
        
        // Heavy penalty for hard violations
        $score += $validation['hard_violations'] * 1000;
        
        // Add penalty points for soft violations
        $score += $validation['total_penalty'];
        
        return $score;
    }
}
?>
