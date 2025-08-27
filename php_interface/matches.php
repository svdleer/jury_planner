<?php
session_start();
require_once 'includes/translations.php';
require_once 'config/database.php';
require_once 'includes/MatchManager.php';
require_once 'includes/TeamManager.php';
require_once 'includes/AssignmentConstraintManager.php';
require_once 'includes/CustomConstraintManager.php';
require_once 'includes/MatchConstraintManager.php';
require_once 'includes/FairnessManager.php';
require_once 'includes/MatchLockManager.php';

$matchManager = new MatchManager($db);
$teamManager = new TeamManager($db);
$constraintManager = new AssignmentConstraintManager($db);
$lockManager = new MatchLockManager($db);

// Handle form submissions
if ($_POST) {
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'create':
                    // Match creation disabled for production
                    $_SESSION['error'] = t('adding_matches_disabled');
                    header('Location: matches.php');
                    exit;
                    
                case 'update':
                    $matchManager->updateMatch($_POST['id'], $_POST);
                    $_SESSION['success'] = t('match_updated_successfully');
                    header('Location: matches.php');
                    exit;
                    
                case 'delete':
                    // Match deletion disabled for production
                    $_SESSION['error'] = t('deleting_matches_disabled');
                    header('Location: matches.php');
                    exit;
                    
                case 'assign_jury':
                    $matchManager->assignJuryTeam($_POST['match_id'], $_POST['jury_team_id']);
                    $_SESSION['success'] = t('jury_team_assigned_successfully');
                    header('Location: matches.php');
                    exit;
                    
                case 'remove_jury':
                    $matchManager->removeJuryAssignment($_POST['assignment_id']);
                    $_SESSION['success'] = t('jury_assignment_removed');
                    header('Location: matches.php');
                    exit;
                    
                case 'lock_match':
                    $lockManager->lockMatch($_POST['match_id'], 'User');
                    $_SESSION['success'] = t('match_locked_successfully');
                    header('Location: matches.php');
                    exit;
                    
                case 'unlock_match':
                    $lockManager->unlockMatch($_POST['match_id']);
                    $_SESSION['success'] = t('match_unlocked_successfully');
                    header('Location: matches.php');
                    exit;
                    
                case 'reset_match':
                    $lockManager->resetMatchAssignments($_POST['match_id']);
                    $_SESSION['success'] = t('match_assignments_reset_successfully');
                    header('Location: matches.php');
                    exit;
                    
                case 'reset_all':
                    $forceIncludeLocked = isset($_POST['force_include_locked']);
                    $lockManager->resetAllAssignments($forceIncludeLocked);
                    $_SESSION['success'] = t('all_assignments_reset_successfully');
                    header('Location: matches.php');
                    exit;
                    
                case 'unassign_all':
                    $forceIncludeLocked = isset($_POST['force_include_locked']);
                    $lockManager->resetAllAssignments($forceIncludeLocked);
                    $_SESSION['success'] = t('all_jury_assignments_removed_successfully');
                    header('Location: matches.php');
                    exit;
                    
                case 'auto_assign':
                    $options = [
                        'prefer_low_usage' => isset($_POST['prefer_low_usage']),
                        'prefer_high_capacity' => isset($_POST['prefer_high_capacity'])
                    ];
                    $result = $constraintManager->autoAssignJuryTeams($options);
                    
                    if ($result['success']) {
                        $_SESSION['success'] = $result['message'];
                    } else {
                        $_SESSION['error'] = $result['message'];
                    }
                    header('Location: matches.php?view=planning');
                    exit;
            }
        }
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
}

// Get filter parameters
$view = $_GET['view'] ?? 'list';
$statusFilter = $_GET['status'] ?? 'all';
$homeTeamFilter = $_GET['home_team'] ?? 'all';
$awayTeamFilter = $_GET['away_team'] ?? 'all';
$dateFilter = $_GET['date'] ?? 'all';
$juryStatusFilter = $_GET['jury_status'] ?? 'all';
$lockStatusFilter = $_GET['lock_status'] ?? 'all';
$teamFilter = $_GET['team'] ?? 'all'; // Keep for backwards compatibility

// Get data
$teams = $teamManager->getAllTeams();
$matchTeams = $matchManager->getUniqueTeamNames();

