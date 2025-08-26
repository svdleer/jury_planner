<?php

// Script to fix remaining multiple uppercase letters in translation strings
// This focuses on the problematic patterns while preserving legitimate cases

function fixTranslationString($text) {
    // Skip if it's an acronym or proper noun that should stay uppercase
    $preservePatterns = [
        '/MNC/', // Organization name
        '/GO Competition/', // Specific competition name
        '/DB/', // When referring to database in technical context
        '/API/', // Technical terms
        '/URL/', // Technical terms
        '/HTTP/', // Technical terms
        '/JSON/', // Technical terms
        '/CSV/', // Technical terms
        '/PDF/', // File formats
        '/HTML/', // Technical terms
    ];
    
    foreach ($preservePatterns as $pattern) {
        if (preg_match($pattern, $text)) {
            return $text; // Don't change if it contains patterns that should be preserved
        }
    }
    
    // Fix common problematic patterns:
    // - "Title Case Words" -> "Title case words" (except first word)
    // - "CAPS Text" -> "Caps text"
    // - Multiple title case words in middle of sentence
    
    $text = preg_replace_callback('/\b([A-Z][a-z]+)\s+([A-Z][a-z]+)/', function($matches) {
        return $matches[1] . ' ' . lcfirst($matches[2]);
    }, $text);
    
    return $text;
}

// Read the file
$filename = 'includes/translations.php';
$content = file_get_contents($filename);

// Process translation strings
$content = preg_replace_callback(
    "/'([^']+)'\s*=>\s*'([^']+)'/", 
    function($matches) {
        $key = $matches[1];
        $value = $matches[2];
        $newValue = fixTranslationString($value);
        return "'" . $key . "' => '" . $newValue . "'";
    }, 
    $content
);

// Write back to file
file_put_contents($filename, $content);

echo "Fixed remaining uppercase issues in translations.php\n";
?>
