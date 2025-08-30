<?php
/**
 * Advanced Constraint Editor for Python Model
 * Based on wp-juryv1.0.py constraint analysis
 */

// Load page-specific includes
require_once 'includes/translations.php';
require_once 'config/database.php';
require_once 'includes/ConstraintManager.php';

$pageTitle = t('advanced_constraint_editor');
$pageDescription = t('advanced_constraint_editor_description');

function getConstraintEditorData($constraintManager) {
    // Get all constraints including inactive ones for the editor
    $constraints = $constraintManager->getAllConstraintsForEditor();
    $teams = $constraintManager->getAllTeams();
    
    return [
        'success' => true,
        'constraints' => $constraints,
        'teams' => $teams,
        'constraint_types' => getAvailableConstraintTypes(),
        'rule_types' => getRuleTypes(),
        'stats' => [
            'total_constraints' => count($constraints),
            'active_constraints' => count(array_filter($constraints, function($c) { return $c['is_active']; })),
            'hard_constraints' => count(array_filter($constraints, function($c) { return $c['rule_type'] === 'forbidden'; })),
            'soft_constraints' => count(array_filter($constraints, function($c) { return $c['rule_type'] !== 'forbidden'; }))
        ]
    ];
}

function getAvailableConstraintTypes() {
    return [
        'assignment_balance' => [
            'name' => 'Toewijzing Balancering',
            'description' => 'Balanceer toewijzingen tussen teams voor eerlijkheid',
            'category' => 'Verdeling',
            'parameters' => [
                'max_assignments_per_day' => ['type' => 'number', 'label' => 'Max toewijzingen per dag', 'default' => 3],
                'max_assignments_per_week' => ['type' => 'number', 'label' => 'Max toewijzingen per week', 'default' => 6],
                'applies_to_all_teams' => ['type' => 'boolean', 'label' => 'Van toepassing op alle teams', 'default' => true]
            ]
        ],
        'consecutive_assignments' => [
            'name' => 'Opeenvolgende Toewijzingen Controle',
            'description' => 'Controleer hoeveel opeenvolgende wedstrijden een team kan worden toegewezen',
            'category' => 'Planning',
            'parameters' => [
                'max_consecutive' => ['type' => 'number', 'label' => 'Max consecutive matches', 'default' => 2],
                'allow_groups' => ['type' => 'boolean', 'label' => 'Allow grouped assignments', 'default' => true],
                'prevent_single_last' => ['type' => 'boolean', 'label' => 'Prevent single last match', 'default' => true],
                'applies_to_all_teams' => ['type' => 'boolean', 'label' => 'Apply to all teams', 'default' => true]
            ]
        ],
        'weekend_restrictions' => [
            'name' => 'Weekend Assignment Restrictions',
            'description' => 'Control weekend assignment patterns and prevent overloading',
            'category' => 'Weekend Rules',
            'parameters' => [
                'no_double_weekends' => ['type' => 'boolean', 'label' => 'Prevent consecutive weekends', 'default' => true],
                'max_per_weekend_day' => ['type' => 'number', 'label' => 'Max assignments per weekend day', 'default' => 1],
                'prefer_home_playing_teams' => ['type' => 'boolean', 'label' => 'Prefer teams playing at home', 'default' => true],
                'applies_to_all_teams' => ['type' => 'boolean', 'label' => 'Apply to all teams', 'default' => true]
            ]
        ],
        'team_unavailability' => [
            'name' => 'Team Unavailability',
            'description' => 'Mark specific teams as unavailable for certain dates or periods',
            'category' => 'Availability',
            'parameters' => [
                'team_id' => ['type' => 'team_select', 'label' => 'Team', 'required' => true],
                'start_date' => ['type' => 'date', 'label' => 'Start date', 'required' => true],
                'end_date' => ['type' => 'date', 'label' => 'End date (optional)'],
                'reason' => ['type' => 'text', 'label' => 'Reason']
            ]
        ],
        'go_match_grouping' => [
            'name' => 'GO Competition Grouping',
            'description' => 'Ensure GO competition matches are assigned consistently',
            'category' => 'Competition Rules',
            'parameters' => [
                'force_same_team' => ['type' => 'boolean', 'label' => 'Force same team for GO matches', 'default' => true],
                'allow_different_for_odd' => ['type' => 'boolean', 'label' => 'Allow different assignment for odd GO match', 'default' => true],
                'applies_to_all_teams' => ['type' => 'boolean', 'label' => 'Apply to all teams', 'default' => true]
            ]
        ],
        'team_restrictions' => [
            'name' => 'Team Assignment Restrictions',
            'description' => 'Prevent teams from jury duties in specific scenarios',
            'category' => 'Conflict Prevention',
            'parameters' => [
                'restrict_own_matches' => ['type' => 'boolean', 'label' => 'Cannot jury own matches', 'default' => true],
                'restrict_away_day' => ['type' => 'boolean', 'label' => 'Cannot jury when playing away', 'default' => true],
                'applies_to_all_teams' => ['type' => 'boolean', 'label' => 'Apply to all teams', 'default' => true]
            ]
        ],
        'home_team_restriction' => [
            'name' => 'Home Team Cannot Jury Own Match',
            'description' => 'Teams cannot jury their own matches (home or away)',
            'category' => 'Conflict Prevention',
            'parameters' => [
                'include_away_matches' => ['type' => 'boolean', 'label' => 'Include away matches', 'default' => true],
                'applies_to_all_teams' => ['type' => 'boolean', 'label' => 'Apply to all teams', 'default' => true]
            ]
        ],
        'away_match_conflict' => [
            'name' => 'Away Match Day Restriction',
            'description' => 'Teams cannot jury when they have away matches on the same day',
            'category' => 'Conflict Prevention',
            'parameters' => [
                'strict_mode' => ['type' => 'boolean', 'label' => 'Strict mode (no exceptions)', 'default' => true],
                'applies_to_all_teams' => ['type' => 'boolean', 'label' => 'Apply to all teams', 'default' => true]
            ]
        ],
        'dedicated_team_assignment' => [
            'name' => 'Dedicated Team Assignment',
            'description' => 'Teams dedicated to specific teams can only jury their matches',
            'category' => 'Team Dedication',
            'parameters' => [
                'team_id' => ['type' => 'team_select', 'label' => 'Jury team', 'required' => true],
                'dedicated_to_teams' => ['type' => 'multi_team_select', 'label' => 'Dedicated to teams', 'required' => true],
                'allow_last_match_exception' => ['type' => 'boolean', 'label' => 'Allow last match exception', 'default' => false]
            ]
        ],
        'cross_day_restrictions' => [
            'name' => 'Cross-Day Assignment Restrictions',
            'description' => 'Prevent assignments on consecutive days or specific patterns',
            'category' => 'Scheduling',
            'parameters' => [
                'no_consecutive_days' => ['type' => 'boolean', 'label' => 'No consecutive days', 'default' => true],
                'min_rest_days' => ['type' => 'number', 'label' => 'Minimum rest days between assignments', 'default' => 0],
                'applies_to_all_teams' => ['type' => 'boolean', 'label' => 'Apply to all teams', 'default' => true]
            ]
        ],
        'proximity_preference' => [
            'name' => 'Proximity and Efficiency',
            'description' => 'Prefer assignments that group matches efficiently',
            'category' => 'Optimization',
            'parameters' => [
                'prefer_consecutive' => ['type' => 'boolean', 'label' => 'Prefer consecutive matches', 'default' => true],
                'min_group_size' => ['type' => 'number', 'label' => 'Minimum group size', 'default' => 2],
                'penalty_for_gaps' => ['type' => 'number', 'label' => 'Penalty for gaps between assignments', 'default' => 10],
                'applies_to_all_teams' => ['type' => 'boolean', 'label' => 'Apply to all teams', 'default' => true]
            ]
        ],
        'point_balancing' => [
            'name' => 'Point System Balancing',
            'description' => 'Balance points earned across teams for fairness',
            'category' => 'Fairness',
            'parameters' => [
                'max_point_difference' => ['type' => 'number', 'label' => 'Maximum point difference between teams', 'default' => 20],
                'prefer_lower_point_teams' => ['type' => 'boolean', 'label' => 'Prefer teams with fewer points', 'default' => true],
                'first_match_points' => ['type' => 'number', 'label' => 'Points for first match', 'default' => 15],
                'last_match_points' => ['type' => 'number', 'label' => 'Points for last match', 'default' => 15],
                'regular_match_points' => ['type' => 'number', 'label' => 'Points for regular match', 'default' => 10],
                'go_match_points' => ['type' => 'number', 'label' => 'Points for GO match', 'default' => 10]
            ]
        ],
        'quiet_day_optimization' => [
            'name' => 'Quiet Day Optimization',
            'description' => 'Optimize assignments for days with few matches',
            'category' => 'Optimization',
            'parameters' => [
                'prefer_playing_teams' => ['type' => 'boolean', 'label' => 'Prefer teams playing on the day', 'default' => true],
                'two_match_strategy' => ['type' => 'select', 'label' => 'Two match day strategy', 'options' => ['both_to_playing', 'split_evenly'], 'default' => 'both_to_playing'],
                'three_match_strategy' => ['type' => 'select', 'label' => 'Three match day strategy', 'options' => ['two_plus_one', 'split_evenly'], 'default' => 'two_plus_one']
            ]
        ],
        'da1_da2_restriction' => [
            'name' => 'Da1/Da2 Mutual Exclusion',
            'description' => 'Da1 and Da2 teams cannot jury each other\'s matches',
            'category' => 'Conflict Prevention',
            'parameters' => [
                'strict_enforcement' => ['type' => 'boolean', 'label' => 'Strict enforcement', 'default' => true]
            ]
        ],
        'h1_h2_special_rules' => [
            'name' => 'H1/H2 Special Assignment Rules',
            'description' => 'Special rules for H1 and H2 team assignments',
            'category' => 'Special Rules',
            'parameters' => [
                'exclude_from_general_pool' => ['type' => 'boolean', 'label' => 'Exclude from general assignment pool', 'default' => false],
                'priority_for_important_matches' => ['type' => 'boolean', 'label' => 'Priority for important matches', 'default' => true]
            ]
        ],
        'workload_distribution' => [
            'name' => 'Historical Workload Distribution',
            'description' => 'Consider historical assignments when distributing new ones',
            'category' => 'Fairness',
            'parameters' => [
                'lookback_weeks' => ['type' => 'number', 'label' => 'Weeks to look back', 'default' => 4],
                'weight_recent_assignments' => ['type' => 'number', 'label' => 'Weight for recent assignments', 'default' => 2.0],
                'applies_to_all_teams' => ['type' => 'boolean', 'label' => 'Apply to all teams', 'default' => true]
            ]
        ],
        'custom_constraint' => [
            'name' => 'Custom Python Constraint',
            'description' => 'Define a custom constraint with Python-like logic',
            'category' => 'Advanced',
            'parameters' => [
                'constraint_name' => ['type' => 'text', 'label' => 'Constraint name', 'required' => true],
                'python_logic' => ['type' => 'textarea', 'label' => 'Python constraint logic', 'required' => true],
                'weight_multiplier' => ['type' => 'number', 'label' => 'Weight multiplier', 'default' => 1.0]
            ]
        ]
    ];
}

