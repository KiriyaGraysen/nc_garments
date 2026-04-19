<?php
require_once('config/database.php');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// If they aren't even logged in, send them to the login page instead
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Denied | NC Garments</title>
    <link rel="icon" href="assets/images/icon.png">
    
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style type="text/tailwindcss">
        @theme {
            --font-sans: 'Poppins', ui-sans-serif, system-ui, sans-serif;
        }
        @custom-variant dark (&:where(.dark, .dark *));
    </style>
</head>
<body class="bg-gray-50 dark:bg-zinc-950 min-h-screen flex items-center justify-center p-4 antialiased font-sans transition-colors duration-300 relative overflow-hidden">

    <nav class="absolute top-0 left-0 w-full h-20 flex items-center justify-between px-6 md:px-12 z-20">
        <div class="w-8"></div>
        
        <div class="flex items-baseline gap-2">
            <span class="text-pink-600 font-serif italic text-2xl leading-none">NC</span>
            <span class="text-sm font-extrabold tracking-[0.15em] text-gray-900 dark:text-white uppercase">Garments</span>
        </div>

        <button id="theme-toggle" class="text-gray-400 hover:text-pink-600 transition-colors cursor-pointer focus:outline-none w-8 flex justify-end" title="Toggle Dark Mode">
            <i id="theme-icon" class="fa-solid fa-sun text-lg"></i>
        </button>
    </nav>

    <div class="absolute top-[-10%] left-[-10%] w-96 h-96 bg-rose-600/10 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute bottom-[-10%] right-[-10%] w-96 h-96 bg-rose-600/5 dark:bg-rose-600/10 rounded-full blur-3xl pointer-events-none"></div>

    <div class="w-full max-w-[420px] bg-white dark:bg-zinc-900 rounded-2xl shadow-2xl border border-gray-100 dark:border-zinc-800 overflow-hidden relative z-10 text-center">
        
        <div class="pt-12 pb-6 px-8 relative">
            <div class="w-24 h-24 bg-rose-50 dark:bg-rose-500/10 text-rose-500 rounded-full flex items-center justify-center mx-auto mb-6 shadow-inner border border-rose-100 dark:border-rose-500/20">
                <i class="fa-solid fa-user-lock text-4xl"></i>
            </div>
            
            <h1 class="text-2xl font-extrabold text-gray-900 dark:text-white mb-2">Access Denied</h1>
            <div class="inline-block bg-gray-100 dark:bg-zinc-800 text-gray-500 dark:text-zinc-400 text-[10px] font-black px-3 py-1 rounded-md uppercase tracking-widest mb-4 border border-gray-200 dark:border-zinc-700">
                Error 403: Forbidden
            </div>
            
            <p class="text-sm font-medium text-gray-600 dark:text-zinc-400 leading-relaxed px-4">
                Your current account role (<span class="font-bold text-gray-900 dark:text-white uppercase tracking-wider text-[11px]"><?php echo htmlspecialchars($_SESSION['role']); ?></span>) does not have the required security clearance to view this module.
            </p>
        </div>

        <div class="px-8 pb-10 mt-2">
            <button onclick="window.location.href='index.php'" class="w-full py-3.5 px-4 bg-gray-900 hover:bg-black dark:bg-white dark:hover:bg-gray-100 text-white dark:text-gray-900 font-bold rounded-xl shadow-lg transition-all duration-300 flex justify-center items-center gap-2 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-900 dark:focus:ring-offset-zinc-900">
                <i class="fa-solid fa-arrow-left text-sm"></i> Return to Dashboard
            </button>
            
            <div class="mt-6 pt-5 border-t border-gray-100 dark:border-zinc-800/50">
                <p class="text-[11px] text-gray-400 dark:text-zinc-500 leading-relaxed">
                    If you believe you should have access to this page, please contact the Superadmin to request a role upgrade.
                </p>
            </div>
        </div>

    </div>

    <script>
        // --- Dark Mode Toggle Logic ---
        const themeToggleBtn = document.getElementById('theme-toggle');
        const themeIcon = document.getElementById('theme-icon');
        const htmlElement = document.documentElement;

        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            htmlElement.classList.add('dark');
            updateToggleUI(true);
        }

        if (themeToggleBtn) {
            themeToggleBtn.addEventListener('click', () => {
                htmlElement.classList.toggle('dark');
                const isDark = htmlElement.classList.contains('dark');
                localStorage.theme = isDark ? 'dark' : 'light';
                updateToggleUI(isDark);
            });
        }

        function updateToggleUI(isDark) {
            if (themeIcon) {
                if (isDark) {
                    themeIcon.classList.replace('fa-moon', 'fa-sun');
                } else {
                    themeIcon.classList.replace('fa-sun', 'fa-moon');
                }
            }
        }
    </script>
</body>
</html>