<?php 
session_start();
require_once 'config/database.php';

$page_title = "Staff Access | NC Garments";

include 'includes/header.php'; 
?>

<main class="flex-1 p-4 md:p-8 overflow-y-auto transition-colors duration-500 font-sans">
    
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white transition-colors duration-500">Staff Access Management</h2>
            <p class="text-gray-500 dark:text-zinc-400 text-sm mt-1 transition-colors duration-500">Provision accounts, manage role permissions, and monitor system access.</p>
        </div>
        <button class="bg-pink-600 hover:bg-pink-700 text-white px-5 py-2.5 rounded-xl text-sm font-bold transition-all duration-300 shadow-lg shadow-pink-600/20 hover:shadow-pink-600/40 flex items-center gap-2 cursor-pointer focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-pink-500 dark:focus:ring-offset-zinc-950">
            <i class="fa-solid fa-user-shield"></i> Provision New Account
        </button>
    </div>

    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
        
        <div class="relative w-full md:w-96 group">
            <i class="fa-solid fa-search absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400 group-focus-within:text-pink-600 transition-colors duration-500"></i>
            <input type="text" placeholder="Search by staff name or username..." 
                   class="w-full pl-11 pr-4 py-3 border border-gray-200 dark:border-zinc-800 rounded-xl bg-white dark:bg-zinc-900/50 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-zinc-500 focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent transition-colors duration-500 shadow-sm">
        </div>
        
        <div class="flex bg-gray-100 dark:bg-zinc-900/80 p-1 rounded-lg w-full md:w-auto overflow-x-auto transition-colors duration-500 border border-gray-200 dark:border-zinc-800">
            <button class="whitespace-nowrap px-5 py-2 bg-white dark:bg-zinc-800 text-pink-600 dark:text-pink-500 text-sm font-bold rounded-md shadow-sm transition-colors duration-500 cursor-pointer flex items-center gap-2">
                <i class="fa-solid fa-users text-xs"></i> All Accounts
            </button>
            <button class="whitespace-nowrap px-5 py-2 text-gray-500 dark:text-zinc-400 hover:text-gray-900 hover:dark:text-white text-sm font-semibold rounded-md transition-colors duration-500 cursor-pointer flex items-center gap-2">
                <i class="fa-solid fa-user-check text-xs"></i> Active
            </button>
            <button class="whitespace-nowrap px-5 py-2 text-gray-500 dark:text-zinc-400 hover:text-gray-900 hover:dark:text-white text-sm font-semibold rounded-md transition-colors duration-500 cursor-pointer flex items-center gap-2">
                <i class="fa-solid fa-user-lock text-xs"></i> Revoked
            </button>
        </div>
    </div>

    <div class="bg-white dark:bg-zinc-900 rounded-2xl shadow-sm border border-gray-100 dark:border-zinc-800 overflow-hidden transition-colors duration-500">
        <div class="overflow-x-auto">
            <table class="w-full whitespace-nowrap">
                
                <thead class="bg-gray-50 dark:bg-zinc-950/50 border-b border-gray-100 dark:border-zinc-800 transition-colors duration-500">
                    <tr>
                        <th class="px-6 py-4 text-left text-[10px] font-extrabold text-gray-500 dark:text-zinc-500 uppercase tracking-widest">Staff Member</th>
                        <th class="px-6 py-4 text-left text-[10px] font-extrabold text-gray-500 dark:text-zinc-500 uppercase tracking-widest">System Role</th>
                        <th class="px-6 py-4 text-left text-[10px] font-extrabold text-gray-500 dark:text-zinc-500 uppercase tracking-widest">Account Status</th>
                        <th class="px-6 py-4 text-left text-[10px] font-extrabold text-gray-500 dark:text-zinc-500 uppercase tracking-widest">Last Active</th>
                        <th class="px-6 py-4 text-right text-[10px] font-extrabold text-gray-500 dark:text-zinc-500 uppercase tracking-widest">Actions</th>
                    </tr>
                </thead>
                
                <tbody class="divide-y divide-gray-50 dark:divide-zinc-800/50 text-sm transition-colors duration-500">
                    
                    <tr class="hover:bg-gray-50/80 dark:hover:bg-zinc-800/30 transition-colors group">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="h-10 w-10 rounded-full bg-pink-600 text-white flex items-center justify-center font-extrabold text-sm shadow-md shadow-pink-600/20">
                                    JJ
                                </div>
                                <div>
                                    <div class="font-bold text-gray-900 dark:text-white group-hover:text-pink-600 dark:group-hover:text-pink-500 transition-colors">Jezel Juanillo</div>
                                    <div class="text-xs font-bold tracking-wider text-gray-400 dark:text-zinc-500 mt-0.5">@jezel_admin</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="bg-pink-50 text-pink-600 dark:bg-pink-500/10 dark:text-pink-400 text-[10px] font-extrabold px-2.5 py-1 rounded-md uppercase tracking-wider border border-pink-200 dark:border-pink-500/20 flex items-center w-max gap-1.5">
                                <i class="fa-solid fa-crown text-[10px]"></i> Superadmin
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-1.5">
                                <div class="h-2 w-2 rounded-full bg-emerald-500"></div>
                                <span class="font-bold text-gray-900 dark:text-white text-xs">Active</span>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-gray-900 dark:text-white font-bold text-xs">Online Now</div>
                            <div class="text-[10px] font-bold text-gray-400 dark:text-zinc-500 mt-1 uppercase tracking-wider">Current Session</div>
                        </td>
                        <td class="px-6 py-4 text-right text-sm font-medium">
                            <button disabled class="bg-gray-50 dark:bg-zinc-800/50 border border-gray-200 dark:border-zinc-800 text-gray-400 dark:text-zinc-600 px-3 py-1.5 rounded-lg transition-all mr-2 text-xs font-bold cursor-not-allowed shadow-sm">
                                Cannot Edit Self
                            </button>
                        </td>
                    </tr>

                    <tr class="hover:bg-gray-50/80 dark:hover:bg-zinc-800/30 transition-colors group">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="h-10 w-10 rounded-full bg-gray-100 dark:bg-zinc-800 text-gray-600 dark:text-zinc-400 flex items-center justify-center font-extrabold text-sm border border-gray-200 dark:border-zinc-700">
                                    MC
                                </div>
                                <div>
                                    <div class="font-bold text-gray-900 dark:text-white group-hover:text-pink-600 dark:group-hover:text-pink-500 transition-colors">Mark Cruz</div>
                                    <div class="text-xs font-bold tracking-wider text-gray-400 dark:text-zinc-500 mt-0.5">@staff_mark</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="bg-gray-100 text-gray-600 dark:bg-zinc-800 dark:text-zinc-400 text-[10px] font-extrabold px-2.5 py-1 rounded-md uppercase tracking-wider border border-gray-200 dark:border-zinc-700 flex items-center w-max gap-1.5">
                                <i class="fa-solid fa-user text-[10px]"></i> General Staff
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-1.5">
                                <div class="h-2 w-2 rounded-full bg-emerald-500"></div>
                                <span class="font-bold text-gray-900 dark:text-white text-xs">Active</span>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-gray-900 dark:text-white font-bold text-xs">Today, 8:45 AM</div>
                            <div class="text-[10px] font-bold text-gray-400 dark:text-zinc-500 mt-1 uppercase tracking-wider">MacBook Pro - Chrome</div>
                        </td>
                        <td class="px-6 py-4 text-right text-sm font-medium flex justify-end gap-2">
                            <button class="bg-white dark:bg-zinc-800 border border-gray-200 dark:border-zinc-700 text-gray-700 dark:text-zinc-300 hover:text-pink-600 dark:hover:text-pink-400 hover:border-pink-200 dark:hover:border-pink-900/50 px-3 py-1.5 rounded-lg transition-all text-xs font-bold cursor-pointer shadow-sm">
                                <i class="fa-solid fa-pen-to-square mr-1"></i> Edit
                            </button>
                            <button class="bg-white dark:bg-zinc-800 border border-gray-200 dark:border-zinc-700 text-rose-500 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-900/20 hover:border-rose-200 dark:hover:border-rose-800 px-3 py-1.5 rounded-lg transition-all text-xs font-bold cursor-pointer shadow-sm">
                                <i class="fa-solid fa-ban mr-1"></i> Revoke
                            </button>
                        </td>
                    </tr>

                    <tr class="hover:bg-gray-50/80 dark:hover:bg-zinc-800/30 transition-colors group opacity-75">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="h-10 w-10 rounded-full bg-gray-100 dark:bg-zinc-800 text-gray-400 dark:text-zinc-600 flex items-center justify-center font-extrabold text-sm border border-gray-200 dark:border-zinc-700">
                                    AL
                                </div>
                                <div>
                                    <div class="font-bold text-gray-500 dark:text-zinc-400 transition-colors line-through">Ana Lopez</div>
                                    <div class="text-xs font-bold tracking-wider text-gray-400 dark:text-zinc-600 mt-0.5">@staff_ana</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="bg-gray-50 text-gray-400 dark:bg-zinc-800/50 dark:text-zinc-500 text-[10px] font-extrabold px-2.5 py-1 rounded-md uppercase tracking-wider border border-gray-100 dark:border-zinc-800 flex items-center w-max gap-1.5">
                                <i class="fa-solid fa-user text-[10px]"></i> General Staff
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-1.5">
                                <div class="h-2 w-2 rounded-full bg-rose-500"></div>
                                <span class="font-bold text-rose-600 dark:text-rose-500 text-xs">Access Revoked</span>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-gray-500 dark:text-zinc-400 font-bold text-xs">Mar 15, 2026</div>
                            <div class="text-[10px] font-bold text-gray-400 dark:text-zinc-600 mt-1 uppercase tracking-wider">Account Locked</div>
                        </td>
                        <td class="px-6 py-4 text-right text-sm font-medium">
                            <button class="bg-white dark:bg-zinc-800 border border-gray-200 dark:border-zinc-700 text-emerald-600 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 hover:border-emerald-200 dark:hover:border-emerald-800 px-3 py-1.5 rounded-lg transition-all mr-2 text-xs font-bold cursor-pointer shadow-sm">
                                <i class="fa-solid fa-unlock-keyhole mr-1"></i> Restore Access
                            </button>
                        </td>
                    </tr>

                </tbody>
            </table>
        </div>
    </div>
</main>


<?php include 'includes/footer.php'; ?>