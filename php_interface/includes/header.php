<?php
require_once 'includes/translations.php';
?>

<!-- Navigation Header Component -->
<nav class="bg-blue-600 text-white shadow-lg">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex items-center">
                <i class="fas fa-swimmer text-2xl mr-3"></i>
                <h1 class="text-xl font-bold"><?php echo t('mnc_jury_planner'); ?></h1>
            </div>
            <div class="flex items-center space-x-4">
                <a href="index.php" class="hover:bg-blue-700 px-3 py-2 rounded">
                    <i class="fas fa-home mr-1"></i> <?php echo t('dashboard'); ?>
                </a>
                <a href="teams.php" class="hover:bg-blue-700 px-3 py-2 rounded">
                    <i class="fas fa-users mr-1"></i> <?php echo t('teams'); ?>
                </a>
                <a href="matches.php" class="hover:bg-blue-700 px-3 py-2 rounded">
                    <i class="fas fa-calendar mr-1"></i> <?php echo t('matches'); ?>
                </a>
                <a href="constraints.php" class="hover:bg-blue-700 px-3 py-2 rounded">
                    <i class="fas fa-cog mr-1"></i> <?php echo t('constraints'); ?>
                </a>
                <a href="constraint_analysis.php" class="hover:bg-blue-700 px-3 py-2 rounded">
                    <i class="fas fa-search mr-1"></i> <?php echo t('analysis'); ?>
                </a>
                <a href="fairness.php" class="hover:bg-blue-700 px-3 py-2 rounded">
                    <i class="fas fa-balance-scale mr-1"></i> <?php echo t('fairness'); ?>
                </a>
                
                <!-- Language Toggle -->
                <?php include 'includes/language_toggle.php'; ?>
            </div>
        </div>
    </div>
</nav>
