<?php
/**
 * Admin Customers Management (Mini CRM)
 */

require_once dirname(__DIR__) . '/config/config.php';
requireRole('admin');

$page_title = 'Customers';

$customer = new Customer();

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = ['success' => false, 'message' => 'Invalid action'];

    switch ($_POST['action'] ?? '') {
        case 'create':
            $data = [
                'name' => sanitize($_POST['name']),
                'phone' => sanitize($_POST['phone']),
                'email' => sanitize($_POST['email'] ?? ''),
                'address' => sanitize($_POST['address'] ?? ''),
                'preferences' => $_POST['preferences'] ?? null,
                'notes' => sanitize($_POST['notes'] ?? ''),
            ];
            $response = $customer->create($data);
            break;

        case 'update':
            $data = [
                'name' => sanitize($_POST['name']),
                'phone' => sanitize($_POST['phone']),
                'email' => sanitize($_POST['email'] ?? ''),
                'address' => sanitize($_POST['address'] ?? ''),
                'notes' => sanitize($_POST['notes'] ?? ''),
            ];

            // Handle preferences
            if (isset($_POST['spicy_level'])) {
                $data['preferences'] = [
                    'spicy_level' => sanitize($_POST['spicy_level']),
                    'special_instructions' => sanitize($_POST['special_instructions'] ?? ''),
                ];
            }

            $response = $customer->update($_POST['customer_id'], $data);
            break;

        case 'delete':
            $response = $customer->delete($_POST['customer_id']);
            break;
    }

    if ($response['success']) {
        setFlash('success', $response['message'] ?? 'Operation successful');
    } else {
        setFlash('error', $response['message'] ?? 'Operation failed');
    }

    redirect(SITE_URL . '/admin/customers.php');
}

require_once INCLUDES_PATH . '/header.php';

// Get parameters
$search = $_GET['search'] ?? '';
$page = (int)($_GET['page'] ?? 1);

$result = $customer->getAll($page, 20, $search ?: null);
$customers = $result['customers'];
$pagination = $result['pagination'];
?>

<!-- Page Header -->
<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Customers</h1>
        <p class="text-gray-500">Manage customer database</p>
    </div>
    <button onclick="openNewCustomerModal()" class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-amber-500 to-orange-600 text-white font-medium rounded-xl hover:from-amber-600 hover:to-orange-700 transition-all shadow-lg shadow-amber-200">
        <i class="fas fa-plus"></i>
        <span>Add Customer</span>
    </button>
</div>

<!-- Search -->
<div class="bg-white rounded-xl border border-gray-100 p-4 mb-6 shadow-sm">
    <form method="GET" action="" class="flex gap-4">
        <div class="flex-1">
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by name or phone..." class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
        </div>
        <button type="submit" class="px-6 py-2.5 bg-gray-800 text-white rounded-xl hover:bg-gray-700">
            <i class="fas fa-search mr-2"></i>Search
        </button>
        <?php if ($search): ?>
        <a href="customers.php" class="px-6 py-2.5 bg-gray-100 text-gray-600 rounded-xl hover:bg-gray-200">Clear</a>
        <?php endif; ?>
    </form>
</div>