if ($view === 'planning') {
    $stats = $constraintManager->getAssignmentStatistics();
    $matches = []; // Initialize as empty array for planning view
    $lockStats = $lockManager->getAssignmentStats();
    $pageTitle = t('auto_assignment_planning');
    $pageDescription = t('auto_assignment_description');
} else {
    $matches = $matchManager->getMatchesWithDetails($statusFilter, $teamFilter, $dateFilter, $homeTeamFilter, $awayTeamFilter, $juryStatusFilter, $lockStatusFilter);
    
    // Add lock information to each match and apply lock status filter
    $filteredMatches = [];
    foreach ($matches as $match) {
        $lockInfo = $lockManager->getMatchLockInfo($match['id']);
        $match['locked'] = $lockInfo['locked'] ?? false;
        $match['locked_at'] = $lockInfo['locked_at'] ?? null;
        $match['locked_by'] = $lockInfo['locked_by'] ?? null;
        
        // Apply lock status filter
        if ($lockStatusFilter !== 'all') {
            switch ($lockStatusFilter) {
                case 'locked':
                    if (!$match['locked']) continue 2; // Skip this match
                    break;
                case 'unlocked':
                    if ($match['locked']) continue 2; // Skip this match
                    break;
            }
        }
        
        $filteredMatches[] = $match;
    }
    $matches = $filteredMatches;
    
    $pageTitle = t('matches_management');
    $pageDescription = t('matches_management_description');
}

ob_start();
?>

