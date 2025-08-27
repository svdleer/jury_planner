#!/bin/bash

# Database Migration Script for Constraint Editor
# This script ensures the required tables exist for the constraint editor

echo "🔄 Starting database migration for constraint editor..."

# Database connection details
DB_HOST="localhost"
DB_NAME="mnc_jury"
DB_USER="jury_user"
DB_PASS="your_password_here"

# Function to execute SQL
execute_sql() {
    local sql="$1"
    mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "$sql"
}

# Check if teams table exists
echo "📋 Checking teams table..."
if ! execute_sql "DESCRIBE teams;" >/dev/null 2>&1; then
    echo "➕ Creating teams table..."
    execute_sql "
    CREATE TABLE teams (
        id INTEGER PRIMARY KEY AUTO_INCREMENT,
        team_name VARCHAR(100) NOT NULL UNIQUE,
        weight DECIMAL(3,2) DEFAULT 1.00 NOT NULL,
        is_active BOOLEAN DEFAULT TRUE NOT NULL,
        dedicated_to_team VARCHAR(100) NULL,
        contact_person VARCHAR(100),
        email VARCHAR(100),
        phone VARCHAR(20),
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        
        INDEX idx_teams_active (is_active),
        INDEX idx_teams_weight (weight)
    );"
    
    # Insert sample teams
    execute_sql "
    INSERT INTO teams (team_name, dedicated_to_team) VALUES
    ('MNC Dordrecht H1', 'MNC Dordrecht H1'),
    ('MNC Dordrecht H3', 'MNC Dordrecht H3'),
    ('MNC Dordrecht H7', 'MNC Dordrecht H7'),
    ('MNC Dordrecht Da1', 'MNC Dordrecht Da1'),
    ('MNC Dordrecht Da3', 'MNC Dordrecht Da3'),
    ('Pool Sharks', NULL),
    ('Wave Riders', NULL),
    ('Water Warriors', NULL);"
    
    echo "✅ Teams table created with sample data"
else
    echo "✅ Teams table already exists"
fi

# Check if planning_rules table exists
echo "📋 Checking planning_rules table..."
if ! execute_sql "DESCRIBE planning_rules;" >/dev/null 2>&1; then
    echo "➕ Creating planning_rules table..."
    execute_sql "
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
    );"
    echo "✅ Planning rules table created"
else
    echo "✅ Planning rules table already exists"
fi

# Check if matches table exists  
echo "📋 Checking matches table..."
if ! execute_sql "DESCRIBE matches;" >/dev/null 2>&1; then
    echo "➕ Creating matches table..."
    execute_sql "
    CREATE TABLE matches (
        id INTEGER PRIMARY KEY AUTO_INCREMENT,
        date_time DATETIME NOT NULL,
        home_team VARCHAR(100) NOT NULL,
        away_team VARCHAR(100) NOT NULL,
        location VARCHAR(200),
        competition VARCHAR(100),
        round_info VARCHAR(50),
        is_locked BOOLEAN DEFAULT FALSE,
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        
        INDEX idx_matches_date (date_time),
        INDEX idx_matches_home_team (home_team),
        INDEX idx_matches_away_team (away_team),
        INDEX idx_matches_competition (competition)
    );"
    echo "✅ Matches table created"
else
    echo "✅ Matches table already exists"
fi

# Check if jury_assignments table exists
echo "📋 Checking jury_assignments table..."
if ! execute_sql "DESCRIBE jury_assignments;" >/dev/null 2>&1; then
    echo "➕ Creating jury_assignments table..."
    execute_sql "
    CREATE TABLE jury_assignments (
        id INTEGER PRIMARY KEY AUTO_INCREMENT,
        match_id INTEGER NOT NULL,
        jury_team_name VARCHAR(100) NOT NULL,
        assignment_type ENUM('clock', 'score', 'general') DEFAULT 'general',
        points_awarded DECIMAL(5,2) DEFAULT 0,
        assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        assigned_by VARCHAR(100),
        
        INDEX idx_assignments_match (match_id),
        INDEX idx_assignments_team (jury_team_name),
        INDEX idx_assignments_type (assignment_type),
        UNIQUE KEY unique_match_team (match_id, jury_team_name)
    );"
    echo "✅ Jury assignments table created"
else
    echo "✅ Jury assignments table already exists"
fi

echo "🎉 Database migration completed successfully!"
echo ""
echo "🔧 Next steps:"
echo "1. Update the database credentials in this script"
echo "2. Run this script on the production server"
echo "3. Test the constraint editor at: https://jury2025.useless.nl/constraint_editor.php"
