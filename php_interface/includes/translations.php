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
            'db_test' => 'Database test',
            
            // Dashboard
            'mnc_jury_planner' => 'MNC jury planner',
            'jury_management_dashboard' => 'MNC jury management dashboard',
            'welcome_message' => 'Welcome to the MNC Dordrecht jury planning system. Manage teams, matches, and jury assignments.',
            'jury_teams' => 'Jury teams',
            'dedicated_teams' => 'Dedicated teams',
            'mnc_teams' => 'MNC teams',
            'home_matches' => 'Home matches',
            'assigned' => 'Assigned',
            'upcoming_matches' => 'Upcoming matches (Next 14 days)',
            'matches_without_jury' => 'Matches without jury',
            'no_upcoming_matches' => 'No upcoming matches found.',
            'all_matches_assigned' => 'All matches have been assigned jury!',
            'needs_jury' => 'Needs jury',
            'view_all_upcoming' => 'View all %d upcoming matches',
            'view_all_unassigned' => 'View all %d unassigned matches',
            'quick_actions' => 'Quick actions',
            'auto_plan' => 'Auto plan',
            'test_db' => 'Test database',
            'advanced_rules' => 'Advanced rules',
            'smart_assign' => 'Smart assign',
            'system_information' => 'System information',
            'database' => 'Database',
            'host' => 'Host',
            'total_all_matches' => 'Total all matches',
            'competitions' => 'Competitions',
            'classes' => 'Classes',
            'excluded_teams' => 'Excluded teams',
            
            // Footer
            'built_with_love_for_waterpolo' => 'Built with ❤️ for Waterpolo',
            
            // Constraint types
            'hard' => 'Hard',
            'soft' => 'Soft',
            
            // Constraint names and descriptions
            'fairness_balance' => 'Fairness & balance',
            'avoid_repeated_first_last_match' => 'Avoid repeated first/last match',
            'avoid_repeated_first_last_description' => 'Avoid assigning the same team repeatedly to the first or last match of the day.',
            'even_season_distribution' => 'Even season distribution',
            'even_season_distribution_description' => 'Spread the total number of officiated matches per team evenly across the season.',
            'historical_point_threshold' => 'Historical point threshold',
            'historical_point_threshold_description' => 'Respect historical point/credit differences; keep point gaps within a threshold (e.g. ≤4 points).',
            
            // Success messages
            'team_created_successfully' => 'Team created successfully!',
            'team_updated_successfully' => 'Team updated successfully!',
            'team_availability_updated' => 'Team availability updated!',
            'match_updated_successfully' => 'Match updated successfully!',
            'jury_team_assigned_successfully' => 'Jury team assigned successfully!',
            'jury_assignment_removed' => 'Jury assignment removed!',
            'match_locked_successfully' => 'Match locked successfully!',
            'match_unlocked_successfully' => 'Match unlocked successfully!',
            'match_assignments_reset_successfully' => 'Match assignments reset successfully!',
            'all_assignments_reset_successfully' => 'All assignments reset successfully!',
            'all_jury_assignments_removed_successfully' => 'All jury assignments removed successfully!',
            'lock_match_confirm' => 'Are you sure you want to lock the match: {0}? This will prevent automatic assignment changes.',
            'unlock_match_confirm' => 'Are you sure you want to unlock the match: {0}? This will allow automatic assignment changes.',
            'reset_match_assignments_confirm' => 'Are you sure you want to reset all jury assignments for match: {0}? This action cannot be undone.',
            
            // Modal and UI texts
            'add_new_team' => 'Add New Team',
            'edit_team_modal' => 'Edit Team',
            'create_team' => 'Create Team',
            'update_team' => 'Update Team',
            'edit_team_h1h2_tooltip' => 'Edit team (dedication is automatic for H1/H2)',
            'edit_team_tooltip' => 'Edit team',
            'special_team_with_automatic_dedication' => 'Special team with automatic dedication',
            'h1h2_special_team' => 'H1/H2 Special Team',
            'h1h2_special_dedication_message' => 'This team is automatically dedicated to both H1 and H2 teams. Dedication cannot be changed.',
            'multiple_dedications_helper' => 'Select teams this jury team is dedicated to (multiple selections allowed)',
            'weight_capacity_helper' => '1.0 = standard capacity, higher values = more assignments',
            
            // Common UI elements
            'open_main_menu' => 'Open main menu',
            'confirm_remove_jury_assignment' => 'Are you sure you want to remove this jury assignment?',
            
            // Error messages
            'adding_matches_disabled' => 'Adding new matches is disabled in production mode.',
            'deleting_matches_disabled' => 'Deleting matches is disabled in production mode.',
            'deleting_teams_disabled' => 'Deleting teams is disabled in production mode.',
            'database_connection_failed' => 'Database connection failed',
            
            // Teams
            'team_name' => 'Team name',
            'weight' => 'Weight',
            'dedicated_to_team' => 'Fixed assigned to team',
            'dedicated_to' => 'Assigned to',
            'dedicated_to' => 'Dedicated to',
            'not_dedicated' => 'Not dedicated to any team',
            'notes' => 'Notes',
            'active' => 'Active',
            'inactive' => 'Inactive',
            'actions' => 'Actions',
            'edit' => 'Edit',
            'delete' => 'Delete',
            'no_teams_found' => 'No teams found',
            'add_team' => 'Add team',
            'edit_team' => 'Edit team',
            'delete_team' => 'Delete team',
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
                        'auto_assignment_planning' => 'Auto assignment planning',
            'auto_assignment_description' => 'Automatically assign jury teams to matches using constraints and optimization',
            'auto_assignment_controls' => 'Auto assignment controls',
            'reset_controls' => 'Reset controls',
            'prefer_teams_fewer_assignments' => 'Prefer teams with fewer assignments',
            'prefer_teams_higher_capacity' => 'Prefer teams with higher capacity',
            'run_auto_assignment' => 'Run auto assignment',
            'reset_controls_description' => 'Use these controls to reset jury assignments. Locked matches will be preserved unless force reset is enabled.',
            'reset_all_assignments' => 'Reset all assignments',
            'team_assignment_status' => 'Team assignment status',
            'match_assignment_overview' => 'Match assignment overview',
            'lock_status_overview' => 'Lock status overview',
            'assignments' => 'assignments',
            'capacity' => 'capacity',
            'total_matches' => 'Total matches',
            'locked_with_assignments' => 'Locked with assignments',
            'matches_list' => 'Matches list',
            'auto_planning' => 'Auto planning',
            'matches_management_description' => 'Manage water polo matches, assign jury teams, and track assignments',
            'matches_overview' => 'Matches overview',
            
            // Constraint analysis
            'matches_management' => 'Matches management',
            'match' => 'Match',
            'lock_status' => 'Lock status',
            'jury_assignment' => 'Jury assignment',
            'unassign_all_jury_teams' => 'Unassign all jury teams',
            'unassign_all' => 'Unassign all',
            'assignment_constraints' => 'Assignment constraints',
            'assignment_constraints_description' => 'Manage jury assignment constraints, exclusions, and team capacities',
            'fairness_dashboard' => 'Fairness dashboard',
            'fairness_dashboard_description' => 'Monitor jury assignment fairness and point distribution',
            'go_competition_scoring' => 'Matches in GO competition series are worth 10 points. Multiple GO matches at the same time count as only one 10-point assignment.',
            'regular_match_scoring' => 'Standard league matches are worth 10 points each.',
            'go_competition' => 'GO competition',
            'regular_match' => 'Regular match',
            
            // Matches
            'date_time' => 'Date/time',
            'day' => 'Day',
            'competition' => 'Competition',
            'class' => 'Class',
            'notes' => 'Notes',
            'home_team' => 'Home team',
            'away_team' => 'Away team',
            'location' => 'Location',
            'jury_team' => 'Jury team',
            'locked' => 'Locked',
            'no_matches_found' => 'No matches found',
            'unassign_all_matches' => 'Unassign all matches',
            'lock_assignments' => 'Lock assignments',
            'unlock_assignments' => 'Unlock assignments',
            'reset_all_assignments' => 'Reset all assignments',
            'confirm_unassign_all' => 'Are you sure you want to unassign all jury teams?',
            'confirm_lock_assignments' => 'Are you sure you want to lock all assignments?',
            'confirm_unlock_assignments' => 'Are you sure you want to unlock all assignments?',
            'confirm_reset_all' => 'Are you sure you want to reset all assignments?',
            'assign_jury' => 'Assign jury',
            'unassign' => 'Unassign',
            'yes' => 'Yes',
            'no' => 'No',
            
            // Constraints
            'constraint_name' => 'Constraint name',
            'type' => 'Type',
            'enabled' => 'Enabled',
            'weight_penalty' => 'Weight/Penalty',
            'description' => 'Description',
            'hard' => 'Hard',
            'soft' => 'Soft',
            'enable' => 'Enable',
            'disable' => 'Disable',
            'set_capacity_description' => 'Set how many assignments each team can handle (1.0 = standard capacity)',
            'capacity_factor' => 'Capacity factor',
            'update_capacity' => 'Update capacity',
            'current_exclusion_constraints' => 'Current exclusion Constraints',
            'error_loading_exclusions' => 'Error loading exclusions',
            'no_exclusion_constraints_defined' => 'No exclusion constraints defined yet.',
            'is_excluded_from_jury_duty' => 'is excluded from jury duty',
            'remove_this_exclusion' => 'Remove this exclusion?',
            'remove' => 'Remove',
            'custom_constraints' => 'Custom constraints',
            'constraint_type' => 'Constraint type',
            'select_constraint_type' => 'Select constraint type',
            'source_team' => 'Source team',
            'target_team' => 'Target team',
            'select_target_team' => 'Select target team',
            'date' => 'Date',
            'value' => 'Value',
            'optional_explanation_constraint' => 'Optional explanation for this constraint',
            'add_constraint' => 'Add constraint',
            'no_custom_constraints_defined' => 'No custom constraints defined yet.',
            'disabled' => 'Disabled',
            'on' => 'on',
            'delete_this_constraint' => 'Delete this constraint?',
            'custom_constraints_available' => 'Custom constraints Available',
            'run_setup_script_constraints' => 'Run the setup script to enable advanced constraint management',
            'select_match' => 'Select a match',
            'team_name' => 'Team name',
            'eligibility' => 'Eligibility',
            'score' => 'Score',
            'constraints' => 'Constraints',
            'capacity' => 'Capacity',
            
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
            
            // Constraints
            'exclude_team_from_jury_duty' => 'Exclude team from Jury duty',
            'select_team_to_exclude' => 'Select team to Exclude',
            'exclude_team' => 'Exclude team',
            'team_capacities' => 'Team capacities',
            'active_constraints' => 'Active constraints',
            'add_new_constraint' => 'Add new Constraint',
            'select_team' => 'Select team',
            'use_form_above_to_exclude' => 'Use the form above to exclude teams from jury duty.',
            'excluded_teams_not_assigned' => 'Excluded teams will not be automatically assigned to matches.',
            'no_upcoming_matches_found' => 'No upcoming matches found.',
            
            // Team exclusion messages
            'team_excluded_successfully' => 'Team \'%s\' excluded successfully.',
            'team_already_excluded' => 'Team \'%s\' is already excluded.',
            'exclusion_removed_successfully' => 'Exclusion removed successfully.',
            'team_capacity_updated_successfully' => 'Team capacity updated successfully.',
            'custom_constraint_added_successfully' => 'Custom constraint added successfully.',
            'error_adding_constraint' => 'Error adding constraint.',
            'constraint_status_updated' => 'Constraint status updated.',
            'constraint_deleted_successfully' => 'Constraint deleted successfully.',
            
            // Advanced constraints page
            'advanced_constraint_configuration' => 'Advanced constraint Configuration',
            'configure_jury_assignment_rules' => 'Configure jury assignment rules, weights, and penalties',
            'back_to_main' => 'Back to Main',
            'bulk_actions' => 'Bulk actions',
            'total_constraints' => 'Total constraints',
            'hard_rules' => 'Hard rules',
            'soft_rules' => 'Soft rules',
            'avg_weight' => 'Avg weight',
            'categories' => 'Categories',
            'enable_selected' => 'Enable selected',
            'disable_selected' => 'Disable selected',
            'constraint_name_label' => 'Constraint name',
            'hard_must_not_violated' => 'Hard (Must not be violated)',
            'soft_may_be_violated' => 'Soft (May be violated with penalty)',
            'penalty_points' => 'Penalty points',
            'penalty' => 'Penalty',
            'enable_this_constraint' => 'Enable this constraint',
            'save_changes' => 'Save changes',
            'code' => 'Code',
            'please_select_constraints_enable' => 'Please select constraints to enable.',
            'please_select_constraints_disable' => 'Please select constraints to disable.',
            'selected_constraints_enabled' => 'Selected constraints enabled!',
            'selected_constraints_disabled' => 'Selected constraints disabled!',
            'error_during_auto_assignment' => 'Error during auto-assignment',
            'error_loading_data' => 'Error loading data',
            'cannot_delete_team_with_assignments' => 'Cannot delete team with existing assignments',
            'database_connection_failed' => 'Database connection failed. Please check your configuration.',
            'team_assigned_successfully' => 'Team assigned successfully!',
            'error_occurred_auto_assignment' => 'An error occurred during auto assignment',
            'error_occurred_validation' => 'An error occurred during validation',
            'error_getting_recommendations' => 'Error getting recommendations',
            'error_occurred_getting_recommendations' => 'An error occurred getting recommendations',
            'error_assigning_team' => 'Error assigning team',
            'error_occurred_during_assignment' => 'An error occurred during assignment',
            'planning_feature_coming_soon' => 'Planning feature coming soon!',
            'reports_feature_coming_soon' => 'Reports feature coming soon!',
            'constraint_updated_successfully' => 'Constraint updated successfully!',
            'failed_to_update_constraint' => 'Failed to update constraint.',
            'hard' => 'Hard',
            'soft' => 'Soft',
            
            // Advanced constraints page
            'advanced_constraint_configuration' => 'Advanced constraint Configuration',
            'configure_jury_assignment_rules' => 'Configure jury assignment rules, weights, and penalties',
            'team_eligibility_analysis' => 'Team eligibility Analysis',
            'match_details' => 'Match details',
            'constraint_types' => 'Constraint types',
            'no_constraints' => 'No constraints',
            'eligible' => 'Eligible',
            'ineligible' => 'Ineligible',
            
            // Filter labels
            'all_statuses' => 'All statuses',
            'all_teams' => 'All teams',
            'all_dates' => 'All dates',
            'date_range' => 'Date range',
            'jury_status' => 'Jury status',
            'partially_assigned' => 'Partially assigned',
            'unassigned' => 'Unassigned',
            'scheduled' => 'Scheduled',
            'in_progress' => 'In progress',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            'upcoming' => 'Upcoming',
            'today' => 'Today',
            'this_week' => 'This week',
            'this_month' => 'This month',
            'unlocked' => 'Unlocked',
            'clear_filters' => 'Clear filters',
            
            // Days of the week
            'monday' => 'Monday',
            'tuesday' => 'Tuesday', 
            'wednesday' => 'Wednesday',
            'thursday' => 'Thursday',
            'friday' => 'Friday',
            'saturday' => 'Saturday',
            'sunday' => 'Sunday',
            
            // Lock/unlock confirmation messages
            'lockMatchConfirm' => 'Are you sure you want to lock the match: {0}? This will prevent changes to jury assignments.',
            'unlockMatchConfirm' => 'Are you sure you want to unlock the match: {0}? This will allow changes to jury assignments.',
            'resetMatchAssignmentsConfirm' => 'Are you sure you want to reset all jury assignments for match: {0}? This action cannot be undone.',
            
            // New translation keys for modal texts
            'unlock_match' => 'Unlock match',
            'lock_match' => 'Lock match',
            'reset_assignments' => 'Reset assignments',
            'assign_jury_team' => 'Assign Jury Team',
            'assigning_jury_for' => 'Assigning jury for',
            'select_jury_team' => 'Select Jury Team',
            'choose_team' => 'Choose a team',
            'assign' => 'Assign',
            'delete_match' => 'Delete Match',
            'delete_match_confirmation' => 'Are you sure you want to delete',
            'action_cannot_be_undone' => 'This action cannot be undone.',
            'reset_all_assignments_description' => 'This will remove all jury assignments from matches. Locked matches will be preserved unless you force reset.',
            'force_reset_locked_matches' => 'Also reset locked matches (force reset)',
            'reset_all' => 'Reset All',
            'unassign_all_jury_teams_description' => 'This will remove all jury team assignments from all matches. Locked matches will be preserved unless you force unassign.',
            'force_unassign_locked_matches' => 'Also unassign locked matches (force unassign)',
            
            // New fairness and constraint analysis keys
            'vs' => 'vs',
            'fairness_score' => 'Fairness Score',
            'point_spread' => 'Point Spread',
            'min_points' => 'Min Points',
            'max_points' => 'Max Points',
            'fairness_recommendations' => 'Fairness Recommendations',
            'team_points_distribution' => 'Team Points Distribution',
            'rank' => 'Rank',
            'team' => 'Team',
            'total_points' => 'Total Points',
            'avg_points_per_match' => 'Avg Points/Match',
            'needs_more' => 'Needs More',
            'above_average' => 'Above Average',
            'balanced' => 'Balanced',
            'point_assignment_rules' => 'Point Assignment Rules',
            'first_and_last_match' => 'First & Last Match',
                        'first_last_match_description' => 'Season opener and finale matches are worth 15 points due to higher importance.',
            
            // Fairness recommendation messages
            'large_point_spread_detected' => 'Large point spread detected ({points} points). Consider prioritizing teams with fewer points.',
            'poor_fairness_score' => 'Poor fairness score ({score}%). Immediate rebalancing recommended.',
            'team_below_average' => 'Team \'{team}\' has {points} points (below average of {average}). Consider prioritizing for next assignments.',
            
            // Constraint Editor
            'constraint_editor' => 'Constraint Editor',
            'constraint_editor_description' => 'Create, edit, and manage custom constraints for jury assignment planning',
            'create_new_constraint' => 'Create New Constraint',
            'existing_constraints' => 'Existing Constraints',
            'no_constraints_found' => 'No constraints found. Create your first constraint to get started.',
            'constraint_name' => 'Constraint Name',
            'rule_type' => 'Rule Type',
            'rule_type_forbidden' => 'Forbidden (Hard Constraint)',
            'rule_type_not_preferred' => 'Not Preferred',
            'rule_type_less_preferred' => 'Less Preferred',
            'rule_type_most_preferred' => 'Most Preferred',
            'weight' => 'Weight',
            'active' => 'Active',
            'inactive' => 'Inactive',
            'parameters' => 'Parameters',
            'deactivate' => 'Deactivate',
            'activate' => 'Activate',
            'edit' => 'Edit',
            'delete' => 'Delete',
            'confirm_delete_constraint' => 'Are you sure you want to delete this constraint?',
            'edit_constraint' => 'Edit Constraint',
            'constraint_type' => 'Constraint Type',
            'select_constraint_type' => 'Select Constraint Type',
            'constraint_parameters' => 'Constraint Parameters',
            'create_constraint' => 'Create Constraint',
            'update_constraint' => 'Update Constraint',
            'cancel' => 'Cancel',
            'team_unavailable' => 'Team Unavailable',
            'avoid_consecutive_matches' => 'Avoid Consecutive Matches',
            'preferred_duty' => 'Preferred Duty',
            'rest_between_matches' => 'Rest Between Matches',
            'max_assignments_per_day' => 'Max Assignments Per Day',
            'time_preference' => 'Time Preference',
            'team' => 'Team',
            'date' => 'Date',
            'reason' => 'Reason',
            'max_consecutive' => 'Max Consecutive',
            'applies_to_all_teams' => 'Applies to All Teams',
            'duty_type' => 'Duty Type',
            'clock_duty' => 'Clock Duty',
            'score_duty' => 'Score Duty',
            'any_duty' => 'Any Duty',
            'min_rest_days' => 'Minimum Rest Days',
            'max_assignments' => 'Max Assignments',
            'preferred_start_time' => 'Preferred Start Time',
            'preferred_end_time' => 'Preferred End Time',
            'update_weight_suggestion' => 'Update weight to suggested value for this rule type?',
            'constraint_created_success' => 'Constraint created successfully',
            'constraint_updated_success' => 'Constraint updated successfully',
            'constraint_deleted_success' => 'Constraint deleted successfully',
            'constraint_toggled_success' => 'Constraint status updated successfully',
            'import_existing_constraints' => 'Import Existing Constraints',
            'confirm_import_constraints' => 'This will import all hardcoded constraints from the system. Continue?',
            'constraints_imported_success' => 'Constraints imported successfully.',
            'constraints_import_failed' => 'Failed to import constraints',
            'imported' => 'imported',
            'skipped' => 'skipped (already exist)',
            'database_migration_required' => 'Database Migration Required',
            'database_migration_description' => 'The constraint editor requires database tables that don\'t exist yet. Please run the migration to create them.',
            'run_migration' => 'Run Migration',
            
            // Common UI elements
            
            // Constraint type names and descriptions
            'wrong_team_dedication' => 'Wrong Team Dedication',
            'wrong_team_dedication_description' => 'Team is dedicated to a specific team but this match doesn\'t involve them',
            'own_match' => 'Own Match',
            'own_match_description' => 'Team cannot jury their own match',
            'away_match_same_day' => 'Away Match Same Day',
            'away_match_same_day_description' => 'Team cannot jury when they have away match same day',
            'consecutive_weekends' => 'Consecutive Weekends',
            'consecutive_weekends_description' => 'Prefer not to assign jury duty on consecutive weekends',
            'recent_assignments' => 'Recent Assignments',
            'recent_assignments_description' => 'Prefer teams with fewer recent assignments (load balancing)',
            'previous_week_assignment' => 'Previous Week Assignment',
            'previous_week_assignment_description' => 'Prefer teams that didn\'t have jury duty in the previous week',
            
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
            'home_match_same_day_bonus' => '{team} has home match vs {opponent} on same day (preferred - already at location)',
            'consecutive_weekends' => '{team} has jury duty on consecutive weekends',
            'recent_assignments' => '{team} has {count} assignments in last 2 weeks',
            'previous_week_assignment' => '{team} had jury duty in the previous week',
        ],

        'nl' => [
            // Navigation
            'dashboard' => 'Dashboard',
            'teams' => 'Teams',
            'matches' => 'Wedstrijden',
            'constraints' => 'Beperkingen',
            'analysis' => 'Analyse',
            'fairness' => 'Eerlijkheid',
            'db_test' => 'Database test',
            
            // Dashboard
            'mnc_jury_planner' => 'MNC jury planner',
            'jury_management_dashboard' => 'MNC jury beheer dashboard',
            'welcome_message' => 'Welkom bij het MNC Dordrecht jury planning systeem. Beheer teams, wedstrijden en jury toewijzingen.',
            'jury_teams' => 'Jury teams',
            'dedicated_teams' => 'Toegewezen teams',
            'mnc_teams' => 'MNC teams',
            'home_matches' => 'Thuiswedstrijden',
            'assigned' => 'Toegewezen',
            'upcoming_matches' => 'Aankomende wedstrijden (komende 14 dagen)',
            'matches_without_jury' => 'Wedstrijden zonder jury',
            'no_upcoming_matches' => 'Geen aankomende wedstrijden gevonden.',
            'all_matches_assigned' => 'Alle wedstrijden hebben jury toegewezen gekregen',
            'needs_jury' => 'Heeft jury nodig',
            'view_all_upcoming' => 'Bekijk alle %d aankomende wedstrijden',
            'view_all_unassigned' => 'Bekijk alle %d niet-toegewezen wedstrijden',
            'quick_actions' => 'Snelle acties',
            'auto_plan' => 'Auto planning',
            'test_db' => 'Test database',
            'advanced_rules' => 'Geavanceerde regels',
            'smart_assign' => 'Slimme toewijzing',
            'system_information' => 'Systeeminformatie',
            'database' => 'Database',
            'host' => 'Host',
            'total_all_matches' => 'Totaal alle Wedstrijden',
            'competitions' => 'Competities',
            'classes' => 'Klassen',
            'excluded_teams' => 'Uitgesloten teams',
            
            // Footer
            'built_with_love_for_waterpolo' => 'Gemaakt met ❤️ voor Waterpolo',
            
            // Teams
            'team_name' => 'Team naam',
            'weight' => 'Gewicht',
            'dedicated_to_team' => 'Vast toegewezen aan team',
            'dedicated_to' => 'Toegewezen aan',
            'dedicated_to' => 'Toegewijd aan',
            'not_dedicated' => 'Niet toegewezen aan een team',
            'notes' => 'Notities',
            'active' => 'Actief',
            'inactive' => 'Inactief',
            'actions' => 'Acties',
            'edit' => 'Bewerken',
            'delete' => 'Verwijderen',
            'no_teams_found' => 'Geen teams gevonden',
            'add_team' => 'Team toevoegen',
            'edit_team' => 'Team bewerken',
            'delete_team' => 'Team verwijderen',
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
                        'auto_assignment_planning' => 'Automatische toewijzing planning',
            'auto_assignment_description' => 'Automatisch jury teams toewijzen aan wedstrijden met behulp van beperkingen en optimalisatie',
            'auto_assignment_controls' => 'Automatische toewijzing controles',
            'reset_controls' => 'Reset controles',
            'prefer_teams_fewer_assignments' => 'Voorkeur voor teams met minder toewijzingen',
            'prefer_teams_higher_capacity' => 'Voorkeur voor teams met hogere capaciteit',
            'run_auto_assignment' => 'Automatische toewijzing uitvoeren',
            'reset_controls_description' => 'Gebruik deze controles om jury toewijzingen te resetten. Vergrendelde wedstrijden worden behouden tenzij force reset wordt ingeschakeld.',
            'reset_all_assignments' => 'Alle toewijzingen resetten',
            'team_assignment_status' => 'Team toewijzing status',
            'match_assignment_overview' => 'Wedstrijd toewijzing overzicht',
            'lock_status_overview' => 'Vergrendel status overzicht',
            'assignments' => 'toewijzingen',
            'capacity' => 'capaciteit',
            'total_matches' => 'Totaal wedstrijden',
            'locked_with_assignments' => 'Vergrendeld met toewijzingen',
            'matches_list' => 'Wedstrijden lijst',
            'auto_planning' => 'Auto planning',
            'matches_management_description' => 'Beheer Waterpolo wedstrijden, wijs jury teams toe en volg toewijzingen',
            'matches_overview' => 'Wedstrijden overzicht',
            
            // Constraint analysis
            'matches_management' => 'Wedstrijden beheer',
            'match' => 'Wedstrijd',
            'lock_status' => 'Vergrendel status',
            'jury_assignment' => 'Jury toewijzing',
            'unassign_all_jury_teams' => 'Alle jury Teams ontoewijzen',
            'unassign_all' => 'Alles ontoewijzen',
            'assignment_constraints' => 'Toewijzing beperkingen',
            'assignment_constraints_description' => 'Beheer jury toewijzing beperkingen, uitsluitingen en team capaciteiten',
            'fairness_dashboard' => 'Eerlijkheid dashboard',
            'fairness_dashboard_description' => 'Monitor jury toewijzing eerlijkheid en punt verdeling',
            'go_competition_scoring' => 'Wedstrijden in GO competitie series zijn 10 punten waard. Meerdere GO wedstrijden op hetzelfde moment tellen als slechts één 10-punten toewijzing.',
            'regular_match_scoring' => 'Standaard competitie wedstrijden zijn elk 10 punten waard.',
            'go_competition' => 'GO Competitie',
            'regular_match' => 'Reguliere wedstrijd',
            
            // Matches
            'date_time' => 'Datum/Tijd',
            'day' => 'Dag',
            'competition' => 'Competitie',
            'class' => 'Klasse',
            'notes' => 'Notities',
            'home_team' => 'Thuisteam',
            'away_team' => 'Uitteam',
            'location' => 'Locatie',
            'jury_team' => 'Jury team',
            'locked' => 'Vergrendeld',
            'no_matches_found' => 'Geen wedstrijden gevonden',
            'unassign_all_matches' => 'Alle wedstrijden Ontoewijzen',
            'lock_assignments' => 'Toewijzingen vergrendelen',
            'unlock_assignments' => 'Toewijzingen ontgrendelen',
            'reset_all_assignments' => 'Alle toewijzingen Resetten',
            'confirm_unassign_all' => 'Weet je zeker dat je alle jury teams wilt ontoewijzen?',
            'confirm_lock_assignments' => 'Weet je zeker dat je alle toewijzingen wilt vergrendelen?',
            'confirm_unlock_assignments' => 'Weet je zeker dat je alle toewijzingen wilt ontgrendelen?',
            'confirm_reset_all' => 'Weet je zeker dat je alle toewijzingen wilt resetten?',
            'assign_jury' => 'Jury toewijzen',
            'unassign' => 'Ontoewijzen',
            'yes' => 'Ja',
            'no' => 'Nee',
            
            // Constraints
            'constraint_name' => 'Beperking naam',
            'type' => 'Type',
            'enabled' => 'Ingeschakeld',
            'weight_penalty' => 'Gewicht/Straf',
            'description' => 'Beschrijving',
            'hard' => 'Hard',
            'soft' => 'Zacht',
            'enable' => 'Inschakelen',
            'disable' => 'Uitschakelen',
            'set_capacity_description' => 'Stel in hoeveel toewijzingen elk team aankan (1.0 = standaard capaciteit)',
            'capacity_factor' => 'Capaciteitsfactor',
            'update_capacity' => 'Capaciteit bijwerken',
            'current_exclusion_constraints' => 'Huidige uitsluitingsbeperkingen',
            'error_loading_exclusions' => 'Fout bij laden uitsluitingen',
            'no_exclusion_constraints_defined' => 'Nog geen uitsluitingsbeperkingen gedefinieerd.',
            'is_excluded_from_jury_duty' => 'is uitgesloten van jury taak',
            'remove_this_exclusion' => 'Deze uitsluiting verwijderen?',
            'remove' => 'Verwijderen',
            'custom_constraints' => 'Aangepaste beperkingen',
            'constraint_type' => 'Beperkingstype',
            'select_constraint_type' => 'Selecteer beperkingstype',
            'source_team' => 'Bronteam',
            'target_team' => 'Doelteam',
            'select_target_team' => 'Selecteer doelteam',
            'date' => 'Datum',
            'value' => 'Waarde',
            'optional_explanation_constraint' => 'Optionele uitleg voor deze beperking',
            'add_constraint' => 'Beperking toevoegen',
            'no_custom_constraints_defined' => 'Nog geen aangepaste beperkingen gedefinieerd.',
            'disabled' => 'Uitgeschakeld',
            'on' => 'op',
            'delete_this_constraint' => 'Deze beperking verwijderen?',
            'custom_constraints_available' => 'Aangepaste beperkingen beschikbaar',
            'run_setup_script_constraints' => 'Voer het setup script uit om geavanceerd beperkingsbeheer in te schakelen',
            'select_match' => 'Selecteer een wedstrijd',
            'team_name' => 'Team naam',
            'eligibility' => 'Geschiktheid',
            'score' => 'Score',
            'constraints' => 'Beperkingen',
            'capacity' => 'Capaciteit',
            
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
            
            // Constraints
            'exclude_team_from_jury_duty' => 'Team uitsluiten van jury taak',
            'select_team_to_exclude' => 'Selecteer team om uit te sluiten',
            'exclude_team' => 'Team uitsluiten',
            'team_capacities' => 'Team capaciteiten',
            'active_constraints' => 'Actieve beperkingen',
            'add_new_constraint' => 'Nieuwe beperking toevoegen',
            'select_team' => 'Selecteer team',
            'use_form_above_to_exclude' => 'Gebruik het formulier hierboven om teams uit te sluiten van jury taak.',
            'excluded_teams_not_assigned' => 'Uitgesloten teams worden niet automatisch toegewezen aan wedstrijden.',
            'no_upcoming_matches_found' => 'Geen aankomende wedstrijden gevonden.',
            
            // Team exclusion messages
            'team_excluded_successfully' => 'Team \'%s\' succesvol uitgesloten.',
            'team_already_excluded' => 'Team \'%s\' is al uitgesloten.',
            'exclusion_removed_successfully' => 'Uitsluiting succesvol verwijderd.',
            'team_capacity_updated_successfully' => 'Team capaciteit succesvol bijgewerkt.',
            'custom_constraint_added_successfully' => 'Aangepaste beperking succesvol toegevoegd.',
            'error_adding_constraint' => 'Fout bij toevoegen beperking.',
            'constraint_status_updated' => 'Beperking status bijgewerkt.',
            'constraint_deleted_successfully' => 'Beperking succesvol verwijderd.',
            
            // Advanced constraints page
            'advanced_constraint_configuration' => 'Geavanceerde beperking Configuratie',
            'configure_jury_assignment_rules' => 'Configureer jury toewijzing regels, gewichten en straffen',
            'back_to_main' => 'Terug naar Hoofdmenu',
            'bulk_actions' => 'Bulk acties',
            'total_constraints' => 'Totaal beperkingen',
            'hard_rules' => 'Harde regels',
            'soft_rules' => 'Zachte regels',
            'avg_weight' => 'Gem. Gewicht',
            'categories' => 'Categorieën',
            'enable_selected' => 'Geselecteerde inschakelen',
            'disable_selected' => 'Geselecteerde uitschakelen',
            'constraint_name_label' => 'Beperking naam',
            'hard_must_not_violated' => 'Hard (Mag niet geschonden worden)',
            'soft_may_be_violated' => 'Zacht (Mag geschonden worden met straf)',
            'penalty_points' => 'Strafpunten',
            'penalty' => 'Straf',
            'enable_this_constraint' => 'Deze beperking inschakelen',
            'save_changes' => 'Wijzigingen opslaan',
            'code' => 'Code',
            'please_select_constraints_enable' => 'Selecteer beperkingen om in te schakelen.',
            'please_select_constraints_disable' => 'Selecteer beperkingen om uit te schakelen.',
            'selected_constraints_enabled' => 'Geselecteerde beperkingen ingeschakeld!',
            'selected_constraints_disabled' => 'Geselecteerde beperkingen uitgeschakeld!',
            'error_during_auto_assignment' => 'Fout tijdens automatische toewijzing',
            'error_loading_data' => 'Fout bij laden van gegevens',
            'cannot_delete_team_with_assignments' => 'Kan team niet verwijderen met bestaande toewijzingen',
            'database_connection_failed' => 'Databaseverbinding mislukt. Controleer uw configuratie.',
            'team_assigned_successfully' => 'Team succesvol toegewezen!',
            'error_occurred_auto_assignment' => 'Er is een fout opgetreden tijdens automatische toewijzing',
            'error_occurred_validation' => 'Er is een fout opgetreden tijdens validatie',
            'error_getting_recommendations' => 'Fout bij ophalen van aanbevelingen',
            'error_occurred_getting_recommendations' => 'Er is een fout opgetreden bij het ophalen van aanbevelingen',
            'error_assigning_team' => 'Fout bij toewijzen van team',
            'error_occurred_during_assignment' => 'Er is een fout opgetreden tijdens toewijzing',
            'planning_feature_coming_soon' => 'Planningsfunctie komt binnenkort!',
            'reports_feature_coming_soon' => 'Rapportagefunctie komt binnenkort!',
            'constraint_updated_successfully' => 'Beperking succesvol bijgewerkt!',
            'failed_to_update_constraint' => 'Fout bij bijwerken beperking.',
            'hard' => 'Hard',
            'soft' => 'Zacht',
            'team_eligibility_analysis' => 'Team geschiktheidsanalyse',
            'match_details' => 'Wedstrijd details',
            'constraint_types' => 'Beperkings types',
            'no_constraints' => 'Geen beperkingen',
            'eligible' => 'Geschikt',
            'ineligible' => 'Niet geschikt',
            
            // Filter labels
            'all_statuses' => 'Alle statussen',
            'all_teams' => 'Alle teams',
            'all_dates' => 'Alle datums',
            'date_range' => 'Datumbereik',
            'jury_status' => 'Jury status',
            'partially_assigned' => 'Gedeeltelijk toegewezen',
            'unassigned' => 'Niet toegewezen',
            'scheduled' => 'Gepland',
            'in_progress' => 'Bezig',
            'completed' => 'Voltooid',
            'cancelled' => 'Geannuleerd',
            'upcoming' => 'Aankomend',
            'today' => 'Vandaag',
            'this_week' => 'Deze week',
            'this_month' => 'Deze maand',
            'unlocked' => 'Ontgrendeld',
            'clear_filters' => 'Filters wissen',
            
            // Days of the week
            'monday' => 'Maandag',
            'tuesday' => 'Dinsdag',
            'wednesday' => 'Woensdag', 
            'thursday' => 'Donderdag',
            'friday' => 'Vrijdag',
            'saturday' => 'Zaterdag',
            'sunday' => 'Zondag',
            
            // Lock/unlock confirmation messages
            'lockMatchConfirm' => 'Weet je zeker dat je de wedstrijd wilt vergrendelen: {0}? Dit voorkomt wijzigingen in jury toewijzingen.',
            'unlockMatchConfirm' => 'Weet je zeker dat je de wedstrijd wilt ontgrendelen: {0}? Dit staat wijzigingen in jury toewijzingen toe.',
            'resetMatchAssignmentsConfirm' => 'Weet je zeker dat je alle jury toewijzingen voor wedstrijd wilt resetten: {0}? Deze actie kan niet ongedaan worden gemaakt.',
            
            // New translation keys for modal texts
            'unlock_match' => 'Wedstrijd ontgrendelen',
            'lock_match' => 'Wedstrijd vergrendelen',
            'reset_assignments' => 'Toewijzingen resetten',
            'assign_jury_team' => 'Jury Team Toewijzen',
            'assigning_jury_for' => 'Jury toewijzen voor',
            'select_jury_team' => 'Selecteer Jury Team',
            'choose_team' => 'Kies een team',
            'assign' => 'Toewijzen',
            'delete_match' => 'Wedstrijd Verwijderen',
            'delete_match_confirmation' => 'Weet je zeker dat je de wedstrijd wilt verwijderen',
            'action_cannot_be_undone' => 'Deze actie kan niet ongedaan worden gemaakt.',
            'reset_all_assignments_description' => 'Dit zal alle jury toewijzingen van wedstrijden verwijderen. Vergrendelde wedstrijden worden behouden tenzij je geforceerd reset.',
            'force_reset_locked_matches' => 'Ook vergrendelde wedstrijden resetten (geforceerd reset)',
            'reset_all' => 'Alles Resetten',
            'unassign_all_jury_teams_description' => 'Dit zal alle jury team toewijzingen van alle wedstrijden verwijderen. Vergrendelde wedstrijden worden behouden tenzij je geforceerd ontoewijst.',
            'force_unassign_locked_matches' => 'Ook vergrendelde wedstrijden ontoewijzen (geforceerd ontoewijzen)',
            
            // New fairness and constraint analysis keys
            'vs' => 'tegen',
            'fairness_score' => 'Eerlijkheid Score',
            'point_spread' => 'Punten Spreiding',
            'min_points' => 'Min Punten',
            'max_points' => 'Max Punten',
            'fairness_recommendations' => 'Eerlijkheid Aanbevelingen',
            'team_points_distribution' => 'Team Punten Verdeling',
            'rank' => 'Rang',
            'team' => 'Team',
            'total_points' => 'Totaal Punten',
            'avg_points_per_match' => 'Gem Punten/Wedstrijd',
            'needs_more' => 'Heeft Meer Nodig',
            'above_average' => 'Boven Gemiddeld',
            'balanced' => 'Gebalanceerd',
            'point_assignment_rules' => 'Punt Toewijzing Regels',
            'first_and_last_match' => 'Eerste & Laatste Wedstrijd',
            'first_last_match_description' => 'Seizoen opener en finale wedstrijden zijn 15 punten waard vanwege hoger belang.',
            
            // Constraint type names and descriptions
            'wrong_team_dedication' => 'Verkeerde Team Toewijzing',
            'wrong_team_dedication_description' => 'Team is toegewezen aan een specifiek team maar deze wedstrijd betreft hen niet',
            'own_match' => 'Eigen Wedstrijd',
            'own_match_description' => 'Team kan niet hun eigen wedstrijd jureren',
            'away_match_same_day' => 'Uitwedstrijd Zelfde Dag',
            'away_match_same_day_description' => 'Team kan niet jureren wanneer ze een uitwedstrijd hebben op dezelfde dag',
            'consecutive_weekends' => 'Opeenvolgende Weekenden',
            'consecutive_weekends_description' => 'Voorkeur om geen jury dienst toe te wijzen op opeenvolgende weekenden',
            'recent_assignments' => 'Recente Toewijzingen',
            'recent_assignments_description' => 'Voorkeur voor teams met minder recente toewijzingen (load balancing)',
            'previous_week_assignment' => 'Vorige Week Toewijzing',
            'previous_week_assignment_description' => 'Voorkeur voor teams die geen jury dienst hadden in de vorige week',
            
            // Fairness recommendation messages
            'large_point_spread_detected' => 'Grote punten spreiding gedetecteerd ({points} punten). Overweeg teams met minder punten te prioriteren.',
            'poor_fairness_score' => 'Slechte eerlijkheid score ({score}%). Onmiddellijke herbalancering aanbevolen.',
            'team_below_average' => 'Team \'{team}\' heeft {points} punten (onder gemiddelde van {average}). Overweeg prioriteit te geven voor volgende toewijzingen.',
            
            // Constraint Editor
            'constraint_editor' => 'Beperkingen Editor',
            'constraint_editor_description' => 'Maak, bewerk en beheer aangepaste beperkingen voor jury toewijzing planning',
            'create_new_constraint' => 'Nieuwe Beperking Maken',
            'existing_constraints' => 'Bestaande Beperkingen',
            'no_constraints_found' => 'Geen beperkingen gevonden. Maak je eerste beperking om te beginnen.',
            'constraint_name' => 'Beperkings Naam',
            'rule_type' => 'Regel Type',
            'rule_type_forbidden' => 'Verboden (Harde Beperking)',
            'rule_type_not_preferred' => 'Niet Gewenst',
            'rule_type_less_preferred' => 'Minder Gewenst',
            'rule_type_most_preferred' => 'Meest Gewenst',
            'weight' => 'Gewicht',
            'active' => 'Actief',
            'inactive' => 'Inactief',
            'parameters' => 'Parameters',
            'deactivate' => 'Deactiveren',
            'activate' => 'Activeren',
            'edit' => 'Bewerken',
            'delete' => 'Verwijderen',
            'confirm_delete_constraint' => 'Weet je zeker dat je deze beperking wilt verwijderen?',
            'edit_constraint' => 'Beperking Bewerken',
            'constraint_type' => 'Beperkings Type',
            'select_constraint_type' => 'Selecteer Beperkings Type',
            'constraint_parameters' => 'Beperkings Parameters',
            'create_constraint' => 'Beperking Maken',
            'update_constraint' => 'Beperking Bijwerken',
            'cancel' => 'Annuleren',
            'team_unavailable' => 'Team Niet Beschikbaar',
            'avoid_consecutive_matches' => 'Vermijd Opeenvolgende Wedstrijden',
            'preferred_duty' => 'Voorkeur Taak',
            'rest_between_matches' => 'Rust Tussen Wedstrijden',
            'max_assignments_per_day' => 'Max Toewijzingen Per Dag',
            'time_preference' => 'Tijd Voorkeur',
            'team' => 'Team',
            'date' => 'Datum',
            'reason' => 'Reden',
            'max_consecutive' => 'Max Opeenvolgend',
            'applies_to_all_teams' => 'Geldt Voor Alle Teams',
            'duty_type' => 'Taak Type',
            'clock_duty' => 'Klok Taak',
            'score_duty' => 'Score Taak',
            'any_duty' => 'Elke Taak',
            'min_rest_days' => 'Minimum Rust Dagen',
            'max_assignments' => 'Max Toewijzingen',
            'preferred_start_time' => 'Voorkeur Start Tijd',
            'preferred_end_time' => 'Voorkeur Eind Tijd',
            'update_weight_suggestion' => 'Gewicht bijwerken naar voorgestelde waarde voor dit regel type?',
            'constraint_created_success' => 'Beperking succesvol aangemaakt',
            'constraint_updated_success' => 'Beperking succesvol bijgewerkt',
            'constraint_deleted_success' => 'Beperking succesvol verwijderd',
            'constraint_toggled_success' => 'Beperkings status succesvol bijgewerkt',
            'import_existing_constraints' => 'Bestaande Beperkingen Importeren',
            'confirm_import_constraints' => 'Dit zal alle hardgecodeerde beperkingen uit het systeem importeren. Doorgaan?',
            'constraints_imported_success' => 'Beperkingen succesvol geïmporteerd.',
            'constraints_import_failed' => 'Importeren van beperkingen mislukt',
            'imported' => 'geïmporteerd',
            'skipped' => 'overgeslagen (bestaan al)',
            'database_migration_required' => 'Database Migratie Vereist',
            'database_migration_description' => 'De beperkingen editor vereist database tabellen die nog niet bestaan. Voer de migratie uit om ze aan te maken.',
            'run_migration' => 'Migratie Uitvoeren',
            
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
            'home_match_same_day_bonus' => '{team} heeft thuiswedstrijd tegen {opponent} op dezelfde dag (voorkeur - al op locatie)',
            'consecutive_weekends' => '{team} heeft jury dienst op opeenvolgende weekenden',
            'recent_assignments' => '{team} heeft {count} toewijzingen in de laatste 2 weken',
            'previous_week_assignment' => '{team} had jury dienst in de vorige week',
            
            // Constraint types
            'hard' => 'Hard',
            'soft' => 'Zacht',
            
            // Constraint names and descriptions
            'fairness_balance' => 'Eerlijkheid & Balans',
            'avoid_repeated_first_last_match' => 'Vermijd herhaalde Eerste/Laatste wedstrijd',
            'avoid_repeated_first_last_description' => 'Vermijd het herhaaldelijk toewijzen van hetzelfde team aan de eerste of laatste wedstrijd van de dag.',
            'even_season_distribution' => 'Gelijkmatige seizoensverdeling',
            'even_season_distribution_description' => 'Spreid het totale aantal geleide wedstrijden per team gelijkmatig over het seizoen.',
            'historical_point_threshold' => 'Historische puntdrempel',
            'historical_point_threshold_description' => 'Respecteer historische punt/krediet verschillen; houd puntenverschillen binnen een drempel (bijv. ≤4 punten).',
            
            // Success messages
            'team_created_successfully' => 'Team succesvol aangemaakt!',
            'team_updated_successfully' => 'Team succesvol bijgewerkt!',
            'team_availability_updated' => 'Team beschikbaarheid bijgewerkt!',
            'match_updated_successfully' => 'Wedstrijd succesvol bijgewerkt!',
            'jury_team_assigned_successfully' => 'Jury team succesvol toegewezen!',
            'jury_assignment_removed' => 'Jury toewijzing verwijderd!',
            'match_locked_successfully' => 'Wedstrijd succesvol vergrendeld!',
            'match_unlocked_successfully' => 'Wedstrijd succesvol ontgrendeld!',
            'match_assignments_reset_successfully' => 'Wedstrijd toewijzingen succesvol gereset!',
            'all_assignments_reset_successfully' => 'Alle toewijzingen succesvol gereset!',
            'all_jury_assignments_removed_successfully' => 'Alle jury toewijzingen succesvol verwijderd!',
            'lock_match_confirm' => 'Weet je zeker dat je de wedstrijd wilt vergrendelen: {0}? Dit voorkomt automatische toewijzingswijzigingen.',
            'unlock_match_confirm' => 'Weet je zeker dat je de wedstrijd wilt ontgrendelen: {0}? Dit staat automatische toewijzingswijzigingen toe.',
            'reset_match_assignments_confirm' => 'Weet je zeker dat je alle jury toewijzingen voor wedstrijd wilt resetten: {0}? Deze actie kan niet ongedaan worden gemaakt.',
            
            // Modal and UI texts
            'add_new_team' => 'Nieuw Team Toevoegen',
            'edit_team_modal' => 'Team Bewerken',
            'create_team' => 'Team Aanmaken',
            'update_team' => 'Team Bijwerken',
            'edit_team_h1h2_tooltip' => 'Team bewerken (toewijzing is automatisch voor H1/H2)',
            'edit_team_tooltip' => 'Team bewerken',
            'special_team_with_automatic_dedication' => 'Speciaal team met automatische toewijzing',
            'h1h2_special_team' => 'H1/H2 Speciaal Team',
            'h1h2_special_dedication_message' => 'Dit team is automatisch toegewezen aan zowel H1 als H2 teams. Toewijzing kan niet worden gewijzigd.',
            'multiple_dedications_helper' => 'Selecteer teams waaraan dit jury team is toegewezen (meerdere selecties toegestaan)',
            'weight_capacity_helper' => '1.0 = standaard capaciteit, hogere waarden = meer toewijzingen',
            
            // Common UI elements
            'open_main_menu' => 'Hoofdmenu openen',
            'confirm_remove_jury_assignment' => 'Weet je zeker dat je deze jury toewijzing wilt verwijderen?',
            
            // Error messages
            'adding_matches_disabled' => 'Nieuwe wedstrijden toevoegen is uitgeschakeld in productie modus.',
            'deleting_matches_disabled' => 'Wedstrijden verwijderen is uitgeschakeld in productie modus.',
            'deleting_teams_disabled' => 'Teams verwijderen is uitgeschakeld in productie modus.',
            'database_connection_failed' => 'Database verbinding mislukt',
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

// Helper function to translate constraint names based on constraint code
function translateConstraintName($constraintName, $constraintCode) {
    $codeToKeyMap = [
        'AVOID_REPEATED_FIRST_LAST' => 'avoid_repeated_first_last_match',
        'EVEN_SEASON_DISTRIBUTION' => 'even_season_distribution', 
        'HISTORICAL_POINT_THRESHOLD' => 'historical_point_threshold',
    ];
    
    // Check if we have a translation for this constraint code
    if (isset($codeToKeyMap[$constraintCode])) {
        return t($codeToKeyMap[$constraintCode]);
    }
    
    // Fallback to original name if no translation found
    return $constraintName;
}

// Helper function to translate constraint descriptions based on constraint code
function translateConstraintDescription($description, $constraintCode) {
    $codeToKeyMap = [
        'AVOID_REPEATED_FIRST_LAST' => 'avoid_repeated_first_last_description',
        'EVEN_SEASON_DISTRIBUTION' => 'even_season_distribution_description',
        'HISTORICAL_POINT_THRESHOLD' => 'historical_point_threshold_description',
    ];
    
    // Check if we have a translation for this constraint code
    if (isset($codeToKeyMap[$constraintCode])) {
        return t($codeToKeyMap[$constraintCode]);
    }
    
    // Fallback to original description if no translation found
    return $description;
}

// Helper function to translate constraint category names
function translateConstraintCategory($categoryName) {
    $categoryMap = [
        'Fairness & Balance' => 'fairness_balance',
    ];
    
    // Check if we have a translation for this category
    if (isset($categoryMap[$categoryName])) {
        return t($categoryMap[$categoryName]);
    }
    
    // Fallback to original category name if no translation found
    return $categoryName;
}

// Helper function to translate day names
function translateDayName($englishDayName) {
    $dayMap = [
        'Monday' => 'monday',
        'Tuesday' => 'tuesday', 
        'Wednesday' => 'wednesday',
        'Thursday' => 'thursday',
        'Friday' => 'friday',
        'Saturday' => 'saturday',
        'Sunday' => 'sunday'
    ];
    
    // Check if we have a translation for this day
    if (isset($dayMap[$englishDayName])) {
        return t($dayMap[$englishDayName]);
    }
    
    // Fallback to original day name if no translation found
    return $englishDayName;
}

// Initialize translations
Translations::init();
?>
