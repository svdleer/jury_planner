import mysql.connector
from mysql.connector import Error
from ortools.sat.python import cp_model
from collections import defaultdict
from datetime import datetime, date, timedelta
from dotenv import load_dotenv, find_dotenv
import os
import sys
import logging
import random

# Set up logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(levelname)s - %(message)s',
    handlers=[
        logging.FileHandler('solver.log'),
        logging.StreamHandler()
    ]
)

class AssignmentDebugCallback(cp_model.CpSolverSolutionCallback):
    def __init__(self, assignment_vars, matches, jury_teams):
        cp_model.CpSolverSolutionCallback.__init__(self)
        self._vars = assignment_vars
        self._matches = matches
        self._jury_teams = jury_teams
        self._team_lookup = {team['team_id']: team['team_name'] for team in jury_teams}
        self._solution_count = 0
        
        # Setup logging
        self.logger = logging.getLogger('solver_callback')
        self.logger.setLevel(logging.INFO)
        
        # Create file handler
        fh = logging.FileHandler('solver.log')
        fh.setLevel(logging.INFO)
        
        # Create formatter
        formatter = logging.Formatter('%(asctime)s - %(levelname)s - %(message)s')
        fh.setFormatter(formatter)
        
        # Add handler to logger
        self.logger.addHandler(fh)

    def log_and_print(self, message):
        print(message)
        self.logger.info(message)

    def on_solution_callback(self):
        self._solution_count += 1
        self.log_and_print(f"\n=== Solution {self._solution_count} ===")
        
        # Group assignments by date for better logging
        assignments_by_date = defaultdict(list)
        
        # Iterate through the matches dictionary
        for date, day_matches in self._matches.items():
            for match in day_matches:
                for team in self._jury_teams:
                    var_key = (match['match_id'], team['team_id'])
                    if var_key in self._vars and self.Value(self._vars[var_key]):
                        assignments_by_date[date].append({
                            'match_id': match['match_id'],
                            'team_id': team['team_id'],
                            'team_name': self._team_lookup[team['team_id']],
                            'home_team': match['home_team'],
                            'away_team': match['away_team'],
                            'time': match.get('time', 'N/A')
                        })

        # Log assignments by date
        for date, assignments in sorted(assignments_by_date.items()):
            self.log_and_print(f"\nDate: {date}")
            for assignment in sorted(assignments, key=lambda x: x['time']):
                self.log_and_print(
                    f"Match {assignment['match_id']}: "
                    f"{assignment['home_team']} vs {assignment['away_team']} "
                    f"- Jury Team {assignment['team_id']} "
                    f"- Team Name {assignment['team_name']}"
                )



# Configuration
max_assignments_per_day = 3

# Load Environment
def load_env_variables():
    load_dotenv(find_dotenv())  # Load environment variables from .env file
    'WP_SOURCE_MYSQL_HOST' == os.getenv('WP_SOURCE_MYSQL_HOST')

    env_vars = {
        'WP_MYSQL_HOST': os.getenv('WP_MYSQL_HOST'),
        'WP_MYSQL_USER': os.getenv('WP_MYSQL_USER'),
        'WP_MYSQL_PASSWORD': os.getenv('WP_MYSQL_PASSWORD'),
        'WP_MYSQL_DATABASE': os.getenv('WP_MYSQL_DATABASE')
    }
    return env_vars


# Database configuration


def get_database_connection(env_vars):
    CONFIG = {
    'host': env_vars['WP_MYSQL_HOST'],
    'user': env_vars['WP_MYSQL_USER'],
    'password': env_vars['WP_MYSQL_PASSWORD'],
    'database': env_vars['WP_MYSQL_DATABASE'],
}
    """Establish a database connection."""
    try:
        connection = mysql.connector.connect(**CONFIG)
        return connection
    except Error as e:
        print(f"Error connecting to MySQL: {e}")
        return None

def get_static_assignments(connection):
    """Retrieve static assignments from the database."""
    try:
        cursor = connection.cursor(dictionary=True)
        query = """
        SELECT home_team, jury_team
        FROM static_assignments
        """
        cursor.execute(query)
        return {row['home_team']: row['jury_team'] for row in cursor.fetchall()}
    except Error as e:
        print(f"Error retrieving static assignments: {e}")
        return {}
    finally:
        if cursor:
            cursor.close()


def fetch_matches(connection, start_date, end_date):
    existing_assignments = fetch_existing_assignments(connection)
        
    cursor = connection.cursor(dictionary=True)
    
    # Fetch home matches
    query = """
    SELECT DATE(date_time) as match_date, date_time, competition, home_team, away_team, match_id
    FROM home_matches
    WHERE date_time BETWEEN %s AND %s
    ORDER BY date_time
    """
    cursor.execute(query, (start_date, end_date))
    home_matches = cursor.fetchall()
    
    # Fetch away matches
    query = """
    SELECT DATE(date_time) as match_date, competition, home_team, away_team, match_id
    FROM all_matches
    WHERE away_team like '%MNC%' AND date_time BETWEEN %s AND %s 
    """
    cursor.execute(query, (start_date, end_date))
    away_matches = cursor.fetchall()
    cursor.close()
    
    # Process home matches
    for match in home_matches:
        match_id = match['match_id']
        if match_id in existing_assignments:
            assignment = existing_assignments[match_id]
            match['assigned_team'] = assignment['team_id']
            match['locked'] = assignment['locked']
            
    # Flatten the lists if they're nested
    if len(home_matches) == 1 and isinstance(home_matches[0], list):
        home_matches = home_matches[0]

    if len(away_matches) == 1 and isinstance(away_matches[0], list):
        away_matches = away_matches[0]        
    
    return home_matches, away_matches

def fetch_existing_assignments(connection):
    try:
        cursor = connection.cursor(dictionary=True)
        query = "SELECT match_id, team_id, locked FROM jury_assignments"
        cursor.execute(query)
        existing_assignments = {str(row['match_id']): row for row in cursor.fetchall()}
        return existing_assignments
    except Error as e:
        print(f"Error fetching existing assignments: {e}")
        return {}
    finally:
        if cursor:
            cursor.close()

def get_jury_teams(connection):
    """Fetch available jury teams."""
    cursor = connection.cursor(dictionary=True)
    query = "SELECT id AS team_id, name AS team_name FROM jury_teams"
    cursor.execute(query)
    jury_teams = cursor.fetchall()
    cursor.close()
    return jury_teams

def insert_assignments_to_database(assignments, connection):
    try:
        cursor = connection.cursor(dictionary=True)
        
        # Fetch existing assignments with their locked status
        cursor.execute("SELECT match_id, locked FROM jury_assignments")
        db_match_data = {}
        for row in cursor.fetchall():
            db_match_data[row['match_id']] = {
                       'locked': row['locked']
            }

        print("Processed match data:", db_match_data)

        sql = """REPLACE INTO jury_assignments (match_id, team_id, locked)
                 VALUES (%s, %s, %s)"""
        
        data = []
        locked_count = 0
        unlocked_count = 0
        
        for assignment in assignments:
            try:
                match_id = int(assignment['match_id'])
                team_id = assignment['team_id']
                
                if match_id in db_match_data:
                    is_locked = db_match_data[match_id]['locked']   
                    print(f"Found lock status for match {match_id}: {is_locked}")
                    
                    if not is_locked:  # If locked = 0 (False), we can update
                        unlocked_count += 1
                        print(f"Updating assignment for match {match_id}")
                        data.append((match_id, team_id, 1))  # Store as 1 in database for locked
                    else:
                        locked_count += 1
                        print(f"Skipping match {match_id} as it is locked")
                else:
                    # Handle non-existent match as unlocked
                    print(f"No database entry found for match {match_id} - treating as unlocked")
                    unlocked_count += 1
                    print(f"Updating assignment for match {match_id}")
                    data.append((match_id, team_id, 1))  # Store as 1 in database for locked

            except KeyError as ke:
                print(f"Missing required key in assignment: {ke}")
                continue

        if data:
            cursor.executemany(sql, data)
            connection.commit()
            print(f"{cursor.rowcount} assignments were inserted/updated successfully.")
        else:
            print("No assignments were inserted/updated.")

        print(f"Locked matches: {locked_count}")
        print(f"Unlocked matches: {unlocked_count}")
        
    except Error as e:
        print(f"Error: {e}")
        connection.rollback()  # Rollback on error
        
    finally:
        if 'cursor' in locals() and cursor is not None:
            cursor.close()

