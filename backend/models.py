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

"""
Database Models for Water Polo Jury Team Planning System
Adapted for existing MNC jury database structure
"""

from sqlalchemy import create_engine, Column, Integer, String, DateTime, Boolean, ForeignKey, Text, Enum
from sqlalchemy.ext.declarative import declarative_base
from sqlalchemy.orm import sessionmaker, relationship
from datetime import datetime
import os

Base = declarative_base()

class AllMatch(Base):
    """All matches from previous seasons and current data"""
    __tablename__ = 'all_matches'
    
    id = Column(Integer, primary_key=True, autoincrement=True)
    date_time = Column(DateTime)
    competition = Column(String(25))
    class_name = Column('class', String(25))  # 'class' is reserved keyword
    home_team = Column(String(25))
    away_team = Column(String(25))
    location = Column(String(25))
    match_id = Column(String(25), index=True)
    sportlink_id = Column(String(25))
    
    def __repr__(self):
        return f"<AllMatch(id={self.id}, home={self.home_team}, away={self.away_team}, date={self.date_time})>"

class HomeMatch(Base):
    """Home matches requiring jury assignments"""
    __tablename__ = 'home_matches'
    
    id = Column(Integer, primary_key=True, autoincrement=True)
    date_time = Column(DateTime)
    competition = Column(String(25))
    class_name = Column('class', String(25))  # 'class' is reserved keyword
    home_team = Column(String(25))
    away_team = Column(String(25))
    location = Column(String(25))
    match_id = Column(String(25), index=True)
    sportlink_id = Column(String(25))
    
    # Relationships
    jury_assignment = relationship("JuryAssignment", back_populates="match", uselist=False)
    jury_shifts = relationship("JuryShift", back_populates="match")
    
    def __repr__(self):
        return f"<HomeMatch(id={self.id}, home={self.home_team}, away={self.away_team}, date={self.date_time})>"

class JuryTeam(Base):
    """Teams available for jury duty"""
    __tablename__ = 'jury_teams'
    
    id = Column(Integer, primary_key=True, autoincrement=True)
    name = Column(String(25), unique=True)
    
    # Relationships
    jury_assignments = relationship("JuryAssignment", back_populates="team")
    jury_shifts = relationship("JuryShift", back_populates="team")
    team_points = relationship("TeamPoints", back_populates="team")
    
    def __repr__(self):
        return f"<JuryTeam(id={self.id}, name='{self.name}')>"

class MncTeam(Base):
    """MNC teams with Sportlink integration"""
    __tablename__ = 'mnc_teams'
    
    id = Column(Integer, primary_key=True, autoincrement=True)
    sportlink_team_id = Column(String(11), nullable=False)
    name = Column(String(25), unique=True)
    
    def __repr__(self):
        return f"<MncTeam(id={self.id}, name='{self.name}', sportlink_id='{self.sportlink_team_id}')>"

class ExcludedTeam(Base):
    """Teams excluded from jury duty"""
    __tablename__ = 'excluded_teams'
    
    id = Column(Integer, primary_key=True, autoincrement=True)
    name = Column(String(25), unique=True)
    
    def __repr__(self):
        return f"<ExcludedTeam(id={self.id}, name='{self.name}')>"

class StaticAssignment(Base):
    """Fixed jury assignments for specific teams"""
    __tablename__ = 'static_assignments'
    
    id = Column(Integer, primary_key=True, autoincrement=True)
    home_team = Column(String(25), nullable=False)
    jury_team = Column(String(25), nullable=False)
    points = Column(Integer, nullable=False)
    
    def __repr__(self):
        return f"<StaticAssignment(home_team='{self.home_team}', jury_team='{self.jury_team}', points={self.points})>"

