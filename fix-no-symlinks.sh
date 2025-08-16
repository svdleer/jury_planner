#!/bin/bash

# Fix Current Deployment - Remove Symlinks, Use Direct Files
# Run this on your Plesk server to simplify the deployment

set -e

# Colors
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

echo -e "${BLUE}🔧 Fixing Deployment - Removing Symlinks${NC}"

# Define paths
DOMAIN_ROOT="/home/httpd/vhosts/jury2025.useless.nl"
HTTPDOCS="$DOMAIN_ROOT/httpdocs"
DEPLOY_DIR="$DOMAIN_ROOT/jury_planner"

echo -e "${YELLOW}📍 Current setup check...${NC}"
if [ -L "$HTTPDOCS" ]; then
    echo -e "${YELLOW}🔗 httpdocs is currently a symlink to: $(readlink $HTTPDOCS)${NC}"
    echo -e "${YELLOW}🗑️  Removing symlink...${NC}"
    rm -f "$HTTPDOCS"
elif [ -d "$HTTPDOCS" ]; then
    echo -e "${GREEN}📁 httpdocs is already a directory${NC}"
else
    echo -e "${RED}❓ httpdocs does not exist${NC}"
fi

# Create httpdocs directory if it doesn't exist
if [ ! -d "$HTTPDOCS" ]; then
    echo -e "${GREEN}📁 Creating httpdocs directory...${NC}"
    mkdir -p "$HTTPDOCS"
fi

# Copy PHP files directly from repository
if [ -d "$DEPLOY_DIR/php_interface" ]; then
    echo -e "${GREEN}📋 Copying PHP files directly to httpdocs...${NC}"
    cp -r "$DEPLOY_DIR/php_interface/"* "$HTTPDOCS/"
    
    # Create marker file
    touch "$HTTPDOCS/.jury_planner_deployed"
    
    echo -e "${GREEN}✅ PHP files copied successfully${NC}"
else
    echo -e "${RED}❌ Source directory $DEPLOY_DIR/php_interface not found${NC}"
    exit 1
fi

# Set permissions
echo -e "${GREEN}🔐 Setting permissions...${NC}"
chown -R jury2025:psacln "$HTTPDOCS"
chmod -R 755 "$HTTPDOCS"

# Update the git hook to use direct copying instead of symlinks
echo -e "${GREEN}🔧 Updating git hook...${NC}"
cat > "$DEPLOY_DIR/../git/jury2025.git/hooks/post-receive" << 'EOF'
#!/bin/bash

# Post-receive hook for simple Plesk deployment (no symlinks)
DOMAIN_ROOT="/home/httpd/vhosts/jury2025.useless.nl"
HTTPDOCS="$DOMAIN_ROOT/httpdocs"
DEPLOY_DIR="$DOMAIN_ROOT/jury_planner"
GIT_DIR="$DOMAIN_ROOT/git/jury2025.git"

echo "🚀 Starting simple deployment (no symlinks)..."

# Update repository
cd "$DEPLOY_DIR"
git fetch origin
git reset --hard origin/main

# Copy PHP files directly to httpdocs
echo "📋 Copying PHP files to httpdocs..."
cp -r "$DEPLOY_DIR/php_interface/"* "$HTTPDOCS/"

# Set permissions
chown -R jury2025:psacln "$HTTPDOCS"
chmod -R 755 "$HTTPDOCS"

echo "✅ Deployment completed - no symlinks used!"
EOF

chmod +x "$DEPLOY_DIR/../git/jury2025.git/hooks/post-receive"

# Verify setup
echo -e "${BLUE}✅ Verification:${NC}"
if [ -d "$HTTPDOCS" ] && [ ! -L "$HTTPDOCS" ]; then
    echo -e "${GREEN}✅ httpdocs is now a regular directory${NC}"
    echo -e "${GREEN}✅ Files exist: $(ls -la $HTTPDOCS | wc -l) items${NC}"
    if [ -f "$HTTPDOCS/index.php" ]; then
        echo -e "${GREEN}✅ index.php found${NC}"
    else
        echo -e "${RED}❌ index.php not found${NC}"
    fi
else
    echo -e "${RED}❌ httpdocs setup failed${NC}"
    exit 1
fi

echo -e "${GREEN}🎉 Symlink removal completed!${NC}"
echo -e "${BLUE}🌍 Your interface should now be available at:${NC}"
echo -e "   • Main: https://jury2025.useless.nl/"
echo -e "   • Dashboard: https://jury2025.useless.nl/mnc_dashboard.php"
echo -e "   • Test DB: https://jury2025.useless.nl/test_connection.php"
echo -e "${GREEN}📁 Structure: httpdocs is now a regular directory with direct files${NC}"
echo -e "${GREEN}🔄 Future deployments will copy files directly (no symlinks)${NC}"
