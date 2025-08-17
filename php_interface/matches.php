<?php
session_start();
require_once 'config/database.php';
require_once 'includes/MatchManager.php';
require_once 'includes/TeamManager.php';
require_once 'includes/AssignmentConstraintManager.php';

$matchManager = new MatchManager($db);
$teamManager = new TeamManager($db);
$constraintManager = new AssignmentConstraintManager($db);

// Handle form submissions
if ($_POST) {
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'create':
                    $matchManager->createMatch($_POST);
                    $_SESSION['success'] = 'Match created successfully!';
                    header('Location: matches.php');
                    exit;
                    
                case 'update':
                    $matchManager->updateMatch($_POST['id'], $_POST);
                    $_SESSION['success'] = 'Match updated successfully!';
                    header('Location: matches.php');
                    exit;
                    
                case 'delete':
                    $matchManager->deleteMatch($_POST['id']);
                    $_SESSION['success'] = 'Match deleted successfully!';
                    header('Location: matches.php');
                    exit;
                    
                case 'assign_jury':
                    $matchManager->assignJuryTeam($_POST['match_id'], $_POST['jury_team_id']);
                    $_SESSION['success'] = 'Jury team assigned successfully!';
                    header('Location: matches.php');
                    exit;
                    
                case 'remove_jury':
                    $matchManager->removeJuryAssignment($_POST['assignment_id']);
                    $_SESSION['success'] = 'Jury assignment removed!';
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
$teamFilter = $_GET['team'] ?? 'all';
$dateFilter = $_GET['date'] ?? 'all';

// Get data
$teams = $teamManager->getAllTeams();

if ($view === 'planning') {
    $stats = $constraintManager->getAssignmentStatistics();
    $pageTitle = 'Auto Assignment Planning';
    $pageDescription = 'Automatically assign jury teams to matches using constraints and optimization';
} else {
    $matches = $matchManager->getMatchesWithDetails($statusFilter, $teamFilter, $dateFilter);
    $pageTitle = 'Matches Management';
    $pageDescription = 'Manage water polo matches, assign jury teams, and track assignments';
}

ob_start();
?>

<div x-data="matchesApp()" x-init="init()">
    <!-- Header with view navigation -->
    <div class="mb-6">
        <div class="sm:flex sm:items-center sm:justify-between mb-4">
            <div class="min-w-0 flex-1">
                <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:truncate sm:text-3xl sm:tracking-tight">
                    <?php echo $view === 'planning' ? 'Auto Assignment Planning' : 'Matches'; ?>
                </h2>
            </div>
            <div class="mt-4 flex sm:ml-4 sm:mt-0 space-x-3">
                <?php if ($view === 'list'): ?>
                    <button @click="showCreateModal = true" type="button" class="inline-flex items-center rounded-md bg-water-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-water-blue-500">
                        <svg class="-ml-0.5 mr-1.5 h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
                        </svg>
                        Add Match
                    </button>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- View Navigation -->
        <div class="border-b border-gray-200">
            <nav class="-mb-px flex space-x-8">
                <a href="matches.php" class="<?php echo $view === 'list' ? 'border-water-blue-500 text-water-blue-600' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'; ?> whitespace-nowrap border-b-2 py-2 px-1 text-sm font-medium">
                    Matches List
                </a>
                <a href="matches.php?view=planning" class="<?php echo $view === 'planning' ? 'border-water-blue-500 text-water-blue-600' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'; ?> whitespace-nowrap border-b-2 py-2 px-1 text-sm font-medium">
                    Auto Planning
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
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Auto Assignment Controls</h3>
                    
                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="auto_assign">
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="flex items-center">
                                    <input type="checkbox" name="prefer_low_usage" class="rounded border-gray-300 text-water-blue-600 focus:ring-water-blue-500">
                                    <span class="ml-2 text-sm text-gray-700">Prefer teams with fewer assignments</span>
                                </label>
                            </div>
                            
                            <div>
                                <label class="flex items-center">
                                    <input type="checkbox" name="prefer_high_capacity" class="rounded border-gray-300 text-water-blue-600 focus:ring-water-blue-500">
                                    <span class="ml-2 text-sm text-gray-700">Prefer teams with higher capacity</span>
                                </label>
                            </div>
                        </div>
                        
                        <div class="flex justify-start">
                            <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-purple-600 hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500">
                                <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                </svg>
                                Run Auto Assignment
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Assignment Statistics -->
            <?php if (isset($stats)): ?>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Team Assignment Counts -->
                    <div class="bg-white shadow-sm ring-1 ring-gray-900/5 rounded-lg">
                        <div class="px-4 py-5 sm:p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Team Assignment Status</h3>
                            <div class="space-y-3">
                                <?php foreach ($stats['team_assignments'] as $team): ?>
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($team['name']); ?></span>
                                        <div class="flex items-center space-x-2">
                                            <span class="text-sm text-gray-600"><?php echo $team['assignment_count']; ?> assignments</span>
                                            <span class="text-xs text-gray-500">(capacity: <?php echo $team['capacity_factor']; ?>)</span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Match Assignment Overview -->
                    <div class="bg-white shadow-sm ring-1 ring-gray-900/5 rounded-lg">
                        <div class="px-4 py-5 sm:p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Match Assignment Overview</h3>
                            <div class="space-y-3">
                                <div class="flex justify-between">
                                    <span class="text-sm font-medium text-gray-900">Total Matches:</span>
                                    <span class="text-sm text-gray-600"><?php echo $stats['match_status']['total_matches']; ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm font-medium text-green-700">Assigned:</span>
                                    <span class="text-sm text-green-600"><?php echo $stats['match_status']['assigned_matches']; ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm font-medium text-red-700">Unassigned:</span>
                                    <span class="text-sm text-red-600"><?php echo $stats['match_status']['unassigned_matches']; ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
    <?php else: ?>
        <!-- List View (existing content) -->
        
    <!-- Filters -->
    <div class="bg-white shadow-sm ring-1 ring-gray-900/5 rounded-lg mb-6">
        <div class="px-4 py-4 sm:px-6">
            <form method="GET" class="grid grid-cols-1 gap-4 sm:grid-cols-4">
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                    <select name="status" id="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-water-blue-500 focus:ring-water-blue-500 sm:text-sm">
                        <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>All Statuses</option>
                        <option value="scheduled" <?php echo $statusFilter === 'scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                        <option value="in_progress" <?php echo $statusFilter === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                        <option value="completed" <?php echo $statusFilter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="cancelled" <?php echo $statusFilter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
                
                <div>
                    <label for="team" class="block text-sm font-medium text-gray-700">Team</label>
                    <select name="team" id="team" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-water-blue-500 focus:ring-water-blue-500 sm:text-sm">
                        <option value="all" <?php echo $teamFilter === 'all' ? 'selected' : ''; ?>>All Teams</option>
                        <?php foreach ($teams as $team): ?>
                            <option value="<?php echo $team['id']; ?>" <?php echo $teamFilter == $team['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($team['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label for="date" class="block text-sm font-medium text-gray-700">Date Range</label>
                    <select name="date" id="date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-water-blue-500 focus:ring-water-blue-500 sm:text-sm">
                        <option value="all" <?php echo $dateFilter === 'all' ? 'selected' : ''; ?>>All Dates</option>
                        <option value="upcoming" <?php echo $dateFilter === 'upcoming' ? 'selected' : ''; ?>>Upcoming</option>
                        <option value="today" <?php echo $dateFilter === 'today' ? 'selected' : ''; ?>>Today</option>
                        <option value="this_week" <?php echo $dateFilter === 'this_week' ? 'selected' : ''; ?>>This Week</option>
                        <option value="this_month" <?php echo $dateFilter === 'this_month' ? 'selected' : ''; ?>>This Month</option>
                    </select>
                </div>
                
                <div class="flex items-end">
                    <button type="submit" class="w-full inline-flex justify-center items-center rounded-md bg-gray-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-gray-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-600">
                        <svg class="-ml-0.5 mr-1.5 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.707A1 1 0 013 7V4z"></path>
                        </svg>
                        Filter
                    </button>
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
                        <button @click="showCreateModal = true" type="button" class="inline-flex items-center rounded-md bg-water-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-water-blue-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-water-blue-600">
                            <svg class="-ml-0.5 mr-1.5 h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
                            </svg>
                            Add Match
                        </button>
                    </div>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-300">
                        <thead>
                            <tr>
                                <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-0">Match</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Date & Time</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Location</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Status</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Jury Assignment</th>
                                <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-0">
                                    <span class="sr-only">Actions</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php foreach ($matches as $match): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="whitespace-nowrap py-4 pl-4 pr-3 sm:pl-0">
                                        <div class="flex items-center">
                                            <div class="h-10 w-10 flex-shrink-0">
                                                <div class="h-10 w-10 rounded-full bg-water-blue-100 flex items-center justify-center">
                                                    <svg class="h-5 w-5 text-water-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                                    </svg>
                                                </div>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900">
                                                    <?php echo htmlspecialchars($match['home_team_name']); ?> vs <?php echo htmlspecialchars($match['away_team_name']); ?>
                                                </div>
                                                <?php if ($match['competition']): ?>
                                                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars($match['competition']); ?></div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                        <div class="text-gray-900"><?php echo date('M j, Y', strtotime($match['match_date'])); ?></div>
                                        <div class="text-gray-500"><?php echo date('g:i A', strtotime($match['match_time'])); ?></div>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                        <div class="text-gray-900"><?php echo htmlspecialchars($match['location']); ?></div>
                                        <?php if ($match['pool_name']): ?>
                                            <div class="text-gray-500"><?php echo htmlspecialchars($match['pool_name']); ?></div>
                                        <?php endif; ?>
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
                                            <?php echo ucfirst(str_replace('_', ' ', $match['status'])); ?>
                                        </span>
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
                                            <button @click="editMatch(<?php echo htmlspecialchars(json_encode($match)); ?>)" class="text-water-blue-600 hover:text-water-blue-900">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                </svg>
                                            </button>
                                            <button @click="deleteMatch(<?php echo $match['id']; ?>, '<?php echo htmlspecialchars($match['home_team_name'] . ' vs ' . $match['away_team_name']); ?>')" class="text-red-600 hover:text-red-900">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                </svg>
                                            </button>
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

    <!-- Create/Edit Match Modal -->
    <div x-show="showCreateModal || showEditModal" x-cloak class="relative z-50" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
        <div class="fixed inset-0 z-10 overflow-y-auto">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div class="relative transform overflow-hidden rounded-lg bg-white px-4 pb-4 pt-5 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6">
                    <form method="POST">
                        <input type="hidden" name="action" :value="showEditModal ? 'update' : 'create'">
                        <input x-show="showEditModal" type="hidden" name="id" :value="editingMatch?.id">
                        
                        <div>
                            <div class="mt-3 text-center sm:mt-0 sm:text-left">
                                <h3 class="text-base font-semibold leading-6 text-gray-900" x-text="showEditModal ? 'Edit Match' : 'Add New Match'"></h3>
                                <div class="mt-4 space-y-4">
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label for="home_team_id" class="block text-sm font-medium leading-6 text-gray-900">Home Team</label>
                                            <select name="home_team_id" id="home_team_id" required x-model="editingMatch.home_team_id" class="mt-2 block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-water-blue-600 sm:text-sm sm:leading-6">
                                                <option value="">Select team</option>
                                                <?php foreach ($teams as $team): ?>
                                                    <option value="<?php echo $team['id']; ?>"><?php echo htmlspecialchars($team['name']); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        
                                        <div>
                                            <label for="away_team_id" class="block text-sm font-medium leading-6 text-gray-900">Away Team</label>
                                            <select name="away_team_id" id="away_team_id" required x-model="editingMatch.away_team_id" class="mt-2 block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-water-blue-600 sm:text-sm sm:leading-6">
                                                <option value="">Select team</option>
                                                <?php foreach ($teams as $team): ?>
                                                    <option value="<?php echo $team['id']; ?>"><?php echo htmlspecialchars($team['name']); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label for="match_date" class="block text-sm font-medium leading-6 text-gray-900">Date</label>
                                            <input type="date" name="match_date" id="match_date" required x-model="editingMatch.match_date" class="mt-2 block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-water-blue-600 sm:text-sm sm:leading-6">
                                        </div>
                                        
                                        <div>
                                            <label for="match_time" class="block text-sm font-medium leading-6 text-gray-900">Time</label>
                                            <input type="time" name="match_time" id="match_time" required x-model="editingMatch.match_time" class="mt-2 block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-water-blue-600 sm:text-sm sm:leading-6">
                                        </div>
                                    </div>
                                    
                                    <div>
                                        <label for="location" class="block text-sm font-medium leading-6 text-gray-900">Location</label>
                                        <input type="text" name="location" id="location" required x-model="editingMatch.location" class="mt-2 block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-water-blue-600 sm:text-sm sm:leading-6">
                                    </div>
                                    
                                    <div>
                                        <label for="pool_name" class="block text-sm font-medium leading-6 text-gray-900">Pool Name</label>
                                        <input type="text" name="pool_name" id="pool_name" x-model="editingMatch.pool_name" class="mt-2 block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-water-blue-600 sm:text-sm sm:leading-6">
                                    </div>
                                    
                                    <div>
                                        <label for="competition" class="block text-sm font-medium leading-6 text-gray-900">Competition</label>
                                        <input type="text" name="competition" id="competition" x-model="editingMatch.competition" class="mt-2 block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-water-blue-600 sm:text-sm sm:leading-6">
                                    </div>
                                    
                                    <div>
                                        <label for="status" class="block text-sm font-medium leading-6 text-gray-900">Status</label>
                                        <select name="status" id="status" x-model="editingMatch.status" class="mt-2 block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-water-blue-600 sm:text-sm sm:leading-6">
                                            <option value="scheduled">Scheduled</option>
                                            <option value="in_progress">In Progress</option>
                                            <option value="completed">Completed</option>
                                            <option value="cancelled">Cancelled</option>
                                        </select>
                                    </div>
                                    
                                    <div>
                                        <label for="notes" class="block text-sm font-medium leading-6 text-gray-900">Notes</label>
                                        <textarea name="notes" id="notes" rows="3" x-model="editingMatch.notes" class="mt-2 block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-water-blue-600 sm:text-sm sm:leading-6"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                            <button type="submit" class="inline-flex w-full justify-center rounded-md bg-water-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-water-blue-500 sm:ml-3 sm:w-auto">
                                <span x-text="showEditModal ? 'Update Match' : 'Create Match'"></span>
                            </button>
                            <button @click="closeModals" type="button" class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
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
</div>

<script>
function matchesApp() {
    return {
        showCreateModal: false,
        showEditModal: false,
        showDeleteModal: false,
        showJuryModal: false,
        editingMatch: {
            id: null,
            home_team_id: '',
            away_team_id: '',
            match_date: '',
            match_time: '',
            location: '',
            pool_name: '',
            competition: '',
            status: 'scheduled',
            notes: ''
        },
        deleteMatchId: null,
        deleteMatchName: '',
        assignJuryMatchId: null,
        assignJuryMatchName: '',
        
        init() {
            // Initialize component
        },
        
        editMatch(match) {
            this.editingMatch = { ...match };
            this.showEditModal = true;
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
            if (confirm('Are you sure you want to remove this jury assignment?')) {
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
        
        closeModals() {
            this.showCreateModal = false;
            this.showEditModal = false;
            this.showJuryModal = false;
            this.editingMatch = {
                id: null,
                home_team_id: '',
                away_team_id: '',
                match_date: '',
                match_time: '',
                location: '',
                pool_name: '',
                competition: '',
                status: 'scheduled',
                notes: ''
            };
        }
    }
}
</script>

<?php
$content = ob_get_clean();
include 'includes/layout.php';
?>
