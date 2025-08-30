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
VENV_DIR="${DEPLOY_DIR}/.venv"

echo -e "${YELLOW}📁 Working directory: ${DEPLOY_DIR}${NC}"

# Step 1: Set proper file permissions
echo -e "${YELLOW}🔐 Step 1: Setting file permissions...${NC}"
find "${DEPLOY_DIR}" -type f -name "*.php" -exec chmod 644 {} \; 2>/dev/null || true
find "${DEPLOY_DIR}" -type f -name "*.sh" -exec chmod 755 {} \; 2>/dev/null || true
find "${DEPLOY_DIR}" -type d -exec chmod 755 {} \; 2>/dev/null || true
echo -e "${GREEN}✅ File permissions updated${NC}"

# Step 2: Check Python availability
echo -e "${YELLOW}🐍 Step 2: Checking Python environment...${NC}"

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

# Step 3: Set up or update Python virtual environment
if [ -n "$PYTHON_CMD" ]; then
    echo -e "${YELLOW}🔧 Step 3: Setting up Python virtual environment...${NC}"
    
    # Create virtual environment if it doesn't exist
    if [ ! -d "$VENV_DIR" ]; then
        echo -e "   Creating virtual environment..."
        $PYTHON_CMD -m venv "$VENV_DIR" 2>/dev/null || {
            echo -e "${YELLOW}   ⚠️  venv module not available, trying alternative...${NC}"
            if command -v virtualenv &> /dev/null; then
                virtualenv -p $PYTHON_CMD "$VENV_DIR"
            else
                echo -e "${RED}   ❌ Virtual environment creation failed${NC}"
                exit 1
            fi
        }
    fi
    
    if [ -f "$VENV_DIR/bin/activate" ]; then
        echo -e "${GREEN}   ✅ Virtual environment ready${NC}"
        
        # Activate and install/update packages
        source "$VENV_DIR/bin/activate"
        
        echo -e "   Upgrading pip..."
        pip install --upgrade pip --quiet 2>/dev/null || echo "   pip upgrade skipped"
        
        echo -e "   Installing/updating required packages..."
        
        # Install from requirements.txt if available
        if [ -f "${DEPLOY_DIR}/planning_engine/requirements.txt" ]; then
            echo -e "   Installing from planning_engine/requirements.txt..."
            pip install -r "${DEPLOY_DIR}/planning_engine/requirements.txt" --quiet 2>/dev/null || {
                echo -e "${YELLOW}   ⚠️  Some packages from requirements.txt failed to install${NC}"
            }
        fi
        
        # Install essential packages for optimization engine
        echo -e "   Installing/updating optimization packages..."
        pip install --quiet numpy mysql-connector-python python-dotenv 2>/dev/null || {
            echo -e "${YELLOW}   ⚠️  Some optimization packages failed to install${NC}"
        }
        
        # Test OR-Tools installation
        echo -e "   Testing OR-Tools import..."
        if python3 -c "from ortools.sat.python import cp_model; print('✅ OR-Tools imported successfully!')" 2>/dev/null; then
            echo -e "${GREEN}   ✅ OR-Tools is working${NC}"
        else
            echo -e "${YELLOW}   ⚠️  OR-Tools import failed, attempting manual installation...${NC}"
            pip install ortools --quiet 2>/dev/null || {
                echo -e "${RED}   ❌ OR-Tools installation failed${NC}"
            }
        fi
        
        deactivate
        echo -e "${GREEN}✅ Python environment updated${NC}"
    else
        echo -e "${RED}❌ Virtual environment creation failed${NC}"
        exit 1
    fi
else
    echo -e "${RED}❌ Python 3 not available${NC}"
    exit 1
fi

# Step 4: Test Python optimization engine
echo -e "${YELLOW}🧪 Step 4: Testing Python optimization engine...${NC}"

if [ -f "$VENV_DIR/bin/python3" ] && [ -f "${DEPLOY_DIR}/planning_engine/pure_autoplanner.py" ]; then
    cd "${DEPLOY_DIR}"
    if source "$VENV_DIR/bin/activate" && python3 -c "
import sys
sys.path.append('planning_engine')
try:
    from ortools.sat.python import cp_model
    print('✅ OR-Tools imported successfully')
    
    import planning_engine.pure_autoplanner
    print('✅ Autoplanner import successful')
except Exception as e:
    print(f'⚠️  Import error: {e}')
    exit(1)
" 2>/dev/null; then
        echo -e "${GREEN}✅ Python optimization engine is working${NC}"
    else
        echo -e "${RED}❌ Python optimization engine failed tests${NC}"
        deactivate 2>/dev/null || true
        exit 1
    fi
    deactivate 2>/dev/null || true
else
    echo -e "${RED}❌ Required files missing${NC}"
    exit 1
fi

# Step 5: Create status file
echo -e "${YELLOW}📄 Step 5: Creating deployment status...${NC}"

# Get package list
VENV_PACKAGES=""
if [ -f "$VENV_DIR/bin/activate" ]; then
    VENV_PACKAGES=$(source "$VENV_DIR/bin/activate" && pip list --format=freeze 2>/dev/null | head -10 | tr '\n' ',' || echo "unknown")
    deactivate 2>/dev/null || true
fi

cat > "${DEPLOY_DIR}/deployment_status.json" << EOF
{
    "deployment_time": "$(date -u +"%Y-%m-%dT%H:%M:%SZ")",
    "deployment_status": "success",
    "python_available": true,
    "python_version": "$PYTHON_VERSION",
    "venv_created": true,
    "ortools_working": true,
    "optimization_engine": true,
    "venv_packages": "${VENV_PACKAGES%,}",
    "post_deploy_script": "executed"
}
EOF

echo -e "${GREEN}✅ Deployment status saved${NC}"

# Final summary
echo ""
echo -e "${BLUE}🎉 Post-Deployment Setup Complete!${NC}"
echo -e "${BLUE}===================================${NC}"
echo -e "${GREEN}✅ All components deployed successfully${NC}"
echo -e "${GREEN}✅ Python optimization engine ready${NC}"
echo -e "${GREEN}✅ OR-Tools installed and working${NC}"
echo ""
echo -e "${GREEN}✨ Ready to plan some water polo matches! 🏊‍♀️🏊‍♂️${NC}"
