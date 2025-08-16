from flask_sqlalchemy import SQLAlchemy
from datetime import datetime
from enum import Enum

db = SQLAlchemy()

class RuleType(Enum):
    FORBIDDEN = "forbidden"
    NOT_PREFERRED = "not_preferred"
    LESS_PREFERRED = "less_preferred"
    MOST_PREFERRED = "most_preferred"

class DutyType(Enum):
    SETUP = "setup"
    CLOCK = "clock"
    BAR = "bar"
    TEARDOWN = "teardown"

class Team(db.Model):
    __tablename__ = 'teams'
    
    id = db.Column(db.Integer, primary_key=True)
    name = db.Column(db.String(100), nullable=False, unique=True)
    weight = db.Column(db.Float, default=1.0, nullable=False)
    is_active = db.Column(db.Boolean, default=True, nullable=False)
    dedicated_to_team_id = db.Column(db.Integer, db.ForeignKey('teams.id'), nullable=True)
    contact_person = db.Column(db.String(100))
    email = db.Column(db.String(100))
    phone = db.Column(db.String(20))
    notes = db.Column(db.Text)
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    updated_at = db.Column(db.DateTime, default=datetime.utcnow, onupdate=datetime.utcnow)
    
    # Relationships
    dedicated_to_team = db.relationship('Team', remote_side=[id], backref='dedicated_teams')
    jury_assignments = db.relationship('JuryAssignment', back_populates='jury_team', cascade='all, delete-orphan')
    
    def __repr__(self):
        return f'<Team {self.name}>'

class Match(db.Model):
    __tablename__ = 'matches'
    
    id = db.Column(db.Integer, primary_key=True)
    date = db.Column(db.Date, nullable=False)
    time = db.Column(db.Time, nullable=False)
    home_team_id = db.Column(db.Integer, db.ForeignKey('teams.id'), nullable=False)
    away_team_id = db.Column(db.Integer, db.ForeignKey('teams.id'), nullable=False)
    location = db.Column(db.String(200))
    competition = db.Column(db.String(100))
    round_info = db.Column(db.String(50))
    is_planned = db.Column(db.Boolean, default=False)
    notes = db.Column(db.Text)
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    updated_at = db.Column(db.DateTime, default=datetime.utcnow, onupdate=datetime.utcnow)
    
    # Relationships
    home_team = db.relationship('Team', foreign_keys=[home_team_id], backref='home_matches')
    away_team = db.relationship('Team', foreign_keys=[away_team_id], backref='away_matches')
    jury_assignments = db.relationship('JuryAssignment', back_populates='match', cascade='all, delete-orphan')
    
    def __repr__(self):
        return f'<Match {self.home_team.name} vs {self.away_team.name} on {self.date}>'

class JuryAssignment(db.Model):
    __tablename__ = 'jury_assignments'
    
    id = db.Column(db.Integer, primary_key=True)
    match_id = db.Column(db.Integer, db.ForeignKey('matches.id'), nullable=False)
    jury_team_id = db.Column(db.Integer, db.ForeignKey('teams.id'), nullable=False)
    duty_type = db.Column(db.Enum(DutyType), nullable=False)
    assigned_at = db.Column(db.DateTime, default=datetime.utcnow)
    notes = db.Column(db.Text)
    
    # Relationships
    match = db.relationship('Match', back_populates='jury_assignments')
    jury_team = db.relationship('Team', back_populates='jury_assignments')
    
    __table_args__ = (
        db.UniqueConstraint('match_id', 'duty_type', name='unique_match_duty'),
    )
    
    def __repr__(self):
        return f'<JuryAssignment {self.jury_team.name} - {self.duty_type.value} for Match {self.match_id}>'

class PlanningRule(db.Model):
    __tablename__ = 'planning_rules'
    
    id = db.Column(db.Integer, primary_key=True)
    name = db.Column(db.String(100), nullable=False)
    description = db.Column(db.Text)
    rule_type = db.Column(db.Enum(RuleType), nullable=False)
    weight = db.Column(db.Float, nullable=False)
    is_active = db.Column(db.Boolean, default=True, nullable=False)
    
    # Rule parameters (JSON field for flexibility)
    parameters = db.Column(db.JSON)
    
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    updated_at = db.Column(db.DateTime, default=datetime.utcnow, onupdate=datetime.utcnow)
    
    def __repr__(self):
        return f'<PlanningRule {self.name}>'

class TeamAvailability(db.Model):
    __tablename__ = 'team_availability'
    
    id = db.Column(db.Integer, primary_key=True)
    team_id = db.Column(db.Integer, db.ForeignKey('teams.id'), nullable=False)
    date = db.Column(db.Date, nullable=False)
    is_available = db.Column(db.Boolean, default=True, nullable=False)
    reason = db.Column(db.String(200))
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    
    # Relationships
    team = db.relationship('Team', backref='availability_records')
    
    __table_args__ = (
        db.UniqueConstraint('team_id', 'date', name='unique_team_date_availability'),
    )
    
    def __repr__(self):
        return f'<TeamAvailability {self.team.name} on {self.date}: {self.is_available}>'

class PlanningSession(db.Model):
    __tablename__ = 'planning_sessions'
    
    id = db.Column(db.Integer, primary_key=True)
    name = db.Column(db.String(100), nullable=False)
    start_date = db.Column(db.Date, nullable=False)
    end_date = db.Column(db.Date, nullable=False)
    status = db.Column(db.String(20), default='pending')  # pending, running, completed, failed
    result_summary = db.Column(db.JSON)
    execution_time = db.Column(db.Float)  # seconds
    error_message = db.Column(db.Text)
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    completed_at = db.Column(db.DateTime)
    
    def __repr__(self):
        return f'<PlanningSession {self.name}>'
