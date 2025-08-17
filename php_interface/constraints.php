<?php
session_start();
require_once 'config/database.php';
require_once 'includes/TeamManager.php';

$teamManager = new TeamManager($db);

// Handle form submissions
if ($_POST) {
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'add_exclusion':
                    $sql = "INSERT INTO excluded_teams (team_id, excluded_team, reason) VALUES (?, ?, ?)";
                    $stmt = $db->prepare($sql);
                    $stmt->execute([$_POST['team_id'], $_POST['excluded_team'], $_POST['reason']]);
                    $_SESSION['success'] = 'Exclusion constraint added successfully!';
                    header('Location: constraints.php');
                    exit;
                    
                case 'delete_exclusion':
                    $sql = "DELETE FROM excluded_teams WHERE id = ?";
                    $stmt = $db->prepare($sql);
                    $stmt->execute([$_POST['exclusion_id']]);
                    $_SESSION['success'] = 'Exclusion constraint deleted successfully!';
                    header('Location: constraints.php');
                    exit;
                    
                case 'update_capacity':
                    $sql = "UPDATE jury_teams SET capacity_factor = ? WHERE id = ?";
                    $stmt = $db->prepare($sql);
                    $stmt->execute([$_POST['capacity_factor'], $_POST['team_id']]);
                    $_SESSION['success'] = 'Team capacity updated successfully!';
                    header('Location: constraints.php');
                    exit;
            }
        }
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
}

// Get data
$teams = $teamManager->getAllTeams();

// Get exclusions
$sql = "SELECT e.*, t.name as team_name 
        FROM excluded_teams e
        JOIN jury_teams t ON e.team_id = t.id
        ORDER BY t.name, e.excluded_team";
$stmt = $db->prepare($sql);
$stmt->execute();
$exclusions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all unique team names for exclusion dropdown
$sql = "SELECT DISTINCT home_team as team_name FROM home_matches
        UNION
        SELECT DISTINCT away_team as team_name FROM home_matches
        ORDER BY team_name";
$stmt = $db->prepare($sql);
$stmt->execute();
$allTeams = $stmt->fetchAll(PDO::FETCH_COLUMN);

$pageTitle = 'Assignment Constraints';
$pageDescription = 'Manage jury assignment constraints, exclusions, and team capacities';

ob_start();
?>

