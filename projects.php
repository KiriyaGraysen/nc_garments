<?php 
session_start();
require_once 'config/database.php';

$page_title = "Orders & Projects | NC Garments";

include 'includes/header.php'; 
?>

        <main class="flex-1 p-4 md:p-8 overflow-y-auto transition-colors duration-500 font-sans">
            
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white transition-colors duration-500">Orders & Projects</h2>
                    <p class="text-gray-500 dark:text-zinc-400 text-sm mt-1 transition-colors duration-500">Manage customer orders, track production progress, and view cost breakdowns.</p>
                </div>
                <button onclick="toggleModal()" class="bg-pink-600 hover:bg-pink-700 text-white px-5 py-2.5 rounded-xl text-sm font-bold transition-all duration-300 shadow-lg shadow-pink-600/20 hover:shadow-pink-600/40 flex items-center gap-2 cursor-pointer focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-pink-500 dark:focus:ring-offset-zinc-950">
                    <i class="fa-solid fa-folder-plus"></i> Create New Project
                </button>
            </div>
        
            <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
                <div class="relative w-full md:w-96 group">
                    <i class="fa-solid fa-search absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400 group-focus-within:text-pink-600 transition-colors duration-500"></i>
                    <input type="text" placeholder="Search by Project ID, Customer..." 
                           class="w-full pl-11 pr-4 py-3 border border-gray-200 dark:border-zinc-800 rounded-xl bg-white dark:bg-zinc-900/50 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-zinc-500 focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent transition-colors duration-500 shadow-sm">
                </div>
                
                <div class="flex bg-gray-100 dark:bg-zinc-900/80 p-1 rounded-lg w-full md:w-auto overflow-x-auto transition-colors duration-500 border border-gray-200 dark:border-zinc-800">
                    <button class="whitespace-nowrap px-4 py-2 bg-white dark:bg-zinc-800 text-pink-600 dark:text-pink-500 text-sm font-bold rounded-md shadow-sm transition-colors duration-500 cursor-pointer">All Projects</button>
                    <button class="whitespace-nowrap px-4 py-2 text-gray-500 dark:text-zinc-400 hover:text-gray-900 hover:dark:text-white text-sm font-semibold rounded-md transition-colors duration-500 cursor-pointer">In Progress</button>
                    <button class="whitespace-nowrap px-4 py-2 text-gray-500 dark:text-zinc-400 hover:text-gray-900 hover:dark:text-white text-sm font-semibold rounded-md transition-colors duration-500 cursor-pointer">Completed</button>
                </div>
            </div>
        
            <div class="bg-white dark:bg-zinc-900 rounded-2xl shadow-sm border border-gray-100 dark:border-zinc-800 overflow-hidden transition-colors duration-500">
                <div class="overflow-x-auto">
                    <table class="w-full whitespace-nowrap">
                        
                        <thead class="bg-gray-50 dark:bg-zinc-950/50 border-b border-gray-100 dark:border-zinc-800 transition-colors duration-500">
                            <tr>
                                <th class="px-6 py-4 text-left text-[10px] font-extrabold text-gray-500 dark:text-zinc-500 uppercase tracking-widest">Project Details</th>
                                <th class="px-6 py-4 text-left text-[10px] font-extrabold text-gray-500 dark:text-zinc-500 uppercase tracking-widest">Production Progress</th>
                                <th class="px-6 py-4 text-left text-[10px] font-extrabold text-gray-500 dark:text-zinc-500 uppercase tracking-widest">Due Date</th>
                                <th class="px-6 py-4 text-left text-[10px] font-extrabold text-gray-500 dark:text-zinc-500 uppercase tracking-widest">Financials</th>
                                <th class="px-6 py-4 text-right text-[10px] font-extrabold text-gray-500 dark:text-zinc-500 uppercase tracking-widest">Actions</th>
                            </tr>
                        </thead>
                        
                        <tbody class="divide-y divide-gray-50 dark:divide-zinc-800/50 text-sm transition-colors duration-500">
                            
                            <tr class="hover:bg-gray-50/80 dark:hover:bg-zinc-800/30 transition-colors group">
                                <td class="px-6 py-4">
                                    <div class="font-bold text-pink-600 dark:text-pink-500 group-hover:text-pink-700 dark:group-hover:text-pink-400 transition-colors">#PRJ-2026-042</div>
                                    <div class="font-bold text-gray-900 dark:text-white mt-1">DHS PE Uniforms</div>
                                    <div class="text-xs font-medium text-gray-500 dark:text-zinc-400 flex items-center gap-1.5 mt-1">
                                        <i class="fa-regular fa-user text-[10px]"></i> Dasmariñas High School • 45 pcs
                                    </div>
                                </td>
                                <td class="px-6 py-4 w-64">
                                    <div class="flex justify-between text-xs mb-1.5">
                                        <span class="font-bold uppercase tracking-wider text-[10px] text-pink-600 dark:text-pink-500">Sewing Phase</span>
                                        <span class="font-bold text-gray-700 dark:text-zinc-300">65%</span>
                                    </div>
                                    <div class="w-full bg-gray-100 dark:bg-zinc-950 rounded-full h-1.5 shadow-inner overflow-hidden">
                                        <div class="bg-gradient-to-r from-pink-500 to-pink-600 h-1.5 rounded-full" style="width: 65%"></div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-gray-900 dark:text-white font-bold">Oct 25, 2026</div>
                                    <div class="text-xs font-semibold text-amber-500 mt-1">
                                        <i class="fa-regular fa-clock text-[10px]"></i> in 5 days
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="font-extrabold text-gray-900 dark:text-white">₱ 15,500.00</div>
                                    <div class="text-[11px] font-bold text-rose-500 tracking-wide mt-1">BAL: ₱ 5,500.00</div>
                                </td>
                                <td class="px-6 py-4 text-right text-sm font-medium">
                                    <button class="bg-white dark:bg-zinc-800 border border-gray-200 dark:border-zinc-700 text-gray-700 dark:text-zinc-300 hover:text-pink-600 dark:hover:text-pink-400 hover:border-pink-200 dark:hover:border-pink-900/50 px-3 py-1.5 rounded-lg transition-all mr-2 text-xs font-bold cursor-pointer shadow-sm">
                                        <i class="fa-solid fa-file-invoice-dollar mr-1"></i> Costing
                                    </button>
                                    <button class="text-gray-400 hover:text-pink-600 dark:hover:text-pink-500 transition-colors cursor-pointer focus:outline-none p-1">
                                        <i class="fa-solid fa-chevron-right"></i>
                                    </button>
                                </td>
                            </tr>
        
                            <tr class="hover:bg-gray-50/80 dark:hover:bg-zinc-800/30 transition-colors group">
                                <td class="px-6 py-4">
                                    <div class="font-bold text-pink-600 dark:text-pink-500 group-hover:text-pink-700 dark:group-hover:text-pink-400 transition-colors">#PRJ-2026-038</div>
                                    <div class="font-bold text-gray-900 dark:text-white mt-1">Custom Blazer</div>
                                    <div class="text-xs font-medium text-gray-500 dark:text-zinc-400 flex items-center gap-1.5 mt-1">
                                        <i class="fa-regular fa-user text-[10px]"></i> Maria Clara • 1 pc
                                    </div>
                                </td>
                                <td class="px-6 py-4 w-64">
                                    <div class="flex justify-between text-xs mb-1.5">
                                        <span class="font-bold uppercase tracking-wider text-[10px] text-amber-600 dark:text-amber-500">Pending Materials</span>
                                        <span class="font-bold text-gray-700 dark:text-zinc-300">10%</span>
                                    </div>
                                    <div class="w-full bg-gray-100 dark:bg-zinc-950 rounded-full h-1.5 shadow-inner overflow-hidden">
                                        <div class="bg-amber-500 h-1.5 rounded-full" style="width: 10%"></div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-gray-900 dark:text-white font-bold">Oct 18, 2026</div>
                                    <div class="text-xs font-bold text-rose-600 mt-1">
                                        <i class="fa-solid fa-circle-exclamation text-[10px]"></i> Overdue by 2 days
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="font-extrabold text-gray-900 dark:text-white">₱ 3,500.00</div>
                                    <div class="text-[11px] font-bold text-rose-500 tracking-wide mt-1">BAL: ₱ 1,500.00</div>
                                </td>
                                <td class="px-6 py-4 text-right text-sm font-medium">
                                    <button class="bg-white dark:bg-zinc-800 border border-gray-200 dark:border-zinc-700 text-gray-700 dark:text-zinc-300 hover:text-pink-600 dark:hover:text-pink-400 hover:border-pink-200 dark:hover:border-pink-900/50 px-3 py-1.5 rounded-lg transition-all mr-2 text-xs font-bold cursor-pointer shadow-sm">
                                        <i class="fa-solid fa-file-invoice-dollar mr-1"></i> Costing
                                    </button>
                                    <button class="text-gray-400 hover:text-pink-600 dark:hover:text-pink-500 transition-colors cursor-pointer focus:outline-none p-1">
                                        <i class="fa-solid fa-chevron-right"></i>
                                    </button>
                                </td>
                            </tr>
        
                            <tr class="hover:bg-gray-50/80 dark:hover:bg-zinc-800/30 transition-colors group">
                                <td class="px-6 py-4">
                                    <div class="font-bold text-pink-600 dark:text-pink-500 group-hover:text-pink-700 dark:group-hover:text-pink-400 transition-colors">#PRJ-2026-045</div>
                                    <div class="font-bold text-gray-900 dark:text-white mt-1">LGU Polo Shirts</div>
                                    <div class="text-xs font-medium text-gray-500 dark:text-zinc-400 flex items-center gap-1.5 mt-1">
                                        <i class="fa-regular fa-user text-[10px]"></i> Local Gov Unit • 120 pcs
                                    </div>
                                </td>
                                <td class="px-6 py-4 w-64">
                                    <div class="flex justify-between text-xs mb-1.5">
                                        <span class="font-bold uppercase tracking-wider text-[10px] text-pink-600 dark:text-pink-500">Cutting Phase</span>
                                        <span class="font-bold text-gray-700 dark:text-zinc-300">25%</span>
                                    </div>
                                    <div class="w-full bg-gray-100 dark:bg-zinc-950 rounded-full h-1.5 shadow-inner overflow-hidden">
                                        <div class="bg-gradient-to-r from-pink-500 to-pink-600 h-1.5 rounded-full" style="width: 25%"></div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-gray-900 dark:text-white font-bold">Nov 15, 2026</div>
                                    <div class="text-xs font-semibold text-gray-500 dark:text-zinc-400 mt-1">
                                        <i class="fa-regular fa-clock text-[10px]"></i> in 26 days
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="font-extrabold text-gray-900 dark:text-white">₱ 42,000.00</div>
                                    <div class="text-[11px] font-bold text-emerald-500 tracking-wide mt-1 bg-emerald-50 dark:bg-emerald-500/10 inline-block px-1.5 py-0.5 rounded">FULLY PAID</div>
                                </td>
                                <td class="px-6 py-4 text-right text-sm font-medium">
                                    <button class="bg-white dark:bg-zinc-800 border border-gray-200 dark:border-zinc-700 text-gray-700 dark:text-zinc-300 hover:text-pink-600 dark:hover:text-pink-400 hover:border-pink-200 dark:hover:border-pink-900/50 px-3 py-1.5 rounded-lg transition-all mr-2 text-xs font-bold cursor-pointer shadow-sm">
                                        <i class="fa-solid fa-file-invoice-dollar mr-1"></i> Costing
                                    </button>
                                    <button class="text-gray-400 hover:text-pink-600 dark:hover:text-pink-500 transition-colors cursor-pointer focus:outline-none p-1">
                                        <i class="fa-solid fa-chevron-right"></i>
                                    </button>
                                </td>
                            </tr>
        
                        </tbody>
                    </table>
                </div>
            </div>
        </main>

<?php include 'includes/footer.php'; ?>