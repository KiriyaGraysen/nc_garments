<?php 

// Set the dynamic page title
$page_title = "Dashboard | NC Garments";

// Load the Header (Sidebar and Top Bar)
include 'includes/header.php'; 
?>

        <main class="flex-1 bg-gray-50 dark:bg-zinc-950 p-8 overflow-y-auto transition-colors duration-500 font-sans">
        
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white transition-colors duration-500">Dashboard Overview</h2>
                    <p class="text-gray-500 dark:text-zinc-400 text-sm mt-1 transition-colors duration-500">Welcome back, Jezel. Here is what's happening today.</p>
                </div>
                <div class="flex gap-3">
                    <button class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 hover:bg-gray-50 dark:hover:bg-zinc-800 text-gray-700 dark:text-zinc-300 px-4 py-2.5 rounded-xl text-sm font-semibold transition-colors shadow-sm flex items-center gap-2 cursor-pointer focus:outline-none focus:ring-2 focus:ring-pink-500">
                        <i class="fa-solid fa-download text-pink-600 dark:text-pink-500"></i> Download Report
                    </button>
                </div>
            </div>
    
            <div class="bg-white/50 dark:bg-zinc-900/30 border border-gray-200 dark:border-zinc-800 rounded-2xl p-6 mb-8 transition-colors duration-500">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-base font-bold text-gray-900 dark:text-white transition-colors duration-500">Last 30 days</h3>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-6">
                    <div class="bg-white dark:bg-zinc-900 rounded-xl shadow-sm border border-gray-100 dark:border-zinc-800 flex flex-col overflow-hidden transition-colors duration-500 hover:shadow-md hover:border-pink-200 dark:hover:border-pink-900/50 group">
                        <div class="p-5 flex items-center gap-4 flex-grow">
                            <div class="h-12 w-12 rounded-xl bg-pink-100 dark:bg-pink-900/30 text-pink-600 dark:text-pink-500 flex items-center justify-center text-xl shrink-0 group-hover:bg-pink-600 group-hover:text-white transition-colors">
                                <i class="fa-solid fa-peso-sign"></i>
                            </div>
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-zinc-400">Total Sales</p>
                                <div class="flex items-baseline gap-2 mt-1">
                                    <h4 class="text-2xl font-extrabold text-gray-900 dark:text-white">₱ 42,500</h4>
                                    <span class="text-xs font-bold text-emerald-500 flex items-center bg-emerald-50 dark:bg-emerald-500/10 px-1.5 py-0.5 rounded-md">
                                        <i class="fa-solid fa-arrow-up text-[10px] mr-1"></i> 12%
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 dark:bg-zinc-900/80 px-5 py-3 border-t border-gray-50 dark:border-zinc-800 transition-colors duration-500">
                            <a href="#" class="text-xs font-bold text-pink-600 dark:text-pink-500 hover:text-pink-700 dark:hover:text-pink-400 flex items-center justify-between">
                                View all <i class="fa-solid fa-arrow-right text-[10px]"></i>
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
                                    <h4 class="text-2xl font-extrabold text-gray-900 dark:text-white">28</h4>
                                    <span class="text-xs font-bold text-emerald-500 flex items-center bg-emerald-50 dark:bg-emerald-500/10 px-1.5 py-0.5 rounded-md">
                                        <i class="fa-solid fa-arrow-up text-[10px] mr-1"></i> 4
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 dark:bg-zinc-900/80 px-5 py-3 border-t border-gray-50 dark:border-zinc-800 transition-colors duration-500">
                            <a href="#" class="text-xs font-bold text-pink-600 dark:text-pink-500 hover:text-pink-700 dark:hover:text-pink-400 flex items-center justify-between">
                                View all <i class="fa-solid fa-arrow-right text-[10px]"></i>
                            </a>
                        </div>
                    </div>
                    <div class="bg-white dark:bg-zinc-900 rounded-xl shadow-sm border border-gray-100 dark:border-zinc-800 flex flex-col overflow-hidden transition-colors duration-500 hover:shadow-md hover:border-pink-200 dark:hover:border-pink-900/50 group">
                        <div class="p-5 flex items-center gap-4 flex-grow">
                            <div class="h-12 w-12 rounded-xl bg-pink-100 dark:bg-pink-900/30 text-pink-600 dark:text-pink-500 flex items-center justify-center text-xl shrink-0 group-hover:bg-pink-600 group-hover:text-white transition-colors">
                                <i class="fa-solid fa-hand-holding-dollar"></i>
                            </div>
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-zinc-400">Receivables</p>
                                <div class="flex items-baseline gap-2 mt-1">
                                    <h4 class="text-2xl font-extrabold text-gray-900 dark:text-white">₱ 8,350</h4>
                                    <span class="text-xs font-bold text-rose-500 flex items-center bg-rose-50 dark:bg-rose-500/10 px-1.5 py-0.5 rounded-md">
                                        <i class="fa-solid fa-arrow-down text-[10px] mr-1"></i> 2.1%
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 dark:bg-zinc-900/80 px-5 py-3 border-t border-gray-50 dark:border-zinc-800 transition-colors duration-500">
                            <a href="#" class="text-xs font-bold text-pink-600 dark:text-pink-500 hover:text-pink-700 dark:hover:text-pink-400 flex items-center justify-between">
                                View all <i class="fa-solid fa-arrow-right text-[10px]"></i>
                            </a>
                        </div>
                    </div>
                    <div class="bg-white dark:bg-zinc-900 rounded-xl shadow-sm border border-gray-100 dark:border-zinc-800 flex flex-col overflow-hidden transition-colors duration-500 hover:shadow-md hover:border-rose-200 dark:hover:border-rose-900/50 group">
                        <div class="p-5 flex items-center gap-4 flex-grow">
                            <div class="h-12 w-12 rounded-xl bg-rose-100 dark:bg-rose-900/30 text-rose-600 flex items-center justify-center text-xl shrink-0 group-hover:bg-rose-600 group-hover:text-white transition-colors">
                                <i class="fa-solid fa-triangle-exclamation"></i>
                            </div>
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-zinc-400">Low Stock</p>
                                <div class="flex items-baseline gap-2 mt-1">
                                    <h4 class="text-2xl font-extrabold text-gray-900 dark:text-white">3 Items</h4>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 dark:bg-zinc-900/80 px-5 py-3 border-t border-gray-50 dark:border-zinc-800 transition-colors duration-500">
                            <a href="#" class="text-xs font-bold text-rose-600 dark:text-rose-500 hover:text-rose-700 dark:hover:text-rose-400 flex items-center justify-between">
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
                        <button class="w-8 h-8 rounded-full flex items-center justify-center text-gray-400 hover:text-pink-600 hover:bg-pink-50 dark:hover:bg-zinc-800 transition-colors focus:outline-none"><i class="fa-solid fa-ellipsis"></i></button>
                    </div>
                    
                    <div class="flex-grow flex items-end gap-4 h-64 pt-4 border-b border-gray-100 dark:border-zinc-800 pb-2">
                        <div class="flex-1 flex flex-col justify-end items-center group cursor-pointer">
                            <span class="text-xs text-transparent group-hover:text-gray-600 dark:group-hover:text-zinc-300 mb-2 transition-colors font-bold">₱22k</span>
                            <div class="w-full max-w-[40px] bg-pink-100 dark:bg-pink-900/20 rounded-t-lg relative h-[40%] group-hover:bg-pink-200 dark:group-hover:bg-pink-900/40 transition-colors">
                                <div class="absolute bottom-0 w-full bg-pink-500 dark:bg-pink-600 rounded-t-lg h-[60%]"></div>
                            </div>
                            <span class="text-xs text-gray-500 dark:text-zinc-500 mt-3 font-bold uppercase">May</span>
                        </div>
                        <div class="flex-1 flex flex-col justify-end items-center group cursor-pointer">
                            <span class="text-xs text-transparent group-hover:text-gray-600 dark:group-hover:text-zinc-300 mb-2 transition-colors font-bold">₱35k</span>
                            <div class="w-full max-w-[40px] bg-pink-100 dark:bg-pink-900/20 rounded-t-lg relative h-[65%] group-hover:bg-pink-200 dark:group-hover:bg-pink-900/40 transition-colors">
                                <div class="absolute bottom-0 w-full bg-pink-500 dark:bg-pink-600 rounded-t-lg h-[55%]"></div>
                            </div>
                            <span class="text-xs text-gray-500 dark:text-zinc-500 mt-3 font-bold uppercase">Jun</span>
                        </div>
                        <div class="flex-1 flex flex-col justify-end items-center group cursor-pointer">
                            <span class="text-xs text-transparent group-hover:text-gray-600 dark:group-hover:text-zinc-300 mb-2 transition-colors font-bold">₱28k</span>
                            <div class="w-full max-w-[40px] bg-pink-100 dark:bg-pink-900/20 rounded-t-lg relative h-[50%] group-hover:bg-pink-200 dark:group-hover:bg-pink-900/40 transition-colors">
                                <div class="absolute bottom-0 w-full bg-pink-500 dark:bg-pink-600 rounded-t-lg h-[65%]"></div>
                            </div>
                            <span class="text-xs text-gray-500 dark:text-zinc-500 mt-3 font-bold uppercase">Jul</span>
                        </div>
                        <div class="flex-1 flex flex-col justify-end items-center group cursor-pointer">
                            <span class="text-xs text-transparent group-hover:text-gray-600 dark:group-hover:text-zinc-300 mb-2 transition-colors font-bold">₱48k</span>
                            <div class="w-full max-w-[40px] bg-pink-100 dark:bg-pink-900/20 rounded-t-lg relative h-[85%] group-hover:bg-pink-200 dark:group-hover:bg-pink-900/40 transition-colors">
                                <div class="absolute bottom-0 w-full bg-pink-500 dark:bg-pink-600 rounded-t-lg h-[45%]"></div>
                            </div>
                            <span class="text-xs text-gray-500 dark:text-zinc-500 mt-3 font-bold uppercase">Aug</span>
                        </div>
                        <div class="flex-1 flex flex-col justify-end items-center group cursor-pointer">
                            <span class="text-xs text-transparent group-hover:text-gray-600 dark:group-hover:text-zinc-300 mb-2 transition-colors font-bold">₱38k</span>
                            <div class="w-full max-w-[40px] bg-pink-100 dark:bg-pink-900/20 rounded-t-lg relative h-[70%] group-hover:bg-pink-200 dark:group-hover:bg-pink-900/40 transition-colors">
                                <div class="absolute bottom-0 w-full bg-pink-500 dark:bg-pink-600 rounded-t-lg h-[50%]"></div>
                            </div>
                            <span class="text-xs text-gray-500 dark:text-zinc-500 mt-3 font-bold uppercase">Sep</span>
                        </div>
                        <div class="flex-1 flex flex-col justify-end items-center group cursor-pointer">
                            <span class="text-xs text-pink-600 dark:text-pink-400 mb-2 transition-colors font-extrabold">₱42k</span>
                            <div class="w-full max-w-[40px] bg-pink-200 dark:bg-pink-900/60 rounded-t-lg relative h-[75%] border-2 border-pink-500 transition-colors">
                                <div class="absolute bottom-0 w-full bg-pink-600 dark:bg-pink-500 rounded-t-sm h-[60%]"></div>
                            </div>
                            <span class="text-xs text-pink-600 dark:text-pink-400 mt-3 font-extrabold uppercase">Oct</span>
                        </div>
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
                        <a href="#" class="text-xs font-bold text-pink-600 dark:text-pink-500 hover:text-pink-700 dark:hover:text-pink-400 uppercase tracking-wide">View Calendar</a>
                    </div>
    
                    <div class="space-y-4 flex-grow">
                        <div class="flex gap-4 group cursor-pointer items-center">
                            <div class="flex flex-col items-center min-w-[3rem]">
                                <span class="text-[10px] font-extrabold text-rose-500 tracking-wider">OCT</span>
                                <span class="text-2xl font-extrabold text-gray-900 dark:text-white group-hover:text-pink-600 dark:group-hover:text-pink-500 transition-colors">25</span>
                            </div>
                            <div class="flex-grow bg-rose-50/50 dark:bg-rose-900/10 border border-rose-100 dark:border-rose-900/30 rounded-xl p-3.5 transition-colors">
                                <h4 class="text-sm font-bold text-gray-900 dark:text-white mb-0.5">DHS - Section Rizal</h4>
                                <p class="text-xs font-medium text-gray-500 dark:text-zinc-400 mb-3">45 pcs PE Uniforms</p>
                                <div class="w-full bg-gray-200 dark:bg-zinc-800 rounded-full h-1.5 overflow-hidden">
                                    <div class="bg-rose-500 h-1.5 rounded-full" style="width: 60%"></div>
                                </div>
                            </div>
                        </div>
    
                        <div class="flex gap-4 group cursor-pointer items-center">
                            <div class="flex flex-col items-center min-w-[3rem]">
                                <span class="text-[10px] font-extrabold text-gray-400 dark:text-zinc-500 tracking-wider">NOV</span>
                                <span class="text-2xl font-extrabold text-gray-900 dark:text-white group-hover:text-pink-600 dark:group-hover:text-pink-500 transition-colors">02</span>
                            </div>
                            <div class="flex-grow bg-gray-50/80 dark:bg-zinc-900/50 border border-gray-100 dark:border-zinc-800 rounded-xl p-3.5 transition-colors">
                                <h4 class="text-sm font-bold text-gray-900 dark:text-white mb-0.5">Maria Clara</h4>
                                <p class="text-xs font-medium text-gray-500 dark:text-zinc-400 mb-3">1 pc Custom Blazer</p>
                                <div class="w-full bg-gray-200 dark:bg-zinc-800 rounded-full h-1.5 overflow-hidden">
                                    <div class="bg-amber-500 h-1.5 rounded-full" style="width: 10%"></div>
                                </div>
                            </div>
                        </div>
    
                        <div class="flex gap-4 group cursor-pointer items-center">
                            <div class="flex flex-col items-center min-w-[3rem]">
                                <span class="text-[10px] font-extrabold text-gray-400 dark:text-zinc-500 tracking-wider">NOV</span>
                                <span class="text-2xl font-extrabold text-gray-900 dark:text-white group-hover:text-pink-600 dark:group-hover:text-pink-500 transition-colors">15</span>
                            </div>
                            <div class="flex-grow bg-gray-50/80 dark:bg-zinc-900/50 border border-gray-100 dark:border-zinc-800 rounded-xl p-3.5 transition-colors">
                                <h4 class="text-sm font-bold text-gray-900 dark:text-white mb-0.5">Local Gov Unit (LGU)</h4>
                                <p class="text-xs font-medium text-gray-500 dark:text-zinc-400 mb-3">120 pcs Polo Shirts</p>
                                <div class="w-full bg-gray-200 dark:bg-zinc-800 rounded-full h-1.5 overflow-hidden">
                                    <div class="bg-pink-600 h-1.5 rounded-full" style="width: 25%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <button class="w-full mt-4 py-3 text-xs font-bold uppercase tracking-wider text-gray-600 dark:text-zinc-400 bg-gray-50 hover:bg-gray-100 dark:bg-zinc-800 dark:hover:bg-zinc-700 rounded-xl transition-colors border border-gray-200 dark:border-zinc-700 focus:outline-none">
                        + 4 more projects
                    </button>
                </div>
    
            </div>
    
        </main>

<?php include 'includes/footer.php' ?>