<!-- Customers Table -->
<div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
    <!-- Mobile Card View -->
    <div class="md:hidden space-y-4 p-4">
        <?php if (empty($customers)): ?>
        <div class="text-center py-12 text-gray-400">
            <i class="fas fa-users text-4xl mb-3"></i>
            <p>No customers found</p>
        </div>
        <?php else: ?>
            <?php foreach ($customers as $c): ?>
            <div class="bg-gray-50 rounded-xl p-4 space-y-3">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-amber-100 rounded-full flex items-center justify-center text-amber-600 font-bold text-lg">
                        <?php echo strtoupper(substr($c['name'], 0, 1)); ?>
                    </div>
                    <div class="flex-1">
                        <p class="font-bold text-gray-800"><?php echo htmlspecialchars($c['name']); ?></p>
                        <p class="text-sm text-gray-400"><?php echo htmlspecialchars($c['phone']); ?></p>
                    </div>
                </div>
                <?php if ($c['address']): ?>
                <div class="text-sm">
                    <span class="text-gray-500">Address:</span>
                    <p class="text-gray-700"><?php echo htmlspecialchars($c['address']); ?></p>
                </div>
                <?php endif; ?>
                <div class="grid grid-cols-2 gap-3">
                    <div class="bg-amber-50 rounded-lg p-3 text-center">
                        <p class="text-xl font-bold text-amber-600"><?php echo $c['total_orders']; ?></p>
                        <p class="text-xs text-gray-500">Orders</p>
                    </div>
                    <div class="bg-green-50 rounded-lg p-3 text-center">
                        <p class="text-sm font-bold text-green-600"><?php echo formatCurrency($c['total_spent']); ?></p>
                        <p class="text-xs text-gray-500">Total Spent</p>
                    </div>
                </div>
                <div class="flex items-center justify-between text-sm">
                    <span class="text-gray-500">Last Order:</span>
                    <span class="text-gray-700"><?php echo $c['last_order_date'] ? formatDate($c['last_order_date']) : 'Never'; ?></span>
                </div>
                <div class="flex items-center justify-end gap-2 pt-2 border-t border-gray-200">
                    <button onclick="viewCustomer(<?php echo $c['id']; ?>)" class="px-4 py-2 text-amber-600 bg-amber-50 rounded-lg text-sm font-medium">
                        <i class="fas fa-eye mr-1"></i>View
                    </button>
                    <button onclick="editCustomer(<?php echo $c['id']; ?>)" class="px-4 py-2 text-blue-600 bg-blue-50 rounded-lg text-sm font-medium">
                        <i class="fas fa-edit mr-1"></i>Edit
                    </button>
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
                    <th class="text-left px-4 py-3 text-sm font-semibold text-gray-600">Customer</th>
                    <th class="text-left px-4 py-3 text-sm font-semibold text-gray-600">Contact</th>
                    <th class="text-left px-4 py-3 text-sm font-semibold text-gray-600">Address</th>
                    <th class="text-left px-4 py-3 text-sm font-semibold text-gray-600">Orders</th>
                    <th class="text-left px-4 py-3 text-sm font-semibold text-gray-600">Total Spent</th>
                    <th class="text-left px-4 py-3 text-sm font-semibold text-gray-600">Last Order</th>
                    <th class="text-right px-4 py-3 text-sm font-semibold text-gray-600">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                <?php if (empty($customers)): ?>
                <tr>
                    <td colspan="7" class="px-4 py-12 text-center text-gray-400">
                        <i class="fas fa-users text-4xl mb-3"></i>
                        <p>No customers found</p>
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($customers as $c): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-amber-100 rounded-full flex items-center justify-center text-amber-600 font-semibold">
                                    <?php echo strtoupper(substr($c['name'], 0, 1)); ?>
                                </div>
                                <div>
                                    <p class="font-medium text-gray-800"><?php echo htmlspecialchars($c['name']); ?></p>
                                    <?php if ($c['email']): ?>
                                    <p class="text-sm text-gray-400"><?php echo htmlspecialchars($c['email']); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <p class="text-gray-800"><?php echo htmlspecialchars($c['phone']); ?></p>
                        </td>
                        <td class="px-4 py-3">
                            <p class="text-sm text-gray-600 max-w-xs truncate"><?php echo htmlspecialchars($c['address'] ?? '-'); ?></p>
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-sm font-medium bg-amber-100 text-amber-800">
                                <?php echo $c['total_orders']; ?>
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <p class="font-semibold text-gray-800"><?php echo formatCurrency($c['total_spent']); ?></p>
                        </td>
                        <td class="px-4 py-3">
                            <p class="text-sm text-gray-600"><?php echo $c['last_order_date'] ? formatDate($c['last_order_date']) : '-'; ?></p>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-2">
                                <button onclick="viewCustomer(<?php echo $c['id']; ?>)" class="p-2 text-gray-400 hover:text-amber-600 hover:bg-amber-50 rounded-lg" title="View">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button onclick="editCustomer(<?php echo $c['id']; ?>)" class="p-2 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($pagination['total_pages'] > 1): ?>
    <div class="flex items-center justify-between px-6 py-4 border-t border-gray-100">
        <p class="text-sm text-gray-500">
            Showing <?php echo (($pagination['current_page'] - 1) * $pagination['per_page']) + 1; ?>
            to <?php echo min($pagination['current_page'] * $pagination['per_page'], $pagination['total']); ?>
            of <?php echo $pagination['total']; ?> customers
        </p>
        <div class="flex gap-2">
            <?php if ($pagination['has_prev']): ?>
            <a href="?page=<?php echo $pagination['current_page'] - 1; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?>" class="px-4 py-2 border border-gray-200 rounded-lg hover:bg-gray-50">Previous</a>
            <?php endif; ?>
            <?php if ($pagination['has_next']): ?>
            <a href="?page=<?php echo $pagination['current_page'] + 1; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?>" class="px-4 py-2 border border-gray-200 rounded-lg hover:bg-gray-50">Next</a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- New Customer Modal -->
