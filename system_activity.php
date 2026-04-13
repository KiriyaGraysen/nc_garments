<?php
require_once('config/database.php');

// SECURITY KICK-OUT: Only let authorized roles see the Audit Log!
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

$page_title = "System Activity | NC Garments";

// 1. Determine which tab the user is viewing (default to 'all')
$view = $_GET['view'] ?? 'all';
$where_sql = "";

// 2. Dynamically build the SQL WHERE clause based on the tab
switch ($view) {
    case 'inventory':
        $where_sql = "WHERE log.target_table IN ('raw_material', 'premade_product', 'project_breakdown')";
        break;
    case 'payment':
        $where_sql = "WHERE log.target_table = 'payment'";
        break;
    case 'security':
        $where_sql = "WHERE log.target_table = 'admin' OR log.action IN ('LOGIN', 'LOGOUT')";
        break;
    default:
        $where_sql = ""; // 'all' requires no filter
}

// 3. Fetch the logs with the dynamic filter applied
$stmt = $conn->prepare("
    SELECT 
        log.log_id, log.action, log.target_table, log.description, log.created_at,
        adm.full_name, adm.role
    FROM activity_log log
    LEFT JOIN admin adm ON log.admin_id = adm.admin_id
    $where_sql
    ORDER BY log.created_at DESC
    LIMIT 100 
");
$stmt->execute();
$log_result = $stmt->get_result();

include 'includes/header.php'; 
?>

<main class="flex-1 p-4 md:p-8 overflow-y-auto transition-colors duration-500 font-sans">
    
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white transition-colors duration-500">History & Changes</h2>
            <p class="text-gray-500 dark:text-zinc-400 text-sm mt-1 transition-colors duration-500">System audit log tracking all user activities, inventory adjustments, and security events.</p>
        </div>
        <button class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 hover:bg-gray-50 dark:hover:bg-zinc-800 text-gray-700 dark:text-zinc-300 px-5 py-2.5 rounded-xl text-sm font-bold transition-all duration-300 shadow-sm flex items-center gap-2 cursor-pointer focus:outline-none focus:ring-2 focus:ring-pink-500">
            <i class="fa-solid fa-file-export text-pink-600 dark:text-pink-500"></i> Export Activity Log
        </button>
    </div>

    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
        <div class="relative w-full md:w-96 group">
            <i class="fa-solid fa-search absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400 group-focus-within:text-pink-600 transition-colors duration-500"></i>
            <input type="text" placeholder="Search logs by user, action, or ID..." 
                   class="w-full pl-11 pr-4 py-3 border border-gray-200 dark:border-zinc-800 rounded-xl bg-white dark:bg-zinc-900/50 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-zinc-500 focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent transition-colors duration-500 shadow-sm">
        </div>
        
        <?php
            // Setup dynamic classes for the active vs inactive tabs
            $active_tab = "bg-white dark:bg-zinc-800 text-pink-600 dark:text-pink-500 shadow-sm";
            $inactive_tab = "text-gray-500 dark:text-zinc-400 hover:text-gray-900 hover:dark:text-white";
        ?>
        
        <div class="flex bg-gray-100 dark:bg-zinc-900/80 p-1 rounded-lg w-full md:w-auto overflow-x-auto transition-colors duration-500 border border-gray-200 dark:border-zinc-800">
            <a href="?view=all" class="whitespace-nowrap px-5 py-2 text-sm font-bold rounded-md transition-colors duration-500 flex items-center gap-2 <?= $view === 'all' ? $active_tab : $inactive_tab ?>">
                <i class="fa-solid fa-list-ul text-xs"></i> All Activity
            </a>
            <a href="?view=inventory" class="whitespace-nowrap px-5 py-2 text-sm font-semibold rounded-md transition-colors duration-500 flex items-center gap-2 <?= $view === 'inventory' ? $active_tab : $inactive_tab ?>">
                <i class="fa-solid fa-boxes-stacked text-xs"></i> Inventory
            </a>
            <a href="?view=payment" class="whitespace-nowrap px-5 py-2 text-sm font-semibold rounded-md transition-colors duration-500 flex items-center gap-2 <?= $view === 'payment' ? $active_tab : $inactive_tab ?>">
                <i class="fa-solid fa-money-bill-wave text-xs"></i> Payments
            </a>
            <a href="?view=security" class="whitespace-nowrap px-5 py-2 text-sm font-semibold rounded-md transition-colors duration-500 flex items-center gap-2 <?= $view === 'security' ? $active_tab : $inactive_tab ?>">
                <i class="fa-solid fa-shield-halved text-xs"></i> Security
            </a>
        </div>
    </div>

    <div class="bg-white dark:bg-zinc-900 rounded-2xl shadow-sm border border-gray-100 dark:border-zinc-800 overflow-hidden transition-colors duration-500">
        <div class="overflow-x-auto">
            <table class="w-full whitespace-nowrap">
                
                <thead class="bg-gray-50 dark:bg-zinc-950/50 border-b border-gray-100 dark:border-zinc-800 transition-colors duration-500">
                    <tr>
                        <th class="px-6 py-4 text-left text-[10px] font-extrabold text-gray-500 dark:text-zinc-500 uppercase tracking-widest">Date & Time</th>
                        <th class="px-6 py-4 text-left text-[10px] font-extrabold text-gray-500 dark:text-zinc-500 uppercase tracking-widest">User / Actor</th>
                        <th class="px-6 py-4 text-left text-[10px] font-extrabold text-gray-500 dark:text-zinc-500 uppercase tracking-widest">Action Performed</th>
                        <th class="px-6 py-4 text-left text-[10px] font-extrabold text-gray-500 dark:text-zinc-500 uppercase tracking-widest">Details & Changes</th>
                    </tr>
                </thead>
                
                <tbody class="divide-y divide-gray-50 dark:divide-zinc-800/50 text-sm transition-colors duration-500">
                    
                    <?php
                    if ($log_result->num_rows === 0) {
                        echo '<tr><td colspan="4" class="px-6 py-8 text-center text-gray-500">No activity logs found for this category.</td></tr>';
                    }

                    while ($log = $log_result->fetch_assoc()) {
                        
                        // 1. Format the Date and Time
                        $log_date = new DateTime($log['created_at']);
                        $time_str = $log_date->format('g:i A');
                        $date_str = $log_date->format('F d, Y');
                        
                        // 2. Format the User Information
                        if ($log['full_name'] === null) {
                            $user_name = "System Automator";
                            $user_role = "Internal";
                            $user_initials = '<i class="fa-solid fa-robot text-[10px]"></i>';
                            $avatar_class = "bg-gray-800 text-white";
                        } else {
                            $user_name = htmlspecialchars($log['full_name']);
                            $user_role = htmlspecialchars($log['role']);
                            $user_initials = strtoupper(substr($user_name, 0, 2));
                            $avatar_class = ($user_role === 'Superadmin' || $user_role === 'admin') ? "bg-pink-600 text-white shadow-md shadow-pink-600/20" : "bg-gray-100 dark:bg-zinc-800 text-gray-600 dark:text-zinc-400 border border-gray-200 dark:border-zinc-700";
                        }

                        // 3. Format the Action Badge colors dynamically
                        $action = strtoupper($log['action']);
                        $badge_class = "bg-gray-50 text-gray-600 border-gray-200"; // Default
                        $icon = "fa-circle-info";

                        if ($action === 'CREATE') {
                            $badge_class = "bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400 border-emerald-200 dark:border-emerald-500/20";
                            $icon = "fa-plus";
                        } elseif ($action === 'UPDATE') {
                            $badge_class = "bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-400 border-amber-200 dark:border-amber-500/20";
                            $icon = "fa-pen";
                        } elseif ($action === 'DELETE' || $action === 'ARCHIVE') {
                            $badge_class = "bg-rose-50 text-rose-600 dark:bg-rose-500/10 dark:text-rose-400 border-rose-200 dark:border-rose-500/20";
                            $icon = "fa-trash";
                        } elseif ($action === 'LOGIN' || $action === 'LOGOUT') {
                            $badge_class = "bg-blue-50 text-blue-600 dark:bg-blue-500/10 dark:text-blue-400 border-blue-200 dark:blue-500/20";
                            $icon = "fa-shield-halved";
                        }

                        // Render the row!
                        echo '
                        <tr class="hover:bg-gray-50/80 dark:hover:bg-zinc-800/30 transition-colors group">
                            <td class="px-6 py-4">
                                <div class="text-gray-900 dark:text-white font-bold text-xs">'.$time_str.'</div>
                                <div class="text-[10px] font-bold text-gray-400 dark:text-zinc-500 mt-1 uppercase tracking-wider">'.$date_str.'</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="h-8 w-8 rounded-full flex items-center justify-center font-extrabold text-xs '.$avatar_class.'">
                                        '.$user_initials.'
                                    </div>
                                    <div>
                                        <div class="font-bold text-gray-900 dark:text-white">'.$user_name.'</div>
                                        <div class="text-[10px] font-bold tracking-wider text-gray-400 dark:text-zinc-500 mt-0.5 uppercase">'.$user_role.'</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="'.$badge_class.' text-[10px] font-extrabold px-2.5 py-1 rounded-md uppercase tracking-wider border flex items-center w-max gap-1.5">
                                    <i class="fa-solid '.$icon.' text-[10px]"></i> '.$action.'
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-bold text-gray-900 dark:text-white text-xs uppercase tracking-widest text-gray-500 dark:text-zinc-400 mb-1">Module: '.htmlspecialchars($log['target_table']).'</div>
                                <div class="text-[11px] font-semibold text-gray-700 dark:text-zinc-300 whitespace-normal max-w-md">
                                    '.htmlspecialchars($log['description']).'
                                </div>
                            </td>
                        </tr>';
                    }
                    ?>

                </tbody>
            </table>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>