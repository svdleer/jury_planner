#!/bin/bash

# Post-receive hook for Plesk deployment
# This script deploys PHP files from php_interface/ to httpdocs root

DOMAIN_ROOT="/home/httpd/vhosts/jury2025.useless.nl"
HTTPDOCS="$DOMAIN_ROOT/httpdocs"
GIT_DIR="$DOMAIN_ROOT/git/jury2025.git"

echo "🚀 Starting deployment to httpdocs..."

# Remove old httpdocs completely
if [ -d "$HTTPDOCS" ] || [ -L "$HTTPDOCS" ]; then
    echo "🗑️  Removing old httpdocs"
    rm -rf "$HTTPDOCS"
fi

# Create fresh httpdocs directory
echo "📁 Creating fresh httpdocs"
mkdir -p "$HTTPDOCS"

# Clone repository directly into httpdocs
echo "📦 Deploying files to httpdocs"
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

echo "✅ Deployment completed!"
echo "🌍 Interface available at: https://jury2025.useless.nl/"
echo "🔍 Test at: https://jury2025.useless.nl/test_connection.php"
echo "📊 Dashboard at: https://jury2025.useless.nl/mnc_dashboard.php"
