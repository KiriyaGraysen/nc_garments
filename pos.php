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
$default_methods = ['Cash', 'GCash', 'Bank Transfer', 'Check'];
$final_methods = [];
foreach ($default_methods as $method) { $final_methods[strtolower($method)] = $method; }
while ($row = $pm_stmt->fetch_assoc()) {
    $lower_key = strtolower(trim($row['payment_method']));
    if (!isset($final_methods[$lower_key])) $final_methods[$lower_key] = trim($row['payment_method']);
}
sort($final_methods);

include 'includes/header.php'; 
?>

<main class="flex-1 p-4 md:p-6 overflow-y-auto font-sans relative bg-gray-50 dark:bg-zinc-950">
    
    <div class="mb-4">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Point of Sale</h2>
        <p class="text-gray-500 dark:text-zinc-400 text-sm mt-1">Process walk-in retail sales and instantly deduct inventory.</p>
    </div>

    <div class="flex flex-col lg:flex-row gap-6 h-[calc(100vh-140px)]">
        
        <div class="flex-1 flex flex-col bg-white dark:bg-zinc-900 rounded-2xl shadow-sm border border-gray-100 dark:border-zinc-800 overflow-hidden">
            <div class="p-4 border-b border-gray-100 dark:border-zinc-800 bg-gray-50/50 dark:bg-zinc-950/30">
                <div class="relative w-full group">
                    <i class="fa-solid fa-search absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400 group-focus-within:text-pink-600 transition-colors"></i>
                    <input type="text" id="search_catalog" onkeyup="filterCatalog()" placeholder="Search by SKU, Name, or Size..." class="w-full pl-11 pr-4 py-3 border border-gray-200 dark:border-zinc-800 rounded-xl bg-white dark:bg-zinc-900 text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-pink-500 transition-colors shadow-sm text-sm">
                </div>
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
                    <div class="col-span-full text-center py-10 text-gray-500">No premade products available or in stock.</div>
                <?php endif; ?>
            </div>
        </div>

        <div class="w-full lg:w-96 flex flex-col bg-white dark:bg-zinc-900 rounded-2xl shadow-sm border border-gray-100 dark:border-zinc-800 overflow-hidden shrink-0">
            <div class="px-5 py-4 border-b border-gray-100 dark:border-zinc-800 bg-gray-50/50 dark:bg-zinc-950/30 flex justify-between items-center">
                <h3 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider"><i class="fa-solid fa-receipt mr-2 text-gray-400"></i> Current Sale</h3>
                <button onclick="clearCart()" class="text-[10px] font-bold text-rose-500 hover:text-rose-600 uppercase tracking-widest">Clear All</button>
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

                <button id="btn_checkout" onclick="processCheckout()" disabled class="w-full bg-pink-600 hover:bg-pink-700 text-white py-3.5 rounded-xl text-sm font-black uppercase tracking-widest transition-all shadow-lg shadow-pink-600/20 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2">
                    <i class="fa-solid fa-cash-register"></i> Complete Checkout
                </button>
            </div>
        </div>
        
    </div>
</main>

<script>
    // CART STATE
    let cart = [];

    // format currency utility
    const formatMoney = (num) => parseFloat(num).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    // FILTER CATALOG (Search Bar)
    function filterCatalog() {
        const query = document.getElementById('search_catalog').value.toLowerCase();
        const cards = document.querySelectorAll('.product-card');
        
        cards.forEach(card => {
            const text = card.textContent.toLowerCase();
            card.style.display = text.includes(query) ? 'flex' : 'none';
        });
    }

    // ADD TO CART
    function addToCart(id, name, size, price, maxStock) {
        const existingItem = cart.find(item => item.id === id);
        
        if (existingItem) {
            if (existingItem.qty < maxStock) {
                existingItem.qty++;
            } else {
                alert(`Only ${maxStock} in stock!`);
            }
        } else {
            cart.push({ id, name, size, price, qty: 1, maxStock });
        }
        renderCart();
    }

    // UPDATE QUANTITY
    function updateQty(id, delta) {
        const itemIndex = cart.findIndex(item => item.id === id);
        if (itemIndex === -1) return;
        
        const item = cart[itemIndex];
        const newQty = item.qty + delta;

        if (newQty <= 0) {
            cart.splice(itemIndex, 1); // Remove item
        } else if (newQty > item.maxStock) {
            alert(`Only ${item.maxStock} in stock!`);
        } else {
            item.qty = newQty;
        }
        renderCart();
    }

    // CLEAR CART
    function clearCart() {
        if(cart.length === 0) return;
        if(confirm('Clear the entire cart?')) {
            cart = [];
            renderCart();
        }
    }

    // 🛠️ THE FIX: Redraw the HTML completely based on the cart array
    function renderCart() {
        const container = document.getElementById('cart_container');
        const totalDisplay = document.getElementById('cart_total_display');
        const btnCheckout = document.getElementById('btn_checkout');
        
        let total = 0;
        
        // If empty, draw the empty state directly into the container
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

        // If not empty, build the item list
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

        // Overwrite the container with the products!
        container.innerHTML = html;
        totalDisplay.textContent = '₱ ' + formatMoney(total);
        btnCheckout.disabled = false;
        
        // Store total globally for checkout
        window.currentCartTotal = total;
    }

    // PROCESS CHECKOUT AJAX
    async function processCheckout() {
        const method = document.getElementById('pos_payment_method').value.trim();
        const ref = document.getElementById('pos_reference').value.trim();

        if(cart.length === 0) return alert('Cart is empty!');
        if(!method) return alert('Please select a Payment Method.');

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
                alert('Sale completed successfully!');
                window.location.reload(); // Reload to refresh catalog stock
            } else {
                alert('Error processing sale: ' + data.message);
                btn.innerHTML = originalBtnHtml;
                btn.disabled = false;
            }
        } catch (error) {
            alert('Network Error occurred.');
            btn.innerHTML = originalBtnHtml;
            btn.disabled = false;
        }
    }
</script>

<?php include 'includes/footer.php'; ?>