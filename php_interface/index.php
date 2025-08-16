<?php
session_start();
require_once 'config/database.php';
require_once 'includes/TeamManager.php';
require_once 'includes/MatchManager.php';

$teamManager = new TeamManager($db);
$matchManager = new MatchManager($db);

// Get dashboard data
$teamStats = $teamManager->getOverallStats();
$matchStats = $matchManager->getMatchStats();
$upcomingMatches = $matchManager->getUpcomingMatches(5);

$pageTitle = 'Dashboard';
$pageDescription = 'Overview of teams, matches, and jury assignments';

ob_start();
?>

<div x-data="dashboardApp()" x-init="init()">
    <!-- Stats Overview -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-8">
        <!-- Total Teams -->
        <div class="bg-white overflow-hidden shadow-sm rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-8 w-8 text-water-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Total Teams</dt>
                            <dd class="text-lg font-medium text-gray-900"><?php echo $teamStats['total_teams'] ?? 0; ?></dd>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-5 py-3">
                <div class="text-sm">
                    <a href="teams.php" class="font-medium text-water-blue-600 hover:text-water-blue-500">
                        View all teams
                    </a>
                </div>
            </div>
        </div>

        <!-- Active Teams -->
        <div class="bg-white overflow-hidden shadow-sm rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-8 w-8 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Active Teams</dt>
                            <dd class="text-lg font-medium text-gray-900"><?php echo $teamStats['active_teams'] ?? 0; ?></dd>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-5 py-3">
                <div class="text-sm">
                    <span class="text-gray-600">
                        <?php echo $teamStats['inactive_teams'] ?? 0; ?> inactive
                    </span>
                </div>
            </div>
        </div>

        <!-- Total Matches -->
        <div class="bg-white overflow-hidden shadow-sm rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-8 w-8 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3a1 1 0 011-1h6a1 1 0 011 1v4h.5a2 2 0 012 2v1a2 2 0 01-2 2H15v7a2 2 0 01-2 2H9a2 2 0 01-2-2v-7H5.5a2 2 0 01-2-2V9a2 2 0 012-2H7zM9 1v2h6V1H9z"></path>
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Total Matches</dt>
                            <dd class="text-lg font-medium text-gray-900"><?php echo $matchStats['total_matches'] ?? 0; ?></dd>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-5 py-3">
                <div class="text-sm">
                    <a href="matches.php" class="font-medium text-water-blue-600 hover:text-water-blue-500">
                        View all matches
                    </a>
                </div>
            </div>
        </div>

        <!-- Upcoming Matches -->
        <div class="bg-white overflow-hidden shadow-sm rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-8 w-8 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Upcoming</dt>
                            <dd class="text-lg font-medium text-gray-900"><?php echo $matchStats['upcoming_matches'] ?? 0; ?></dd>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-5 py-3">
                <div class="text-sm">
                    <span class="text-gray-600">
                        <?php echo $matchStats['past_matches'] ?? 0; ?> completed
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Upcoming Matches -->
    <div class="bg-white shadow-sm ring-1 ring-gray-900/5 rounded-lg mb-8">
        <div class="px-4 py-5 sm:p-6">
            <div class="sm:flex sm:items-center sm:justify-between mb-4">
                <h3 class="text-lg font-medium leading-6 text-gray-900">Upcoming Matches</h3>
                <div class="mt-3 sm:mt-0 sm:ml-4">
                    <a href="matches.php" class="text-sm font-medium text-water-blue-600 hover:text-water-blue-500">
                        View all →
                    </a>
                </div>
            </div>
            
            <?php if (empty($upcomingMatches)): ?>
                <div class="text-center py-8">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3a1 1 0 011-1h6a1 1 0 011 1v4h.5a2 2 0 012 2v1a2 2 0 01-2 2H15v7a2 2 0 01-2 2H9a2 2 0 01-2-2v-7H5.5a2 2 0 01-2-2V9a2 2 0 012-2H7zM9 1v2h6V1H9z"></path>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No upcoming matches</h3>
                    <p class="mt-1 text-sm text-gray-500">Create your first match to get started.</p>
                    <div class="mt-6">
                        <a href="matches.php" class="inline-flex items-center rounded-md bg-water-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-water-blue-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-water-blue-600">
                            <svg class="-ml-0.5 mr-1.5 h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
                            </svg>
                            Add Match
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="divide-y divide-gray-200">
                    <?php foreach ($upcomingMatches as $match): ?>
                        <div class="py-4 flex items-center justify-between">
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
                                    <div class="text-sm text-gray-500">
                                        <?php echo date('M j, Y', strtotime($match['match_date'])); ?> at <?php echo date('g:i A', strtotime($match['match_time'])); ?>
                                        <?php if ($match['location']): ?>
                                            • <?php echo htmlspecialchars($match['location']); ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center text-sm text-gray-500">
                                <?php if ($match['assignment_count'] > 0): ?>
                                    <span class="inline-flex items-center rounded-full bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">
                                        Jury Assigned
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center rounded-full bg-yellow-50 px-2 py-1 text-xs font-medium text-yellow-800 ring-1 ring-inset ring-yellow-600/20">
                                        Needs Jury
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="bg-white shadow-sm ring-1 ring-gray-900/5 rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">Quick Actions</h3>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <a href="teams.php" class="relative block w-full rounded-lg border-2 border-dashed border-gray-300 p-6 text-center hover:border-gray-400 focus:outline-none focus:ring-2 focus:ring-water-blue-500 focus:ring-offset-2">
                    <svg class="mx-auto h-8 w-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                    <span class="mt-2 block text-sm font-medium text-gray-900">Manage Teams</span>
                </a>

                <a href="matches.php" class="relative block w-full rounded-lg border-2 border-dashed border-gray-300 p-6 text-center hover:border-gray-400 focus:outline-none focus:ring-2 focus:ring-water-blue-500 focus:ring-offset-2">
                    <svg class="mx-auto h-8 w-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3a1 1 0 011-1h6a1 1 0 011 1v4h.5a2 2 0 012 2v1a2 2 0 01-2 2H15v7a2 2 0 01-2 2H9a2 2 0 01-2-2v-7H5.5a2 2 0 01-2-2V9a2 2 0 012-2H7zM9 1v2h6V1H9z"></path>
                    </svg>
                    <span class="mt-2 block text-sm font-medium text-gray-900">Manage Matches</span>
                </a>

                <button onclick="alert('Planning feature coming soon!')" class="relative block w-full rounded-lg border-2 border-dashed border-gray-300 p-6 text-center hover:border-gray-400 focus:outline-none focus:ring-2 focus:ring-water-blue-500 focus:ring-offset-2">
                    <svg class="mx-auto h-8 w-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                    </svg>
                    <span class="mt-2 block text-sm font-medium text-gray-900">Auto Planning</span>
                </button>

                <button onclick="alert('Reports feature coming soon!')" class="relative block w-full rounded-lg border-2 border-dashed border-gray-300 p-6 text-center hover:border-gray-400 focus:outline-none focus:ring-2 focus:ring-water-blue-500 focus:ring-offset-2">
                    <svg class="mx-auto h-8 w-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <span class="mt-2 block text-sm font-medium text-gray-900">Reports</span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function dashboardApp() {
    return {
        init() {
            // Initialize dashboard
            console.log('Dashboard initialized');
        }
    }
}
</script>

