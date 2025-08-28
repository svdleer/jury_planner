<?php
session_start();
require_once 'includes/translations.php';
require_once 'config/database.php';
require_once 'includes/ConstraintManager.php';
require_once 'optimization_interface.php';

$constraintManager = new ConstraintManager($db);
$optimizationInterface = new OptimizationInterface($db);

$pageTitle = t('constraint_editor');
$pageDescription = t('constraint_editor_description');

// Handle form submissions
if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create_constraint') {
        $result = $constraintManager->createConstraint($_POST);
        $message = $result['success'] ? t('constraint_created_success') : ($result['error'] ?? t('unknown_error'));
        $messageType = $result['success'] ? 'success' : 'error';
    } elseif ($action === 'update_constraint') {
        $result = $constraintManager->updateConstraint($_POST['constraint_id'], $_POST);
        $message = $result['success'] ? t('constraint_updated_success') : ($result['error'] ?? t('unknown_error'));
        $messageType = $result['success'] ? 'success' : 'error';
    } elseif ($action === 'delete_constraint') {
        $result = $constraintManager->deleteConstraint($_POST['constraint_id']);
        $message = $result['success'] ? t('constraint_deleted_success') : ($result['error'] ?? t('unknown_error'));
        $messageType = $result['success'] ? 'success' : 'error';
    } elseif ($action === 'toggle_constraint') {
        $result = $constraintManager->toggleConstraint($_POST['constraint_id']);
        $message = $result['success'] ? t('constraint_toggled_success') : ($result['error'] ?? t('unknown_error'));
        $messageType = $result['success'] ? 'success' : 'error';
    } elseif ($action === 'import_constraints') {
        $result = $constraintManager->importExistingConstraints();
        if ($result['success']) {
            $message = t('constraints_imported_success') . " " . $result['imported'] . " " . t('imported') . ", " . $result['skipped'] . " " . t('skipped');
            $messageType = 'success';
        } else {
            $message = t('constraints_import_failed');
            $messageType = 'error';
        }
    } elseif ($action === 'import_python_templates') {
        $result = $constraintManager->importPythonTemplateConstraints();
        if ($result['success']) {
            $message = t('python_templates_imported_success') . " " . $result['imported'] . " " . t('imported') . ", " . $result['skipped'] . " " . t('skipped');
            $messageType = 'success';
        } else {
            $message = t('python_templates_import_failed');
            $messageType = 'error';
        }
    } elseif ($action === 'import_all_constraints') {
        $result = $constraintManager->importAllConstraints();
        if ($result['success']) {
            $message = t('all_constraints_imported_success') . " " . 
                      $result['total_imported'] . " " . t('imported') . " (" .
                      $result['php_imported'] . " PHP + " . $result['python_imported'] . " Python), " .
                      $result['total_skipped'] . " " . t('skipped');
            $messageType = 'success';
        } else {
            $message = t('all_constraints_import_failed');
            $messageType = 'error';
        }
    } elseif ($action === 'run_optimization') {
        $result = $optimizationInterface->runOptimization([
            'timeout' => intval($_POST['timeout'] ?? 300),
            'solver_type' => $_POST['solver_type'] ?? 'auto'
        ]);
        if ($result['success']) {
            $message = t('optimization_success') . " " . $result['imported_assignments'] . " " . t('assignments_imported');
            $messageType = 'success';
        } else {
            $message = t('optimization_failed') . ": " . ($result['error'] ?? t('unknown_error'));
            $messageType = 'error';
        }
    } elseif ($action === 'preview_optimization') {
        $result = $optimizationInterface->previewOptimization([
            'timeout' => intval($_POST['timeout'] ?? 120),
            'solver_type' => $_POST['solver_type'] ?? 'auto'
        ]);
        $previewResult = $result; // Store for display
    } elseif ($action === 'validate_constraints') {
        $validationResult = $optimizationInterface->validateConstraints();
    }
}

