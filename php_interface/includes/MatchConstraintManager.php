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
        
        // 9. Improved weekly distribution - check if team had assignment in previous week
        $lastWeekAssignments = $this->getWeeklyAssignmentCount($juryTeamName, $matchDate, -1); // previous week
        if ($lastWeekAssignments > 0) {
            $violations[] = [
                'type' => 'previous_week_assignment',
                'severity' => 'SOFT',
                'message' => $this->formatConstraintMessage('previous_week_assignment', [
                    'team' => $juryTeamName
                ]),
                'score_penalty' => -25 // Higher penalty than general recent assignments
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
     * Get count of assignments for a team in a specific week relative to given date
     * $weekOffset: -1 = previous week, 0 = current week, 1 = next week
     */
    private function getWeeklyAssignmentCount($teamName, $referenceDate, $weekOffset) {
        // Calculate the start and end of the target week
        $referenceDateObj = new DateTime($referenceDate);
        $dayOfWeek = $referenceDateObj->format('w'); // 0 = Sunday, 6 = Saturday
        
        // Adjust to get Monday as start of week (ISO standard)
        $daysToMonday = ($dayOfWeek === 0) ? -6 : -(($dayOfWeek - 1));
        $weekStart = clone $referenceDateObj;
        $weekStart->modify("{$daysToMonday} days");
        
        // Add week offset
        if ($weekOffset !== 0) {
            $weekStart->modify(($weekOffset * 7) . " days");
        }
        
        $weekEnd = clone $weekStart;
        $weekEnd->modify('+6 days');
        
        $sql = "SELECT COUNT(*) FROM jury_assignments ja
                JOIN home_matches m ON ja.match_id = m.id
                JOIN jury_teams jt ON ja.team_id = jt.id
                WHERE jt.name = ?
                AND DATE(m.date_time) BETWEEN ? AND ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$teamName, $weekStart->format('Y-m-d'), $weekEnd->format('Y-m-d')]);
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
                'name' => t('wrong_team_dedication'),
                'severity' => t('hard'),
                'description' => t('wrong_team_dedication_description')
            ],
            'own_match' => [
                'name' => t('own_match'),
                'severity' => t('hard'),
                'description' => t('own_match_description')
            ],
            'away_match_same_day' => [
                'name' => t('away_match_same_day'),
                'severity' => t('hard'),
                'description' => t('away_match_same_day_description')
            ],
            'consecutive_weekends' => [
                'name' => t('consecutive_weekends'),
                'severity' => t('soft'),
                'description' => t('consecutive_weekends_description')
            ],
            'recent_assignments' => [
                'name' => t('recent_assignments'),
                'severity' => t('soft'),
                'description' => t('recent_assignments_description')
            ],
            'previous_week_assignment' => [
                'name' => t('previous_week_assignment'),
                'severity' => t('soft'),
                'description' => t('previous_week_assignment_description')
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
