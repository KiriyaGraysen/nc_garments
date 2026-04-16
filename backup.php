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

<main class="flex-1 p-4 md:p-8 overflow-y-auto transition-colors duration-500 font-sans">
    
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
                
                <button onclick="document.getElementById('backup-file').click()" id="btn-select-file" class="flex-1 bg-gray-50 hover:bg-gray-100 dark:bg-zinc-800 dark:hover:bg-zinc-700 text-gray-700 dark:text-zinc-300 border border-gray-200 dark:border-zinc-700 py-3 rounded-xl text-sm font-bold transition-all duration-300 shadow-sm flex items-center justify-center gap-2 cursor-pointer truncate px-2">
                    <i class="fa-solid fa-file-code"></i> <span id="file-name-display">Select .sql File</span>
                </button>
                
                <button id="btn-restore" disabled onclick="restoreDatabase()" class="bg-amber-500/50 dark:bg-amber-600/50 text-white px-6 py-3 rounded-xl text-sm font-bold transition-all duration-300 shadow-sm flex items-center justify-center gap-2 cursor-not-allowed opacity-50">
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
                        $status_color = ($log['status'] === 'Successful') ? 'bg-emerald-50 text-emerald-600 border-emerald-200' : 'bg-rose-50 text-rose-600 border-rose-200';
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
</main>

<script>
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
            
            // Refresh the page to update the logs table!
            window.location.reload();

        } catch (error) {
            alert("Export failed: " + error.message);
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
            alert("Please select a file first.");
            return;
        }

        const confirmRestore = confirm("CRITICAL WARNING: Restoring a database will overwrite ALL current data in the system. Any work done since this backup was generated will be permanently lost.\n\nAre you absolutely sure you want to proceed?");
        
        if (!confirmRestore) return;

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
                    alert("Database restored successfully!");
                    window.location.reload();
                } else {
                    alert("Error: " + result.message);
                    btnRestore.innerHTML = originalText;
                    btnRestore.disabled = false;
                }
            } catch (jsonError) {
                console.error("Raw Response:", rawText);
                alert("Server error during restore. Check console for details.");
                btnRestore.innerHTML = originalText;
                btnRestore.disabled = false;
            }

        } catch (error) {
            alert("Network Error: " + error.message);
            btnRestore.innerHTML = originalText;
            btnRestore.disabled = false;
        }
    }
</script>

<?php include 'includes/footer.php'; ?>