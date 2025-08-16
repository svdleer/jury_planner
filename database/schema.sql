-- Water Polo Jury Planner Database Schema
-- MySQL/MariaDB compatible

-- Create database
CREATE DATABASE IF NOT EXISTS jury_planner;
USE jury_planner;

-- Teams table
CREATE TABLE teams (
    id INTEGER PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL UNIQUE,
    weight DECIMAL(3,2) DEFAULT 1.00 NOT NULL,
    is_active BOOLEAN DEFAULT TRUE NOT NULL,
    dedicated_to_team_id INTEGER NULL,
    contact_person VARCHAR(100),
    email VARCHAR(100),
    phone VARCHAR(20),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (dedicated_to_team_id) REFERENCES teams(id) ON DELETE SET NULL,
    INDEX idx_teams_active (is_active),
    INDEX idx_teams_weight (weight)
);

-- Matches table
CREATE TABLE matches (
    id INTEGER PRIMARY KEY AUTO_INCREMENT,
    date DATE NOT NULL,
    time TIME NOT NULL,
    home_team_id INTEGER NOT NULL,
    away_team_id INTEGER NOT NULL,
    location VARCHAR(200),
    competition VARCHAR(100),
    round_info VARCHAR(50),
    is_planned BOOLEAN DEFAULT FALSE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (home_team_id) REFERENCES teams(id) ON DELETE RESTRICT,
    FOREIGN KEY (away_team_id) REFERENCES teams(id) ON DELETE RESTRICT,
    INDEX idx_matches_date (date),
    INDEX idx_matches_planned (is_planned),
    INDEX idx_matches_home_team (home_team_id),
    INDEX idx_matches_away_team (away_team_id)
);

-- Jury assignments table
CREATE TABLE jury_assignments (
    id INTEGER PRIMARY KEY AUTO_INCREMENT,
    match_id INTEGER NOT NULL,
    jury_team_id INTEGER NOT NULL,
    duty_type ENUM('setup', 'clock', 'bar', 'teardown') NOT NULL,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notes TEXT,
    
    FOREIGN KEY (match_id) REFERENCES matches(id) ON DELETE CASCADE,
    FOREIGN KEY (jury_team_id) REFERENCES teams(id) ON DELETE RESTRICT,
    UNIQUE KEY unique_match_duty (match_id, duty_type),
    INDEX idx_assignments_match (match_id),
    INDEX idx_assignments_team (jury_team_id),
    INDEX idx_assignments_duty (duty_type)
);

-- Planning rules table
CREATE TABLE planning_rules (
    id INTEGER PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    rule_type ENUM('forbidden', 'not_preferred', 'less_preferred', 'most_preferred') NOT NULL,
    weight DECIMAL(8,2) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE NOT NULL,
    parameters JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_rules_type (rule_type),
    INDEX idx_rules_active (is_active),
    INDEX idx_rules_weight (weight)
);

-- Team availability table
CREATE TABLE team_availability (
    id INTEGER PRIMARY KEY AUTO_INCREMENT,
    team_id INTEGER NOT NULL,
    date DATE NOT NULL,
    is_available BOOLEAN DEFAULT TRUE NOT NULL,
    reason VARCHAR(200),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE,
    UNIQUE KEY unique_team_date_availability (team_id, date),
    INDEX idx_availability_date (date),
    INDEX idx_availability_team (team_id)
);

-- Planning sessions table
CREATE TABLE planning_sessions (
    id INTEGER PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status VARCHAR(20) DEFAULT 'pending',
    result_summary JSON,
    execution_time DECIMAL(8,3),
    error_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    
    INDEX idx_sessions_status (status),
    INDEX idx_sessions_date_range (start_date, end_date),
    INDEX idx_sessions_created (created_at)
);

-- Insert sample data

-- Sample teams
INSERT INTO teams (name, weight, contact_person, email, phone) VALUES
('Aqua Warriors', 1.0, 'John Smith', 'john@aquawarriors.com', '+31612345678'),
('Water Lions', 1.2, 'Sarah Johnson', 'sarah@waterlions.com', '+31687654321'),
('Pool Sharks', 0.8, 'Mike Davis', 'mike@poolsharks.com', '+31698765432'),
('Wave Riders', 1.0, 'Emma Wilson', 'emma@waveriders.com', '+31676543210'),
('Splash Masters', 1.1, 'Chris Brown', 'chris@splashmasters.com', '+31665432109'),
('Blue Dolphins', 0.9, 'Lisa Garcia', 'lisa@bluedolphins.com', '+31654321098');

-- Sample dedicated team relationship
UPDATE teams SET dedicated_to_team_id = 1 WHERE name = 'Pool Sharks';

-- Sample matches
INSERT INTO matches (date, time, home_team_id, away_team_id, location, competition) VALUES
('2025-08-20', '19:00:00', 1, 2, 'Aqua Center Pool 1', 'League Championship'),
('2025-08-21', '20:00:00', 3, 4, 'Aqua Center Pool 2', 'League Championship'),
('2025-08-22', '18:30:00', 5, 6, 'Aqua Center Pool 1', 'League Championship'),
('2025-08-27', '19:00:00', 2, 3, 'Aqua Center Pool 1', 'League Championship'),
('2025-08-28', '20:00:00', 4, 5, 'Aqua Center Pool 2', 'League Championship'),
('2025-08-29', '18:30:00', 6, 1, 'Aqua Center Pool 1', 'League Championship');

-- Sample planning rules
INSERT INTO planning_rules (name, description, rule_type, weight, parameters) VALUES
('Team Unavailable - Pool Sharks Aug 21', 
 'Pool Sharks unavailable on August 21st due to team event', 
 'forbidden', 
 -1000.0, 
 JSON_OBJECT('constraint_type', 'team_unavailable', 'team_id', 3, 'date', '2025-08-21', 'reason', 'Team event')),

('Avoid Consecutive Matches', 
 'Teams should avoid working consecutive matches to prevent fatigue', 
 'not_preferred', 
 -40.0, 
 JSON_OBJECT('constraint_type', 'avoid_consecutive_matches', 'applies_to_all_teams', true, 'max_consecutive', 1)),

('Clock Duty Preference - Wave Riders', 
 'Wave Riders prefer clock duty', 
 'most_preferred', 
 20.0, 
 JSON_OBJECT('constraint_type', 'preferred_duty', 'team_id', 4, 'duty_type', 'clock')),

('Rest Between Matches - All Teams', 
 'All teams should have at least 1 day rest between assignments', 
 'less_preferred', 
 -25.0, 
 JSON_OBJECT('constraint_type', 'rest_between_matches', 'applies_to_all_teams', true, 'min_rest_days', 1));

-- Sample team availability restrictions
INSERT INTO team_availability (team_id, date, is_available, reason) VALUES
(3, '2025-08-21', FALSE, 'Team event'),
(5, '2025-08-29', FALSE, 'Player tournament');

COMMIT;