// Get all constraints
$constraints = $constraintManager->getAllConstraints();
$teams = $constraintManager->getAllTeams();
$optimizationStats = $optimizationInterface->getOptimizationHistory();
$recommendations = $optimizationInterface->getConstraintRecommendations();
$pythonAvailability = $optimizationInterface->isPythonOptimizationAvailable();

// Check if database tables exist
$tablesExist = true;
try {
    $db->query("SELECT 1 FROM planning_rules LIMIT 1");
} catch (PDOException $e) {
    $tablesExist = false;
}

ob_start();
?>

<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    
    <!-- Messages -->
    <?php if (isset($message)): ?>
    <div class="mb-6">
        <div class="p-4 rounded-md <?php echo $messageType === 'success' ? 'bg-green-50 border border-green-200 text-green-700' : 'bg-red-50 border border-red-200 text-red-700'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Import Actions -->
    <div class="mb-6 flex flex-wrap gap-3">
        <!-- Import Dropdown -->
        <div class="relative inline-block text-left">
            <div>
                <button type="button" class="inline-flex justify-center w-full rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" id="import-menu-button" aria-expanded="true" aria-haspopup="true" onclick="toggleImportMenu()">
                        📥 <?php echo t('import_constraints'); ?>
                        <svg class="-mr-1 ml-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>

                <div class="origin-top-right absolute right-0 mt-2 w-72 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 focus:outline-none z-10 hidden" id="import-menu" role="menu" aria-orientation="vertical" aria-labelledby="import-menu-button" tabindex="-1">
                    <div class="py-1" role="none">
                        <form method="POST" class="block" role="none">
                            <input type="hidden" name="action" value="import_constraints">
                            <button type="submit" class="group flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900 w-full text-left" role="menuitem" onclick="return confirm('<?php echo t('confirm_import_php_constraints'); ?>')">
                                <span class="mr-3">🔧</span>
                                <div>
                                    <div class="font-medium"><?php echo t('php_legacy_constraints'); ?></div>
                                    <div class="text-xs text-gray-500"><?php echo t('import_hardcoded_system_constraints'); ?></div>
                                </div>
                            </button>
                        </form>
                        <form method="POST" class="block" role="none">
                            <input type="hidden" name="action" value="import_python_templates">
                            <button type="submit" class="group flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900 w-full text-left" role="menuitem" onclick="return confirm('<?php echo t('confirm_import_python_templates'); ?>')">
                                <span class="mr-3">🐍</span>
                                <div>
                                    <div class="font-medium"><?php echo t('python_templates'); ?></div>
                                    <div class="text-xs text-gray-500"><?php echo t('import_python_optimization_templates'); ?></div>
                                </div>
                            </button>
                        </form>
                        <form method="POST" class="block" role="none">
                            <input type="hidden" name="action" value="import_all_constraints">
                            <button type="submit" class="group flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900 w-full text-left" role="menuitem" onclick="return confirm('<?php echo t('confirm_import_all_constraints'); ?>')">
                                <span class="mr-3">⚡</span>
                                <div>
                                    <div class="font-medium"><?php echo t('import_all_constraints'); ?></div>
                                    <div class="text-xs text-gray-500"><?php echo t('import_all_available_constraints'); ?></div>
                                </div>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <button type="button" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded" 
                    onclick="showCreateConstraintModal()">
                <?php echo t('create_new_constraint'); ?>
            </button>
        </div>
    </div>

    <!-- Messages -->
    <?php if (isset($message)): ?>
    <div class="mb-4 p-4 rounded-md <?php echo $messageType === 'success' ? 'bg-green-50 text-green-800' : 'bg-red-50 text-red-800'; ?>">
        <?php echo htmlspecialchars($message); ?>
    </div>
    <?php endif; ?>

    <!-- Database Migration Notice -->
    <?php if (!$tablesExist): ?>
    <div class="mb-6 p-4 rounded-md bg-yellow-50 border border-yellow-200">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-yellow-800">
                    <?php echo t('database_migration_required'); ?>
                </h3>
                <div class="mt-2 text-sm text-yellow-700">
                    <p><?php echo t('database_migration_description'); ?></p>
                </div>
                <div class="mt-4">
                    <div class="-mx-2 -my-1.5 flex">
                        <a href="migrate_database.php" class="bg-yellow-50 px-2 py-1.5 rounded-md text-sm font-medium text-yellow-800 hover:bg-yellow-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-yellow-50 focus:ring-yellow-600">
                            <?php echo t('run_migration'); ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Constraints List -->
    <div class="bg-white shadow overflow-hidden sm:rounded-md">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                <?php echo t('existing_constraints'); ?>
            </h3>
            
            <?php if (empty($constraints)): ?>
            <p class="text-gray-500 text-center py-8"><?php echo t('no_constraints_found'); ?></p>
            <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($constraints as $constraint): ?>
                <div class="border border-gray-200 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <div class="flex items-center space-x-3">
                                <h4 class="text-lg font-medium text-gray-900">
                                    <?php echo htmlspecialchars($constraint['name']); ?>
                                </h4>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    <?php 
                                    switch($constraint['rule_type']) {
                                        case 'forbidden': echo 'bg-red-100 text-red-800'; break;
                                        case 'not_preferred': echo 'bg-orange-100 text-orange-800'; break;
                                        case 'less_preferred': echo 'bg-yellow-100 text-yellow-800'; break;
                                        case 'most_preferred': echo 'bg-green-100 text-green-800'; break;
                                    }
                                    ?>">
                                    <?php echo t('rule_type_' . $constraint['rule_type']); ?>
                                </span>
                                <span class="text-sm text-gray-500">
                                    <?php echo t('weight'); ?>: <?php echo $constraint['weight']; ?>
                                </span>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    <?php echo $constraint['is_active'] ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?>">
                                    <?php echo $constraint['is_active'] ? t('active') : t('inactive'); ?>
                                </span>
                            </div>
                            <p class="mt-1 text-sm text-gray-600">
                                <?php echo htmlspecialchars($constraint['description']); ?>
                            </p>
                            <?php if ($constraint['parameters']): ?>
                            <div class="mt-2">
                                <span class="text-xs text-gray-500"><?php echo t('parameters'); ?>:</span>
                                <div class="text-xs bg-gray-50 px-2 py-1 rounded border">
                                    <?php 
                                    $parameters = $constraint['parameters'];
                                    if (is_string($parameters)) {
                                        $parameters = json_decode($parameters, true);
                                    }
                                    
                                    // Display parameters in a more user-friendly way
                                    if (is_array($parameters)) {
                                        $displayParams = [];
                                        foreach ($parameters as $key => $value) {
                                            if ($key === 'constraint_type') continue; // Skip internal field
                                            
                                            if (is_bool($value)) {
                                                $value = $value ? 'Yes' : 'No';
                                            } elseif (is_null($value)) {
                                                $value = 'Not set';
                                            }
                                            
                                            $displayParams[] = ucfirst(str_replace('_', ' ', $key)) . ': ' . $value;
                                        }
                                        echo htmlspecialchars(implode(' • ', $displayParams));
                                    } else {
                                        echo htmlspecialchars($parameters);
                                    }
                                    ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="flex items-center space-x-2">
                            <form method="POST" class="inline">
                                <input type="hidden" name="action" value="toggle_constraint">
                                <input type="hidden" name="constraint_id" value="<?php echo $constraint['id']; ?>">
                                <button type="submit" class="text-sm px-3 py-1 rounded
                                    <?php echo $constraint['is_active'] ? 'bg-gray-200 text-gray-700 hover:bg-gray-300' : 'bg-green-200 text-green-700 hover:bg-green-300'; ?>">
                                    <?php echo $constraint['is_active'] ? t('deactivate') : t('activate'); ?>
                                </button>
                            </form>
                            <button type="button" onclick="editConstraint(<?php echo htmlspecialchars(json_encode($constraint)); ?>)"
                                    class="text-sm bg-blue-200 text-blue-700 hover:bg-blue-300 px-3 py-1 rounded">
                                <?php echo t('edit'); ?>
                            </button>
                            <form method="POST" class="inline" onsubmit="return confirm('<?php echo t('confirm_delete_constraint'); ?>')">
                                <input type="hidden" name="action" value="delete_constraint">
                                <input type="hidden" name="constraint_id" value="<?php echo $constraint['id']; ?>">
                                <button type="submit" class="text-sm bg-red-200 text-red-700 hover:bg-red-300 px-3 py-1 rounded">
                                    <?php echo t('delete'); ?>
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
</div>

