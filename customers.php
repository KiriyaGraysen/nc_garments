<?php
$page_title = "Customers and Payments | NC Garments";
require_once('config/database.php');

$view_archived = (isset($_GET['view']) && $_GET['view'] === 'archived') ? 1 : 0;

// 1. Setup Sorting Logic
$sort = $_GET['sort'] ?? 'name_asc';

switch ($sort) {
    case 'name_desc': $order_by = "c.full_name DESC"; break;
    case 'billed_desc': $order_by = "total_billed DESC"; break;
    case 'paid_desc': $order_by = "total_paid DESC"; break;
    case 'balance_desc': $order_by = "(total_billed - total_paid) DESC"; break;
    case 'recent_pay': $order_by = "latest_payment_date DESC"; break;
    default: $order_by = "c.full_name ASC"; break; // Default is name_asc
}

// 2. Fetch Customers with Dynamic Balance Calculations AND Sorting
$stmt = $conn->prepare("
    SELECT 
        c.customer_id, c.full_name, c.contact_number, c.address, c.is_archived,
        COALESCE((SELECT SUM(agreed_price) FROM project WHERE customer_id = c.customer_id AND is_archived = 0), 0) as total_billed,
        COALESCE((SELECT SUM(amount_paid) FROM payment pay JOIN project p ON pay.project_id = p.project_id WHERE p.customer_id = c.customer_id), 0) as total_paid,
        (SELECT MAX(payment_date) FROM payment pay JOIN project p ON pay.project_id = p.project_id WHERE p.customer_id = c.customer_id) as latest_payment_date
    FROM customer c
    WHERE c.is_archived = ?
    ORDER BY $order_by
");
$stmt->bind_param("i", $view_archived);
$stmt->execute();
$customers_result = $stmt->get_result();

// Fetch unique payment methods used in the past
$pm_stmt = $conn->prepare("SELECT DISTINCT payment_method FROM payment WHERE payment_method IS NOT NULL AND payment_method != ''");
$pm_stmt->execute();
$db_methods_result = $pm_stmt->get_result();

// Define our mandatory defaults
$default_methods = ['Cash', 'GCash', 'Bank Transfer'];
$final_methods = [];

// Add defaults to our final list using lowercase as the "Key" to prevent duplicates
foreach ($default_methods as $method) {
    $final_methods[strtolower($method)] = $method;
}

// Loop through database history and add them only if they don't already exist
while ($row = $db_methods_result->fetch_assoc()) {
    $db_method = trim($row['payment_method']);
    $lower_key = strtolower($db_method);
    
    // If it's not already in our list, add it!
    if (!isset($final_methods[$lower_key])) {
        $final_methods[$lower_key] = $db_method;
    }
}

// Sort them alphabetically for a clean dropdown UI
sort($final_methods);

function format_contact_number($phone) {
    if (empty($phone)) return '';
    
    // Remove any spaces or existing dashes the user might have typed
    $clean = preg_replace('/[^0-9]/', '', $phone);
    
    // Check if it is a standard 11-digit PH mobile number (starts with 09)
    if (strlen($clean) === 11 && substr($clean, 0, 2) === '09') {
        // Format to 09XX-XXX-XXXX
        return substr($clean, 0, 4) . '-' . substr($clean, 4, 3) . '-' . substr($clean, 7);
    }
    
    // If it's a landline or international number, just return it as they typed it
    return $phone; 
}

include 'includes/header.php'; 
?>

<main class="flex-1 p-4 md:p-8 overflow-y-auto transition-colors duration-500 font-sans relative">
    
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white transition-colors duration-500">Customers & Payments</h2>
            <p class="text-gray-500 dark:text-zinc-400 text-sm mt-1 transition-colors duration-500">Manage your client directory, track billing histories, and record incoming payments.</p>
        </div>
        <button onclick="openCustomerModal()" class="bg-pink-600 hover:bg-pink-700 text-white px-5 py-2.5 rounded-xl text-sm font-bold transition-all duration-300 shadow-lg shadow-pink-600/20 flex items-center gap-2 cursor-pointer focus:outline-none">
            <i class="fa-solid fa-user-plus"></i> Add New Customer
        </button>
    </div>

    <div class="flex flex-col lg:flex-row justify-between items-center mb-6 gap-4">
        
        <div class="flex w-full lg:w-auto gap-3 flex-1 max-w-2xl">
            <div class="relative w-full group">
                <i class="fa-solid fa-search absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400 group-focus-within:text-pink-600 transition-colors duration-500"></i>
                <input type="text" id="search-input" placeholder="Search by customer name or ID..." 
                       class="w-full pl-11 pr-4 py-3 border border-gray-200 dark:border-zinc-800 rounded-xl bg-white dark:bg-zinc-900/50 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-zinc-500 focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent transition-colors duration-500 shadow-sm text-sm font-medium">
            </div>
            
            <div class="relative w-48 shrink-0">
                <select onchange="window.location.href=this.value" class="w-full px-4 py-3 border border-gray-200 dark:border-zinc-800 rounded-xl bg-white dark:bg-zinc-900/50 text-gray-700 dark:text-zinc-300 focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent transition-colors duration-500 shadow-sm text-sm font-bold cursor-pointer appearance-none">
                    <option value="?view=<?= $view_archived === 1 ? 'archived' : 'active' ?>&sort=name_asc" <?= $sort == 'name_asc' ? 'selected' : '' ?>>Name (A-Z)</option>
                    <option value="?view=<?= $view_archived === 1 ? 'archived' : 'active' ?>&sort=name_desc" <?= $sort == 'name_desc' ? 'selected' : '' ?>>Name (Z-A)</option>
                    <option value="?view=<?= $view_archived === 1 ? 'archived' : 'active' ?>&sort=billed_desc" <?= $sort == 'billed_desc' ? 'selected' : '' ?>>Highest Billed</option>
                    <option value="?view=<?= $view_archived === 1 ? 'archived' : 'active' ?>&sort=paid_desc" <?= $sort == 'paid_desc' ? 'selected' : '' ?>>Highest Paid</option>
                    <option value="?view=<?= $view_archived === 1 ? 'archived' : 'active' ?>&sort=balance_desc" <?= $sort == 'balance_desc' ? 'selected' : '' ?>>Highest Balance</option>
                    <option value="?view=<?= $view_archived === 1 ? 'archived' : 'active' ?>&sort=recent_pay" <?= $sort == 'recent_pay' ? 'selected' : '' ?>>Recent Payments</option>
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
                <i class="fa-solid fa-address-book mr-1.5"></i> Active Customers
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
                        <th class="px-6 py-4 text-left text-[10px] font-extrabold text-gray-500 dark:text-zinc-500 uppercase tracking-widest">Customer Profile</th>
                        <th class="px-6 py-4 text-left text-[10px] font-extrabold text-gray-500 dark:text-zinc-500 uppercase tracking-widest">Contact Information</th>
                        <th class="px-6 py-4 text-left text-[10px] font-extrabold text-gray-500 dark:text-zinc-500 uppercase tracking-widest">Financial Standing</th>
                        <th class="px-6 py-4 text-left text-[10px] font-extrabold text-gray-500 dark:text-zinc-500 uppercase tracking-widest">Latest Activity</th>
                        <th class="px-6 py-4 text-center text-[10px] font-extrabold text-gray-500 dark:text-zinc-500 uppercase tracking-widest">Actions</th>
                    </tr>
                </thead>
                <tbody id="customer-tbody" class="divide-y divide-gray-50 dark:divide-zinc-800/50 text-sm transition-colors duration-500">
                    <?php
                    if ($customers_result->num_rows === 0) {
                        echo '<tr id="php-empty-state"><td colspan="5" class="px-6 py-8 text-center text-gray-500 font-medium">No customers found.</td></tr>';
                    }

                    while ($cust = $customers_result->fetch_assoc()) {
                        $initials = strtoupper(substr($cust['full_name'], 0, 2));
                        $balance = $cust['total_billed'] - $cust['total_paid'];
                        
                        $badge_class = $balance > 0 ? 'bg-rose-50 text-rose-600 dark:bg-rose-500/10 dark:text-rose-400 border-rose-200 dark:border-rose-500/20' : 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400 border-emerald-200 dark:border-emerald-500/20';
                        $badge_text = $balance > 0 ? 'With Balance' : 'Cleared';
                        
                        $latest_date = $cust['latest_payment_date'] ? date('M d, Y', strtotime($cust['latest_payment_date'])) : 'No Payments Yet';

                        echo '
                        <tr class="customer-row hover:bg-gray-50/80 dark:hover:bg-zinc-800/30 transition-colors group cursor-pointer" onclick="viewCustomerDetails('.$cust['customer_id'].')">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="h-10 w-10 rounded-full bg-pink-100 dark:bg-pink-900/30 text-pink-600 dark:text-pink-500 flex items-center justify-center font-extrabold text-sm border border-pink-200 dark:border-pink-800/50 shrink-0">
                                        '.$initials.'
                                    </div>
                                    <div>
                                        <div class="font-bold text-gray-900 dark:text-white group-hover:text-pink-600 transition-colors">'.htmlspecialchars($cust['full_name']).'</div>
                                        <div class="text-xs font-bold tracking-wider text-gray-400 mt-0.5">ID: CUST-'.str_pad($cust['customer_id'], 4, '0', STR_PAD_LEFT).'</div>
                                    </div>
                                </div>
                            </td>
                            
                            <td class="px-6 py-4">
                                <div class="text-gray-700 dark:text-zinc-300 font-medium text-xs flex items-center gap-2">
                                    <i class="fa-solid fa-phone text-gray-400"></i> '.(htmlspecialchars(format_contact_number($cust['contact_number'] ?? '')) ?: 'N/A').'
                                </div>
                                <div class="text-gray-500 dark:text-zinc-400 font-medium text-xs flex items-start gap-2 mt-1.5 whitespace-normal break-words max-w-[250px]">
                                    <i class="fa-solid fa-location-dot text-gray-400 mt-0.5"></i> 
                                    <span class="leading-relaxed">'.(htmlspecialchars($cust['address'] ?? '') ?: 'N/A').'</span>
                                </div>
                            </td>
                            
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <span class="text-[10px] font-extrabold px-2.5 py-1 rounded-md uppercase tracking-wider border '.$badge_class.'">
                                        '.$badge_text.'
                                    </span>
                                </div>
                                <div class="text-[11px] font-bold text-gray-900 dark:text-white mt-2">
                                    Bal: <span class="'.($balance > 0 ? 'text-rose-500' : 'text-gray-500').' text-sm font-extrabold">₱ '.number_format($balance, 2).'</span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-[10px] font-bold text-gray-400 dark:text-zinc-500 mt-1 uppercase tracking-wider">'.$latest_date.'</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-center gap-1.5">
                                    <button onclick="event.stopPropagation(); openCustomerModal('.$cust['customer_id'].', \''.addslashes($cust['full_name'] ?? '').'\', \''.addslashes($cust['contact_number'] ?? '').'\', \''.addslashes($cust['address'] ?? '').'\')" class="relative group/btn flex items-center justify-center w-8 h-8 bg-white dark:bg-zinc-800 border border-gray-200 dark:border-zinc-700 hover:border-blue-300 text-gray-400 hover:text-blue-500 rounded-lg transition-all duration-300 shadow-sm focus:outline-none">
                                        <i class="fa-solid fa-pen transition-colors"></i>
                                        <span class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 px-2.5 py-1 text-[10px] font-bold text-white bg-gray-900 dark:bg-black rounded-md opacity-0 group-hover/btn:opacity-100 transition-opacity whitespace-nowrap pointer-events-none z-50 shadow-lg">
                                            Edit Customer
                                            <span class="absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent border-t-gray-900 dark:border-t-black"></span>
                                        </span>
                                    </button>';
                                
                        if ($view_archived === 0) {
                            echo '<button onclick="event.stopPropagation(); archiveCustomer('.$cust['customer_id'].')" class="relative group/btn flex items-center justify-center w-8 h-8 bg-white dark:bg-zinc-800 border border-gray-200 dark:border-zinc-700 hover:border-amber-300 text-gray-400 hover:text-amber-500 rounded-lg transition-all duration-300 shadow-sm focus:outline-none">
                                      <i class="fa-solid fa-box-archive transition-colors"></i>
                                      <span class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 px-2.5 py-1 text-[10px] font-bold text-white bg-gray-900 dark:bg-black rounded-md opacity-0 group-hover/btn:opacity-100 transition-opacity whitespace-nowrap pointer-events-none z-50 shadow-lg">
                                          Archive Customer
                                          <span class="absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent border-t-gray-900 dark:border-t-black"></span>
                                      </span>
                                  </button>';
                        } else {
                            echo '<button onclick="event.stopPropagation(); restoreCustomer('.$cust['customer_id'].')" class="relative group/btn flex items-center justify-center w-8 h-8 bg-white dark:bg-zinc-800 border border-gray-200 dark:border-zinc-700 hover:border-emerald-300 text-gray-400 hover:text-emerald-500 rounded-lg transition-all duration-300 shadow-sm focus:outline-none">
                                      <i class="fa-solid fa-clock-rotate-left transition-colors"></i>
                                      <span class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 px-2.5 py-1 text-[10px] font-bold text-white bg-gray-900 dark:bg-black rounded-md opacity-0 group-hover/btn:opacity-100 transition-opacity whitespace-nowrap pointer-events-none z-50 shadow-lg">
                                          Restore Customer
                                          <span class="absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent border-t-gray-900 dark:border-t-black"></span>
                                      </span>
                                  </button>';
                        }

                        echo '      </div>
                                </td>
                        </tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
        
        <div id="pagination-container" class="w-full bg-gray-50/50 dark:bg-zinc-950/30 rounded-b-2xl transition-colors duration-500"></div>
    </div>
</main>

<div id="customer-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm transition-opacity" onclick="closeCustomerModal()"></div>
    <div class="relative bg-white dark:bg-zinc-900 rounded-2xl w-full max-w-md shadow-2xl overflow-hidden flex flex-col max-h-[90vh] border border-gray-100 dark:border-zinc-800">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-zinc-800 flex justify-between items-center bg-gray-50/50 dark:bg-zinc-950/30">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white" id="cm_title">Add New Customer</h3>
            <button onclick="closeCustomerModal()" class="text-gray-400 hover:text-rose-500 transition-colors focus:outline-none">
                <i class="fa-solid fa-xmark text-xl"></i>
            </button>
        </div>
        <div class="p-6 overflow-y-auto flex-1">
            <form id="customer-form" class="space-y-5">
                <input type="hidden" id="cm_id">
                <div>
                    <label class="block text-xs font-bold text-gray-600 dark:text-zinc-400 mb-2 uppercase tracking-wide">Full Name / Organization *</label>
                    <input type="text" id="cm_name" required class="w-full px-4 py-3 bg-gray-50 dark:bg-zinc-950 border border-gray-200 dark:border-zinc-800 text-gray-900 dark:text-white rounded-xl focus:ring-2 focus:ring-pink-500 focus:border-transparent outline-none transition-all text-sm font-medium placeholder-gray-400 dark:placeholder-zinc-600">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-600 dark:text-zinc-400 mb-2 uppercase tracking-wide">Contact Number</label>
                    <input type="text" id="cm_contact" class="w-full px-4 py-3 bg-gray-50 dark:bg-zinc-950 border border-gray-200 dark:border-zinc-800 text-gray-900 dark:text-white rounded-xl focus:ring-2 focus:ring-pink-500 focus:border-transparent outline-none transition-all text-sm font-medium placeholder-gray-400 dark:placeholder-zinc-600">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-600 dark:text-zinc-400 mb-2 uppercase tracking-wide">Address</label>
                    <input type="text" id="cm_address" class="w-full px-4 py-3 bg-gray-50 dark:bg-zinc-950 border border-gray-200 dark:border-zinc-800 text-gray-900 dark:text-white rounded-xl focus:ring-2 focus:ring-pink-500 focus:border-transparent outline-none transition-all text-sm font-medium placeholder-gray-400 dark:placeholder-zinc-600">
                </div>
            </form>
        </div>
        <div class="px-6 py-4 border-t border-gray-100 dark:border-zinc-800 bg-gray-50/50 dark:bg-zinc-950/30 flex justify-end gap-3">
            <button onclick="closeCustomerModal()" class="px-5 py-2.5 text-sm font-bold text-gray-600 dark:text-zinc-400 hover:bg-gray-100 dark:hover:bg-zinc-800 rounded-xl transition-colors focus:outline-none">Cancel</button>
            <button onclick="saveCustomer()" class="bg-pink-600 hover:bg-pink-700 text-white px-5 py-2.5 rounded-xl text-sm font-bold transition-all shadow-md shadow-pink-600/20 focus:outline-none">Save Customer</button>
        </div>
    </div>
</div>

<div id="details-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm transition-opacity" onclick="closeDetailsModal()"></div>
    <div class="relative bg-white dark:bg-zinc-900 rounded-2xl w-full max-w-4xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh] border border-gray-100 dark:border-zinc-800">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-zinc-800 flex justify-between items-center bg-gray-50/50 dark:bg-zinc-950/30">
            <div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white" id="det_name">Customer Name</h3>
                <p class="text-xs font-bold text-pink-600 dark:text-pink-500 uppercase tracking-widest mt-1" id="det_id">CUST-000</p>
            </div>
            <button onclick="closeDetailsModal()" class="text-gray-400 hover:text-rose-500 transition-colors focus:outline-none">
                <i class="fa-solid fa-xmark text-xl"></i>
            </button>
        </div>
        <div class="p-6 overflow-y-auto flex-1 grid grid-cols-1 lg:grid-cols-3 gap-6 bg-gray-50/30 dark:bg-zinc-950/30">
            
            <div class="lg:col-span-2 space-y-4">
                <h4 class="text-xs font-extrabold text-gray-500 dark:text-zinc-500 uppercase tracking-widest border-b border-gray-200 dark:border-zinc-800 pb-2">Active Projects & Billing</h4>
                <div id="det_projects" class="space-y-3">
                    </div>
            </div>

            <div class="space-y-4 lg:border-l lg:pl-6 border-gray-200 dark:border-zinc-800">
                <h4 class="text-xs font-extrabold text-gray-500 dark:text-zinc-500 uppercase tracking-widest border-b border-gray-200 dark:border-zinc-800 pb-2">Recent Payments</h4>
                <div id="det_payments" class="space-y-3">
                    </div>
            </div>

        </div>
    </div>
</div>

<div id="payment-modal" class="fixed inset-0 z-[60] hidden flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm transition-opacity" onclick="closePaymentModal()"></div>
    <div class="relative bg-white dark:bg-zinc-900 rounded-2xl w-full max-w-sm shadow-2xl overflow-hidden flex flex-col border border-gray-100 dark:border-zinc-800">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-zinc-800 flex justify-between items-center bg-gray-50/50 dark:bg-zinc-950/30">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white">Record Payment</h3>
            <button onclick="closePaymentModal()" class="text-gray-400 hover:text-rose-500 transition-colors focus:outline-none">
                <i class="fa-solid fa-xmark text-xl"></i>
            </button>
        </div>
        <div class="p-6">
            <form id="payment-form" class="space-y-5">
                <input type="hidden" id="pay_project_id">
                <div>
                    <label class="block text-xs font-bold text-gray-600 dark:text-zinc-400 mb-2 uppercase tracking-wide">Amount Paid (₱) *</label>
                    <input type="number" id="pay_amount" step="0.01" required class="w-full px-4 py-3 bg-gray-50 dark:bg-zinc-950 border border-gray-200 dark:border-zinc-800 text-gray-900 dark:text-white rounded-xl focus:ring-2 focus:ring-pink-500 focus:border-transparent outline-none transition-all text-lg font-bold">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-600 dark:text-zinc-400 mb-2 uppercase tracking-wide">Payment Method *</label>
                    <input list="payment_methods_list" id="pay_method" placeholder="Select or type a method..." required class="w-full px-4 py-3 bg-gray-50 dark:bg-zinc-950 border border-gray-200 dark:border-zinc-800 text-gray-900 dark:text-white rounded-xl focus:ring-2 focus:ring-pink-500 focus:border-transparent outline-none transition-all text-sm font-medium">
                    <datalist id="payment_methods_list">
                        <?php 
                        foreach($final_methods as $method) {
                            echo '<option value="' . htmlspecialchars($method) . '"></option>';
                        }
                        ?>
                    </datalist>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-600 dark:text-zinc-400 mb-2 uppercase tracking-wide">Reference No. (Optional)</label>
                    <input type="text" id="pay_ref" placeholder="e.g., GCash Ref No." class="w-full px-4 py-3 bg-gray-50 dark:bg-zinc-950 border border-gray-200 dark:border-zinc-800 text-gray-900 dark:text-white rounded-xl focus:ring-2 focus:ring-pink-500 focus:border-transparent outline-none transition-all text-sm font-medium placeholder-gray-400 dark:placeholder-zinc-600">
                </div>
            </form>
        </div>
        <div class="px-6 py-4 border-t border-gray-100 dark:border-zinc-800 bg-gray-50/50 dark:bg-zinc-950/30 flex justify-end gap-3">
            <button onclick="closePaymentModal()" class="px-5 py-2.5 text-sm font-bold text-gray-600 dark:text-zinc-400 hover:bg-gray-100 dark:hover:bg-zinc-800 rounded-xl transition-colors focus:outline-none">Cancel</button>
            <button onclick="savePayment()" class="bg-pink-600 hover:bg-pink-700 text-white px-5 py-2.5 rounded-xl text-sm font-bold transition-all shadow-md shadow-pink-600/20 focus:outline-none">Confirm Payment</button>
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

<script>
    // ==========================================
    // 0. GLOBAL UI OVERRIDES (REPLACING NATIVE ALERTS/CONFIRMS)
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
                icon.className = "fa-solid fa-clock-rotate-left";
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

    // --- Pagination & Search Logic ---
    const searchInput = document.getElementById('search-input');
    const tbody = document.getElementById('customer-tbody');
    const allRows = Array.from(tbody.querySelectorAll('tr.customer-row'));
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
                tbody.insertAdjacentHTML('beforeend', `<tr id="js-empty-state"><td colspan="${colspanCount}" class="px-6 py-8 text-center text-gray-500 font-medium">No customers found matching your search.</td></tr>`);
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

    // ------------------------------------

    function formatCurrency(num) {
        return parseFloat(num).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    // --- CUSTOMER ADD/EDIT LOGIC ---
    function openCustomerModal(id = '', name = '', contact = '', address = '') {
        document.getElementById('cm_id').value = id;
        document.getElementById('cm_name').value = name;
        document.getElementById('cm_contact').value = contact;
        document.getElementById('cm_address').value = address;
        document.getElementById('cm_title').textContent = id ? "Edit Customer" : "Add New Customer";
        document.getElementById('customer-modal').classList.remove('hidden');
    }

    function closeCustomerModal() {
        document.getElementById('customer-modal').classList.add('hidden');
    }

    async function saveCustomer() {
        const payload = {
            customer_id: document.getElementById('cm_id').value,
            full_name: document.getElementById('cm_name').value.trim(),
            contact_number: document.getElementById('cm_contact').value.trim(),
            address: document.getElementById('cm_address').value.trim()
        };

        if(!payload.full_name) return customAlert("Name is required!", "Missing Field", "error");

        try {
            const res = await fetch('actions/save_customer.php', {
                method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload)
            });
            const data = await res.json();
            if(data.status === 'success') {
                customAlert("Customer saved successfully.", "Success", "success");
                setTimeout(() => window.location.reload(), 1500);
            }
            else customAlert("Error: " + data.message, "Error", "error");
        } catch (e) { customAlert("Network Error", "Error", "error"); }
    }

    async function archiveCustomer(id) {
        const isConfirmed = await customConfirm("Archive this customer?", "Archive Customer");
        if(!isConfirmed) return;
        try {
            const res = await fetch('actions/delete_customer.php', {
                method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ customer_id: id })
            });
            const data = await res.json();
            if(data.status === 'success') window.location.reload();
        } catch (e) { customAlert("Network Error", "Error", "error"); }
    }

    async function restoreCustomer(id) {
        const isConfirmed = await customConfirm("Restore this customer?", "Restore Customer", "Yes, Restore", "info");
        if(!isConfirmed) return;
        try {
            const res = await fetch('actions/restore_customer.php', {
                method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ customer_id: id })
            });
            const data = await res.json();
            if(data.status === 'success') window.location.reload();
        } catch (e) { customAlert("Network Error", "Error", "error"); }
    }

    // --- VIEW DETAILS LOGIC ---
    function closeDetailsModal() { document.getElementById('details-modal').classList.add('hidden'); }

    async function viewCustomerDetails(id) {
        document.getElementById('details-modal').classList.remove('hidden');
        document.getElementById('det_projects').innerHTML = '<p class="text-gray-500 text-sm"><i class="fa-solid fa-spinner fa-spin"></i> Loading...</p>';
        document.getElementById('det_payments').innerHTML = '';

        try {
            const res = await fetch(`actions/get_customer_details.php?customer_id=${id}`);
            const data = await res.json();

            if (data.status === 'success') {
                const c = data.customer;
                document.getElementById('det_name').textContent = c.full_name;
                document.getElementById('det_id').textContent = `CUST-${String(c.customer_id).padStart(4, '0')}`;

                // Render Projects
                let projHtml = '';
                if(data.projects.length === 0) projHtml = '<p class="text-sm text-gray-500 dark:text-zinc-500 italic">No active projects found.</p>';
                
                data.projects.forEach(p => {
                    const price = parseFloat(p.agreed_price);
                    const paid = parseFloat(p.total_paid);
                    const bal = price - paid;
                    
                    projHtml += `
                    <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 p-4 rounded-xl shadow-sm flex justify-between items-center transition-colors">
                        <div>
                            <p class="font-bold text-gray-900 dark:text-white">#PRJ-${p.project_id} - ${p.project_name}</p>
                            <p class="text-xs text-gray-500 dark:text-zinc-400 mt-1">Price: ₱${formatCurrency(price)} | Paid: ₱${formatCurrency(paid)}</p>
                            <p class="text-sm font-extrabold ${bal > 0 ? 'text-rose-500' : 'text-emerald-500'} mt-1">Balance: ₱${formatCurrency(bal)}</p>
                        </div>
                        ${bal > 0 ? `<button onclick="openPaymentModal(${p.project_id}, ${bal})" class="bg-emerald-100 dark:bg-emerald-500/20 text-emerald-700 dark:text-emerald-400 hover:bg-emerald-200 dark:hover:bg-emerald-500/30 px-3 py-1.5 rounded-lg text-xs font-bold transition-colors focus:outline-none"><i class="fa-solid fa-plus mr-1"></i> Pay</button>` : `<span class="text-emerald-500 text-xl"><i class="fa-solid fa-circle-check"></i></span>`}
                    </div>`;
                });
                document.getElementById('det_projects').innerHTML = projHtml;

                // Render Payments with Hover "Void" button
                let payHtml = '';
                if(data.payments.length === 0) payHtml = '<p class="text-sm text-gray-500 dark:text-zinc-500 italic">No payments recorded.</p>';
                
                data.payments.forEach(pay => {
                    payHtml += `
                    <div class="border-b border-gray-100 dark:border-zinc-800 pb-3 mb-3 flex justify-between items-start group/pay">
                        <div>
                            <p class="font-bold text-emerald-600 dark:text-emerald-500">+ ₱${formatCurrency(pay.amount_paid)} <span class="text-xs text-gray-400 dark:text-zinc-500 font-normal">(${pay.payment_method})</span></p>
                            <p class="text-xs text-gray-600 dark:text-zinc-300 mt-0.5">For: ${pay.project_name}</p>
                            <p class="text-[10px] text-gray-400 dark:text-zinc-500 mt-0.5">${new Date(pay.payment_date).toLocaleDateString()}</p>
                        </div>
                        <button onclick="voidPayment(${pay.payment_id})" class="text-[10px] font-bold text-rose-500 hover:text-rose-600 opacity-0 group-hover/pay:opacity-100 transition-opacity focus:outline-none bg-rose-50 dark:bg-rose-500/10 px-2 py-1 rounded border border-rose-200 dark:border-rose-500/20 uppercase tracking-widest mt-1">
                            Void
                        </button>
                    </div>`;
                });
                document.getElementById('det_payments').innerHTML = payHtml;
            }
        } catch (e) {
            document.getElementById('det_projects').innerHTML = '<p class="text-rose-500 text-sm">Error loading data.</p>';
        }
    }

    // --- ADD PAYMENT LOGIC ---
    function openPaymentModal(projectId, maxAmount) {
        document.getElementById('pay_project_id').value = projectId;
        document.getElementById('pay_amount').value = maxAmount; 
        document.getElementById('pay_method').value = ''; 
        document.getElementById('pay_ref').value = '';
        document.getElementById('payment-modal').classList.remove('hidden');
    }

    function closePaymentModal() { document.getElementById('payment-modal').classList.add('hidden'); }

    async function savePayment() {
        const payload = {
            project_id: document.getElementById('pay_project_id').value,
            amount_paid: document.getElementById('pay_amount').value,
            payment_method: document.getElementById('pay_method').value.trim(), 
            reference_number: document.getElementById('pay_ref').value.trim()
        };

        if(payload.amount_paid <= 0) return customAlert("Amount must be greater than zero.", "Invalid Amount", "error");
        if(!payload.payment_method) return customAlert("Please select or type a Payment Method.", "Missing Field", "error");

        try {
            const res = await fetch('actions/save_payment.php', {
                method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload)
            });
            const data = await res.json();
            if(data.status === 'success') {
                customAlert("Payment recorded.", "Success", "success");
                setTimeout(() => window.location.reload(), 1500);
            }
            else customAlert("Error: " + data.message, "Error", "error");
        } catch (e) { customAlert("Network Error", "Error", "error"); }
    }

    async function voidPayment(paymentId) {
        if (!paymentId) return customAlert("Payment ID missing.", "Error", "error");
        
        const isConfirmed = await customConfirm("Are you sure you want to VOID this payment?\n\nThis action cannot be undone and will add the amount back to the customer's balance.", "Void Payment", "Yes, Void", "danger");
        if (!isConfirmed) return;

        try {
            const res = await fetch('actions/void_payment.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ payment_id: paymentId })
            });
            const data = await res.json();
            if (data.status === 'success') {
                customAlert("Payment voided successfully.", "Success", "success");
                setTimeout(() => window.location.reload(), 1500);
            } else {
                customAlert("Error: " + data.message, "Error", "error");
            }
        } catch (e) { customAlert("Network Error while trying to void the payment.", "Error", "error"); }
    }
</script>

<?php include 'includes/footer.php'; ?>