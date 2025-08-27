# Waterpolo Jury Team Planning System 🏊‍♂️

An intelligent, automated planning system for scheduling jury teams for water polo matches with advanced constraint-based optimization and configurable rules.

## ✨ Key Features

- **🧠 Advanced Planning Engine**: Constraint-based scheduling using Google OR-Tools CP-SAT solver
- **⚙️ Configurable Rules**: Modular rule system with weighted constraints (forbidden, preferred, etc.)
- **🌐 Modern Web Portal**: Responsive interface for viewing, managing, and exporting schedules
- **🗄️ Database Integration**: Full MySQL integration for teams, matches, and assignments
- **📊 Multiple Export Formats**: PDF, Excel, CSV, and TXT export capabilities
- **🔍 Advanced Filtering**: Filter by team, date, duty type, and more
- **⚡ Real-time Planning**: Fast optimization with detailed result analysis
- **📱 Responsive Design**: Works on desktop, tablet, and mobile devices

## 🏗️ System Architecture

```
jury_planner/
├── app.py                          # Main Flask application
├── backend/
│   ├── models.py                   # Database models (SQLAlchemy)
│   └── exporters.py               # Export utilities (PDF, Excel, CSV)
├── planning_engine/
│   ├── scheduler.py               # OR-Tools constraint solver
│   └── rule_manager.py            # Rule configuration system
├── frontend/
│   ├── templates/index.html       # Main web interface
│   └── static/
│       ├── css/styles.css         # Custom styling
│       └── js/app.js              # Frontend JavaScript
├── database/
│   └── schema.sql                 # Database schema and sample data
├── php_interface/                 # Modern PHP web interface
│   ├── config/                    # Database and app configuration
│   ├── includes/                  # Data access classes and layout
│   ├── assets/css/                # Custom Tailwind CSS styles
│   ├── index.php                  # Dashboard page
│   ├── teams.php                  # Team management page
│   └── matches.php                # Match management page
├── manage.py                      # CLI management tool
├── setup.sh                       # Automated setup script
└── requirements.txt               # Python dependencies
```

## 🚀 Quick Start

### Option 1: Automated Setup
```bash
git clone https://github.com/svdleer/jury_planner.git
cd jury_planner
./setup.sh
```

### Option 2: Manual Setup
```bash
# 1. Create virtual environment
python3 -m venv venv
source venv/bin/activate

# 2. Install dependencies
pip install -r requirements.txt

# 3. Configure database (edit .env file)
cp .env.example .env

# 4. Setup database
mysql -u root -p < database/schema.sql

# 5. Start application
python app.py
```

Visit: **http://localhost:5000**

## � PHP Web Interface

This project includes a modern PHP web interface as an alternative to the Python Flask application, providing direct database management with a sleek, responsive design.

### Key Features
- **⚡ High Performance**: Direct MySQL access without API overhead
- **📱 Responsive Design**: Built with Tailwind CSS and Alpine.js
- **🎛️ Full CRUD Operations**: Complete team and match management
- **📊 Real-time Dashboard**: Live statistics and upcoming matches
- **🔍 Advanced Filtering**: Filter matches by status, team, and date

### Database Configuration
The PHP interface is pre-configured for the production database:
```env
DB_HOST=vps.serial.nl
DB_NAME=mnc_jury
DB_USER=mnc_jury
DB_PASSWORD=5j51_hE9r
```

### Quick Setup
```bash
cd php_interface
cp .env.example .env
# Configure your web server to serve from php_interface/
# Visit test_connection.php to verify database connectivity
```

### Technology Stack
- **Backend**: PHP 8+, PDO for MySQL
- **Frontend**: Tailwind CSS 3.x, Alpine.js 3.x
- **Design**: Mobile-first, water polo themed
- **Security**: Prepared statements, input sanitization

For detailed setup and usage instructions, see `php_interface/README.md`

## �🎯 Core Components

### 1. Planning Engine
- **Solver**: Google OR-Tools CP-SAT for optimal scheduling
- **Objective**: Maximize satisfaction while balancing workload
- **Constraints**: Hard (forbidden) and soft (weighted preferences)
- **Performance**: Handles 100+ matches with complex rules in seconds

### 2. Rule System
| Rule Type | Weight Range | Purpose | Example |
|-----------|--------------|---------|---------|
| **Forbidden** | -1000 to -500 | Hard constraints | Team unavailable |
| **Not Preferred** | -100 to -20 | Strong avoidance | Consecutive matches |
| **Less Preferred** | -50 to -5 | Mild avoidance | Specific opponents |
| **Most Preferred** | +5 to +50 | Positive bonus | Preferred duties |

### 3. Team Management
- **Weight System**: Proportional workload distribution (0.5x to 2.0x)
- **Dedicated Teams**: Teams assigned to specific clubs/matches
- **Availability**: Date-based availability restrictions
- **Contact Management**: Full contact information storage

### 4. Match Duties
Each match requires four jury roles:
- **🔧 Setup**: Pre-match preparation
- **⏰ Clock**: Timekeeping during match
- **🍹 Bar**: Refreshment service
- **🧹 Teardown**: Post-match cleanup

## 📋 Usage Examples

### CLI Operations
```bash
# Add teams
python manage.py teams add "Aqua Warriors" --weight 1.0 --contact "John Smith"

# Add matches
python manage.py matches add "2025-08-20" "19:00" 1 2 --location "Pool 1"

# Run planning
python manage.py plan "2025-08-20" "2025-08-27" --name "Weekly Schedule"

# View schedule
python manage.py schedule --start-date "2025-08-20"
```