<!-- Create/Edit Constraint Modal -->
<div id="constraintModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900 mb-4" id="modalTitle">
                <?php echo t('create_new_constraint'); ?>
            </h3>
            
            <form id="constraintForm" method="POST">
                <input type="hidden" name="action" value="create_constraint" id="formAction">
                <input type="hidden" name="constraint_id" value="" id="constraintId">
                
                <!-- Basic Information -->
                <div class="space-y-4">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">
                            <?php echo t('constraint_name'); ?> *
                        </label>
                        <input type="text" name="name" id="name" required
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    
                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700">
                            <?php echo t('description'); ?>
                        </label>
                        <textarea name="description" id="description" rows="3"
                                  class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="rule_type" class="block text-sm font-medium text-gray-700">
                                <?php echo t('rule_type'); ?> *
                            </label>
                            <select name="rule_type" id="rule_type" required
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="forbidden"><?php echo t('rule_type_forbidden'); ?></option>
                                <option value="not_preferred"><?php echo t('rule_type_not_preferred'); ?></option>
                                <option value="less_preferred"><?php echo t('rule_type_less_preferred'); ?></option>
                                <option value="most_preferred"><?php echo t('rule_type_most_preferred'); ?></option>
                            </select>
                        </div>
                        
                        <div>
                            <label for="weight" class="block text-sm font-medium text-gray-700">
                                <?php echo t('weight'); ?> *
                            </label>
                            <input type="number" name="weight" id="weight" step="0.1" required
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                    </div>
                    
                    <!-- Constraint Type Selection -->
                    <div>
                        <label for="constraint_type" class="block text-sm font-medium text-gray-700">
                            <?php echo t('constraint_type'); ?> *
                        </label>
                        <select name="constraint_type" id="constraint_type" required onchange="updateConstraintForm()"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            <option value=""><?php echo t('select_constraint_type'); ?></option>
                            <option value="team_unavailable"><?php echo t('team_unavailable'); ?></option>
                            <option value="avoid_consecutive_matches"><?php echo t('avoid_consecutive_matches'); ?></option>
                            <option value="preferred_duty"><?php echo t('preferred_duty'); ?></option>
                            <option value="rest_between_matches"><?php echo t('rest_between_matches'); ?></option>
                            <option value="max_assignments_per_day"><?php echo t('max_assignments_per_day'); ?></option>
                            <option value="time_preference"><?php echo t('time_preference'); ?></option>
                        </select>
                    </div>
                    
                    <!-- Dynamic Parameters Section -->
                    <div id="parametersSection" class="hidden">
                        <h4 class="text-md font-medium text-gray-900 mb-2"><?php echo t('constraint_parameters'); ?></h4>
                        <div id="parametersContainer"></div>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-3 mt-6">
                    <button type="button" onclick="hideConstraintModal()"
                            class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">
                        <?php echo t('cancel'); ?>
                    </button>
                    <button type="submit"
                            class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded">
                        <span id="submitButtonText"><?php echo t('create_constraint'); ?></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const teams = <?php echo json_encode($teams); ?>;

