<?php
// connects all page to the database
require_once('config/database.php');

if (empty($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit();
}

$stmt = $conn->prepare("
    SELECT full_name, username, role
    FROM admin
    WHERE admin_id = ?
");
$stmt->bind_param("s", $_SESSION['admin_id']);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();

$stmt->close();

function generate_initials($name) {
    // 1. Remove any accidental spaces at the start or end
    $name = trim($name);
    
    // Fallback if the name is somehow empty
    if (empty($name)) {
        return "U"; // 'U' for User
    }

    // 2. Split the name into an array of words
    $words = explode(" ", $name);
    $word_count = count($words);

    if ($word_count >= 2) {
        // CONDITION A: 2 or more words (e.g., "Sherwin Samonte" -> "SS")
        // Grab the first letter of the first word
        $first_letter = substr($words[0], 0, 1);
        // Grab the first letter of the very last word (ignores middle names)
        $last_letter = substr(end($words), 0, 1);
        
        $initials = $first_letter . $last_letter;
    } else {
        // CONDITION B: Only 1 word (e.g., "Admin" -> "AD")
        // Grab the first two letters of that single word
        $initials = substr($name, 0, 2);
    }

    // 3. Return it in uppercase so it always looks good in the avatar
    return strtoupper($initials);
}

$admin_initials = generate_initials(htmlspecialchars($admin['full_name']));

// Get the current filename (e.g., 'index.php', 'inventory.php') to highlight the active menu link
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en" id="html-root" class="transition-colors duration-500">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : 'NC Garments System'; ?></title>
    <link rel="icon" href="../assets/images/icon.png">
    
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style type="text/tailwindcss">
        @theme {
            --font-sans: 'Poppins', ui-sans-serif, system-ui, sans-serif;
        }
        @custom-variant dark (&:where(.dark, .dark *));
    </style>

    <style>
        /* 1. HIDE NUMBER INPUT SPINNERS GLOBALLY */
        input::-webkit-outer-spin-button,
        input::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        input[type=number] {
            -moz-appearance: textfield;
        }

        /* 2. FIX CALENDAR ICON IN DARK MODE */
        .dark input[type="date"]::-webkit-calendar-picker-indicator {
            filter: invert(1) brightness(100%);
            cursor: pointer;
        }
    </style>

    <script>
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
</head>
<body class="flex h-screen bg-gray-50 dark:bg-zinc-950 font-sans overflow-hidden transition-colors duration-500 antialiased">
    
    <div id="sidebar-overlay" class="fixed inset-0 bg-black/50 z-40 hidden md:hidden transition-opacity backdrop-blur-sm" onclick="toggleSidebar()"></div>

    <aside id="sidebar" class="fixed inset-y-0 left-0 z-50 w-72 bg-zinc-900 dark:bg-black text-white flex flex-col shrink-0 transition-transform duration-300 ease-in-out -translate-x-full md:relative md:translate-x-0 border-r border-transparent dark:border-zinc-800">
        
        <div class="h-16 flex items-center justify-between px-6 shrink-0 border-b border-zinc-800 dark:border-zinc-900 relative overflow-hidden">
            <div class="absolute left-0 top-0 w-32 h-32 bg-pink-600/10 rounded-full blur-2xl pointer-events-none"></div>
            
            <div class="flex items-baseline gap-2 relative z-10">
                <span class="text-pink-600 font-serif italic text-3xl leading-none">NC</span>
                <span class="text-lg font-extrabold tracking-[0.15em] uppercase">Garments</span>
            </div>

            <button onclick="toggleSidebar()" class="md:hidden text-zinc-400 hover:text-white focus:outline-none relative z-10">
                <i class="fa-solid fa-xmark text-xl"></i>
            </button>
        </div>
        
        <nav class="flex-1 px-4 py-6 space-y-1.5 overflow-y-auto">
            
            <p class="px-3 text-[10px] font-black text-zinc-500 uppercase tracking-widest mb-2 mt-2">Main Menu</p>
            
            <a href="index.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-200 group <?php echo ($current_page == 'index.php') ? 'bg-pink-600 text-white shadow-md shadow-pink-600/20' : 'text-zinc-400 hover:bg-zinc-800 hover:text-white'; ?>">
                <i class="fa-solid fa-house w-5 text-center transition-colors <?php echo ($current_page == 'index.php') ? 'text-white' : 'text-zinc-500 group-hover:text-pink-400'; ?>"></i>
                <span class="font-bold text-sm tracking-wide">Dashboard</span>
            </a>

            <a href="pos.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-200 group <?php echo ($current_page == 'pos.php') ? 'bg-pink-600 text-white shadow-md shadow-pink-600/20' : 'text-zinc-400 hover:bg-zinc-800 hover:text-white'; ?>">
                <i class="fa-solid fa-cash-register w-5 text-center transition-colors <?php echo ($current_page == 'pos.php') ? 'text-white' : 'text-zinc-500 group-hover:text-pink-400'; ?>"></i>
                <span class="font-bold text-sm tracking-wide">Point of Sale</span>
            </a>

            <a href="projects.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-200 group <?php echo ($current_page == 'projects.php') ? 'bg-pink-600 text-white shadow-md shadow-pink-600/20' : 'text-zinc-400 hover:bg-zinc-800 hover:text-white'; ?>">
                <i class="fa-solid fa-scissors w-5 text-center transition-colors <?php echo ($current_page == 'projects.php') ? 'text-white' : 'text-zinc-500 group-hover:text-pink-400'; ?>"></i>
                <span class="font-bold text-sm tracking-wide">Orders & Projects</span>
            </a>

            <a href="inventory.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-200 group <?php echo ($current_page == 'inventory.php') ? 'bg-pink-600 text-white shadow-md shadow-pink-600/20' : 'text-zinc-400 hover:bg-zinc-800 hover:text-white'; ?>">
                <i class="fa-solid fa-boxes-stacked w-5 text-center transition-colors <?php echo ($current_page == 'inventory.php') ? 'text-white' : 'text-zinc-500 group-hover:text-pink-400'; ?>"></i>
                <span class="font-bold text-sm tracking-wide">Inventory</span>
            </a>

            <a href="customers.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-200 group <?php echo ($current_page == 'customers.php') ? 'bg-pink-600 text-white shadow-md shadow-pink-600/20' : 'text-zinc-400 hover:bg-zinc-800 hover:text-white'; ?>">
                <i class="fa-solid fa-users w-5 text-center transition-colors <?php echo ($current_page == 'customers.php') ? 'text-white' : 'text-zinc-500 group-hover:text-pink-400'; ?>"></i>
                <span class="font-bold text-sm tracking-wide">Customers & Payments</span>
            </a>
            
            <p class="px-3 text-[10px] font-black text-zinc-500 uppercase tracking-widest mb-2 mt-6">Analytics</p>

            <a href="reports.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-200 group <?php echo ($current_page == 'reports.php') ? 'bg-pink-600 text-white shadow-md shadow-pink-600/20' : 'text-zinc-400 hover:bg-zinc-800 hover:text-white'; ?>">
                <i class="fa-solid fa-chart-pie w-5 text-center transition-colors <?php echo ($current_page == 'reports.php') ? 'text-white' : 'text-zinc-500 group-hover:text-pink-400'; ?>"></i>
                <span class="font-bold text-sm tracking-wide">Financial Reports</span>
            </a>

            <p class="px-3 text-[10px] font-black text-zinc-500 uppercase tracking-widest mb-2 mt-6">Administration</p>

            <a href="staff.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-200 group <?php echo ($current_page == 'staff.php') ? 'bg-pink-600 text-white shadow-md shadow-pink-600/20' : 'text-zinc-400 hover:bg-zinc-800 hover:text-white'; ?>">
                <i class="fa-solid fa-shield-halved w-5 text-center transition-colors <?php echo ($current_page == 'staff.php') ? 'text-white' : 'text-zinc-500 group-hover:text-pink-400'; ?>"></i>
                <span class="font-bold text-sm tracking-wide">Staff Access</span>
            </a>
            
            <a href="system_activity.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-200 group <?php echo ($current_page == 'system_activity.php') ? 'bg-pink-600 text-white shadow-md shadow-pink-600/20' : 'text-zinc-400 hover:bg-zinc-800 hover:text-white'; ?>">
                <i class="fa-solid fa-clock-rotate-left w-5 text-center transition-colors <?php echo ($current_page == 'system_activity.php') ? 'text-white' : 'text-zinc-500 group-hover:text-pink-400'; ?>"></i>
                <span class="font-bold text-sm tracking-wide">System Activity</span>
            </a>
            
            <a href="backup.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-200 group <?php echo ($current_page == 'backup.php') ? 'bg-pink-600 text-white shadow-md shadow-pink-600/20' : 'text-zinc-400 hover:bg-zinc-800 hover:text-white'; ?>">
                <i class="fa-solid fa-database w-5 text-center transition-colors <?php echo ($current_page == 'backup.php') ? 'text-white' : 'text-zinc-500 group-hover:text-pink-400'; ?>"></i>
                <span class="font-bold text-sm tracking-wide">Backup</span>
            </a>
            
        </nav>
        
        <div class="p-4 mt-auto shrink-0 border-t border-zinc-800 dark:border-zinc-900">
            <a href="settings.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-200 group <?php echo ($current_page == 'settings.php') ? 'bg-pink-600 text-white shadow-md shadow-pink-600/20' : 'text-zinc-400 hover:bg-zinc-800 hover:text-white'; ?>">
                <i class="fa-solid fa-gear w-5 text-center transition-colors <?php echo ($current_page == 'settings.php') ? 'text-white' : 'text-zinc-500 group-hover:text-pink-400'; ?>"></i>
                <span class="font-bold text-sm tracking-wide">Settings</span>
            </a>
        </div>
    </aside>

    <div class="flex-1 flex flex-col overflow-hidden bg-gray-50 dark:bg-zinc-950 transition-colors duration-500">
        
        <header class="h-16 bg-white dark:bg-zinc-900 border-b border-gray-100 dark:border-zinc-800 flex items-center justify-between px-4 md:px-8 shrink-0 transition-colors duration-500 z-10 shadow-sm shadow-gray-100/50 dark:shadow-none">
            
            <div class="flex items-center flex-1 max-w-lg">
                <button onclick="toggleSidebar()" class="md:hidden mr-4 text-gray-500 hover:text-pink-600 focus:outline-none transition-colors">
                    <i class="fa-solid fa-bars text-xl"></i>
                </button>

                <div class="relative group flex-1 hidden sm:block">
                    <i class="fa-solid fa-search absolute left-0 top-1/2 transform -translate-y-1/2 text-gray-400 group-focus-within:text-pink-600 transition-colors"></i>
                    <input type="text" placeholder="Search orders, customers..." 
                           class="w-full pl-8 pr-4 py-2 bg-transparent border-none text-sm font-medium text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-zinc-500 focus:outline-none focus:ring-0 transition-colors">
                </div>
            </div>

            <div class="flex items-center gap-4 md:gap-6">
                
                <div class="hidden lg:flex flex-col text-right justify-center">
                    <span id="live-time" class="text-sm font-extrabold text-gray-900 dark:text-white tracking-widest leading-none mb-0.5">
                        --:--:--
                    </span>
                    <span id="live-date" class="text-[9px] font-bold text-pink-600 dark:text-pink-500 uppercase tracking-widest leading-none">
                        ----, --- --, ----
                    </span>
                </div>
                
                <div class="hidden lg:block h-6 w-px bg-gray-200 dark:bg-zinc-800 mx-1"></div>

                <button id="theme-toggle" class="text-gray-400 hover:text-pink-600 transition-colors cursor-pointer focus:outline-none" title="Toggle Dark Mode">
                    <i id="theme-icon" class="fa-solid fa-moon text-lg"></i>
                </button>
                
                <button class="relative text-gray-400 hover:text-pink-600 transition-colors cursor-pointer focus:outline-none">
                    <i class="fa-regular fa-bell text-xl"></i>
                    <span class="absolute top-0 right-0 block h-2 w-2 rounded-full bg-pink-600 ring-2 ring-white dark:ring-zinc-900"></span>
                </button>

                <div class="hidden md:block h-6 w-px bg-gray-200 dark:bg-zinc-800"></div>

                <div class="relative">
                    <button onclick="toggleUserMenu()" id="user-menu-btn" class="flex items-center gap-3 hover:opacity-80 transition-opacity cursor-pointer focus:outline-none">
                        <div class="h-8 w-8 rounded-full bg-pink-600 flex items-center justify-center text-white font-bold text-xs shadow-md shadow-pink-600/20"><?php echo $admin_initials; ?></div>
                        <div class="hidden md:flex flex-col items-start leading-tight">
                            <span class="text-sm font-bold text-gray-900 dark:text-white"><?php echo htmlspecialchars($admin['full_name']); ?></span>
                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest"><?php echo htmlspecialchars($admin['role']); ?></span>
                        </div>
                        <i class="fa-solid fa-chevron-down text-gray-400 text-xs hidden md:block ml-1 transition-transform duration-300" id="user-menu-arrow"></i>
                    </button>
                
                    <div id="user-dropdown" class="hidden absolute right-0 top-full mt-3 w-56 bg-white dark:bg-zinc-900 border border-gray-100 dark:border-zinc-800 rounded-xl shadow-xl z-50 overflow-hidden transition-all origin-top-right">
                        
                        <div class="px-4 py-3 border-b border-gray-50 dark:border-zinc-800/50">
                            <p class="text-xs font-bold text-gray-500 dark:text-zinc-400 uppercase tracking-wider">Signed in as</p>
                            <p class="text-sm font-bold text-gray-900 dark:text-white truncate mt-0.5"><?php echo htmlspecialchars($admin['username']); ?></p>
                        </div>
                
                        <div class="py-1">
                            <a href="profile.php" class="flex items-center gap-3 px-4 py-2.5 text-sm font-semibold text-gray-700 dark:text-zinc-300 hover:bg-gray-50 dark:hover:bg-zinc-800 hover:text-pink-600 dark:hover:text-pink-500 transition-colors">
                                <i class="fa-regular fa-id-badge w-4 text-center"></i> My Profile
                            </a>
                            <a href="history.php?user=me" class="flex items-center gap-3 px-4 py-2.5 text-sm font-semibold text-gray-700 dark:text-zinc-300 hover:bg-gray-50 dark:hover:bg-zinc-800 hover:text-pink-600 dark:hover:text-pink-500 transition-colors">
                                <i class="fa-solid fa-clock-rotate-left w-4 text-center"></i> My Activity Log
                            </a>
                            <a href="manual.pdf" target="_blank" class="flex items-center gap-3 px-4 py-2.5 text-sm font-semibold text-gray-700 dark:text-zinc-300 hover:bg-gray-50 dark:hover:bg-zinc-800 hover:text-pink-600 dark:hover:text-pink-500 transition-colors">
                                <i class="fa-regular fa-circle-question w-4 text-center"></i> User Manual
                            </a>
                        </div>
                
                        <div class="py-1 border-t border-gray-50 dark:border-zinc-800/50 bg-gray-50/50 dark:bg-zinc-950/50">
                            <a href="../actions/logout.php" class="flex items-center gap-3 px-4 py-2.5 text-sm font-bold text-rose-600 dark:text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-900/20 transition-colors">
                                <i class="fa-solid fa-right-from-bracket w-4 text-center"></i> Secure Logout
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <script>
            function updateLiveClock() {
                const now = new Date();
                
                // Format Time (e.g., 02:45:12 PM)
                let hours = now.getHours();
                let minutes = now.getMinutes();
                let seconds = now.getSeconds();
                let ampm = hours >= 12 ? 'PM' : 'AM';
                
                hours = hours % 12;
                hours = hours ? hours : 12; // the hour '0' should be '12'
                
                // Add leading zeros
                hours = hours < 10 ? '0' + hours : hours;
                minutes = minutes < 10 ? '0' + minutes : minutes;
                seconds = seconds < 10 ? '0' + seconds : seconds;
                
                const timeString = hours + ':' + minutes + ':' + seconds + ' ' + ampm;
                
                // Format Date (e.g., Friday, Apr 17, 2026)
                const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                
                const dayName = days[now.getDay()];
                const monthName = months[now.getMonth()];
                const dateNum = now.getDate();
                const year = now.getFullYear();
                
                const dateString = dayName + ', ' + monthName + ' ' + dateNum + ', ' + year;

                // Update the HTML
                const timeEl = document.getElementById('live-time');
                const dateEl = document.getElementById('live-date');
                
                if (timeEl && dateEl) {
                    timeEl.textContent = timeString;
                    dateEl.textContent = dateString;
                }
            }

            // Run immediately, then update every second
            updateLiveClock();
            setInterval(updateLiveClock, 1000);
        </script>