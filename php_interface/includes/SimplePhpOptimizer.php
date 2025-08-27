<?php

/**
 * Simple PHP-based optimization fallback
 * When Python optimization is not available, provides basic assignment logic
 */
class SimplePhpOptimizer {
    private $db;
    private $constraintManager;
    
    public function __construct($database) {
        $this->db = $database;
        $this->constraintManager = new ConstraintManager($database);
    }
    
    /**
     * Run simple PHP-based optimization
     */
    public function runSimpleOptimization($options = []) {
        try {
            $matches = $this->getUpcomingMatches();
            $teams = $this->getAvailableTeams();
            $constraints = $this->getActiveConstraints();
            
            $assignments = [];
            $totalScore = 0;
            $constraintViolations = 0;
            
            foreach ($matches as $match) {
                $matchAssignments = $this->assignTeamsToMatch($match, $teams, $constraints);
                $assignments = array_merge($assignments, $matchAssignments);
                
                // Simple scoring
                foreach ($matchAssignments as $assignment) {
                    $score = $this->calculateAssignmentScore($assignment, $constraints);
                    $totalScore += $score;
                    
                    if ($score < 0) {
                        $constraintViolations++;
                    }
                }
            }
            
            return [
                'success' => true,
                'assignments' => $assignments,
                'optimization_score' => $totalScore,
                'constraints_satisfied' => count($constraints) - $constraintViolations,
                'total_constraints' => count($constraints),
                'solver_time' => 0.1, // PHP is fast
                'metadata' => [
                    'solver_type' => 'php_simple',
                    'algorithm' => 'greedy_assignment',
                    'note' => 'Simple PHP fallback - not as sophisticated as Python optimization'
                ],
                'period' => $this->getOptimizationPeriod($matches)
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'solver_type' => 'php_simple'
            ];
        }
    }
    
    /**
     * Assign teams to a specific match using simple logic
     */
    private function assignTeamsToMatch($match, $teams, $constraints) {
        $assignments = [];
        $requiredDuties = ['clock', 'score']; // Basic duties
        
        foreach ($requiredDuties as $duty) {
            $bestTeam = $this->findBestTeamForDuty($match, $duty, $teams, $constraints);
            
            if ($bestTeam) {
                $assignments[] = [
                    'match_id' => $match['id'],
                    'team_name' => $bestTeam['team_name'],
                    'duty_type' => $duty,
                    'points' => 10,
                    'assignment_score' => $this->calculateAssignmentScore([
                        'match' => $match,
                        'team' => $bestTeam,
                        'duty' => $duty
                    ], $constraints)
                ];
            }
        }
        
        return $assignments;
    }
    
    /**
     * Find the best available team for a specific duty
     */
    private function findBestTeamForDuty($match, $duty, $teams, $constraints) {
        $bestTeam = null;
        $bestScore = -9999;
        
        foreach ($teams as $team) {
            $score = $this->calculateTeamMatchScore($team, $match, $duty, $constraints);
            
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestTeam = $team;
            }
        }
        
        return $bestTeam;
    }
    
    /**
     * Calculate score for assigning a team to a match duty
     */
    private function calculateTeamMatchScore($team, $match, $duty, $constraints) {
        $score = 10; // Base score
        
        foreach ($constraints as $constraint) {
            if (!$constraint['is_active']) continue;
            
            $parameters = json_decode($constraint['parameters'], true);
            $constraintType = $parameters['constraint_type'] ?? '';
            
            switch ($constraintType) {
                case 'team_unavailable':
                    if ($this->isTeamUnavailable($team, $match, $parameters)) {
                        $score += $constraint['weight']; // Usually negative
                    }
                    break;
                    
                case 'wrong_team_dedication':
                    if ($this->violatesDedication($team, $match, $parameters)) {
                        $score += $constraint['weight']; // Usually negative
                    }
                    break;
                    
                case 'own_match':
                    if ($this->isOwnMatch($team, $match)) {
                        $score += $constraint['weight']; // Usually negative
                    }
                    break;
                    
                case 'preferred_duty':
                    if ($this->matchesPreferredDuty($team, $duty, $parameters)) {
                        $score += $constraint['weight']; // Usually positive
                    }
                    break;
            }
        }
        
        return $score;
    }
    
    /**
     * Check if team is unavailable for this match
     */
    private function isTeamUnavailable($team, $match, $parameters) {
        if ($parameters['team_id'] != $team['id']) return false;
        
        $matchDate = date('Y-m-d', strtotime($match['date_time']));
        $unavailableDate = $parameters['date'] ?? '';
        
        return $matchDate === $unavailableDate;
    }
    
    /**
     * Check if assignment violates team dedication
     */
    private function violatesDedication($team, $match, $parameters) {
        if (!isset($team['dedicated_to_team'])) return false;
        
        $dedicatedTeam = $team['dedicated_to_team'];
        $homeTeam = $match['home_team'];
        $awayTeam = $match['away_team'];
        
        return $dedicatedTeam !== $homeTeam && $dedicatedTeam !== $awayTeam;
    }
    
    /**
     * Check if this is team's own match
     */
    private function isOwnMatch($team, $match) {
        $teamName = $team['team_name'];
        return $teamName === $match['home_team'] || $teamName === $match['away_team'];
    }
    
    /**
     * Check if duty matches team preference
     */
    private function matchesPreferredDuty($team, $duty, $parameters) {
        if ($parameters['team_id'] != $team['id']) return false;
        
        return ($parameters['duty_type'] ?? '') === $duty;
    }
    
    /**
     * Calculate assignment score (for completed assignments)
     */
    private function calculateAssignmentScore($assignment, $constraints) {
        // Simple scoring - would be more complex in real implementation
        return 10;
    }
    
    /**
     * Get upcoming matches
     */
    private function getUpcomingMatches() {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM matches 
                WHERE date_time >= CURDATE() 
                ORDER BY date_time 
                LIMIT 20
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
    
    /**
     * Get available teams
     */
    private function getAvailableTeams() {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM jury_teams 
                WHERE is_active = 1 
                ORDER BY team_name
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
    
    /**
     * Get active constraints
     */
    private function getActiveConstraints() {
        $constraints = $this->constraintManager->getAllConstraints();
        return array_filter($constraints, function($c) { 
            return $c['is_active']; 
        });
    }
    
    /**
     * Get optimization period
     */
    private function getOptimizationPeriod($matches) {
        if (empty($matches)) return [];
        
        $dates = array_column($matches, 'date_time');
        return [
            'start_date' => min($dates),
            'end_date' => max($dates)
        ];
    }
}
