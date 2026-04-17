<?php
require_once('config/database.php');
$page_title = "Inventory | NC Garments";

// 1. Determine which tab is active and check if it's an archived view
$valid_views = ['raw_material', 'premade_product', 'archived_raw', 'archived_premade'];
$view = $_GET['view'] ?? 'raw_material';
if (!in_array($view, $valid_views)) {
    $view = 'raw_material'; 
}

$is_archived_view = ($view === 'archived_raw' || $view === 'archived_premade') ? 1 : 0;
$base_type = ($view === 'raw_material' || $view === 'archived_raw') ? 'raw_material' : 'premade_product';

// 2. Fetch data based on base type and archive status
if ($base_type === 'raw_material') {
    $stmt = $conn->prepare("
        SELECT material_id as id, sku, material_name as name, current_stock as stock, 
               unit_of_measure as metric, current_price as price, min_stock_alert as alert
        FROM raw_material WHERE is_archived = ? ORDER BY material_name ASC
    ");
} else {
    $stmt = $conn->prepare("
        SELECT product_id as id, sku, product_name as name, current_stock as stock, 
               size as metric, selling_price as price, min_stock_alert as alert
        FROM premade_product WHERE is_archived = ? ORDER BY product_name ASC
    ");
}
$stmt->bind_param("i", $is_archived_view);
$stmt->execute();
$items_result = $stmt->get_result();

include 'includes/header.php'; 
?>

<main class="flex-1 p-4 md:p-8 overflow-y-auto transition-colors duration-500 font-sans relative">
    
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white transition-colors duration-500">Inventory Management</h2>
            <p class="text-gray-500 dark:text-zinc-400 text-sm mt-1 transition-colors duration-500">Track raw materials for production and premade products for retail sale.</p>
        </div>
        <?php if (!$is_archived_view): ?>
        <button onclick="openInventoryModal()" class="bg-pink-600 hover:bg-pink-700 text-white px-5 py-2.5 rounded-xl text-sm font-bold transition-all duration-300 shadow-lg shadow-pink-600/20 flex items-center gap-2 cursor-pointer focus:outline-none">
            <i class="fa-solid fa-plus"></i> Add New Item
        </button>
        <?php endif; ?>
    </div>

    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
        
        <div class="relative w-full md:w-96 group">
            <i class="fa-solid fa-search absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400 group-focus-within:text-pink-600 transition-colors duration-500"></i>
            <input type="text" placeholder="Search by SKU or item name..." 
                   class="w-full pl-11 pr-4 py-3 border border-gray-200 dark:border-zinc-800 rounded-xl bg-white dark:bg-zinc-900/50 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-zinc-500 focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent transition-colors duration-500 shadow-sm">
        </div>
        
        <?php
            $active_tab = "bg-white dark:bg-zinc-800 text-pink-600 dark:text-pink-500 shadow-sm";
            $inactive_tab = "text-gray-500 dark:text-zinc-400 hover:text-gray-900 hover:dark:text-white";
        ?>
        
        <div class="flex bg-gray-100 dark:bg-zinc-900/80 p-1 rounded-lg w-full md:w-auto overflow-x-auto transition-colors duration-500 border border-gray-200 dark:border-zinc-800">
            <a href="?view=raw_material" class="whitespace-nowrap px-4 py-2 text-sm font-bold rounded-md transition-colors duration-500 flex items-center gap-2 <?= $view === 'raw_material' ? $active_tab : $inactive_tab ?>">
                <i class="fa-solid fa-layer-group text-xs"></i> Raw Materials
            </a>
            <a href="?view=premade_product" class="whitespace-nowrap px-4 py-2 text-sm font-semibold rounded-md transition-colors duration-500 flex items-center gap-2 <?= $view === 'premade_product' ? $active_tab : $inactive_tab ?>">
                <i class="fa-solid fa-tags text-xs"></i> Premade Products
            </a>
            <span class="mx-1 border-r border-gray-300 dark:border-zinc-700"></span>
            <a href="?view=archived_raw" class="whitespace-nowrap px-4 py-2 text-sm font-semibold rounded-md transition-colors duration-500 flex items-center gap-2 <?= $view === 'archived_raw' ? $active_tab : $inactive_tab ?>">
                <i class="fa-solid fa-box-archive text-xs"></i> Arch. Materials
            </a>
            <a href="?view=archived_premade" class="whitespace-nowrap px-4 py-2 text-sm font-semibold rounded-md transition-colors duration-500 flex items-center gap-2 <?= $view === 'archived_premade' ? $active_tab : $inactive_tab ?>">
                <i class="fa-solid fa-box-archive text-xs"></i> Arch. Products
            </a>
        </div>
    </div>

    <div class="bg-white dark:bg-zinc-900 rounded-2xl shadow-sm border border-gray-100 dark:border-zinc-800 overflow-hidden transition-colors duration-500">
        <div class="overflow-x-auto">
            <table class="w-full whitespace-nowrap">
                <thead class="bg-gray-50 dark:bg-zinc-950/50 border-b border-gray-100 dark:border-zinc-800 transition-colors duration-500">
                    <tr>
                        <th class="px-6 py-4 text-left text-[10px] font-extrabold text-gray-500 dark:text-zinc-500 uppercase tracking-widest">Item Details</th>
                        <th class="px-6 py-4 text-left text-[10px] font-extrabold text-gray-500 dark:text-zinc-500 uppercase tracking-widest">Stock Level</th>
                        
                        <?php if ($base_type === 'raw_material'): ?>
                        <th class="px-6 py-4 text-left text-[10px] font-extrabold text-gray-500 dark:text-zinc-500 uppercase tracking-widest">Backordered</th>
                        <?php endif; ?>

                        <th class="px-6 py-4 text-left text-[10px] font-extrabold text-gray-500 dark:text-zinc-500 uppercase tracking-widest">Unit Price/Cost</th>
                        <th class="px-6 py-4 text-left text-[10px] font-extrabold text-gray-500 dark:text-zinc-500 uppercase tracking-widest">Status</th>
                        <th class="px-6 py-4 text-right text-[10px] font-extrabold text-gray-500 dark:text-zinc-500 uppercase tracking-widest">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 dark:divide-zinc-800/50 text-sm transition-colors duration-500">
                    
                    <?php
                    if ($items_result->num_rows === 0) {
                        $colspan = ($base_type === 'raw_material') ? 6 : 5;
                        echo '<tr><td colspan="'.$colspan.'" class="px-6 py-8 text-center text-gray-500">No items found in this category.</td></tr>';
                    }

                    while ($item = $items_result->fetch_assoc()) {
                        
                        $stock_val = (float)$item['stock'];
                        $alert_val = (float)$item['alert'];
                        
                        $deficit = 0;
                        $display_stock = $stock_val;
                        
                        if ($stock_val < 0) {
                            $deficit = abs($stock_val);
                            $display_stock = 0; 
                        }
                        
                        $is_out_of_stock = $stock_val <= 0;
                        $is_low_stock = !$is_out_of_stock && ($stock_val <= $alert_val);
                        
                        $metric_label = $base_type === 'raw_material' ? htmlspecialchars($item['metric']) : 'Size: ' . htmlspecialchars($item['metric']);

                        $safe_sku = addslashes($item['sku']);
                        $safe_name = addslashes($item['name']);
                        $safe_metric = addslashes($item['metric']);
                        
                        $row_bg = '';
                        if (!$is_archived_view) {
                            if ($is_out_of_stock) $row_bg = 'bg-rose-50/30 dark:bg-rose-900/5';
                            elseif ($is_low_stock) $row_bg = 'bg-amber-50/30 dark:bg-amber-900/5';
                        }
                        
                        $stock_color = 'text-gray-900 dark:text-white';
                        $metric_color = 'text-gray-500';
                        $alert_color = 'text-gray-400';
                        
                        if (!$is_archived_view) {
                            if ($is_out_of_stock) {
                                $stock_color = 'text-rose-600 dark:text-rose-500';
                                $metric_color = 'text-rose-600/70';
                                $alert_color = 'text-rose-500';
                            } elseif ($is_low_stock) {
                                $stock_color = 'text-amber-600 dark:text-amber-500';
                                $metric_color = 'text-amber-600/70';
                                $alert_color = 'text-amber-500';
                            }
                        }
                        
                        echo '
                        <tr class="hover:bg-gray-50/80 dark:hover:bg-zinc-800/30 transition-colors group ' . $row_bg . '">
                            <td class="px-6 py-4">
                                <div class="font-bold text-gray-900 dark:text-white group-hover:text-pink-600 transition-colors">'.htmlspecialchars($item['name']).'</div>
                                <div class="text-xs font-bold tracking-wider text-gray-400 mt-1">SKU: '.htmlspecialchars($item['sku']).'</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-baseline gap-1">
                                    <span class="text-lg font-extrabold '.$stock_color.'">'.$display_stock.'</span>
                                    <span class="text-xs font-bold '.$metric_color.' uppercase">'.$metric_label.'</span>
                                </div>
                                <div class="text-[10px] font-bold '.$alert_color.' mt-1 uppercase tracking-wider">Min Alert: '.$item['alert'].'</div>
                            </td>';
                            
                            // ONLY SHOW BACKORDERED COLUMN FOR RAW MATERIALS
                            if ($base_type === 'raw_material') {
                                echo '<td class="px-6 py-4">';
                                if ($deficit > 0 && !$is_archived_view) {
                                    echo '
                                    <div class="flex items-baseline gap-1">
                                        <span class="text-base font-extrabold text-rose-600 dark:text-rose-500">'.$deficit.'</span>
                                        <span class="text-[10px] font-bold text-rose-600/70 uppercase">'.$metric_label.'</span>
                                    </div>
                                    <div class="text-[10px] font-bold text-rose-500 mt-1 uppercase tracking-wider">Owed to Prod.</div>';
                                } else {
                                    echo '<span class="text-gray-300 dark:text-zinc-700 font-bold">--</span>';
                                }
                                echo '</td>';
                            }

                            echo '<td class="px-6 py-4">
                                <div class="font-extrabold text-gray-900 dark:text-white">₱ '.number_format($item['price'], 2).'</div>
                                <div class="text-[10px] font-bold text-gray-400 mt-1 uppercase tracking-wider">Per Unit</div>
                            </td>
                            <td class="px-6 py-4">';
                                if ($is_archived_view) {
                                    echo '<span class="bg-gray-100 text-gray-600 dark:bg-zinc-800 dark:text-zinc-400 text-[10px] font-extrabold px-2.5 py-1 rounded-md uppercase tracking-wider border border-gray-200 dark:border-zinc-700 flex items-center w-max gap-1.5">
                                            <i class="fa-solid fa-box-archive"></i> Archived
                                          </span>';
                                } elseif ($is_out_of_stock) {
                                    echo '<span class="bg-rose-100 text-rose-700 dark:bg-rose-500/20 dark:text-rose-400 text-[10px] font-extrabold px-2.5 py-1 rounded-md uppercase tracking-wider border border-rose-200 dark:border-rose-500/30 flex items-center w-max gap-1.5">
                                            <i class="fa-solid fa-circle-xmark"></i> Out of Stock
                                          </span>';
                                } elseif ($is_low_stock) {
                                    echo '<span class="bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-400 text-[10px] font-extrabold px-2.5 py-1 rounded-md uppercase tracking-wider border border-amber-200 dark:border-amber-500/30 flex items-center w-max gap-1.5">
                                            <i class="fa-solid fa-triangle-exclamation"></i> Low Stock
                                          </span>';
                                } else {
                                    echo '<span class="bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400 text-[10px] font-extrabold px-2.5 py-1 rounded-md uppercase tracking-wider border border-emerald-200 dark:border-emerald-500/20 flex items-center w-max gap-1.5">
                                            <i class="fa-solid fa-check"></i> In Stock
                                          </span>';
                                }
                        echo '</td>
                            <td class="px-6 py-4 text-right text-sm font-medium">';
                                
                                if ($is_archived_view) {
                                    echo '<button onclick="restoreItem('.$item['id'].', \''.$base_type.'\')" class="text-gray-400 hover:text-emerald-600 focus:outline-none p-2" title="Restore Item">
                                            <i class="fa-solid fa-clock-rotate-left"></i>
                                          </button>';
                                } else {
                                    echo '<button onclick="openInventoryModal('.$item['id'].', \''.$base_type.'\', \''.$safe_sku.'\', \''.$safe_name.'\', '.$item['stock'].', '.$item['price'].', '.$item['alert'].', \''.$safe_metric.'\')" class="text-gray-400 hover:text-pink-600 focus:outline-none p-2" title="Edit Item">
                                            <i class="fa-solid fa-pen"></i>
                                          </button>
                                          <button onclick="archiveItem('.$item['id'].', \''.$base_type.'\')" class="text-gray-400 hover:text-rose-600 focus:outline-none p-2" title="Archive Item">
                                            <i class="fa-solid fa-box-archive"></i>
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

<div id="inventory-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm transition-opacity" onclick="closeInventoryModal()"></div>
    
    <div class="relative bg-white dark:bg-zinc-900 rounded-2xl w-full max-w-md shadow-2xl overflow-hidden flex flex-col max-h-[90vh] border border-gray-100 dark:border-zinc-800">
        
        <div class="px-6 py-4 border-b border-gray-100 dark:border-zinc-800 flex justify-between items-center bg-gray-50/50 dark:bg-zinc-950/30">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white" id="inv_title">Add New Item</h3>
            <button onclick="closeInventoryModal()" class="text-gray-400 hover:text-rose-500 transition-colors focus:outline-none">
                <i class="fa-solid fa-xmark text-xl"></i>
            </button>
        </div>
        
        <div class="p-6 overflow-y-auto flex-1">
            <form id="inventory-form" class="space-y-5">
                <input type="hidden" id="inv_id">
                
                <div>
                    <label class="block text-xs font-bold text-gray-600 dark:text-zinc-400 mb-2 uppercase tracking-wide">Item Type</label>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="relative flex cursor-pointer rounded-xl border border-gray-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-3 shadow-sm focus:outline-none has-[:checked]:border-pink-600 has-[:checked]:bg-pink-50 dark:has-[:checked]:bg-pink-900/10 transition-all">
                            <input type="radio" name="inv_type" value="raw_material" class="sr-only" onchange="toggleItemTypeFields()" checked>
                            <div class="text-sm font-bold text-gray-900 dark:text-white text-center w-full">Raw Material</div>
                        </label>
                        <label class="relative flex cursor-pointer rounded-xl border border-gray-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-3 shadow-sm focus:outline-none has-[:checked]:border-pink-600 has-[:checked]:bg-pink-50 dark:has-[:checked]:bg-pink-900/10 transition-all">
                            <input type="radio" name="inv_type" value="premade_product" class="sr-only" onchange="toggleItemTypeFields()">
                            <div class="text-sm font-bold text-gray-900 dark:text-white text-center w-full">Premade Product</div>
                        </label>
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-4">
                    <div class="col-span-1">
                        <label class="block text-xs font-bold text-gray-600 dark:text-zinc-400 mb-2 uppercase tracking-wide">SKU</label>
                        <input type="text" id="inv_sku" required placeholder="RAW-001" class="w-full px-4 py-3 bg-gray-50 dark:bg-zinc-950 border border-gray-200 dark:border-zinc-800 text-gray-900 dark:text-white rounded-xl focus:ring-2 focus:ring-pink-500 focus:border-transparent outline-none transition-all text-sm font-bold placeholder-gray-400 dark:placeholder-zinc-600">
                    </div>
                    <div class="col-span-2">
                        <label class="block text-xs font-bold text-gray-600 dark:text-zinc-400 mb-2 uppercase tracking-wide">Item Name</label>
                        <input type="text" id="inv_name" required placeholder="e.g., Signature Pink Thread" class="w-full px-4 py-3 bg-gray-50 dark:bg-zinc-950 border border-gray-200 dark:border-zinc-800 text-gray-900 dark:text-white rounded-xl focus:ring-2 focus:ring-pink-500 focus:border-transparent outline-none transition-all text-sm font-medium placeholder-gray-400 dark:placeholder-zinc-600">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-600 dark:text-zinc-400 mb-2 uppercase tracking-wide">Current Stock / Deficit</label>
                        <input type="number" step="0.01" id="inv_stock" value="0.00" required class="w-full px-4 py-3 bg-gray-50 dark:bg-zinc-950 border border-gray-200 dark:border-zinc-800 text-gray-900 dark:text-white rounded-xl focus:ring-2 focus:ring-pink-500 focus:border-transparent outline-none transition-all text-sm font-medium">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-600 dark:text-zinc-400 mb-2 uppercase tracking-wide">Price / Cost (₱)</label>
                        <input type="number" id="inv_price" step="0.01" min="0" value="0.00" required class="w-full px-4 py-3 bg-gray-50 dark:bg-zinc-950 border border-gray-200 dark:border-zinc-800 text-gray-900 dark:text-white rounded-xl focus:ring-2 focus:ring-pink-500 focus:border-transparent outline-none transition-all text-sm font-medium">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div id="field_uom">
                        <label class="block text-xs font-bold text-gray-600 dark:text-zinc-400 mb-2 uppercase tracking-wide">Unit of Measure</label>
                        <input type="text" id="inv_uom" placeholder="e.g., Yards, Cones" class="w-full px-4 py-3 bg-gray-50 dark:bg-zinc-950 border border-gray-200 dark:border-zinc-800 text-gray-900 dark:text-white rounded-xl focus:ring-2 focus:ring-pink-500 focus:border-transparent outline-none transition-all text-sm font-medium placeholder-gray-400 dark:placeholder-zinc-600">
                    </div>
                    <div id="field_size" class="hidden">
                        <label class="block text-xs font-bold text-gray-600 dark:text-zinc-400 mb-2 uppercase tracking-wide">Clothing Size</label>
                        <input type="text" id="inv_size" placeholder="e.g., Small, XL" class="w-full px-4 py-3 bg-gray-50 dark:bg-zinc-950 border border-gray-200 dark:border-zinc-800 text-gray-900 dark:text-white rounded-xl focus:ring-2 focus:ring-pink-500 focus:border-transparent outline-none transition-all text-sm font-medium placeholder-gray-400 dark:placeholder-zinc-600">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-600 dark:text-zinc-400 mb-2 uppercase tracking-wide">Low Stock Alert</label>
                        <input type="number" step="0.01" id="inv_alert" min="0" value="10.00" required class="w-full px-4 py-3 bg-gray-50 dark:bg-zinc-950 border border-gray-200 dark:border-zinc-800 text-gray-900 dark:text-white rounded-xl focus:ring-2 focus:ring-pink-500 focus:border-transparent outline-none transition-all text-sm font-medium">
                    </div>
                </div>
            </form>
        </div>
        
        <div class="px-6 py-4 border-t border-gray-100 dark:border-zinc-800 bg-gray-50/50 dark:bg-zinc-950/30 flex justify-end gap-3">
            <button type="button" onclick="closeInventoryModal()" class="px-5 py-2.5 text-sm font-bold text-gray-600 dark:text-zinc-400 hover:bg-gray-100 dark:hover:bg-zinc-800 rounded-xl transition-colors focus:outline-none">
                Cancel
            </button>
            <button type="button" onclick="saveInventory()" class="bg-pink-600 hover:bg-pink-700 text-white px-5 py-2.5 rounded-xl text-sm font-bold transition-all shadow-md shadow-pink-600/20 focus:outline-none">
                Save Item
            </button>
        </div>
    </div>
</div>

<script>
    function toggleItemTypeFields() {
        const type = document.querySelector('input[name="inv_type"]:checked').value;
        if (type === 'raw_material') {
            document.getElementById('field_uom').classList.remove('hidden');
            document.getElementById('field_size').classList.add('hidden');
        } else {
            document.getElementById('field_uom').classList.add('hidden');
            document.getElementById('field_size').classList.remove('hidden');
        }
    }

    const currentBaseType = '<?= $base_type ?>';

    function openInventoryModal(id = '', type = currentBaseType, sku = '', name = '', stock = 0, price = 0.00, alert = 10, metric = '') {
        document.getElementById('inv_id').value = id;
        document.getElementById('inv_sku').value = sku;
        document.getElementById('inv_name').value = name;
        document.getElementById('inv_stock').value = stock;
        document.getElementById('inv_price').value = price;
        document.getElementById('inv_alert').value = alert;

        document.querySelector(`input[name="inv_type"][value="${type}"]`).checked = true;
        toggleItemTypeFields(); 

        if (type === 'raw_material') {
            document.getElementById('inv_uom').value = metric;
            document.getElementById('inv_size').value = '';
        } else {
            document.getElementById('inv_size').value = metric;
            document.getElementById('inv_uom').value = '';
        }

        document.querySelectorAll('input[name="inv_type"]').forEach(radio => {
            radio.disabled = id !== ''; 
        });

        document.getElementById('inv_title').textContent = id ? "Edit Inventory Item" : "Add New Item";
        document.getElementById('inventory-modal').classList.remove('hidden');
    }

    function closeInventoryModal() {
        document.getElementById('inventory-modal').classList.add('hidden');
    }

    async function saveInventory() {
        const type = document.querySelector('input[name="inv_type"]:checked').value;
        const payload = {
            item_id: document.getElementById('inv_id').value,
            item_type: type,
            sku: document.getElementById('inv_sku').value,
            name: document.getElementById('inv_name').value,
            stock: document.getElementById('inv_stock').value,
            price: document.getElementById('inv_price').value,
            alert: document.getElementById('inv_alert').value,
            uom: document.getElementById('inv_uom').value,
            size: document.getElementById('inv_size').value
        };

        if(!payload.sku) return alert("SKU is required!");
        if(!payload.name) return alert("Item Name is required!");

        try {
            const res = await fetch('actions/save_inventory.php', {
                method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload)
            });
            const data = await res.json();
            if(data.status === 'success') {
                window.location.href = `?view=${type}`;
            } else {
                alert("Error: " + data.message);
            }
        } catch (e) { alert("Network Error"); }
    }

    async function archiveItem(id, type) {
        if(!confirm("Are you sure you want to archive this item? It will be hidden from active inventory.")) return;
        try {
            const res = await fetch('actions/delete_inventory.php', {
                method: 'POST', headers: { 'Content-Type': 'application/json' }, 
                body: JSON.stringify({ item_id: id, item_type: type })
            });
            const data = await res.json();
            if(data.status === 'success') window.location.reload();
        } catch (e) { alert("Network Error"); }
    }

    async function restoreItem(id, type) {
        if(!confirm("Restore this item back to active inventory?")) return;
        try {
            const res = await fetch('actions/restore_inventory.php', {
                method: 'POST', headers: { 'Content-Type': 'application/json' }, 
                body: JSON.stringify({ item_id: id, item_type: type })
            });
            const data = await res.json();
            if(data.status === 'success') window.location.reload();
        } catch (e) { alert("Network Error"); }
    }
</script>

<?php include 'includes/footer.php'; ?>