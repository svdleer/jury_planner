#!/bin/bash

# Production Server Setup Script for Plesk Server
# Run this script on your production server (jury2025.useless.nl)

set -e

# Colors
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

echo -e "${BLUE}🏭 Setting up Plesk Production Server for Jury Planner${NC}"

# Define Plesk paths
DOMAIN_ROOT="/home/httpd/vhosts/jury2025.useless.nl"
HTTPDOCS="$DOMAIN_ROOT/httpdocs"
DEPLOY_DIR="$DOMAIN_ROOT/jury_planner"
GIT_DIR="$DOMAIN_ROOT/git/jury2025.git"

# Create git directory if it doesn't exist
echo -e "${GREEN}📁 Creating git directory...${NC}"
mkdir -p "$DOMAIN_ROOT/git"

# Initialize bare repository for receiving pushes
echo -e "${GREEN}🗂️  Initializing git repository...${NC}"
cd "$DOMAIN_ROOT/git"
if [ ! -d "jury2025.git" ]; then
    git init --bare jury2025.git
    echo -e "${GREEN}✅ Created bare git repository${NC}"
else
    echo -e "${YELLOW}ℹ️  Git repository already exists${NC}"
fi

# Create post-receive hook for automatic deployment
echo -e "${GREEN}🔧 Setting up deployment hook...${NC}"
cat > "$GIT_DIR/hooks/post-receive" << 'EOF'
#!/bin/bash

# Post-receive hook for Plesk automatic deployment
DOMAIN_ROOT="/home/httpd/vhosts/jury2025.useless.nl"
HTTPDOCS="$DOMAIN_ROOT/httpdocs"
DEPLOY_DIR="$DOMAIN_ROOT/jury_planner"
GIT_DIR="$DOMAIN_ROOT/git/jury2025.git"

echo "🚀 Starting Plesk automatic deployment..."

# Create deployment directory (one level above httpdocs)
if [ ! -d "$DEPLOY_DIR" ]; then
    echo "📁 Creating deployment directory: $DEPLOY_DIR"
    mkdir -p "$DEPLOY_DIR"
fi

# Clone/update the working directory
if [ ! -d "$DEPLOY_DIR/.git" ]; then
    echo "📦 Initial deployment - cloning repository..."
    git clone "$GIT_DIR" "$DEPLOY_DIR"
else
    echo "🔄 Updating existing deployment..."
    cd "$DEPLOY_DIR"
    git fetch origin
    git reset --hard origin/main
fi

# Backup existing httpdocs if it exists and is not our deployment
echo "🔗 Setting up web-accessible PHP interface in httpdocs root..."
if [ -d "$HTTPDOCS" ] && [ ! -L "$HTTPDOCS" ]; then
    echo "📦 Backing up existing httpdocs..."
    mv "$HTTPDOCS" "$HTTPDOCS.backup.$(date +%Y%m%d_%H%M%S)" 2>/dev/null || true
fi

# Remove httpdocs if it's a symlink or directory
if [ -L "$HTTPDOCS" ] || [ -d "$HTTPDOCS" ]; then
    rm -rf "$HTTPDOCS"
fi

# Create symlink from httpdocs to php_interface (direct root access)
ln -sf "$DEPLOY_DIR/php_interface" "$HTTPDOCS"

# Set proper permissions for Plesk
echo "🔐 Setting Plesk permissions..."
chown -R jury2025:psacln "$DEPLOY_DIR"
chmod -R 755 "$DEPLOY_DIR"
chmod -R 755 "$HTTPDOCS"

# Copy environment file if it doesn't exist
if [ ! -f "$DEPLOY_DIR/.env" ] && [ -f "$DEPLOY_DIR/.env.example" ]; then
    echo "📝 Creating environment file..."
    cp "$DEPLOY_DIR/.env.example" "$DEPLOY_DIR/.env"
fi

# Set specific permissions for sensitive files
chmod 600 "$DEPLOY_DIR/.env" 2>/dev/null || true
chmod 644 "$HTTPDOCS/.htaccess" 2>/dev/null || true

echo "✅ Plesk deployment completed successfully!"
echo "🌍 PHP Interface available at: https://jury2025.useless.nl/"
echo "🔍 Test connection at: https://jury2025.useless.nl/test_connection.php"
echo "📊 Dashboard at: https://jury2025.useless.nl/mnc_dashboard.php"
EOF

# Make the hook executable
chmod +x "$GIT_DIR/hooks/post-receive"

echo -e "${GREEN}✅ Post-receive hook created${NC}"

# Create Plesk-compatible directory structure
echo -e "${GREEN}🌐 Setting up Plesk directory structure...${NC}"
mkdir -p "$DEPLOY_DIR"
mkdir -p "$HTTPDOCS"

echo -e "${BLUE}📋 Plesk Deployment Configuration:${NC}"
cat << 'EOF'

Plesk Directory Structure:
├── /home/httpd/vhosts/jury2025.useless.nl/
│   ├── httpdocs/                    # Symlink to php_interface (root web access)
│   ├── jury_planner/                # Full repository (private)
│   │   ├── php_interface/           # PHP web interface source
│   │   ├── backend/                 # Python backend (private)
│   │   ├── planning_engine/         # Planning engine (private)
│   │   └── .env                     # Environment config (private)
│   └── git/
│       └── jury2025.git/            # Bare git repository

Web Access:
- https://jury2025.useless.nl/           # Main interface (root)
- https://jury2025.useless.nl/teams.php  # Team management
- https://jury2025.useless.nl/matches.php # Match management

EOF

echo -e "${GREEN}🎉 Plesk production server setup completed!${NC}"
echo -e "${YELLOW}💡 Next steps:${NC}"
echo -e "   1. Push from your local machine: ./deploy.sh"
echo -e "   2. Test the deployment: https://jury2025.useless.nl/"
echo -e "   3. Verify database: https://jury2025.useless.nl/test_connection.php"
