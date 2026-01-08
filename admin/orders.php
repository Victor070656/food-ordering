<?php
/**
 * Admin Orders Management
 */

require_once dirname(__DIR__) . '/config/config.php';
requireRole('admin');

$page_title = 'Orders';

$order = new Order();
$rider = new Rider();

$action = $_GET['action'] ?? 'list';

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = ['success' => false, 'message' => 'Invalid action'];

    switch ($_POST['action'] ?? '') {
        case 'create':
            $data = [
                'customer_name' => sanitize($_POST['customer_name']),
                'customer_phone' => sanitize($_POST['customer_phone']),
                'delivery_address' => sanitize($_POST['delivery_address']),
                'items' => $_POST['items'] ?? [],
                'payment_method' => sanitize($_POST['payment_method'] ?? 'cash_on_delivery'),
                'payment_status' => sanitize($_POST['payment_status'] ?? 'pending'),
                'special_instructions' => sanitize($_POST['special_instructions'] ?? ''),
            ];

            // Parse items from JSON string
            if (isset($_POST['items_json'])) {
                $data['items'] = json_decode($_POST['items_json'], true);
            }

            $response = $order->create($data);
            break;

        case 'update_status':
            $response = $order->updateStatus($_POST['order_id'], $_POST['status']);
            if ($response['success'] && $_POST['status'] === 'delivered') {
                $orderData = $order->getById($_POST['order_id']);
                if ($orderData && $orderData['rider_id']) {
                    $rider->incrementDeliveryCount($orderData['rider_id']);
                }
            }
            break;

        case 'assign_rider':
            $response = $order->assignRider($_POST['order_id'], $_POST['rider_id']);
            break;

        case 'delete':
            // Soft delete by cancelling
            $response = $order->updateStatus($_POST['order_id'], 'cancelled');
            break;
    }

    if (isset($_POST['ajax'])) {
        jsonResponse($response);
    }

    if ($response['success']) {
        setFlash('success', $response['message'] ?? 'Operation successful');
    } else {
        setFlash('error', $response['message'] ?? 'Operation failed');
    }

    redirect(SITE_URL . '/admin/orders.php');
}

require_once INCLUDES_PATH . '/header.php';

// Get filters
$status = $_GET['status'] ?? '';
$paymentStatus = $_GET['payment_status'] ?? '';
$search = $_GET['search'] ?? '';
$today = $_GET['today'] ?? '';

$filters = [];
if ($status) $filters['status'] = $status;
if ($paymentStatus) $filters['payment_status'] = $paymentStatus;
if ($search) $filters['search'] = $search;
if ($today) $filters['today'] = true;

$result = $order->getAll($filters);
$orders = $result['orders'];
$pagination = $result['pagination'];
$riders = $rider->getAvailableRiders();
?>

<!-- Page Header -->
<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Orders</h1>
        <p class="text-gray-500">Manage all orders</p>
    </div>
    <button onclick="openNewOrderModal()" class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-amber-500 to-orange-600 text-white font-medium rounded-xl hover:from-amber-600 hover:to-orange-700 transition-all shadow-lg shadow-amber-200">
        <i class="fas fa-plus"></i>
        <span>New Order</span>
    </button>
</div>

<!-- Filters -->
<div class="bg-white rounded-xl border border-gray-100 p-4 mb-6 shadow-sm">
    <form method="GET" action="" class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search orders..." class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
        </div>
        <div>
            <select name="status" class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                <option value="">All Statuses</option>
                <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                <option value="preparing" <?php echo $status === 'preparing' ? 'selected' : ''; ?>>Preparing</option>
                <option value="out_for_delivery" <?php echo $status === 'out_for_delivery' ? 'selected' : ''; ?>>Out for Delivery</option>
                <option value="delivered" <?php echo $status === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                <option value="failed" <?php echo $status === 'failed' ? 'selected' : ''; ?>>Failed</option>
                <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
            </select>
        </div>
        <div>
            <select name="payment_status" class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                <option value="">All Payment Status</option>
                <option value="paid" <?php echo $paymentStatus === 'paid' ? 'selected' : ''; ?>>Paid</option>
                <option value="pending" <?php echo $paymentStatus === 'pending' ? 'selected' : ''; ?>>Pending</option>
                <option value="failed" <?php echo $paymentStatus === 'failed' ? 'selected' : ''; ?>>Failed</option>
            </select>
        </div>
        <div class="flex gap-2">
            <button type="submit" class="flex-1 px-4 py-2 bg-gray-800 text-white rounded-lg hover:bg-gray-700">Filter</button>
            <a href="orders.php" class="px-4 py-2 bg-gray-100 text-gray-600 rounded-lg hover:bg-gray-200">Clear</a>
        </div>
    </form>
