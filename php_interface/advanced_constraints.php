<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/AdvancedConstraintManager.php';
require_once 'includes/translations.php';

// Handle language parameter
if (isset($_GET['lang'])) {
    Translations::setLanguage($_GET['lang']);
}

// Get current language for template
$currentLang = Translations::getCurrentLanguage();

// Create compatibility $lang array for any legacy code
$lang = [];
if (class_exists('Translations')) {
    $langKeys = ['filter', 'matches', 'status', 'all_statuses', 'scheduled', 'in_progress', 'completed', 'cancelled', 
                 'home_team', 'away_team', 'all_teams', 'date_range', 'all_dates', 'upcoming', 'today', 'this_week', 
                 'this_month', 'jury_status', 'assigned'];
    foreach ($langKeys as $key) {
        $lang[$key] = t($key);
    }
}

$constraintManager = new AdvancedConstraintManager($pdo);

// Clean up any "Non-Weekend" constraints that don't make sense for water polo
$constraintManager->removeConstraintsByPattern('Non-Weekend');

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_constraint':
                $result = $constraintManager->updateConstraint($_POST['constraint_id'], [
                    'constraint_name' => $_POST['constraint_name'],
                    'description' => $_POST['description'],
                    'constraint_type' => $_POST['constraint_type'],
                    'enabled' => isset($_POST['enabled']),
                    'weight' => floatval($_POST['weight']),
                    'penalty_points' => intval($_POST['penalty_points'])
                ]);
                
                if ($result) {
                    $_SESSION['success'] = t('constraint_updated_successfully');
                } else {
                    $_SESSION['error'] = t('failed_to_update_constraint');
                }
                header('Location: advanced_constraints.php');
                exit;
                break;
                
            case 'bulk_enable':
                $constraintIds = $_POST['constraint_ids'] ?? [];
                foreach ($constraintIds as $id) {
                    $constraint = $constraintManager->getConstraintById($id);
                    if ($constraint) {
                        $constraint['enabled'] = true;
                        $constraintManager->updateConstraint($id, $constraint);
                    }
                }
                $_SESSION['success'] = t('selected_constraints_enabled');
                header('Location: advanced_constraints.php');
                exit;
                break;
                
            case 'bulk_disable':
                $constraintIds = $_POST['constraint_ids'] ?? [];
                foreach ($constraintIds as $id) {
                    $constraint = $constraintManager->getConstraintById($id);
                    if ($constraint) {
                        $constraint['enabled'] = false;
                        $constraintManager->updateConstraint($id, $constraint);
                    }
                }
                $_SESSION['success'] = t('selected_constraints_disabled');
                header('Location: advanced_constraints.php');
                exit;
                break;
        }
    }
}

$constraintsByCategory = $constraintManager->getAllConstraintsByCategory();
$stats = $constraintManager->getConstraintStats();

// Set page variables after language is initialized
$pageTitle = t('advanced_constraint_configuration');
$pageDescription = t('configure_jury_assignment_rules');

// Debug: Let's test if translations are working
$debugLang = Translations::getCurrentLanguage();
$debugTitle = t('advanced_constraint_configuration');

ob_start();
?>

