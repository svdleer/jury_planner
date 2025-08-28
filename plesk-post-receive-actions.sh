#!/bin/bash

# Plesk Post-Receive Actions for Python Virtual Environment Setup
# Add these commands to your Plesk Git deployment actions

echo "🐍 Setting up Python optimization environment..."

# Make Python setup scripts executable
chmod +x setup_python_venv.sh 2>/dev/null || true
chmod +x run_python_optimization.sh 2>/dev/null || true

# Set up virtual environment if not exists
if [ ! -d "venv" ]; then
    echo "🔧 Creating Python virtual environment..."
    
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

echo "🐍 Python optimization setup complete!"
