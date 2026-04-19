<?php
require_once('config/database.php');

// SECURITY KICK-OUT
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    header("Location: access_denied.php");
    exit();
}

$current_user_role = $_SESSION['role'];
$current_user_id = $_SESSION['admin_id'];
$page_title = "Staff Access | NC Garments";

// 1. Determine Tab View and Dynamic SQL
$view = $_GET['view'] ?? 'all';

if ($view === 'archived') {
    $where_sql = "WHERE is_archived = 1";
} else {
    $where_sql = "WHERE is_archived = 0";
    if ($view === 'active') {
        $where_sql .= " AND status = 'active'";
    } elseif ($view === 'revoked') {
        $where_sql .= " AND status = 'deactivated'";
    }
}

// 2. Fetch Data
$stmt = $conn->prepare("
    SELECT admin_id, full_name, email, role, status, last_login
    FROM admin
    $where_sql
    ORDER BY full_name ASC
");
$stmt->execute();
$staff_result = $stmt->get_result();

function format_last_login($datetime_string) {
    if (empty($datetime_string)) return "Hasn't logged in yet";
    $clean_string = str_replace(',', '', $datetime_string);
    $target_date = new DateTime($clean_string);
    $today = new DateTime('today');
    $yesterday = new DateTime('yesterday');
    $compare_date = clone $target_date;
    $compare_date->setTime(0, 0, 0);
    $formatted_time = $target_date->format('h:i A');
    
    if ($compare_date == $today) return "Today, " . $formatted_time;
    elseif ($compare_date == $yesterday) return "Yesterday, " . $formatted_time;
    else return $target_date->format('M j, Y'); 
}

include 'includes/header.php'; 
?>

<main class="flex-1 p-4 md:p-8 overflow-y-auto transition-colors duration-500 font-sans relative">
    
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white transition-colors duration-500">Staff Access Management</h2>
            <p class="text-gray-500 dark:text-zinc-400 text-sm mt-1 transition-colors duration-500">Provision accounts, manage role permissions, and monitor system access.</p>
        </div>
        <?php if ($view !== 'archived'): ?>
        <button onclick="openStaffModal()" class="bg-pink-600 hover:bg-pink-700 text-white px-5 py-2.5 rounded-xl text-sm font-bold transition-all duration-300 shadow-lg shadow-pink-600/20 flex items-center gap-2 cursor-pointer focus:outline-none">
            <i class="fa-solid fa-user-plus"></i> Provision New Account
        </button>
        <?php endif; ?>
    </div>

    <div class="flex flex-col lg:flex-row justify-between items-center mb-6 gap-4">
        <div class="flex w-full lg:w-auto gap-3 flex-1 max-w-2xl">
            <div class="relative w-full group">
                <i class="fa-solid fa-search absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400 group-focus-within:text-pink-600 transition-colors duration-500"></i>
                
                <input type="text" id="search-input" placeholder="Search by staff name or email..." readonly onfocus="this.removeAttribute('readonly');"
                       class="w-full pl-11 pr-4 py-3 border border-gray-200 dark:border-zinc-800 rounded-xl bg-white dark:bg-zinc-900/50 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-zinc-500 focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent transition-colors duration-500 shadow-sm text-sm font-medium">
            </div>
        </div>
        
        <?php
            $active_tab = "bg-white dark:bg-zinc-800 text-pink-600 dark:text-pink-500 shadow-sm";
            $inactive_tab = "text-gray-500 dark:text-zinc-400 hover:text-gray-900 hover:dark:text-white";
        ?>
        
        <div class="flex bg-gray-100 dark:bg-zinc-900/80 p-1 rounded-lg w-full lg:w-auto overflow-x-auto transition-colors duration-500 border border-gray-200 dark:border-zinc-800 shrink-0">
            <a href="?view=all" class="whitespace-nowrap px-4 py-2 text-sm font-bold rounded-md transition-colors duration-500 flex items-center gap-2 <?= $view === 'all' ? $active_tab : $inactive_tab ?>">
                <i class="fa-solid fa-users text-xs"></i> All Accounts
            </a>
            <a href="?view=active" class="whitespace-nowrap px-4 py-2 text-sm font-semibold rounded-md transition-colors duration-500 flex items-center gap-2 <?= $view === 'active' ? $active_tab : $inactive_tab ?>">
                <i class="fa-solid fa-user-check text-xs"></i> Active
            </a>
            <a href="?view=revoked" class="whitespace-nowrap px-4 py-2 text-sm font-semibold rounded-md transition-colors duration-500 flex items-center gap-2 <?= $view === 'revoked' ? $active_tab : $inactive_tab ?>">
                <i class="fa-solid fa-user-lock text-xs"></i> Revoked
            </a>
            <span class="mx-1 border-r border-gray-300 dark:border-zinc-700"></span>
            <a href="?view=archived" class="whitespace-nowrap px-4 py-2 text-sm font-semibold rounded-md transition-colors duration-500 flex items-center gap-2 <?= $view === 'archived' ? $active_tab : $inactive_tab ?>">
                <i class="fa-solid fa-box-archive text-xs"></i> Archived
            </a>
        </div>
    </div>

    <div class="bg-white dark:bg-zinc-900 rounded-2xl shadow-sm border border-gray-100 dark:border-zinc-800 overflow-hidden transition-colors duration-500 flex flex-col">
        <div class="overflow-x-auto flex-1">
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
                <tbody id="staff-tbody" class="divide-y divide-gray-50 dark:divide-zinc-800/50 text-sm transition-colors duration-500">
                    
                    <?php
                    // 🚨 PHP Empty State
                    if ($staff_result->num_rows === 0) {
                        echo '<tr id="php-empty-state"><td colspan="5" class="px-6 py-8 text-center text-gray-500 font-medium">No accounts found in this view.</td></tr>';
                    }

                    while ($staff = $staff_result->fetch_assoc()) {
                        $raw_login_date = $staff['last_login'];
                        $formatted_login = format_last_login($raw_login_date);
                        
                        $full_name = htmlspecialchars($staff['full_name']);
                        $user_initials = strtoupper(substr($full_name, 0, 2));
                        $email = htmlspecialchars($staff['email']);
                        $status = htmlspecialchars($staff['status']);
                        $role = htmlspecialchars($staff['role']);
                        
                        $role_class = '';
                        $role_icon = '';
                        
                        if ($role === 'superadmin') {
                            $role_class = 'bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-400 border-amber-200 dark:border-amber-500/20';
                            $role_icon = 'fa-crown';
                        } elseif ($role === 'admin') {
                            $role_class = 'bg-pink-50 text-pink-600 dark:bg-pink-500/10 dark:text-pink-400 border-pink-200 dark:border-pink-500/20';
                            $role_icon = 'fa-user-shield';
                        } else {
                            $role_class = 'bg-gray-100 text-gray-600 dark:bg-zinc-800 dark:text-zinc-400 border-gray-200 dark:border-zinc-700';
                            $role_icon = 'fa-user';
                        }

                        $is_current_user = ($staff['admin_id'] == $current_user_id);
                        $row_class = ($view === 'archived' || $status === 'deactivated') ? 'opacity-60 grayscale-[50%]' : '';
                        
                        // 🚨 RBAC CHECK: Admin looking at Superadmin
                        $can_edit_this_user = true;
                        if ($current_user_role === 'admin' && $role === 'superadmin') {
                            $can_edit_this_user = false;
                        }
                        
                        echo '
                            <tr class="staff-row hover:bg-gray-50/80 dark:hover:bg-zinc-800/30 transition-colors group ' . $row_class . '">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="h-10 w-10 rounded-full bg-pink-600 text-white flex items-center justify-center font-extrabold text-sm shadow-md shadow-pink-600/20 shrink-0">
                                            ' . $user_initials . '
                                        </div>
                                        <div class="min-w-0">
                                            <div class="font-bold text-gray-900 dark:text-white group-hover:text-pink-600 transition-colors truncate">' . $full_name . ' ' . ($is_current_user ? '<span class="text-[10px] text-pink-500">(You)</span>' : '') . '</div>
                                            <div class="text-xs font-medium tracking-wide text-gray-500 dark:text-zinc-400 mt-0.5 truncate"><i class="fa-regular fa-envelope mr-1 border-none text-[10px]"></i>' . $email . '</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="' . $role_class . ' text-[10px] font-extrabold px-2.5 py-1 rounded-md uppercase tracking-wider flex items-center w-max gap-1.5 border">
                                        <i class="fa-solid ' . $role_icon . ' text-[11px]"></i>' . $role . '
                                    </span>
                                </td>
                                <td class="px-6 py-4">';
                                
                        if ($view === 'archived') {
                            echo '<span class="bg-gray-100 text-gray-600 dark:bg-zinc-800 dark:text-zinc-400 text-[10px] font-extrabold px-2.5 py-1 rounded-md uppercase tracking-wider border border-gray-200 dark:border-zinc-700 flex items-center w-max gap-1.5">
                                    <i class="fa-solid fa-box-archive"></i> Archived
                                  </span>';
                        } else {
                            echo '<div class="flex items-center gap-1.5">
                                    <div class="h-2 w-2 rounded-full ' . ($status === 'active' ? 'bg-emerald-500' : 'bg-rose-500') . '"></div>
                                    <span class="font-bold text-gray-900 dark:text-white text-xs first-letter:uppercase">' . $status . '</span>
                                  </div>';
                        }
                                
                        echo '  </td>
                                <td class="px-6 py-4">
                                    <div class="text-gray-900 dark:text-white font-bold text-xs">' . $formatted_login . '</div>
                                </td>
                                <td class="px-6 py-4 text-right text-sm font-medium flex justify-end gap-1.5">'; 
                        
                        // 🚨 RBAC ENFORCEMENT: Output Buttons or Read-Only state
                        if ($view === 'archived') {
                            if ($can_edit_this_user) {
                                echo '<button onclick="restoreStaff('.$staff['admin_id'].')" class="relative group/btn flex items-center justify-center w-8 h-8 bg-white dark:bg-zinc-800 border border-gray-200 dark:border-zinc-700 hover:border-emerald-300 text-gray-400 hover:text-emerald-500 rounded-lg transition-all duration-300 shadow-sm focus:outline-none">
                                          <i class="fa-solid fa-clock-rotate-left transition-colors"></i>
                                          
                                          <span class="absolute right-full top-1/2 -translate-y-1/2 mr-2 px-2.5 py-1 text-[10px] font-bold text-white bg-gray-900 dark:bg-black rounded-md opacity-0 group-hover/btn:opacity-100 transition-opacity whitespace-nowrap pointer-events-none z-50 shadow-lg flex items-center">
                                              Restore
                                              <span class="absolute left-full top-1/2 -translate-y-1/2 border-4 border-transparent border-l-gray-900 dark:border-l-black"></span>
                                          </span>
                                      </button>';
                            } else {
                                echo '<span class="text-xs text-gray-400 dark:text-zinc-500 font-medium italic flex items-center h-8"><i class="fa-solid fa-lock text-[10px] mr-1.5"></i> Read Only</span>';
                            }
                        } else {
                            
                            if ($can_edit_this_user) {
                                // EDIT
                                echo '<button onclick="openStaffModal('.$staff['admin_id'].', \''.addslashes($full_name).'\', \''.addslashes($email).'\', \''.$role.'\')" class="relative group/btn flex items-center justify-center w-8 h-8 bg-white dark:bg-zinc-800 border border-gray-200 dark:border-zinc-700 hover:border-blue-300 text-gray-400 hover:text-blue-500 rounded-lg transition-all duration-300 shadow-sm focus:outline-none">
                                          <i class="fa-solid fa-pen-to-square transition-colors"></i>
                                          
                                          <span class="absolute right-full top-1/2 -translate-y-1/2 mr-2 px-2.5 py-1 text-[10px] font-bold text-white bg-gray-900 dark:bg-black rounded-md opacity-0 group-hover/btn:opacity-100 transition-opacity whitespace-nowrap pointer-events-none z-50 shadow-lg flex items-center">
                                              Edit
                                              <span class="absolute left-full top-1/2 -translate-y-1/2 border-4 border-transparent border-l-gray-900 dark:border-l-black"></span>
                                          </span>
                                      </button>';
                                      
                                // RESET PASSWORD
                                if ($current_user_role === 'superadmin' && !$is_current_user) {
                                    echo '<button onclick="openResetPasswordModal('.$staff['admin_id'].', \''.addslashes($full_name).'\')" class="relative group/btn flex items-center justify-center w-8 h-8 bg-white dark:bg-zinc-800 border border-gray-200 dark:border-zinc-700 hover:border-purple-300 text-gray-400 hover:text-purple-500 rounded-lg transition-all duration-300 shadow-sm focus:outline-none">
                                              <i class="fa-solid fa-key transition-colors"></i>
                                              
                                              <span class="absolute right-full top-1/2 -translate-y-1/2 mr-2 px-2.5 py-1 text-[10px] font-bold text-white bg-gray-900 dark:bg-black rounded-md opacity-0 group-hover/btn:opacity-100 transition-opacity whitespace-nowrap pointer-events-none z-50 shadow-lg flex items-center">
                                                  Reset Password
                                                  <span class="absolute left-full top-1/2 -translate-y-1/2 border-4 border-transparent border-l-gray-900 dark:border-l-black"></span>
                                              </span>
                                          </button>';
                                }
                                      
                                if (!$is_current_user) {
                                    // REVOKE / RESTORE STATUS
                                    if ($status === 'active') {
                                        echo '<button onclick="toggleStatus('.$staff['admin_id'].', \'deactivated\')" class="relative group/btn flex items-center justify-center w-8 h-8 bg-white dark:bg-zinc-800 border border-gray-200 dark:border-zinc-700 hover:border-rose-300 text-gray-400 hover:text-rose-500 rounded-lg transition-all duration-300 shadow-sm focus:outline-none">
                                                  <i class="fa-solid fa-ban transition-colors"></i>
                                                  
                                                  <span class="absolute right-full top-1/2 -translate-y-1/2 mr-2 px-2.5 py-1 text-[10px] font-bold text-white bg-gray-900 dark:bg-black rounded-md opacity-0 group-hover/btn:opacity-100 transition-opacity whitespace-nowrap pointer-events-none z-50 shadow-lg flex items-center">
                                                      Revoke Access
                                                      <span class="absolute left-full top-1/2 -translate-y-1/2 border-4 border-transparent border-l-gray-900 dark:border-l-black"></span>
                                                  </span>
                                              </button>';
                                    } else {
                                        echo '<button onclick="toggleStatus('.$staff['admin_id'].', \'active\')" class="relative group/btn flex items-center justify-center w-8 h-8 bg-white dark:bg-zinc-800 border border-gray-200 dark:border-zinc-700 hover:border-emerald-300 text-gray-400 hover:text-emerald-500 rounded-lg transition-all duration-300 shadow-sm focus:outline-none">
                                                  <i class="fa-solid fa-unlock-keyhole transition-colors"></i>
                                                  
                                                  <span class="absolute right-full top-1/2 -translate-y-1/2 mr-2 px-2.5 py-1 text-[10px] font-bold text-white bg-gray-900 dark:bg-black rounded-md opacity-0 group-hover/btn:opacity-100 transition-opacity whitespace-nowrap pointer-events-none z-50 shadow-lg flex items-center">
                                                      Restore Access
                                                      <span class="absolute left-full top-1/2 -translate-y-1/2 border-4 border-transparent border-l-gray-900 dark:border-l-black"></span>
                                                  </span>
                                              </button>';
                                    }
                                    
                                    // ARCHIVE
                                    echo '<button onclick="deleteStaff('.$staff['admin_id'].')" class="relative group/btn flex items-center justify-center w-8 h-8 bg-white dark:bg-zinc-800 border border-gray-200 dark:border-zinc-700 hover:border-amber-300 text-gray-400 hover:text-amber-500 rounded-lg transition-all duration-300 shadow-sm focus:outline-none">
                                              <i class="fa-solid fa-box-archive transition-colors"></i>
                                              
                                              <span class="absolute right-full top-1/2 -translate-y-1/2 mr-2 px-2.5 py-1 text-[10px] font-bold text-white bg-gray-900 dark:bg-black rounded-md opacity-0 group-hover/btn:opacity-100 transition-opacity whitespace-nowrap pointer-events-none z-50 shadow-lg flex items-center">
                                                  Archive
                                                  <span class="absolute left-full top-1/2 -translate-y-1/2 border-4 border-transparent border-l-gray-900 dark:border-l-black"></span>
                                              </span>
                                          </button>';
                                }
                            } else {
                                // Locked state for Admin looking at Superadmin
                                echo '<span class="text-xs text-gray-400 dark:text-zinc-500 font-medium italic flex items-center h-8"><i class="fa-solid fa-lock text-[10px] mr-1.5"></i> Read Only</span>';
                            }
                        }
                        
                        echo '</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
        <div id="pagination-container" class="w-full bg-gray-50/50 dark:bg-zinc-950/30 rounded-b-2xl transition-colors duration-500"></div>
    </div>
</main>

<div id="staff-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm transition-opacity" onclick="closeStaffModal()"></div>
    <div class="relative bg-white dark:bg-zinc-900 rounded-2xl w-full max-w-md shadow-2xl overflow-hidden flex flex-col border border-gray-100 dark:border-zinc-800">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-zinc-800 flex justify-between items-center bg-gray-50/50 dark:bg-zinc-950/30">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white" id="modal_title">Provision New Account</h3>
            <button onclick="closeStaffModal()" class="text-gray-400 hover:text-rose-500 transition-colors focus:outline-none">
                <i class="fa-solid fa-xmark text-xl"></i>
            </button>
        </div>
        <div class="p-6">
            <form id="staff-form" class="space-y-4">
                <input type="hidden" id="staff_id">
                
                <div>
                    <label class="block text-xs font-bold text-gray-600 dark:text-zinc-400 mb-2 uppercase tracking-wide">Full Name *</label>
                    <input type="text" id="staff_name" required class="w-full px-4 py-3 bg-gray-50 dark:bg-zinc-950 border border-gray-200 dark:border-zinc-800 text-gray-900 dark:text-white rounded-xl focus:ring-2 focus:ring-pink-500 outline-none transition-all text-sm font-medium">
                </div>
                
                <div>
                    <label class="block text-xs font-bold text-gray-600 dark:text-zinc-400 mb-2 uppercase tracking-wide">Email Address *</label>
                    <input type="email" id="staff_email" required class="w-full px-4 py-3 bg-gray-50 dark:bg-zinc-950 border border-gray-200 dark:border-zinc-800 text-gray-900 dark:text-white rounded-xl focus:ring-2 focus:ring-pink-500 outline-none transition-all text-sm font-medium">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-600 dark:text-zinc-400 mb-2 uppercase tracking-wide">System Role *</label>
                    <select id="staff_role" class="w-full px-4 py-3 bg-gray-50 dark:bg-zinc-950 border border-gray-200 dark:border-zinc-800 text-gray-900 dark:text-white rounded-xl focus:ring-2 focus:ring-pink-500 outline-none transition-all text-sm font-medium">
                        <option value="staff">Staff</option>
                        <option value="admin">Admin</option>
                        <?php if ($current_user_role === 'superadmin'): ?>
                            <option value="superadmin">Superadmin</option>
                        <?php endif; ?>
                    </select>
                    <p id="role_warning" class="text-[10px] text-gray-500 mt-1 hidden italic">You cannot edit your own system role.</p>
                </div>

                <div id="new_account_notice" class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800/30 rounded-xl p-3 flex gap-3">
                    <i class="fa-solid fa-envelope text-blue-500 mt-0.5"></i>
                    <p class="text-xs text-blue-700 dark:text-blue-300 font-medium">A secure random password will be generated and automatically emailed to the user upon creation.</p>
                </div>

            </form>
        </div>
        <div class="px-6 py-4 border-t border-gray-100 dark:border-zinc-800 bg-gray-50/50 dark:bg-zinc-950/30 flex justify-end gap-3">
            <button onclick="closeStaffModal()" class="px-5 py-2.5 text-sm font-bold text-gray-600 dark:text-zinc-400 hover:bg-gray-100 dark:hover:bg-zinc-800 rounded-xl transition-colors focus:outline-none">Cancel</button>
            <button onclick="saveStaff()" class="bg-pink-600 hover:bg-pink-700 text-white px-5 py-2.5 rounded-xl text-sm font-bold transition-all shadow-md focus:outline-none">Save Account</button>
        </div>
    </div>
</div>

<div id="reset-password-modal" class="fixed inset-0 z-[80] hidden flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm transition-opacity" onclick="closeResetPasswordModal()"></div>
    <div class="relative bg-white dark:bg-zinc-900 rounded-2xl w-full max-w-sm shadow-2xl overflow-hidden flex flex-col border border-purple-200 dark:border-purple-800">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-zinc-800 flex items-center gap-3 bg-purple-50/50 dark:bg-purple-900/10">
            <div class="h-8 w-8 rounded-full bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 flex items-center justify-center"><i class="fa-solid fa-shield-halved"></i></div>
            <div>
                <h3 class="text-sm font-bold text-gray-900 dark:text-white">Security Verification</h3>
                <p class="text-[10px] text-gray-500 uppercase tracking-widest font-bold">Resetting: <span id="reset_target_name" class="text-purple-600">Name</span></p>
            </div>
        </div>
        <div class="p-6">
            <input type="hidden" id="reset_target_id">
            <p class="text-xs text-gray-600 dark:text-zinc-400 mb-4 leading-relaxed">To generate a new random password for this user, please verify your identity by entering your <strong>Superadmin password</strong>.</p>
            
            <input type="password" id="verify_admin_password" autocomplete="new-password" placeholder="Enter your password..." class="w-full px-4 py-3 bg-gray-50 dark:bg-zinc-950 border border-gray-200 dark:border-zinc-800 text-gray-900 dark:text-white rounded-xl focus:ring-2 focus:ring-purple-500 outline-none transition-all text-sm font-medium mb-2">
        </div>
        <div class="px-6 py-4 border-t border-gray-100 dark:border-zinc-800 bg-gray-50/50 dark:bg-zinc-950/30 flex justify-end gap-3">
            <button onclick="closeResetPasswordModal()" class="px-5 py-2.5 text-sm font-bold text-gray-600 dark:text-zinc-400 hover:bg-gray-100 dark:hover:bg-zinc-800 rounded-xl transition-colors focus:outline-none">Cancel</button>
            <button onclick="confirmResetPassword()" class="bg-purple-600 hover:bg-purple-700 text-white px-5 py-2.5 rounded-xl text-sm font-bold transition-all shadow-md focus:outline-none">Verify & Reset</button>
        </div>
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

<script>
    // 🚨 GLOBALS FOR ROLE CHECKING
    const CURRENT_USER_ID = "<?php echo $current_user_id; ?>";
    const CURRENT_USER_ROLE = "<?php echo $current_user_role; ?>";

    // --- GLOBAL MODAL OVERRIDES (Alert & Confirm) ---
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
                icon.className = "fa-solid fa-ban"; 
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

    window.alert = customAlert;

    // --- Search & Pagination Logic ---
    const searchInput = document.getElementById('search-input');
    const tbody = document.getElementById('staff-tbody');
    
    if (tbody && searchInput) {
        const allRows = Array.from(tbody.querySelectorAll('tr.staff-row'));
        const paginationContainer = document.getElementById('pagination-container');
        const colspanCount = 5;
        
        let currentPage = 1;
        const rowsPerPage = 15;

        function updateTable() {
            const searchTerm = searchInput.value.toLowerCase();
            const filteredRows = allRows.filter(row => {
                const text = row.innerText.toLowerCase();
                return text.includes(searchTerm);
            });

            const totalItems = filteredRows.length;
            const totalPages = Math.ceil(totalItems / rowsPerPage) || 1;
            if (currentPage > totalPages) currentPage = 1;

            const startIndex = (currentPage - 1) * rowsPerPage;
            const endIndex = startIndex + rowsPerPage;

            allRows.forEach(row => row.style.display = 'none');
            filteredRows.slice(startIndex, endIndex).forEach(row => row.style.display = '');

            const existingEmptyRow = document.getElementById('js-empty-state');
            const phpEmpty = document.getElementById('php-empty-state');

            if (totalItems === 0) {
                if (phpEmpty && searchTerm === '') {
                    phpEmpty.style.display = '';
                    if (existingEmptyRow) existingEmptyRow.style.display = 'none';
                } else {
                    if (phpEmpty) phpEmpty.style.display = 'none';
                    if (!existingEmptyRow) {
                        tbody.insertAdjacentHTML('beforeend', `<tr id="js-empty-state"><td colspan="${colspanCount}" class="px-6 py-8 text-center text-gray-500 font-medium">No accounts found matching your search.</td></tr>`);
                    } else {
                        existingEmptyRow.style.display = '';
                    }
                }
            } else {
                if (existingEmptyRow) existingEmptyRow.style.display = 'none';
                if (phpEmpty) phpEmpty.style.display = 'none';
            }

            renderPagination(totalItems, totalPages);
        }

        function renderPagination(totalItems, totalPages) {
            if (totalItems === 0) {
                paginationContainer.innerHTML = '';
                return;
            }

            let html = `
                <div class="flex flex-col sm:flex-row items-center justify-between gap-4 w-full px-6 py-4 border-t border-gray-100 dark:border-zinc-800">
                    <div class="text-xs font-semibold text-gray-500 dark:text-zinc-400">
                        Showing <span class="font-bold text-gray-900 dark:text-white">${((currentPage - 1) * rowsPerPage) + 1}</span> to <span class="font-bold text-gray-900 dark:text-white">${Math.min(currentPage * rowsPerPage, totalItems)}</span> of <span class="font-bold text-gray-900 dark:text-white">${totalItems}</span> entries
                    </div>
                    <div class="flex gap-1">
                        <button onclick="changePage(${currentPage - 1})" class="px-3 py-1.5 text-xs font-bold rounded-lg transition-colors ${currentPage === 1 ? 'text-gray-400 dark:text-zinc-600 cursor-not-allowed' : 'text-gray-700 dark:text-zinc-300 hover:bg-gray-200 dark:hover:bg-zinc-800'}" ${currentPage === 1 ? 'disabled' : ''}>Prev</button>
            `;

            for (let i = 1; i <= totalPages; i++) {
                if (totalPages > 7) {
                     if (i === 1 || i === totalPages || (i >= currentPage - 1 && i <= currentPage + 1)) {
                         html += makePageBtn(i);
                     } else if (i === currentPage - 2 || i === currentPage + 2) {
                         html += `<span class="px-2 py-1 text-xs text-gray-400 dark:text-zinc-600">...</span>`;
                     }
                } else {
                     html += makePageBtn(i);
                }
            }

            html += `
                        <button onclick="changePage(${currentPage + 1})" class="px-3 py-1.5 text-xs font-bold rounded-lg transition-colors ${currentPage === totalPages ? 'text-gray-400 dark:text-zinc-600 cursor-not-allowed' : 'text-gray-700 dark:text-zinc-300 hover:bg-gray-200 dark:hover:bg-zinc-800'}" ${currentPage === totalPages ? 'disabled' : ''}>Next</button>
                    </div>
                </div>
            `;
            paginationContainer.innerHTML = html;
        }

        function makePageBtn(i) {
            const activeClass = i === currentPage 
                ? 'bg-pink-600 text-white shadow-md shadow-pink-600/20' 
                : 'text-gray-700 dark:text-zinc-300 hover:bg-gray-200 dark:hover:bg-zinc-800';
            return `<button onclick="changePage(${i})" class="px-3 py-1.5 text-xs font-bold rounded-lg transition-colors ${activeClass}">${i}</button>`;
        }

        window.changePage = function(page) {
            currentPage = page;
            updateTable();
        }

        searchInput.addEventListener('input', () => {
            currentPage = 1; 
            updateTable();
        });

        updateTable();
    }

    // --- STAFF MODAL LOGIC ---
    function openStaffModal(id = '', name = '', email = '', role = 'staff') {
        document.getElementById('staff_id').value = id;
        document.getElementById('staff_name').value = name;
        document.getElementById('staff_email').value = email;
        document.getElementById('staff_role').value = role;
        
        const roleSelect = document.getElementById('staff_role');
        const roleWarning = document.getElementById('role_warning');
        
        // 🚨 FIX: Changed === to == so it matches regardless of Number vs String
        if (id !== '' && id == CURRENT_USER_ID) {
            roleSelect.disabled = true;
            roleSelect.classList.add('opacity-60', 'cursor-not-allowed');
            roleWarning.classList.remove('hidden');
        } else {
            roleSelect.disabled = false;
            roleSelect.classList.remove('opacity-60', 'cursor-not-allowed');
            roleWarning.classList.add('hidden');
        }
        
        if (id) {
            document.getElementById('modal_title').textContent = "Edit Staff Account";
            document.getElementById('new_account_notice').classList.add('hidden');
        } else {
            document.getElementById('modal_title').textContent = "Provision New Account";
            document.getElementById('new_account_notice').classList.remove('hidden');
        }
        
        document.getElementById('staff-modal').classList.remove('hidden');
    }

    function closeStaffModal() {
        document.getElementById('staff-modal').classList.add('hidden');
    }

    async function saveStaff() {
        const roleSelect = document.getElementById('staff_role');
        const payload = {
            admin_id: document.getElementById('staff_id').value,
            full_name: document.getElementById('staff_name').value,
            email: document.getElementById('staff_email').value,
            // 🚨 Use the current session role if they are editing themselves (since dropdown is disabled)
            role: roleSelect.disabled ? CURRENT_USER_ROLE : roleSelect.value 
        };

        if (!payload.full_name || !payload.email) return customAlert("Please fill all required fields.", "Missing Fields", "error");

        try {
            const res = await fetch('actions/save_staff.php', {
                method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload)
            });
            const data = await res.json();
            
            if (data.status === 'success') {
                closeStaffModal();
                
                if (data.generated_password) {
                    customAlert(`Account created successfully.\n\nSimulated Email Sent!\nTemporary Password: ${data.generated_password}`, "Account Provisioned", "success");
                } else {
                    customAlert("Account updated successfully.", "Success", "success");
                }
                
                setTimeout(() => window.location.reload(), 3000);
            } else {
                customAlert("Error: " + data.message, "Error", "error");
            }
        } catch (e) { customAlert("Network Error", "Error", "error"); }
    }

    // --- SECURE PASSWORD RESET LOGIC ---
    function openResetPasswordModal(id, name) {
        document.getElementById('reset_target_id').value = id;
        document.getElementById('reset_target_name').textContent = name;
        document.getElementById('verify_admin_password').value = '';
        document.getElementById('reset-password-modal').classList.remove('hidden');
    }

    function closeResetPasswordModal() {
        document.getElementById('reset-password-modal').classList.add('hidden');
    }

    async function confirmResetPassword() {
        const payload = {
            target_admin_id: document.getElementById('reset_target_id').value,
            superadmin_password: document.getElementById('verify_admin_password').value
        };

        if (!payload.superadmin_password) return customAlert("You must enter your password to verify this action.", "Verification Failed", "error");

        const btn = document.querySelector('#reset-password-modal button.bg-purple-600');
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i>Sending...';
        btn.disabled = true;

        try {
            const res = await fetch('actions/reset_password.php', {
                method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload)
            });
            const data = await res.json();
            
            if (data.status === 'success') {
                closeResetPasswordModal();
                customAlert(data.message, "Password Reset & Emailed", "success");
            } else {
                customAlert(data.message, "Verification Failed", "error");
            }
        } catch (e) { 
            customAlert("Network Error", "Error", "error"); 
        } finally {
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    }

    // --- STATUS TOGGLES ---
    async function toggleStatus(id, newStatus) {
        const actionText = newStatus === 'active' ? 'restore access for' : 'revoke access from';
        const type = newStatus === 'active' ? 'info' : 'danger';
        const btnText = newStatus === 'active' ? 'Yes, Restore' : 'Yes, Revoke';
        
        const isConfirmed = await customConfirm(`Are you sure you want to ${actionText} this user?`, "Change Status", btnText, type);
        if (!isConfirmed) return;
        
        try {
            const res = await fetch('actions/toggle_staff_status.php', {
                method: 'POST', headers: { 'Content-Type': 'application/json' }, 
                body: JSON.stringify({ admin_id: id, new_status: newStatus })
            });
            const data = await res.json();
            if (data.status === 'success') {
                customAlert("Status updated successfully.", "Success", "success");
                setTimeout(() => window.location.reload(), 1500);
            } else {
                customAlert("Error: " + data.message, "Error", "error");
            }
        } catch (e) { customAlert("Network Error", "Error", "error"); }
    }

    async function deleteStaff(id) {
        const isConfirmed = await customConfirm("Are you sure you want to permanently archive this account? They will be removed from this list entirely.", "Archive Account", "Yes, Archive", "warning");
        if (!isConfirmed) return;
        
        try {
            const res = await fetch('actions/delete_staff.php', {
                method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ admin_id: id })
            });
            const data = await res.json();
            if (data.status === 'success') {
                customAlert("Account archived.", "Success", "success");
                setTimeout(() => window.location.reload(), 1500);
            } else customAlert("Error: " + data.message, "Error", "error");
        } catch (e) { customAlert("Network Error", "Error", "error"); }
    }
    
    async function restoreStaff(id) {
        const isConfirmed = await customConfirm("Are you sure you want to unarchive this account? They will be restored to the system.", "Restore Account", "Yes, Restore", "info");
        if (!isConfirmed) return;
        
        try {
            const res = await fetch('actions/restore_staff.php', {
                method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ admin_id: id })
            });
            const data = await res.json();
            if (data.status === 'success') {
                customAlert("Account restored.", "Success", "success");
                setTimeout(() => window.location.reload(), 1500);
            } else customAlert("Error: " + data.message, "Error", "error");
        } catch (e) { customAlert("Network Error", "Error", "error"); }
    }
</script>

<?php include 'includes/footer.php'; ?>