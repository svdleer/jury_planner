<?php
/**
 * Advanced Constraint Configuration Manager
 * Handles all jury assignment constraints with enable/disable, hard            [
                'constraint_code' => 'SATURDAY_SUNDAY_BALANCE',
                'constraint_name' => 'Saturday vs Sunday Balance',
                'category' => 'Fairness & Balance',
                'description' => 'Balance Saturday versus Sunday assignments per team across the season.',
                'constraint_type' => 'soft',
                'enabled' => true,
                'weight' => 2.00,
                'penalty_points' => 10
            ], weight settings
 */
class AdvancedConstraintManager {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
        $this->initializeConstraintsTable();
        $this->cleanupInappropriateConstraints();
    }
    
    /**
     * Initialize the constraints configuration table
     */
    private function initializeConstraintsTable() {
        $sql = "CREATE TABLE IF NOT EXISTS constraint_config (
            id INT AUTO_INCREMENT PRIMARY KEY,
            constraint_code VARCHAR(50) UNIQUE NOT NULL,
            constraint_name VARCHAR(255) NOT NULL,
            category VARCHAR(100) NOT NULL,
            description TEXT,
            constraint_type ENUM('hard', 'soft') DEFAULT 'soft',
            enabled BOOLEAN DEFAULT TRUE,
            weight DECIMAL(5,2) DEFAULT 1.00,
            penalty_points INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        
        $this->db->exec($sql);
        $this->seedDefaultConstraints();
    }
    
    /**
     * Seed the database with all the constraint rules
     */
    private function seedDefaultConstraints() {
        $constraints = [
            // Shift Structure & Adjacency
            [
                'constraint_code' => 'SINGLE_SHIFT_PER_DAY',
                'constraint_name' => 'Single Contiguous Shift Per Day',
                'category' => 'Shift Structure & Adjacency',
                'description' => 'Maintain a single, contiguous "shift" per team per day/weekend.',
                'constraint_type' => 'hard',
                'enabled' => true,
                'weight' => 3.00,
                'penalty_points' => 50
            ],
            [
                'constraint_code' => 'MIN_TWO_CONSECUTIVE',
                'constraint_name' => 'Minimum Two Consecutive Matches',
                'category' => 'Shift Structure & Adjacency',
                'description' => 'Each shift should include at least two consecutive matches when possible.',
                'constraint_type' => 'soft',
                'enabled' => true,
                'weight' => 2.00,
                'penalty_points' => 10
            ],
            [
                'constraint_code' => 'MAX_TWO_MATCHES_DEFAULT',
                'constraint_name' => 'Maximum Two Matches (Default)',
                'category' => 'Shift Structure & Adjacency',
                'description' => 'Keep shifts to two matches by default; three allowed in certain situations; four only under special conditions.',
                'constraint_type' => 'soft',
                'enabled' => true,
                'weight' => 2.50,
                'penalty_points' => 15
            ],
            [
                'constraint_code' => 'SIMULTANEOUS_SAME_TEAM',
                'constraint_name' => 'Simultaneous Matches Same Team',
                'category' => 'Shift Structure & Adjacency',
                'description' => 'If multiple matches occur simultaneously, they must go to the same jury team.',
                'constraint_type' => 'hard',
                'enabled' => true,
                'weight' => 4.00,
                'penalty_points' => 100
            ],
            [
                'constraint_code' => 'LAST_MATCH_CONTINUITY',
                'constraint_name' => 'Last Match Continuity',
                'category' => 'Shift Structure & Adjacency',
                'description' => 'If just one match remains, give it to the team that officiated immediately before (unless disallowed).',
                'constraint_type' => 'soft',
                'enabled' => true,
                'weight' => 1.50,
                'penalty_points' => 5
            ],
            [
                'constraint_code' => 'ONE_SHIFT_PER_WEEKEND',
                'constraint_name' => 'One Shift Per Weekend',
                'category' => 'Shift Structure & Adjacency',
                'description' => 'Only one jury shift per weekend per team, and no second separate shift later that day.',
                'constraint_type' => 'hard',
                'enabled' => true,
                'weight' => 3.50,
                'penalty_points' => 75
            ],
            [
                'constraint_code' => 'NO_GAPS_IN_SHIFT',
                'constraint_name' => 'No Gaps in Shift',
                'category' => 'Shift Structure & Adjacency',
                'description' => 'Assignments in a shift must be adjacent with no gaps.',
                'constraint_type' => 'hard',
                'enabled' => true,
                'weight' => 2.75,
                'penalty_points' => 25
            ],
            
            // Fairness & Balance
            [
                'constraint_code' => 'EVEN_SEASON_DISTRIBUTION',
                'constraint_name' => 'Even Season Distribution',
                'category' => 'Fairness & Balance',
                'description' => 'Spread the total number of officiated matches per team evenly across the season.',
                'constraint_type' => 'soft',
                'enabled' => true,
                'weight' => 3.00,
                'penalty_points' => 20
            ],
            [
                'constraint_code' => 'PRESERVE_FREE_WEEKENDS',
                'constraint_name' => 'Preserve Free Weekends',
                'category' => 'Fairness & Balance',
                'description' => 'If a team has a free weekend, keep it free (no jury duty).',
                'constraint_type' => 'soft',
                'enabled' => true,
                'weight' => 1.75,
                'penalty_points' => 10
            ],
            [
                'constraint_code' => 'NO_JURY_WHEN_AWAY',
                'constraint_name' => 'No Jury When Playing Away',
                'category' => 'Fairness & Balance',
                'description' => 'If a team has an away match during that weekend, don\'t schedule them for jury duty.',
                'constraint_type' => 'hard',
                'enabled' => true,
                'weight' => 4.00,
                'penalty_points' => 80
            ],
            [
                'constraint_code' => 'AVOID_REPEATED_FIRST_LAST',
                'constraint_name' => 'Avoid Repeated First/Last Match',
                'category' => 'Fairness & Balance',
                'description' => 'Avoid assigning the same team repeatedly to the first or last match of the day.',
                'constraint_type' => 'soft',
                'enabled' => true,
                'weight' => 1.25,
                'penalty_points' => 8
            ],
            [
                'constraint_code' => 'HISTORICAL_POINT_THRESHOLD',
                'constraint_name' => 'Historical Point Threshold',
                'category' => 'Fairness & Balance',
                'description' => 'Respect historical point/credit differences; keep point gaps within a threshold (e.g. ≤4 points).',
                'constraint_type' => 'soft',
                'enabled' => true,
                'weight' => 2.50,
                'penalty_points' => 12
            ],
            
            // Preference Hierarchy
            [
                'constraint_code' => 'PREFER_HOME_TEAMS',
                'constraint_name' => 'Prefer Home Playing Teams',
                'category' => 'Preference Hierarchy',
                'description' => 'Prefer home-playing teams first.',
                'constraint_type' => 'soft',
                'enabled' => true,
                'weight' => 2.00,
                'penalty_points' => 5
            ],
            [
                'constraint_code' => 'FALLBACK_HOME_OTHER_DAY',
                'constraint_name' => 'Fallback: Home Other Day',
                'category' => 'Preference Hierarchy',
                'description' => 'Next, select fallback teams with a home game on another day of the same weekend (if not excluded).',
                'constraint_type' => 'soft',
                'enabled' => true,
                'weight' => 1.50,
                'penalty_points' => 3
            ],
            [
                'constraint_code' => 'FALLBACK_AWAY_OTHER_DAY',
                'constraint_name' => 'Fallback: Away Other Day',
                'category' => 'Preference Hierarchy',
                'description' => 'If still needed, pick fallback teams with an away match on another day of the same weekend (if not excluded).',
                'constraint_type' => 'soft',
                'enabled' => true,
                'weight' => 1.25,
                'penalty_points' => 2
            ],
            [
                'constraint_code' => 'FALLBACK_DISTANT_TEAMS',
                'constraint_name' => 'Fallback: Distant Teams',
                'category' => 'Preference Hierarchy',
                'description' => 'As a last resort, consider teams that require longer travel to the venue.',
                'constraint_type' => 'soft',
                'enabled' => true,
                'weight' => 1.00,
                'penalty_points' => 1
            ],
            [
                'constraint_code' => 'PREFER_ON_SITE_TEAMS',
                'constraint_name' => 'Prefer On-Site Teams',
                'category' => 'Preference Hierarchy',
                'description' => 'Whenever possible, prefer teams already on-site to reduce additional arrivals.',
                'constraint_type' => 'soft',
                'enabled' => true,
                'weight' => 1.50,
                'penalty_points' => 3
            ],
            
            // Special Cases & Exceptions
            [
                'constraint_code' => 'VOID_MATCH_COMPENSATION',
                'constraint_name' => 'Void Match Compensation',
                'category' => 'Special Cases & Exceptions',
                'description' => 'A "0-point/void/forfeit" match inside a 2-match shift can trigger a compensating 3rd match.',
                'constraint_type' => 'soft',
                'enabled' => true,
                'weight' => 1.00,
                'penalty_points' => 0
            ],
            [
                'constraint_code' => 'VOID_SECOND_MATCH_RULE',
                'constraint_name' => 'Void Second Match Day Rule',
                'category' => 'Special Cases & Exceptions',
                'description' => 'If that voided match is the second match of the day, do not increment the day counter.',
                'constraint_type' => 'soft',
                'enabled' => true,
                'weight' => 1.00,
                'penalty_points' => 0
            ],
            [
                'constraint_code' => 'SAME_TIMESLOT_THIRD_MATCH',
                'constraint_name' => 'Same Timeslot Third Match',
                'category' => 'Special Cases & Exceptions',
                'description' => 'If a team already has two matches and the next match is in the same time slot, allow (or require) a 3rd match to maintain coverage.',
                'constraint_type' => 'soft',
                'enabled' => true,
                'weight' => 2.00,
                'penalty_points' => 5
            ],
            [
                'constraint_code' => 'LAST_MATCH_EXCEPTION',
                'constraint_name' => 'Last Match Exception',
                'category' => 'Special Cases & Exceptions',
                'description' => 'When only one match remains, you may assign a 3rd match to the same team as the previous match—unless it\'s their own match, in which case reshuffle.',
                'constraint_type' => 'soft',
                'enabled' => true,
                'weight' => 1.50,
                'penalty_points' => 3
            ],
            [
                'constraint_code' => 'SPECIAL_TEAM_EXCEPTIONS',
                'constraint_name' => 'Special Team Exceptions',
                'category' => 'Special Cases & Exceptions',
                'description' => 'Certain teams may be allowed exceptions (e.g. exceeding two matches on last-match days or restrictions on carrying remarks).',
                'constraint_type' => 'soft',
                'enabled' => false,
                'weight' => 1.00,
                'penalty_points' => 0
            ],
            [
                'constraint_code' => 'SINGLE_MATCH_LAST_RESORT',
                'constraint_name' => 'Single Match Last Resort',
                'category' => 'Special Cases & Exceptions',
                'description' => 'If there are too few matches on a day, allow single-match shifts as a last resort.',
                'constraint_type' => 'soft',
                'enabled' => true,
                'weight' => 0.50,
                'penalty_points' => 2
            ],
            
            // Scheduling & Tie-breakers
            [
                'constraint_code' => 'LOCK_CONFIRMED_SHIFTS',
                'constraint_name' => 'Lock Confirmed Shifts',
                'category' => 'Scheduling & Tie-breakers',
                'description' => 'Lock confirmed shifts and only reshuffle parts that haven\'t been finalized.',
                'constraint_type' => 'hard',
                'enabled' => true,
                'weight' => 5.00,
                'penalty_points' => 200
            ],
            [
                'constraint_code' => 'TIEBREAK_FEWEST_POINTS',
                'constraint_name' => 'Tiebreaker: Fewest Points',
                'category' => 'Scheduling & Tie-breakers',
                'description' => 'When multiple teams qualify, break ties by the fewest season-to-date jury points.',
                'constraint_type' => 'soft',
                'enabled' => true,
                'weight' => 1.00,
                'penalty_points' => 0
            ],
            [
                'constraint_code' => 'TIEBREAK_LONGEST_SINCE_LAST',
                'constraint_name' => 'Tiebreaker: Longest Since Last',
                'category' => 'Scheduling & Tie-breakers',
                'description' => 'When multiple teams qualify, break ties by the longest time since their last jury shift.',
                'constraint_type' => 'soft',
                'enabled' => true,
                'weight' => 1.00,
                'penalty_points' => 0
            ]
        ];
        
        // Check if constraints already exist
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM constraint_config");
        $stmt->execute();
        $count = $stmt->fetchColumn();
        
        if ($count == 0) {
            // Insert all constraints
            $insertSql = "INSERT INTO constraint_config 
                (constraint_code, constraint_name, category, description, constraint_type, enabled, weight, penalty_points) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->db->prepare($insertSql);
            
            foreach ($constraints as $constraint) {
                $stmt->execute([
                    $constraint['constraint_code'],
                    $constraint['constraint_name'],
                    $constraint['category'],
                    $constraint['description'],
                    $constraint['constraint_type'],
                    $constraint['enabled'],
                    $constraint['weight'],
                    $constraint['penalty_points']
                ]);
            }
        }
    }
    
    /**
     * Clean up inappropriate constraints that don't make sense for water polo
     */
    private function cleanupInappropriateConstraints() {
        $inappropriateConstraints = [
            'FALLBACK_NON_WEEKEND_TEAMS',
            'NON_WEEKEND_TEAMS',
            'WEEKDAY_ASSIGNMENTS',
            'NON_WEEKEND_FALLBACK'
        ];
        
        $placeholders = str_repeat('?,', count($inappropriateConstraints) - 1) . '?';
        $sql = "DELETE FROM constraint_config WHERE constraint_code IN ($placeholders)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($inappropriateConstraints);
    }
    
    /**
     * Remove constraints by name pattern (e.g., "Non-Weekend" patterns)
     */
    public function removeConstraintsByPattern($pattern) {
        $sql = "DELETE FROM constraint_config WHERE constraint_name LIKE ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(["%$pattern%"]);
    }
    
    /**
     * Get all constraints grouped by category
     */
    public function getAllConstraintsByCategory() {
        $sql = "SELECT * FROM constraint_config ORDER BY category, constraint_name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $constraints = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $grouped = [];
        foreach ($constraints as $constraint) {
            $grouped[$constraint['category']][] = $constraint;
        }
        
        return $grouped;
    }
    
    /**
     * Get enabled constraints only
     */
    public function getEnabledConstraints() {
        $sql = "SELECT * FROM constraint_config WHERE enabled = 1 ORDER BY weight DESC, category";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Update a constraint configuration
     */
    public function updateConstraint($id, $data) {
        $sql = "UPDATE constraint_config SET 
                constraint_name = ?, 
                description = ?, 
                constraint_type = ?, 
                enabled = ?, 
                weight = ?, 
                penalty_points = ?, 
                updated_at = CURRENT_TIMESTAMP 
                WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $data['constraint_name'],
            $data['description'],
            $data['constraint_type'],
            $data['enabled'] ? 1 : 0,
            $data['weight'],
            $data['penalty_points'],
            $id
        ]);
    }
    
    /**
     * Get constraint by ID
     */
    public function getConstraintById($id) {
        $sql = "SELECT * FROM constraint_config WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get constraint statistics
     */
    public function getConstraintStats() {
        $sql = "SELECT 
                    COUNT(*) as total_constraints,
                    SUM(CASE WHEN enabled = 1 THEN 1 ELSE 0 END) as enabled_constraints,
                    SUM(CASE WHEN constraint_type = 'hard' THEN 1 ELSE 0 END) as hard_constraints,
                    SUM(CASE WHEN constraint_type = 'soft' THEN 1 ELSE 0 END) as soft_constraints,
                    AVG(weight) as avg_weight,
                    COUNT(DISTINCT category) as categories
                FROM constraint_config";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>
