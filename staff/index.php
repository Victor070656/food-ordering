<?php
/**
 * Staff Dashboard
 */

require_once dirname(__DIR__) . '/config/config.php';
requireAnyRole(['admin', 'staff']);

$page_title = 'Staff Dashboard';

$order = new Order();
$rider = new Rider();

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $response = ['success' => false, 'message' => 'Invalid request'];

    $orderId = $_POST['order_id'] ?? 0;
    $status = $_POST['status'] ?? '';
    $riderId = $_POST['rider_id'] ?? null;

    if ($orderId && $status) {
        $response = $order->updateStatus($orderId, $status);

        // Also assign rider if provided
        if ($response['success'] && $riderId) {
            $response = $order->assignRider($orderId, $riderId);
        }
    }

    if (isset($_POST['ajax'])) {
        jsonResponse($response);
        exit;
    }

    if ($response['success']) {
        setFlash('success', $response['message'] ?? 'Status updated');
    } else {
        setFlash('error', $response['message'] ?? 'Failed to update status');
    }

    redirect(SITE_URL . '/staff/index.php');
}

// Get statistics
$stats = $order->getStats();
$todayOrders = $order->getTodayOrders();
// Get pending and preparing orders (all, not just today) - limit to 3 most recent
$allPendingOrders = $order->getAll(['status' => 'pending', 'per_page' => 50])['orders'];
$allPreparingOrders = $order->getAll(['status' => 'preparing', 'per_page' => 50])['orders'];
// Merge and sort by created_at DESC (newest first)
$activeOrders = array_merge($allPendingOrders, $allPreparingOrders);
usort($activeOrders, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});
// Keep only 3 most recent
$recentOrders = array_slice($activeOrders, 0, 3);
// Today's pending/preparing for stats
$todayPending = $order->getAll(['status' => 'pending', 'today' => true])['orders'];
$todayPreparing = $order->getAll(['status' => 'preparing', 'today' => true])['orders'];
$readyOrders = array_merge($todayPending, $todayPreparing);
$availableRiders = $rider->getAvailableRiders();

require_once INCLUDES_PATH . '/header.php';
?>

<!-- Page Header -->
<div class="mb-8">
    <h1 class="text-2xl font-bold text-gray-800">Kitchen Dashboard</h1>
    <p class="text-gray-500">Manage orders and preparation</p>
</div>

<!-- Stats Cards -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
    <div class="bg-white rounded-xl border border-gray-100 p-5 shadow-sm">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-yellow-50 rounded-xl flex items-center justify-center">
                <i class="fas fa-clock text-yellow-500 text-xl"></i>
            </div>
            <div>
                <p class="text-gray-500 text-sm">Pending</p>
                <p class="text-2xl font-bold text-gray-800"><?php echo count($allPendingOrders); ?></p>
                <?php if (count($todayPending) > 0): ?>
                <p class="text-xs text-gray-400"><?php echo count($todayPending); ?> today</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-xl border border-gray-100 p-5 shadow-sm">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-blue-50 rounded-xl flex items-center justify-center">
                <i class="fas fa-fire text-blue-500 text-xl"></i>
            </div>
            <div>
                <p class="text-gray-500 text-sm">Preparing</p>
                <p class="text-2xl font-bold text-gray-800"><?php echo count($allPreparingOrders); ?></p>
                <?php if (count($todayPreparing) > 0): ?>
                <p class="text-xs text-gray-400"><?php echo count($todayPreparing); ?> today</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-xl border border-gray-100 p-5 shadow-sm">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-green-50 rounded-xl flex items-center justify-center">
                <i class="fas fa-check-circle text-green-500 text-xl"></i>
            </div>
            <div>
                <p class="text-gray-500 text-sm">Completed Today</p>
                <p class="text-2xl font-bold text-gray-800"><?php echo $stats['today']['delivered'] ?? 0; ?></p>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-xl border border-gray-100 p-5 shadow-sm">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-purple-50 rounded-xl flex items-center justify-center">
                <i class="fas fa-motorcycle text-purple-500 text-xl"></i>
            </div>
            <div>
                <p class="text-gray-500 text-sm">Available Riders</p>
                <p class="text-2xl font-bold text-gray-800"><?php echo count($availableRiders); ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Orders to Prepare -->
