<?php
$page_title = "Settings | NC Garments";

include 'includes/header.php'; 
?>

<main class="flex-1 p-4 md:p-8 overflow-y-auto transition-colors duration-500 font-sans relative scroll-smooth">
    
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white transition-colors duration-500">Account & Settings</h2>
            <p class="text-gray-500 dark:text-zinc-400 text-sm mt-1 transition-colors duration-500">Manage your personal information, security, and system preferences.</p>
        </div>
        <button class="bg-pink-600 hover:bg-pink-700 text-white px-6 py-2.5 rounded-xl text-sm font-bold transition-all duration-300 shadow-lg shadow-pink-600/20 hover:shadow-pink-600/40 flex items-center gap-2 cursor-pointer focus:outline-none">
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
                <a href="#system-prefs" class="settings-nav-link w-full flex items-center gap-3 px-4 py-3 text-gray-600 dark:text-zinc-400 hover:bg-gray-100 dark:hover:bg-zinc-900 rounded-xl font-semibold text-sm transition-all duration-300 text-left border border-transparent">
                    <i class="fa-solid fa-sliders w-5 text-center"></i> System Preferences
                </a>
            </div>
        </div>

        <div class="flex-1 space-y-8 pb-12">
            
            <div id="personal-info" class="settings-section bg-white dark:bg-zinc-900 rounded-2xl shadow-sm border border-gray-100 dark:border-zinc-800 p-6 md:p-8 transition-colors duration-500 scroll-mt-8">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-6 border-b border-gray-100 dark:border-zinc-800 pb-4">Personal Information</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-gray-600 dark:text-zinc-400 mb-2 uppercase tracking-wide">Full Name</label>
                        <input type="text" value="<?php echo htmlspecialchars($admin['full_name'] ?? ''); ?>" autocomplete="name" class="w-full px-4 py-3 bg-gray-50 dark:bg-zinc-950 border border-gray-200 dark:border-zinc-800 text-gray-900 dark:text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent transition-colors duration-500 text-sm font-medium">
                    </div>
                    
                    <div>
                        <label class="block text-xs font-bold text-gray-600 dark:text-zinc-400 mb-2 uppercase tracking-wide">Username</label>
                        <input type="text" value="<?php echo htmlspecialchars($admin['username'] ?? ''); ?>" autocomplete="username" class="w-full px-4 py-3 bg-gray-50 dark:bg-zinc-950 border border-gray-200 dark:border-zinc-800 text-gray-900 dark:text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent transition-colors duration-500 text-sm font-medium">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-600 dark:text-zinc-400 mb-2 uppercase tracking-wide">Email Address</label>
                        <input type="email" value="<?php echo htmlspecialchars($admin['email'] ?? ''); ?>" autocomplete="email" placeholder="admin@example.com" class="w-full px-4 py-3 bg-gray-50 dark:bg-zinc-950 border border-gray-200 dark:border-zinc-800 text-gray-900 dark:text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent transition-colors duration-500 text-sm font-medium">
                    </div>
                </div>
            </div>

            <div id="security" class="settings-section bg-white dark:bg-zinc-900 rounded-2xl shadow-sm border border-gray-100 dark:border-zinc-800 p-6 md:p-8 transition-colors duration-500 scroll-mt-8">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-6 border-b border-gray-100 dark:border-zinc-800 pb-4">Change Password</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-gray-600 dark:text-zinc-400 mb-2 uppercase tracking-wide">Current Password</label>
                        <input type="password" autocomplete="new-password" placeholder="••••••••" class="w-full px-4 py-3 bg-gray-50 dark:bg-zinc-950 border border-gray-200 dark:border-zinc-800 text-gray-900 dark:text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent transition-colors duration-500 text-sm font-medium">
                    </div>
                    
                    <div>
                        <label class="block text-xs font-bold text-gray-600 dark:text-zinc-400 mb-2 uppercase tracking-wide">New Password</label>
                        <input type="password" autocomplete="new-password" placeholder="••••••••" class="w-full px-4 py-3 bg-gray-50 dark:bg-zinc-950 border border-gray-200 dark:border-zinc-800 text-gray-900 dark:text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent transition-colors duration-500 text-sm font-medium">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-600 dark:text-zinc-400 mb-2 uppercase tracking-wide">Confirm New Password</label>
                        <input type="password" autocomplete="new-password" placeholder="••••••••" class="w-full px-4 py-3 bg-gray-50 dark:bg-zinc-950 border border-gray-200 dark:border-zinc-800 text-gray-900 dark:text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent transition-colors duration-500 text-sm font-medium">
                    </div>
                </div>
            </div>

            <div id="system-prefs" class="settings-section bg-white dark:bg-zinc-900 rounded-2xl shadow-sm border border-gray-100 dark:border-zinc-800 p-6 md:p-8 transition-colors duration-500 scroll-mt-8">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-6 border-b border-gray-100 dark:border-zinc-800 pb-4">System Preferences</h3>
                
                <div class="flex items-center justify-between py-4 border-b border-gray-50 dark:border-zinc-800/50">
                    <div>
                        <h4 class="text-sm font-bold text-gray-900 dark:text-white">Low Stock Alerts</h4>
                        <p class="text-xs text-gray-500 dark:text-zinc-400 mt-0.5">Show red badge warnings when inventory falls below minimum threshold.</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" value="" class="sr-only peer" checked>
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-pink-300 dark:peer-focus:ring-pink-800 rounded-full peer dark:bg-zinc-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-pink-600"></div>
                    </label>
                </div>

                <div class="flex items-center justify-between py-4 border-b border-gray-50 dark:border-zinc-800/50">
                    <div>
                        <h4 class="text-sm font-bold text-gray-900 dark:text-white">Strict Session Timeout</h4>
                        <p class="text-xs text-gray-500 dark:text-zinc-400 mt-0.5">Automatically log out securely after 30 minutes of inactivity.</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" value="" class="sr-only peer" checked>
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-pink-300 dark:peer-focus:ring-pink-800 rounded-full peer dark:bg-zinc-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-pink-600"></div>
                    </label>
                </div>
                
                <div class="flex items-center justify-between py-4 border-b border-gray-50 dark:border-zinc-800/50">
                    <div>
                        <h4 class="text-sm font-bold text-gray-900 dark:text-white">Compact Table Density</h4>
                        <p class="text-xs text-gray-500 dark:text-zinc-400 mt-0.5">Reduce padding in tables to display more rows at once.</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" value="" class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-pink-300 dark:peer-focus:ring-pink-800 rounded-full peer dark:bg-zinc-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-pink-600"></div>
                    </label>
                </div>

                <div class="flex items-center justify-between py-4">
                    <div>
                        <h4 class="text-sm font-bold text-gray-900 dark:text-white">Enable UI Sounds</h4>
                        <p class="text-xs text-gray-500 dark:text-zinc-400 mt-0.5">Play subtle audio chimes for notifications and success messages.</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" value="" class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-pink-300 dark:peer-focus:ring-pink-800 rounded-full peer dark:bg-zinc-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-pink-600"></div>
                    </label>
                </div>
            </div>

        </div>
    </div>
