<?php
$page_title = "Settings | NC Garments";

include 'includes/header.php'; 
?>

<main class="flex-1 p-4 md:p-8 overflow-y-auto transition-colors duration-500 font-sans relative scroll-smooth">
    
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white transition-colors duration-500">Account & Settings</h2>
            <p class="text-gray-500 dark:text-zinc-400 text-sm mt-1 transition-colors duration-500">Manage your personal information and security.</p>
        </div>
        <button id="save-btn" onclick="saveSettings()" class="bg-pink-600 hover:bg-pink-700 text-white px-6 py-2.5 rounded-xl text-sm font-bold transition-all duration-300 shadow-lg shadow-pink-600/20 hover:shadow-pink-600/40 flex items-center gap-2 cursor-pointer focus:outline-none">
            <i class="fa-solid fa-floppy-disk"></i> Save All Changes
        </button>
    </div>

    <div class="flex flex-col lg:flex-row gap-8">
        
        <div class="w-full lg:w-64 shrink-0">
            <div class="flex flex-col gap-2 sticky top-4">
                <a href="#personal-info" class="settings-nav-link w-full flex items-center gap-3 px-4 py-3 bg-white dark:bg-zinc-900 border border-pink-200 dark:border-pink-900/50 rounded-xl text-pink-600 dark:text-pink-500 font-bold text-sm shadow-sm transition-all duration-300 text-left">
                    <i class="fa-solid fa-user w-5 text-center"></i> Personal Details
                </a>
                <a href="#security" class="settings-nav-link w-full flex items-center gap-3 px-4 py-3 text-gray-600 dark:text-zinc-400 hover:bg-gray-100 dark:hover:bg-zinc-900 rounded-xl font-semibold text-sm transition-all duration-300 text-left border border-transparent">
                    <i class="fa-solid fa-shield-halved w-5 text-center"></i> Password & Security
                </a>
            </div>
        </div>

        <div class="flex-1 space-y-8 pb-12">
            
            <div id="personal-info" class="settings-section bg-white dark:bg-zinc-900 rounded-2xl shadow-sm border border-gray-100 dark:border-zinc-800 p-6 md:p-8 transition-colors duration-500 scroll-mt-8">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-6 border-b border-gray-100 dark:border-zinc-800 pb-4">Personal Information</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-xs font-bold text-gray-600 dark:text-zinc-400 mb-2 uppercase tracking-wide">Full Name</label>
                        <input type="text" id="full_name" value="<?php echo htmlspecialchars($admin['full_name'] ?? ''); ?>" autocomplete="name" class="w-full px-4 py-3 bg-gray-50 dark:bg-zinc-950 border border-gray-200 dark:border-zinc-800 text-gray-900 dark:text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent transition-colors duration-500 text-sm font-medium">
                    </div>
                    
                    <div>
                        <label class="block text-xs font-bold text-gray-600 dark:text-zinc-400 mb-2 uppercase tracking-wide">Email Address</label>
                        <input type="email" id="email" value="<?php echo htmlspecialchars($admin['email'] ?? ''); ?>" autocomplete="email" placeholder="admin@example.com" class="w-full px-4 py-3 bg-gray-50 dark:bg-zinc-950 border border-gray-200 dark:border-zinc-800 text-gray-900 dark:text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent transition-colors duration-500 text-sm font-medium">
                    </div>
                </div>
            </div>

            <div id="security" class="settings-section bg-white dark:bg-zinc-900 rounded-2xl shadow-sm border border-gray-100 dark:border-zinc-800 p-6 md:p-8 transition-colors duration-500 scroll-mt-8">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-6 border-b border-gray-100 dark:border-zinc-800 pb-4">Change Password</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-gray-600 dark:text-zinc-400 mb-2 uppercase tracking-wide">Current Password</label>
                        <input type="password" id="current_password" autocomplete="new-password" placeholder="••••••••" class="w-full px-4 py-3 bg-gray-50 dark:bg-zinc-950 border border-gray-200 dark:border-zinc-800 text-gray-900 dark:text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent transition-colors duration-500 text-sm font-medium">
                        <p class="text-[10px] font-medium text-gray-500 dark:text-zinc-500 mt-1.5">Required only if you are changing your password.</p>
                    </div>
                    
                    <div>
                        <label class="block text-xs font-bold text-gray-600 dark:text-zinc-400 mb-2 uppercase tracking-wide">New Password</label>
                        <input type="password" id="new_password" autocomplete="new-password" placeholder="••••••••" class="w-full px-4 py-3 bg-gray-50 dark:bg-zinc-950 border border-gray-200 dark:border-zinc-800 text-gray-900 dark:text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent transition-colors duration-500 text-sm font-medium">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-600 dark:text-zinc-400 mb-2 uppercase tracking-wide">Confirm New Password</label>
                        <input type="password" id="confirm_password" autocomplete="new-password" placeholder="••••••••" class="w-full px-4 py-3 bg-gray-50 dark:bg-zinc-950 border border-gray-200 dark:border-zinc-800 text-gray-900 dark:text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent transition-colors duration-500 text-sm font-medium">
                    </div>
                </div>
            </div>

        </div>
    </div>