function getRuleTypes() {
    return [
        'forbidden' => [
            'label' => t('rule_type_forbidden'),
            'description' => 'Hard constraint that must not be violated',
            'weight_range' => [-10000, -100],
            'default_weight' => -1000,
            'color' => 'red'
        ],
        'not_preferred' => [
            'label' => t('rule_type_not_preferred'),
            'description' => 'Strongly discouraged with high penalty',
            'weight_range' => [-100, -10],
            'default_weight' => -50,
            'color' => 'orange'
        ],
        'less_preferred' => [
            'label' => t('rule_type_less_preferred'),
            'description' => 'Slightly discouraged with moderate penalty',
            'weight_range' => [-30, -1],
            'default_weight' => -15,
            'color' => 'yellow'
        ],
        'most_preferred' => [
            'label' => t('rule_type_most_preferred'),
            'description' => 'Highly encouraged with strong positive weight',
            'weight_range' => [1, 100],
            'default_weight' => 50,
            'color' => 'green'
        ]
    ];
}

function createAdvancedConstraint($constraintManager, $data) {
    // Validate the constraint data
    $validation = validateAdvancedConstraint($data);
    if (!$validation['success']) {
        return $validation;
    }
    
    // Process the constraint data
    $constraintData = processConstraintData($data);
    
    // Create the constraint
    return $constraintManager->createConstraint($constraintData);
}