<div id="newCustomerModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl w-full max-w-lg">
        <div class="flex items-center justify-between p-5 border-b border-gray-100">
            <h2 class="text-lg font-semibold text-gray-800">Add New Customer</h2>
            <button onclick="closeModal('newCustomerModal')" class="p-2 text-gray-400 hover:bg-gray-100 rounded-lg">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="POST" action="">
            <input type="hidden" name="action" value="create">
            <div class="p-5 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Full Name *</label>
                    <input type="text" name="name" required class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Phone Number *</label>
                    <input type="text" name="phone" required class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                    <input type="email" name="email" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Address</label>
                    <textarea name="address" rows="2" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Preferences (JSON)</label>
                    <input type="text" name="preferences" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500" placeholder='{"spicy": "yes", "protein": "chicken"}'>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
                    <textarea name="notes" rows="2" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500"></textarea>
                </div>
            </div>
            <div class="flex items-center justify-end gap-3 p-5 border-t border-gray-100 bg-gray-50">
                <button type="button" onclick="closeModal('newCustomerModal')" class="px-5 py-2.5 border border-gray-200 rounded-xl hover:bg-gray-100">Cancel</button>
                <button type="submit" class="px-5 py-2.5 bg-gradient-to-r from-amber-500 to-orange-600 text-white rounded-xl hover:from-amber-600 hover:to-orange-700">Add Customer</button>
            </div>
        </form>
    </div>
</div>

<!-- View Customer Modal -->
<div id="viewCustomerModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between p-5 border-b border-gray-100">
            <h2 class="text-lg font-semibold text-gray-800">Customer Details</h2>
            <button onclick="closeModal('viewCustomerModal')" class="p-2 text-gray-400 hover:bg-gray-100 rounded-lg">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div id="customerDetails" class="p-5">
            <!-- Content loaded via AJAX -->
        </div>
    </div>
</div>

<script>
function openNewCustomerModal() {
    document.getElementById('newCustomerModal').classList.remove('hidden');
}

function closeModal(id) {
    document.getElementById(id).classList.add('hidden');
}

