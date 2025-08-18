<?php
require_once 'ConstraintValidationEngine.php';
require_once 'AdvancedConstraintManager.php';

/**
 * Smart Assignment Engine
 * Automatically assigns jury teams to matches using constraint validation
 */
class SmartAssignmentEngine {
    private $db;
    private $constraintEngine;
    private $constraintManager;
    
    public function __construct($database) {
        $this->db = $database;
        $this->constraintEngine = new ConstraintValidationEngine($database);
        $this->constraintManager = new AdvancedConstraintManager($database);
    }
    
    /**
     * Auto-assign jury teams to unassigned matches
     */
    public function autoAssignMatches($maxAssignments = null, $dryRun = false) {
        $results = [
            'assignments_made' => 0,
            'assignments_failed' => 0,
            'details' => [],
            'warnings' => [],
            'errors' => []
        ];
        
        // Get unassigned matches ordered by date
        $unassignedMatches = $this->getUnassignedMatches();
        
        if ($maxAssignments) {
            $unassignedMatches = array_slice($unassignedMatches, 0, $maxAssignments);
        }
        
        foreach ($unassignedMatches as $match) {
            $assignment = $this->findBestAssignment($match['id']);
            
            if ($assignment['success']) {
                if (!$dryRun) {
                    $success = $this->makeAssignment($match['id'], $assignment['team_id']);
                    
                    if ($success) {
                        $results['assignments_made']++;
                        $results['details'][] = [
                            'match_id' => $match['id'],
                            'match_info' => $match['home_team'] . ' vs ' . $match['away_team'],
                            'date_time' => $match['date_time'],
                            'assigned_team' => $assignment['team_name'],
                            'constraint_score' => $assignment['score'],
                            'violations' => $assignment['validation']['violations'],
                            'warnings' => $assignment['validation']['warnings']
                        ];
                    } else {
                        $results['assignments_failed']++;
                        $results['errors'][] = "Failed to assign team {$assignment['team_name']} to match {$match['id']}";
                    }
                } else {
                    // Dry run - just log what would happen
                    $results['assignments_made']++;
                    $results['details'][] = [
                        'match_id' => $match['id'],
                        'match_info' => $match['home_team'] . ' vs ' . $match['away_team'],
                        'date_time' => $match['date_time'],
                        'would_assign_team' => $assignment['team_name'],
                        'constraint_score' => $assignment['score'],
                        'violations' => $assignment['validation']['violations'],
                        'warnings' => $assignment['validation']['warnings']
                    ];
                }
            } else {
                $results['assignments_failed']++;
                $results['errors'][] = "No suitable team found for match {$match['id']} ({$match['home_team']} vs {$match['away_team']})";
            }
        }
        
        return $results;
    }
    
    /**
     * Find the best team assignment for a specific match
     */
    public function findBestAssignment($matchId) {
        $teams = $this->getAvailableTeams();
        $bestAssignment = null;
        $bestScore = PHP_INT_MAX;
        
        foreach ($teams as $team) {
            $validation = $this->constraintEngine->validateAssignment($matchId, $team['id']);
            
            // Skip teams with hard constraint violations
            if (!$validation['valid']) {
                continue;
            }
            
            $score = $this->calculateAssignmentScore($team['id'], $validation);
            
            if ($score < $bestScore) {
                $bestScore = $score;
                $bestAssignment = [
                    'team_id' => $team['id'],
                    'team_name' => $team['name'],
                    'score' => $score,
                    'validation' => $validation
                ];
            }
        }
        
        return [
            'success' => $bestAssignment !== null,
            'team_id' => $bestAssignment['team_id'] ?? null,
            'team_name' => $bestAssignment['team_name'] ?? null,
            'score' => $bestAssignment['score'] ?? null,
            'validation' => $bestAssignment['validation'] ?? null
        ];
    }
    
    /**
     * Calculate assignment score considering fairness and constraints
     */
    private function calculateAssignmentScore($teamId, $validation) {
        $score = 0;
        
        // Add constraint penalty
        $score += $validation['total_penalty'];
        
        // Add fairness factor (teams with fewer assignments get lower scores)
        $teamAssignmentCount = $this->getTeamAssignmentCount($teamId);
        $averageAssignments = $this->getAverageAssignmentCount();
        
        // Bonus for teams below average (negative score), penalty for above average
        $fairnessFactor = ($teamAssignmentCount - $averageAssignments) * 10;
        $score += $fairnessFactor;
        
        // Add recent assignment penalty (prefer teams that haven't been assigned recently)
        $lastAssignmentDays = $this->getDaysSinceLastAssignment($teamId);
        if ($lastAssignmentDays < 7) {
            $score += (7 - $lastAssignmentDays) * 5; // Penalty for recent assignments
        }
        
        return $score;
    }
    
