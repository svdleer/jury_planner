# Quick Start Guide - Water Polo Jury Planner

## 🚀 Quick Setup (5 minutes)

### Prerequisites
- Python 3.8 or higher
- MySQL 5.7 or higher (or MariaDB)
- Git

### 1. Clone and Setup
```bash
git clone <repository-url>
cd jury_planner
./setup.sh
```

### 2. Configure Database
Edit `.env` file with your database credentials:
```env
DB_HOST=localhost
DB_USER=your-mysql-username
DB_PASSWORD=your-mysql-password
DB_NAME=jury_planner
```

### 3. Start Application
```bash
source venv/bin/activate
python app.py
```

Visit: http://localhost:5000

## 📋 Basic Usage

### Step 1: Add Teams
1. Click "Teams" in navigation
2. Click "Add New Team"
3. Fill in team details:
   - **Name**: Team name
   - **Weight**: Workload capacity (1.0 = normal)
   - **Contact**: Contact person information

### Step 2: Add Matches
Use the CLI tool for bulk match import:
```bash
python manage.py matches add "2025-08-20" "19:00" 1 2 --location "Pool 1" --competition "League"
```

Or import from your existing system (see API documentation).

### Step 3: Configure Rules
1. Click "Rules" in navigation
2. Click "Add New Rule"
3. Choose from templates:
   - **Team Unavailable**: Block specific dates
   - **Avoid Consecutive**: Prevent back-to-back assignments
   - **Preferred Duties**: Assign teams to preferred roles
   - **Rest Between Matches**: Ensure adequate rest periods

### Step 4: Run Planning
1. Click "Planning" in navigation
2. Set date range for planning
3. Click "Run Planning Algorithm"
4. Review results and export schedule

## 🎯 Example Scenario

Let's set up a simple tournament:

### Teams Setup
```bash
python manage.py teams add "Aqua Warriors" --weight 1.0 --contact "John Smith"
python manage.py teams add "Water Lions" --weight 1.2 --contact "Sarah Johnson"  
python manage.py teams add "Pool Sharks" --weight 0.8 --contact "Mike Davis"
python manage.py teams add "Wave Riders" --weight 1.0 --contact "Emma Wilson"
```

### Matches Setup
```bash
python manage.py matches add "2025-08-20" "19:00" 1 2 --location "Pool 1"
python manage.py matches add "2025-08-21" "19:00" 3 4 --location "Pool 1"
python manage.py matches add "2025-08-22" "19:00" 1 3 --location "Pool 1"
```

### Rules Setup
1. Add "Team Unavailable" rule: Pool Sharks unavailable Aug 21
2. Add "Avoid Consecutive" rule: No team works consecutive days
3. Add "Preferred Duty" rule: Wave Riders prefer clock duty

### Run Planning
```bash
python manage.py plan "2025-08-20" "2025-08-22" --name "Weekend Tournament"
```

### View Results
```bash
python manage.py schedule --start-date "2025-08-20" --end-date "2025-08-22"
```

## 📊 Dashboard Overview

The dashboard shows:
- **Active Teams**: Number of teams available for assignment
- **Total Matches**: Matches in the system
- **Planned Matches**: Matches with jury assignments
- **Active Rules**: Currently enabled planning rules
- **Recent Sessions**: Planning execution history

## 🔧 Common Tasks

### Import Teams from CSV
Create a CSV file with columns: name,weight,contact,email,phone
```bash
# Create import script (not included, custom per data source)
```

### Bulk Match Import
For recurring schedules, modify the database directly or use the API.

### Export Schedule
- **PDF**: Professional format for printing/sharing
- **Excel**: For further analysis and modifications
- **CSV**: For import into other systems
- **TXT**: Simple text format

### Backup Data
```bash
mysqldump -u user -p jury_planner > backup.sql
```

## ⚠️ Important Notes

### Team Weights
- **1.0**: Normal capacity (recommended default)
- **1.5**: High capacity team (50% more assignments)
- **0.5**: Limited capacity team (50% fewer assignments)

### Rule Priorities
Rules are processed by weight:
- **Forbidden** (-1000): Absolute constraints
- **Not Preferred** (-50): Strong avoidance
- **Less Preferred** (-15): Mild avoidance  
- **Most Preferred** (+20): Positive preference

### Planning Performance
- Small tournaments (< 20 matches): Instant
- Medium tournaments (20-100 matches): 1-30 seconds
- Large tournaments (> 100 matches): 30-300 seconds

## 🆘 Troubleshooting

### Planning Fails
```bash
# Check team availability
python manage.py teams list

# Check matches in date range
python manage.py matches list

# Verify rules aren't conflicting
# Review rule weights and constraints
```

### Database Issues
```bash
# Test connection
mysql -u user -p jury_planner -e "SELECT COUNT(*) FROM teams;"

# Recreate database
python manage.py setup
```

### Performance Issues
- Reduce date range for planning
- Increase `MAX_PLANNING_TIME` in .env
- Review and simplify complex rules

## 📞 Next Steps

1. **Customize Rules**: Add specific constraints for your organization
2. **Import Data**: Bulk import teams and matches from existing systems
3. **Automate Planning**: Set up scheduled planning runs
4. **Integration**: Connect to external calendar/notification systems
5. **Advanced Features**: Explore conflict resolution and manual overrides

For detailed configuration and advanced features, see `CONFIGURATION.md`.

## 🔗 Useful Commands

```bash
# View all CLI options
python manage.py --help

# Quick database reset
python manage.py setup

# List everything
python manage.py teams list
python manage.py matches list

# Run planning for next week
python manage.py plan "$(date -d '+1 week' +%Y-%m-%d)" "$(date -d '+2 weeks' +%Y-%m-%d)"
```

Happy planning! 🏊‍♂️