def group_matches_by_day(matches):
    grouped = {}
    for match in matches:
        if not isinstance(match, dict):
            print(f"Unexpected match format: {match}")
            continue
        day = match['match_date']
        if day not in grouped:
            grouped[day] = []
        grouped[day].append(match)
    return grouped

def group_matches_by_weekend(matches):
    weekend_matches = {}
    for match in matches:
        date = match['date_time'].date()  
        if date.weekday() >= 5:  
            if date not in weekend_matches:
                weekend_matches[date] = []
            weekend_matches[date].append(match)
    return weekend_matches

def get_playing_teams(home_matches):
    playing_teams_by_date = {}
    for match in home_matches:
        date = match['match_date']
        home_team = match['home_team']
        if date not in playing_teams_by_date:
            playing_teams_by_date[date] = set()
        playing_teams_by_date[date].add(home_team)
    return playing_teams_by_date

def get_last_match_of_day(day_matches):
    return max(day_matches, key=lambda match: match['date_time'])

def add_one_team_per_match_constraint(model, assignment_vars, home_matches, jury_teams, static_match_ids):
    for match in home_matches:
        match_id = match['match_id']
        if match_id not in static_match_ids:
            model.Add(sum(assignment_vars[(match_id, team['team_id'])] 
                for team in jury_teams 
                if (match_id, team['team_id']) in assignment_vars) == 1)

def add_forbid_2nd_assignment_constraint(model, assignment_vars, matches, jury_teams):
    logging.info("Starting to add forbid 2nd assignment constraint")
    
    # Group matches by day
    matches_by_day = group_matches_by_day(matches)
    logging.debug(f"Matches grouped into {len(matches_by_day)} days")
    
    for day, day_matches in matches_by_day.items():
        sorted_day_matches = sorted(day_matches, key=lambda x: x['date_time'])
        logging.debug(f"Processing day {day} with {len(sorted_day_matches)} matches")
        
        for team in jury_teams:
            team_id = team['team_id']
            if team_id != 99:  # Skip team 99 (static assignments)
                logging.debug(f"Processing team {team_id}")
                for i in range(len(sorted_day_matches)):
                    current_match = sorted_day_matches[i]
                    current_assignment = assignment_vars[(current_match['match_id'], team_id)]
                    
                    if len(sorted_day_matches) > 1:
                        if i == 0:
                            # First match of the day
                            next_assignment = assignment_vars[(sorted_day_matches[i + 1]['match_id'], team_id)]
                            model.Add(current_assignment <= next_assignment)
                            logging.debug(f"Added first match constraint for team {team_id}, match {current_match['match_id']}")
                        elif i == len(sorted_day_matches) - 1:
                            # Last match of the day
                            prev_assignment = assignment_vars[(sorted_day_matches[i - 1]['match_id'], team_id)]
                            model.Add(current_assignment <= prev_assignment)
                            logging.debug(f"Added last match constraint for team {team_id}, match {current_match['match_id']}")
                        else:
                            # Middle matches of the day
                            prev_assignment = assignment_vars[(sorted_day_matches[i - 1]['match_id'], team_id)]
                            next_assignment = assignment_vars[(sorted_day_matches[i + 1]['match_id'], team_id)]
                            model.Add(current_assignment <= prev_assignment + next_assignment)
                            logging.debug(f"Added middle match constraint for team {team_id}, match {current_match['match_id']}")
    
    logging.info('Consecutive assignments constraint added')
    return model


def add_consecutive_matches_constraint(model, assignment_vars, matches, grouped_matches, jury_teams, team_preferences, weight=1):
    logging.info("Starting to add consecutive matches constraint")
    penalty_vars = []
    matches_by_day = []
    
    # Group matches by day
    matches_by_day = defaultdict(list)
    for match in matches:
        day = match['date_time'].date()
        matches_by_day[day].append(match)
    logging.debug(f"Grouped matches into {len(matches_by_day)} days")
    
    for day, day_matches in matches_by_day.items():
        sorted_day_matches = sorted(day_matches, key=lambda x: x['date_time'])
        logging.debug(f"Processing day {day} with {len(sorted_day_matches)} matches")
        
        for team in jury_teams:
            team_id = team['team_id']
            logging.debug(f"Processing team {team_id}")
            
            if len(sorted_day_matches) >= 4:
                logging.debug(f"Day {day} has {len(sorted_day_matches)} matches (≥4): processing groups of 2 and single assignments")
                # For 4 or more matches, encourage groups of 2 and discourage single assignments
                for i in range(len(sorted_day_matches) - 1):
                    two_consecutive = model.NewBoolVar(f'two_consecutive_{team_id}_{day}_{i}')
                    consecutive_sum = sum(assignment_vars[(match['match_id'], team_id)] for match in sorted_day_matches[i:i+2])
                    
                    # If two_consecutive is true, ensure exactly 2 consecutive matches are assigned
                    model.Add(consecutive_sum == 2).OnlyEnforceIf(two_consecutive)
                    
                    # Add a reward (negative penalty) for having 2 consecutive matches
                    penalty_vars.append(model.NewIntVar(-1, 0, f'reward_two_consecutive_{team_id}_{day}_{i}'))
                    model.Add(penalty_vars[-1] == -1).OnlyEnforceIf(two_consecutive)
                    model.Add(penalty_vars[-1] == 0).OnlyEnforceIf(two_consecutive.Not())
                
                # Discourage single match assignments
                for i in range(len(sorted_day_matches)):
                    single_match = model.NewBoolVar(f'single_match_{team_id}_{day}_{i}')
                    if i == 0:
                        model.Add(assignment_vars[(sorted_day_matches[i]['match_id'], team_id)] == 1).OnlyEnforceIf(single_match)
                        model.Add(assignment_vars[(sorted_day_matches[i+1]['match_id'], team_id)] == 0).OnlyEnforceIf(single_match)
                    elif i == len(sorted_day_matches) - 1:
                        model.Add(assignment_vars[(sorted_day_matches[i]['match_id'], team_id)] == 1).OnlyEnforceIf(single_match)
                        model.Add(assignment_vars[(sorted_day_matches[i-1]['match_id'], team_id)] == 0).OnlyEnforceIf(single_match)
                    else:
                        model.Add(assignment_vars[(sorted_day_matches[i]['match_id'], team_id)] == 1).OnlyEnforceIf(single_match)
                        model.Add(assignment_vars[(sorted_day_matches[i-1]['match_id'], team_id)] == 0).OnlyEnforceIf(single_match)
                        model.Add(assignment_vars[(sorted_day_matches[i+1]['match_id'], team_id)] == 0).OnlyEnforceIf(single_match)
                    
                    # Add a penalty for single match assignments
                    penalty_vars.append(model.NewIntVar(0, 2, f'penalty_single_match_{team_id}_{day}_{i}'))
                    model.Add(penalty_vars[-1] == 2).OnlyEnforceIf(single_match)
                    model.Add(penalty_vars[-1] == 0).OnlyEnforceIf(single_match.Not())
                
                # Allow groups of 3 only to prevent single assignments
                logging.debug(f"Processing groups of 3 for day {day}")
                for i in range(len(sorted_day_matches) - 2):
                    three_consecutive = model.NewBoolVar(f'three_consecutive_{team_id}_{day}_{i}')
                    consecutive_sum = sum(assignment_vars[(match['match_id'], team_id)] for match in sorted_day_matches[i:i+3])
                    
                    model.Add(consecutive_sum == 3).OnlyEnforceIf(three_consecutive)
                    
                    # Add a small reward for groups of 3 (less than groups of 2)
                    penalty_vars.append(model.NewIntVar(-1, 0, f'reward_three_consecutive_{team_id}_{day}_{i}'))
                    model.Add(penalty_vars[-1] == -1).OnlyEnforceIf(three_consecutive)
                    model.Add(penalty_vars[-1] == 0).OnlyEnforceIf(three_consecutive.Not())
            
            elif len(sorted_day_matches) == 2:
                logging.debug(f"Day {day} has exactly 2 matches: encouraging both assignments")
                # Encourage assigning both matches
                two_matches = model.NewBoolVar(f'two_matches_{team_id}_{day}')
                day_sum = sum(assignment_vars[(match['match_id'], team_id)] for match in sorted_day_matches)
                model.Add(day_sum == 2).OnlyEnforceIf(two_matches)
                
                penalty_vars.append(model.NewIntVar(-1, 0, f'reward_two_matches_{team_id}_{day}'))
                model.Add(penalty_vars[-1] == -1).OnlyEnforceIf(two_matches)
                model.Add(penalty_vars[-1] == 0).OnlyEnforceIf(two_matches.Not())
            
            elif len(sorted_day_matches) == 3:
                logging.debug(f"Day {day} has exactly 3 matches: encouraging 2 or 3 assignments")
                # Encourage assigning either 2 or 3 matches
                two_or_three_matches = model.NewBoolVar(f'two_or_three_matches_{team_id}_{day}')
                day_sum = sum(assignment_vars[(match['match_id'], team_id)] for match in sorted_day_matches)
                model.Add(day_sum >= 2).OnlyEnforceIf(two_or_three_matches)
                
                penalty_vars.append(model.NewIntVar(-1, 0, f'reward_two_or_three_matches_{team_id}_{day}'))
                model.Add(penalty_vars[-1] == -1).OnlyEnforceIf(two_or_three_matches)
                model.Add(penalty_vars[-1] == 0).OnlyEnforceIf(two_or_three_matches.Not())

    logging.info(f"Finished adding consecutive matches constraint with {len(penalty_vars)} penalty variables")
    return penalty_vars