</div>

<!-- Orders Table -->
<div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
    <!-- Mobile Card View -->
    <div class="md:hidden space-y-4 p-4">
        <?php if (empty($orders)): ?>
        <div class="text-center py-12 text-gray-400">
            <i class="fas fa-inbox text-4xl mb-3"></i>
            <p>No orders found</p>
        </div>
        <?php else: ?>
            <?php foreach ($orders as $row): ?>
            <div class="bg-gray-50 rounded-xl p-4 space-y-3">
                <div class="flex items-center justify-between">
                    <span class="font-mono font-bold text-gray-800"><?php echo htmlspecialchars($row['order_number']); ?></span>
                    <?php echo getStatusBadge($row['status']); ?>
                </div>
                <div>
                    <p class="font-medium text-gray-800"><?php echo htmlspecialchars($row['customer_name']); ?></p>
                    <p class="text-sm text-gray-400"><?php echo htmlspecialchars($row['customer_phone']); ?></p>
                </div>
                <div class="flex items-center justify-between text-sm">
                    <span class="text-gray-500">Amount:</span>
                    <span class="font-bold text-amber-600"><?php echo formatCurrency($row['total_amount']); ?></span>
                </div>
                <div class="flex items-center justify-between text-sm">
                    <span class="text-gray-500">Time:</span>
                    <span class="text-gray-700"><?php echo formatDateTime($row['created_at'], 'j M h:i A'); ?></span>
                </div>
                <?php if ($row['rider_name']): ?>
                <div class="flex items-center justify-between text-sm">
                    <span class="text-gray-500">Rider:</span>
                    <span class="text-gray-700"><?php echo htmlspecialchars($row['rider_name']); ?></span>
                </div>
                <?php endif; ?>
                <div class="flex items-center justify-between text-sm">
                    <span class="text-gray-500">Payment:</span>
                    <div class="text-right">
                        <?php echo getStatusBadge($row['payment_status']); ?>
                    </div>
                </div>
                <div class="flex items-center justify-end gap-2 pt-2 border-t border-gray-200">
                    <a href="order-details.php?id=<?php echo $row['id']; ?>" class="px-4 py-2 text-amber-600 bg-amber-50 rounded-lg text-sm font-medium">
                        <i class="fas fa-eye mr-1"></i>View
                    </a>
                    <?php if (in_array($row['status'], ['pending', 'preparing'])): ?>
                    <button onclick="openAssignRiderModal(<?php echo $row['id']; ?>)" class="px-4 py-2 text-purple-600 bg-purple-50 rounded-lg text-sm font-medium">
                        <i class="fas fa-motorcycle mr-1"></i>Assign
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
                    <th class="text-left px-4 py-3 text-sm font-semibold text-gray-600">Order #</th>
                    <th class="text-left px-4 py-3 text-sm font-semibold text-gray-600">Customer</th>
                    <th class="text-left px-4 py-3 text-sm font-semibold text-gray-600">Status</th>
                    <th class="text-left px-4 py-3 text-sm font-semibold text-gray-600">Rider</th>
                    <th class="text-left px-4 py-3 text-sm font-semibold text-gray-600">Amount</th>
                    <th class="text-left px-4 py-3 text-sm font-semibold text-gray-600">Payment</th>
                    <th class="text-left px-4 py-3 text-sm font-semibold text-gray-600">Time</th>
                    <th class="text-right px-4 py-3 text-sm font-semibold text-gray-600">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                <?php if (empty($orders)): ?>
                <tr>
                    <td colspan="8" class="px-4 py-12 text-center text-gray-400">
                        <i class="fas fa-inbox text-4xl mb-3"></i>
                        <p>No orders found</p>
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($orders as $row): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <span class="font-mono font-medium text-gray-800"><?php echo htmlspecialchars($row['order_number']); ?></span>
                        </td>
                        <td class="px-4 py-3">
                            <p class="font-medium text-gray-800"><?php echo htmlspecialchars($row['customer_name']); ?></p>
                            <p class="text-sm text-gray-400"><?php echo htmlspecialchars($row['customer_phone']); ?></p>
                        </td>
                        <td class="px-4 py-3"><?php echo getStatusBadge($row['status']); ?></td>
                        <td class="px-4 py-3">
                            <?php if ($row['rider_name']): ?>
                                <span class="text-gray-700"><?php echo htmlspecialchars($row['rider_name']); ?></span>
                            <?php else: ?>
                                <span class="text-gray-400">Not assigned</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3">
                            <p class="font-semibold text-gray-800"><?php echo formatCurrency($row['total_amount']); ?></p>
                        </td>
                        <td class="px-4 py-3">
                            <?php echo getStatusBadge($row['payment_status']); ?>
                            <p class="text-xs text-gray-400 mt-1"><?php echo ucfirst(str_replace('_', ' ', $row['payment_method'])); ?></p>
                        </td>
                        <td class="px-4 py-3">
                            <p class="text-sm text-gray-600"><?php echo formatDateTime($row['created_at'], 'j M h:i A'); ?></p>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-2">
                                <a href="order-details.php?id=<?php echo $row['id']; ?>" class="p-2 text-gray-400 hover:text-amber-600 hover:bg-amber-50 rounded-lg" title="View">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <?php if (in_array($row['status'], ['pending', 'preparing'])): ?>
                                <button onclick="openAssignRiderModal(<?php echo $row['id']; ?>)" class="p-2 text-gray-400 hover:text-purple-600 hover:bg-purple-50 rounded-lg" title="Assign Rider">
                                    <i class="fas fa-motorcycle"></i>
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

    <!-- Pagination -->
    <?php if ($pagination['total_pages'] > 1): ?>
    <div class="flex items-center justify-between px-6 py-4 border-t border-gray-100">
        <p class="text-sm text-gray-500">
            Showing <?php echo (($pagination['current_page'] - 1) * $pagination['per_page']) + 1; ?>
            to <?php echo min($pagination['current_page'] * $pagination['per_page'], $pagination['total']); ?>
            of <?php echo $pagination['total']; ?> orders
        </p>
        <div class="flex gap-2">
            <?php if ($pagination['has_prev']): ?>
            <a href="?page=<?php echo $pagination['current_page'] - 1; ?><?php echo $status ? '&status='.$status : ''; ?><?php echo $paymentStatus ? '&payment_status='.$paymentStatus : ''; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?>" class="px-4 py-2 border border-gray-200 rounded-lg hover:bg-gray-50">Previous</a>
            <?php endif; ?>
            <?php if ($pagination['has_next']): ?>
            <a href="?page=<?php echo $pagination['current_page'] + 1; ?><?php echo $status ? '&status='.$status : ''; ?><?php echo $paymentStatus ? '&payment_status='.$paymentStatus : ''; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?>" class="px-4 py-2 border border-gray-200 rounded-lg hover:bg-gray-50">Next</a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- New Order Modal -->
