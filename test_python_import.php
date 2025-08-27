<?php
// Test script for Python template import

require_once 'php_interface/config/database.php';
require_once 'php_interface/includes/ConstraintManager.php';

echo "🧪 Testing Python Template Import\n\n";

try {
    $constraintManager = new ConstraintManager($db);
    
    echo "1️⃣ Current constraint count...\n";
    $currentConstraints = $constraintManager->getAllConstraints();
    echo "   Current constraints: " . count($currentConstraints) . "\n\n";
    
    echo "2️⃣ Importing Python templates...\n";
    $result = $constraintManager->importPythonTemplateConstraints();
    echo "   Success: " . ($result['success'] ? '✅' : '❌') . "\n";
    echo "   Imported: " . $result['imported'] . "\n";
    echo "   Skipped: " . $result['skipped'] . "\n\n";
    
    echo "3️⃣ New constraint count...\n";
    $newConstraints = $constraintManager->getAllConstraints();
    echo "   Total constraints: " . count($newConstraints) . "\n\n";
    
    echo "4️⃣ Testing import all constraints...\n";
    $allResult = $constraintManager->importAllConstraints();
    echo "   Success: " . ($allResult['success'] ? '✅' : '❌') . "\n";
    echo "   PHP imported: " . $allResult['php_imported'] . "\n";
    echo "   PHP skipped: " . $allResult['php_skipped'] . "\n";
    echo "   Python imported: " . $allResult['python_imported'] . "\n";
    echo "   Python skipped: " . $allResult['python_skipped'] . "\n";
    echo "   Total imported: " . $allResult['total_imported'] . "\n";
    echo "   Total skipped: " . $allResult['total_skipped'] . "\n\n";
    
    echo "5️⃣ Final constraint count...\n";
    $finalConstraints = $constraintManager->getAllConstraints();
    echo "   Final total: " . count($finalConstraints) . "\n\n";
    
    echo "6️⃣ Constraint breakdown...\n";
    $activeCount = 0;
    $templateCount = 0;
    
    foreach ($finalConstraints as $constraint) {
        if ($constraint['is_active']) {
            $activeCount++;
        }
        if (strpos($constraint['name'], '(Template)') !== false) {
            $templateCount++;
        }
    }
    
    echo "   Active constraints: " . $activeCount . "\n";
    echo "   Template constraints: " . $templateCount . "\n";
    echo "   Inactive constraints: " . (count($finalConstraints) - $activeCount) . "\n\n";
    
    echo "✅ Python template import test completed!\n";
    echo "\n📖 Templates are now available in the constraint editor!\n";
    echo "   Templates start as inactive - activate and configure them as needed.\n";
    
} catch (Exception $e) {
    echo "❌ Test failed: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
