<?php
require_once('config/database.php');

// SECURITY KICK-OUT
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    header("Location: index.php");
    exit();
}

$page_title = "Financial Reports | NC Garments";

// 1. Handle View & Date Filtering Logic
$view = $_GET['view'] ?? 'projects'; // 'projects' or 'retail'
$default_start = date('Y-m-01');
$default_end = date('Y-m-d');

$start_date = $_GET['start_date'] ?? $default_start;
$end_date = $_GET['end_date'] ?? $default_end;

// Append time to the end date so it includes the entirety of the selected day
$end_date_inclusive = $end_date . ' 23:59:59';

$transactions = [];
$total_period_revenue = 0;

// 2. Fetch the Data based on the active tab
if ($view === 'projects') {
    // FETCH PROJECT PAYMENTS
    $stmt = $conn->prepare("
        SELECT 
            p.payment_id,
            p.payment_date as txn_date,
            p.reference_number,
            p.payment_method,
            p.amount_paid as total_amount,
            prj.project_id as ref_id,
            prj.project_name as item_name,
            COALESCE(c.full_name, 'Internal / Walk-in') as client_name,
            a.full_name as processed_by
        FROM payment p
        JOIN project prj ON p.project_id = prj.project_id
        LEFT JOIN customer c ON prj.customer_id = c.customer_id
        LEFT JOIN admin a ON p.processed_by_admin = a.admin_id
        WHERE p.payment_date BETWEEN ? AND ?
        ORDER BY p.payment_date DESC
    ");
} else {
    // FETCH RETAIL (POS) SALES
    $stmt = $conn->prepare("
        SELECT 
            rs.sale_id as payment_id,
            rs.sale_date as txn_date,
            rs.reference_number,
            rs.payment_method,
            rs.total_amount,
            rs.sale_id as ref_id,
            'POS Retail Sale' as item_name,
            COALESCE(c.full_name, 'Walk-in Customer') as client_name,
            a.full_name as processed_by
        FROM retail_sale rs
        LEFT JOIN customer c ON rs.customer_id = c.customer_id
        LEFT JOIN admin a ON rs.processed_by_admin = a.admin_id
        WHERE rs.sale_date BETWEEN ? AND ?
        ORDER BY rs.sale_date DESC
    ");
}

$stmt->bind_param("ss", $start_date, $end_date_inclusive);
$stmt->execute();
$ledger_result = $stmt->get_result();

while ($row = $ledger_result->fetch_assoc()) {
    $transactions[] = $row;
    $total_period_revenue += $row['total_amount'];
}

include 'includes/header.php'; 
?>

<main class="flex-1 p-4 md:p-8 overflow-y-auto transition-colors duration-500 font-sans relative">
    
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white transition-colors duration-500">Financial Ledger</h2>
            <p class="text-gray-500 dark:text-zinc-400 text-sm mt-1 transition-colors duration-500">Track and export all incoming payments and historical financial records.</p>
        </div>
        
        <div class="bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-500/30 px-6 py-3 rounded-2xl flex items-center gap-4">
            <div class="h-10 w-10 rounded-full bg-emerald-100 dark:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-lg">
                <i class="fa-solid fa-sack-dollar"></i>
            </div>
            <div>
                <p class="text-[10px] font-extrabold text-emerald-600 dark:text-emerald-400 uppercase tracking-widest">Period Revenue</p>
                <h3 class="text-xl font-black text-gray-900 dark:text-white leading-tight">₱ <?= number_format($total_period_revenue, 2) ?></h3>
            </div>
        </div>
    </div>

    <div class="flex gap-6 border-b border-gray-200 dark:border-zinc-800 mb-6">
        <a href="?view=projects&start_date=<?= $start_date ?>&end_date=<?= $end_date ?>" class="pb-3 text-sm font-bold transition-colors <?= $view === 'projects' ? 'border-b-2 border-pink-600 text-pink-600 dark:text-pink-500' : 'text-gray-500 hover:text-gray-700 dark:text-zinc-400' ?>">
            <i class="fa-solid fa-scissors mr-1.5"></i> Custom Orders Ledger
        </a>
        <a href="?view=retail&start_date=<?= $start_date ?>&end_date=<?= $end_date ?>" class="pb-3 text-sm font-bold transition-colors <?= $view === 'retail' ? 'border-b-2 border-pink-600 text-pink-600 dark:text-pink-500' : 'text-gray-500 hover:text-gray-700 dark:text-zinc-400' ?>">
            <i class="fa-solid fa-store mr-1.5"></i> Retail Sales (POS)
        </a>
    </div>

    <div class="bg-white dark:bg-zinc-900 rounded-2xl shadow-sm border border-gray-100 dark:border-zinc-800 p-4 mb-6 transition-colors duration-500 flex flex-col md:flex-row justify-between items-center gap-4">
        
        <form method="GET" action="reports.php" class="flex flex-col md:flex-row items-center gap-3 w-full md:w-auto">
            <input type="hidden" name="view" value="<?= htmlspecialchars($view) ?>">
            <div class="flex items-center gap-2 w-full md:w-auto">
                <label class="text-xs font-bold text-gray-500 uppercase tracking-wide">From:</label>
                <input type="date" name="start_date" value="<?= htmlspecialchars($start_date) ?>" class="px-3 py-2 bg-gray-50 dark:bg-zinc-950 border border-gray-200 dark:border-zinc-800 rounded-lg text-sm text-gray-900 dark:text-white outline-none focus:border-pink-500 transition-all">
            </div>
            <div class="flex items-center gap-2 w-full md:w-auto">
                <label class="text-xs font-bold text-gray-500 uppercase tracking-wide">To:</label>
                <input type="date" name="end_date" value="<?= htmlspecialchars($end_date) ?>" class="px-3 py-2 bg-gray-50 dark:bg-zinc-950 border border-gray-200 dark:border-zinc-800 rounded-lg text-sm text-gray-900 dark:text-white outline-none focus:border-pink-500 transition-all">
            </div>
            <button type="submit" class="w-full md:w-auto bg-gray-100 hover:bg-gray-200 dark:bg-zinc-800 dark:hover:bg-zinc-700 text-gray-700 dark:text-zinc-300 px-4 py-2 rounded-lg text-sm font-bold transition-all focus:outline-none border border-gray-200 dark:border-zinc-700">
                <i class="fa-solid fa-filter mr-1"></i> Filter Dates
            </button>
        </form>

        <button onclick="downloadCSV('<?= $view ?>')" class="w-full md:w-auto bg-pink-600 hover:bg-pink-700 text-white px-5 py-2.5 rounded-xl text-sm font-bold transition-all duration-300 shadow-md shadow-pink-600/20 flex items-center justify-center gap-2 focus:outline-none">
            <i class="fa-solid fa-file-csv"></i> Export to Excel (.csv)
        </button>

    </div>

    <div class="bg-white dark:bg-zinc-900 rounded-2xl shadow-sm border border-gray-100 dark:border-zinc-800 overflow-hidden transition-colors duration-500">
        <div class="overflow-x-auto">
            <table class="w-full whitespace-nowrap">
                <thead class="bg-gray-50 dark:bg-zinc-950/50 border-b border-gray-100 dark:border-zinc-800 transition-colors duration-500">
                    <tr>
                        <th class="px-6 py-4 text-left text-[10px] font-extrabold text-gray-500 dark:text-zinc-500 uppercase tracking-widest">Date & Time</th>
                        <th class="px-6 py-4 text-left text-[10px] font-extrabold text-gray-500 dark:text-zinc-500 uppercase tracking-widest">Client & Activity</th>
                        <th class="px-6 py-4 text-left text-[10px] font-extrabold text-gray-500 dark:text-zinc-500 uppercase tracking-widest">Payment Info</th>
                        <th class="px-6 py-4 text-right text-[10px] font-extrabold text-gray-500 dark:text-zinc-500 uppercase tracking-widest">Total Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 dark:divide-zinc-800/50 text-sm transition-colors duration-500">
                    
                    <?php if (empty($transactions)): ?>
                        <tr><td colspan="4" class="px-6 py-8 text-center text-gray-500 font-medium italic">No transactions found for this date range.</td></tr>
                    <?php endif; ?>

                    <?php foreach ($transactions as $txn): 
                        $date = new DateTime($txn['txn_date']);
                        $method_color = strtolower($txn['payment_method']) === 'cash' ? 'text-emerald-600 dark:text-emerald-400' : 'text-blue-600 dark:text-blue-400';
                        $prefix = ($view === 'projects') ? '#PRJ-' : '#SALE-';
                    ?>
                    <tr class="hover:bg-gray-50/80 dark:hover:bg-zinc-800/30 transition-colors group">
                        <td class="px-6 py-4">
                            <div class="text-gray-900 dark:text-white font-bold text-xs"><?= $date->format('M d, Y') ?></div>
                            <div class="text-[10px] font-bold text-gray-400 dark:text-zinc-500 mt-1 tracking-wider"><?= $date->format('h:i A') ?></div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="font-bold text-gray-900 dark:text-white text-sm group-hover:text-pink-600 transition-colors truncate max-w-[250px]">
                                <?= htmlspecialchars($txn['client_name']) ?>
                            </div>
                            <div class="text-xs font-semibold text-gray-500 dark:text-zinc-400 mt-1 truncate max-w-[250px]">
                                <span class="text-pink-600/70"><?= $prefix . str_pad($txn['ref_id'], 4, '0', STR_PAD_LEFT) ?></span> • <?= htmlspecialchars($txn['item_name']) ?>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="font-bold <?= $method_color ?> text-xs flex items-center gap-1.5 uppercase tracking-wide">
                                <i class="fa-solid <?= strtolower($txn['payment_method']) === 'cash' ? 'fa-money-bill-wave' : 'fa-building-columns' ?>"></i>
                                <?= htmlspecialchars($txn['payment_method']) ?>
                            </div>
                            <div class="text-[10px] font-bold text-gray-400 dark:text-zinc-500 mt-1 uppercase tracking-wider">
                                Ref: <?= htmlspecialchars($txn['reference_number'] ?: 'N/A') ?>
                            </div>
                            <div class="text-[9px] font-bold text-gray-400 dark:text-zinc-600 mt-1">
                                By: <?= htmlspecialchars($txn['processed_by']) ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <span class="text-base font-extrabold text-emerald-600 dark:text-emerald-500">
                                ₱ <?= number_format($txn['total_amount'], 2) ?>
                            </span>
                            
                            <?php if ($view === 'retail'): ?>
                            <div class="mt-1">
                                <button class="text-[10px] font-bold text-pink-600 hover:text-pink-700 transition-colors uppercase tracking-widest focus:outline-none">
                                    View Receipt
                                </button>
                            </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>

                </tbody>
            </table>
        </div>
    </div>
</main>

<script>
    // Grabs the current dates from the inputs and passes them to the export script
    function downloadCSV(viewType) {
        const startDate = document.querySelector('input[name="start_date"]').value;
        const endDate = document.querySelector('input[name="end_date"]').value;
        
        // You will need to update your export_ledger.php file to handle the `type` parameter!
        window.location.href = `actions/export_ledger.php?type=${viewType}&start=${startDate}&end=${endDate}`;
    }
</script>

<?php include 'includes/footer.php'; ?>