<?php
$page_title = "Point of Sale | NC Garments";
require_once('config/database.php');

// SECURITY KICK-OUT
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    header("Location: index.php");
    exit();
}

// 1. Fetch all available premade products (Only those with stock > 0)
$products_stmt = $conn->query("
    SELECT product_id, sku, product_name, size, current_stock, selling_price 
    FROM premade_product 
    WHERE is_archived = 0 AND current_stock > 0 
    ORDER BY product_name ASC, size ASC
");
$catalog = $products_stmt->fetch_all(MYSQLI_ASSOC);

// 2. Fetch unique payment methods for the datalist
$pm_stmt = $conn->query("SELECT DISTINCT payment_method FROM payment WHERE payment_method IS NOT NULL AND payment_method != '' UNION SELECT DISTINCT payment_method FROM retail_sale WHERE payment_method IS NOT NULL");
$default_methods = ['Cash', 'GCash', 'Bank Transfer'];
$final_methods = [];
foreach ($default_methods as $method) { $final_methods[strtolower($method)] = $method; }
while ($row = $pm_stmt->fetch_assoc()) {
    $lower_key = strtolower(trim($row['payment_method']));
    if (!isset($final_methods[$lower_key])) $final_methods[$lower_key] = trim($row['payment_method']);
}
sort($final_methods);

// 3. Fetch Today's Transactions for the Modal
$today_start = date('Y-m-d 00:00:00');
$today_end = date('Y-m-d 23:59:59');

$today_stmt = $conn->prepare("
    SELECT rs.sale_id, rs.total_amount, rs.payment_method, rs.sale_date, a.full_name as cashier
    FROM retail_sale rs
    LEFT JOIN admin a ON rs.processed_by_admin = a.admin_id
    WHERE rs.sale_date BETWEEN ? AND ?
    ORDER BY rs.sale_date DESC
");
$today_stmt->bind_param("ss", $today_start, $today_end);
$today_stmt->execute();
$today_sales = $today_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

include 'includes/header.php'; 
?>

<main class="flex-1 p-4 md:p-6 overflow-y-auto font-sans relative bg-gray-50 dark:bg-zinc-950">
    
    <div class="mb-4">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Point of Sale</h2>
        <p class="text-gray-500 dark:text-zinc-400 text-sm mt-1">Process walk-in retail sales and instantly deduct inventory.</p>
    </div>

    <div class="flex flex-col lg:flex-row gap-6 h-[calc(100vh-140px)]">
        
        <div class="flex-1 flex flex-col bg-white dark:bg-zinc-900 rounded-2xl shadow-sm border border-gray-100 dark:border-zinc-800 overflow-hidden">
            
            <div class="p-4 border-b border-gray-100 dark:border-zinc-800 bg-gray-50/50 dark:bg-zinc-950/30 flex justify-between items-center gap-4">
                <div class="relative w-full group max-w-xl">
                    <i class="fa-solid fa-search absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400 group-focus-within:text-pink-600 transition-colors"></i>
                    <input type="text" id="search_catalog" onkeyup="filterCatalog()" placeholder="Search by SKU, Name, or Size..." class="w-full pl-11 pr-4 py-3 border border-gray-200 dark:border-zinc-800 rounded-xl bg-white dark:bg-zinc-900 text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-pink-500 transition-colors shadow-sm text-sm">
                </div>

                <button onclick="openRecentSalesModal()" class="shrink-0 bg-gray-100 hover:bg-gray-200 dark:bg-zinc-800 dark:hover:bg-zinc-700 text-gray-700 dark:text-zinc-300 px-4 py-3 rounded-xl text-sm font-bold transition-all focus:outline-none border border-gray-200 dark:border-zinc-700 flex items-center gap-2">
                    <i class="fa-solid fa-clock-rotate-left text-pink-600 dark:text-pink-500"></i> 
                    <span class="hidden sm:inline">Today's Sales</span>
                </button>
            </div>
            
            <div class="p-4 overflow-y-auto flex-1 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 content-start" id="catalog_grid">
                <?php foreach ($catalog as $item): ?>
                <div class="product-card bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-xl p-4 hover:border-pink-300 dark:hover:border-pink-800 transition-colors cursor-pointer group flex flex-col justify-between" 
                     onclick="addToCart(<?= $item['product_id'] ?>, '<?= htmlspecialchars(addslashes($item['product_name'])) ?>', '<?= htmlspecialchars($item['size']) ?>', <?= $item['selling_price'] ?>, <?= $item['current_stock'] ?>)">
                    <div>
                        <div class="flex justify-between items-start mb-2">
                            <span class="text-[10px] font-extrabold text-pink-600 dark:text-pink-500 bg-pink-50 dark:bg-pink-500/10 px-2 py-1 rounded border border-pink-200 dark:border-pink-500/20 uppercase tracking-widest"><?= htmlspecialchars($item['sku']) ?></span>
                            <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400 flex items-center"><i class="fa-solid fa-boxes-stacked mr-1"></i> <?= $item['current_stock'] ?> left</span>
                        </div>
                        <h4 class="text-sm font-bold text-gray-900 dark:text-white mb-1 leading-tight group-hover:text-pink-600 transition-colors product-name"><?= htmlspecialchars($item['product_name']) ?> <span class="text-gray-400 product-size">(<?= htmlspecialchars($item['size']) ?>)</span></h4>
                    </div>
                    <div class="flex justify-between items-end mt-4">
                        <span class="text-lg font-black text-gray-900 dark:text-white tracking-tight">₱ <?= number_format($item['selling_price'], 2) ?></span>
                        <div class="h-8 w-8 rounded-full bg-gray-100 dark:bg-zinc-800 text-gray-400 group-hover:bg-pink-600 group-hover:text-white flex items-center justify-center transition-all shadow-sm">
                            <i class="fa-solid fa-plus"></i>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if(empty($catalog)): ?>
                    <div class="col-span-full text-center py-10 text-gray-500 font-medium italic">No premade products available or in stock.</div>
                <?php endif; ?>
            </div>
        </div>

        <div class="w-full lg:w-96 flex flex-col bg-white dark:bg-zinc-900 rounded-2xl shadow-sm border border-gray-100 dark:border-zinc-800 overflow-hidden shrink-0">
            <div class="px-5 py-4 border-b border-gray-100 dark:border-zinc-800 bg-gray-50/50 dark:bg-zinc-950/30 flex justify-between items-center">
                <h3 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider"><i class="fa-solid fa-receipt mr-2 text-gray-400"></i> Current Sale</h3>
                <button onclick="clearCart()" class="text-[10px] font-bold text-rose-500 hover:text-rose-600 uppercase tracking-widest focus:outline-none">Clear All</button>
            </div>
            
            <div class="flex-1 overflow-y-auto p-4 space-y-3" id="cart_container">
                <div class="h-full flex flex-col items-center justify-center text-gray-400 space-y-3 opacity-50" id="empty_cart_msg">
                    <i class="fa-solid fa-cart-arrow-down text-4xl"></i>
                    <p class="text-xs font-bold uppercase tracking-wider">Cart is empty</p>
                </div>
            </div>

            <div class="p-5 border-t border-gray-100 dark:border-zinc-800 bg-gray-50/50 dark:bg-zinc-950/30">
                <div class="flex justify-between items-center mb-4">
                    <span class="text-sm font-bold text-gray-500 uppercase tracking-wider">Total Amount</span>
                    <span class="text-2xl font-black text-pink-600 dark:text-pink-500" id="cart_total_display">₱ 0.00</span>
                </div>
                
                <div class="space-y-3 mb-4">
                    <div>
                        <input list="payment_methods_list" id="pos_payment_method" placeholder="Payment Method *" class="w-full px-3 py-2.5 bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-700 text-gray-900 dark:text-white rounded-lg focus:ring-2 focus:ring-pink-500 outline-none text-sm font-medium transition-all shadow-sm">
                        <datalist id="payment_methods_list">
                            <?php foreach($final_methods as $method) echo '<option value="'.htmlspecialchars($method).'"></option>'; ?>
                        </datalist>
                    </div>
                    <div>
                        <input type="text" id="pos_reference" placeholder="Reference No. (If GCash/Bank)" class="w-full px-3 py-2.5 bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-700 text-gray-900 dark:text-white rounded-lg focus:ring-2 focus:ring-pink-500 outline-none text-sm font-medium transition-all shadow-sm">
                    </div>
                </div>

                <button id="btn_checkout" onclick="processCheckout()" disabled class="w-full bg-pink-600 hover:bg-pink-700 text-white py-3.5 rounded-xl text-sm font-black uppercase tracking-widest transition-all shadow-lg shadow-pink-600/20 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2 focus:outline-none">
                    <i class="fa-solid fa-cash-register"></i> Complete Checkout
                </button>
            </div>
        </div>
        
    </div>

    <div id="recent-sales-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm transition-opacity" onclick="closeRecentSalesModal()"></div>
        <div class="relative bg-white dark:bg-zinc-900 rounded-2xl w-full max-w-2xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh] border border-gray-100 dark:border-zinc-800">
            
            <div class="px-6 py-4 border-b border-gray-100 dark:border-zinc-800 flex justify-between items-center bg-gray-50/50 dark:bg-zinc-950/30">
                <div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Today's Transactions</h3>
                    <p class="text-xs font-bold text-pink-600 dark:text-pink-500 uppercase tracking-widest mt-1"><?= date('F j, Y') ?></p>
                </div>
                <button onclick="closeRecentSalesModal()" class="text-gray-400 hover:text-rose-500 transition-colors focus:outline-none">
                    <i class="fa-solid fa-xmark text-xl"></i>
                </button>
            </div>
            
            <div class="flex-1 overflow-y-auto p-0">
                <?php if (empty($today_sales)): ?>
                    <div class="p-8 text-center text-gray-500 font-medium italic">No retail sales have been processed today yet.</div>
                <?php else: ?>
                    <table class="w-full text-left whitespace-nowrap">
                        <thead class="bg-gray-50 dark:bg-zinc-950/50 sticky top-0 border-b border-gray-100 dark:border-zinc-800 z-10">
                            <tr>
                                <th class="px-6 py-3 text-[10px] font-extrabold text-gray-500 dark:text-zinc-500 uppercase tracking-widest">Time & ID</th>
                                <th class="px-6 py-3 text-[10px] font-extrabold text-gray-500 dark:text-zinc-500 uppercase tracking-widest">Cashier</th>
                                <th class="px-6 py-3 text-[10px] font-extrabold text-gray-500 dark:text-zinc-500 uppercase tracking-widest">Payment</th>
                                <th class="px-6 py-3 text-right text-[10px] font-extrabold text-gray-500 dark:text-zinc-500 uppercase tracking-widest">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 dark:divide-zinc-800/50 text-sm">
                            <?php foreach ($today_sales as $sale): 
                                $sale_date = new DateTime($sale['sale_date']);
                            ?>
                            <tr class="hover:bg-gray-50/80 dark:hover:bg-zinc-800/30 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="font-bold text-gray-900 dark:text-white"><?= $sale_date->format('h:i A') ?></div>
                                    <div class="text-[10px] font-bold text-pink-600/70 dark:text-pink-500/70 mt-1 uppercase tracking-widest">#SALE-<?= str_pad($sale['sale_id'], 4, '0', STR_PAD_LEFT) ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="font-medium text-gray-700 dark:text-zinc-300 text-xs"><?= htmlspecialchars($sale['cashier'] ?? 'Unknown Admin') ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="font-bold text-xs flex items-center gap-1.5 uppercase tracking-wide <?= strtolower($sale['payment_method']) === 'cash' ? 'text-emerald-600 dark:text-emerald-400' : 'text-blue-600 dark:text-blue-400' ?>">
                                        <i class="fa-solid <?= strtolower($sale['payment_method']) === 'cash' ? 'fa-money-bill-wave' : 'fa-building-columns' ?>"></i>
                                        <?= htmlspecialchars($sale['payment_method']) ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex flex-col items-end">
                                        <span class="text-base font-extrabold text-emerald-600 dark:text-emerald-500">
                                            ₱ <?= number_format($sale['total_amount'], 2) ?>
                                        </span>
                                        <button onclick="voidSale(<?= $sale['sale_id'] ?>)" class="text-[10px] font-bold text-rose-500 hover:text-rose-600 mt-1 uppercase tracking-widest flex items-center gap-1 focus:outline-none transition-colors">
                                            <i class="fa-solid fa-ban"></i> Void Sale
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            
            <div class="px-6 py-4 border-t border-gray-100 dark:border-zinc-800 bg-gray-50/50 dark:bg-zinc-950/30 flex justify-end">
                <button onclick="closeRecentSalesModal()" class="px-5 py-2.5 text-sm font-bold text-gray-600 dark:text-zinc-400 hover:bg-gray-100 dark:hover:bg-zinc-800 rounded-xl transition-colors focus:outline-none">Close</button>
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

    // Overwrite the native functions entirely for standard calls
    window.alert = customAlert;

    // --- POS LOGIC ---

    let cart = [];

    const formatMoney = (num) => parseFloat(num).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    function filterCatalog() {
        const query = document.getElementById('search_catalog').value.toLowerCase();
        const cards = document.querySelectorAll('.product-card');
        
        cards.forEach(card => {
            const text = card.textContent.toLowerCase();
            card.style.display = text.includes(query) ? 'flex' : 'none';
        });
    }

    function addToCart(id, name, size, price, maxStock) {
        const existingItem = cart.find(item => item.id === id);
        
        if (existingItem) {
            if (existingItem.qty < maxStock) {
                existingItem.qty++;
            } else {
                customAlert(`Only ${maxStock} in stock!`, "Stock Limit", "warning");
            }
        } else {
            cart.push({ id, name, size, price, qty: 1, maxStock });
        }
        renderCart();
    }

    function updateQty(id, delta) {
        const itemIndex = cart.findIndex(item => item.id === id);
        if (itemIndex === -1) return;
        
        const item = cart[itemIndex];
        const newQty = item.qty + delta;

        if (newQty <= 0) {
            cart.splice(itemIndex, 1);
        } else if (newQty > item.maxStock) {
            customAlert(`Only ${item.maxStock} in stock!`, "Stock Limit", "warning");
        } else {
            item.qty = newQty;
        }
        renderCart();
    }

    async function clearCart() {
        if(cart.length === 0) return;
        
        const isConfirmed = await customConfirm('Are you sure you want to clear the entire cart?', 'Clear Cart', 'Yes, Clear', 'danger');
        if(isConfirmed) {
            cart = [];
            renderCart();
        }
    }

    function renderCart() {
        const container = document.getElementById('cart_container');
        const totalDisplay = document.getElementById('cart_total_display');
        const btnCheckout = document.getElementById('btn_checkout');
        
        let total = 0;
        
        if (cart.length === 0) {
            container.innerHTML = `
                <div class="h-full flex flex-col items-center justify-center text-gray-400 space-y-3 opacity-50">
                    <i class="fa-solid fa-cart-arrow-down text-4xl"></i>
                    <p class="text-xs font-bold uppercase tracking-wider">Cart is empty</p>
                </div>
            `;
            totalDisplay.textContent = '₱ 0.00';
            btnCheckout.disabled = true;
            window.currentCartTotal = 0;
            return;
        }

        let html = '';

        cart.forEach(item => {
            const subtotal = item.price * item.qty;
            total += subtotal;
            
            html += `
            <div class="bg-white dark:bg-zinc-950 border border-gray-100 dark:border-zinc-800 rounded-lg p-3 flex justify-between items-center shadow-sm relative group overflow-hidden">
                <div class="flex-1 pr-2">
                    <p class="text-xs font-bold text-gray-900 dark:text-white leading-tight truncate">${item.name}</p>
                    <p class="text-[10px] text-gray-500 dark:text-zinc-500 mt-0.5">Size: ${item.size} | ₱${formatMoney(item.price)}</p>
                    <p class="text-xs font-black text-pink-600 dark:text-pink-500 mt-1">₱${formatMoney(subtotal)}</p>
                </div>
                
                <div class="flex items-center gap-2 bg-gray-50 dark:bg-zinc-900 rounded-lg border border-gray-200 dark:border-zinc-700 p-1 shrink-0">
                    <button onclick="updateQty(${item.id}, -1)" class="w-6 h-6 rounded bg-white dark:bg-zinc-800 text-gray-500 hover:text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-900/20 transition-colors flex items-center justify-center focus:outline-none"><i class="fa-solid fa-minus text-[10px]"></i></button>
                    <span class="text-xs font-black w-4 text-center text-gray-900 dark:text-white">${item.qty}</span>
                    <button onclick="updateQty(${item.id}, 1)" class="w-6 h-6 rounded bg-white dark:bg-zinc-800 text-gray-500 hover:text-emerald-500 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 transition-colors flex items-center justify-center focus:outline-none"><i class="fa-solid fa-plus text-[10px]"></i></button>
                </div>
            </div>`;
        });

        container.innerHTML = html;
        totalDisplay.textContent = '₱ ' + formatMoney(total);
        btnCheckout.disabled = false;
        
        window.currentCartTotal = total;
    }

    async function processCheckout() {
        const method = document.getElementById('pos_payment_method').value.trim();
        const ref = document.getElementById('pos_reference').value.trim();

        if(cart.length === 0) return customAlert('Cart is empty!', 'Warning', 'warning');
        if(!method) return customAlert('Please select a Payment Method.', 'Missing Field', 'warning');

        const btn = document.getElementById('btn_checkout');
        const originalBtnHtml = btn.innerHTML;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Processing...';
        btn.disabled = true;

        const payload = {
            cart: cart,
            payment_method: method,
            reference_number: ref,
            total_amount: window.currentCartTotal
        };

        try {
            const response = await fetch('actions/process_retail_sale.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const data = await response.json();
            
            if (data.status === 'success') {
                document.getElementById('pos_payment_method').value = '';
                document.getElementById('pos_reference').value = '';
                customAlert('Sale completed successfully!', 'Success', 'success');
                setTimeout(() => window.location.reload(), 1500); 
            } else {
                customAlert('Error processing sale: ' + data.message, 'Error', 'error');
                btn.innerHTML = originalBtnHtml;
                btn.disabled = false;
            }
        } catch (error) {
            customAlert('Network Error occurred.', 'Error', 'error');
            btn.innerHTML = originalBtnHtml;
            btn.disabled = false;
        }
    }

    // Modal Toggle Functions
    function openRecentSalesModal() {
        document.getElementById('recent-sales-modal').classList.remove('hidden');
    }

    function closeRecentSalesModal() {
        document.getElementById('recent-sales-modal').classList.add('hidden');
    }

    // VOID SALE AJAX
    async function voidSale(saleId) {
        const isConfirmed = await customConfirm(
            'Are you sure you want to VOID this transaction?\n\nThis will permanently delete the record and return the purchased items back into your inventory.', 
            'Void Sale', 
            'Yes, Void', 
            'danger'
        );
        
        if (!isConfirmed) return;

        try {
            const response = await fetch('actions/void_retail_sale.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ sale_id: saleId })
            });
            const data = await response.json();
            
            if (data.status === 'success') {
                customAlert('Sale voided successfully. Inventory has been restored.', 'Success', 'success');
                setTimeout(() => window.location.reload(), 1500); 
            } else {
                customAlert('Error voiding sale: ' + data.message, 'Error', 'error');
            }
        } catch (error) {
            customAlert('Network Error occurred while trying to void the sale.', 'Error', 'error');
        }
    }
</script>

<?php include 'includes/footer.php'; ?>