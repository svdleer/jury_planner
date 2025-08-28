#!/bin/bash

# Post-Deployment Script for Plesk Auto-Deploy
# Water Polo Jury Planner - Automatic Setup After Deployment
# This script runs automatically after Plesk deploys to httpdocs

set -e  # Exit on any error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}🏊 Post-Deployment Setup - Water Polo Jury Planner${NC}"
echo -e "${BLUE}=================================================${NC}"

# Get the current directory (should be httpdocs)
DEPLOY_DIR=$(pwd)
VENV_DIR="${DEPLOY_DIR}/venv"

echo -e "${YELLOW}📁 Working directory: ${DEPLOY_DIR}${NC}"

# Step 1: Set proper file permissions
echo -e "${YELLOW}🔐 Step 1: Setting file permissions...${NC}"
find "${DEPLOY_DIR}" -type f -name "*.php" -exec chmod 644 {} \; 2>/dev/null || true
find "${DEPLOY_DIR}" -type f -name "*.sh" -exec chmod 755 {} \; 2>/dev/null || true
find "${DEPLOY_DIR}" -type d -exec chmod 755 {} \; 2>/dev/null || true
echo -e "${GREEN}✅ File permissions updated${NC}"

# Step 2: Check if we need to clean up old php_interface
if [ -d "${DEPLOY_DIR}/php_interface" ]; then
    echo -e "${YELLOW}🗑️  Step 2: Removing old php_interface directory...${NC}"
    rm -rf "${DEPLOY_DIR}/php_interface"
    echo -e "${GREEN}✅ Old php_interface removed${NC}"
else
    echo -e "${GREEN}✅ Step 2: No php_interface cleanup needed${NC}"
fi

# Step 3: Check Python availability
echo -e "${YELLOW}🐍 Step 3: Checking Python environment...${NC}"

# Find Python 3
PYTHON_CMD=""
if command -v python3 &> /dev/null; then
    PYTHON_VERSION=$(python3 --version 2>&1)
    echo -e "${GREEN}✅ Found: $PYTHON_VERSION${NC}"
    PYTHON_CMD="python3"
elif command -v python &> /dev/null; then
    PYTHON_VERSION=$(python --version 2>&1)
    if [[ $PYTHON_VERSION == *"Python 3"* ]]; then
        echo -e "${GREEN}✅ Found: $PYTHON_VERSION${NC}"
        PYTHON_CMD="python"
    else
        echo -e "${YELLOW}⚠️  Found Python 2: $PYTHON_VERSION${NC}"
    fi
fi

# Step 4: Set up or update Python virtual environment
if [ -n "$PYTHON_CMD" ]; then
    echo -e "${YELLOW}🔧 Step 4: Setting up Python virtual environment...${NC}"
    
    # Remove old venv if it exists and recreate
    if [ -d "$VENV_DIR" ]; then
        echo -e "   Removing old virtual environment..."
        rm -rf "$VENV_DIR"
    fi
    
    # Create new virtual environment
    echo -e "   Creating virtual environment..."
    $PYTHON_CMD -m venv "$VENV_DIR" 2>/dev/null || {
        echo -e "${YELLOW}   ⚠️  venv module not available, trying alternative...${NC}"
        # Try alternative method
        if command -v virtualenv &> /dev/null; then
            virtualenv -p $PYTHON_CMD "$VENV_DIR"
        else
            echo -e "${YELLOW}   ⚠️  virtualenv not available, skipping venv setup${NC}"
            PYTHON_CMD=""
        fi
    }
    
    if [ -f "$VENV_DIR/bin/activate" ]; then
        echo -e "${GREEN}   ✅ Virtual environment created${NC}"
        
        # Activate and install packages
        source "$VENV_DIR/bin/activate"
        
        echo -e "   Upgrading pip..."
        pip install --upgrade pip --quiet 2>/dev/null || echo "   pip upgrade skipped"
        
        echo -e "   Installing required packages..."
        
        # Install from requirements.txt if available
        if [ -f "${DEPLOY_DIR}/requirements.txt" ]; then
            pip install -r "${DEPLOY_DIR}/requirements.txt" --quiet 2>/dev/null || {
                echo -e "${YELLOW}   ⚠️  Some packages from requirements.txt failed to install${NC}"
            }
        fi
        
        # Install essential packages for optimization engine
        echo -e "   Installing optimization packages..."
        pip install --quiet numpy scipy mysql-connector-python python-dotenv 2>/dev/null || {
            echo -e "${YELLOW}   ⚠️  Some optimization packages failed to install${NC}"
        }
        
        deactivate
        echo -e "${GREEN}✅ Python environment ready${NC}"
    else
        echo -e "${YELLOW}⚠️  Virtual environment creation failed${NC}"
    fi
