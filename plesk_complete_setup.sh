#!/bin/bash

# Comprehensive Plesk Deployment and Setup Script
# Water Polo Jury Planner with Python Optimization Engine
# Run this script on your Plesk server to fix deployment and set up Python

set -e  # Exit on any error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
NC='\033[0m' # No Color

echo -e "${BLUE}🏊 Water Polo Jury Planner - Plesk Setup${NC}"
echo -e "${BLUE}=========================================${NC}"

# Configuration
DOMAIN="jury2025.useless.nl"
USER="jury2025"
GIT_DIR="/home/httpd/vhosts/${DOMAIN}/git/jury2025.git"
DEPLOY_DIR="/home/httpd/vhosts/${DOMAIN}/httpdocs"
BACKUP_DIR="/home/httpd/vhosts/${DOMAIN}/backup-$(date +%Y%m%d-%H%M%S)"
VENV_DIR="${DEPLOY_DIR}/venv"

echo -e "${PURPLE}📋 Configuration:${NC}"
echo -e "   🌐 Domain: ${DOMAIN}"
echo -e "   👤 User: ${USER}"
echo -e "   📁 Git Directory: ${GIT_DIR}"
echo -e "   🚀 Deploy Directory: ${DEPLOY_DIR}"
echo -e "   💾 Backup Directory: ${BACKUP_DIR}"
echo -e "   🐍 Virtual Environment: ${VENV_DIR}"
echo ""

