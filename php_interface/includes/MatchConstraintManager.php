<?php

require_once __DIR__ . '/translations.php';

class MatchConstraintManager {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    /**
     * Format constraint message with parameters
     */
    private function formatConstraintMessage($key, $params = []) {
        $message = t($key);
        foreach ($params as $param => $value) {
            $message = str_replace('{' . $param . '}', $value, $message);
        }
        return $message;
    }
    
    /**
     * Check if a team can be assigned as jury for a specific match
     * Returns array of constraint violations with severity levels
     */
    public function checkMatchConstraints($juryTeamName, $match) {
        $violations = [];
        $matchDate = date('Y-m-d', strtotime($match['date_time']));
        $matchTime = date('H:i', strtotime($match['date_time']));
        
        // HARD CONSTRAINTS (must not violate)
        
        // 1. Cannot jury if dedicated to specific team and this match doesn't involve that team
        $dedicatedTeam = $this->getTeamDedication($juryTeamName);
        if ($dedicatedTeam) {
            if ($match['home_team'] !== $dedicatedTeam && $match['away_team'] !== $dedicatedTeam) {
                $violations[] = [
                    'type' => 'wrong_dedication',
                    'severity' => 'HARD',
                    'message' => $this->formatConstraintMessage('dedicated_to_wrong_team', [
                        'team' => $juryTeamName,
                        'dedicated_team' => $dedicatedTeam
                    ]),
                    'score_penalty' => -1000
                ];
            }
        }
        
        // 2. Cannot jury own match
        if ($match['home_team'] === $juryTeamName || $match['away_team'] === $juryTeamName) {
            $violations[] = [
                'type' => 'own_match',
                'severity' => 'HARD',
                'message' => $this->formatConstraintMessage('cannot_jury_own_match', [
                    'team' => $juryTeamName
                ]),
                'score_penalty' => -1000
            ];
        }
        
        // 3. Cannot jury if team has away match same day
        $awayMatches = $this->getTeamAwayMatches($juryTeamName, $matchDate);
        foreach ($awayMatches as $awayMatch) {
            if ($awayMatch['id'] != $match['id']) {
                $violations[] = [
                    'type' => 'away_match_same_day',
                    'severity' => 'HARD',
                    'message' => $this->formatConstraintMessage('away_match_same_day', [
                        'team' => $juryTeamName,
                        'opponent' => $awayMatch['home_team']
                    ]),
                    'score_penalty' => -1000
                ];
            }
        }
        
        // 4. Cannot jury if team has home match within 2 hours
        $nearbyHomeMatches = $this->getTeamHomeMatchesNearTime($juryTeamName, $match['date_time'], 2);
        foreach ($nearbyHomeMatches as $homeMatch) {
            if ($homeMatch['id'] != $match['id']) {
                $timeDiff = round(abs(strtotime($match['date_time']) - strtotime($homeMatch['date_time'])) / 3600, 1);
                $violations[] = [
                    'type' => 'home_match_nearby',
                    'severity' => 'HARD',
                    'message' => $this->formatConstraintMessage('home_match_within_hours', [
                        'team' => $juryTeamName,
                        'opponent' => $homeMatch['away_team'],
                        'hours' => $timeDiff
                    ]),
                    'score_penalty' => -1000
                ];
            }
        }
        
        // SOFT CONSTRAINTS (preferences)
        
        // 5. Prefer teams that have home matches same day (they're already at the location)
        $sameDayHomeMatches = $this->getTeamHomeMatches($juryTeamName, $matchDate);
        foreach ($sameDayHomeMatches as $homeMatch) {
            if ($homeMatch['id'] != $match['id']) {
                $timeDiff = abs(strtotime($match['date_time']) - strtotime($homeMatch['date_time'])) / 3600;
                if ($timeDiff >= 2) {
                    $violations[] = [
                        'type' => 'home_match_same_day_bonus',
                        'severity' => 'BONUS',
                        'message' => $this->formatConstraintMessage('home_match_same_day_bonus', [
                            'team' => $juryTeamName,
                            'opponent' => $homeMatch['away_team']
                        ]),
                        'score_penalty' => +25  // BONUS points for being at the location
                    ];
                }
            }
        }
        
        // 6. Prefer not to jury matches involving teams from same pool/division
        $poolConflict = $this->checkPoolConflict($juryTeamName, $match['home_team'], $match['away_team']);
        if ($poolConflict) {
            $violations[] = [
                'type' => 'same_pool',
                'severity' => 'SOFT',
                'message' => $this->formatConstraintMessage('same_pool_conflict', [
                    'team' => $juryTeamName
                ]),
                'score_penalty' => -30
            ];
        }
        
        // 7. Prefer not to jury consecutive weekends
        $hasConsecutiveWeekends = $this->checkConsecutiveWeekendAssignments($juryTeamName, $matchDate);
        if ($hasConsecutiveWeekends) {
            $violations[] = [
                'type' => 'consecutive_weekends',
                'severity' => 'SOFT',
                'message' => $this->formatConstraintMessage('consecutive_weekends', [
                    'team' => $juryTeamName
                ]),
                'score_penalty' => -20
            ];
        }
        
        // 8. Prefer teams that haven't had recent jury duty (load balancing)
        $recentAssignments = $this->getRecentAssignmentCount($juryTeamName, $matchDate, 14); // last 2 weeks
        if ($recentAssignments > 2) {
            $violations[] = [
                'type' => 'recent_assignments',
                'severity' => 'SOFT',
                'message' => $this->formatConstraintMessage('recent_assignments', [
                    'team' => $juryTeamName,
                    'count' => $recentAssignments
                ]),
                'score_penalty' => -10 * $recentAssignments
            ];
        }
        
        return $violations;
    }
    
