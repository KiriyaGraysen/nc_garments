<?php
// connects all page to the database
require_once('config/database.php');

if (empty($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit();
}

// 🚨 Added 'email' to your SELECT just in case you use it later!
$stmt = $conn->prepare("
    SELECT full_name, email, role
    FROM admin
    WHERE admin_id = ?
");
$stmt->bind_param("s", $_SESSION['admin_id']);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();

$stmt->close();

// ==========================================
// 🚨 FETCH LOW STOCK NOTIFICATIONS (UNIFIED)
// ==========================================
$low_stock_alerts = [];
$notif_query = $conn->query("
    SELECT material_name as name, current_stock as stock, min_stock_alert as alert, unit_of_measure as metric, 'raw_material' as type 
    FROM raw_material 
    WHERE current_stock <= min_stock_alert AND is_archived = 0
    
    UNION ALL
    
    SELECT product_name as name, current_stock as stock, min_stock_alert as alert, size as metric, 'premade_product' as type 
    FROM premade_product 
    WHERE current_stock <= min_stock_alert AND is_archived = 0
    
    ORDER BY stock ASC
");

if ($notif_query) {
    while($row = $notif_query->fetch_assoc()) {
        $low_stock_alerts[] = $row;
    }
}
$notif_count = count($low_stock_alerts);

function generate_initials($name) {
    $name = trim($name);
    if (empty($name)) return "U"; 
    $words = explode(" ", $name);
    $word_count = count($words);

    if ($word_count >= 2) {
        $first_letter = substr($words[0], 0, 1);
        $last_letter = substr(end($words), 0, 1);
        $initials = $first_letter . $last_letter;
    } else {
        $initials = substr($name, 0, 2);
    }
    return strtoupper($initials);
}

$admin_initials = generate_initials(htmlspecialchars($admin['full_name']));
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
        input::-webkit-outer-spin-button,
        input::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        input[type=number] {
            -moz-appearance: textfield;
        }
        .dark input[type="date"]::-webkit-calendar-picker-indicator {
            filter: invert(1) brightness(100%);
            cursor: pointer;
        }
        * {
            scrollbar-width: thin;
            scrollbar-color: #d1d5db transparent;
        }
        .dark * {
            scrollbar-color: #3f3f46 transparent;
        }
        ::-webkit-scrollbar {
            width: 8px; 
            height: 8px; 
        }
        ::-webkit-scrollbar-track {
            background: transparent; 
        }
        ::-webkit-scrollbar-thumb {
            background-color: #d1d5db; 
            border-radius: 20px;
            border: 2px solid transparent;
            background-clip: content-box;
        }
        ::-webkit-scrollbar-thumb:hover {
            background-color: #db2777; 
        }
        .dark ::-webkit-scrollbar-thumb {
            background-color: #3f3f46; 
        }
        .dark ::-webkit-scrollbar-thumb:hover {
            background-color: #db2777; 
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
<body class="flex h-[100dvh] bg-gray-50 dark:bg-zinc-950 font-sans overflow-hidden transition-colors duration-500 antialiased">
    
    <div id="sidebar-overlay" class="fixed inset-0 bg-black/50 z-40 hidden md:hidden transition-opacity backdrop-blur-sm" onclick="toggleSidebar()"></div>

    <aside id="sidebar" class="fixed top-0 left-0 h-[100dvh] z-50 w-72 bg-zinc-900 dark:bg-black text-white flex flex-col shrink-0 transition-transform duration-300 ease-in-out -translate-x-full md:relative md:translate-x-0 border-r border-transparent dark:border-zinc-800">
        
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
        
        </aside>

    <div class="flex-1 flex flex-col overflow-hidden bg-gray-50 dark:bg-zinc-950 transition-colors duration-500">
        
        <header class="h-16 bg-white dark:bg-zinc-900 border-b border-gray-100 dark:border-zinc-800 flex items-center justify-between px-4 md:px-8 shrink-0 transition-colors duration-500 z-10 shadow-sm shadow-gray-100/50 dark:shadow-none">
            
            <div class="flex items-center flex-1 max-w-lg relative">
                <button onclick="toggleSidebar()" class="md:hidden mr-4 text-gray-500 hover:text-pink-600 focus:outline-none transition-colors">
                    <i class="fa-solid fa-bars text-xl"></i>
                </button>

                <div class="relative group flex-1 hidden sm:block">
                    <i class="fa-solid fa-search absolute left-0 top-1/2 transform -translate-y-1/2 text-gray-400 group-focus-within:text-pink-600 transition-colors"></i>
                    <input type="text" placeholder="Search orders, customers, inventory..." autocomplete="off"
                           class="w-full pl-8 pr-4 py-2 bg-transparent border-none text-sm font-medium text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-zinc-500 focus:outline-none focus:ring-0 transition-colors">
                </div>
            </div>

            <div class="flex items-center gap-4 md:gap-6">
                
                <div class="hidden lg:flex flex-col text-right justify-center">
                    <span id="live-time" class="text-sm font-extrabold text-gray-900 dark:text-white tracking-widest leading-none mb-0.5">--:--:--</span>
                    <span id="live-date" class="text-[9px] font-bold text-pink-600 dark:text-pink-500 uppercase tracking-widest leading-none">----, --- --, ----</span>
                </div>
                
                <div class="hidden lg:block h-6 w-px bg-gray-200 dark:bg-zinc-800 mx-1"></div>

                <button id="theme-toggle" class="text-gray-400 hover:text-pink-600 transition-colors cursor-pointer focus:outline-none" title="Toggle Dark Mode">
                    <i id="theme-icon" class="fa-solid fa-moon text-lg"></i>
                </button>
                
                <div class="relative">
                    <button onclick="toggleNotifications()" class="relative text-gray-400 hover:text-pink-600 transition-colors cursor-pointer focus:outline-none">
                        <i class="fa-regular fa-bell text-xl"></i>
                        <?php if ($notif_count > 0): ?>
                            <span class="absolute -top-1 -right-1 flex h-4 min-w-[16px] items-center justify-center rounded-full bg-pink-600 px-1 text-[9px] font-bold text-white ring-2 ring-white dark:ring-zinc-900">
                                <?= $notif_count > 9 ? '9+' : $notif_count ?>
                            </span>
                        <?php endif; ?>
                    </button>

                    <div id="notif-dropdown" class="hidden absolute right-0 top-full mt-3 w-80 bg-white dark:bg-zinc-900 border border-gray-100 dark:border-zinc-800 rounded-2xl shadow-xl z-50 overflow-hidden origin-top-right transition-all">
                        
                        <div class="px-4 py-3 border-b border-gray-50 dark:border-zinc-800/50 flex justify-between items-center bg-gray-50/50 dark:bg-zinc-950/30">
                            <span class="text-xs font-black text-gray-500 dark:text-zinc-400 uppercase tracking-widest">Alerts & Stock</span>
                            <?php if ($notif_count > 0): ?>
                                <span class="text-[10px] font-bold bg-pink-50 dark:bg-pink-500/10 text-pink-600 px-2 py-0.5 rounded-md"><?= $notif_count ?> Low Items</span>
                            <?php endif; ?>
                        </div>

                        <div class="max-h-80 overflow-y-auto">
                            <?php if ($notif_count > 0): ?>
                                <?php foreach ($low_stock_alerts as $alert): ?>
                                    <div class="px-4 py-3 hover:bg-gray-50 dark:hover:bg-zinc-800/50 border-b border-gray-50 dark:border-zinc-800/30 transition-colors">
                                        <div class="flex items-center gap-3">
                                            <div class="h-8 w-8 rounded-full bg-rose-100 dark:bg-rose-500/10 flex items-center justify-center shrink-0">
                                                <i class="fa-solid fa-triangle-exclamation text-rose-600 text-xs"></i>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <div class="flex justify-between items-center mb-0.5">
                                                    <p class="text-xs font-bold text-gray-900 dark:text-white truncate pr-2"><?= htmlspecialchars($alert['name']) ?></p>
                                                    <?php if($alert['type'] === 'raw_material'): ?>
                                                        <span class="text-[8px] font-extrabold uppercase px-1.5 py-0.5 rounded bg-purple-50 text-purple-600 border border-purple-200 dark:bg-purple-900/20 dark:text-purple-400 dark:border-purple-800">Mat</span>
                                                    <?php else: ?>
                                                        <span class="text-[8px] font-extrabold uppercase px-1.5 py-0.5 rounded bg-blue-50 text-blue-600 border border-blue-200 dark:bg-blue-900/20 dark:text-blue-400 dark:border-blue-800">Prod</span>
                                                    <?php endif; ?>
                                                </div>
                                                <p class="text-[10px] font-medium text-rose-500">
                                                    Stock: <?= $alert['stock'] ?> <?= $alert['metric'] ?> 
                                                    <span class="text-gray-400 dark:text-zinc-600 ml-1">(Limit: <?= $alert['alert'] ?>)</span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="px-6 py-10 text-center">
                                    <i class="fa-solid fa-check-circle text-emerald-500 text-3xl mb-3 opacity-20"></i>
                                    <p class="text-xs font-bold text-gray-500 uppercase tracking-widest">All Stock Sufficient</p>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($notif_count > 0): ?>
                        <div class="border-t border-gray-100 dark:border-zinc-800">
                            <a href="inventory.php?view=alerts" class="block w-full py-3 text-center text-[10px] font-black text-pink-600 uppercase tracking-widest hover:bg-pink-50 dark:hover:bg-pink-500/5 transition-colors bg-gray-50/50 dark:bg-zinc-950/30">
                                View All In Alerts Tab
                            </a>
                        </div>
                        <?php endif; ?>
                        
                    </div>
                </div>

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
                            <p class="text-sm font-bold text-gray-900 dark:text-white truncate mt-0.5"><?php echo htmlspecialchars($admin['email']); ?></p>
                        </div>
                
                        <div class="py-1">
                            <a href="settings.php" class="flex items-center gap-3 px-4 py-2.5 text-sm font-semibold text-gray-700 dark:text-zinc-300 hover:bg-gray-50 dark:hover:bg-zinc-800 hover:text-pink-600 dark:hover:text-pink-500 transition-colors">
                                <i class="fa-solid fa-gear w-4 text-center"></i> Settings
                            </a>
                            <button onclick="openManualModal()" class="w-full flex items-center gap-3 px-4 py-2.5 text-sm font-semibold text-gray-700 dark:text-zinc-300 hover:bg-gray-50 dark:hover:bg-zinc-800 hover:text-pink-600 dark:hover:text-pink-500 transition-colors text-left focus:outline-none">
                                <i class="fa-solid fa-circle-question w-4 text-center"></i> User Manual
                            </button>
                        </div>
                
                        <div class="py-1 border-t border-gray-50 dark:border-zinc-800/50 bg-gray-50/50 dark:bg-zinc-950/50">
                            <a href="actions/logout.php" class="flex items-center gap-3 px-4 py-2.5 text-sm font-bold text-rose-600 dark:text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-900/20 transition-colors">
                                <i class="fa-solid fa-right-from-bracket w-4 text-center"></i> Secure Logout
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <div id="manual-modal" class="fixed inset-0 z-[100] hidden flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/60 backdrop-blur-sm transition-opacity" onclick="closeManualModal()"></div>
            <div class="relative bg-white dark:bg-zinc-900 rounded-2xl w-full max-w-5xl shadow-2xl overflow-hidden flex flex-col border border-gray-100 dark:border-zinc-800">
                
                <div class="px-6 py-4 border-b border-gray-100 dark:border-zinc-800 flex justify-between items-center bg-gray-50/50 dark:bg-zinc-950/30">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white"><i class="fa-solid fa-book-open text-pink-600 mr-2"></i> System User Manual</h3>
                    <div class="flex gap-3">
                        <a href="assets/docs/manual.pdf" target="_blank" class="text-xs font-bold text-pink-600 hover:text-pink-700 bg-pink-50 hover:bg-pink-100 dark:bg-pink-500/10 dark:hover:bg-pink-500/20 px-3 py-1.5 rounded-lg transition-colors flex items-center gap-1.5">
                            <i class="fa-solid fa-arrow-up-right-from-square"></i> Open in New Tab
                        </a>
                        <button onclick="closeManualModal()" class="text-gray-400 hover:text-rose-500 transition-colors focus:outline-none ml-2">
                            <i class="fa-solid fa-xmark text-xl"></i>
                        </button>
                    </div>
                </div>
                
                <div class="p-0 bg-gray-100 dark:bg-black h-[75vh] relative flex flex-col items-center justify-center">
                    
                    <iframe src="assets/docs/manual.pdf" class="hidden md:block w-full h-full border-0"></iframe>

                    <div class="md:hidden flex flex-col items-center justify-center text-center p-6 h-full w-full">
                        <div class="w-20 h-20 bg-pink-100 dark:bg-pink-900/30 text-pink-600 rounded-full flex items-center justify-center text-3xl mb-4 shadow-inner">
                            <i class="fa-solid fa-file-pdf"></i>
                        </div>
                        <h4 class="text-lg font-bold text-gray-900 dark:text-white mb-2">User Manual</h4>
                        <p class="text-sm text-gray-500 dark:text-zinc-400 mb-6 max-w-xs leading-relaxed">
                            Mobile browsers block PDFs from loading inside windows. Please open the manual directly to read it.
                        </p>
                        <a href="assets/docs/manual.pdf" target="_blank" class="bg-pink-600 hover:bg-pink-700 text-white px-6 py-3 rounded-xl text-sm font-bold shadow-md shadow-pink-600/20 flex items-center gap-2 transition-all">
                            <i class="fa-solid fa-book-open-reader"></i> Open Native Reader
                        </a>
                    </div>

                </div>

            </div>
        </div>

        <script>
            // 🚨 SCRIPT RE-WRITTEN WITH NO VARIABLE COLLISIONS
            function toggleNotifications() {
                const dropdown = document.getElementById('notif-dropdown');
                dropdown.classList.toggle('hidden');
                
                const userDropdown = document.getElementById('user-dropdown');
                const arrow = document.getElementById('user-menu-arrow');
                if (userDropdown) userDropdown.classList.add('hidden');
                if (arrow) arrow.classList.remove('rotate-180');
            }

            function toggleUserMenu() {
                const dropdown = document.getElementById('user-dropdown');
                const arrow = document.getElementById('user-menu-arrow');
                dropdown.classList.toggle('hidden');
                arrow.classList.toggle('rotate-180');
                
                const notifDropdown = document.getElementById('notif-dropdown');
                if (notifDropdown) notifDropdown.classList.add('hidden');
            }
            
            function openManualModal() {
                document.getElementById('manual-modal').classList.remove('hidden');
                // Auto-close the user menu when opening the modal
                document.getElementById('user-dropdown').classList.add('hidden');
                document.getElementById('user-menu-arrow').classList.remove('rotate-180');
            }
            
            function closeManualModal() {
                document.getElementById('manual-modal').classList.add('hidden');
            }

            window.addEventListener('click', function(e) {
                if (!e.target.closest('#user-menu-btn') && !e.target.closest('#user-dropdown')) {
                    const userDropdown = document.getElementById('user-dropdown');
                    const arrow = document.getElementById('user-menu-arrow');
                    if (userDropdown) userDropdown.classList.add('hidden');
                    if (arrow) arrow.classList.remove('rotate-180');
                }
                
                if (!e.target.closest('button[onclick="toggleNotifications()"]') && !e.target.closest('#notif-dropdown')) {
                    const notifDropdown = document.getElementById('notif-dropdown');
                    if (notifDropdown) notifDropdown.classList.add('hidden');
                }
            });

            function updateLiveClock() {
                const now = new Date();
                let hours = now.getHours();
                let minutes = now.getMinutes();
                let seconds = now.getSeconds();
                let ampm = hours >= 12 ? 'PM' : 'AM';
                
                hours = hours % 12;
                hours = hours ? hours : 12;
                
                hours = hours < 10 ? '0' + hours : hours;
                minutes = minutes < 10 ? '0' + minutes : minutes;
                seconds = seconds < 10 ? '0' + seconds : seconds;
                
                const timeString = hours + ':' + minutes + ':' + seconds + ' ' + ampm;
                const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                const dateString = days[now.getDay()] + ', ' + months[now.getMonth()] + ' ' + now.getDate() + ', ' + now.getFullYear();

                const timeEl = document.getElementById('live-time');
                const dateEl = document.getElementById('live-date');
                
                if (timeEl && dateEl) {
                    timeEl.textContent = timeString;
                    dateEl.textContent = dateString;
                }
            }

            updateLiveClock();
            setInterval(updateLiveClock, 1000);
        </script>