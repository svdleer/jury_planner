#!/bin/bash

# Python Optimization Wrapper Script
# This script activates the virtual environment if available, otherwise uses system Python

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
VENV_DIR="$SCRIPT_DIR/venv"

# Check if virtual environment exists
if [ -d "$VENV_DIR" ] && [ -f "$VENV_DIR/bin/activate" ]; then
    # Use virtual environment
    source "$VENV_DIR/bin/activate"
    
    if [ $? -eq 0 ]; then
        echo "Using virtual environment: $VENV_DIR"
    else
        echo "Warning: Failed to activate virtual environment, falling back to system Python"
    fi
else
    echo "Virtual environment not found, using system Python"
    # Check if required packages are available in system Python
    python3 -c "import ortools" 2>/dev/null
    if [ $? -ne 0 ]; then
        echo "Warning: OR-Tools not found in system Python. Consider setting up virtual environment."
    fi
fi

# Run Python with all passed arguments
python3 "$@"
PYTHON_EXIT_CODE=$?

# Deactivate virtual environment if it was activated
if [ -d "$VENV_DIR" ] && [ -f "$VENV_DIR/bin/activate" ]; then
    deactivate 2>/dev/null || true
fi

# Return the Python script's exit code
exit $PYTHON_EXIT_CODE
