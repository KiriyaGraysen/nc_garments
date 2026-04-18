<?php
$page_title = "Backup Database | NC Garments";
require_once('config/database.php');

// Fetch the logs using a LEFT JOIN to get the admin's name
$log_stmt = $conn->prepare("
    SELECT 
        b.created_at, 
        b.filename, 
        b.file_size, 
        b.action_type, 
        b.status,
        COALESCE(a.full_name, 'Unknown Admin') AS admin_name 
    FROM backup_log b
    LEFT JOIN admin a ON b.admin_id = a.admin_id
    ORDER BY b.created_at DESC 
    LIMIT 30
");
$log_stmt->execute();
$logs = $log_stmt->get_result();

include 'includes/header.php'; 
?>

<main class="flex-1 p-4 md:p-8 overflow-y-auto transition-colors duration-500 font-sans relative">
    
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white transition-colors duration-500">System Backup & Recovery</h2>
            <p class="text-gray-500 dark:text-zinc-400 text-sm mt-1 transition-colors duration-500">Safeguard your enterprise data by generating manual backups or restoring from a previous save.</p>
        </div>
        <div class="flex items-center gap-2 bg-rose-50 dark:bg-rose-500/10 border border-rose-200 dark:border-rose-500/20 px-4 py-2.5 rounded-xl">
            <i class="fa-solid fa-lock text-rose-600 dark:text-rose-400 text-sm"></i>
            <span class="text-xs font-bold text-rose-600 dark:text-rose-400 uppercase tracking-wider">Superadmin Access Only</span>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        
        <div class="bg-white dark:bg-zinc-900 rounded-2xl shadow-sm border border-gray-100 dark:border-zinc-800 p-6 transition-colors duration-500 flex flex-col">
            <div class="flex items-start gap-4 mb-4">
                <div class="h-12 w-12 rounded-xl bg-pink-50 dark:bg-pink-900/20 text-pink-600 dark:text-pink-500 flex items-center justify-center text-xl shrink-0">
                    <i class="fa-solid fa-cloud-arrow-down"></i>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Export Database</h3>
                    <p class="text-xs font-medium text-gray-500 dark:text-zinc-400 mt-1 leading-relaxed">
                        Download a complete, secure <span class="font-bold text-gray-700 dark:text-zinc-300">.sql</span> snapshot of all current system data, including users, inventory, and financial records.
                    </p>
                </div>
            </div>
            <div class="mt-auto pt-4 border-t border-gray-50 dark:border-zinc-800/50">
                <button id="btn-export" onclick="exportDatabase()" class="w-full bg-pink-600 hover:bg-pink-700 text-white py-3 rounded-xl text-sm font-bold transition-all duration-300 shadow-lg shadow-pink-600/20 hover:shadow-pink-600/40 flex items-center justify-center gap-2 cursor-pointer focus:outline-none disabled:opacity-75 disabled:cursor-not-allowed">
                    <i class="fa-solid fa-download"></i> Generate & Download Backup
                </button>
            </div>
        </div>

        <div class="bg-white dark:bg-zinc-900 rounded-2xl shadow-sm border border-gray-100 dark:border-zinc-800 p-6 transition-colors duration-500 flex flex-col">
            <div class="flex items-start gap-4 mb-4">
                <div class="h-12 w-12 rounded-xl bg-amber-50 dark:bg-amber-900/20 text-amber-600 dark:text-amber-500 flex items-center justify-center text-xl shrink-0">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Restore System</h3>
                    <p class="text-xs font-medium text-gray-500 dark:text-zinc-400 mt-1 leading-relaxed">
                        Upload a previously saved <span class="font-bold text-gray-700 dark:text-zinc-300">.sql</span> backup file to recover lost data. <br>
                        <span class="text-rose-500 font-bold"><i class="fa-solid fa-triangle-exclamation text-[10px]"></i> Warning: This will overwrite current data.</span>
                    </p>
                </div>
            </div>
            <div class="mt-auto pt-4 border-t border-gray-50 dark:border-zinc-800/50 flex gap-3">
                <input type="file" id="backup-file" class="hidden" accept=".sql" onchange="handleFileSelect(this)">
                
                <button onclick="document.getElementById('backup-file').click()" id="btn-select-file" class="flex-1 bg-gray-50 hover:bg-gray-100 dark:bg-zinc-800 dark:hover:bg-zinc-700 text-gray-700 dark:text-zinc-300 border border-gray-200 dark:border-zinc-700 py-3 rounded-xl text-sm font-bold transition-all duration-300 shadow-sm flex items-center justify-center gap-2 cursor-pointer truncate px-2 focus:outline-none">
                    <i class="fa-solid fa-file-code"></i> <span id="file-name-display">Select .sql File</span>
                </button>
                
                <button id="btn-restore" disabled onclick="restoreDatabase()" class="bg-amber-500/50 dark:bg-amber-600/50 text-white px-6 py-3 rounded-xl text-sm font-bold transition-all duration-300 shadow-sm flex items-center justify-center gap-2 cursor-not-allowed opacity-50 focus:outline-none">
                    <i class="fa-solid fa-upload"></i> Restore
                </button>
            </div>
        </div>

    </div>

    <div class="bg-white dark:bg-zinc-900 rounded-2xl shadow-sm border border-gray-100 dark:border-zinc-800 overflow-hidden transition-colors duration-500">
        
        <div class="px-6 py-5 border-b border-gray-100 dark:border-zinc-800 flex justify-between items-center bg-gray-50/50 dark:bg-zinc-950/30">
            <h3 class="text-sm font-bold text-gray-900 dark:text-white">Recent Backup Logs</h3>
            <span class="text-[10px] font-bold text-gray-400 dark:text-zinc-500 uppercase tracking-wider">Last 30 Logs</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full whitespace-nowrap">
                <thead class="bg-gray-50 dark:bg-zinc-950/50 border-b border-gray-100 dark:border-zinc-800 transition-colors duration-500">
                    <tr>
                        <th class="px-6 py-4 text-left text-[10px] font-extrabold text-gray-500 dark:text-zinc-500 uppercase tracking-widest">Date Generated</th>
                        <th class="px-6 py-4 text-left text-[10px] font-extrabold text-gray-500 dark:text-zinc-500 uppercase tracking-widest">Filename & Size</th>
                        <th class="px-6 py-4 text-left text-[10px] font-extrabold text-gray-500 dark:text-zinc-500 uppercase tracking-widest">Action / By</th>
                        <th class="px-6 py-4 text-left text-[10px] font-extrabold text-gray-500 dark:text-zinc-500 uppercase tracking-widest">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 dark:divide-zinc-800/50 text-sm transition-colors duration-500">
                    
                    <?php if ($logs->num_rows === 0): ?>
                        <tr><td colspan="4" class="px-6 py-8 text-center text-gray-500 font-medium">No backup logs found.</td></tr>
                    <?php endif; ?>

                    <?php while ($log = $logs->fetch_assoc()): 
                        $date = new DateTime($log['created_at']);
                        $status_color = ($log['status'] === 'Successful') ? 'bg-emerald-50 text-emerald-600 border-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-400 dark:border-emerald-500/20' : 'bg-rose-50 text-rose-600 border-rose-200 dark:bg-rose-500/10 dark:text-rose-400 dark:border-rose-500/20';
                        $action_icon = ($log['action_type'] === 'Export') ? '<i class="fa-solid fa-download text-pink-500 mr-1.5"></i>' : '<i class="fa-solid fa-upload text-amber-500 mr-1.5"></i>';
                    ?>
                    <tr class="hover:bg-gray-50/80 dark:hover:bg-zinc-800/30 transition-colors group">
                        <td class="px-6 py-4">
                            <div class="text-gray-900 dark:text-white font-bold text-xs"><?= $date->format('F d, Y') ?></div>
                            <div class="text-[10px] font-bold text-gray-400 dark:text-zinc-500 mt-1 uppercase tracking-wider"><?= $date->format('h:i A') ?></div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="font-bold text-gray-700 dark:text-zinc-300 text-xs flex items-center gap-2">
                                <i class="fa-solid fa-file-zipper text-gray-400"></i> <?= htmlspecialchars($log['filename']) ?>
                            </div>
                            <div class="text-[11px] font-semibold text-gray-500 dark:text-zinc-400 mt-1">
                                <?= htmlspecialchars($log['file_size']) ?>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2">
                                <span class="font-bold text-gray-900 dark:text-white text-xs">
                                    <?= $action_icon ?> <?= htmlspecialchars($log['action_type']) ?> by <?= htmlspecialchars($log['admin_name']) ?>
                                </span>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="<?= $status_color ?> text-[10px] font-extrabold px-2.5 py-1 rounded-md uppercase tracking-wider border">
                                <?= htmlspecialchars($log['status']) ?>
                            </span>
                        </td>
                    </tr>
                    <?php endwhile; ?>

                </tbody>
            </table>
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
                icon.className = "fa-solid fa-triangle-exclamation"; // Optional: Use trash if deleting, but triangle is better for critical warnings
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

    // Overwrite native alerts
    window.alert = customAlert;


    // ==========================================
    // BACKUP & RESTORE LOGIC
    // ==========================================
    
    async function exportDatabase() {
        const btn = document.getElementById('btn-export');
        const originalHtml = btn.innerHTML;
        
        // Show loading state
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Generating Backup...';
        btn.disabled = true;

        try {
            // Fetch the SQL file from the backend
            const response = await fetch('actions/export_db.php');
            
            if (!response.ok) throw new Error("Server failed to generate backup");

            // Extract the filename the PHP script generated from the headers
            const contentDisposition = response.headers.get('Content-Disposition');
            let filename = 'nc_garments_backup.sql'; // Fallback name
            if (contentDisposition && contentDisposition.includes('filename=')) {
                filename = contentDisposition.split('filename=')[1].replace(/["']/g, '');
            }

            // Convert the response to a downloadable Blob
            const blob = await response.blob();
            const url = window.URL.createObjectURL(blob);
            
            // Create an invisible link, click it to trigger download, then destroy it
            const a = document.createElement('a');
            a.style.display = 'none';
            a.href = url;
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            
            customAlert("Backup generated and downloaded successfully.", "Export Successful", "success");
            setTimeout(() => window.location.reload(), 1500);

        } catch (error) {
            customAlert("Export failed: " + error.message, "Export Error", "error");
            btn.innerHTML = originalHtml;
            btn.disabled = false;
        }
    }

    function handleFileSelect(input) {
        const btnRestore = document.getElementById('btn-restore');
        const fileNameDisplay = document.getElementById('file-name-display');

        if (input.files && input.files.length > 0) {
            const fileName = input.files[0].name;
            fileNameDisplay.textContent = fileName;
            
            // Enable the restore button and make it vibrant Amber
            btnRestore.disabled = false;
            btnRestore.classList.remove('bg-amber-500/50', 'dark:bg-amber-600/50', 'cursor-not-allowed', 'opacity-50');
            btnRestore.classList.add('bg-amber-500', 'hover:bg-amber-600', 'cursor-pointer');
        } else {
            fileNameDisplay.textContent = "Select .sql File";
            btnRestore.disabled = true;
            btnRestore.classList.add('bg-amber-500/50', 'dark:bg-amber-600/50', 'cursor-not-allowed', 'opacity-50');
            btnRestore.classList.remove('bg-amber-500', 'hover:bg-amber-600', 'cursor-pointer');
        }
    }

    async function restoreDatabase() {
        const fileInput = document.getElementById('backup-file');
        
        if (!fileInput.files || fileInput.files.length === 0) {
            customAlert("Please select a file first.", "Missing File", "warning");
            return;
        }

        const isConfirmed = await customConfirm(
            "CRITICAL WARNING: Restoring a database will overwrite ALL current data in the system. Any work done since this backup was generated will be permanently lost.\n\nAre you absolutely sure you want to proceed?", 
            "System Restore", 
            "Yes, Overwrite Data", 
            "danger"
        );
        
        if (!isConfirmed) return;

        const formData = new FormData();
        formData.append("backup_file", fileInput.files[0]);

        const btnRestore = document.getElementById('btn-restore');
        const originalText = btnRestore.innerHTML;
        btnRestore.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Restoring...';
        btnRestore.disabled = true;

        try {
            const response = await fetch('actions/restore_db.php', {
                method: 'POST',
                body: formData
            });
            
            const rawText = await response.text();
            
            try {
                const result = JSON.parse(rawText);
                if (result.status === 'success') {
                    customAlert("Database restored successfully!", "Restore Complete", "success");
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    customAlert("Error: " + result.message, "Restore Failed", "error");
                    btnRestore.innerHTML = originalText;
                    btnRestore.disabled = false;
                }
            } catch (jsonError) {
                console.error("Raw Response:", rawText);
                customAlert("Server error during restore. Check console for details.", "Server Error", "error");
                btnRestore.innerHTML = originalText;
                btnRestore.disabled = false;
            }

        } catch (error) {
            customAlert("Network Error: " + error.message, "Network Error", "error");
            btnRestore.innerHTML = originalText;
            btnRestore.disabled = false;
        }
    }
</script>

<?php include 'includes/footer.php'; ?>