<?php
require_once 'includes/translations.php';
require_once 'config/database.php';
require_once 'includes/MncTeamManager.php';
require_once 'includes/MncMatchManager.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    $teamManager = new MncTeamManager($pdo);
    $matchManager = new MncMatchManager($pdo);
    
    $teamStats = $teamManager->getTeamStats();
    $matchStats = $matchManager->getMatchStats();
    $upcomingMatches = $matchManager->getUpcomingMatches(14);
    $matchesWithoutJury = $matchManager->getMatchesWithoutJury();
    
} catch (Exception $e) {
    $error = "Database connection failed: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="<?php echo Translations::getCurrentLanguage(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('mnc_jury_planner'); ?> - <?php echo t('dashboard'); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
</head>
<body class="bg-gray-50 min-h-screen">

    <!-- Navigation -->
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
                    <a href="test_connection.php" class="hover:bg-blue-700 px-3 py-2 rounded">
                        <i class="fas fa-database mr-1"></i> <?php echo t('db_test'); ?>
                    </a>
                    
                    <!-- Language Toggle -->
                    <?php include 'includes/language_toggle.php'; ?>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <?php if (isset($error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php else: ?>
            
        <!-- Header -->
        <div class="bg-white shadow rounded-lg p-6 mb-6">
            <h2 class="text-2xl font-bold text-gray-900 mb-2">
                <i class="fas fa-tachometer-alt text-blue-600 mr-2"></i>
                <?php echo t('jury_management_dashboard'); ?>
            </h2>
            <p class="text-gray-600">
                <?php echo t('welcome_message'); ?>
            </p>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-users text-2xl text-blue-600"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate"><?php echo t('jury_teams'); ?></dt>
                                <dd class="text-lg font-medium text-gray-900"><?php echo $teamStats['jury_teams']; ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-shield-alt text-2xl text-green-600"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate"><?php echo t('mnc_teams'); ?></dt>
                                <dd class="text-lg font-medium text-gray-900"><?php echo $teamStats['mnc_teams']; ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-calendar text-2xl text-purple-600"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate"><?php echo t('home_matches'); ?></dt>
                                <dd class="text-lg font-medium text-gray-900"><?php echo $matchStats['total_home_matches']; ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-gavel text-2xl text-orange-600"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate"><?php echo t('assigned'); ?></dt>
                                <dd class="text-lg font-medium text-gray-900"><?php echo $matchStats['assigned_matches']; ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Two Column Layout -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            
            <!-- Upcoming Matches -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                        <i class="fas fa-calendar-day text-blue-600 mr-2"></i>
                        <?php echo t('upcoming_matches'); ?>
                    </h3>
                    
                    <?php if (empty($upcomingMatches)): ?>
                        <p class="text-gray-500 italic"><?php echo t('no_upcoming_matches'); ?></p>
                    <?php else: ?>
                        <div class="space-y-3">
                            <?php foreach (array_slice($upcomingMatches, 0, 5) as $match): ?>
                                <div class="flex items-center justify-between p-3 bg-gray-50 rounded">
                                    <div>
                                        <div class="font-medium text-gray-900">
                                            <?php echo htmlspecialchars($match['home_team']); ?> - 
                                            <?php echo htmlspecialchars($match['away_team']); ?>
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            <?php echo date('M j, Y H:i', strtotime($match['date_time'])); ?> - 
                                            <?php echo htmlspecialchars($match['competition']); ?>
                                        </div>
                                    </div>
                                    <div class="text-sm text-blue-600 font-medium">
                                        <?php echo htmlspecialchars($match['location']); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <?php if (count($upcomingMatches) > 5): ?>
                            <div class="mt-4 text-center">
                                <a href="matches.php" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                    <?php echo t('view_all_upcoming', [count($upcomingMatches)]); ?>
                                </a>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Matches Without Jury -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                        <i class="fas fa-exclamation-triangle text-yellow-600 mr-2"></i>
                        <?php echo t('matches_without_jury'); ?>
                    </h3>
                    
                    <?php if (empty($matchesWithoutJury)): ?>
                        <div class="text-green-600">
                            <i class="fas fa-check-circle mr-2"></i>
                            <?php echo t('all_matches_assigned'); ?>
                        </div>
                    <?php else: ?>
                        <div class="space-y-3">
                            <?php foreach (array_slice($matchesWithoutJury, 0, 5) as $match): ?>
                                <div class="flex items-center justify-between p-3 bg-yellow-50 border border-yellow-200 rounded">
                                    <div>
                                        <div class="font-medium text-gray-900">
                                            <?php echo htmlspecialchars($match['home_team']); ?> - 
                                            <?php echo htmlspecialchars($match['away_team']); ?>
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            <?php echo date('M j, Y H:i', strtotime($match['date_time'])); ?>
                                        </div>
                                    </div>
                                    <div class="text-sm text-yellow-700 font-medium">
                                        <?php echo t('needs_jury'); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <?php if (count($matchesWithoutJury) > 5): ?>
                            <div class="mt-4 text-center">
                                <a href="matches.php?filter=no_jury" class="text-yellow-600 hover:text-yellow-800 text-sm font-medium">
                                    <?php echo t('view_all_unassigned', [count($matchesWithoutJury)]); ?>
                                </a>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="mt-6 bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                    <i class="fas fa-rocket text-purple-600 mr-2"></i>
                    <?php echo t('quick_actions'); ?>
                </h3>
                
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <!-- Add Team button disabled for production -->
                    <!-- <a href="teams.php?action=add" class="flex items-center justify-center px-4 py-3 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                        <i class="fas fa-plus mr-2"></i>
                        <?php echo t('add_team'); ?>
                    </a> -->
                    
                    <!-- Add Match button disabled for production -->
                    <!-- <a href="matches.php?action=add" class="flex items-center justify-center px-4 py-3 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700">
                        <i class="fas fa-calendar-plus mr-2"></i>
                        Add Match
                    </a> -->
                    
                    <a href="matches.php?view=planning" class="flex items-center justify-center px-4 py-3 border border-transparent text-sm font-medium rounded-md text-white bg-purple-600 hover:bg-purple-700">
                        <i class="fas fa-brain mr-2"></i>
                        <?php echo t('auto_plan'); ?>
                    </a>
                    
                    <a href="test_connection.php" class="flex items-center justify-center px-4 py-3 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        <i class="fas fa-database mr-2"></i>
                        <?php echo t('test_db'); ?>
                    </a>
                    
                    <a href="constraints.php" class="flex items-center justify-center px-4 py-3 border border-transparent text-sm font-medium rounded-md text-white bg-orange-600 hover:bg-orange-700">
                        <i class="fas fa-ban mr-2"></i>
                        <?php echo t('constraints'); ?>
                    </a>
                    
                    <a href="advanced_constraints.php" class="flex items-center justify-center px-4 py-3 border border-transparent text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700">
                        <i class="fas fa-cogs mr-2"></i>
                        <?php echo t('advanced_rules'); ?>
                    </a>
                    
                    <a href="smart_assignment.php" class="flex items-center justify-center px-4 py-3 border border-transparent text-sm font-medium rounded-md text-white bg-yellow-600 hover:bg-yellow-700">
                        <i class="fas fa-robot mr-2"></i>
                        <?php echo t('smart_assign'); ?>
                    </a>
                    
                    <a href="fairness.php" class="flex items-center justify-center px-4 py-3 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                        <i class="fas fa-balance-scale mr-2"></i>
                        <?php echo t('fairness'); ?>
                    </a>
                    
                    <a href="constraint_analysis.php" class="flex items-center justify-center px-4 py-3 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        <i class="fas fa-search mr-2"></i>
                        <?php echo t('analysis'); ?>
                    </a>
                </div>
            </div>
        </div>

        <!-- System Information -->
        <div class="mt-6 bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                    <i class="fas fa-info-circle text-blue-600 mr-2"></i>
                    <?php echo t('system_information'); ?>
                </h3>
                
                <div class="grid grid-cols-2 md:grid-cols-3 gap-4 text-sm">
                    <div>
                        <span class="font-medium text-gray-700"><?php echo t('database'); ?>:</span>
                        <span class="text-gray-600"><?php echo $_ENV['DB_NAME'] ?? 'mnc_jury'; ?></span>
                    </div>
                    <div>
                        <span class="font-medium text-gray-700"><?php echo t('host'); ?>:</span>
                        <span class="text-gray-600"><?php echo $_ENV['DB_HOST'] ?? 'localhost'; ?></span>
                    </div>
                    <div>
                        <span class="font-medium text-gray-700"><?php echo t('total_all_matches'); ?>:</span>
                        <span class="text-gray-600"><?php echo $matchStats['total_all_matches']; ?></span>
                    </div>
                    <div>
                        <span class="font-medium text-gray-700"><?php echo t('competitions'); ?>:</span>
                        <span class="text-gray-600"><?php echo $matchStats['competitions']; ?></span>
                    </div>
                    <div>
                        <span class="font-medium text-gray-700"><?php echo t('classes'); ?>:</span>
                        <span class="text-gray-600"><?php echo $matchStats['classes']; ?></span>
                    </div>
                    <div>
                        <span class="font-medium text-gray-700"><?php echo t('excluded_teams'); ?>:</span>
                        <span class="text-gray-600"><?php echo $teamStats['excluded_teams']; ?></span>
                    </div>
                </div>
            </div>
        </div>

        <?php endif; ?>
    </div>
</body>
</html>