function updateAdvancedConstraint($constraintManager, $data) {
    if (empty($data['id'])) {
        return ['success' => false, 'error' => 'Constraint ID is required'];
    }
    
    // Validate the constraint data
    $validation = validateAdvancedConstraint($data);
    if (!$validation['success']) {
        return $validation;
    }
    
    // Process the constraint data
    $constraintData = processConstraintData($data);
    
    // Update the constraint
    return $constraintManager->updateConstraint($data['id'], $constraintData);
}

function deleteAdvancedConstraint($constraintManager, $data) {
    if (empty($data['id'])) {
        return ['success' => false, 'error' => 'Constraint ID is required'];
    }
    
    return $constraintManager->deleteConstraint($data['id']);
}

function toggleAdvancedConstraint($constraintManager, $data) {
    if (empty($data['id'])) {
        return ['success' => false, 'error' => 'Constraint ID is required'];
    }
    
    return $constraintManager->toggleConstraint($data['id']);
}

function importPythonConstraints($constraintManager) {
    // Import constraints based on wp-juryv1.0.py analysis
    $pythonConstraints = [
        [
            'name' => 'One Team Per Match',
            'description' => 'Each match must have exactly one jury team assigned',
            'rule_type' => 'forbidden',
            'weight' => -10000,
            'constraint_type' => 'one_team_per_match',
            'parameters' => ['applies_to_all_matches' => true]
        ],
        [
            'name' => 'Forbid Second Assignment Same Day',
            'description' => 'Prevent teams from getting a second assignment on the same day unless consecutive',
            'rule_type' => 'forbidden',
            'weight' => -1000,
            'constraint_type' => 'forbid_2nd_assignment',
            'parameters' => ['applies_to_all_teams' => true, 'allow_consecutive' => true]
        ],
        [
            'name' => 'Consecutive Matches Preference',
            'description' => 'Encourage consecutive match assignments and discourage single assignments',
            'rule_type' => 'most_preferred',
            'weight' => 100,
            'constraint_type' => 'consecutive_matches',
            'parameters' => [
                'reward_two_consecutive' => -1,
                'penalty_single_match' => 2,
                'reward_three_consecutive' => -1,
                'applies_to_all_teams' => true
            ]
        ],
        [
            'name' => 'Maximum Assignments Per Day',
            'description' => 'Limit maximum assignments per day with special GO match rules',
            'rule_type' => 'forbidden',
            'weight' => -1000,
            'constraint_type' => 'max_assignments_per_day',
            'parameters' => [
                'max_assignments' => 3,
                'allow_four_for_go_matches' => true,
                'special_odd_day_rules' => true,
                'applies_to_all_teams' => true
            ]
        ],
        [
            'name' => 'Away Team Cannot Jury',
            'description' => 'Teams with away matches cannot jury on the same day',
            'rule_type' => 'forbidden',
            'weight' => -10000,
            'constraint_type' => 'no_away_team_jury',
            'parameters' => ['applies_to_all_teams' => true]
        ],
        [
            'name' => 'GO Matches Same Team',
            'description' => 'GO competition matches should be assigned to the same team',
            'rule_type' => 'forbidden',
            'weight' => -1000,
            'constraint_type' => 'go_matches_constraint',
            'parameters' => [
                'enforce_same_team' => true,
                'handle_different_times' => true,
                'complex_go_scenarios' => true
            ]
        ],
        [
            'name' => 'Team Cannot Jury Own Match',
            'description' => 'Teams cannot jury their own home or away matches',
            'rule_type' => 'forbidden',
            'weight' => -10000,
            'constraint_type' => 'team_not_jury_own',
            'parameters' => ['applies_to_all_teams' => true]
        ],
        [
            'name' => 'Da1/Da2 Mutual Exclusion',
            'description' => 'Da1 cannot jury Da2 matches and vice versa',
            'rule_type' => 'forbidden',
            'weight' => -10000,
            'constraint_type' => 'd1_d2_constraint',
            'parameters' => ['enforce_mutual_exclusion' => true]
        ],
        [
            'name' => 'No Double Weekend Assignments',
            'description' => 'Prevent teams from being assigned on consecutive weekends',
            'rule_type' => 'forbidden',
            'weight' => -1000,
            'constraint_type' => 'no_double_weekend',
            'parameters' => ['applies_to_all_teams' => true, 'exclude_static_team' => true]
        ],
        [
            'name' => 'Weekend Assignment Limit',
            'description' => 'Limit teams to at most one match per weekend day',
            'rule_type' => 'forbidden',
            'weight' => -1000,
            'constraint_type' => 'weekend_assignment_limit',
            'parameters' => ['max_per_weekend_day' => 1]
        ],
        [
            'name' => 'No Consecutive Day Assignments',
            'description' => 'Prevent assignments on consecutive days',
            'rule_type' => 'not_preferred',
            'weight' => -100,
            'constraint_type' => 'no_consecutive_days',
            'parameters' => ['applies_to_all_teams' => true, 'exclude_static_team' => true]
        ],
        [
            'name' => 'No Single Last Match',
            'description' => 'Prevent teams from being assigned only to the last match of the day',
            'rule_type' => 'forbidden',
            'weight' => -1000,
            'constraint_type' => 'no_single_last_match',
            'parameters' => ['applies_to_all_teams' => true, 'exclude_static_team' => true]
        ],
        [
            'name' => 'Prefer Home Playing Teams',
            'description' => 'Prefer teams that are playing at home for jury assignments',
            'rule_type' => 'most_preferred',
            'weight' => 50,
            'constraint_type' => 'prefer_home_playing',
            'parameters' => ['preference_multiplier' => 1]
        ],
        [
            'name' => 'Quiet Match Day Optimization',
            'description' => 'Optimize assignments for days with 2-3 matches',
            'rule_type' => 'most_preferred',
            'weight' => 50,
            'constraint_type' => 'quiet_match_day',
            'parameters' => [
                'two_match_penalty' => 50,
                'three_match_penalty' => 50,
                'prefer_playing_teams' => true
            ]
        ],
        [
            'name' => 'Proximity Constraint',
            'description' => 'Prefer assignments that minimize gaps between matches',
            'rule_type' => 'less_preferred',
            'weight' => -10,
            'constraint_type' => 'proximity_constraint',
            'parameters' => ['penalty_weight' => 10]
        ],
        [
            'name' => 'Weekend Jury Preference',
            'description' => 'Soft constraint for jury duty based on home/away matches in weekend',
            'rule_type' => 'not_preferred',
            'weight' => -1000,
            'constraint_type' => 'weekend_jury_preference',
            'parameters' => [
                'heavy_penalty_away_weekend' => true,
                'preference_no_home_match' => true
            ]
        ],
        [
            'name' => 'Point System Balancing',
            'description' => 'Minimize point differences between teams',
            'rule_type' => 'most_preferred',
            'weight' => 1,
            'constraint_type' => 'point_balancing',
            'parameters' => [
                'first_match_points' => 15,
                'last_match_points' => 15,
                'go_match_points' => 10,
                'regular_match_points' => 10,
                'minimize_difference' => true
            ]
        ]
    ];
    
    $imported = 0;
    $skipped = 0;
    
    foreach ($pythonConstraints as $constraint) {
        try {
            // Check if constraint already exists
            $existing = $constraintManager->getAllConstraints();
            $exists = false;
            
            foreach ($existing as $existingConstraint) {
                if ($existingConstraint['name'] === $constraint['name']) {
                    $exists = true;
                    break;
                }
            }
            
            if ($exists) {
                $skipped++;
                continue;
            }
            
            // Convert to the format expected by ConstraintManager
            $constraintData = [
                'name' => $constraint['name'],
                'description' => $constraint['description'],
                'rule_type' => $constraint['rule_type'],
                'weight' => $constraint['weight'],
                'constraint_type' => $constraint['constraint_type']
            ];
            
            // Add parameters
            foreach ($constraint['parameters'] as $key => $value) {
                $constraintData['param_' . $key] = $value;
            }
            
            $result = $constraintManager->createConstraint($constraintData);
            
            if ($result['success']) {
                $imported++;
            }
            
        } catch (Exception $e) {
            error_log("Error importing Python constraint '{$constraint['name']}': " . $e->getMessage());
        }
    }
    
    return [
        'success' => true,
        'imported' => $imported,
        'skipped' => $skipped,
        'message' => "Imported $imported Python constraints, skipped $skipped existing ones"
    ];
}