<div id="newOrderModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between p-5 border-b border-gray-100">
            <h2 class="text-lg font-semibold text-gray-800">Create New Order</h2>
            <button onclick="closeModal('newOrderModal')" class="p-2 text-gray-400 hover:bg-gray-100 rounded-lg">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form id="newOrderForm" method="POST" action="">
            <input type="hidden" name="action" value="create">
            <div class="p-5 space-y-5">
                <!-- Customer Info -->
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Customer Name *</label>
                        <input type="text" name="customer_name" required class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Phone Number *</label>
                        <input type="text" name="customer_phone" required class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Delivery Address *</label>
                    <textarea name="delivery_address" rows="2" required class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500"></textarea>
                </div>

                <!-- Order Items -->
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="block text-sm font-medium text-gray-700">Order Items *</label>
                        <button type="button" onclick="addOrderItem()" class="text-amber-600 text-sm font-medium hover:underline">
                            <i class="fas fa-plus mr-1"></i> Add Item
                        </button>
                    </div>
                    <div id="orderItems" class="space-y-3">
                        <div class="item-row grid grid-cols-12 gap-2 items-center">
                            <input type="text" placeholder="Item name" class="item-name col-span-5 px-3 py-2 border border-gray-200 rounded-lg text-sm">
                            <input type="number" placeholder="Qty" value="1" min="1" class="item-qty col-span-2 px-3 py-2 border border-gray-200 rounded-lg text-sm text-center">
                            <input type="number" placeholder="Price" step="0.01" class="item-price col-span-3 px-3 py-2 border border-gray-200 rounded-lg text-sm">
                            <button type="button" onclick="removeItem(this)" class="col-span-2 p-2 text-red-500 hover:bg-red-50 rounded-lg">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Payment Info -->
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Payment Method</label>
                        <select name="payment_method" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                            <option value="cash_on_delivery">Cash on Delivery</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="pos">POS</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Payment Status</label>
                        <select name="payment_status" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                            <option value="pending">Pending</option>
                            <option value="paid">Paid</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Special Instructions</label>
                    <textarea name="special_instructions" rows="2" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500" placeholder="Any special requests or notes..."></textarea>
                </div>
            </div>
            <div class="flex items-center justify-end gap-3 p-5 border-t border-gray-100 bg-gray-50">
                <button type="button" onclick="closeModal('newOrderModal')" class="px-5 py-2.5 border border-gray-200 rounded-xl hover:bg-gray-100">Cancel</button>
                <button type="submit" class="px-5 py-2.5 bg-gradient-to-r from-amber-500 to-orange-600 text-white rounded-xl hover:from-amber-600 hover:to-orange-700">Create Order</button>
            </div>
        </form>
    </div>
