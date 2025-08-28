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

# Move Python optimization files from root to httpdocs
echo "🐍 Moving Python optimization files"
if [ -d "$HTTPDOCS/planning_engine" ]; then
    echo "✓ planning_engine already in place"
else
    echo "⚠️ planning_engine directory not found in httpdocs"
fi

# Copy essential Python files if they exist at root level but not in httpdocs
for file in "setup_python_venv.sh" "run_python_optimization.sh" "requirements.txt"; do
    if [ -f "$HTTPDOCS/$file" ]; then
        echo "✓ $file already in place"
    else
        echo "⚠️ $file not found in httpdocs"
    fi
done

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

# Clean up non-web files from httpdocs (but keep Python optimization files)
echo "🧹 Cleaning up non-web files"
rm -rf "$HTTPDOCS/backend" 2>/dev/null || true
rm -rf "$HTTPDOCS/database" 2>/dev/null || true
rm -f "$HTTPDOCS/manage.py" 2>/dev/null || true
rm -f "$HTTPDOCS/app.py" 2>/dev/null || true
rm -f "$HTTPDOCS/analyze_database.py" 2>/dev/null || true
rm -f "$HTTPDOCS"/*.md 2>/dev/null || true
rm -rf "$HTTPDOCS/.github" 2>/dev/null || true

# Keep planning_engine, requirements.txt, and setup scripts for Python optimization
echo "🐍 Setting up Python optimization environment"

# Make Python setup scripts executable
chmod +x "$HTTPDOCS/setup_python_venv.sh" 2>/dev/null || true
chmod +x "$HTTPDOCS/run_python_optimization.sh" 2>/dev/null || true

# Set up virtual environment if not exists
if [ ! -d "$HTTPDOCS/venv" ]; then
    echo "🔧 Creating Python virtual environment..."
    cd "$HTTPDOCS"
    
    # Run the setup script
    if [ -f "setup_python_venv.sh" ]; then
        ./setup_python_venv.sh
        if [ $? -eq 0 ]; then
            echo "✅ Python virtual environment created successfully"
        else
            echo "⚠️  Warning: Failed to create Python virtual environment"
        fi
    else
        echo "⚠️  Warning: setup_python_venv.sh not found"
    fi
else
    echo "✅ Python virtual environment already exists"
fi

echo "✅ Deployment completed!"
echo "🌍 Interface available at: https://jury2025.useless.nl/"
echo "🔍 Test at: https://jury2025.useless.nl/test_connection.php"
echo "📊 Dashboard at: https://jury2025.useless.nl/mnc_dashboard.php"
echo "🐍 Python test at: https://jury2025.useless.nl/test_python_status.php"
echo "⚙️  Constraint editor at: https://jury2025.useless.nl/constraint_editor.php"
