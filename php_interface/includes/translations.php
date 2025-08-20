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
            'dedicated_teams' => 'Fixed Assigned Teams',
            'mnc_teams' => 'MNC Teams',
            'home_matches' => 'Home Matches',
            'assigned' => 'Assigned',
            'upcoming_matches' => 'Upcoming Matches (Next 14 Days)',
            'matches_without_jury' => 'Matches Without Jury',
            'no_upcoming_matches' => 'No upcoming matches found.',
            'all_matches_assigned' => 'All matches have been assigned jury!',
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
            'dedicated_to_team' => 'Fixed Assigned to Team',
            'dedicated_to' => 'Dedicated to',
            'notes' => 'Notes',
            'active' => 'Active',
            'inactive' => 'Inactive',
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
            'auto_assignment_description' => 'Automatically assign jury teams to matches using constraints and optimization',
            'matches_management_description' => 'Manage water polo matches, assign jury teams, and track assignments',
            'matches_overview' => 'Matches Overview',
            
            // Constraint analysis
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
            'go_competition_scoring' => 'Matches in GO competition series are worth 10 points. Multiple GO matches at the same time count as only one 10-point assignment.',
            'regular_match_scoring' => 'Standard league matches are worth 10 points each.',
            'go_competition' => 'GO Competition',
            'regular_match' => 'Regular Match',
            
            // Matches
            'date_time' => 'Date/Time',
            'day' => 'Day',
            'competition' => 'Competition',
            'notes' => 'Notes',
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
            
            // Analysis page
            'match_constraint_analysis' => 'Match constraint analysis',
            'analyze_why_teams_can_or_cannot_be_assigned' => 'Analyze why teams can or cannot be assigned as jury for specific matches',
            'analyze_constraints_for_jury_assignments' => 'Analyze constraints for jury assignments based on team schedules and match conflicts',
            'select_match_to_analyze' => 'Select match to analyze',
            'match_details' => 'Match details',
            'home_team' => 'Home team',
            'away_team' => 'Away team',
            'team_eligibility_analysis' => 'Team eligibility analysis',
            'no_constraints' => 'No constraints',
            'constraint_types' => 'Constraint types',
            
            // Constraint messages
            'dedicated_to_wrong_team' => '{team} is dedicated to {dedicated_team} but this match doesn\'t involve them',
            'cannot_jury_own_match' => '{team} cannot jury their own match',
            'away_match_same_day' => '{team} has away match vs {opponent} on same day',
            'home_match_within_hours' => '{team} has home match vs {opponent} within {hours} hours',
            'home_match_same_day_bonus' => '{team} has home match vs {opponent} on same day (preferred - already at location)',
            'same_pool_conflict' => '{team} is in same pool as match participants',
            'consecutive_weekends' => '{team} has jury duty on consecutive weekends',
            'recent_assignments' => '{team} has {count} assignments in last 2 weeks',
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
            'dedicated_teams' => 'Vast toegewezen teams',
            'mnc_teams' => 'MNC Teams',
            'home_matches' => 'Thuiswedstrijden',
            'assigned' => 'Toegewezen',
            'upcoming_matches' => 'Aankomende Wedstrijden (Komende 14 Dagen)',
            'matches_without_jury' => 'Wedstrijden zonder jury',
            'no_upcoming_matches' => 'Geen aankomende wedstrijden gevonden.',
            'all_matches_assigned' => 'Alle wedstrijden hebben jury toegewezen gekregen',
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
            'dedicated_to_team' => 'Vast toegewezen aan team',
            'dedicated_to' => 'Toegewijd aan',
            'notes' => 'Notities',
            'active' => 'Actief',
            'inactive' => 'Inactief',
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
            'auto_assignment_description' => 'Automatisch jury teams toewijzen aan wedstrijden met behulp van beperkingen en optimalisatie',
            'matches_management_description' => 'Beheer waterpolo wedstrijden, wijs jury teams toe en volg toewijzingen',
            'matches_overview' => 'Wedstrijden Overzicht',
            
            // Constraint analysis
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
            'go_competition_scoring' => 'Wedstrijden in GO competitie series zijn 10 punten waard. Meerdere GO wedstrijden op hetzelfde moment tellen als slechts één 10-punten toewijzing.',
            'regular_match_scoring' => 'Standaard competitie wedstrijden zijn elk 10 punten waard.',
            'go_competition' => 'GO Competitie',
            'regular_match' => 'Reguliere Wedstrijd',
            
            // Matches
            'date_time' => 'Datum/Tijd',
            'day' => 'Dag',
            'competition' => 'Competitie',
            'notes' => 'Notities',
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
            
            // Analysis page  
            'match_constraint_analysis' => 'Wedstrijd beperkingen analyse',
            'analyze_why_teams_can_or_cannot_be_assigned' => 'Analyseer waarom teams wel of niet kunnen worden toegewezen als jury voor specifieke wedstrijden',
            'analyze_constraints_for_jury_assignments' => 'Analyseer beperkingen voor jury toewijzingen gebaseerd op teamschema\'s en wedstrijd conflicten',
            'select_match_to_analyze' => 'Selecteer wedstrijd om te analyseren',
            'match_details' => 'Wedstrijd details',
            'home_team' => 'Thuisteam',
            'away_team' => 'Uitteam',
            'team_eligibility_analysis' => 'Team geschiktheids analyse',
            'no_constraints' => 'Geen beperkingen',
            'constraint_types' => 'Beperkings types',
            
            // Constraint messages
            'dedicated_to_wrong_team' => '{team} is toegewezen aan {dedicated_team} maar deze wedstrijd behelst hen niet',
            'cannot_jury_own_match' => '{team} kan niet hun eigen wedstrijd jureren',
            'away_match_same_day' => '{team} heeft uitwedstrijd tegen {opponent} op dezelfde dag',
            'home_match_within_hours' => '{team} heeft thuiswedstrijd tegen {opponent} binnen {hours} uur',
            'home_match_same_day_bonus' => '{team} heeft thuiswedstrijd tegen {opponent} op dezelfde dag (voorkeur - al op locatie)',
            'same_pool_conflict' => '{team} zit in dezelfde poule als wedstrijd deelnemers',
            'consecutive_weekends' => '{team} heeft jury dienst op opeenvolgende weekenden',
            'recent_assignments' => '{team} heeft {count} toewijzingen in de laatste 2 weken',
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
