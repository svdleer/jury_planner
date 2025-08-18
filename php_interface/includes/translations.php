<?php

class Translations {
    private static $translations = [
        'en' => [
            // Navigation
            'dashboard' => 'Dashboard',
            'teams' => 'Teams',
            'matches' => 'Matches',
            'constraints' => 'Constraints',
            'analysis' => 'Analysis',
            'fairness' => 'Fairness',
            'db_test' => 'DB Test',
            
            // Dashboard
            'mnc_jury_planner' => 'MNC Jury Planner',
            'jury_management_dashboard' => 'MNC Jury Management Dashboard',
            'welcome_message' => 'Welcome to the MNC Dordrecht jury planning system. Manage teams, matches, and jury assignments.',
            'jury_teams' => 'Jury Teams',
            'mnc_teams' => 'MNC Teams',
            'home_matches' => 'Home Matches',
            'assigned' => 'Assigned',
            'upcoming_matches' => 'Upcoming Matches (Next 14 Days)',
            'matches_without_jury' => 'Matches Without Jury Assignment',
            'no_upcoming_matches' => 'No upcoming matches found.',
            'all_matches_assigned' => 'All matches have jury assignments!',
            'needs_jury' => 'Needs Jury',
            'view_all_upcoming' => 'View all %d upcoming matches',
            'view_all_unassigned' => 'View all %d unassigned matches',
            'quick_actions' => 'Quick Actions',
            'auto_plan' => 'Auto Plan',
            'test_db' => 'Test DB',
            'advanced_rules' => 'Advanced Rules',
            'smart_assign' => 'Smart Assign',
            'system_information' => 'System Information',
            'database' => 'Database',
            'host' => 'Host',
            'total_all_matches' => 'Total All Matches',
            'competitions' => 'Competitions',
            'classes' => 'Classes',
            'excluded_teams' => 'Excluded Teams',
            
            // Teams
            'team_name' => 'Team Name',
            'weight' => 'Weight',
            'dedicated_to' => 'Dedicated to',
            'notes' => 'Notes',
            'active' => 'Active',
            'actions' => 'Actions',
            'edit' => 'Edit',
            'delete' => 'Delete',
            'no_teams_found' => 'No teams found',
            'add_team' => 'Add Team',
            'edit_team' => 'Edit Team',
            'delete_team' => 'Delete Team',
            'save' => 'Save',
            'cancel' => 'Cancel',
            'confirm_delete' => 'Are you sure you want to delete this team?',
            'team_added_success' => 'Team added successfully',
            'team_updated_success' => 'Team updated successfully',
            'team_deleted_success' => 'Team deleted successfully',
            'error_occurred' => 'An error occurred',
            'teams_management_description' => 'Manage jury teams, their weights, and availability',
            'teams_get_started_message' => 'Get started by creating your first jury team.',
            'status' => 'Status',
            'auto_assignment_planning' => 'Auto Assignment Planning',
            'matches_management' => 'Matches Management',
            'match' => 'Match',
            'lock_status' => 'Lock Status',
            'jury_assignment' => 'Jury Assignment',
            'unassign_all_jury_teams' => 'Unassign All Jury Teams',
            'unassign_all' => 'Unassign All',
            'assignment_constraints' => 'Assignment Constraints',
            'assignment_constraints_description' => 'Manage jury assignment constraints, exclusions, and team capacities',
            'fairness_dashboard' => 'Fairness Dashboard',
            'fairness_dashboard_description' => 'Monitor jury assignment fairness and point distribution',
            
            // Matches
            'date_time' => 'Date/Time',
            'day' => 'Day',
            'competition' => 'Competition',
            'class' => 'Class',
            'home_team' => 'Home Team',
            'away_team' => 'Away Team',
            'location' => 'Location',
            'jury_team' => 'Jury Team',
            'locked' => 'Locked',
            'no_matches_found' => 'No matches found',
            'unassign_all_matches' => 'Unassign All Matches',
            'lock_assignments' => 'Lock Assignments',
            'unlock_assignments' => 'Unlock Assignments',
            'reset_all_assignments' => 'Reset All Assignments',
            'confirm_unassign_all' => 'Are you sure you want to unassign all jury teams?',
            'confirm_lock_assignments' => 'Are you sure you want to lock all assignments?',
            'confirm_unlock_assignments' => 'Are you sure you want to unlock all assignments?',
            'confirm_reset_all' => 'Are you sure you want to reset all assignments?',
            'assign_jury' => 'Assign Jury',
            'unassign' => 'Unassign',
            'yes' => 'Yes',
            'no' => 'No',
            
            // Constraints
            'constraint_name' => 'Constraint Name',
            'type' => 'Type',
            'enabled' => 'Enabled',
            'weight_penalty' => 'Weight/Penalty',
            'description' => 'Description',
            'hard' => 'Hard',
            'soft' => 'Soft',
            'enable' => 'Enable',
            'disable' => 'Disable',
            
            // Common
            'loading' => 'Loading...',
            'search' => 'Search',
            'filter' => 'Filter',
            'all' => 'All',
            'none' => 'None',
            'success' => 'Success',
            'error' => 'Error',
            'warning' => 'Warning',
            'info' => 'Info',
            'close' => 'Close',
            'submit' => 'Submit',
            'reset' => 'Reset',
            'back' => 'Back',
            'next' => 'Next',
            'previous' => 'Previous',
            'total' => 'Total',
            'select' => 'Select',
            'optional' => 'Optional',
            'required' => 'Required',
            
            // Filter labels
            'all_statuses' => 'All Statuses',
            'all_teams' => 'All Teams',
            'all_dates' => 'All Dates',
            'date_range' => 'Date Range',
            'jury_status' => 'Jury Status',
            'partially_assigned' => 'Partially Assigned',
            'unassigned' => 'Unassigned',
            'scheduled' => 'Scheduled',
            'in_progress' => 'In Progress',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            'upcoming' => 'Upcoming',
            'today' => 'Today',
            'this_week' => 'This Week',
            'this_month' => 'This Month',
            'unlocked' => 'Unlocked',
            'clear_filters' => 'Clear Filters',
        ],
        
        'nl' => [
            // Navigation
            'dashboard' => 'Dashboard',
            'teams' => 'Teams',
            'matches' => 'Wedstrijden',
            'constraints' => 'Beperkingen',
            'analysis' => 'Analyse',
            'fairness' => 'Eerlijkheid',
            'db_test' => 'DB Test',
            
            // Dashboard
            'mnc_jury_planner' => 'MNC Jury Planner',
            'jury_management_dashboard' => 'MNC Jury Beheer Dashboard',
            'welcome_message' => 'Welkom bij het MNC Dordrecht jury planning systeem. Beheer teams, wedstrijden en jury toewijzingen.',
            'jury_teams' => 'Jury Teams',
            'mnc_teams' => 'MNC Teams',
            'home_matches' => 'Thuiswedstrijden',
            'assigned' => 'Toegewezen',
            'upcoming_matches' => 'Aankomende Wedstrijden (Komende 14 Dagen)',
            'matches_without_jury' => 'Wedstrijden Zonder Jury Toewijzing',
            'no_upcoming_matches' => 'Geen aankomende wedstrijden gevonden.',
            'all_matches_assigned' => 'Alle wedstrijden hebben jury toewijzingen!',
            'needs_jury' => 'Heeft Jury Nodig',
            'view_all_upcoming' => 'Bekijk alle %d aankomende wedstrijden',
            'view_all_unassigned' => 'Bekijk alle %d niet-toegewezen wedstrijden',
            'quick_actions' => 'Snelle Acties',
            'auto_plan' => 'Auto Planning',
            'test_db' => 'Test DB',
            'advanced_rules' => 'Geavanceerde Regels',
            'smart_assign' => 'Slimme Toewijzing',
            'system_information' => 'Systeeminformatie',
            'database' => 'Database',
            'host' => 'Host',
            'total_all_matches' => 'Totaal Alle Wedstrijden',
            'competitions' => 'Competities',
            'classes' => 'Klassen',
            'excluded_teams' => 'Uitgesloten Teams',
            
            // Teams
            'team_name' => 'Team Naam',
            'weight' => 'Gewicht',
            'dedicated_to' => 'Toegewijd aan',
            'notes' => 'Notities',
            'active' => 'Actief',
            'actions' => 'Acties',
            'edit' => 'Bewerken',
            'delete' => 'Verwijderen',
            'no_teams_found' => 'Geen teams gevonden',
            'add_team' => 'Team Toevoegen',
            'edit_team' => 'Team Bewerken',
            'delete_team' => 'Team Verwijderen',
            'save' => 'Opslaan',
            'cancel' => 'Annuleren',
            'confirm_delete' => 'Weet je zeker dat je dit team wilt verwijderen?',
            'team_added_success' => 'Team succesvol toegevoegd',
            'team_updated_success' => 'Team succesvol bijgewerkt',
            'team_deleted_success' => 'Team succesvol verwijderd',
            'error_occurred' => 'Er is een fout opgetreden',
            'teams_management_description' => 'Beheer jury teams, hun gewichten en beschikbaarheid',
            'teams_get_started_message' => 'Begin met het aanmaken van je eerste jury team.',
            'status' => 'Status',
            'auto_assignment_planning' => 'Automatische Toewijzing Planning',
            'matches_management' => 'Wedstrijden Beheer',
            'match' => 'Wedstrijd',
            'lock_status' => 'Vergrendel Status',
            'jury_assignment' => 'Jury Toewijzing',
            'unassign_all_jury_teams' => 'Alle Jury Teams Ontoewijzen',
            'unassign_all' => 'Alles Ontoewijzen',
            'assignment_constraints' => 'Toewijzing Beperkingen',
            'assignment_constraints_description' => 'Beheer jury toewijzing beperkingen, uitsluitingen en team capaciteiten',
            'fairness_dashboard' => 'Eerlijkheid Dashboard',
            'fairness_dashboard_description' => 'Monitor jury toewijzing eerlijkheid en punt verdeling',
            
            // Matches
            'date_time' => 'Datum/Tijd',
            'day' => 'Dag',
            'competition' => 'Competitie',
            'class' => 'Klasse',
            'home_team' => 'Thuisteam',
            'away_team' => 'Uitteam',
            'location' => 'Locatie',
            'jury_team' => 'Jury Team',
            'locked' => 'Vergrendeld',
            'no_matches_found' => 'Geen wedstrijden gevonden',
            'unassign_all_matches' => 'Alle Wedstrijden Ontoewijzen',
            'lock_assignments' => 'Toewijzingen Vergrendelen',
            'unlock_assignments' => 'Toewijzingen Ontgrendelen',
            'reset_all_assignments' => 'Alle Toewijzingen Resetten',
            'confirm_unassign_all' => 'Weet je zeker dat je alle jury teams wilt ontoewijzen?',
            'confirm_lock_assignments' => 'Weet je zeker dat je alle toewijzingen wilt vergrendelen?',
            'confirm_unlock_assignments' => 'Weet je zeker dat je alle toewijzingen wilt ontgrendelen?',
            'confirm_reset_all' => 'Weet je zeker dat je alle toewijzingen wilt resetten?',
            'assign_jury' => 'Jury Toewijzen',
            'unassign' => 'Ontoewijzen',
            'yes' => 'Ja',
            'no' => 'Nee',
            
            // Constraints
            'constraint_name' => 'Beperking Naam',
            'type' => 'Type',
            'enabled' => 'Ingeschakeld',
            'weight_penalty' => 'Gewicht/Straf',
            'description' => 'Beschrijving',
            'hard' => 'Hard',
            'soft' => 'Zacht',
            'enable' => 'Inschakelen',
            'disable' => 'Uitschakelen',
            
            // Common
            'loading' => 'Laden...',
            'search' => 'Zoeken',
            'filter' => 'Filter',
            'all' => 'Alle',
            'none' => 'Geen',
            'success' => 'Succes',
            'error' => 'Fout',
            'warning' => 'Waarschuwing',
            'info' => 'Info',
            'close' => 'Sluiten',
            'submit' => 'Verzenden',
            'reset' => 'Reset',
            'back' => 'Terug',
            'next' => 'Volgende',
            'previous' => 'Vorige',
            'total' => 'Totaal',
            'select' => 'Selecteer',
            'optional' => 'Optioneel',
            'required' => 'Vereist',
            
            // Filter labels
            'all_statuses' => 'Alle Statussen',
            'all_teams' => 'Alle Teams',
            'all_dates' => 'Alle Datums',
            'date_range' => 'Datumbereik',
            'jury_status' => 'Jury Status',
            'partially_assigned' => 'Gedeeltelijk Toegewezen',
            'unassigned' => 'Niet Toegewezen',
            'scheduled' => 'Gepland',
            'in_progress' => 'Bezig',
            'completed' => 'Voltooid',
            'cancelled' => 'Geannuleerd',
            'upcoming' => 'Aankomend',
            'today' => 'Vandaag',
            'this_week' => 'Deze Week',
            'this_month' => 'Deze Maand',
            'unlocked' => 'Ontgrendeld',
            'clear_filters' => 'Filters Wissen',
        ]
    ];
    
    private static $currentLanguage = 'nl'; // Default to Dutch
    
    public static function setLanguage($lang) {
        if (isset(self::$translations[$lang])) {
            self::$currentLanguage = $lang;
            $_SESSION['language'] = $lang;
        }
    }
    
    public static function getCurrentLanguage() {
        return self::$currentLanguage;
    }
    
    public static function get($key, $params = []) {
        $translation = self::$translations[self::$currentLanguage][$key] ?? 
                      self::$translations['en'][$key] ?? 
                      $key;
        
        // Handle parameters like sprintf
        if (!empty($params)) {
            return vsprintf($translation, $params);
        }
        
        return $translation;
    }
    
    public static function init() {
        // Initialize session if not started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Check for language parameter in URL
        if (isset($_GET['lang'])) {
            self::setLanguage($_GET['lang']);
        } elseif (isset($_SESSION['language'])) {
            self::setLanguage($_SESSION['language']);
        }
    }
    
    public static function getAvailableLanguages() {
        return [
            'nl' => 'Nederlands',
            'en' => 'English'
        ];
    }
}

// Convenience function for translations
function t($key, $params = []) {
    return Translations::get($key, $params);
}

// Initialize translations
Translations::init();
?>