function showCreateConstraintModal() {
    document.getElementById('modalTitle').textContent = '<?php echo t('create_new_constraint'); ?>';
    document.getElementById('formAction').value = 'create_constraint';
    document.getElementById('constraintId').value = '';
    document.getElementById('submitButtonText').textContent = '<?php echo t('create_constraint'); ?>';
    document.getElementById('constraintForm').reset();
    document.getElementById('constraintModal').classList.remove('hidden');
    updateConstraintForm();
}

function editConstraint(constraint) {
    document.getElementById('modalTitle').textContent = '<?php echo t('edit_constraint'); ?>';
    document.getElementById('formAction').value = 'update_constraint';
    document.getElementById('constraintId').value = constraint.id;
    document.getElementById('submitButtonText').textContent = '<?php echo t('update_constraint'); ?>';
    
    // Fill form with constraint data
    document.getElementById('name').value = constraint.name;
    document.getElementById('description').value = constraint.description;
    document.getElementById('rule_type').value = constraint.rule_type;
    document.getElementById('weight').value = constraint.weight;
    
    // Parse parameters and set constraint type
    const params = JSON.parse(constraint.parameters || '{}');
    document.getElementById('constraint_type').value = params.constraint_type || '';
    
    document.getElementById('constraintModal').classList.remove('hidden');
    updateConstraintForm();
    
    // Fill parameter fields after form is updated
    setTimeout(() => {
        fillParameterFields(params);
    }, 100);
}

