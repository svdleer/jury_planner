<?php
session_start();
require_once 'config/database.php';
require_once 'includes/FairnessManager.php';

$fairnessManager = new FairnessManager($db);

$pageTitle = 'Fairness Dashboard';
$pageDescription = 'Monitor jury assignment fairness and point distribution';

// Get fairness report
$report = $fairnessManager->getFairnessReport();
$metrics = $report['metrics'];
$teamDetails = $report['team_details'];
$recommendations = $report['recommendations'];

ob_start();
?>

<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <div class="sm:flex sm:items-center sm:justify-between mb-6">
        <div class="min-w-0 flex-1">
            <h2 class="text-2xl font-bold leading-7 text-gray-900">Fairness Dashboard</h2>
            <p class="mt-1 text-sm text-gray-500">Monitor jury assignment fairness and point distribution across teams</p>
        </div>
    </div>

    <!-- Fairness Metrics -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center">
                        <span class="text-white text-sm font-bold">FS</span>
                    </div>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Fairness Score</p>
                    <p class="text-2xl font-bold text-gray-900"><?php echo round($metrics['fairness_score'], 1); ?>%</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-red-500 rounded-full flex items-center justify-center">
                        <span class="text-white text-sm font-bold">Δ</span>
                    </div>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Point Spread</p>
                    <p class="text-2xl font-bold text-gray-900"><?php echo $metrics['points_difference']; ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center">
                        <span class="text-white text-sm font-bold">↓</span>
                    </div>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Min Points</p>
                    <p class="text-2xl font-bold text-gray-900"><?php echo $metrics['min_points']; ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-orange-500 rounded-full flex items-center justify-center">
                        <span class="text-white text-sm font-bold">↑</span>
                    </div>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Max Points</p>
                    <p class="text-2xl font-bold text-gray-900"><?php echo $metrics['max_points']; ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Recommendations -->
    <?php if (!empty($recommendations)): ?>
    <div class="mb-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Fairness Recommendations</h3>
        <div class="space-y-3">
            <?php foreach ($recommendations as $rec): ?>
                <div class="border-l-4 p-4 <?php 
                    echo $rec['type'] === 'danger' ? 'border-red-400 bg-red-50' : 
                        ($rec['type'] === 'warning' ? 'border-yellow-400 bg-yellow-50' : 'border-blue-400 bg-blue-50'); 
                ?>">
                    <p class="text-sm <?php 
                        echo $rec['type'] === 'danger' ? 'text-red-700' : 
                            ($rec['type'] === 'warning' ? 'text-yellow-700' : 'text-blue-700'); 
                    ?>">
                        <?php echo htmlspecialchars($rec['message']); ?>
                    </p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Team Points Table -->
    <div class="bg-white shadow-sm ring-1 ring-gray-900/5 rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Team Points Distribution</h3>
            
            <div class="overflow-x-auto">
                <table class="min-w-full table-auto">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="px-4 py-2 text-left text-sm font-medium text-gray-900">Rank</th>
                            <th class="px-4 py-2 text-left text-sm font-medium text-gray-900">Team</th>
                            <th class="px-4 py-2 text-left text-sm font-medium text-gray-900">Total Points</th>
                            <th class="px-4 py-2 text-left text-sm font-medium text-gray-900">Assignments</th>
                            <th class="px-4 py-2 text-left text-sm font-medium text-gray-900">Avg Points/Match</th>
                            <th class="px-4 py-2 text-left text-sm font-medium text-gray-900">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($teamDetails as $teamId => $team): ?>
                            <?php 
                            $assignmentCount = count($team['assignments']);
                            $avgPointsPerMatch = $assignmentCount > 0 ? round($team['total_points'] / $assignmentCount, 1) : 0;
                            $isAboveAvg = $team['total_points'] > $metrics['average_points'];
                            $isBelowAvg = $team['total_points'] < $metrics['average_points'] - 5;
                            ?>
                            <tr class="<?php echo $isBelowAvg ? 'bg-red-50' : ($isAboveAvg ? 'bg-green-50' : ''); ?>">
                                <td class="px-4 py-2">
                                    <span class="inline-flex items-center justify-center w-6 h-6 rounded-full text-xs font-bold
                                        <?php echo $team['rank'] === 1 ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800'; ?>">
                                        <?php echo $team['rank']; ?>
                                    </span>
                                </td>
                                <td class="px-4 py-2">
                                    <div class="font-medium text-gray-900"><?php echo htmlspecialchars($team['team_name']); ?></div>
                                </td>
                                <td class="px-4 py-2">
                                    <span class="text-lg font-bold <?php echo $isBelowAvg ? 'text-red-600' : ($isAboveAvg ? 'text-green-600' : 'text-gray-900'); ?>">
                                        <?php echo $team['total_points']; ?>
                                    </span>
                                </td>
                                <td class="px-4 py-2">
                                    <span class="text-sm text-gray-600"><?php echo $assignmentCount; ?> matches</span>
                                </td>
                                <td class="px-4 py-2">
                                    <span class="text-sm text-gray-600"><?php echo $avgPointsPerMatch; ?></span>
                                </td>
                                <td class="px-4 py-2">
                                    <?php if ($isBelowAvg): ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                            Needs More
                                        </span>
                                    <?php elseif ($isAboveAvg): ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            Above Average
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            Balanced
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Point Assignment Rules -->
    <div class="mt-6 bg-white shadow-sm ring-1 ring-gray-900/5 rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Point Assignment Rules</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="border rounded-lg p-4">
                    <div class="flex items-center mb-2">
                        <span class="w-8 h-8 bg-yellow-500 text-white rounded-full flex items-center justify-center text-sm font-bold mr-3">15</span>
                        <span class="font-medium">First & Last Match</span>
                    </div>
                    <p class="text-sm text-gray-600">Season opener and finale matches are worth 15 points due to higher importance.</p>
                </div>
                
                <div class="border rounded-lg p-4">
                    <div class="flex items-center mb-2">
                        <span class="w-8 h-8 bg-blue-500 text-white rounded-full flex items-center justify-center text-sm font-bold mr-3">10</span>
                        <span class="font-medium">GO Competition</span>
                    </div>
                    <p class="text-sm text-gray-600">Matches in GO competition series are worth 10 points.</p>
                </div>
                
                <div class="border rounded-lg p-4">
                    <div class="flex items-center mb-2">
                        <span class="w-8 h-8 bg-green-500 text-white rounded-full flex items-center justify-center text-sm font-bold mr-3">10</span>
                        <span class="font-medium">Regular Match</span>
                    </div>
                    <p class="text-sm text-gray-600">Standard league matches are worth 10 points each.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Water Polo Jury Planner</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 min-h-screen">
    <nav class="bg-blue-600 text-white p-4">
        <div class="container mx-auto flex justify-between items-center">
            <h1 class="text-xl font-bold">MNC Jury Planner</h1>
            <div class="flex items-center space-x-4">
                <a href="mnc_dashboard.php" class="hover:bg-blue-700 px-3 py-2 rounded">
                    <i class="fas fa-home mr-1"></i> Dashboard
                </a>
                <a href="constraints.php" class="hover:bg-blue-700 px-3 py-2 rounded">
                    <i class="fas fa-cog mr-1"></i> Constraints
                </a>
                <a href="fairness.php" class="bg-blue-700 px-3 py-2 rounded">
                    <i class="fas fa-balance-scale mr-1"></i> Fairness
                </a>
            </div>
        </div>
    </nav>
    
    <?php echo $content; ?>
</body>
</html>
