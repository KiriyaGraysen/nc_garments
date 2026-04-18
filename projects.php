<?php
$page_title = "Orders & Projects | NC Garments";
require_once('config/database.php');

$view_archived = (isset($_GET['view']) && $_GET['view'] === 'archived') ? 1 : 0;

$sort = $_GET['sort'] ?? 'newest';
switch ($sort) {
    case 'name_asc': $order_by = "p.project_name ASC"; break;
    case 'name_desc': $order_by = "p.project_name DESC"; break;
    case 'price_desc': $order_by = "p.agreed_price DESC"; break;
    case 'due_asc': $order_by = "p.due_date ASC"; break;
    case 'oldest': $order_by = "p.project_id ASC"; break;
    case 'newest':
    default: $order_by = "p.project_id DESC"; break;
}

$stmt = $conn->prepare("
    SELECT 
        p.project_id, p.project_name, p.quantity, p.agreed_price, p.status, p.progress, 
        p.start_date, p.due_date, p.finish_date, p.overdue_notes,
        c.full_name,
        COALESCE(SUM(pb.total_cost), 0) AS total_material_cost
    FROM project p
    LEFT JOIN customer c ON c.customer_id = p.customer_id
    LEFT JOIN premade_product pre ON pre.product_id = p.produced_product_id
    LEFT JOIN project_breakdown pb ON p.project_id = pb.project_id
    WHERE p.is_archived = ?
    GROUP BY p.project_id
    ORDER BY $order_by
");
$stmt->bind_param("i", $view_archived);
$stmt->execute();
$project_result = $stmt->get_result();

$rm_stmt = $conn->prepare("SELECT material_id, material_name, current_price, unit_of_measure FROM raw_material ORDER BY material_name ASC");
$rm_stmt->execute();
$materials_json = json_encode($rm_stmt->get_result()->fetch_all(MYSQLI_ASSOC)); 

$cust_stmt = $conn->prepare("SELECT customer_id, full_name FROM customer ORDER BY full_name ASC");
$cust_stmt->execute();
$customers = $cust_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$prod_stmt = $conn->prepare("SELECT product_id, product_name, size FROM premade_product ORDER BY product_name ASC");
$prod_stmt->execute();
$products = $prod_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$progress_percentages = [
    'not started' => 0, 'sampling' => 15, 'cutting' => 30, 
    'printing' => 45, 'sewing' => 60, 'quality check' => 75, 
    'finishing' => 85, 'packing' => 95, 'done' => 100, 
    'released' => 100, 'cancelled' => 0
];

function calculate_timeline($start, $due, $finish) {
    $today = new DateTime('today');
    $due_date = empty($due) ? null : new DateTime($due);
    $start_date = empty($start) ? null : new DateTime($start);
    $finish_date = empty($finish) ? null : new DateTime($finish);
    
    $days_spent = 0;
    if ($start_date) {
        $end_point = $finish_date ? $finish_date : clone $today;
        $days_spent = $start_date->diff($end_point)->days;
    }

    $is_overdue = false;
    $status_html = '<span class="text-gray-500"><i class="fa-regular fa-clock"></i> On Schedule</span>';
    
    if ($due_date && !$finish_date) {
        $due_date->setTime(0, 0, 0); 
        $diff = $today->diff($due_date);
        $days_left = $diff->days;
        $is_past = $diff->invert === 1; 

        if ($days_left === 0) {
            $status_html = '<span class="text-amber-500 font-bold"><i class="fa-solid fa-circle-exclamation"></i> Due Today</span>';
        } elseif ($is_past) {
            $is_overdue = true;
            $status_html = '<span class="text-rose-600 font-bold"><i class="fa-solid fa-triangle-exclamation"></i> Overdue by ' . $days_left . ' d</span>';
        } else {
            $status_html = '<span class="' . ($days_left <= 7 ? 'text-amber-500' : 'text-gray-500') . '"><i class="fa-regular fa-clock"></i> In ' . $days_left . ' d</span>';
        }
    } elseif ($finish_date) {
        $status_html = '<span class="text-emerald-500 font-bold"><i class="fa-solid fa-check-circle"></i> Completed</span>';
    }

    return [
        'start_format' => $start_date ? $start_date->format('M j, Y') : '--',
        'due_format' => $due_date ? $due_date->format('M j, Y') : 'No Due Date',
        'finish_format' => $finish_date ? $finish_date->format('M j, Y') : '--',
        'days_spent' => $days_spent,
        'status_html' => $status_html,
        'is_overdue' => $is_overdue
    ];
}

$progress_options = ['not started', 'sampling', 'cutting', 'printing', 'sewing', 'quality check', 'finishing', 'packing', 'done', 'released', 'cancelled'];

include 'includes/header.php'; 
?>

<main class="flex-1 p-4 md:p-8 overflow-y-auto transition-colors duration-500 font-sans relative">
    
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white transition-colors duration-500">Orders & Projects</h2>
            <p class="text-gray-500 dark:text-zinc-400 text-sm mt-1 transition-colors duration-500">Manage customer orders, update production phases, and track timelines.</p>
        </div>
        <?php if (!$view_archived): ?>
        <button onclick="openCreateProjectModal()" class="bg-pink-600 hover:bg-pink-700 text-white px-5 py-2.5 rounded-xl text-sm font-bold transition-all duration-300 shadow-lg shadow-pink-600/20 flex items-center gap-2 cursor-pointer focus:outline-none">
            <i class="fa-solid fa-folder-plus"></i> Create New Project
        </button>
        <?php endif; ?>
    </div>

    <div class="flex flex-col lg:flex-row justify-between items-center mb-6 gap-4">
        
        <div class="flex w-full lg:w-auto gap-3 flex-1 max-w-2xl">
            <div class="relative w-full group">
                <i class="fa-solid fa-search absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400 group-focus-within:text-pink-600 transition-colors duration-500"></i>
                <input type="text" id="search-input" placeholder="Search project name, client, or ID..." 
                       class="w-full pl-11 pr-4 py-3 border border-gray-200 dark:border-zinc-800 rounded-xl bg-white dark:bg-zinc-900/50 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-zinc-500 focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent transition-colors duration-500 shadow-sm text-sm font-medium">
            </div>
            
            <div class="relative w-48 shrink-0">
                <select onchange="window.location.href=this.value" class="w-full px-4 py-3 border border-gray-200 dark:border-zinc-800 rounded-xl bg-white dark:bg-zinc-900/50 text-gray-700 dark:text-zinc-300 focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent transition-colors duration-500 shadow-sm text-sm font-bold cursor-pointer appearance-none">
                    <option value="?view=<?= $view_archived === 1 ? 'archived' : 'active' ?>&sort=newest" <?= $sort == 'newest' ? 'selected' : '' ?>>Newest First</option>
                    <option value="?view=<?= $view_archived === 1 ? 'archived' : 'active' ?>&sort=oldest" <?= $sort == 'oldest' ? 'selected' : '' ?>>Oldest First</option>
                    <option value="?view=<?= $view_archived === 1 ? 'archived' : 'active' ?>&sort=name_asc" <?= $sort == 'name_asc' ? 'selected' : '' ?>>Name (A-Z)</option>
                    <option value="?view=<?= $view_archived === 1 ? 'archived' : 'active' ?>&sort=name_desc" <?= $sort == 'name_desc' ? 'selected' : '' ?>>Name (Z-A)</option>
                    <option value="?view=<?= $view_archived === 1 ? 'archived' : 'active' ?>&sort=due_asc" <?= $sort == 'due_asc' ? 'selected' : '' ?>>Nearest Due Date</option>
                    <option value="?view=<?= $view_archived === 1 ? 'archived' : 'active' ?>&sort=price_desc" <?= $sort == 'price_desc' ? 'selected' : '' ?>>Highest Price</option>
                </select>
                <i class="fa-solid fa-chevron-down absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-400 text-xs pointer-events-none"></i>
            </div>
        </div>

        <?php
            $active_tab = "bg-white dark:bg-zinc-800 text-pink-600 dark:text-pink-500 shadow-sm";
            $inactive_tab = "text-gray-500 dark:text-zinc-400 hover:text-gray-900 hover:dark:text-white";
        ?>

        <div class="flex bg-gray-100 dark:bg-zinc-900/80 p-1 rounded-lg w-full lg:w-auto overflow-x-auto transition-colors duration-500 border border-gray-200 dark:border-zinc-800">
            <a href="?view=active&sort=<?= $sort ?>" class="whitespace-nowrap px-4 py-2 text-sm font-bold rounded-md transition-colors duration-500 flex items-center gap-2 <?= $view_archived === 0 ? $active_tab : $inactive_tab ?>">
                <i class="fa-solid fa-layer-group mr-1.5"></i> Active Projects
            </a>
            <a href="?view=archived&sort=<?= $sort ?>" class="whitespace-nowrap px-4 py-2 text-sm font-bold transition-colors duration-500 flex items-center gap-2 <?= $view_archived === 1 ? $active_tab : $inactive_tab ?>">
                <i class="fa-solid fa-box-archive mr-1.5"></i> Archived
            </a>
        </div>
    </div>

    <div class="bg-white dark:bg-zinc-900 rounded-2xl shadow-sm border border-gray-100 dark:border-zinc-800 flex flex-col transition-colors duration-500">
        <div class="overflow-x-auto flex-1">
            
            <table class="w-full whitespace-nowrap">
                <thead class="bg-gray-50 dark:bg-zinc-950/50 border-b border-gray-100 dark:border-zinc-800 transition-colors duration-500">
                    <tr>
                        <th class="px-6 py-4 text-left text-[10px] font-extrabold text-gray-500 dark:text-zinc-500 uppercase tracking-widest">Project Details</th>
                        <th class="px-6 py-4 text-left text-[10px] font-extrabold text-gray-500 dark:text-zinc-500 uppercase tracking-widest w-56">Current Progress</th>
                        <th class="px-6 py-4 text-left text-[10px] font-extrabold text-gray-500 dark:text-zinc-500 uppercase tracking-widest">Timeline & Dates</th>
                        <th class="px-6 py-4 text-left text-[10px] font-extrabold text-gray-500 dark:text-zinc-500 uppercase tracking-widest">Financials</th>
                        <th class="px-6 py-4 text-center text-[10px] font-extrabold text-gray-500 dark:text-zinc-500 uppercase tracking-widest">Actions</th>
                    </tr>
                </thead>
                
                <tbody id="project-tbody" class="divide-y divide-gray-50 dark:divide-zinc-800/50 text-sm transition-colors duration-500">
                    <?php
                    if ($project_result->num_rows === 0) {
                        echo '<tr id="php-empty-state"><td colspan="5" class="px-6 py-8 text-center text-gray-500 font-medium">No projects found.</td></tr>';
                    }

                    while ($project = $project_result->fetch_assoc()) {
                        $full_name = empty($project['full_name']) ? 'Internal Restock' : htmlspecialchars($project['full_name']);
                        $project_name = htmlspecialchars($project['project_name']);
                        $qty = htmlspecialchars($project['quantity']);
                        
                        $agreed_price = (float)$project['agreed_price'];
                        $material_cost = (float)$project['total_material_cost'];
                        
                        $is_internal_js = empty($project['full_name']) ? 'true' : 'false';

                        $est_profit = $agreed_price - $material_cost;
                        $profit_color = ($est_profit > 0) ? 'text-emerald-500' : (($agreed_price == 0 && $material_cost > 0) ? 'text-amber-500' : 'text-gray-400');
                        
                        $time = calculate_timeline($project['start_date'], $project['due_date'], $project['finish_date']);
                        
                        $pct = $progress_percentages[$project['progress']] ?? 0;
                        $bar_color = ($project['progress'] === 'cancelled') ? 'bg-rose-500' : 'bg-pink-600';
                        $pct_color = ($project['progress'] === 'cancelled') ? 'text-rose-500' : 'text-pink-600';
                        $disabled_select = $view_archived ? 'disabled' : '';

                        $current_project_options = $progress_options;
                        if ($is_internal_js === 'true') {
                            $current_project_options = array_values(array_diff($progress_options, ['packing', 'released']));
                        }
                        
                        echo '
                            <tr class="project-row hover:bg-gray-50/80 dark:hover:bg-zinc-800/30 transition-colors group cursor-pointer" onclick="viewProjectDetails(' . $project['project_id'] . ')">
                                <td class="px-6 py-4">
                                    <div class="font-bold text-pink-600 dark:text-pink-500 group-hover:text-pink-700 transition-colors">#PRJ-2026-' . str_pad($project['project_id'], 3, '0', STR_PAD_LEFT) . '</div>
                                    <div class="font-bold text-gray-900 dark:text-white mt-1">' . $project_name . '</div>
                                    <div class="text-xs font-medium text-gray-500 dark:text-zinc-400 flex items-center gap-1.5 mt-1">
                                        <i class="fa-regular fa-user text-[10px]"></i> ' . $full_name . ' • ' . $qty . ' pcs
                                    </div>
                                </td>
                                
                                <td class="px-6 py-4" onclick="event.stopPropagation();">
                                    <div class="mb-2 w-full">
                                        <div class="flex justify-between items-center text-[10px] font-extrabold uppercase tracking-wider mb-1">
                                            <span class="text-gray-500">' . $project['progress'] . '</span>
                                            <span class="'.$pct_color.'">' . $pct . '%</span>
                                        </div>
                                        <div class="w-full bg-gray-200 dark:bg-zinc-950 rounded-full h-1.5 overflow-hidden">
                                            <div class="'.$bar_color.' h-1.5 rounded-full transition-all duration-500" style="width: ' . $pct . '%"></div>
                                        </div>
                                    </div>
                                    <select onchange="updateProgress(' . $project['project_id'] . ', this.value, \'' . $project['progress'] . '\')" '.$disabled_select.' class="w-full bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-700 text-gray-900 dark:text-white text-[11px] font-bold rounded-lg px-2 py-1.5 outline-none focus:ring-2 focus:ring-pink-500 transition-all uppercase tracking-wider cursor-pointer shadow-sm disabled:opacity-50">
                                        ';
                                        foreach ($current_project_options as $opt) {
                                            $selected = ($project['progress'] === $opt) ? 'selected' : '';
                                            echo '<option value="'.$opt.'" '.$selected.'>'.$opt.'</option>';
                                        }
                                        echo '
                                    </select>
                                </td>
                                
                                <td class="px-6 py-4">
                                    <div class="flex flex-col gap-1 text-[11px] font-semibold text-gray-600 dark:text-zinc-400">
                                        <div><span class="text-gray-400">Start:</span> <span class="text-gray-900 dark:text-white">'.$time['start_format'].'</span></div>
                                        <div><span class="text-gray-400">Due:</span> <span class="text-gray-900 dark:text-white">'.$time['due_format'].'</span></div>
                                        <div class="mt-1 pt-1 border-t border-gray-100 dark:border-zinc-800 flex justify-between items-center">
                                            '.$time['status_html'].'
                                            <span class="text-[10px] font-extrabold bg-gray-100 dark:bg-zinc-800 px-2 py-0.5 rounded">'.$time['days_spent'].' Days</span>
                                        </div>
                                    </div>
                                </td>
                                
                                <td class="px-6 py-4">
                                    <div class="font-extrabold text-gray-900 dark:text-white">
                                        ₱ ' . number_format($agreed_price, 2) . '
                                    </div>
                                    <div class="text-[11px] font-bold text-gray-500 dark:text-zinc-400 tracking-wide mt-1 uppercase">Cost: ₱ ' . number_format($material_cost, 2) . '</div>
                                    <div class="text-[11px] font-bold ' . $profit_color . ' tracking-wide mt-0.5 uppercase">Est. Profit: ₱ ' . number_format($est_profit, 2) . '</div>
                                </td>
                                
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-center gap-2">';
                                    
                                    // NOTES BUTTON
                                    if ($time['is_overdue'] || !empty($project['overdue_notes'])) {
                                        $note_icon = empty($project['overdue_notes']) ? 'fa-regular fa-comment' : 'fa-solid fa-comment';
                                        $btn_border = empty($project['overdue_notes']) ? 'border-gray-200 dark:border-zinc-700 text-gray-400 hover:border-amber-300 hover:text-amber-500' : 'border-amber-200 dark:border-amber-500/50 text-amber-500';
                                        
                                        echo '<button onclick="event.stopPropagation(); openNotesModal('.$project['project_id'].', \''.addslashes($project['overdue_notes'] ?? '').'\')" class="relative group/btn flex items-center justify-center w-8 h-8 bg-white dark:bg-zinc-800 border '.$btn_border.' rounded-lg transition-all duration-300 shadow-sm focus:outline-none">
                                                <i class="'.$note_icon.' transition-colors"></i>
                                                
                                                <span class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 px-2.5 py-1 text-[10px] font-bold text-white bg-gray-900 dark:bg-black rounded-md opacity-0 group-hover/btn:opacity-100 transition-opacity whitespace-nowrap pointer-events-none z-50 shadow-lg">
                                                    Project Notes
                                                    <span class="absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent border-t-gray-900 dark:border-t-black"></span>
                                                </span>
                                              </button>';
                                    }

                                    // EDIT BUTTON
                                    echo '<button onclick="event.stopPropagation(); viewProjectDetails(' . $project['project_id'] . ')" class="relative group/btn flex items-center justify-center w-8 h-8 bg-white dark:bg-zinc-800 border border-gray-200 dark:border-zinc-700 hover:border-blue-300 text-gray-400 hover:text-blue-500 rounded-lg transition-all duration-300 shadow-sm focus:outline-none">
                                            <i class="fa-solid fa-pen-to-square transition-colors"></i>
                                            
                                            <span class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 px-2.5 py-1 text-[10px] font-bold text-white bg-gray-900 dark:bg-black rounded-md opacity-0 group-hover/btn:opacity-100 transition-opacity whitespace-nowrap pointer-events-none z-50 shadow-lg">
                                                Edit Details
                                                <span class="absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent border-t-gray-900 dark:border-t-black"></span>
                                            </span>
                                        </button>';
                                            
                                    // COSTING BUTTON
                                    echo '<button onclick="event.stopPropagation(); openCostingModal(' . $project['project_id'] . ', \'' . addslashes($project_name) . '\', ' . $agreed_price . ', ' . $is_internal_js . ')" class="relative group/btn flex items-center justify-center w-8 h-8 bg-white dark:bg-zinc-800 border border-gray-200 dark:border-zinc-700 hover:border-pink-300 text-gray-400 hover:text-pink-600 rounded-lg transition-all duration-300 shadow-sm focus:outline-none">
                                            <i class="fa-solid fa-file-invoice-dollar transition-colors"></i>
                                            
                                            <span class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 px-2.5 py-1 text-[10px] font-bold text-white bg-gray-900 dark:bg-black rounded-md opacity-0 group-hover/btn:opacity-100 transition-opacity whitespace-nowrap pointer-events-none z-50 shadow-lg">
                                                Costing
                                                <span class="absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent border-t-gray-900 dark:border-t-black"></span>
                                            </span>
                                        </button>';

                                    // ARCHIVE / RESTORE BUTTON
                                    if ($view_archived === 0) {
                                        echo '<button onclick="event.stopPropagation(); archiveProject(' . $project['project_id'] . ')" class="relative group/btn flex items-center justify-center w-8 h-8 bg-white dark:bg-zinc-800 border border-gray-200 dark:border-zinc-700 hover:border-amber-300 text-gray-400 hover:text-amber-500 rounded-lg transition-all duration-300 shadow-sm focus:outline-none">
                                                  <i class="fa-solid fa-box-archive transition-colors"></i>
                                                  
                                                  <span class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 px-2.5 py-1 text-[10px] font-bold text-white bg-gray-900 dark:bg-black rounded-md opacity-0 group-hover/btn:opacity-100 transition-opacity whitespace-nowrap pointer-events-none z-50 shadow-lg">
                                                      Archive
                                                      <span class="absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent border-t-gray-900 dark:border-t-black"></span>
                                                  </span>
                                              </button>';
                                    } else {
                                        echo '<button onclick="event.stopPropagation(); restoreProject(' . $project['project_id'] . ')" class="relative group/btn flex items-center justify-center w-8 h-8 bg-white dark:bg-zinc-800 border border-gray-200 dark:border-zinc-700 hover:border-emerald-300 text-gray-400 hover:text-emerald-500 rounded-lg transition-all duration-300 shadow-sm focus:outline-none">
                                                  <i class="fa-solid fa-clock-rotate-left transition-colors"></i>
                                                  
                                                  <span class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 px-2.5 py-1 text-[10px] font-bold text-white bg-gray-900 dark:bg-black rounded-md opacity-0 group-hover/btn:opacity-100 transition-opacity whitespace-nowrap pointer-events-none z-50 shadow-lg">
                                                      Restore
                                                      <span class="absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent border-t-gray-900 dark:border-t-black"></span>
                                                  </span>
                                              </button>';
                                    }

                                    echo '  </div>
                                        </td>
                            </tr>
                        ';
                    }
                    ?>
                </tbody>
            </table>
        </div>
        
        <div id="pagination-container" class="w-full bg-gray-50/50 dark:bg-zinc-950/30 rounded-b-2xl transition-colors duration-500"></div>
    </div>
    
    <div id="create-project-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm transition-opacity" onclick="closeCreateProjectModal()"></div>
        <div class="relative bg-white dark:bg-zinc-900 rounded-2xl w-full max-w-5xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh] border border-gray-100 dark:border-zinc-800">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-zinc-800 flex justify-between items-center bg-gray-50/50 dark:bg-zinc-950/30">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Create New Project</h3>
                <button onclick="closeCreateProjectModal()" class="text-gray-400 hover:text-rose-500 transition-colors focus:outline-none"><i class="fa-solid fa-xmark text-xl"></i></button>
            </div>
            <div class="p-6 overflow-y-auto flex-1 bg-gray-50/30 dark:bg-zinc-950/30">
                <form id="create-project-form">
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 relative">
                        <div class="lg:col-span-7 flex flex-col gap-5">
                            <div class="bg-white dark:bg-zinc-900 p-5 rounded-xl border border-gray-100 dark:border-zinc-800 shadow-sm">
                                <h4 class="text-xs font-extrabold text-gray-500 dark:text-zinc-500 uppercase tracking-widest mb-3 border-b border-gray-100 dark:border-zinc-800 pb-2">Project Information</h4>
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="col-span-2 md:col-span-1">
                                        <label class="block text-[10px] font-extrabold text-gray-500 uppercase tracking-widest mb-1">Project Name</label>
                                        <input type="text" id="cp_project_name" placeholder="e.g., LGU Polo Shirts" class="w-full px-4 py-2.5 bg-gray-50 dark:bg-zinc-950 border border-gray-200 dark:border-zinc-800 text-gray-900 dark:text-white rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent outline-none transition-all text-sm font-medium">
                                    </div>
                                    <div class="col-span-2 md:col-span-1">
                                        <label class="block text-[10px] font-extrabold text-gray-500 uppercase tracking-widest mb-1">Due Date</label>
                                        <input type="date" id="cp_due_date" class="w-full px-4 py-2.5 bg-gray-50 dark:bg-zinc-950 border border-gray-200 dark:border-zinc-800 text-gray-900 dark:text-white rounded-lg focus:ring-2 focus:ring-pink-500 outline-none transition-all text-sm font-medium">
                                    </div>
                                </div>
                            </div>
                            <div class="bg-white dark:bg-zinc-900 p-5 rounded-xl border border-gray-100 dark:border-zinc-800 shadow-sm">
                                <h4 class="text-xs font-extrabold text-gray-500 dark:text-zinc-500 uppercase tracking-widest mb-3 border-b border-gray-100 dark:border-zinc-800 pb-2">Production Workflow</h4>
                                <div class="grid grid-cols-2 gap-4 mb-4">
                                    <label class="relative flex cursor-pointer rounded-xl border border-gray-200 dark:border-zinc-800 bg-gray-50 dark:bg-zinc-950 p-3 shadow-sm focus:outline-none has-[:checked]:border-pink-600 has-[:checked]:bg-pink-50 dark:has-[:checked]:bg-pink-900/10 transition-all">
                                        <input type="radio" name="workflow_type" value="customer" class="sr-only" checked onchange="toggleWorkflow()">
                                        <div class="flex items-center gap-3">
                                            <div class="text-pink-600 dark:text-pink-500 text-lg"><i class="fa-solid fa-users"></i></div>
                                            <div><p class="text-sm font-bold text-gray-900 dark:text-white">Make-to-Order</p></div>
                                        </div>
                                    </label>
                                    <label class="relative flex cursor-pointer rounded-xl border border-gray-200 dark:border-zinc-800 bg-gray-50 dark:bg-zinc-950 p-3 shadow-sm focus:outline-none has-[:checked]:border-amber-500 has-[:checked]:bg-amber-50 dark:has-[:checked]:bg-amber-900/10 transition-all">
                                        <input type="radio" name="workflow_type" value="internal" class="sr-only" onchange="toggleWorkflow()">
                                        <div class="flex items-center gap-3">
                                            <div class="text-amber-500 text-lg"><i class="fa-solid fa-boxes-stacked"></i></div>
                                            <div><p class="text-sm font-bold text-gray-900 dark:text-white">Make-to-Stock</p></div>
                                        </div>
                                    </label>
                                </div>
                                <div id="section-customer" class="space-y-4">
                                    <div class="flex justify-between items-center mb-1">
                                        <label class="block text-[10px] font-extrabold text-gray-500 uppercase tracking-widest">Select Client</label>
                                        <label class="flex items-center gap-2 cursor-pointer">
                                            <input type="checkbox" id="new-customer-toggle" class="rounded border-gray-300 text-pink-600 focus:ring-pink-500" onchange="toggleNewCustomer()">
                                            <span class="text-[10px] font-bold text-pink-600 dark:text-pink-500 uppercase tracking-widest">Insert New Client</span>
                                        </label>
                                    </div>
                                    <div id="existing-customer-div">
                                        <select id="cp_existing_customer" class="w-full px-4 py-2.5 bg-gray-50 dark:bg-zinc-950 border border-gray-200 dark:border-zinc-800 text-gray-900 dark:text-white rounded-lg focus:ring-2 focus:ring-pink-500 outline-none transition-all text-sm font-medium shadow-sm">
                                            <option value="">-- Choose Existing Client --</option>
                                            <?php foreach($customers as $c): ?>
                                                <option value="<?= $c['customer_id'] ?>"><?= htmlspecialchars($c['full_name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div id="new-customer-div" class="hidden space-y-3">
                                        <input type="text" id="cp_new_cust_name" placeholder="Full Name / Organization" class="w-full px-4 py-2.5 bg-gray-50 dark:bg-zinc-950 border border-gray-200 dark:border-zinc-800 text-gray-900 dark:text-white rounded-lg focus:ring-2 focus:ring-pink-500 outline-none text-sm shadow-sm">
                                        <div class="grid grid-cols-2 gap-3">
                                            <input type="text" id="cp_new_cust_contact" placeholder="Contact Number" class="w-full px-4 py-2.5 bg-gray-50 dark:bg-zinc-950 border border-gray-200 dark:border-zinc-800 text-gray-900 dark:text-white rounded-lg focus:ring-2 focus:ring-pink-500 outline-none text-sm shadow-sm">
                                            <input type="text" id="cp_new_cust_address" placeholder="Address (Optional)" class="w-full px-4 py-2.5 bg-gray-50 dark:bg-zinc-950 border border-gray-200 dark:border-zinc-800 text-gray-900 dark:text-white rounded-lg focus:ring-2 focus:ring-pink-500 outline-none text-sm shadow-sm">
                                        </div>
                                    </div>
                                </div>
                                <div id="section-internal" class="hidden space-y-2">
                                    <label class="block text-[10px] font-extrabold text-amber-600 dark:text-amber-500 uppercase tracking-widest">Target Product (Finished Goods)</label>
                                    <select id="cp_target_product" class="w-full px-4 py-2.5 bg-gray-50 dark:bg-zinc-950 border border-amber-200 dark:border-amber-800/50 text-gray-900 dark:text-white rounded-lg focus:ring-2 focus:ring-amber-500 outline-none transition-all text-sm font-medium shadow-sm">
                                        <option value="">-- Choose Product to Restock --</option>
                                        <?php foreach($products as $p): ?>
                                            <option value="<?= $p['product_id'] ?>"><?= htmlspecialchars($p['product_name']) ?> (Size: <?= htmlspecialchars($p['size']) ?>)</option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="lg:col-span-5 relative min-h-[400px] lg:min-h-0">
                            <div class="lg:absolute inset-0 bg-white dark:bg-zinc-900 p-5 rounded-xl border border-gray-100 dark:border-zinc-800 shadow-sm flex flex-col h-full">
                                <div class="flex justify-between items-center mb-3 border-b border-gray-100 dark:border-zinc-800 pb-2 flex-none">
                                    <h4 class="text-xs font-extrabold text-gray-500 dark:text-zinc-500 uppercase tracking-widest">Quantity & Sizing</h4>
                                    <label class="flex items-center gap-2 cursor-pointer" id="create_sizing_toggle_label">
                                        <input type="checkbox" id="enable-sizing-toggle" class="rounded border-gray-300 text-pink-600 focus:ring-pink-500" onchange="toggleSizingModule()">
                                        <span class="text-xs font-bold text-pink-600 dark:text-pink-500">Specify Sizes / Measurements</span>
                                    </label>
                                </div>
                                <div class="flex-none mb-4 relative">
                                    <label class="block text-[10px] font-extrabold text-gray-500 uppercase tracking-widest mb-1">Total Quantity</label>
                                    <input type="number" id="cp_total_quantity" min="1" value="1" class="w-1/2 px-4 py-2.5 bg-gray-50 dark:bg-zinc-950 border border-gray-200 dark:border-zinc-800 text-gray-900 dark:text-white rounded-lg focus:ring-2 focus:ring-pink-500 outline-none transition-all text-sm font-bold shadow-sm">
                                    <p id="qty-warning" class="hidden text-[9px] font-semibold text-amber-500 mt-1 leading-tight absolute left-0 -bottom-4">Auto-calculated from sizes.</p>
                                </div>
                                <div id="sizing-area" class="hidden flex-1 overflow-hidden flex flex-col border-t border-gray-100 dark:border-zinc-800 pt-3 min-h-0">
                                    <div class="flex gap-4 mb-3 flex-none">
                                        <label class="flex items-center gap-1 cursor-pointer text-xs font-bold text-gray-700 dark:text-zinc-300">
                                            <input type="radio" name="sizing_type" value="standard" checked onchange="switchSizingType()" class="text-pink-600 focus:ring-pink-500"> Standard
                                        </label>
                                        <label class="flex items-center gap-1 cursor-pointer text-xs font-bold text-gray-700 dark:text-zinc-300">
                                            <input type="radio" name="sizing_type" value="custom" onchange="switchSizingType()" class="text-pink-600 focus:ring-pink-500"> Custom
                                        </label>
                                    </div>
                                    <div class="flex-1 overflow-y-auto pr-1 min-h-0">
                                        <div id="standard-sizing-wrapper" class="space-y-2">
                                            <table class="w-full text-left relative">
                                                <thead class="sticky top-0 bg-white dark:bg-zinc-900 z-10 shadow-sm">
                                                    <tr class="text-[10px] font-extrabold text-gray-500 dark:text-zinc-400 uppercase tracking-widest border-b border-gray-200 dark:border-zinc-700">
                                                        <th class="pb-2 pt-1 w-3/5">Size Label</th>
                                                        <th class="pb-2 pt-1 w-1/4">Qty</th>
                                                        <th class="pb-2 pt-1 w-8"></th>
                                                    </tr>
                                                </thead>
                                                <tbody id="standard-sizing-tbody"></tbody>
                                            </table>
                                            <button type="button" onclick="addStandardSizeRow()" class="mt-3 text-[11px] font-bold text-pink-600 hover:text-pink-700 transition-colors focus:outline-none">
                                                <i class="fa-solid fa-plus bg-pink-100 p-1 rounded"></i> Add Size
                                            </button>
                                        </div>
                                        <div id="custom-sizing-wrapper" class="hidden space-y-2">
                                            <table class="w-full text-left relative">
                                                <thead class="sticky top-0 bg-white dark:bg-zinc-900 z-10 shadow-sm">
                                                    <tr class="text-[10px] font-extrabold text-gray-500 dark:text-zinc-400 uppercase tracking-widest border-b border-gray-200 dark:border-zinc-700">
                                                        <th class="pb-2 pt-1 w-2/5">Body Part</th>
                                                        <th class="pb-2 pt-1 w-1/4">Value</th>
                                                        <th class="pb-2 pt-1 w-1/4">Unit</th>
                                                        <th class="pb-2 pt-1 w-8"></th>
                                                    </tr>
                                                </thead>
                                                <tbody id="custom-sizing-tbody"></tbody>
                                            </table>
                                            <button type="button" onclick="addCustomMeasureRow()" class="mt-3 text-[11px] font-bold text-pink-600 hover:text-pink-700 transition-colors focus:outline-none">
                                                <i class="fa-solid fa-plus bg-pink-100 p-1 rounded"></i> Add Measurement
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="px-6 py-4 border-t border-gray-100 dark:border-zinc-800 bg-gray-50/50 dark:bg-zinc-950/30 flex justify-end gap-3 mt-auto">
                <button type="button" onclick="closeCreateProjectModal()" class="px-5 py-2.5 text-sm font-bold text-gray-600 dark:text-zinc-400 hover:bg-gray-100 dark:hover:bg-zinc-800 rounded-xl transition-colors focus:outline-none">Cancel</button>
                <button type="button" onclick="submitNewProject(false)" class="bg-white dark:bg-zinc-800 border border-gray-200 dark:border-zinc-700 text-gray-700 dark:text-zinc-300 hover:text-pink-600 hover:border-pink-200 px-5 py-2.5 rounded-xl transition-all text-sm font-bold shadow-sm focus:outline-none">Save Only</button>
                <button type="button" onclick="submitNewProject(true)" class="bg-pink-600 hover:bg-pink-700 text-white px-5 py-2.5 rounded-xl text-sm font-bold transition-all shadow-md shadow-pink-600/20 focus:outline-none flex items-center gap-2">Save & Proceed to Costing <i class="fa-solid fa-arrow-right"></i></button>
            </div>
        </div>
    </div>

    <div id="costing-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm transition-opacity" onclick="closeCostingModal()"></div>
        <div class="relative bg-white dark:bg-zinc-900 rounded-2xl w-full max-w-5xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh] border border-gray-100 dark:border-zinc-800">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-zinc-800 flex justify-between items-center bg-gray-50/50 dark:bg-zinc-950/30">
                <div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Project Costing Breakdown</h3>
                    <p id="modal-project-name" class="text-xs font-bold text-pink-600 dark:text-pink-500 uppercase tracking-widest mt-1">Project Name</p>
                </div>
                <button onclick="closeCostingModal()" class="text-gray-400 hover:text-rose-500 transition-colors focus:outline-none"><i class="fa-solid fa-xmark text-xl"></i></button>
            </div>
            <div class="p-6 overflow-y-auto flex-1">
                <form id="costing-form">
                    <input type="hidden" id="modal-project-id" name="project_id">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-gray-200 dark:border-zinc-700 text-[10px] font-extrabold text-gray-500 dark:text-zinc-400 uppercase tracking-widest">
                                <th class="pb-3 w-2/5">Raw Material</th>
                                <th class="pb-3 w-1/6">Qty Needed</th>
                                <th class="pb-3 w-1/12">UOM</th> 
                                <th class="pb-3 w-1/6">Unit Price (₱)</th>
                                <th class="pb-3 w-1/6 text-right">Total (₱)</th>
                                <th class="pb-3 w-8"></th>
                            </tr>
                        </thead>
                        <tbody id="costing-tbody" class="text-sm"></tbody>
                    </table>
                    <button type="button" onclick="addCostingRow()" class="mt-4 text-xs font-bold text-pink-600 dark:text-pink-500 hover:text-pink-700 dark:hover:text-pink-400 flex items-center gap-1.5 transition-colors focus:outline-none cursor-pointer">
                        <i class="fa-solid fa-plus bg-pink-100 dark:bg-pink-900/30 p-1 rounded"></i> Add Material
                    </button>
                </form>
            </div>
            <div class="px-6 py-4 border-t border-gray-100 dark:border-zinc-800 bg-gray-50/50 dark:bg-zinc-950/30 flex justify-between items-center">
                <div class="flex gap-8 items-center">
                    <div class="flex flex-col">
                        <span class="text-[10px] font-extrabold text-gray-500 dark:text-zinc-500 uppercase tracking-widest">Total Material Cost</span>
                        <span id="grand-total-display" class="text-xl font-extrabold text-gray-900 dark:text-white">₱ 0.00</span>
                    </div>
                    <div class="flex flex-col relative">
                        <span id="costing-price-label" class="text-[10px] font-extrabold text-pink-600 dark:text-pink-500 uppercase tracking-widest">Agreed Price (Charge)</span>
                        <div class="relative mt-0.5">
                            <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-900 dark:text-white font-bold text-sm">₱</span>
                            <input type="number" id="modal-agreed-price" step="0.01" value="0.00" class="w-32 bg-white dark:bg-zinc-900 border border-gray-300 dark:border-zinc-700 text-gray-900 dark:text-white text-lg font-extrabold rounded-lg pl-7 pr-3 py-1 outline-none focus:border-pink-500 shadow-sm" oninput="calculateProfitUI()">
                        </div>
                    </div>
                    <div class="flex flex-col border-l border-gray-200 dark:border-zinc-700 pl-8">
                        <span class="text-[10px] font-extrabold text-gray-500 dark:text-zinc-500 uppercase tracking-widest">Est. Profit</span>
                        <span id="est-profit-display" class="text-xl font-extrabold text-gray-400">₱ 0.00</span>
                    </div>
                </div>
                <div class="flex gap-2">
                    <button type="button" id="btn-delete-costing" onclick="deleteCostingBreakdown()" class="hidden bg-rose-100 hover:bg-rose-200 text-rose-600 px-4 py-2.5 rounded-xl text-sm font-bold transition-all focus:outline-none"><i class="fa-solid fa-eraser"></i> Clear Breakdown</button>
                    <button type="button" onclick="saveCosting()" class="bg-pink-600 hover:bg-pink-700 text-white px-6 py-2.5 rounded-xl text-sm font-bold transition-all shadow-md shadow-pink-600/20 cursor-pointer focus:outline-none"><i class="fa-solid fa-floppy-disk mr-1.5"></i> Save All</button>
                </div>
            </div>
        </div>
    </div>

    <div id="notes-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm transition-opacity" onclick="closeNotesModal()"></div>
        <div class="relative bg-white dark:bg-zinc-900 rounded-2xl w-full max-w-md shadow-2xl overflow-hidden flex flex-col border border-gray-100 dark:border-zinc-800">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-zinc-800 flex justify-between items-center bg-gray-50/50 dark:bg-zinc-950/30">
                <h3 class="text-lg font-bold text-amber-600"><i class="fa-solid fa-triangle-exclamation mr-2"></i> Project Notes</h3>
                <button onclick="closeNotesModal()" class="text-gray-400 hover:text-rose-500 focus:outline-none"><i class="fa-solid fa-xmark text-xl"></i></button>
            </div>
            <div class="p-6">
                <input type="hidden" id="note_project_id">
                <label class="block text-xs font-bold text-gray-600 dark:text-zinc-400 mb-2 uppercase">Reason for Delay / Overdue Notes</label>
                <textarea id="note_text" rows="4" placeholder="Explain why the project is delayed..." class="w-full px-4 py-3 bg-gray-50 dark:bg-zinc-950 border border-gray-200 dark:border-zinc-800 text-gray-900 dark:text-white rounded-xl focus:ring-2 focus:ring-amber-500 outline-none text-sm font-medium"></textarea>
            </div>
            <div class="px-6 py-4 border-t border-gray-100 dark:border-zinc-800 flex justify-end gap-3">
                <button onclick="closeNotesModal()" class="px-5 py-2.5 text-sm font-bold text-gray-600 hover:bg-gray-100 dark:hover:bg-zinc-800 rounded-xl">Cancel</button>
                <button onclick="saveNotes()" class="bg-amber-500 hover:bg-amber-600 text-white px-5 py-2.5 rounded-xl text-sm font-bold shadow-md">Save Notes</button>
            </div>
        </div>
    </div>

    <div id="view-details-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm transition-opacity" onclick="closeViewDetailsModal()"></div>
        <div class="relative bg-white dark:bg-zinc-900 rounded-2xl w-full max-w-5xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh] border border-gray-100 dark:border-zinc-800">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-zinc-800 flex justify-between items-center bg-gray-50/50 dark:bg-zinc-950/30">
                <div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Project Details & Configuration</h3>
                    <p class="text-xs font-bold text-pink-600 dark:text-pink-500 uppercase tracking-widest mt-1" id="vd_project_id">#PRJ-000</p>
                </div>
                <button onclick="closeViewDetailsModal()" class="text-gray-400 hover:text-rose-500 transition-colors focus:outline-none">
                    <i class="fa-solid fa-xmark text-xl"></i>
                </button>
            </div>
            <div class="p-6 overflow-y-auto flex-1 bg-gray-50/30 dark:bg-zinc-950/30">
                <input type="hidden" id="edit_project_id">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 relative">
                    <div class="lg:col-span-7 flex flex-col gap-5">
                        <div class="bg-white dark:bg-zinc-900 p-5 rounded-xl border border-gray-100 dark:border-zinc-800 shadow-sm">
                            <h4 class="text-xs font-extrabold text-gray-500 dark:text-zinc-500 uppercase tracking-widest mb-3 border-b border-gray-100 dark:border-zinc-800 pb-2">Client / Target</h4>
                            <div id="vd_client_info" class="text-sm text-gray-800 dark:text-zinc-200 font-medium"></div>
                        </div>
                        <div class="bg-white dark:bg-zinc-900 p-5 rounded-xl border border-gray-100 dark:border-zinc-800 shadow-sm space-y-4">
                            <h4 class="text-xs font-extrabold text-gray-500 dark:text-zinc-500 uppercase tracking-widest mb-3 border-b border-gray-100 dark:border-zinc-800 pb-2">Project Configuration</h4>
                            <div>
                                <label class="block text-[10px] font-extrabold text-gray-500 uppercase tracking-widest mb-1">Project Name</label>
                                <input type="text" id="edit_project_name" class="w-full px-4 py-2.5 bg-gray-50 dark:bg-zinc-950 border border-gray-200 dark:border-zinc-800 rounded-lg text-sm font-bold text-gray-900 dark:text-white outline-none focus:border-pink-500 transition-all">
                            </div>
                            <div class="flex gap-4">
                                <div class="w-1/2 relative">
                                    <label class="block text-[10px] font-extrabold text-gray-500 uppercase tracking-widest mb-1">Total Quantity</label>
                                    <input type="number" id="edit_quantity" min="1" class="w-full px-4 py-2.5 bg-gray-50 dark:bg-zinc-950 border border-gray-200 dark:border-zinc-800 rounded-lg text-sm font-bold text-gray-900 dark:text-white outline-none focus:border-pink-500 transition-all" oninput="overrideEditQuantity()">
                                    <p id="edit_qty_warning" class="hidden text-[9px] font-semibold text-amber-500 mt-1 leading-tight absolute -bottom-4 left-0">Auto-calculated from sizes.</p>
                                </div>
                                <div class="w-1/2">
                                    <label class="block text-[10px] font-extrabold text-gray-500 uppercase tracking-widest mb-1">Due Date</label>
                                    <input type="date" id="edit_due_date" class="w-full px-4 py-2.5 bg-gray-50 dark:bg-zinc-950 border border-gray-200 dark:border-zinc-800 rounded-lg text-sm font-bold text-gray-900 dark:text-white outline-none focus:border-pink-500 transition-all">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="lg:col-span-5 flex flex-col gap-5 min-h-[400px] lg:min-h-0">
                        <div class="bg-white dark:bg-zinc-900 p-5 rounded-xl border border-gray-100 dark:border-zinc-800 shadow-sm flex flex-col flex-1 min-h-0">
                            <div class="flex justify-between items-center mb-3 border-b border-gray-100 dark:border-zinc-800 pb-2 flex-none">
                                <h4 class="text-xs font-extrabold text-gray-500 dark:text-zinc-500 uppercase tracking-widest">Sizing & Measurements</h4>
                                <label class="flex items-center gap-2 cursor-pointer" id="edit_sizing_toggle_label">
                                    <input type="checkbox" id="edit_enable_sizing_toggle" class="rounded border-gray-300 text-pink-600 focus:ring-pink-500" onchange="toggleEditSizingModule()">
                                    <span class="text-[10px] font-bold text-pink-600 dark:text-pink-500 uppercase tracking-widest">Enable Sizing</span>
                                </label>
                            </div>
                            <div id="edit_sizing_area" class="hidden flex-1 overflow-hidden flex flex-col min-h-0">
                                <div class="flex gap-4 mb-4 flex-none">
                                    <label class="flex items-center gap-1 cursor-pointer text-xs font-bold text-gray-700 dark:text-zinc-300">
                                        <input type="radio" name="edit_sizing_type" value="standard" onchange="switchEditSizingType()" class="text-pink-600 focus:ring-pink-500"> Standard
                                    </label>
                                    <label class="flex items-center gap-1 cursor-pointer text-xs font-bold text-gray-700 dark:text-zinc-300">
                                        <input type="radio" name="edit_sizing_type" value="custom" onchange="switchEditSizingType()" class="text-pink-600 focus:ring-pink-500"> Custom
                                    </label>
                                </div>
                                <div class="flex-1 overflow-y-auto pr-1 min-h-0">
                                    <div id="edit_standard_wrapper" class="space-y-2">
                                        <table class="w-full text-left relative">
                                            <thead class="sticky top-0 bg-white dark:bg-zinc-900 z-10 shadow-sm">
                                                <tr class="text-[10px] font-extrabold text-gray-500 dark:text-zinc-400 uppercase tracking-widest border-b border-gray-200 dark:border-zinc-700">
                                                    <th class="pb-2 pt-1 w-3/5">Size Label</th>
                                                    <th class="pb-2 pt-1 w-1/4">Qty</th>
                                                    <th class="pb-2 pt-1 w-8"></th>
                                                </tr>
                                            </thead>
                                            <tbody id="edit_standard_tbody"></tbody>
                                        </table>
                                        <button type="button" onclick="addEditStandardRow()" class="mt-3 text-[11px] font-bold text-pink-600 hover:text-pink-700 transition-colors focus:outline-none">
                                            <i class="fa-solid fa-plus bg-pink-100 p-1 rounded"></i> Add Size
                                        </button>
                                    </div>
                                    <div id="edit_custom_wrapper" class="hidden space-y-2">
                                        <table class="w-full text-left relative">
                                            <thead class="sticky top-0 bg-white dark:bg-zinc-900 z-10 shadow-sm">
                                                <tr class="text-[10px] font-extrabold text-gray-500 dark:text-zinc-400 uppercase tracking-widest border-b border-gray-200 dark:border-zinc-700">
                                                    <th class="pb-2 pt-1 w-2/5">Body Part</th>
                                                    <th class="pb-2 pt-1 w-1/4">Value</th>
                                                    <th class="pb-2 pt-1 w-1/4">Unit</th>
                                                    <th class="pb-2 pt-1 w-8"></th>
                                                </tr>
                                            </thead>
                                            <tbody id="edit_custom_tbody"></tbody>
                                        </table>
                                        <button type="button" onclick="addEditCustomRow()" class="mt-3 text-[11px] font-bold text-pink-600 hover:text-pink-700 transition-colors focus:outline-none">
                                            <i class="fa-solid fa-plus bg-pink-100 p-1 rounded"></i> Add Measurement
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div id="vd_shortages_section" class="hidden bg-rose-50 dark:bg-rose-900/10 p-4 rounded-xl border border-rose-200 dark:border-rose-800/50 shadow-sm shrink-0">
                            <h4 class="text-[11px] font-extrabold text-rose-600 dark:text-rose-500 uppercase tracking-widest mb-2 flex items-center gap-1.5 border-b border-rose-200/50 dark:border-rose-800/50 pb-2">
                                <i class="fa-solid fa-triangle-exclamation"></i> Insufficient Materials
                            </h4>
                            <div id="vd_shortages_list" class="space-y-2 mt-2 max-h-48 overflow-y-auto pr-1">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-gray-100 dark:border-zinc-800 bg-gray-50/50 dark:bg-zinc-950/30 flex justify-between items-center mt-auto">
                <div id="vd_action_start_production">
                </div>
                <div class="flex gap-3">
                    <button onclick="closeViewDetailsModal()" class="px-5 py-2.5 text-sm font-bold text-gray-600 dark:text-zinc-400 hover:bg-gray-100 dark:hover:bg-zinc-800 rounded-xl transition-colors focus:outline-none">Cancel</button>
                    <button onclick="saveProjectUpdates()" id="btn-update-project" class="bg-pink-600 hover:bg-pink-700 text-white px-5 py-2.5 rounded-xl text-sm font-bold transition-all shadow-md focus:outline-none">Update Project Details</button>
                </div>
            </div>
        </div>
    </div>
    
    <div id="internal-stock-modal" class="fixed inset-0 z-[70] hidden flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm transition-opacity"></div>
        <div class="relative bg-white dark:bg-zinc-900 rounded-2xl w-full max-w-sm shadow-2xl overflow-hidden flex flex-col border border-emerald-100 dark:border-emerald-900/30">
            <div class="p-6 text-center">
                <div class="w-16 h-16 bg-emerald-100 dark:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 rounded-full flex items-center justify-center mx-auto mb-4 text-2xl border border-emerald-200 dark:border-emerald-500/30">
                    <i class="fa-solid fa-boxes-packing"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Restock Complete!</h3>
                <p class="text-sm font-medium text-gray-600 dark:text-zinc-400 leading-relaxed" id="internal_stock_msg"></p>
            </div>
            <div class="px-6 py-4 border-t border-gray-100 dark:border-zinc-800 bg-gray-50/50 dark:bg-zinc-950/30 flex justify-center">
                <button onclick="closeInternalStockModal()" class="bg-emerald-500 hover:bg-emerald-600 text-white px-8 py-2.5 rounded-xl text-sm font-bold shadow-md shadow-emerald-500/20 focus:outline-none transition-all">Awesome</button>
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
    // 0. GLOBAL UI OVERRIDES (REPLACING NATIVE ALERTS/CONFIRMS)
    // ==========================================
    
    // 🚨 BUG FIX: Global timers to prevent modals from hiding each other
    let globalAlertTimeout;
    let globalConfirmTimeout;

    function customAlert(message, title = "Notice", type = "info") {
        const modal = document.getElementById('global-alert-modal');
        const box = document.getElementById('global-alert-box');
        const msgEl = document.getElementById('global-alert-msg');
        const titleEl = document.getElementById('global-alert-title');
        const iconWrapper = document.getElementById('global-alert-icon-wrapper');
        const icon = document.getElementById('global-alert-icon');

        msgEl.textContent = message;
        titleEl.textContent = title;

        // Theme the alert based on type
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

        clearTimeout(globalAlertTimeout); // Prevent previous close animations from hiding this
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
        globalAlertTimeout = setTimeout(() => modal.classList.add('hidden'), 200);
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
            } else {
                iconWrapper.className += "bg-amber-100 dark:bg-amber-500/20 text-amber-600 dark:text-amber-400 border-amber-200 dark:border-amber-500/30";
                icon.className = "fa-solid fa-triangle-exclamation";
                btnOk.className += "bg-amber-500 hover:bg-amber-600 shadow-amber-500/20";
            }

            clearTimeout(globalConfirmTimeout); // Prevent previous close animations from hiding this
            modal.classList.remove('hidden');
            setTimeout(() => {
                box.classList.remove('scale-95', 'opacity-0');
                box.classList.add('scale-100', 'opacity-100');
            }, 10);

            const cleanupAndResolve = (result) => {
                box.classList.remove('scale-100', 'opacity-100');
                box.classList.add('scale-95', 'opacity-0');
                
                globalConfirmTimeout = setTimeout(() => modal.classList.add('hidden'), 200);
                
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

    // --- Pagination & Search Logic ---
    const searchInput = document.getElementById('search-input');
    const tbody = document.getElementById('project-tbody');
    const allRows = Array.from(tbody.querySelectorAll('tr.project-row'));
    const paginationContainer = document.getElementById('pagination-container');
    const colspanCount = 5;
    
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
        if (totalItems === 0) {
            if (!existingEmptyRow) {
                tbody.insertAdjacentHTML('beforeend', `<tr id="js-empty-state"><td colspan="${colspanCount}" class="px-6 py-8 text-center text-gray-500 font-medium">No projects found matching your search.</td></tr>`);
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
                    <button onclick="changePage(event, ${currentPage - 1})" class="px-3 py-1.5 text-xs font-bold rounded-lg transition-colors ${currentPage === 1 ? 'text-gray-400 dark:text-zinc-600 cursor-not-allowed' : 'text-gray-700 dark:text-zinc-300 hover:bg-gray-200 dark:hover:bg-zinc-800'}" ${currentPage === 1 ? 'disabled' : ''}>Prev</button>
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
                    <button onclick="changePage(event, ${currentPage + 1})" class="px-3 py-1.5 text-xs font-bold rounded-lg transition-colors ${currentPage === totalPages ? 'text-gray-400 dark:text-zinc-600 cursor-not-allowed' : 'text-gray-700 dark:text-zinc-300 hover:bg-gray-200 dark:hover:bg-zinc-800'}" ${currentPage === totalPages ? 'disabled' : ''}>Next</button>
                </div>
            </div>
        `;
        paginationContainer.innerHTML = html;
    }

    function makePageBtn(i) {
        const activeClass = i === currentPage 
            ? 'bg-pink-600 text-white shadow-md shadow-pink-600/20' 
            : 'text-gray-700 dark:text-zinc-300 hover:bg-gray-200 dark:hover:bg-zinc-800';
        return `<button onclick="changePage(event, ${i})" class="px-3 py-1.5 text-xs font-bold rounded-lg transition-colors ${activeClass}">${i}</button>`;
    }

    function changePage(event, page) {
        event.stopPropagation(); 
        currentPage = page;
        updateTable();
    }

    searchInput.addEventListener('input', () => {
        currentPage = 1; 
        updateTable();
    });

    updateTable();

    // ----------------------------------------------------

    let isNewProjectFlow = false; 

    // ==========================================
    // 1. CREATE PROJECT WIZARD LOGIC
    // ==========================================
    function openCreateProjectModal() { document.getElementById('create-project-modal').classList.remove('hidden'); }
    function closeCreateProjectModal() { document.getElementById('create-project-modal').classList.add('hidden'); }

    function toggleWorkflow() {
        const isCustomer = document.querySelector('input[name="workflow_type"][value="customer"]').checked;
        const sizingToggleLabel = document.getElementById('create_sizing_toggle_label');
        const sizingCheckbox = document.getElementById('enable-sizing-toggle');

        if (isCustomer) {
            document.getElementById('section-customer').classList.remove('hidden');
            document.getElementById('section-internal').classList.add('hidden');
            if(sizingToggleLabel) sizingToggleLabel.classList.remove('hidden');
        } else {
            document.getElementById('section-customer').classList.add('hidden');
            document.getElementById('section-internal').classList.remove('hidden');
            if(sizingToggleLabel) sizingToggleLabel.classList.add('hidden');
            if(sizingCheckbox) sizingCheckbox.checked = false;
            toggleSizingModule(); 
        }
    }

    function toggleNewCustomer() {
        const isNew = document.getElementById('new-customer-toggle').checked;
        if (isNew) {
            document.getElementById('existing-customer-div').classList.add('hidden');
            document.getElementById('new-customer-div').classList.remove('hidden');
        } else {
            document.getElementById('existing-customer-div').classList.remove('hidden');
            document.getElementById('new-customer-div').classList.add('hidden');
        }
    }

    function toggleSizingModule() {
        const isChecked = document.getElementById('enable-sizing-toggle').checked;
        const sizingArea = document.getElementById('sizing-area');
        if (isChecked) {
            sizingArea.classList.remove('hidden');
            const type = document.querySelector('input[name="sizing_type"]:checked').value;
            if(type === 'standard' && document.getElementById('standard-sizing-tbody').children.length === 0) addStandardSizeRow();
            if(type === 'custom' && document.getElementById('custom-sizing-tbody').children.length === 0) addCustomMeasureRow();
            switchSizingType(); 
        } else {
            sizingArea.classList.add('hidden');
            const qtyInput = document.getElementById('cp_total_quantity');
            qtyInput.readOnly = false;
            qtyInput.classList.remove('bg-gray-100', 'dark:bg-zinc-800', 'text-gray-500');
            document.getElementById('qty-warning').classList.add('hidden');
        }
    }

    function switchSizingType() {
        const type = document.querySelector('input[name="sizing_type"]:checked').value;
        const standardWrapper = document.getElementById('standard-sizing-wrapper');
        const customWrapper = document.getElementById('custom-sizing-wrapper');
        const qtyInput = document.getElementById('cp_total_quantity');
        const qtyWarning = document.getElementById('qty-warning');

        if (type === 'standard') {
            standardWrapper.classList.remove('hidden');
            customWrapper.classList.add('hidden');
            qtyInput.readOnly = true;
            qtyInput.classList.add('bg-gray-100', 'dark:bg-zinc-800', 'text-gray-500');
            qtyWarning.classList.remove('hidden');
            calculateTotalStandardQuantity();
        } else {
            standardWrapper.classList.add('hidden');
            customWrapper.classList.remove('hidden');
            qtyInput.readOnly = false;
            qtyInput.classList.remove('bg-gray-100', 'dark:bg-zinc-800', 'text-gray-500');
            qtyWarning.classList.add('hidden');
        }
    }

    function addStandardSizeRow() {
        const tbody = document.getElementById('standard-sizing-tbody');
        const tr = document.createElement('tr');
        tr.className = "border-b border-gray-100 dark:border-zinc-800/50";
        tr.innerHTML = `
            <td class="py-2 pr-2"><input type="text" placeholder="e.g., Medium" class="sizing-label w-full px-3 py-1.5 bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 text-gray-900 dark:text-white rounded-lg text-sm outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent transition-all"></td>
            <td class="py-2 pr-2"><input type="number" min="1" value="1" class="sizing-qty w-full px-3 py-1.5 bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 text-gray-900 dark:text-white rounded-lg text-sm outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent transition-all" oninput="calculateTotalStandardQuantity()"></td>
            <td class="py-2 text-right"><button type="button" onclick="this.closest('tr').remove(); calculateTotalStandardQuantity();" class="text-gray-400 hover:text-rose-500 focus:outline-none p-1"><i class="fa-solid fa-trash text-[10px]"></i></button></td>
        `;
        tbody.appendChild(tr);
        calculateTotalStandardQuantity();
    }

    function calculateTotalStandardQuantity() {
        if (!document.getElementById('enable-sizing-toggle').checked) return;
        if (document.querySelector('input[name="sizing_type"]:checked').value !== 'standard') return;
        let total = 0;
        document.querySelectorAll('#standard-sizing-tbody .sizing-qty').forEach(input => total += parseInt(input.value) || 0);
        document.getElementById('cp_total_quantity').value = total > 0 ? total : 1;
    }

    function addCustomMeasureRow() {
        const tbody = document.getElementById('custom-sizing-tbody');
        const tr = document.createElement('tr');
        tr.className = "border-b border-gray-100 dark:border-zinc-800/50";
        tr.innerHTML = `
            <td class="py-2 pr-2"><input type="text" placeholder="e.g., Chest" class="measure-part w-full px-3 py-1.5 bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 text-gray-900 dark:text-white rounded-lg text-sm outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent transition-all"></td>
            <td class="py-2 pr-2"><input type="number" step="0.25" placeholder="0.00" class="measure-val w-full px-3 py-1.5 bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 text-gray-900 dark:text-white rounded-lg text-sm outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent transition-all"></td>
            <td class="py-2 pr-2"><select class="measure-unit w-full px-3 py-1.5 bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 text-gray-900 dark:text-white rounded-lg text-sm outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent transition-all"><option value="inches">inches</option><option value="cm">cm</option></select></td>
            <td class="py-2 text-right"><button type="button" onclick="this.closest('tr').remove();" class="text-gray-400 hover:text-rose-500 focus:outline-none p-1"><i class="fa-solid fa-trash text-[10px]"></i></button></td>
        `;
        tbody.appendChild(tr);
    }

    async function submitNewProject(proceedToCosting) {
        const formData = new FormData();
        formData.append('project_name', document.getElementById('cp_project_name').value);
        formData.append('quantity', document.getElementById('cp_total_quantity').value);
        formData.append('due_date', document.getElementById('cp_due_date').value);
        formData.append('agreed_price', 0.00); 

        if (document.getElementById('enable-sizing-toggle').checked) {
            const sizingType = document.querySelector('input[name="sizing_type"]:checked').value;
            formData.append('sizing_type', sizingType);
            let sizingData = [];
            if (sizingType === 'standard') {
                document.querySelectorAll('#standard-sizing-tbody tr').forEach(row => {
                    sizingData.push({ label: row.querySelector('.sizing-label').value, qty: row.querySelector('.sizing-qty').value });
                });
            } else {
                document.querySelectorAll('#custom-sizing-tbody tr').forEach(row => {
                    sizingData.push({ part: row.querySelector('.measure-part').value, val: row.querySelector('.measure-val').value, unit: row.querySelector('.measure-unit').value });
                });
            }
            formData.append('sizing_data', JSON.stringify(sizingData));
        }

        const workflowType = document.querySelector('input[name="workflow_type"]:checked').value;
        formData.append('workflow_type', workflowType);

        if (workflowType === 'customer') {
            const isNewCustomer = document.getElementById('new-customer-toggle').checked;
            formData.append('is_new_customer', isNewCustomer);
            if (isNewCustomer) {
                formData.append('new_customer_name', document.getElementById('cp_new_cust_name').value);
                formData.append('new_customer_contact', document.getElementById('cp_new_cust_contact').value);
                formData.append('new_customer_address', document.getElementById('cp_new_cust_address').value);
            } else {
                formData.append('existing_customer_id', document.getElementById('cp_existing_customer').value);
            }
        } else {
            formData.append('target_product_id', document.getElementById('cp_target_product').value);
        }

        try {
            const response = await fetch('actions/save_project.php', { method: 'POST', body: formData });
            const rawText = await response.text(); 
            
            try {
                const result = JSON.parse(rawText);
                if (result.status === 'success') {
                    closeCreateProjectModal();
                    if (proceedToCosting) {
                        const isInternal = (workflowType === 'internal');
                        const calculatedPrice = result.agreed_price ? result.agreed_price : 0;
                        isNewProjectFlow = true; 
                        openCostingModal(result.project_id, result.project_name, calculatedPrice, isInternal);
                    } else {
                        customAlert("Project created successfully!", "Success", "success");
                        setTimeout(() => window.location.reload(), 1500);
                    }
                } else {
                    customAlert('Database Logic Error: ' + result.message, "Error", "error");
                }
            } catch (jsonError) {
                console.error("Raw Server Response:", rawText);
                customAlert("PHP Error in save_project.php:\n\n" + rawText.substring(0, 500), "Server Error", "error");
            }
        } catch (error) { 
            customAlert('True Network Fetch Error: ' + error.message, "Network Error", "error"); 
        }
    }

    // ==========================================
    // 2. COSTING, PROFIT & CURRENCY LOGIC
    // ==========================================
    function formatCurrency(number) { return parseFloat(number).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }
    const rawMaterials = <?php echo $materials_json; ?>;

    async function openCostingModal(projectId, projectName, currentAgreedPrice = 0, isInternal = false) {
        document.getElementById('modal-project-id').value = projectId;
        document.getElementById('modal-project-name').textContent = projectName;
        
        const priceLabel = document.getElementById('costing-price-label');
        const priceInput = document.getElementById('modal-agreed-price');

        if (isInternal) {
            if(priceLabel) {
                priceLabel.textContent = 'Expected Retail Value';
                priceLabel.className = 'text-[10px] font-extrabold text-amber-500 uppercase tracking-widest';
            }
            if(priceInput) {
                priceInput.readOnly = true;
                priceInput.title = "Calculated automatically from product retail price. Cannot be edited here.";
                priceInput.classList.add('bg-gray-100', 'dark:bg-zinc-800', 'text-gray-500', 'cursor-not-allowed');
            }
        } else {
            if(priceLabel) {
                priceLabel.textContent = 'Agreed Price (Charge)';
                priceLabel.className = 'text-[10px] font-extrabold text-pink-600 dark:text-pink-500 uppercase tracking-widest';
            }
            if(priceInput) {
                priceInput.readOnly = false;
                priceInput.title = "";
                priceInput.classList.remove('bg-gray-100', 'dark:bg-zinc-800', 'text-gray-500', 'cursor-not-allowed');
            }
        }

        if(priceInput) priceInput.value = parseFloat(currentAgreedPrice).toFixed(2);
        
        const tbody = document.getElementById('costing-tbody');
        const deleteBtn = document.getElementById('btn-delete-costing');
        
        tbody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-sm text-gray-500"><i class="fa-solid fa-spinner fa-spin"></i> Loading...</td></tr>';
        document.getElementById('costing-modal').classList.remove('hidden');
        
        try {
            const response = await fetch(`actions/get_costing.php?project_id=${projectId}`);
            const result = await response.json();
            tbody.innerHTML = ''; 
            if (result.status === 'success' && result.data.length > 0) {
                result.data.forEach(item => addCostingRow(item.material_id, item.quantity_used, item.unit_cost));
                if(deleteBtn) deleteBtn.classList.remove('hidden');
            } else {
                addCostingRow();
                if(deleteBtn) deleteBtn.classList.add('hidden');
            }
            calculateGrandTotal();
        } catch (error) { tbody.innerHTML = ''; addCostingRow(); }
    }

    function addCostingRow(prefillMatId = "", prefillQty = 1, prefillPrice = "0.00") {
        const tbody = document.getElementById('costing-tbody');
        let optionsHtml = '<option value="" data-uom="--">Select Material...</option>';
        let selectedUom = '--';

        rawMaterials.forEach(mat => {
            const isSelected = (mat.material_id == prefillMatId) ? 'selected' : '';
            if (isSelected) selectedUom = mat.unit_of_measure;
            optionsHtml += `<option value="${mat.material_id}" data-price="${mat.current_price}" data-uom="${mat.unit_of_measure}" ${isSelected}>${mat.material_name}</option>`;
        });

        const tr = document.createElement('tr');
        tr.className = "border-b border-gray-50 dark:border-zinc-800/50 group";
        tr.innerHTML = `
            <td class="py-3 pr-4"><select class="w-full bg-gray-50 dark:bg-zinc-950 border border-gray-200 dark:border-zinc-800 text-gray-900 dark:text-white text-sm font-medium rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent p-2.5 outline-none transition-all shadow-sm" onchange="updateRowData(this)">${optionsHtml}</select></td>
            <td class="py-3 pr-4"><input type="number" min="1" value="${prefillQty}" class="qty-input w-full bg-gray-50 dark:bg-zinc-950 border border-gray-200 dark:border-zinc-800 text-gray-900 dark:text-white text-sm font-bold rounded-lg p-2.5 outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent transition-all shadow-sm" oninput="calculateRowTotal(this)"></td>
            <td class="py-3 pr-4"><span class="uom-display text-[10px] font-extrabold text-gray-400 dark:text-zinc-500 uppercase tracking-widest bg-gray-100 dark:bg-zinc-800 px-2 py-1.5 rounded-md">${selectedUom}</span></td>
            <td class="py-3 pr-4 relative"><span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 font-bold text-sm">₱</span><input type="number" step="0.01" value="${parseFloat(prefillPrice).toFixed(2)}" class="price-input w-full bg-gray-50 dark:bg-zinc-950 border border-gray-200 dark:border-zinc-800 text-gray-900 dark:text-white text-sm font-bold rounded-lg pl-8 p-2.5 outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent transition-all shadow-sm" oninput="calculateRowTotal(this)"></td>
            <td class="py-3 text-right font-extrabold text-gray-900 dark:text-white row-total-display text-sm">₱ ${formatCurrency(prefillQty * prefillPrice)}</td>
            <td class="py-3 text-right"><button type="button" onclick="this.closest('tr').remove(); calculateGrandTotal();" class="text-gray-300 hover:text-rose-500 dark:text-zinc-600 dark:hover:text-rose-500 transition-colors focus:outline-none p-2 opacity-0 group-hover:opacity-100"><i class="fa-solid fa-trash"></i></button></td>
        `;
        tbody.appendChild(tr);
    }

    function closeCostingModal() { 
        document.getElementById('costing-modal').classList.add('hidden'); 
        if (isNewProjectFlow) {
            window.location.reload();
        }
    }

    function updateRowData(selectElement) {
        const opt = selectElement.options[selectElement.selectedIndex];
        const row = selectElement.closest('tr');
        row.querySelector('.price-input').value = parseFloat(opt.getAttribute('data-price') || 0).toFixed(2);
        row.querySelector('.uom-display').textContent = opt.getAttribute('data-uom') || '--';
        calculateRowTotal(row.querySelector('.price-input'));
    }

    function calculateRowTotal(inputElement) {
        const row = inputElement.closest('tr');
        const total = (parseFloat(row.querySelector('.qty-input').value) || 0) * (parseFloat(row.querySelector('.price-input').value) || 0);
        row.querySelector('.row-total-display').textContent = '₱ ' + formatCurrency(total);
        calculateGrandTotal();
    }

    function calculateGrandTotal() {
        let grandTotal = 0;
        document.querySelectorAll('#costing-tbody tr').forEach(row => {
            grandTotal += (parseFloat(row.querySelector('.qty-input').value) || 0) * (parseFloat(row.querySelector('.price-input').value) || 0);
        });
        document.getElementById('grand-total-display').textContent = '₱ ' + formatCurrency(grandTotal);
        calculateProfitUI(); 
    }

    function calculateProfitUI() {
        const totalCost = parseFloat(document.getElementById('grand-total-display').textContent.replace('₱', '').replace(/,/g, '').trim()) || 0;
        const agreedPriceInput = document.getElementById('modal-agreed-price');
        if(!agreedPriceInput) return; 
        const agreedPrice = parseFloat(agreedPriceInput.value) || 0;
        const profit = agreedPrice - totalCost;
        const profitDisplay = document.getElementById('est-profit-display');
        if(profitDisplay) {
            profitDisplay.textContent = '₱ ' + formatCurrency(profit);
            if (profit > 0) profitDisplay.className = "text-xl font-extrabold text-emerald-500";
            else if (agreedPrice === 0) profitDisplay.className = "text-xl font-extrabold text-gray-400";
            else profitDisplay.className = "text-xl font-extrabold text-rose-500";
        }
    }

    async function saveCosting() {
        const projectId = document.getElementById('modal-project-id').value;
        const agreedPriceInput = document.getElementById('modal-agreed-price');
        const agreedPrice = agreedPriceInput && !agreedPriceInput.readOnly ? agreedPriceInput.value : 0;
        let materialsData = [];
        document.querySelectorAll('#costing-tbody tr').forEach(row => {
            const materialId = row.querySelector('select').value;
            if (materialId !== "") {
                materialsData.push({ material_id: materialId, quantity: row.querySelector('.qty-input').value, unit_price: row.querySelector('.price-input').value });
            }
        });

        if (materialsData.length === 0 && agreedPrice == 0) return customAlert("Please add at least one material or set a valid Agreed Price.", "Missing Data", "error");

        const saveBtn = document.querySelector('button[onclick="saveCosting()"]');
        const originalBtnHtml = saveBtn.innerHTML;
        try {
            saveBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1.5"></i> Saving...';
            saveBtn.disabled = true;
            const response = await fetch('actions/save_costing.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ project_id: projectId, agreed_price: agreedPrice, materials: materialsData }) });
            const result = JSON.parse(await response.text());
            if (result.status === 'success') { 
                closeCostingModal(); 
                customAlert("Costing breakdown saved successfully.", "Success", "success");
                setTimeout(() => window.location.reload(), 1500); 
            }
            else { 
                customAlert('Error:\n' + result.message, "Save Failed", "error"); 
                saveBtn.innerHTML = originalBtnHtml; 
                saveBtn.disabled = false; 
            }
        } catch (error) { 
            customAlert('Network Error', "Error", "error"); 
            saveBtn.innerHTML = originalBtnHtml; 
            saveBtn.disabled = false; 
        }
    }

    async function deleteCostingBreakdown() {
        const isConfirmed = await customConfirm("Are you sure you want to delete this entire costing breakdown? This cannot be undone.", "Delete Breakdown", "Yes, delete it", "danger");
        if (!isConfirmed) return;
        
        try {
            const response = await fetch('actions/delete_costing.php', { method: 'POST', body: JSON.stringify({ project_id: document.getElementById('modal-project-id').value }), headers: { 'Content-Type': 'application/json' } });
            const result = await response.json();
            if(result.status === 'success') {
                customAlert("Costing breakdown deleted.", "Success", "success");
                setTimeout(() => window.location.reload(), 1500);
            }
            else customAlert("Error deleting breakdown.", "Error", "error");
        } catch (error) { customAlert("Network error while deleting.", "Error", "error"); }
    }

    // ==========================================
    // 3. PROGRESS, NOTES, ARCHIVE & DETAILS
    // ==========================================
    
    async function updateProgress(projectId, newProgress, oldProgress) {
        
        if ((newProgress === 'sampling' || newProgress === 'cutting') && oldProgress === 'not started') {
            const isConfirmed = await customConfirm(`You are moving the project to '${newProgress}'.\n\nDo you want to officially start this project and deduct the materials from the warehouse inventory?`, "Start Production");
            if (isConfirmed) {
                // 🚨 BUG FIX: Added small delay so the first modal closes smoothly before next opens
                await new Promise(resolve => setTimeout(resolve, 250)); 
                await startProjectProduction(projectId, false, newProgress); // Added AWAIT!
            } else {
                window.location.reload(); 
            }
            return; 
        }

        if (newProgress === 'not started' && oldProgress !== 'not started') {
            const isConfirmed = await customConfirm("⚠️ You are rolling this project back to 'Not Started'.\n\nDo you want to REFUND the deducted raw materials back into the warehouse inventory?", "Rollback Project");
            if (isConfirmed) {
                refundProjectMaterials(projectId);
                return; 
            }
        }

        try {
            const res = await fetch('actions/update_progress.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ project_id: projectId, progress: newProgress }) });
            const data = await res.json();
            
            if (data.status === 'success') {
                if (data.stock_action === 'added') {
                    document.getElementById('internal_stock_msg').textContent = data.stock_message;
                    document.getElementById('internal-stock-modal').classList.remove('hidden');
                } else if (data.stock_action === 'deducted') {
                    customAlert(data.stock_message, "Inventory Adjusted", "info");
                    setTimeout(() => window.location.reload(), 2500);
                } else {
                    window.location.reload();
                }
            } else {
                customAlert("Error updating progress", "Error", "error");
            }
        } catch(e) { customAlert("Network Error", "Error", "error"); }
    }

    function closeInternalStockModal() {
        document.getElementById('internal-stock-modal').classList.add('hidden');
        window.location.reload();
    }

    function openNotesModal(id, currentNotes) {
        document.getElementById('note_project_id').value = id;
        document.getElementById('note_text').value = currentNotes;
        document.getElementById('notes-modal').classList.remove('hidden');
    }
    function closeNotesModal() { document.getElementById('notes-modal').classList.add('hidden'); }

    async function saveNotes() {
        try {
            const res = await fetch('actions/update_progress.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ project_id: document.getElementById('note_project_id').value, overdue_notes: document.getElementById('note_text').value }) });
            if ((await res.json()).status === 'success') {
                closeNotesModal();
                window.location.reload();
            }
        } catch(e) { customAlert("Network Error", "Error", "error"); }
    }

    async function archiveProject(projectId) {
        const isConfirmed = await customConfirm("Archive this project? It will be moved to the Archived tab.", "Archive Project");
        if (!isConfirmed) return;
        try {
            const res = await fetch('actions/delete_project.php', { method: 'POST', body: JSON.stringify({ project_id: projectId }), headers: { 'Content-Type': 'application/json' } });
            if((await res.json()).status === 'success') window.location.reload();
        } catch (e) { customAlert("Network error.", "Error", "error"); }
    }

    async function restoreProject(projectId) {
        const isConfirmed = await customConfirm("Restore this project back to the active list?", "Restore Project", "Yes, Restore", "info");
        if (!isConfirmed) return;
        try {
            const res = await fetch('actions/restore_project.php', { method: 'POST', body: JSON.stringify({ project_id: projectId }), headers: { 'Content-Type': 'application/json' } });
            if((await res.json()).status === 'success') window.location.reload();
        } catch (e) { customAlert("Network error.", "Error", "error"); }
    }

    function toggleEditSizingModule() {
        const isChecked = document.getElementById('edit_enable_sizing_toggle').checked;
        const sizingArea = document.getElementById('edit_sizing_area');
        const qtyInput = document.getElementById('edit_quantity');
        const qtyWarning = document.getElementById('edit_qty_warning');

        if (isChecked) {
            sizingArea.classList.remove('hidden');
            if(!document.querySelector('input[name="edit_sizing_type"]:checked')) {
                document.querySelector('input[name="edit_sizing_type"][value="standard"]').checked = true;
            }
            switchEditSizingType();
        } else {
            sizingArea.classList.add('hidden');
            qtyInput.readOnly = false;
            qtyInput.classList.remove('bg-gray-200', 'dark:bg-zinc-800', 'text-gray-500');
            if(qtyWarning) qtyWarning.classList.add('hidden');
        }
    }

    function closeViewDetailsModal() { document.getElementById('view-details-modal').classList.add('hidden'); }

    async function viewProjectDetails(projectId) {
        document.getElementById('view-details-modal').classList.remove('hidden');
        document.getElementById('vd_project_id').textContent = "#PRJ-2026-" + String(projectId).padStart(3, '0');
        document.getElementById('edit_project_id').value = projectId;
        
        document.getElementById('edit_standard_tbody').innerHTML = '';
        document.getElementById('edit_custom_tbody').innerHTML = '';
        
        try {
            const response = await fetch(`actions/get_project_details.php?project_id=${projectId}`);
            const result = await response.json();
            
            if(result.status === 'success') {
                const p = result.project;
                document.getElementById('edit_project_name').value = p.project_name;
                document.getElementById('edit_quantity').value = p.quantity;
                document.getElementById('edit_due_date').value = p.due_date || '';
                
                const shortagesSection = document.getElementById('vd_shortages_section');
                const shortagesList = document.getElementById('vd_shortages_list');

                if (p.progress !== 'done' && p.progress !== 'released' && result.shortages && result.shortages.length > 0) {
                    shortagesSection.classList.remove('hidden');
                    let shortHtml = '';
                    
                    result.shortages.forEach(s => {
                        shortHtml += `
                            <div class="flex justify-between items-center bg-white dark:bg-zinc-950 p-2.5 rounded-lg border border-rose-100 dark:border-rose-800/30 shadow-sm">
                                <div class="min-w-0 pr-2">
                                    <p class="text-xs font-bold text-gray-900 dark:text-white truncate">${s.material_name}</p>
                                    <p class="text-[9px] font-bold text-gray-500 dark:text-zinc-500 mt-0.5 uppercase tracking-wider">Req: ${s.required_qty} | Have: ${s.current_stock}</p>
                                </div>
                                <div class="text-right shrink-0">
                                    <span class="text-[10px] font-black text-rose-600 dark:text-rose-500 bg-rose-100 dark:bg-rose-900/30 px-2 py-1 rounded uppercase tracking-widest">-${s.missing_qty}</span>
                                </div>
                            </div>
                        `;
                    });
                    shortagesList.innerHTML = shortHtml;
                } else {
                    shortagesSection.classList.add('hidden');
                    shortagesList.innerHTML = '';
                }
                
                const startBtnContainer = document.getElementById('vd_action_start_production');
                if (p.progress === 'not started') {
                    startBtnContainer.innerHTML = `
                        <button type="button" onclick="startProjectProduction(${projectId})" class="bg-emerald-500 hover:bg-emerald-600 text-white px-5 py-2.5 rounded-xl text-sm font-bold transition-all shadow-md shadow-emerald-500/20 focus:outline-none flex items-center gap-2">
                            <i class="fa-solid fa-scissors"></i> Start Production & Deduct Stock
                        </button>
                    `;
                } else {
                    startBtnContainer.innerHTML = '';
                }

                const sizingToggleLabel = document.getElementById('edit_sizing_toggle_label');
                let clientHtml = '';
                
                if (p.customer_id) {
                    clientHtml = `<p class="font-bold text-pink-600"><i class="fa-solid fa-user mr-2"></i>${p.customer_name}</p>
                                  <p class="text-xs text-gray-500 mt-1"><i class="fa-solid fa-phone mr-2"></i>${p.contact_number || 'No contact'}</p>`;
                    if(sizingToggleLabel) sizingToggleLabel.classList.remove('hidden');
                } else {
                    clientHtml = `<p class="font-bold text-amber-600"><i class="fa-solid fa-boxes-stacked mr-2"></i>Internal Restock</p>
                                  <p class="text-xs text-gray-500 mt-1">Target: ${p.internal_product} (${p.internal_size})</p>`;
                    if(sizingToggleLabel) sizingToggleLabel.classList.add('hidden');
                }
                document.getElementById('vd_client_info').innerHTML = clientHtml;
                
                const hasSizing = (result.measurements && result.measurements.length > 0) || (result.sizes && result.sizes.length > 0);
                const toggleBtn = document.getElementById('edit_enable_sizing_toggle');
                
                if (p.customer_id) {
                    if (hasSizing) {
                        toggleBtn.checked = true;
                        toggleEditSizingModule();

                        if (result.measurements && result.measurements.length > 0) {
                            document.querySelector('input[name="edit_sizing_type"][value="custom"]').checked = true;
                            switchEditSizingType();
                            result.measurements.forEach(m => addEditCustomRow(m.body_part, m.measurement_value, m.unit));
                        } else {
                            document.querySelector('input[name="edit_sizing_type"][value="standard"]').checked = true;
                            switchEditSizingType();
                            result.sizes.forEach(s => addEditStandardRow(s.size_label, s.quantity));
                        }
                    } else {
                        toggleBtn.checked = false;
                        toggleEditSizingModule();
                        addEditStandardRow('', p.quantity);
                    }
                } else {
                    toggleBtn.checked = false;
                    toggleEditSizingModule();
                }
            }
        } catch (e) { customAlert("Error loading details.", "Error", "error"); }
    }

    function switchEditSizingType() {
        const type = document.querySelector('input[name="edit_sizing_type"]:checked').value;
        const qtyInput = document.getElementById('edit_quantity');
        const qtyWarning = document.getElementById('edit_qty_warning');
        
        if (type === 'standard') {
            document.getElementById('edit_standard_wrapper').classList.remove('hidden');
            document.getElementById('edit_custom_wrapper').classList.add('hidden');
            qtyInput.readOnly = true;
            qtyInput.classList.add('bg-gray-200', 'dark:bg-zinc-800', 'text-gray-500');
            if(qtyWarning) qtyWarning.classList.remove('hidden');
            calculateEditTotalQty();
        } else {
            document.getElementById('edit_standard_wrapper').classList.add('hidden');
            document.getElementById('edit_custom_wrapper').classList.remove('hidden');
            qtyInput.readOnly = false;
            qtyInput.classList.remove('bg-gray-200', 'dark:bg-zinc-800', 'text-gray-500');
            if(qtyWarning) qtyWarning.classList.add('hidden');
        }
    }

    function addEditStandardRow(label = '', qty = 1) {
        const tr = document.createElement('tr');
        tr.className = "border-b border-gray-100 dark:border-zinc-800/50";
        tr.innerHTML = `<td class="py-1.5 pr-2"><input type="text" value="${label}" class="edit-sz-label w-full px-2 py-1 bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-700 text-gray-900 dark:text-white rounded text-sm outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent transition-all"></td><td class="py-1.5 pr-2"><input type="number" min="1" value="${qty}" class="edit-sz-qty w-full px-2 py-1 bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-700 text-gray-900 dark:text-white rounded text-sm outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent transition-all" oninput="calculateEditTotalQty()"></td><td class="py-1.5 text-right"><button type="button" onclick="this.closest('tr').remove(); calculateEditTotalQty();" class="text-gray-400 hover:text-rose-500"><i class="fa-solid fa-trash text-[10px]"></i></button></td>`;
        document.getElementById('edit_standard_tbody').appendChild(tr);
        calculateEditTotalQty();
    }

    function addEditCustomRow(part = '', val = '', unit = 'inches') {
        const tr = document.createElement('tr');
        tr.className = "border-b border-gray-100 dark:border-zinc-800/50";
        tr.innerHTML = `<td class="py-1.5 pr-2"><input type="text" value="${part}" class="edit-ms-part w-full px-2 py-1 bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-700 text-gray-900 dark:text-white rounded text-sm outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent transition-all"></td><td class="py-1.5 pr-2"><input type="number" step="0.25" value="${val}" class="edit-ms-val w-full px-2 py-1 bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-700 text-gray-900 dark:text-white rounded text-sm outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent transition-all"></td><td class="py-1.5 pr-2"><select class="edit-ms-unit w-full px-2 py-1 bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-700 text-gray-900 dark:text-white rounded text-sm outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent transition-all"><option value="inches" ${unit==='inches'?'selected':''}>inches</option><option value="cm" ${unit==='cm'?'selected':''}>cm</option></select></td><td class="py-1.5 text-right"><button type="button" onclick="this.closest('tr').remove();" class="text-gray-400 hover:text-rose-500"><i class="fa-solid fa-trash text-[10px]"></i></button></td>`;
        document.getElementById('edit_custom_tbody').appendChild(tr);
    }

    function calculateEditTotalQty() {
        if (!document.getElementById('edit_enable_sizing_toggle').checked) return;
        if (document.querySelector('input[name="edit_sizing_type"]:checked').value !== 'standard') return;
        let total = 0;
        document.querySelectorAll('#edit_standard_tbody .edit-sz-qty').forEach(input => total += parseInt(input.value) || 0);
        document.getElementById('edit_quantity').value = total > 0 ? total : 1;
    }

    function overrideEditQuantity() {
        if (!document.getElementById('edit_enable_sizing_toggle').checked) return;
        if (document.querySelector('input[name="edit_sizing_type"]:checked').value === 'standard') calculateEditTotalQty();
    }

    async function saveProjectUpdates() {
        const isSizingEnabled = document.getElementById('edit_enable_sizing_toggle').checked;
        let type = 'none'; 
        let sizingData = [];
        
        if (isSizingEnabled) {
            type = document.querySelector('input[name="edit_sizing_type"]:checked').value;
            if (type === 'standard') {
                document.querySelectorAll('#edit_standard_tbody tr').forEach(row => sizingData.push({ label: row.querySelector('.edit-sz-label').value, qty: row.querySelector('.edit-sz-qty').value }));
            } else {
                document.querySelectorAll('#edit_custom_tbody tr').forEach(row => sizingData.push({ part: row.querySelector('.edit-ms-part').value, val: row.querySelector('.edit-ms-val').value, unit: row.querySelector('.edit-ms-unit').value }));
            }
        }

        const payload = { 
            project_id: document.getElementById('edit_project_id').value, 
            project_name: document.getElementById('edit_project_name').value, 
            due_date: document.getElementById('edit_due_date').value, 
            quantity: document.getElementById('edit_quantity').value, 
            sizing_type: type, 
            sizing_data: JSON.stringify(sizingData) 
        };

        const btn = document.getElementById('btn-update-project');
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1"></i> Saving...';
        btn.disabled = true;

        try {
            const response = await fetch('actions/update_project.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
            const rawText = await response.text();
            
            try {
                const result = JSON.parse(rawText);
                if (result.status === 'success') {
                    customAlert("Project Details Updated", "Success", "success");
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    customAlert("Database Logic Error: " + result.message, "Error", "error"); 
                    btn.disabled = false; 
                    btn.innerHTML = "Update Project Details";
                }
            } catch (jsonError) {
                console.error("Raw Server Response:", rawText);
                customAlert("PHP Error in update_project.php:\n\n" + rawText.substring(0, 500), "Server Error", "error");
                btn.disabled = false; 
                btn.innerHTML = "Update Project Details";
            }
            
        } catch (error) { 
            customAlert("True Network Fetch Error: " + error.message, "Network Error", "error"); 
            btn.disabled = false; 
            btn.innerHTML = "Update Project Details"; 
        }
    }

    // ==========================================
    // 4. SMART START PRODUCTION LOGIC (Deficits)
    // ==========================================
    async function startProjectProduction(projectId, forceStart = false, targetPhase = 'cutting') {
        try {
            const response = await fetch('actions/start_production.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ project_id: projectId, force_start: forceStart, target_phase: targetPhase })
            });
            
            const data = await response.json();

            if (data.status === 'warning') {
                let missingList = data.shortages.join("\n- ");
                const isConfirmed = await customConfirm(
                    "⚠️ INSUFFICIENT MATERIALS ⚠️\n\n" +
                    "You do not have enough raw materials in the warehouse to complete this project. " +
                    "Proceeding will push your inventory into a negative deficit (Backorder).\n\n" +
                    "Missing Items:\n- " + missingList + "\n\n" +
                    "Do you want to force-start production anyway?",
                    "Warning: Low Stock",
                    "Force Start",
                    "danger"
                );

                if (isConfirmed) {
                    // 🚨 Added tiny delay so second confirm modal closes smoothly before processing
                    await new Promise(resolve => setTimeout(resolve, 250));
                    await startProjectProduction(projectId, true, targetPhase); // 🚨 Added AWAIT!
                } else {
                    window.location.reload(); 
                }
                
            } else if (data.status === 'success') {
                customAlert(data.message, "Success", "success");
                setTimeout(() => window.location.reload(), 2000); 
            } else {
                customAlert("Error: " + data.message, "Error", "error");
            }
            
        } catch (error) {
            customAlert("A network error occurred while trying to start production.", "Network Error", "error");
        }
    }

    async function refundProjectMaterials(projectId) {
        try {
            const response = await fetch('actions/refund_materials.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ project_id: projectId })
            });
            const data = await response.json();
            
            if (data.status === 'success') {
                customAlert(data.message, "Refund Successful", "success");
                setTimeout(() => window.location.reload(), 2000);
            } else {
                customAlert("Error refunding: " + data.message, "Refund Failed", "error");
                setTimeout(() => window.location.reload(), 2500); 
            }
        } catch (e) {
            customAlert("A network error occurred while trying to refund materials.", "Network Error", "error");
        }
    }
</script>

<?php include 'includes/footer.php'; ?>