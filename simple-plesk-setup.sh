#!/bin/bash

# Simple Plesk Deployment Script - No Symlinks
# Deploys PHP files directly to httpdocs and keeps repository below

set -e

# Colors
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo -e "${BLUE}🏭 Simple Plesk Deployment (No Symlinks)${NC}"

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

# Post-receive hook for simple Plesk deployment
DOMAIN_ROOT="/home/httpd/vhosts/jury2025.useless.nl"
HTTPDOCS="$DOMAIN_ROOT/httpdocs"
DEPLOY_DIR="$DOMAIN_ROOT/jury_planner"
GIT_DIR="$DOMAIN_ROOT/git/jury2025.git"

echo "🚀 Starting simple Plesk deployment..."

# Create deployment directory below httpdocs
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

# Copy PHP files directly to httpdocs (no symlinks)
echo "📋 Copying PHP interface to httpdocs..."
if [ -d "$HTTPDOCS" ]; then
    # Backup existing files if not our deployment
    if [ ! -f "$HTTPDOCS/.jury_planner_deployed" ]; then
        echo "📦 Backing up existing httpdocs..."
        mv "$HTTPDOCS" "$HTTPDOCS.backup.$(date +%Y%m%d_%H%M%S)" 2>/dev/null || true
        mkdir -p "$HTTPDOCS"
    fi
else
    mkdir -p "$HTTPDOCS"
fi

# Copy all PHP interface files to httpdocs
cp -r "$DEPLOY_DIR/php_interface/"* "$HTTPDOCS/"

# Create marker file to indicate our deployment
touch "$HTTPDOCS/.jury_planner_deployed"

# Set proper permissions for Plesk
echo "🔐 Setting Plesk permissions..."
chown -R jury2025:psacln "$DEPLOY_DIR"
chown -R jury2025:psacln "$HTTPDOCS"
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

echo "✅ Simple Plesk deployment completed successfully!"
echo "🌍 PHP Interface available at: https://jury2025.useless.nl/"
echo "🔍 Test connection at: https://jury2025.useless.nl/test_connection.php"
echo "📊 Dashboard at: https://jury2025.useless.nl/mnc_dashboard.php"
echo "📁 Repository stored at: $DEPLOY_DIR"
EOF

# Make the hook executable
chmod +x "$GIT_DIR/hooks/post-receive"

echo -e "${GREEN}✅ Post-receive hook created${NC}"

echo -e "${BLUE}📋 Simple Plesk Deployment Configuration:${NC}"
cat << 'EOF'

Simple Directory Structure (No Symlinks):
├── /home/httpd/vhosts/jury2025.useless.nl/
│   ├── httpdocs/                    # Direct PHP files (web accessible)
│   │   ├── index.php                # Main entry point
│   │   ├── mnc_dashboard.php        # Dashboard
│   │   ├── test_connection.php      # Database test
│   │   └── config/                  # Configuration files
│   ├── jury_planner/                # Full repository (below web root)
│   │   ├── php_interface/           # Source files
│   │   ├── backend/                 # Python backend (private)
│   │   ├── .env                     # Environment config (private)
│   │   └── documentation/           # Documentation (private)
│   └── git/
│       └── jury2025.git/            # Bare git repository

Deployment Process:
1. Git receives push
2. Repository updated in jury_planner/
3. PHP files copied directly to httpdocs/
4. No symlinks needed!

Web Access:
- https://jury2025.useless.nl/           # Main interface
- https://jury2025.useless.nl/test_connection.php  # Database test

EOF

echo -e "${GREEN}🎉 Simple Plesk setup completed!${NC}"
echo -e "${YELLOW}💡 Next steps:${NC}"
echo -e "   1. Push from your local machine: ./deploy.sh"
echo -e "   2. Test the deployment: https://jury2025.useless.nl/"
echo -e "   3. No symlinks to manage!"
