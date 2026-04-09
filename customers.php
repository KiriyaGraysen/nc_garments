<?php 
session_start();
require_once 'config/database.php';

$page_title = "Customers and Payments | NC Garments";

include 'includes/header.php'; 
?>

<main class="flex-1 p-4 md:p-8 overflow-y-auto transition-colors duration-500 font-sans">
    
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white transition-colors duration-500">Customers & Payments</h2>
            <p class="text-gray-500 dark:text-zinc-400 text-sm mt-1 transition-colors duration-500">Manage your client directory, track billing histories, and record incoming payments.</p>
        </div>
        <button class="bg-pink-600 hover:bg-pink-700 text-white px-5 py-2.5 rounded-xl text-sm font-bold transition-all duration-300 shadow-lg shadow-pink-600/20 hover:shadow-pink-600/40 flex items-center gap-2 cursor-pointer focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-pink-500 dark:focus:ring-offset-zinc-950">
            <i class="fa-solid fa-user-plus"></i> Add New Customer
        </button>
    </div>

    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
        
        <div class="relative w-full md:w-96 group">
            <i class="fa-solid fa-search absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400 group-focus-within:text-pink-600 transition-colors duration-500"></i>
            <input type="text" placeholder="Search by customer name or phone..." 
                   class="w-full pl-11 pr-4 py-3 border border-gray-200 dark:border-zinc-800 rounded-xl bg-white dark:bg-zinc-900/50 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-zinc-500 focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent transition-colors duration-500 shadow-sm">
        </div>
        
        <div class="flex bg-gray-100 dark:bg-zinc-900/80 p-1 rounded-lg w-full md:w-auto overflow-x-auto transition-colors duration-500 border border-gray-200 dark:border-zinc-800">
            <button class="whitespace-nowrap px-5 py-2 bg-white dark:bg-zinc-800 text-pink-600 dark:text-pink-500 text-sm font-bold rounded-md shadow-sm transition-colors duration-500 cursor-pointer flex items-center gap-2">
                <i class="fa-solid fa-address-book text-xs"></i> All Customers
            </button>
            <button class="whitespace-nowrap px-5 py-2 text-gray-500 dark:text-zinc-400 hover:text-gray-900 hover:dark:text-white text-sm font-semibold rounded-md transition-colors duration-500 cursor-pointer flex items-center gap-2">
                <i class="fa-solid fa-money-bill-transfer text-xs"></i> Recent Payments
            </button>
            <button class="whitespace-nowrap px-5 py-2 text-gray-500 dark:text-zinc-400 hover:text-gray-900 hover:dark:text-white text-sm font-semibold rounded-md transition-colors duration-500 cursor-pointer flex items-center gap-2">
                <i class="fa-solid fa-triangle-exclamation text-xs"></i> Unpaid Balances
            </button>
        </div>
    </div>

    <div class="bg-white dark:bg-zinc-900 rounded-2xl shadow-sm border border-gray-100 dark:border-zinc-800 overflow-hidden transition-colors duration-500">
        <div class="overflow-x-auto">
            <table class="w-full whitespace-nowrap">
                
                <thead class="bg-gray-50 dark:bg-zinc-950/50 border-b border-gray-100 dark:border-zinc-800 transition-colors duration-500">
                    <tr>
                        <th class="px-6 py-4 text-left text-[10px] font-extrabold text-gray-500 dark:text-zinc-500 uppercase tracking-widest">Customer Profile</th>
                        <th class="px-6 py-4 text-left text-[10px] font-extrabold text-gray-500 dark:text-zinc-500 uppercase tracking-widest">Contact Information</th>
                        <th class="px-6 py-4 text-left text-[10px] font-extrabold text-gray-500 dark:text-zinc-500 uppercase tracking-widest">Financial Standing</th>
                        <th class="px-6 py-4 text-left text-[10px] font-extrabold text-gray-500 dark:text-zinc-500 uppercase tracking-widest">Latest Activity</th>
                        <th class="px-6 py-4 text-right text-[10px] font-extrabold text-gray-500 dark:text-zinc-500 uppercase tracking-widest">Actions</th>
                    </tr>
                </thead>
                
                <tbody class="divide-y divide-gray-50 dark:divide-zinc-800/50 text-sm transition-colors duration-500">
                    
                    <tr class="hover:bg-gray-50/80 dark:hover:bg-zinc-800/30 transition-colors group">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="h-10 w-10 rounded-full bg-pink-100 dark:bg-pink-900/30 text-pink-600 dark:text-pink-500 flex items-center justify-center font-extrabold text-sm border border-pink-200 dark:border-pink-800/50">
                                    MS
                                </div>
                                <div>
                                    <div class="font-bold text-gray-900 dark:text-white group-hover:text-pink-600 dark:group-hover:text-pink-500 transition-colors">Maria Santos</div>
                                    <div class="text-xs font-bold tracking-wider text-gray-400 dark:text-zinc-500 mt-0.5">ID: CUST-0104</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-gray-700 dark:text-zinc-300 font-medium text-xs flex items-center gap-2">
                                <i class="fa-solid fa-phone text-gray-400"></i> 0917-555-0192
                            </div>
                            <div class="text-gray-500 dark:text-zinc-400 font-medium text-xs flex items-center gap-2 mt-1.5">
                                <i class="fa-solid fa-location-dot text-gray-400"></i> Dasmariñas High School
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2">
                                <span class="bg-rose-50 text-rose-600 dark:bg-rose-500/10 dark:text-rose-400 text-[10px] font-extrabold px-2.5 py-1 rounded-md uppercase tracking-wider border border-rose-200 dark:border-rose-500/20">
                                    With Balance
                                </span>
                            </div>
                            <div class="text-[11px] font-bold text-gray-900 dark:text-white mt-2">
                                Bal: <span class="text-rose-500 text-sm font-extrabold">₱ 5,500.00</span>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-gray-900 dark:text-white font-bold text-xs">Payment Received</div>
                            <div class="text-[10px] font-bold text-gray-400 dark:text-zinc-500 mt-1 uppercase tracking-wider">Oct 12, 2026 (GCash)</div>
                        </td>
                        <td class="px-6 py-4 text-right text-sm font-medium">
                            <button class="bg-white dark:bg-zinc-800 border border-gray-200 dark:border-zinc-700 text-gray-700 dark:text-zinc-300 hover:text-pink-600 dark:hover:text-pink-400 hover:border-pink-200 dark:hover:border-pink-900/50 px-3 py-1.5 rounded-lg transition-all mr-2 text-xs font-bold cursor-pointer shadow-sm">
                                <i class="fa-solid fa-money-bill-wave mr-1"></i> Pay
                            </button>
                            <button class="text-gray-400 hover:text-pink-600 dark:hover:text-pink-500 transition-colors cursor-pointer focus:outline-none p-1">
                                <i class="fa-solid fa-chevron-right"></i>
                            </button>
                        </td>
                    </tr>

                    <tr class="hover:bg-gray-50/80 dark:hover:bg-zinc-800/30 transition-colors group">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="h-10 w-10 rounded-full bg-pink-100 dark:bg-pink-900/30 text-pink-600 dark:text-pink-500 flex items-center justify-center font-extrabold text-sm border border-pink-200 dark:border-pink-800/50">
                                    JP
                                </div>
                                <div>
                                    <div class="font-bold text-gray-900 dark:text-white group-hover:text-pink-600 dark:group-hover:text-pink-500 transition-colors">Hon. Juan Perez</div>
                                    <div class="text-xs font-bold tracking-wider text-gray-400 dark:text-zinc-500 mt-0.5">ID: CUST-0089</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-gray-700 dark:text-zinc-300 font-medium text-xs flex items-center gap-2">
                                <i class="fa-solid fa-phone text-gray-400"></i> 0920-123-4567
                            </div>
                            <div class="text-gray-500 dark:text-zinc-400 font-medium text-xs flex items-center gap-2 mt-1.5">
                                <i class="fa-solid fa-location-dot text-gray-400"></i> LGU Municipal Hall
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2">
                                <span class="bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400 text-[10px] font-extrabold px-2.5 py-1 rounded-md uppercase tracking-wider border border-emerald-200 dark:border-emerald-500/20">
                                    Cleared
                                </span>
                            </div>
                            <div class="text-[11px] font-bold text-gray-500 dark:text-zinc-400 mt-2 uppercase">
                                Lifetime: ₱ 85,000.00
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-gray-900 dark:text-white font-bold text-xs">Project Completed</div>
                            <div class="text-[10px] font-bold text-gray-400 dark:text-zinc-500 mt-1 uppercase tracking-wider">Oct 05, 2026 (Check)</div>
                        </td>
                        <td class="px-6 py-4 text-right text-sm font-medium">
                            <button class="bg-white dark:bg-zinc-800 border border-gray-200 dark:border-zinc-700 text-gray-700 dark:text-zinc-300 hover:text-pink-600 dark:hover:text-pink-400 hover:border-pink-200 dark:hover:border-pink-900/50 px-3 py-1.5 rounded-lg transition-all mr-2 text-xs font-bold cursor-pointer shadow-sm">
                                <i class="fa-solid fa-file-invoice mr-1"></i> Invoice
                            </button>
                            <button class="text-gray-400 hover:text-pink-600 dark:hover:text-pink-500 transition-colors cursor-pointer focus:outline-none p-1">
                                <i class="fa-solid fa-chevron-right"></i>
                            </button>
                        </td>
                    </tr>

                    <tr class="hover:bg-gray-50/80 dark:hover:bg-zinc-800/30 transition-colors group">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="h-10 w-10 rounded-full bg-pink-100 dark:bg-pink-900/30 text-pink-600 dark:text-pink-500 flex items-center justify-center font-extrabold text-sm border border-pink-200 dark:border-pink-800/50">
                                    EG
                                </div>
                                <div>
                                    <div class="font-bold text-gray-900 dark:text-white group-hover:text-pink-600 dark:group-hover:text-pink-500 transition-colors">Elena Gomez</div>
                                    <div class="text-xs font-bold tracking-wider text-gray-400 dark:text-zinc-500 mt-0.5">ID: CUST-0142</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-gray-700 dark:text-zinc-300 font-medium text-xs flex items-center gap-2">
                                <i class="fa-solid fa-phone text-gray-400"></i> 0998-765-4321
                            </div>
                            <div class="text-gray-500 dark:text-zinc-400 font-medium text-xs flex items-center gap-2 mt-1.5">
                                <i class="fa-solid fa-location-dot text-gray-400"></i> Salitran, Dasmariñas
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2">
                                <span class="bg-rose-50 text-rose-600 dark:bg-rose-500/10 dark:text-rose-400 text-[10px] font-extrabold px-2.5 py-1 rounded-md uppercase tracking-wider border border-rose-200 dark:border-rose-500/20">
                                    With Balance
                                </span>
                            </div>
                            <div class="text-[11px] font-bold text-gray-900 dark:text-white mt-2">
                                Bal: <span class="text-rose-500 text-sm font-extrabold">₱ 1,500.00</span>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-gray-900 dark:text-white font-bold text-xs">Downpayment</div>
                            <div class="text-[10px] font-bold text-gray-400 dark:text-zinc-500 mt-1 uppercase tracking-wider">Yesterday (Cash)</div>
                        </td>
                        <td class="px-6 py-4 text-right text-sm font-medium">
                            <button class="bg-white dark:bg-zinc-800 border border-gray-200 dark:border-zinc-700 text-gray-700 dark:text-zinc-300 hover:text-pink-600 dark:hover:text-pink-400 hover:border-pink-200 dark:hover:border-pink-900/50 px-3 py-1.5 rounded-lg transition-all mr-2 text-xs font-bold cursor-pointer shadow-sm">
                                <i class="fa-solid fa-money-bill-wave mr-1"></i> Pay
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