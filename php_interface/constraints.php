<?php
session_start();
require_once 'includes/translations.php';
require_once 'config/database.php';
require_once 'includes/TeamManager.php';
require_once 'includes/CustomConstraintManager.php';

$teamManager = new TeamManager($db);
$constraintManager = new CustomConstraintManager($db);

$pageTitle = t('assignment_constraints');
$pageDescription = t('assignment_constraints_description');

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
            } elseif ($_POST['action'] === 'add_custom_constraint') {
                $type = $_POST['constraint_type'];
                $sourceTeam = $_POST['source_team'];
                $targetTeam = $_POST['target_team'] ?? null;
                $date = $_POST['constraint_date'] ?? null;
                $value = $_POST['constraint_value'] ?? null;
                $reason = $_POST['reason'] ?? '';
                
                if ($constraintManager->addConstraint($type, $sourceTeam, $targetTeam, $date, $value, $reason)) {
                    $message = "Custom constraint added successfully.";
                } else {
                    $message = "Error adding constraint.";
                }
            } elseif ($_POST['action'] === 'toggle_constraint' && isset($_POST['constraint_id'])) {
                $isActive = $_POST['is_active'] == '1' ? 0 : 1;
                if ($constraintManager->updateConstraintStatus($_POST['constraint_id'], $isActive)) {
                    $message = "Constraint status updated.";
                }
            } elseif ($_POST['action'] === 'delete_constraint' && isset($_POST['constraint_id'])) {
                if ($constraintManager->deleteConstraint($_POST['constraint_id'])) {
                    $message = "Constraint deleted successfully.";
                }
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

// Get custom constraints
$customConstraints = [];
$constraintTypes = [];
try {
    // Check if custom_constraints table exists
    $stmt = $db->query("SHOW TABLES LIKE 'custom_constraints'");
    if ($stmt->rowCount() > 0) {
        $customConstraints = $constraintManager->getAllConstraints();
        $constraintTypes = $constraintManager->getConstraintTypes();
    }
} catch (Exception $e) {
    // Table doesn't exist yet
}

ob_start();
?>

<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">

    <?php if ($message): ?>
        <div class="mb-6 p-4 rounded-md <?= strpos($message, 'Error') === 0 ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700' ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <!-- Add Exclusion Form -->
    <div class="bg-white shadow-sm ring-1 ring-gray-900/5 rounded-lg mb-6">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4"><?php echo t('exclude_team_from_jury_duty'); ?></h3>
            <form method="POST" class="flex gap-4 items-end">
                <input type="hidden" name="action" value="exclude_team">
                <div class="flex-1">
                    <label for="team_name" class="block text-sm font-medium text-gray-700 mb-1">
                        <?php echo t('select_team_to_exclude'); ?>
                    </label>
                    <select id="team_name" 
                            name="team_name" 
                            required
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-water-blue-500 focus:ring-water-blue-500 sm:text-sm">
                        <option value="">-- <?php echo t('select_team'); ?> --</option>
                        <?php foreach ($teams as $team): ?>
                            <?php 
                            // Check if this team is already excluded
                            $isAlreadyExcluded = false;
                            foreach ($exclusions as $exclusion) {
                                if ($exclusion['name'] === $team['name']) {
                                    $isAlreadyExcluded = true;
                                    break;
                                }
                            }
                            ?>
                            <?php if (!$isAlreadyExcluded): ?>
                                <option value="<?php echo htmlspecialchars($team['name']); ?>">
                                    <?php echo htmlspecialchars($team['name']); ?>
                                </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" 
                        class="inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700">
                    <?php echo t('exclude_team'); ?>
                </button>
            </form>
        </div>
    </div>

    <!-- Team Capacities -->
    <div class="bg-white shadow-sm ring-1 ring-gray-900/5 rounded-lg mb-6">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4"><?php echo t('team_capacities'); ?></h3>
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
                    <?php echo t('use_form_above_to_exclude'); ?>
                    <?php echo t('excluded_teams_not_assigned'); ?>
                </p>
            </div>
        </div>
    </div>

    <!-- Custom Constraints Section -->
    <?php if (!empty($constraintTypes)): ?>
    <div class="bg-white shadow-sm ring-1 ring-gray-900/5 rounded-lg mb-6">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Custom Constraints</h3>
            
            <!-- Add Custom Constraint Form -->
            <div class="border-b border-gray-200 pb-6 mb-6">
                <h4 class="text-md font-medium text-gray-900 mb-3"><?php echo t('add_new_constraint'); ?></h4>
                <form method="POST" class="space-y-4" x-data="{ constraintType: '', showFields: {} }">
                    <input type="hidden" name="action" value="add_custom_constraint">
                    
                    <div>
                        <label for="constraint_type" class="block text-sm font-medium text-gray-700 mb-1">Constraint Type</label>
                        <select name="constraint_type" x-model="constraintType" required
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-water-blue-500 focus:ring-water-blue-500 sm:text-sm">
                            <option value="">-- Select constraint type --</option>
                            <?php foreach ($constraintTypes as $key => $type): ?>
                                <option value="<?php echo $key; ?>"><?php echo $type['name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="source_team" class="block text-sm font-medium text-gray-700 mb-1">Source Team</label>
                            <select name="source_team" required
                                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-water-blue-500 focus:ring-water-blue-500 sm:text-sm">
                                <option value="">-- Select team --</option>
                                <?php foreach ($teams as $team): ?>
                                    <option value="<?php echo htmlspecialchars($team['name']); ?>">
                                        <?php echo htmlspecialchars($team['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div x-show="constraintType === 'team_team_conflict'">
                            <label for="target_team" class="block text-sm font-medium text-gray-700 mb-1">Target Team</label>
                            <select name="target_team"
                                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-water-blue-500 focus:ring-water-blue-500 sm:text-sm">
                                <option value="">-- Select target team --</option>
                                <?php foreach ($teams as $team): ?>
                                    <option value="<?php echo htmlspecialchars($team['name']); ?>">
                                        <?php echo htmlspecialchars($team['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div x-show="constraintType === 'date_restriction'">
                            <label for="constraint_date" class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                            <input type="date" name="constraint_date"
                                   class="block w-full rounded-md border-gray-300 shadow-sm focus:border-water-blue-500 focus:ring-water-blue-500 sm:text-sm">
                        </div>
                        
                        <div x-show="constraintType === 'capacity_override' || constraintType === 'assignment_limit'">
                            <label for="constraint_value" class="block text-sm font-medium text-gray-700 mb-1">
                                Value <span x-show="constraintType === 'capacity_override'">(0.1 - 5.0)</span>
                            </label>
                            <input type="number" name="constraint_value" step="0.1" min="0.1" max="5.0"
                                   class="block w-full rounded-md border-gray-300 shadow-sm focus:border-water-blue-500 focus:ring-water-blue-500 sm:text-sm">
                        </div>
                    </div>
                    
                    <div>
                        <label for="reason" class="block text-sm font-medium text-gray-700 mb-1">Reason</label>
                        <textarea name="reason" rows="2"
                                  class="block w-full rounded-md border-gray-300 shadow-sm focus:border-water-blue-500 focus:ring-water-blue-500 sm:text-sm"
                                  placeholder="Optional explanation for this constraint"></textarea>
                    </div>
                    
                    <button type="submit"
                            class="inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-water-blue-600 hover:bg-water-blue-700">
                        Add Constraint
                    </button>
                </form>
            </div>
            
            <!-- Existing Custom Constraints -->
            <h4 class="text-md font-medium text-gray-900 mb-3"><?php echo t('active_constraints'); ?></h4>
            <?php if (empty($customConstraints)): ?>
                <p class="text-gray-500">No custom constraints defined yet.</p>
            <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($customConstraints as $constraint): ?>
                        <div class="border rounded-lg p-4 <?php echo $constraint['is_active'] ? 'bg-white' : 'bg-gray-50'; ?>">
                            <div class="flex justify-between items-start">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-2">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                                     <?php echo $constraint['is_active'] ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?>">
                                            <?php echo $constraintTypes[$constraint['constraint_type']]['name'] ?? $constraint['constraint_type']; ?>
                                        </span>
                                        <?php if (!$constraint['is_active']): ?>
                                            <span class="text-xs text-gray-500">(Disabled)</span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <p class="text-sm text-gray-900">
                                        <strong><?php echo htmlspecialchars($constraint['source_team']); ?></strong>
                                        <?php if ($constraint['target_team']): ?>
                                            ↔ <strong><?php echo htmlspecialchars($constraint['target_team']); ?></strong>
                                        <?php endif; ?>
                                        <?php if ($constraint['constraint_date']): ?>
                                            on <?php echo date('M j, Y', strtotime($constraint['constraint_date'])); ?>
                                        <?php endif; ?>
                                        <?php if ($constraint['constraint_value']): ?>
                                            (Value: <?php echo $constraint['constraint_value']; ?>)
                                        <?php endif; ?>
                                    </p>
                                    
                                    <?php if ($constraint['reason']): ?>
                                        <p class="text-xs text-gray-600 mt-1"><?php echo htmlspecialchars($constraint['reason']); ?></p>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="flex gap-2 ml-4">
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="action" value="toggle_constraint">
                                        <input type="hidden" name="constraint_id" value="<?php echo $constraint['id']; ?>">
                                        <input type="hidden" name="is_active" value="<?php echo $constraint['is_active']; ?>">
                                        <button type="submit" 
                                                class="text-xs px-2 py-1 rounded <?php echo $constraint['is_active'] ? 'text-orange-600 hover:text-orange-800' : 'text-green-600 hover:text-green-800'; ?>">
                                            <?php echo $constraint['is_active'] ? 'Disable' : 'Enable'; ?>
                                        </button>
                                    </form>
                                    
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="action" value="delete_constraint">
                                        <input type="hidden" name="constraint_id" value="<?php echo $constraint['id']; ?>">
                                        <button type="submit" 
                                                class="text-xs px-2 py-1 rounded text-red-600 hover:text-red-800"
                                                onclick="return confirm('Delete this constraint?')">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php else: ?>
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-blue-800">Custom Constraints Available</h3>
                <div class="mt-2 text-sm text-blue-700">
                    <p>Run the setup script to enable advanced constraint management:</p>
                    <code class="mt-2 block bg-blue-100 p-2 rounded text-xs">curl "https://jury2025.useless.nl/setup_custom_constraints.php"</code>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

</div>

<?php
$content = ob_get_clean();
include 'includes/layout.php';
?>
