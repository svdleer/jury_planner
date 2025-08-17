<?php
require_once 'includes/config.php';
require_once 'includes/SmartAssignmentEngine.php';

$smartEngine = new SmartAssignmentEngine($pdo);

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'auto_assign':
            $maxAssignments = (int)($_POST['max_assignments'] ?? 10);
            $dryRun = isset($_POST['dry_run']);
            
            $results = $smartEngine->autoAssignMatches($maxAssignments, $dryRun);
            echo json_encode($results);
            exit;
            
        case 'get_recommendations':
            $matchId = (int)($_POST['match_id'] ?? 0);
            $limit = (int)($_POST['limit'] ?? 5);
            
            if ($matchId > 0) {
                $recommendations = $smartEngine->getMatchRecommendations($matchId, $limit);
                echo json_encode(['success' => true, 'recommendations' => $recommendations]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Invalid match ID']);
            }
            exit;
            
        case 'validate_all':
            $results = $smartEngine->validateAllAssignments();
            echo json_encode($results);
            exit;
            
        case 'assign_team':
            $matchId = (int)($_POST['match_id'] ?? 0);
            $teamId = (int)($_POST['team_id'] ?? 0);
            
            if ($matchId > 0 && $teamId > 0) {
                try {
                    $sql = "INSERT INTO jury_assignments (match_id, team_id, assigned_at) VALUES (?, ?, NOW())";
                    $stmt = $pdo->prepare($sql);
                    $success = $stmt->execute([$matchId, $teamId]);
                    
                    echo json_encode(['success' => $success]);
                } catch (Exception $e) {
                    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
                }
            } else {
                echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
            }
            exit;
    }
}

// Get unassigned matches for the interface
$sql = "SELECT m.id, m.home_team, m.away_team, m.date_time
        FROM home_matches m
        LEFT JOIN jury_assignments ja ON m.id = ja.match_id
        WHERE ja.id IS NULL
        AND m.date_time >= NOW()
        ORDER BY m.date_time
        LIMIT 20";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$unassignedMatches = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get recent assignments for validation
$sql = "SELECT ja.id, ja.match_id, ja.team_id, jt.name as team_name,
               m.home_team, m.away_team, m.date_time
        FROM jury_assignments ja
        JOIN jury_teams jt ON ja.team_id = jt.id
        JOIN home_matches m ON ja.match_id = m.id
        WHERE m.date_time >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ORDER BY m.date_time DESC
        LIMIT 10";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$recentAssignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

ob_start();
?>

