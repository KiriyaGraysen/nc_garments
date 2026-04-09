</div> <script>
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
        
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebar-overlay');
            
            // Toggle the -translate-x-full class to slide the sidebar in and out
            sidebar.classList.toggle('-translate-x-full');
            
            // Toggle the hidden class to show/hide the dark overlay background
            overlay.classList.toggle('hidden');
        }
    </script>
</body>
</html>