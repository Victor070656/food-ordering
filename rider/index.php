<?php
/**
 * Rider Dashboard
 */

require_once dirname(__DIR__) . '/config/config.php';
requireRole('rider');

$page_title = 'Rider Dashboard';

$user = getCurrentUser();
$riderClass = new Rider();
$order = new Order();

// Get current rider
$currentRider = $riderClass->getByUserId($user['id']);
if (!$currentRider) {
    setFlash('error', 'Rider profile not found');
    redirect(SITE_URL . '/public/logout.php');
}

// Get today's deliveries
$todayDeliveries = $riderClass->getTodayDeliveries($currentRider['id']);
$activeDeliveries = $riderClass->getActiveDeliveries($currentRider['id']);
$stats = $riderClass->getStats($currentRider['id']);

require_once INCLUDES_PATH . '/header.php';
?>

<!-- Page Header -->
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Hello, <?php echo htmlspecialchars($user['name']); ?>!</h1>
    <p class="text-gray-500"><?php echo date('l, j M Y'); ?></p>
</div>

<!-- Stats Cards -->
<div class="grid grid-cols-2 gap-4 mb-6">
    <div class="bg-gradient-to-br from-amber-500 to-orange-600 rounded-xl p-5 text-white">
        <div class="flex items-center gap-3 mb-2">
            <i class="fas fa-motorcycle text-2xl opacity-80"></i>
            <span class="text-amber-100">Active Deliveries</span>
        </div>
        <p class="text-4xl font-bold"><?php echo count($activeDeliveries); ?></p>
    </div>
    <div class="bg-white rounded-xl border border-gray-100 p-5 shadow-sm">
        <div class="flex items-center gap-3 mb-2">
            <i class="fas fa-check-circle text-2xl text-green-500"></i>
            <span class="text-gray-500">Completed Today</span>
        </div>
        <p class="text-4xl font-bold text-gray-800"><?php echo $stats['today']['completed'] ?? 0; ?></p>
    </div>
    <div class="bg-white rounded-xl border border-gray-100 p-5 shadow-sm">
        <div class="flex items-center gap-3 mb-2">
            <i class="fas fa-box text-2xl text-blue-500"></i>
            <span class="text-gray-500">Total Today</span>
        </div>
        <p class="text-4xl font-bold text-gray-800"><?php echo $stats['today']['total_deliveries'] ?? 0; ?></p>
    </div>
    <div class="bg-white rounded-xl border border-gray-100 p-5 shadow-sm">
        <div class="flex items-center gap-3 mb-2">
            <i class="fas fa-trophy text-2xl text-purple-500"></i>
            <span class="text-gray-500">All Time</span>
        </div>
        <p class="text-4xl font-bold text-gray-800"><?php echo $stats['all_time']['completed'] ?? 0; ?></p>
    </div>
</div>

