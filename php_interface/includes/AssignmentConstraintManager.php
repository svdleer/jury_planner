<?php

require_once __DIR__ . '/translations.php';

class AssignmentConstraintManager {
    private $db;
    private $customConstraintManager;
    private $matchConstraintManager;
    private $fairnessManager;
    
    public function __construct($database) {
        $this->db = $database;
        // Initialize constraint managers if they exist
        if (class_exists('CustomConstraintManager')) {
            $this->customConstraintManager = new CustomConstraintManager($database);
        }
        if (class_exists('MatchConstraintManager')) {
            $this->matchConstraintManager = new MatchConstraintManager($database);
        }
        if (class_exists('FairnessManager')) {
            $this->fairnessManager = new FairnessManager($database);
        }
    }
    
    /**
     * Auto-assign jury teams         $sql = "SELECT COUNT(*) FROM jury_assignments ja
                JOIN home_matches m ON ja.match_id = m.id
                WHERE ja.team_id = ?
                AND DATE(m.date_time) = ?";atches based on constraints
     */
    public function autoAssignJuryTeams($options = []) {
        $results = [
            'success' => false,
            'assignments' => [],
            'conflicts' => [],
            'message' => ''
        ];
        
        try {
            $this->db->beginTransaction();
            
            // Get all matches without jury assignments
            $unassignedMatches = $this->getUnassignedMatches();
            
            if (empty($unassignedMatches)) {
                $results['message'] = 'No unassigned matches found.';
                $this->db->commit();
                return $results;
            }
            
            // Get all available jury teams with their constraints
            $availableTeams = $this->getAvailableTeams();
            
            if (empty($availableTeams)) {
                $results['message'] = 'No jury teams available for assignment.';
                $this->db->commit();
                return $results;
            }
            
            // Apply assignment logic with constraints
            $assignments = $this->calculateOptimalAssignments($unassignedMatches, $availableTeams, $options);
            
            // Execute assignments
            foreach ($assignments as $assignment) {
                $this->createJuryAssignment($assignment['match_id'], $assignment['team_id'], 'Auto-assigned');
                $results['assignments'][] = $assignment;
            }
            
            $this->db->commit();
            $results['success'] = true;
            $results['message'] = count($assignments) . ' jury assignments created successfully.';
            
        } catch (Exception $e) {
            $this->db->rollBack();
            $results['message'] = t('error_during_auto_assignment') . ': ' . $e->getMessage();
        }
        
        return $results;
    }
    
