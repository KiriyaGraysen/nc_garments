<?php
$page_title = "Orders & Projects | NC Garments";
require_once('config/database.php');

// 1. Fetch Projects (UPDATED: Now dynamically calculates total material cost!)
$stmt = $conn->prepare('
    SELECT 
        p.project_id, p.project_name, p.quantity, p.due_date, p.agreed_price, p.status, 
        c.full_name,
        COALESCE(SUM(pb.total_cost), 0) AS total_material_cost
    FROM project p
    LEFT JOIN customer c ON c.customer_id = p.customer_id
    LEFT JOIN premade_product pre ON pre.product_id = p.produced_product_id
    LEFT JOIN project_breakdown pb ON p.project_id = pb.project_id
    GROUP BY p.project_id
    ORDER BY p.project_id DESC
');
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
    
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white transition-colors duration-500">Orders & Projects</h2>
            <p class="text-gray-500 dark:text-zinc-400 text-sm mt-1 transition-colors duration-500">Manage customer orders, track production progress, and view cost breakdowns.</p>
        </div>
        <button onclick="openCreateProjectModal()" class="bg-pink-600 hover:bg-pink-700 text-white px-5 py-2.5 rounded-xl text-sm font-bold transition-all duration-300 shadow-lg shadow-pink-600/20 hover:shadow-pink-600/40 flex items-center gap-2 cursor-pointer focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-pink-500 dark:focus:ring-offset-zinc-950">
            <i class="fa-solid fa-folder-plus"></i> Create New Project
        </button>
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
                            <tr class="hover:bg-gray-50/80 dark:hover:bg-zinc-800/30 transition-colors group">
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
                                        <button onclick="editAgreedPrice(' . $project['project_id'] . ', ' . $agreed_price . ')" class="text-gray-400 hover:text-pink-600 dark:hover:text-pink-400 transition-colors focus:outline-none cursor-pointer" title="Edit Price">
                                            <i class="fa-solid fa-pen text-[10px]"></i>
                                        </button>
                                    </div>
                                    <div class="text-[11px] font-bold text-gray-500 dark:text-zinc-400 tracking-wide mt-1 uppercase">Cost: ₱ ' . number_format($material_cost, 2) . '</div>
                                    <div class="text-[11px] font-bold ' . $profit_color . ' tracking-wide mt-0.5 uppercase">Est. Profit: ₱ ' . number_format($est_profit, 2) . '</div>
                                </td>

                                
                                <td class="px-6 py-4 text-right text-sm font-medium">
                                    <button onclick="openCostingModal(' . $project['project_id'] . ', \'' . addslashes($project_name) . '\')" class="bg-white dark:bg-zinc-800 border border-gray-200 dark:border-zinc-700 text-gray-700 dark:text-zinc-300 hover:text-pink-600 dark:hover:text-pink-400 hover:border-pink-200 dark:hover:border-pink-900/50 px-3 py-1.5 rounded-lg transition-all mr-2 text-xs font-bold cursor-pointer shadow-sm focus:outline-none">
                                        <i class="fa-solid fa-file-invoice-dollar mr-1"></i> Costing
                                    </button>
                                    <button class="text-gray-400 hover:text-pink-600 dark:hover:text-pink-500 transition-colors cursor-pointer focus:outline-none p-1">
                                        <i class="fa-solid fa-chevron-right"></i>
                                    </button>
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
        
        <div class="relative bg-white dark:bg-zinc-900 rounded-2xl w-full max-w-2xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh] border border-gray-100 dark:border-zinc-800">
            
            <div class="px-6 py-4 border-b border-gray-100 dark:border-zinc-800 flex justify-between items-center bg-gray-50/50 dark:bg-zinc-950/30">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Create New Project</h3>
                <button onclick="closeCreateProjectModal()" class="text-gray-400 hover:text-rose-500 transition-colors focus:outline-none">
                    <i class="fa-solid fa-xmark text-xl"></i>
                </button>
            </div>

            <div class="p-6 overflow-y-auto flex-1">
                <form id="create-project-form" class="space-y-6">
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div class="col-span-2">
                            <label class="block text-xs font-bold text-gray-600 dark:text-zinc-400 mb-2 uppercase tracking-wide">Project Name / Description</label>
                            <input type="text" placeholder="e.g., LGU Polo Shirts" class="w-full px-4 py-3 bg-gray-50 dark:bg-zinc-950 border border-gray-200 dark:border-zinc-800 text-gray-900 dark:text-white rounded-xl focus:ring-2 focus:ring-pink-500 focus:border-transparent outline-none transition-all text-sm font-medium">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-600 dark:text-zinc-400 mb-2 uppercase tracking-wide">Quantity Needed</label>
                            <input type="number" min="1" value="1" class="w-full px-4 py-3 bg-gray-50 dark:bg-zinc-950 border border-gray-200 dark:border-zinc-800 text-gray-900 dark:text-white rounded-xl focus:ring-2 focus:ring-pink-500 outline-none transition-all text-sm font-medium">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-600 dark:text-zinc-400 mb-2 uppercase tracking-wide">Due Date</label>
                            <input type="date" class="w-full px-4 py-3 bg-gray-50 dark:bg-zinc-950 border border-gray-200 dark:border-zinc-800 text-gray-900 dark:text-white rounded-xl focus:ring-2 focus:ring-pink-500 outline-none transition-all text-sm font-medium">
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
                            <select class="w-full px-4 py-3 bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 text-gray-900 dark:text-white rounded-xl focus:ring-2 focus:ring-pink-500 outline-none transition-all text-sm font-medium shadow-sm">
                                <option value="">-- Choose Existing Client --</option>
                                <?php foreach($customers as $c): ?>
                                    <option value="<?= $c['customer_id'] ?>"><?= htmlspecialchars($c['full_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div id="new-customer-div" class="hidden space-y-4">
                            <input type="text" placeholder="Full Name / Organization" class="w-full px-4 py-3 bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 text-gray-900 dark:text-white rounded-xl focus:ring-2 focus:ring-pink-500 outline-none text-sm shadow-sm">
                            <div class="grid grid-cols-2 gap-4">
                                <input type="text" placeholder="Contact Number" class="w-full px-4 py-3 bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 text-gray-900 dark:text-white rounded-xl focus:ring-2 focus:ring-pink-500 outline-none text-sm shadow-sm">
                                <input type="text" placeholder="Address (Optional)" class="w-full px-4 py-3 bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 text-gray-900 dark:text-white rounded-xl focus:ring-2 focus:ring-pink-500 outline-none text-sm shadow-sm">
                            </div>
                        </div>
                    </div>

                    <div id="section-internal" class="hidden space-y-4 bg-amber-50/50 dark:bg-amber-900/10 p-4 rounded-xl border border-amber-100 dark:border-amber-900/30">
                        <label class="block text-xs font-bold text-amber-700 dark:text-amber-500 uppercase tracking-wide">Target Product (Finished Goods)</label>
                        <select class="w-full px-4 py-3 bg-white dark:bg-zinc-900 border border-amber-200 dark:border-amber-800/50 text-gray-900 dark:text-white rounded-xl focus:ring-2 focus:ring-amber-500 outline-none transition-all text-sm font-medium shadow-sm">
                            <option value="">-- Choose Product to Restock --</option>
                            <?php foreach($products as $p): ?>
                                <option value="<?= $p['product_id'] ?>"><?= htmlspecialchars($p['product_name']) ?> (Size: <?= htmlspecialchars($p['size']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                        <p class="text-[10px] font-semibold text-amber-600 dark:text-amber-500"><i class="fa-solid fa-circle-info"></i> Customer ID will be set to NULL automatically.</p>
                    </div>

                    <div class="bg-gray-50 dark:bg-zinc-950 p-4 rounded-xl border border-gray-100 dark:border-zinc-800">
                        <label class="block text-xs font-bold text-gray-600 dark:text-zinc-400 mb-2 uppercase tracking-wide">Agreed Price (Customer Charge)</label>
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400 font-bold text-sm">₱</span>
                            <input type="number" step="0.01" value="0.00" class="w-full pl-8 pr-4 py-3 bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 text-gray-900 dark:text-white rounded-xl focus:ring-2 focus:ring-pink-500 outline-none transition-all text-sm font-bold shadow-sm">
                        </div>
                        <p class="text-[10px] font-semibold text-gray-500 dark:text-zinc-400 mt-2">Leave as 0.00 if you need to calculate the Raw Material Cost first.</p>
                    </div>

                </form>
            </div>

            <div class="px-6 py-4 border-t border-gray-100 dark:border-zinc-800 bg-gray-50/50 dark:bg-zinc-950/30 flex justify-end gap-3">
                <button type="button" onclick="closeCreateProjectModal()" class="px-5 py-2.5 text-sm font-bold text-gray-600 dark:text-zinc-400 hover:bg-gray-100 dark:hover:bg-zinc-800 rounded-xl transition-colors focus:outline-none">
                    Cancel
                </button>
                <button type="button" onclick="submitNewProject(false)" class="bg-white dark:bg-zinc-800 border border-gray-200 dark:border-zinc-700 text-gray-700 dark:text-zinc-300 hover:text-pink-600 dark:hover:text-pink-400 hover:border-pink-200 px-5 py-2.5 rounded-xl transition-all text-sm font-bold shadow-sm focus:outline-none">
                    Save & Close
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
                <div class="flex flex-col">
                    <span class="text-xs font-extrabold text-gray-500 dark:text-zinc-500 uppercase tracking-widest">Grand Total Cost</span>
                    <span id="grand-total-display" class="text-2xl font-extrabold text-gray-900 dark:text-white">₱ 0.00</span>
                </div>
                <div class="flex gap-2">
                    <button type="button" id="btn-delete-costing" onclick="deleteCosting()" class="hidden bg-rose-100 hover:bg-rose-200 text-rose-600 px-4 py-2.5 rounded-xl text-sm font-bold transition-all focus:outline-none">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                    <button type="button" onclick="saveCosting()" class="bg-pink-600 hover:bg-pink-700 text-white px-6 py-2.5 rounded-xl text-sm font-bold transition-all shadow-md shadow-pink-600/20 cursor-pointer focus:outline-none focus:ring-2 focus:ring-pink-500">
                        <i class="fa-solid fa-floppy-disk mr-1.5"></i> Save / Update
                    </button>
                </div>
            </div>

        </div>
    </div>

</main>

<script>
    // --- CREATE PROJECT WIZARD LOGIC ---
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
    
    // --- SUBMIT NEW PROJECT WIZARD TO BACKEND ---
    async function submitNewProject(proceedToCosting) {
        // 1. Gather data from the form
        const formData = new FormData();
        
        // Basic Info
        formData.append('project_name', document.querySelector('input[placeholder="e.g., LGU Polo Shirts"]').value);
        formData.append('quantity', document.querySelector('input[type="number"][min="1"]').value);
        formData.append('due_date', document.querySelector('input[type="date"]').value);
        formData.append('agreed_price', document.querySelector('input[step="0.01"]').value);
        
        // Workflow Info
        const workflowType = document.querySelector('input[name="workflow_type"]:checked').value;
        formData.append('workflow_type', workflowType);

        if (workflowType === 'customer') {
            const isNewCustomer = document.getElementById('new-customer-toggle').checked;
            formData.append('is_new_customer', isNewCustomer);
            
            if (isNewCustomer) {
                formData.append('new_customer_name', document.querySelector('input[placeholder="Full Name / Organization"]').value);
                formData.append('new_customer_contact', document.querySelector('input[placeholder="Contact Number"]').value);
                formData.append('new_customer_address', document.querySelector('input[placeholder="Address (Optional)"]').value);
            } else {
                formData.append('existing_customer_id', document.querySelector('#existing-customer-div select').value);
            }
        } else {
            formData.append('target_product_id', document.querySelector('#section-internal select').value);
        }

        try {
            // 2. Send the data to our PHP script using Fetch API
            const response = await fetch('actions/save_project.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();

            if (result.status === 'success') {
                // Close the create project modal
                closeCreateProjectModal();
                
                if (proceedToCosting) {
                    // Open the Costing Modal with the newly created project data!
                    openCostingModal(result.project_id, result.project_name);
                } else {
                    // Just reload the page to show the new project in the table
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


    // --- COSTING MODAL LOGIC (Kept exactly as you designed it) ---
    const rawMaterials = <?php echo $materials_json; ?>;

    // --- UPDATED COSTING LOGIC ---
    async function openCostingModal(projectId, projectName) {
        document.getElementById('modal-project-id').value = projectId;
        document.getElementById('modal-project-name').textContent = projectName;
        const tbody = document.getElementById('costing-tbody');
        const deleteBtn = document.getElementById('btn-delete-costing');
        
        tbody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-sm text-gray-500"><i class="fa-solid fa-spinner fa-spin"></i> Loading...</td></tr>';
        document.getElementById('costing-modal').classList.remove('hidden');
        
        try {
            // Fetch existing breakdown data!
            const response = await fetch(`actions/get_costing.php?project_id=${projectId}`);
            const result = await response.json();
            
            tbody.innerHTML = ''; // Clear loading spinner
            
            if (result.status === 'success' && result.data.length > 0) {
                // If data exists, loop through and add pre-filled rows
                result.data.forEach(item => {
                    addCostingRow(item.material_id, item.quantity_used, item.unit_cost);
                });
                deleteBtn.classList.remove('hidden'); // Show delete button
            } else {
                // If no data, just add one empty row
                addCostingRow();
                deleteBtn.classList.add('hidden'); // Hide delete button
            }
            calculateGrandTotal();
        } catch (error) {
            tbody.innerHTML = '';
            addCostingRow();
        }
    }

    // Notice we added parameters here so we can pre-fill the data!
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
                ₱ ${(prefillQty * prefillPrice).toFixed(2)}
            </td>
            <td class="py-3 text-right">
                <button type="button" onclick="this.closest('tr').remove(); calculateGrandTotal();" class="text-gray-300 hover:text-rose-500 dark:text-zinc-600 dark:hover:text-rose-500 transition-colors focus:outline-none p-2 opacity-0 group-hover:opacity-100">
                    <i class="fa-solid fa-trash"></i>
                </button>
            </td>
        `;
        tbody.appendChild(tr);
    }

    // --- NEW: EDIT AGREED PRICE ---
    async function editAgreedPrice(projectId, currentPrice) {
        // Native prompt is the cleanest, most mobile-friendly way to ask for a single number
        const newPriceStr = prompt("Enter the new Agreed Price (₱):", currentPrice);
        
        if (newPriceStr !== null && newPriceStr.trim() !== "") {
            const newPrice = parseFloat(newPriceStr);
            if (isNaN(newPrice)) {
                alert("Please enter a valid number.");
                return;
            }

            try {
                const response = await fetch('actions/update_price.php', {
                    method: 'POST',
                    body: JSON.stringify({ project_id: projectId, new_price: newPrice }),
                    headers: { 'Content-Type': 'application/json' }
                });
                const result = await response.json();
                
                if(result.status === 'success') {
                    window.location.reload(); // Reload to show new price & profit
                } else {
                    alert("Error updating price: " + result.message);
                }
            } catch (error) {
                alert("Network error while updating price.");
            }
        }
    }

    // --- NEW: DELETE ENTIRE COSTING ---
    async function deleteCosting() {
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
        
        row.querySelector('.row-total-display').textContent = '₱ ' + (qty * price).toFixed(2);
        calculateGrandTotal();
    }

    function calculateGrandTotal() {
        let grandTotal = 0;
        document.querySelectorAll('#costing-tbody tr').forEach(row => {
            const qty = parseFloat(row.querySelector('.qty-input').value) || 0;
            const price = parseFloat(row.querySelector('.price-input').value) || 0;
            grandTotal += (qty * price);
        });
        document.getElementById('grand-total-display').textContent = '₱ ' + grandTotal.toFixed(2);
    }

    // --- SUBMIT COSTING BREAKDOWN TO BACKEND (WITH DEBUGGING) ---
    async function saveCosting() {
        const projectId = document.getElementById('modal-project-id').value;
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

        if (materialsData.length === 0) {
            alert("Please add at least one material to the costing breakdown.");
            return;
        }

        const payload = {
            project_id: projectId,
            materials: materialsData
        };

        const saveBtn = document.querySelector('button[onclick="saveCosting()"]');
        const originalBtnHtml = saveBtn.innerHTML;

        try {
            saveBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1.5"></i> Saving...';
            saveBtn.disabled = true;

            const response = await fetch('actions/save_costing.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload)
            });

            // IMPORTANT: We grab the raw text FIRST before trying to parse it as JSON
            const rawText = await response.text();

            try {
                // Now we try to convert it to JSON
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
                // If parsing fails, it means PHP outputted HTML (a fatal error!)
                console.error("Raw Server Response:", rawText);
                alert("PHP Fatal Error! The server responded with HTML instead of JSON.\n\n" + rawText.substring(0, 300) + "\n\n(Check your browser console for the full error).");
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


</script>

<?php include 'includes/footer.php'; ?>
