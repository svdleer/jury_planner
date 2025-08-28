#!/usr/bin/env python3
"""
Flask Application Factory for Jury Planner
Python-only architecture replacing PHP/Python hybrid
"""

import os
from flask import Flask, jsonify, request, render_template
from flask_sqlalchemy import SQLAlchemy
from flask_migrate import Migrate
from flask_cors import CORS
from celery import Celery
import redis
from datetime import datetime, date

# Initialize extensions
db = SQLAlchemy()
migrate = Migrate()
celery = Celery()

def create_app(config_name='development'):
    """Application factory pattern"""
    app = Flask(__name__)
    
    # Configuration
    app.config['SECRET_KEY'] = os.environ.get('SECRET_KEY', 'dev-secret-key')
    app.config['SQLALCHEMY_DATABASE_URI'] = os.environ.get(
        'DATABASE_URL', 
        'mysql://jury_user:jury_pass@localhost/jury_planner'
    )
    app.config['SQLALCHEMY_TRACK_MODIFICATIONS'] = False
    
    # Celery configuration for background tasks
    app.config['CELERY_BROKER_URL'] = os.environ.get('REDIS_URL', 'redis://localhost:6379/0')
    app.config['CELERY_RESULT_BACKEND'] = os.environ.get('REDIS_URL', 'redis://localhost:6379/0')
    
    # Initialize extensions
    db.init_app(app)
    migrate.init_app(app, db)
    CORS(app)
    
    # Initialize Celery
    celery.conf.update(app.config)
    
    # Register blueprints
    from .api import api_bp
    from .web import web_bp
    
    app.register_blueprint(api_bp, url_prefix='/api')
    app.register_blueprint(web_bp)
    
    return app

# Database Models (SQLAlchemy)
class Team(db.Model):
    """Jury team model"""
    __tablename__ = 'teams'
    
    id = db.Column(db.Integer, primary_key=True)
    name = db.Column(db.String(100), nullable=False, unique=True)
    weight = db.Column(db.Float, default=1.0)
    contact_person = db.Column(db.String(100))
    email = db.Column(db.String(255))
    phone = db.Column(db.String(20))
    is_active = db.Column(db.Boolean, default=True)
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    
    # Relationships
    assignments = db.relationship('JuryAssignment', backref='team', lazy=True)
    
    def to_dict(self):
        return {
            'id': self.id,
            'name': self.name,
            'weight': self.weight,
            'contact_person': self.contact_person,
            'email': self.email,
            'phone': self.phone,
            'is_active': self.is_active
        }

class Match(db.Model):
    """Match/game model"""
    __tablename__ = 'matches'
    
    id = db.Column(db.Integer, primary_key=True)
    home_team = db.Column(db.String(100), nullable=False)
    away_team = db.Column(db.String(100), nullable=False)
    date = db.Column(db.Date, nullable=False)
    time = db.Column(db.Time, nullable=False)
    location = db.Column(db.String(200))
    is_planned = db.Column(db.Boolean, default=False)
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    
    # Relationships
    assignments = db.relationship('JuryAssignment', backref='match', lazy=True)
    
    def to_dict(self):
        return {
            'id': self.id,
            'home_team': self.home_team,
            'away_team': self.away_team,
            'date': self.date.isoformat() if self.date else None,
            'time': self.time.strftime('%H:%M') if self.time else None,
            'location': self.location,
            'is_planned': self.is_planned
        }

class PlanningRule(db.Model):
    """Planning constraint/rule model"""
    __tablename__ = 'planning_rules'
    
    id = db.Column(db.Integer, primary_key=True)
    name = db.Column(db.String(200), nullable=False)
    rule_type = db.Column(db.String(50), nullable=False)
    priority = db.Column(db.Integer, default=1)
    is_active = db.Column(db.Boolean, default=True)
    parameters = db.Column(db.JSON)
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    
    def to_dict(self):
        return {
            'id': self.id,
            'name': self.name,
            'rule_type': self.rule_type,
            'priority': self.priority,
            'is_active': self.is_active,
            'parameters': self.parameters
        }

class JuryAssignment(db.Model):
    """Jury assignment model"""
    __tablename__ = 'jury_assignments'
    
    id = db.Column(db.Integer, primary_key=True)
    match_id = db.Column(db.Integer, db.ForeignKey('matches.id'), nullable=False)
    team_id = db.Column(db.Integer, db.ForeignKey('teams.id'), nullable=False)
    duty_type = db.Column(db.String(50), nullable=False)  # timer, referee, etc.
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    
    def to_dict(self):
        return {
            'id': self.id,
            'match_id': self.match_id,
            'team_id': self.team_id,
            'duty_type': self.duty_type,
            'match': self.match.to_dict() if self.match else None,
            'team': self.team.to_dict() if self.team else None
        }

class PlanningSession(db.Model):
    """Planning session tracking"""
    __tablename__ = 'planning_sessions'
    
    id = db.Column(db.Integer, primary_key=True)
    name = db.Column(db.String(200), nullable=False)
    start_date = db.Column(db.Date, nullable=False)
    end_date = db.Column(db.Date, nullable=False)
    status = db.Column(db.String(50), default='pending')  # pending, running, completed, failed
    result_summary = db.Column(db.JSON)
    execution_time = db.Column(db.Float)
    error_message = db.Column(db.Text)
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    completed_at = db.Column(db.DateTime)
    
    def to_dict(self):
        return {
            'id': self.id,
            'name': self.name,
            'start_date': self.start_date.isoformat() if self.start_date else None,
            'end_date': self.end_date.isoformat() if self.end_date else None,
            'status': self.status,
            'result_summary': self.result_summary,
            'execution_time': self.execution_time,
            'error_message': self.error_message,
            'created_at': self.created_at.isoformat() if self.created_at else None,
            'completed_at': self.completed_at.isoformat() if self.completed_at else None
        }

# Celery Tasks for Background Processing
@celery.task(bind=True)
def run_optimization_task(self, session_id, start_date, end_date):
    """Background task for running optimization"""
    from .optimization_engine import PythonOptimizationEngine
    
    try:
        # Update session status
        session = PlanningSession.query.get(session_id)
        session.status = 'running'
        db.session.commit()
        
        # Run optimization
        engine = PythonOptimizationEngine()
        result = engine.optimize(start_date, end_date)
        
        if result['success']:
            # Save assignments
            for assignment_data in result['assignments']:
                assignment = JuryAssignment(
                    match_id=assignment_data['match_id'],
                    team_id=assignment_data['team_id'],
                    duty_type=assignment_data['duty_type']
                )
                db.session.add(assignment)
            
            # Update session
            session.status = 'completed'
            session.result_summary = {
                'total_assignments': len(result['assignments']),
                'objective_value': result['objective_value'],
                'solve_status': result['status']
            }
            session.execution_time = result['solve_time']
            session.completed_at = datetime.utcnow()
            
        else:
            session.status = 'failed'
            session.error_message = result.get('error', 'Unknown error')
        
        db.session.commit()
        return result
        
    except Exception as e:
        session.status = 'failed'
        session.error_message = str(e)
        db.session.commit()
        raise

if __name__ == '__main__':
    app = create_app()
    app.run(debug=True, host='0.0.0.0', port=5000)