class JuryAssignment(Base):
    """Current jury assignments for matches"""
    __tablename__ = 'jury_assignments'
    
    id = Column(Integer, primary_key=True, autoincrement=True)
    match_id = Column(Integer, ForeignKey('home_matches.id'), nullable=False, unique=True)
    team_id = Column(Integer, ForeignKey('jury_teams.id'), nullable=False)
    locked = Column(Boolean, default=False, nullable=False)
    
    # Relationships
    match = relationship("HomeMatch", back_populates="jury_assignment")
    team = relationship("JuryTeam", back_populates="jury_assignments")
    
    def __repr__(self):
        return f"<JuryAssignment(match_id={self.match_id}, team_id={self.team_id}, locked={self.locked})>"

class JuryShift(Base):
    """Jury shifts with points tracking"""
    __tablename__ = 'jury_shifts'
    
    id = Column(Integer, primary_key=True, autoincrement=True)
    date_time = Column(DateTime, nullable=False)
    match_id = Column(Integer, ForeignKey('home_matches.id'), nullable=False)
    team_id = Column(Integer, ForeignKey('jury_teams.id'), nullable=False)
    points = Column(Integer, nullable=False)
    locked = Column(Boolean, default=False, nullable=False)
    
    # Relationships
    match = relationship("HomeMatch", back_populates="jury_shifts")
    team = relationship("JuryTeam", back_populates="jury_shifts")
    
    def __repr__(self):
        return f"<JuryShift(match_id={self.match_id}, team_id={self.team_id}, points={self.points})>"

class TeamPoints(Base):
    """Team points tracking"""
    __tablename__ = 'team_points'
    
    id = Column(Integer, primary_key=True, autoincrement=True)
    team_id = Column(Integer, ForeignKey('jury_teams.id'), nullable=False)
    points = Column(Integer, nullable=False)
    
    # Relationships
    team = relationship("JuryTeam", back_populates="team_points")
    
    def __repr__(self):
        return f"<TeamPoints(team_id={self.team_id}, points={self.points})>"

class Match(Base):
    """Current season matches (empty table for new matches)"""
    __tablename__ = 'matches'
    
    id = Column(Integer, primary_key=True, autoincrement=True)
    date_time = Column(DateTime)
    competition = Column(String(25))
    class_name = Column('class', String(25))  # 'class' is reserved keyword
    home_team = Column(String(25))
    away_team = Column(String(25))
    location = Column(String(25))
    match_id = Column(String(25))
    sportlink_id = Column(String(25))
    
    def __repr__(self):
        return f"<Match(id={self.id}, home={self.home_team}, away={self.away_team}, date={self.date_time})>"

class User(Base):
    """System users with role-based access"""
    __tablename__ = 'users'
    
    id = Column(Integer, primary_key=True, autoincrement=True)
    username = Column(String(50), nullable=False, unique=True)
    password = Column(String(255), nullable=False)
    email = Column(String(100), nullable=False, unique=True)
    role = Column(Enum('admin', 'user', name='user_roles'), default='user')
    created_at = Column(DateTime, default=datetime.utcnow, nullable=False)
    last_login = Column(DateTime)
    is_active = Column(Boolean, default=True)
    
    def __repr__(self):
        return f"<User(id={self.id}, username='{self.username}', role='{self.role}')>"

def create_database_engine(database_url=None):
    """Create database engine using environment variables or provided URL"""
    if not database_url:
        # Build URL from environment variables
        db_user = os.getenv('DB_USER', 'root')
        db_password = os.getenv('DB_PASSWORD', '')
        db_host = os.getenv('DB_HOST', 'localhost')
        db_port = os.getenv('DB_PORT', '3306')
        db_name = os.getenv('DB_NAME', 'mnc_jury')
        
        database_url = f"mysql+pymysql://{db_user}:{db_password}@{db_host}:{db_port}/{db_name}"
    
    engine = create_engine(database_url, echo=False)
    return engine

def get_session(engine=None):
    """Get database session"""
    if not engine:
        engine = create_database_engine()
    
    Session = sessionmaker(bind=engine)
    return Session()

def init_database(engine=None):
    """Initialize database tables (only creates missing tables, preserves existing data)"""
    if not engine:
        engine = create_database_engine()
    
    # This will only create tables that don't exist
    # Existing tables with data will be preserved
    Base.metadata.create_all(engine)
    return engine

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
