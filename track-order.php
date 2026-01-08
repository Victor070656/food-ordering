<?php
/**
 * Track Order Page
 * Shows detailed order tracking with timeline
 */

require_once dirname(__FILE__) . '/config/config.php';

// Must be logged in as customer
requireLogin();
if (!hasRole('customer')) {
    redirect(SITE_URL . '/auth.php');
}

$page_title = 'Track Order';

$order = new Order();
$customerUser = new CustomerUser();

$profile = $customerUser->getProfile($_SESSION['user_id']);
$orderId = $_GET['id'] ?? null;

if (!$orderId) {
    redirect(SITE_URL . '/dashboard.php');
}

// Get order details and verify it belongs to this customer
$orderDetails = $order->getById($orderId);

if (!$orderDetails || $orderDetails['customer_id'] != $profile['customer_id']) {
    redirect(SITE_URL . '/dashboard.php');
}

// Get order items
$orderItems = $order->getItems($orderId);

// Status timeline
$statusSteps = [
    'pending' => ['icon' => 'fa-clock', 'label' => 'Order Placed', 'desc' => 'Your order has been received'],
    'preparing' => ['icon' => 'fa-fire', 'label' => 'Preparing', 'desc' => 'Your food is being cooked'],
    'out_for_delivery' => ['icon' => 'fa-motorcycle', 'label' => 'Out for Delivery', 'desc' => 'Rider is on the way'],
    'delivered' => ['icon' => 'fa-check-circle', 'label' => 'Delivered', 'desc' => 'Enjoy your meal!'],
];

$statusOrder = ['pending', 'preparing', 'out_for_delivery', 'delivered'];
$currentStatusIndex = array_search($orderDetails['status'], $statusOrder);
if ($currentStatusIndex === false) $currentStatusIndex = 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        amber: { 500: '#f59e0b', 600: '#d97706' }
                    }
                }
            }
        }
    </script>
    <style>
        @keyframes pulse-ring {
            0% { transform: scale(0.8); opacity: 1; }
            100% { transform: scale(1.5); opacity: 0; }
        }
        .pulse-ring {
            animation: pulse-ring 2s infinite;
        }
    </style>
</head>
<body class="bg-gray-50">

<!-- Navigation -->
<nav class="bg-white shadow-sm sticky top-0 z-40">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            <a href="<?php echo SITE_URL; ?>/dashboard.php" class="flex items-center gap-2 text-gray-600">
                <i class="fas fa-arrow-left"></i>
                <span>Back to Dashboard</span>
            </a>
            <a href="<?php echo SITE_URL; ?>/" class="text-amber-500 font-medium">Order More</a>
        </div>
    </div>
</nav>

