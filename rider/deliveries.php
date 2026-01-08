<?php
/**
 * Rider Deliveries Page
 */

require_once dirname(__DIR__) . '/config/config.php';
requireRole('rider');

$page_title = 'My Deliveries';

$user = getCurrentUser();
$riderClass = new Rider();
$order = new Order();

$currentRider = $riderClass->getByUserId($user['id']);
if (!$currentRider) {
    setFlash('error', 'Rider profile not found');
    redirect(SITE_URL . '/public/logout.php');
}

$allDeliveries = $riderClass->getOrders($currentRider['id']);

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $orderData = $order->getById($_POST['order_id']);

    // Verify this order belongs to the rider
    if ($orderData && $orderData['rider_id'] == $currentRider['id']) {
        $response = $order->updateStatus($_POST['order_id'], $_POST['status']);

        if (isset($_POST['ajax'])) {
            jsonResponse($response);
        }

        if ($response['success']) {
            setFlash('success', $response['message'] ?? 'Status updated');
        } else {
            setFlash('error', $response['message'] ?? 'Failed to update status');
        }
    }

    redirect(SITE_URL . '/rider/deliveries.php');
}

require_once INCLUDES_PATH . '/header.php';
?>

<!-- Page Header -->
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">My Deliveries</h1>
    <p class="text-gray-500">View all assigned deliveries</p>
</div>

<!-- Stats Summary -->
<div class="grid grid-cols-3 gap-4 mb-6">
    <div class="bg-white rounded-xl border border-gray-100 p-4 shadow-sm text-center">
        <p class="text-3xl font-bold text-purple-600"><?php echo count(array_filter($allDeliveries, fn($d) => $d['status'] === 'out_for_delivery')); ?></p>
        <p class="text-sm text-gray-500">Active</p>
    </div>
    <div class="bg-white rounded-xl border border-gray-100 p-4 shadow-sm text-center">
        <p class="text-3xl font-bold text-green-600"><?php echo count(array_filter($allDeliveries, fn($d) => $d['status'] === 'delivered')); ?></p>
        <p class="text-sm text-gray-500">Delivered</p>
    </div>
    <div class="bg-white rounded-xl border border-gray-100 p-4 shadow-sm text-center">
        <p class="text-3xl font-bold text-gray-800"><?php echo count($allDeliveries); ?></p>
        <p class="text-sm text-gray-500">Total</p>
    </div>
</div>

<!-- Deliveries List -->
<div class="space-y-4">
    <?php if (empty($allDeliveries)): ?>
    <div class="bg-white rounded-xl border border-gray-100 p-12 text-center text-gray-400 shadow-sm">
        <i class="fas fa-motorcycle text-4xl mb-3"></i>
        <p>No deliveries assigned yet</p>
    </div>
    <?php else: ?>
        <?php foreach ($allDeliveries as $o): ?>
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-4">
                    <span class="font-mono font-bold text-lg text-gray-800"><?php echo htmlspecialchars($o['order_number']); ?></span>
                    <?php echo getStatusBadge($o['status']); ?>
                </div>
                <span class="text-sm text-gray-400"><?php echo formatDateTime($o['created_at'], 'j M h:i A'); ?></span>
            </div>

            <div class="grid md:grid-cols-2 gap-4 mb-4">
                <div>
                    <p class="text-sm text-gray-500 mb-1">Customer</p>
                    <p class="font-medium text-gray-800"><?php echo htmlspecialchars($o['customer_name']); ?></p>
                    <a href="tel:<?php echo htmlspecialchars($o['customer_phone']); ?>" class="text-amber-600 text-sm hover:underline"><?php echo htmlspecialchars($o['customer_phone']); ?></a>
                </div>
                <div>
                    <p class="text-sm text-gray-500 mb-1">Delivery Address</p>
                    <p class="text-gray-700"><?php echo htmlspecialchars($o['delivery_address']); ?></p>
                </div>
            </div>

            <div class="bg-gray-50 rounded-lg p-3 mb-4">
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-500">Total Amount</span>
                    <span class="font-bold text-lg text-gray-800"><?php echo formatCurrency($o['total_amount']); ?></span>
                </div>
                <div class="flex justify-between items-center mt-1">
                    <span class="text-sm text-gray-500">Payment</span>
                    <span class="text-sm text-gray-600"><?php echo ucfirst(str_replace('_', ' ', $o['payment_method'])); ?></span>
                </div>
            </div>

            <?php if ($o['status'] === 'out_for_delivery'): ?>
            <div class="flex gap-3">
                <a href="tel:<?php echo htmlspecialchars($o['customer_phone']); ?>" class="flex-1 px-4 py-2.5 border border-gray-200 text-gray-700 rounded-lg hover:bg-gray-50 text-center">
                    <i class="fas fa-phone mr-2"></i>Call
                </a>
                <a href="https://wa.me/<?php echo formatPhoneNumber($o['customer_phone']); ?>" target="_blank" class="flex-1 px-4 py-2.5 bg-green-500 text-white rounded-lg hover:bg-green-600 text-center">
                    <i class="fab fa-whatsapp mr-2"></i>WhatsApp
                </a>
                <button onclick="markDelivered(<?php echo $o['id']; ?>)" class="flex-1 px-4 py-2.5 bg-gradient-to-r from-amber-500 to-orange-600 text-white rounded-lg hover:from-amber-600 hover:to-orange-700">
                    <i class="fas fa-check mr-2"></i>Delivered
                </button>
            </div>
            <?php elseif ($o['status'] === 'delivered'): ?>
            <div class="flex items-center justify-center text-green-600">
                <i class="fas fa-check-circle mr-2"></i>
                <span>Delivered at <?php echo formatDateTime($o['delivered_at'], 'h:i A'); ?></span>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
function markDelivered(orderId) {
    if (!confirm('Mark this order as delivered?')) return;

    const formData = new FormData();
    formData.append('action', 'update_status');
    formData.append('order_id', orderId);
    formData.append('status', 'delivered');
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
