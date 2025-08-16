#!/bin/bash

# Clean Plesk Setup - No Symlinks!
# Run this script on your production server (jury2025.useless.nl)

set -e

# Colors
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

echo -e "${BLUE}🧹 Clean Plesk Setup for Jury Planner (No Symlinks!)${NC}"

# Define Plesk paths
DOMAIN_ROOT="/home/httpd/vhosts/jury2025.useless.nl"
HTTPDOCS="$DOMAIN_ROOT/httpdocs"
GIT_DIR="$DOMAIN_ROOT/git/jury2025.git"

echo -e "${RED}🗑️  REMOVING ALL EXISTING DEPLOYMENT...${NC}"

# Remove everything and start fresh
if [ -L "$HTTPDOCS" ]; then
    echo "🗑️  Removing symlink httpdocs"
    rm -f "$HTTPDOCS"
elif [ -d "$HTTPDOCS" ]; then
    echo "📦 Backing up existing httpdocs"
    mv "$HTTPDOCS" "$HTTPDOCS.backup.$(date +%Y%m%d_%H%M%S)"
fi

# Remove old jury_planner directory if it exists
if [ -d "$DOMAIN_ROOT/jury_planner" ]; then
    echo "🗑️  Removing old jury_planner directory"
    rm -rf "$DOMAIN_ROOT/jury_planner"
fi

# Remove and recreate git directory
if [ -d "$DOMAIN_ROOT/git" ]; then
    echo "🗑️  Removing old git directory"
    rm -rf "$DOMAIN_ROOT/git"
fi

# Create fresh git directory
echo -e "${GREEN}📁 Creating fresh git repository...${NC}"
mkdir -p "$DOMAIN_ROOT/git"
cd "$DOMAIN_ROOT/git"
git init --bare jury2025.git

# Create clean deployment hook
echo -e "${GREEN}🔧 Setting up clean deployment hook...${NC}"
cat > "$GIT_DIR/hooks/post-receive" << 'EOF'
#!/bin/bash

# Clean deployment hook - direct files, no symlinks
DOMAIN_ROOT="/home/httpd/vhosts/jury2025.useless.nl"
HTTPDOCS="$DOMAIN_ROOT/httpdocs"
GIT_DIR="$DOMAIN_ROOT/git/jury2025.git"

echo "🚀 Starting clean deployment (no symlinks)..."

# Remove old httpdocs completely
if [ -d "$HTTPDOCS" ] || [ -L "$HTTPDOCS" ]; then
    echo "🗑️  Removing old httpdocs"
    rm -rf "$HTTPDOCS"
fi

# Create fresh httpdocs directory
echo "📁 Creating fresh httpdocs"
mkdir -p "$HTTPDOCS"

# Clone repository directly into httpdocs
echo "📦 Deploying files directly to httpdocs"
git --git-dir="$GIT_DIR" --work-tree="$HTTPDOCS" checkout -f main

# Move PHP interface files to root of httpdocs
echo "📂 Moving PHP interface to root"
if [ -d "$HTTPDOCS/php_interface" ]; then
    # Move all files from php_interface to httpdocs root
    mv "$HTTPDOCS/php_interface"/* "$HTTPDOCS/"
    # Move hidden files too
    find "$HTTPDOCS/php_interface" -name ".*" -not -name "." -not -name ".." -exec mv {} "$HTTPDOCS/" \; 2>/dev/null || true
    # Remove empty php_interface directory
    rmdir "$HTTPDOCS/php_interface"
fi

# Handle environment file
if [ ! -f "$HTTPDOCS/.env" ] && [ -f "$HTTPDOCS/.env.example" ]; then
    echo "📝 Creating environment file"
    cp "$HTTPDOCS/.env.example" "$HTTPDOCS/.env"
fi

# Set proper permissions
echo "🔐 Setting permissions"
chown -R jury2025:psacln "$HTTPDOCS"
chmod -R 755 "$HTTPDOCS"
chmod 600 "$HTTPDOCS/.env" 2>/dev/null || true

# Clean up non-web files from httpdocs
echo "🧹 Cleaning up non-web files"
rm -rf "$HTTPDOCS/backend" 2>/dev/null || true
rm -rf "$HTTPDOCS/planning_engine" 2>/dev/null || true
rm -rf "$HTTPDOCS/database" 2>/dev/null || true
rm -f "$HTTPDOCS/requirements.txt" 2>/dev/null || true
rm -f "$HTTPDOCS/manage.py" 2>/dev/null || true
rm -f "$HTTPDOCS/app.py" 2>/dev/null || true
rm -f "$HTTPDOCS/analyze_database.py" 2>/dev/null || true
rm -f "$HTTPDOCS"/*.sh 2>/dev/null || true
rm -f "$HTTPDOCS"/*.md 2>/dev/null || true
rm -rf "$HTTPDOCS/.github" 2>/dev/null || true

echo "✅ Clean deployment completed!"
echo "🌍 Interface available at: https://jury2025.useless.nl/"
echo "🔍 Test at: https://jury2025.useless.nl/test_connection.php"
echo "📊 Dashboard at: https://jury2025.useless.nl/mnc_dashboard.php"
EOF

# Make the hook executable
chmod +x "$GIT_DIR/hooks/post-receive"

echo -e "${GREEN}✅ Clean setup completed!${NC}"
echo -e "${BLUE}📋 New Clean Structure:${NC}"
cat << 'EOF'

Clean Directory Structure (No Symlinks):
├── /home/httpd/vhosts/jury2025.useless.nl/
│   ├── httpdocs/                    # Direct PHP files only
│   │   ├── index.php                # Main entry point
│   │   ├── mnc_dashboard.php        # Dashboard
│   │   ├── test_connection.php      # Database test
│   │   ├── config/                  # Configuration
│   │   ├── includes/                # PHP classes
│   │   └── .env                     # Environment (secure)
│   └── git/
│       └── jury2025.git/            # Git repository

Web Access:
- https://jury2025.useless.nl/                  # Main interface
- https://jury2025.useless.nl/test_connection.php # Database test

EOF

echo -e "${GREEN}🎉 Ready for clean deployment!${NC}"
echo -e "${YELLOW}💡 Next steps:${NC}"
echo -e "   1. Push from local: ./deploy.sh"
echo -e "   2. Visit: https://jury2025.useless.nl/"
echo -e "   3. Everything will be in httpdocs root - NO SYMLINKS!"
