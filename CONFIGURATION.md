# Water Polo Jury Planner Configuration Guide

## System Architecture

The Water Polo Jury Planning System is built with a modular architecture consisting of:

### 1. Backend Components
- **Flask Web Framework**: RESTful API server
- **SQLAlchemy ORM**: Database abstraction layer
- **MySQL Database**: Persistent data storage
- **Planning Engine**: Constraint-based scheduling using OR-Tools
- **Rule Management**: Modular rule configuration system

### 2. Frontend Components
- **Bootstrap 5**: Modern, responsive UI framework
- **Vanilla JavaScript**: Interactive web interface
- **Font Awesome**: Icon library

### 3. Planning Engine
The core planning engine uses Google OR-Tools CP-SAT solver with:
- **Hard Constraints**: Rules that cannot be violated (forbidden)
- **Soft Constraints**: Rules with penalties/bonuses (preferences)
- **Objective Function**: Maximizes overall satisfaction while balancing workload

## Configuration Options

### Rule Types and Weights

#### Forbidden Rules (Hard Constraints)
- **Weight Range**: -1000.0 to -500.0
- **Purpose**: Absolute restrictions that cannot be violated
- **Examples**: Team unavailability, dedicated team restrictions

#### Not Preferred Rules
- **Weight Range**: -100.0 to -20.0
- **Purpose**: Strong negative preferences
- **Examples**: Avoid consecutive matches, maximum duties per period

#### Less Preferred Rules
- **Weight Range**: -50.0 to -5.0
- **Purpose**: Mild negative preferences
- **Examples**: Avoid specific opponents, rest between matches

#### Most Preferred Rules
- **Weight Range**: 5.0 to 50.0
- **Purpose**: Positive preferences and bonuses
- **Examples**: Preferred duty assignments, preferred dates

### Team Configuration

#### Team Weight System
- **Weight 1.0**: Standard workload
- **Weight > 1.0**: Higher capacity teams (more assignments)
- **Weight < 1.0**: Lower capacity teams (fewer assignments)
- **Weight 0.0**: Inactive teams (no assignments)

#### Dedicated Teams
Teams can be dedicated to specific other teams, meaning they primarily work matches where their dedicated team plays. Exception: Can work other matches if it's the only match of the day.

### Match Duties

The system handles four types of duties per match:
1. **Setup**: Preparation before the match
2. **Clock**: Timekeeping during the match
3. **Bar**: Refreshment service
4. **Teardown**: Cleanup after the match

Each match requires exactly one team per duty, and each team can only have one duty per match.

## Environment Variables

Configure these in your `.env` file:

```env
# Flask Configuration
FLASK_APP=app.py
FLASK_ENV=development
SECRET_KEY=your-secure-secret-key

# Database Configuration
DB_HOST=localhost
DB_PORT=3306
DB_NAME=jury_planner
DB_USER=your-username
DB_PASSWORD=your-password
DATABASE_URL=mysql+pymysql://${DB_USER}:${DB_PASSWORD}@${DB_HOST}:${DB_PORT}/${DB_NAME}

# Planning Engine Configuration
MAX_PLANNING_TIME=300  # Maximum solver time in seconds
DEFAULT_RULE_WEIGHT=1.0
STRICT_MODE=False  # If True, planning fails if any hard constraint is violated
```

## API Endpoints

### Teams
- `GET /api/teams` - List all teams
- `POST /api/teams` - Create a new team
- `PUT /api/teams/<id>` - Update a team
- `DELETE /api/teams/<id>` - Delete a team

### Matches
- `GET /api/matches` - List matches with optional filters
- `POST /api/matches` - Create a new match
- `PUT /api/matches/<id>` - Update a match

### Planning Rules
- `GET /api/rules/templates` - Get available rule templates
- `GET /api/rules` - List all planning rules
- `POST /api/rules` - Create a new rule
- `PUT /api/rules/<id>` - Update a rule

### Planning Engine
- `POST /api/planning/run` - Execute the planning algorithm

### Export
- `GET /api/export/<format>` - Export schedule (pdf, excel, csv, txt)

## Database Schema

### Core Tables
- **teams**: Team information and configuration
- **matches**: Match schedule and details
- **jury_assignments**: Assignments of teams to match duties
- **planning_rules**: Configurable planning constraints
- **team_availability**: Team availability restrictions
- **planning_sessions**: Planning execution history

## Performance Tuning

### Planning Algorithm
- **Solver Timeout**: Adjust `MAX_PLANNING_TIME` for larger problems
- **Memory Usage**: OR-Tools automatically manages memory
- **Complexity**: O(teams × matches × duties × rules)

### Database Optimization
- Indexes on frequently queried columns (date, team_id, etc.)
- Regular maintenance for optimal performance
- Consider connection pooling for high-load scenarios

## Security Considerations

### Authentication
- Current version: No authentication (suitable for internal use)
- Recommended: Add authentication middleware for production

### Data Validation
- All user inputs are validated
- SQL injection protection via SQLAlchemy ORM
- XSS protection in frontend templates

### Network Security
- Use HTTPS in production
- Configure firewall to restrict database access
- Regular security updates for dependencies

## Monitoring and Logging

### Application Logs
- Planning execution times and results
- Error tracking and debugging information
- Performance metrics

### Database Monitoring
- Query performance analysis
- Storage usage tracking
- Backup and recovery procedures

## Backup and Recovery

### Database Backup
```bash
# Daily backup
mysqldump -u user -p jury_planner > backup_$(date +%Y%m%d).sql

# Automated backup script
0 2 * * * mysqldump -u user -p jury_planner > /backups/jury_planner_$(date +\%Y\%m\%d).sql
```

### Application Backup
- Configuration files (.env, custom rules)
- Exported schedules and reports
- Planning session history

## Deployment Options

### Development
- Built-in Flask development server
- SQLite database for testing
- Local file storage

### Production
- WSGI server (Gunicorn, uWSGI)
- MySQL/MariaDB database
- Reverse proxy (Nginx, Apache)
- Process management (systemd, supervisord)

### Docker Deployment
A Dockerfile can be created for containerized deployment:

```dockerfile
FROM python:3.9-slim
WORKDIR /app
COPY requirements.txt .
RUN pip install -r requirements.txt
COPY . .
EXPOSE 5000
CMD ["gunicorn", "--bind", "0.0.0.0:5000", "app:app"]
```

## Troubleshooting

### Common Issues

#### Planning Fails
- Check team availability and weights
- Verify match data is complete
- Review rule conflicts
- Increase solver timeout

#### Database Connection
- Verify credentials in .env file
- Check MySQL service status
- Confirm network connectivity

#### Performance Issues
- Review database indexes
- Analyze query performance
- Check system resources during planning

### Debug Mode
Enable debug logging by setting `FLASK_ENV=development` in .env file.

## Customization

### Adding New Rule Types
1. Define rule template in `rule_manager.py`
2. Implement constraint logic in `scheduler.py`
3. Add validation in the API layer
4. Update frontend interface

### Custom Export Formats
1. Create new exporter class in `exporters.py`
2. Add route in `app.py`
3. Update frontend export options

### UI Customization
- Modify CSS in `frontend/static/css/styles.css`
- Update templates in `frontend/templates/`
- Add new JavaScript functionality in `frontend/static/js/app.js`

This configuration provides a comprehensive water polo jury planning system with advanced constraint-based scheduling, modern web interface, and extensive customization options.
