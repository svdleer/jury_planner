#!/usr/bin/env python3
"""
Waterpolo Jury Planner - Command Line Interface
Provides utilities for managing teams, matches, and running planning sessions
"""

import argparse
import sys
import os
from datetime import datetime, date, timedelta
from dotenv import load_dotenv

# Add the project root to the Python path
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))

# Load environment variables
load_dotenv()

# Import after setting up the path
from app import create_app
from backend.models import db, Team, Match, JuryAssignment, PlanningRule, PlanningSession
from planning_engine.scheduler import JuryPlanningEngine
from planning_engine.rule_manager import RuleConfigurationManager

def setup_database():
    """Initialize the database and create tables"""
    app = create_app()
    with app.app_context():
        db.create_all()
        print("✅ Database tables created successfully")

def add_team(name, weight=1.0, contact=None, email=None, phone=None):
    """Add a new team"""
    app = create_app()
    with app.app_context():
        # Check if team already exists
        existing_team = Team.query.filter_by(name=name).first()
        if existing_team:
            print(f"❌ Team '{name}' already exists")
            return False
        
        team = Team(
            name=name,
            weight=weight,
            contact_person=contact,
            email=email,
            phone=phone
        )
        
        db.session.add(team)
        db.session.commit()
        
        print(f"✅ Team '{name}' added successfully (ID: {team.id})")
        return True

def list_teams():
    """List all teams"""
    app = create_app()
    with app.app_context():
        teams = Team.query.all()
        
        if not teams:
            print("No teams found")
            return
        
        print("\n📋 Teams:")
        print("-" * 80)
        print(f"{'ID':<4} {'Name':<20} {'Weight':<8} {'Active':<8} {'Contact':<20}")
        print("-" * 80)
        
        for team in teams:
            status = "Yes" if team.is_active else "No"
            contact = team.contact_person or "-"
            print(f"{team.id:<4} {team.name:<20} {team.weight:<8} {status:<8} {contact:<20}")

def add_match(date_str, time_str, home_team_id, away_team_id, location=None, competition=None):
    """Add a new match"""
    app = create_app()
    with app.app_context():
        try:
            match_date = datetime.strptime(date_str, '%Y-%m-%d').date()
            match_time = datetime.strptime(time_str, '%H:%M').time()
        except ValueError as e:
            print(f"❌ Invalid date/time format: {e}")
            return False
        
        # Validate teams exist
        home_team = Team.query.get(home_team_id)
        away_team = Team.query.get(away_team_id)
        
        if not home_team:
            print(f"❌ Home team with ID {home_team_id} not found")
            return False
        
        if not away_team:
            print(f"❌ Away team with ID {away_team_id} not found")
            return False
        
        match = Match(
            date=match_date,
            time=match_time,
            home_team_id=home_team_id,
            away_team_id=away_team_id,
            location=location,
            competition=competition
        )
        
        db.session.add(match)
        db.session.commit()
        
        print(f"✅ Match added: {home_team.name} vs {away_team.name} on {date_str} at {time_str} (ID: {match.id})")
        return True

def list_matches(limit=10):
    """List recent matches"""
    app = create_app()
    with app.app_context():
        matches = Match.query.order_by(Match.date.desc(), Match.time.desc()).limit(limit).all()
        
        if not matches:
            print("No matches found")
            return
        
        print(f"\n🏆 Recent Matches (showing {len(matches)}):")
        print("-" * 100)
        print(f"{'ID':<4} {'Date':<12} {'Time':<6} {'Match':<30} {'Location':<20} {'Planned':<8}")
        print("-" * 100)
        
        for match in matches:
            match_desc = f"{match.home_team.name} vs {match.away_team.name}"
            location = match.location or "-"
            planned = "Yes" if match.is_planned else "No"
            print(f"{match.id:<4} {match.date:<12} {match.time.strftime('%H:%M'):<6} {match_desc:<30} {location:<20} {planned:<8}")

