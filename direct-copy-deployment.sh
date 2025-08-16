#!/bin/bash

# Simple Direct Copy Deployment for Plesk
# Copies PHP files directly to httpdocs root

set -e

# Colors
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo -e "${BLUE}📁 Direct Copy Deployment for Plesk${NC}"

# Define paths
DOMAIN_ROOT="/home/httpd/vhosts/jury2025.useless.nl"
HTTPDOCS="$DOMAIN_ROOT/httpdocs"
DEPLOY_DIR="$DOMAIN_ROOT/jury_planner"
GIT_DIR="$DOMAIN_ROOT/git/jury2025.git"

echo "🚀 Starting direct copy deployment..."

# Update repository
if [ ! -d "$DEPLOY_DIR/.git" ]; then
    echo "📦 Initial clone..."
    git clone "$GIT_DIR" "$DEPLOY_DIR"
else
    echo "🔄 Updating repository..."
    cd "$DEPLOY_DIR"
    git fetch origin
    git reset --hard origin/main
fi

# Remove symlinks if they exist
if [ -L "$HTTPDOCS" ]; then
    echo "🗑️  Removing symlink..."
    rm -f "$HTTPDOCS"
fi

# Create httpdocs directory if it doesn't exist
if [ ! -d "$HTTPDOCS" ]; then
    echo "📁 Creating httpdocs directory..."
    mkdir -p "$HTTPDOCS"
fi

# Copy PHP interface files directly to httpdocs
echo "📋 Copying PHP files to httpdocs root..."
cp -r "$DEPLOY_DIR/php_interface/"* "$HTTPDOCS/"

# Copy environment file to httpdocs (for PHP access)
if [ -f "$DEPLOY_DIR/.env" ]; then
    cp "$DEPLOY_DIR/.env" "$HTTPDOCS/.env"
elif [ -f "$DEPLOY_DIR/.env.example" ]; then
    cp "$DEPLOY_DIR/.env.example" "$HTTPDOCS/.env"
fi

# Set proper permissions
echo "🔐 Setting permissions..."
chown -R jury2025:psacln "$HTTPDOCS"
chmod -R 755 "$HTTPDOCS"
chmod 600 "$HTTPDOCS/.env" 2>/dev/null || true

echo "✅ Direct copy deployment completed!"
echo "🌍 Interface available at: https://jury2025.useless.nl/"
