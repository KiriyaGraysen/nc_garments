<?php
$page_title = "Orders & Projects | NC Garments";
require_once('config/database.php');

$view_archived = (isset($_GET['view']) && $_GET['view'] === 'archived') ? 1 : 0;

// 1. Fetch Projects
$stmt = $conn->prepare('
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
    ORDER BY p.project_id DESC
');
$stmt->bind_param("i", $view_archived);
$stmt->execute();
$project_result = $stmt->get_result();

$rm_stmt = $conn->prepare("SELECT material_id, material_name, current_price, unit_of_measure FROM raw_material ORDER BY material_name ASC");
$rm_stmt->execute();
$materials_json = json_encode($rm_stmt->get_result()->fetch_all(MYSQLI_ASSOC)); // <-- Added _json here!

$cust_stmt = $conn->prepare("SELECT customer_id, full_name FROM customer ORDER BY full_name ASC");
$cust_stmt->execute();
$customers = $cust_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$prod_stmt = $conn->prepare("SELECT product_id, product_name, size FROM premade_product ORDER BY product_name ASC");
$prod_stmt->execute();
$products = $prod_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Progress Bar Mapping
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

    <div class="flex gap-6 border-b border-gray-200 dark:border-zinc-800 mb-6">
        <a href="?view=active" class="pb-3 text-sm font-bold transition-colors <?= $view_archived === 0 ? 'border-b-2 border-pink-600 text-pink-600 dark:text-pink-500' : 'text-gray-500 hover:text-gray-700 dark:text-zinc-400' ?>">
            <i class="fa-solid fa-layer-group mr-1.5"></i> Active Projects
        </a>
        <a href="?view=archived" class="pb-3 text-sm font-bold transition-colors <?= $view_archived === 1 ? 'border-b-2 border-pink-600 text-pink-600 dark:text-pink-500' : 'text-gray-500 hover:text-gray-700 dark:text-zinc-400' ?>">
            <i class="fa-solid fa-box-archive mr-1.5"></i> Archived
        </a>
    </div>

    <div class="bg-white dark:bg-zinc-900 rounded-2xl shadow-sm border border-gray-100 dark:border-zinc-800 overflow-hidden transition-colors duration-500">
        <div class="overflow-x-auto">
            
            <table class="w-full whitespace-nowrap">
                <thead class="bg-gray-50 dark:bg-zinc-950/50 border-b border-gray-100 dark:border-zinc-800 transition-colors duration-500">
                    <tr>
                        <th class="px-6 py-4 text-left text-[10px] font-extrabold text-gray-500 dark:text-zinc-500 uppercase tracking-widest">Project Details</th>
                        <th class="px-6 py-4 text-left text-[10px] font-extrabold text-gray-500 dark:text-zinc-500 uppercase tracking-widest w-56">Current Progress</th>
                        <th class="px-6 py-4 text-left text-[10px] font-extrabold text-gray-500 dark:text-zinc-500 uppercase tracking-widest">Timeline & Dates</th>
                        <th class="px-6 py-4 text-left text-[10px] font-extrabold text-gray-500 dark:text-zinc-500 uppercase tracking-widest">Financials</th>
                        <th class="px-6 py-4 text-right text-[10px] font-extrabold text-gray-500 dark:text-zinc-500 uppercase tracking-widest">Actions</th>
                    </tr>
                </thead>
                
                <tbody class="divide-y divide-gray-50 dark:divide-zinc-800/50 text-sm transition-colors duration-500">
                    <?php
                    if ($project_result->num_rows === 0) {
                        echo '<tr><td colspan="5" class="px-6 py-8 text-center text-gray-500 font-medium">No projects found.</td></tr>';
                    }

                    while ($project = $project_result->fetch_assoc()) {
                        $full_name = empty($project['full_name']) ? 'Internal Restock' : htmlspecialchars($project['full_name']);
                        $project_name = htmlspecialchars($project['project_name']);
                        $qty = htmlspecialchars($project['quantity']);
                        
                        $agreed_price = (float)$project['agreed_price'];
                        $material_cost = (float)$project['total_material_cost'];
                        $est_profit = $agreed_price - $material_cost;
                        $profit_color = ($est_profit > 0) ? 'text-emerald-500' : (($agreed_price == 0 && $material_cost > 0) ? 'text-amber-500' : 'text-gray-400');
                        
                        $time = calculate_timeline($project['start_date'], $project['due_date'], $project['finish_date']);
                        
                        $pct = $progress_percentages[$project['progress']] ?? 0;
                        $bar_color = ($project['progress'] === 'cancelled') ? 'bg-rose-500' : 'bg-pink-600';
                        $pct_color = ($project['progress'] === 'cancelled') ? 'text-rose-500' : 'text-pink-600';
                        $disabled_select = $view_archived ? 'disabled' : '';
                        
                        echo '
                            <tr class="hover:bg-gray-50/80 dark:hover:bg-zinc-800/30 transition-colors group cursor-pointer" onclick="viewProjectDetails(' . $project['project_id'] . ')">
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
                                    <select onchange="updateProgress(' . $project['project_id'] . ', this.value)" '.$disabled_select.' class="w-full bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-700 text-gray-900 dark:text-white text-[11px] font-bold rounded-lg px-2 py-1.5 outline-none focus:ring-2 focus:ring-pink-500 transition-all uppercase tracking-wider cursor-pointer shadow-sm disabled:opacity-50">
                                        ';
                                        foreach ($progress_options as $opt) {
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
                                
                                <td class="px-6 py-4" onclick="event.stopPropagation();">
                                    <div class="font-extrabold text-gray-900 dark:text-white flex items-center gap-2">
                                        ₱ ' . number_format($agreed_price, 2) . '
                                        <button onclick="editAgreedPrice(' . $project['project_id'] . ', ' . $agreed_price . ')" class="text-gray-400 hover:text-pink-600 focus:outline-none cursor-pointer" title="Edit Price">
                                            <i class="fa-solid fa-pen text-[10px]"></i>
                                        </button>
                                    </div>
                                    <div class="text-[11px] font-bold text-gray-500 dark:text-zinc-400 tracking-wide mt-1 uppercase">Cost: ₱ ' . number_format($material_cost, 2) . '</div>
                                    <div class="text-[11px] font-bold ' . $profit_color . ' tracking-wide mt-0.5 uppercase">Est. Profit: ₱ ' . number_format($est_profit, 2) . '</div>
                                </td>
                                
                                <td class="px-6 py-4 text-right text-sm font-medium">
                                    <div class="flex flex-col gap-2 items-end">';
                                    
                                    if ($time['is_overdue'] || !empty($project['overdue_notes'])) {
                                        $note_icon = empty($project['overdue_notes']) ? 'fa-regular fa-comment' : 'fa-solid fa-comment text-amber-500';
                                        echo '<button onclick="event.stopPropagation(); openNotesModal('.$project['project_id'].', \''.addslashes($project['overdue_notes'] ?? '').'\')" class="text-gray-500 hover:text-amber-500 text-xs font-bold transition-colors focus:outline-none bg-gray-50 dark:bg-zinc-800 px-2 py-1 rounded border border-gray-200 dark:border-zinc-700 shadow-sm">
                                                <i class="'.$note_icon.' mr-1"></i> Notes
                                              </button>';
                                    }

                                    echo '<div class="flex items-center mt-1">
                                            <button onclick="event.stopPropagation(); viewProjectDetails(' . $project['project_id'] . ')" class="text-gray-400 hover:text-blue-500 transition-colors focus:outline-none p-2 mr-1" title="View/Edit Details">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>
                                            <button onclick="event.stopPropagation(); openCostingModal(' . $project['project_id'] . ', \'' . addslashes($project_name) . '\', ' . $agreed_price . ')" class="bg-white dark:bg-zinc-800 border border-gray-200 dark:border-zinc-700 text-gray-700 dark:text-zinc-300 hover:text-pink-600 hover:border-pink-200 px-3 py-1.5 rounded-lg transition-all mr-2 text-xs font-bold shadow-sm focus:outline-none">
                                                <i class="fa-solid fa-file-invoice-dollar mr-1"></i> Costing
                                            </button>';

                                    if ($view_archived === 0) {
                                        echo '<button onclick="event.stopPropagation(); archiveProject(' . $project['project_id'] . ')" class="text-gray-400 hover:text-amber-500 transition-colors focus:outline-none p-2" title="Archive Project">
                                                  <i class="fa-solid fa-box-archive"></i>
                                              </button>';
                                    } else {
                                        echo '<button onclick="event.stopPropagation(); restoreProject(' . $project['project_id'] . ')" class="text-gray-400 hover:text-emerald-500 transition-colors focus:outline-none p-2" title="Restore Project">
                                                  <i class="fa-solid fa-clock-rotate-left"></i>
                                              </button>';
                                    }

                                    echo '   </div>
                                        </div>
                                    </td>
                            </tr>
                        ';
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <div id="create-project-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm transition-opacity" onclick="closeCreateProjectModal()"></div>
        
        <div class="relative bg-white dark:bg-zinc-900 rounded-2xl w-full max-w-5xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh] border border-gray-100 dark:border-zinc-800">
            
            <div class="px-6 py-4 border-b border-gray-100 dark:border-zinc-800 flex justify-between items-center bg-gray-50/50 dark:bg-zinc-950/30">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Create New Project</h3>
                <button onclick="closeCreateProjectModal()" class="text-gray-400 hover:text-rose-500 transition-colors focus:outline-none">
                    <i class="fa-solid fa-xmark text-xl"></i>
                </button>
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
                                            <div>
                                                <p class="text-sm font-bold text-gray-900 dark:text-white">Make-to-Order</p>
                                            </div>
                                        </div>
                                    </label>
                                    <label class="relative flex cursor-pointer rounded-xl border border-gray-200 dark:border-zinc-800 bg-gray-50 dark:bg-zinc-950 p-3 shadow-sm focus:outline-none has-[:checked]:border-amber-500 has-[:checked]:bg-amber-50 dark:has-[:checked]:bg-amber-900/10 transition-all">
                                        <input type="radio" name="workflow_type" value="internal" class="sr-only" onchange="toggleWorkflow()">
                                        <div class="flex items-center gap-3">
                                            <div class="text-amber-500 text-lg"><i class="fa-solid fa-boxes-stacked"></i></div>
                                            <div>
                                                <p class="text-sm font-bold text-gray-900 dark:text-white">Make-to-Stock</p>
                                            </div>
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
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="checkbox" id="enable-sizing-toggle" class="rounded border-gray-300 text-pink-600 focus:ring-pink-500" onchange="toggleSizingModule()">
                                        <span class="text-[10px] font-bold text-pink-600 dark:text-pink-500 uppercase tracking-widest">Enable Sizing</span>
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
                        <span class="text-[10px] font-extrabold text-pink-600 dark:text-pink-500 uppercase tracking-widest">Agreed Price (Charge)</span>
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

                    <div class="lg:col-span-5 relative min-h-[400px] lg:min-h-0">
                        <div class="lg:absolute inset-0 bg-white dark:bg-zinc-900 p-5 rounded-xl border border-gray-100 dark:border-zinc-800 shadow-sm flex flex-col h-full">
                            <div class="flex justify-between items-center mb-3 border-b border-gray-100 dark:border-zinc-800 pb-2 flex-none">
                                <h4 class="text-xs font-extrabold text-gray-500 dark:text-zinc-500 uppercase tracking-widest">Sizing & Measurements</h4>
                            </div>
                            
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
                </div>
            </div>
            
            <div class="px-6 py-4 border-t border-gray-100 dark:border-zinc-800 bg-gray-50/50 dark:bg-zinc-950/30 flex justify-end gap-3 mt-auto">
                <button onclick="closeViewDetailsModal()" class="px-5 py-2.5 text-sm font-bold text-gray-600 dark:text-zinc-400 hover:bg-gray-100 dark:hover:bg-zinc-800 rounded-xl transition-colors focus:outline-none">Cancel</button>
                <button onclick="saveProjectUpdates()" id="btn-update-project" class="bg-pink-600 hover:bg-pink-700 text-white px-5 py-2.5 rounded-xl text-sm font-bold transition-all shadow-md focus:outline-none">Update Project Details</button>
            </div>
        </div>
    </div>

</main>

<script>
    // ==========================================
    // 1. CREATE PROJECT WIZARD LOGIC
    // ==========================================
    function openCreateProjectModal() { document.getElementById('create-project-modal').classList.remove('hidden'); }
    function closeCreateProjectModal() { document.getElementById('create-project-modal').classList.add('hidden'); }

    function toggleWorkflow() {
        const isCustomer = document.querySelector('input[name="workflow_type"][value="customer"]').checked;
        if (isCustomer) {
            document.getElementById('section-customer').classList.remove('hidden');
            document.getElementById('section-internal').classList.add('hidden');
        } else {
            document.getElementById('section-customer').classList.add('hidden');
            document.getElementById('section-internal').classList.remove('hidden');
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
            qtyInput.classList.remove('bg-gray-100', 'text-gray-500');
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
            qtyInput.classList.add('bg-gray-100', 'text-gray-500');
            qtyWarning.classList.remove('hidden');
            calculateTotalStandardQuantity();
        } else {
            standardWrapper.classList.add('hidden');
            customWrapper.classList.remove('hidden');
            qtyInput.readOnly = false;
            qtyInput.classList.remove('bg-gray-100', 'text-gray-500');
            qtyWarning.classList.add('hidden');
        }
    }

    function addStandardSizeRow() {
        const tbody = document.getElementById('standard-sizing-tbody');
        const tr = document.createElement('tr');
        tr.className = "border-b border-gray-100 dark:border-zinc-800/50";
        tr.innerHTML = `
            <td class="py-2 pr-2"><input type="text" placeholder="e.g., Medium" class="sizing-label w-full px-3 py-1.5 bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-lg text-sm outline-none focus:border-pink-500"></td>
            <td class="py-2 pr-2"><input type="number" min="1" value="1" class="sizing-qty w-full px-3 py-1.5 bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-lg text-sm outline-none focus:border-pink-500" oninput="calculateTotalStandardQuantity()"></td>
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
            <td class="py-2 pr-2"><input type="text" placeholder="e.g., Chest" class="measure-part w-full px-3 py-1.5 bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-lg text-sm outline-none focus:border-pink-500"></td>
            <td class="py-2 pr-2"><input type="number" step="0.25" placeholder="0.00" class="measure-val w-full px-3 py-1.5 bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-lg text-sm outline-none focus:border-pink-500"></td>
            <td class="py-2 pr-2"><select class="measure-unit w-full px-3 py-1.5 bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-lg text-sm outline-none focus:border-pink-500"><option value="inches">inches</option><option value="cm">cm</option></select></td>
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
            const result = await response.json();
            if (result.status === 'success') {
                closeCreateProjectModal();
                if (proceedToCosting) openCostingModal(result.project_id, result.project_name);
                else window.location.reload();
            } else alert('Error: ' + result.message);
        } catch (error) { alert('Network Error'); }
    }

    // ==========================================
    // 2. COSTING, PROFIT & CURRENCY LOGIC
    // ==========================================
    function formatCurrency(number) { return parseFloat(number).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }
    const rawMaterials = <?php echo $materials_json; ?>;

    async function openCostingModal(projectId, projectName, currentAgreedPrice = 0) {
        document.getElementById('modal-project-id').value = projectId;
        document.getElementById('modal-project-name').textContent = projectName;
        const priceInput = document.getElementById('modal-agreed-price');
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

    function closeCostingModal() { document.getElementById('costing-modal').classList.add('hidden'); }

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
        const agreedPrice = document.getElementById('modal-agreed-price') ? document.getElementById('modal-agreed-price').value : 0;
        let materialsData = [];
        document.querySelectorAll('#costing-tbody tr').forEach(row => {
            const materialId = row.querySelector('select').value;
            if (materialId !== "") {
                materialsData.push({ material_id: materialId, quantity: row.querySelector('.qty-input').value, unit_price: row.querySelector('.price-input').value });
            }
        });

        if (materialsData.length === 0 && agreedPrice == 0) return alert("Please add at least one material or set a valid Agreed Price.");

        const saveBtn = document.querySelector('button[onclick="saveCosting()"]');
        const originalBtnHtml = saveBtn.innerHTML;
        try {
            saveBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1.5"></i> Saving...';
            saveBtn.disabled = true;
            const response = await fetch('actions/save_costing.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ project_id: projectId, agreed_price: agreedPrice, materials: materialsData }) });
            const result = JSON.parse(await response.text());
            if (result.status === 'success') { closeCostingModal(); window.location.reload(); }
            else { alert('Error:\n' + result.message); saveBtn.innerHTML = originalBtnHtml; saveBtn.disabled = false; }
        } catch (error) { alert('Network Error'); saveBtn.innerHTML = originalBtnHtml; saveBtn.disabled = false; }
    }

    async function deleteCostingBreakdown() {
        if (!confirm("Are you sure you want to delete this entire costing breakdown? This cannot be undone.")) return;
        try {
            const response = await fetch('actions/delete_costing.php', { method: 'POST', body: JSON.stringify({ project_id: document.getElementById('modal-project-id').value }), headers: { 'Content-Type': 'application/json' } });
            const result = await response.json();
            if(result.status === 'success') window.location.reload();
            else alert("Error deleting breakdown.");
        } catch (error) { alert("Network error while deleting."); }
    }

    async function editAgreedPrice(projectId, currentPrice) {
        let newPrice = prompt("Enter new agreed price (₱):", currentPrice);
        if (newPrice !== null) {
            if (isNaN(newPrice) || newPrice.trim() === "") return alert("Please enter a valid number.");
            try {
                const response = await fetch('actions/update_agreed_price.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ project_id: projectId, agreed_price: newPrice }) });
                const result = await response.json();
                if (result.status === 'success') window.location.reload();
                else alert("Failed to update price.");
            } catch (error) { alert("Network error."); }
        }
    }

    // ==========================================
    // 3. PROGRESS, NOTES, ARCHIVE & DETAILS
    // ==========================================
    async function updateProgress(projectId, newProgress) {
        try {
            const res = await fetch('actions/update_progress.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ project_id: projectId, progress: newProgress }) });
            const data = await res.json();
            if (data.status === 'success') window.location.reload();
            else alert("Error updating progress");
        } catch(e) { alert("Network Error"); }
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
            if ((await res.json()).status === 'success') window.location.reload();
        } catch(e) { alert("Network Error"); }
    }

    async function archiveProject(projectId) {
        if (!confirm("Archive this project? It will be moved to the Archived tab.")) return;
        try {
            const res = await fetch('actions/delete_project.php', { method: 'POST', body: JSON.stringify({ project_id: projectId }), headers: { 'Content-Type': 'application/json' } });
            if((await res.json()).status === 'success') window.location.reload();
        } catch (e) { alert("Network error."); }
    }

    async function restoreProject(projectId) {
        if (!confirm("Restore this project back to the active list?")) return;
        try {
            const res = await fetch('actions/restore_project.php', { method: 'POST', body: JSON.stringify({ project_id: projectId }), headers: { 'Content-Type': 'application/json' } });
            if((await res.json()).status === 'success') window.location.reload();
        } catch (e) { alert("Network error."); }
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
                
                let clientHtml = '';
                if (p.customer_id) clientHtml = `<p class="font-bold text-pink-600"><i class="fa-solid fa-user mr-2"></i>${p.customer_name}</p><p class="text-xs text-gray-500 mt-1"><i class="fa-solid fa-phone mr-2"></i>${p.contact_number || 'No contact'}</p>`;
                else clientHtml = `<p class="font-bold text-amber-600"><i class="fa-solid fa-boxes-stacked mr-2"></i>Internal Restock</p><p class="text-xs text-gray-500 mt-1">Target: ${p.internal_product} (${p.internal_size})</p>`;
                document.getElementById('vd_client_info').innerHTML = clientHtml;
                
                if (result.measurements && result.measurements.length > 0) {
                    document.querySelector('input[name="edit_sizing_type"][value="custom"]').checked = true;
                    switchEditSizingType();
                    result.measurements.forEach(m => addEditCustomRow(m.body_part, m.measurement_value, m.unit));
                } else {
                    document.querySelector('input[name="edit_sizing_type"][value="standard"]').checked = true;
                    switchEditSizingType();
                    if(result.sizes && result.sizes.length > 0) result.sizes.forEach(s => addEditStandardRow(s.size_label, s.quantity));
                    else addEditStandardRow('', p.quantity);
                }
            }
        } catch (e) { alert("Error loading details."); }
    }

    function switchEditSizingType() {
        const type = document.querySelector('input[name="edit_sizing_type"]:checked').value;
        const qtyInput = document.getElementById('edit_quantity');
        if (type === 'standard') {
            document.getElementById('edit_standard_wrapper').classList.remove('hidden');
            document.getElementById('edit_custom_wrapper').classList.add('hidden');
            qtyInput.readOnly = true;
            qtyInput.classList.add('bg-gray-200', 'text-gray-500');
            calculateEditTotalQty();
        } else {
            document.getElementById('edit_standard_wrapper').classList.add('hidden');
            document.getElementById('edit_custom_wrapper').classList.remove('hidden');
            qtyInput.readOnly = false;
            qtyInput.classList.remove('bg-gray-200', 'text-gray-500');
        }
    }

    function addEditStandardRow(label = '', qty = 1) {
        const tr = document.createElement('tr');
        tr.className = "border-b border-gray-100 dark:border-zinc-800/50";
        tr.innerHTML = `<td class="py-1.5 pr-2"><input type="text" value="${label}" class="edit-sz-label w-full px-2 py-1 bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-700 rounded text-sm outline-none"></td><td class="py-1.5 pr-2"><input type="number" min="1" value="${qty}" class="edit-sz-qty w-full px-2 py-1 bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-700 rounded text-sm outline-none" oninput="calculateEditTotalQty()"></td><td class="py-1.5 text-right"><button type="button" onclick="this.closest('tr').remove(); calculateEditTotalQty();" class="text-gray-400 hover:text-rose-500"><i class="fa-solid fa-trash text-[10px]"></i></button></td>`;
        document.getElementById('edit_standard_tbody').appendChild(tr);
        calculateEditTotalQty();
    }

    function addEditCustomRow(part = '', val = '', unit = 'inches') {
        const tr = document.createElement('tr');
        tr.className = "border-b border-gray-100 dark:border-zinc-800/50";
        tr.innerHTML = `<td class="py-1.5 pr-2"><input type="text" value="${part}" class="edit-ms-part w-full px-2 py-1 bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-700 rounded text-sm outline-none"></td><td class="py-1.5 pr-2"><input type="number" step="0.25" value="${val}" class="edit-ms-val w-full px-2 py-1 bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-700 rounded text-sm outline-none"></td><td class="py-1.5 pr-2"><select class="edit-ms-unit w-full px-2 py-1 bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-700 rounded text-sm outline-none"><option value="inches" ${unit==='inches'?'selected':''}>inches</option><option value="cm" ${unit==='cm'?'selected':''}>cm</option></select></td><td class="py-1.5 text-right"><button type="button" onclick="this.closest('tr').remove();" class="text-gray-400 hover:text-rose-500"><i class="fa-solid fa-trash text-[10px]"></i></button></td>`;
        document.getElementById('edit_custom_tbody').appendChild(tr);
    }

    function calculateEditTotalQty() {
        if (document.querySelector('input[name="edit_sizing_type"]:checked').value !== 'standard') return;
        let total = 0;
        document.querySelectorAll('#edit_standard_tbody .edit-sz-qty').forEach(input => total += parseInt(input.value) || 0);
        document.getElementById('edit_quantity').value = total > 0 ? total : 1;
    }

    function overrideEditQuantity() {
        if (document.querySelector('input[name="edit_sizing_type"]:checked').value === 'standard') calculateEditTotalQty();
    }

    async function saveProjectUpdates() {
        const type = document.querySelector('input[name="edit_sizing_type"]:checked').value;
        let sizingData = [];
        
        if (type === 'standard') {
            document.querySelectorAll('#edit_standard_tbody tr').forEach(row => sizingData.push({ label: row.querySelector('.edit-sz-label').value, qty: row.querySelector('.edit-sz-qty').value }));
        } else {
            document.querySelectorAll('#edit_custom_tbody tr').forEach(row => sizingData.push({ part: row.querySelector('.edit-ms-part').value, val: row.querySelector('.edit-ms-val').value, unit: row.querySelector('.edit-ms-unit').value }));
        }

        const payload = { project_id: document.getElementById('edit_project_id').value, project_name: document.getElementById('edit_project_name').value, due_date: document.getElementById('edit_due_date').value, quantity: document.getElementById('edit_quantity').value, sizing_type: type, sizing_data: JSON.stringify(sizingData) };

        const btn = document.getElementById('btn-update-project');
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1"></i> Saving...';
        btn.disabled = true;

        try {
            const res = await fetch('actions/update_project.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
            if ((await res.json()).status === 'success') window.location.reload();
            else { alert("Error updating project"); btn.disabled = false; btn.innerHTML = "Update Project Details"; }
        } catch (e) { alert("Network Error"); btn.disabled = false; btn.innerHTML = "Update Project Details"; }
    }
</script>

<?php include 'includes/footer.php'; ?>