<!-- Debug: Current language is <?php echo $debugLang; ?>, Title: <?php echo $debugTitle; ?> -->
<div class="max-w-7xl mx-auto p-6" x-data="constraintManager()">
    <div class="min-h-screen">
        <!-- Action Bar -->
        <div class="mb-6 flex justify-between items-center">
            <a href="mnc_dashboard.php" class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                <i class="fas fa-arrow-left mr-2"></i><?php echo t('back_to_main'); ?>
            </a>
            <button @click="showBulkActions = !showBulkActions" class="inline-flex items-center rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500">
                <i class="fas fa-cogs mr-2"></i><?php echo t('bulk_actions'); ?>
            </button>
        </div>

        <!-- Statistics Overview -->
        <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-6">
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-list-check text-gray-400 text-xl"></i>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate"><?php echo t('total_constraints'); ?></dt>
                                    <dd class="text-lg font-medium text-gray-900"><?php echo $stats['total_constraints']; ?></dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-toggle-on text-green-400 text-xl"></i>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate"><?php echo t('enabled'); ?></dt>
                                    <dd class="text-lg font-medium text-green-600"><?php echo $stats['enabled_constraints']; ?></dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-exclamation-triangle text-red-400 text-xl"></i>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate"><?php echo t('hard_rules'); ?></dt>
                                    <dd class="text-lg font-medium text-red-600"><?php echo $stats['hard_constraints']; ?></dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-balance-scale text-yellow-400 text-xl"></i>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate"><?php echo t('soft_rules'); ?></dt>
                                    <dd class="text-lg font-medium text-yellow-600"><?php echo $stats['soft_constraints']; ?></dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-weight-hanging text-blue-400 text-xl"></i>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate"><?php echo t('avg_weight'); ?></dt>
                                    <dd class="text-lg font-medium text-blue-600"><?php echo number_format($stats['avg_weight'], 2); ?></dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-layer-group text-purple-400 text-xl"></i>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate"><?php echo t('categories'); ?></dt>
                                    <dd class="text-lg font-medium text-purple-600"><?php echo $stats['categories']; ?></dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bulk Actions Panel -->
        <div x-show="showBulkActions" x-transition class="mx-auto max-w-7xl px-4 mb-6 sm:px-6 lg:px-8">
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <i class="fas fa-info-circle text-yellow-400 mr-2"></i>
                        <span class="text-sm font-medium text-yellow-800"><?php echo t('bulk_actions'); ?></span>
                    </div>
                    <div class="flex space-x-2">
                        <button @click="bulkEnable()" class="inline-flex items-center px-3 py-1 border border-transparent text-xs font-medium rounded text-green-700 bg-green-100 hover:bg-green-200">
                            <i class="fas fa-check mr-1"></i><?php echo t('enable_selected'); ?>
                        </button>
                        <button @click="bulkDisable()" class="inline-flex items-center px-3 py-1 border border-transparent text-xs font-medium rounded text-red-700 bg-red-100 hover:bg-red-200">
                            <i class="fas fa-times mr-1"></i><?php echo t('disable_selected'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Constraints by Category -->
        <div class="mx-auto max-w-7xl px-4 pb-12 sm:px-6 lg:px-8">
            <?php foreach ($constraintsByCategory as $category => $constraints): ?>
            <div class="mb-8">
                <div class="bg-white shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">
                            <i class="fas fa-folder mr-2 text-blue-600"></i><?php echo htmlspecialchars($category); ?>
                        </h3>
                        
                        <div class="space-y-4">
                            <?php foreach ($constraints as $constraint): ?>
                            <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50" x-data="{ editMode: false }">
                                <div class="flex items-start justify-between">
                                    <div class="flex items-start space-x-3 flex-1">
                                        <!-- Checkbox for bulk selection -->
                                        <input type="checkbox" 
                                               x-model="selectedConstraints" 
                                               value="<?php echo $constraint['id']; ?>"
                                               class="mt-1 h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                        
                                        <div class="flex-1">
                                            <div class="flex items-center space-x-3">
                                                <h4 class="text-sm font-medium text-gray-900">
                                                    <?php echo htmlspecialchars($constraint['constraint_name']); ?>
                                                </h4>
                                                
                                                <!-- Constraint Type Badge -->
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $constraint['constraint_type'] === 'hard' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                                    <i class="fas <?php echo $constraint['constraint_type'] === 'hard' ? 'fa-exclamation-triangle' : 'fa-balance-scale'; ?> mr-1"></i>
                                                    <?php echo ucfirst($constraint['constraint_type']); ?>
                                                </span>
                                                
                                                <!-- Status Badge -->
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $constraint['enabled'] ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?>">
                                                    <i class="fas <?php echo $constraint['enabled'] ? 'fa-check' : 'fa-times'; ?> mr-1"></i>
                                                    <?php echo $constraint['enabled'] ? t('enabled') : t('disabled'); ?>
                                                </span>
                                                
                                                <!-- Weight -->
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                    <i class="fas fa-weight-hanging mr-1"></i>
                                                    <?php echo t('weight'); ?>: <?php echo number_format($constraint['weight'], 2); ?>
                                                </span>
                                                
                                                <!-- Penalty Points -->
                                                <?php if ($constraint['penalty_points'] > 0): ?>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                                                    <i class="fas fa-minus-circle mr-1"></i>
                                                    <?php echo t('penalty'); ?>: <?php echo $constraint['penalty_points']; ?>
                                                </span>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <p class="mt-2 text-sm text-gray-600">
                                                <?php echo htmlspecialchars($constraint['description']); ?>
                                            </p>
                                            
                                            <div class="mt-2 text-xs text-gray-500">
                                                <?php echo t('code'); ?>: <code class="bg-gray-100 px-1 rounded"><?php echo $constraint['constraint_code']; ?></code>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="flex items-center space-x-2">
                                        <button @click="editMode = !editMode" class="text-gray-400 hover:text-gray-600">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- Edit Form -->
                                <div x-show="editMode" x-transition class="mt-4 border-t pt-4">
                                    <form method="POST" class="space-y-4">
                                        <input type="hidden" name="action" value="update_constraint">
                                        <input type="hidden" name="constraint_id" value="<?php echo $constraint['id']; ?>">
                                        
                                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700"><?php echo t('constraint_name_label'); ?></label>
                                                <input type="text" name="constraint_name" value="<?php echo htmlspecialchars($constraint['constraint_name']); ?>" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                            </div>
                                            
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700"><?php echo t('type'); ?></label>
                                                <select name="constraint_type" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                                    <option value="hard" <?php echo $constraint['constraint_type'] === 'hard' ? 'selected' : ''; ?>><?php echo t('hard_must_not_violated'); ?></option>
                                                    <option value="soft" <?php echo $constraint['constraint_type'] === 'soft' ? 'selected' : ''; ?>><?php echo t('soft_may_be_violated'); ?></option>
                                                </select>
                                            </div>
                                            
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700"><?php echo t('weight'); ?></label>
                                                <input type="number" step="0.01" min="0" max="10" name="weight" value="<?php echo $constraint['weight']; ?>" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                            </div>
                                            
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700"><?php echo t('penalty_points'); ?></label>
                                                <input type="number" min="0" name="penalty_points" value="<?php echo $constraint['penalty_points']; ?>" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                            </div>
                                        </div>
                                        
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700"><?php echo t('description'); ?></label>
                                            <textarea name="description" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"><?php echo htmlspecialchars($constraint['description']); ?></textarea>
                                        </div>
                                        
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center">
                                                <input type="checkbox" name="enabled" id="enabled_<?php echo $constraint['id']; ?>" <?php echo $constraint['enabled'] ? 'checked' : ''; ?> class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                                <label for="enabled_<?php echo $constraint['id']; ?>" class="ml-2 text-sm text-gray-700"><?php echo t('enable_this_constraint'); ?></label>
                                            </div>
                                            
                                            <div class="flex space-x-2">
                                                <button type="button" @click="editMode = false" class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                                    <?php echo t('cancel'); ?>
                                                </button>
                                                <button type="submit" class="inline-flex items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 shadow-sm hover:bg-blue-700">
                                                    <i class="fas fa-save mr-2"></i><?php echo t('save_changes'); ?>
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
        function constraintManager() {
            return {
                showBulkActions: false,
                selectedConstraints: [],
                
                bulkEnable() {
                    if (this.selectedConstraints.length === 0) {
                        alert('<?php echo t('please_select_constraints_enable'); ?>');
                        return;
                    }
                    
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.innerHTML = `
                        <input type="hidden" name="action" value="bulk_enable">
                        ${this.selectedConstraints.map(id => `<input type="hidden" name="constraint_ids[]" value="${id}">`).join('')}
                    `;
                    document.body.appendChild(form);
                    form.submit();
                },
                
                bulkDisable() {
                    if (this.selectedConstraints.length === 0) {
                        alert('<?php echo t('please_select_constraints_disable'); ?>');
                        return;
                    }
                    
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.innerHTML = `
                        <input type="hidden" name="action" value="bulk_disable">
                        ${this.selectedConstraints.map(id => `<input type="hidden" name="constraint_ids[]" value="${id}">`).join('')}
                    `;
                    document.body.appendChild(form);
                    form.submit();
                }
            }
        }
    </script>

<?php
$content = ob_get_clean();
include 'includes/layout.php';
?>