<!-- Active Deliveries -->
<?php if (!empty($activeDeliveries)): ?>
<div class="mb-6">
    <h2 class="text-lg font-semibold text-gray-800 mb-4">
        <i class="fas fa-motorcycle text-amber-500 mr-2"></i>Active Deliveries
    </h2>
    <div class="space-y-4">
        <?php foreach ($activeDeliveries as $o): ?>
        <div class="bg-white rounded-xl border-l-4 border-amber-500 shadow-sm p-5">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <span class="font-mono font-bold text-lg text-gray-800"><?php echo htmlspecialchars($o['order_number']); ?></span>
                    <p class="text-sm text-gray-400"><?php echo formatDateTime($o['created_at'], 'h:i A'); ?></p>
                </div>
                <span class="px-4 py-2 bg-amber-100 text-amber-700 rounded-full font-medium">
                    <i class="fas fa-truck mr-2"></i>Out for Delivery
                </span>
            </div>

            <div class="grid md:grid-cols-2 gap-4 mb-4">
                <div class="space-y-2">
                    <div class="flex items-center gap-2 text-sm">
                        <i class="fas fa-user text-gray-400 w-5"></i>
                        <span class="font-medium"><?php echo htmlspecialchars($o['customer_name']); ?></span>
                    </div>
                    <div class="flex items-center gap-2 text-sm">
                        <i class="fas fa-phone text-gray-400 w-5"></i>
                        <a href="tel:<?php echo htmlspecialchars($o['customer_phone']); ?>" class="text-amber-600 font-medium hover:underline">
                            <?php echo htmlspecialchars($o['customer_phone']); ?>
                        </a>
                    </div>
                </div>
                <div class="space-y-2">
                    <div class="flex items-start gap-2 text-sm">
                        <i class="fas fa-map-marker-alt text-gray-400 w-5 mt-0.5"></i>
                        <span class="text-gray-600"><?php echo htmlspecialchars($o['delivery_address']); ?></span>
                    </div>
                    <div class="flex items-center gap-2 text-sm">
                        <i class="fas fa-money-bill text-gray-400 w-5"></i>
                        <span class="font-bold text-gray-800"><?php echo formatCurrency($o['total_amount']); ?></span>
                        <span class="text-gray-400">(<?php echo ucfirst(str_replace('_', ' ', $o['payment_method'])); ?>)</span>
                    </div>
                </div>
            </div>

            <div class="bg-gray-50 rounded-lg p-3 mb-4">
                <p class="text-sm text-gray-500 mb-2">Items to deliver:</p>
                <?php
                $items = $order->getItems($o['id']);
                foreach ($items as $item):
                ?>
                <div class="flex justify-between text-sm">
                    <span><?php echo htmlspecialchars($item['item_name']); ?> × <?php echo $item['quantity']; ?></span>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="flex gap-3">
                <a href="tel:<?php echo htmlspecialchars($o['customer_phone']); ?>" class="flex-1 px-4 py-2.5 border border-gray-200 text-gray-700 rounded-lg hover:bg-gray-50 text-center">
                    <i class="fas fa-phone mr-2"></i>Call Customer
                </a>
                <a href="https://wa.me/<?php echo formatPhoneNumber($o['customer_phone']); ?>" target="_blank" class="flex-1 px-4 py-2.5 bg-green-500 text-white rounded-lg hover:bg-green-600 text-center">
                    <i class="fab fa-whatsapp mr-2"></i>WhatsApp
                </a>
                <button onclick="markDelivered(<?php echo $o['id']; ?>)" class="flex-1 px-4 py-2.5 bg-gradient-to-r from-amber-500 to-orange-600 text-white rounded-lg hover:from-amber-600 hover:to-orange-700">
                    <i class="fas fa-check mr-2"></i>Mark Delivered
                </button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php else: ?>
<div class="bg-white rounded-xl border border-gray-100 p-8 text-center shadow-sm mb-6">
    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
        <i class="fas fa-check text-2xl text-gray-400"></i>
    </div>
    <h3 class="text-lg font-semibold text-gray-800 mb-2">All Caught Up!</h3>
    <p class="text-gray-500">No active deliveries at the moment</p>
</div>
<?php endif; ?>

<!-- Today's Completed -->
<?php if (($stats['today']['completed'] ?? 0) > 0): ?>
<div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
    <div class="p-5 border-b border-gray-100">
        <h2 class="font-semibold text-gray-800">
            <i class="fas fa-check-circle text-green-500 mr-2"></i>Completed Today
        </h2>
    </div>
    <div class="divide-y divide-gray-50">
        <?php
        $completedToday = array_filter($todayDeliveries, fn($d) => $d['status'] === 'delivered');
        foreach ($completedToday as $o):
        ?>
        <div class="p-4 flex items-center justify-between">
            <div>
                <span class="font-mono font-medium text-gray-800"><?php echo htmlspecialchars($o['order_number']); ?></span>
                <p class="text-sm text-gray-400"><?php echo htmlspecialchars($o['customer_name']); ?></p>
            </div>
            <div class="text-right">
                <p class="font-semibold text-gray-800"><?php echo formatCurrency($o['total_amount']); ?></p>
                <p class="text-xs text-green-600"><i class="fas fa-check mr-1"></i>Delivered at <?php echo formatDateTime($o['delivered_at'], 'h:i A'); ?></p>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<script>
function markDelivered(orderId) {
    if (!confirm('Mark this order as delivered?')) return;

    const formData = new FormData();
    formData.append('action', 'update_status');
    formData.append('order_id', orderId);
    formData.append('status', 'delivered');
    formData.append('ajax', '1');

    fetch('<?php echo SITE_URL; ?>/admin/orders.php', { method: 'POST', body: formData })
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