</div>

<!-- View Order Modal -->
<div id="viewOrderModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between p-5 border-b border-gray-100">
            <h2 class="text-lg font-semibold text-gray-800">Order Details</h2>
            <button onclick="closeModal('viewOrderModal')" class="p-2 text-gray-400 hover:bg-gray-100 rounded-lg">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div id="orderDetails" class="p-5">
            <!-- Content loaded via AJAX -->
        </div>
    </div>
</div>

<!-- Assign Rider Modal -->
<div id="assignRiderModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl w-full max-w-md">
        <div class="flex items-center justify-between p-5 border-b border-gray-100">
            <h2 class="text-lg font-semibold text-gray-800">Assign Rider</h2>
            <button onclick="closeModal('assignRiderModal')" class="p-2 text-gray-400 hover:bg-gray-100 rounded-lg">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form id="assignRiderForm" method="POST" action="">
            <input type="hidden" name="action" value="assign_rider">
            <input type="hidden" name="order_id" id="assign_order_id">
            <div class="p-5">
                <label class="block text-sm font-medium text-gray-700 mb-2">Select Rider</label>
                <select name="rider_id" id="riderSelect" required class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                    <option value="">-- Select Rider --</option>
                    <?php foreach ($riders as $r): ?>
                    <option value="<?php echo $r['id']; ?>"><?php echo htmlspecialchars($r['name']); ?> (<?php echo htmlspecialchars($r['vehicle_type'] ?? 'N/A'); ?>)</option>
                    <?php endforeach; ?>
                </select>
                <?php if (empty($riders)): ?>
                <p class="text-sm text-red-500 mt-2">No riders currently available</p>
                <?php endif; ?>
            </div>
            <div class="flex items-center justify-end gap-3 p-5 border-t border-gray-100 bg-gray-50">
                <button type="button" onclick="closeModal('assignRiderModal')" class="px-5 py-2.5 border border-gray-200 rounded-xl hover:bg-gray-100">Cancel</button>
                <button type="submit" class="px-5 py-2.5 bg-gradient-to-r from-amber-500 to-orange-600 text-white rounded-xl hover:from-amber-600 hover:to-orange-700">Assign</button>
            </div>
        </form>
    </div>
