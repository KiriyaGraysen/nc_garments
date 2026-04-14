<?php
$page_title = "Customers and Payments | NC Garments";
require_once('config/database.php');

$view_archived = (isset($_GET['view']) && $_GET['view'] === 'archived') ? 1 : 0;

// Fetch Customers with Dynamic Balance Calculations
$stmt = $conn->prepare('
    SELECT 
        c.customer_id, c.full_name, c.contact_number, c.address, c.is_archived,
        COALESCE((SELECT SUM(agreed_price) FROM project WHERE customer_id = c.customer_id AND is_archived = 0), 0) as total_billed,
        COALESCE((SELECT SUM(amount_paid) FROM payment pay JOIN project p ON pay.project_id = p.project_id WHERE p.customer_id = c.customer_id), 0) as total_paid,
        (SELECT MAX(payment_date) FROM payment pay JOIN project p ON pay.project_id = p.project_id WHERE p.customer_id = c.customer_id) as latest_payment_date
    FROM customer c
    WHERE c.is_archived = ?
    ORDER BY c.full_name ASC
');
$stmt->bind_param("i", $view_archived);
$stmt->execute();
$customers_result = $stmt->get_result();

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

    <div class="flex gap-6 border-b border-gray-200 dark:border-zinc-800 mb-6">
        <a href="?view=active" class="pb-3 text-sm font-bold transition-colors <?= $view_archived === 0 ? 'border-b-2 border-pink-600 text-pink-600 dark:text-pink-500' : 'text-gray-500 hover:text-gray-700 dark:text-zinc-400' ?>">
            <i class="fa-solid fa-address-book mr-1.5"></i> Active Customers
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
                        <th class="px-6 py-4 text-left text-[10px] font-extrabold text-gray-500 dark:text-zinc-500 uppercase tracking-widest">Customer Profile</th>
                        <th class="px-6 py-4 text-left text-[10px] font-extrabold text-gray-500 dark:text-zinc-500 uppercase tracking-widest">Contact Information</th>
                        <th class="px-6 py-4 text-left text-[10px] font-extrabold text-gray-500 dark:text-zinc-500 uppercase tracking-widest">Financial Standing</th>
                        <th class="px-6 py-4 text-left text-[10px] font-extrabold text-gray-500 dark:text-zinc-500 uppercase tracking-widest">Latest Activity</th>
                        <th class="px-6 py-4 text-right text-[10px] font-extrabold text-gray-500 dark:text-zinc-500 uppercase tracking-widest">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 dark:divide-zinc-800/50 text-sm transition-colors duration-500">
                    <?php
                    if ($customers_result->num_rows === 0) {
                        echo '<tr><td colspan="5" class="px-6 py-8 text-center text-gray-500">No customers found.</td></tr>';
                    }

                    while ($cust = $customers_result->fetch_assoc()) {
                        $initials = strtoupper(substr($cust['full_name'], 0, 2));
                        $balance = $cust['total_billed'] - $cust['total_paid'];
                        
                        $badge_class = $balance > 0 ? 'bg-rose-50 text-rose-600 dark:bg-rose-500/10 dark:text-rose-400 border-rose-200 dark:border-rose-500/20' : 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400 border-emerald-200 dark:border-emerald-500/20';
                        $badge_text = $balance > 0 ? 'With Balance' : 'Cleared';
                        
                        $latest_date = $cust['latest_payment_date'] ? date('M d, Y', strtotime($cust['latest_payment_date'])) : 'No Payments Yet';

                        echo '
                        <tr class="hover:bg-gray-50/80 dark:hover:bg-zinc-800/30 transition-colors group cursor-pointer" onclick="viewCustomerDetails('.$cust['customer_id'].')">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="h-10 w-10 rounded-full bg-pink-100 dark:bg-pink-900/30 text-pink-600 dark:text-pink-500 flex items-center justify-center font-extrabold text-sm border border-pink-200 dark:border-pink-800/50">
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
                            <td class="px-6 py-4 text-right text-sm font-medium">
                                <button onclick="event.stopPropagation(); openCustomerModal('.$cust['customer_id'].', \''.addslashes($cust['full_name'] ?? '').'\', \''.addslashes($cust['contact_number'] ?? '').'\', \''.addslashes($cust['address'] ?? '').'\')" class="text-gray-400 hover:text-pink-600 focus:outline-none p-2" title="Edit Customer">
                                    <i class="fa-solid fa-pen"></i>
                                </button>';
                                
                        if ($view_archived === 0) {
                            echo '<button onclick="event.stopPropagation(); archiveCustomer('.$cust['customer_id'].')" class="text-gray-400 hover:text-rose-600 focus:outline-none p-2" title="Archive Customer">
                                      <i class="fa-solid fa-trash"></i>
                                  </button>';
                        }

                        echo '</td>
                        </tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
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

            <div class="space-y-4 border-l pl-6 border-gray-200 dark:border-zinc-800">
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
                    <select id="pay_method" class="w-full px-4 py-3 bg-gray-50 dark:bg-zinc-950 border border-gray-200 dark:border-zinc-800 text-gray-900 dark:text-white rounded-xl focus:ring-2 focus:ring-pink-500 focus:border-transparent outline-none transition-all text-sm font-medium">
                        <option value="Cash">Cash</option>
                        <option value="GCash">GCash</option>
                        <option value="Bank Transfer">Bank Transfer</option>
                        <option value="Check">Check</option>
                    </select>
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

<script>
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
            full_name: document.getElementById('cm_name').value,
            contact_number: document.getElementById('cm_contact').value,
            address: document.getElementById('cm_address').value
        };

        if(!payload.full_name) return alert("Name is required!");

        try {
            const res = await fetch('actions/save_customer.php', {
                method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload)
            });
            const data = await res.json();
            if(data.status === 'success') window.location.reload();
            else alert("Error: " + data.message);
        } catch (e) { alert("Network Error"); }
    }

    async function archiveCustomer(id) {
        if(!confirm("Archive this customer?")) return;
        try {
            const res = await fetch('actions/delete_customer.php', {
                method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ customer_id: id })
            });
            const data = await res.json();
            if(data.status === 'success') window.location.reload();
        } catch (e) { alert("Network Error"); }
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

                // Render Payments
                let payHtml = '';
                if(data.payments.length === 0) payHtml = '<p class="text-sm text-gray-500 dark:text-zinc-500 italic">No payments recorded.</p>';
                
                data.payments.forEach(pay => {
                    payHtml += `
                    <div class="border-b border-gray-100 dark:border-zinc-800 pb-3 mb-3">
                        <p class="font-bold text-emerald-600 dark:text-emerald-500">+ ₱${formatCurrency(pay.amount_paid)} <span class="text-xs text-gray-400 dark:text-zinc-500 font-normal">(${pay.payment_method})</span></p>
                        <p class="text-xs text-gray-600 dark:text-zinc-300 mt-0.5">For: ${pay.project_name}</p>
                        <p class="text-[10px] text-gray-400 dark:text-zinc-500 mt-0.5">${new Date(pay.payment_date).toLocaleDateString()}</p>
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
        document.getElementById('pay_amount').value = maxAmount; // Auto-fill with remaining balance
        document.getElementById('pay_ref').value = '';
        document.getElementById('payment-modal').classList.remove('hidden');
    }

    function closePaymentModal() { document.getElementById('payment-modal').classList.add('hidden'); }

    async function savePayment() {
        const payload = {
            project_id: document.getElementById('pay_project_id').value,
            amount_paid: document.getElementById('pay_amount').value,
            payment_method: document.getElementById('pay_method').value,
            reference_number: document.getElementById('pay_ref').value
        };

        if(payload.amount_paid <= 0) return alert("Amount must be greater than zero.");

        try {
            const res = await fetch('actions/save_payment.php', {
                method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload)
            });
            const data = await res.json();
            if(data.status === 'success') window.location.reload();
            else alert("Error: " + data.message);
        } catch (e) { alert("Network Error"); }
    }
</script>

<?php include 'includes/footer.php'; ?>
