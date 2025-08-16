from flask import Flask, request, jsonify, render_template, send_file
from flask_cors import CORS
from flask_migrate import Migrate
from datetime import datetime, date, timedelta
import os
from dotenv import load_dotenv
import logging
from io import BytesIO

# Load environment variables
load_dotenv()

# Import models and engines
from backend.models import db, Team, Match, JuryAssignment, PlanningRule, TeamAvailability, PlanningSession
from planning_engine.scheduler import JuryPlanningEngine
from planning_engine.rule_manager import RuleConfigurationManager
from backend.exporters import PDFExporter, ExcelExporter, CSVExporter

# Configure logging
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

def create_app():
    """Application factory pattern"""
    app = Flask(__name__, template_folder='../frontend/templates', static_folder='../frontend/static')
    
    # Configuration
    app.config['SECRET_KEY'] = os.getenv('SECRET_KEY', 'dev-secret-key')
    app.config['SQLALCHEMY_DATABASE_URI'] = os.getenv('DATABASE_URL', 'sqlite:///jury_planner.db')
    app.config['SQLALCHEMY_TRACK_MODIFICATIONS'] = False
    app.config['JSON_SORT_KEYS'] = False
    
    # Initialize extensions
    db.init_app(app)
    migrate = Migrate(app, db)
    CORS(app)
    
    # Initialize managers
    rule_manager = RuleConfigurationManager()
    planning_engine = JuryPlanningEngine()
    
    # Routes
    @app.route('/')
    def index():
        """Main dashboard page"""
        return render_template('index.html')
    
    @app.route('/api/dashboard/stats')
    def dashboard_stats():
        """Get dashboard statistics"""
        try:
            stats = {
                'total_teams': Team.query.filter_by(is_active=True).count(),
                'total_matches': Match.query.count(),
                'planned_matches': Match.query.filter_by(is_planned=True).count(),
                'active_rules': PlanningRule.query.filter_by(is_active=True).count(),
                'recent_sessions': PlanningSession.query.order_by(PlanningSession.created_at.desc()).limit(5).all()
            }
            
            # Convert sessions to dict
            stats['recent_sessions'] = [
                {
                    'id': session.id,
                    'name': session.name,
                    'status': session.status,
                    'created_at': session.created_at.isoformat(),
                    'execution_time': session.execution_time
                }
                for session in stats['recent_sessions']
            ]
            
            return jsonify(stats)
        except Exception as e:
            logger.error(f"Error fetching dashboard stats: {e}")
            return jsonify({'error': str(e)}), 500
    
    # Team Management Routes
    @app.route('/api/teams', methods=['GET'])
    def get_teams():
        """Get all teams with optional filtering"""
        try:
            query = Team.query
            
            # Apply filters
            if request.args.get('active_only') == 'true':
                query = query.filter_by(is_active=True)
            
            teams = query.all()
            
            result = []
            for team in teams:
                team_data = {
                    'id': team.id,
                    'name': team.name,
                    'weight': team.weight,
                    'is_active': team.is_active,
                    'contact_person': team.contact_person,
                    'email': team.email,
                    'phone': team.phone,
                    'dedicated_to_team_id': team.dedicated_to_team_id,
                    'dedicated_to_team_name': team.dedicated_to_team.name if team.dedicated_to_team else None
                }
                result.append(team_data)
            
            return jsonify(result)
        except Exception as e:
            logger.error(f"Error fetching teams: {e}")
            return jsonify({'error': str(e)}), 500
    
    @app.route('/api/teams', methods=['POST'])
    def create_team():
        """Create a new team"""
        try:
            data = request.get_json()
            
            team = Team(
                name=data['name'],
                weight=data.get('weight', 1.0),
                contact_person=data.get('contact_person'),
                email=data.get('email'),
                phone=data.get('phone'),
                dedicated_to_team_id=data.get('dedicated_to_team_id'),
                notes=data.get('notes')
            )
            
            db.session.add(team)
            db.session.commit()
            
            return jsonify({'id': team.id, 'message': 'Team created successfully'}), 201
        except Exception as e:
            db.session.rollback()
            logger.error(f"Error creating team: {e}")
            return jsonify({'error': str(e)}), 500
    
    @app.route('/api/teams/<int:team_id>', methods=['PUT'])
    def update_team(team_id):
        """Update an existing team"""
        try:
            team = Team.query.get_or_404(team_id)
            data = request.get_json()
            
            team.name = data.get('name', team.name)
            team.weight = data.get('weight', team.weight)
            team.contact_person = data.get('contact_person', team.contact_person)
            team.email = data.get('email', team.email)
            team.phone = data.get('phone', team.phone)
            team.dedicated_to_team_id = data.get('dedicated_to_team_id', team.dedicated_to_team_id)
            team.notes = data.get('notes', team.notes)
            team.is_active = data.get('is_active', team.is_active)
            
            db.session.commit()
            
            return jsonify({'message': 'Team updated successfully'})
        except Exception as e:
            db.session.rollback()
            logger.error(f"Error updating team: {e}")
            return jsonify({'error': str(e)}), 500
    
    # Match Management Routes
    @app.route('/api/matches', methods=['GET'])
    def get_matches():
        """Get matches with optional filtering"""
        try:
            query = Match.query
            
            # Apply filters
            start_date = request.args.get('start_date')
            end_date = request.args.get('end_date')
            home_team_id = request.args.get('home_team_id')
            planned_only = request.args.get('planned_only')
            
            if start_date:
                query = query.filter(Match.date >= date.fromisoformat(start_date))
            if end_date:
                query = query.filter(Match.date <= date.fromisoformat(end_date))
            if home_team_id:
                query = query.filter_by(home_team_id=home_team_id)
            if planned_only == 'true':
                query = query.filter_by(is_planned=True)
            
            matches = query.order_by(Match.date, Match.time).all()
            
            result = []
            for match in matches:
                match_data = {
                    'id': match.id,
                    'date': match.date.isoformat(),
                    'time': match.time.strftime('%H:%M'),
                    'home_team': {
                        'id': match.home_team.id,
                        'name': match.home_team.name
                    },
                    'away_team': {
                        'id': match.away_team.id,
                        'name': match.away_team.name
                    },
                    'location': match.location,
                    'competition': match.competition,
                    'is_planned': match.is_planned,
                    'jury_assignments': [
                        {
                            'jury_team': {
                                'id': assignment.jury_team.id,
                                'name': assignment.jury_team.name
                            },
                            'duty_type': assignment.duty_type.value
                        }
                        for assignment in match.jury_assignments
                    ]
                }
                result.append(match_data)
            
            return jsonify(result)
        except Exception as e:
            logger.error(f"Error fetching matches: {e}")
            return jsonify({'error': str(e)}), 500
    
    # Planning Rules Routes
    @app.route('/api/rules/templates')
    def get_rule_templates():
        """Get available rule templates"""
        try:
            templates = rule_manager.get_rule_templates()
            
            result = {}
            for name, template in templates.items():
                result[name] = {
                    'name': template.name,
                    'description': template.description,
                    'rule_type': template.rule_type.value,
                    'default_weight': template.default_weight,
                    'parameters_schema': template.parameters_schema,
                    'category': template.category
                }
            
            return jsonify(result)
        except Exception as e:
            logger.error(f"Error fetching rule templates: {e}")
            return jsonify({'error': str(e)}), 500
    
    @app.route('/api/rules', methods=['GET'])
    def get_rules():
        """Get all planning rules"""
        try:
            rules = PlanningRule.query.all()
            
            result = []
            for rule in rules:
                rule_data = {
                    'id': rule.id,
                    'name': rule.name,
                    'description': rule.description,
                    'rule_type': rule.rule_type.value,
                    'weight': rule.weight,
                    'is_active': rule.is_active,
                    'parameters': rule.parameters,
                    'created_at': rule.created_at.isoformat()
                }
                result.append(rule_data)
            
            return jsonify(result)
        except Exception as e:
            logger.error(f"Error fetching rules: {e}")
            return jsonify({'error': str(e)}), 500
    
    @app.route('/api/rules', methods=['POST'])
    def create_rule():
        """Create a new planning rule"""
        try:
            data = request.get_json()
            template_name = data.get('template')
            
            if template_name:
                # Create from template
                rule_config = rule_manager.create_rule_from_template(
                    template_name=template_name,
                    rule_name=data['name'],
                    parameters=data['parameters'],
                    custom_weight=data.get('weight')
                )
                
                rule = PlanningRule(
                    name=rule_config['name'],
                    description=rule_config['description'],
                    rule_type=rule_config['rule_type'],
                    weight=rule_config['weight'],
                    parameters=rule_config['parameters'],
                    is_active=rule_config['is_active']
                )
            else:
                # Create manually
                from backend.models import RuleType
                rule = PlanningRule(
                    name=data['name'],
                    description=data.get('description', ''),
                    rule_type=RuleType(data['rule_type']),
                    weight=data['weight'],
                    parameters=data.get('parameters', {}),
                    is_active=data.get('is_active', True)
                )
            
            db.session.add(rule)
            db.session.commit()
            
            return jsonify({'id': rule.id, 'message': 'Rule created successfully'}), 201
        except Exception as e:
            db.session.rollback()
            logger.error(f"Error creating rule: {e}")
            return jsonify({'error': str(e)}), 500
    
    # Planning Engine Routes
    @app.route('/api/planning/run', methods=['POST'])
    def run_planning():
        """Run the planning algorithm"""
        try:
            data = request.get_json()
            
            start_date = date.fromisoformat(data['start_date'])
            end_date = date.fromisoformat(data['end_date'])
            session_name = data.get('name', f'Planning {start_date} to {end_date}')
            
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
                # Get data for planning
                matches = Match.query.filter(
                    Match.date >= start_date,
                    Match.date <= end_date
                ).all()
                
                teams = Team.query.filter_by(is_active=True).all()
                rules = PlanningRule.query.filter_by(is_active=True).all()
                
                # Run planning
                planning_engine = JuryPlanningEngine()
                setup_success = planning_engine.setup_problem(matches, teams, rules, start_date, end_date)
                
                if not setup_success:
                    raise Exception("Failed to setup planning problem")
                
                success, result = planning_engine.solve()
                
                if success:
                    # Save assignments
                    # First, clear existing assignments for the period
                    match_ids = [m.id for m in matches]
                    JuryAssignment.query.filter(JuryAssignment.match_id.in_(match_ids)).delete()
                    
                    # Create new assignments
                    for assignment_data in result['assignments']:
                        from backend.models import DutyType
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
                    
                    return jsonify({
                        'success': True,
                        'session_id': session.id,
                        'message': f'Planning completed successfully. {len(result["assignments"])} assignments created.',
                        'result': result
                    })
                else:
                    session.status = 'failed'
                    session.error_message = result.get('error', 'Unknown error')
                    db.session.commit()
                    
                    return jsonify({
                        'success': False,
                        'session_id': session.id,
                        'error': result.get('error', 'Planning failed')
                    }), 400
                    
            except Exception as e:
                session.status = 'failed'
                session.error_message = str(e)
                db.session.commit()
                raise e
                
        except Exception as e:
            db.session.rollback()
            logger.error(f"Error running planning: {e}")
            return jsonify({'error': str(e)}), 500
    
    # Export Routes
    @app.route('/api/export/<format>')
    def export_schedule(format):
        """Export schedule in various formats"""
        try:
            # Get filters from query parameters
            start_date = request.args.get('start_date')
            end_date = request.args.get('end_date')
            team_id = request.args.get('team_id')
            
            # Build query
            query = db.session.query(Match, JuryAssignment, Team).join(
                JuryAssignment, Match.id == JuryAssignment.match_id
            ).join(
                Team, JuryAssignment.jury_team_id == Team.id
            )
            
            if start_date:
                query = query.filter(Match.date >= date.fromisoformat(start_date))
            if end_date:
                query = query.filter(Match.date <= date.fromisoformat(end_date))
            if team_id:
                query = query.filter(Team.id == team_id)
            
            results = query.order_by(Match.date, Match.time).all()
            
            # Prepare data
            export_data = []
            for match, assignment, jury_team in results:
                export_data.append({
                    'date': match.date,
                    'time': match.time,
                    'home_team': match.home_team.name,
                    'away_team': match.away_team.name,
                    'location': match.location,
                    'competition': match.competition,
                    'jury_team': jury_team.name,
                    'duty': assignment.duty_type.value
                })
            
            # Export based on format
            if format.lower() == 'pdf':
                exporter = PDFExporter()
                buffer = exporter.export(export_data)
                return send_file(buffer, as_attachment=True, download_name='jury_schedule.pdf', mimetype='application/pdf')
            
            elif format.lower() == 'excel':
                exporter = ExcelExporter()
                buffer = exporter.export(export_data)
                return send_file(buffer, as_attachment=True, download_name='jury_schedule.xlsx', mimetype='application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            
            elif format.lower() == 'csv':
                exporter = CSVExporter()
                buffer = exporter.export(export_data)
                return send_file(buffer, as_attachment=True, download_name='jury_schedule.csv', mimetype='text/csv')
            
            else:
                return jsonify({'error': 'Unsupported format'}), 400
                
        except Exception as e:
            logger.error(f"Error exporting schedule: {e}")
            return jsonify({'error': str(e)}), 500
    
    # Error handlers
    @app.errorhandler(404)
    def not_found(error):
        return jsonify({'error': 'Not found'}), 404
    
    @app.errorhandler(500)
    def internal_error(error):
        db.session.rollback()
        return jsonify({'error': 'Internal server error'}), 500
    
    return app

if __name__ == '__main__':
    app = create_app()
    
    with app.app_context():
        # Create tables
        db.create_all()
        
        # Run the app
        app.run(host='0.0.0.0', port=5000, debug=True)
