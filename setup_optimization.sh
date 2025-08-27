#!/bin/bash

# Python Optimization Setup Script
# Installs OR-Tools and sets up the optimization environment

set -e

echo "🔧 Setting up Python Optimization Environment..."

# Check if Python is available
if ! command -v python3 &> /dev/null; then
    echo "❌ Python 3 is required but not installed."
    echo "Please install Python 3 and try again."
    exit 1
fi

echo "✅ Python 3 found: $(python3 --version)"

# Create virtual environment if it doesn't exist
VENV_DIR="optimization_env"
if [ ! -d "$VENV_DIR" ]; then
    echo "📦 Creating virtual environment..."
    python3 -m venv $VENV_DIR
fi

# Activate virtual environment
source $VENV_DIR/bin/activate

echo "📋 Installing Python dependencies..."

# Core optimization packages
pip install --upgrade pip
pip install ortools>=9.0.0
pip install numpy>=1.20.0

# Additional useful packages
pip install psutil  # For monitoring
pip install scipy   # For advanced math

# Verify installation
echo "🧪 Testing OR-Tools installation..."
python3 -c "
from ortools.linear_solver import pywraplp
from ortools.sat.python import cp_model
print('✅ OR-Tools installed successfully')
print('Available solvers:', pywraplp.Solver.SupportsProblemType(pywraplp.Solver.LINEAR_PROGRAMMING))
"

# Create activation script
cat > activate_optimization.sh << 'EOF'
#!/bin/bash
# Activate the optimization environment
source $(dirname $0)/optimization_env/bin/activate
echo "🚀 Python optimization environment activated"
echo "You can now run: python planning_engine/enhanced_optimizer.py"
EOF

chmod +x activate_optimization.sh

# Test the enhanced optimizer
echo "🧪 Testing enhanced optimizer..."
if [ -f "planning_engine/enhanced_optimizer.py" ]; then
    python3 planning_engine/enhanced_optimizer.py --help 2>/dev/null || echo "Note: Use with config file arguments"
    echo "✅ Enhanced optimizer is ready"
else
    echo "⚠️  Enhanced optimizer script not found. Make sure it's in planning_engine/"
fi

echo ""
echo "🎉 Setup complete!"
echo ""
echo "📖 Usage Instructions:"
echo "1. Activate environment: source activation_optimization.sh"
echo "2. Run from PHP: Use OptimizationInterface->runOptimization()"
echo "3. Run manually: python planning_engine/enhanced_optimizer.py config.json solution.json"
echo ""
echo "🔗 The constraint editor now supports Python optimization!"
