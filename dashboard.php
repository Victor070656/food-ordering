<?php
/**
 * Customer Dashboard
 */

require_once dirname(__FILE__) . '/config/config.php';

// Must be logged in as customer
requireLogin();
if (!hasRole('customer')) {
    redirect(SITE_URL . '/auth.php');
}

$page_title = 'My Dashboard';

$customerUser = new CustomerUser();
$order = new Order();
$menuItem = new MenuItem();

$profile = $customerUser->getProfile($_SESSION['user_id']);
$myOrders = $customerUser->getOrders($profile['customer_id'] ?? $_SESSION['customer_id']);
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
</head>
<body class="bg-gray-50">

<!-- Navigation -->
<nav class="bg-white shadow-sm sticky top-0 z-40">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            <a href="<?php echo SITE_URL; ?>/" class="flex items-center gap-2">
                <div class="w-10 h-10 bg-gradient-to-br from-amber-500 to-orange-600 rounded-xl flex items-center justify-center">
                    <i class="fas fa-utensils text-white"></i>
                </div>
                <span class="font-bold text-xl text-gray-800"><?php echo SITE_NAME; ?></span>
            </a>
            <div class="flex items-center gap-4">
                <a href="<?php echo SITE_URL; ?>/" class="text-gray-600 hover:text-amber-600 font-medium">Order Food</a>
                <a href="<?php echo SITE_URL; ?>/logout.php" class="text-red-500 hover:text-red-600 font-medium">Logout</a>
            </div>
        </div>
    </div>
</nav>