function validateAdvancedConstraint($data) {
    $errors = [];
    
    // Basic validation
    if (empty($data['name'])) {
        $errors[] = 'Constraint name is required';
    }
    
    if (empty($data['constraint_type'])) {
        $errors[] = 'Constraint type is required';
    }
    
    if (empty($data['rule_type'])) {
        $errors[] = 'Rule type is required';
    }
    
    if (!isset($data['weight']) || !is_numeric($data['weight'])) {
        $errors[] = 'Valid weight is required';
    }
    
    // Constraint-specific validation
    if (!empty($data['constraint_type'])) {
        $constraintTypes = getAvailableConstraintTypes();
        
        if (isset($constraintTypes[$data['constraint_type']])) {
            $constraintDef = $constraintTypes[$data['constraint_type']];
            
            foreach ($constraintDef['parameters'] as $param => $paramDef) {
                if (!empty($paramDef['required']) && empty($data['param_' . $param])) {
                    $errors[] = $paramDef['label'] . ' is required';
                }
            }
        }
    }
    
    return [
        'success' => empty($errors),
        'errors' => $errors
    ];
}

function processConstraintData($data) {
    return [
        'name' => $data['name'],
        'description' => $data['description'] ?? '',
        'rule_type' => $data['rule_type'],
        'weight' => floatval($data['weight']),
        'constraint_type' => $data['constraint_type'],
        // All param_* fields will be automatically handled by ConstraintManager
        ...$data
    ];
}

