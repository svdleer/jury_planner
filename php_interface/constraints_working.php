<?php
session_start();
require_once 'config/database.php';
require_once 'includes/TeamManager.php';

$teamManager = new TeamManager($db);

$pageTitle = 'Assignment Constraints';
$pageDescription = 'Manage jury assignment constraints, exclusions, and team capacities';

// Get teams - this should work since it works elsewhere
$teams = $teamManager->getAllTeams();

// Get exclusions
try {
    $sql = "SELECT e.*, t.name as team_name 
            FROM excluded_teams e
            JOIN jury_teams t ON e.team_id = t.id
            ORDER BY t.name, e.excluded_team";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $exclusions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $exclusions = [];
    $exclusionError = $e->getMessage();
}

ob_start();
?>

<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <div class="sm:flex sm:items-center sm:justify-between mb-6">
        <div class="min-w-0 flex-1">
            <h2 class="text-2xl font-bold leading-7 text-gray-900">Assignment Constraints</h2>
            <p class="mt-1 text-sm text-gray-500">Manage exclusions and team capacities for jury assignments</p>
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
                                Update Capacity
                            </button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Current Exclusions -->
    <div class="bg-white shadow-sm ring-1 ring-gray-900/5 rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Current Exclusion Constraints</h3>
            
            <?php if (isset($exclusionError)): ?>
                <div class="text-red-600 mb-4">Error loading exclusions: <?php echo htmlspecialchars($exclusionError); ?></div>
            <?php endif; ?>
            
            <?php if (empty($exclusions)): ?>
                <p class="text-gray-500">No exclusion constraints defined yet.</p>
            <?php else: ?>
                <div class="space-y-2">
                    <?php foreach ($exclusions as $exclusion): ?>
                        <div class="border rounded p-3">
                            <p><strong><?php echo htmlspecialchars($exclusion['team_name']); ?></strong> cannot jury for <strong><?php echo htmlspecialchars($exclusion['excluded_team']); ?></strong></p>
                            <?php if ($exclusion['reason']): ?>
                                <p class="text-sm text-gray-600">Reason: <?php echo htmlspecialchars($exclusion['reason']); ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <div class="mt-4">
                <p class="text-sm text-gray-600">Full constraint management interface coming soon!</p>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include 'includes/layout.php';
?>
