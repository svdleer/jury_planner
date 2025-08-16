#!/bin/bash

# PHP Interface Setup Script for Water Polo Jury Planner
# This script helps set up the PHP interface for development

echo "🏊 Water Polo Jury Planner - PHP Interface Setup"
echo "=================================================="

# Check PHP version
echo "Checking PHP version..."
PHP_VERSION=$(php -v | head -n 1 | cut -d " " -f 2 | cut -d "." -f 1,2)
if (( $(echo "$PHP_VERSION >= 8.0" | bc -l) )); then
    echo "✓ PHP $PHP_VERSION detected"
else
    echo "❌ PHP 8.0+ required, found $PHP_VERSION"
    exit 1
fi

# Check if .env exists
if [ ! -f "../.env" ]; then
    echo "Creating .env file from template..."
    cat > ../.env << EOL
# Database Configuration
DB_HOST=localhost
DB_PORT=3306
DB_NAME=jury_planner
DB_USER=root
DB_PASS=

# Application Environment
APP_ENV=development
EOL
    echo "✓ Created .env file. Please update database credentials."
else
    echo "✓ .env file exists"
fi

# Check database connection
echo "Testing database connection..."
php -r "
    \$env = parse_ini_file('../.env');
    try {
        \$pdo = new PDO(
            \"mysql:host={\$env['DB_HOST']};port={\$env['DB_PORT']};dbname={\$env['DB_NAME']}\",
            \$env['DB_USER'],
            \$env['DB_PASS']
        );
        echo \"✓ Database connection successful\n\";
    } catch (PDOException \$e) {
        echo \"❌ Database connection failed: \" . \$e->getMessage() . \"\n\";
        echo \"Please check your database settings in .env\n\";
        exit(1);
    }
"

# Check if tables exist
echo "Checking database schema..."
php -r "
    \$env = parse_ini_file('../.env');
    try {
        \$pdo = new PDO(
            \"mysql:host={\$env['DB_HOST']};port={\$env['DB_PORT']};dbname={\$env['DB_NAME']}\",
            \$env['DB_USER'],
            \$env['DB_PASS']
        );
        
        \$tables = ['teams', 'matches', 'jury_assignments'];
        \$missing = [];
        
        foreach (\$tables as \$table) {
            \$stmt = \$pdo->query(\"SHOW TABLES LIKE '\$table'\");
            if (\$stmt->rowCount() == 0) {
                \$missing[] = \$table;
            }
        }
        
        if (empty(\$missing)) {
            echo \"✓ All required tables exist\n\";
        } else {
            echo \"❌ Missing tables: \" . implode(', ', \$missing) . \"\n\";
            echo \"Please run the database setup script first.\n\";
        }
    } catch (PDOException \$e) {
        echo \"❌ Failed to check schema: \" . \$e->getMessage() . \"\n\";
    }
"

# Set permissions
echo "Setting file permissions..."
find . -type f -name "*.php" -exec chmod 644 {} \;
find . -type d -exec chmod 755 {} \;
echo "✓ File permissions set"

# Check if we're in a web server directory
if [ -d "/var/www" ] && [[ $PWD == /var/www* ]]; then
    echo "✓ Running in web server directory"
elif [ -d "/Applications/XAMPP" ]; then
    echo "💡 XAMPP detected. Consider linking this directory to htdocs"
elif [ -d "/Applications/MAMP" ]; then
    echo "💡 MAMP detected. Consider linking this directory to htdocs"
else
    echo "💡 Make sure to serve this directory through a web server"
fi

echo ""
echo "🚀 Setup completed!"
echo ""
echo "Next steps:"
echo "1. Update database credentials in ../.env if needed"
echo "2. Ensure your web server is running"
echo "3. Access the interface through your web browser"
echo "4. Visit index.php to start using the application"
echo ""
echo "For development, you can use PHP's built-in server:"
echo "  php -S localhost:8080"
echo ""
echo "Then visit: http://localhost:8080"