function hideConstraintModal() {
    document.getElementById('constraintModal').classList.add('hidden');
}

function updateConstraintForm() {
    const constraintType = document.getElementById('constraint_type').value;
    const parametersSection = document.getElementById('parametersSection');
    const parametersContainer = document.getElementById('parametersContainer');
    
    if (!constraintType) {
        parametersSection.classList.add('hidden');
        return;
    }
    
    parametersSection.classList.remove('hidden');
    parametersContainer.innerHTML = '';
    
    // Update weight suggestions based on rule type
    const ruleType = document.getElementById('rule_type').value;
    const weightField = document.getElementById('weight');
    if (!weightField.value) {
        switch(ruleType) {
            case 'forbidden': weightField.value = -1000; break;
            case 'not_preferred': weightField.value = -40; break;
            case 'less_preferred': weightField.value = -25; break;
            case 'most_preferred': weightField.value = 20; break;
        }
    }
    
    // Generate parameter fields based on constraint type
    switch(constraintType) {
        case 'team_unavailable':
            parametersContainer.innerHTML = `
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700"><?php echo t('team'); ?></label>
                        <select name="param_team_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                            ${teams.map(team => `<option value="${team.id}">${team.team_name}</option>`).join('')}
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700"><?php echo t('date'); ?></label>
                        <input type="date" name="param_date" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700"><?php echo t('reason'); ?></label>
                    <input type="text" name="param_reason" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                </div>
            `;
            break;
            
        case 'avoid_consecutive_matches':
            parametersContainer.innerHTML = `
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700"><?php echo t('max_consecutive'); ?></label>
                        <input type="number" name="param_max_consecutive" value="1" min="0" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    </div>
                    <div>
                        <label class="flex items-center">
                            <input type="checkbox" name="param_applies_to_all_teams" value="1" checked class="rounded">
                            <span class="ml-2 text-sm text-gray-700"><?php echo t('applies_to_all_teams'); ?></span>
                        </label>
                    </div>
                </div>
            `;
            break;
            
        case 'preferred_duty':
            parametersContainer.innerHTML = `
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700"><?php echo t('team'); ?></label>
                        <select name="param_team_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                            ${teams.map(team => `<option value="${team.id}">${team.team_name}</option>`).join('')}
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700"><?php echo t('duty_type'); ?></label>
                        <select name="param_duty_type" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                            <option value="clock"><?php echo t('clock_duty'); ?></option>
                            <option value="score"><?php echo t('score_duty'); ?></option>
                            <option value="any"><?php echo t('any_duty'); ?></option>
                        </select>
                    </div>
                </div>
            `;
            break;
            
        case 'rest_between_matches':
            parametersContainer.innerHTML = `
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700"><?php echo t('min_rest_days'); ?></label>
                        <input type="number" name="param_min_rest_days" value="1" min="0" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    </div>
                    <div>
                        <label class="flex items-center">
                            <input type="checkbox" name="param_applies_to_all_teams" value="1" checked class="rounded">
                            <span class="ml-2 text-sm text-gray-700"><?php echo t('applies_to_all_teams'); ?></span>
                        </label>
                    </div>
                </div>
            `;
            break;
            
        case 'max_assignments_per_day':
            parametersContainer.innerHTML = `
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700"><?php echo t('max_assignments'); ?></label>
                        <input type="number" name="param_max_assignments" value="2" min="1" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    </div>
                    <div>
                        <label class="flex items-center">
                            <input type="checkbox" name="param_applies_to_all_teams" value="1" checked class="rounded">
                            <span class="ml-2 text-sm text-gray-700"><?php echo t('applies_to_all_teams'); ?></span>
                        </label>
                    </div>
                </div>
            `;
            break;
            
        case 'time_preference':
            parametersContainer.innerHTML = `
                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700"><?php echo t('team'); ?></label>
                        <select name="param_team_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                            ${teams.map(team => `<option value="${team.id}">${team.team_name}</option>`).join('')}
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700"><?php echo t('preferred_start_time'); ?></label>
                        <input type="time" name="param_preferred_start_time" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700"><?php echo t('preferred_end_time'); ?></label>
                        <input type="time" name="param_preferred_end_time" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    </div>
                </div>
            `;
            break;
    }
}

