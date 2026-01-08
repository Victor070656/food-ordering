<?php
/**
 * Admin Dashboard
 */

require_once dirname(__DIR__) . '/config/config.php';
requireRole('admin');

$page_title = 'Dashboard';

$order = new Order();
$customer = new Customer();
$rider = new Rider();
$user = new User();

// Get statistics
$stats = $order->getStats();
$todayOrders = $order->getTodayOrders();
$recentOrders = $order->getRecent(5);
$topCustomers = $customer->getTopCustomers(5);
$availableRiders = $rider->getAvailableRiders();

require_once INCLUDES_PATH . '/header.php';
?>

<!-- Page Header -->
<div class="mb-8">
    <h1 class="text-2xl font-bold text-gray-800">Dashboard</h1>
    <p class="text-gray-500">Welcome back, <?php echo htmlspecialchars($currentUser['name'] ?? getCurrentUser()['name']); ?>!</p>
</div>

<!-- Stats Cards -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
    <!-- Today's Orders -->
    <div class="bg-white rounded-xl border border-gray-100 p-5 shadow-sm">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm">Today's Orders</p>
                <p class="text-2xl font-bold text-gray-800 mt-1"><?php echo number_format($stats['today']['total_orders'] ?? 0); ?></p>
            </div>
            <div class="w-12 h-12 bg-amber-50 rounded-xl flex items-center justify-center">
                <i class="fas fa-shopping-bag text-amber-500 text-xl"></i>
            </div>
        </div>
        <p class="text-xs text-gray-400 mt-3">
            <i class="fas fa-arrow-up text-green-500"></i>
            <?php echo number_format($stats['today']['delivered'] ?? 0); ?> delivered today
        </p>
    </div>

    <!-- Today's Revenue -->
    <div class="bg-white rounded-xl border border-gray-100 p-5 shadow-sm">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm">Today's Revenue</p>
                <p class="text-2xl font-bold text-gray-800 mt-1"><?php echo formatCurrency($stats['today']['total_revenue'] ?? 0); ?></p>
            </div>
            <div class="w-12 h-12 bg-green-50 rounded-xl flex items-center justify-center">
                <i class="fas fa-naira-sign text-green-500 text-xl"></i>
            </div>
        </div>
        <p class="text-xs text-gray-400 mt-3">
            <i class="fas fa-chart-line text-green-500"></i>
            This week: <?php echo formatCurrency($stats['week']['total_revenue'] ?? 0); ?>
        </p>
    </div>

    <!-- Pending Deliveries -->
    <div class="bg-white rounded-xl border border-gray-100 p-5 shadow-sm">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm">Pending</p>
                <p class="text-2xl font-bold text-gray-800 mt-1"><?php echo number_format(($stats['today']['pending'] ?? 0) + ($stats['today']['preparing'] ?? 0)); ?></p>
            </div>
            <div class="w-12 h-12 bg-yellow-50 rounded-xl flex items-center justify-center">
                <i class="fas fa-clock text-yellow-500 text-xl"></i>
            </div>
        </div>
        <p class="text-xs text-gray-400 mt-3">
            <i class="fas fa-motorcycle text-purple-500"></i>
            <?php echo number_format($stats['today']['out_for_delivery'] ?? 0); ?> out for delivery
        </p>
    </div>

    <!-- Pending Payments -->
    <div class="bg-white rounded-xl border border-gray-100 p-5 shadow-sm">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm">Pending Payments</p>
                <p class="text-2xl font-bold text-gray-800 mt-1"><?php echo formatCurrency($stats['pending_payments']['amount'] ?? 0); ?></p>
            </div>
            <div class="w-12 h-12 bg-red-50 rounded-xl flex items-center justify-center">
                <i class="fas fa-exclamation-circle text-red-500 text-xl"></i>
            </div>
        </div>
        <p class="text-xs text-gray-400 mt-3">
            <i class="fas fa-receipt"></i>
            <?php echo number_format($stats['pending_payments']['count'] ?? 0); ?> orders pending payment
        </p>
    </div>
</div>

<!-- Quick Actions -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
    <a href="orders.php?action=new" class="bg-gradient-to-br from-amber-500 to-orange-600 rounded-xl p-4 text-white shadow-lg shadow-amber-200 hover:shadow-xl transition-shadow">
        <i class="fas fa-plus text-2xl mb-2"></i>
        <p class="font-semibold">New Order</p>
    </a>
    <a href="customers.php?action=new" class="bg-white rounded-xl p-4 border border-gray-100 text-gray-700 hover:border-amber-200 transition-colors">
        <i class="fas fa-user-plus text-2xl mb-2 text-amber-500"></i>
        <p class="font-semibold">Add Customer</p>
    </a>
    <a href="riders.php" class="bg-white rounded-xl p-4 border border-gray-100 text-gray-700 hover:border-amber-200 transition-colors">
        <i class="fas fa-motorcycle text-2xl mb-2 text-purple-500"></i>
        <p class="font-semibold">Manage Riders</p>
    </a>
    <a href="payments.php" class="bg-white rounded-xl p-4 border border-gray-100 text-gray-700 hover:border-amber-200 transition-colors">
        <i class="fas fa-credit-card text-2xl mb-2 text-green-500"></i>
        <p class="font-semibold">Payments</p>
    </a>
</div>

