<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Waterpolo jury planner'; ?></title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Heroicons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@heroicons/react@2.0.0/24/outline/index.css">
    
    <!-- Alpine.js for interactive components -->
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <!-- Custom styles -->
    <style>
        [x-cloak] { display: none !important; }
        
        /* Custom scrollbar */
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f1f5f9;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 3px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
    </style>
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'water-blue': {
                            50: '#f0f9ff',
                            100: '#e0f2fe',
                            200: '#bae6fd',
                            300: '#7dd3fc',
                            400: '#38bdf8',
                            500: '#0ea5e9',
                            600: '#0284c7',
                            700: '#0369a1',
                            800: '#075985',
                            900: '#0c4a6e',
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="h-full">
    <div class="min-h-full">
        <!-- Navigation -->
        <nav class="bg-water-blue-800" x-data="{ mobileMenuOpen: false }">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 items-center justify-between">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="flex items-center">
                                <svg class="h-8 w-8 text-water-blue-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                </svg>
                                <span class="ml-2 text-xl font-bold text-white">Jury Planner</span>
                            </div>
                        </div>
                        <div class="hidden md:block">
                            <div class="ml-10 flex items-baseline space-x-4">
                                <a href="index.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'bg-water-blue-900 text-white' : 'text-water-blue-300 hover:bg-water-blue-700 hover:text-white'; ?> rounded-md px-3 py-2 text-sm font-medium transition-colors">
                                    Dashboard
                                </a>
                                <a href="teams.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'teams.php' ? 'bg-water-blue-900 text-white' : 'text-water-blue-300 hover:bg-water-blue-700 hover:text-white'; ?> rounded-md px-3 py-2 text-sm font-medium transition-colors">
                                    Teams
                                </a>
                                <a href="matches.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'matches.php' ? 'bg-water-blue-900 text-white' : 'text-water-blue-300 hover:bg-water-blue-700 hover:text-white'; ?> rounded-md px-3 py-2 text-sm font-medium transition-colors">
                                    Matches
                                </a>
                                <a href="schedule.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'schedule.php' ? 'bg-water-blue-900 text-white' : 'text-water-blue-300 hover:bg-water-blue-700 hover:text-white'; ?> rounded-md px-3 py-2 text-sm font-medium transition-colors">
                                    Schedule
                                </a>
                                <a href="planning.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'planning.php' ? 'bg-water-blue-900 text-white' : 'text-water-blue-300 hover:bg-water-blue-700 hover:text-white'; ?> rounded-md px-3 py-2 text-sm font-medium transition-colors">
                                    Planning
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Mobile menu button -->
                    <div class="md:hidden">
                        <button @click="mobileMenuOpen = !mobileMenuOpen" class="text-water-blue-300 hover:bg-water-blue-700 hover:text-white block px-3 py-2 rounded-md text-base font-medium">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path x-show="!mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                                <path x-show="mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Mobile menu -->
            <div x-show="mobileMenuOpen" x-cloak class="md:hidden">
                <div class="space-y-1 px-2 pb-3 pt-2 sm:px-3 bg-water-blue-900">
                    <a href="index.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'bg-water-blue-800 text-white' : 'text-water-blue-300 hover:bg-water-blue-700 hover:text-white'; ?> block rounded-md px-3 py-2 text-base font-medium">Dashboard</a>
                    <a href="teams.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'teams.php' ? 'bg-water-blue-800 text-white' : 'text-water-blue-300 hover:bg-water-blue-700 hover:text-white'; ?> block rounded-md px-3 py-2 text-base font-medium">Teams</a>
                    <a href="matches.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'matches.php' ? 'bg-water-blue-800 text-white' : 'text-water-blue-300 hover:bg-water-blue-700 hover:text-white'; ?> block rounded-md px-3 py-2 text-base font-medium">Matches</a>
                    <a href="schedule.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'schedule.php' ? 'bg-water-blue-800 text-white' : 'text-water-blue-300 hover:bg-water-blue-700 hover:text-white'; ?> block rounded-md px-3 py-2 text-base font-medium">Schedule</a>
                    <a href="planning.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'planning.php' ? 'bg-water-blue-800 text-white' : 'text-water-blue-300 hover:bg-water-blue-700 hover:text-white'; ?> block rounded-md px-3 py-2 text-base font-medium">Planning</a>
                </div>
            </div>
        </nav>

        <!-- Page header -->
        <header class="bg-white shadow">
            <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                <h1 class="text-3xl font-bold tracking-tight text-gray-900">
                    <?php echo $pageTitle ?? 'Dashboard'; ?>
                </h1>
                <?php if (isset($pageDescription)): ?>
                    <p class="mt-2 text-sm text-gray-600"><?php echo $pageDescription; ?></p>
                <?php endif; ?>
            </div>
        </header>

        <!-- Main content -->
        <main>
            <div class="mx-auto max-w-7xl py-6 sm:px-6 lg:px-8">
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="mb-6 rounded-md bg-green-50 p-4" x-data="{ show: true }" x-show="show">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-green-800"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></p>
                            </div>
                            <div class="ml-auto pl-3">
                                <button @click="show = false" class="text-green-500 hover:text-green-600">
                                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="mb-6 rounded-md bg-red-50 p-4" x-data="{ show: true }" x-show="show">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-red-800"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></p>
                            </div>
                            <div class="ml-auto pl-3">
                                <button @click="show = false" class="text-red-500 hover:text-red-600">
                                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Page content will be inserted here -->
                <?php echo $content ?? ''; ?>
            </div>
        </main>
    </div>

    <!-- Toast notifications container -->
    <div id="toast-container" class="fixed top-4 right-4 z-50 space-y-2"></div>

    <script>
        // Toast notification function
        function showToast(message, type = 'info') {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            const bgColor = {
                'success': 'bg-green-500',
                'error': 'bg-red-500',
                'warning': 'bg-yellow-500',
                'info': 'bg-blue-500'
            }[type] || 'bg-blue-500';
            
            toast.className = `${bgColor} text-white px-6 py-3 rounded-lg shadow-lg transform transition-all duration-300 translate-x-full opacity-0`;
            toast.innerHTML = `
                <div class="flex items-center justify-between">
                    <span>${message}</span>
                    <button onclick="this.closest('div').remove()" class="ml-4 text-white hover:text-gray-200">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>
            `;
            
            container.appendChild(toast);
            
            // Animate in
            setTimeout(() => {
                toast.classList.remove('translate-x-full', 'opacity-0');
            }, 10);
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                toast.classList.add('translate-x-full', 'opacity-0');
                setTimeout(() => toast.remove(), 300);
            }, 5000);
        }

        // Form validation helper
        function validateForm(formSelector) {
            const form = document.querySelector(formSelector);
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.classList.add('border-red-500');
                    isValid = false;
                } else {
                    field.classList.remove('border-red-500');
                }
            });
            
            return isValid;
        }

        // Loading state helper
        function setLoadingState(button, loading) {
            if (loading) {
                button.disabled = true;
                button.innerHTML = `
                    <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Loading...
                `;
            } else {
                button.disabled = false;
                button.innerHTML = button.dataset.originalText || 'Submit';
            }
        }
    </script>
</body>
</html>
