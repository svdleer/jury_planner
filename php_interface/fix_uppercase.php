<?php
/**
 * Script to identify and fix lines with multiple uppercase characters
 * This enforces a coding style rule to prevent multiple uppercase letters in a single line
 */

function scanFileForMultipleUppercase($filePath) {
    if (!file_exists($filePath)) {
        return [];
    }
    
    $lines = file($filePath, FILE_IGNORE_NEW_LINES);
    $violations = [];
    
    foreach ($lines as $lineNumber => $line) {
        // Skip lines that are comments, strings, or contain legitimate acronyms
        if (preg_match('/^\s*\/\//', $line) || // Skip comment lines
            preg_match('/^\s*\*/', $line) ||   // Skip doc comment lines
            preg_match('/^\s*\/\*/', $line)) { // Skip block comment start
            continue;
        }
        
        // Count uppercase letters in the line (excluding common acceptable patterns)
        $cleanLine = $line;
        
        // Remove common acceptable patterns:
        // - Environment variables like $_ENV, $_POST, $_GET
        $cleanLine = preg_replace('/\$_[A-Z]+/', '', $cleanLine);
        // - PDO constants
        $cleanLine = preg_replace('/PDO::[A-Z_]+/', '', $cleanLine);
        // - SQL keywords in strings
        $cleanLine = preg_replace('/"[^"]*"/', '', $cleanLine);
        $cleanLine = preg_replace("/'[^']*'/", '', $cleanLine);
        // - Class names that are camelCase (acceptable)
        $cleanLine = preg_replace('/\b[A-Z][a-z]+[A-Z][a-z]*\b/', '', $cleanLine);
        // - Function names like __construct
        $cleanLine = preg_replace('/__[a-z]+/', '', $cleanLine);
        
        // Count remaining uppercase letters
        $uppercaseCount = preg_match_all('/[A-Z]/', $cleanLine);
        
        if ($uppercaseCount > 1) {
            $violations[] = [
                'line' => $lineNumber + 1,
                'content' => $line,
                'uppercase_count' => $uppercaseCount,
                'suggestion' => suggestFix($line)
            ];
        }
    }
    
    return $violations;
}

function suggestFix($line) {
    $suggestions = [];
    
    // Common fixes for multiple uppercase patterns
    $patterns = [
        // Constants that should be lowercase
        '/\bDB_HOST\b/' => 'db_host',
        '/\bDB_USER\b/' => 'db_user', 
        '/\bDB_PASSWORD\b/' => 'db_password',
        '/\bDB_NAME\b/' => 'db_name',
        // Comments with multiple caps
        '/\bHTML\b/' => 'Html',
        '/\bCSS\b/' => 'Css',
        '/\bJS\b/' => 'Js',
        '/\bAPI\b/' => 'Api',
        '/\bURL\b/' => 'Url',
        '/\bJSON\b/' => 'Json',
        '/\bXML\b/' => 'Xml',
        // Common acronyms in comments
        '/\bMNC\b/' => 'Mnc',
        '/\bGO\b/' => 'Go',
    ];
    
    $suggestion = $line;
    foreach ($patterns as $pattern => $replacement) {
        if (preg_match($pattern, $line)) {
            $suggestion = preg_replace($pattern, $replacement, $suggestion);
            $suggestions[] = "Replace '$pattern' with '$replacement'";
        }
    }
    
    return empty($suggestions) ? "Consider breaking line or using lowercase" : implode(', ', $suggestions);
}

function fixFile($filePath, $autoFix = false) {
    echo "Scanning: $filePath\n";
    echo str_repeat("=", 50) . "\n";
    
    $violations = scanFileForMultipleUppercase($filePath);
    
    if (empty($violations)) {
        echo "✅ No violations found!\n\n";
        return;
    }
    
    echo "Found " . count($violations) . " lines with multiple uppercase characters:\n\n";
    
    foreach ($violations as $violation) {
        echo "Line {$violation['line']}: {$violation['uppercase_count']} uppercase letters\n";
        echo "Current:    {$violation['content']}\n";
        echo "Suggestion: {$violation['suggestion']}\n";
        echo str_repeat("-", 50) . "\n";
    }
    
    if ($autoFix) {
        echo "Auto-fixing not implemented yet for safety. Please review suggestions manually.\n";
    }
    
    echo "\n";
}

// Main execution
$filesToCheck = [
    'advanced_constraints.php',
    'config/database.php',
    'includes/translations.php',
    'teams.php',
    'matches.php',
    'mnc_dashboard.php'
];

echo "Multiple Uppercase Character Checker\n";
echo "====================================\n\n";

foreach ($filesToCheck as $file) {
    if (file_exists($file)) {
        fixFile($file);
    } else {
        echo "⚠️  File not found: $file\n\n";
    }
}

echo "✅ Scan complete!\n";
echo "\nRecommendations:\n";
echo "1. Break long lines with multiple uppercase acronyms\n";
echo "2. Use camelCase instead of UPPER_CASE for non-constants\n";
echo "3. Consider using lowercase for better readability\n";
echo "4. Keep environment variables and SQL constants as-is (they're filtered)\n";
?>
