<?php
require_once('config/database.php');

// SECURITY KICK-OUT
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    header("Location: index.php");
    exit();
}

$page_title = "System Activity | NC Garments";

// 1. Determine which tab the user is viewing
$view = $_GET['view'] ?? 'all';
$where_sql = "";

switch ($view) {
    case 'inventory':
        $where_sql = "WHERE log.target_table IN ('raw_material', 'premade_product', 'project_breakdown', 'inventory_adjustments')";
        break;
    case 'payment':
        // Modified to show both Custom Project Payments and POS Retail Sales!
        $where_sql = "WHERE log.target_table IN ('payment', 'retail_sale')";
        break;
    case 'security':
        $where_sql = "WHERE log.target_table = 'admin' OR log.action IN ('LOGIN', 'LOGOUT')";
        break;
    default:
        $where_sql = ""; 
}

// 3. Fetch the logs
$stmt = $conn->prepare("
    SELECT 
        log.log_id, log.action, log.target_table, log.description, log.created_at,
        adm.full_name, adm.role
    FROM activity_log log
    LEFT JOIN admin adm ON log.admin_id = adm.admin_id
    $where_sql
    ORDER BY log.created_at DESC
    LIMIT 200 
");
$stmt->execute();
$log_result = $stmt->get_result();

// 4. Smart Log Formatter for old text logs
function format_log_description($text) {
    $clean_text = htmlspecialchars($text);
    $pattern = '/from\s+([0-9\.,]+)\s+to\s+([0-9\.,]+)/i';
    $replacement = '<span class="text-gray-400 dark:text-zinc-500 font-normal mx-1 lowercase">from</span><span class="text-rose-500 dark:text-rose-400 line-through font-black">$1</span><span class="text-gray-400 dark:text-zinc-500 font-normal mx-1 lowercase">to</span><span class="text-emerald-600 dark:text-emerald-500 font-black">$2</span>';
    return preg_replace($pattern, $replacement, $clean_text);
}

include 'includes/header.php'; 
?>

<main class="flex-1 p-4 md:p-8 overflow-y-auto transition-colors duration-500 font-sans relative">
    
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white transition-colors duration-500">History & Changes</h2>
            <p class="text-gray-500 dark:text-zinc-400 text-sm mt-1 transition-colors duration-500">System audit log tracking all user activities, inventory adjustments, and security events.</p>
        </div>
        <button onclick="exportLogs()" class="bg-pink-600 hover:bg-pink-700 text-white px-5 py-2.5 rounded-xl text-sm font-bold transition-all duration-300 shadow-lg shadow-pink-600/20 flex items-center gap-2 cursor-pointer focus:outline-none">
            <i class="fa-solid fa-file-export"></i> Export Activity Log
        </button>
    </div>

    <div class="flex flex-col lg:flex-row justify-between items-center mb-6 gap-4">
        <div class="flex w-full lg:w-auto gap-3 flex-1 max-w-2xl">
            <div class="relative w-full group">
                <i class="fa-solid fa-search absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400 group-focus-within:text-pink-600 transition-colors duration-500"></i>
                <input type="text" id="search-input" placeholder="Search logs by user, action, or details..." 
                       class="w-full pl-11 pr-4 py-3 border border-gray-200 dark:border-zinc-800 rounded-xl bg-white dark:bg-zinc-900/50 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-zinc-500 focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent transition-colors duration-500 shadow-sm text-sm font-medium">
            </div>
        </div>
        
        <?php
            $active_tab = "bg-white dark:bg-zinc-800 text-pink-600 dark:text-pink-500 shadow-sm";
            $inactive_tab = "text-gray-500 dark:text-zinc-400 hover:text-gray-900 hover:dark:text-white";
        ?>
        
        <div class="flex bg-gray-100 dark:bg-zinc-900/80 p-1 rounded-lg w-full lg:w-auto overflow-x-auto transition-colors duration-500 border border-gray-200 dark:border-zinc-800">
            <a href="?view=all" class="whitespace-nowrap px-4 py-2 text-sm font-bold rounded-md transition-colors duration-500 flex items-center gap-2 <?= $view === 'all' ? $active_tab : $inactive_tab ?>">
                <i class="fa-solid fa-list-ul mr-1.5"></i> All Activity
            </a>
            <a href="?view=inventory" class="whitespace-nowrap px-4 py-2 text-sm font-bold transition-colors duration-500 flex items-center gap-2 <?= $view === 'inventory' ? $active_tab : $inactive_tab ?>">
                <i class="fa-solid fa-boxes-stacked mr-1.5"></i> Inventory
            </a>
            <a href="?view=payment" class="whitespace-nowrap px-4 py-2 text-sm font-bold transition-colors duration-500 flex items-center gap-2 <?= $view === 'payment' ? $active_tab : $inactive_tab ?>">
                <i class="fa-solid fa-money-bill-wave mr-1.5"></i> Payments
            </a>
            <a href="?view=security" class="whitespace-nowrap px-4 py-2 text-sm font-bold transition-colors duration-500 flex items-center gap-2 <?= $view === 'security' ? $active_tab : $inactive_tab ?>">
                <i class="fa-solid fa-shield-halved mr-1.5"></i> Security
            </a>
        </div>
    </div>

    <div class="bg-white dark:bg-zinc-900 rounded-2xl shadow-sm border border-gray-100 dark:border-zinc-800 flex flex-col transition-colors duration-500">
        <div class="overflow-x-auto flex-1">
            <table class="w-full whitespace-nowrap">
                
                <thead class="bg-gray-50 dark:bg-zinc-950/50 border-b border-gray-100 dark:border-zinc-800 transition-colors duration-500">
                    <tr>
                        <th class="px-6 py-4 text-left text-[10px] font-extrabold text-gray-500 dark:text-zinc-500 uppercase tracking-widest">Date & Time</th>
                        <th class="px-6 py-4 text-left text-[10px] font-extrabold text-gray-500 dark:text-zinc-500 uppercase tracking-widest">User / Actor</th>
                        <th class="px-6 py-4 text-left text-[10px] font-extrabold text-gray-500 dark:text-zinc-500 uppercase tracking-widest">Action Performed</th>
                        <th class="px-6 py-4 text-left text-[10px] font-extrabold text-gray-500 dark:text-zinc-500 uppercase tracking-widest">Details & Changes</th>
                    </tr>
                </thead>
                
                <tbody id="log-tbody" class="divide-y divide-gray-50 dark:divide-zinc-800/50 text-sm transition-colors duration-500">
                    
                    <?php
                    if ($log_result->num_rows === 0) {
                        echo '<tr id="php-empty-state"><td colspan="4" class="px-6 py-8 text-center text-gray-500 font-medium">No activity logs found for this category.</td></tr>';
                    }

                    while ($log = $log_result->fetch_assoc()) {
                        
                        $log_date = new DateTime($log['created_at']);
                        $time_str = $log_date->format('g:i A');
                        $date_str = $log_date->format('F d, Y');
                        
                        if ($log['full_name'] === null) {
                            $user_name = "System Automator";
                            $user_role = "Internal";
                            $user_initials = '<i class="fa-solid fa-robot text-[10px]"></i>';
                            $avatar_class = "bg-gray-800 text-white";
                        } else {
                            $user_name = htmlspecialchars($log['full_name']);
                            $user_role = htmlspecialchars($log['role']);
                            $user_initials = strtoupper(substr($user_name, 0, 2));
                            $avatar_class = ($user_role === 'superadmin' || $user_role === 'admin') ? "bg-pink-600 text-white shadow-md shadow-pink-600/20" : "bg-gray-100 dark:bg-zinc-800 text-gray-600 dark:text-zinc-400 border border-gray-200 dark:border-zinc-700";
                        }

                        $action = strtoupper($log['action']);
                        $badge_class = "bg-gray-50 text-gray-600 border-gray-200 dark:bg-zinc-800 dark:text-zinc-400 dark:border-zinc-700"; 
                        $icon = "fa-circle-info";

                        if ($action === 'CREATE' || $action === 'ADD') {
                            $badge_class = "bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400 border-emerald-200 dark:border-emerald-500/20";
                            $icon = "fa-plus";
                        } elseif ($action === 'UPDATE' || $action === 'EDIT' || $action === 'ADJUST') {
                            $badge_class = "bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-400 border-amber-200 dark:border-amber-500/20";
                            $icon = "fa-pen";
                        } elseif ($action === 'DELETE' || $action === 'ARCHIVE' || $action === 'VOID') {
                            $badge_class = "bg-rose-50 text-rose-600 dark:bg-rose-500/10 dark:text-rose-400 border-rose-200 dark:border-rose-500/20";
                            $icon = "fa-trash";
                        } elseif ($action === 'LOGIN' || $action === 'LOGOUT') {
                            $badge_class = "bg-blue-50 text-blue-600 dark:bg-blue-500/10 dark:text-blue-400 border-blue-200 dark:border-blue-500/20";
                            $icon = "fa-shield-halved";
                        } elseif ($action === 'EXPORT') {
                            $badge_class = "bg-purple-50 text-purple-600 dark:bg-purple-500/10 dark:text-purple-400 border-purple-200 dark:border-purple-500/20";
                            $icon = "fa-file-export";
                        }

                        // 🚨 JSON PARSING LOGIC FOR DETAILS
                        $desc_data = json_decode($log['description'], true);
                        $is_detailed = is_array($desc_data) && isset($desc_data['is_detailed']) && $desc_data['is_detailed'];
                        
                        $row_attr = '';
                        $details_badge = '';
                        
                        if ($is_detailed) {
                            $display_desc = format_log_description($desc_data['summary']);
                            // Safely embed the JSON inside a data attribute
                            $json_string = htmlspecialchars($log['description'], ENT_QUOTES, 'UTF-8');
                            $row_attr = 'onclick="viewLogDetails(this)" data-details="' . $json_string . '" class="log-row cursor-pointer hover:bg-gray-50/80 dark:hover:bg-zinc-800/30 transition-colors group" title="Click to view details"';
                            $details_badge = '<div class="mt-2 inline-flex items-center gap-1.5 text-[10px] font-bold text-pink-600 dark:text-pink-400 bg-pink-50 dark:bg-pink-500/10 px-2 py-1 rounded border border-pink-200 dark:border-pink-500/20 uppercase tracking-widest"><i class="fa-solid fa-magnifying-glass"></i> View Record</div>';
                        } else {
                            $display_desc = format_log_description($log['description']);
                            $row_attr = 'class="log-row hover:bg-gray-50/80 dark:hover:bg-zinc-800/30 transition-colors group"';
                        }

                        echo '
                        <tr ' . $row_attr . '>
                            <td class="px-6 py-4">
                                <div class="text-gray-900 dark:text-white font-bold text-xs">'.$time_str.'</div>
                                <div class="text-[10px] font-bold text-gray-400 dark:text-zinc-500 mt-1 uppercase tracking-wider">'.$date_str.'</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="h-8 w-8 rounded-full flex items-center justify-center font-extrabold text-xs '.$avatar_class.' shrink-0">
                                        '.$user_initials.'
                                    </div>
                                    <div>
                                        <div class="font-bold text-gray-900 dark:text-white group-hover:text-pink-600 transition-colors">'.$user_name.'</div>
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
                                <div class="text-[12px] font-semibold text-gray-700 dark:text-zinc-300 whitespace-normal max-w-md leading-relaxed">
                                    '.$display_desc.'
                                </div>
                                '.$details_badge.'
                            </td>
                        </tr>';
                    }
                    ?>

                </tbody>
            </table>
        </div>
        <div id="pagination-container" class="w-full bg-gray-50/50 dark:bg-zinc-950/30 rounded-b-2xl transition-colors duration-500"></div>
    </div>

    <div id="log-details-modal" class="fixed inset-0 z-[80] hidden flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm transition-opacity" onclick="closeLogDetails()"></div>
        <div class="relative bg-white dark:bg-zinc-900 rounded-2xl w-full max-w-3xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh] border border-gray-100 dark:border-zinc-800">
            
            <div class="px-6 py-4 border-b border-gray-100 dark:border-zinc-800 flex justify-between items-center bg-gray-50/50 dark:bg-zinc-950/30">
                <div>
                    <h3 id="ld_title" class="text-lg font-bold text-gray-900 dark:text-white">Record Details</h3>
                    <p id="ld_project" class="text-xs font-bold text-pink-600 dark:text-pink-500 uppercase tracking-widest mt-1">Project Name</p>
                </div>
                <button onclick="closeLogDetails()" class="text-gray-400 hover:text-rose-500 transition-colors focus:outline-none">
                    <i class="fa-solid fa-xmark text-xl"></i>
                </button>
            </div>
            
            <div class="p-6 overflow-y-auto flex-1">
                
                <div id="ld_summary_wrapper" class="bg-gray-50 dark:bg-zinc-800/50 border border-gray-200 dark:border-zinc-700 p-4 rounded-xl mb-6">
                    <p id="ld_summary_container" class="text-xs font-bold text-gray-700 dark:text-zinc-300 flex items-center gap-2">
                        <i class="fa-solid fa-circle-info"></i> <span id="ld_summary">Summary goes here.</span>
                    </p>
                </div>

                <h4 id="ld_table_title" class="text-xs font-extrabold text-gray-500 dark:text-zinc-500 uppercase tracking-widest mb-3 border-b border-gray-200 dark:border-zinc-800 pb-2">Record Snapshot</h4>
                
                <table class="w-full text-left">
                    <thead id="ld_table_head">
                        </thead>
                    <tbody id="ld_tbody" class="divide-y divide-gray-50 dark:divide-zinc-800/50">
                        </tbody>
                </table>
            </div>

            <div id="ld_footer" class="px-6 py-4 border-t border-gray-100 dark:border-zinc-800 bg-gray-50/50 dark:bg-zinc-950/30 flex justify-between items-center">
                <p id="ld_footer_text" class="text-[10px] font-extrabold text-gray-500 uppercase tracking-widest">Total Value</p>
                <p id="ld_total" class="text-lg font-black text-rose-600 dark:text-rose-500 leading-none">₱ 0.00</p>
            </div>
        </div>
    </div>

    <div id="global-confirm-modal" class="fixed inset-0 z-[90] hidden flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm transition-opacity" id="global-confirm-backdrop"></div>
        <div class="relative bg-white dark:bg-zinc-900 rounded-2xl w-full max-w-sm shadow-2xl overflow-hidden flex flex-col border border-gray-100 dark:border-zinc-800 transform scale-95 opacity-0 transition-all duration-200" id="global-confirm-box">
            <div class="p-6 text-center">
                <div id="global-confirm-icon-wrapper" class="w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4 text-2xl border">
                    <i id="global-confirm-icon" class="fa-solid fa-triangle-exclamation"></i>
                </div>
                <h3 id="global-confirm-title" class="text-xl font-bold text-gray-900 dark:text-white mb-2">Are you sure?</h3>
                <p id="global-confirm-msg" class="text-sm font-medium text-gray-600 dark:text-zinc-400 leading-relaxed whitespace-pre-wrap"></p>
            </div>
            <div class="px-6 py-4 border-t border-gray-100 dark:border-zinc-800 bg-gray-50/50 dark:bg-zinc-950/30 flex justify-center gap-3">
                <button id="global-confirm-cancel" class="px-5 py-2.5 text-sm font-bold text-gray-600 dark:text-zinc-400 hover:bg-gray-100 dark:hover:bg-zinc-800 rounded-xl transition-colors focus:outline-none flex-1">Cancel</button>
                <button id="global-confirm-ok" class="text-white px-5 py-2.5 rounded-xl text-sm font-bold shadow-md focus:outline-none transition-all flex-1">Confirm</button>
            </div>
        </div>
    </div>

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

</main>

<script>
    // ==========================================
    // GLOBAL UI OVERRIDES
    // ==========================================
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

    function customConfirm(message, title = "Are you sure?", confirmBtnText = "Confirm", type = "warning") {
        return new Promise((resolve) => {
            const modal = document.getElementById('global-confirm-modal');
            const box = document.getElementById('global-confirm-box');
            const msgEl = document.getElementById('global-confirm-msg');
            const titleEl = document.getElementById('global-confirm-title');
            const btnOk = document.getElementById('global-confirm-ok');
            const btnCancel = document.getElementById('global-confirm-cancel');
            const backdrop = document.getElementById('global-confirm-backdrop');
            const iconWrapper = document.getElementById('global-confirm-icon-wrapper');
            const icon = document.getElementById('global-confirm-icon');

            msgEl.textContent = message;
            titleEl.textContent = title;
            btnOk.textContent = confirmBtnText;

            iconWrapper.className = "w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4 text-2xl border ";
            btnOk.className = "text-white px-5 py-2.5 rounded-xl text-sm font-bold shadow-md focus:outline-none transition-all flex-1 ";
            
            if (type === "danger") {
                iconWrapper.className += "bg-rose-100 dark:bg-rose-500/20 text-rose-600 dark:text-rose-400 border-rose-200 dark:border-rose-500/30";
                icon.className = "fa-solid fa-trash";
                btnOk.className += "bg-rose-600 hover:bg-rose-700 shadow-rose-600/20";
            } else if (type === "info") {
                iconWrapper.className += "bg-emerald-100 dark:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 border-emerald-200 dark:border-emerald-500/30";
                icon.className = "fa-solid fa-file-export";
                btnOk.className += "bg-emerald-500 hover:bg-emerald-600 shadow-emerald-500/20";
            } else {
                iconWrapper.className += "bg-amber-100 dark:bg-amber-500/20 text-amber-600 dark:text-amber-400 border-amber-200 dark:border-amber-500/30";
                icon.className = "fa-solid fa-triangle-exclamation";
                btnOk.className += "bg-amber-500 hover:bg-amber-600 shadow-amber-500/20";
            }

            modal.classList.remove('hidden');
            setTimeout(() => {
                box.classList.remove('scale-95', 'opacity-0');
                box.classList.add('scale-100', 'opacity-100');
            }, 10);

            const cleanupAndResolve = (result) => {
                box.classList.remove('scale-100', 'opacity-100');
                box.classList.add('scale-95', 'opacity-0');
                setTimeout(() => modal.classList.add('hidden'), 200);
                
                btnOk.removeEventListener('click', onOk);
                btnCancel.removeEventListener('click', onCancel);
                backdrop.removeEventListener('click', onCancel);
                
                resolve(result);
            };

            const onOk = () => cleanupAndResolve(true);
            const onCancel = () => cleanupAndResolve(false);

            btnOk.addEventListener('click', onOk);
            btnCancel.addEventListener('click', onCancel);
            backdrop.addEventListener('click', onCancel);
        });
    }

    window.alert = customAlert;

    // 🚨 NEW: DYNAMIC MODAL PARSER
    function viewLogDetails(rowElement) {
        const dataStr = rowElement.getAttribute('data-details');
        if (!dataStr) return;

        try {
            const data = JSON.parse(dataStr);
            if (!data.is_detailed) return;

            // Set Universal Data
            document.getElementById('ld_project').textContent = data.project;
            document.getElementById('ld_summary').textContent = data.summary;
            
            let html = '';
            
            // ===============================================
            // SCENARIO 1: DELTA LOGGING (Before & After)
            // ===============================================
            if (data.type === 'update_comparison') {
                
                document.getElementById('ld_title').textContent = "Update Details";
                document.getElementById('ld_table_title').textContent = "Modified Fields";
                
                // Style Summary Wrapper (Amber/Blue)
                document.getElementById('ld_summary_wrapper').className = "bg-amber-50 dark:bg-amber-500/10 border border-amber-100 dark:border-amber-500/20 p-4 rounded-xl mb-6";
                document.getElementById('ld_summary_container').className = "text-xs font-bold text-amber-600 dark:text-amber-400 flex items-center gap-2";

                // Build 3-Column Header
                document.getElementById('ld_table_head').innerHTML = `
                    <tr class="text-[10px] font-extrabold text-gray-400 dark:text-zinc-500 uppercase tracking-widest border-b border-gray-100 dark:border-zinc-800">
                        <th class="pb-2 w-1/4">Field Changed</th>
                        <th class="pb-2 w-3/8 text-rose-500">Original Value</th>
                        <th class="pb-2 w-3/8 text-emerald-600 text-right">New Value</th>
                    </tr>
                `;
                
                // Build Rows
                if (data.changes && data.changes.length > 0) {
                    data.changes.forEach(c => {
                        html += `
                            <tr class="border-b border-gray-50 dark:border-zinc-800/50 hover:bg-gray-50 dark:hover:bg-zinc-800/30 transition-colors">
                                <td class="py-3 pr-2 text-xs font-bold text-gray-900 dark:text-white align-top">${c.field}</td>
                                <td class="py-3 pr-2 text-xs font-medium text-rose-500 line-through align-top whitespace-pre-wrap">${c.old}</td>
                                <td class="py-3 text-xs font-bold text-emerald-600 dark:text-emerald-500 text-right align-top whitespace-pre-wrap">${c.new}</td>
                            </tr>
                        `;
                    });
                }
                
                // Hide Financial Footer
                document.getElementById('ld_footer').classList.add('hidden');
                
            // ===============================================
            // SCENARIO 2: FINANCIAL/INVENTORY (Receipt)
            // ===============================================
            } else {
                
                document.getElementById('ld_title').textContent = "Archived Record Details";
                document.getElementById('ld_table_title').textContent = "Deleted Items Snapshot";

                // Style Summary Wrapper (Rose)
                document.getElementById('ld_summary_wrapper').className = "bg-rose-50 dark:bg-rose-500/10 border border-rose-100 dark:border-rose-500/20 p-4 rounded-xl mb-6";
                document.getElementById('ld_summary_container').className = "text-xs font-bold text-rose-600 dark:text-rose-400 flex items-center gap-2";

                // Build 4-Column Header
                document.getElementById('ld_table_head').innerHTML = `
                    <tr class="text-[10px] font-extrabold text-gray-400 dark:text-zinc-500 uppercase tracking-widest border-b border-gray-100 dark:border-zinc-800">
                        <th class="pb-2 w-1/2">Material Details</th>
                        <th class="pb-2 text-right">Quantity</th>
                        <th class="pb-2 text-right">Unit Cost</th>
                        <th class="pb-2 text-right">Total Value</th>
                    </tr>
                `;
                
                // Build Rows
                if (data.items && data.items.length > 0) {
                    data.items.forEach(item => {
                        html += `
                            <tr class="border-b border-gray-50 dark:border-zinc-800/50 hover:bg-gray-50 dark:hover:bg-zinc-800/30 transition-colors">
                                <td class="py-3 pr-2 text-xs font-bold text-gray-900 dark:text-white">${item.name}</td>
                                <td class="py-3 pr-2 text-xs font-bold text-gray-500 dark:text-zinc-400 text-right">${item.qty}</td>
                                <td class="py-3 pr-2 text-xs font-bold text-gray-500 dark:text-zinc-400 text-right">₱ ${parseFloat(item.unit_cost).toLocaleString('en-US', {minimumFractionDigits:2})}</td>
                                <td class="py-3 text-xs font-black text-gray-900 dark:text-white text-right">₱ ${parseFloat(item.total).toLocaleString('en-US', {minimumFractionDigits:2})}</td>
                            </tr>
                        `;
                    });
                }
                
                // Show Financial Footer
                document.getElementById('ld_total').textContent = '₱ ' + parseFloat(data.total_value).toLocaleString('en-US', {minimumFractionDigits: 2});
                document.getElementById('ld_footer').classList.remove('hidden');
            }
            
            document.getElementById('ld_tbody').innerHTML = html;
            document.getElementById('log-details-modal').classList.remove('hidden');
        } catch (e) {
            console.error("Failed to parse log details:", e);
        }
    }

    function closeLogDetails() {
        document.getElementById('log-details-modal').classList.add('hidden');
    }

    // --- Search Logic ---
    const searchInput = document.getElementById('search-input');
    const tbody = document.getElementById('log-tbody');
    
    if (tbody && searchInput) {
        const allRows = Array.from(tbody.querySelectorAll('tr.log-row'));
        const paginationContainer = document.getElementById('pagination-container');
        const colspanCount = 4;
        
        let currentPage = 1;
        const rowsPerPage = 15;

        function updateTable() {
            const searchTerm = searchInput.value.toLowerCase();
            
            const filteredRows = allRows.filter(row => {
                const text = row.innerText.toLowerCase();
                return text.includes(searchTerm);
            });

            const totalItems = filteredRows.length;
            const totalPages = Math.ceil(totalItems / rowsPerPage) || 1;
            
            if (currentPage > totalPages) currentPage = 1;

            const startIndex = (currentPage - 1) * rowsPerPage;
            const endIndex = startIndex + rowsPerPage;

            allRows.forEach(row => row.style.display = 'none');

            filteredRows.slice(startIndex, endIndex).forEach(row => {
                row.style.display = '';
            });

            const existingEmptyRow = document.getElementById('js-empty-state');
            if (totalItems === 0 && allRows.length > 0) {
                if (!existingEmptyRow) {
                    tbody.insertAdjacentHTML('beforeend', `<tr id="js-empty-state"><td colspan="${colspanCount}" class="px-6 py-8 text-center text-gray-500 font-medium italic">No matching logs found.</td></tr>`);
                } else {
                    existingEmptyRow.style.display = '';
                }
            } else {
                if (existingEmptyRow) existingEmptyRow.style.display = 'none';
            }

            const phpEmpty = document.getElementById('php-empty-state');
            if(phpEmpty && allRows.length > 0) phpEmpty.style.display = 'none';

            renderPagination(totalItems, totalPages);
        }

        function renderPagination(totalItems, totalPages) {
            if (totalItems === 0) {
                paginationContainer.innerHTML = '';
                return;
            }

            let html = `
                <div class="flex flex-col sm:flex-row items-center justify-between gap-4 w-full px-6 py-4 border-t border-gray-100 dark:border-zinc-800">
                    <div class="text-xs font-semibold text-gray-500 dark:text-zinc-400">
                        Showing <span class="font-bold text-gray-900 dark:text-white">${((currentPage - 1) * rowsPerPage) + 1}</span> to <span class="font-bold text-gray-900 dark:text-white">${Math.min(currentPage * rowsPerPage, totalItems)}</span> of <span class="font-bold text-gray-900 dark:text-white">${totalItems}</span> entries
                    </div>
                    <div class="flex gap-1">
                        <button onclick="changePage(${currentPage - 1})" class="px-3 py-1.5 text-xs font-bold rounded-lg transition-colors ${currentPage === 1 ? 'text-gray-400 dark:text-zinc-600 cursor-not-allowed' : 'text-gray-700 dark:text-zinc-300 hover:bg-gray-200 dark:hover:bg-zinc-800'}" ${currentPage === 1 ? 'disabled' : ''}>Prev</button>
            `;

            for (let i = 1; i <= totalPages; i++) {
                if (totalPages > 7) {
                     if (i === 1 || i === totalPages || (i >= currentPage - 1 && i <= currentPage + 1)) {
                         html += makePageBtn(i);
                     } else if (i === currentPage - 2 || i === currentPage + 2) {
                         html += `<span class="px-2 py-1 text-xs text-gray-400 dark:text-zinc-600">...</span>`;
                     }
                } else {
                     html += makePageBtn(i);
                }
            }

            html += `
                        <button onclick="changePage(${currentPage + 1})" class="px-3 py-1.5 text-xs font-bold rounded-lg transition-colors ${currentPage === totalPages ? 'text-gray-400 dark:text-zinc-600 cursor-not-allowed' : 'text-gray-700 dark:text-zinc-300 hover:bg-gray-200 dark:hover:bg-zinc-800'}" ${currentPage === totalPages ? 'disabled' : ''}>Next</button>
                    </div>
                </div>
            `;
            paginationContainer.innerHTML = html;
        }

        function makePageBtn(i) {
            const activeClass = i === currentPage 
                ? 'bg-pink-600 text-white shadow-md shadow-pink-600/20' 
                : 'text-gray-700 dark:text-zinc-300 hover:bg-gray-200 dark:hover:bg-zinc-800';
            return `<button onclick="changePage(${i})" class="px-3 py-1.5 text-xs font-bold rounded-lg transition-colors ${activeClass}">${i}</button>`;
        }

        window.changePage = function(page) {
            currentPage = page;
            updateTable();
        }

        searchInput.addEventListener('input', () => {
            currentPage = 1; 
            updateTable();
        });

        updateTable();
    }

    async function exportLogs() {
        const isConfirmed = await customConfirm("Are you sure you want to export all visible activity logs to a CSV file?", "Export Logs", "Yes, Export", "info");
        if (isConfirmed) {
            window.location.href = `actions/export_logs.php?view=<?= htmlspecialchars($view) ?>`;
        }
    }
</script>

<?php include 'includes/footer.php'; ?>