def run_planning(start_date_str, end_date_str, session_name=None):
    """Run the planning algorithm"""
    app = create_app()
    with app.app_context():
        try:
            start_date = datetime.strptime(start_date_str, '%Y-%m-%d').date()
            end_date = datetime.strptime(end_date_str, '%Y-%m-%d').date()
        except ValueError as e:
            print(f"❌ Invalid date format: {e}")
            return False
        
        if not session_name:
            session_name = f"CLI Planning {start_date} to {end_date}"
        
        print(f"🧠 Running planning algorithm for {start_date} to {end_date}...")
        
        # Get data
        matches = Match.query.filter(
            Match.date >= start_date,
            Match.date <= end_date
        ).all()
        
        teams = Team.query.filter_by(is_active=True).all()
        rules = PlanningRule.query.filter_by(is_active=True).all()
        
        if not matches:
            print("❌ No matches found in the specified date range")
            return False
        
        if not teams:
            print("❌ No active teams found")
            return False
        
        print(f"📊 Found {len(matches)} matches, {len(teams)} teams, {len(rules)} rules")
        
        # Create planning session
        session = PlanningSession(
            name=session_name,
            start_date=start_date,
            end_date=end_date,
            status='running'
        )
        db.session.add(session)
        db.session.commit()
        
        try:
            # Run planning
            engine = JuryPlanningEngine()
            setup_success = engine.setup_problem(matches, teams, rules, start_date, end_date)
            
            if not setup_success:
                raise Exception("Failed to setup planning problem")
            
            print("⚙️  Solving optimization problem...")
            success, result = engine.solve()
            
            if success:
                # Save assignments
                match_ids = [m.id for m in matches]
                JuryAssignment.query.filter(JuryAssignment.match_id.in_(match_ids)).delete()
                
                from backend.models import DutyType
                for assignment_data in result['assignments']:
                    assignment = JuryAssignment(
                        match_id=assignment_data['match_id'],
                        jury_team_id=assignment_data['team_id'],
                        duty_type=DutyType(assignment_data['duty_type'])
                    )
                    db.session.add(assignment)
                
                # Mark matches as planned
                for match in matches:
                    match.is_planned = True
                
                # Update session
                session.status = 'completed'
                session.result_summary = {
                    'total_assignments': len(result['assignments']),
                    'objective_value': result['objective_value'],
                    'solve_status': result['status']
                }
                session.execution_time = result['solve_time']
                session.completed_at = datetime.utcnow()
                
                db.session.commit()
                
                print("✅ Planning completed successfully!")
                print(f"📈 Results:")
                print(f"   • Session ID: {session.id}")
                print(f"   • Status: {result['status']}")
                print(f"   • Execution time: {result['solve_time']:.2f}s")
                print(f"   • Assignments created: {len(result['assignments'])}")
                print(f"   • Objective value: {result['objective_value']:.2f}")
                
                return True
            else:
                session.status = 'failed'
                session.error_message = result.get('error', 'Unknown error')
                db.session.commit()
                
                print(f"❌ Planning failed: {result.get('error', 'Unknown error')}")
                return False
                
        except Exception as e:
            session.status = 'failed'
            session.error_message = str(e)
            db.session.commit()
            print(f"❌ Planning failed: {e}")
            return False

def show_schedule(start_date_str=None, end_date_str=None):
    """Show the current schedule"""
    app = create_app()
    with app.app_context():
        query = db.session.query(Match, JuryAssignment, Team).join(
            JuryAssignment, Match.id == JuryAssignment.match_id, isouter=True
        ).join(
            Team, JuryAssignment.jury_team_id == Team.id, isouter=True
        )
        
        if start_date_str:
            start_date = datetime.strptime(start_date_str, '%Y-%m-%d').date()
            query = query.filter(Match.date >= start_date)
        
        if end_date_str:
            end_date = datetime.strptime(end_date_str, '%Y-%m-%d').date()
            query = query.filter(Match.date <= end_date)
        
        results = query.order_by(Match.date, Match.time).all()
        
        if not results:
            print("No scheduled matches found")
            return
        
        print("\n📅 Schedule:")
        print("-" * 120)
        
        current_match = None
        for match, assignment, jury_team in results:
            if current_match != match.id:
                if current_match is not None:
                    print()
                
                match_desc = f"{match.home_team.name} vs {match.away_team.name}"
                print(f"{match.date} {match.time.strftime('%H:%M')} - {match_desc}")
                print(f"  Location: {match.location or 'TBD'}")
                
                if assignment:
                    print("  Jury assignments:")
                else:
                    print("  ❌ No jury assignments")
                
                current_match = match.id
            
            if assignment and jury_team:
                duty_display = assignment.duty_type.value.replace('_', ' ').title()
                print(f"    • {duty_display}: {jury_team.name}")