<div x-data="constraintsApp()" x-init="init()">
    <!-- Header -->
    <div class="sm:flex sm:items-center sm:justify-between mb-6">
        <div class="min-w-0 flex-1">
            <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:truncate sm:text-3xl sm:tracking-tight">
                Assignment Constraints
            </h2>
            <p class="mt-1 text-sm text-gray-500">Manage exclusions and team capacities for jury assignments</p>
        </div>
        <div class="mt-4 flex sm:ml-4 sm:mt-0 space-x-3">
            <button @click="showAddExclusionModal = true" type="button" class="inline-flex items-center rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500">
                <svg class="-ml-0.5 mr-1.5 h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
                </svg>
                Add Exclusion
            </button>
        </div>
    </div>

    <!-- Team Capacities -->
    <div class="bg-white shadow-sm ring-1 ring-gray-900/5 rounded-lg mb-6">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Team Capacities</h3>
            <p class="text-sm text-gray-600 mb-4">Set how many assignments each team can handle (1.0 = standard capacity)</p>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php foreach ($teams as $team): ?>
                    <div class="border rounded-lg p-4">
                        <form method="POST" class="space-y-3">
                            <input type="hidden" name="action" value="update_capacity">
                            <input type="hidden" name="team_id" value="<?php echo $team['id']; ?>">
                            
                            <h4 class="font-medium text-gray-900"><?php echo htmlspecialchars($team['name']); ?></h4>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Capacity Factor</label>
                                <input type="number" name="capacity_factor" step="0.1" min="0.1" max="5.0" 
                                       value="<?php echo $team['capacity_factor'] ?? 1.0; ?>"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-water-blue-500 focus:ring-water-blue-500 sm:text-sm">
                            </div>
                            
                            <button type="submit" class="w-full inline-flex justify-center items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-water-blue-600 hover:bg-water-blue-700">
                                Update
                            </button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Exclusion Constraints -->
    <div class="bg-white shadow-sm ring-1 ring-gray-900/5 rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Exclusion Constraints</h3>
            <p class="text-sm text-gray-600 mb-4">Teams that should not jury for specific other teams</p>
            
            <?php if (empty($exclusions)): ?>
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728L5.636 5.636m12.728 12.728L18.364 5.636 5.636 18.364"></path>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No exclusions defined</h3>
                    <p class="mt-1 text-sm text-gray-500">Get started by adding an exclusion constraint.</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-300">
                        <thead>
                            <tr>
                                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Jury Team</th>
                                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Cannot Jury For</th>
                                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Reason</th>
                                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php foreach ($exclusions as $exclusion): ?>
                                <tr>
                                    <td class="px-3 py-4 text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($exclusion['team_name']); ?>
                                    </td>
                                    <td class="px-3 py-4 text-sm text-gray-500">
                                        <?php echo htmlspecialchars($exclusion['excluded_team']); ?>
                                    </td>
                                    <td class="px-3 py-4 text-sm text-gray-500">
                                        <?php echo htmlspecialchars($exclusion['reason'] ?? 'No reason provided'); ?>
                                    </td>
                                    <td class="px-3 py-4 text-sm text-gray-500">
                                        <button @click="deleteExclusion(<?php echo $exclusion['id']; ?>, '<?php echo htmlspecialchars($exclusion['team_name']); ?>', '<?php echo htmlspecialchars($exclusion['excluded_team']); ?>')" 
                                                class="text-red-600 hover:text-red-900">
                                            Delete
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Add Exclusion Modal -->
    <div x-show="showAddExclusionModal" class="fixed inset-0 z-10 overflow-y-auto" style="display: none;">
        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
            <div class="relative transform overflow-hidden rounded-lg bg-white px-4 pb-4 pt-5 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6">
                <form method="POST">
                    <input type="hidden" name="action" value="add_exclusion">
                    
                    <div class="sm:flex sm:items-start">
                        <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left w-full">
                            <h3 class="text-base font-semibold leading-6 text-gray-900 mb-4">Add Exclusion Constraint</h3>
                            
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Jury Team</label>
                                    <select name="team_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-water-blue-500 focus:ring-water-blue-500 sm:text-sm">
                                        <option value="">Select a jury team...</option>
                                        <?php foreach ($teams as $team): ?>
                                            <option value="<?php echo $team['id']; ?>"><?php echo htmlspecialchars($team['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Cannot Jury For Team</label>
                                    <select name="excluded_team" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-water-blue-500 focus:ring-water-blue-500 sm:text-sm">
                                        <option value="">Select a team...</option>
                                        <?php foreach ($allTeams as $teamName): ?>
                                            <option value="<?php echo htmlspecialchars($teamName); ?>"><?php echo htmlspecialchars($teamName); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Reason (optional)</label>
                                    <textarea name="reason" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-water-blue-500 focus:ring-water-blue-500 sm:text-sm" placeholder="Why is this team excluded?"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                        <button type="submit" class="inline-flex w-full justify-center rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500 sm:ml-3 sm:w-auto">
                            Add Exclusion
                        </button>
                        <button @click="showAddExclusionModal = false" type="button" class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Exclusion Modal -->
    <div x-show="showDeleteModal" class="fixed inset-0 z-10 overflow-y-auto" style="display: none;">
        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
            <div class="relative transform overflow-hidden rounded-lg bg-white px-4 pb-4 pt-5 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6">
                <div class="sm:flex sm:items-start">
                    <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                        <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                        </svg>
                    </div>
                    <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left">
                        <h3 class="text-base font-semibold leading-6 text-gray-900">Delete Exclusion</h3>
                        <div class="mt-2">
                            <p class="text-sm text-gray-500" x-text="`Are you sure you want to delete the exclusion: ${deleteExclusionTeam} cannot jury for ${deleteExcludedTeam}?`"></p>
                        </div>
                    </div>
                </div>
                <form method="POST" class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                    <input type="hidden" name="action" value="delete_exclusion">
                    <input type="hidden" name="exclusion_id" x-model="deleteExclusionId">
                    <button type="submit" class="inline-flex w-full justify-center rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500 sm:ml-3 sm:w-auto">Delete</button>
                    <button @click="showDeleteModal = false" type="button" class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto">Cancel</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function constraintsApp() {
    return {
        showAddExclusionModal: false,
        showDeleteModal: false,
        deleteExclusionId: null,
        deleteExclusionTeam: '',
        deleteExcludedTeam: '',
        
        init() {
            // Initialize any needed data
        },
        
        deleteExclusion(id, teamName, excludedTeam) {
            this.deleteExclusionId = id;
            this.deleteExclusionTeam = teamName;
            this.deleteExcludedTeam = excludedTeam;
            this.showDeleteModal = true;
        }
    }
}
</script>

<?php
$content = ob_get_clean();
include 'includes/layout.php';
?>