    /**
     * Get unassigned matches
     */
    private function getUnassignedMatches() {
        $sql = "SELECT m.id, m.home_team, m.away_team, m.date_time
                FROM home_matches m
                LEFT JOIN jury_assignments ja ON m.id = ja.match_id
                WHERE ja.id IS NULL
                AND m.date_time >= NOW()
                ORDER BY m.date_time";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get available teams (not excluded)
     */
    private function getAvailableTeams() {
        $sql = "SELECT jt.id, jt.name
                FROM jury_teams jt
                WHERE jt.name NOT IN (SELECT name FROM excluded_teams)
                ORDER BY jt.name";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get team assignment count
     */
    private function getTeamAssignmentCount($teamId) {
        $sql = "SELECT COUNT(*) FROM jury_assignments WHERE team_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$teamId]);
        return $stmt->fetchColumn();
    }
    
    /**
     * Get average assignment count across all teams
     */
    private function getAverageAssignmentCount() {
        $sql = "SELECT AVG(assignment_count) as avg_count
                FROM (
                    SELECT COUNT(*) as assignment_count
                    FROM jury_assignments ja
                    RIGHT JOIN jury_teams jt ON ja.team_id = jt.id
                    GROUP BY jt.id
                ) team_counts";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchColumn() ?: 0;
    }
    
    /**
     * Get days since last assignment for a team
     */
    private function getDaysSinceLastAssignment($teamId) {
        $sql = "SELECT DATEDIFF(NOW(), MAX(m.date_time)) as days_since
                FROM jury_assignments ja
                JOIN home_matches m ON ja.match_id = m.id
                WHERE ja.team_id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$teamId]);
        $days = $stmt->fetchColumn();
        
        return $days ?: 999; // If no previous assignments, return large number
    }
    
    /**
     * Make the actual assignment
     */
    private function makeAssignment($matchId, $teamId) {
        try {
            $sql = "INSERT INTO jury_assignments (match_id, team_id, assigned_at) VALUES (?, ?, NOW())";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$matchId, $teamId]);
        } catch (Exception $e) {
            error_log("Assignment failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get assignment recommendations for a specific match
     */
    public function getMatchRecommendations($matchId, $limit = 5) {
        $teams = $this->getAvailableTeams();
        $recommendations = [];
        
        foreach ($teams as $team) {
            $validation = $this->constraintEngine->validateAssignment($matchId, $team['id']);
            $score = $this->calculateAssignmentScore($team['id'], $validation);
            
            $recommendations[] = [
                'team_id' => $team['id'],
                'team_name' => $team['name'],
                'score' => $score,
                'valid' => $validation['valid'],
                'hard_violations' => $validation['hard_violations'],
                'soft_violations' => $validation['soft_violations'],
                'penalty_points' => $validation['total_penalty'],
                'violations' => $validation['violations'],
                'warnings' => $validation['warnings'],
                'assignment_count' => $this->getTeamAssignmentCount($team['id']),
                'last_assignment_days' => $this->getDaysSinceLastAssignment($team['id'])
            ];
        }
        
        // Sort by score (lower is better)
        usort($recommendations, function($a, $b) {
            // Valid assignments always come first
            if ($a['valid'] && !$b['valid']) return -1;
            if (!$a['valid'] && $b['valid']) return 1;
            
            return $a['score'] <=> $b['score'];
        });
        
        return array_slice($recommendations, 0, $limit);
    }
    
    /**
     * Validate all current assignments
     */
    public function validateAllAssignments() {
        $sql = "SELECT ja.id, ja.match_id, ja.team_id, jt.name as team_name,
                       m.home_team, m.away_team, m.date_time
                FROM jury_assignments ja
                JOIN jury_teams jt ON ja.team_id = jt.id
                JOIN home_matches m ON ja.match_id = m.id
                ORDER BY m.date_time";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $results = [
            'total_assignments' => count($assignments),
            'valid_assignments' => 0,
            'invalid_assignments' => 0,
            'assignments_with_warnings' => 0,
            'details' => []
        ];
        
        foreach ($assignments as $assignment) {
            $validation = $this->constraintEngine->validateAssignment(
                $assignment['match_id'], 
                $assignment['team_id']
            );
            
            $assignmentResult = [
                'assignment_id' => $assignment['id'],
                'match_info' => $assignment['home_team'] . ' vs ' . $assignment['away_team'],
                'date_time' => $assignment['date_time'],
                'team_name' => $assignment['team_name'],
                'valid' => $validation['valid'],
                'violations' => $validation['violations'],
                'warnings' => $validation['warnings']
            ];
            
            if ($validation['valid']) {
                $results['valid_assignments']++;
                if (count($validation['warnings']) > 0) {
                    $results['assignments_with_warnings']++;
                }
            } else {
                $results['invalid_assignments']++;
            }
            
            $results['details'][] = $assignmentResult;
        }
        
        return $results;
    }
}
?>
