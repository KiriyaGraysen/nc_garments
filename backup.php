<?php 
session_start();
require_once 'config/database.php';

$page_title = "Backup Database | NC Garments";

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
                <button class="w-full bg-pink-600 hover:bg-pink-700 text-white py-3 rounded-xl text-sm font-bold transition-all duration-300 shadow-lg shadow-pink-600/20 hover:shadow-pink-600/40 flex items-center justify-center gap-2 cursor-pointer focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-pink-500 dark:focus:ring-offset-zinc-950">
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
                <input type="file" id="backup-file" class="hidden" accept=".sql">
                <button onclick="document.getElementById('backup-file').click()" class="flex-1 bg-gray-50 hover:bg-gray-100 dark:bg-zinc-800 dark:hover:bg-zinc-700 text-gray-700 dark:text-zinc-300 border border-gray-200 dark:border-zinc-700 py-3 rounded-xl text-sm font-bold transition-all duration-300 shadow-sm flex items-center justify-center gap-2 cursor-pointer">
                    <i class="fa-solid fa-file-code"></i> Select .sql File
                </button>
                <button disabled class="bg-amber-500/50 dark:bg-amber-600/50 text-white px-6 py-3 rounded-xl text-sm font-bold transition-all duration-300 shadow-sm flex items-center justify-center gap-2 cursor-not-allowed opacity-50">
                    <i class="fa-solid fa-upload"></i> Restore
                </button>
            </div>
        </div>

    </div>

    <div class="bg-white dark:bg-zinc-900 rounded-2xl shadow-sm border border-gray-100 dark:border-zinc-800 overflow-hidden transition-colors duration-500">
        
        <div class="px-6 py-5 border-b border-gray-100 dark:border-zinc-800 flex justify-between items-center bg-gray-50/50 dark:bg-zinc-950/30">
            <h3 class="text-sm font-bold text-gray-900 dark:text-white">Recent Backup Logs</h3>
            <span class="text-[10px] font-bold text-gray-400 dark:text-zinc-500 uppercase tracking-wider">Last 30 Days</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full whitespace-nowrap">
                
                <thead class="bg-gray-50 dark:bg-zinc-950/50 border-b border-gray-100 dark:border-zinc-800 transition-colors duration-500">
                    <tr>
                        <th class="px-6 py-4 text-left text-[10px] font-extrabold text-gray-500 dark:text-zinc-500 uppercase tracking-widest">Date Generated</th>
                        <th class="px-6 py-4 text-left text-[10px] font-extrabold text-gray-500 dark:text-zinc-500 uppercase tracking-widest">Filename & Size</th>
                        <th class="px-6 py-4 text-left text-[10px] font-extrabold text-gray-500 dark:text-zinc-500 uppercase tracking-widest">Initiated By</th>
                        <th class="px-6 py-4 text-left text-[10px] font-extrabold text-gray-500 dark:text-zinc-500 uppercase tracking-widest">Status</th>
                    </tr>
                </thead>
                
                <tbody class="divide-y divide-gray-50 dark:divide-zinc-800/50 text-sm transition-colors duration-500">
                    
                    <tr class="hover:bg-gray-50/80 dark:hover:bg-zinc-800/30 transition-colors group">
                        <td class="px-6 py-4">
                            <div class="text-gray-900 dark:text-white font-bold text-xs">April 03, 2026</div>
                            <div class="text-[10px] font-bold text-gray-400 dark:text-zinc-500 mt-1 uppercase tracking-wider">05:30 PM</div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="font-bold text-pink-600 dark:text-pink-500 text-xs flex items-center gap-2">
                                <i class="fa-solid fa-file-zipper text-gray-400"></i> nc_garments_backup_20260403.sql
                            </div>
                            <div class="text-[11px] font-semibold text-gray-500 dark:text-zinc-400 mt-1">
                                2.4 MB
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2">
                                <div class="h-6 w-6 rounded-full bg-pink-600 text-white flex items-center justify-center font-extrabold text-[10px]">
                                    JJ
                                </div>
                                <span class="font-bold text-gray-900 dark:text-white text-xs">Jezel Juanillo</span>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400 text-[10px] font-extrabold px-2.5 py-1 rounded-md uppercase tracking-wider border border-emerald-200 dark:border-emerald-500/20">
                                Successful
                            </span>
                        </td>
                    </tr>

                    <tr class="hover:bg-gray-50/80 dark:hover:bg-zinc-800/30 transition-colors group">
                        <td class="px-6 py-4">
                            <div class="text-gray-900 dark:text-white font-bold text-xs">April 01, 2026</div>
                            <div class="text-[10px] font-bold text-gray-400 dark:text-zinc-500 mt-1 uppercase tracking-wider">12:00 AM</div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="font-bold text-gray-700 dark:text-zinc-300 text-xs flex items-center gap-2">
                                <i class="fa-solid fa-file-zipper text-gray-400"></i> nc_garments_monthly_auto.sql
                            </div>
                            <div class="text-[11px] font-semibold text-gray-500 dark:text-zinc-400 mt-1">
                                2.3 MB
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2">
                                <div class="h-6 w-6 rounded-full bg-gray-800 text-white flex items-center justify-center font-extrabold text-[10px]">
                                    <i class="fa-solid fa-robot"></i>
                                </div>
                                <span class="font-bold text-gray-900 dark:text-white text-xs">System Auto-Task</span>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400 text-[10px] font-extrabold px-2.5 py-1 rounded-md uppercase tracking-wider border border-emerald-200 dark:border-emerald-500/20">
                                Successful
                            </span>
                        </td>
                    </tr>

                </tbody>
            </table>
        </div>
    </div>
</main>


<?php include 'includes/footer.php'; ?>