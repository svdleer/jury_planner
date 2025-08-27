<?php

class FairnessManager {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    /**
     * Calculate points for a match assignment based on match importance
     */
    public function calculateMatchPoints($match) {
        $matchId = $match['id'];
        $competition = isset($match['competition']) ? strtolower($match['competition']) : '';
        
        // Get all matches to determine if this is first or last
        $allMatches = $this->getAllMatches();
        if (empty($allMatches)) {
            return 10; // Default points if no matches found
        }
        
        $isFirstMatch = ($matchId == $allMatches[0]['id']);
        $isLastMatch = ($matchId == end($allMatches)['id']);
        $isGoMatch = strpos($competition, 'go') !== false;
        
        // Point assignment logic (matching Python implementation)
        if ($isFirstMatch || $isLastMatch) {
            return 15; // First and last matches are worth more
        } elseif ($isGoMatch) {
            return 10; // GO competition matches
        } else {
            return 10; // Regular matches
        }
    }
    
    /**
     * Get all matches ordered by date
     */
    private function getAllMatches() {
        $sql = "SELECT id, date_time FROM home_matches ORDER BY date_time ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Calculate current team points based on existing assignments
     */
    public function calculateTeamPoints($teamId = null) {
        $teamPoints = [];
        
        // Get all teams or specific team
        if ($teamId) {
            $teamSql = "SELECT id, name FROM jury_teams WHERE id = ?";
            $stmt = $this->db->prepare($teamSql);
            $stmt->execute([$teamId]);
            $teams = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $teamSql = "SELECT id, name FROM jury_teams WHERE id != 99"; // Exclude static team
            $stmt = $this->db->prepare($teamSql);
            $stmt->execute();
            $teams = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        foreach ($teams as $team) {
            $teamPoints[$team['id']] = [
                'team_name' => $team['name'],
                'total_points' => 0,
                'assignments' => []
            ];
            
            // Get all assignments for this team
            $sql = "SELECT m.id, m.date_time, m.home_team, m.away_team, m.competition
                    FROM jury_assignments ja
                    JOIN home_matches m ON ja.match_id = m.id
                    WHERE ja.team_id = ?
                    ORDER BY m.date_time";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$team['id']]);
            $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Group GO competition matches by date/time (same time = one assignment)
            $processedAssignments = $this->groupGoCompetitionMatches($assignments);
            
            foreach ($processedAssignments as $assignment) {
                $points = $assignment['points'];
                $teamPoints[$team['id']]['total_points'] += $points;
                $teamPoints[$team['id']]['assignments'][] = [
                    'match_id' => $assignment['match_id'],
                    'date' => $assignment['date'],
                    'match' => $assignment['match'],
                    'points' => $points,
                    'competition' => $assignment['competition'],
                    'grouped_count' => $assignment['grouped_count'] ?? 1
                ];
            }
        }
        
        return $teamPoints;
    }
    
    /**
     * Calculate fairness metrics (min/max point spread)
     */
    public function calculateFairnessMetrics() {
        $teamPoints = $this->calculateTeamPoints();
        
        if (empty($teamPoints)) {
            return [
                'min_points' => 0,
                'max_points' => 0,
                'points_difference' => 0,
                'fairness_score' => 100,
                'teams_count' => 0
            ];
        }
        
        $totalPointsArray = array_column($teamPoints, 'total_points');
        $minPoints = min($totalPointsArray);
        $maxPoints = max($totalPointsArray);
        $pointsDifference = $maxPoints - $minPoints;
        
        // Calculate fairness score (lower difference = higher score)
        // Perfect fairness = 100, larger differences reduce the score
        $fairnessScore = max(0, 100 - ($pointsDifference * 2));
        
        return [
            'min_points' => $minPoints,
            'max_points' => $maxPoints,
            'points_difference' => $pointsDifference,
            'fairness_score' => $fairnessScore,
            'teams_count' => count($teamPoints),
            'average_points' => count($teamPoints) > 0 ? array_sum($totalPointsArray) / count($teamPoints) : 0
        ];
    }
    
    /**
     * Get team fairness recommendations for new assignments
     */
    public function getAssignmentRecommendations($matchId) {
        $teamPoints = $this->calculateTeamPoints();
        $match = $this->getMatchById($matchId);
        $matchPoints = $this->calculateMatchPoints($match);
        
        $recommendations = [];
        
        foreach ($teamPoints as $teamId => $teamData) {
            $currentPoints = $teamData['total_points'];
            $projectedPoints = $currentPoints + $matchPoints;
            
            // Calculate how this assignment would affect fairness
            $otherTeamPoints = array_column(array_filter($teamPoints, function($k) use ($teamId) {
                return $k != $teamId;
            }, ARRAY_FILTER_USE_KEY), 'total_points');
            
            if (!empty($otherTeamPoints)) {
                $minOthers = min($otherTeamPoints);
                $maxOthers = max($otherTeamPoints);
                
                // Calculate fairness impact
                $currentSpread = max($otherTeamPoints) - min($otherTeamPoints);
                $newSpread = max($projectedPoints, $maxOthers) - min($projectedPoints, $minOthers);
                $fairnessImpact = $currentSpread - $newSpread; // Positive = improves fairness
                
                $recommendations[] = [
                    'team_id' => $teamId,
                    'team_name' => $teamData['team_name'],
                    'current_points' => $currentPoints,
                    'projected_points' => $projectedPoints,
                    'match_points' => $matchPoints,
                    'fairness_impact' => $fairnessImpact,
                    'priority' => $this->calculateAssignmentPriority($currentPoints, $teamPoints, $fairnessImpact)
                ];
            }
        }
        
        // Sort by priority (higher = better for fairness)
        usort($recommendations, function($a, $b) {
            return $b['priority'] <=> $a['priority'];
        });
        
        return $recommendations;
    }
    
    /**
     * Calculate assignment priority based on fairness
     */
    private function calculateAssignmentPriority($currentPoints, $allTeamPoints, $fairnessImpact) {
        $allPoints = array_column($allTeamPoints, 'total_points');
        $averagePoints = array_sum($allPoints) / count($allPoints);
        
        // Priority factors:
        // 1. Teams with fewer points get higher priority
        $pointsDeficit = max(0, $averagePoints - $currentPoints);
        
        // 2. Positive fairness impact is good
        $fairnessBonus = max(0, $fairnessImpact * 10);
        
        // 3. Avoid giving too many points to teams that are already ahead
        $excessPenalty = max(0, ($currentPoints - $averagePoints) * 2);
        
        return $pointsDeficit + $fairnessBonus - $excessPenalty;
    }
    
    /**
     * Get match by ID
     */
    private function getMatchById($matchId) {
        $sql = "SELECT * FROM home_matches WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$matchId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get detailed fairness report
     */
    public function getFairnessReport() {
        $teamPoints = $this->calculateTeamPoints();
        $metrics = $this->calculateFairnessMetrics();
        
        // Add ranking to teams
        uasort($teamPoints, function($a, $b) {
            return $b['total_points'] <=> $a['total_points'];
        });
        
        $rank = 1;
        foreach ($teamPoints as &$team) {
            $team['rank'] = $rank++;
        }
        
        return [
            'metrics' => $metrics,
            'team_details' => $teamPoints,
            'recommendations' => $this->getFairnessRecommendations($teamPoints, $metrics)
        ];
    }
    
    /**
     * Get recommendations to improve fairness
     */
    private function getFairnessRecommendations($teamPoints, $metrics) {
        $recommendations = [];
        
        if ($metrics['points_difference'] > 10) {
            $message = Translations::get('large_point_spread_detected');
            $message = str_replace('{points}', $metrics['points_difference'], $message);
            $recommendations[] = [
                'type' => 'warning',
                'message' => $message
            ];
        }
        
        if ($metrics['fairness_score'] < 70) {
            $message = Translations::get('poor_fairness_score');
            $message = str_replace('{score}', $metrics['fairness_score'], $message);
            $recommendations[] = [
                'type' => 'danger',
                'message' => $message
            ];
        }
        
        // Find teams that need more assignments
        $avgPoints = $metrics['average_points'];
        foreach ($teamPoints as $teamData) {
            if ($teamData['total_points'] < $avgPoints - 5) {
                $message = Translations::get('team_below_average');
                $message = str_replace('{team}', $teamData['team_name'], $message);
                $message = str_replace('{points}', $teamData['total_points'], $message);
                $message = str_replace('{average}', round($avgPoints, 1), $message);
                
                $recommendations[] = [
                    'type' => 'info',
                    'message' => $message
                ];
            }
        }
        
        return $recommendations;
    }
    
    /**
     * Group GO competition matches by date/time - multiple matches at same time count as one assignment
     */
    private function groupGoCompetitionMatches($assignments) {
        $grouped = [];
        $goGroups = [];
        
        foreach ($assignments as $assignment) {
            $competition = $assignment['competition'] ?? '';
            $isGoMatch = stripos($competition, 'go') !== false;
            
            if ($isGoMatch) {
                $timeKey = $assignment['date_time'];
                
                if (!isset($goGroups[$timeKey])) {
                    // First match at this time - create the group
                    $goGroups[$timeKey] = [
                        'match_id' => $assignment['id'],
                        'date' => $assignment['date_time'],
                        'match' => $assignment['home_team'] . ' vs ' . $assignment['away_team'],
                        'points' => 10, // GO competition matches are always 10 points total
                        'competition' => $competition,
                        'grouped_count' => 1,
                        'additional_matches' => []
                    ];
                } else {
                    // Additional match at same time - just track it but don't add points
                    $goGroups[$timeKey]['grouped_count']++;
                    $goGroups[$timeKey]['additional_matches'][] = $assignment['home_team'] . ' vs ' . $assignment['away_team'];
                    $goGroups[$timeKey]['match'] .= ' + ' . $assignment['home_team'] . ' vs ' . $assignment['away_team'];
                }
            } else {
                // Non-GO match - process normally
                $grouped[] = [
                    'match_id' => $assignment['id'],
                    'date' => $assignment['date_time'],
                    'match' => $assignment['home_team'] . ' vs ' . $assignment['away_team'],
                    'points' => $this->calculateMatchPoints($assignment),
                    'competition' => $competition
                ];
            }
        }
        
        // Add all GO groups to the result
        foreach ($goGroups as $group) {
            $grouped[] = $group;
        }
        
        return $grouped;
    }
}

?>