</main>

<div id="global-alert-modal" class="fixed inset-0 z-[90] hidden flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm transition-opacity" onclick="closeGlobalAlert()"></div>
    <div class="relative bg-white dark:bg-zinc-900 rounded-2xl w-full max-w-sm shadow-2xl overflow-hidden flex flex-col border border-gray-100 dark:border-zinc-800 transform scale-95 opacity-0 transition-all duration-200" id="global-alert-box">
        <div class="p-6 text-center">
            <div id="global-alert-icon-wrapper" class="w-16 h-16 bg-pink-100 dark:bg-pink-500/20 text-pink-600 dark:text-pink-400 rounded-full flex items-center justify-center mx-auto mb-4 text-2xl border border-pink-200 dark:border-pink-500/30">
                <i id="global-alert-icon" class="fa-solid fa-circle-info"></i>
            </div>
            <h3 id="global-alert-title" class="text-xl font-bold text-gray-900 dark:text-white mb-2">Notice</h3>
            <p id="global-alert-msg" class="text-sm font-medium text-gray-600 dark:text-zinc-400 leading-relaxed whitespace-pre-wrap"></p>
        </div>
        <div class="px-6 py-4 border-t border-gray-100 dark:border-zinc-800 bg-gray-50/50 dark:bg-zinc-950/30 flex justify-center">
            <button onclick="closeGlobalAlert()" class="bg-pink-600 hover:bg-pink-700 text-white px-8 py-2.5 rounded-xl text-sm font-bold shadow-md shadow-pink-600/20 focus:outline-none transition-all w-full">Got it</button>
        </div>
    </div>
</div>

