<?php
session_start();
require_once 'includes/translations.php';
require_once 'config/database.php';
require_once 'includes/TeamManager.php';

$teamManager = new TeamManager($db);

// Handle form submissions
if ($_POST) {
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'create':
                    $teamManager->createTeam($_POST);
                    $_SESSION['success'] = t('team_created_successfully');
                    header('Location: teams.php');
                    exit;
                    
                case 'update':
                    $teamManager->updateTeam($_POST['id'], $_POST);
                    $_SESSION['success'] = t('team_updated_successfully');
                    header('Location: teams.php');
                    exit;
                    
                case 'delete':
                    // Team deletion disabled for production
                    $_SESSION['error'] = t('deleting_teams_disabled');
                    header('Location: teams.php');
                    exit;
                    
                case 'set_availability':
                    $teamManager->setTeamAvailability(
                        $_POST['team_id'], 
                        $_POST['date'], 
                        $_POST['available'] === '1',
                        $_POST['reason']
                    );
                    $_SESSION['success'] = t('team_availability_updated');
                    header('Location: teams.php?view=availability&id=' . $_POST['team_id']);
                    exit;
            }
        }
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
}

// Get current action and team data
$action = $_GET['action'] ?? 'list';
$teamId = $_GET['id'] ?? null;
$view = $_GET['view'] ?? 'list';

$teams = $teamManager->getAllTeams();
$mncTeams = $teamManager->getAllMncTeams();
$editTeam = null;

if ($teamId && in_array($action, ['edit', 'view'])) {
    $editTeam = $teamManager->getTeamById($teamId);
}

$pageTitle = t('teams');
$pageDescription = t('teams_management_description');

ob_start();
?>