<?php
$content = ob_get_clean();
include 'includes/layout.php';
?>
?>

<div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
    <!-- Total Teams -->
    <div class="overflow-hidden rounded-lg bg-white shadow">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-6 w-6 text-water-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Active Teams</dt>
                        <dd class="text-lg font-medium text-gray-900"><?php echo $teamStats['active']; ?></dd>
                    </dl>
                </div>
            </div>
        </div>
        <div class="bg-gray-50 px-5 py-3">
            <div class="text-sm">
                <a href="teams.php" class="font-medium text-water-blue-700 hover:text-water-blue-900">
                    View all teams
                </a>
            </div>
        </div>
    </div>

    <!-- Total Matches -->
    <div class="overflow-hidden rounded-lg bg-white shadow">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Total Matches</dt>
                        <dd class="text-lg font-medium text-gray-900"><?php echo $matchStats['total_matches']; ?></dd>
                    </dl>
                </div>
            </div>
        </div>
        <div class="bg-gray-50 px-5 py-3">
            <div class="text-sm">
                <a href="matches.php" class="font-medium text-water-blue-700 hover:text-water-blue-900">
                    View all matches
                </a>
            </div>
        </div>
    </div>

    <!-- Planned Matches -->
    <div class="overflow-hidden rounded-lg bg-white shadow">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-6 w-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                    </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Planned Matches</dt>
                        <dd class="text-lg font-medium text-gray-900"><?php echo $matchStats['planned_matches']; ?></dd>
                    </dl>
                </div>
            </div>
        </div>
        <div class="bg-gray-50 px-5 py-3">
            <div class="text-sm">
                <a href="schedule.php" class="font-medium text-water-blue-700 hover:text-water-blue-900">
                    View schedule
                </a>
            </div>
        </div>
    </div>

    <!-- Upcoming Matches -->
    <div class="overflow-hidden rounded-lg bg-white shadow">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-6 w-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Upcoming Matches</dt>
                        <dd class="text-lg font-medium text-gray-900"><?php echo $matchStats['upcoming_matches']; ?></dd>
                    </dl>
                </div>
            </div>
        </div>
        <div class="bg-gray-50 px-5 py-3">
            <div class="text-sm">
                <a href="planning.php" class="font-medium text-water-blue-700 hover:text-water-blue-900">
                    Run planning
                </a>
            </div>
        </div>
    </div>
