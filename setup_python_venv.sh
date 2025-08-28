#!/bin/bash

# Python Virtual Environment Setup for Jury Planner
# This script sets up a Python virtual environment with required packages for optimization

echo "🐍 Setting up Python Virtual Environment for Jury Planner..."

# Check if Python3 is available
if ! command -v python3 &> /dev/null; then
    echo "❌ Python3 is not installed. Please install Python3 first."
    exit 1
fi

echo "✓ Python3 found: $(python3 --version)"

# Create virtual environment directory
VENV_DIR="$(pwd)/venv"
echo "📁 Creating virtual environment at: $VENV_DIR"

# Remove existing venv if it exists
if [ -d "$VENV_DIR" ]; then
    echo "🗑️  Removing existing virtual environment..."
    rm -rf "$VENV_DIR"
fi

# Create new virtual environment
python3 -m venv "$VENV_DIR"

if [ $? -ne 0 ]; then
    echo "❌ Failed to create virtual environment"
    exit 1
fi

echo "✓ Virtual environment created successfully"

# Activate virtual environment
source "$VENV_DIR/bin/activate"

if [ $? -ne 0 ]; then
    echo "❌ Failed to activate virtual environment"
    exit 1
fi

echo "✓ Virtual environment activated"

# Upgrade pip
echo "📦 Upgrading pip..."
pip install --upgrade pip

# Install required packages
echo "📦 Installing required packages..."

# First, install numpy to ensure consistent version
echo "📦 Installing numpy first to ensure compatibility..."
pip install numpy==1.24.3

# Then install pandas with compatible version
echo "📦 Installing pandas with numpy compatibility..."
pip install pandas==2.0.3

# Install OR-Tools (which will use the existing numpy)
echo "📦 Installing OR-Tools optimization library..."
pip install ortools==9.7.2996

# Database connectivity
pip install mysql-connector-python>=8.0.0
pip install PyMySQL>=1.0.0

# Additional utilities
pip install python-dateutil>=2.8.0
pip install flask>=2.0.0
pip install sqlalchemy>=1.4.0

# Verify installations
echo "🔍 Verifying installations..."

python3 -c "
import sys
import pkg_resources

required_packages = [
    'ortools',
    'numpy', 
    'pandas',
    'mysql.connector',
    'pymysql',
    'dateutil',
    'flask',
    'sqlalchemy'
]

print('Checking installed packages:')
for package in required_packages:
    try:
        if package == 'mysql.connector':
            import mysql.connector
            print(f'✓ {package}: OK')
        elif package == 'dateutil':
            import dateutil
            print(f'✓ {package}: OK') 
        else:
            __import__(package)
            print(f'✓ {package}: OK')
    except ImportError as e:
        print(f'❌ {package}: MISSING - {e}')
        sys.exit(1)

print('\\n🎉 All packages installed successfully!')
"

if [ $? -ne 0 ]; then
    echo "❌ Package verification failed"
    exit 1
fi

# Create activation script
ACTIVATE_SCRIPT="$(pwd)/activate_venv.sh"
cat > "$ACTIVATE_SCRIPT" << 'EOF'
#!/bin/bash
# Activate Python virtual environment for Jury Planner

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
VENV_DIR="$SCRIPT_DIR/venv"

if [ -d "$VENV_DIR" ]; then
    source "$VENV_DIR/bin/activate"
    echo "✓ Python virtual environment activated"
    echo "Python: $(which python3)"
    echo "Pip: $(which pip)"
else
    echo "❌ Virtual environment not found at: $VENV_DIR"
    echo "Run setup_python_venv.sh first"
    exit 1
fi
EOF

chmod +x "$ACTIVATE_SCRIPT"

# Create requirements.txt for future reference
cat > "$(pwd)/requirements.txt" << 'EOF'
# Python requirements for Jury Planner Optimization Engine
# Fixed versions to ensure compatibility
numpy==1.24.3
pandas==2.0.3
ortools==9.7.2996
mysql-connector-python>=8.0.0
PyMySQL>=1.0.0
python-dateutil>=2.8.0
flask>=2.0.0
sqlalchemy>=1.4.0
EOF

# Test the optimization engine
echo "🧪 Testing optimization engine..."
python3 -c "
import sys
import os
sys.path.append('$(pwd)/planning_engine')

try:
    from ortools.sat.python import cp_model
    print('✓ OR-Tools CP-SAT solver: OK')
    
    from ortools.linear_solver import pywraplp
    print('✓ OR-Tools Linear solver: OK')
    
    print('✓ Optimization engine test passed!')
except Exception as e:
    print(f'❌ Optimization engine test failed: {e}')
    sys.exit(1)
"

if [ $? -ne 0 ]; then
    echo "❌ Optimization engine test failed"
    exit 1
fi

echo ""
echo "🎉 Python Virtual Environment Setup Complete!"
echo "================================================"
echo ""
echo "📁 Virtual environment location: $VENV_DIR"
echo "🔧 Activation script: $ACTIVATE_SCRIPT"
echo "📋 Requirements file: $(pwd)/requirements.txt"
echo ""
echo "💡 To activate the environment manually:"
echo "   source $VENV_DIR/bin/activate"
echo ""
echo "💡 To activate using the script:"
echo "   source $ACTIVATE_SCRIPT"
echo ""
echo "🚀 Next steps:"
echo "1. Upload this project to your remote server"
echo "2. Run this script on the remote server"
echo "3. Enable shell_exec() in PHP configuration"
echo "4. Test the optimization features"
echo ""

# Deactivate virtual environment
deactivate
echo "✓ Virtual environment deactivated"