<div class="grid lg:grid-cols-2 gap-6">
    <!-- Recent Orders -->
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm">
        <div class="flex items-center justify-between p-5 border-b border-gray-100">
            <h2 class="font-semibold text-gray-800">Recent Orders</h2>
            <a href="orders.php" class="text-amber-600 text-sm hover:underline">View All</a>
        </div>
        <div class="divide-y divide-gray-50">
            <?php if (empty($recentOrders)): ?>
            <div class="p-8 text-center text-gray-400">
                <i class="fas fa-inbox text-4xl mb-3"></i>
                <p>No orders yet</p>
            </div>
            <?php else: ?>
                <?php foreach ($recentOrders as $row): ?>
                <div class="p-4 hover:bg-gray-50 transition-colors">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <div class="flex items-center gap-3">
                                <span class="font-mono text-sm font-medium text-gray-800">#<?php echo htmlspecialchars($row['order_number']); ?></span>
                                <?php echo getStatusBadge($row['status']); ?>
                            </div>
                            <p class="text-sm text-gray-500 mt-1"><?php echo htmlspecialchars($row['customer_name']); ?></p>
                        </div>
                        <div class="text-right">
                            <p class="font-semibold text-gray-800"><?php echo formatCurrency($row['total_amount']); ?></p>
                            <p class="text-xs text-gray-400"><?php echo formatDateTime($row['created_at'], 'h:i A'); ?></p>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Today's Orders by Status -->
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm">
        <div class="flex items-center justify-between p-5 border-b border-gray-100">
            <h2 class="font-semibold text-gray-800">Today's Overview</h2>
            <span class="text-sm text-gray-400"><?php echo date('j M Y'); ?></span>
        </div>
        <div class="p-5 space-y-4">
            <?php
            $statusColors = [
                'pending' => 'bg-yellow-100 text-yellow-800',
                'preparing' => 'bg-blue-100 text-blue-800',
                'out_for_delivery' => 'bg-purple-100 text-purple-800',
                'delivered' => 'bg-green-100 text-green-800',
                'failed' => 'bg-red-100 text-red-800',
                'cancelled' => 'bg-gray-100 text-gray-800',
            ];

            $statusIcons = [
                'pending' => 'fa-clock',
                'preparing' => 'fa-fire',
                'out_for_delivery' => 'fa-motorcycle',
                'delivered' => 'fa-check',
                'failed' => 'fa-times',
                'cancelled' => 'fa-ban',
            ];

            $totalToday = $stats['today']['total_orders'] ?? 0;
            ?>

            <?php foreach ($statusColors as $status => $color): ?>
                <?php
                $count = $stats['today'][$status] ?? 0;
                $percent = $totalToday > 0 ? round(($count / $totalToday) * 100) : 0;
                ?>
                <?php if ($count > 0 || in_array($status, ['pending', 'preparing', 'out_for_delivery', 'delivered'])): ?>
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 rounded-lg <?php echo $color; ?> flex items-center justify-center">
                        <i class="fas <?php echo $statusIcons[$status]; ?>"></i>
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-sm font-medium text-gray-700 capitalize"><?php echo str_replace('_', ' ', $status); ?></span>
                            <span class="text-sm text-gray-500"><?php echo $count; ?> orders</span>
                        </div>
                        <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
                            <div class="h-full <?php echo $color; ?> rounded-full" style="width: <?php echo $percent; ?>%"></div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Top Customers -->
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm">
        <div class="flex items-center justify-between p-5 border-b border-gray-100">
            <h2 class="font-semibold text-gray-800">Top Customers</h2>
            <a href="customers.php" class="text-amber-600 text-sm hover:underline">View All</a>
        </div>
        <div class="divide-y divide-gray-50">
            <?php if (empty($topCustomers)): ?>
            <div class="p-8 text-center text-gray-400">
                <i class="fas fa-users text-4xl mb-3"></i>
                <p>No customers yet</p>
            </div>
            <?php else: ?>
                <?php foreach ($topCustomers as $c): ?>
                <div class="p-4 hover:bg-gray-50 transition-colors">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-amber-100 rounded-full flex items-center justify-center text-amber-600 font-semibold">
                                <?php echo strtoupper(substr($c['name'], 0, 1)); ?>
                            </div>
                            <div>
                                <p class="font-medium text-gray-800"><?php echo htmlspecialchars($c['name']); ?></p>
                                <p class="text-xs text-gray-400"><?php echo $c['total_orders']; ?> orders</p>
                            </div>
                        </div>
                        <p class="font-semibold text-amber-600"><?php echo formatCurrency($c['total_spent']); ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Available Riders -->
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm">
        <div class="flex items-center justify-between p-5 border-b border-gray-100">
            <h2 class="font-semibold text-gray-800">Available Riders</h2>
            <a href="riders.php" class="text-amber-600 text-sm hover:underline">Manage</a>
        </div>
        <div class="divide-y divide-gray-50">
            <?php if (empty($availableRiders)): ?>
            <div class="p-8 text-center text-gray-400">
                <i class="fas fa-motorcycle text-4xl mb-3"></i>
                <p>No riders available</p>
            </div>
            <?php else: ?>
                <?php foreach ($availableRiders as $r): ?>
                <div class="p-4 hover:bg-gray-50 transition-colors">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center text-purple-600 font-semibold">
                                <?php echo strtoupper(substr($r['name'], 0, 1)); ?>
                            </div>
                            <div>
                                <p class="font-medium text-gray-800"><?php echo htmlspecialchars($r['name']); ?></p>
                                <p class="text-xs text-gray-400"><?php echo htmlspecialchars($r['vehicle_type'] ?? 'N/A'); ?></p>
                            </div>
                        </div>
                        <span class="px-3 py-1 text-xs font-medium rounded-full bg-green-100 text-green-700">Available</span>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
