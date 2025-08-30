<?php
/**
 * UNIFIED AUTOPLANNING - The ONE and ONLY way to autoplan matches
 * This replaces ALL other optimization interfaces
 */

require_once 'includes/translations.php';
require_once 'config/database.php';
require_once 'includes/ConstraintManager.php';
require_once 'includes/PurePythonAutoplannerService.php';

$pageTitle = t('auto_plan');
$pageDescription = t('auto_assignment_description');

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    try {
        $database = new Database();
        $pdo = $database->getConnection();
        
        switch ($_POST['action']) {
            case 'get_data':
                echo json_encode(getAutoplanData($pdo));
                break;
                
            case 'generate_autoplan':
                echo json_encode(generateAutoplan($pdo));
                break;
                
            case 'clear_assignments':
                echo json_encode(clearAutoAssignments($pdo));
                break;
                
            default:
                throw new Exception('Invalid action');
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// Get initial data for display
try {
    $database = new Database();
    $pdo = $database->getConnection();
    $data = getAutoplanData($pdo);
} catch (Exception $e) {
    $error = "Database error: " . $e->getMessage();
}

function getAutoplanData($pdo) {
    // Get teams with proper weight column
    $teamsStmt = $pdo->prepare("
        SELECT id, team_name as name, is_active, weight
        FROM teams 
        WHERE is_active = 1
        ORDER BY team_name
    ");
    $teamsStmt->execute();
    $teams = $teamsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Ensure weight column exists, fallback to 1.0
    foreach ($teams as &$team) {
        if (!isset($team['weight']) || $team['weight'] === null) {
            $team['weight'] = 1.0;
        }
        $team['capacity_weight'] = $team['weight']; // For backward compatibility
    }
    
    // Get upcoming matches from home_matches table (matches that need jury assignments)
    // Plan for ALL future matches in the entire dataset
    $matchesStmt = $pdo->prepare("
        SELECT 
            m.id,
            m.date_time,
            m.home_team,
            m.away_team,
            m.location,
            m.competition,
            COUNT(a.id) as assigned_count
        FROM home_matches m
        LEFT JOIN jury_assignments a ON m.id = a.match_id
        WHERE m.date_time >= CURDATE()
        GROUP BY m.id
        ORDER BY m.date_time
    ");
    $matchesStmt->execute();
    $matches = $matchesStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get constraints
    $constraintManager = new ConstraintManager($pdo);
    $constraints = $constraintManager->getAllConstraints();
    $activeConstraints = array_filter($constraints, function($c) { return $c['is_active']; });
    
    // Get existing assignments for all future home matches
    $assignmentsStmt = $pdo->prepare("
        SELECT 
            a.match_id,
            a.team_id,
            t.team_name,
            m.home_team,
            m.away_team,
            m.date_time
        FROM jury_assignments a
        JOIN teams t ON a.team_id = t.id
        JOIN home_matches m ON a.match_id = m.id
        WHERE m.date_time >= CURDATE()
        ORDER BY m.date_time
    ");
    $assignmentsStmt->execute();
    $assignments = $assignmentsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    return [
        'success' => true,
        'teams' => $teams,
        'matches' => $matches,
        'constraints' => $activeConstraints,
        'assignments' => $assignments,
        'stats' => [
            'total_teams' => count($teams),
            'total_matches' => count($matches),
            'active_constraints' => count($activeConstraints),
            'existing_assignments' => count($assignments),
            'matches_without_jury' => count(array_filter($matches, function($m) { return $m['assigned_count'] == 0; }))
        ]
    ];
}

function generateAutoplan($pdo) {
    $pythonService = new PurePythonAutoplannerService();
    
    // Get data
    $data = getAutoplanData($pdo);
    $teams = $data['teams'];
    $matches = $data['matches'];
    $constraints = $data['constraints'];
    
    // Add required duties to each match
    foreach ($matches as &$match) {
        $match['required_duties'] = [
            ['type' => 'referee', 'count' => 1, 'weight' => 1.0],
            ['type' => 'assistant_referee_1', 'count' => 1, 'weight' => 0.8],
            ['type' => 'assistant_referee_2', 'count' => 1, 'weight' => 0.8]
        ];
    }
    
    // Configuration
    $config = [
        'solver_type' => $_POST['solver_type'] ?? 'auto',
        'time_limit_seconds' => (int)($_POST['time_limit'] ?? 300),
        'verbose' => !empty($_POST['verbose'])
    ];
    
    // Run optimization
    $result = $pythonService->generateAutoplan($teams, $matches, $constraints, $config);
    
    if (!$result['success']) {
        throw new Exception('Optimization failed: ' . ($result['error'] ?? 'Unknown error'));
    }
    
    // Clear existing assignments for the matches we're planning (only upcoming home matches)
    // First get the match IDs we're planning for
    $upcomingMatchIds = [];
    foreach ($matches as $match) {
        $upcomingMatchIds[] = $match['id'];
    }
    
    if (!empty($upcomingMatchIds)) {
        $placeholders = str_repeat('?,', count($upcomingMatchIds) - 1) . '?';
        $deleteStmt = $pdo->prepare("DELETE FROM jury_assignments WHERE match_id IN ({$placeholders})");
        $deleteStmt->execute($upcomingMatchIds);
    }
    
    // Store new assignments (using REPLACE to handle any duplicates)
    $stmt = $pdo->prepare("
        REPLACE INTO jury_assignments (match_id, team_id)
        VALUES (?, ?)
    ");
    
    $assignmentCount = 0;
    foreach ($result['assignments'] as $assignment) {
        $stmt->execute([
            $assignment['match_id'],
            $assignment['team_id']
        ]);
        $assignmentCount++;
    }
    
    return [
        'success' => true,
        'message' => sprintf(t('optimization_success'), $assignmentCount),
        'assignments_created' => $assignmentCount,
        'solver_info' => $result['solver_info'] ?? [],
        'stats' => $data['stats']
    ];
}

function clearAutoAssignments($pdo) {
    $stmt = $pdo->prepare("DELETE FROM jury_assignments");
    $deleted = $stmt->execute();
    $count = $stmt->rowCount();
    
    return [
        'success' => true,
        'message' => sprintf(t('assignments_cleared'), $count),
        'deleted_count' => $count
    ];
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

    <!-- Status Cards -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
        <div class="bg-white p-4 rounded-lg shadow border-l-4 border-blue-500">
            <div class="flex items-center">
                <i class="fas fa-users text-blue-500 text-xl mr-3"></i>
                <div>
                    <p class="text-sm text-gray-600"><?php echo t('teams'); ?></p>
                    <p class="text-2xl font-bold" id="teamsCount">-</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white p-4 rounded-lg shadow border-l-4 border-green-500">
            <div class="flex items-center">
                <i class="fas fa-calendar text-green-500 text-xl mr-3"></i>
                <div>
                    <p class="text-sm text-gray-600"><?php echo t('matches'); ?></p>
                    <p class="text-2xl font-bold" id="matchesCount">-</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white p-4 rounded-lg shadow border-l-4 border-purple-500">
            <div class="flex items-center">
                <i class="fas fa-ban text-purple-500 text-xl mr-3"></i>
                <div>
                    <p class="text-sm text-gray-600"><?php echo t('constraints'); ?></p>
                    <p class="text-2xl font-bold" id="constraintsCount">-</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white p-4 rounded-lg shadow border-l-4 border-yellow-500">
            <div class="flex items-center">
                <i class="fas fa-exclamation-triangle text-yellow-500 text-xl mr-3"></i>
                <div>
                    <p class="text-sm text-gray-600"><?php echo t('unassigned'); ?></p>
                    <p class="text-2xl font-bold" id="unassignedCount">-</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white p-4 rounded-lg shadow border-l-4 border-teal-500">
            <div class="flex items-center">
                <i class="fas fa-check text-teal-500 text-xl mr-3"></i>
                <div>
                    <p class="text-sm text-gray-600"><?php echo t('assigned'); ?></p>
                    <p class="text-2xl font-bold" id="assignedCount">-</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Controls -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h2 class="text-xl font-bold mb-4">
            <i class="fas fa-cogs mr-2"></i>
            <?php echo t('auto_assignment_controls'); ?>
        </h2>
        
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1"><?php echo t('solver_type'); ?></label>
                <select id="solverType" class="w-full border border-gray-300 rounded-md px-3 py-2">
                    <option value="auto"><?php echo t('auto_select'); ?></option>
                    <option value="sat"><?php echo t('constraint_sat'); ?></option>
                    <option value="linear"><?php echo t('linear_solver'); ?></option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1"><?php echo t('timeout_seconds'); ?></label>
                <select id="timeLimit" class="w-full border border-gray-300 rounded-md px-3 py-2">
                    <option value="60">1 <?php echo strtolower(t('minute')); ?></option>
                    <option value="300" selected>5 <?php echo strtolower(t('minutes')); ?></option>
                    <option value="600">10 <?php echo strtolower(t('minutes')); ?></option>
                    <option value="1800">30 <?php echo strtolower(t('minutes')); ?></option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1"><?php echo t('options'); ?></label>
                <label class="flex items-center">
                    <input type="checkbox" id="verbose" class="mr-2">
                    <span class="text-sm"><?php echo t('verbose_output'); ?></span>
                </label>
            </div>
            
            <div class="flex items-end">
                <button onclick="refreshData()" class="bg-gray-500 text-white px-4 py-2 rounded mr-2 hover:bg-gray-600">
                    <i class="fas fa-sync mr-1"></i> <?php echo t('refresh'); ?>
                </button>
            </div>
        </div>
        
        <div class="flex flex-wrap gap-3">
            <button onclick="generateAutoplan()" id="generateBtn" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 disabled:bg-gray-400">
                <i class="fas fa-brain mr-2"></i>
                <?php echo t('run_auto_assignment'); ?>
            </button>
            
            <button onclick="clearAssignments()" id="clearBtn" class="bg-red-600 text-white px-6 py-3 rounded-lg hover:bg-red-700">
                <i class="fas fa-trash mr-2"></i>
                <?php echo t('reset_all_assignments'); ?>
            </button>
            
            <a href="matches.php" class="bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700">
                <i class="fas fa-eye mr-2"></i>
                <?php echo t('view_all_upcoming'); ?>
            </a>
            
            <a href="constraints.php" class="bg-purple-600 text-white px-6 py-3 rounded-lg hover:bg-purple-700">
                <i class="fas fa-ban mr-2"></i>
                <?php echo t('constraints'); ?>
            </a>
        </div>
    </div>

    <!-- Progress -->
    <div id="progress" class="bg-white rounded-lg shadow p-6 mb-6" style="display: none;">
        <div class="flex items-center justify-center">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mr-4"></div>
            <div>
                <h3 class="text-lg font-medium"><?php echo t('generating_autoplan'); ?></h3>
                <p class="text-gray-600"><?php echo t('please_wait_optimization'); ?></p>
            </div>
        </div>
    </div>

    <!-- Results -->
    <div id="results" style="display: none;"></div>

    <!-- Current Assignments -->
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-bold mb-4">
            <i class="fas fa-list mr-2"></i>
            <?php echo t('current_auto_assignments'); ?>
        </h2>
        
        <div id="assignmentsList">
            <p class="text-gray-500 italic"><?php echo t('loading_assignments'); ?></p>
        </div>
    </div>
</div>

<script>
let currentData = null;

// Load data on page load
document.addEventListener('DOMContentLoaded', function() {
    refreshData();
});

async function refreshData() {
    try {
        const response = await fetch('autoplan.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=get_data'
        });
        
        const data = await response.json();
        
        if (data.success) {
            currentData = data;
            updateDisplay(data);
        } else {
            showError('<?php echo t('error_loading_data'); ?>: ' + data.error);
        }
    } catch (error) {
        showError('<?php echo t('error_occurred'); ?>: ' + error.message);
    }
}

function updateDisplay(data) {
    // Update stats
    document.getElementById('teamsCount').textContent = data.stats.total_teams;
    document.getElementById('matchesCount').textContent = data.stats.total_matches;
    document.getElementById('constraintsCount').textContent = data.stats.active_constraints;
    document.getElementById('unassignedCount').textContent = data.stats.matches_without_jury;
    document.getElementById('assignedCount').textContent = data.stats.existing_assignments;
    
    // Update assignments list
    updateAssignmentsList(data.assignments);
}

function updateAssignmentsList(assignments) {
    const container = document.getElementById('assignmentsList');
    
    if (assignments.length === 0) {
        container.innerHTML = `<p class="text-gray-500 italic"><?php echo t('no_auto_assignments_yet'); ?></p>`;
        return;
    }
    
    let html = '<div class="overflow-x-auto"><table class="min-w-full divide-y divide-gray-200">';
    html += '<thead class="bg-gray-50"><tr>';
    html += '<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"><?php echo t('match'); ?></th>';
    html += '<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"><?php echo t('date'); ?></th>';
    html += '<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"><?php echo t('team'); ?></th>';
    html += '</tr></thead><tbody class="bg-white divide-y divide-gray-200">';
    
    assignments.forEach(assignment => {
        html += '<tr>';
        html += `<td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${assignment.home_team} <?php echo t('vs'); ?> ${assignment.away_team}</td>`;
        html += `<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${new Date(assignment.date_time).toLocaleDateString()}</td>`;
        html += `<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${assignment.team_name}</td>`;
        html += '</tr>';
    });
    
    html += '</tbody></table></div>';
    container.innerHTML = html;
}

async function generateAutoplan() {
    const generateBtn = document.getElementById('generateBtn');
    const progress = document.getElementById('progress');
    const results = document.getElementById('results');
    
    // Show progress
    generateBtn.disabled = true;
    progress.style.display = 'block';
    results.style.display = 'none';
    
    try {
        const solverType = document.getElementById('solverType').value;
        const timeLimit = document.getElementById('timeLimit').value;
        const verbose = document.getElementById('verbose').checked ? '1' : '0';
        
        const response = await fetch('autoplan.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=generate_autoplan&solver_type=${solverType}&time_limit=${timeLimit}&verbose=${verbose}`
        });
        
        const result = await response.json();
        
        if (result.success) {
            showSuccess(result.message, result);
            refreshData(); // Reload data
        } else {
            showError('<?php echo t('optimization_failed'); ?>'.replace('{error}', result.error));
        }
        
    } catch (error) {
        showError('<?php echo t('error_occurred'); ?>: ' + error.message);
    } finally {
        generateBtn.disabled = false;
        progress.style.display = 'none';
    }
}

async function clearAssignments() {
    if (!confirm('<?php echo t('clear_assignments_confirm'); ?>')) {
        return;
    }
    
    try {
        const response = await fetch('autoplan.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=clear_assignments'
        });
        
        const result = await response.json();
        
        if (result.success) {
            showSuccess(result.message);
            refreshData();
        } else {
            showError('<?php echo t('error_occurred'); ?>: ' + result.error);
        }
        
    } catch (error) {
        showError('<?php echo t('error_occurred'); ?>: ' + error.message);
    }
}

function showSuccess(message, data = null) {
    const results = document.getElementById('results');
    let html = `<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
        <i class="fas fa-check-circle mr-2"></i>
        ${message}
    </div>`;
    
    if (data && data.solver_info) {
        html += `<div class="bg-white rounded-lg shadow p-4 mb-4">
            <h3 class="text-lg font-medium mb-2">Optimization Results</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                <div><strong>Assignments Created:</strong> ${data.assignments_created}</div>
                <div><strong>Solver Time:</strong> ${data.solver_info.solver_time_seconds || 0}s</div>
                <div><strong>Status:</strong> ${data.solver_info.solver_status || 'N/A'}</div>
                <div><strong>Constraints:</strong> ${data.solver_info.constraints_satisfied || 0}/${data.solver_info.total_constraints || 0}</div>
            </div>
        </div>`;
    }
    
    results.innerHTML = html;
    results.style.display = 'block';
}

function showError(message) {
    const results = document.getElementById('results');
    results.innerHTML = `<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
        <i class="fas fa-exclamation-triangle mr-2"></i>
        ${message}
    </div>`;
    results.style.display = 'block';
}
</script>

<?php
$content = ob_get_clean();
include 'includes/layout.php';
?>