<script>
    // --- GLOBAL MODAL OVERRIDES ---
    function customAlert(message, title = "Notice", type = "info") {
        const modal = document.getElementById('global-alert-modal');
        const box = document.getElementById('global-alert-box');
        const msgEl = document.getElementById('global-alert-msg');
        const titleEl = document.getElementById('global-alert-title');
        const iconWrapper = document.getElementById('global-alert-icon-wrapper');
        const icon = document.getElementById('global-alert-icon');

        msgEl.textContent = message;
        titleEl.textContent = title;

        iconWrapper.className = "w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4 text-2xl border ";
        if (type === "error") {
            iconWrapper.className += "bg-rose-100 dark:bg-rose-500/20 text-rose-600 dark:text-rose-400 border-rose-200 dark:border-rose-500/30";
            icon.className = "fa-solid fa-circle-xmark";
        } else if (type === "success") {
            iconWrapper.className += "bg-emerald-100 dark:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 border-emerald-200 dark:border-emerald-500/30";
            icon.className = "fa-solid fa-circle-check";
        } else if (type === "warning") {
            iconWrapper.className += "bg-amber-100 dark:bg-amber-500/20 text-amber-600 dark:text-amber-400 border-amber-200 dark:border-amber-500/30";
            icon.className = "fa-solid fa-triangle-exclamation";
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
        setTimeout(() => modal.classList.add('hidden'), 200);
    }

    // 🚨 BACKEND SAVING LOGIC
    async function saveSettings() {
        const btn = document.getElementById('save-btn');
        const originalText = btn.innerHTML;

        const fullName = document.getElementById('full_name').value.trim();
        const email = document.getElementById('email').value.trim();
        const currentPassword = document.getElementById('current_password').value;
        const newPassword = document.getElementById('new_password').value;
        const confirmPassword = document.getElementById('confirm_password').value;

        if (!fullName || !email) {
            customAlert("Full Name and Email Address cannot be blank.", "Missing Required Fields", "error");
            return;
        }

        if (newPassword !== '' && newPassword !== confirmPassword) {
            customAlert("Your new passwords do not match. Please retype them carefully.", "Password Mismatch", "error");
            return;
        }
        
        if (newPassword !== '' && currentPassword === '') {
            customAlert("You must enter your Current Password to set a new one.", "Verification Required", "warning");
            return;
        }

        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving...';
        btn.disabled = true;

        try {
            const response = await fetch('actions/save_settings.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    full_name: fullName,
                    email: email,
                    current_password: currentPassword,
                    new_password: newPassword
                })
            });
            
            const data = await response.json();
            
            if (data.status === 'success') {
                customAlert('Your account settings have been successfully updated!', 'Settings Saved', 'success');
                
                // Clear out the password fields if they were used
                document.getElementById('current_password').value = '';
                document.getElementById('new_password').value = '';
                document.getElementById('confirm_password').value = '';
                
                // Update the UI header name without refreshing
                const nameDisplays = document.querySelectorAll('.text-sm.font-bold.text-gray-900');
                if(nameDisplays.length > 0) nameDisplays[nameDisplays.length-1].textContent = fullName;
                
            } else {
                customAlert(data.message, 'Update Failed', 'error');
            }
        } catch (error) {
            customAlert('A network error occurred while trying to save.', 'System Error', 'error');
        } finally {
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    }

    // 🚨 DYNAMIC SIDEBAR HIGHLIGHT LOGIC (SCROLLSPY)
    document.addEventListener("DOMContentLoaded", function() {
        const navLinks = document.querySelectorAll('.settings-nav-link');
        const sections = document.querySelectorAll('.settings-section');

        function setActiveLink(activeId) {
            navLinks.forEach(link => {
                const targetId = link.getAttribute('href').substring(1);
                if (targetId === activeId) {
                    link.classList.remove('text-gray-600', 'dark:text-zinc-400', 'hover:bg-gray-100', 'dark:hover:bg-zinc-900', 'border-transparent');
                    link.classList.add('bg-white', 'dark:bg-zinc-900', 'border-pink-200', 'dark:border-pink-900/50', 'text-pink-600', 'dark:text-pink-500', 'shadow-sm', 'font-bold');
                    link.classList.remove('font-semibold');
                } else {
                    link.classList.add('text-gray-600', 'dark:text-zinc-400', 'hover:bg-gray-100', 'dark:hover:bg-zinc-900', 'border-transparent', 'font-semibold');
                    link.classList.remove('bg-white', 'dark:bg-zinc-900', 'border-pink-200', 'dark:border-pink-900/50', 'text-pink-600', 'dark:text-pink-500', 'shadow-sm', 'font-bold');
                }
            });
        }

        const observerOptions = { root: null, rootMargin: '-20% 0px -70% 0px', threshold: 0 };
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) setActiveLink(entry.target.id);
            });
        }, observerOptions);

        sections.forEach(section => observer.observe(section));

        navLinks.forEach(link => {
            link.addEventListener('click', function() {
                const targetId = this.getAttribute('href').substring(1);
                setActiveLink(targetId);
            });
        });
    });
</script>

<?php include 'includes/footer.php'; ?>