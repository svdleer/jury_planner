<?php
session_start();
require_once 'config/database.php';

$pageTitle = 'Assignment Constraints';
$pageDescription = 'Manage jury assignment constraints, exclusions, and team capacities';

ob_start();
?>

<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <h1 class="text-3xl font-bold text-gray-900 mb-6">Assignment Constraints</h1>
    <p class="text-gray-600 mb-8">This page is working! Full constraints management coming soon.</p>
    
    <div class="bg-white shadow rounded-lg p-6">
        <h2 class="text-xl font-semibold mb-4">Quick Stats</h2>
        <?php
        try {
            $sql = "SELECT COUNT(*) FROM jury_teams";
            $stmt = $db->prepare($sql);
            $stmt->execute();
            $teamCount = $stmt->fetchColumn();
            
            $sql = "SELECT COUNT(*) FROM excluded_teams";
            $stmt = $db->prepare($sql);
            $stmt->execute();
            $exclusionCount = $stmt->fetchColumn();
            
            echo "<p>Teams: $teamCount</p>";
            echo "<p>Exclusions: $exclusionCount</p>";
            
        } catch (Exception $e) {
            echo "<p class='text-red-600'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
        ?>
    </div>
</div>

<?php
$content = ob_get_clean();
include 'includes/layout.php';
?>
