<?php
/**
 * Admin Users Management
 */

require_once dirname(__DIR__) . '/config/config.php';
requireRole('admin');

$page_title = 'Users';

$userObj = new User();

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = ['success' => false, 'message' => 'Invalid action'];

    switch ($_POST['action'] ?? '') {
        case 'create':
            $data = [
                'name' => sanitize($_POST['name']),
                'email' => sanitize($_POST['email']),
                'phone' => sanitize($_POST['phone']),
                'password' => $_POST['password'],
                'role' => sanitize($_POST['role']),
                'status' => sanitize($_POST['status'] ?? 'active'),
            ];
            $response = $userObj->create($data);
            break;

        case 'update':
            $data = [
                'name' => sanitize($_POST['name']),
                'email' => sanitize($_POST['email']),
                'phone' => sanitize($_POST['phone']),
                'role' => sanitize($_POST['role']),
                'status' => sanitize($_POST['status'] ?? 'active'),
            ];
            if (!empty($_POST['password'])) {
                $data['password'] = $_POST['password'];
            }
            $response = $userObj->update($_POST['user_id'], $data);
            break;

        case 'delete':
            $response = $userObj->delete($_POST['user_id']);
            break;
    }

    if ($response['success']) {
        setFlash('success', $response['message'] ?? 'Operation successful');
    } else {
        setFlash('error', $response['message'] ?? 'Operation failed');
    }

    redirect(SITE_URL . '/admin/users.php');
}

require_once INCLUDES_PATH . '/header.php';

$role = $_GET['role'] ?? '';
$statusFilter = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';

$users = $userObj->getAll($role ?: null, $statusFilter ?: null, $search ?: null);
?>

<!-- Page Header -->
<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Users</h1>
        <p class="text-gray-500">Manage system users</p>
    </div>
    <button onclick="openNewUserModal()" class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-amber-500 to-orange-600 text-white font-medium rounded-xl hover:from-amber-600 hover:to-orange-700 transition-all shadow-lg shadow-amber-200">
        <i class="fas fa-plus"></i>
        <span>Add User</span>
    </button>
</div>

<!-- Filters -->
<div class="bg-white rounded-xl border border-gray-100 p-4 mb-6 shadow-sm">
    <form method="GET" action="" class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search users..." class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
        </div>
        <div>
            <select name="role" class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                <option value="">All Roles</option>
                <option value="admin" <?php echo $role === 'admin' ? 'selected' : ''; ?>>Admin</option>
                <option value="staff" <?php echo $role === 'staff' ? 'selected' : ''; ?>>Staff</option>
                <option value="rider" <?php echo $role === 'rider' ? 'selected' : ''; ?>>Rider</option>
            </select>
        </div>
        <div>
            <select name="status" class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                <option value="">All Status</option>
                <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Active</option>
                <option value="inactive" <?php echo $statusFilter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
            </select>
        </div>
        <div class="flex gap-2">
            <button type="submit" class="flex-1 px-4 py-2 bg-gray-800 text-white rounded-lg hover:bg-gray-700">Filter</button>
            <a href="users.php" class="px-4 py-2 bg-gray-100 text-gray-600 rounded-lg hover:bg-gray-200">Clear</a>
        </div>
    </form>
</div>

