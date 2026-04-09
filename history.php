<?php 
session_start();
require_once 'config/database.php';

$page_title = "History & Changes | NC Garments";

include 'includes/header.php'; 
?>

<main class="flex-1 p-4 md:p-8 overflow-y-auto transition-colors duration-500 font-sans">
    
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white transition-colors duration-500">History & Changes</h2>
            <p class="text-gray-500 dark:text-zinc-400 text-sm mt-1 transition-colors duration-500">System audit log tracking all user activities, inventory adjustments, and security events.</p>
        </div>
        <button class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 hover:bg-gray-50 dark:hover:bg-zinc-800 text-gray-700 dark:text-zinc-300 px-5 py-2.5 rounded-xl text-sm font-bold transition-all duration-300 shadow-sm flex items-center gap-2 cursor-pointer focus:outline-none focus:ring-2 focus:ring-pink-500">
            <i class="fa-solid fa-file-export text-pink-600 dark:text-pink-500"></i> Export Activity Log
        </button>
    </div>

    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
        
        <div class="relative w-full md:w-96 group">
            <i class="fa-solid fa-search absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400 group-focus-within:text-pink-600 transition-colors duration-500"></i>
            <input type="text" placeholder="Search logs by user, action, or ID..." 
                   class="w-full pl-11 pr-4 py-3 border border-gray-200 dark:border-zinc-800 rounded-xl bg-white dark:bg-zinc-900/50 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-zinc-500 focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent transition-colors duration-500 shadow-sm">
        </div>
        
        <div class="flex bg-gray-100 dark:bg-zinc-900/80 p-1 rounded-lg w-full md:w-auto overflow-x-auto transition-colors duration-500 border border-gray-200 dark:border-zinc-800">
            <button class="whitespace-nowrap px-5 py-2 bg-white dark:bg-zinc-800 text-pink-600 dark:text-pink-500 text-sm font-bold rounded-md shadow-sm transition-colors duration-500 cursor-pointer flex items-center gap-2">
                <i class="fa-solid fa-list-ul text-xs"></i> All Activity
            </button>
            <button class="whitespace-nowrap px-5 py-2 text-gray-500 dark:text-zinc-400 hover:text-gray-900 hover:dark:text-white text-sm font-semibold rounded-md transition-colors duration-500 cursor-pointer flex items-center gap-2">
                <i class="fa-solid fa-boxes-stacked text-xs"></i> Inventory
            </button>
            <button class="whitespace-nowrap px-5 py-2 text-gray-500 dark:text-zinc-400 hover:text-gray-900 hover:dark:text-white text-sm font-semibold rounded-md transition-colors duration-500 cursor-pointer flex items-center gap-2">
                <i class="fa-solid fa-shield-halved text-xs"></i> Security
            </button>
        </div>
    </div>

    <div class="bg-white dark:bg-zinc-900 rounded-2xl shadow-sm border border-gray-100 dark:border-zinc-800 overflow-hidden transition-colors duration-500">
        <div class="overflow-x-auto">
            <table class="w-full whitespace-nowrap">
                
                <thead class="bg-gray-50 dark:bg-zinc-950/50 border-b border-gray-100 dark:border-zinc-800 transition-colors duration-500">
                    <tr>
                        <th class="px-6 py-4 text-left text-[10px] font-extrabold text-gray-500 dark:text-zinc-500 uppercase tracking-widest">Date & Time</th>
                        <th class="px-6 py-4 text-left text-[10px] font-extrabold text-gray-500 dark:text-zinc-500 uppercase tracking-widest">User / Actor</th>
                        <th class="px-6 py-4 text-left text-[10px] font-extrabold text-gray-500 dark:text-zinc-500 uppercase tracking-widest">Action Performed</th>
                        <th class="px-6 py-4 text-left text-[10px] font-extrabold text-gray-500 dark:text-zinc-500 uppercase tracking-widest">Details & Changes</th>
                        <th class="px-6 py-4 text-right text-[10px] font-extrabold text-gray-500 dark:text-zinc-500 uppercase tracking-widest">IP Address</th>
                    </tr>
                </thead>
                
                <tbody class="divide-y divide-gray-50 dark:divide-zinc-800/50 text-sm transition-colors duration-500">
                    
                    <tr class="hover:bg-gray-50/80 dark:hover:bg-zinc-800/30 transition-colors group">
                        <td class="px-6 py-4">
                            <div class="text-gray-900 dark:text-white font-bold text-xs">Today, 2:15 PM</div>
                            <div class="text-[10px] font-bold text-gray-400 dark:text-zinc-500 mt-1 uppercase tracking-wider">April 09, 2026</div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="h-8 w-8 rounded-full bg-gray-100 dark:bg-zinc-800 text-gray-600 dark:text-zinc-400 flex items-center justify-center font-extrabold text-xs border border-gray-200 dark:border-zinc-700">
                                    MC
                                </div>
                                <div>
                                    <div class="font-bold text-gray-900 dark:text-white">Mark Cruz</div>
                                    <div class="text-[10px] font-bold tracking-wider text-gray-400 dark:text-zinc-500 mt-0.5 uppercase">Staff</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-400 text-[10px] font-extrabold px-2.5 py-1 rounded-md uppercase tracking-wider border border-amber-200 dark:border-amber-500/20 flex items-center w-max gap-1.5">
                                <i class="fa-solid fa-boxes-stacked text-[10px]"></i> Updated Inventory
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="font-bold text-gray-900 dark:text-white text-xs">Signature Pink Thread (RAW-THR-042)</div>
                            <div class="text-[11px] font-semibold text-gray-500 dark:text-zinc-400 mt-1">
                                Stock decreased: <span class="line-through text-gray-400">5 Cones</span> <i class="fa-solid fa-arrow-right text-[10px] mx-1 text-gray-300"></i> <span class="text-rose-500 font-extrabold">3 Cones</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="text-[11px] font-mono text-gray-500 dark:text-zinc-400">192.168.1.45</div>
                        </td>
                    </tr>

                    <tr class="hover:bg-gray-50/80 dark:hover:bg-zinc-800/30 transition-colors group">
                        <td class="px-6 py-4">
                            <div class="text-gray-900 dark:text-white font-bold text-xs">Today, 10:30 AM</div>
                            <div class="text-[10px] font-bold text-gray-400 dark:text-zinc-500 mt-1 uppercase tracking-wider">April 09, 2026</div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="h-8 w-8 rounded-full bg-pink-600 text-white flex items-center justify-center font-extrabold text-xs shadow-md shadow-pink-600/20">
                                    JJ
                                </div>
                                <div>
                                    <div class="font-bold text-pink-600 dark:text-pink-500">Jezel Juanillo</div>
                                    <div class="text-[10px] font-bold tracking-wider text-gray-400 dark:text-zinc-500 mt-0.5 uppercase">Superadmin</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400 text-[10px] font-extrabold px-2.5 py-1 rounded-md uppercase tracking-wider border border-emerald-200 dark:border-emerald-500/20 flex items-center w-max gap-1.5">
                                <i class="fa-solid fa-folder-plus text-[10px]"></i> Created Project
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="font-bold text-gray-900 dark:text-white text-xs">DHS PE Uniforms</div>
                            <div class="text-[11px] font-semibold text-gray-500 dark:text-zinc-400 mt-1">
                                Generated Tracking ID: <span class="font-bold text-gray-700 dark:text-zinc-300">#PRJ-2026-042</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="text-[11px] font-mono text-gray-500 dark:text-zinc-400">192.168.1.12</div>
                        </td>
                    </tr>

                    <tr class="hover:bg-gray-50/80 dark:hover:bg-zinc-800/30 transition-colors group bg-rose-50/30 dark:bg-rose-900/5">
                        <td class="px-6 py-4">
                            <div class="text-gray-900 dark:text-white font-bold text-xs">Yesterday, 11:45 PM</div>
                            <div class="text-[10px] font-bold text-gray-400 dark:text-zinc-500 mt-1 uppercase tracking-wider">April 08, 2026</div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="h-8 w-8 rounded-full bg-gray-800 text-white flex items-center justify-center font-extrabold text-xs">
                                    <i class="fa-solid fa-robot text-[10px]"></i>
                                </div>
                                <div>
                                    <div class="font-bold text-gray-900 dark:text-white">System Automator</div>
                                    <div class="text-[10px] font-bold tracking-wider text-gray-400 dark:text-zinc-500 mt-0.5 uppercase">Internal</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="bg-rose-100 text-rose-700 dark:bg-rose-500/20 dark:text-rose-400 text-[10px] font-extrabold px-2.5 py-1 rounded-md uppercase tracking-wider border border-rose-200 dark:border-rose-500/30 flex items-center w-max gap-1.5">
                                <i class="fa-solid fa-shield-halved text-[10px]"></i> Security Alert
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="font-bold text-gray-900 dark:text-white text-xs">Account Lockout Triggered</div>
                            <div class="text-[11px] font-semibold text-rose-600 dark:text-rose-500 mt-1">
                                5 failed login attempts for username: <span class="font-bold">@staff_ana</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="text-[11px] font-mono text-gray-500 dark:text-zinc-400">112.204.15.88</div>
                        </td>
                    </tr>

                </tbody>
            </table>
        </div>
    </div>
</main>


<?php include 'includes/footer.php'; ?>