function fillParameterFields(params) {
    Object.keys(params).forEach(key => {
        if (key === 'constraint_type') return;
        
        const fieldName = 'param_' + key;
        const field = document.querySelector(`[name="${fieldName}"]`);
        if (field) {
            if (field.type === 'checkbox') {
                field.checked = params[key];
            } else {
                field.value = params[key];
            }
        }
    });
}

// Auto-update weight based on rule type
document.getElementById('rule_type').addEventListener('change', function() {
    const weightField = document.getElementById('weight');
    if (weightField.value === '' || confirm('<?php echo t('update_weight_suggestion'); ?>')) {
        switch(this.value) {
            case 'forbidden': weightField.value = -1000; break;
            case 'not_preferred': weightField.value = -40; break;
            case 'less_preferred': weightField.value = -25; break;
            case 'most_preferred': weightField.value = 20; break;
        }
    }
});
</script>

<!-- Optimization Interface Section -->
<div class="mt-8 bg-gradient-to-r from-purple-50 to-blue-50 rounded-lg shadow-lg">
    <div class="p-6">
        <div class="flex items-center space-x-2 mb-4">
            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
            </svg>
            <h2 class="text-xl font-bold text-gray-800"><?php echo t('python_optimization_engine'); ?></h2>
        </div>
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Python Availability Status -->
            <?php if (!$pythonAvailability['available']): ?>
            <div class="lg:col-span-3 mb-6">
                <div class="p-4 bg-amber-50 border border-amber-200 rounded-lg">
                    <div class="flex items-center space-x-2">
                        <svg class="w-5 h-5 text-amber-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                        </svg>
                        <h3 class="font-medium text-amber-800"><?php echo t('python_optimization_unavailable'); ?></h3>
                    </div>
                    <div class="mt-2 text-sm text-amber-700">
                        <p><strong><?php echo t('reason'); ?>:</strong> <?php echo htmlspecialchars($pythonAvailability['reason']); ?></p>
                        <p><strong><?php echo t('suggestion'); ?>:</strong> <?php echo htmlspecialchars($pythonAvailability['suggestion']); ?></p>
                    </div>
                    <div class="mt-3 p-3 bg-amber-100 rounded border-l-4 border-amber-400">
                        <p class="text-sm text-amber-800">
                            <strong><?php echo t('alternative_solution'); ?>:</strong> 
                            <?php echo t('use_constraint_editor_manually'); ?> 
                            <a href="matches.php" class="underline"><?php echo t('matches_page'); ?></a> 
                            <?php echo t('for_manual_assignment'); ?>.
                        </p>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Optimization Controls -->
            <div class="lg:col-span-2 <?php echo !$pythonAvailability['available'] ? 'opacity-50 pointer-events-none' : ''; ?>">
                <form method="POST" class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700"><?php echo t('solver_type'); ?></label>
                            <select name="solver_type" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                <option value="auto"><?php echo t('auto_select'); ?></option>
                                <option value="linear"><?php echo t('linear_solver'); ?></option>
                                <option value="sat"><?php echo t('constraint_sat'); ?></option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700"><?php echo t('timeout_seconds'); ?></label>
                            <input type="number" name="timeout" value="300" min="30" max="1800" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                        </div>
                    </div>
                    
                    <div class="flex space-x-4">
                        <button type="submit" name="action" value="validate_constraints" 
                                class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors">
                            <?php echo t('validate_constraints'); ?>
                        </button>
                        <button type="submit" name="action" value="preview_optimization" 
                                class="px-4 py-2 bg-purple-600 text-white rounded hover:bg-purple-700 transition-colors">
                            <?php echo t('preview_optimization'); ?>
                        </button>
                        <button type="submit" name="action" value="run_optimization" 
                                class="px-6 py-2 bg-green-600 text-white rounded hover:bg-green-700 transition-colors font-semibold"
                                onclick="return confirm('<?php echo t('run_optimization_confirm'); ?>')">
                            <?php echo t('run_optimization'); ?>
                        </button>
                    </div>
                </form>
                
                <!-- Validation Results -->
                <?php if (isset($validationResult)): ?>
                <div class="mt-4 p-4 rounded-lg <?php echo $validationResult['valid'] ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200'; ?>">
                    <div class="flex items-center space-x-2">
                        <?php if ($validationResult['valid']): ?>
                            <svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="text-green-800 font-medium"><?php echo t('validation_passed'); ?></span>
                        <?php else: ?>
                            <svg class="w-5 h-5 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="text-red-800 font-medium"><?php echo t('validation_failed'); ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mt-2 text-sm">
                        <p><?php echo t('active_constraints'); ?>: <?php echo $validationResult['active_constraints']; ?>/<?php echo $validationResult['total_constraints']; ?></p>
                        
                        <?php if (!empty($validationResult['errors'])): ?>
                            <div class="mt-2">
                                <strong><?php echo t('errors'); ?>:</strong>
                                <ul class="list-disc list-inside mt-1 text-red-700">
                                    <?php foreach ($validationResult['errors'] as $error): ?>
                                        <li><?php echo htmlspecialchars($error['constraint']); ?>: <?php echo implode(', ', $error['errors']); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($validationResult['warnings'])): ?>
                            <div class="mt-2">
                                <strong><?php echo t('warnings'); ?>:</strong>
                                <ul class="list-disc list-inside mt-1 text-yellow-700">
                                    <?php foreach ($validationResult['warnings'] as $warning): ?>
                                        <li><?php echo htmlspecialchars($warning['constraint']); ?>: <?php echo implode(', ', $warning['conflicts']); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Preview Results -->
                <?php if (isset($previewResult)): ?>
                <div class="mt-4 p-4 bg-gray-50 border border-gray-200 rounded-lg">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="font-medium text-gray-800"><?php echo t('optimization_preview'); ?></h3>
                        <?php if (isset($previewResult['fallback_used']) && $previewResult['fallback_used']): ?>
                            <span class="text-xs bg-orange-100 text-orange-800 px-2 py-1 rounded">
                                <?php echo t('using_php_optimizer'); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($previewResult['success']): ?>
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <span class="text-gray-600"><?php echo t('optimization_score'); ?>:</span>
                                <span class="font-medium"><?php echo number_format($previewResult['optimization_score'] ?? 0, 2); ?></span>
                            </div>
                            <div>
                                <span class="text-gray-600"><?php echo t('assignments'); ?>:</span>
                                <span class="font-medium"><?php echo count($previewResult['assignments'] ?? []); ?></span>
                            </div>
                            <div>
                                <span class="text-gray-600"><?php echo t('constraints_satisfied'); ?>:</span>
                                <span class="font-medium"><?php echo ($previewResult['constraints_satisfied'] ?? 0); ?>/<?php echo ($previewResult['total_constraints'] ?? 0); ?></span>
                            </div>
                            <div>
                                <span class="text-gray-600"><?php echo t('execution_time'); ?>:</span>
                                <span class="font-medium"><?php echo number_format($previewResult['solver_time'] ?? $previewResult['execution_time'] ?? 0, 2); ?>s</span>
                            </div>
                        </div>
                        
                        <?php if (isset($previewResult['fallback_used']) && $previewResult['fallback_used']): ?>
                        <div class="mt-3 p-2 bg-blue-50 border border-blue-200 rounded text-xs text-blue-700">
                            <strong><?php echo t('note'); ?>:</strong> <?php echo t('preview_using_php_fallback'); ?>
                        </div>
                        <?php endif; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <p class="text-red-600"><?php echo t('preview_failed'); ?>: <?php echo htmlspecialchars($previewResult['error'] ?? t('unknown_error')); ?></p>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Statistics & Recommendations -->
            <div class="space-y-4">
                <!-- Optimization Stats -->
                <div class="bg-white p-4 rounded-lg border border-gray-200">
                    <h3 class="font-medium text-gray-800 mb-3"><?php echo t('optimization_stats'); ?></h3>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600"><?php echo t('total_runs'); ?>:</span>
                            <span class="font-medium"><?php echo $optimizationStats['total_runs'] ?? 0; ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600"><?php echo t('avg_score'); ?>:</span>
                            <span class="font-medium"><?php echo number_format($optimizationStats['avg_score'] ?? 0, 1); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600"><?php echo t('satisfaction_rate'); ?>:</span>
                            <span class="font-medium"><?php echo number_format($optimizationStats['avg_satisfaction_rate'] ?? 0, 1); ?>%</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600"><?php echo t('avg_time'); ?>:</span>
                            <span class="font-medium"><?php echo number_format($optimizationStats['avg_solver_time'] ?? 0, 1); ?>s</span>
                        </div>
                    </div>
                </div>
                
                <!-- Recommendations -->
                <div class="bg-white p-4 rounded-lg border border-gray-200">
                    <h3 class="font-medium text-gray-800 mb-3"><?php echo t('recommendations'); ?></h3>
                    <?php if (!empty($recommendations['missing_constraints'])): ?>
                        <div class="space-y-2">
                            <h4 class="text-sm font-medium text-orange-600"><?php echo t('missing_constraints'); ?></h4>
                            <?php foreach ($recommendations['missing_constraints'] as $rec): ?>
                                <div class="text-xs p-2 bg-orange-50 rounded border-l-2 border-orange-300">
                                    <div class="font-medium text-orange-800"><?php echo ucfirst($rec['type']); ?></div>
                                    <div class="text-orange-700"><?php echo $rec['description']; ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($recommendations['load_balancing'])): ?>
                        <div class="space-y-2 mt-3">
                            <h4 class="text-sm font-medium text-blue-600"><?php echo t('load_balancing'); ?></h4>
                            <?php foreach ($recommendations['load_balancing'] as $rec): ?>
                                <div class="text-xs p-2 bg-blue-50 rounded border-l-2 border-blue-300">
                                    <div class="text-blue-700"><?php echo $rec['description']; ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Auto-update weight based on rule type
document.getElementById('rule_type').addEventListener('change', function() {
    const weightField = document.getElementById('weight');
    if (weightField.value === '' || confirm('<?php echo t('update_weight_suggestion'); ?>')) {
        switch(this.value) {
            case 'forbidden': weightField.value = -1000; break;
            case 'not_preferred': weightField.value = -40; break;
            case 'less_preferred': weightField.value = -25; break;
            case 'most_preferred': weightField.value = 20; break;
        }
    }
});

// Import dropdown toggle
function toggleImportMenu() {
    const menu = document.getElementById('import-menu');
    menu.classList.toggle('hidden');
}

// Close dropdown when clicking outside
document.addEventListener('click', function(event) {
    const menu = document.getElementById('import-menu');
    const button = document.getElementById('import-menu-button');
    
    if (!menu.contains(event.target) && !button.contains(event.target)) {
        menu.classList.add('hidden');
    }
});
</script>

<?php
$content = ob_get_clean();
include 'includes/layout.php';
?>
