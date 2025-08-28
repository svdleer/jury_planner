#!/bin/bash

# Manual Server Setup Script for Plesk Deployment
# Run this script on the production server to fix deployment issues

echo "🔧 Fixing Plesk Deployment Configuration..."

# Variables
GIT_DIR="/home/httpd/vhosts/jury2025.useless.nl/git/jury2025.git"
DEPLOY_DIR="/home/httpd/vhosts/jury2025.useless.nl/httpdocs"
BACKUP_DIR="/home/httpd/vhosts/jury2025.useless.nl/backup-$(date +%Y%m%d-%H%M%S)"

echo "📁 Git Directory: $GIT_DIR"
echo "🌐 Deploy Directory: $DEPLOY_DIR"
echo "💾 Backup Directory: $BACKUP_DIR"

# Create backup
echo "📦 Creating backup of current httpdocs..."
if [ -d "$DEPLOY_DIR" ]; then
    mkdir -p "$BACKUP_DIR"
    cp -r "$DEPLOY_DIR"/* "$BACKUP_DIR/" 2>/dev/null || echo "No files to backup"
    echo "✅ Backup created at $BACKUP_DIR"
fi

# Clear php_interface if it exists
if [ -d "$DEPLOY_DIR/php_interface" ]; then
    echo "🗑️  Removing old php_interface directory..."
    rm -rf "$DEPLOY_DIR/php_interface"
    echo "✅ Old php_interface removed"
fi

# Deploy latest files directly to httpdocs
echo "🚀 Deploying latest files to httpdocs root..."
cd "$GIT_DIR"
git --git-dir="$GIT_DIR" --work-tree="$DEPLOY_DIR" checkout -f main

# Set proper permissions
echo "🔐 Setting proper file permissions..."
find "$DEPLOY_DIR" -type f -name "*.php" -exec chmod 644 {} \;
find "$DEPLOY_DIR" -type f -name "*.sh" -exec chmod 755 {} \;
find "$DEPLOY_DIR" -type d -exec chmod 755 {} \;
chown -R jury2025:psacln "$DEPLOY_DIR"

# Create/update post-receive hook
echo "🔗 Creating proper post-receive hook..."
cat > "$GIT_DIR/hooks/post-receive" << 'EOF'
#!/bin/bash
# Post-receive hook for Plesk deployment
# Deploys files directly to httpdocs root

GIT_DIR="/home/httpd/vhosts/jury2025.useless.nl/git/jury2025.git"
DEPLOY_DIR="/home/httpd/vhosts/jury2025.useless.nl/httpdocs"

echo "Deploying to $DEPLOY_DIR..."
cd "$GIT_DIR"
git --git-dir="$GIT_DIR" --work-tree="$DEPLOY_DIR" checkout -f main

# Set permissions
find "$DEPLOY_DIR" -type f -name "*.php" -exec chmod 644 {} \;
find "$DEPLOY_DIR" -type f -name "*.sh" -exec chmod 755 {} \;
find "$DEPLOY_DIR" -type d -exec chmod 755 {} \;
chown -R jury2025:psacln "$DEPLOY_DIR"

echo "Deployment complete!"
EOF

# Make post-receive hook executable
chmod +x "$GIT_DIR/hooks/post-receive"

echo "✅ Post-receive hook configured"

# Test deployment
echo "🧪 Testing deployment..."
if [ -f "$DEPLOY_DIR/index.php" ]; then
    echo "✅ index.php found in httpdocs root"
else
    echo "❌ index.php not found in httpdocs root"
fi

if [ -f "$DEPLOY_DIR/test_deployment.php" ]; then
    echo "✅ test_deployment.php found in httpdocs root"
else
    echo "❌ test_deployment.php not found in httpdocs root"
fi

if [ -f "$DEPLOY_DIR/setup_python_venv.sh" ]; then
    echo "✅ setup_python_venv.sh found in httpdocs root"
else
    echo "❌ setup_python_venv.sh not found in httpdocs root"
fi

echo ""
echo "🎉 Server setup complete!"
echo "📝 Next steps:"
echo "   1. Test deployment: https://jury2025.useless.nl/test_deployment.php"
echo "   2. Run Python setup: https://jury2025.useless.nl/setup_python_manual.php"
echo "   3. Check file structure: https://jury2025.useless.nl/debug_file_structure.php"