// Get initial data
try {
    $database = new Database();
    $pdo = $database->getConnection();
    $constraintManager = new ConstraintManager($pdo);
    $data = getConstraintEditorData($constraintManager);
} catch (Exception $e) {
    $error = "Database error: " . $e->getMessage();
}

ob_start();
?>

<div class="max-w-7xl mx-auto">
    
    <?php if (isset($error)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
            <i class="fas fa-exclamation-triangle mr-2"></i>
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white p-4 rounded-lg shadow border-l-4 border-blue-500">
            <div class="flex items-center">
                <i class="fas fa-list text-blue-500 text-xl mr-3"></i>
                <div>
                    <p class="text-sm text-gray-600"><?php echo t('total_constraints'); ?></p>
                    <p class="text-2xl font-bold" id="totalConstraints">-</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white p-4 rounded-lg shadow border-l-4 border-green-500">
            <div class="flex items-center">
                <i class="fas fa-check-circle text-green-500 text-xl mr-3"></i>
                <div>
                    <p class="text-sm text-gray-600"><?php echo t('active_constraints'); ?></p>
                    <p class="text-2xl font-bold" id="activeConstraints">-</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white p-4 rounded-lg shadow border-l-4 border-red-500">
            <div class="flex items-center">
                <i class="fas fa-ban text-red-500 text-xl mr-3"></i>
                <div>
                    <p class="text-sm text-gray-600"><?php echo t('hard_rules'); ?></p>
                    <p class="text-2xl font-bold" id="hardConstraints">-</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white p-4 rounded-lg shadow border-l-4 border-yellow-500">
            <div class="flex items-center">
                <i class="fas fa-balance-scale text-yellow-500 text-xl mr-3"></i>
                <div>
                    <p class="text-sm text-gray-600"><?php echo t('soft_rules'); ?></p>
                    <p class="text-2xl font-bold" id="softConstraints">-</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <div class="flex flex-wrap gap-3">
            <button onclick="showCreateConstraintModal()" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700">
                <i class="fas fa-plus mr-2"></i>
                <?php echo t('create_new_constraint'); ?>
            </button>
            
            <button onclick="refreshData()" class="bg-gray-500 text-white px-6 py-3 rounded-lg hover:bg-gray-600">
                <i class="fas fa-sync mr-2"></i>
                <?php echo t('refresh'); ?>
            </button>
        </div>
    </div>

    <!-- Constraints List -->
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-bold mb-4">
            <i class="fas fa-cogs mr-2"></i>
            <?php echo t('existing_constraints'); ?>
        </h2>
        
        <div id="constraintsList">
            <div class="text-center py-8">
                <i class="fas fa-spinner fa-spin text-gray-400 text-3xl mb-4"></i>
                <p class="text-gray-500"><?php echo t('loading'); ?>...</p>
            </div>
        </div>
    </div>
</div>

<!-- Create/Edit Constraint Modal -->
<div id="constraintModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900" id="modalTitle">
                    <?php echo t('create_new_constraint'); ?>
                </h3>
                <button onclick="hideConstraintModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <form id="constraintForm" onsubmit="saveConstraint(event)">
                <input type="hidden" id="constraintId" name="id">
                
                <!-- Basic Information -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1"><?php echo t('constraint_name'); ?></label>
                        <input type="text" id="constraintName" name="name" required class="w-full border border-gray-300 rounded-md px-3 py-2">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1"><?php echo t('constraint_type'); ?></label>
                        <select id="constraintType" name="constraint_type" required onchange="updateConstraintParameters()" class="w-full border border-gray-300 rounded-md px-3 py-2">
                            <option value=""><?php echo t('select_constraint_type'); ?></option>
                        </select>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1"><?php echo t('rule_type'); ?></label>
                        <select id="ruleType" name="rule_type" required onchange="updateWeightSuggestion()" class="w-full border border-gray-300 rounded-md px-3 py-2">
                            <option value=""><?php echo t('select_constraint_type'); ?></option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1"><?php echo t('weight'); ?></label>
                        <input type="number" id="constraintWeight" name="weight" step="0.1" required class="w-full border border-gray-300 rounded-md px-3 py-2">
                        <p class="text-xs text-gray-500 mt-1" id="weightSuggestion"></p>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1"><?php echo t('description'); ?></label>
                    <textarea id="constraintDescription" name="description" rows="3" class="w-full border border-gray-300 rounded-md px-3 py-2"></textarea>
                </div>
                
                <!-- Dynamic Parameters -->
                <div id="constraintParameters" class="mb-4">
                    <!-- Parameters will be dynamically generated here -->
                </div>
                
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="hideConstraintModal()" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                        <?php echo t('cancel'); ?>
                    </button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                        <span id="saveButtonText"><?php echo t('create_constraint'); ?></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let currentData = null;
let constraintTypes = {};
let ruleTypes = {};
let teams = [];

// Load data on page load
document.addEventListener('DOMContentLoaded', function() {
    refreshData();
});

async function refreshData() {
    try {
        const response = await fetch('ajax_constraint_handler.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=get_data'
        });
        
        const data = await response.json();
        
        if (data.success) {
            currentData = data;
            constraintTypes = data.constraint_types;
            ruleTypes = data.rule_types;
            teams = data.teams;
            
            // Debug: Check constraint types count
            console.log('Loaded constraint types:', Object.keys(constraintTypes).length, 'types');
            console.log('Constraint type keys:', Object.keys(constraintTypes));
            
            updateDisplay(data);
        } else {
            showError('<?php echo t('error_loading_data'); ?>: ' + data.error);
        }
    } catch (error) {
        showError('<?php echo t('error_occurred'); ?>: ' + error.message);
    }
}