def add_maximum_assignments_per_day_constraint(model, assignment_vars, matches, jury_teams, max_assignments_per_day, static_assignments):
    matches_by_day = group_matches_by_day(matches)
    
    for day, day_matches in matches_by_day.items():
        for team in jury_teams:
            team_id = team['team_id']
            if team_id != 99:  # Skip team 99 (static assignments)
                day_assignments = []
                go_assignments = []
                for match in day_matches:
                    match_id = match['match_id']
                    competition = match.get('competition', '').lower().strip()
                    is_go_match = 'go' in competition
                    if (match_id, team_id) in assignment_vars:
                        day_assignments.append(assignment_vars[(match_id, team_id)])
                        if is_go_match:
                            go_assignments.append(assignment_vars[(match_id, team_id)])
                    elif isinstance(static_assignments, dict) and str(match_id) in static_assignments and static_assignments[str(match_id)] == team_id:
                        day_assignments.append(1)  # Count static assignment
                        if is_go_match:
                            go_assignments.append(1)
                    elif isinstance(static_assignments, list) and any(sa['match_id'] == match_id and sa['team_id'] == team_id for sa in static_assignments):
                        day_assignments.append(1)  # Count static assignment
                        if is_go_match:
                            go_assignments.append(1)
                
                if day_assignments:
                    # Create a boolean variable for when exactly 4 GO matches are assigned
                    four_go_matches = model.NewBoolVar(f"four_go_matches_{team_id}_{day}")
                    model.Add(sum(go_assignments) == 4).OnlyEnforceIf(four_go_matches)
                    model.Add(sum(go_assignments) != 4).OnlyEnforceIf(four_go_matches.Not())

                    # Allow 4 assignments if 4 GO matches are assigned
                    model.Add(sum(day_assignments) <= 4).OnlyEnforceIf(four_go_matches)

                    # For non-4-GO-match cases, use max_assignments_per_day
                    model.Add(sum(day_assignments) <= max_assignments_per_day).OnlyEnforceIf(four_go_matches.Not())

                    # Check for odd number of matches and exactly 2 GO assignments
                    if len(day_matches) % 2 == 1:
                        two_go_matches = model.NewBoolVar(f"two_go_matches_{team_id}_{day}")
                        model.Add(sum(go_assignments) == 2).OnlyEnforceIf(two_go_matches)
                        model.Add(sum(go_assignments) != 2).OnlyEnforceIf(two_go_matches.Not())

                        # Allow max_assignments_per_day + 1 if there are exactly 2 GO assignments
                        model.Add(sum(day_assignments) <= max_assignments_per_day + 1).OnlyEnforceIf(two_go_matches)

    # Add cross-day constraints to prevent assignments on consecutive days
    days = sorted(matches_by_day.keys())
    for day_index, day in enumerate(days):
        matches = matches_by_day[day]
        if day_index > 0:
            prev_day = days[day_index - 1]
            prev_day_matches = matches_by_day[prev_day]
            for team in jury_teams:
                team_id = team['team_id']
                if team_id != 99:  # Skip team 99 (static assignments)
                    model.Add(assignment_vars[(matches[0]['match_id'], team_id)] + 
                              assignment_vars[(prev_day_matches[-1]['match_id'], team_id)] <= 1)



def add_no_assignment_for_away_teams_constraint(model, assignment_vars, home_matches, away_matches, jury_teams):
    away_teams_by_day = group_matches_by_day(away_matches)
    home_matches_by_day = group_matches_by_day(home_matches)

    for day, day_away_matches in away_teams_by_day.items():
        if day in home_matches_by_day:
            day_home_matches = home_matches_by_day[day]
            for away_match in day_away_matches:
                away_team_name = away_match['away_team']
                for jury_team in jury_teams:
                    if jury_team['team_name'] == away_team_name:
                        # This jury team has an away match on this day
                        for home_match in day_home_matches:
                            model.Add(assignment_vars[(home_match['match_id'], jury_team['team_id'])] == 0)

    # No violations are returned as these are hard constraints
    return []





