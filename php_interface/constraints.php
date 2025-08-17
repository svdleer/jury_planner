<?php
session_start();
require_once 'config/database.php';
require_once 'includes/TeamManager.php';

$teamManager = new TeamManager($db);

$pageTitle = 'Assignment Constraints';
$pageDescription = 'Manage jury assignment constraints, exclusions, and team capacities';

$message = '';

// Handle form submissions
if ($_POST) {
    try {
        if (isset($_POST['action'])) {
            if ($_POST['action'] === 'exclude_team' && isset($_POST['team_name'])) {
                $teamName = trim($_POST['team_name']);
                if (!empty($teamName)) {
                    // Check if already excluded
                    $stmt = $db->prepare("SELECT COUNT(*) FROM excluded_teams WHERE name = ?");
                    $stmt->execute([$teamName]);
                    if ($stmt->fetchColumn() == 0) {
                        $stmt = $db->prepare("INSERT INTO excluded_teams (name) VALUES (?)");
                        $stmt->execute([$teamName]);
                        $message = "Team '{$teamName}' excluded successfully.";
                    } else {
                        $message = "Team '{$teamName}' is already excluded.";
                    }
                }
            } elseif ($_POST['action'] === 'remove_exclusion' && isset($_POST['exclusion_id'])) {
                $stmt = $db->prepare("DELETE FROM excluded_teams WHERE id = ?");
                $stmt->execute([$_POST['exclusion_id']]);
                $message = "Exclusion removed successfully.";
            } elseif ($_POST['action'] === 'update_capacity' && isset($_POST['team_id'], $_POST['capacity_factor'])) {
                $stmt = $db->prepare("UPDATE jury_teams SET capacity_factor = ? WHERE id = ?");
                $stmt->execute([$_POST['capacity_factor'], $_POST['team_id']]);
                $message = "Team capacity updated successfully.";
            }
        }
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
    }
}

// Get teams - this should work since it works elsewhere
$teams = $teamManager->getAllTeams();

// Get exclusions
try {
    $sql = "SELECT * FROM excluded_teams ORDER BY name";
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

    <?php if ($message): ?>
        <div class="mb-6 p-4 rounded-md <?= strpos($message, 'Error') === 0 ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700' ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <!-- Add Exclusion Form -->
    <div class="bg-white shadow-sm ring-1 ring-gray-900/5 rounded-lg mb-6">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Exclude Team from Jury Duty</h3>
            <form method="POST" class="flex gap-4 items-end">
                <input type="hidden" name="action" value="exclude_team">
                <div class="flex-1">
                    <label for="team_name" class="block text-sm font-medium text-gray-700 mb-1">
                        Team Name
                    </label>
                    <input type="text" 
                           id="team_name" 
                           name="team_name" 
                           required
                           class="block w-full rounded-md border-gray-300 shadow-sm focus:border-water-blue-500 focus:ring-water-blue-500 sm:text-sm"
                           placeholder="Enter team name">
                </div>
                <button type="submit" 
                        class="inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700">
                    Exclude Team
                </button>
            </form>
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
                        <div class="border rounded p-3 flex justify-between items-center">
                            <p><strong><?php echo htmlspecialchars($exclusion['name']); ?></strong> is excluded from jury duty</p>
                            <form method="POST" class="inline">
                                <input type="hidden" name="action" value="remove_exclusion">
                                <input type="hidden" name="exclusion_id" value="<?php echo $exclusion['id']; ?>">
                                <button type="submit" 
                                        class="text-red-600 hover:text-red-800 text-sm px-3 py-1 border border-red-300 rounded"
                                        onclick="return confirm('Remove this exclusion?')">
                                    Remove
                                </button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <div class="mt-4">
                <p class="text-sm text-gray-600">
                    Use the form above to exclude teams from jury duty. 
                    Excluded teams will not be automatically assigned to matches.
                </p>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include 'includes/layout.php';
?>
