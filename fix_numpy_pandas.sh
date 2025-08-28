#!/bin/bash

# Fix NumPy/Pandas Binary Compatibility Issue
# Water Polo Jury Planner - Emergency Package Fix
# Run this script when you get numpy.dtype size errors

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}🔧 NumPy/Pandas Compatibility Fix${NC}"
echo -e "${BLUE}=================================${NC}"

# Get the current directory
CURRENT_DIR=$(pwd)
VENV_DIR="${CURRENT_DIR}/venv"

echo -e "${YELLOW}📁 Working directory: ${CURRENT_DIR}${NC}"

# Check if virtual environment exists
if [ ! -f "$VENV_DIR/bin/activate" ]; then
    echo -e "${RED}❌ Virtual environment not found at: $VENV_DIR${NC}"
    echo -e "${YELLOW}Please run setup_python_venv.sh first${NC}"
    exit 1
fi

echo -e "${YELLOW}🔍 Checking current Python environment...${NC}"
source "$VENV_DIR/bin/activate"

# Show current versions before fix
echo -e "${YELLOW}Current package versions:${NC}"
python3 -c "
try:
    import numpy; print(f'NumPy: {numpy.__version__}')
except: print('NumPy: not installed or broken')
try:
    import pandas; print(f'Pandas: {pandas.__version__}')
except: print('Pandas: not installed or broken')
try:
    import ortools; print(f'OR-Tools: {ortools.__version__}')
except: print('OR-Tools: not installed or broken')
" 2>/dev/null || echo "Some packages are broken"

echo -e "${YELLOW}🗑️ Removing problematic packages...${NC}"

# Force remove all potentially conflicting packages
pip uninstall -y numpy pandas ortools scipy scikit-learn matplotlib seaborn 2>/dev/null || true

echo -e "${YELLOW}🧹 Clearing pip cache...${NC}"
pip cache purge 2>/dev/null || true

echo -e "${YELLOW}⬆️ Upgrading pip...${NC}"
pip install --upgrade pip

echo -e "${YELLOW}📦 Installing packages in correct order...${NC}"

# Install packages in specific order with version constraints
echo -e "   1. Installing NumPy (base package)..."
pip install --no-cache-dir "numpy>=1.21.0,<2.0.0" --force-reinstall

echo -e "   2. Installing SciPy..."
pip install --no-cache-dir "scipy>=1.7.0,<2.0.0" --force-reinstall

echo -e "   3. Installing Pandas..."
pip install --no-cache-dir "pandas>=1.3.0,<2.0.0" --force-reinstall

echo -e "   4. Installing OR-Tools..."
pip install --no-cache-dir ortools --force-reinstall

echo -e "   5. Installing other packages..."
pip install --no-cache-dir mysql-connector-python python-dotenv

echo -e "${YELLOW}✅ Testing fixed installation...${NC}"

# Test the fixed installation
python3 -c "
import sys
print('Python version:', sys.version)
print()

try:
    import numpy as np
    print('✅ NumPy:', np.__version__)
    
    import pandas as pd
    print('✅ Pandas:', pd.__version__)
    
    import scipy
    print('✅ SciPy:', scipy.__version__)
    
    from ortools.sat.python import cp_model
    print('✅ OR-Tools: Import successful')
    
    import mysql.connector
    print('✅ MySQL Connector: Available')
    
    print()
    print('🎉 All packages working correctly!')
    
except Exception as e:
    print(f'❌ Error: {e}')
    sys.exit(1)
"

echo -e "${YELLOW}🧪 Testing optimization engine import...${NC}"

# Test the actual optimization engine
if [ -f "${CURRENT_DIR}/planning_engine/enhanced_optimizer.py" ]; then
    python3 -c "
import sys
sys.path.append('planning_engine')
try:
    import enhanced_optimizer
    print('✅ Optimization engine imports successfully')
except Exception as e:
    print(f'❌ Optimization engine error: {e}')
    sys.exit(1)
"
else
    echo -e "${YELLOW}⚠️  enhanced_optimizer.py not found, skipping engine test${NC}"
fi

deactivate

echo ""
echo -e "${GREEN}🎉 NumPy/Pandas Compatibility Fix Complete!${NC}"
echo -e "${GREEN}==========================================${NC}"
echo -e "${GREEN}✅ All packages reinstalled with compatible versions${NC}"
echo -e "${GREEN}✅ Python optimization engine should now work${NC}"
echo ""
echo -e "${BLUE}🔗 Test the fix:${NC}"
echo -e "   🧪 Run optimization test: https://$(hostname 2>/dev/null || echo 'your-domain.com')/preview_debug.php"
echo -e "   🔍 Check Python status: https://$(hostname 2>/dev/null || echo 'your-domain.com')/test_python_status.php"
echo ""
echo -e "${GREEN}✨ Ready to optimize! 🏊‍♀️🏊‍♂️${NC}"
