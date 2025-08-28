<?php
/**
 * Pure Python Autoplanning Interface
 * Replaces the hybrid PHP/Python approach with pure Python OR-Tools optimization
 */

require_once __DIR__ . '/includes/db_connection.php';
require_once __DIR__ . '/includes/ConstraintManager.php';
require_once __DIR__ . '/includes/PurePythonAutoplannerService.php';

header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Validate request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Only POST requests are allowed');
    }
    
    // Get action from request
    $action = $_POST['action'] ?? $_GET['action'] ?? '';
    
    switch ($action) {
        case 'generate_autoplan':
            $result = generateAutoPlan();
            break;
            
        case 'test_python_service':
            $result = testPythonService();
            break;
            
        case 'get_optimization_data':
            $result = getOptimizationData();
            break;
            
        default:
            throw new Exception('Invalid action: ' . $action);
    }
    
    echo json_encode($result);
    
} catch (Exception $e) {
    error_log("Pure Python Autoplanning Error: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}

/**
 * Generate autoplan using pure Python OR-Tools optimization
 */
function generateAutoPlan(): array {
    global $pdo;
    
    // Initialize Python service
    $pythonService = new PurePythonAutoplannerService();
    
    // Get teams data
    $teams = getTeamsData($pdo);
    if (empty($teams)) {
        throw new Exception('No active teams found');
    }
    
    // Get matches data
    $matches = getMatchesData($pdo);
    if (empty($matches)) {
        throw new Exception('No matches found');
    }
    
    // Get constraints data
    $constraintManager = new ConstraintManager($pdo);
    $constraints = $constraintManager->getAllConstraints();
    
    // Get configuration
    $config = getOptimizationConfig();
    
    // Call Python optimization service
    $result = $pythonService->generateAutoplan($teams, $matches, $constraints, $config);
    
    if (!$result['success']) {
        throw new Exception('Python optimization failed: ' . ($result['error'] ?? 'Unknown error'));
    }
    
    // Store assignments in database
    $assignmentCount = storeAssignments($pdo, $result['assignments']);
    
    return [
        'success' => true,
        'message' => "Autoplan generated successfully with {$assignmentCount} assignments",
        'assignments_created' => $assignmentCount,
        'solver_info' => $result['solver_info'],
        'metadata' => $result['metadata'],
        'optimization_summary' => [
            'teams_processed' => count($teams),
            'matches_processed' => count($matches),
            'constraints_applied' => count($constraints),
            'solver_time' => $result['solver_info']['solver_time_seconds'] ?? 0,
            'solver_status' => $result['solver_info']['solver_status'] ?? 'UNKNOWN'
        ]
    ];
}

/**
 * Test Python service connectivity
 */
function testPythonService(): array {
    $pythonService = new PurePythonAutoplannerService();
    return $pythonService->testConnection();
}

/**
 * Get optimization data for review
 */
function getOptimizationData(): array {
    global $pdo;
    
    $constraintManager = new ConstraintManager($pdo);
    
    return [
        'success' => true,
        'data' => [
            'teams' => getTeamsData($pdo),
            'matches' => getMatchesData($pdo),
            'constraints' => $constraintManager->getAllConstraints(),
            'existing_assignments' => getExistingAssignments($pdo)
        ]
    ];
}

/**
 * Get teams data for optimization
 */
function getTeamsData(PDO $pdo): array {
    $stmt = $pdo->prepare("
        SELECT 
            id,
            team_name as name,
            COALESCE(capacity_weight, 1.0) as capacity_weight,
            is_active,
            dedicated_to_team
        FROM teams 
        WHERE is_active = 1
        ORDER BY team_name
    ");
    
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get matches data for optimization
 */
function getMatchesData(PDO $pdo): array {
    $stmt = $pdo->prepare("
        SELECT 
            m.id,
            CONCAT(DATE(m.date_time), ' ', TIME(m.date_time)) as date_time,
            m.home_team,
            m.away_team,
            m.location,
            m.competition,
            COALESCE(m.importance_multiplier, 1.0) as importance_multiplier,
            COALESCE(m.is_locked, 0) as is_locked
        FROM matches m
        WHERE m.date_time >= CURDATE()
        ORDER BY m.date_time
    ");
    
    $stmt->execute();
    $matches = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Add required duties for each match
    foreach ($matches as &$match) {
        $match['required_duties'] = getMatchDuties($pdo, $match['id']);
    }
    
    return $matches;
}

/**
 * Get required duties for a match
 */
function getMatchDuties(PDO $pdo, int $matchId): array {
    // Default duties if no specific configuration exists
    $defaultDuties = [
        ['type' => 'referee', 'count' => 1, 'weight' => 1.0],
        ['type' => 'assistant_referee_1', 'count' => 1, 'weight' => 0.8],
        ['type' => 'assistant_referee_2', 'count' => 1, 'weight' => 0.8]
    ];
    
    // TODO: In future, this could be configurable per match/competition
    return $defaultDuties;
}

/**
 * Get optimization configuration
 */
function getOptimizationConfig(): array {
    return [
        'solver_type' => $_POST['solver_type'] ?? 'auto',
        'time_limit_seconds' => (int)($_POST['time_limit'] ?? 300),
        'verbose' => !empty($_POST['verbose']),
        'solver_config' => [
            'prefer_balanced_distribution' => true,
            'minimize_travel_distance' => true,
            'respect_team_preferences' => true
        ]
    ];
}

/**
 * Store assignments in database
 */
function storeAssignments(PDO $pdo, array $assignments): int {
    if (empty($assignments)) {
        return 0;
    }
    
    // Clear existing auto-generated assignments
    $pdo->exec("DELETE FROM assignments WHERE source = 'auto_planner'");
    
    // Prepare insert statement
    $stmt = $pdo->prepare("
        INSERT INTO assignments (
            match_id, 
            team_id, 
            duty_type, 
            assignment_time, 
            confidence_score,
            source,
            created_at
        ) VALUES (?, ?, ?, ?, ?, 'auto_planner', NOW())
    ");
    
    $count = 0;
    foreach ($assignments as $assignment) {
        $stmt->execute([
            $assignment['match_id'],
            $assignment['team_id'],
            $assignment['duty_type'],
            $assignment['assignment_time'],
            $assignment['confidence_score'] ?? 0.95
        ]);
        $count++;
    }
    
    return $count;
}

/**
 * Get existing assignments
 */
function getExistingAssignments(PDO $pdo): array {
    $stmt = $pdo->prepare("
        SELECT 
            a.*,
            t.team_name,
            m.home_team,
            m.away_team,
            m.date_time as match_date_time
        FROM assignments a
        JOIN teams t ON a.team_id = t.id
        JOIN matches m ON a.match_id = m.id
        ORDER BY m.date_time, a.duty_type
    ");
    
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
