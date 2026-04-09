<?php 
session_start();
require_once 'config/database.php';

$page_title = "Settings | NC Garments";

include 'includes/header.php'; 
?>

<main class="flex-1 p-4 md:p-8 overflow-y-auto transition-colors duration-500 font-sans">
    
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white transition-colors duration-500">System Settings</h2>
            <p class="text-gray-500 dark:text-zinc-400 text-sm mt-1 transition-colors duration-500">Configure business details, tax compliance, and global system preferences.</p>
        </div>
        <button class="bg-pink-600 hover:bg-pink-700 text-white px-6 py-2.5 rounded-xl text-sm font-bold transition-all duration-300 shadow-lg shadow-pink-600/20 hover:shadow-pink-600/40 flex items-center gap-2 cursor-pointer focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-pink-500 dark:focus:ring-offset-zinc-950">
            <i class="fa-solid fa-floppy-disk"></i> Save All Changes
        </button>
    </div>

    <div class="flex flex-col lg:flex-row gap-8">
        
        <div class="w-full lg:w-64 shrink-0">
            <div class="flex flex-col gap-2 sticky top-4">
                <button class="w-full flex items-center gap-3 px-4 py-3 bg-white dark:bg-zinc-900 border border-pink-200 dark:border-pink-900/50 rounded-xl text-pink-600 dark:text-pink-500 font-bold text-sm shadow-sm transition-colors duration-500 text-left">
                    <i class="fa-solid fa-store w-5 text-center"></i> Business Profile
                </button>
                <button class="w-full flex items-center gap-3 px-4 py-3 text-gray-600 dark:text-zinc-400 hover:bg-gray-100 dark:hover:bg-zinc-900 rounded-xl font-semibold text-sm transition-colors duration-500 text-left border border-transparent">
                    <i class="fa-solid fa-file-invoice-dollar w-5 text-center"></i> BIR & Invoicing
                </button>
                <button class="w-full flex items-center gap-3 px-4 py-3 text-gray-600 dark:text-zinc-400 hover:bg-gray-100 dark:hover:bg-zinc-900 rounded-xl font-semibold text-sm transition-colors duration-500 text-left border border-transparent">
                    <i class="fa-solid fa-bell w-5 text-center"></i> Notifications
                </button>
                <button class="w-full flex items-center gap-3 px-4 py-3 text-gray-600 dark:text-zinc-400 hover:bg-gray-100 dark:hover:bg-zinc-900 rounded-xl font-semibold text-sm transition-colors duration-500 text-left border border-transparent">
                    <i class="fa-solid fa-shield-halved w-5 text-center"></i> Security & Sessions
                </button>
            </div>
        </div>

        <div class="flex-1 space-y-6">
            
            <div class="bg-white dark:bg-zinc-900 rounded-2xl shadow-sm border border-gray-100 dark:border-zinc-800 p-6 md:p-8 transition-colors duration-500">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-6 border-b border-gray-100 dark:border-zinc-800 pb-4">Business Information</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-gray-600 dark:text-zinc-400 mb-2 uppercase tracking-wide">Registered Business Name</label>
                        <input type="text" value="Needle Class Garments" class="w-full px-4 py-3 bg-gray-50 dark:bg-zinc-950 border border-gray-200 dark:border-zinc-800 text-gray-900 dark:text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent transition-colors duration-500 text-sm font-medium">
                    </div>
                    
                    <div>
                        <label class="block text-xs font-bold text-gray-600 dark:text-zinc-400 mb-2 uppercase tracking-wide">Contact Number</label>
                        <input type="text" value="(046) 416-1234" class="w-full px-4 py-3 bg-gray-50 dark:bg-zinc-950 border border-gray-200 dark:border-zinc-800 text-gray-900 dark:text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent transition-colors duration-500 text-sm font-medium">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-600 dark:text-zinc-400 mb-2 uppercase tracking-wide">Business Email</label>
                        <input type="email" value="management@needleclass.ph" class="w-full px-4 py-3 bg-gray-50 dark:bg-zinc-950 border border-gray-200 dark:border-zinc-800 text-gray-900 dark:text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent transition-colors duration-500 text-sm font-medium">
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-gray-600 dark:text-zinc-400 mb-2 uppercase tracking-wide">Shop Address (For Receipts)</label>
                        <textarea rows="2" class="w-full px-4 py-3 bg-gray-50 dark:bg-zinc-950 border border-gray-200 dark:border-zinc-800 text-gray-900 dark:text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent transition-colors duration-500 text-sm font-medium resize-none">Unit 4, Commercial Bldg, Salitran, Dasmariñas City, Cavite</textarea>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-zinc-900 rounded-2xl shadow-sm border border-gray-100 dark:border-zinc-800 p-6 md:p-8 transition-colors duration-500">
                <div class="flex justify-between items-center mb-6 border-b border-gray-100 dark:border-zinc-800 pb-4">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">BIR & Invoicing Compliance</h3>
                    <span class="bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400 text-[10px] font-extrabold px-2.5 py-1 rounded-md uppercase tracking-wider border border-emerald-200 dark:border-emerald-500/20">
                        System Compliant
                    </span>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-xs font-bold text-gray-600 dark:text-zinc-400 mb-2 uppercase tracking-wide">Tax Identification Number (TIN)</label>
                        <input type="text" value="000-123-456-000" class="w-full px-4 py-3 bg-gray-50 dark:bg-zinc-950 border border-gray-200 dark:border-zinc-800 text-gray-900 dark:text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent transition-colors duration-500 text-sm font-medium font-mono">
                    </div>
                    
                    <div>
                        <label class="block text-xs font-bold text-gray-600 dark:text-zinc-400 mb-2 uppercase tracking-wide">BIR Permit To Use (PTU)</label>
                        <input type="text" value="PTU-2026-987654321" class="w-full px-4 py-3 bg-gray-50 dark:bg-zinc-950 border border-gray-200 dark:border-zinc-800 text-gray-900 dark:text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent transition-colors duration-500 text-sm font-medium font-mono">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-600 dark:text-zinc-400 mb-2 uppercase tracking-wide">Default Tax Rate (%)</label>
                        <div class="relative">
                            <input type="number" value="12" class="w-full pl-4 pr-10 py-3 bg-gray-50 dark:bg-zinc-950 border border-gray-200 dark:border-zinc-800 text-gray-900 dark:text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent transition-colors duration-500 text-sm font-medium">
                            <span class="absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-400 font-bold">%</span>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-600 dark:text-zinc-400 mb-2 uppercase tracking-wide">Invoice Prefix</label>
                        <input type="text" value="NC-" class="w-full px-4 py-3 bg-gray-50 dark:bg-zinc-950 border border-gray-200 dark:border-zinc-800 text-gray-900 dark:text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent transition-colors duration-500 text-sm font-medium font-mono">
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-zinc-900 rounded-2xl shadow-sm border border-gray-100 dark:border-zinc-800 p-6 md:p-8 transition-colors duration-500">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-6 border-b border-gray-100 dark:border-zinc-800 pb-4">Global Preferences</h3>
                
                <div class="flex items-center justify-between py-2 border-b border-gray-50 dark:border-zinc-800/50">
                    <div>
                        <h4 class="text-sm font-bold text-gray-900 dark:text-white">Low Stock Alerts</h4>
                        <p class="text-xs text-gray-500 dark:text-zinc-400 mt-0.5">Show red warnings in inventory when items fall below minimum threshold.</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" value="" class="sr-only peer" checked>
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-pink-300 dark:peer-focus:ring-pink-800 rounded-full peer dark:bg-zinc-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-pink-600"></div>
                    </label>
                </div>

                <div class="flex items-center justify-between py-4 border-b border-gray-50 dark:border-zinc-800/50">
                    <div>
                        <h4 class="text-sm font-bold text-gray-900 dark:text-white">Strict Session Timeout</h4>
                        <p class="text-xs text-gray-500 dark:text-zinc-400 mt-0.5">Automatically log out staff members after 30 minutes of inactivity.</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" value="" class="sr-only peer" checked>
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-pink-300 dark:peer-focus:ring-pink-800 rounded-full peer dark:bg-zinc-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-pink-600"></div>
                    </label>
                </div>
            </div>

        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>