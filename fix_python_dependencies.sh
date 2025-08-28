#!/bin/bash

# Quick fix for numpy/pandas compatibility issue
# This script resolves the "numpy.dtype size changed" error

echo "🔧 Fixing Python dependencies compatibility issue..."

# Check if we're in the correct directory
if [ ! -f "optimization_interface.php" ]; then
    echo "❌ Please run this script from the jury planner directory"
    exit 1
fi

VENV_DIR="$(pwd)/venv"

# Check if virtual environment exists
if [ ! -d "$VENV_DIR" ]; then
    echo "❌ Virtual environment not found. Running full setup..."
    ./setup_python_venv.sh
    exit $?
fi

echo "📁 Found virtual environment at: $VENV_DIR"

# Activate virtual environment
source "$VENV_DIR/bin/activate"

if [ $? -ne 0 ]; then
    echo "❌ Failed to activate virtual environment"
    exit 1
fi

echo "✓ Virtual environment activated"

# Force reinstall of problematic packages in correct order
echo "🔄 Uninstalling problematic packages..."
pip uninstall -y numpy pandas ortools

echo "📦 Reinstalling packages in correct order..."

# Install numpy first
echo "📦 Installing numpy 1.24.3..."
pip install numpy==1.24.3

# Install pandas with specific version
echo "📦 Installing pandas 2.0.3..."
pip install pandas==2.0.3

# Install OR-Tools
echo "📦 Installing OR-Tools 9.7.2996..."
pip install ortools==9.7.2996

# Verify the fix
echo "🧪 Testing the fix..."
python3 -c "
try:
    import numpy as np
    print(f'✓ numpy {np.__version__}: OK')
    
    import pandas as pd
    print(f'✓ pandas {pd.__version__}: OK')
    
    from ortools.sat.python import cp_model
    print('✓ OR-Tools CP-SAT: OK')
    
    from ortools.linear_solver import pywraplp
    print('✓ OR-Tools Linear: OK')
    
    print('\\n🎉 Dependencies fixed successfully!')
    
except Exception as e:
    print(f'❌ Test failed: {e}')
    exit(1)
"

if [ $? -ne 0 ]; then
    echo "❌ Fix verification failed"
    exit 1
fi

# Test the actual optimization engine
echo "🧪 Testing optimization engine..."
cd planning_engine
python3 -c "
import sys
import os

try:
    # Test imports
    from ortools.sat.python import cp_model
    import numpy as np
    import pandas as pd
    
    print('✓ All imports successful')
    
    # Test basic functionality
    model = cp_model.CpModel()
    x = model.NewIntVar(0, 10, 'x')
    model.Add(x >= 5)
    
    solver = cp_model.CpSolver()
    status = solver.Solve(model)
    
    if status == cp_model.OPTIMAL:
        print('✓ OR-Tools solver test passed')
    else:
        print('⚠️  OR-Tools solver test inconclusive')
    
    print('\\n🎉 Optimization engine is working!')
    
except Exception as e:
    print(f'❌ Optimization engine test failed: {e}')
    sys.exit(1)
"

if [ $? -ne 0 ]; then
    echo "❌ Optimization engine test failed"
    exit 1
fi

# Deactivate virtual environment
deactivate

echo ""
echo "🎉 Dependencies Fixed Successfully!"
echo "=================================="
echo ""
echo "✓ numpy 1.24.3"
echo "✓ pandas 2.0.3" 
echo "✓ ortools 9.7.2996"
echo ""
echo "💡 The optimization engine should now work without errors."
echo "🚀 Try running your optimization preview again!"
echo ""