def add_go_matches_constraint(model, assignment_vars, matches, jury_teams):
    go_matches = [m for m in matches if 'go' in m['competition'].lower()]
    other_matches = [m for m in matches if 'go' not in m['competition'].lower()]
    other_matches.sort(key=lambda x: x['date_time'])

    all_matches = go_matches + other_matches

    available_teams = set(team['team_id'] for team in jury_teams if team['team_id'] != 99)
    if len(go_matches) == 2:
        # Existing constraints for 3 and 4 GO matches
        if len(go_matches) == 2 and (go_matches[0]['date_time'] == go_matches[1]['date_time']):
            for team in jury_teams:
                team_id = team['team_id']
                model.Add(assignment_vars[(go_matches[0]['match_id'], team_id)] ==
                          assignment_vars[(go_matches[1]['match_id'], team_id)])
        elif len(go_matches) == 2 and (go_matches[0]['date_time'] != go_matches[1]['date_time']):
            for team in jury_teams:
                team_id = team['team_id']
                model.Add(assignment_vars[(go_matches[0]['match_id'], team_id)] ==
                          assignment_vars[(go_matches[1]['match_id'], team_id)])

    elif len(go_matches) == 3:
        if len(go_matches) == 3 and (go_matches[0]['date_time'] == go_matches[1]['date_time'] or 
                                     go_matches[1]['date_time'] == go_matches[2]['date_time']):
            for team in jury_teams:
                team_id = team['team_id']
                model.Add(assignment_vars[(go_matches[0]['match_id'], team_id)] ==
                          assignment_vars[(go_matches[1]['match_id'], team_id)])
                model.Add(assignment_vars[(go_matches[1]['match_id'], team_id)] ==
                          assignment_vars[(go_matches[2]['match_id'], team_id)])

    elif len(go_matches) == 4:
        if len(go_matches) == 4 and (go_matches[0]['date_time'] == go_matches[1]['date_time'] and 
                                     go_matches[2]['date_time'] == go_matches[3]['date_time']):
            for team in jury_teams:
                team_id = team['team_id']
                model.Add(assignment_vars[(go_matches[0]['match_id'], team_id)] ==
                          assignment_vars[(go_matches[1]['match_id'], team_id)])
                model.Add(assignment_vars[(go_matches[1]['match_id'], team_id)] ==
                          assignment_vars[(go_matches[2]['match_id'], team_id)])
                model.Add(assignment_vars[(go_matches[2]['match_id'], team_id)] ==
                          assignment_vars[(go_matches[3]['match_id'], team_id)])
        
        elif len(go_matches) == 4 and (go_matches[0]['date_time'] == go_matches[1]['date_time'] and 
                                       go_matches[2]['date_time'] != go_matches[3]['date_time']):
            for team in jury_teams:
                team_id = team['team_id']
                model.Add(assignment_vars[(go_matches[0]['match_id'], team_id)] ==
                          assignment_vars[(go_matches[1]['match_id'], team_id)])
                model.Add(assignment_vars[(go_matches[1]['match_id'], team_id)] ==
                          assignment_vars[(go_matches[2]['match_id'], team_id)])
    
                model.Add(sum(assignment_vars[(go_matches[0]['match_id'], team['team_id'])] for team in jury_teams) ==
                          sum(assignment_vars[(go_matches[1]['match_id'], team['team_id'])] for team in jury_teams))
                model.Add(sum(assignment_vars[(go_matches[0]['match_id'], team['team_id'])] * team['team_id'] for team in jury_teams) !=
                          sum(assignment_vars[(go_matches[3]['match_id'], team['team_id'])] * team['team_id'] for team in jury_teams))

    
    elif len(go_matches) >= 5:
        if len(go_matches) >= 5 and (go_matches[0]['date_time'] == go_matches[1]['date_time'] and 
                                     go_matches[2]['date_time'] != go_matches[3]['date_time'] or
                                   
                                     go_matches[0]['date_time'] != go_matches[1]['date_time'] and
                                     go_matches[2]['date_time'] == go_matches[3]['date_time'] or
                                     go_matches[0]['date_time'] != go_matches[1]['date_time'] and
                                     go_matches[1]['date_time'] == go_matches[2]['date_time']):
            for team in jury_teams:
                team_id = team['team_id']
                model.Add(assignment_vars[(go_matches[0]['match_id'], team_id)] ==
                          assignment_vars[(go_matches[1]['match_id'], team_id)])
                model.Add(assignment_vars[(go_matches[1]['match_id'], team_id)] ==
                          assignment_vars[(go_matches[2]['match_id'], team_id)])
                model.Add(assignment_vars[(go_matches[1]['match_id'], team_id)] ==
                          assignment_vars[(go_matches[3]['match_id'], team_id)])
                   

                #model.Add(sum(assignment_vars[(go_matches[0]['match_id'], team['team_id'])] for team in jury_teams) ==
                #          sum(assignment_vars[(go_matches[3]['match_id'], team['team_id'])] for team in jury_teams))
                #model.Add(sum(assignment_vars[(go_matches[0]['match_id'], team['team_id'])] * team['team_id'] for team in jury_teams) !=
                #          sum(assignment_vars[(go_matches[4]['match_id'], team['team_id'])] * team['team_id'] for team in jury_teams))
        elif len(go_matches) >= 5 and (go_matches[0]['date_time'] == go_matches[1]['date_time'] and 
                                       go_matches[2]['date_time'] == go_matches[3]['date_time']):
                          
            for team in jury_teams:
                team_id = team['team_id']
                model.Add(assignment_vars[(go_matches[0]['match_id'], team_id)] ==
                          assignment_vars[(go_matches[1]['match_id'], team_id)])
                model.Add(assignment_vars[(go_matches[1]['match_id'], team_id)] ==
                          assignment_vars[(go_matches[2]['match_id'], team_id)])
                model.Add(assignment_vars[(go_matches[2]['match_id'], team_id)] ==
                          assignment_vars[(go_matches[3]['match_id'], team_id)])
        
                model.Add(sum(assignment_vars[(go_matches[0]['match_id'], team['team_id'])] for team in jury_teams) ==
                          sum(assignment_vars[(go_matches[1]['match_id'], team['team_id'])] for team in jury_teams))
                model.Add(sum(assignment_vars[(go_matches[0]['match_id'], team['team_id'])] * team['team_id'] for team in jury_teams) !=
                          sum(assignment_vars[(go_matches[4]['match_id'], team['team_id'])] * team['team_id'] for team in jury_teams))
        

                
 

def add_team_not_jury_own_match_constraint(model, matches, assignment_vars, jury_teams):
    for match in matches:
        match_id = match['match_id']
        home_team = match['home_team']
        away_team = match['away_team']  # Assuming there's an away_team field
        
        for team in jury_teams:
            team_id = team['team_id']
            team_name = team['team_name']
            
            if team_name == home_team or team_name == away_team:
                # Add a hard constraint to prevent assignment
                model.Add(assignment_vars[(match_id, team_id)] == 0)

    # No need to return penalty variables as we're using hard constraints
    return []

def add_d1_d2_constraint(model, assignment_vars, home_matches, jury_teams):
    for match in home_matches:
        for team in jury_teams:
            if (match['match_id'], team['team_id']) in assignment_vars:
                if (match['home_team'] == 'MNC Dordrecht Da1' and team['team_name'] == 'MNC Dordrecht Da2') or \
                   (match['home_team'] == 'MNC Dordrecht Da2' and team['team_name'] == 'MNC Dordrecht Da1'):
                    model.Add(assignment_vars[(match['match_id'], team['team_id'])] == 0)



