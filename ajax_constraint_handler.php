<?php
/**
 * AJAX Handler for Advanced Constraint Editor
 */

header('Content-Type: application/json');

// Handle AJAX requests only
if (!isset($_POST['action'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

// Load required files
require_once 'config/database.php';
require_once 'includes/ConstraintManager.php';

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
    return [
        'success' => true,
        'message' => 'This feature will be implemented soon'
    ];
}

function updateAdvancedConstraint($constraintManager, $data) {
    return [
        'success' => true,
        'message' => 'This feature will be implemented soon'
    ];
}

function deleteAdvancedConstraint($constraintManager, $data) {
    return [
        'success' => true,
        'message' => 'This feature will be implemented soon'
    ];
}

function toggleAdvancedConstraint($constraintManager, $data) {
    return [
        'success' => true,
        'message' => 'This feature will be implemented soon'
    ];
}

function importPythonConstraints($constraintManager) {
    return [
        'success' => true,
        'message' => 'This feature will be implemented soon'
    ];
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
            'name' => 'Assignment Load Balancing',
            'description' => 'Balance assignments across teams to ensure fairness',
            'category' => 'Load Balancing',
            'parameters' => [
                'max_assignments_per_day' => [
                    'type' => 'number',
                    'label' => 'Max assignments per day',
                    'default' => 3
                ],
                'max_assignments_per_week' => [
                    'type' => 'number',
                    'label' => 'Max assignments per week',
                    'default' => 6
                ],
                'applies_to_all_teams' => [
                    'type' => 'boolean',
                    'label' => 'Apply to all teams',
                    'default' => true
                ]
            ]
        ],
        'consecutive_assignments' => [
            'name' => 'Consecutive Assignment Control',
            'description' => 'Control how many consecutive matches a team can be assigned',
            'category' => 'Scheduling',
            'parameters' => [
                'max_consecutive' => [
                    'type' => 'number',
                    'label' => 'Max consecutive matches',
                    'default' => 2
                ],
                'allow_groups' => [
                    'type' => 'boolean',
                    'label' => 'Allow grouped assignments',
                    'default' => true
                ],
                'prevent_single_last' => [
                    'type' => 'boolean',
                    'label' => 'Prevent single last match',
                    'default' => true
                ],
                'applies_to_all_teams' => [
                    'type' => 'boolean',
                    'label' => 'Apply to all teams',
                    'default' => true
                ]
            ]
        ],
        'team_restrictions' => [
            'name' => 'Team Assignment Restrictions',
            'description' => 'Prevent teams from jury duties in specific scenarios',
            'category' => 'Conflict Prevention',
            'parameters' => [
                'restrict_own_matches' => [
                    'type' => 'boolean',
                    'label' => 'Cannot jury own matches',
                    'default' => true
                ],
                'restrict_away_day' => [
                    'type' => 'boolean',
                    'label' => 'Cannot jury when playing away',
                    'default' => true
                ],
                'applies_to_all_teams' => [
                    'type' => 'boolean',
                    'label' => 'Apply to all teams',
                    'default' => true
                ]
            ]
        ]
    ];
}

function getRuleTypes() {
    return [
        'forbidden' => [
            'label' => 'Verboden (Harde Beperking)',
            'description' => 'Hard constraint that must not be violated',
            'weight_range' => [-10000, -100],
            'default_weight' => -1000,
            'color' => 'red'
        ],
        'not_preferred' => [
            'label' => 'Niet Gewenst',
            'description' => 'Strongly discouraged with high penalty',
            'weight_range' => [-100, -10],
            'default_weight' => -50,
            'color' => 'orange'
        ],
        'most_preferred' => [
            'label' => 'Meest Gewenst',
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
            
        case 'import_python_constraints':
            echo json_encode(importPythonConstraints($constraintManager));
            break;
            
        case 'validate_constraint':
            echo json_encode(validateAdvancedConstraint($_POST));
            break;
            
        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