<div class="max-w-7xl mx-auto p-6" x-data="smartAssignmentApp()">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Smart Assignment Engine</h1>
        <p class="text-gray-600">Automatically assign jury teams using advanced constraint validation</p>
    </div>
    
    <!-- Auto Assignment Section -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <h2 class="text-xl font-semibold mb-4 flex items-center">
            <svg class="w-6 h-6 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
            </svg>
            Auto Assignment
        </h2>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Max Assignments</label>
                <input type="number" x-model="maxAssignments" min="1" max="50" value="10"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="flex items-end">
                <label class="flex items-center">
                    <input type="checkbox" x-model="dryRun" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    <span class="ml-2 text-sm text-gray-700">Dry Run (Preview Only)</span>
                </label>
            </div>
            <div class="flex items-end space-x-2">
                <button @click="runAutoAssignment()" :disabled="loading"
                        class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50 flex items-center">
                    <span x-show="!loading">Run Auto Assignment</span>
                    <span x-show="loading" class="flex items-center">
                        <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Processing...
                    </span>
                </button>
                
                <button @click="validateAllAssignments()" :disabled="loading"
                        class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 disabled:opacity-50">
                    Validate All
                </button>
            </div>
        </div>
        
        <!-- Auto Assignment Results -->
        <div x-show="autoResults" class="mt-6">
            <h3 class="text-lg font-medium mb-4">Assignment Results</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <div class="bg-green-50 p-4 rounded-lg">
                    <div class="text-2xl font-bold text-green-800" x-text="autoResults?.assignments_made || 0"></div>
                    <div class="text-sm text-green-600">Assignments Made</div>
                </div>
                <div class="bg-red-50 p-4 rounded-lg">
                    <div class="text-2xl font-bold text-red-800" x-text="autoResults?.assignments_failed || 0"></div>
                    <div class="text-sm text-red-600">Assignments Failed</div>
                </div>
                <div class="bg-yellow-50 p-4 rounded-lg">
                    <div class="text-2xl font-bold text-yellow-800" x-text="(autoResults?.details || []).filter(d => d.warnings?.length > 0).length"></div>
                    <div class="text-sm text-yellow-600">With Warnings</div>
                </div>
            </div>
            
            <!-- Assignment Details -->
            <div x-show="autoResults?.details?.length > 0" class="overflow-x-auto">
                <table class="min-w-full bg-white border border-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Match</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Date/Time</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Assigned Team</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Score</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Issues</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <template x-for="detail in autoResults.details" :key="detail.match_id">
                            <tr>
                                <td class="px-4 py-2 text-sm text-gray-900" x-text="detail.match_info"></td>
                                <td class="px-4 py-2 text-sm text-gray-900" x-text="formatDateTime(detail.date_time)"></td>
                                <td class="px-4 py-2 text-sm text-gray-900">
                                    <span x-text="detail.assigned_team || detail.would_assign_team"></span>
                                    <span x-show="detail.would_assign_team" class="ml-2 text-xs bg-yellow-100 text-yellow-800 px-2 py-1 rounded">Preview</span>
                                </td>
                                <td class="px-4 py-2 text-sm text-gray-900" x-text="detail.constraint_score"></td>
                                <td class="px-4 py-2 text-sm">
                                    <div x-show="detail.violations?.length > 0" class="text-red-600 text-xs">
                                        <span x-text="detail.violations.length + ' violations'"></span>
                                    </div>
                                    <div x-show="detail.warnings?.length > 0" class="text-yellow-600 text-xs">
                                        <span x-text="detail.warnings.length + ' warnings'"></span>
                                    </div>
                                    <div x-show="!detail.violations?.length && !detail.warnings?.length" class="text-green-600 text-xs">
                                        No issues
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Unassigned Matches Section -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <h2 class="text-xl font-semibold mb-4">Unassigned Matches</h2>
        
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white border border-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Match</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Date/Time</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php foreach ($unassignedMatches as $match): ?>
                    <tr>
                        <td class="px-4 py-2 text-sm text-gray-900">
                            <?= htmlspecialchars($match['home_team']) ?> vs <?= htmlspecialchars($match['away_team']) ?>
                        </td>
                        <td class="px-4 py-2 text-sm text-gray-900">
                            <?= date('Y-m-d H:i', strtotime($match['date_time'])) ?>
                        </td>
                        <td class="px-4 py-2 text-sm">
                            <button @click="getRecommendations(<?= $match['id'] ?>)"
                                    class="px-3 py-1 bg-blue-600 text-white text-xs rounded hover:bg-blue-700">
                                Get Recommendations
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Recommendations Modal -->
    <div x-show="showRecommendations" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
         @click.self="showRecommendations = false">
        
        <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Team Recommendations</h3>
                
                <div x-show="recommendations?.length > 0" class="space-y-3">
                    <template x-for="rec in recommendations" :key="rec.team_id">
                        <div class="border rounded-lg p-4" :class="rec.valid ? 'border-green-200 bg-green-50' : 'border-red-200 bg-red-50'">
                            <div class="flex justify-between items-start">
                                <div class="flex-1">
                                    <h4 class="font-medium text-gray-900" x-text="rec.team_name"></h4>
                                    <div class="text-sm text-gray-600 mt-1">
                                        <span>Score: </span><span x-text="rec.score"></span> |
                                        <span>Assignments: </span><span x-text="rec.assignment_count"></span> |
                                        <span>Last assigned: </span><span x-text="rec.last_assignment_days + ' days ago'"></span>
                                    </div>
                                    
                                    <!-- Violations and Warnings -->
                                    <div x-show="rec.violations?.length > 0" class="mt-2">
                                        <div class="text-xs text-red-600 font-medium">Violations:</div>
                                        <template x-for="violation in rec.violations">
                                            <div class="text-xs text-red-600 ml-2" x-text="violation.message"></div>
                                        </template>
                                    </div>
                                    
                                    <div x-show="rec.warnings?.length > 0" class="mt-2">
                                        <div class="text-xs text-yellow-600 font-medium">Warnings:</div>
                                        <template x-for="warning in rec.warnings">
                                            <div class="text-xs text-yellow-600 ml-2" x-text="warning.message"></div>
                                        </template>
                                    </div>
                                </div>
                                
                                <button x-show="rec.valid" @click="assignTeam(selectedMatchId, rec.team_id)"
                                        class="px-3 py-1 bg-green-600 text-white text-xs rounded hover:bg-green-700">
                                    Assign
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
                
                <div class="mt-6 flex justify-end">
                    <button @click="showRecommendations = false"
                            class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Validation Results -->
    <div x-show="validationResults" class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-semibold mb-4">Assignment Validation Results</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
            <div class="bg-blue-50 p-4 rounded-lg">
                <div class="text-2xl font-bold text-blue-800" x-text="validationResults?.total_assignments || 0"></div>
                <div class="text-sm text-blue-600">Total Assignments</div>
            </div>
            <div class="bg-green-50 p-4 rounded-lg">
                <div class="text-2xl font-bold text-green-800" x-text="validationResults?.valid_assignments || 0"></div>
                <div class="text-sm text-green-600">Valid Assignments</div>
            </div>
            <div class="bg-red-50 p-4 rounded-lg">
                <div class="text-2xl font-bold text-red-800" x-text="validationResults?.invalid_assignments || 0"></div>
                <div class="text-sm text-red-600">Invalid Assignments</div>
            </div>
            <div class="bg-yellow-50 p-4 rounded-lg">
                <div class="text-2xl font-bold text-yellow-800" x-text="validationResults?.assignments_with_warnings || 0"></div>
                <div class="text-sm text-yellow-600">With Warnings</div>
            </div>
        </div>
    </div>