def add_no_double_weekend_assignments_constraint(model, assignment_vars, matches, jury_teams):

    # Group matches by day
    matches_by_day = group_matches_by_day(matches)
    
    # Identify weekend days
    weekend_days = [day for day in matches_by_day.keys() if day.weekday() >= 5]
    
    if len(weekend_days) < 2:
        print("Not enough weekend days to apply the restriction.")
        return model

    # Sort weekend days to ensure we have consecutive weekend days
    weekend_days.sort()

    for i in range(len(weekend_days) - 1):
        day1 = weekend_days[i]
        day2 = weekend_days[i + 1]
        
        if day1.weekday() == 5 and day2.weekday() == 6:  # Ensure day1 is Saturday and day2 is Sunday
            matches_day1 = matches_by_day[day1]
            matches_day2 = matches_by_day[day2]
            
            for team in jury_teams:
                team_id = team['team_id']
                if team_id != 99:  # Skip team 99 (static assignments)
                    home_matches_day1 = [assignment_vars[(match['match_id'], team_id)] for match in matches_day1 if match['home_team'] == team['team_name']]
                    assignments_day1 = [assignment_vars[(match['match_id'], team_id)] for match in matches_day1]
                    assignments_day2 = [assignment_vars[(match['match_id'], team_id)] for match in matches_day2]
                    
                    # Create a boolean variable for assignments on both days
                    assigned_on_day1 = model.NewBoolVar(f'assigned_on_day1_{team_id}_{day1}')
                    assigned_on_day2 = model.NewBoolVar(f'assigned_on_day2_{team_id}_{day2}')
                    
                    model.Add(sum(home_matches_day1) + sum(assignments_day1) > 0).OnlyEnforceIf(assigned_on_day1)
                    model.Add(sum(home_matches_day1) + sum(assignments_day1) == 0).OnlyEnforceIf(assigned_on_day1.Not())
                    
                    model.Add(sum(assignments_day2) > 0).OnlyEnforceIf(assigned_on_day2)
                    model.Add(sum(assignments_day2) == 0).OnlyEnforceIf(assigned_on_day2.Not())
                    
                    # Add hard constraint to prevent assignments on both days
                    model.AddBoolOr([assigned_on_day1.Not(), assigned_on_day2.Not()])
    
    print('Hard constraint to prevent double weekend assignments added.')
    return model



def add_weekend_assignment_constraint(model, matches, jury_teams, assignment_vars):
    # Group matches by day
    grouped_matches = {}
    for match in matches:
        if isinstance(match['date_time'], str):
            date = datetime.strptime(match['date_time'], "%Y-%m-%d %H:%M:%S").date()
        else:
            date = match['date_time'].date()
        if date.weekday() >= 5:  # 5 is Saturday, 6 is Sunday
            if date not in grouped_matches:
                grouped_matches[date] = []
            grouped_matches[date].append(match)

    weekend_days = list(grouped_matches.keys())
    if not weekend_days:
        print("No weekend matches found!")
        return model

    print(f"Weekend days: {weekend_days}")
    for day, day_matches in grouped_matches.items():
        print(f"Day: {day}, Matches: {len(day_matches)}")
        for match in day_matches:
            print(f"  Match ID: {match['match_id']}, Date/Time: {match['date_time']}, Home Team: {match['home_team']}, Away Team: {match['away_team']}")

    # Apply hard constraints for weekend matches
    for day, day_matches in grouped_matches.items():
        for team in jury_teams:
            team_id = team['team_id']
            day_assignments = [assignment_vars[(match['match_id'], team_id)] for match in day_matches]
            
            # Hard constraint: Ensure a team is assigned to at most one match per weekend day
            model.Add(sum(day_assignments) <= 1)
            print(f"Added constraint for team {team_id} on day {day}: at most one match")


def add_no_consecutive_assignments_between_days_constraint(model, assignment_vars, matches, jury_teams):
    logging.info("Starting to add no consecutive assignments between days constraint")
    matches_by_day = group_matches_by_day(matches)
    
    days = sorted(matches_by_day.keys())
    logging.debug(f"Processing {len(days)} days")
    
    for day_index, day in enumerate(days):
        if day_index < len(days) - 1:
            logging.debug(f"Processing day {day} (index: {day_index})")
            current_day_matches = matches_by_day[day]
            next_day_matches = matches_by_day[days[day_index + 1]]
            logging.debug(f"Current day matches: {len(current_day_matches)}, Next day matches: {len(next_day_matches)}")
            
            last_match_of_current_day = max(current_day_matches, key=lambda x: x['date_time'])
            first_match_of_next_day = min(next_day_matches, key=lambda x: x['date_time'])
            logging.debug(f"Last match ID of current day: {last_match_of_current_day['match_id']}, First match ID of next day: {first_match_of_next_day['match_id']}")
            
            for team in jury_teams:
                team_id = team['team_id']
                if team_id != 99:  # Skip team 99 (static assignments)
                    logging.debug(f"Adding constraint for team {team_id}")
                    model.Add(assignment_vars[(last_match_of_current_day['match_id'], team_id)] + 
                              assignment_vars[(first_match_of_next_day['match_id'], team_id)] <= 1)
    
    logging.info("Finished adding no consecutive assignments between days constraint")
    return model



def add_no_single_last_match_constraint(model, assignment_vars, matches, jury_teams):
    # Group matches by day
    matches_by_day = group_matches_by_day(matches)
    
    for day, day_matches in matches_by_day.items():
        sorted_day_matches = sorted(day_matches, key=lambda x: x['date_time'])
        last_match = sorted_day_matches[-1]
        
        for team in jury_teams:
            team_id = team['team_id']
            if team_id != 99:  # Skip team 99 (static assignments)
                day_assignments = [assignment_vars[(match['match_id'], team_id)] for match in sorted_day_matches]
                last_match_assignment = assignment_vars[(last_match['match_id'], team_id)]
                
                # Create a boolean variable for single last match assignment
                single_last_match = model.NewBoolVar(f'single_last_match_{team_id}_{day}')
                
                # If the team is assigned to the last match, ensure it's also assigned to at least one other match
                model.Add(sum(day_assignments) == 1).OnlyEnforceIf(single_last_match)
                model.Add(last_match_assignment == 1).OnlyEnforceIf(single_last_match)
                
                # Prevent single last match assignments
                model.Add(single_last_match == 0)
    
    print('No single last match constraint added.')

def prefer_home_playing_jury_teams_constraint(grouped_matches, jury_teams, assignment_vars, team_preferences):
    objective_terms = []
    for day, matches in grouped_matches.items():
        for match in matches:
            for team in jury_teams:
                objective_terms.append(
                    assignment_vars[(match['match_id'], team['team_id'])] * 
                    team_preferences[day][team['team_id']]
                )
    return objective_terms


