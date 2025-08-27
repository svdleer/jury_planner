#!/bin/bash

# Optional database enhancement script
# Adds is_locked column to matches table for advanced match locking functionality

echo "🔧 Optional Database Enhancement: Adding is_locked column"
echo ""

# Check if we're in the right directory
if [ ! -f "php_interface/config/database.php" ]; then
    echo "❌ Error: Please run this script from the jury_planner root directory"
    exit 1
fi

# Test database connection first
echo "📋 Testing database connection..."
php -r "
require_once 'php_interface/config/database.php';
try {
    \$stmt = \$db->query('SELECT 1');
    echo '✅ Database connection successful\n';
} catch (Exception \$e) {
    echo '❌ Database connection failed: ' . \$e->getMessage() . '\n';
    exit(1);
}
"

if [ $? -ne 0 ]; then
    echo "❌ Cannot connect to database. Please check your configuration."
    exit 1
fi

# Check if column already exists
echo "🔍 Checking if is_locked column already exists..."
COLUMN_EXISTS=$(php -r "
require_once 'php_interface/config/database.php';
try {
    \$stmt = \$db->query('SHOW COLUMNS FROM matches LIKE \"is_locked\"');
    echo \$stmt->rowCount();
} catch (Exception \$e) {
    echo '0';
}
")

if [ "$COLUMN_EXISTS" -gt 0 ]; then
    echo "✅ is_locked column already exists. No action needed."
    exit 0
fi

echo "➕ Adding is_locked column to matches table..."

# Add the column
php -r "
require_once 'php_interface/config/database.php';
try {
    \$db->exec('ALTER TABLE matches ADD COLUMN is_locked BOOLEAN DEFAULT FALSE');
    echo '✅ is_locked column added successfully\n';
} catch (Exception \$e) {
    echo '❌ Error adding column: ' . \$e->getMessage() . '\n';
    exit(1);
}
"

if [ $? -eq 0 ]; then
    echo ""
    echo "🎉 Enhancement complete!"
    echo ""
    echo "📖 What this adds:"
    echo "   • Ability to lock matches to prevent assignment changes"
    echo "   • Enhanced optimization control"
    echo "   • Protection for finalized assignments"
    echo ""
    echo "💡 The constraint editor will automatically use this feature when available."
else
    echo "❌ Failed to add is_locked column. Check database permissions."
    exit 1
fi
