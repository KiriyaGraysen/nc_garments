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

// Total Receivables
$rec_stmt = $conn->query("
    SELECT 
        (SELECT COALESCE(SUM(agreed_price), 0) FROM project WHERE status = 'active' AND is_archived = 0) - 
        (SELECT COALESCE(SUM(amount_paid), 0) FROM payment pay JOIN project p ON pay.project_id = p.project_id WHERE p.status = 'active' AND p.is_archived = 0) AS total_receivables
");
$receivables = $rec_stmt->fetch_assoc()['total_receivables'];

// Low Stock Alerts
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
for ($i = 5; $i >= 0; $i--) {
    $month_key = date('Y-m', strtotime("-$i months"));
    $months[$month_key] = [
        'label' => date('M', strtotime("-$i months")),
        'revenue' => 0,
        'cost' => 0
    ];
}

$chart_rev = $conn->query("
    SELECT DATE_FORMAT(payment_date, '%Y-%m') as month_year, SUM(amount_paid) as total_rev
    FROM payment
    WHERE payment_date >= DATE_SUB(LAST_DAY(CURDATE() - INTERVAL 6 MONTH), INTERVAL 0 DAY)
    GROUP BY month_year
");
while ($row = $chart_rev->fetch_assoc()) {
    if (isset($months[$row['month_year']])) $months[$row['month_year']]['revenue'] += $row['total_rev'];
}

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

$max_val = 1; 
foreach ($months as $m) {
    if ($m['revenue'] > $max_val) $max_val = $m['revenue'];
}

// ========================================================
// 3. UPCOMING DEADLINES (Top 3 for Dashboard)
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

$remaining_projects = max(0, $active_orders - $deadlines_stmt->num_rows);

$progress_percentages = [
    'not started' => 0, 'sampling' => 15, 'cutting' => 30, 
    'printing' => 45, 'sewing' => 60, 'quality check' => 75, 
    'finishing' => 85, 'packing' => 95, 'done' => 100, 
    'released' => 100, 'cancelled' => 0
];

// ========================================================
// 4. ALL DEADLINES FOR CALENDAR MODAL
// ========================================================
$all_deadlines_stmt = $conn->query("
    SELECT p.project_id, p.project_name, p.quantity, p.due_date, p.progress, 
           COALESCE(c.full_name, 'Internal Restock') as client_name
    FROM project p
    LEFT JOIN customer c ON p.customer_id = c.customer_id
    WHERE p.status = 'active' AND p.is_archived = 0
    ORDER BY p.due_date ASC
");

$calendar_projects = [];
while ($row = $all_deadlines_stmt->fetch_assoc()) {
    $date_key = date('Y-m-d', strtotime($row['due_date']));
    $calendar_projects[$date_key][] = $row; // Group projects by exact due date
}

$page_title = "Dashboard | NC Garments";
include 'includes/header.php'; 
?>

        <main class="flex-1 bg-gray-50 dark:bg-zinc-950 p-8 overflow-y-auto transition-colors duration-500 font-sans relative">
        
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
                        <div class="p-5 flex items-center gap-4 flex-grow overflow-hidden">
                            <div class="h-12 w-12 rounded-xl bg-pink-100 dark:bg-pink-900/30 text-pink-600 dark:text-pink-500 flex items-center justify-center text-xl shrink-0 group-hover:bg-pink-600 group-hover:text-white transition-colors">
                                <i class="fa-solid fa-peso-sign"></i>
                            </div>
                            <div class="min-w-0">
                                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-zinc-400 truncate">Total Sales (30 Days)</p>
                                <div class="flex items-baseline gap-2 mt-1">
                                    <h4 class="text-xl xl:text-2xl font-extrabold text-gray-900 dark:text-white whitespace-nowrap tracking-tight">₱ <?= number_format($total_sales, 2) ?></h4>
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
                        <div class="p-5 flex items-center gap-4 flex-grow overflow-hidden">
                            <div class="h-12 w-12 rounded-xl bg-pink-100 dark:bg-pink-900/30 text-pink-600 dark:text-pink-500 flex items-center justify-center text-xl shrink-0 group-hover:bg-pink-600 group-hover:text-white transition-colors">
                                <i class="fa-solid fa-shirt"></i>
                            </div>
                            <div class="min-w-0">
                                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-zinc-400 truncate">Active Orders</p>
                                <div class="flex items-baseline gap-2 mt-1">
                                    <h4 class="text-xl xl:text-2xl font-extrabold text-gray-900 dark:text-white whitespace-nowrap tracking-tight"><?= number_format($active_orders) ?></h4>
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
                        <div class="p-5 flex items-center gap-4 flex-grow overflow-hidden">
                            <div class="h-12 w-12 rounded-xl bg-pink-100 dark:bg-pink-900/30 text-pink-600 dark:text-pink-500 flex items-center justify-center text-xl shrink-0 group-hover:bg-pink-600 group-hover:text-white transition-colors">
                                <i class="fa-solid fa-hand-holding-dollar"></i>
                            </div>
                            <div class="min-w-0">
                                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-zinc-400 truncate">Receivables</p>
                                <div class="flex items-baseline gap-2 mt-1">
                                    <h4 class="text-xl xl:text-2xl font-extrabold text-gray-900 dark:text-white whitespace-nowrap tracking-tight">₱ <?= number_format($receivables, 2) ?></h4>
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
                        <div class="p-5 flex items-center gap-4 flex-grow overflow-hidden">
                            <div class="h-12 w-12 rounded-xl <?= $low_stock > 0 ? 'bg-rose-100 dark:bg-rose-900/30 text-rose-600 group-hover:bg-rose-600' : 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 group-hover:bg-emerald-600' ?> flex items-center justify-center text-xl shrink-0 group-hover:text-white transition-colors">
                                <i class="fa-solid <?= $low_stock > 0 ? 'fa-triangle-exclamation' : 'fa-check' ?>"></i>
                            </div>
                            <div class="min-w-0">
                                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-zinc-400 truncate">Low Stock Alerts</p>
                                <div class="flex items-baseline gap-2 mt-1">
                                    <h4 class="text-xl xl:text-2xl font-extrabold text-gray-900 dark:text-white whitespace-nowrap tracking-tight"><?= number_format($low_stock) ?> Items</h4>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 dark:bg-zinc-900/80 px-5 py-3 border-t border-gray-50 dark:border-zinc-800 transition-colors duration-500">
                            <a href="inventory.php?view=alerts" class="text-xs font-bold <?= $low_stock > 0 ? 'text-rose-600 dark:text-rose-500 hover:text-rose-700 dark:hover:text-rose-400' : 'text-emerald-600 dark:text-emerald-500 hover:text-emerald-700 dark:hover:text-emerald-400' ?> flex items-center justify-between">
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
                        $counter = 0;
                        $total_months = count($months);
                        foreach ($months as $m): 
                            $counter++;
                            $rev = $m['revenue'];
                            $cost = $m['cost'];
                            $profit = max(0, $rev - $cost);
                            
                            $height_pct = ($max_val > 0) ? ($rev / $max_val) * 100 : 0;
                            if ($height_pct < 5 && $rev > 0) $height_pct = 5;
                            
                            $inner_height_pct = ($rev > 0) ? ($profit / $rev) * 100 : 0;
                            $label = $rev >= 1000 ? round($rev / 1000, 1) . 'k' : $rev;
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
                        <button onclick="openTimelineModal()" class="text-xs font-bold text-pink-600 dark:text-pink-500 hover:text-pink-700 dark:hover:text-pink-400 uppercase tracking-wide focus:outline-none flex items-center gap-1">
                            <i class="fa-regular fa-calendar-days"></i> View Timeline
                        </button>
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
                            
                            $today = new DateTime('today');
                            $due_date->setTime(0, 0, 0); 
                            $diff = $today->diff($due_date);
                            $days_left = $diff->invert ? -$diff->days : $diff->days;
                            
                            if ($days_left <= 0) { $color = 'rose'; } 
                            elseif ($days_left <= 7) { $color = 'amber'; } 
                            else { $color = 'emerald'; }
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

        <div id="timeline-modal" class="fixed inset-0 z-[60] hidden flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/60 backdrop-blur-sm transition-opacity" onclick="closeTimelineModal()"></div>
            
            <div class="relative bg-white dark:bg-zinc-900 rounded-2xl w-full max-w-5xl shadow-2xl overflow-hidden flex flex-col h-[80vh] max-h-[800px] border border-gray-100 dark:border-zinc-800">
                
                <div class="px-6 py-4 border-b border-gray-100 dark:border-zinc-800 flex justify-between items-center bg-gray-50/50 dark:bg-zinc-950/30">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Project Timeline Calendar</h3>
                        <p class="text-xs text-gray-500 dark:text-zinc-400 mt-1">Select a date to view production deadlines and progress.</p>
                    </div>
                    <button onclick="closeTimelineModal()" class="text-gray-400 hover:text-rose-500 transition-colors focus:outline-none">
                        <i class="fa-solid fa-xmark text-xl"></i>
                    </button>
                </div>

                <div class="flex flex-col md:flex-row flex-1 overflow-hidden">
                    
                    <div class="w-full md:w-[55%] p-6 border-r border-gray-100 dark:border-zinc-800 flex flex-col bg-white dark:bg-zinc-900">
                        
                        <div class="flex justify-between items-center mb-6">
                            <button onclick="changeMonth(-1)" class="w-8 h-8 rounded-lg bg-gray-50 dark:bg-zinc-800 hover:bg-pink-50 dark:hover:bg-pink-900/20 text-gray-500 hover:text-pink-600 dark:hover:text-pink-500 transition-colors flex items-center justify-center focus:outline-none">
                                <i class="fa-solid fa-chevron-left text-xs"></i>
                            </button>
                            <h4 class="text-base font-black text-gray-900 dark:text-white uppercase tracking-widest" id="calendar-month-year">MONTH YYYY</h4>
                            <button onclick="changeMonth(1)" class="w-8 h-8 rounded-lg bg-gray-50 dark:bg-zinc-800 hover:bg-pink-50 dark:hover:bg-pink-900/20 text-gray-500 hover:text-pink-600 dark:hover:text-pink-500 transition-colors flex items-center justify-center focus:outline-none">
                                <i class="fa-solid fa-chevron-right text-xs"></i>
                            </button>
                        </div>

                        <div class="grid grid-cols-7 mb-2">
                            <div class="text-center text-[10px] font-extrabold text-rose-500 uppercase tracking-widest">Sun</div>
                            <div class="text-center text-[10px] font-extrabold text-gray-400 dark:text-zinc-500 uppercase tracking-widest">Mon</div>
                            <div class="text-center text-[10px] font-extrabold text-gray-400 dark:text-zinc-500 uppercase tracking-widest">Tue</div>
                            <div class="text-center text-[10px] font-extrabold text-gray-400 dark:text-zinc-500 uppercase tracking-widest">Wed</div>
                            <div class="text-center text-[10px] font-extrabold text-gray-400 dark:text-zinc-500 uppercase tracking-widest">Thu</div>
                            <div class="text-center text-[10px] font-extrabold text-gray-400 dark:text-zinc-500 uppercase tracking-widest">Fri</div>
                            <div class="text-center text-[10px] font-extrabold text-gray-400 dark:text-zinc-500 uppercase tracking-widest">Sat</div>
                        </div>

                        <div id="calendar-grid" class="grid grid-cols-7 gap-2 flex-grow auto-rows-fr">
                            </div>
                        
                        <div class="mt-6 flex items-center gap-4 justify-center">
                            <div class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-pink-500"></span><span class="text-[10px] font-bold text-gray-500 uppercase tracking-wider">Has Deadlines</span></div>
                            <div class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full border-2 border-pink-500"></span><span class="text-[10px] font-bold text-gray-500 uppercase tracking-wider">Today</span></div>
                        </div>
                    </div>

                    <div class="w-full md:w-[45%] p-6 bg-gray-50/50 dark:bg-zinc-950/30 overflow-y-auto" id="calendar-details">
                        <div class="h-full flex flex-col items-center justify-center text-gray-400 space-y-3 opacity-50">
                            <i class="fa-regular fa-calendar-check text-4xl"></i>
                            <p class="text-xs font-bold uppercase tracking-wider">Select a date to view deadlines</p>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <script>
            const calendarData = <?php echo json_encode($calendar_projects); ?>;
            const progressMap = <?php echo json_encode($progress_percentages); ?>;
            
            const monthNames = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
            
            let dateCursor = new Date(); // Start at current date
            let currentMonth = dateCursor.getMonth();
            let currentYear = dateCursor.getFullYear();
            let selectedDateStr = null;

            function openTimelineModal() {
                document.getElementById('timeline-modal').classList.remove('hidden');
                renderCalendar(currentMonth, currentYear);
                
                // Automatically select today if there are projects
                const todayStr = getLocalYYYYMMDD(new Date());
                selectDate(todayStr, true);
            }

            function closeTimelineModal() {
                document.getElementById('timeline-modal').classList.add('hidden');
            }

            function changeMonth(offset) {
                currentMonth += offset;
                if (currentMonth < 0) { currentMonth = 11; currentYear--; }
                if (currentMonth > 11) { currentMonth = 0; currentYear++; }
                renderCalendar(currentMonth, currentYear);
            }
            
            // Helper to prevent timezone shifting issues
            function getLocalYYYYMMDD(dateObj) {
                const year = dateObj.getFullYear();
                const month = String(dateObj.getMonth() + 1).padStart(2, '0');
                const day = String(dateObj.getDate()).padStart(2, '0');
                return `${year}-${month}-${day}`;
            }

            function renderCalendar(month, year) {
                document.getElementById('calendar-month-year').textContent = `${monthNames[month]} ${year}`;
                const grid = document.getElementById('calendar-grid');
                grid.innerHTML = '';

                const firstDay = new Date(year, month, 1).getDay();
                const daysInMonth = new Date(year, month + 1, 0).getDate();
                
                const todayStr = getLocalYYYYMMDD(new Date());

                // Blank cells for previous month
                for (let i = 0; i < firstDay; i++) {
                    grid.innerHTML += `<div class="rounded-xl border border-transparent"></div>`;
                }

                // Actual days
                for (let day = 1; day <= daysInMonth; day++) {
                    const dateObj = new Date(year, month, day);
                    const dateStr = getLocalYYYYMMDD(dateObj);
                    
                    const hasProjects = calendarData.hasOwnProperty(dateStr);
                    const isToday = (dateStr === todayStr);
                    const isSelected = (dateStr === selectedDateStr);

                    let baseClasses = "relative rounded-xl border flex items-center justify-center font-bold text-sm cursor-pointer transition-all hover:border-pink-300 dark:hover:border-pink-700 focus:outline-none ";
                    
                    if (isSelected) {
                        baseClasses += "bg-pink-600 text-white border-pink-600 shadow-md shadow-pink-600/30 ";
                    } else if (isToday) {
                        baseClasses += "bg-pink-50 dark:bg-pink-900/20 text-pink-600 dark:text-pink-400 border-pink-500 ";
                    } else if (hasProjects) {
                        baseClasses += "bg-white dark:bg-zinc-800 text-gray-900 dark:text-white border-gray-200 dark:border-zinc-700 hover:bg-pink-50 dark:hover:bg-pink-900/10 ";
                    } else {
                        baseClasses += "bg-transparent text-gray-500 dark:text-zinc-500 border-gray-100 dark:border-zinc-800/50 hover:bg-gray-50 dark:hover:bg-zinc-800 ";
                    }

                    // Dot indicator
                    const dotHtml = hasProjects && !isSelected ? `<span class="absolute bottom-1 w-1.5 h-1.5 rounded-full ${isToday ? 'bg-pink-500' : 'bg-pink-500'}"></span>` : '';

                    grid.innerHTML += `
                        <button onclick="selectDate('${dateStr}')" class="${baseClasses}">
                            ${day}
                            ${dotHtml}
                        </button>
                    `;
                }
            }

            function selectDate(dateStr, isAuto = false) {
                selectedDateStr = dateStr;
                renderCalendar(currentMonth, currentYear); // Re-render to highlight selection

                const detailsPane = document.getElementById('calendar-details');
                const projects = calendarData[dateStr] || [];
                
                // Format display date
                const dObj = new Date(dateStr + "T00:00:00"); // Force local parsing
                const displayDate = dObj.toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' });

                if (projects.length === 0) {
                    if(isAuto) return; // Don't show empty state if auto-selecting today
                    detailsPane.innerHTML = `
                        <div class="h-full flex flex-col items-center justify-center text-gray-400 space-y-3 opacity-50">
                            <i class="fa-regular fa-face-smile text-4xl"></i>
                            <p class="text-xs font-bold uppercase tracking-wider text-center">No deadlines scheduled for<br><span class="text-gray-500">${displayDate}</span></p>
                        </div>
                    `;
                    return;
                }

                let html = `
                    <div class="mb-6 border-b border-gray-200 dark:border-zinc-800 pb-4">
                        <p class="text-[10px] font-black text-pink-600 dark:text-pink-500 uppercase tracking-widest mb-1">Due Date</p>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white">${displayDate}</h3>
                        <p class="text-sm font-bold text-gray-500 dark:text-zinc-400 mt-1">${projects.length} project(s) due</p>
                    </div>
                    <div class="space-y-4">
                `;

                const today = new Date();
                today.setHours(0,0,0,0);
                const diffTime = dObj - today;
                const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

                let color = 'emerald';
                if (diffDays <= 0) color = 'rose';
                else if (diffDays <= 7) color = 'amber';

                projects.forEach(p => {
                    const pct = progressMap[p.progress] || 0;
                    
                    html += `
                        <div class="bg-white dark:bg-zinc-900 border border-${color}-200 dark:border-${color}-900/30 shadow-sm rounded-xl p-4 relative overflow-hidden group hover:border-${color}-300 transition-colors">
                            <div class="absolute top-0 left-0 w-1 h-full bg-${color}-500"></div>
                            
                            <div class="flex justify-between items-start mb-2 pl-2">
                                <div>
                                    <h4 class="text-sm font-bold text-gray-900 dark:text-white">${p.project_name}</h4>
                                    <p class="text-xs font-medium text-gray-500 dark:text-zinc-400">${p.client_name} (${p.quantity} pcs)</p>
                                </div>
                                <span class="text-[10px] font-black text-${color}-600 dark:text-${color}-400 bg-${color}-50 dark:bg-${color}-900/20 px-2 py-1 rounded uppercase tracking-widest">${p.progress}</span>
                            </div>
                            
                            <div class="pl-2 mt-4">
                                <div class="flex justify-between text-[10px] font-bold text-gray-400 dark:text-zinc-500 mb-1 tracking-wider uppercase">
                                    <span>Progress</span>
                                    <span>${pct}%</span>
                                </div>
                                <div class="w-full bg-gray-100 dark:bg-zinc-800 rounded-full h-1.5 overflow-hidden">
                                    <div class="bg-${color}-500 h-1.5 rounded-full" style="width: ${pct}%"></div>
                                </div>
                            </div>
                        </div>
                    `;
                });

                html += `</div>`;
                detailsPane.innerHTML = html;
            }
        </script>

<?php include 'includes/footer.php' ?>