</div>

<script>
function smartAssignmentApp() {
    return {
        loading: false,
        maxAssignments: 10,
        dryRun: true,
        autoResults: null,
        validationResults: null,
        showRecommendations: false,
        recommendations: [],
        selectedMatchId: null,
        
        async runAutoAssignment() {
            this.loading = true;
            try {
                const formData = new FormData();
                formData.append('action', 'auto_assign');
                formData.append('max_assignments', this.maxAssignments);
                if (this.dryRun) {
                    formData.append('dry_run', '1');
                }
                
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });
                
                this.autoResults = await response.json();
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred during auto assignment');
            } finally {
                this.loading = false;
            }
        },
        
        async validateAllAssignments() {
            this.loading = true;
            try {
                const formData = new FormData();
                formData.append('action', 'validate_all');
                
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });
                
                this.validationResults = await response.json();
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred during validation');
            } finally {
                this.loading = false;
            }
        },
        
        async getRecommendations(matchId) {
            this.selectedMatchId = matchId;
            this.loading = true;
            try {
                const formData = new FormData();
                formData.append('action', 'get_recommendations');
                formData.append('match_id', matchId);
                formData.append('limit', '5');
                
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                if (result.success) {
                    this.recommendations = result.recommendations;
                    this.showRecommendations = true;
                } else {
                    alert('Error getting recommendations: ' + result.error);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred getting recommendations');
            } finally {
                this.loading = false;
            }
        },
        
        async assignTeam(matchId, teamId) {
            try {
                const formData = new FormData();
                formData.append('action', 'assign_team');
                formData.append('match_id', matchId);
                formData.append('team_id', teamId);
                
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                if (result.success) {
                    alert('Team assigned successfully!');
                    this.showRecommendations = false;
                    location.reload(); // Refresh to update the unassigned matches list
                } else {
                    alert('Error assigning team: ' + result.error);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred during assignment');
            }
        },
        
        formatDateTime(dateTime) {
            const date = new Date(dateTime);
            return date.toLocaleString('en-GB', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit',
                hour12: false
            });
        }
    }
}
</script>

<?php
$content = ob_get_clean();
include 'includes/layout.php';
?>