<div class="mb-8">
    <h2 class="text-lg font-semibold text-gray-800 mb-4">Orders to Prepare</h2>

    <?php if (empty($recentOrders)): ?>
    <div class="bg-white rounded-xl border border-gray-100 p-12 text-center text-gray-400 shadow-sm">
        <i class="fas fa-clipboard-check text-4xl mb-3"></i>
        <p>No orders to prepare</p>
    </div>
    <?php else: ?>
    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
        <?php foreach ($recentOrders as $o): ?>
        <div class="bg-white rounded-xl border border-gray-100 p-5 shadow-sm hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between mb-4">
                <span class="font-mono font-bold text-gray-800">#<?php echo htmlspecialchars($o['order_number']); ?></span>
                <?php echo getStatusBadge($o['status']); ?>
            </div>

            <div class="space-y-3 mb-4">
                <div class="flex items-center gap-2 text-sm">
                    <i class="fas fa-user text-gray-400 w-4"></i>
                    <span class="font-medium"><?php echo htmlspecialchars($o['customer_name']); ?></span>
                </div>
                <div class="flex items-center gap-2 text-sm">
                    <i class="fas fa-phone text-gray-400 w-4"></i>
                    <span><?php echo htmlspecialchars($o['customer_phone']); ?></span>
                </div>
                <div class="flex items-start gap-2 text-sm">
                    <i class="fas fa-map-marker-alt text-gray-400 w-4 mt-0.5"></i>
                    <span class="text-gray-600 line-clamp-2"><?php echo htmlspecialchars($o['delivery_address']); ?></span>
                </div>
                <div class="flex items-center gap-2 text-sm">
                    <i class="fas fa-calendar text-gray-400 w-4"></i>
                    <span class="text-gray-500"><?php echo formatDateTime($o['created_at'], 'M j, h:i A'); ?></span>
                </div>
            </div>

            <div class="border-t border-gray-100 pt-4 mb-4">
                <p class="text-sm text-gray-500 mb-2">Items:</p>
                <div class="space-y-1 text-sm">
                    <?php
                    $items = $order->getItems($o['id']);
                    foreach ($items as $item):
                    ?>
                    <div class="flex justify-between">
                        <span><?php echo htmlspecialchars($item['item_name']); ?> × <?php echo $item['quantity']; ?></span>
                        <span class="text-gray-400"><?php echo formatCurrency($item['total_price']); ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="flex items-center justify-between text-sm text-gray-500 mb-4">
                <span>Total</span>
                <span class="font-bold text-lg text-gray-800"><?php echo formatCurrency($o['total_amount']); ?></span>
            </div>

            <?php if ($o['special_instructions']): ?>
            <div class="bg-amber-50 rounded-lg p-3 mb-4">
                <p class="text-sm text-amber-800"><i class="fas fa-sticky-note mr-2"></i><?php echo htmlspecialchars($o['special_instructions']); ?></p>
            </div>
            <?php endif; ?>

            <div class="flex gap-2">
                <?php if ($o['status'] === 'pending'): ?>
                <button onclick="updateOrderStatus(<?php echo $o['id']; ?>, 'preparing')" class="flex-1 px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 font-medium">
                    <i class="fas fa-fire mr-2"></i>Start Preparing
                </button>
                <?php else: ?>
                <button onclick="openReadyModal(<?php echo $o['id']; ?>)" class="flex-1 px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 font-medium">
                    <i class="fas fa-check mr-2"></i>Mark Ready
                </button>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Today's All Orders -->
<div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
    <div class="p-5 border-b border-gray-100">
        <h2 class="font-semibold text-gray-800">Today's All Orders</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-100">
                <tr>
                    <th class="text-left px-6 py-4 text-sm font-semibold text-gray-600">Order #</th>
                    <th class="text-left px-6 py-4 text-sm font-semibold text-gray-600">Customer</th>
                    <th class="text-left px-6 py-4 text-sm font-semibold text-gray-600">Status</th>
                    <th class="text-left px-6 py-4 text-sm font-semibold text-gray-600">Time</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                <?php if (empty($todayOrders)): ?>
                <tr>
                    <td colspan="4" class="px-6 py-12 text-center text-gray-400">
                        <p>No orders today</p>
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($todayOrders as $o): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <span class="font-mono font-medium text-gray-800"><?php echo htmlspecialchars($o['order_number']); ?></span>
                        </td>
                        <td class="px-6 py-4">
                            <p class="font-medium text-gray-800"><?php echo htmlspecialchars($o['customer_name']); ?></p>
                        </td>
                        <td class="px-6 py-4"><?php echo getStatusBadge($o['status']); ?></td>
                        <td class="px-6 py-4">
                            <p class="text-sm text-gray-600"><?php echo formatDateTime($o['created_at'], 'h:i A'); ?></p>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Ready for Delivery Modal -->
<div id="readyModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl w-full max-w-sm">
        <div class="flex items-center justify-between p-5 border-b border-gray-100">
            <h2 class="text-lg font-semibold text-gray-800">Ready for Delivery</h2>
            <button onclick="closeModal('readyModal')" class="p-2 text-gray-400 hover:bg-gray-100 rounded-lg">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form id="readyForm" method="POST" action="">
            <input type="hidden" name="action" value="update_status">
            <input type="hidden" name="order_id" id="ready_order_id">
            <input type="hidden" name="status" value="out_for_delivery">
            <div class="p-5">
                <label class="block text-sm font-medium text-gray-700 mb-2">Assign Rider</label>
                <select name="rider_id" id="ready_rider_id" required class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                    <option value="">-- Select Rider --</option>
                    <?php foreach ($availableRiders as $r): ?>
                    <option value="<?php echo $r['id']; ?>"><?php echo htmlspecialchars($r['name']); ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (empty($availableRiders)): ?>
                <p class="text-sm text-red-500 mt-2">No riders available</p>
                <?php endif; ?>
            </div>
            <div class="flex items-center justify-end gap-3 p-5 border-t border-gray-100 bg-gray-50">
                <button type="button" onclick="closeModal('readyModal')" class="px-5 py-2.5 border border-gray-200 rounded-xl hover:bg-gray-100">Cancel</button>
                <button type="submit" class="px-5 py-2.5 bg-gradient-to-r from-amber-500 to-orange-600 text-white rounded-xl hover:from-amber-600 hover:to-orange-700">Assign & Send</button>
            </div>
        </form>
    </div>
</div>

<script>
function updateOrderStatus(orderId, status) {
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
            } else {
                alert(data.message || 'Failed to update status');
            }
        });
}

function openReadyModal(orderId) {
    document.getElementById('ready_order_id').value = orderId;
    document.getElementById('readyModal').classList.remove('hidden');
}

function closeModal(id) {
    document.getElementById(id).classList.add('hidden');
}

document.getElementById('readyForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    formData.append('ajax', '1');

    fetch('', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || 'Failed to update');
            }
        });
});
</script>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