function updateDisplay(data) {
    // Update statistics
    document.getElementById('totalConstraints').textContent = data.stats.total_constraints;
    document.getElementById('activeConstraints').textContent = data.stats.active_constraints;
    document.getElementById('hardConstraints').textContent = data.stats.hard_constraints;
    document.getElementById('softConstraints').textContent = data.stats.soft_constraints;
    
    // Update constraints list
    updateConstraintsList(data.constraints);
    
    // Update modal dropdowns
    updateModalDropdowns();
}

function updateConstraintsList(constraints) {
    const container = document.getElementById('constraintsList');
    
    if (constraints.length === 0) {
        container.innerHTML = `
            <div class="text-center py-8">
                <i class="fas fa-plus-circle text-gray-400 text-3xl mb-4"></i>
                <p class="text-gray-500 mb-4"><?php echo t('no_constraints_found'); ?></p>
                <button onclick="showCreateConstraintModal()" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                    <?php echo t('create_new_constraint'); ?>
                </button>
            </div>
        `;
        return;
    }
    
    // Group constraints by category
    const categories = {};
    constraints.forEach(constraint => {
        const params = constraint.parameters || {};
        const type = params.constraint_type || 'other';
        const category = constraintTypes[type]?.category || 'Other';
        
        if (!categories[category]) {
            categories[category] = [];
        }
        categories[category].push(constraint);
    });
    
    let html = '';
    
    Object.keys(categories).sort().forEach(category => {
        html += `
            <div class="mb-6">
                <h3 class="text-lg font-medium text-gray-900 mb-3 border-b border-gray-200 pb-2">
                    ${category}
                </h3>
                <div class="grid grid-cols-1 gap-4">
        `;
        
        categories[category].forEach(constraint => {
            const ruleType = ruleTypes[constraint.rule_type] || {};
            const isActive = constraint.is_active;
            
            html += `
                <div class="border rounded-lg p-4 ${isActive ? 'border-gray-200' : 'border-gray-100 bg-gray-50'}">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center space-x-2 mb-2">
                                <h4 class="text-lg font-medium ${isActive ? 'text-gray-900' : 'text-gray-500'}">${constraint.name}</h4>
                                <span class="px-2 py-1 text-xs rounded-full ${getRuleTypeColor(constraint.rule_type)}">${ruleType.label || constraint.rule_type}</span>
                                <span class="px-2 py-1 text-xs rounded-full ${isActive ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'}">
                                    ${isActive ? '<?php echo t('active'); ?>' : '<?php echo t('inactive'); ?>'}
                                </span>
                            </div>
                            <p class="text-sm text-gray-600 mb-2">${constraint.description || ''}</p>
                            <div class="flex items-center space-x-4 text-sm text-gray-500">
                                <span><i class="fas fa-weight-hanging mr-1"></i><?php echo t('weight'); ?>: ${constraint.weight}</span>
                                <span><i class="fas fa-cog mr-1"></i><?php echo t('parameters'); ?>: ${getParameterCount(constraint.parameters)}</span>
                            </div>
                        </div>
                        <div class="flex items-center space-x-2 ml-4">
                            <button onclick="toggleConstraint(${constraint.id})" class="text-gray-400 hover:text-gray-600" title="${isActive ? '<?php echo t('deactivate'); ?>' : '<?php echo t('activate'); ?>'}">
                                <i class="fas fa-power-off"></i>
                            </button>
                            <button onclick="editConstraint(${constraint.id})" class="text-blue-600 hover:text-blue-800" title="<?php echo t('edit'); ?>">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button onclick="deleteConstraint(${constraint.id})" class="text-red-600 hover:text-red-800" title="<?php echo t('delete'); ?>">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `;
        });
        
        html += `
                </div>
            </div>
        `;
    });
    
    container.innerHTML = html;
}

function getRuleTypeColor(ruleType) {
    const colors = {
        'forbidden': 'bg-red-100 text-red-800',
        'not_preferred': 'bg-orange-100 text-orange-800',
        'less_preferred': 'bg-yellow-100 text-yellow-800',
        'most_preferred': 'bg-green-100 text-green-800'
    };
    return colors[ruleType] || 'bg-gray-100 text-gray-800';
}

