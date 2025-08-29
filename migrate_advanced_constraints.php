<?php
/**
 * Database Migration: Create Advanced Constraint System Tables
 * This script creates the planning_rules table needed for the advanced constraint editor
 */

require_once 'config/database.php';
require_once 'includes/translations.php';

$pageTitle = t('database_migration');
$pageDescription = t('create_advanced_constraint_tables');

// Handle migration request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        $database = new Database();
        $pdo = $database->getConnection();
        
        if ($_POST['action'] === 'migrate') {
            $success = migrateAdvancedConstraints($pdo);
            if ($success) {
                $message = t('migration_successful');
                $messageType = 'success';
            } else {
                $message = t('migration_failed');
                $messageType = 'error';
            }
        }
    } catch (Exception $e) {
        $message = t('migration_error') . ': ' . $e->getMessage();
        $messageType = 'error';
    }
}

function migrateAdvancedConstraints($pdo) {
    try {
        // Create planning_rules table
        $sql = "
        CREATE TABLE IF NOT EXISTS planning_rules (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            rule_type ENUM(
                'forbidden',
                'not_preferred', 
                'less_preferred',
                'preferred',
                'most_preferred'
            ) DEFAULT 'not_preferred',
            weight DECIMAL(10,2) DEFAULT 1.0,
            constraint_type ENUM(
                'team_unavailable',
                'avoid_consecutive_matches',
                'preferred_duty',
                'rest_between_matches',
                'max_assignments_per_day',
                'time_preference',
                'workload_distribution',
                'location_preference',
                'team_restrictions',
                'custom'
            ) DEFAULT 'custom',
            parameters JSON,
            is_active BOOLEAN DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_rule_type (rule_type),
            INDEX idx_constraint_type (constraint_type),
            INDEX idx_is_active (is_active)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        
        $pdo->exec($sql);
        
        // Check if table was created successfully
        $stmt = $pdo->prepare("SHOW TABLES LIKE 'planning_rules'");
        $stmt->execute();
        $tableExists = $stmt->fetch() !== false;
        
        if ($tableExists) {
            // Add some default constraint templates based on the Python model
            addDefaultConstraintTemplates($pdo);
            return true;
        }
        
        return false;
    } catch (PDOException $e) {
        error_log("Migration error: " . $e->getMessage());
        return false;
    }
}

function addDefaultConstraintTemplates($pdo) {
    $templates = [
        [
            'name' => 'Teams Not Jury Own Matches',
            'description' => 'Teams cannot be assigned as jury for their own matches',
            'rule_type' => 'forbidden',
            'constraint_type' => 'team_restrictions',
            'weight' => 1000.0,
            'parameters' => json_encode(['type' => 'own_match_restriction'])
        ],
        [
            'name' => 'Away Team Same Day',
            'description' => 'Teams cannot jury when they have an away match on the same day',
            'rule_type' => 'forbidden',
            'constraint_type' => 'team_restrictions',
            'weight' => 1000.0,
            'parameters' => json_encode(['type' => 'away_match_same_day'])
        ],
        [
            'name' => 'Maximum Assignments Per Day',
            'description' => 'Limit the number of assignments a team can have per day',
            'rule_type' => 'forbidden',
            'constraint_type' => 'max_assignments_per_day',
            'weight' => 1000.0,
            'parameters' => json_encode(['max_assignments' => 3])
        ],
        [
            'name' => 'No Consecutive Weekend Assignments',
            'description' => 'Avoid assigning the same team jury duty on consecutive weekends',
            'rule_type' => 'not_preferred',
            'constraint_type' => 'avoid_consecutive_matches',
            'weight' => 100.0,
            'parameters' => json_encode(['type' => 'consecutive_weekends'])
        ],
        [
            'name' => 'Prefer Home Playing Teams',
            'description' => 'Prefer teams that are already playing at home for jury duty',
            'rule_type' => 'preferred',
            'constraint_type' => 'location_preference',
            'weight' => 50.0,
            'parameters' => json_encode(['type' => 'home_team_preference'])
        ],
        [
            'name' => 'Workload Distribution',
            'description' => 'Balance assignments across teams based on recent activity',
            'rule_type' => 'preferred',
            'constraint_type' => 'workload_distribution',
            'weight' => 75.0,
            'parameters' => json_encode(['lookback_days' => 14, 'balance_factor' => 1.0])
        ],
        [
            'name' => 'Rest Between Matches',
            'description' => 'Ensure teams have adequate rest between jury assignments',
            'rule_type' => 'not_preferred',
            'constraint_type' => 'rest_between_matches',
            'weight' => 80.0,
            'parameters' => json_encode(['min_rest_days' => 1])
        ]
    ];
    
    $stmt = $pdo->prepare("
        INSERT INTO planning_rules (name, description, rule_type, constraint_type, weight, parameters, is_active)
        VALUES (?, ?, ?, ?, ?, ?, 1)
    ");
    
    foreach ($templates as $template) {
        // Check if template already exists
        $checkStmt = $pdo->prepare("SELECT id FROM planning_rules WHERE name = ?");
        $checkStmt->execute([$template['name']]);
        
        if (!$checkStmt->fetch()) {
            $stmt->execute([
                $template['name'],
                $template['description'],
                $template['rule_type'],
                $template['constraint_type'],
                $template['weight'],
                $template['parameters']
            ]);
        }
    }
}

ob_start();
?>

<div class="max-w-4xl mx-auto">
    
    <?php if (isset($message)): ?>
        <div class="mb-6 p-4 rounded-md <?php echo $messageType === 'success' ? 'bg-green-50 border border-green-200 text-green-800' : 'bg-red-50 border border-red-200 text-red-800'; ?>">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas <?php echo $messageType === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?> text-lg"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium"><?php echo htmlspecialchars($message); ?></p>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Migration Status -->
    <div class="bg-white shadow rounded-lg p-6 mb-6">
        <h2 class="text-xl font-bold mb-4">
            <i class="fas fa-database mr-2"></i>
            <?php echo t('advanced_constraint_system_migration'); ?>
        </h2>
        
        <p class="text-gray-600 mb-6">
            <?php echo t('migration_description'); ?>
        </p>
        
        <!-- Check current status -->
        <?php
        try {
            $database = new Database();
            $pdo = $database->getConnection();
            
            // Check if planning_rules table exists
            $stmt = $pdo->prepare("SHOW TABLES LIKE 'planning_rules'");
            $stmt->execute();
            $tableExists = $stmt->fetch() !== false;
            
            if ($tableExists) {
                // Count existing rules
                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM planning_rules");
                $stmt->execute();
                $ruleCount = $stmt->fetch()['count'];
                
                echo '<div class="bg-green-50 border border-green-200 rounded-md p-4 mb-4">';
                echo '<div class="flex">';
                echo '<div class="flex-shrink-0"><i class="fas fa-check-circle text-green-400"></i></div>';
                echo '<div class="ml-3">';
                echo '<h3 class="text-sm font-medium text-green-800">' . t('migration_complete') . '</h3>';
                echo '<div class="mt-2 text-sm text-green-700">';
                echo '<p>' . sprintf(t('rules_found'), $ruleCount) . '</p>';
                echo '<a href="advanced_constraint_editor.php" class="mt-2 inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-green-600 hover:bg-green-700">';
                echo '<i class="fas fa-external-link-alt mr-2"></i>' . t('open_constraint_editor');
                echo '</a>';
                echo '</div>';
                echo '</div>';
                echo '</div>';
                echo '</div>';
            } else {
                echo '<div class="bg-yellow-50 border border-yellow-200 rounded-md p-4 mb-4">';
                echo '<div class="flex">';
                echo '<div class="flex-shrink-0"><i class="fas fa-exclamation-triangle text-yellow-400"></i></div>';
                echo '<div class="ml-3">';
                echo '<h3 class="text-sm font-medium text-yellow-800">' . t('migration_required') . '</h3>';
                echo '<div class="mt-2 text-sm text-yellow-700">';
                echo '<p>' . t('migration_required_description') . '</p>';
                echo '</div>';
                echo '</div>';
                echo '</div>';
                echo '</div>';
                
                // Show migration button
                echo '<form method="POST" class="mt-4">';
                echo '<input type="hidden" name="action" value="migrate">';
                echo '<button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">';
                echo '<i class="fas fa-database mr-2"></i>' . t('run_migration');
                echo '</button>';
                echo '</form>';
            }
        } catch (Exception $e) {
            echo '<div class="bg-red-50 border border-red-200 rounded-md p-4">';
            echo '<div class="flex">';
            echo '<div class="flex-shrink-0"><i class="fas fa-times-circle text-red-400"></i></div>';
            echo '<div class="ml-3">';
            echo '<h3 class="text-sm font-medium text-red-800">' . t('database_error') . '</h3>';
            echo '<div class="mt-2 text-sm text-red-700">';
            echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
        }
        ?>
    </div>
    
    <!-- Migration Details -->
    <div class="bg-white shadow rounded-lg p-6">
        <h2 class="text-xl font-bold mb-4">
            <i class="fas fa-info-circle mr-2"></i>
            <?php echo t('what_will_be_created'); ?>
        </h2>
        
        <div class="space-y-4">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <i class="fas fa-table text-blue-500 mt-1"></i>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-gray-900"><?php echo t('planning_rules_table'); ?></h3>
                    <p class="text-sm text-gray-500"><?php echo t('planning_rules_description'); ?></p>
                </div>
            </div>
            
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <i class="fas fa-cogs text-green-500 mt-1"></i>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-gray-900"><?php echo t('default_constraint_templates'); ?></h3>
                    <p class="text-sm text-gray-500"><?php echo t('default_templates_description'); ?></p>
                </div>
            </div>
            
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <i class="fas fa-code text-purple-500 mt-1"></i>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-gray-900"><?php echo t('python_integration'); ?></h3>
                    <p class="text-sm text-gray-500"><?php echo t('python_integration_description'); ?></p>
                </div>
            </div>
        </div>
        
        <div class="mt-6 pt-6 border-t border-gray-200">
            <div class="flex items-center justify-between">
                <div>
                    <a href="index.php" class="text-blue-600 hover:text-blue-800">
                        <i class="fas fa-arrow-left mr-1"></i> <?php echo t('back_to_dashboard'); ?>
                    </a>
                </div>
                <div>
                    <a href="constraints.php" class="text-blue-600 hover:text-blue-800 mr-4">
                        <?php echo t('basic_constraints'); ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include 'includes/layout.php';
?>