<!-- Users Table -->
<div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
    <!-- Mobile Card View -->
    <div class="md:hidden space-y-4 p-4">
        <?php if (empty($users)): ?>
        <div class="text-center py-12 text-gray-400">
            <i class="fas fa-users text-4xl mb-3"></i>
            <p>No users found</p>
        </div>
        <?php else: ?>
            <?php foreach ($users as $u): ?>
            <div class="bg-gray-50 rounded-xl p-4 space-y-3">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 <?php echo $u['role'] === 'admin' ? 'bg-amber-100 text-amber-600' : ($u['role'] === 'rider' ? 'bg-purple-100 text-purple-600' : 'bg-blue-100 text-blue-600'); ?> rounded-full flex items-center justify-center font-bold text-lg">
                        <?php echo strtoupper(substr($u['name'], 0, 1)); ?>
                    </div>
                    <div class="flex-1">
                        <p class="font-bold text-gray-800"><?php echo htmlspecialchars($u['name']); ?></p>
                        <p class="text-sm text-gray-400"><?php echo htmlspecialchars($u['email']); ?></p>
                    </div>
                    <div>
                        <span class="px-3 py-1 text-xs font-medium rounded-full <?php echo $u['role'] === 'admin' ? 'bg-amber-100 text-amber-700' : ($u['role'] === 'rider' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700'); ?>">
                            <?php echo ucfirst($u['role']); ?>
                        </span>
                    </div>
                </div>
                <div class="flex items-center justify-between text-sm">
                    <span class="text-gray-500">Status:</span>
                    <?php echo getStatusBadge($u['status']); ?>
                </div>
                <div class="flex items-center justify-between text-sm">
                    <span class="text-gray-500">Joined:</span>
                    <span class="text-gray-700"><?php echo formatDate($u['created_at']); ?></span>
                </div>
                <div class="flex items-center justify-end gap-2 pt-2 border-t border-gray-200">
                    <button onclick="editUser(<?php echo $u['id']; ?>, '<?php echo htmlspecialchars($u['name'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($u['email'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($u['phone'], ENT_QUOTES); ?>', '<?php echo $u['role']; ?>', '<?php echo $u['status']; ?>')" class="px-4 py-2 text-blue-600 bg-blue-50 rounded-lg text-sm font-medium">
                        <i class="fas fa-edit mr-1"></i>Edit
                    </button>
                    <?php if ($u['id'] != getCurrentUser()['id']): ?>
                    <button onclick="deleteUser(<?php echo $u['id']; ?>, '<?php echo htmlspecialchars($u['name'], ENT_QUOTES); ?>')" class="px-4 py-2 text-red-600 bg-red-50 rounded-lg text-sm font-medium">
                        <i class="fas fa-trash mr-1"></i>Delete
                    </button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Desktop Table View -->
    <div class="hidden md:block overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-100">
                <tr>
                    <th class="text-left px-4 py-3 text-sm font-semibold text-gray-600">User</th>
                    <th class="text-left px-4 py-3 text-sm font-semibold text-gray-600">Role</th>
                    <th class="text-left px-4 py-3 text-sm font-semibold text-gray-600">Status</th>
                    <th class="text-left px-4 py-3 text-sm font-semibold text-gray-600">Created</th>
                    <th class="text-right px-4 py-3 text-sm font-semibold text-gray-600">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                <?php if (empty($users)): ?>
                <tr>
                    <td colspan="5" class="px-4 py-12 text-center text-gray-400">
                        <i class="fas fa-users text-4xl mb-3"></i>
                        <p>No users found</p>
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($users as $u): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 <?php echo $u['role'] === 'admin' ? 'bg-amber-100 text-amber-600' : ($u['role'] === 'rider' ? 'bg-purple-100 text-purple-600' : 'bg-blue-100 text-blue-600'); ?> rounded-full flex items-center justify-center font-semibold">
                                    <?php echo strtoupper(substr($u['name'], 0, 1)); ?>
                                </div>
                                <div>
                                    <p class="font-medium text-gray-800"><?php echo htmlspecialchars($u['name']); ?></p>
                                    <p class="text-sm text-gray-400"><?php echo htmlspecialchars($u['email']); ?></p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <span class="px-3 py-1 text-xs font-medium rounded-full <?php echo $u['role'] === 'admin' ? 'bg-amber-100 text-amber-700' : ($u['role'] === 'rider' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700'); ?>">
                                <?php echo ucfirst($u['role']); ?>
                            </span>
                        </td>
                        <td class="px-4 py-3"><?php echo getStatusBadge($u['status']); ?></td>
                        <td class="px-4 py-3">
                            <p class="text-sm text-gray-600"><?php echo formatDate($u['created_at']); ?></p>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-2">
                                <button onclick="editUser(<?php echo $u['id']; ?>, '<?php echo htmlspecialchars($u['name'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($u['email'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($u['phone'], ENT_QUOTES); ?>', '<?php echo $u['role']; ?>', '<?php echo $u['status']; ?>')" class="p-2 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <?php if ($u['id'] != getCurrentUser()['id']): ?>
                                <button onclick="deleteUser(<?php echo $u['id']; ?>, '<?php echo htmlspecialchars($u['name'], ENT_QUOTES); ?>')" class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- New User Modal -->
<div id="newUserModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl w-full max-w-lg">
        <div class="flex items-center justify-between p-5 border-b border-gray-100">
            <h2 class="text-lg font-semibold text-gray-800">Add New User</h2>
            <button onclick="closeModal('newUserModal')" class="p-2 text-gray-400 hover:bg-gray-100 rounded-lg">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form id="newUserForm" method="POST" action="">
            <input type="hidden" name="action" value="create">
            <div class="p-5 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Full Name *</label>
                    <input type="text" name="name" required class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Email *</label>
                    <input type="email" name="email" required class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Phone Number *</label>
                    <input type="text" name="phone" required class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Password *</label>
                    <input type="password" name="password" required class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Role *</label>
                        <select name="role" required class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                            <option value="staff">Staff</option>
                            <option value="rider">Rider</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                        <select name="status" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="flex items-center justify-end gap-3 p-5 border-t border-gray-100 bg-gray-50">
                <button type="button" onclick="closeModal('newUserModal')" class="px-5 py-2.5 border border-gray-200 rounded-xl hover:bg-gray-100">Cancel</button>
                <button type="submit" class="px-5 py-2.5 bg-gradient-to-r from-amber-500 to-orange-600 text-white rounded-xl hover:from-amber-600 hover:to-orange-700">Add User</button>
            </div>
        </form>
    </div>
</div>

<script>
function openNewUserModal() {
    document.getElementById('newUserModal').classList.remove('hidden');
}

function closeModal(id) {
    document.getElementById(id).classList.add('hidden');
}

function editUser(id, name, email, phone, role, status) {
    // For simplicity, redirecting to a separate edit page would be better
    // But for this implementation, we'll just show an alert
    alert('Edit functionality: Implement edit form for user ' + name);
}

function deleteUser(id, name) {
    if (!confirm('Are you sure you want to delete user "' + name + '"? This action cannot be undone.')) return;

    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="user_id" value="${id}">
    `;
    document.body.appendChild(form);
    form.submit();
}
</script>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
