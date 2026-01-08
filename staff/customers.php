<?php
/**
 * Staff Customers Page
 */

require_once dirname(__DIR__) . '/config/config.php';
requireAnyRole(['admin', 'staff']);

$page_title = 'Customers';

$customer = new Customer();

$search = $_GET['search'] ?? '';
$topCustomers = $customer->getTopCustomers(20);

require_once INCLUDES_PATH . '/header.php';
?>

<!-- Page Header -->
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Customers</h1>
    <p class="text-gray-500">View customer information</p>
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

<!-- Top Customers -->
<div class="bg-white rounded-xl border border-gray-100 shadow-sm">
    <div class="p-5 border-b border-gray-100">
        <h2 class="font-semibold text-gray-800">Top Customers</h2>
        <p class="text-sm text-gray-400">Most frequent customers</p>
    </div>

    <!-- Mobile Card View -->
    <div class="md:hidden space-y-4 p-4">
        <?php if (empty($topCustomers)): ?>
        <div class="text-center py-12 text-gray-400">
            <i class="fas fa-users text-4xl mb-3"></i>
            <p>No customers found</p>
        </div>
        <?php else: ?>
            <?php foreach ($topCustomers as $c): ?>
            <div class="bg-gray-50 rounded-xl p-4 space-y-3">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-amber-100 rounded-full flex items-center justify-center text-amber-600 font-bold text-lg">
                        <?php echo strtoupper(substr($c['name'], 0, 1)); ?>
                    </div>
                    <div class="flex-1">
                        <p class="font-bold text-gray-800"><?php echo htmlspecialchars($c['name']); ?></p>
                        <a href="tel:<?php echo htmlspecialchars($c['phone']); ?>" class="text-amber-600 hover:underline text-sm"><?php echo htmlspecialchars($c['phone']); ?></a>
                    </div>
                </div>
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
                    <th class="text-left px-4 py-3 text-sm font-semibold text-gray-600">Phone</th>
                    <th class="text-left px-4 py-3 text-sm font-semibold text-gray-600">Orders</th>
                    <th class="text-left px-4 py-3 text-sm font-semibold text-gray-600">Total Spent</th>
                    <th class="text-left px-4 py-3 text-sm font-semibold text-gray-600">Last Order</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                <?php if (empty($topCustomers)): ?>
                <tr>
                    <td colspan="5" class="px-4 py-12 text-center text-gray-400">
                        <i class="fas fa-users text-4xl mb-3"></i>
                        <p>No customers found</p>
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($topCustomers as $c): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-amber-100 rounded-full flex items-center justify-center text-amber-600 font-semibold">
                                    <?php echo strtoupper(substr($c['name'], 0, 1)); ?>
                                </div>
                                <span class="font-medium text-gray-800"><?php echo htmlspecialchars($c['name']); ?></span>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <a href="tel:<?php echo htmlspecialchars($c['phone']); ?>" class="text-amber-600 hover:underline"><?php echo htmlspecialchars($c['phone']); ?></a>
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-sm font-medium bg-amber-100 text-amber-800">
                                <?php echo $c['total_orders']; ?>
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="font-semibold text-gray-800"><?php echo formatCurrency($c['total_spent']); ?></span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="text-sm text-gray-600"><?php echo $c['last_order_date'] ? formatDate($c['last_order_date']) : '-'; ?></span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