function getParameterCount(parameters) {
    if (!parameters) return 0;
    if (typeof parameters === 'string') {
        try {
            parameters = JSON.parse(parameters);
        } catch {
            return 0;
        }
    }
    return Object.keys(parameters).length;
}

function updateModalDropdowns() {
    // Update constraint type dropdown
    const constraintTypeSelect = document.getElementById('constraintType');
    constraintTypeSelect.innerHTML = '<option value=""><?php echo t('select_constraint_type'); ?></option>';
    
    console.log('Updating modal dropdowns with', Object.keys(constraintTypes).length, 'constraint types');
    
    Object.keys(constraintTypes).forEach(type => {
        const option = document.createElement('option');
        option.value = type;
        option.textContent = constraintTypes[type].name;
        constraintTypeSelect.appendChild(option);
        console.log('Added dropdown option:', type, '-', constraintTypes[type].name);
    });
    
    console.log('Final dropdown has', constraintTypeSelect.options.length - 1, 'constraint options');
    
    // Update rule type dropdown
    const ruleTypeSelect = document.getElementById('ruleType');
    ruleTypeSelect.innerHTML = '<option value=""><?php echo t('select_constraint_type'); ?></option>';
    
    Object.keys(ruleTypes).forEach(type => {
        const option = document.createElement('option');
        option.value = type;
        option.textContent = ruleTypes[type].label;
        ruleTypeSelect.appendChild(option);
    });
}

function showCreateConstraintModal() {
    document.getElementById('modalTitle').textContent = '<?php echo t('create_new_constraint'); ?>';
    document.getElementById('saveButtonText').textContent = '<?php echo t('create_constraint'); ?>';
    document.getElementById('constraintForm').reset();
    document.getElementById('constraintId').value = '';
    document.getElementById('constraintParameters').innerHTML = '';
    document.getElementById('constraintModal').classList.remove('hidden');
}

function hideConstraintModal() {
    document.getElementById('constraintModal').classList.add('hidden');
}

function editConstraint(id) {
    const constraint = currentData.constraints.find(c => c.id == id);
    if (!constraint) return;
    
    document.getElementById('modalTitle').textContent = '<?php echo t('edit_constraint'); ?>';
    document.getElementById('saveButtonText').textContent = '<?php echo t('update_constraint'); ?>';
    
    // Fill form
    document.getElementById('constraintId').value = constraint.id;
    document.getElementById('constraintName').value = constraint.name;
    document.getElementById('constraintDescription').value = constraint.description || '';
    document.getElementById('ruleType').value = constraint.rule_type;
    document.getElementById('constraintWeight').value = constraint.weight;
    
    // Parse parameters
    let parameters = constraint.parameters;
    if (typeof parameters === 'string') {
        try {
            parameters = JSON.parse(parameters);
        } catch {
            parameters = {};
        }
    }
    
    if (parameters.constraint_type) {
        document.getElementById('constraintType').value = parameters.constraint_type;
        updateConstraintParameters();
        
        // Fill parameter values
        setTimeout(() => {
            Object.keys(parameters).forEach(key => {
                if (key !== 'constraint_type') {
                    const field = document.querySelector(`[name="param_${key}"]`);
                    if (field) {
                        if (field.type === 'checkbox') {
                            field.checked = parameters[key];
                        } else {
                            field.value = parameters[key];
                        }
                    }
                }
            });
        }, 100);
    }
    
    document.getElementById('constraintModal').classList.remove('hidden');
}

function updateConstraintParameters() {
    const constraintType = document.getElementById('constraintType').value;
    const container = document.getElementById('constraintParameters');
    
    if (!constraintType || !constraintTypes[constraintType]) {
        container.innerHTML = '';
        return;
    }
    
    const constraintDef = constraintTypes[constraintType];
    
    let html = `
        <h4 class="text-md font-medium text-gray-900 mb-3"><?php echo t('constraint_parameters'); ?></h4>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    `;
    
    Object.keys(constraintDef.parameters).forEach(param => {
        const paramDef = constraintDef.parameters[param];
        const fieldName = `param_${param}`;
        
        html += '<div>';
        html += `<label class="block text-sm font-medium text-gray-700 mb-1">${paramDef.label}${paramDef.required ? ' *' : ''}</label>`;
        
        switch (paramDef.type) {
            case 'number':
                html += `<input type="number" name="${fieldName}" ${paramDef.required ? 'required' : ''} value="${paramDef.default || ''}" class="w-full border border-gray-300 rounded-md px-3 py-2">`;
                break;
            case 'text':
                html += `<input type="text" name="${fieldName}" ${paramDef.required ? 'required' : ''} value="${paramDef.default || ''}" class="w-full border border-gray-300 rounded-md px-3 py-2">`;
                break;
            case 'textarea':
                html += `<textarea name="${fieldName}" ${paramDef.required ? 'required' : ''} rows="3" class="w-full border border-gray-300 rounded-md px-3 py-2">${paramDef.default || ''}</textarea>`;
                break;
            case 'date':
                html += `<input type="date" name="${fieldName}" ${paramDef.required ? 'required' : ''} value="${paramDef.default || ''}" class="w-full border border-gray-300 rounded-md px-3 py-2">`;
                break;
            case 'boolean':
                html += `<label class="flex items-center"><input type="checkbox" name="${fieldName}" ${paramDef.default ? 'checked' : ''} class="mr-2"> ${paramDef.label}</label>`;
                break;
            case 'team_select':
                html += `<select name="${fieldName}" ${paramDef.required ? 'required' : ''} class="w-full border border-gray-300 rounded-md px-3 py-2">`;
                html += '<option value="">Select team...</option>';
                teams.forEach(team => {
                    html += `<option value="${team.id}">${team.team_name}</option>`;
                });
                html += '</select>';
                break;
            case 'select':
                html += `<select name="${fieldName}" ${paramDef.required ? 'required' : ''} class="w-full border border-gray-300 rounded-md px-3 py-2">`;
                paramDef.options.forEach(option => {
                    const selected = option === paramDef.default ? 'selected' : '';
                    html += `<option value="${option}" ${selected}>${option}</option>`;
                });
                html += '</select>';
                break;
        }
        
        html += '</div>';
    });
    
    html += '</div>';
    container.innerHTML = html;
}

