#!/bin/bash
# Setup script for Python dependencies on production server

echo "🐍 Setting up Python environment for Jury Planner..."

# Check if virtual environment exists
if [ ! -d ".venv" ]; then
    echo "Creating virtual environment..."
    python3 -m venv .venv
fi

# Activate virtual environment
echo "Activating virtual environment..."
source .venv/bin/activate

# Upgrade pip
echo "Upgrading pip..."
pip install --upgrade pip

# Install requirements
echo "Installing Python dependencies..."
pip install -r planning_engine/requirements.txt

echo "✅ Python environment setup complete!"
echo "📦 Installed packages:"
pip list | grep -E "(ortools|numpy)"

# Test import
echo "🧪 Testing OR-Tools import..."
python3 -c "from ortools.sat.python import cp_model; print('✅ OR-Tools imported successfully!')" || echo "❌ OR-Tools import failed"