    /**
     * Get matches that don't have jury assignments yet
     */
    private function getUnassignedMatches() {
        $sql = "SELECT m.id, m.date_time, m.home_team, m.away_team
                FROM home_matches m
                LEFT JOIN jury_assignments ja ON m.id = ja.match_id
                WHERE ja.match_id IS NULL
                AND m.date_time >= CURDATE()
                ORDER BY m.date_time";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get available jury teams with their capacity and exclusion constraints
     */
    private function getAvailableTeams() {
        $sql = "SELECT jt.id, jt.name, jt.capacity_factor,
                       COUNT(ja.id) as current_assignments
                FROM jury_teams jt
                LEFT JOIN jury_assignments ja ON jt.id = ja.team_id
                GROUP BY jt.id, jt.name, jt.capacity_factor
                ORDER BY current_assignments ASC, jt.capacity_factor DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Calculate optimal assignments using constraint logic
     */
    private function calculateOptimalAssignments($matches, $teams, $options = []) {
        $assignments = [];
        $teamUsage = [];
        
        // Initialize team usage counter
        foreach ($teams as $team) {
            $teamUsage[$team['id']] = $team['current_assignments'];
        }
        
        foreach ($matches as $match) {
            $bestTeam = $this->findBestTeamForMatch($match, $teams, $teamUsage, $options);
            
            if ($bestTeam) {
                $assignments[] = [
                    'match_id' => $match['id'],
                    'team_id' => $bestTeam['id'],
                    'team_name' => $bestTeam['name'],
                    'match_info' => $match['home_team'] . ' vs ' . $match['away_team']
                ];
                
                // Update team usage for subsequent assignments
                $teamUsage[$bestTeam['id']]++;
            }
        }
        
        return $assignments;
    }
    
    /**
     * Find the best team for a specific match based on constraints
     */
    private function findBestTeamForMatch($match, $teams, $teamUsage, $options = []) {
        $eligibleTeams = [];
        
        foreach ($teams as $team) {
            // Use new match constraint system if available
            if ($this->matchConstraintManager) {
                $score = $this->matchConstraintManager->calculateEligibilityScore(
                    $team['name'], 
                    $match, 
                    $team['capacity_factor']
                );
                
                // Skip teams that violate hard constraints
                if ($score <= -1000) {
                    continue;
                }
                
                // Adjust score for current usage (load balancing)
                $score -= $teamUsage[$team['id']] * 10;
                
                // Apply fairness scoring if available
                if ($this->fairnessManager) {
                    $recommendations = $this->fairnessManager->getAssignmentRecommendations($match['id']);
                    foreach ($recommendations as $rec) {
                        if ($rec['team_id'] == $team['id']) {
                            $score += $rec['priority']; // Add fairness priority to score
                            break;
                        }
                    }
                }
                
                $eligibleTeams[] = [
                    'team' => $team,
                    'score' => $score
                ];
            } else {
                // Fallback to original constraint checking
                if ($this->isTeamExcludedFromMatch($team['id'], $match)) {
                    continue;
                }
                
                if ($this->isTeamUnavailableForDate($team['id'], $match['date_time'])) {
                    continue;
                }
                
                $score = $this->calculateTeamScore($team, $teamUsage[$team['id']], $options);
                
                $eligibleTeams[] = [
                    'team' => $team,
                    'score' => $score
                ];
            }
        }
        
        if (empty($eligibleTeams)) {
            return null;
        }
        
        // Sort by score (higher is better) and return best team
        usort($eligibleTeams, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });
        
        return $eligibleTeams[0]['team'];
    }
    
    /**
     * Check if a team is excluded from a specific match
     */
    private function isTeamExcludedFromMatch($teamId, $match) {
        $teamName = $this->getTeamNameById($teamId);
        
        // Check if team is participating in the match (can't be jury for own games)
        if ($match['home_team'] === $teamName || $match['away_team'] === $teamName) {
            return true;
        }
        
        // Check excluded_teams table - this table now only contains team names that are excluded
        $sql = "SELECT COUNT(*) FROM excluded_teams WHERE name = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$teamName]);
        
        if ($stmt->fetchColumn() > 0) {
            return true;
        }
        
        // Check custom constraints if available
        if ($this->customConstraintManager) {
            $matchDate = date('Y-m-d', strtotime($match['date_time']));
            $violations = $this->customConstraintManager->checkAssignmentConstraints(
                $teamName, 
                $match['home_team'], 
                $match['away_team'], 
                $matchDate
            );
            
            return !empty($violations);
        }
        
        return false;
    }
    
    /**
     * Check if a team is already assigned to another match on the same date
     */
    private function isTeamUnavailableForDate($teamId, $matchDateTime) {
        $matchDate = date('Y-m-d', strtotime($matchDateTime));
        
        $sql = "SELECT COUNT(*) FROM jury_assignments ja
                JOIN home_matches m ON ja.match_id = m.id
                WHERE ja.team_id = ?
                AND DATE(m.date_time) = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$teamId, $matchDate]);
        
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Calculate team score for assignment priority
     */
    private function calculateTeamScore($team, $currentAssignments, $options = []) {
        $score = 0;
        
        // Base score from capacity factor (higher capacity = higher score)
        $baseCapacity = $team['capacity_factor'];
        
        // Check for capacity override in custom constraints
        if ($this->customConstraintManager) {
            $override = $this->customConstraintManager->getCapacityOverride($team['name']);
            if ($override !== null) {
                $baseCapacity = $override;
            }
        }
        
        $score += $baseCapacity * 100;
        
        // Penalty for teams with more assignments (load balancing)
        $score -= $currentAssignments * 10;
        
        // Apply weight preferences if provided
        if (isset($options['prefer_low_usage']) && $options['prefer_low_usage']) {
            $score -= $currentAssignments * 20; // Extra penalty for usage
        }
        
        if (isset($options['prefer_high_capacity']) && $options['prefer_high_capacity']) {
            $score += $baseCapacity * 50; // Extra bonus for capacity
        }
        
        return $score;
    }
    
    /**
     * Get team name by ID
     */
    private function getTeamNameById($teamId) {
        $sql = "SELECT name FROM jury_teams WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$teamId]);
        return $stmt->fetchColumn();
    }
    
    /**
     * Create a jury assignment
     */
    private function createJuryAssignment($matchId, $teamId, $notes = '') {
        $sql = "INSERT INTO jury_assignments (match_id, team_id) VALUES (?, ?)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$matchId, $teamId]);
    }
    
    /**
     * Get assignment statistics
     */
    public function getAssignmentStatistics() {
        $stats = [];
        
        // Team assignment counts
        $sql = "SELECT jt.name, jt.capacity_factor, COUNT(ja.id) as assignment_count
                FROM jury_teams jt
                LEFT JOIN jury_assignments ja ON jt.id = ja.team_id
                GROUP BY jt.id, jt.name, jt.capacity_factor
                ORDER BY assignment_count DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $stats['team_assignments'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Match assignment status
        $sql = "SELECT 
                    COUNT(*) as total_matches,
                    SUM(CASE WHEN ja.match_id IS NOT NULL THEN 1 ELSE 0 END) as assigned_matches,
                    SUM(CASE WHEN ja.match_id IS NULL THEN 1 ELSE 0 END) as unassigned_matches
                FROM home_matches m
                LEFT JOIN jury_assignments ja ON m.id = ja.match_id
                WHERE m.date_time >= CURDATE()";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $stats['match_status'] = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $stats;
    }
}
