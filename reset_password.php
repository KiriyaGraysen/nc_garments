<?php
require_once('config/database.php');

// If they are already logged in, redirect to dashboard
if (isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

$token = $_GET['token'] ?? '';
$is_valid = false;

if (!empty($token)) {
    // Check if token exists and has NOT expired
    $stmt = $conn->prepare("SELECT admin_id FROM admin WHERE reset_token = ? AND reset_expires > NOW() AND status = 'active' AND is_archived = 0");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    if ($stmt->get_result()->num_rows === 1) {
        $is_valid = true;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set New Password | NC Garments</title>
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
        
        <?php if ($is_valid): ?>
        <div class="pt-10 pb-6 px-8 text-center relative">
            <div class="w-16 h-16 bg-pink-50 dark:bg-pink-900/20 rounded-full flex items-center justify-center mx-auto mb-4 border border-pink-100 dark:border-pink-800/30">
                <i class="fa-solid fa-lock text-2xl text-pink-600 dark:text-pink-500"></i>
            </div>
            <h1 class="text-2xl font-extrabold tracking-wide text-gray-900 dark:text-white">Secure New Password</h1>
            <p class="text-xs font-medium text-gray-500 dark:text-zinc-400 mt-3 leading-relaxed">
                Please enter a strong new password for your account below.
            </p>
        </div>

        <div class="px-8 pb-8">
            <form id="reset-form">
                <input type="hidden" id="token" value="<?= htmlspecialchars($token) ?>">
                
                <div class="mb-5">
                    <label class="block text-xs font-semibold text-gray-600 dark:text-zinc-400 mb-1.5 uppercase tracking-wide">New Password</label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400 dark:text-zinc-500">
                            <i class="fa-solid fa-key text-sm"></i>
                        </span>
                        <input type="password" id="new_password" required placeholder="••••••••" 
                               class="w-full pl-11 pr-10 py-3 bg-gray-50 dark:bg-zinc-950 border border-gray-200 dark:border-zinc-800 text-gray-900 dark:text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-pink-500 transition-colors duration-300 text-sm">
                        
                        <button type="button" tabindex="-1" onclick="togglePassword('new_password', 'eye-icon-new')" class="absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-pink-500 transition-colors cursor-pointer focus:outline-none">
                            <i id="eye-icon-new" class="fa-solid fa-eye fa-fw text-sm"></i>
                        </button>
                    </div>
                </div>

                <div class="mb-8">
                    <label class="block text-xs font-semibold text-gray-600 dark:text-zinc-400 mb-1.5 uppercase tracking-wide">Confirm Password</label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400 dark:text-zinc-500">
                            <i class="fa-solid fa-check-double text-sm"></i>
                        </span>
                        <input type="password" id="confirm_password" required placeholder="••••••••" 
                               class="w-full pl-11 pr-10 py-3 bg-gray-50 dark:bg-zinc-950 border border-gray-200 dark:border-zinc-800 text-gray-900 dark:text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-pink-500 transition-colors duration-300 text-sm">
                        
                        <button type="button" tabindex="-1" onclick="togglePassword('confirm_password', 'eye-icon-confirm')" class="absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-pink-500 transition-colors cursor-pointer focus:outline-none">
                            <i id="eye-icon-confirm" class="fa-solid fa-eye fa-fw text-sm"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" id="save-btn" class="w-full py-3.5 px-4 bg-pink-600 hover:bg-pink-700 text-white font-bold rounded-xl shadow-lg transition-colors duration-300 flex justify-center items-center gap-2 cursor-pointer outline-none focus:ring-2 focus:ring-offset-2 focus:ring-pink-500 dark:focus:ring-offset-zinc-900">
                    Save New Password <i class="fa-solid fa-arrow-right text-sm"></i>
                </button>
            </form>
        </div>

        <?php else: ?>
        <div class="pt-12 pb-10 px-8 text-center relative">
            <div class="w-20 h-20 bg-rose-50 dark:bg-rose-500/10 text-rose-500 rounded-full flex items-center justify-center mx-auto mb-6 shadow-inner border border-rose-100 dark:border-rose-500/20">
                <i class="fa-solid fa-clock text-4xl"></i>
            </div>
            <h1 class="text-2xl font-extrabold text-gray-900 dark:text-white mb-2">Link Expired</h1>
            <p class="text-sm font-medium text-gray-600 dark:text-zinc-400 leading-relaxed mb-8">
                For your security, password reset links expire after 1 hour. This link is no longer valid.
            </p>
            <button onclick="window.location.href='forgot_password.php'" class="w-full py-3.5 px-4 bg-gray-900 hover:bg-black dark:bg-white dark:hover:bg-gray-100 text-white dark:text-gray-900 font-bold rounded-xl shadow-lg transition-all duration-300 flex justify-center items-center gap-2">
                Request New Link
            </button>
        </div>
        <?php endif; ?>

    </div>

    <div id="global-alert-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm transition-opacity" onclick="closeGlobalAlert()"></div>
        <div class="relative bg-white dark:bg-zinc-900 rounded-2xl w-full max-w-sm shadow-2xl overflow-hidden flex flex-col border border-gray-100 dark:border-zinc-800 transform scale-95 opacity-0 transition-all duration-200" id="global-alert-box">
            <div class="p-6 text-center">
                <div id="global-alert-icon-wrapper" class="w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4 text-2xl border">
                    <i id="global-alert-icon" class="fa-solid fa-circle-info"></i>
                </div>
                <h3 id="global-alert-title" class="text-xl font-bold text-gray-900 dark:text-white mb-2">Notice</h3>
                <p id="global-alert-msg" class="text-sm font-medium text-gray-600 dark:text-zinc-400 leading-relaxed"></p>
            </div>
            <div class="px-6 py-4 border-t border-gray-100 dark:border-zinc-800 bg-gray-50/50 dark:bg-zinc-950/30 flex justify-center">
                <button onclick="closeGlobalAlert()" class="bg-pink-600 hover:bg-pink-700 text-white px-8 py-2.5 rounded-xl text-sm font-bold shadow-md focus:outline-none w-full">Got it</button>
            </div>
        </div>
    </div>

    <script>
        function customAlert(message, title = "Notice", type = "info") {
            const modal = document.getElementById('global-alert-modal');
            const box = document.getElementById('global-alert-box');
            document.getElementById('global-alert-msg').textContent = message;
            document.getElementById('global-alert-title').textContent = title;
            
            const iconWrapper = document.getElementById('global-alert-icon-wrapper');
            const icon = document.getElementById('global-alert-icon');
            iconWrapper.className = "w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4 text-2xl border ";
            if (type === "error") {
                iconWrapper.className += "bg-rose-100 dark:bg-rose-500/20 text-rose-600 dark:text-rose-400 border-rose-200 dark:border-rose-500/30";
                icon.className = "fa-solid fa-circle-xmark";
            } else if (type === "success") {
                iconWrapper.className += "bg-emerald-100 dark:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 border-emerald-200 dark:border-emerald-500/30";
                icon.className = "fa-solid fa-circle-check";
            } else {
                iconWrapper.className += "bg-pink-100 dark:bg-pink-500/20 text-pink-600 dark:text-pink-400 border-pink-200 dark:border-pink-500/30";
                icon.className = "fa-solid fa-circle-info";
            }

            modal.classList.remove('hidden');
            setTimeout(() => {
                box.classList.remove('scale-95', 'opacity-0');
                box.classList.add('scale-100', 'opacity-100');
            }, 10);
        }

        function closeGlobalAlert() {
            const modal = document.getElementById('global-alert-modal');
            const box = document.getElementById('global-alert-box');
            box.classList.remove('scale-100', 'opacity-100');
            box.classList.add('scale-95', 'opacity-0');
            setTimeout(() => {
                modal.classList.add('hidden');
                if (redirectAfterClose) window.location.href = 'login.php';
            }, 200);
        }

        // --- PASSWORD VISIBILITY TOGGLE ---
        function togglePassword(inputId, iconId) {
            const passwordInput = document.getElementById(inputId);
            const eyeIcon = document.getElementById(iconId);
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                eyeIcon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }

        let redirectAfterClose = false;

        const resetForm = document.getElementById('reset-form');
        if (resetForm) {
            resetForm.addEventListener('submit', async function(event) {
                event.preventDefault(); 
            
                const token = document.getElementById('token').value;
                const newPassword = document.getElementById('new_password').value;
                const confirmPassword = document.getElementById('confirm_password').value;
                const saveBtn = document.getElementById('save-btn');
            
                if (newPassword !== confirmPassword) {
                    customAlert("Your passwords do not match. Please try again.", "Error", "error");
                    return;
                }

                saveBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving...';
                saveBtn.disabled = true; 
            
                try {
                    const response = await fetch('actions/update_forgotten_password.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ token: token, new_password: newPassword })
                    });
            
                    const data = await response.json();
            
                    if (data.status === 'success') {
                        redirectAfterClose = true;
                        customAlert("Your password has been successfully reset! You can now log in.", "Success", "success");
                    } else {
                        customAlert(data.message, "Error", "error");
                        saveBtn.innerHTML = 'Save New Password <i class="fa-solid fa-arrow-right text-sm"></i>';
                        saveBtn.disabled = false;
                    }
            
                } catch (error) {
                    customAlert('System Error: ' + error.message, "Error", "error");
                    saveBtn.innerHTML = 'Save New Password <i class="fa-solid fa-arrow-right text-sm"></i>';
                    saveBtn.disabled = false;
                }
            });
        }
    
        // Dark Mode Logic
        const themeToggleBtn = document.getElementById('theme-toggle');
        const themeIcon = document.getElementById('theme-icon');
        const htmlElement = document.documentElement;

        if (htmlElement.classList.contains('dark')) updateToggleUI(true);

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
                themeIcon.classList.replace(isDark ? 'fa-moon' : 'fa-sun', isDark ? 'fa-sun' : 'fa-moon');
            }
        }
    </script>
</body>
</html>