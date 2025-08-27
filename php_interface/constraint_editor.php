<?php
session_start();
require_once 'includes/translations.php';
require_once 'config/database.php';
require_once 'includes/ConstraintManager.php';

$constraintManager = new ConstraintManager($db);

$pageTitle = t('constraint_editor');
$pageDescription = t('constraint_editor_description');

// Handle form submissions
if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create_constraint') {
        $result = $constraintManager->createConstraint($_POST);
        $message = $result['success'] ? t('constraint_created_success') : $result['error'];
        $messageType = $result['success'] ? 'success' : 'error';
    } elseif ($action === 'update_constraint') {
        $result = $constraintManager->updateConstraint($_POST['constraint_id'], $_POST);
        $message = $result['success'] ? t('constraint_updated_success') : $result['error'];
        $messageType = $result['success'] ? 'success' : 'error';
    } elseif ($action === 'delete_constraint') {
        $result = $constraintManager->deleteConstraint($_POST['constraint_id']);
        $message = $result['success'] ? t('constraint_deleted_success') : $result['error'];
        $messageType = $result['success'] ? 'success' : 'error';
    } elseif ($action === 'toggle_constraint') {
        $result = $constraintManager->toggleConstraint($_POST['constraint_id']);
        $message = $result['success'] ? t('constraint_toggled_success') : $result['error'];
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
    }
}

// Get all constraints
$constraints = $constraintManager->getAllConstraints();
$teams = $constraintManager->getAllTeams();

ob_start();
?>

<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <!-- Header -->
    <div class="md:flex md:items-center md:justify-between mb-6">
        <div class="flex-1 min-w-0">
            <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                <?php echo t('constraint_editor'); ?>
            </h2>
            <p class="mt-1 text-sm text-gray-500">
                <?php echo t('constraint_editor_description'); ?>
            </p>
        </div>
        <div class="mt-4 flex md:mt-0 md:ml-4 space-x-3">
            <form method="POST" class="inline">
                <input type="hidden" name="action" value="import_constraints">
                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded"
                        onclick="return confirm('<?php echo t('confirm_import_constraints'); ?>')">
                    <?php echo t('import_existing_constraints'); ?>
                </button>
            </form>
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
                                <code class="text-xs bg-gray-100 px-2 py-1 rounded">
                                    <?php echo htmlspecialchars(json_encode(json_decode($constraint['parameters']), JSON_PRETTY_PRINT)); ?>
                                </code>
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

<?php
$content = ob_get_clean();
include 'layouts/layout.php';
?>