<div x-data="matchesApp()" x-init="init()">
    <!-- Header with view navigation -->
    <div class="mb-6">
        <div class="sm:flex sm:items-center sm:justify-between mb-4">
            <div class="min-w-0 flex-1">
                <?php if ($view === 'planning'): ?>
                    <h2 class="text-xl font-semibold leading-7 text-gray-700">
                        <i class="fas fa-magic mr-2"></i><?php echo t('auto_assignment_planning'); ?>
                    </h2>
                <?php else: ?>
                    <h2 class="text-xl font-semibold leading-7 text-gray-700">
                        <i class="fas fa-list mr-2"></i><?php echo t('matches_overview'); ?>
                    </h2>
                <?php endif; ?>
            </div>
            <div class="mt-4 flex sm:ml-4 sm:mt-0 space-x-3">
                <!-- Add Match button disabled for production -->
            </div>
        </div>
        
        <!-- View Navigation -->
        <div class="border-b border-gray-200">
            <nav class="-mb-px flex space-x-8">
                <a href="matches.php" class="<?php echo $view === 'list' ? 'border-water-blue-500 text-water-blue-600' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'; ?> whitespace-nowrap border-b-2 py-2 px-1 text-sm font-medium">
                    <?php echo t('matches_list'); ?>
                </a>
                <a href="matches.php?view=planning" class="<?php echo $view === 'planning' ? 'border-water-blue-500 text-water-blue-600' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'; ?> whitespace-nowrap border-b-2 py-2 px-1 text-sm font-medium">
                    <?php echo t('auto_planning'); ?>
                </a>
            </nav>
        </div>
    </div>

    <?php if ($view === 'planning'): ?>
        <!-- Planning View -->
        <div class="space-y-6">
            <!-- Auto Assignment Controls -->
            <div class="bg-white shadow-sm ring-1 ring-gray-900/5 rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4"><?php echo t('auto_assignment_controls'); ?></h3>
                    
                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="auto_assign">
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="flex items-center">
                                    <input type="checkbox" name="prefer_low_usage" class="rounded border-gray-300 text-water-blue-600 focus:ring-water-blue-500">
                                    <span class="ml-2 text-sm text-gray-700"><?php echo t('prefer_teams_fewer_assignments'); ?></span>
                                </label>
                            </div>
                            
                            <div>
                                <label class="flex items-center">
                                    <input type="checkbox" name="prefer_high_capacity" class="rounded border-gray-300 text-water-blue-600 focus:ring-water-blue-500">
                                    <span class="ml-2 text-sm text-gray-700"><?php echo t('prefer_teams_higher_capacity'); ?></span>
                                </label>
                            </div>
                        </div>
                        
                        <div class="flex justify-start">
                            <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-purple-600 hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500">
                                <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                </svg>
                                <?php echo t('run_auto_assignment'); ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Reset Controls -->
            <div class="bg-white shadow-sm ring-1 ring-gray-900/5 rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4"><?php echo t('reset_controls'); ?></h3>
                    <p class="text-sm text-gray-600 mb-4"><?php echo t('reset_controls_description'); ?></p>
                    
                    <div class="flex flex-wrap gap-3">
                        <button @click="showResetAllModal = true" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                            <svg class="-ml-1 mr-2 h-5 w-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            <?php echo t('reset_all_assignments'); ?>
                        </button>
                        
                        <button @click="showUnassignAllModal = true" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-orange-700 bg-orange-50 hover:bg-orange-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500">
                            <svg class="-ml-1 mr-2 h-5 w-5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                            <?php echo t('unassign_all_matches'); ?>
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Assignment Statistics -->
            <?php if (isset($stats)): ?>
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Team Assignment Counts -->
                    <div class="bg-white shadow-sm ring-1 ring-gray-900/5 rounded-lg">
                        <div class="px-4 py-5 sm:p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4"><?php echo t('team_assignment_status'); ?></h3>
                            <div class="space-y-3">
                                <?php foreach ($stats['team_assignments'] as $team): ?>
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($team['name']); ?></span>
                                        <div class="flex items-center space-x-2">
                                            <span class="text-sm text-gray-600"><?php echo $team['assignment_count']; ?> <?php echo t('assignments'); ?></span>
                                            <span class="text-xs text-gray-500">(<?php echo t('capacity'); ?>: <?php echo $team['capacity_factor']; ?>)</span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Match Assignment Overview -->
                    <div class="bg-white shadow-sm ring-1 ring-gray-900/5 rounded-lg">
                        <div class="px-4 py-5 sm:p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4"><?php echo t('match_assignment_overview'); ?></h3>
                            <div class="space-y-3">
                                <div class="flex justify-between">
                                    <span class="text-sm font-medium text-gray-900"><?php echo t('total_matches'); ?>:</span>
                                    <span class="text-sm text-gray-600"><?php echo $stats['match_status']['total_matches']; ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm font-medium text-green-700"><?php echo t('assigned'); ?>:</span>
                                    <span class="text-sm text-green-600"><?php echo $stats['match_status']['assigned_matches']; ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm font-medium text-red-700"><?php echo t('unassigned'); ?>:</span>
                                    <span class="text-sm text-red-600"><?php echo $stats['match_status']['unassigned_matches']; ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Lock Status Overview -->
                    <?php if (isset($lockStats)): ?>
                        <div class="bg-white shadow-sm ring-1 ring-gray-900/5 rounded-lg">
                            <div class="px-4 py-5 sm:p-6">
                                <h3 class="text-lg font-semibold text-gray-900 mb-4"><?php echo t('lock_status_overview'); ?></h3>
                                <div class="space-y-3">
                                    <div class="flex justify-between">
                                        <span class="text-sm font-medium text-gray-900"><?php echo t('total_matches'); ?>:</span>
                                        <span class="text-sm text-gray-600"><?php echo $lockStats['total_matches']; ?></span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-sm font-medium text-red-700"><?php echo t('locked'); ?>:</span>
                                        <span class="text-sm text-red-600"><?php echo $lockStats['locked_matches']; ?></span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-sm font-medium text-green-700"><?php echo t('unlocked'); ?>:</span>
                                        <span class="text-sm text-green-600"><?php echo $lockStats['unlocked_matches']; ?></span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-sm font-medium text-blue-700"><?php echo t('locked_with_assignments'); ?>:</span>
                                        <span class="text-sm text-blue-600"><?php echo $lockStats['locked_matches_with_assignments']; ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        
    <?php else: ?>
        <!-- List View (existing content) -->
        
    <!-- Filters -->
    <div class="bg-white shadow-sm ring-1 ring-gray-900/5 rounded-lg mb-6">
        <div class="px-4 py-4 sm:px-6">
            <h3 class="text-base font-semibold leading-6 text-gray-900 mb-4"><?php echo t('filter'); ?> <?php echo t('matches'); ?></h3>
            <form method="GET" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
                <input type="hidden" name="view" value="<?php echo htmlspecialchars($view); ?>">
                <input type="hidden" name="lang" value="<?php echo htmlspecialchars($currentLang); ?>">
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700"><?php echo t('status'); ?></label>
                    <select name="status" id="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-water-blue-500 focus:ring-water-blue-500 sm:text-sm">
                        <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>><?php echo t('all_statuses'); ?></option>
                        <option value="scheduled" <?php echo $statusFilter === 'scheduled' ? 'selected' : ''; ?>><?php echo t('scheduled'); ?></option>
                        <option value="in_progress" <?php echo $statusFilter === 'in_progress' ? 'selected' : ''; ?>><?php echo t('in_progress'); ?></option>
                        <option value="completed" <?php echo $statusFilter === 'completed' ? 'selected' : ''; ?>><?php echo t('completed'); ?></option>
                        <option value="cancelled" <?php echo $statusFilter === 'cancelled' ? 'selected' : ''; ?>><?php echo t('cancelled'); ?></option>
                    </select>
                </div>
                
                <div>
                    <label for="home_team" class="block text-sm font-medium text-gray-700"><?php echo t('home_team'); ?></label>
                    <select name="home_team" id="home_team" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-water-blue-500 focus:ring-water-blue-500 sm:text-sm">
                        <option value="all" <?php echo $homeTeamFilter === 'all' ? 'selected' : ''; ?>><?php echo t('all_teams'); ?></option>
                        <?php foreach ($matchTeams as $teamName): ?>
                            <option value="<?php echo htmlspecialchars($teamName); ?>" <?php echo $homeTeamFilter == $teamName ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($teamName); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label for="away_team" class="block text-sm font-medium text-gray-700"><?php echo t('away_team'); ?></label>
                    <select name="away_team" id="away_team" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-water-blue-500 focus:ring-water-blue-500 sm:text-sm">
                        <option value="all" <?php echo $awayTeamFilter === 'all' ? 'selected' : ''; ?>><?php echo t('all_teams'); ?></option>
                        <?php foreach ($matchTeams as $teamName): ?>
                            <option value="<?php echo htmlspecialchars($teamName); ?>" <?php echo $awayTeamFilter == $teamName ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($teamName); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label for="date" class="block text-sm font-medium text-gray-700"><?php echo t('date_range'); ?></label>
                    <select name="date" id="date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-water-blue-500 focus:ring-water-blue-500 sm:text-sm">
                        <option value="all" <?php echo $dateFilter === 'all' ? 'selected' : ''; ?>><?php echo t('all_dates'); ?></option>
                        <option value="upcoming" <?php echo $dateFilter === 'upcoming' ? 'selected' : ''; ?>><?php echo t('upcoming'); ?></option>
                        <option value="today" <?php echo $dateFilter === 'today' ? 'selected' : ''; ?>><?php echo t('today'); ?></option>
                        <option value="this_week" <?php echo $dateFilter === 'this_week' ? 'selected' : ''; ?>><?php echo t('this_week'); ?></option>
                        <option value="this_month" <?php echo $dateFilter === 'this_month' ? 'selected' : ''; ?>><?php echo t('this_month'); ?></option>
                    </select>
                </div>
                
                <div>
                    <label for="jury_status" class="block text-sm font-medium text-gray-700"><?php echo t('jury_status'); ?></label>
                    <select name="jury_status" id="jury_status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-water-blue-500 focus:ring-water-blue-500 sm:text-sm">
                        <option value="all" <?php echo $juryStatusFilter === 'all' ? 'selected' : ''; ?>><?php echo t('all_statuses'); ?></option>
                        <option value="assigned" <?php echo $juryStatusFilter === 'assigned' ? 'selected' : ''; ?>><?php echo t('assigned'); ?></option>
                        <option value="unassigned" <?php echo $juryStatusFilter === 'unassigned' ? 'selected' : ''; ?>><?php echo t('unassigned'); ?></option>
                        <option value="partial" <?php echo $juryStatusFilter === 'partial' ? 'selected' : ''; ?>><?php echo t('partially_assigned'); ?></option>
                    </select>
                </div>
                
                <div>
                    <label for="lock_status" class="block text-sm font-medium text-gray-700"><?php echo t('lock_status'); ?></label>
                    <select name="lock_status" id="lock_status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-water-blue-500 focus:ring-water-blue-500 sm:text-sm">
                        <option value="all" <?php echo $lockStatusFilter === 'all' ? 'selected' : ''; ?>><?php echo t('all_statuses'); ?></option>
                        <option value="locked" <?php echo $lockStatusFilter === 'locked' ? 'selected' : ''; ?>><?php echo t('locked'); ?></option>
                        <option value="unlocked" <?php echo $lockStatusFilter === 'unlocked' ? 'selected' : ''; ?>><?php echo t('unlocked'); ?></option>
                    </select>
                </div>
                
                <div class="col-span-full flex items-center justify-end gap-2 mt-4">
                    <button type="submit" class="inline-flex justify-center items-center rounded-md bg-gray-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-gray-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-600">
                        <svg class="-ml-0.5 mr-1.5 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.707A1 1 0 013 7V4z"></path>
                        </svg>
                        <?php echo t('filter'); ?>
                    </button>
                    <a href="?view=<?php echo $view; ?>&lang=<?php echo $currentLang; ?>" class="inline-flex justify-center items-center rounded-md bg-gray-300 px-3 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-200 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-300">
                        <svg class="-ml-0.5 mr-1.5 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        <?php echo t('clear_filters'); ?>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Matches Table -->
    <div class="bg-white shadow-sm ring-1 ring-gray-900/5 rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <?php if (empty($matches)): ?>
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3a1 1 0 011-1h6a1 1 0 011 1v4h.5a2 2 0 012 2v1a2 2 0 01-2 2H15v7a2 2 0 01-2 2H9a2 2 0 01-2-2v-7H5.5a2 2 0 01-2-2V9a2 2 0 012-2H7zM9 1v2h6V1H9z"></path>
                    </svg>
                    <h3 class="mt-2 text-sm font-semibold text-gray-900">No matches found</h3>
                    <p class="mt-1 text-sm text-gray-500">Get started by creating your first match or adjust your filters.</p>
                    <div class="mt-6">
                        <!-- Add Match button disabled for production -->
                    </div>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-300">
                        <thead>
                            <tr>
                                <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-0"><?php echo t('date_time'); ?></th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900"><?php echo t('home_team'); ?></th>
                                <th scope="col" class="px-3 py-3.5 text-center text-sm font-semibold text-gray-900"><?php echo t('competition'); ?> / <?php echo t('class'); ?></th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900"><?php echo t('away_team'); ?></th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900"><?php echo t('status'); ?></th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900"><?php echo t('lock_status'); ?></th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900"><?php echo t('jury_assignment'); ?></th>
                                <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-0">
                                    <span class="sr-only"><?php echo t('actions'); ?></span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php foreach ($matches as $match): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="whitespace-nowrap py-4 pl-4 pr-3 sm:pl-0">
                                        <div class="text-sm font-medium text-gray-900"><?php echo translateDayName(date('l', strtotime($match['match_date']))); ?></div>
                                        <div class="text-sm text-gray-900"><?php echo date('M j, Y', strtotime($match['match_date'])); ?></div>
                                        <div class="text-sm text-gray-500"><?php echo date('H:i', strtotime($match['match_time'])); ?></div>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                        <div class="flex items-center">
                                            <div class="h-8 w-8 flex-shrink-0">
                                                <div class="h-8 w-8 rounded-full bg-blue-100 flex items-center justify-center">
                                                    <svg class="h-4 w-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                                                    </svg>
                                                </div>
                                            </div>
                                            <div class="ml-3">
                                                <div class="text-sm font-medium text-gray-900">
                                                    <?php echo htmlspecialchars($match['home_team_name']); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 text-center">
                                        <div class="text-xs text-gray-600">
                                            <?php if ($match['competition']): ?>
                                                <div><?php echo htmlspecialchars($match['competition']); ?></div>
                                            <?php endif; ?>
                                            <?php if ($match['class']): ?>
                                                <div class="<?php echo $match['competition'] ? 'mt-1' : ''; ?>"><?php echo htmlspecialchars($match['class']); ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                        <div class="flex items-center">
                                            <div class="h-8 w-8 flex-shrink-0">
                                                <div class="h-8 w-8 rounded-full bg-green-100 flex items-center justify-center">
                                                    <svg class="h-4 w-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                    </svg>
                                                </div>
                                            </div>
                                            <div class="ml-3">
                                                <div class="text-sm font-medium text-gray-900">
                                                    <?php echo htmlspecialchars($match['away_team_name']); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                        <?php
                                        $statusColors = [
                                            'scheduled' => 'bg-blue-50 text-blue-700 ring-blue-600/20',
                                            'in_progress' => 'bg-yellow-50 text-yellow-800 ring-yellow-600/20',
                                            'completed' => 'bg-green-50 text-green-700 ring-green-600/20',
                                            'cancelled' => 'bg-red-50 text-red-700 ring-red-600/20'
                                        ];
                                        $statusColor = $statusColors[$match['status']] ?? 'bg-gray-50 text-gray-600 ring-gray-500/10';
                                        ?>
                                        <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium ring-1 ring-inset <?php echo $statusColor; ?>">
                                            <?php echo t($match['status']); ?>
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                        <?php if ($match['locked']): ?>
                                            <div class="flex items-center">
                                                <span class="inline-flex items-center rounded-full bg-red-50 px-2 py-1 text-xs font-medium text-red-700 ring-1 ring-inset ring-red-600/20">
                                                    <svg class="mr-1 h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M10 1a4.5 4.5 0 00-4.5 4.5V9H5a2 2 0 00-2 2v6a2 2 0 002 2h10a2 2 0 002-2v-6a2 2 0 00-2-2h-.5V5.5A4.5 4.5 0 0010 1zm3 8V5.5a3 3 0 10-6 0V9h6z" clip-rule="evenodd" />
                                                    </svg>
                                                    Locked
                                                </span>
                                                <?php if ($match['locked_by']): ?>
                                                    <div class="ml-2 text-xs text-gray-500" title="Locked at <?php echo $match['locked_at']; ?>">
                                                        by <?php echo htmlspecialchars($match['locked_by']); ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="inline-flex items-center rounded-full bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">
                                                <svg class="mr-1 h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 1a4.5 4.5 0 00-4.5 4.5V9H5a2 2 0 00-2 2v6a2 2 0 002 2h10a2 2 0 002-2v-6a2 2 0 00-2-2h-.5V5.5A4.5 4.5 0 0010 1zm3 8V5.5a3 3 0 10-6 0V9h6z" clip-rule="evenodd" />
                                                </svg>
                                                <?php echo t('unlocked'); ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-3 py-4 text-sm text-gray-500">
                                        <?php if ($match['jury_assignments']): ?>
                                            <div class="space-y-1">
                                                <?php foreach ($match['jury_assignments'] as $assignment): ?>
                                                    <div class="flex items-center justify-between">
                                                        <span class="inline-flex items-center rounded-full bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">
                                                            <?php echo htmlspecialchars($assignment['jury_team_name']); ?>
                                                        </span>
                                                        <button @click="removeJuryAssignment(<?php echo $assignment['assignment_id']; ?>)" class="ml-2 text-red-600 hover:text-red-900">
                                                            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                            </svg>
                                                        </button>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php else: ?>
                                            <button @click="showAssignJuryModal(<?php echo $match['id']; ?>, '<?php echo htmlspecialchars($match['home_team_name'] . ' vs ' . $match['away_team_name']); ?>')" class="inline-flex items-center rounded-md bg-white px-2 py-1 text-xs font-medium text-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                                                <svg class="-ml-0.5 mr-1 h-3 w-3" viewBox="0 0 20 20" fill="currentColor">
                                                    <path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
                                                </svg>
                                                Assign Jury
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                    <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-0">
                                        <div class="flex justify-end space-x-2">
                                            <!-- Lock/Unlock buttons -->
                                            <?php if ($match['locked']): ?>
                                                <button @click="unlockMatch(<?php echo $match['id']; ?>, '<?php echo htmlspecialchars($match['home_team_name'] . ' vs ' . $match['away_team_name']); ?>')" 
                                                        class="text-green-600 hover:text-green-900" title="Unlock match">
                                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                                        <path d="M10 2a5 5 0 00-5 5v2a2 2 0 00-2 2v5a2 2 0 002 2h10a2 2 0 002-2v-5a2 2 0 00-2-2H7V7a3 3 0 015.905-.75 1 1 0 001.937-.5A5.002 5.002 0 0010 2z"/>
                                                    </svg>
                                                </button>
                                            <?php else: ?>
                                                <button @click="lockMatch(<?php echo $match['id']; ?>, '<?php echo htmlspecialchars($match['home_team_name'] . ' vs ' . $match['away_team_name']); ?>')" 
                                                        class="text-red-600 hover:text-red-900" title="Lock match">
                                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                                                    </svg>
                                                </button>
                                            <?php endif; ?>
                                            
                                            <!-- Reset button (only for unlocked matches with assignments) -->
                                            <?php if (!$match['locked'] && $match['jury_assignments']): ?>
                                                <button @click="resetMatchAssignments(<?php echo $match['id']; ?>, '<?php echo htmlspecialchars($match['home_team_name'] . ' vs ' . $match['away_team_name']); ?>')" 
                                                        class="text-orange-600 hover:text-orange-900" title="Reset assignments">
                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                                    </svg>
                                                </button>
                                            <?php endif; ?>
                                            
                                            <!-- Delete button disabled for production -->
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Assign Jury Modal -->
    <div x-show="showJuryModal" x-cloak class="relative z-50" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
        <div class="fixed inset-0 z-10 overflow-y-auto">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div class="relative transform overflow-hidden rounded-lg bg-white px-4 pb-4 pt-5 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6">
                    <form method="POST">
                        <input type="hidden" name="action" value="assign_jury">
                        <input type="hidden" name="match_id" :value="assignJuryMatchId">
                        
                        <div>
                            <div class="mt-3 text-center sm:mt-0 sm:text-left">
                                <h3 class="text-base font-semibold leading-6 text-gray-900">Assign Jury Team</h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-500" x-text="'Assigning jury for: ' + assignJuryMatchName"></p>
                                </div>
                                <div class="mt-4">
                                    <label for="jury_team_id" class="block text-sm font-medium leading-6 text-gray-900">Select Jury Team</label>
                                    <select name="jury_team_id" id="jury_team_id" required class="mt-2 block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-water-blue-600 sm:text-sm sm:leading-6">
                                        <option value="">Choose a team</option>
                                        <?php foreach ($teams as $team): ?>
                                            <?php if ($team['is_active']): ?>
                                                <option value="<?php echo $team['id']; ?>"><?php echo htmlspecialchars($team['name']); ?> (Weight: <?php echo $team['weight']; ?>)</option>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                            <button type="submit" class="inline-flex w-full justify-center rounded-md bg-water-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-water-blue-500 sm:ml-3 sm:w-auto">Assign</button>
                            <button @click="showJuryModal = false" type="button" class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div x-show="showDeleteModal" x-cloak class="relative z-50" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
        <div class="fixed inset-0 z-10 overflow-y-auto">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div class="relative transform overflow-hidden rounded-lg bg-white px-4 pb-4 pt-5 text-left shadow-xl transition-all sm:text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"></path>
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left">
                            <h3 class="text-base font-semibold leading-6 text-gray-900">Delete Match</h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500">
                                    Are you sure you want to delete <strong x-text="deleteMatchName"></strong>? This action cannot be undone.
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                        <form method="POST" class="inline">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" :value="deleteMatchId">
                            <button type="submit" class="inline-flex w-full justify-center rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500 sm:ml-3 sm:w-auto">Delete</button>
                        </form>
                        <button @click="showDeleteModal = false" type="button" class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto">Cancel</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php endif; // End of list view ?>

    <!-- Reset All Assignments Modal -->
    <div x-show="showResetAllModal" x-cloak class="relative z-50" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
        <div class="fixed inset-0 z-10 overflow-y-auto">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div class="relative transform overflow-hidden rounded-lg bg-white px-4 pb-4 pt-5 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"></path>
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left">
                            <h3 class="text-base font-semibold leading-6 text-gray-900">Reset All Assignments</h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500">
                                    This will remove all jury assignments from matches. Locked matches will be preserved unless you force reset.
                                </p>
                            </div>
                            <div class="mt-4">
                                <label class="flex items-center">
                                    <input type="checkbox" x-model="forceIncludeLocked" class="rounded border-gray-300 text-red-600 focus:ring-red-500">
                                    <span class="ml-2 text-sm text-gray-700">Also reset locked matches (force reset)</span>
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                        <button @click="confirmResetAll()" class="inline-flex w-full justify-center rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500 sm:ml-3 sm:w-auto">
                            Reset All
                        </button>
                        <button @click="showResetAllModal = false; forceIncludeLocked = false" type="button" class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Unassign All Modal -->
    <div x-show="showUnassignAllModal" x-cloak class="relative z-50" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
        <div class="fixed inset-0 z-10 overflow-y-auto">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div class="relative transform overflow-hidden rounded-lg bg-white px-4 pb-4 pt-5 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-orange-100 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left">
                            <h3 class="text-base font-semibold leading-6 text-gray-900"><?php echo t('unassign_all_jury_teams'); ?></h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500">
                                    This will remove all jury team assignments from all matches. Locked matches will be preserved unless you force unassign.
                                </p>
                            </div>
                            <div class="mt-4">
                                <label class="flex items-center">
                                    <input type="checkbox" x-model="forceIncludeLockedUnassign" class="rounded border-gray-300 text-orange-600 focus:ring-orange-500">
                                    <span class="ml-2 text-sm text-gray-700">Also unassign locked matches (force unassign)</span>
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                        <button @click="confirmUnassignAll()" class="inline-flex w-full justify-center rounded-md bg-orange-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-orange-500 sm:ml-3 sm:w-auto">
                            <?php echo t('unassign_all'); ?>
                        </button>
                        <button @click="showUnassignAllModal = false; forceIncludeLockedUnassign = false" type="button" class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function matchesApp() {
    return {
        showDeleteModal: false,
        showJuryModal: false,
        showResetAllModal: false,
        showUnassignAllModal: false,
        forceIncludeLocked: false,
        forceIncludeLockedUnassign: false,
        deleteMatchId: null,
        deleteMatchName: '',
        assignJuryMatchId: null,
        assignJuryMatchName: '',
        
        init() {
            // Initialize component
        },
        
        deleteMatch(id, name) {
            this.deleteMatchId = id;
            this.deleteMatchName = name;
            this.showDeleteModal = true;
        },
        
        showAssignJuryModal(matchId, matchName) {
            this.assignJuryMatchId = matchId;
            this.assignJuryMatchName = matchName;
            this.showJuryModal = true;
        },
        
        removeJuryAssignment(assignmentId) {
            console.log('removeJuryAssignment called with:', assignmentId);
            console.log('Translations object:', window.JuryPlanner?.translations);
            
            const confirmMessage = window.JuryPlanner?.translations?.confirmRemoveJuryAssignment 
                || 'Are you sure you want to remove this jury assignment?';
                
            console.log('Confirm message:', confirmMessage);
            
            if (confirm(confirmMessage)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="remove_jury">
                    <input type="hidden" name="assignment_id" value="${assignmentId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        },
        
        lockMatch(matchId, matchName) {
            console.log('lockMatch called with:', matchId, matchName);
            console.log('Translations object:', window.JuryPlanner?.translations);
            
            const confirmMessage = window.JuryPlanner?.translations?.lockMatchConfirm 
                ? window.JuryPlanner.translations.lockMatchConfirm.replace('{0}', matchName)
                : `Are you sure you want to lock the match: ${matchName}? This will prevent changes to jury assignments.`;
                
            console.log('Confirm message:', confirmMessage);
            
            if (confirm(confirmMessage)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="lock_match">
                    <input type="hidden" name="match_id" value="${matchId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        },
        
        unlockMatch(matchId, matchName) {
            console.log('unlockMatch called with:', matchId, matchName);
            console.log('Translations object:', window.JuryPlanner?.translations);
            
            const confirmMessage = window.JuryPlanner?.translations?.unlockMatchConfirm 
                ? window.JuryPlanner.translations.unlockMatchConfirm.replace('{0}', matchName)
                : `Are you sure you want to unlock the match: ${matchName}? This will allow changes to jury assignments.`;
                
            console.log('Confirm message:', confirmMessage);
            
            if (confirm(confirmMessage)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="unlock_match">
                    <input type="hidden" name="match_id" value="${matchId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        },
        
        resetMatchAssignments(matchId, matchName) {
            console.log('resetMatchAssignments called with:', matchId, matchName);
            console.log('Translations object:', window.JuryPlanner?.translations);
            
            const confirmMessage = window.JuryPlanner?.translations?.resetMatchAssignmentsConfirm 
                ? window.JuryPlanner.translations.resetMatchAssignmentsConfirm.replace('{0}', matchName)
                : `Are you sure you want to reset all jury assignments for match: ${matchName}? This action cannot be undone.`;
                
            console.log('Confirm message:', confirmMessage);
            
            if (confirm(confirmMessage)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="reset_match">
                    <input type="hidden" name="match_id" value="${matchId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        },
        
        confirmResetAll() {
            const message = this.forceIncludeLocked 
                ? 'Reset ALL jury assignments including locked matches? This action cannot be undone.'
                : 'Reset all jury assignments from unlocked matches? Locked matches will be preserved.';
                
            if (confirm(message)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="reset_all">
                    ${this.forceIncludeLocked ? '<input type="hidden" name="force_include_locked" value="1">' : ''}
                `;
                document.body.appendChild(form);
                form.submit();
            }
        },
        
        confirmUnassignAll() {
            const message = this.forceIncludeLockedUnassign 
                ? 'Unassign ALL jury teams including from locked matches? This action cannot be undone.'
                : 'Unassign all jury teams from unlocked matches? Locked matches will be preserved.';
                
            if (confirm(message)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="unassign_all">
                    ${this.forceIncludeLockedUnassign ? '<input type="hidden" name="force_include_locked" value="1">' : ''}
                `;
                document.body.appendChild(form);
                form.submit();
            }
        },
        
        closeModals() {
            this.showJuryModal = false;
            this.showResetAllModal = false;
            this.showUnassignAllModal = false;
            this.forceIncludeLocked = false;
            this.forceIncludeLockedUnassign = false;
        }
    }
}
</script>

<?php
$content = ob_get_clean();
include 'includes/layout.php';
?>