def quiet_match_day_constraint(model, assignment_vars, grouped_matches, jury_teams):
    penalty_vars = []
    
    for day, matches in grouped_matches.items():
        if len(matches) in [2, 3]:
            # Get playing jury teams for this day (only considering home teams)
            playing_jury_teams = []
            for match in matches:
                if match['home_team'] not in ['MNC Dordrecht H1', 'MNC Dordrecht H2']:
                    for team in jury_teams:
                        if team['team_name'] == match['home_team']:
                            playing_jury_teams.append(team)

            if len(matches) == 2:
                if len(playing_jury_teams) == 2:
                    # Soft constraint for 2 matches
                    team1, team2 = playing_jury_teams
                    penalty = model.NewIntVar(0, 2, f"penalty_2matches_day_{day}")
                    model.Add(assignment_vars[(matches[0]['match_id'], team1['team_id'])] +
                            assignment_vars[(matches[1]['match_id'], team2['team_id'])] +
                            assignment_vars[(matches[1]['match_id'], team1['team_id'])] +
                            assignment_vars[(matches[0]['match_id'], team2['team_id'])] + penalty == 2)
                    penalty_vars.append(penalty * 10)  # Increase weight of penalty

                elif len(playing_jury_teams) == 1:
                    # Prevent the single playing jury team from being assigned to either match
                    team = playing_jury_teams[0]
                    for match in matches:
                        model.Add(assignment_vars[(match['match_id'], team['team_id'])] == 0)
                    #print(f"For day {day}: Preventing {team['team_name']} from being assigned to either match.")
            elif len(matches) == 3 and len(playing_jury_teams) >= 2:
                # Soft constraint for 3 matches
                team1, team2 = playing_jury_teams[:2]
                
                # Determine which team is playing in the first two matches
                team_playing_early = None
                for i in range(2):
                    if team1['team_name'] in [matches[i]['home_team'], matches[i]['away_team']]:
                        team_playing_early = team1
                        break
                    if team2['team_name'] in [matches[i]['home_team'], matches[i]['away_team']]:
                        team_playing_early = team2
                        break

                if team_playing_early:
                    # The team playing in the first two matches should be assigned to the last match
                    team_one_match = team_playing_early
                    team_two_matches = team2 if team_playing_early == team1 else team1
                else:
                    # If neither team is playing early, keep the original order
                    team_two_matches = team1
                    team_one_match = team2

                penalty1 = model.NewIntVar(0, 2, f"penalty_3matches_team_two_matches_day_{day}")
                penalty2 = model.NewIntVar(0, 1, f"penalty_3matches_team_one_match_day_{day}")
                
                # Team assigned to two matches (first two matches)
                model.Add(assignment_vars[(matches[0]['match_id'], team_two_matches['team_id'])] +
                          assignment_vars[(matches[1]['match_id'], team_two_matches['team_id'])] + penalty1 == 2)
                
                # Team assigned to one match (last match)
                model.Add(assignment_vars[(matches[2]['match_id'], team_one_match['team_id'])] + penalty2 == 1)
                
                penalty_vars.extend([penalty1 * 50, penalty2 * 50])  # Increase weight of penalties

    return penalty_vars



def add_proximity_constraint(model, assignment_vars, matches, jury_teams, weight=10):
    penalty_vars = []
    
    # Group matches by day
    matches_by_day = group_matches_by_day(matches)
    
    for day, day_matches in matches_by_day.items():
        sorted_day_matches = sorted(day_matches, key=lambda x: x['date_time'])
        
        for team in jury_teams:
            team_id = team['team_id']
            assigned_matches = []
            
            for match in sorted_day_matches:
                assigned = assignment_vars[(match['match_id'], team_id)]
                assigned_matches.append((match, assigned))
            
            for i, (match, assigned) in enumerate(assigned_matches):
                for j, (other_match, other_assigned) in enumerate(assigned_matches):
                    if i != j:
                        matches_in_between = abs(i - j) - 1
                        #print(f"Matches in between {matches_in_between} for team {team_id} between match {match['match_id']} and {other_match['match_id']}")
                        
                        # Create a penalty variable for the number of matches in between
                        penalty = model.NewIntVar(0, matches_in_between, f'penalty_proximity_{match["match_id"]}_{other_match["match_id"]}_{team_id}')
                        model.Add(penalty == matches_in_between).OnlyEnforceIf(assigned).OnlyEnforceIf(other_assigned)
                        penalty_vars.append(penalty * weight)
    
    #print('Proximity constraint added.')
    return penalty_vars

def add_prefer_no_jury_same_weekend_as_match(model, assignment_vars, matches, jury_teams, weight=1000):
    logging.info("Adding soft constraint for jury duty based on home/away matches")
    penalty_vars = []
    
    # Group matches by weekend and day
    weekend_day_matches = defaultdict(lambda: defaultdict(list))
    for date, day_matches in matches.items():
        year, week, _ = date.isocalendar()
        weekend_key = (year, week)
        weekend_day_matches[weekend_key][date].extend(day_matches)
    
    # Process each weekend
    for weekend_key, days in weekend_day_matches.items():
        weekend_dates = list(days.keys())
        
        for team in jury_teams:
            team_id = team['team_id']
            
            for current_date, current_day_matches in days.items():
                has_home_match_today = any(
                    match['home_team'] == team_id 
                    for match in current_day_matches
                )
                has_away_match_today = any(
                    match['away_team'] == team_id 
                    for match in current_day_matches
                )
                
                # Check for away matches in the weekend
                has_away_match_weekend = any(
                    any(match['away_team'] == team_id for match in day_matches)
                    for day_matches in days.values()
                )
                
                # Rule 1: Heavy penalty if team has away match in weekend
                if has_away_match_weekend:
                    for match in current_day_matches:
                        penalty_var = model.NewIntVar(0, 1, 
                            f'penalty_away_match_{team_id}_weekend_{weekend_key}_match_{match["match_id"]}')
                        model.Add(penalty_var == 1).OnlyEnforceIf(assignment_vars[(match['match_id'], team_id)])
                        model.Add(penalty_var == 0).OnlyEnforceIf(assignment_vars[(match['match_id'], team_id)].Not())
                        penalty_vars.append(penalty_var * weight)
                
                # Rule 2: Penalty for jury duty on days without home match
                elif not has_home_match_today:
                    other_dates = [d for d in weekend_dates if d != current_date]
                    for other_date in other_dates:
                        has_home_match_other_day = any(
                            match['home_team'] == team_id 
                            for match in days[other_date]
                        )
                        if not has_home_match_other_day:
                            for match in days[other_date]:
                                penalty_var = model.NewIntVar(0, 1,
                                    f'penalty_no_home_{team_id}_day_{other_date}_match_{match["match_id"]}')
                                model.Add(penalty_var == 1).OnlyEnforceIf(assignment_vars[(match['match_id'], team_id)])
                                model.Add(penalty_var == 0).OnlyEnforceIf(assignment_vars[(match['match_id'], team_id)].Not())
                                penalty_vars.append(penalty_var * weight)
    
    logging.info(f"Finished adding weekend jury preference constraint with {len(penalty_vars)} penalty variables")
    return penalty_vars


def apply_static_assignments(model, assignment_vars, matches, jury_teams, static_assignments):    
    logging.info("Starting static assignments process")
    grouped_matches = {}
    static_match_ids = set()

    for match in matches:
        day = match['date_time'].date()
        if day not in grouped_matches:
            logging.debug(f"Creating new group for day: {day}")
            grouped_matches[day] = []
        grouped_matches[day].append(match)

    for day, day_matches in grouped_matches.items():
        logging.info(f"\nProcessing day: {day}")
        last_match = get_last_match_of_day(day_matches)
        if len(day_matches) == 2:
            logging.debug(f"Day has exactly 2 matches")
            static_matches = [match for match in day_matches if match['home_team'] in static_assignments]
            
            if len(static_matches) == 2:
                logging.info("Both matches are static")
                for match in day_matches:
                    logging.debug(f"Setting match {match['match_id']} as static")
                    model.Add(assignment_vars[(match['match_id'], 99)] == 1)
                    static_match_ids.add(match['match_id'])
                    # Ensure no other team is assigned to this match
                    for team_id in assignment_vars.keys():
                        if team_id[0] == match['match_id'] and team_id[1] != 99:
                            model.Add(assignment_vars[team_id] == 0)
            elif len(static_matches) == 1:
                logging.info("One match is static, treating both as static")
                for match in day_matches:
                    logging.debug(f"Setting match {match['match_id']} as static")
                    model.Add(assignment_vars[(match['match_id'], 99)] == 1)
                    static_match_ids.add(match['match_id'])
                    # Ensure no other team is assigned to this match
                    for team_id in assignment_vars.keys():
                        if team_id[0] == match['match_id'] and team_id[1] != 99:
                            model.Add(assignment_vars[team_id] == 0)
            else:
                logging.debug("No static matches, no special handling needed")
                pass
        else:
            logging.debug(f"Day has {len(day_matches)} matches (not 2)")
            # For days with more or fewer than 2 matches, apply the original static assignment logic
            for i, match in enumerate(day_matches):
                if i == len(day_matches) - 1:  # Last match of the day
                    if day_matches[i-1]['match_id'] in static_match_ids:
                        logging.info(f"Last match of day {day}, making static due to previous match")
                        model.Add(assignment_vars[(day_matches[i]['match_id'], 99)] == 1)
                        static_match_ids.add(day_matches[i]['match_id'])
                        # Ensure no other team is assigned to this match
                        for team_id in assignment_vars.keys():
                            if team_id[0] == day_matches[i]['match_id'] and team_id[1] != 99:
                                model.Add(assignment_vars[team_id] == 0)
                elif match['home_team'] in static_assignments:
                    logging.info(f"Static assignment found for match {match['match_id']} (Home team: {match['home_team']})")
                    model.Add(assignment_vars[(match['match_id'], 99)] == 1)
                    static_match_ids.add(match['match_id'])
                    # Ensure no other team is assigned to this match
                    for team_id in assignment_vars.keys():
                        if team_id[0] == match['match_id'] and team_id[1] != 99:
                            model.Add(assignment_vars[team_id] == 0)

    logging.info(f"Total static match IDs: {len(static_match_ids)}")
    return model, static_match_ids