</div>

<script>
let itemCount = 1;

function openNewOrderModal() {
    document.getElementById('newOrderModal').classList.remove('hidden');
}

function closeModal(id) {
    document.getElementById(id).classList.add('hidden');
}

function addOrderItem() {
    const container = document.getElementById('orderItems');
    const div = document.createElement('div');
    div.className = 'item-row grid grid-cols-12 gap-2 items-center';
    div.innerHTML = `
        <input type="text" placeholder="Item name" class="item-name col-span-5 px-3 py-2 border border-gray-200 rounded-lg text-sm">
        <input type="number" placeholder="Qty" value="1" min="1" class="item-qty col-span-2 px-3 py-2 border border-gray-200 rounded-lg text-sm text-center">
        <input type="number" placeholder="Price" step="0.01" class="item-price col-span-3 px-3 py-2 border border-gray-200 rounded-lg text-sm">
        <button type="button" onclick="removeItem(this)" class="col-span-2 p-2 text-red-500 hover:bg-red-50 rounded-lg">
            <i class="fas fa-trash"></i>
        </button>
    `;
    container.appendChild(div);
    itemCount++;
}

function removeItem(btn) {
    const items = document.querySelectorAll('.item-row');
    if (items.length > 1) {
        btn.parentElement.remove();
    }
}

function openAssignRiderModal(orderId) {
    document.getElementById('assign_order_id').value = orderId;
    document.getElementById('assignRiderModal').classList.remove('hidden');
}

function viewOrder(orderId) {
    document.getElementById('viewOrderModal').classList.remove('hidden');
    document.getElementById('orderDetails').innerHTML = '<div class="text-center py-8"><i class="fas fa-spinner fa-spin text-2xl text-amber-500"></i></div>';

    fetch('<?php echo SITE_URL; ?>/api/order.php?id=' + orderId)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const order = data.order;
                let itemsHtml = order.items.map(item => `
                    <div class="flex justify-between py-2 border-b border-gray-50 last:border-0">
                        <span>${item.item_name} × ${item.quantity}</span>
                        <span class="font-medium"><?php echo DEFAULT_CURRENCY; ?>${item.total_price.toFixed(2)}</span>
                    </div>
                `).join('');

                document.getElementById('orderDetails').innerHTML = `
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-500">Order #</span>
                            <span class="font-mono font-semibold">${order.order_number}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-500">Status</span>
                            ${getStatusBadgeHtml(order.status)}
                        </div>
                        <hr class="border-gray-100">
                        <div>
                            <p class="text-sm text-gray-500 mb-2">Customer</p>
                            <p class="font-medium">${order.customer_name}</p>
                            <p class="text-sm text-gray-400">${order.customer_phone}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 mb-2">Delivery Address</p>
                            <p class="text-sm">${order.delivery_address}</p>
                        </div>
                        <hr class="border-gray-100">
                        <div>
                            <p class="text-sm text-gray-500 mb-2">Items</p>
                            <div class="space-y-1">${itemsHtml}</div>
                        </div>
                        <hr class="border-gray-100">
                        <div class="flex justify-between text-gray-500">
                            <span>Subtotal</span>
                            <span><?php echo DEFAULT_CURRENCY; ?>${order.subtotal.toFixed(2)}</span>
                        </div>
                        <div class="flex justify-between text-gray-500">
                            <span>Delivery Fee</span>
                            <span><?php echo DEFAULT_CURRENCY; ?>${order.delivery_fee.toFixed(2)}</span>
                        </div>
                        <div class="flex justify-between text-lg font-bold">
                            <span>Total</span>
                            <span class="text-amber-600"><?php echo DEFAULT_CURRENCY; ?>${order.total_amount.toFixed(2)}</span>
                        </div>
                        <hr class="border-gray-100">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-500">Payment</span>
                            <span>${order.payment_method.replace(/_/g, ' ')}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-500">Payment Status</span>
                            ${getPaymentStatusBadgeHtml(order.payment_status)}
                        </div>
                        <hr class="border-gray-100">
                        <div class="bg-gray-50 rounded-xl p-4">
                            <p class="text-sm text-gray-500 mb-3">Update Status</p>
                            <div class="flex flex-wrap gap-2">
                                <button onclick="updateStatus(${order.id}, 'preparing')" class="px-3 py-1.5 text-sm bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200">Preparing</button>
                                <button onclick="updateStatus(${order.id}, 'out_for_delivery')" class="px-3 py-1.5 text-sm bg-purple-100 text-purple-700 rounded-lg hover:bg-purple-200">Out for Delivery</button>
                                <button onclick="updateStatus(${order.id}, 'delivered')" class="px-3 py-1.5 text-sm bg-green-100 text-green-700 rounded-lg hover:bg-green-200">Delivered</button>
                                <button onclick="updateStatus(${order.id}, 'failed')" class="px-3 py-1.5 text-sm bg-red-100 text-red-700 rounded-lg hover:bg-red-200">Failed</button>
                            </div>
                        </div>
                    </div>
                `;
            }
        });
}

