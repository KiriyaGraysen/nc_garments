<?php
require_once('config/database.php');
$page_title = "Inventory | NC Garments";

// 1. Determine which tab is active
$valid_views = ['raw_material', 'premade_product', 'alerts', 'archived'];
$view = $_GET['view'] ?? 'raw_material';
if (!in_array($view, $valid_views)) {
    $view = 'raw_material'; 
}

$is_unified = ($view === 'alerts' || $view === 'archived');
$is_archived_view = ($view === 'archived');

// 2. Setup Sorting Logic
$sort = $_GET['sort'] ?? 'name_asc';

switch ($sort) {
    case 'name_desc': $order_by = "name DESC"; break;
    case 'stock_asc': $order_by = "stock ASC"; break;
    case 'stock_desc': $order_by = "stock DESC"; break;
    case 'price_asc': $order_by = "price ASC"; break;
    case 'price_desc': $order_by = "price DESC"; break;
    default: $order_by = "name ASC"; break; // Default
}

// 3. Define the base queries
$query_raw = "SELECT material_id as id, sku, material_name as name, current_stock as stock, unit_of_measure as metric, current_price as price, min_stock_alert as alert, 'raw_material' as type FROM raw_material";
$query_prod = "SELECT product_id as id, sku, product_name as name, current_stock as stock, size as metric, selling_price as price, min_stock_alert as alert, 'premade_product' as type FROM premade_product";