    /**
     * Get team's away matches on specific date
     */
    private function getTeamAwayMatches($teamName, $date) {
        $sql = "SELECT * FROM home_matches 
                WHERE away_team = ? 
                AND DATE(date_time) = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$teamName, $date]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get team's home matches on specific date
     */
    private function getTeamHomeMatches($teamName, $date) {
        $sql = "SELECT * FROM home_matches 
                WHERE home_team = ? 
                AND DATE(date_time) = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$teamName, $date]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get team's home matches within X hours of given time
     */
    private function getTeamHomeMatchesNearTime($teamName, $dateTime, $hoursWindow) {
        $startTime = date('Y-m-d H:i:s', strtotime($dateTime) - ($hoursWindow * 3600));
        $endTime = date('Y-m-d H:i:s', strtotime($dateTime) + ($hoursWindow * 3600));
        
        $sql = "SELECT * FROM home_matches 
                WHERE home_team = ? 
                AND date_time BETWEEN ? AND ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$teamName, $startTime, $endTime]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Check if jury team is in same pool as match participants
     * This would need to be implemented based on your pool/division structure
     */
    private function checkPoolConflict($juryTeam, $homeTeam, $awayTeam) {
        // This is a placeholder - you'd need to implement based on your league structure
        // For now, return false
        return false;
    }
    
    /**
     * Check if team has jury assignments on consecutive weekends
     */
    private function checkConsecutiveWeekendAssignments($teamName, $matchDate) {
        $matchWeekend = $this->getWeekendOfDate($matchDate);
        $previousWeekend = date('Y-m-d', strtotime($matchWeekend . ' -7 days'));
        $nextWeekend = date('Y-m-d', strtotime($matchWeekend . ' +7 days'));
        
        $sql = "SELECT COUNT(*) FROM jury_assignments ja
                JOIN home_matches m ON ja.match_id = m.id
                JOIN jury_teams jt ON ja.team_id = jt.id
                WHERE jt.name = ?
                AND (DATE(m.date_time) BETWEEN ? AND ? 
                     OR DATE(m.date_time) BETWEEN ? AND ?)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $teamName, 
            $previousWeekend, date('Y-m-d', strtotime($previousWeekend . ' +1 day')),
            $nextWeekend, date('Y-m-d', strtotime($nextWeekend . ' +1 day'))
        ]);
        
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Get weekend date for a given date (Saturday)
     */
    private function getWeekendOfDate($date) {
        $dayOfWeek = date('w', strtotime($date));
        $daysToSaturday = (6 - $dayOfWeek) % 7;
        return date('Y-m-d', strtotime($date . " +{$daysToSaturday} days"));
    }
    
    /**
     * Get count of recent assignments for a team
     */
    private function getRecentAssignmentCount($teamName, $beforeDate, $days) {
        $startDate = date('Y-m-d', strtotime($beforeDate . " -{$days} days"));
        
        $sql = "SELECT COUNT(*) FROM jury_assignments ja
                JOIN home_matches m ON ja.match_id = m.id
                JOIN jury_teams jt ON ja.team_id = jt.id
                WHERE jt.name = ?
                AND DATE(m.date_time) BETWEEN ? AND ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$teamName, $startDate, $beforeDate]);
        return $stmt->fetchColumn();
    }
    
    /**
     * Calculate eligibility score for a team-match combination
     * Higher score = better choice
     */
    public function calculateEligibilityScore($juryTeamName, $match, $teamCapacityFactor = 1.0) {
        $violations = $this->checkMatchConstraints($juryTeamName, $match);
        
        // Start with base score from capacity
        $score = $teamCapacityFactor * 100;
        
        // Apply penalties
        foreach ($violations as $violation) {
            // Hard constraints make team ineligible
            if ($violation['severity'] === 'HARD') {
                return -1000; // Ineligible
            }
            
            // Apply soft constraint penalties
            $score += $violation['score_penalty'];
        }
        
        return $score;
    }
    
    /**
     * Get all constraint types with descriptions
     */
    public function getConstraintTypes() {
        return [
            'wrong_dedication' => [
                'name' => 'Wrong Team Dedication',
                'severity' => 'HARD',
                'description' => 'Team is dedicated to a specific team but this match doesn\'t involve them'
            ],
            'own_match' => [
                'name' => 'Own Match',
                'severity' => 'HARD',
                'description' => 'Team cannot jury their own match'
            ],
            'away_match_same_day' => [
                'name' => 'Away Match Same Day',
                'severity' => 'HARD',
                'description' => 'Team cannot jury when they have away match same day'
            ],
            'home_match_nearby' => [
                'name' => 'Home Match Within 2 Hours',
                'severity' => 'HARD',
                'description' => 'Team cannot jury within 2 hours of their home match'
            ],
            'same_pool' => [
                'name' => 'Same Pool/Division',
                'severity' => 'SOFT',
                'description' => 'Prefer team not to jury matches in their own pool'
            ],
            'consecutive_weekends' => [
                'name' => 'Consecutive Weekends',
                'severity' => 'SOFT',
                'description' => 'Prefer not to assign jury duty on consecutive weekends'
            ],
            'recent_assignments' => [
                'name' => 'Recent Assignments',
                'severity' => 'SOFT',
                'description' => 'Prefer teams with fewer recent assignments (load balancing)'
            ]
        ];
    }
    
    /**
     * Get the team that a jury team is dedicated to (if any)
     * Returns the name of the team they're dedicated to, or null if not dedicated
     */
    private function getTeamDedication($juryTeamName) {
        // Team dedication feature not implemented - always return null
        return null;
    }
}

?>