</main>

<script>
    // 🚨 DYNAMIC SIDEBAR HIGHLIGHT LOGIC (SCROLLSPY)
    document.addEventListener("DOMContentLoaded", function() {
        const navLinks = document.querySelectorAll('.settings-nav-link');
        const sections = document.querySelectorAll('.settings-section');

        // Function to style the active link
        function setActiveLink(activeId) {
            navLinks.forEach(link => {
                const targetId = link.getAttribute('href').substring(1);
                
                if (targetId === activeId) {
                    // Make it Pink/Active
                    link.classList.remove('text-gray-600', 'dark:text-zinc-400', 'hover:bg-gray-100', 'dark:hover:bg-zinc-900', 'border-transparent');
                    link.classList.add('bg-white', 'dark:bg-zinc-900', 'border-pink-200', 'dark:border-pink-900/50', 'text-pink-600', 'dark:text-pink-500', 'shadow-sm', 'font-bold');
                    link.classList.remove('font-semibold');
                } else {
                    // Make it Gray/Inactive
                    link.classList.add('text-gray-600', 'dark:text-zinc-400', 'hover:bg-gray-100', 'dark:hover:bg-zinc-900', 'border-transparent', 'font-semibold');
                    link.classList.remove('bg-white', 'dark:bg-zinc-900', 'border-pink-200', 'dark:border-pink-900/50', 'text-pink-600', 'dark:text-pink-500', 'shadow-sm', 'font-bold');
                }
            });
        }

        // Setup the Intersection Observer to watch scrolling
        const observerOptions = {
            root: null, // use the viewport
            rootMargin: '-20% 0px -70% 0px', // Trigger when section is near the top
            threshold: 0
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    setActiveLink(entry.target.id);
                }
            });
        }, observerOptions);

        sections.forEach(section => {
            observer.observe(section);
        });

        // Also handle manual clicks for instant feedback
        navLinks.forEach(link => {
            link.addEventListener('click', function() {
                const targetId = this.getAttribute('href').substring(1);
                setActiveLink(targetId);
            });
        });
    });
</script>

<?php include 'includes/footer.php'; ?>