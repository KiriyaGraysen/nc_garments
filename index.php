<?php
require_once('config/database.php');

// Security Kick-out
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Extract the user's first name for the greeting
$admin_name = $_SESSION['full_name'] ?? 'Admin';
$first_name = explode(' ', trim($admin_name))[0];

// ========================================================
// 1. TOP STATS CALCULATIONS
// ========================================================

// Total Sales (Last 30 Days)
$sales_stmt = $conn->query("SELECT COALESCE(SUM(amount_paid), 0) as total FROM payment WHERE payment_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)");
$total_sales = $sales_stmt->fetch_assoc()['total'];

// Active Orders
$active_stmt = $conn->query("SELECT COUNT(*) as count FROM project WHERE status = 'active' AND is_archived = 0");
$active_orders = $active_stmt->fetch_assoc()['count'];

// Total Receivables (Total Agreed Price of Active Projects - Total Payments for Active Projects)
$rec_stmt = $conn->query("
    SELECT 
        (SELECT COALESCE(SUM(agreed_price), 0) FROM project WHERE status = 'active' AND is_archived = 0) - 
        (SELECT COALESCE(SUM(amount_paid), 0) FROM payment pay JOIN project p ON pay.project_id = p.project_id WHERE p.status = 'active' AND p.is_archived = 0) AS total_receivables
");
$receivables = $rec_stmt->fetch_assoc()['total_receivables'];

// Low Stock Alerts (Raw Materials + Premade Products)
$low_stmt = $conn->query("
    SELECT 
        (SELECT COUNT(*) FROM premade_product WHERE current_stock <= min_stock_alert AND is_archived = 0) +
        (SELECT COUNT(*) FROM raw_material WHERE current_stock <= min_stock_alert AND is_archived = 0) AS total_low
");
$low_stock = $low_stmt->fetch_assoc()['total_low'];


// ========================================================
// 2. CHART DATA (LAST 6 MONTHS REVENUE VS PROFIT)
// ========================================================
$months = [];
// Generate an array of the last 6 months dynamically (e.g., '2026-04' => ['label' => 'Apr', 'rev' => 0, 'cost' => 0])
for ($i = 5; $i >= 0; $i--) {
    $month_key = date('Y-m', strtotime("-$i months"));
    $months[$month_key] = [
        'label' => date('M', strtotime("-$i months")),
        'revenue' => 0,
        'cost' => 0
    ];
}

// Fetch Revenue (Payments) for the last 6 months
$chart_rev = $conn->query("
    SELECT DATE_FORMAT(payment_date, '%Y-%m') as month_year, SUM(amount_paid) as total_rev
    FROM payment
    WHERE payment_date >= DATE_SUB(LAST_DAY(CURDATE() - INTERVAL 6 MONTH), INTERVAL 0 DAY)
    GROUP BY month_year
");
while ($row = $chart_rev->fetch_assoc()) {
    if (isset($months[$row['month_year']])) $months[$row['month_year']]['revenue'] += $row['total_rev'];
}

// Fetch Costs (Breakdowns) for projects created in the last 6 months
$chart_cost = $conn->query("
    SELECT DATE_FORMAT(p.created_at, '%Y-%m') as month_year, SUM(pb.total_cost) as total_cost
    FROM project p
    JOIN project_breakdown pb ON p.project_id = pb.project_id
    WHERE p.created_at >= DATE_SUB(LAST_DAY(CURDATE() - INTERVAL 6 MONTH), INTERVAL 0 DAY)
    GROUP BY month_year
");
while ($row = $chart_cost->fetch_assoc()) {
    if (isset($months[$row['month_year']])) $months[$row['month_year']]['cost'] += $row['total_cost'];
}

// Find the maximum revenue month to scale our CSS bar heights dynamically
$max_val = 1; 
foreach ($months as $m) {
    if ($m['revenue'] > $max_val) $max_val = $m['revenue'];
}

// ========================================================
// 3. UPCOMING DEADLINES
// ========================================================
$deadlines_stmt = $conn->query("
    SELECT p.project_id, p.project_name, p.quantity, p.due_date, p.progress, 
           COALESCE(c.full_name, 'Internal Restock') as client_name
    FROM project p
    LEFT JOIN customer c ON p.customer_id = c.customer_id
    WHERE p.status = 'active' AND p.is_archived = 0
    ORDER BY p.due_date ASC
    LIMIT 3
");

// Calculate how many active projects are left over for the "+ X more" button
$remaining_projects = max(0, $active_orders - $deadlines_stmt->num_rows);

$progress_percentages = [
    'not started' => 0, 'sampling' => 15, 'cutting' => 30, 
    'printing' => 45, 'sewing' => 60, 'quality check' => 75, 
    'finishing' => 85, 'packing' => 95, 'done' => 100, 
    'released' => 100, 'cancelled' => 0
];


$page_title = "Dashboard | NC Garments";
include 'includes/header.php'; 
?>

        <main class="flex-1 bg-gray-50 dark:bg-zinc-950 p-8 overflow-y-auto transition-colors duration-500 font-sans">
        
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white transition-colors duration-500">Dashboard Overview</h2>
                    <p class="text-gray-500 dark:text-zinc-400 text-sm mt-1 transition-colors duration-500">Welcome back, <?= htmlspecialchars($first_name) ?>. Here is what's happening today.</p>
                </div>
                <div class="flex gap-3">
                    <a href="reports.php" class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 hover:bg-gray-50 dark:hover:bg-zinc-800 text-gray-700 dark:text-zinc-300 px-4 py-2.5 rounded-xl text-sm font-semibold transition-colors shadow-sm flex items-center gap-2 cursor-pointer focus:outline-none focus:ring-2 focus:ring-pink-500">
                        <i class="fa-solid fa-download text-pink-600 dark:text-pink-500"></i> Download Report
                    </a>
                </div>
            </div>
    
            <div class="bg-white/50 dark:bg-zinc-900/30 border border-gray-200 dark:border-zinc-800 rounded-2xl p-6 mb-8 transition-colors duration-500">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-base font-bold text-gray-900 dark:text-white transition-colors duration-500">Live Snapshot</h3>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-6">
                    
                    <div class="bg-white dark:bg-zinc-900 rounded-xl shadow-sm border border-gray-100 dark:border-zinc-800 flex flex-col overflow-hidden transition-colors duration-500 hover:shadow-md hover:border-pink-200 dark:hover:border-pink-900/50 group">
                        <div class="p-5 flex items-center gap-4 flex-grow">
                            <div class="h-12 w-12 rounded-xl bg-pink-100 dark:bg-pink-900/30 text-pink-600 dark:text-pink-500 flex items-center justify-center text-xl shrink-0 group-hover:bg-pink-600 group-hover:text-white transition-colors">
                                <i class="fa-solid fa-peso-sign"></i>
                            </div>
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-zinc-400">Total Sales (30 Days)</p>
                                <div class="flex items-baseline gap-2 mt-1">
                                    <h4 class="text-2xl font-extrabold text-gray-900 dark:text-white">₱ <?= number_format($total_sales, 2) ?></h4>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 dark:bg-zinc-900/80 px-5 py-3 border-t border-gray-50 dark:border-zinc-800 transition-colors duration-500">
                            <a href="reports.php" class="text-xs font-bold text-pink-600 dark:text-pink-500 hover:text-pink-700 dark:hover:text-pink-400 flex items-center justify-between">
                                View ledgers <i class="fa-solid fa-arrow-right text-[10px]"></i>
                            </a>
                        </div>
                    </div>
                    
                    <div class="bg-white dark:bg-zinc-900 rounded-xl shadow-sm border border-gray-100 dark:border-zinc-800 flex flex-col overflow-hidden transition-colors duration-500 hover:shadow-md hover:border-pink-200 dark:hover:border-pink-900/50 group">
                        <div class="p-5 flex items-center gap-4 flex-grow">
                            <div class="h-12 w-12 rounded-xl bg-pink-100 dark:bg-pink-900/30 text-pink-600 dark:text-pink-500 flex items-center justify-center text-xl shrink-0 group-hover:bg-pink-600 group-hover:text-white transition-colors">
                                <i class="fa-solid fa-shirt"></i>
                            </div>
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-zinc-400">Active Orders</p>
                                <div class="flex items-baseline gap-2 mt-1">
                                    <h4 class="text-2xl font-extrabold text-gray-900 dark:text-white"><?= number_format($active_orders) ?></h4>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 dark:bg-zinc-900/80 px-5 py-3 border-t border-gray-50 dark:border-zinc-800 transition-colors duration-500">
                            <a href="projects.php" class="text-xs font-bold text-pink-600 dark:text-pink-500 hover:text-pink-700 dark:hover:text-pink-400 flex items-center justify-between">
                                Manage projects <i class="fa-solid fa-arrow-right text-[10px]"></i>
                            </a>
                        </div>
                    </div>
                    
                    <div class="bg-white dark:bg-zinc-900 rounded-xl shadow-sm border border-gray-100 dark:border-zinc-800 flex flex-col overflow-hidden transition-colors duration-500 hover:shadow-md hover:border-pink-200 dark:hover:border-pink-900/50 group">
                        <div class="p-5 flex items-center gap-4 flex-grow">
                            <div class="h-12 w-12 rounded-xl bg-pink-100 dark:bg-pink-900/30 text-pink-600 dark:text-pink-500 flex items-center justify-center text-xl shrink-0 group-hover:bg-pink-600 group-hover:text-white transition-colors">
                                <i class="fa-solid fa-hand-holding-dollar"></i>
                            </div>
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-zinc-400">Outstanding Receivables</p>
                                <div class="flex items-baseline gap-2 mt-1">
                                    <h4 class="text-2xl font-extrabold text-gray-900 dark:text-white">₱ <?= number_format($receivables, 2) ?></h4>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 dark:bg-zinc-900/80 px-5 py-3 border-t border-gray-50 dark:border-zinc-800 transition-colors duration-500">
                            <a href="customers.php" class="text-xs font-bold text-pink-600 dark:text-pink-500 hover:text-pink-700 dark:hover:text-pink-400 flex items-center justify-between">
                                Collect payments <i class="fa-solid fa-arrow-right text-[10px]"></i>
                            </a>
                        </div>
                    </div>
                    
                    <div class="bg-white dark:bg-zinc-900 rounded-xl shadow-sm border border-gray-100 dark:border-zinc-800 flex flex-col overflow-hidden transition-colors duration-500 hover:shadow-md <?= $low_stock > 0 ? 'hover:border-rose-200 dark:hover:border-rose-900/50' : 'hover:border-emerald-200 dark:hover:border-emerald-900/50' ?> group">
                        <div class="p-5 flex items-center gap-4 flex-grow">
                            <div class="h-12 w-12 rounded-xl <?= $low_stock > 0 ? 'bg-rose-100 dark:bg-rose-900/30 text-rose-600 group-hover:bg-rose-600' : 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 group-hover:bg-emerald-600' ?> flex items-center justify-center text-xl shrink-0 group-hover:text-white transition-colors">
                                <i class="fa-solid <?= $low_stock > 0 ? 'fa-triangle-exclamation' : 'fa-check' ?>"></i>
                            </div>
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-zinc-400">Low Stock Alerts</p>
                                <div class="flex items-baseline gap-2 mt-1">
                                    <h4 class="text-2xl font-extrabold text-gray-900 dark:text-white"><?= number_format($low_stock) ?> Items</h4>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 dark:bg-zinc-900/80 px-5 py-3 border-t border-gray-50 dark:border-zinc-800 transition-colors duration-500">
                            <a href="inventory.php" class="text-xs font-bold <?= $low_stock > 0 ? 'text-rose-600 dark:text-rose-500 hover:text-rose-700 dark:hover:text-rose-400' : 'text-emerald-600 dark:text-emerald-500 hover:text-emerald-700 dark:hover:text-emerald-400' ?> flex items-center justify-between">
                                Check inventory <i class="fa-solid fa-arrow-right text-[10px]"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
    
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                
                <div class="lg:col-span-2 bg-white dark:bg-zinc-900 border border-gray-100 dark:border-zinc-800 rounded-2xl p-6 shadow-sm transition-colors duration-500 flex flex-col">
                    <div class="flex justify-between items-center mb-6">
                        <div>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">Revenue Overview</h3>
                            <p class="text-xs font-medium text-gray-500 dark:text-zinc-400 mt-1 uppercase tracking-wider">Gross sales vs. Profit margin (Last 6 Months)</p>
                        </div>
                        <a href="reports.php" class="w-8 h-8 rounded-full flex items-center justify-center text-gray-400 hover:text-pink-600 hover:bg-pink-50 dark:hover:bg-zinc-800 transition-colors focus:outline-none"><i class="fa-solid fa-ellipsis"></i></a>
                    </div>
                    
                    <div class="flex-grow flex items-end gap-4 h-64 pt-4 border-b border-gray-100 dark:border-zinc-800 pb-2">
                        
                        <?php 
                        // Loop through our calculated PHP months array to render the CSS bars!
                        $counter = 0;
                        $total_months = count($months);
                        foreach ($months as $m): 
                            $counter++;
                            $rev = $m['revenue'];
                            $cost = $m['cost'];
                            $profit = max(0, $rev - $cost);
                            
                            // Height logic for CSS
                            $height_pct = ($max_val > 0) ? ($rev / $max_val) * 100 : 0;
                            if ($height_pct < 5 && $rev > 0) $height_pct = 5; // Minimum visible height
                            
                            $inner_height_pct = ($rev > 0) ? ($profit / $rev) * 100 : 0;
                            
                            // Formatting the number text
                            $label = $rev >= 1000 ? round($rev / 1000, 1) . 'k' : $rev;
                            
                            // Style logic (highlight the most recent month)
                            $is_current = ($counter === $total_months);
                        ?>
                            <div class="flex-1 flex flex-col justify-end items-center group cursor-pointer" title="Revenue: ₱<?= number_format($rev,2) ?> | Cost: ₱<?= number_format($cost,2) ?>">
                                <span class="text-xs <?= $is_current ? 'text-pink-600 dark:text-pink-400 font-extrabold' : 'text-transparent group-hover:text-gray-600 dark:group-hover:text-zinc-300 font-bold' ?> mb-2 transition-colors">₱<?= $label ?></span>
                                
                                <div class="w-full max-w-[40px] <?= $is_current ? 'bg-pink-200 dark:bg-pink-900/60 border-2 border-pink-500' : 'bg-pink-100 dark:bg-pink-900/20 group-hover:bg-pink-200 dark:group-hover:bg-pink-900/40' ?> rounded-t-lg relative transition-colors" style="height: <?= $height_pct ?>%;">
                                    <div class="absolute bottom-0 w-full <?= $is_current ? 'bg-pink-600 dark:bg-pink-500' : 'bg-pink-500 dark:bg-pink-600' ?> rounded-t-sm transition-all" style="height: <?= $inner_height_pct ?>%;"></div>
                                </div>
                                
                                <span class="text-xs <?= $is_current ? 'text-pink-600 dark:text-pink-400 font-extrabold' : 'text-gray-500 dark:text-zinc-500 font-bold' ?> mt-3 uppercase"><?= $m['label'] ?></span>
                            </div>
                        <?php endforeach; ?>
                        
                    </div>
                    
                    <div class="flex justify-center gap-6 mt-5">
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 bg-pink-200 dark:bg-pink-900/60 rounded-full"></div>
                            <span class="text-xs text-gray-600 dark:text-zinc-400 font-bold uppercase tracking-wider">Gross Cost</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 bg-pink-600 dark:bg-pink-500 rounded-full"></div>
                            <span class="text-xs text-gray-600 dark:text-zinc-400 font-bold uppercase tracking-wider">Net Profit</span>
                        </div>
                    </div>
                </div>
    
                <div class="bg-white dark:bg-zinc-900 border border-gray-100 dark:border-zinc-800 rounded-2xl p-6 shadow-sm transition-colors duration-500 flex flex-col">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Upcoming Deadlines</h3>
                        <a href="projects.php" class="text-xs font-bold text-pink-600 dark:text-pink-500 hover:text-pink-700 dark:hover:text-pink-400 uppercase tracking-wide">View Timeline</a>
                    </div>
    
                    <div class="space-y-4 flex-grow">
                        
                        <?php 
                        if ($deadlines_stmt->num_rows === 0) {
                            echo '<div class="text-center text-sm text-gray-500 py-8 italic">No upcoming deadlines.</div>';
                        }
                        
                        while ($project = $deadlines_stmt->fetch_assoc()): 
                            $due_date = new DateTime($project['due_date']);
                            $month = strtoupper($due_date->format('M'));
                            $day = $due_date->format('d');
                            $pct = $progress_percentages[$project['progress']] ?? 0;
                            
                            // Calculate days left to determine UI colors
                            $today = new DateTime('today');
                            $due_date->setTime(0, 0, 0); 
                            $diff = $today->diff($due_date);
                            $days_left = $diff->invert ? -$diff->days : $diff->days;
                            
                            // Dynamic color engine
                            if ($days_left <= 0) { // Overdue or Due Today
                                $color = 'rose';
                            } elseif ($days_left <= 7) { // Due within a week
                                $color = 'amber';
                            } else { // Safe
                                $color = 'emerald';
                            }
                        ?>
                        <div class="flex gap-4 group cursor-pointer items-center" onclick="window.location.href='projects.php'">
                            <div class="flex flex-col items-center min-w-[3rem]">
                                <span class="text-[10px] font-extrabold text-<?= $color ?>-500 tracking-wider"><?= $month ?></span>
                                <span class="text-2xl font-extrabold text-gray-900 dark:text-white group-hover:text-pink-600 dark:group-hover:text-pink-500 transition-colors"><?= $day ?></span>
                            </div>
                            <div class="flex-grow bg-<?= $color ?>-50/50 dark:bg-<?= $color ?>-900/10 border border-<?= $color ?>-100 dark:border-<?= $color ?>-900/30 rounded-xl p-3.5 transition-colors">
                                <h4 class="text-sm font-bold text-gray-900 dark:text-white mb-0.5 truncate max-w-[200px]"><?= htmlspecialchars($project['project_name']) ?></h4>
                                <p class="text-xs font-medium text-gray-500 dark:text-zinc-400 mb-3 truncate max-w-[200px]"><?= htmlspecialchars($project['client_name']) ?> (<?= htmlspecialchars($project['quantity']) ?> pcs)</p>
                                <div class="w-full bg-gray-200 dark:bg-zinc-800 rounded-full h-1.5 overflow-hidden">
                                    <div class="bg-<?= $color ?>-500 h-1.5 rounded-full" style="width: <?= $pct ?>%"></div>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>

                    </div>
                    
                    <?php if ($remaining_projects > 0): ?>
                    <button onclick="window.location.href='projects.php'" class="w-full mt-4 py-3 text-xs font-bold uppercase tracking-wider text-gray-600 dark:text-zinc-400 bg-gray-50 hover:bg-gray-100 dark:bg-zinc-800 dark:hover:bg-zinc-700 rounded-xl transition-colors border border-gray-200 dark:border-zinc-700 focus:outline-none">
                        + <?= $remaining_projects ?> more active projects
                    </button>
                    <?php endif; ?>
                </div>
    
            </div>
    
        </main>

<?php include 'includes/footer.php' ?>