def calculate_points(model, assignment_vars, home_matches, jury_teams):
    point_vars = {}
    team_total_points = {}
    for team in jury_teams:
        team_id = team['team_id']
        team_total_points[team_id] = model.NewIntVar(0, len(home_matches) * 15, f'total_points_{team_id}')
    
    for match in home_matches:
        match_id = match['match_id']
        for team in jury_teams:
            team_id = team['team_id']
            point_vars[(match_id, team_id)] = model.NewIntVar(0,5000, f'points_{match_id}_{team_id}')
            
            # Point assignment logic for all teams
            is_first_match = match_id == home_matches[0]['match_id']
            is_last_match = match_id == home_matches[-1]['match_id']
            is_go_match = 'go' in match['competition'].lower()
            
            if is_first_match:
                model.Add(point_vars[(match_id, team_id)] == 15).OnlyEnforceIf(assignment_vars[(match_id, team_id)])

            elif is_last_match:
                model.Add(point_vars[(match_id, team_id)] == 15).OnlyEnforceIf(assignment_vars[(match_id, team_id)])

            elif is_go_match:
                model.Add(point_vars[(match_id, team_id)] == 10).OnlyEnforceIf(assignment_vars[(match_id, team_id)])

            else:
                model.Add(point_vars[(match_id, team_id)] == 10).OnlyEnforceIf(assignment_vars[(match_id, team_id)])

            
            model.Add(point_vars[(match_id, team_id)] == 0).OnlyEnforceIf(assignment_vars[(match_id, team_id)].Not())

    # Sum up points for each team (only for home matches)
    for team in jury_teams:
        team_id = team['team_id']
        model.Add(team_total_points[team_id] == sum(point_vars[(match['match_id'], team_id)] for match in home_matches))
        #print(f"Debug: Total points for team {team_id}: {team_total_points[team_id]}")

    # Calculate min and max total points (excluding team 99)
    non_static_team_points = [points for team_id, points in team_total_points.items() if team_id != 99]
    min_total_points = model.NewIntVar(0, len(home_matches) * 15, 'min_total_points')
    max_total_points = model.NewIntVar(0, len(home_matches) * 15, 'max_total_points')

    model.AddMinEquality(min_total_points, non_static_team_points)
    model.AddMaxEquality(max_total_points, non_static_team_points)

    # Calculate the difference between max and min total points
    points_difference = model.NewIntVar(0, len(home_matches) * 15, 'points_difference')
    model.Add(points_difference == max_total_points - min_total_points)

    total_points = model.NewIntVar(0, len(home_matches) * 15 * len(jury_teams), 'total_points')

    return total_points, point_vars, points_difference, team_total_points, min_total_points, max_total_points





def calculate_team_preferences(home_matches, away_matches, jury_teams):
    logging.info("Starting to calculate team preferences")
    preferences = {}
    all_matches = home_matches
    logging.debug(f"Processing {len(all_matches)} home matches")
    
    grouped_matches = group_matches_by_day(all_matches)
    logging.debug(f"Matches grouped into {len(grouped_matches)} days")

    for day, matches in grouped_matches.items():
        logging.debug(f"Processing day: {day}")
        preferences[day] = {}
        playing_teams = set()
        
        for match in matches:
            playing_teams.add(match['home_team'])
        logging.debug(f"Found {len(playing_teams)} playing teams for day {day}")
        
        for team in jury_teams:
            if team['team_name'] in playing_teams:
                preferences[day][team['team_id']] = 1  # Higher preference (lower cost)
                logging.debug(f"Team {team['team_id']} is playing: preference set to 1")
            else:
                preferences[day][team['team_id']] = 2  # Lower preference (higher cost)
                logging.debug(f"Team {team['team_id']} is not playing: preference set to 2")

    logging.info("Finished calculating team preferences")
    return preferences


def create_assignment_variables(model, home_matches, jury_teams):
    assignment_vars = {}
    
    #print("\nCreating assignment variables:")
    for match in home_matches:
        for team in jury_teams:
            var_name = f"match_{match['match_id']}_team_{team['team_id']}"
            
            if match.get('locked', False) and match.get('assigned_team') == team['team_id']:
                # For locked assignments, set the variable to 1
                assignment_vars[(match['match_id'], team['team_id'])] = model.NewIntVar(1, 1, var_name)
                #print(f"  Team {team['team_id']} (Locked): Variable set to 1")
            elif not match.get('locked', False):
                # For unlocked assignments, create a binary variable
                assignment_vars[(match['match_id'], team['team_id'])] = model.NewBoolVar(var_name)
                #print(f"  Team {team['team_id']} (Unlocked): Binary variable created")
            else:
                # For locked assignments to other teams, set the variable to 0
                assignment_vars[(match['match_id'], team['team_id'])] = model.NewIntVar(0, 0, var_name)
                #print(f"  Team {team['team_id']} (Locked to another team): Variable set to 0")
    
    return assignment_vars