function updateWeightSuggestion() {
    const ruleType = document.getElementById('ruleType').value;
    const weightField = document.getElementById('constraintWeight');
    const suggestion = document.getElementById('weightSuggestion');
    
    if (ruleType && ruleTypes[ruleType]) {
        const ruleTypeDef = ruleTypes[ruleType];
        weightField.value = ruleTypeDef.default_weight;
        suggestion.textContent = `Suggested range: ${ruleTypeDef.weight_range[0]} to ${ruleTypeDef.weight_range[1]}`;
    } else {
        suggestion.textContent = '';
    }
}

async function saveConstraint(event) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    const data = {};
    
    for (let [key, value] of formData.entries()) {
        data[key] = value;
    }
    
    // Handle checkboxes (they won't be in formData if unchecked)
    const checkboxes = event.target.querySelectorAll('input[type="checkbox"]');
    checkboxes.forEach(checkbox => {
        if (!data.hasOwnProperty(checkbox.name)) {
            data[checkbox.name] = false;
        } else {
            data[checkbox.name] = true;
        }
    });
    
    const action = data.id ? 'update_constraint' : 'create_constraint';
    data.action = action;
    
    try {
        const response = await fetch('ajax_constraint_handler.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            showSuccess(action === 'create_constraint' ? '<?php echo t('constraint_created_success'); ?>' : '<?php echo t('constraint_updated_success'); ?>');
            hideConstraintModal();
            refreshData();
        } else {
            showError(result.error);
        }
    } catch (error) {
        showError('<?php echo t('error_occurred'); ?>: ' + error.message);
    }
}

async function deleteConstraint(id) {
    if (!confirm('<?php echo t('confirm_delete_constraint'); ?>')) {
        return;
    }
    
    try {
        const response = await fetch('ajax_constraint_handler.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=delete_constraint&id=${id}`
        });
        
        const result = await response.json();
        
        if (result.success) {
            showSuccess('<?php echo t('constraint_deleted_success'); ?>');
            refreshData();
        } else {
            showError(result.error);
        }
    } catch (error) {
        showError('<?php echo t('error_occurred'); ?>: ' + error.message);
    }
}

async function toggleConstraint(id) {
    try {
        const response = await fetch('ajax_constraint_handler.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=toggle_constraint&id=${id}`
        });
        
        const result = await response.json();
        
        if (result.success) {
            showSuccess('<?php echo t('constraint_toggled_success'); ?>');
            refreshData();
        } else {
            showError(result.error);
        }
    } catch (error) {
        showError('<?php echo t('error_occurred'); ?>: ' + error.message);
    }
}

function showSuccess(message) {
    // Create and show success notification
    const notification = document.createElement('div');
    notification.className = 'fixed top-4 right-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded z-50';
    notification.innerHTML = `
        <div class="flex items-center">
            <i class="fas fa-check-circle mr-2"></i>
            <span>${message}</span>
            <button onclick="this.parentElement.parentElement.remove()" class="ml-4 text-green-600 hover:text-green-800">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        if (notification.parentElement) {
            notification.remove();
        }
    }, 5000);
}

function showError(message) {
    // Create and show error notification
    const notification = document.createElement('div');
    notification.className = 'fixed top-4 right-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded z-50';
    notification.innerHTML = `
        <div class="flex items-center">
            <i class="fas fa-exclamation-triangle mr-2"></i>
            <span>${message}</span>
            <button onclick="this.parentElement.parentElement.remove()" class="ml-4 text-red-600 hover:text-red-800">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        if (notification.parentElement) {
            notification.remove();
        }
    }, 7000);
}

// Listen for language changes
window.addEventListener('pageshow', function() {
    // Check if the language has changed by monitoring URL parameters
    const urlParams = new URLSearchParams(window.location.search);
    const currentLang = urlParams.get('lang') || 'nl';
    
    // Store the current language to detect changes
    if (!window.lastLanguage) {
        window.lastLanguage = currentLang;
    } else if (window.lastLanguage !== currentLang) {
        // Language has changed, refresh the constraint types
        console.log('Language changed from', window.lastLanguage, 'to', currentLang);
        window.lastLanguage = currentLang;
        refreshData();
    }
});

// Also listen for popstate (back/forward navigation)
window.addEventListener('popstate', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const currentLang = urlParams.get('lang') || 'nl';
    
    if (window.lastLanguage && window.lastLanguage !== currentLang) {
        console.log('Language changed via navigation from', window.lastLanguage, 'to', currentLang);
        window.lastLanguage = currentLang;
        refreshData();
    }
});
</script>

<?php
$content = ob_get_clean();
include 'includes/layout.php';
?>
