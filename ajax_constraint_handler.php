<?php
/**
 * AJAX Handler for Advanced Constraint Editor
 */

// Clean any output buffering and ensure clean JSON response
ob_clean();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

// Handle AJAX requests only
if (!isset($_POST['action'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

// Load required files
require_once 'config/database.php';
require_once 'includes/ConstraintManager.php';
require_once 'includes/translations.php';

// Include all constraint editor functions FIRST
function getConstraintEditorData($constraintManager) {
    // Get all constraints including inactive ones for the editor
    $constraints = $constraintManager->getAllConstraintsForEditor();
    $teams = $constraintManager->getAllTeams();
    
    return [
        'success' => true,
        'constraints' => $constraints,
        'teams' => $teams,
        'constraint_types' => getConstraintTypes(),
        'rule_types' => getRuleTypes(),
        'stats' => [
            'total_constraints' => count($constraints),
            'active_constraints' => count(array_filter($constraints, function($c) { return $c['is_active']; })),
            'hard_constraints' => count(array_filter($constraints, function($c) { return $c['rule_type'] === 'forbidden'; })),
            'soft_constraints' => count(array_filter($constraints, function($c) { return $c['rule_type'] !== 'forbidden'; }))
        ]
    ];
}

function createAdvancedConstraint($constraintManager, $data) {
    try {
        // Build parameters from form data
        $parameters = [];
        foreach ($data as $key => $value) {
            if (strpos($key, 'param_') === 0) {
                $paramName = substr($key, 6); // Remove 'param_' prefix
                $parameters[$paramName] = $value;
            }
        }
        
        // Add constraint type to parameters
        if (isset($data['constraint_type'])) {
            $parameters['constraint_type'] = $data['constraint_type'];
        }
        
        $constraintData = [
            'name' => $data['name'],
            'description' => $data['description'] ?? '',
            'rule_type' => $data['rule_type'],
            'weight' => $data['weight'],
            'parameters' => json_encode($parameters),
            'is_active' => 1
        ];
        
        return $constraintManager->createConstraint($constraintData);
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

function updateAdvancedConstraint($constraintManager, $data) {
    try {
        if (!isset($data['id'])) {
            return ['success' => false, 'error' => 'Constraint ID is required'];
        }
        
        // Build parameters from form data
        $parameters = [];
        foreach ($data as $key => $value) {
            if (strpos($key, 'param_') === 0) {
                $paramName = substr($key, 6); // Remove 'param_' prefix
                $parameters[$paramName] = $value;
            }
        }
        
        // Add constraint type to parameters
        if (isset($data['constraint_type'])) {
            $parameters['constraint_type'] = $data['constraint_type'];
        }
        
        $constraintData = [
            'name' => $data['name'],
            'description' => $data['description'] ?? '',
            'rule_type' => $data['rule_type'],
            'weight' => $data['weight'],
            'parameters' => json_encode($parameters)
        ];
        
        return $constraintManager->updateConstraint($data['id'], $constraintData);
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

function deleteAdvancedConstraint($constraintManager, $data) {
    try {
        if (!isset($data['id'])) {
            return ['success' => false, 'error' => 'Constraint ID is required'];
        }
        
        return $constraintManager->deleteConstraint($data['id']);
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

function toggleAdvancedConstraint($constraintManager, $data) {
    try {
        if (!isset($data['id'])) {
            return ['success' => false, 'error' => 'Constraint ID is required'];
        }
        
        return $constraintManager->toggleConstraint($data['id']);
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

function validateAdvancedConstraint($data) {
    return [
        'success' => true,
        'message' => 'This feature will be implemented soon'
    ];
}

function getConstraintTypes() {
    return [
        'assignment_balance' => [
            'name' => t('assignment_balance'),
            'description' => t('assignment_balance_description'),
            'category' => t('load_balancing'),
            'parameters' => [
                'max_assignments_per_day' => ['type' => 'number', 'label' => 'Max toewijzingen per dag', 'default' => 3],
                'max_assignments_per_week' => ['type' => 'number', 'label' => 'Max toewijzingen per week', 'default' => 6],
                'applies_to_all_teams' => ['type' => 'boolean', 'label' => 'Van toepassing op alle teams', 'default' => true]
            ]
        ],
        'consecutive_assignments' => [
            'name' => t('consecutive_assignments'),
            'description' => t('consecutive_assignments_description'),
            'category' => t('scheduling'),
            'parameters' => [
                'max_consecutive' => ['type' => 'number', 'label' => 'Max opeenvolgende wedstrijden', 'default' => 2],
                'allow_groups' => ['type' => 'boolean', 'label' => 'Gegroepeerde toewijzingen toestaan', 'default' => true],
                'prevent_single_last' => ['type' => 'boolean', 'label' => 'Voorkom enkele laatste wedstrijd', 'default' => true],
                'applies_to_all_teams' => ['type' => 'boolean', 'label' => 'Van toepassing op alle teams', 'default' => true]
            ]
        ],
        'weekend_restrictions' => [
            'name' => t('weekend_restrictions'),
            'description' => t('weekend_restrictions_description'),
            'category' => t('weekend_rules'),
            'parameters' => [
                'no_double_weekends' => ['type' => 'boolean', 'label' => 'Voorkom opeenvolgende weekenden', 'default' => true],
                'max_per_weekend_day' => ['type' => 'number', 'label' => 'Max toewijzingen per weekenddag', 'default' => 1],
                'prefer_home_playing_teams' => ['type' => 'boolean', 'label' => 'Verkies thuisspelende teams', 'default' => true],
                'applies_to_all_teams' => ['type' => 'boolean', 'label' => 'Van toepassing op alle teams', 'default' => true]
            ]
        ],
        'team_unavailability' => [
            'name' => t('team_unavailability'),
            'description' => t('team_unavailability_description'),
            'category' => t('availability'),
            'parameters' => [
                'team_id' => ['type' => 'team_select', 'label' => 'Team', 'required' => true],
                'start_date' => ['type' => 'date', 'label' => 'Startdatum', 'required' => true],
                'end_date' => ['type' => 'date', 'label' => 'Einddatum (optioneel)'],
                'reason' => ['type' => 'text', 'label' => 'Reden']
            ]
        ],
        'go_match_grouping' => [
            'name' => t('go_match_grouping'),
            'description' => t('go_match_grouping_description'),
            'category' => t('competition_rules'),
            'parameters' => [
                'force_same_team' => ['type' => 'boolean', 'label' => 'Forceer hetzelfde team voor GO wedstrijden', 'default' => true],
                'allow_different_for_odd' => ['type' => 'boolean', 'label' => 'Sta andere toewijzing toe voor oneven GO wedstrijd', 'default' => true],
                'applies_to_all_teams' => ['type' => 'boolean', 'label' => 'Van toepassing op alle teams', 'default' => true]
            ]
        ],
        'team_restrictions' => [
            'name' => t('team_restrictions'),
            'description' => t('team_restrictions_description'),
            'category' => t('conflict_prevention'),
            'parameters' => [
                'restrict_own_matches' => ['type' => 'boolean', 'label' => 'Kan eigen wedstrijden niet jureren', 'default' => true],
                'restrict_away_day' => ['type' => 'boolean', 'label' => 'Kan niet jureren bij uitwedstrijd', 'default' => true],
                'applies_to_all_teams' => ['type' => 'boolean', 'label' => 'Van toepassing op alle teams', 'default' => true]
            ]
        ],
        'home_team_restriction' => [
            'name' => t('home_team_restriction'),
            'description' => t('home_team_restriction_description'),
            'category' => t('conflict_prevention'),
            'parameters' => [
                'include_away_matches' => ['type' => 'boolean', 'label' => 'Inclusief uitwedstrijden', 'default' => true],
                'applies_to_all_teams' => ['type' => 'boolean', 'label' => 'Van toepassing op alle teams', 'default' => true]
            ]
        ],
        'away_match_conflict' => [
            'name' => t('away_match_conflict'),
            'description' => t('away_match_conflict_description'),
            'category' => t('conflict_prevention'),
            'parameters' => [
                'strict_mode' => ['type' => 'boolean', 'label' => 'Strikte modus (geen uitzonderingen)', 'default' => true],
                'applies_to_all_teams' => ['type' => 'boolean', 'label' => 'Van toepassing op alle teams', 'default' => true]
            ]
        ],
        'dedicated_team_assignment' => [
            'name' => t('dedicated_team_assignment'),
            'description' => t('dedicated_team_assignment_description'),
            'category' => t('team_dedication'),
            'parameters' => [
                'team_id' => ['type' => 'team_select', 'label' => 'Jury team', 'required' => true],
                'dedicated_to_teams' => ['type' => 'multi_team_select', 'label' => 'Toegewezen aan teams', 'required' => true],
                'allow_last_match_exception' => ['type' => 'boolean', 'label' => 'Sta laatste wedstrijd uitzondering toe', 'default' => false]
            ]
        ],
        'cross_day_restrictions' => [
            'name' => t('cross_day_restrictions'),
            'description' => t('cross_day_restrictions_description'),
            'category' => t('scheduling'),
            'parameters' => [
                'no_consecutive_days' => ['type' => 'boolean', 'label' => 'Geen opeenvolgende dagen', 'default' => true],
                'min_rest_days' => ['type' => 'number', 'label' => 'Minimum rustdagen tussen toewijzingen', 'default' => 0],
                'applies_to_all_teams' => ['type' => 'boolean', 'label' => 'Van toepassing op alle teams', 'default' => true]
            ]
        ],
        'proximity_preference' => [
            'name' => t('proximity_preference'),
            'description' => t('proximity_preference_description'),
            'category' => t('optimization'),
            'parameters' => [
                'prefer_consecutive' => ['type' => 'boolean', 'label' => 'Verkies opeenvolgende wedstrijden', 'default' => true],
                'min_group_size' => ['type' => 'number', 'label' => 'Minimum groepsgrootte', 'default' => 2],
                'penalty_for_gaps' => ['type' => 'number', 'label' => 'Straf voor gaten tussen toewijzingen', 'default' => 10],
                'applies_to_all_teams' => ['type' => 'boolean', 'label' => 'Van toepassing op alle teams', 'default' => true]
            ]
        ],
        'point_balancing' => [
            'name' => t('point_balancing'),
            'description' => t('point_balancing_description'),
            'category' => t('fairness_category'),
            'parameters' => [
                'max_point_difference' => ['type' => 'number', 'label' => 'Maximum puntenverschil tussen teams', 'default' => 20],
                'prefer_lower_point_teams' => ['type' => 'boolean', 'label' => 'Verkies teams met minder punten', 'default' => true],
                'first_match_points' => ['type' => 'number', 'label' => 'Punten voor eerste wedstrijd', 'default' => 15],
                'last_match_points' => ['type' => 'number', 'label' => 'Punten voor laatste wedstrijd', 'default' => 15],
                'regular_match_points' => ['type' => 'number', 'label' => 'Punten voor reguliere wedstrijd', 'default' => 10],
                'go_match_points' => ['type' => 'number', 'label' => 'Punten voor GO wedstrijd', 'default' => 10]
            ]
        ],
        'quiet_day_optimization' => [
            'name' => t('quiet_day_optimization'),
            'description' => t('quiet_day_optimization_description'),
            'category' => t('optimization'),
            'parameters' => [
                'prefer_playing_teams' => ['type' => 'boolean', 'label' => 'Verkies teams die spelen op de dag', 'default' => true],
                'two_match_strategy' => ['type' => 'select', 'label' => 'Twee wedstrijden dag strategie', 'options' => ['beide_naar_spelend', 'gelijk_verdelen'], 'default' => 'beide_naar_spelend'],
                'three_match_strategy' => ['type' => 'select', 'label' => 'Drie wedstrijden dag strategie', 'options' => ['twee_plus_een', 'gelijk_verdelen'], 'default' => 'twee_plus_een']
            ]
        ],
        'da1_da2_restriction' => [
            'name' => t('da1_da2_restriction'),
            'description' => t('da1_da2_restriction_description'),
            'category' => t('competition_rules'),
            'parameters' => [
                'strict_enforcement' => ['type' => 'boolean', 'label' => 'Strikte handhaving', 'default' => true]
            ]
        ],
        'h1_h2_special_rules' => [
            'name' => t('h1_h2_special_rules'),
            'description' => t('h1_h2_special_rules_description'),
            'category' => t('special_rules'),
            'parameters' => [
                'exclude_from_general_pool' => ['type' => 'boolean', 'label' => 'Uitsluiten van algemene pool', 'default' => false],
                'priority_for_important_matches' => ['type' => 'boolean', 'label' => 'Prioriteit voor belangrijke wedstrijden', 'default' => true]
            ]
        ],
        'workload_distribution' => [
            'name' => t('workload_distribution'),
            'description' => t('workload_distribution_description'),
            'category' => t('fairness_category'),
            'parameters' => [
                'lookback_weeks' => ['type' => 'number', 'label' => 'Weken terug kijken', 'default' => 4],
                'weight_recent_assignments' => ['type' => 'number', 'label' => 'Gewicht voor recente toewijzingen', 'default' => 2.0],
                'applies_to_all_teams' => ['type' => 'boolean', 'label' => 'Van toepassing op alle teams', 'default' => true]
            ]
        ],
        'custom_constraint' => [
            'name' => t('custom_constraint'),
            'description' => t('custom_constraint_description'),
            'category' => t('advanced_category'),
            'parameters' => [
                'constraint_name' => ['type' => 'text', 'label' => 'Beperking naam', 'required' => true],
                'python_logic' => ['type' => 'textarea', 'label' => 'Python beperking logica', 'required' => true],
                'weight_multiplier' => ['type' => 'number', 'label' => 'Gewicht vermenigvuldiger', 'default' => 1.0]
            ]
        ]
    ];
}

function getRuleTypes() {
    return [
        'forbidden' => [
            'label' => t('forbidden_hard_constraint'),
            'description' => 'Hard constraint that must not be violated',
            'weight_range' => [-10000, -100],
            'default_weight' => -1000,
            'color' => 'red'
        ],
        'not_preferred' => [
            'label' => t('not_preferred'),
            'description' => 'Strongly discouraged with high penalty',
            'weight_range' => [-100, -10],
            'default_weight' => -50,
            'color' => 'orange'
        ],
        'most_preferred' => [
            'label' => t('most_preferred'),
            'description' => 'Highly encouraged with strong positive weight',
            'weight_range' => [1, 100],
            'default_weight' => 50,
            'color' => 'green'
        ]
    ];
}

try {
    $database = new Database();
    $pdo = $database->getConnection();
    $constraintManager = new ConstraintManager($pdo);
    
    switch ($_POST['action']) {
        case 'get_data':
            echo json_encode(getConstraintEditorData($constraintManager));
            break;
            
        case 'create_constraint':
            echo json_encode(createAdvancedConstraint($constraintManager, $_POST));
            break;
            
        case 'update_constraint':
            echo json_encode(updateAdvancedConstraint($constraintManager, $_POST));
            break;
            
        case 'delete_constraint':
            echo json_encode(deleteAdvancedConstraint($constraintManager, $_POST));
            break;
            
        case 'toggle_constraint':
            echo json_encode(toggleAdvancedConstraint($constraintManager, $_POST));
            break;
            
        case 'validate_constraint':
            echo json_encode(validateAdvancedConstraint($_POST));
            break;
            
        case 'render_parameter_form':
            $constraintType = $_POST['constraint_type'] ?? '';
            $parameters = json_decode($_POST['parameters'] ?? '{}', true);
            echo json_encode(['success' => true, 'html' => renderParameterForm($constraintType, $parameters)]);
            break;
            
        default:
            throw new Exception('Invalid action: ' . $_POST['action']);
    }
} catch (Exception $e) {
    // Ensure clean output
    ob_clean();
    
    // Log the error for debugging
    error_log("AJAX Constraint Handler Error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
    
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => $e->getMessage(),
        'debug' => [
            'file' => basename($e->getFile()),
            'line' => $e->getLine(),
            'action' => $_POST['action'] ?? 'unknown'
        ]
    ]);
} catch (Error $e) {
    // Handle fatal errors
    ob_clean();
    error_log("AJAX Constraint Handler Fatal Error: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => 'A fatal error occurred: ' . $e->getMessage()
    ]);
}

function renderParameterForm($constraintType, $parameters = []) {
    $types = getConstraintTypes();
    
    if (!isset($types[$constraintType])) {
        return '<p class="text-red-500">Invalid constraint type</p>';
    }
    
    $typeInfo = $types[$constraintType];
    $html = '';
    
    foreach ($typeInfo['parameters'] as $paramName => $paramInfo) {
        $currentValue = isset($parameters[$paramName]) ? $parameters[$paramName] : 
                       (isset($paramInfo['default']) ? $paramInfo['default'] : '');
        
        $html .= '<div class="mb-4">';
        $html .= '<label class="block text-sm font-medium text-gray-700 mb-2">' . htmlspecialchars($paramInfo['label']) . '</label>';
        
        switch ($paramInfo['type']) {
            case 'number':
                $html .= '<input type="number" name="' . $paramName . '" value="' . $currentValue . '" class="form-input w-full">';
                break;
                
            case 'boolean':
                $checked = $currentValue ? 'checked' : '';
                $html .= '<label class="flex items-center">';
                $html .= '<input type="checkbox" name="' . $paramName . '" value="1" ' . $checked . ' class="mr-2">';
                $html .= '<span class="text-sm text-gray-600">Deze optie inschakelen</span>';
                $html .= '</label>';
                break;
                
            case 'text':
                $html .= '<input type="text" name="' . $paramName . '" value="' . htmlspecialchars($currentValue) . '" class="form-input w-full">';
                break;
                
            case 'date':
                $html .= '<input type="date" name="' . $paramName . '" value="' . htmlspecialchars($currentValue) . '" class="form-input w-full">';
                break;
                
            case 'textarea':
                $html .= '<textarea name="' . $paramName . '" rows="4" class="form-input w-full">' . htmlspecialchars($currentValue) . '</textarea>';
                break;
                
            case 'select':
                $html .= '<select name="' . $paramName . '" class="form-input w-full">';
                if (isset($paramInfo['options'])) {
                    foreach ($paramInfo['options'] as $option) {
                        $selected = ($currentValue == $option) ? 'selected' : '';
                        $html .= '<option value="' . htmlspecialchars($option) . '" ' . $selected . '>' . htmlspecialchars($option) . '</option>';
                    }
                }
                $html .= '</select>';
                break;
                
            case 'team_select':
                $html .= '<select name="' . $paramName . '" class="form-input w-full">';
                $html .= '<option value="">Selecteer een team...</option>';
                // Note: In a real implementation, you'd fetch teams from database
                $sampleTeams = ['Team A', 'Team B', 'Team C', 'Da1', 'Da2', 'H1', 'H2'];
                foreach ($sampleTeams as $team) {
                    $selected = ($currentValue == $team) ? 'selected' : '';
                    $html .= '<option value="' . htmlspecialchars($team) . '" ' . $selected . '>' . htmlspecialchars($team) . '</option>';
                }
                $html .= '</select>';
                break;
                
            case 'multi_team_select':
                $html .= '<select name="' . $paramName . '[]" multiple class="form-input w-full" size="4">';
                // Note: In a real implementation, you'd fetch teams from database
                $sampleTeams = ['Team A', 'Team B', 'Team C', 'Da1', 'Da2', 'H1', 'H2'];
                $selectedTeams = is_array($currentValue) ? $currentValue : explode(',', $currentValue);
                foreach ($sampleTeams as $team) {
                    $selected = in_array($team, $selectedTeams) ? 'selected' : '';
                    $html .= '<option value="' . htmlspecialchars($team) . '" ' . $selected . '>' . htmlspecialchars($team) . '</option>';
                }
                $html .= '</select>';
                $html .= '<p class="text-xs text-gray-500 mt-1">Houd Ctrl/Cmd ingedrukt om meerdere teams te selecteren</p>';
                break;
                
            default:
                $html .= '<input type="text" name="' . $paramName . '" value="' . htmlspecialchars($currentValue) . '" class="form-input w-full">';
                break;
        }
        
        if (isset($paramInfo['required']) && $paramInfo['required']) {
            $html = str_replace('class="form-input', 'required class="form-input', $html);
        }
        
        $html .= '</div>';
    }
    
    return $html;
}
?>