def assign_jury_teams_to_matches(home_matches, away_matches, jury_teams, static_assignments):
    grouped_matches = group_matches_by_day(home_matches)
    weekend_matches = group_matches_by_weekend(home_matches)
    assignments = []
    model = cp_model.CpModel()
    
    # Create variables
    assignment_vars = {}
    
    # Respect original assignments
    assignment_vars = create_assignment_variables(model, home_matches, jury_teams)

    soft_constraints = []          
    # Define non-static jury teams
    non_static_jury_teams = [team for team in jury_teams if team['team_id'] != 99]

   # Create assignment variables
    assignment_vars = {}
    for day, matches in grouped_matches.items():
        for match in matches:
            for team in jury_teams:
                var_name = f"match_{match['match_id']}_team_{team['team_id']}"
                assignment_vars[(match['match_id'], team['team_id'])] = model.NewBoolVar(var_name)

    model, static_match_ids = apply_static_assignments(model, assignment_vars, home_matches, jury_teams, static_assignments)

    # Calculate team preferences
    team_preferences = calculate_team_preferences(home_matches, away_matches, jury_teams)   

    # Add no double weekend assignments constraint
    add_no_double_weekend_assignments_constraint(model, assignment_vars, matches, jury_teams)

    # Add cross-day constraints to prevent assignments on consecutive days
    add_no_consecutive_assignments_between_days_constraint(model, assignment_vars, home_matches, non_static_jury_teams)

    # Forbid 2 shifts per day
    add_forbid_2nd_assignment_constraint(model, assignment_vars, home_matches, non_static_jury_teams)

    # Make sure match are consecutive
    consecutive_match_violations = add_consecutive_matches_constraint(model, assignment_vars, home_matches, grouped_matches, non_static_jury_teams, team_preferences, weight=1)

    for day, matches in grouped_matches.items():
        add_one_team_per_match_constraint(model, assignment_vars, matches, non_static_jury_teams, static_match_ids)
        add_go_matches_constraint(model, assignment_vars, matches, non_static_jury_teams)
        add_maximum_assignments_per_day_constraint(model, assignment_vars, matches, non_static_jury_teams, max_assignments_per_day,static_assignments)
        add_no_assignment_for_away_teams_constraint(model, assignment_vars, matches, away_matches, non_static_jury_teams)
        add_team_not_jury_own_match_constraint(model, home_matches, assignment_vars, non_static_jury_teams) 
        add_d1_d2_constraint(model, assignment_vars, home_matches, jury_teams)

        # Add soft constraints
        quiet_match_day_violations = quiet_match_day_constraint(model, assignment_vars, grouped_matches, non_static_jury_teams)
        home_playing_jury_teams_violations = prefer_home_playing_jury_teams_constraint(grouped_matches, non_static_jury_teams, assignment_vars, team_preferences)
        weekend_match_penalties = add_prefer_no_jury_same_weekend_as_match(model, assignment_vars, grouped_matches, non_static_jury_teams, weight=1000)

        soft_constraints.extend(consecutive_match_violations)
        soft_constraints.extend(quiet_match_day_violations)
        soft_constraints.extend(home_playing_jury_teams_violations)
        soft_constraints.extend(weekend_match_penalties)

    # Add soft constraint to ensure proximity between home match and assigned matches
    proximity_penalties = add_proximity_constraint(model, assignment_vars, home_matches, non_static_jury_teams)


    # Calculate points
    total_points, point_vars, points_difference, team_total_points, min_total_points, max_total_points = calculate_points(model, assignment_vars, home_matches, jury_teams)

    # Create random weights for assignments
    assignment_weights = {}
    for match in home_matches:
        for team in jury_teams:
            if team['team_id'] != 99:  # Skip static team
                assignment_weights[(match['match_id'], team['team_id'])] = random.randint(1, 10)

    # Calculate randomization penalty
    randomization_terms = []
    for (match_id, team_id), var in assignment_vars.items():
        if team_id != 99:  # Skip static team
            randomization_terms.append(assignment_weights[(match_id, team_id)] * var)
            
    # Objective: Minimize point difference and soft constraint violations
    model.Minimize(points_difference * 1 + sum(soft_constraints) * 100 + sum(proximity_penalties) * 1 + sum(randomization_terms) * 0.5)

    # Solve the model
    solver = cp_model.CpSolver()
    
    # Without logging
    status = solver.Solve(model)

    # With Logging
    callback = AssignmentDebugCallback(assignment_vars, grouped_matches, non_static_jury_teams)
    status = solver.Solve(model, callback) 
    
    # Print the solution
    if status == cp_model.OPTIMAL or status == cp_model.FEASIBLE:
        assignments = []
        #print(solver.Value(assignment_vars))
        for match in home_matches:
            for team in jury_teams:
                if solver.Value(assignment_vars[(match['match_id'], team['team_id'])]) == 1:
                    assignments.append({
                        'match_id': match['match_id'],
                        'team_id': team['team_id'],
                        'team_name': team['team_name'],
                        'date_time': match['date_time'],
                        'home_team': match['home_team'],
                        'away_team': match['away_team'],
                        'competition': match['competition'],
                        'assigned_team': team['team_name'],

                    })

        print(f"Total assignments: {len(assignments)}")
        #for assignment in assignments:
        #   print(f"Match ID: {assignment['match_id']}, Team ID: {assignment['team_id']}, Team Name: {assignment['team_name']}, Date/Time: {assignment['date_time']}, Home Team: {assignment['home_team']}, Away Team: {assignment['away_team']}, Competition: {assignment['competition']}") # Print the status of resolving the model
    print("Model resolution status:")
    if status == cp_model.OPTIMAL:
        print("OPTIMAL - The optimal solution has been found.")
    elif status == cp_model.FEASIBLE:
        print("FEASIBLE - A feasible solution has been found, but it may not be optimal.")
    elif status == cp_model.INFEASIBLE:
        print("INFEASIBLE - The problem has been proven infeasible.")
    elif status == cp_model.MODEL_INVALID:
        print("MODEL_INVALID - The model is invalid.")
    else:
        print(f"UNKNOWN - The status of the solution is unknown. Status code: {status}")

    # Print additional solver statistics
    print(f"Solve time: {solver.WallTime():.2f} seconds")
    print(f"Number of branches explored: {solver.NumBranches()}")
    print(f"Number of conflicts: {solver.NumConflicts()}")


     # Process the results
    if status == cp_model.OPTIMAL or status == cp_model.FEASIBLE:
        assignments = []
        for match in home_matches:
            for team in jury_teams:
                if solver.Value(assignment_vars[(match['match_id'], team['team_id'])]) == 1:
                    if match['match_id'] in [m['match_id'] for m in home_matches]:
                        points = solver.Value(point_vars[(match['match_id'], team['team_id'])])
                        assignments.append({
                            'match_id': match['match_id'],
                            'date_time': match['date_time'],
                            'home_team': match['home_team'],
                            'away_team': match['away_team'],
                            'assigned_team': team['team_name'],
                            'team_id': team['team_id'],
                            'points': points
                        })
                    # print(f"Debug: Assigned match {match['match_id']} to team {team['team_id']} with {points} points")

        print(f"Total points: {solver.Value(total_points)}")
        print(f"Points difference: {solver.Value(points_difference)}")
        print(f"Min total points: {solver.Value(min_total_points)}")
        print(f"Max total points: {solver.Value(max_total_points)}")
 #       print(f"Consecutive match violations: {solver.Value(sum(consecutive_match_violations))}")
        
        for team in jury_teams:
            if team['team_id'] != 99:
                team_points = sum(a['points'] for a in assignments if a['team_id'] == team['team_id'])
                print(f"Team {team['team_name']} total points: {team_points}")
            else:
                static_assignments_count = sum(1 for a in assignments if a['team_id'] == 99)
                print(f"Team 99 (static) assignments: {static_assignments_count}")
        #print(assignments)
        return assignments
    else:
        print("No solution found.")
        return None
    
    return assignments




def main():
    env_vars = load_env_variables()
    connection = get_database_connection(env_vars)

    start_date = datetime(2024,9, 1)
    end_date = datetime(2025,4,30)



    #end_date = start_date + timedelta(days=200)

    home_matches,away_matches  = fetch_matches(connection, start_date, end_date)
    static_assignments = get_static_assignments(connection)
    jury_teams = get_jury_teams(connection)

      # Set the maximum number of assignments per day


    assignments = assign_jury_teams_to_matches(home_matches, away_matches, jury_teams, static_assignments)

    if assignments:
        print("\nAssigned Matches:")
        for assignment in assignments:
            print(f"Match {assignment['match_id']} on {assignment['date_time']}: {assignment['home_team']} vs {assignment['away_team']} - Assigned to {assignment['assigned_team']}")
        # Insert assignments into the database
        insert_assignments_to_database(assignments, connection)
    else:
        print("No valid assignment found.")    

    connection.close()

if __name__ == "__main__":
    main()
