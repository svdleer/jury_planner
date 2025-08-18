<?php
require_once 'includes/translations.php';
require_once 'config/database.php';
require_once 'includes/TeamManager.php';
require_once 'includes/MatchManager.php';
require_once 'includes/MatchConstraintManager.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    $teamManager = new TeamManager($pdo);
    $matchManager = new MatchManager($pdo);
    $constraintManager = new MatchConstraintManager($pdo);

    $pageTitle = t('analysis');
    $pageDescription = t('analyze_constraints_for_jury_assignments');

    // Get upcoming matches
    $upcomingMatches = [];
    $teams = [];
    $upcomingMatches = $matchManager->getUpcomingMatches(10);
    $teams = $teamManager->getAllTeams();
} catch (Exception $e) {
    $error = "Error loading data: " . $e->getMessage();
}

// Selected match for analysis
$selectedMatchId = $_GET['match_id'] ?? ($upcomingMatches[0]['id'] ?? null);
$selectedMatch = null;
if ($selectedMatchId) {
    foreach ($upcomingMatches as $match) {
        if ($match['id'] == $selectedMatchId) {
            $selectedMatch = $match;
            break;
        }
    }
}

ob_start();
?>

<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <div class="sm:flex sm:items-center sm:justify-between mb-6">
        <div class="min-w-0 flex-1">
            <h2 class="text-2xl font-bold leading-7 text-gray-900"><?php echo t('match_constraint_analysis'); ?></h2>
            <p class="mt-1 text-sm text-gray-500"><?php echo t('analyze_why_teams_can_or_cannot_be_assigned'); ?></p>
        </div>
    </div>

    <?php if (isset($error)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php elseif (empty($upcomingMatches)): ?>
        <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mb-4">
            No upcoming matches found.
        </div>
    <?php else: ?>

    <!-- Match Selection -->
    <div class="bg-white shadow-sm ring-1 ring-gray-900/5 rounded-lg mb-6">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4"><?php echo t('select_match_to_analyze'); ?></h3>
            <form method="GET" class="flex gap-4 items-end">
                <div class="flex-1">
                    <label for="match_id" class="block text-sm font-medium text-gray-700 mb-1">Match</label>
                    <select name="match_id" id="match_id" onchange="this.form.submit()"
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-water-blue-500 focus:ring-water-blue-500 sm:text-sm">
                        <option value="">-- Select a match --</option>
                        <?php foreach ($upcomingMatches as $match): ?>
                            <option value="<?php echo $match['id']; ?>" <?php echo $match['id'] == $selectedMatchId ? 'selected' : ''; ?>>
                                <?php echo date('M j, Y H:i', strtotime($match['date_time'])); ?> - 
                                <?php echo htmlspecialchars($match['home_team']); ?> vs 
                                <?php echo htmlspecialchars($match['away_team']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
        </div>
    </div>

    <?php if ($selectedMatch): ?>
    <!-- Match Details -->
    <div class="bg-white shadow-sm ring-1 ring-gray-900/5 rounded-lg mb-6">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4"><?php echo t('match_details'); ?></h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Date & Time</label>
                    <p class="mt-1 text-sm text-gray-900"><?php echo date('l, F j, Y \a\t H:i', strtotime($selectedMatch['date_time'])); ?></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700"><?php echo t('home_team'); ?></label>
                    <p class="mt-1 text-sm text-gray-900 font-semibold"><?php echo htmlspecialchars($selectedMatch['home_team']); ?></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700"><?php echo t('away_team'); ?></label>
                    <p class="mt-1 text-sm text-gray-900 font-semibold"><?php echo htmlspecialchars($selectedMatch['away_team']); ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Team Constraint Analysis -->
    <div class="bg-white shadow-sm ring-1 ring-gray-900/5 rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4"><?php echo t('team_eligibility_analysis'); ?></h3>
            
            <div class="overflow-x-auto">
                <table class="min-w-full table-auto">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="px-4 py-2 text-left text-sm font-medium text-gray-900">Team</th>
                            <th class="px-4 py-2 text-left text-sm font-medium text-gray-900">Eligibility</th>
                            <th class="px-4 py-2 text-left text-sm font-medium text-gray-900">Score</th>
                            <th class="px-4 py-2 text-left text-sm font-medium text-gray-900">Constraints</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php 
                        $teamAnalysis = [];
                        foreach ($teams as $team) {
                            $violations = $constraintManager->checkMatchConstraints($team['name'], $selectedMatch);
                            $score = $constraintManager->calculateEligibilityScore($team['name'], $selectedMatch, $team['capacity_factor']);
                            
                            $teamAnalysis[] = [
                                'team' => $team,
                                'violations' => $violations,
                                'score' => $score,
                                'eligible' => $score > -1000
                            ];
                        }
                        
                        // Sort by score (best first)
                        usort($teamAnalysis, function($a, $b) {
                            return $b['score'] <=> $a['score'];
                        });
                        
                        foreach ($teamAnalysis as $analysis): 
                        ?>
                            <tr class="<?php echo !$analysis['eligible'] ? 'bg-red-50' : ($analysis['score'] > 50 ? 'bg-green-50' : ''); ?>">
                                <td class="px-4 py-2">
                                    <div class="font-medium text-gray-900"><?php echo htmlspecialchars($analysis['team']['name']); ?></div>
                                    <div class="text-xs text-gray-500">Capacity: <?php echo $analysis['team']['capacity_factor']; ?></div>
                                </td>
                                <td class="px-4 py-2">
                                    <?php if ($analysis['eligible']): ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            Eligible
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                            Ineligible
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-2">
                                    <span class="font-mono text-sm <?php echo $analysis['score'] > 50 ? 'text-green-600' : ($analysis['score'] < 0 ? 'text-red-600' : 'text-gray-600'); ?>">
                                        <?php echo number_format($analysis['score'], 1); ?>
                                    </span>
                                </td>
                                <td class="px-4 py-2">
                                    <?php if (empty($analysis['violations'])): ?>
                                        <span class="text-sm text-gray-500"><?php echo t('no_constraints'); ?></span>
                                    <?php else: ?>
                                        <div class="space-y-1">
                                            <?php foreach ($analysis['violations'] as $violation): ?>
                                                <div class="flex items-center gap-2">
                                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium <?php 
                                                        echo $violation['severity'] === 'HARD' ? 'bg-red-100 text-red-800' : 
                                                             ($violation['severity'] === 'BONUS' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'); 
                                                    ?>">
                                                        <?php echo $violation['severity']; ?>
                                                    </span>
                                                    <span class="text-xs text-gray-600"><?php echo htmlspecialchars($violation['message']); ?></span>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Constraint Types Legend -->
    <div class="mt-6 bg-white shadow-sm ring-1 ring-gray-900/5 rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4"><?php echo t('constraint_types'); ?></h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <?php foreach ($constraintManager->getConstraintTypes() as $type => $info): ?>
                    <div class="border rounded-lg p-3">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium <?php echo $info['severity'] === 'HARD' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                <?php echo $info['severity']; ?>
                            </span>
                            <span class="font-medium text-sm"><?php echo $info['name']; ?></span>
                        </div>
                        <p class="text-xs text-gray-600"><?php echo $info['description']; ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
include 'includes/layout.php';
?>