// 4. Fetch data based on the view
if ($view === 'raw_material') {
    $stmt = $conn->prepare("$query_raw WHERE is_archived = 0 ORDER BY $order_by");
} elseif ($view === 'premade_product') {
    $stmt = $conn->prepare("$query_prod WHERE is_archived = 0 ORDER BY $order_by");
} elseif ($view === 'alerts') {
    $stmt = $conn->prepare("
        SELECT * FROM (
            $query_raw WHERE is_archived = 0 AND current_stock <= min_stock_alert
            UNION ALL
            $query_prod WHERE is_archived = 0 AND current_stock <= min_stock_alert
        ) as combined ORDER BY $order_by
    ");
} elseif ($view === 'archived') {
    $stmt = $conn->prepare("
        SELECT * FROM (
            $query_raw WHERE is_archived = 1
            UNION ALL
            $query_prod WHERE is_archived = 1
        ) as combined ORDER BY $order_by
    ");
}

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

    <div class="flex flex-col lg:flex-row justify-between items-center mb-6 gap-4">
        
        <div class="flex w-full lg:w-auto gap-3 flex-1 max-w-2xl">
            <div class="relative w-full group">
                <i class="fa-solid fa-search absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400 group-focus-within:text-pink-600 transition-colors duration-500"></i>
                <input type="text" id="search-input" placeholder="Search SKU or item name..." autocomplete="off" data-lpignore="true" readonly onfocus="this.removeAttribute('readonly');"
                       class="w-full pl-11 pr-4 py-3 border border-gray-200 dark:border-zinc-800 rounded-xl bg-white dark:bg-zinc-900/50 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-zinc-500 focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent transition-colors duration-500 shadow-sm text-sm font-medium">
            </div>
            
            <div class="relative w-48 shrink-0">
                <select onchange="window.location.href=this.value" class="w-full px-4 py-3 border border-gray-200 dark:border-zinc-800 rounded-xl bg-white dark:bg-zinc-900/50 text-gray-700 dark:text-zinc-300 focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent transition-colors duration-500 shadow-sm text-sm font-bold cursor-pointer appearance-none">
                    <option value="?view=<?= $view ?>&sort=name_asc" <?= $sort == 'name_asc' ? 'selected' : '' ?>>Name (A-Z)</option>
                    <option value="?view=<?= $view ?>&sort=name_desc" <?= $sort == 'name_desc' ? 'selected' : '' ?>>Name (Z-A)</option>
                    <option value="?view=<?= $view ?>&sort=stock_asc" <?= $sort == 'stock_asc' ? 'selected' : '' ?>>Stock (Low to High)</option>
                    <option value="?view=<?= $view ?>&sort=stock_desc" <?= $sort == 'stock_desc' ? 'selected' : '' ?>>Stock (High to Low)</option>
                    <?php if (!$is_unified): ?>
                        <option value="?view=<?= $view ?>&sort=price_asc" <?= $sort == 'price_asc' ? 'selected' : '' ?>>Price (Low to High)</option>
                        <option value="?view=<?= $view ?>&sort=price_desc" <?= $sort == 'price_desc' ? 'selected' : '' ?>>Price (High to Low)</option>
                    <?php endif; ?>
                </select>
                <i class="fa-solid fa-chevron-down absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-400 text-xs pointer-events-none"></i>
            </div>
        </div>
        
        <div class="flex bg-gray-100 dark:bg-zinc-900/80 p-1 rounded-lg w-full lg:w-auto overflow-x-auto transition-colors duration-500 border border-gray-200 dark:border-zinc-800">
            <a href="?view=raw_material" class="whitespace-nowrap px-4 py-2 text-sm font-bold rounded-md transition-colors duration-500 flex items-center gap-2 <?= $view === 'raw_material' ? 'bg-white dark:bg-zinc-800 text-pink-600 dark:text-pink-500 shadow-sm' : 'text-gray-500 dark:text-zinc-400 hover:text-gray-900 hover:dark:text-white' ?>">
                <i class="fa-solid fa-layer-group text-xs"></i> Materials
            </a>
            <a href="?view=premade_product" class="whitespace-nowrap px-4 py-2 text-sm font-semibold rounded-md transition-colors duration-500 flex items-center gap-2 <?= $view === 'premade_product' ? 'bg-white dark:bg-zinc-800 text-pink-600 dark:text-pink-500 shadow-sm' : 'text-gray-500 dark:text-zinc-400 hover:text-gray-900 hover:dark:text-white' ?>">
                <i class="fa-solid fa-tags text-xs"></i> Products
            </a>
            <span class="mx-1 border-r border-gray-300 dark:border-zinc-700"></span>
            <a href="?view=alerts" class="whitespace-nowrap px-4 py-2 text-sm font-semibold rounded-md transition-colors duration-500 flex items-center gap-2 <?= $view === 'alerts' ? 'bg-rose-100 dark:bg-rose-500/20 text-rose-600 dark:text-rose-400 shadow-sm' : 'text-rose-400 dark:text-rose-500/70 hover:text-rose-600 dark:hover:text-rose-400' ?>">
                <i class="fa-solid fa-triangle-exclamation text-xs"></i> Low Stock Alerts
            </a>
            <a href="?view=archived" class="whitespace-nowrap px-4 py-2 text-sm font-semibold rounded-md transition-colors duration-500 flex items-center gap-2 <?= $view === 'archived' ? 'bg-white dark:bg-zinc-800 text-gray-800 dark:text-gray-200 shadow-sm' : 'text-gray-500 dark:text-zinc-400 hover:text-gray-900 hover:dark:text-white' ?>">
                <i class="fa-solid fa-box-archive text-xs"></i> Archived
            </a>
        </div>
    </div>

    <div class="bg-white dark:bg-zinc-900 rounded-2xl shadow-sm border border-gray-100 dark:border-zinc-800 overflow-hidden transition-colors duration-500 flex flex-col">
        <div class="overflow-x-auto flex-1">
            <table class="w-full whitespace-nowrap">
                <thead class="bg-gray-50 dark:bg-zinc-950/50 border-b border-gray-100 dark:border-zinc-800 transition-colors duration-500">
                    <tr>
                        <th class="px-6 py-4 text-left text-[10px] font-extrabold text-gray-500 dark:text-zinc-500 uppercase tracking-widest">Item Name</th>
                        
                        <?php if ($is_unified): ?>
                            <th class="px-6 py-4 text-left text-[10px] font-extrabold text-gray-500 dark:text-zinc-500 uppercase tracking-widest">Category</th>
                        <?php endif; ?>

                        <th class="px-6 py-4 text-left text-[10px] font-extrabold text-gray-500 dark:text-zinc-500 uppercase tracking-widest">Stock Level</th>
                        
                        <?php if ($view === 'raw_material'): ?>
                            <th class="px-6 py-4 text-left text-[10px] font-extrabold text-gray-500 dark:text-zinc-500 uppercase tracking-widest">Backordered</th>
                        <?php endif; ?>

                        <?php if (!$is_unified): ?>
                            <th class="px-6 py-4 text-left text-[10px] font-extrabold text-gray-500 dark:text-zinc-500 uppercase tracking-widest">Unit Price/Cost</th>
                        <?php endif; ?>
                        
                        <th class="px-6 py-4 text-left text-[10px] font-extrabold text-gray-500 dark:text-zinc-500 uppercase tracking-widest">Status</th>
                        <th class="px-6 py-4 text-center text-[10px] font-extrabold text-gray-500 dark:text-zinc-500 uppercase tracking-widest">Actions</th>
                    </tr>
                </thead>
                <tbody id="inventory-tbody" class="divide-y divide-gray-50 dark:divide-zinc-800/50 text-sm transition-colors duration-500">
                    
                    <?php
                    // 🚨 PHP Empty State
                    if ($items_result->num_rows === 0) {
                        $colspan = $is_unified ? 5 : ($view === 'raw_material' ? 6 : 5);
                        echo '<tr id="php-empty-state"><td colspan="'.$colspan.'" class="px-6 py-8 text-center text-gray-500 font-medium">No items found in this category.</td></tr>';
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
                        
                        $metric_label = $item['type'] === 'raw_material' ? htmlspecialchars($item['metric']) : 'Size: ' . htmlspecialchars($item['metric']);

                        $safe_sku = addslashes($item['sku']);
                        $safe_name = addslashes($item['name']);
                        $safe_metric = addslashes($item['metric']);
                        $safe_type = addslashes($item['type']);
                        
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
                        <tr class="inventory-row hover:bg-gray-50/80 dark:hover:bg-zinc-800/30 transition-colors group ' . $row_bg . '">
                            <td class="px-6 py-4">
                                <div class="font-bold text-gray-900 dark:text-white group-hover:text-pink-600 transition-colors">'.htmlspecialchars($item['name']).'</div>
                                <div class="text-xs font-bold tracking-wider text-gray-400 mt-1">SKU: '.htmlspecialchars($item['sku']).'</div>
                            </td>';

                            if ($is_unified) {
                                $cat_bg = $item['type'] === 'raw_material' ? 'bg-purple-50 text-purple-600 border-purple-200 dark:bg-purple-900/20 dark:text-purple-400 dark:border-purple-800' : 'bg-blue-50 text-blue-600 border-blue-200 dark:bg-blue-900/20 dark:text-blue-400 dark:border-blue-800';
                                $cat_label = $item['type'] === 'raw_material' ? 'Material' : 'Product';
                                echo '<td class="px-6 py-4">
                                        <span class="'.$cat_bg.' text-[10px] font-extrabold px-2 py-0.5 rounded uppercase border">'.$cat_label.'</span>
                                      </td>';
                            }

                        echo '
                            <td class="px-6 py-4">
                                <div class="flex items-baseline gap-1">
                                    <span class="text-lg font-extrabold '.$stock_color.'">'.$display_stock.'</span>
                                    <span class="text-xs font-bold '.$metric_color.' uppercase">'.$metric_label.'</span>
                                </div>
                                <div class="text-[10px] font-bold '.$alert_color.' mt-1 uppercase tracking-wider">Min Alert: '.$item['alert'].'</div>
                            </td>';
                            
                            if ($view === 'raw_material') {
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

                            if (!$is_unified) {
                                echo '<td class="px-6 py-4">
                                    <div class="font-extrabold text-gray-900 dark:text-white">₱ '.number_format($item['price'], 2).'</div>
                                    <div class="text-[10px] font-bold text-gray-400 mt-1 uppercase tracking-wider">Per Unit</div>
                                </td>';
                            }

                            echo '<td class="px-6 py-4">';
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
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-center gap-1.5">';
                                
                                // 🚨 UPDATED: Left-aligned Tooltips + Icon-only Buttons
                                if ($is_archived_view) {
                                    echo '<button onclick="restoreItem('.$item['id'].', \''.$safe_type.'\')" class="relative group/btn flex items-center justify-center w-8 h-8 bg-white dark:bg-zinc-800 border border-gray-200 dark:border-zinc-700 hover:border-emerald-300 text-gray-400 hover:text-emerald-500 rounded-lg transition-all duration-300 shadow-sm focus:outline-none">
                                              <i class="fa-solid fa-clock-rotate-left transition-colors"></i>
                                              <span class="absolute right-full top-1/2 -translate-y-1/2 mr-2 px-2.5 py-1 text-[10px] font-bold text-white bg-gray-900 dark:bg-black rounded-md opacity-0 group-hover/btn:opacity-100 transition-opacity whitespace-nowrap pointer-events-none z-50 shadow-lg flex items-center">
                                                  Restore Item
                                                  <span class="absolute left-full top-1/2 -translate-y-1/2 border-4 border-transparent border-l-gray-900 dark:border-l-black"></span>
                                              </span>
                                          </button>';
                                } else {
                                    echo '<button onclick="openAdjustModal('.$item['id'].', \''.$safe_type.'\', \''.$safe_name.'\', '.$item['stock'].', \''.$safe_metric.'\')" class="relative group/btn flex items-center justify-center w-8 h-8 bg-white dark:bg-zinc-800 border border-gray-200 dark:border-zinc-700 hover:border-purple-300 text-gray-400 hover:text-purple-500 rounded-lg transition-all duration-300 shadow-sm focus:outline-none">
                                              <i class="fa-solid fa-plus-minus transition-colors"></i>
                                              <span class="absolute right-full top-1/2 -translate-y-1/2 mr-2 px-2.5 py-1 text-[10px] font-bold text-white bg-gray-900 dark:bg-black rounded-md opacity-0 group-hover/btn:opacity-100 transition-opacity whitespace-nowrap pointer-events-none z-50 shadow-lg flex items-center">
                                                  Adjust Stock
                                                  <span class="absolute left-full top-1/2 -translate-y-1/2 border-4 border-transparent border-l-gray-900 dark:border-l-black"></span>
                                              </span>
                                          </button>

                                          <button onclick="openInventoryModal('.$item['id'].', \''.$safe_type.'\', \''.$safe_sku.'\', \''.$safe_name.'\', '.$item['stock'].', '.$item['price'].', '.$item['alert'].', \''.$safe_metric.'\')" class="relative group/btn flex items-center justify-center w-8 h-8 bg-white dark:bg-zinc-800 border border-gray-200 dark:border-zinc-700 hover:border-blue-300 text-gray-400 hover:text-blue-500 rounded-lg transition-all duration-300 shadow-sm focus:outline-none">
                                              <i class="fa-solid fa-pen-to-square transition-colors"></i>
                                              <span class="absolute right-full top-1/2 -translate-y-1/2 mr-2 px-2.5 py-1 text-[10px] font-bold text-white bg-gray-900 dark:bg-black rounded-md opacity-0 group-hover/btn:opacity-100 transition-opacity whitespace-nowrap pointer-events-none z-50 shadow-lg flex items-center">
                                                  Edit Details
                                                  <span class="absolute left-full top-1/2 -translate-y-1/2 border-4 border-transparent border-l-gray-900 dark:border-l-black"></span>
                                              </span>
                                          </button>
                                          
                                          <button onclick="archiveItem('.$item['id'].', \''.$safe_type.'\')" class="relative group/btn flex items-center justify-center w-8 h-8 bg-white dark:bg-zinc-800 border border-gray-200 dark:border-zinc-700 hover:border-amber-300 text-gray-400 hover:text-amber-500 rounded-lg transition-all duration-300 shadow-sm focus:outline-none">
                                              <i class="fa-solid fa-box-archive transition-colors"></i>
                                              <span class="absolute right-full top-1/2 -translate-y-1/2 mr-2 px-2.5 py-1 text-[10px] font-bold text-white bg-gray-900 dark:bg-black rounded-md opacity-0 group-hover/btn:opacity-100 transition-opacity whitespace-nowrap pointer-events-none z-50 shadow-lg flex items-center">
                                                  Archive Item
                                                  <span class="absolute left-full top-1/2 -translate-y-1/2 border-4 border-transparent border-l-gray-900 dark:border-l-black"></span>
                                              </span>
                                          </button>';
                                }

                        echo '  </div>
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

<div id="adjust-stock-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm transition-opacity" onclick="closeAdjustModal()"></div>
    
    <div class="relative bg-white dark:bg-zinc-900 rounded-2xl w-full max-w-md shadow-2xl overflow-hidden flex flex-col border border-gray-100 dark:border-zinc-800">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-zinc-800 flex justify-between items-center bg-gray-50/50 dark:bg-zinc-950/30">
            <div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Adjust Stock Level</h3>
                <p id="adj_item_name" class="text-xs font-bold text-purple-600 dark:text-purple-400 uppercase tracking-widest mt-1">Item Name</p>
            </div>
            <button onclick="closeAdjustModal()" class="text-gray-400 hover:text-rose-500 transition-colors focus:outline-none">
                <i class="fa-solid fa-xmark text-xl"></i>
            </button>
        </div>
        
        <div class="p-6">
            <form id="adjust-stock-form" class="space-y-5">
                <input type="hidden" id="adj_id">
                <input type="hidden" id="adj_type">
                
                <div class="flex justify-between items-center bg-gray-50 dark:bg-zinc-950 p-4 rounded-xl border border-gray-200 dark:border-zinc-800">
                    <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Current Stock:</span>
                    <span id="adj_current_stock" class="text-lg font-black text-gray-900 dark:text-white">0</span>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-600 dark:text-zinc-400 mb-2 uppercase tracking-wide">Action Type</label>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="relative flex cursor-pointer rounded-xl border border-gray-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-3 shadow-sm focus:outline-none has-[:checked]:border-emerald-500 has-[:checked]:bg-emerald-50 dark:has-[:checked]:bg-emerald-500/10 transition-all">
                            <input type="radio" name="adj_action" value="add" class="sr-only" checked>
                            <div class="text-sm font-bold text-emerald-600 dark:text-emerald-400 text-center w-full"><i class="fa-solid fa-plus mr-1"></i> Add Stock</div>
                        </label>
                        <label class="relative flex cursor-pointer rounded-xl border border-gray-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-3 shadow-sm focus:outline-none has-[:checked]:border-rose-500 has-[:checked]:bg-rose-50 dark:has-[:checked]:bg-rose-500/10 transition-all">
                            <input type="radio" name="adj_action" value="deduct" class="sr-only">
                            <div class="text-sm font-bold text-rose-600 dark:text-rose-400 text-center w-full"><i class="fa-solid fa-minus mr-1"></i> Deduct Stock</div>
                        </label>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-600 dark:text-zinc-400 mb-2 uppercase tracking-wide">Quantity to Adjust</label>
                    <input type="number" step="0.01" min="0.01" id="adj_qty" required placeholder="0.00" class="w-full px-4 py-3 bg-gray-50 dark:bg-zinc-950 border border-gray-200 dark:border-zinc-800 text-gray-900 dark:text-white rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent outline-none transition-all text-sm font-bold">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-600 dark:text-zinc-400 mb-2 uppercase tracking-wide">Reason / Remarks (Required)</label>
                    <textarea id="adj_reason" rows="3" required placeholder="e.g., Spoiled fabric during tailoring, found extra stock..." class="w-full px-4 py-3 bg-gray-50 dark:bg-zinc-950 border border-gray-200 dark:border-zinc-800 text-gray-900 dark:text-white rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent outline-none transition-all text-sm font-medium"></textarea>
                </div>
            </form>
        </div>
        
        <div class="px-6 py-4 border-t border-gray-100 dark:border-zinc-800 bg-gray-50/50 dark:bg-zinc-950/30 flex justify-end gap-3">
            <button type="button" onclick="closeAdjustModal()" class="px-5 py-2.5 text-sm font-bold text-gray-600 dark:text-zinc-400 hover:bg-gray-100 dark:hover:bg-zinc-800 rounded-xl transition-colors focus:outline-none">Cancel</button>
            <button type="button" onclick="saveAdjustment()" class="bg-purple-600 hover:bg-purple-700 text-white px-5 py-2.5 rounded-xl text-sm font-bold transition-all shadow-md shadow-purple-600/20 focus:outline-none">Submit Adjustment</button>
        </div>
    </div>
</div>

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
                        <label class="relative flex cursor-pointer rounded-xl border border-gray-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-3 shadow-sm focus:outline-none has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50 dark:has-[:checked]:bg-blue-900/10 transition-all">
                            <input type="radio" name="inv_type" value="raw_material" class="sr-only" onchange="toggleItemTypeFields()" checked>
                            <div class="text-sm font-bold text-gray-900 dark:text-white text-center w-full">Raw Material</div>
                        </label>
                        <label class="relative flex cursor-pointer rounded-xl border border-gray-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-3 shadow-sm focus:outline-none has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50 dark:has-[:checked]:bg-blue-900/10 transition-all">
                            <input type="radio" name="inv_type" value="premade_product" class="sr-only" onchange="toggleItemTypeFields()">
                            <div class="text-sm font-bold text-gray-900 dark:text-white text-center w-full">Premade Product</div>
                        </label>
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-4">
                    <div class="col-span-1">
                        <label class="block text-xs font-bold text-gray-600 dark:text-zinc-400 mb-2 uppercase tracking-wide">SKU</label>
                        <input type="text" id="inv_sku" required placeholder="RAW-001" class="w-full px-4 py-3 bg-gray-50 dark:bg-zinc-950 border border-gray-200 dark:border-zinc-800 text-gray-900 dark:text-white rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition-all text-sm font-bold placeholder-gray-400 dark:placeholder-zinc-600">
                    </div>
                    <div class="col-span-2">
                        <label class="block text-xs font-bold text-gray-600 dark:text-zinc-400 mb-2 uppercase tracking-wide">Item Name</label>
                        <input type="text" id="inv_name" required placeholder="e.g., Signature Pink Thread" class="w-full px-4 py-3 bg-gray-50 dark:bg-zinc-950 border border-gray-200 dark:border-zinc-800 text-gray-900 dark:text-white rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition-all text-sm font-medium placeholder-gray-400 dark:placeholder-zinc-600">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-600 dark:text-zinc-400 mb-2 uppercase tracking-wide">Current Stock</label>
                        <input type="number" step="0.01" id="inv_stock" value="0.00" required class="w-full px-4 py-3 bg-gray-50 dark:bg-zinc-950 border border-gray-200 dark:border-zinc-800 text-gray-900 dark:text-white rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition-all text-sm font-medium">
                        <p id="inv_stock_hint" class="hidden text-[9px] text-gray-400 font-medium mt-1">Use the adjust button to modify.</p>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-600 dark:text-zinc-400 mb-2 uppercase tracking-wide">Price / Cost (₱)</label>
                        <input type="number" id="inv_price" step="0.01" min="0" value="0.00" required class="w-full px-4 py-3 bg-gray-50 dark:bg-zinc-950 border border-gray-200 dark:border-zinc-800 text-gray-900 dark:text-white rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition-all text-sm font-medium">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div id="field_uom">
                        <label class="block text-xs font-bold text-gray-600 dark:text-zinc-400 mb-2 uppercase tracking-wide">Unit of Measure</label>
                        <input type="text" id="inv_uom" placeholder="e.g., Yards, Cones" class="w-full px-4 py-3 bg-gray-50 dark:bg-zinc-950 border border-gray-200 dark:border-zinc-800 text-gray-900 dark:text-white rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition-all text-sm font-medium placeholder-gray-400 dark:placeholder-zinc-600">
                    </div>
                    <div id="field_size" class="hidden">
                        <label class="block text-xs font-bold text-gray-600 dark:text-zinc-400 mb-2 uppercase tracking-wide">Clothing Size</label>
                        <input type="text" id="inv_size" placeholder="e.g., Small, XL" class="w-full px-4 py-3 bg-gray-50 dark:bg-zinc-950 border border-gray-200 dark:border-zinc-800 text-gray-900 dark:text-white rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition-all text-sm font-medium placeholder-gray-400 dark:placeholder-zinc-600">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-600 dark:text-zinc-400 mb-2 uppercase tracking-wide">Low Stock Alert</label>
                        <input type="number" step="0.01" id="inv_alert" min="0" value="10.00" required class="w-full px-4 py-3 bg-gray-50 dark:bg-zinc-950 border border-gray-200 dark:border-zinc-800 text-gray-900 dark:text-white rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition-all text-sm font-medium">
                    </div>
                </div>
            </form>
        </div>
        
        <div class="px-6 py-4 border-t border-gray-100 dark:border-zinc-800 bg-gray-50/50 dark:bg-zinc-950/30 flex justify-end gap-3">
            <button type="button" onclick="closeInventoryModal()" class="px-5 py-2.5 text-sm font-bold text-gray-600 dark:text-zinc-400 hover:bg-gray-100 dark:hover:bg-zinc-800 rounded-xl transition-colors focus:outline-none">
                Cancel
            </button>
            <button type="button" onclick="saveInventory()" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl text-sm font-bold transition-all shadow-md shadow-blue-600/20 focus:outline-none">
                Save Details
            </button>
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
    // 0. GLOBAL UI OVERRIDES
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

    window.alert = customAlert;

    // --- Pagination Logic ---
    const searchInput = document.getElementById('search-input');
    const tbody = document.getElementById('inventory-tbody');
    const allRows = Array.from(tbody.querySelectorAll('tr.inventory-row'));
    const paginationContainer = document.getElementById('pagination-container');
    const isUnified = <?= $is_unified ? 'true' : 'false' ?>;
    const isRawMaterial = <?= $view === 'raw_material' ? 'true' : 'false' ?>;
    
    let colspanCount = 5;
    if (isUnified) colspanCount = 5; 
    else if (isRawMaterial) colspanCount = 6;

    let currentPage = 1;
    const rowsPerPage = 15;

    function updateTable() {
        const searchTerm = searchInput.value.toLowerCase().trim();
        
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
        const phpEmpty = document.getElementById('php-empty-state');

        // 🚨 FIXED: Smart Empty State Logic
        if (totalItems === 0) {
            if (phpEmpty && searchTerm === '') {
                // Database is empty
                phpEmpty.style.display = '';
                if (existingEmptyRow) existingEmptyRow.style.display = 'none';
            } else {
                // Search is empty
                if (phpEmpty) phpEmpty.style.display = 'none';
                if (!existingEmptyRow) {
                    tbody.insertAdjacentHTML('beforeend', `<tr id="js-empty-state"><td colspan="${colspanCount}" class="px-6 py-8 text-center text-gray-500 font-medium">No items found matching your search.</td></tr>`);
                } else {
                    existingEmptyRow.style.display = '';
                }
            }
        } else {
            if (existingEmptyRow) existingEmptyRow.style.display = 'none';
            if (phpEmpty) phpEmpty.style.display = 'none';
        }

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


    // ==========================================
    // 🚨 NEW: STOCK ADJUSTMENT LOGIC
    // ==========================================
    function openAdjustModal(id, type, name, currentStock, metric) {
        document.getElementById('adj_id').value = id;
        document.getElementById('adj_type').value = type;
        document.getElementById('adj_item_name').textContent = name;
        
        // Remove 'Size: ' prefix for clean UI if it's a product
        const cleanMetric = metric.replace('Size: ', '');
        document.getElementById('adj_current_stock').textContent = currentStock + ' ' + cleanMetric;
        
        document.getElementById('adj_qty').value = '';
        document.getElementById('adj_reason').value = '';
        
        // Reset to Add Stock
        document.querySelector('input[name="adj_action"][value="add"]').checked = true;

        document.getElementById('adjust-stock-modal').classList.remove('hidden');
    }

    function closeAdjustModal() {
        document.getElementById('adjust-stock-modal').classList.add('hidden');
    }

    async function saveAdjustment() {
        const payload = {
            item_id: document.getElementById('adj_id').value,
            item_type: document.getElementById('adj_type').value,
            action: document.querySelector('input[name="adj_action"]:checked').value,
            qty: document.getElementById('adj_qty').value,
            reason: document.getElementById('adj_reason').value.trim()
        };

        if(!payload.qty || payload.qty <= 0) return customAlert("Please enter a valid quantity.", "Missing Data", "error");
        if(!payload.reason) return customAlert("A reason is required for stock adjustments.", "Missing Reason", "error");

        try {
            const res = await fetch('actions/adjust_stock.php', {
                method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload)
            });
            const data = await res.json();
            if(data.status === 'success') {
                customAlert("Stock adjusted successfully and logged.", "Success", "success");
                setTimeout(() => window.location.reload(), 1500);
            } else {
                customAlert("Error: " + data.message, "Error", "error");
            }
        } catch (e) { customAlert("Network Error", "Error", "error"); }
    }


    // ==========================================
    // STANDARD INVENTORY MODAL LOGIC
    // ==========================================
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

    const currentBaseType = isUnified ? 'raw_material' : '<?= $view ?>';

    function openInventoryModal(id = '', type = currentBaseType, sku = '', name = '', stock = 0, price = 0.00, alert = 10, metric = '') {
        document.getElementById('inv_id').value = id;
        document.getElementById('inv_sku').value = sku;
        document.getElementById('inv_name').value = name;
        document.getElementById('inv_price').value = price;
        document.getElementById('inv_alert').value = alert;

        const stockInput = document.getElementById('inv_stock');
        const stockHint = document.getElementById('inv_stock_hint');
        
        // 🚨 NEW: DISABLE QUANTITY EDIT IF UPDATING AN ITEM
        if (id !== '') {
            stockInput.value = stock;
            stockInput.readOnly = true;
            stockInput.classList.add('bg-gray-100', 'dark:bg-zinc-800', 'cursor-not-allowed', 'text-gray-500');
            stockHint.classList.remove('hidden');
        } else {
            stockInput.value = 0.00;
            stockInput.readOnly = false;
            stockInput.classList.remove('bg-gray-100', 'dark:bg-zinc-800', 'cursor-not-allowed', 'text-gray-500');
            stockHint.classList.add('hidden');
        }

        document.querySelector(`input[name="inv_type"][value="${type}"]`).checked = true;
        toggleItemTypeFields(); 

        if (type === 'raw_material') {
            document.getElementById('inv_uom').value = metric;
            document.getElementById('inv_size').value = '';
        } else {
            document.getElementById('inv_size').value = metric.replace('Size: ', '');
            document.getElementById('inv_uom').value = '';
        }

        document.querySelectorAll('input[name="inv_type"]').forEach(radio => {
            radio.disabled = id !== ''; 
        });

        document.getElementById('inv_title').textContent = id ? "Edit Item Details" : "Add New Item";
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
            stock: document.getElementById('inv_stock').value, // Used only if new
            price: document.getElementById('inv_price').value,
            alert: document.getElementById('inv_alert').value,
            uom: document.getElementById('inv_uom').value,
            size: document.getElementById('inv_size').value
        };

        if(!payload.sku) return customAlert("SKU is required!", "Missing Data", "error");
        if(!payload.name) return customAlert("Item Name is required!", "Missing Data", "error");

        try {
            const res = await fetch('actions/save_inventory.php', {
                method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload)
            });
            const data = await res.json();
            if(data.status === 'success') {
                customAlert("Item details saved successfully!", "Success", "success");
                const returnView = isUnified ? type : '<?= $view ?>';
                setTimeout(() => window.location.href = `?view=${returnView}`, 1500);
            } else {
                customAlert("Error: " + data.message, "Error", "error");
            }
        } catch (e) { customAlert("Network Error", "Error", "error"); }
    }

    async function archiveItem(id, type) {
        const isConfirmed = await customConfirm("Are you sure you want to archive this item? It will be hidden from active inventory.", "Archive Item");
        if(!isConfirmed) return;
        
        try {
            const res = await fetch('actions/delete_inventory.php', {
                method: 'POST', headers: { 'Content-Type': 'application/json' }, 
                body: JSON.stringify({ item_id: id, item_type: type })
            });
            const data = await res.json();
            if(data.status === 'success') window.location.reload();
            else customAlert(data.message, "Error", "error");
        } catch (e) { customAlert("Network Error", "Error", "error"); }
    }

    async function restoreItem(id, type) {
        const isConfirmed = await customConfirm("Restore this item back to active inventory?", "Restore Item", "Yes, Restore", "info");
        if(!isConfirmed) return;
        
        try {
            const res = await fetch('actions/restore_inventory.php', {
                method: 'POST', headers: { 'Content-Type': 'application/json' }, 
                body: JSON.stringify({ item_id: id, item_type: type })
            });
            const data = await res.json();
            if(data.status === 'success') window.location.reload();
            else customAlert(data.message, "Error", "error");
        } catch (e) { customAlert("Network Error", "Error", "error"); }
    }
</script>

<?php include 'includes/footer.php'; ?>