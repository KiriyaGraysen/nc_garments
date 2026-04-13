<?php
$page_title = "Orders & Projects | NC Garments";
require_once('config/database.php');

// Check which tab we are viewing (default to active)
$view_archived = (isset($_GET['view']) && $_GET['view'] === 'archived') ? 1 : 0;

// 1. Fetch Projects (UPDATED: Now filters by is_archived)
$stmt = $conn->prepare('
    SELECT 
        p.project_id, p.project_name, p.quantity, p.due_date, p.agreed_price, p.status, 
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

// 2. Fetch Raw Materials for the Costing Dropdown
$rm_stmt = $conn->prepare("SELECT material_id, material_name, current_price, unit_of_measure FROM raw_material ORDER BY material_name ASC");
$rm_stmt->execute();
$materials = $rm_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$materials_json = json_encode($materials);

// 3. Fetch Customers & Products for the "Create Project" Modal
$cust_stmt = $conn->prepare("SELECT customer_id, full_name FROM customer ORDER BY full_name ASC");
$cust_stmt->execute();
$customers = $cust_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$prod_stmt = $conn->prepare("SELECT product_id, product_name, size FROM premade_product ORDER BY product_name ASC");
$prod_stmt->execute();
$products = $prod_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

function format_project_deadline($date_string) {
    if (empty($date_string)) {
        return ['date' => 'No Due Date', 'status' => '', 'color' => 'text-gray-500'];
    }

    $due_date = new DateTime($date_string);
    $due_date->setTime(0, 0, 0); 
    $today = new DateTime('today');
    $formatted_date = $due_date->format('M j, Y');
    $diff = $today->diff($due_date);
    $days = $diff->days;
    $is_past = $diff->invert === 1; 

    if ($days === 0) {
        $status = '<i class="fa-solid fa-circle-exclamation text-[10px]"></i> Due Today';
        $color = 'text-amber-500';
    } elseif ($is_past) {
        $status = '<i class="fa-solid fa-circle-exclamation text-[10px]"></i> Overdue by ' . $days . ' day' . ($days > 1 ? 's' : '');
        $color = 'text-rose-600';
    } else {
        $status = '<i class="fa-regular fa-clock text-[10px]"></i> in ' . $days . ' days';
        $color = ($days <= 7) ? 'text-amber-500' : 'text-gray-500 dark:text-zinc-400';
    }

    return [
        'date' => $formatted_date,
        'status' => $status,
        'color' => $color
    ];
}

include 'includes/header.php'; 
?>

<main class="flex-1 p-4 md:p-8 overflow-y-auto transition-colors duration-500 font-sans relative">
    
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white transition-colors duration-500">Orders & Projects</h2>
            <p class="text-gray-500 dark:text-zinc-400 text-sm mt-1 transition-colors duration-500">Manage customer orders, track production progress, and view cost breakdowns.</p>
        </div>
        <button onclick="openCreateProjectModal()" class="bg-pink-600 hover:bg-pink-700 text-white px-5 py-2.5 rounded-xl text-sm font-bold transition-all duration-300 shadow-lg shadow-pink-600/20 hover:shadow-pink-600/40 flex items-center gap-2 cursor-pointer focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-pink-500 dark:focus:ring-offset-zinc-950">
            <i class="fa-solid fa-folder-plus"></i> Create New Project
        </button>
    </div>

    <div class="flex gap-6 border-b border-gray-200 dark:border-zinc-800 mb-6">
        <a href="?view=active" class="pb-3 text-sm font-bold transition-colors <?= $view_archived === 0 ? 'border-b-2 border-pink-600 text-pink-600 dark:text-pink-500' : 'text-gray-500 hover:text-gray-700 dark:text-zinc-400 dark:hover:text-zinc-200' ?>">
            <i class="fa-solid fa-layer-group mr-1.5"></i> Active Projects
        </a>
        <a href="?view=archived" class="pb-3 text-sm font-bold transition-colors <?= $view_archived === 1 ? 'border-b-2 border-pink-600 text-pink-600 dark:text-pink-500' : 'text-gray-500 hover:text-gray-700 dark:text-zinc-400 dark:hover:text-zinc-200' ?>">
            <i class="fa-solid fa-box-archive mr-1.5"></i> Archived
        </a>
    </div>

    <div class="bg-white dark:bg-zinc-900 rounded-2xl shadow-sm border border-gray-100 dark:border-zinc-800 overflow-hidden transition-colors duration-500">
        <div class="overflow-x-auto">
            
            <table class="w-full whitespace-nowrap">
                <thead class="bg-gray-50 dark:bg-zinc-950/50 border-b border-gray-100 dark:border-zinc-800 transition-colors duration-500">
                    <tr>
                        <th class="px-6 py-4 text-left text-[10px] font-extrabold text-gray-500 dark:text-zinc-500 uppercase tracking-widest">Project Details</th>
                        <th class="px-6 py-4 text-left text-[10px] font-extrabold text-gray-500 dark:text-zinc-500 uppercase tracking-widest">Production Progress</th>
                        <th class="px-6 py-4 text-left text-[10px] font-extrabold text-gray-500 dark:text-zinc-500 uppercase tracking-widest">Due Date</th>
                        <th class="px-6 py-4 text-left text-[10px] font-extrabold text-gray-500 dark:text-zinc-500 uppercase tracking-widest">Financials</th>
                        <th class="px-6 py-4 text-right text-[10px] font-extrabold text-gray-500 dark:text-zinc-500 uppercase tracking-widest">Actions</th>
                    </tr>
                </thead>
                
                <tbody class="divide-y divide-gray-50 dark:divide-zinc-800/50 text-sm transition-colors duration-500">
                    <?php
                    if ($project_result->num_rows === 0) {
                        echo '<tr><td colspan="5" class="px-6 py-8 text-center text-gray-500 dark:text-zinc-400 font-medium">No projects found in this view.</td></tr>';
                    }

                    while ($project = $project_result->fetch_assoc()) {
                        $full_name = empty($project['full_name']) ? 'NC Garments (Internal)' : htmlspecialchars($project['full_name']);
                        $project_name = htmlspecialchars($project['project_name']);
                        $qty = htmlspecialchars($project['quantity']);
                        $agreed_price = (float)$project['agreed_price'];
                        $material_cost = (float)$project['total_material_cost'];
                        
                        // Calculate Profit
                        $est_profit = $agreed_price - $material_cost;
                        $profit_color = ($est_profit > 0) ? 'text-emerald-500' : (($agreed_price == 0 && $material_cost > 0) ? 'text-amber-500' : 'text-gray-400');
                        $deadline_info = format_project_deadline($project['due_date']);
                        
                        echo '
                            <tr class="hover:bg-gray-50/80 dark:hover:bg-zinc-800/30 transition-colors group cursor-pointer" onclick="viewProjectDetails(' . $project['project_id'] . ')">
                                <td class="px-6 py-4">
                                    <div class="font-bold text-pink-600 dark:text-pink-500 group-hover:text-pink-700 dark:group-hover:text-pink-400 transition-colors">#PRJ-2026-' . str_pad($project['project_id'] ?? '0', 3, '0', STR_PAD_LEFT) . '</div>
                                    <div class="font-bold text-gray-900 dark:text-white mt-1">' . $project_name . '</div>
                                    <div class="text-xs font-medium text-gray-500 dark:text-zinc-400 flex items-center gap-1.5 mt-1">
                                        <i class="fa-regular fa-user text-[10px]"></i> ' . $full_name . ' • ' . $qty . ' pcs
                                    </div>
                                </td>
                                
                                <td class="px-6 py-4 w-64">
                                    <div class="flex justify-between text-xs mb-1.5">
                                        <span class="font-bold uppercase tracking-wider text-[10px] text-pink-600 dark:text-pink-500">In Production</span>
                                    </div>
                                    <div class="w-full bg-gray-100 dark:bg-zinc-950 rounded-full h-1.5 shadow-inner overflow-hidden">
                                        <div class="bg-gradient-to-r from-pink-500 to-pink-600 h-1.5 rounded-full" style="width: 50%"></div>
                                    </div>
                                </td>
                                
                                <td class="px-6 py-4">
                                    <div class="text-gray-900 dark:text-white font-bold">' . $deadline_info['date'] . '</div>
                                    <div class="text-xs font-semibold mt-1 ' . $deadline_info['color'] . '">
                                        ' . $deadline_info['status'] . '
                                    </div>
                                </td>
                                
                                <td class="px-6 py-4">
                                    <div class="font-extrabold text-gray-900 dark:text-white flex items-center gap-2">
                                        Price: ₱ ' . number_format($agreed_price, 2) . '
                                        <button onclick="event.stopPropagation(); editAgreedPrice(' . $project['project_id'] . ', ' . $agreed_price . ')" class="text-gray-400 hover:text-pink-600 dark:hover:text-pink-400 transition-colors focus:outline-none cursor-pointer" title="Edit Price">
                                            <i class="fa-solid fa-pen text-[10px]"></i>
                                        </button>
                                    </div>
                                    <div class="text-[11px] font-bold text-gray-500 dark:text-zinc-400 tracking-wide mt-1 uppercase">Cost: ₱ ' . number_format($material_cost, 2) . '</div>
                                    <div class="text-[11px] font-bold ' . $profit_color . ' tracking-wide mt-0.5 uppercase">Est. Profit: ₱ ' . number_format($est_profit, 2) . '</div>
                                </td>
                                
                                <td class="px-6 py-4 text-right text-sm font-medium">
                                    <button onclick="event.stopPropagation(); openCostingModal(' . $project['project_id'] . ', \'' . addslashes($project_name) . '\', ' . $agreed_price . ')" class="bg-white dark:bg-zinc-800 border border-gray-200 dark:border-zinc-700 text-gray-700 dark:text-zinc-300 hover:text-pink-600 dark:hover:text-pink-400 hover:border-pink-200 px-3 py-1.5 rounded-lg transition-all mr-2 text-xs font-bold shadow-sm focus:outline-none">
                                        <i class="fa-solid fa-file-invoice-dollar mr-1"></i> Costing
                                    </button>';

                        // Dynamic Action Button based on view
                        if ($view_archived === 0) {
                            echo '<button onclick="event.stopPropagation(); archiveProject(' . $project['project_id'] . ')" class="text-gray-400 hover:text-amber-500 transition-colors focus:outline-none p-2" title="Archive Project">
                                      <i class="fa-solid fa-box-archive"></i>
                                  </button>';
                        } else {
                            echo '<button onclick="event.stopPropagation(); restoreProject(' . $project['project_id'] . ')" class="text-gray-400 hover:text-emerald-500 transition-colors focus:outline-none p-2" title="Restore Project">
                                      <i class="fa-solid fa-clock-rotate-left"></i>
                                  </button>';
                        }

                        echo '   </td>
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
        
        <div class="relative bg-white dark:bg-zinc-900 rounded-2xl w-full max-w-3xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh] border border-gray-100 dark:border-zinc-800">
            
            <div class="px-6 py-4 border-b border-gray-100 dark:border-zinc-800 flex justify-between items-center bg-gray-50/50 dark:bg-zinc-950/30">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Create New Project</h3>
                <button onclick="closeCreateProjectModal()" class="text-gray-400 hover:text-rose-500 transition-colors focus:outline-none">
                    <i class="fa-solid fa-xmark text-xl"></i>
                </button>
            </div>
            
            <div class="p-6 overflow-y-auto flex-1">
                <form id="create-project-form" class="space-y-6">
                    
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                        <div class="col-span-2">
                            <label class="block text-xs font-bold text-gray-600 dark:text-zinc-400 mb-2 uppercase tracking-wide">Project Name / Description</label>
                            <input type="text" id="cp_project_name" placeholder="e.g., LGU Polo Shirts" class="w-full px-4 py-3 bg-gray-50 dark:bg-zinc-950 border border-gray-200 dark:border-zinc-800 text-gray-900 dark:text-white rounded-xl focus:ring-2 focus:ring-pink-500 focus:border-transparent outline-none transition-all text-sm font-medium">
                        </div>
                        <div class="col-span-2 md:col-span-1">
                            <label class="block text-xs font-bold text-gray-600 dark:text-zinc-400 mb-2 uppercase tracking-wide">Due Date</label>
                            <input type="date" id="cp_due_date" class="w-full px-4 py-3 bg-gray-50 dark:bg-zinc-950 border border-gray-200 dark:border-zinc-800 text-gray-900 dark:text-white rounded-xl focus:ring-2 focus:ring-pink-500 outline-none transition-all text-sm font-medium">
                        </div>
                    </div>

                    <hr class="border-gray-100 dark:border-zinc-800">

                    <div class="bg-gray-50 dark:bg-zinc-950 p-4 rounded-xl border border-gray-100 dark:border-zinc-800 space-y-4">
                        <div class="flex justify-between items-center">
                            <label class="block text-xs font-bold text-gray-600 dark:text-zinc-400 uppercase tracking-wide">Quantity & Sizing</label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" id="enable-sizing-toggle" class="rounded border-gray-300 text-pink-600 focus:ring-pink-500" onchange="toggleSizingModule()">
                                <span class="text-xs font-bold text-pink-600 dark:text-pink-500">Specify Sizes / Measurements</span>
                            </label>
                        </div>

                        <div class="flex items-center gap-4">
                            <div class="w-1/3">
                                <label class="block text-[10px] font-bold text-gray-500 dark:text-zinc-500 mb-1 uppercase">Total Quantity</label>
                                <input type="number" id="cp_total_quantity" min="1" value="1" class="w-full px-4 py-2.5 bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 text-gray-900 dark:text-white rounded-lg focus:ring-2 focus:ring-pink-500 outline-none transition-all text-sm font-bold shadow-sm">
                            </div>
                            <div id="qty-warning" class="w-2/3 hidden">
                                <p class="text-[10px] font-semibold text-amber-600 dark:text-amber-500"><i class="fa-solid fa-circle-info"></i> Quantity is locked. It will auto-calculate based on your Standard Sizing breakdown below.</p>
                            </div>
                        </div>

                        <div id="sizing-area" class="hidden space-y-4 pt-2 border-t border-gray-200 dark:border-zinc-800">
                            
                            <div class="flex gap-6">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" name="sizing_type" value="standard" checked onchange="switchSizingType()" class="text-pink-600 focus:ring-pink-500">
                                    <span class="text-sm font-bold text-gray-700 dark:text-zinc-300">Standard Sizes (S, M, L)</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" name="sizing_type" value="custom" onchange="switchSizingType()" class="text-pink-600 focus:ring-pink-500">
                                    <span class="text-sm font-bold text-gray-700 dark:text-zinc-300">Custom Measurements</span>
                                </label>
                            </div>

                            <div id="standard-sizing-wrapper" class="space-y-2">
                                <table class="w-full text-left">
                                    <thead>
                                        <tr class="text-[10px] font-extrabold text-gray-500 dark:text-zinc-400 uppercase tracking-widest border-b border-gray-200 dark:border-zinc-700">
                                            <th class="pb-2 w-2/3">Size Label (e.g., Small, 32, XL)</th>
                                            <th class="pb-2 w-1/4">Qty</th>
                                            <th class="pb-2 w-8"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="standard-sizing-tbody">
                                        </tbody>
                                </table>
                                <button type="button" onclick="addStandardSizeRow()" class="mt-2 text-[11px] font-bold text-pink-600 hover:text-pink-700 transition-colors focus:outline-none">
                                    <i class="fa-solid fa-plus bg-pink-100 p-1 rounded"></i> Add Size
                                </button>
                            </div>

                            <div id="custom-sizing-wrapper" class="hidden space-y-2">
                                <table class="w-full text-left">
                                    <thead>
                                        <tr class="text-[10px] font-extrabold text-gray-500 dark:text-zinc-400 uppercase tracking-widest border-b border-gray-200 dark:border-zinc-700">
                                            <th class="pb-2 w-1/2">Body Part (e.g., Waist, Chest)</th>
                                            <th class="pb-2 w-1/4">Measurement</th>
                                            <th class="pb-2 w-1/4">Unit</th>
                                            <th class="pb-2 w-8"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="custom-sizing-tbody">
                                        </tbody>
                                </table>
                                <button type="button" onclick="addCustomMeasureRow()" class="mt-2 text-[11px] font-bold text-pink-600 hover:text-pink-700 transition-colors focus:outline-none">
                                    <i class="fa-solid fa-plus bg-pink-100 p-1 rounded"></i> Add Measurement
                                </button>
                            </div>

                        </div>
                    </div>

                    <hr class="border-gray-100 dark:border-zinc-800">

                    <div>
                        <label class="block text-xs font-bold text-gray-600 dark:text-zinc-400 mb-3 uppercase tracking-wide">Production Workflow</label>
                        <div class="grid grid-cols-2 gap-4">
                            <label class="relative flex cursor-pointer rounded-xl border border-gray-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-4 shadow-sm focus:outline-none has-[:checked]:border-pink-600 has-[:checked]:bg-pink-50 dark:has-[:checked]:bg-pink-900/10 transition-all">
                                <input type="radio" name="workflow_type" value="customer" class="sr-only" checked onchange="toggleWorkflow()">
                                <div class="flex items-center gap-3">
                                    <div class="text-pink-600 dark:text-pink-500 text-xl"><i class="fa-solid fa-users"></i></div>
                                    <div>
                                        <p class="text-sm font-bold text-gray-900 dark:text-white">Make-to-Order</p>
                                        <p class="text-[10px] font-semibold text-gray-500 dark:text-zinc-400 mt-0.5">For a specific client/customer</p>
                                    </div>
                                </div>
                            </label>
                            <label class="relative flex cursor-pointer rounded-xl border border-gray-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-4 shadow-sm focus:outline-none has-[:checked]:border-amber-500 has-[:checked]:bg-amber-50 dark:has-[:checked]:bg-amber-900/10 transition-all">
                                <input type="radio" name="workflow_type" value="internal" class="sr-only" onchange="toggleWorkflow()">
                                <div class="flex items-center gap-3">
                                    <div class="text-amber-500 text-xl"><i class="fa-solid fa-boxes-stacked"></i></div>
                                    <div>
                                        <p class="text-sm font-bold text-gray-900 dark:text-white">Make-to-Stock</p>
                                        <p class="text-[10px] font-semibold text-gray-500 dark:text-zinc-400 mt-0.5">Internal shop inventory restock</p>
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div id="section-customer" class="space-y-4 bg-gray-50 dark:bg-zinc-950 p-4 rounded-xl border border-gray-100 dark:border-zinc-800">
                        <div class="flex justify-between items-center mb-2">
                            <label class="block text-xs font-bold text-gray-600 dark:text-zinc-400 uppercase tracking-wide">Select Client</label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" id="new-customer-toggle" class="rounded border-gray-300 text-pink-600 focus:ring-pink-500" onchange="toggleNewCustomer()">
                                <span class="text-xs font-bold text-pink-600 dark:text-pink-500">Insert New Client</span>
                            </label>
                        </div>
                        
                        <div id="existing-customer-div">
                            <select id="cp_existing_customer" class="w-full px-4 py-3 bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 text-gray-900 dark:text-white rounded-xl focus:ring-2 focus:ring-pink-500 outline-none transition-all text-sm font-medium shadow-sm">
                                <option value="">-- Choose Existing Client --</option>
                                <?php foreach($customers as $c): ?>
                                    <option value="<?= $c['customer_id'] ?>"><?= htmlspecialchars($c['full_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div id="new-customer-div" class="hidden space-y-4">
                            <input type="text" id="cp_new_cust_name" placeholder="Full Name / Organization" class="w-full px-4 py-3 bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 text-gray-900 dark:text-white rounded-xl focus:ring-2 focus:ring-pink-500 outline-none text-sm shadow-sm">
                            <div class="grid grid-cols-2 gap-4">
                                <input type="text" id="cp_new_cust_contact" placeholder="Contact Number" class="w-full px-4 py-3 bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 text-gray-900 dark:text-white rounded-xl focus:ring-2 focus:ring-pink-500 outline-none text-sm shadow-sm">
                                <input type="text" id="cp_new_cust_address" placeholder="Address (Optional)" class="w-full px-4 py-3 bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 text-gray-900 dark:text-white rounded-xl focus:ring-2 focus:ring-pink-500 outline-none text-sm shadow-sm">
                            </div>
                        </div>
                    </div>

                    <div id="section-internal" class="hidden space-y-4 bg-amber-50/50 dark:bg-amber-900/10 p-4 rounded-xl border border-amber-100 dark:border-amber-900/30">
                        <label class="block text-xs font-bold text-amber-700 dark:text-amber-500 uppercase tracking-wide">Target Product (Finished Goods)</label>
                        <select id="cp_target_product" class="w-full px-4 py-3 bg-white dark:bg-zinc-900 border border-amber-200 dark:border-amber-800/50 text-gray-900 dark:text-white rounded-xl focus:ring-2 focus:ring-amber-500 outline-none transition-all text-sm font-medium shadow-sm">
                            <option value="">-- Choose Product to Restock --</option>
                            <?php foreach($products as $p): ?>
                                <option value="<?= $p['product_id'] ?>"><?= htmlspecialchars($p['product_name']) ?> (Size: <?= htmlspecialchars($p['size']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                </form>
            </div>

            <div class="px-6 py-4 border-t border-gray-100 dark:border-zinc-800 bg-gray-50/50 dark:bg-zinc-950/30 flex justify-end gap-3">
                <button type="button" onclick="closeCreateProjectModal()" class="px-5 py-2.5 text-sm font-bold text-gray-600 dark:text-zinc-400 hover:bg-gray-100 dark:hover:bg-zinc-800 rounded-xl transition-colors focus:outline-none">
                    Cancel
                </button>
                <button type="button" onclick="submitNewProject(false)" class="bg-white dark:bg-zinc-800 border border-gray-200 dark:border-zinc-700 text-gray-700 dark:text-zinc-300 hover:text-pink-600 hover:border-pink-200 px-5 py-2.5 rounded-xl transition-all text-sm font-bold shadow-sm focus:outline-none">
                    Save Only
                </button>
                <button type="button" onclick="submitNewProject(true)" class="bg-pink-600 hover:bg-pink-700 text-white px-5 py-2.5 rounded-xl text-sm font-bold transition-all shadow-md shadow-pink-600/20 focus:outline-none flex items-center gap-2">
                    Save & Proceed to Costing <i class="fa-solid fa-arrow-right"></i>
                </button>
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
                <button onclick="closeCostingModal()" class="text-gray-400 hover:text-rose-500 transition-colors focus:outline-none">
                    <i class="fa-solid fa-xmark text-xl"></i>
                </button>
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
                    <button type="button" id="btn-delete-costing" onclick="deleteCostingBreakdown()" class="hidden bg-rose-100 hover:bg-rose-200 text-rose-600 px-4 py-2.5 rounded-xl text-sm font-bold transition-all focus:outline-none">
                        <i class="fa-solid fa-eraser"></i> Clear Breakdown
                    </button>
                    <button type="button" onclick="saveCosting()" class="bg-pink-600 hover:bg-pink-700 text-white px-6 py-2.5 rounded-xl text-sm font-bold transition-all shadow-md shadow-pink-600/20 cursor-pointer focus:outline-none">
                        <i class="fa-solid fa-floppy-disk mr-1.5"></i> Save All
                    </button>
                </div>
            </div>


        </div>
    </div>

</main>

<div id="view-details-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm transition-opacity" onclick="closeViewDetailsModal()"></div>
    
    <div class="relative bg-white dark:bg-zinc-900 rounded-2xl w-full max-w-4xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh] border border-gray-100 dark:border-zinc-800">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-zinc-800 flex justify-between items-center bg-gray-50/50 dark:bg-zinc-950/30">
            <div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white" id="vd_project_name">Project Details</h3>
                <p class="text-xs font-bold text-pink-600 dark:text-pink-500 uppercase tracking-widest mt-1" id="vd_project_id">#PRJ-000</p>
            </div>
            <div class="flex items-center gap-3">
                <button onclick="closeViewDetailsModal()" class="text-gray-400 hover:text-rose-500 transition-colors focus:outline-none">
                    <i class="fa-solid fa-xmark text-xl"></i>
                </button>
            </div>
        </div>

        <div class="p-6 overflow-y-auto flex-1 bg-gray-50/30 dark:bg-zinc-950/30">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                
                <div class="space-y-6">
                    <div class="bg-white dark:bg-zinc-900 p-5 rounded-xl border border-gray-100 dark:border-zinc-800 shadow-sm">
                        <h4 class="text-xs font-extrabold text-gray-500 uppercase tracking-widest mb-4 border-b border-gray-100 dark:border-zinc-800 pb-2">Client / Target</h4>
                        <div id="vd_client_info" class="text-sm text-gray-800 dark:text-zinc-200 font-medium">
                            </div>
                    </div>

                    <div class="bg-white dark:bg-zinc-900 p-5 rounded-xl border border-gray-100 dark:border-zinc-800 shadow-sm flex justify-between">
                        <div>
                            <p class="text-[10px] font-extrabold text-gray-500 uppercase tracking-widest">Total Quantity</p>
                            <p class="text-xl font-bold text-gray-900 dark:text-white mt-1" id="vd_quantity">0</p>
                        </div>
                        <div class="text-right">
                            <p class="text-[10px] font-extrabold text-gray-500 uppercase tracking-widest">Due Date</p>
                            <p class="text-sm font-bold text-gray-900 dark:text-white mt-1" id="vd_due_date">...</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-zinc-900 p-5 rounded-xl border border-gray-100 dark:border-zinc-800 shadow-sm">
                    <h4 class="text-xs font-extrabold text-gray-500 uppercase tracking-widest mb-4 border-b border-gray-100 dark:border-zinc-800 pb-2">Sizing & Measurements</h4>
                    <div id="vd_sizing_content" class="text-sm text-gray-800 dark:text-zinc-300">
                        </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
    // ==========================================
    // 1. CREATE PROJECT WIZARD LOGIC
    // ==========================================
    function openCreateProjectModal() {
        document.getElementById('create-project-modal').classList.remove('hidden');
    }

    function closeCreateProjectModal() {
        document.getElementById('create-project-modal').classList.add('hidden');
    }

    function toggleWorkflow() {
        const isCustomer = document.querySelector('input[name="workflow_type"][value="customer"]').checked;
        const sectionCustomer = document.getElementById('section-customer');
        const sectionInternal = document.getElementById('section-internal');

        if (isCustomer) {
            sectionCustomer.classList.remove('hidden');
            sectionInternal.classList.add('hidden');
        } else {
            sectionCustomer.classList.add('hidden');
            sectionInternal.classList.remove('hidden');
        }
    }

    function toggleNewCustomer() {
        const isNew = document.getElementById('new-customer-toggle').checked;
        const existingDiv = document.getElementById('existing-customer-div');
        const newDiv = document.getElementById('new-customer-div');

        if (isNew) {
            existingDiv.classList.add('hidden');
            newDiv.classList.remove('hidden');
        } else {
            existingDiv.classList.remove('hidden');
            newDiv.classList.add('hidden');
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
            <td class="py-2 pr-2">
                <input type="text" placeholder="e.g., Medium" class="sizing-label w-full px-3 py-1.5 bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-lg text-sm outline-none focus:border-pink-500">
            </td>
            <td class="py-2 pr-2">
                <input type="number" min="1" value="1" class="sizing-qty w-full px-3 py-1.5 bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-lg text-sm outline-none focus:border-pink-500" oninput="calculateTotalStandardQuantity()">
            </td>
            <td class="py-2 text-right">
                <button type="button" onclick="this.closest('tr').remove(); calculateTotalStandardQuantity();" class="text-gray-400 hover:text-rose-500 focus:outline-none p-1">
                    <i class="fa-solid fa-trash text-[10px]"></i>
                </button>
            </td>
        `;
        tbody.appendChild(tr);
        calculateTotalStandardQuantity();
    }

    function calculateTotalStandardQuantity() {
        if (!document.getElementById('enable-sizing-toggle').checked) return;
        if (document.querySelector('input[name="sizing_type"]:checked').value !== 'standard') return;
        
        let total = 0;
        document.querySelectorAll('#standard-sizing-tbody .sizing-qty').forEach(input => {
            total += parseInt(input.value) || 0;
        });
        
        document.getElementById('cp_total_quantity').value = total > 0 ? total : 1;
    }

    function addCustomMeasureRow() {
        const tbody = document.getElementById('custom-sizing-tbody');
        const tr = document.createElement('tr');
        tr.className = "border-b border-gray-100 dark:border-zinc-800/50";
        tr.innerHTML = `
            <td class="py-2 pr-2">
                <input type="text" placeholder="e.g., Chest" class="measure-part w-full px-3 py-1.5 bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-lg text-sm outline-none focus:border-pink-500">
            </td>
            <td class="py-2 pr-2">
                <input type="number" step="0.25" placeholder="0.00" class="measure-val w-full px-3 py-1.5 bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-lg text-sm outline-none focus:border-pink-500">
            </td>
            <td class="py-2 pr-2">
                <select class="measure-unit w-full px-3 py-1.5 bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-lg text-sm outline-none focus:border-pink-500">
                    <option value="inches">inches</option>
                    <option value="cm">cm</option>
                </select>
            </td>
            <td class="py-2 text-right">
                <button type="button" onclick="this.closest('tr').remove();" class="text-gray-400 hover:text-rose-500 focus:outline-none p-1">
                    <i class="fa-solid fa-trash text-[10px]"></i>
                </button>
            </td>
        `;
        tbody.appendChild(tr);
    }

    async function submitNewProject(proceedToCosting) {
        const formData = new FormData();
        
        formData.append('project_name', document.getElementById('cp_project_name').value);
        formData.append('quantity', document.getElementById('cp_total_quantity').value);
        formData.append('due_date', document.getElementById('cp_due_date').value);
        formData.append('agreed_price', 0.00); 

        const isSizingEnabled = document.getElementById('enable-sizing-toggle').checked;
        if (isSizingEnabled) {
            const sizingType = document.querySelector('input[name="sizing_type"]:checked').value;
            formData.append('sizing_type', sizingType);

            let sizingData = [];
            if (sizingType === 'standard') {
                document.querySelectorAll('#standard-sizing-tbody tr').forEach(row => {
                    sizingData.push({
                        label: row.querySelector('.sizing-label').value,
                        qty: row.querySelector('.sizing-qty').value
                    });
                });
            } else {
                document.querySelectorAll('#custom-sizing-tbody tr').forEach(row => {
                    sizingData.push({
                        part: row.querySelector('.measure-part').value,
                        val: row.querySelector('.measure-val').value,
                        unit: row.querySelector('.measure-unit').value
                    });
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
            const response = await fetch('actions/save_project.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();

            if (result.status === 'success') {
                closeCreateProjectModal();
                if (proceedToCosting) {
                    openCostingModal(result.project_id, result.project_name);
                } else {
                    window.location.reload();
                }
            } else {
                alert('Error: ' + result.message);
            }
        } catch (error) {
            console.error('Error saving project:', error);
            alert('A network error occurred.');
        }
    }


    // ==========================================
    // 2. COSTING, PROFIT & CURRENCY LOGIC
    // ==========================================
    
    // Helper function to format numbers with commas and 2 decimals
    function formatCurrency(number) {
        return parseFloat(number).toLocaleString('en-US', { 
            minimumFractionDigits: 2, 
            maximumFractionDigits: 2 
        });
    }

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
                result.data.forEach(item => {
                    addCostingRow(item.material_id, item.quantity_used, item.unit_cost);
                });
                if(deleteBtn) deleteBtn.classList.remove('hidden');
            } else {
                addCostingRow();
                if(deleteBtn) deleteBtn.classList.add('hidden');
            }
            calculateGrandTotal();
        } catch (error) {
            tbody.innerHTML = '';
            addCostingRow();
        }
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
            <td class="py-3 pr-4">
                <select class="w-full bg-gray-50 dark:bg-zinc-950 border border-gray-200 dark:border-zinc-800 text-gray-900 dark:text-white text-sm font-medium rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent p-2.5 outline-none transition-all shadow-sm" onchange="updateRowData(this)">
                    ${optionsHtml}
                </select>
            </td>
            <td class="py-3 pr-4">
                <input type="number" min="1" value="${prefillQty}" class="qty-input w-full bg-gray-50 dark:bg-zinc-950 border border-gray-200 dark:border-zinc-800 text-gray-900 dark:text-white text-sm font-bold rounded-lg p-2.5 outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent transition-all shadow-sm" oninput="calculateRowTotal(this)">
            </td>
            <td class="py-3 pr-4">
                <span class="uom-display text-[10px] font-extrabold text-gray-400 dark:text-zinc-500 uppercase tracking-widest bg-gray-100 dark:bg-zinc-800 px-2 py-1.5 rounded-md">${selectedUom}</span>
            </td>
            <td class="py-3 pr-4 relative">
                <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 font-bold text-sm">₱</span>
                <input type="number" step="0.01" value="${parseFloat(prefillPrice).toFixed(2)}" class="price-input w-full bg-gray-50 dark:bg-zinc-950 border border-gray-200 dark:border-zinc-800 text-gray-900 dark:text-white text-sm font-bold rounded-lg pl-8 p-2.5 outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent transition-all shadow-sm" oninput="calculateRowTotal(this)">
            </td>
            <td class="py-3 text-right font-extrabold text-gray-900 dark:text-white row-total-display text-sm">
                ₱ ${formatCurrency(prefillQty * prefillPrice)}
            </td>
            <td class="py-3 text-right">
                <button type="button" onclick="this.closest('tr').remove(); calculateGrandTotal();" class="text-gray-300 hover:text-rose-500 dark:text-zinc-600 dark:hover:text-rose-500 transition-colors focus:outline-none p-2 opacity-0 group-hover:opacity-100">
                    <i class="fa-solid fa-trash"></i>
                </button>
            </td>
        `;
        tbody.appendChild(tr);
    }

    function closeCostingModal() {
        document.getElementById('costing-modal').classList.add('hidden');
    }

    function updateRowData(selectElement) {
        const selectedOption = selectElement.options[selectElement.selectedIndex];
        const price = selectedOption.getAttribute('data-price') || 0;
        const uom = selectedOption.getAttribute('data-uom') || '--';
        
        const row = selectElement.closest('tr');
        row.querySelector('.price-input').value = parseFloat(price).toFixed(2);
        row.querySelector('.uom-display').textContent = uom;
        
        calculateRowTotal(row.querySelector('.price-input'));
    }

    function calculateRowTotal(inputElement) {
        const row = inputElement.closest('tr');
        const qty = parseFloat(row.querySelector('.qty-input').value) || 0;
        const price = parseFloat(row.querySelector('.price-input').value) || 0;
        
        const total = qty * price;
        row.querySelector('.row-total-display').textContent = '₱ ' + formatCurrency(total);
        calculateGrandTotal();
    }

    function calculateGrandTotal() {
        let grandTotal = 0;
        document.querySelectorAll('#costing-tbody tr').forEach(row => {
            const qty = parseFloat(row.querySelector('.qty-input').value) || 0;
            const price = parseFloat(row.querySelector('.price-input').value) || 0;
            grandTotal += (qty * price);
        });
        
        document.getElementById('grand-total-display').textContent = '₱ ' + formatCurrency(grandTotal);
        calculateProfitUI(); 
    }

    function calculateProfitUI() {
        const rawTotalText = document.getElementById('grand-total-display').textContent.replace('₱', '').replace(/,/g, '').trim();
        const totalCost = parseFloat(rawTotalText) || 0;
        const agreedPriceInput = document.getElementById('modal-agreed-price');
        
        if(!agreedPriceInput) return; 
        const agreedPrice = parseFloat(agreedPriceInput.value) || 0;
        
        const profit = agreedPrice - totalCost;
        const profitDisplay = document.getElementById('est-profit-display');
        
        if(profitDisplay) {
            profitDisplay.textContent = '₱ ' + formatCurrency(profit);
            if (profit > 0) {
                profitDisplay.className = "text-xl font-extrabold text-emerald-500";
            } else if (agreedPrice === 0) {
                profitDisplay.className = "text-xl font-extrabold text-gray-400";
            } else {
                profitDisplay.className = "text-xl font-extrabold text-rose-500";
            }
        }
    }

    async function saveCosting() {
        const projectId = document.getElementById('modal-project-id').value;
        const agreedPriceInput = document.getElementById('modal-agreed-price');
        const agreedPrice = agreedPriceInput ? agreedPriceInput.value : 0;
        const rows = document.querySelectorAll('#costing-tbody tr');
        let materialsData = [];

        rows.forEach(row => {
            const selectElement = row.querySelector('select');
            const materialId = selectElement.value;
            if (materialId !== "") {
                const qty = row.querySelector('.qty-input').value;
                const price = row.querySelector('.price-input').value;
                materialsData.push({
                    material_id: materialId,
                    quantity: qty,
                    unit_price: price
                });
            }
        });

        if (materialsData.length === 0 && agreedPrice == 0) {
            alert("Please add at least one material or set a valid Agreed Price.");
            return;
        }

        const payload = {
            project_id: projectId,
            agreed_price: agreedPrice, 
            materials: materialsData
        };

        const saveBtn = document.querySelector('button[onclick="saveCosting()"]');
        const originalBtnHtml = saveBtn.innerHTML;

        try {
            saveBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1.5"></i> Saving...';
            saveBtn.disabled = true;

            const response = await fetch('actions/save_costing.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });

            const rawText = await response.text();

            try {
                const result = JSON.parse(rawText);
                if (result.status === 'success') {
                    closeCostingModal();
                    window.location.reload(); 
                } else {
                    alert('Backend Logic Error:\n' + result.message);
                    saveBtn.innerHTML = originalBtnHtml;
                    saveBtn.disabled = false;
                }
            } catch (jsonError) {
                console.error("Raw Server Response:", rawText);
                alert("PHP Fatal Error! The server responded with HTML instead of JSON.\n\n" + rawText.substring(0, 300));
                saveBtn.innerHTML = originalBtnHtml;
                saveBtn.disabled = false;
            }

        } catch (error) {
            console.error('Fetch network error:', error);
            alert('True Network Error:\n' + error.message);
            saveBtn.innerHTML = originalBtnHtml;
            saveBtn.disabled = false;
        }
    }

    async function deleteCostingBreakdown() {
        if (!confirm("Are you sure you want to delete this entire costing breakdown? This cannot be undone.")) return;
        const projectId = document.getElementById('modal-project-id').value;
        try {
            const response = await fetch('actions/delete_costing.php', {
                method: 'POST',
                body: JSON.stringify({ project_id: projectId }),
                headers: { 'Content-Type': 'application/json' }
            });
            const result = await response.json();
            if(result.status === 'success') {
                window.location.reload();
            } else {
                alert("Error deleting breakdown.");
            }
        } catch (error) {
            alert("Network error while deleting.");
        }
    }

    // ==========================================
    // 3. ARCHIVE, RESTORE & VIEW DETAILS LOGIC
    // ==========================================
    
    // UPDATED: Now prompts to Archive instead of permanent delete
    async function archiveProject(projectId) {
        if (!confirm("Archive this project? It will be moved to the Archived tab to keep your dashboard clean.")) return;
        
        try {
            const response = await fetch('actions/delete_project.php', {
                method: 'POST',
                body: JSON.stringify({ project_id: projectId }),
                headers: { 'Content-Type': 'application/json' }
            });
            const result = await response.json();
            if(result.status === 'success') {
                window.location.reload();
            } else {
                alert("Error: " + result.message);
            }
        } catch (e) {
            alert("Network error.");
        }
    }

    // NEW: Function to restore a project
    async function restoreProject(projectId) {
        if (!confirm("Restore this project back to the active list?")) return;
        
        try {
            const response = await fetch('actions/restore_project.php', {
                method: 'POST',
                body: JSON.stringify({ project_id: projectId }),
                headers: { 'Content-Type': 'application/json' }
            });
            const result = await response.json();
            if(result.status === 'success') {
                window.location.reload();
            } else {
                alert("Error: " + result.message);
            }
        } catch (e) {
            alert("Network error.");
        }
    }

    function closeViewDetailsModal() {
        document.getElementById('view-details-modal').classList.add('hidden');
    }

    async function viewProjectDetails(projectId) {
        document.getElementById('view-details-modal').classList.remove('hidden');
        document.getElementById('vd_project_id').textContent = "#PRJ-2026-" + String(projectId).padStart(3, '0');
        
        try {
            const response = await fetch(`actions/get_project_details.php?project_id=${projectId}`);
            const result = await response.json();
            
            if(result.status === 'success') {
                const p = result.project;
                document.getElementById('vd_project_name').textContent = p.project_name;
                document.getElementById('vd_quantity').textContent = p.quantity + " pcs";
                document.getElementById('vd_due_date').textContent = p.due_date || 'No Date Set';
                
                let clientHtml = '';
                if (p.customer_id) {
                    clientHtml = `
                        <p class="font-bold text-pink-600"><i class="fa-solid fa-user mr-2"></i>${p.customer_name}</p>
                        <p class="text-xs text-gray-500 mt-1"><i class="fa-solid fa-phone mr-2"></i>${p.contact_number || 'No contact'}</p>
                    `;
                } else {
                    clientHtml = `
                        <p class="font-bold text-amber-600"><i class="fa-solid fa-boxes-stacked mr-2"></i>Internal Restock</p>
                        <p class="text-xs text-gray-500 mt-1">Target: ${p.internal_product} (${p.internal_size})</p>
                    `;
                }
                document.getElementById('vd_client_info').innerHTML = clientHtml;
                
                let sizingHtml = '';
                if (result.sizes && result.sizes.length > 0) {
                    sizingHtml = '<ul class="space-y-2">';
                    result.sizes.forEach(s => {
                        sizingHtml += `<li class="flex justify-between border-b border-gray-100 dark:border-zinc-800 pb-1"><span>Size ${s.size_label}</span> <span class="font-bold">${s.quantity} pcs</span></li>`;
                    });
                    sizingHtml += '</ul>';
                } else if (result.measurements && result.measurements.length > 0) {
                    sizingHtml = '<ul class="space-y-2">';
                    result.measurements.forEach(m => {
                        sizingHtml += `<li class="flex justify-between border-b border-gray-100 dark:border-zinc-800 pb-1"><span>${m.body_part}</span> <span class="font-bold text-pink-600">${m.measurement_value} ${m.unit}</span></li>`;
                    });
                    sizingHtml += '</ul>';
                } else {
                    sizingHtml = '<p class="text-gray-400 italic">No specific sizing breakdown provided.</p>';
                }
                document.getElementById('vd_sizing_content').innerHTML = sizingHtml;
            }
        } catch (e) {
            document.getElementById('vd_client_info').innerHTML = "<p class='text-rose-500'>Error loading details.</p>";
        }
    }
</script>

<?php include 'includes/footer.php'; ?>