</div>

<div class="mt-8 grid grid-cols-1 gap-8 lg:grid-cols-2">
    <!-- Upcoming Matches -->
    <div class="overflow-hidden rounded-lg bg-white shadow">
        <div class="p-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Upcoming Matches</h3>
            <?php if (empty($upcomingMatches)): ?>
                <p class="text-gray-500 text-sm">No upcoming matches scheduled.</p>
            <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($upcomingMatches as $match): ?>
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <div>
                                <p class="font-medium text-gray-900">
                                    <?php echo htmlspecialchars($match['home_team_name']); ?> vs 
                                    <?php echo htmlspecialchars($match['away_team_name']); ?>
                                </p>
                                <p class="text-sm text-gray-500">
                                    <?php echo date('M j, Y', strtotime($match['date'])); ?> at 
                                    <?php echo date('H:i', strtotime($match['time'])); ?>
                                </p>
                                <?php if ($match['location']): ?>
                                    <p class="text-xs text-gray-400"><?php echo htmlspecialchars($match['location']); ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="text-right">
                                <?php if ($match['assignment_count'] > 0): ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        Planned
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                        Pending
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="mt-4">
                    <a href="matches.php" class="text-sm font-medium text-water-blue-700 hover:text-water-blue-900">
                        View all matches →
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="overflow-hidden rounded-lg bg-white shadow">
        <div class="p-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Quick Actions</h3>
            <div class="space-y-3">
                <a href="teams.php?action=create" class="block w-full text-left px-4 py-3 bg-water-blue-50 border border-water-blue-200 rounded-lg hover:bg-water-blue-100 transition-colors">
                    <div class="flex items-center">
                        <svg class="h-5 w-5 text-water-blue-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        <span class="font-medium text-water-blue-900">Add New Team</span>
                    </div>
                    <p class="text-sm text-water-blue-700 mt-1">Create a new jury team with contact details</p>
                </a>

                <a href="matches.php?action=create" class="block w-full text-left px-4 py-3 bg-green-50 border border-green-200 rounded-lg hover:bg-green-100 transition-colors">
                    <div class="flex items-center">
                        <svg class="h-5 w-5 text-green-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        <span class="font-medium text-green-900">Add New Match</span>
                    </div>
                    <p class="text-sm text-green-700 mt-1">Schedule a new water polo match</p>
                </a>

                <a href="planning.php" class="block w-full text-left px-4 py-3 bg-purple-50 border border-purple-200 rounded-lg hover:bg-purple-100 transition-colors">
                    <div class="flex items-center">
                        <svg class="h-5 w-5 text-purple-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                        </svg>
                        <span class="font-medium text-purple-900">Run Planning Algorithm</span>
                    </div>
                    <p class="text-sm text-purple-700 mt-1">Automatically assign jury teams to matches</p>
                </a>

                <a href="schedule.php" class="block w-full text-left px-4 py-3 bg-yellow-50 border border-yellow-200 rounded-lg hover:bg-yellow-100 transition-colors">
                    <div class="flex items-center">
                        <svg class="h-5 w-5 text-yellow-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <span class="font-medium text-yellow-900">Export Schedule</span>
                    </div>
                    <p class="text-sm text-yellow-700 mt-1">Download schedule in PDF, Excel, or CSV format</p>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Planning Status -->
<div class="mt-8">
    <div class="overflow-hidden rounded-lg bg-white shadow">
        <div class="p-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">System Status</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="text-center p-4 bg-gray-50 rounded-lg">
                    <div class="text-2xl font-bold text-gray-900">
                        <?php echo number_format(($matchStats['planned_matches'] / max($matchStats['total_matches'], 1)) * 100, 1); ?>%
                    </div>
                    <div class="text-sm text-gray-500">Matches Planned</div>
                </div>
                <div class="text-center p-4 bg-gray-50 rounded-lg">
                    <div class="text-2xl font-bold text-gray-900"><?php echo $teamStats['active']; ?></div>
                    <div class="text-sm text-gray-500">Active Teams</div>
                </div>
                <div class="text-center p-4 bg-gray-50 rounded-lg">
                    <div class="text-2xl font-bold text-gray-900"><?php echo $matchStats['upcoming_matches']; ?></div>
                    <div class="text-sm text-gray-500">Upcoming Matches</div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include 'includes/layout.php';
?>