<div x-data="teamsApp()" x-init="init()">
    <!-- Header with actions -->
    <div class="sm:flex sm:items-center sm:justify-between mb-6">
        <div class="min-w-0 flex-1">
            <!-- Page title provided by layout -->
        </div>
        <div class="mt-4 flex sm:ml-4 sm:mt-0">
            <button @click="showCreateModal = true" class="inline-flex items-center rounded-md bg-water-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-water-blue-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-water-blue-600">
                <svg class="-ml-0.5 mr-1.5 h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
                </svg>
                <?php echo t('add_team'); ?>
            </button>
        </div>
    </div>

    <!-- Teams Table -->
    <div class="bg-white shadow-sm ring-1 ring-gray-900/5 rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <?php if (empty($teams)): ?>
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                    <h3 class="mt-2 text-sm font-semibold text-gray-900"><?php echo t('no_teams_found'); ?></h3>
                    <p class="mt-1 text-sm text-gray-500"><?php echo t('teams_get_started_message'); ?></p>
                    <div class="mt-6">
                        <button @click="showCreateModal = true" class="inline-flex items-center rounded-md bg-water-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-water-blue-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-water-blue-600">
                            <svg class="-ml-0.5 mr-1.5 h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
                            </svg>
                            <?php echo t('add_team'); ?>
                        </button>
                    </div>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-300">
                        <thead>
                            <tr>
                                <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-0"><?php echo t('team_name'); ?></th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900"><?php echo t('weight'); ?></th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900"><?php echo t('dedicated_to'); ?></th>
                                <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-0">
                                    <span class="sr-only"><?php echo t('actions'); ?></span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php foreach ($teams as $team): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="whitespace-nowrap py-4 pl-4 pr-3 sm:pl-0">
                                        <div class="flex items-center">
                                            <div class="h-10 w-10 flex-shrink-0">
                                                <div class="h-10 w-10 rounded-full bg-water-blue-100 flex items-center justify-center">
                                                    <span class="text-sm font-medium text-water-blue-700">
                                                        <?php echo strtoupper(substr($team['name'], 0, 2)); ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($team['name']); ?></div>
                                                <?php if ($team['notes']): ?>
                                                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars(substr($team['notes'], 0, 50)) . (strlen($team['notes']) > 50 ? '...' : ''); ?></div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium <?php echo $team['weight'] >= 1.5 ? 'bg-green-100 text-green-800' : ($team['weight'] >= 1.0 ? 'bg-blue-100 text-blue-800' : 'bg-yellow-100 text-yellow-800'); ?>">
                                            <?php echo number_format($team['weight'], 2); ?>
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                        <?php if ($team['dedicated_to_team_name']): ?>
                                            <span class="inline-flex items-center rounded-full bg-orange-100 px-2.5 py-0.5 text-xs font-medium text-orange-800">
                                                <?php echo htmlspecialchars($team['dedicated_to_team_name']); ?>
                                                <?php if ($team['name'] === 'H1/H2'): ?>
                                                    <span class="ml-1 text-xs">(Both teams)</span>
                                                <?php endif; ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-gray-400">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-0">
                                        <div class="flex justify-end space-x-2">
                                            <button @click="editTeam(<?php echo htmlspecialchars(json_encode($team)); ?>)" class="text-water-blue-600 hover:text-water-blue-900">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                </svg>
                                            </button>
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

    <!-- Create/Edit Team Modal -->
    <div x-show="showCreateModal || showEditModal" x-cloak class="relative z-50" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
        <div class="fixed inset-0 z-10 overflow-y-auto">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div class="relative transform overflow-hidden rounded-lg bg-white px-4 pb-4 pt-5 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6">
                    <form method="POST" @submit="submitForm">
                        <input type="hidden" name="action" :value="showEditModal ? 'update' : 'create'">
                        <input x-show="showEditModal" type="hidden" name="id" :value="editingTeam?.id">
                        
                        <div>
                            <div class="mt-3 text-center sm:mt-0 sm:text-left">
                                <h3 class="text-base font-semibold leading-6 text-gray-900" x-text="showEditModal ? 'Edit Team' : 'Add New Team'"></h3>
                                <div class="mt-4 space-y-4">
                                    <div>
                                        <label for="name" class="block text-sm font-medium leading-6 text-gray-900"><?php echo t('team_name'); ?></label>
                                        <input type="text" name="name" id="name" required x-model="editingTeam.name" class="mt-2 block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-water-blue-600 sm:text-sm sm:leading-6">
                                    </div>
                                    
                                    <div>
                                        <label for="weight" class="block text-sm font-medium leading-6 text-gray-900"><?php echo t('weight'); ?></label>
                                        <input type="number" step="0.1" min="0" max="5" name="weight" id="weight" x-model="editingTeam.weight" class="mt-2 block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-water-blue-600 sm:text-sm sm:leading-6">
                                        <p class="mt-1 text-xs text-gray-500">1.0 = standard capacity, higher values = more assignments</p>
                                    </div>
                                    
                                    <div>
                                        <label for="dedicated_to_team_id" class="block text-sm font-medium leading-6 text-gray-900"><?php echo t('dedicated_to'); ?></label>
                                        <select name="dedicated_to_team_id" id="dedicated_to_team_id" x-model="editingTeam.dedicated_to_team_id" class="mt-2 block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-water-blue-600 sm:text-sm sm:leading-6">
                                            <option value=""><?php echo t('not_dedicated'); ?></option>
                                            <?php foreach ($mncTeams as $mncTeam): ?>
                                                <option value="<?php echo $mncTeam['id']; ?>"><?php echo htmlspecialchars($mncTeam['name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <p class="mt-1 text-xs text-gray-500">Select a team this jury team is dedicated to (optional)</p>
                                    </div>
                                    
                                    <div>
                                        <label for="notes" class="block text-sm font-medium leading-6 text-gray-900"><?php echo t('notes'); ?></label>
                                        <textarea name="notes" id="notes" rows="3" x-model="editingTeam.notes" class="mt-2 block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-water-blue-600 sm:text-sm sm:leading-6"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                            <button type="submit" class="inline-flex w-full justify-center rounded-md bg-water-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-water-blue-500 sm:ml-3 sm:w-auto">
                                <span x-text="showEditModal ? 'Update Team' : 'Create Team'"></span>
                            </button>
                            <button @click="closeModals" type="button" class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto"><?php echo t('cancel'); ?></button>
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
                <div class="relative transform overflow-hidden rounded-lg bg-white px-4 pb-4 pt-5 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"></path>
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left">
                            <h3 class="text-base font-semibold leading-6 text-gray-900"><?php echo t('delete_team'); ?></h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500">
                                    Are you sure you want to delete <strong x-text="deleteTeamName"></strong>? This action cannot be undone.
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                        <form method="POST" class="inline">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" :value="deleteTeamId">
                            <button type="submit" class="inline-flex w-full justify-center rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500 sm:ml-3 sm:w-auto"><?php echo t('delete'); ?></button>
                        </form>
                        <button @click="showDeleteModal = false" type="button" class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto"><?php echo t('cancel'); ?></button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function teamsApp() {
    return {
        showCreateModal: false,
        showEditModal: false,
        showDeleteModal: false,
        editingTeam: {
            id: null,
            name: '',
            weight: 1.0,
            dedicated_to_team_id: '',
            notes: ''
        },
        deleteTeamId: null,
        deleteTeamName: '',
        
        init() {
            // Initialize component
        },
        
        editTeam(team) {
            this.editingTeam = { ...team };
            this.showEditModal = true;
        },
        
        deleteTeam(id, name) {
            this.deleteTeamId = id;
            this.deleteTeamName = name;
            this.showDeleteModal = true;
        },
        
        closeModals() {
            this.showCreateModal = false;
            this.showEditModal = false;
            this.editingTeam = {
                id: null,
                name: '',
                weight: 1.0,
                dedicated_to_team_id: '',
                notes: ''
            };
        },
        
        submitForm(event) {
            const form = event.target;
            const formData = new FormData(form);
            
            // Form will submit normally
            return true;
        }
    }
}
</script>

<?php
$content = ob_get_clean();
include 'includes/layout.php';
?>
