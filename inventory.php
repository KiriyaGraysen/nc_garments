<?php
$page_title = "Inventory | NC Garments";

include 'includes/header.php'; 
?>

        <main class="flex-1 p-4 md:p-8 overflow-y-auto transition-colors duration-500 font-sans">
    
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white transition-colors duration-500">Inventory Management</h2>
                    <p class="text-gray-500 dark:text-zinc-400 text-sm mt-1 transition-colors duration-500">Track raw materials for production and premade products for retail sale.</p>
                </div>
                <button class="bg-pink-600 hover:bg-pink-700 text-white px-5 py-2.5 rounded-xl text-sm font-bold transition-all duration-300 shadow-lg shadow-pink-600/20 hover:shadow-pink-600/40 flex items-center gap-2 cursor-pointer focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-pink-500 dark:focus:ring-offset-zinc-950">
                    <i class="fa-solid fa-plus"></i> Add New Item
                </button>
            </div>
        
            <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
                
                <div class="relative w-full md:w-96 group">
                    <i class="fa-solid fa-search absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400 group-focus-within:text-pink-600 transition-colors duration-500"></i>
                    <input type="text" placeholder="Search by SKU or material name..." 
                           class="w-full pl-11 pr-4 py-3 border border-gray-200 dark:border-zinc-800 rounded-xl bg-white dark:bg-zinc-900/50 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-zinc-500 focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent transition-colors duration-500 shadow-sm">
                </div>
                
                <div class="flex bg-gray-100 dark:bg-zinc-900/80 p-1 rounded-lg w-full md:w-auto overflow-x-auto transition-colors duration-500 border border-gray-200 dark:border-zinc-800">
                    <button class="whitespace-nowrap px-5 py-2 bg-white dark:bg-zinc-800 text-pink-600 dark:text-pink-500 text-sm font-bold rounded-md shadow-sm transition-colors duration-500 cursor-pointer flex items-center gap-2">
                        <i class="fa-solid fa-layer-group text-xs"></i> Raw Materials
                    </button>
                    <button class="whitespace-nowrap px-5 py-2 text-gray-500 dark:text-zinc-400 hover:text-gray-900 hover:dark:text-white text-sm font-semibold rounded-md transition-colors duration-500 cursor-pointer flex items-center gap-2">
                        <i class="fa-solid fa-tags text-xs"></i> Premade Products
                    </button>
                </div>
            </div>
        
            <div class="bg-white dark:bg-zinc-900 rounded-2xl shadow-sm border border-gray-100 dark:border-zinc-800 overflow-hidden transition-colors duration-500">
                <div class="overflow-x-auto">
                    <table class="w-full whitespace-nowrap">
                        
                        <thead class="bg-gray-50 dark:bg-zinc-950/50 border-b border-gray-100 dark:border-zinc-800 transition-colors duration-500">
                            <tr>
                                <th class="px-6 py-4 text-left text-[10px] font-extrabold text-gray-500 dark:text-zinc-500 uppercase tracking-widest">Item Details</th>
                                <th class="px-6 py-4 text-left text-[10px] font-extrabold text-gray-500 dark:text-zinc-500 uppercase tracking-widest">Stock Level</th>
                                <th class="px-6 py-4 text-left text-[10px] font-extrabold text-gray-500 dark:text-zinc-500 uppercase tracking-widest">Unit Cost</th>
                                <th class="px-6 py-4 text-left text-[10px] font-extrabold text-gray-500 dark:text-zinc-500 uppercase tracking-widest">Status</th>
                                <th class="px-6 py-4 text-right text-[10px] font-extrabold text-gray-500 dark:text-zinc-500 uppercase tracking-widest">Actions</th>
                            </tr>
                        </thead>
                        
                        <tbody class="divide-y divide-gray-50 dark:divide-zinc-800/50 text-sm transition-colors duration-500">
                            
                            <tr class="hover:bg-gray-50/80 dark:hover:bg-zinc-800/30 transition-colors group">
                                <td class="px-6 py-4">
                                    <div class="font-bold text-gray-900 dark:text-white group-hover:text-pink-600 dark:group-hover:text-pink-500 transition-colors">White Tetoron Fabric</div>
                                    <div class="text-xs font-bold tracking-wider text-gray-400 dark:text-zinc-500 mt-1">SKU: RAW-FAB-001</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-baseline gap-1">
                                        <span class="text-lg font-extrabold text-gray-900 dark:text-white">150</span>
                                        <span class="text-xs font-bold text-gray-500 dark:text-zinc-400 uppercase">Yards</span>
                                    </div>
                                    <div class="text-[10px] font-bold text-gray-400 dark:text-zinc-500 mt-1 uppercase tracking-wider">Min Alert: 20 Yds</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="font-extrabold text-gray-900 dark:text-white">₱ 85.00</div>
                                    <div class="text-[10px] font-bold text-gray-400 dark:text-zinc-500 mt-1 uppercase tracking-wider">Per Yard</div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400 text-[10px] font-extrabold px-2.5 py-1 rounded-md uppercase tracking-wider border border-emerald-200 dark:border-emerald-500/20">
                                        In Stock
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right text-sm font-medium">
                                    <button class="text-gray-400 hover:text-pink-600 dark:hover:text-pink-500 transition-colors cursor-pointer focus:outline-none p-2" title="Edit Item">
                                        <i class="fa-solid fa-pen"></i>
                                    </button>
                                    <button class="text-gray-400 hover:text-pink-600 dark:hover:text-pink-500 transition-colors cursor-pointer focus:outline-none p-2" title="Restock History">
                                        <i class="fa-solid fa-clock-rotate-left"></i>
                                    </button>
                                </td>
                            </tr>
        
                            <tr class="hover:bg-gray-50/80 dark:hover:bg-zinc-800/30 transition-colors group bg-rose-50/30 dark:bg-rose-900/5">
                                <td class="px-6 py-4">
                                    <div class="font-bold text-gray-900 dark:text-white group-hover:text-pink-600 dark:group-hover:text-pink-500 transition-colors">Signature Pink Thread</div>
                                    <div class="text-xs font-bold tracking-wider text-gray-400 dark:text-zinc-500 mt-1">SKU: RAW-THR-042</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-baseline gap-1">
                                        <span class="text-lg font-extrabold text-rose-600 dark:text-rose-500">3</span>
                                        <span class="text-xs font-bold text-rose-600/70 dark:text-rose-500/70 uppercase">Cones</span>
                                    </div>
                                    <div class="text-[10px] font-bold text-rose-500 mt-1 uppercase tracking-wider">Min Alert: 5 Cones</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="font-extrabold text-gray-900 dark:text-white">₱ 45.00</div>
                                    <div class="text-[10px] font-bold text-gray-400 dark:text-zinc-500 mt-1 uppercase tracking-wider">Per Cone</div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="bg-rose-100 text-rose-700 dark:bg-rose-500/20 dark:text-rose-400 text-[10px] font-extrabold px-2.5 py-1 rounded-md uppercase tracking-wider border border-rose-200 dark:border-rose-500/30 flex items-center w-max gap-1">
                                        <i class="fa-solid fa-triangle-exclamation"></i> Low Stock
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right text-sm font-medium">
                                    <button class="text-gray-400 hover:text-pink-600 dark:hover:text-pink-500 transition-colors cursor-pointer focus:outline-none p-2" title="Edit Item">
                                        <i class="fa-solid fa-pen"></i>
                                    </button>
                                    <button class="text-gray-400 hover:text-pink-600 dark:hover:text-pink-500 transition-colors cursor-pointer focus:outline-none p-2" title="Restock History">
                                        <i class="fa-solid fa-clock-rotate-left"></i>
                                    </button>
                                </td>
                            </tr>
        
                            <tr class="hover:bg-gray-50/80 dark:hover:bg-zinc-800/30 transition-colors group">
                                <td class="px-6 py-4">
                                    <div class="font-bold text-gray-900 dark:text-white group-hover:text-pink-600 dark:group-hover:text-pink-500 transition-colors">15mm Black Buttons</div>
                                    <div class="text-xs font-bold tracking-wider text-gray-400 dark:text-zinc-500 mt-1">SKU: RAW-ACC-012</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-baseline gap-1">
                                        <span class="text-lg font-extrabold text-gray-900 dark:text-white">850</span>
                                        <span class="text-xs font-bold text-gray-500 dark:text-zinc-400 uppercase">Pcs</span>
                                    </div>
                                    <div class="text-[10px] font-bold text-gray-400 dark:text-zinc-500 mt-1 uppercase tracking-wider">Min Alert: 200 Pcs</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="font-extrabold text-gray-900 dark:text-white">₱ 2.50</div>
                                    <div class="text-[10px] font-bold text-gray-400 dark:text-zinc-500 mt-1 uppercase tracking-wider">Per Piece</div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400 text-[10px] font-extrabold px-2.5 py-1 rounded-md uppercase tracking-wider border border-emerald-200 dark:border-emerald-500/20">
                                        In Stock
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right text-sm font-medium">
                                    <button class="text-gray-400 hover:text-pink-600 dark:hover:text-pink-500 transition-colors cursor-pointer focus:outline-none p-2" title="Edit Item">
                                        <i class="fa-solid fa-pen"></i>
                                    </button>
                                    <button class="text-gray-400 hover:text-pink-600 dark:hover:text-pink-500 transition-colors cursor-pointer focus:outline-none p-2" title="Restock History">
                                        <i class="fa-solid fa-clock-rotate-left"></i>
                                    </button>
                                </td>
                            </tr>
        
                        </tbody>
                    </table>
                </div>
            </div>
        </main>

<?php include 'includes/footer.php'; ?>