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
        
        <div class="bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-500/30 px-6 py-3 rounded-2xl flex items-center gap-4 shrink-0 w-full md:w-auto justify-between md:justify-start">
            <div class="h-10 w-10 rounded-full bg-emerald-100 dark:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-lg">
                <i class="fa-solid fa-sack-dollar"></i>
            </div>
            <div class="text-right md:text-left">
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

    <div class="flex flex-col xl:flex-row justify-between items-center mb-6 gap-4">
        
        <div class="flex w-full xl:w-auto gap-3 flex-1 max-w-2xl">
            <div class="relative w-full group">
                <i class="fa-solid fa-search absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400 group-focus-within:text-pink-600 transition-colors duration-500"></i>
                <input type="text" id="search-input" placeholder="Search records, clients, or IDs..." 
                       class="w-full pl-11 pr-4 py-3 border border-gray-200 dark:border-zinc-800 rounded-xl bg-white dark:bg-zinc-900/50 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-zinc-500 focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent transition-colors duration-500 shadow-sm text-sm font-medium">
            </div>
        </div>

        <div class="flex flex-col md:flex-row items-center gap-3 w-full xl:w-auto">
            <form method="GET" action="" class="flex flex-col sm:flex-row items-center gap-3 w-full md:w-auto bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 p-1.5 rounded-xl shadow-sm">
                <input type="hidden" name="view" value="<?= htmlspecialchars($view) ?>">
                <div class="flex items-center gap-2 w-full sm:w-auto px-2">
                    <label class="text-[10px] font-extrabold text-gray-500 uppercase tracking-widest">From</label>
                    <input type="date" name="start_date" value="<?= htmlspecialchars($start_date) ?>" class="bg-transparent border-none text-sm font-bold text-gray-900 dark:text-white outline-none cursor-pointer focus:ring-0">
                </div>
                <div class="hidden sm:block h-6 w-px bg-gray-200 dark:bg-zinc-700"></div>
                <div class="flex items-center gap-2 w-full sm:w-auto px-2">
                    <label class="text-[10px] font-extrabold text-gray-500 uppercase tracking-widest">To</label>
                    <input type="date" name="end_date" value="<?= htmlspecialchars($end_date) ?>" class="bg-transparent border-none text-sm font-bold text-gray-900 dark:text-white outline-none cursor-pointer focus:ring-0">
                </div>
                <button type="submit" class="w-full sm:w-auto bg-gray-100 hover:bg-gray-200 dark:bg-zinc-800 dark:hover:bg-zinc-700 text-gray-700 dark:text-zinc-300 px-4 py-2 rounded-lg text-xs font-bold transition-all focus:outline-none shrink-0">
                    <i class="fa-solid fa-filter mr-1"></i> Filter
                </button>
            </form>

            <button onclick="exportLedger('<?= $view ?>')" class="w-full md:w-auto bg-pink-600 hover:bg-pink-700 text-white px-5 py-3 rounded-xl text-sm font-bold transition-all duration-300 shadow-md shadow-pink-600/20 flex items-center justify-center gap-2 focus:outline-none shrink-0">
                <i class="fa-solid fa-file-csv"></i> Export Data (.csv)
            </button>
        </div>
    </div>

    <div class="bg-white dark:bg-zinc-900 rounded-2xl shadow-sm border border-gray-100 dark:border-zinc-800 overflow-hidden transition-colors duration-500 flex flex-col">
        <div class="overflow-x-auto flex-1">
            <table class="w-full whitespace-nowrap">
                <thead class="bg-gray-50 dark:bg-zinc-950/50 border-b border-gray-100 dark:border-zinc-800 transition-colors duration-500">
                    <tr>
                        <th class="px-6 py-4 text-left text-[10px] font-extrabold text-gray-500 dark:text-zinc-500 uppercase tracking-widest">Date & Time</th>
                        <th class="px-6 py-4 text-left text-[10px] font-extrabold text-gray-500 dark:text-zinc-500 uppercase tracking-widest">Client & Activity</th>
                        <th class="px-6 py-4 text-left text-[10px] font-extrabold text-gray-500 dark:text-zinc-500 uppercase tracking-widest">Payment Info</th>
                        <th class="px-6 py-4 text-right text-[10px] font-extrabold text-gray-500 dark:text-zinc-500 uppercase tracking-widest">Total Amount</th>
                    </tr>
                </thead>
                <tbody id="transaction-tbody" class="divide-y divide-gray-50 dark:divide-zinc-800/50 text-sm transition-colors duration-500">
                    
                    <?php if (empty($transactions)): ?>
                        <tr id="php-empty-state"><td colspan="4" class="px-6 py-8 text-center text-gray-500 font-medium italic">No transactions found for this date range.</td></tr>
                    <?php endif; ?>

                    <?php foreach ($transactions as $txn): 
                        $date = new DateTime($txn['txn_date']);
                        $method_color = strtolower($txn['payment_method']) === 'cash' ? 'text-emerald-600 dark:text-emerald-400' : 'text-blue-600 dark:text-blue-400';
                        $prefix = ($view === 'projects') ? '#PRJ-' : '#SALE-';
                    ?>
                    <tr class="transaction-row hover:bg-gray-50/80 dark:hover:bg-zinc-800/30 transition-colors group">
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
        
        <div id="pagination-container" class="w-full bg-gray-50/50 dark:bg-zinc-950/30 rounded-b-2xl transition-colors duration-500"></div>
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
    // GLOBAL UI OVERRIDES
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
                icon.className = "fa-solid fa-file-export";
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

    // Overwrite the native functions
    window.alert = customAlert;

    // --- Search & Pagination Logic ---
    const searchInput = document.getElementById('search-input');
    const tbody = document.getElementById('transaction-tbody');
    
    if (tbody && searchInput) {
        const allRows = Array.from(tbody.querySelectorAll('tr.transaction-row'));
        const paginationContainer = document.getElementById('pagination-container');
        const colspanCount = 4;
        
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
            if (totalItems === 0 && allRows.length > 0) {
                if (!existingEmptyRow) {
                    tbody.insertAdjacentHTML('beforeend', `<tr id="js-empty-state"><td colspan="${colspanCount}" class="px-6 py-8 text-center text-gray-500 font-medium italic">No transactions found matching your search.</td></tr>`);
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
                        <button onclick="changePage(${currentPage - 1})" class="px-3 py-1.5 text-xs font-bold rounded-lg transition-colors ${currentPage === 1 ? 'text-gray-400 dark:text-zinc-600 cursor-not-allowed' : 'text-gray-700 dark:text-zinc-300 hover:bg-gray-200 dark:hover:bg-zinc-800'}" ${currentPage === 1 ? 'disabled' : ''}>Prev</button>
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
                        <button onclick="changePage(${currentPage + 1})" class="px-3 py-1.5 text-xs font-bold rounded-lg transition-colors ${currentPage === totalPages ? 'text-gray-400 dark:text-zinc-600 cursor-not-allowed' : 'text-gray-700 dark:text-zinc-300 hover:bg-gray-200 dark:hover:bg-zinc-800'}" ${currentPage === totalPages ? 'disabled' : ''}>Next</button>
                    </div>
                </div>
            `;
            paginationContainer.innerHTML = html;
        }

        function makePageBtn(i) {
            const activeClass = i === currentPage 
                ? 'bg-pink-600 text-white shadow-md shadow-pink-600/20' 
                : 'text-gray-700 dark:text-zinc-300 hover:bg-gray-200 dark:hover:bg-zinc-800';
            return `<button onclick="changePage(${i})" class="px-3 py-1.5 text-xs font-bold rounded-lg transition-colors ${activeClass}">${i}</button>`;
        }

        window.changePage = function(page) {
            currentPage = page;
            updateTable();
        }

        searchInput.addEventListener('input', () => {
            currentPage = 1; 
            updateTable();
        });

        // Initialize table
        updateTable();
    }

    async function exportLedger(viewType) {
        const isConfirmed = await customConfirm("Are you sure you want to export this data to a CSV file?", "Export Ledger", "Yes, Export", "info");
        if (isConfirmed) {
            const startDate = document.querySelector('input[name="start_date"]').value;
            const endDate = document.querySelector('input[name="end_date"]').value;
            window.location.href = `actions/export_ledger.php?type=${viewType}&start=${startDate}&end=${endDate}`;
        }
    }
</script>

<?php include 'includes/footer.php'; ?>