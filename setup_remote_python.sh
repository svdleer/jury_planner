#!/bin/bash

# Remote Python Virtual Environment Setup Script
# This script creates and configures the Python virtual environment on the production server

echo "🚀 Setting up Python Virtual Environment on Production Server..."
echo "========================================"

# Configuration
REMOTE_SERVER="jury2025.useless.nl"
REMOTE_PATH="/home/httpd/vhosts/jury2025.useless.nl/httpdocs"
REMOTE_USER="silvester"

echo "📡 Connecting to: $REMOTE_USER@$REMOTE_SERVER"
echo "📁 Remote path: $REMOTE_PATH"

# Create the setup command
SETUP_COMMAND="cd $REMOTE_PATH && chmod +x setup_python_venv.sh && ./setup_python_venv.sh"

echo ""
echo "🔧 Executing Python virtual environment setup on remote server..."
echo "Command: $SETUP_COMMAND"
echo ""

# Execute setup on remote server
ssh "$REMOTE_USER@$REMOTE_SERVER" "$SETUP_COMMAND"

if [ $? -eq 0 ]; then
    echo ""
    echo "✅ Python virtual environment setup completed successfully!"
    echo ""
    echo "🧪 Testing Python optimization availability..."
    
    # Test the Python optimization
    TEST_COMMAND="cd $REMOTE_PATH && php test_python_status.php"
    echo "Running: $TEST_COMMAND"
    
    ssh "$REMOTE_USER@$REMOTE_SERVER" "$TEST_COMMAND"
    
    echo ""
    echo "🎉 Setup complete! You can now test the Python optimization engine."
else
    echo ""
    echo "❌ Setup failed. Please check the error messages above."
    exit 1
fi
