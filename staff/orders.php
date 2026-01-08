<?php
/**
 * Staff Orders Page
 */

require_once dirname(__DIR__) . '/config/config.php';
requireAnyRole(['admin', 'staff']);

$page_title = 'Orders';

$order = new Order();

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $response = $order->updateStatus($_POST['order_id'], $_POST['status']);

    if (isset($_POST['ajax'])) {
        jsonResponse($response);
    }

    if ($response['success']) {
        setFlash('success', $response['message'] ?? 'Status updated');
    } else {
        setFlash('error', $response['message'] ?? 'Failed to update status');
    }

    redirect(SITE_URL . '/staff/orders.php');
}

require_once INCLUDES_PATH . '/header.php';

$status = $_GET['status'] ?? '';
$today = $_GET['today'] ?? '';

$filters = [];
if ($today === '1')
    $filters['today'] = true;
if ($status)
    $filters['status'] = $status;

$result = $order->getAll($filters);
$orders = $result['orders'];
?>

<!-- Page Header -->
<div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Orders</h1>
        <p class="text-gray-500">Manage and update orders</p>
    </div>
    <a href="?today=1"
        class="inline-flex items-center gap-2 px-4 py-2 bg-amber-500 text-white rounded-lg text-sm font-medium hover:bg-amber-600">
        <i class="fas fa-calendar-day"></i>
        <span>View Today's Orders</span>
    </a>
</div>

<!-- Filter Tabs -->
<div class="flex gap-2 mb-6 overflow-x-auto">
    <a href="?status="
        class="px-4 py-2 rounded-lg text-sm font-medium <?php echo $today === '' ? 'bg-amber-500 text-white' : 'bg-white text-gray-600 hover:bg-gray-50'; ?>">
        All Time
    </a>
    <a href="?status=pending"
        class="px-4 py-2 rounded-lg text-sm font-medium <?php echo $status === 'pending' ? 'bg-yellow-500 text-white' : 'bg-white text-gray-600 hover:bg-gray-50'; ?>">
        Pending
    </a>
    <a href="?status=preparing"
        class="px-4 py-2 rounded-lg text-sm font-medium <?php echo $status === 'preparing' ? 'bg-blue-500 text-white' : 'bg-white text-gray-600 hover:bg-gray-50'; ?>">
        Preparing
    </a>
    <a href="?status=out_for_delivery"
        class="px-4 py-2 rounded-lg text-sm font-medium <?php echo $status === 'out_for_delivery' ? 'bg-purple-500 text-white' : 'bg-white text-gray-600 hover:bg-gray-50'; ?>">
        Out for Delivery
    </a>
    <a href="?status=delivered"
        class="px-4 py-2 rounded-lg text-sm font-medium <?php echo $status === 'delivered' ? 'bg-green-500 text-white' : 'bg-white text-gray-600 hover:bg-gray-50'; ?>">
        Delivered
    </a>
</div>

<!-- Orders Grid -->
<div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
    <?php if (empty($orders)): ?>
        <div class="col-span-full bg-white rounded-xl border border-gray-100 p-12 text-center text-gray-400 shadow-sm">
            <i class="fas fa-inbox text-4xl mb-3"></i>
            <p>No orders found</p>
        </div>
    <?php else: ?>
        <?php foreach ($orders as $o): ?>
            <div class="bg-white rounded-xl border border-gray-100 p-5 shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <span class="font-mono font-bold text-gray-800">#<?php echo htmlspecialchars($o['order_number']); ?></span>
                    <?php echo getStatusBadge($o['status']); ?>
                </div>

                <div class="space-y-2 mb-4">
                    <div class="flex items-center gap-2 text-sm">
                        <i class="fas fa-user text-gray-400 w-4"></i>
                        <span class="font-medium"><?php echo htmlspecialchars($o['customer_name']); ?></span>
                    </div>
                    <div class="flex items-center gap-2 text-sm">
                        <i class="fas fa-phone text-gray-400 w-4"></i>
                        <a href="tel:<?php echo htmlspecialchars($o['customer_phone']); ?>"
                            class="text-amber-600 hover:underline"><?php echo htmlspecialchars($o['customer_phone']); ?></a>
                    </div>
                </div>

                <div class="bg-gray-50 rounded-lg p-3 mb-4">
                    <p class="text-sm text-gray-500 mb-2">Items:</p>
                    <?php
                    $items = $order->getItems($o['id']);
                    foreach ($items as $item):
                        ?>
                        <div class="flex justify-between text-sm">
                            <span><?php echo htmlspecialchars($item['item_name']); ?> × <?php echo $item['quantity']; ?></span>
                            <span class="text-gray-400"><?php echo formatCurrency($item['total_price']); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="flex items-center justify-between text-sm mb-4">
                    <span class="text-gray-500">Total</span>
                    <span class="font-bold text-lg"><?php echo formatCurrency($o['total_amount']); ?></span>
                </div>

                <?php if ($o['status'] === 'pending'): ?>
                    <button onclick="updateStatus(<?php echo $o['id']; ?>, 'preparing')"
                        class="w-full px-4 py-2.5 bg-blue-500 text-white rounded-lg hover:bg-blue-600 font-medium">
                        <i class="fas fa-fire mr-2"></i>Start Preparing
                    </button>
                <?php elseif ($o['status'] === 'preparing'): ?>
                    <button onclick="updateStatus(<?php echo $o['id']; ?>, 'out_for_delivery')"
                        class="w-full px-4 py-2.5 bg-green-500 text-white rounded-lg hover:bg-green-600 font-medium">
                        <i class="fas fa-motorcycle mr-2"></i>Ready for Pickup
                    </button>
                <?php elseif ($o['status'] === 'out_for_delivery'): ?>
                    <button onclick="updateStatus(<?php echo $o['id']; ?>, 'delivered')"
                        class="w-full px-4 py-2.5 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium">
                        <i class="fas fa-check mr-2"></i>Mark Delivered
                    </button>
                <?php else: ?>
                    <div class="w-full px-4 py-2.5 bg-gray-100 text-gray-500 rounded-lg text-center">
                        Order <?php echo $o['status']; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
    function updateStatus(orderId, status) {
        if (!confirm('Update order status to ' + status.replace(/_/g, ' ') + '?')) return;

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
</script>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>