def main():
    """Main CLI entry point"""
    parser = argparse.ArgumentParser(description='Waterpolo Jury Planner CLI')
    subparsers = parser.add_subparsers(dest='command', help='Available commands')
    
    # Setup command
    setup_parser = subparsers.add_parser('setup', help='Initialize database')
    
    # Team commands
    team_parser = subparsers.add_parser('teams', help='Team management')
    team_subparsers = team_parser.add_subparsers(dest='team_action')
    
    # Add team
    add_team_parser = team_subparsers.add_parser('add', help='Add a new team')
    add_team_parser.add_argument('name', help='Team name')
    add_team_parser.add_argument('--weight', type=float, default=1.0, help='Team weight (default: 1.0)')
    add_team_parser.add_argument('--contact', help='Contact person')
    add_team_parser.add_argument('--email', help='Contact email')
    add_team_parser.add_argument('--phone', help='Contact phone')
    
    # List teams
    list_teams_parser = team_subparsers.add_parser('list', help='List all teams')
    
    # Match commands
    match_parser = subparsers.add_parser('matches', help='Match management')
    match_subparsers = match_parser.add_subparsers(dest='match_action')
    
    # Add match
    add_match_parser = match_subparsers.add_parser('add', help='Add a new match')
    add_match_parser.add_argument('date', help='Match date (YYYY-MM-DD)')
    add_match_parser.add_argument('time', help='Match time (HH:MM)')
    add_match_parser.add_argument('home_team_id', type=int, help='Home team ID')
    add_match_parser.add_argument('away_team_id', type=int, help='Away team ID')
    add_match_parser.add_argument('--location', help='Match location')
    add_match_parser.add_argument('--competition', help='Competition name')
    
    # List matches
    list_matches_parser = match_subparsers.add_parser('list', help='List matches')
    list_matches_parser.add_argument('--limit', type=int, default=10, help='Number of matches to show')
    
    # Planning command
    planning_parser = subparsers.add_parser('plan', help='Run planning algorithm')
    planning_parser.add_argument('start_date', help='Start date (YYYY-MM-DD)')
    planning_parser.add_argument('end_date', help='End date (YYYY-MM-DD)')
    planning_parser.add_argument('--name', help='Session name')
    
    # Schedule command
    schedule_parser = subparsers.add_parser('schedule', help='Show current schedule')
    schedule_parser.add_argument('--start-date', help='Start date (YYYY-MM-DD)')
    schedule_parser.add_argument('--end-date', help='End date (YYYY-MM-DD)')
    
    args = parser.parse_args()
    
    if not args.command:
        parser.print_help()
        return
    
    try:
        if args.command == 'setup':
            setup_database()
        
        elif args.command == 'teams':
            if args.team_action == 'add':
                add_team(args.name, args.weight, args.contact, args.email, args.phone)
            elif args.team_action == 'list':
                list_teams()
            else:
                team_parser.print_help()
        
        elif args.command == 'matches':
            if args.match_action == 'add':
                add_match(args.date, args.time, args.home_team_id, args.away_team_id, 
                         args.location, args.competition)
            elif args.match_action == 'list':
                list_matches(args.limit)
            else:
                match_parser.print_help()
        
        elif args.command == 'plan':
            run_planning(args.start_date, args.end_date, args.name)
        
        elif args.command == 'schedule':
            show_schedule(args.start_date, args.end_date)
        
        else:
            parser.print_help()
    
    except KeyboardInterrupt:
        print("\n❌ Operation cancelled by user")
        sys.exit(1)
    except Exception as e:
        print(f"❌ Error: {e}")
        sys.exit(1)

if __name__ == '__main__':
    main()