<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    <!-- Welcome Section -->
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-800">Welcome, <?php echo htmlspecialchars($profile['name']); ?>!</h1>
        <p class="text-gray-500">Track your orders and manage your account</p>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-xl border border-gray-100 p-5 shadow-sm">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-blue-50 rounded-xl flex items-center justify-center">
                    <i class="fas fa-shopping-bag text-blue-500 text-xl"></i>
                </div>
                <div>
                    <p class="text-gray-500 text-sm">Total Orders</p>
                    <p class="text-2xl font-bold text-gray-800"><?php echo $profile['total_orders'] ?? 0; ?></p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 p-5 shadow-sm">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-green-50 rounded-xl flex items-center justify-center">
                    <i class="fas fa-naira-sign text-green-500 text-xl"></i>
                </div>
                <div>
                    <p class="text-gray-500 text-sm">Total Spent</p>
                    <p class="text-xl font-bold text-gray-800"><?php echo formatCurrency($profile['total_spent'] ?? 0); ?></p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 p-5 shadow-sm">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-yellow-50 rounded-xl flex items-center justify-center">
                    <i class="fas fa-clock text-yellow-500 text-xl"></i>
                </div>
                <div>
                    <p class="text-gray-500 text-sm">Pending</p>
                    <p class="text-2xl font-bold text-gray-800"><?php echo count(array_filter($myOrders, fn($o) => in_array($o['status'], ['pending', 'preparing']))); ?></p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 p-5 shadow-sm">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-green-50 rounded-xl flex items-center justify-center">
                    <i class="fas fa-check-circle text-green-500 text-xl"></i>
                </div>
                <div>
                    <p class="text-gray-500 text-sm">Completed</p>
                    <p class="text-2xl font-bold text-gray-800"><?php echo count(array_filter($myOrders, fn($o) => $o['status'] === 'delivered')); ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="grid grid-cols-2 gap-4 mb-8">
        <a href="<?php echo SITE_URL; ?>/" class="bg-gradient-to-br from-amber-500 to-orange-600 rounded-xl p-5 text-white shadow-lg hover:shadow-xl transition-shadow flex items-center gap-4">
            <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center">
                <i class="fas fa-utensils text-2xl"></i>
            </div>
            <div>
                <p class="font-semibold text-lg">Order Food</p>
                <p class="text-amber-100 text-sm">Browse our menu</p>
            </div>
        </a>
        <a href="profile.php" class="bg-white rounded-xl border border-gray-100 p-5 hover:border-amber-200 transition-colors flex items-center gap-4 shadow-sm">
            <div class="w-12 h-12 bg-blue-50 rounded-xl flex items-center justify-center">
                <i class="fas fa-user text-blue-500 text-xl"></i>
            </div>
            <div>
                <p class="font-semibold text-gray-800">My Profile</p>
                <p class="text-gray-400 text-sm">Update your details</p>
            </div>
        </a>
    </div>

    <!-- My Orders -->
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm">
        <div class="p-5 border-b border-gray-100 flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-gray-800">My Orders</h2>
                <p class="text-sm text-gray-400">Track your order history</p>
            </div>
        </div>

        <?php if (empty($myOrders)): ?>
        <div class="p-12 text-center text-gray-400">
            <i class="fas fa-shopping-basket text-4xl mb-3"></i>
            <p>No orders yet</p>
            <a href="<?php echo SITE_URL; ?>/" class="inline-block mt-4 px-6 py-2 bg-amber-500 text-white rounded-lg hover:bg-amber-600">
                Place Your First Order
            </a>
        </div>
        <?php else: ?>
        <div class="divide-y divide-gray-50">
            <?php foreach ($myOrders as $o): ?>
            <div class="p-5 hover:bg-gray-50">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-3">
                        <a href="track-order.php?id=<?php echo $o['id']; ?>" class="font-mono font-bold text-gray-800 hover:text-amber-500">#<?php echo htmlspecialchars($o['order_number']); ?></a>
                        <?php
                        $badges = [
                            'pending' => '<span class="px-3 py-1 text-xs font-medium rounded-full bg-yellow-100 text-yellow-800">Pending</span>',
                            'preparing' => '<span class="px-3 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800">Preparing</span>',
                            'out_for_delivery' => '<span class="px-3 py-1 text-xs font-medium rounded-full bg-purple-100 text-purple-800">Out for Delivery</span>',
                            'delivered' => '<span class="px-3 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">Delivered</span>',
                            'cancelled' => '<span class="px-3 py-1 text-xs font-medium rounded-full bg-red-100 text-red-800">Cancelled</span>',
                        ];
                        echo $badges[$o['status']] ?? $o['status'];
                        ?>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-sm text-gray-500"><?php echo formatDateTime($o['created_at'], 'j M h:i A'); ?></span>
                        <a href="track-order.php?id=<?php echo $o['id']; ?>" class="text-sm text-amber-500 hover:text-amber-600">
                            <i class="fas fa-eye"></i> View
                        </a>
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600"><?php echo $o['items_count']; ?> items</p>
                        <p class="text-xs text-gray-400"><?php echo ucfirst(str_replace('_', ' ', $o['payment_method'])); ?></p>
                    </div>
                    <p class="font-bold text-amber-600"><?php echo formatCurrency($o['total_amount']); ?></p>
                </div>

                <?php if ($o['status'] === 'out_for_delivery'): ?>
                <div class="mt-3 p-3 bg-purple-50 rounded-lg flex items-center justify-between">
                    <div class="flex items-center gap-2 text-sm text-purple-700">
                        <i class="fas fa-motorcycle"></i>
                        <span>Your order is on the way! Expected arrival: 15-30 mins</span>
                    </div>
                    <a href="track-order.php?id=<?php echo $o['id']; ?>" class="text-sm text-purple-600 hover:text-purple-700 font-medium">Track</a>
                </div>
                <?php elseif ($o['status'] === 'preparing'): ?>
                <div class="mt-3 p-3 bg-blue-50 rounded-lg flex items-center justify-between">
                    <div class="flex items-center gap-2 text-sm text-blue-700">
                        <i class="fas fa-fire"></i>
                        <span>Your order is being prepared...</span>
                    </div>
                    <a href="track-order.php?id=<?php echo $o['id']; ?>" class="text-sm text-blue-600 hover:text-blue-700 font-medium">Track</a>
                </div>
                <?php elseif ($o['status'] === 'pending'): ?>
                <div class="mt-3 p-3 bg-yellow-50 rounded-lg flex items-center justify-between">
                    <div class="flex items-center gap-2 text-sm text-yellow-700">
                        <i class="fas fa-clock"></i>
                        <span>Your order is being confirmed...</span>
                    </div>
                    <a href="track-order.php?id=<?php echo $o['id']; ?>" class="text-sm text-yellow-600 hover:text-yellow-700 font-medium">Track</a>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

</main>

<!-- Footer -->
<footer class="bg-white border-t border-gray-100 py-8 mt-12">
    <div class="max-w-7xl mx-auto px-4 text-center text-gray-400 text-sm">
        <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.</p>
    </div>
</footer>

</body>
</html>