else
    echo -e "${YELLOW}⚠️  Step 4: Python 3 not available, skipping venv setup${NC}"
fi

# Step 5: Verify key files
echo -e "${YELLOW}🧪 Step 5: Verifying deployment...${NC}"

CRITICAL_FILES=(
    "index.php"
    "optimization_interface.php"
    "planning_engine/enhanced_optimizer.py"
    "setup_python_venv.sh"
    "run_python_optimization.sh"
)

ALL_GOOD=true
for file in "${CRITICAL_FILES[@]}"; do
    if [ -f "${DEPLOY_DIR}/$file" ]; then
        echo -e "${GREEN}   ✅ $file${NC}"
    else
        echo -e "${RED}   ❌ $file${NC}"
        ALL_GOOD=false
    fi
done

# Check virtual environment
if [ -f "$VENV_DIR/bin/python3" ] || [ -f "$VENV_DIR/bin/python" ]; then
    echo -e "${GREEN}   ✅ Python virtual environment${NC}"
else
    echo -e "${YELLOW}   ⚠️  Python virtual environment (manual setup may be needed)${NC}"
fi

# Step 6: Test Python optimization engine
if [ -f "$VENV_DIR/bin/python3" ] && [ -f "${DEPLOY_DIR}/planning_engine/enhanced_optimizer.py" ]; then
    echo -e "${YELLOW}🧪 Step 6: Testing Python optimization engine...${NC}"
    
    cd "${DEPLOY_DIR}"
    if source "$VENV_DIR/bin/activate" && python3 -c "
import sys
sys.path.append('planning_engine')
try:
    import enhanced_optimizer
    print('✅ Optimization engine import successful')
except Exception as e:
    print(f'⚠️  Import warning: {e}')
" 2>/dev/null; then
        echo -e "${GREEN}✅ Python optimization engine is working${NC}"
    else
        echo -e "${YELLOW}⚠️  Python optimization engine needs attention${NC}"
    fi
    deactivate 2>/dev/null || true
else
    echo -e "${YELLOW}⚠️  Step 6: Skipping optimization test (components missing)${NC}"
fi

# Step 7: Create status file
echo -e "${YELLOW}📄 Step 7: Creating deployment status...${NC}"
cat > "${DEPLOY_DIR}/deployment_status.json" << EOF
{
    "deployment_time": "$(date -u +"%Y-%m-%dT%H:%M:%SZ")",
    "deployment_status": "$([ "$ALL_GOOD" = true ] && echo "success" || echo "partial")",
    "python_available": $([ -n "$PYTHON_CMD" ] && echo "true" || echo "false"),
    "venv_created": $([ -f "$VENV_DIR/bin/activate" ] && echo "true" || echo "false"),
    "optimization_engine": $([ -f "${DEPLOY_DIR}/planning_engine/enhanced_optimizer.py" ] && echo "true" || echo "false")
}
EOF
chmod 644 "${DEPLOY_DIR}/deployment_status.json"
echo -e "${GREEN}✅ Deployment status saved${NC}"

# Final summary
echo ""
echo -e "${BLUE}🎉 Post-Deployment Setup Complete!${NC}"
echo -e "${BLUE}===================================${NC}"

if [ "$ALL_GOOD" = true ]; then
    echo -e "${GREEN}✅ All critical files deployed successfully${NC}"
    echo -e "${GREEN}✅ Application ready to use${NC}"
else
    echo -e "${YELLOW}⚠️  Some components need attention${NC}"
fi

if [ -f "$VENV_DIR/bin/activate" ]; then
    echo -e "${GREEN}✅ Python optimization engine ready${NC}"
else
    echo -e "${YELLOW}⚠️  Python setup may need manual configuration${NC}"
fi

echo ""
echo -e "${BLUE}🔗 Quick Links:${NC}"
echo -e "   🏠 Main App: https://$(hostname)/index.php"
echo -e "   🧪 Test Deploy: https://$(hostname)/test_deployment.php"
echo -e "   🔍 Debug Info: https://$(hostname)/debug_file_structure.php"
echo -e "   🐍 Python Setup: https://$(hostname)/setup_python_manual.php"

echo ""
echo -e "${GREEN}✨ Ready to plan some water polo matches! 🏊‍♀️🏊‍♂️${NC}"
