<?php
require_once('config/database.php');

// If they are already logged in, they don't need to recover their password!
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
    <title>Account Recovery | Needle Class ERP</title>
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
            <div class="w-16 h-16 bg-pink-50 dark:bg-pink-900/20 rounded-full flex items-center justify-center mx-auto mb-4 border border-pink-100 dark:border-pink-800/30">
                <i class="fa-solid fa-key text-2xl text-pink-600 dark:text-pink-500"></i>
            </div>
            <h1 class="text-2xl font-extrabold tracking-wide text-gray-900 dark:text-white">
                Account Recovery
            </h1>
            <p class="text-xs font-medium text-gray-500 dark:text-zinc-400 mt-3 leading-relaxed">
                Enter your registered username or email address. If it exists in our system, we will send you instructions to securely reset your password.
            </p>
        </div>

        <div class="px-8 pb-8">
            <form id="recovery-form">
                <div class="mb-6">
                    <label for="recovery_id" class="block text-xs font-semibold text-gray-600 dark:text-zinc-400 mb-1.5 uppercase tracking-wide">Username or Email</label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400 dark:text-zinc-500">
                            <i class="fa-solid fa-envelope text-sm"></i>
                        </span>
                        <input type="text" id="recovery_id" required placeholder="e.g., jezel.admin or jezel@nc.com" 
                               class="w-full pl-11 pr-4 py-3 bg-gray-50 dark:bg-zinc-950 border border-gray-200 dark:border-zinc-800 text-gray-900 dark:text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent transition-colors duration-300 placeholder-gray-400 dark:placeholder-zinc-600 text-sm">
                    </div>
                </div>

                <button type="submit" id="recover-btn" class="w-full py-3.5 px-4 bg-gray-900 hover:bg-black dark:bg-white dark:hover:bg-gray-100 text-white dark:text-gray-900 font-bold rounded-xl shadow-lg transition-colors duration-300 flex justify-center items-center gap-2 cursor-pointer outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-900 dark:focus:ring-offset-zinc-900">
                    Send Reset Link <i class="fa-solid fa-paper-plane text-sm"></i>
                </button>
            </form>

            <div class="mt-6 text-center">
                <a href="login.php" class="text-sm font-semibold text-gray-500 hover:text-pink-600 dark:text-zinc-400 dark:hover:text-pink-500 transition-colors flex items-center justify-center gap-2 outline-none">
                    <i class="fa-solid fa-arrow-left"></i> Back to Login
                </a>
            </div>
        </div>
    </div>

    <script>
        const recoveryForm = document.getElementById('recovery-form');
    
        recoveryForm.addEventListener('submit', async function(event) {
            event.preventDefault(); 
        
            const recoveryId = document.getElementById('recovery_id').value;
            const recoverBtn = document.getElementById('recover-btn');
            const originalBtnText = recoverBtn.innerHTML;
        
            recoverBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Processing...';
            recoverBtn.disabled = true; 
            recoverBtn.classList.add('opacity-75', 'cursor-not-allowed');
        
            try {
                // We will build this backend action next!
                const response = await fetch('actions/process_recovery.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ recovery_id: recoveryId })
                });
        
                const data = await response.json();
        
                if (data.status === 'success') {
                    // Success! Change the form to show a success message
                    recoveryForm.innerHTML = `
                        <div class="bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800/50 rounded-xl p-4 text-center">
                            <i class="fa-solid fa-circle-check text-3xl text-emerald-500 mb-2"></i>
                            <h4 class="font-bold text-emerald-800 dark:text-emerald-400 text-sm mb-1">Request Received</h4>
                            <p class="text-xs text-emerald-600 dark:text-emerald-500/80">If an account matches that information, a reset link has been sent.</p>
                        </div>
                    `;
                } else {
                    alert(data.message || 'An error occurred.');
                    resetButton();
                }
        
            } catch (error) {
                alert('System Error: ' + error.message);
                resetButton();
            }
        
            function resetButton() {
                recoverBtn.innerHTML = originalBtnText;
                recoverBtn.disabled = false;
                recoverBtn.classList.remove('opacity-75', 'cursor-not-allowed');
            }
        });
    
        // --- Dark Mode Toggle Logic ---
        const themeToggleBtn = document.getElementById('theme-toggle');
        const themeIcon = document.getElementById('theme-icon');
        const htmlElement = document.documentElement;

        if (htmlElement.classList.contains('dark')) {
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