function updateStatus(orderId, status) {
    const formData = new FormData();
    formData.append('action', 'update_status');
    formData.append('order_id', orderId);
    formData.append('status', status);
    formData.append('ajax', '1');

    fetch('', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                location.reload();
            }
        });
}

function getStatusBadgeHtml(status) {
    const badges = {
        'pending': '<span class="px-3 py-1 text-xs font-medium rounded-full bg-yellow-100 text-yellow-800">Pending</span>',
        'preparing': '<span class="px-3 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800">Preparing</span>',
        'out_for_delivery': '<span class="px-3 py-1 text-xs font-medium rounded-full bg-purple-100 text-purple-800">Out for Delivery</span>',
        'delivered': '<span class="px-3 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">Delivered</span>',
        'failed': '<span class="px-3 py-1 text-xs font-medium rounded-full bg-red-100 text-red-800">Failed</span>',
        'cancelled': '<span class="px-3 py-1 text-xs font-medium rounded-full bg-red-100 text-red-800">Cancelled</span>'
    };
    return badges[status] || status;
}

function getPaymentStatusBadgeHtml(status) {
    const badges = {
        'paid': '<span class="px-3 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">Paid</span>',
        'pending': '<span class="px-3 py-1 text-xs font-medium rounded-full bg-yellow-100 text-yellow-800">Pending</span>',
        'failed': '<span class="px-3 py-1 text-xs font-medium rounded-full bg-red-100 text-red-800">Failed</span>'
    };
    return badges[status] || status;
}

// Form submission for new order
document.getElementById('newOrderForm').addEventListener('submit', function(e) {
    const items = [];
    document.querySelectorAll('.item-row').forEach(row => {
        const name = row.querySelector('.item-name').value.trim();
        const qty = parseInt(row.querySelector('.item-qty').value) || 0;
        const price = parseFloat(row.querySelector('.item-price').value) || 0;
        if (name && qty > 0 && price > 0) {
            items.push({ name, quantity: qty, price });
        }
    });

    if (items.length === 0) {
        e.preventDefault();
        alert('Please add at least one item');
        return;
    }

    const itemsInput = document.createElement('input');
    itemsInput.type = 'hidden';
    itemsInput.name = 'items_json';
    itemsInput.value = JSON.stringify(items);
    this.appendChild(itemsInput);
});
</script>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
