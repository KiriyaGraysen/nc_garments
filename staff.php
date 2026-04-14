<?php
require_once('config/database.php');

// SECURITY KICK-OUT: Only let Superadmins manage staff
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

$page_title = "Staff Access | NC Garments";

// 1. Determine Tab View and Dynamic SQL
$view = $_GET['view'] ?? 'all';

if ($view === 'archived') {
    // If viewing archived, ONLY show archived accounts
    $where_sql = "WHERE is_archived = 1";
} else {
    // Otherwise, only show active accounts and filter by status
    $where_sql = "WHERE is_archived = 0";
    if ($view === 'active') {
        $where_sql .= " AND status = 'active'";
    } elseif ($view === 'revoked') {
        $where_sql .= " AND status = 'deactivated'";
    }
}

// 2. Fetch Data
$stmt = $conn->prepare("
    SELECT admin_id, full_name, email, username, role, status, last_login
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
            <i class="fa-solid fa-user-shield"></i> Provision New Account
        </button>
        <?php endif; ?>
    </div>

    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
        
        <div class="relative w-full md:w-96 group">
            <i class="fa-solid fa-search absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400 group-focus-within:text-pink-600 transition-colors duration-500"></i>
            <input type="text" placeholder="Search by staff name or username..." 
                   class="w-full pl-11 pr-4 py-3 border border-gray-200 dark:border-zinc-800 rounded-xl bg-white dark:bg-zinc-900/50 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-zinc-500 focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent transition-colors duration-500 shadow-sm">
        </div>
        
        <?php
            $active_tab = "bg-white dark:bg-zinc-800 text-pink-600 dark:text-pink-500 shadow-sm";
            $inactive_tab = "text-gray-500 dark:text-zinc-400 hover:text-gray-900 hover:dark:text-white";
        ?>
        
        <div class="flex bg-gray-100 dark:bg-zinc-900/80 p-1 rounded-lg w-full md:w-auto overflow-x-auto transition-colors duration-500 border border-gray-200 dark:border-zinc-800">
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
                    
                    <?php
                    if ($staff_result->num_rows === 0) {
                        echo '<tr><td colspan="5" class="px-6 py-8 text-center text-gray-500">No accounts found in this view.</td></tr>';
                    }

                    while ($staff = $staff_result->fetch_assoc()) {
                        $raw_login_date = $staff['last_login'];
                        $formatted_login = format_last_login($raw_login_date);
                        
                        $full_name = htmlspecialchars($staff['full_name']);
                        $user_initials = strtoupper(substr($full_name, 0, 2));
                        $user = htmlspecialchars($staff['username']);
                        $email = htmlspecialchars($staff['email']);
                        $status = htmlspecialchars($staff['status']);
                        $role = htmlspecialchars($staff['role']);
                        
                        // Safety check: Is this row the current logged-in user?
                        $is_current_user = ($staff['admin_id'] == $_SESSION['admin_id']);
                        
                        // If it's archived, grayscale the entire row
                        $row_class = ($view === 'archived' || $status === 'deactivated') ? 'opacity-60 grayscale-[50%]' : '';
                        
                        echo '
                            <tr class="hover:bg-gray-50/80 dark:hover:bg-zinc-800/30 transition-colors group ' . $row_class . '">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="h-10 w-10 rounded-full bg-pink-600 text-white flex items-center justify-center font-extrabold text-sm shadow-md shadow-pink-600/20">
                                            ' . $user_initials . '
                                        </div>
                                        <div>
                                            <div class="font-bold text-gray-900 dark:text-white group-hover:text-pink-600 transition-colors">' . $full_name . ' ' . ($is_current_user ? '<span class="text-[10px] text-pink-500">(You)</span>' : '') . '</div>
                                            <div class="text-xs font-bold tracking-wider text-gray-400 dark:text-zinc-500 mt-0.5">@' . $user . '</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="' . ($role === 'admin' ? 'bg-pink-50 text-pink-600 dark:bg-pink-500/10 dark:text-pink-400 border border-pink-200 dark:border-pink-500/20' : 'bg-gray-100 text-gray-600 dark:bg-zinc-800 dark:text-zinc-400 border-gray-200 dark:border-zinc-700') . ' text-[10px] font-extrabold px-2.5 py-1 rounded-md uppercase tracking-wider flex items-center w-max gap-1.5">
                                        <i class="fa-solid ' . ($role === 'admin' ? 'fa-crown' : 'fa-user') . ' text-[10px]"></i>' . $role . '
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
                                <td class="px-6 py-4 text-right text-sm font-medium flex justify-end gap-2">';
                        
                        if ($view === 'archived') {
                            // If viewing archived, only show the Unarchive button
                            echo '<button onclick="restoreStaff('.$staff['admin_id'].')" class="bg-white dark:bg-zinc-800 border border-gray-200 dark:border-zinc-700 text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 hover:border-emerald-200 px-3 py-1.5 rounded-lg transition-all text-xs font-bold shadow-sm">
                                    <i class="fa-solid fa-trash-can-arrow-up mr-1"></i> Unarchive
                                  </button>';
                        } else {
                            // Standard Actions
                            echo '<button onclick="openStaffModal('.$staff['admin_id'].', \''.addslashes($full_name).'\', \''.addslashes($email).'\', \''.addslashes($user).'\', \''.$role.'\')" class="bg-white dark:bg-zinc-800 border border-gray-200 dark:border-zinc-700 text-gray-700 dark:text-zinc-300 hover:text-pink-600 px-3 py-1.5 rounded-lg transition-all text-xs font-bold shadow-sm">
                                    <i class="fa-solid fa-pen-to-square mr-1"></i> Edit
                                  </button>';
                                  
                            if (!$is_current_user) {
                                if ($status === 'active') {
                                    echo '<button onclick="toggleStatus('.$staff['admin_id'].', \'deactivated\')" class="bg-white dark:bg-zinc-800 border border-gray-200 dark:border-zinc-700 text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-900/20 hover:border-rose-200 px-3 py-1.5 rounded-lg transition-all text-xs font-bold shadow-sm">
                                            <i class="fa-solid fa-ban mr-1"></i> Revoke
                                          </button>';
                                } else {
                                    echo '<button onclick="toggleStatus('.$staff['admin_id'].', \'active\')" class="bg-white dark:bg-zinc-800 border border-gray-200 dark:border-zinc-700 text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 hover:border-emerald-200 px-3 py-1.5 rounded-lg transition-all text-xs font-bold shadow-sm">
                                            <i class="fa-solid fa-unlock-keyhole mr-1"></i> Restore
                                          </button>';
                                }
                                
                                echo '<button onclick="deleteStaff('.$staff['admin_id'].')" class="text-gray-400 hover:text-rose-600 focus:outline-none p-1 ml-1" title="Archive Account">
                                        <i class="fa-solid fa-trash"></i>
                                      </button>';
                            }
                        }
                        
                        echo '</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
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

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-600 dark:text-zinc-400 mb-2 uppercase tracking-wide">Username *</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 font-bold">@</span>
                            <input type="text" id="staff_username" required class="w-full pl-8 pr-4 py-3 bg-gray-50 dark:bg-zinc-950 border border-gray-200 dark:border-zinc-800 text-gray-900 dark:text-white rounded-xl focus:ring-2 focus:ring-pink-500 outline-none transition-all text-sm font-medium">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-600 dark:text-zinc-400 mb-2 uppercase tracking-wide">System Role *</label>
                        <select id="staff_role" class="w-full px-4 py-3 bg-gray-50 dark:bg-zinc-950 border border-gray-200 dark:border-zinc-800 text-gray-900 dark:text-white rounded-xl focus:ring-2 focus:ring-pink-500 outline-none transition-all text-sm font-medium">
                            <option value="staff">Staff</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-600 dark:text-zinc-400 mb-2 uppercase tracking-wide">Password <span id="pass_req" class="text-rose-500">*</span></label>
                    <input type="password" id="staff_password" placeholder="Leave blank to keep current password" class="w-full px-4 py-3 bg-gray-50 dark:bg-zinc-950 border border-gray-200 dark:border-zinc-800 text-gray-900 dark:text-white rounded-xl focus:ring-2 focus:ring-pink-500 outline-none transition-all text-sm font-medium">
                    <p class="text-[10px] text-gray-400 mt-1" id="pass_hint">Required for new accounts. Minimum 6 characters.</p>
                </div>
            </form>
        </div>
        <div class="px-6 py-4 border-t border-gray-100 dark:border-zinc-800 bg-gray-50/50 dark:bg-zinc-950/30 flex justify-end gap-3">
            <button onclick="closeStaffModal()" class="px-5 py-2.5 text-sm font-bold text-gray-600 dark:text-zinc-400 hover:bg-gray-100 dark:hover:bg-zinc-800 rounded-xl transition-colors focus:outline-none">Cancel</button>
            <button onclick="saveStaff()" class="bg-pink-600 hover:bg-pink-700 text-white px-5 py-2.5 rounded-xl text-sm font-bold transition-all shadow-md focus:outline-none">Save Account</button>
        </div>
    </div>
</div>

<script>
    function openStaffModal(id = '', name = '', email = '', username = '', role = 'staff') {
        document.getElementById('staff_id').value = id;
        document.getElementById('staff_name').value = name;
        document.getElementById('staff_email').value = email;
        document.getElementById('staff_username').value = username;
        document.getElementById('staff_role').value = role;
        document.getElementById('staff_password').value = '';
        
        if (id) {
            document.getElementById('modal_title').textContent = "Edit Staff Account";
            document.getElementById('pass_req').classList.add('hidden');
            document.getElementById('staff_password').placeholder = "Leave blank to keep current password";
        } else {
            document.getElementById('modal_title').textContent = "Provision New Account";
            document.getElementById('pass_req').classList.remove('hidden');
            document.getElementById('staff_password').placeholder = "Enter initial password";
        }
        
        document.getElementById('staff-modal').classList.remove('hidden');
    }

    function closeStaffModal() {
        document.getElementById('staff-modal').classList.add('hidden');
    }

    async function saveStaff() {
        const payload = {
            admin_id: document.getElementById('staff_id').value,
            full_name: document.getElementById('staff_name').value,
            email: document.getElementById('staff_email').value,
            username: document.getElementById('staff_username').value,
            role: document.getElementById('staff_role').value,
            password: document.getElementById('staff_password').value
        };

        if (!payload.full_name || !payload.email || !payload.username) return alert("Please fill all required fields.");
        if (!payload.admin_id && !payload.password) return alert("Password is required for new accounts.");

        try {
            const res = await fetch('actions/save_staff.php', {
                method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload)
            });
            const data = await res.json();
            if (data.status === 'success') window.location.reload();
            else alert("Error: " + data.message);
        } catch (e) { alert("Network Error"); }
    }

    async function toggleStatus(id, newStatus) {
        const actionText = newStatus === 'active' ? 'restore access for' : 'revoke access from';
        if (!confirm(`Are you sure you want to ${actionText} this user?`)) return;
        
        try {
            const res = await fetch('actions/toggle_staff_status.php', {
                method: 'POST', headers: { 'Content-Type': 'application/json' }, 
                body: JSON.stringify({ admin_id: id, new_status: newStatus })
            });
            const data = await res.json();
            if (data.status === 'success') window.location.reload();
            else alert("Error: " + data.message);
        } catch (e) { alert("Network Error"); }
    }

    async function deleteStaff(id) {
        if (!confirm("Are you sure you want to permanently archive this account? They will be removed from this list entirely.")) return;
        
        try {
            const res = await fetch('actions/delete_staff.php', {
                method: 'POST', headers: { 'Content-Type': 'application/json' }, 
                body: JSON.stringify({ admin_id: id })
            });
            const data = await res.json();
            if (data.status === 'success') window.location.reload();
            else alert("Error: " + data.message);
        } catch (e) { alert("Network Error"); }
    }
    
    // NEW: Restore Archived Staff
    async function restoreStaff(id) {
        if (!confirm("Are you sure you want to unarchive this account? They will be restored to the system.")) return;
        
        try {
            const res = await fetch('actions/restore_staff.php', {
                method: 'POST', headers: { 'Content-Type': 'application/json' }, 
                body: JSON.stringify({ admin_id: id })
            });
            const data = await res.json();
            if (data.status === 'success') window.location.reload();
            else alert("Error: " + data.message);
        } catch (e) { alert("Network Error"); }
    }
</script>

<?php include 'includes/footer.php'; ?>