<main class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    <!-- Order Header -->
    <div class="bg-white rounded-2xl shadow-sm p-6 mb-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <p class="text-sm text-gray-400">Order Number</p>
                <p class="text-2xl font-bold text-gray-800">#<?php echo htmlspecialchars($orderDetails['order_number']); ?></p>
            </div>
            <div class="text-right">
                <p class="text-sm text-gray-400">Total Amount</p>
                <p class="text-2xl font-bold text-amber-500"><?php echo formatCurrency($orderDetails['total_amount']); ?></p>
            </div>
        </div>
        <div class="flex items-center gap-4 text-sm text-gray-500">
            <span><i class="fas fa-calendar mr-1"></i> <?php echo formatDateTime($orderDetails['created_at']); ?></span>
            <span><i class="fas fa-credit-card mr-1"></i> <?php echo ucfirst(str_replace('_', ' ', $orderDetails['payment_method'])); ?></span>
        </div>
    </div>

    <!-- Status Timeline -->
    <div class="bg-white rounded-2xl shadow-sm p-6 mb-6">
        <h2 class="font-semibold text-gray-800 mb-6">Order Status</h2>

        <div class="relative">
            <!-- Progress Line -->
            <div class="absolute left-4 top-0 bottom-0 w-0.5 bg-gray-200"></div>
            <div class="absolute left-4 top-0 w-0.5 bg-amber-500 transition-all duration-500" style="height: <?php echo ($currentStatusIndex * 75); ?>%"></div>

            <div class="space-y-6">
                <?php foreach ($statusSteps as $index => $step): ?>
                <?php
                $isCompleted = $index <= $currentStatusIndex;
                $isCurrent = $index == $currentStatusIndex;
                $isCancelled = $orderDetails['status'] === 'cancelled';
                $isFailed = $orderDetails['status'] === 'failed';
                ?>
                <div class="relative flex items-start gap-4">
                    <div class="relative z-10 w-8 h-8 rounded-full flex items-center justify-center
                        <?php if ($isCancelled || $isFailed): ?>
                            bg-red-100 text-red-500
                        <?php elseif ($isCurrent): ?>
                            bg-amber-500 text-white
                        <?php elseif ($isCompleted): ?>
                            bg-green-500 text-white
                        <?php else: ?>
                            bg-gray-200 text-gray-400
                        <?php endif; ?>">
                        <?php if ($isCurrent && !($isCancelled || $isFailed)): ?>
                        <div class="absolute inset-0 rounded-full bg-amber-500 pulse-ring"></div>
                        <?php endif; ?>
                        <i class="fas <?php echo $step['icon']; ?> text-sm relative z-10"></i>
                    </div>
                    <div class="flex-1 pt-1">
                        <p class="font-medium text-gray-800 <?php echo $isCurrent ? 'text-amber-500' : ''; ?>">
                            <?php echo $step['label']; ?>
                            <?php if ($isCurrent): ?>
                            <span class="ml-2 text-xs bg-amber-100 text-amber-600 px-2 py-0.5 rounded-full">Current</span>
                            <?php endif; ?>
                        </p>
                        <p class="text-sm text-gray-500"><?php echo $step['desc']; ?></p>
                        <?php if ($isCurrent && $orderDetails['status'] === 'out_for_delivery'): ?>
                        <p class="text-sm text-amber-500 mt-1"><i class="fas fa-clock mr-1"></i> Arriving in 15-30 minutes</p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if ($orderDetails['status'] === 'cancelled'): ?>
        <div class="mt-6 p-4 bg-red-50 rounded-xl flex items-center gap-3 text-red-700">
            <i class="fas fa-times-circle text-xl"></i>
            <div>
                <p class="font-medium">Order Cancelled</p>
                <p class="text-sm">This order has been cancelled. Please contact us for more information.</p>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Order Items -->
    <div class="bg-white rounded-2xl shadow-sm p-6 mb-6">
        <h2 class="font-semibold text-gray-800 mb-4">Order Items</h2>
        <div class="divide-y divide-gray-100">
            <?php foreach ($orderItems as $item): ?>
            <div class="flex items-center justify-between py-3">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-amber-50 rounded-lg flex items-center justify-center">
                        <i class="fas fa-utensils text-amber-500"></i>
                    </div>
                    <div>
                        <p class="font-medium text-gray-800"><?php echo htmlspecialchars($item['item_name']); ?></p>
                        <p class="text-sm text-gray-400">Qty: <?php echo $item['quantity']; ?></p>
                    </div>
                </div>
                <p class="font-medium text-gray-800"><?php echo formatCurrency($item['total_price']); ?></p>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="border-t border-gray-100 mt-4 pt-4 space-y-2">
            <div class="flex justify-between text-sm">
                <span class="text-gray-500">Subtotal</span>
                <span class="text-gray-800"><?php echo formatCurrency($orderDetails['subtotal']); ?></span>
            </div>
            <div class="flex justify-between text-sm">
                <span class="text-gray-500">Delivery Fee</span>
                <span class="text-gray-800"><?php echo formatCurrency($orderDetails['delivery_fee']); ?></span>
            </div>
            <div class="flex justify-between text-lg font-bold pt-2 border-t border-gray-100">
                <span class="text-gray-800">Total</span>
                <span class="text-amber-500"><?php echo formatCurrency($orderDetails['total_amount']); ?></span>
            </div>
        </div>
    </div>

    <!-- Delivery Info -->
    <div class="bg-white rounded-2xl shadow-sm p-6">
        <h2 class="font-semibold text-gray-800 mb-4">Delivery Information</h2>
        <div class="space-y-3">
            <div class="flex items-start gap-3">
                <div class="w-8 h-8 bg-gray-100 rounded-lg flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-map-marker-alt text-gray-500"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-400">Delivery Address</p>
                    <p class="text-gray-800"><?php echo htmlspecialchars($orderDetails['delivery_address']); ?></p>
                </div>
            </div>
            <?php if (!empty($orderDetails['special_instructions'])): ?>
            <div class="flex items-start gap-3">
                <div class="w-8 h-8 bg-gray-100 rounded-lg flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-sticky-note text-gray-500"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-400">Special Instructions</p>
                    <p class="text-gray-800"><?php echo htmlspecialchars($orderDetails['special_instructions']); ?></p>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Actions -->
    <div class="mt-6 flex gap-3">
        <a href="<?php echo SITE_URL; ?>/" class="flex-1 py-3 bg-amber-500 text-white font-semibold rounded-xl hover:bg-amber-600 text-center">
            <i class="fas fa-plus mr-2"></i>Order Again
        </a>
        <button onclick="window.print()" class="px-6 py-3 border border-gray-300 text-gray-700 font-semibold rounded-xl hover:bg-gray-50">
            <i class="fas fa-print"></i>
        </button>
    </div>

</main>

<script>
// Auto-refresh every 30 seconds for active orders
<?php if (in_array($orderDetails['status'], ['pending', 'preparing', 'out_for_delivery'])): ?>
setTimeout(() => {
    location.reload();
}, 30000);
<?php endif; ?>
</script>

</body>
</html>
