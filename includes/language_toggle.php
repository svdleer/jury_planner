<?php
require_once 'includes/translations.php';
?>

<!-- Language Toggle Component -->
<div class="language-toggle" x-data="{ open: false }">
    <div class="relative">
        <button @click="open = !open" 
                class="flex items-center space-x-2 px-3 py-2 text-sm font-medium text-white bg-blue-500 hover:bg-blue-600 rounded-md transition-colors">
            <i class="fas fa-globe"></i>
            <span><?php echo Translations::getAvailableLanguages()[Translations::getCurrentLanguage()]; ?></span>
            <i class="fas fa-chevron-down transition-transform" :class="{ 'rotate-180': open }"></i>
        </button>
        
        <div x-show="open" 
             @click.away="open = false"
             x-transition:enter="transition ease-out duration-100"
             x-transition:enter-start="transform opacity-0 scale-95"
             x-transition:enter-end="transform opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-75"
             x-transition:leave-start="transform opacity-100 scale-100"
             x-transition:leave-end="transform opacity-0 scale-95"
             class="absolute right-0 mt-2 w-32 bg-white rounded-md shadow-lg ring-1 ring-black ring-opacity-5 z-50">
            <div class="py-1">
                <?php foreach (Translations::getAvailableLanguages() as $code => $name): ?>
                    <a href="?lang=<?php echo $code; ?>&<?php echo $_SERVER['QUERY_STRING'] ? preg_replace('/lang=[^&]*&?/', '', $_SERVER['QUERY_STRING']) : ''; ?>" 
                       class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 <?php echo Translations::getCurrentLanguage() === $code ? 'bg-blue-50 text-blue-700' : ''; ?>">
                        <span class="mr-2">
                            <?php if ($code === 'nl'): ?>
                                🇳🇱
                            <?php elseif ($code === 'en'): ?>
                                🇬🇧
                            <?php endif; ?>
                        </span>
                        <?php echo $name; ?>
                        <?php if (Translations::getCurrentLanguage() === $code): ?>
                            <i class="fas fa-check ml-auto text-blue-600"></i>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<style>
.language-toggle .fas.fa-chevron-down {
    transition: transform 0.2s ease;
}
</style>
