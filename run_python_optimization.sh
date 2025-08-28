#!/bin/bash

# Python Optimization Wrapper Script
# This script activates the virtual environment and runs Python optimization

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
VENV_DIR="$SCRIPT_DIR/venv"

# Check if virtual environment exists
if [ ! -d "$VENV_DIR" ]; then
    echo "Error: Virtual environment not found at $VENV_DIR"
    echo "Please run setup_python_venv.sh first"
    exit 1
fi

# Activate virtual environment
source "$VENV_DIR/bin/activate"

if [ $? -ne 0 ]; then
    echo "Error: Failed to activate virtual environment"
    exit 1
fi

# Run Python with all passed arguments
python3 "$@"
PYTHON_EXIT_CODE=$?

# Deactivate virtual environment
deactivate

# Return the Python script's exit code
exit $PYTHON_EXIT_CODE
