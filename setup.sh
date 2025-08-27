#!/bin/bash

# Waterpolo Jury Planner Setup Script

set -e

echo "🏊‍♂️ Waterpolo Jury Planner Setup"
echo "=================================="

# Check if Python 3 is installed
if ! command -v python3 &> /dev/null; then
    echo "❌ Python 3 is required but not installed."
    exit 1
fi

echo "✅ Python 3 found: $(python3 --version)"

# Check if pip is installed
if ! command -v pip3 &> /dev/null; then
    echo "❌ pip3 is required but not installed."
    exit 1
fi

echo "✅ pip3 found"

# Create virtual environment if it doesn't exist
if [ ! -d "venv" ]; then
    echo "🔧 Creating virtual environment..."
    python3 -m venv venv
fi

# Activate virtual environment
echo "🔧 Activating virtual environment..."
source venv/bin/activate

# Upgrade pip
echo "🔧 Upgrading pip..."
pip install --upgrade pip

# Install requirements
echo "📦 Installing Python dependencies..."
pip install -r requirements.txt

# Create .env file if it doesn't exist
if [ ! -f ".env" ]; then
    echo "🔧 Creating .env file..."
    cp .env.example .env
    echo "⚠️  Please edit .env file with your database credentials"
fi

# Check if MySQL is available
if command -v mysql &> /dev/null; then
    echo "✅ MySQL found"
    read -p "Do you want to create the database schema? (y/n): " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        echo "🔧 Setting up database..."
        echo "Please enter your MySQL credentials:"
        read -p "MySQL host (localhost): " DB_HOST
        DB_HOST=${DB_HOST:-localhost}
        read -p "MySQL user (root): " DB_USER
        DB_USER=${DB_USER:-root}
        read -s -p "MySQL password: " DB_PASSWORD
        echo
        
        # Test connection
        if mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASSWORD" -e "SELECT 1;" &> /dev/null; then
            echo "✅ Database connection successful"
            echo "🔧 Creating database schema..."
            mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASSWORD" < database/schema.sql
            echo "✅ Database schema created with sample data"
            
            # Update .env file
            sed -i.bak "s/DB_HOST=localhost/DB_HOST=$DB_HOST/" .env
            sed -i.bak "s/DB_USER=your-username/DB_USER=$DB_USER/" .env
            sed -i.bak "s/DB_PASSWORD=your-password/DB_PASSWORD=$DB_PASSWORD/" .env
            rm .env.bak
            echo "✅ .env file updated with database credentials"
        else
            echo "❌ Database connection failed. Please check your credentials."
        fi
    fi
else
    echo "⚠️  MySQL not found. Please install MySQL and run the schema manually:"
    echo "   mysql -u root -p < database/schema.sql"
fi

echo ""
echo "🎉 Setup completed!"
echo ""
echo "To start the application:"
echo "1. Activate the virtual environment: source venv/bin/activate"
echo "2. Configure database settings in .env file"
echo "3. Run the application: python app.py"
echo ""
echo "The application will be available at: http://localhost:5000"
echo ""
echo "📖 For more information, see README.md"