function viewCustomer(id) {
    document.getElementById('viewCustomerModal').classList.remove('hidden');
    document.getElementById('customerDetails').innerHTML = '<div class="text-center py-8"><i class="fas fa-spinner fa-spin text-2xl text-amber-500"></i></div>';

    fetch('<?php echo SITE_URL; ?>/api/customer.php?id=' + id)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const c = data.customer;
                const prefs = c.preferences ? JSON.stringify(c.preferences, null, 2) : 'None';

                document.getElementById('customerDetails').innerHTML = `
                    <div class="space-y-6">
                        <div class="flex items-center gap-4">
                            <div class="w-16 h-16 bg-gradient-to-br from-amber-400 to-orange-500 rounded-full flex items-center justify-center text-white text-2xl font-bold">
                                ${c.name.charAt(0).toUpperCase()}
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-gray-800">${c.name}</h3>
                                <p class="text-gray-500">Customer since ${new Date(c.created_at).toLocaleDateString()}</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="bg-amber-50 rounded-xl p-4 text-center">
                                <p class="text-3xl font-bold text-amber-600">${c.total_orders}</p>
                                <p class="text-sm text-gray-500">Total Orders</p>
                            </div>
                            <div class="bg-green-50 rounded-xl p-4 text-center">
                                <p class="text-3xl font-bold text-green-600"><?php echo DEFAULT_CURRENCY; ?>${c.total_spent.toLocaleString()}</p>
                                <p class="text-sm text-gray-500">Total Spent</p>
                            </div>
                        </div>

                        <div class="space-y-3">
                            <div class="flex items-start gap-3">
                                <i class="fas fa-phone text-gray-400 mt-1"></i>
                                <div>
                                    <p class="text-sm text-gray-500">Phone</p>
                                    <p class="font-medium">${c.phone}</p>
                                </div>
                            </div>
                            ${c.email ? `
                            <div class="flex items-start gap-3">
                                <i class="fas fa-envelope text-gray-400 mt-1"></i>
                                <div>
                                    <p class="text-sm text-gray-500">Email</p>
                                    <p class="font-medium">${c.email}</p>
                                </div>
                            </div>
                            ` : ''}
                            ${c.address ? `
                            <div class="flex items-start gap-3">
                                <i class="fas fa-map-marker-alt text-gray-400 mt-1"></i>
                                <div>
                                    <p class="text-sm text-gray-500">Address</p>
                                    <p class="font-medium">${c.address}</p>
                                </div>
                            </div>
                            ` : ''}
                            <div class="flex items-start gap-3">
                                <i class="fas fa-clipboard-list text-gray-400 mt-1"></i>
                                <div>
                                    <p class="text-sm text-gray-500">Preferences</p>
                                    <pre class="text-sm bg-gray-50 p-2 rounded-lg mt-1 overflow-x-auto">${prefs}</pre>
                                </div>
                            </div>
                            ${c.notes ? `
                            <div class="flex items-start gap-3">
                                <i class="fas fa-sticky-note text-gray-400 mt-1"></i>
                                <div>
                                    <p class="text-sm text-gray-500">Notes</p>
                                    <p class="text-sm">${c.notes}</p>
                                </div>
                            </div>
                            ` : ''}
                        </div>

                        <div class="border-t border-gray-100 pt-4">
                            <h4 class="font-semibold text-gray-800 mb-3">Recent Orders</h4>
                            <div id="customerOrders" class="space-y-2">
                                <p class="text-gray-400 text-sm">Loading...</p>
                            </div>
                        </div>
                    </div>
                `;

                // Load customer orders
                loadCustomerOrders(id);
            }
        });
}

function loadCustomerOrders(id) {
    fetch('<?php echo SITE_URL; ?>/api/customer-orders.php?id=' + id)
        .then(r => r.json())
        .then(data => {
            if (data.success && data.orders.length > 0) {
                const ordersHtml = data.orders.map(o => `
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <div>
                            <span class="font-mono text-sm font-medium">#${o.order_number}</span>
                            <p class="text-xs text-gray-400">${new Date(o.created_at).toLocaleDateString()}</p>
                        </div>
                        <div class="text-right">
                            <p class="font-semibold"><?php echo DEFAULT_CURRENCY; ?>${o.total_amount.toFixed(2)}</p>
                            ${getStatusBadgeHtml(o.status)}
                        </div>
                    </div>
                `).join('');
                document.getElementById('customerOrders').innerHTML = ordersHtml;
            } else {
                document.getElementById('customerOrders').innerHTML = '<p class="text-gray-400 text-sm">No orders yet</p>';
            }
        });
}

function getStatusBadgeHtml(status) {
    const badges = {
        'pending': '<span class="px-2 py-0.5 text-xs font-medium rounded-full bg-yellow-100 text-yellow-800">Pending</span>',
        'preparing': '<span class="px-2 py-0.5 text-xs font-medium rounded-full bg-blue-100 text-blue-800">Preparing</span>',
        'out_for_delivery': '<span class="px-2 py-0.5 text-xs font-medium rounded-full bg-purple-100 text-purple-800">Out</span>',
        'delivered': '<span class="px-2 py-0.5 text-xs font-medium rounded-full bg-green-100 text-green-800">Delivered</span>'
    };
    return badges[status] || '';
}
</script>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
