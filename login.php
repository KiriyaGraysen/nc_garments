<?php
require_once('config/database.php');

// NEW: If they are already logged in, send them straight to the dashboard!
if (isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}
?>


<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Access | Needle Class ERP</title>
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

    <div class="absolute top-[-10%] left-[-10%] w-96 h-96 bg-pink-600/10 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute bottom-[-10%] right-[-10%] w-96 h-96 bg-pink-600/5 dark:bg-pink-600/10 rounded-full blur-3xl pointer-events-none"></div>

    <div class="w-full max-w-[420px] bg-white dark:bg-zinc-900 rounded-2xl shadow-2xl border border-gray-100 dark:border-zinc-800 overflow-hidden relative z-10 mt-10">
        
        <div class="pt-10 pb-6 px-8 text-center relative">
            <h1 class="text-3xl font-extrabold tracking-widest text-gray-900 dark:text-white flex justify-center items-center">
                <span class="text-pink-600 font-serif italic text-5xl mr-2">NC</span> NEEDLE CLASS
            </h1>
            <p class="text-xs font-semibold text-gray-400 dark:text-zinc-500 mt-3 uppercase tracking-[0.2em]">Enterprise System Access</p>
        </div>

        <div class="px-8 pb-10">
            <form id="login-form">
                <div class="mb-5">
                    <label for="username" class="block text-xs font-semibold text-gray-600 dark:text-zinc-400 mb-1.5 uppercase tracking-wide">Username</label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400 dark:text-zinc-500">
                            <i class="fa-solid fa-user text-sm"></i>
                        </span>
                        <input type="text" id="username" required placeholder="Enter your assigned username" 
                               class="w-full pl-10 pr-4 py-3 bg-gray-50 dark:bg-zinc-950 border border-gray-200 dark:border-zinc-800 text-gray-900 dark:text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent transition-colors duration-300 placeholder-gray-400 dark:placeholder-zinc-600 text-sm">
                    </div>
                </div>

                <div class="mb-6">
                    <div class="flex justify-between items-center mb-1.5">
                        <label for="password" class="block text-xs font-semibold text-gray-600 dark:text-zinc-400 uppercase tracking-wide">Password</label>
                        <a href="#" class="text-[11px] font-semibold text-pink-600 hover:text-pink-500 transition-colors">Forgot Password?</a>
                    </div>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400 dark:text-zinc-500">
                            <i class="fa-solid fa-lock text-sm"></i>
                        </span>
                        <input type="password" id="password" required placeholder="••••••••••••" 
                               class="w-full pl-10 pr-10 py-3 bg-gray-50 dark:bg-zinc-950 border border-gray-200 dark:border-zinc-800 text-gray-900 dark:text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent transition-colors duration-300 placeholder-gray-400 dark:placeholder-zinc-600 text-sm tracking-widest">
                        
                        <button type="button" onclick="togglePassword()" class="absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-pink-500 transition-colors cursor-pointer focus:outline-none">
                            <i id="eye-icon" class="fa-solid fa-eye fa-fw text-sm"></i>
                        </button>
                    </div>
                </div>

                <div class="flex items-center mb-6">
                    <input type="checkbox" id="remember" class="w-4 h-4 text-pink-600 bg-gray-100 border-gray-300 rounded focus:ring-pink-500 dark:focus:ring-pink-600 dark:ring-offset-zinc-900 focus:ring-2 dark:bg-zinc-800 dark:border-zinc-700 cursor-pointer">
                    <label for="remember" class="ml-2 text-sm font-medium text-gray-600 dark:text-zinc-400 cursor-pointer">Keep me logged in</label>
                </div>

                <button type="submit" id="login-btn" class="w-full py-3.5 px-4 bg-pink-600 hover:bg-pink-700 text-white font-bold rounded-xl shadow-lg shadow-pink-600/20 hover:shadow-pink-600/40 transition-colors duration-300 flex justify-center items-center gap-2 cursor-pointer outline-none focus:ring-2 focus:ring-offset-2 focus:ring-pink-500 dark:focus:ring-offset-zinc-900">
                    Secure Login <i class="fa-solid fa-arrow-right text-sm"></i>
                </button>
            </form>

            <div class="mt-4 pt-4 border-t border-gray-100 dark:border-zinc-800/50">
                <div class="flex items-start justify-center gap-2 text-center">
                    <i class="fa-solid fa-shield-halved text-gray-400 dark:text-zinc-600 mt-0.5 text-xs"></i>
                    <p class="text-[11px] text-gray-500 dark:text-zinc-500 leading-relaxed max-w-[250px]">
                        Authorized personnel only. Contact the Superadmin for account provisioning or access issues.
                    </p>
                </div>
            </div>

        </div>
    </div>

    <script>
        const loginForm = document.getElementById('login-form');
    
        // 1. Listen for the form submission
        loginForm.addEventListener('submit', async function(event) {
            // Stop the page from immediately refreshing!
            event.preventDefault(); 
        
            // 2. Grab the inputs, the button, AND the checkbox
            const usernameInput = document.getElementById('username').value;
            const passwordInput = document.getElementById('password').value;
            const rememberInput = document.getElementById('remember').checked; // NEW: Grab the checkbox!
            
            const loginBtn = document.getElementById('login-btn');
            const originalBtnText = loginBtn.innerHTML;
        
            // 3. UI Update: Show the loading state
            loginBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Authenticating...';
            loginBtn.disabled = true; 
            loginBtn.classList.add('opacity-75', 'cursor-not-allowed');
        
            try {
                // 4. Send the data to your PHP file in the background
                const response = await fetch('actions/login.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        username: usernameInput,
                        password: passwordInput,
                        remember: rememberInput // NEW: Send the checkbox status to PHP!
                    })
                });
        
                const data = await response.json();
        
                if (data.success) {
                    window.location.href = 'index.php'; 
                } else {
                    alert(data.message || 'Invalid username or password.');
                    resetButton();
                }
        
            } catch (error) {
                alert('System Error: ' + error.message);
                resetButton();
            }
        
            function resetButton() {
                loginBtn.innerHTML = originalBtnText;
                loginBtn.disabled = false;
                loginBtn.classList.remove('opacity-75', 'cursor-not-allowed');
            }
        });
    
        // --- Dark Mode Toggle Logic ---
        const themeToggleBtn = document.getElementById('theme-toggle');
        const themeIcon = document.getElementById('theme-icon');
        const htmlElement = document.documentElement; // Targets the <html> tag directly

        // 1. On page load, make sure the icon matches the current theme (set by header.php)
        if (htmlElement.classList.contains('dark')) {
            updateToggleUI(true);
        }

        // 2. Listen for the toggle button click
        if (themeToggleBtn) {
            themeToggleBtn.addEventListener('click', () => {
                htmlElement.classList.toggle('dark');
                const isDark = htmlElement.classList.contains('dark');
                
                // Save preference so the header.php catches it on next refresh
                localStorage.theme = isDark ? 'dark' : 'light';
                updateToggleUI(isDark);
            });
        }

        // 3. Update the Moon/Sun icon visually
        function updateToggleUI(isDark) {
            if (themeIcon) {
                if (isDark) {
                    themeIcon.classList.replace('fa-moon', 'fa-sun');
                } else {
                    themeIcon.classList.replace('fa-sun', 'fa-moon');
                }
            }
        }
    
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const eyeIcon = document.getElementById('eye-icon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                eyeIcon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }
    </script>
</body>
</html>