# Step 1: Create backup
echo -e "${YELLOW}📦 Step 1: Creating backup...${NC}"
if [ -d "$DEPLOY_DIR" ]; then
    mkdir -p "$BACKUP_DIR"
    cp -r "$DEPLOY_DIR"/* "$BACKUP_DIR/" 2>/dev/null || echo "No files to backup"
    echo -e "${GREEN}✅ Backup created at $BACKUP_DIR${NC}"
else
    echo -e "${YELLOW}⚠️  Deploy directory doesn't exist, will be created${NC}"
fi

# Step 2: Clear old deployment
echo -e "${YELLOW}🗑️  Step 2: Cleaning old deployment...${NC}"
if [ -d "$DEPLOY_DIR/php_interface" ]; then
    echo -e "   Removing old php_interface directory..."
    rm -rf "$DEPLOY_DIR/php_interface"
    echo -e "${GREEN}   ✅ Old php_interface removed${NC}"
fi

# Remove any old Python virtual environment
if [ -d "$VENV_DIR" ]; then
    echo -e "   Removing old virtual environment..."
    rm -rf "$VENV_DIR"
    echo -e "${GREEN}   ✅ Old virtual environment removed${NC}"
fi

# Step 3: Deploy latest files
echo -e "${YELLOW}🚀 Step 3: Deploying latest files...${NC}"
if [ ! -d "$GIT_DIR" ]; then
    echo -e "${RED}❌ Git directory not found: $GIT_DIR${NC}"
    exit 1
fi

mkdir -p "$DEPLOY_DIR"
cd "$GIT_DIR"
git --git-dir="$GIT_DIR" --work-tree="$DEPLOY_DIR" checkout -f main
echo -e "${GREEN}✅ Files deployed to httpdocs root${NC}"

# Step 4: Set up proper git post-receive hook
echo -e "${YELLOW}🔗 Step 4: Setting up git post-receive hook...${NC}"
cat > "$GIT_DIR/hooks/post-receive" << 'EOF'
#!/bin/bash
# Post-receive hook for Plesk deployment
# Deploys files directly to httpdocs root and sets up Python environment

DOMAIN="jury2025.useless.nl"
USER="jury2025"
GIT_DIR="/home/httpd/vhosts/${DOMAIN}/git/jury2025.git"
DEPLOY_DIR="/home/httpd/vhosts/${DOMAIN}/httpdocs"

echo "🚀 Deploying to $DEPLOY_DIR..."
cd "$GIT_DIR"
git --git-dir="$GIT_DIR" --work-tree="$DEPLOY_DIR" checkout -f main

# Set permissions
echo "🔐 Setting permissions..."
find "$DEPLOY_DIR" -type f -name "*.php" -exec chmod 644 {} \;
find "$DEPLOY_DIR" -type f -name "*.sh" -exec chmod 755 {} \;
find "$DEPLOY_DIR" -type d -exec chmod 755 {} \;
chown -R $USER:psacln "$DEPLOY_DIR"

echo "✅ Deployment complete!"
EOF

chmod +x "$GIT_DIR/hooks/post-receive"
echo -e "${GREEN}✅ Post-receive hook configured${NC}"

# Step 5: Set file permissions
echo -e "${YELLOW}🔐 Step 5: Setting file permissions...${NC}"
find "$DEPLOY_DIR" -type f -name "*.php" -exec chmod 644 {} \;
find "$DEPLOY_DIR" -type f -name "*.sh" -exec chmod 755 {} \;
find "$DEPLOY_DIR" -type d -exec chmod 755 {} \;
chown -R $USER:psacln "$DEPLOY_DIR"
echo -e "${GREEN}✅ File permissions set${NC}"

# Step 6: Check Python availability
echo -e "${YELLOW}🐍 Step 6: Checking Python environment...${NC}"
cd "$DEPLOY_DIR"

# Check if Python 3 is available
if command -v python3 &> /dev/null; then
    PYTHON_VERSION=$(python3 --version)
    echo -e "${GREEN}✅ Python found: $PYTHON_VERSION${NC}"
    PYTHON_CMD="python3"
elif command -v python &> /dev/null; then
    PYTHON_VERSION=$(python --version)
    if [[ $PYTHON_VERSION == *"Python 3"* ]]; then
        echo -e "${GREEN}✅ Python found: $PYTHON_VERSION${NC}"
        PYTHON_CMD="python"
    else
        echo -e "${RED}❌ Python 3 required, found: $PYTHON_VERSION${NC}"
        PYTHON_CMD=""
    fi
else
    echo -e "${RED}❌ Python not found${NC}"
    PYTHON_CMD=""
fi

# Step 7: Set up Python virtual environment
if [ -n "$PYTHON_CMD" ]; then
    echo -e "${YELLOW}🔧 Step 7: Setting up Python virtual environment...${NC}"
    
    # Create virtual environment
    echo -e "   Creating virtual environment..."
    $PYTHON_CMD -m venv "$VENV_DIR"
    
    if [ -f "$VENV_DIR/bin/activate" ]; then
        echo -e "${GREEN}   ✅ Virtual environment created${NC}"
        
        # Activate and install requirements
        source "$VENV_DIR/bin/activate"
        
        echo -e "   Installing Python packages..."
        if [ -f "$DEPLOY_DIR/requirements.txt" ]; then
            pip install --upgrade pip
            pip install -r "$DEPLOY_DIR/requirements.txt"
            echo -e "${GREEN}   ✅ Python packages installed${NC}"
        else
            # Install essential packages
            pip install --upgrade pip
            pip install numpy scipy mysql-connector-python python-dotenv
            echo -e "${GREEN}   ✅ Essential Python packages installed${NC}"
        fi
        
        deactivate
    else
        echo -e "${RED}   ❌ Failed to create virtual environment${NC}"
    fi
else
    echo -e "${YELLOW}⚠️  Step 7: Skipping Python setup (Python 3 not available)${NC}"
fi

# Step 8: Test deployment
echo -e "${YELLOW}🧪 Step 8: Testing deployment...${NC}"

# Check key files
FILES_TO_CHECK=(
    "index.php"
    "test_deployment.php" 
    "debug_file_structure.php"
    "setup_python_manual.php"
    "optimization_interface.php"
    "setup_python_venv.sh"
    "run_python_optimization.sh"
)

ALL_GOOD=true
for file in "${FILES_TO_CHECK[@]}"; do
    if [ -f "$DEPLOY_DIR/$file" ]; then
        echo -e "${GREEN}   ✅ $file${NC}"
    else
        echo -e "${RED}   ❌ $file${NC}"
        ALL_GOOD=false
    fi
done

# Check planning engine
if [ -d "$DEPLOY_DIR/planning_engine" ]; then
    echo -e "${GREEN}   ✅ planning_engine directory${NC}"
    if [ -f "$DEPLOY_DIR/planning_engine/enhanced_optimizer.py" ]; then
        echo -e "${GREEN}   ✅ enhanced_optimizer.py${NC}"
    else
        echo -e "${RED}   ❌ enhanced_optimizer.py${NC}"
        ALL_GOOD=false
    fi
else
    echo -e "${RED}   ❌ planning_engine directory${NC}"
    ALL_GOOD=false
fi

# Check virtual environment
if [ -d "$VENV_DIR" ] && [ -f "$VENV_DIR/bin/python3" ]; then
    echo -e "${GREEN}   ✅ Python virtual environment${NC}"
else
    echo -e "${YELLOW}   ⚠️  Python virtual environment (may need manual setup)${NC}"
fi

# Step 9: Final summary
echo ""
echo -e "${PURPLE}🎉 Plesk Setup Complete!${NC}"
echo -e "${PURPLE}========================${NC}"

if [ "$ALL_GOOD" = true ]; then
    echo -e "${GREEN}✅ All core files deployed successfully${NC}"
else
    echo -e "${YELLOW}⚠️  Some files missing - check git repository${NC}"
fi

echo ""
echo -e "${BLUE}📋 Next Steps:${NC}"
echo -e "   1. 🌐 Test deployment: https://${DOMAIN}/test_deployment.php"
echo -e "   2. 🔍 Check file structure: https://${DOMAIN}/debug_file_structure.php"
echo -e "   3. 🐍 Set up Python (if needed): https://${DOMAIN}/setup_python_manual.php"
echo -e "   4. 🏊 Access main application: https://${DOMAIN}/"
echo -e "   5. 🗄️  Test database: https://${DOMAIN}/test_connection.php"

echo ""
echo -e "${BLUE}🔧 Manual Python Setup (if automatic setup failed):${NC}"
echo -e "   cd $DEPLOY_DIR"
echo -e "   ./setup_python_venv.sh"

echo ""
echo -e "${BLUE}🗂️  Backup Location:${NC}"
echo -e "   $BACKUP_DIR"

echo ""
echo -e "${GREEN}🎊 Setup completed successfully!${NC}"