### Web Interface
1. **Dashboard**: Overview of teams, matches, and planning status
2. **Teams**: Add/edit teams with weights and contact info
3. **Schedule**: View assignments with filtering and export options
4. **Rules**: Configure planning constraints and preferences
5. **Planning**: Execute algorithm and view detailed results

## 🔧 Configuration

### Environment Variables (.env)
```env
# Database
DB_HOST=localhost
DB_USER=your-username
DB_PASSWORD=your-password
DB_NAME=jury_planner

# Planning Engine
MAX_PLANNING_TIME=300
STRICT_MODE=False
```

### Team Weight Examples
- **2.0**: High-capacity team (double normal workload)
- **1.0**: Standard team (normal workload)
- **0.5**: Limited team (half normal workload)
- **0.0**: Inactive team (no assignments)

## 🛠️ Technology Stack

- **Backend**: Python 3.8+, Flask 2.3, SQLAlchemy 2.0
- **Database**: MySQL 5.7+ / MariaDB 10.3+
- **Frontend**: Bootstrap 5, Vanilla JavaScript, Font Awesome
- **Planning**: Google OR-Tools CP-SAT Solver
- **Export**: ReportLab (PDF), OpenPyXL (Excel), Pandas
- **Dependencies**: See `requirements.txt` for complete list

## 📊 Performance Metrics

| Scale | Matches | Teams | Rules | Planning Time |
|-------|---------|-------|-------|---------------|
| Small | 1-20 | 4-8 | 1-5 | < 1 second |
| Medium | 21-100 | 8-15 | 5-15 | 1-30 seconds |
| Large | 100+ | 15+ | 15+ | 30-300 seconds |

## 🔒 Security & Production

### Development
- Built-in Flask development server
- SQLite option for testing
- Debug mode with detailed error reporting

### Production Recommendations
- WSGI server (Gunicorn/uWSGI)
- Reverse proxy (Nginx/Apache)
- HTTPS encryption
- Authentication middleware
- Regular security updates

## 🚀 Deployment & Automation

### Automated Deployment to Production

The project includes automated deployment scripts for seamless production deployment:

#### Local Deployment Commands
```bash
# Full deployment (commits all changes and deploys)
./deploy.sh "Your commit message"

# Quick PHP interface deployment
./deploy-php.sh "PHP updates"

# Deploy without committing (if already committed)
git push origin main && git push production main
```

#### Production Server Setup
1. **On your production server** (`jury2025@jury2025.useless.nl`):
```bash
# Download and run the setup script
wget https://github.com/svdleer/jury_planner/raw/main/production-setup.sh
chmod +x production-setup.sh
./production-setup.sh
```

2. **Configure your web server** to serve from `/var/www/html/jury2025/php_interface/`

#### Automatic Deployment Workflow
- **GitHub Actions**: Automatically deploys on push to main branch
- **Git Hooks**: Production server automatically updates on git push
- **Environment Management**: Secure credential handling via `.env` files

#### Repository Configuration
- **Origin**: `https://github.com/svdleer/jury_planner.git` (Development)
- **Production**: `jury2025@jury2025.useless.nl:/home/httpd/vhosts/jury2025.useless.nl/git/jury2025.git` (Plesk Deployment)

### Deployment Features
- ✅ **Automatic commits** with timestamps
- ✅ **Dual-push** to GitHub and Plesk production server
- ✅ **Server-side hooks** for instant deployment
- ✅ **Plesk-compatible** directory structure
- ✅ **Symlink strategy** for web-accessible files only
- ✅ **Permission management** for Plesk hosting
- ✅ **Environment configuration** handling
- ✅ **Rollback capabilities** via git
- ✅ **Deployment status** notifications

### Quick Production Access
After deployment, access your application at:
- **PHP Interface**: `https://jury2025.useless.nl/`
- **Database Test**: `https://jury2025.useless.nl/test_connection.php`
- **Team Management**: `https://jury2025.useless.nl/teams.php`
- **Match Management**: `https://jury2025.useless.nl/matches.php`

### Plesk-Specific Setup
For detailed Plesk hosting setup instructions, see: **[PLESK_DEPLOYMENT.md](PLESK_DEPLOYMENT.md)**

## 📖 Documentation

- **[QUICKSTART.md](QUICKSTART.md)**: 5-minute setup guide
- **[CONFIGURATION.md](CONFIGURATION.md)**: Detailed configuration options
- **API Documentation**: Available at `/api/docs` (when running)

## 🤝 Contributing

1. Fork the repository
2. Create feature branch (`git checkout -b feature/amazing-feature`)
3. Commit changes (`git commit -m 'Add amazing feature'`)
4. Push to branch (`git push origin feature/amazing-feature`)
5. Open Pull Request

## 📝 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🙏 Acknowledgments

- Google OR-Tools team for the excellent optimization library
- Water polo community for requirements and testing feedback
- Open source contributors for various dependencies

## 📞 Support

- **Issues**: GitHub Issues for bug reports and feature requests
- **Documentation**: Comprehensive guides and API documentation
- **Community**: Water polo planning community discussions

---

**Ready to revolutionize your water polo jury planning? Get started in 5 minutes!** 🚀
