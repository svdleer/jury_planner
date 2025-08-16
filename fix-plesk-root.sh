#!/bin/bash

# Fix Plesk Root Deployment Script
# Run this on your Plesk server to move the interface to document root

set -e

# Colors
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo -e "${BLUE}🔧 Fixing Plesk Root Deployment...${NC}"

# Define paths
DOMAIN_ROOT="/home/httpd/vhosts/jury2025.useless.nl"
HTTPDOCS="$DOMAIN_ROOT/httpdocs"
DEPLOY_DIR="$DOMAIN_ROOT/jury_planner"

echo -e "${YELLOW}📍 Current setup check...${NC}"
if [ -L "$HTTPDOCS" ]; then
    echo "✅ httpdocs is currently a symlink to: $(readlink $HTTPDOCS)"
elif [ -d "$HTTPDOCS" ]; then
    echo "📁 httpdocs is currently a directory"
else
    echo "❓ httpdocs does not exist"
fi

# Backup existing httpdocs if it's a directory (not our symlink)
if [ -d "$HTTPDOCS" ] && [ ! -L "$HTTPDOCS" ]; then
    echo -e "${YELLOW}📦 Backing up existing httpdocs...${NC}"
    mv "$HTTPDOCS" "$HTTPDOCS.backup.$(date +%Y%m%d_%H%M%S)"
fi

# Remove httpdocs if it exists
if [ -L "$HTTPDOCS" ] || [ -d "$HTTPDOCS" ]; then
    echo -e "${YELLOW}🗑️  Removing current httpdocs...${NC}"
    rm -rf "$HTTPDOCS"
fi

# Create new symlink to php_interface root
echo -e "${GREEN}🔗 Creating root symlink...${NC}"
ln -sf "$DEPLOY_DIR/php_interface" "$HTTPDOCS"

# Set permissions
echo -e "${GREEN}🔐 Setting permissions...${NC}"
chown -R jury2025:psacln "$DEPLOY_DIR"
chmod -R 755 "$DEPLOY_DIR"
chmod -R 755 "$HTTPDOCS"

# Verify setup
echo -e "${BLUE}✅ Verification:${NC}"
if [ -L "$HTTPDOCS" ]; then
    echo "✅ httpdocs is now a symlink to: $(readlink $HTTPDOCS)"
    echo "✅ Target exists: $([ -d "$(readlink $HTTPDOCS)" ] && echo "Yes" || echo "No")"
else
    echo "❌ httpdocs symlink creation failed"
    exit 1
fi

echo -e "${GREEN}🎉 Root deployment fix completed!${NC}"
echo -e "${BLUE}🌍 Your interface should now be available at:${NC}"
echo -e "   • Main: https://jury2025.useless.nl/"
echo -e "   • Dashboard: https://jury2025.useless.nl/mnc_dashboard.php"
echo -e "   • Test DB: https://jury2025.useless.nl/test_connection.php"
