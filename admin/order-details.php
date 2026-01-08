<?php
/**
 * Admin Order Details Page
 */

require_once dirname(__DIR__) . '/config/config.php';
requireRole('admin');

$page_title = 'Order Details';

$order = new Order();
$rider = new Rider();

$orderId = $_GET['id'] ?? 0;

if (!$orderId) {
    setFlash('error', 'Order ID is required');
    redirect(SITE_URL . '/admin/orders.php');
}

$orderData = $order->getById($orderId);

if (!$orderData) {
    setFlash('error', 'Order not found');
    redirect(SITE_URL . '/admin/orders.php');
}

// Get order items
$items = $order->getItems($orderId);

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = ['success' => false, 'message' => 'Invalid action'];

    switch ($_POST['action'] ?? '') {
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

        case 'update_payment_status':
            $response = $order->update($_POST['order_id'], ['payment_status' => $_POST['payment_status']]);
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

    // Reload order data
    $orderData = $order->getById($orderId);
    $items = $order->getItems($orderId);
}

$riders = $rider->getAvailableRiders();

require_once INCLUDES_PATH . '/header.php';
?>

<!-- Back Button -->
<div class="mb-6">
    <a href="orders.php" class="inline-flex items-center gap-2 text-gray-600 hover:text-amber-600 font-medium">
        <i class="fas fa-arrow-left"></i>
        <span>Back to Orders</span>
    </a>
</div>

<!-- Page Header -->
<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Order Details</h1>
        <p class="text-gray-500">Order #<?php echo htmlspecialchars($orderData['order_number']); ?></p>
    </div>
    <div class="flex items-center gap-3">
        <span class="text-sm text-gray-500">Created: <?php echo formatDateTime($orderData['created_at'], 'j M Y, h:i A'); ?></span>
    </div>
</div>

<div class="grid lg:grid-cols-3 gap-6">
    <!-- Left Column - Order Details -->
    <div class="lg:col-span-2 space-y-6">
        <!-- Order Status -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm">
            <div class="p-5 border-b border-gray-100">
                <h2 class="font-semibold text-gray-800">Order Status</h2>
            </div>
            <div class="p-5">
                <div class="flex flex-wrap gap-2 mb-4">
                    <?php
                    $statuses = ['pending', 'preparing', 'out_for_delivery', 'delivered', 'failed', 'cancelled'];
                    foreach ($statuses as $status):
                        $isActive = $orderData['status'] === $status;
                        $baseClasses = 'px-4 py-2 text-sm font-medium rounded-xl transition-all ';
                        $statusClasses = [
                            'pending' => 'bg-yellow-100 text-yellow-800 hover:bg-yellow-200',
                            'preparing' => 'bg-blue-100 text-blue-800 hover:bg-blue-200',
                            'out_for_delivery' => 'bg-purple-100 text-purple-800 hover:bg-purple-200',
                            'delivered' => 'bg-green-100 text-green-800 hover:bg-green-200',
                            'failed' => 'bg-red-100 text-red-800 hover:bg-red-200',
                            'cancelled' => 'bg-red-100 text-red-800 hover:bg-red-200',
                        ];
                    ?>
                    <form method="POST" class="inline">
                        <input type="hidden" name="action" value="update_status">
                        <input type="hidden" name="order_id" value="<?php echo $orderId; ?>">
                        <input type="hidden" name="status" value="<?php echo $status; ?>">
                        <button type="submit" class="<?php echo $baseClasses . $statusClasses[$status]; ?>" <?php echo $isActive ? 'disabled' : ''; ?>>
                            <?php echo ucfirst(str_replace('_', ' ', $status)); ?>
                            <?php if ($isActive): ?>
                            <i class="fas fa-check ml-1"></i>
                            <?php endif; ?>
                        </button>
                    </form>
                    <?php endforeach; ?>
                </div>

                <!-- Status Timeline -->
                <div class="relative">
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 rounded-full <?php echo in_array($orderData['status'], ['pending', 'preparing', 'out_for_delivery', 'delivered']) ? 'bg-green-500' : 'bg-gray-300'; ?>"></div>
                            <span class="text-sm text-gray-600">Pending</span>
                        </div>
                        <div class="flex-1 h-1 mx-2 <?php echo in_array($orderData['status'], ['preparing', 'out_for_delivery', 'delivered']) ? 'bg-green-500' : 'bg-gray-200'; ?>"></div>
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 rounded-full <?php echo in_array($orderData['status'], ['preparing', 'out_for_delivery', 'delivered']) ? 'bg-green-500' : 'bg-gray-300'; ?>"></div>
                            <span class="text-sm text-gray-600">Preparing</span>
                        </div>
                        <div class="flex-1 h-1 mx-2 <?php echo in_array($orderData['status'], ['out_for_delivery', 'delivered']) ? 'bg-green-500' : 'bg-gray-200'; ?>"></div>
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 rounded-full <?php echo in_array($orderData['status'], ['out_for_delivery', 'delivered']) ? 'bg-green-500' : 'bg-gray-300'; ?>"></div>
                            <span class="text-sm text-gray-600">Out</span>
                        </div>
                        <div class="flex-1 h-1 mx-2 <?php echo $orderData['status'] === 'delivered' ? 'bg-green-500' : 'bg-gray-200'; ?>"></div>
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 rounded-full <?php echo $orderData['status'] === 'delivered' ? 'bg-green-500' : 'bg-gray-300'; ?>"></div>
                            <span class="text-sm text-gray-600">Delivered</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Customer & Delivery Info -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm">
            <div class="p-5 border-b border-gray-100">
                <h2 class="font-semibold text-gray-800">Customer Information</h2>
            </div>
            <div class="p-5">
                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <p class="text-sm text-gray-500 mb-1">Customer Name</p>
                        <p class="font-medium text-gray-800"><?php echo htmlspecialchars($orderData['customer_name']); ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 mb-1">Phone Number</p>
                        <p class="font-medium text-gray-800">
                            <a href="tel:<?php echo htmlspecialchars($orderData['customer_phone']); ?>" class="text-amber-600 hover:underline">
                                <?php echo htmlspecialchars($orderData['customer_phone']); ?>
                            </a>
                        </p>
                    </div>
                    <div class="md:col-span-2">
                        <p class="text-sm text-gray-500 mb-1">Delivery Address</p>
                        <p class="text-gray-800"><?php echo nl2br(htmlspecialchars($orderData['delivery_address'])); ?></p>
                    </div>
                    <?php if (!empty($orderData['special_instructions'])): ?>
                    <div class="md:col-span-2">
                        <p class="text-sm text-gray-500 mb-1">Special Instructions</p>
                        <p class="text-gray-800 bg-amber-50 p-3 rounded-xl"><?php echo nl2br(htmlspecialchars($orderData['special_instructions'])); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Order Items -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm">
            <div class="p-5 border-b border-gray-100">
                <h2 class="font-semibold text-gray-800">Order Items</h2>
            </div>
            <div class="p-5">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="text-left text-sm text-gray-500 border-b border-gray-100">
                                <th class="pb-3">Item</th>
                                <th class="pb-3 text-center">Qty</th>
                                <th class="pb-3 text-right">Price</th>
                                <th class="pb-3 text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                            <tr class="border-b border-gray-50">
                                <td class="py-3">
                                    <p class="font-medium text-gray-800"><?php echo htmlspecialchars($item['item_name']); ?></p>
                                </td>
                                <td class="py-3 text-center">
                                    <span class="px-2 py-1 bg-gray-100 rounded-lg text-sm"><?php echo $item['quantity']; ?></span>
                                </td>
                                <td class="py-3 text-right text-gray-600"><?php echo formatCurrency($item['unit_price']); ?></td>
                                <td class="py-3 text-right font-medium text-gray-800"><?php echo formatCurrency($item['total_price']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Order Summary -->
                <div class="mt-6 pt-4 border-t border-gray-100 space-y-2">
                    <div class="flex justify-between text-gray-600">
                        <span>Subtotal</span>
                        <span><?php echo formatCurrency($orderData['subtotal']); ?></span>
                    </div>
                    <div class="flex justify-between text-gray-600">
                        <span>Delivery Fee</span>
                        <span><?php echo formatCurrency($orderData['delivery_fee']); ?></span>
                    </div>
                    <div class="flex justify-between text-xl font-bold pt-2 border-t border-gray-200">
                        <span>Total</span>
                        <span class="text-amber-600"><?php echo formatCurrency($orderData['total_amount']); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column - Actions -->
    <div class="space-y-6">
        <!-- Payment Info -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm">
            <div class="p-5 border-b border-gray-100">
                <h2 class="font-semibold text-gray-800">Payment</h2>
            </div>
            <div class="p-5 space-y-4">
                <div>
                    <p class="text-sm text-gray-500 mb-1">Payment Method</p>
                    <p class="font-medium text-gray-800">
                        <i class="fas <?php echo $orderData['payment_method'] === 'cash_on_delivery' ? 'fa-money-bill text-green-500' : ($orderData['payment_method'] === 'bank_transfer' ? 'fa-university text-blue-500' : 'fa-credit-card text-purple-500'); ?> mr-2"></i>
                        <?php echo ucfirst(str_replace('_', ' ', $orderData['payment_method'])); ?>
                    </p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 mb-1">Payment Status</p>
                    <div class="flex items-center gap-2">
                        <?php echo getStatusBadge($orderData['payment_status']); ?>
                        <form method="POST" class="inline">
                            <input type="hidden" name="action" value="update_payment_status">
                            <input type="hidden" name="order_id" value="<?php echo $orderId; ?>">
                            <select name="payment_status" onchange="this.form.submit()" class="text-sm border border-gray-200 rounded-lg px-2 py-1">
                                <option value="">Change...</option>
                                <option value="paid">Mark Paid</option>
                                <option value="pending">Mark Pending</option>
                                <option value="failed">Mark Failed</option>
                            </select>
                        </form>
                    </div>
                </div>
                <?php if (!empty($orderData['payment_screenshot'])): ?>
                <div>
                    <p class="text-sm text-gray-500 mb-2">Payment Screenshot</p>
                    <button onclick="viewScreenshot('<?php echo SITE_URL . '/' . htmlspecialchars($orderData['payment_screenshot']); ?>')" class="flex items-center gap-2 px-3 py-2 bg-amber-50 text-amber-700 rounded-lg hover:bg-amber-100">
                        <i class="fas fa-image"></i>
                        <span class="text-sm">View Screenshot</span>
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Assign Rider -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm">
            <div class="p-5 border-b border-gray-100">
                <h2 class="font-semibold text-gray-800">Delivery Rider</h2>
            </div>
            <div class="p-5">
                <?php if ($orderData['rider_id']): ?>
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-motorcycle text-purple-500"></i>
                    </div>
                    <div>
                        <p class="font-medium text-gray-800"><?php echo htmlspecialchars($orderData['rider_name']); ?></p>
                        <p class="text-sm text-gray-500"><?php echo htmlspecialchars($orderData['rider_phone'] ?? ''); ?></p>
                    </div>
                </div>
                <?php if (in_array($orderData['status'], ['pending', 'preparing'])): ?>
                <form method="POST">
                    <input type="hidden" name="action" value="assign_rider">
                    <input type="hidden" name="order_id" value="<?php echo $orderId; ?>">
                    <input type="hidden" name="rider_id" value="">
                    <button type="submit" onclick="return confirm('Remove rider assignment?')" class="w-full px-4 py-2 text-red-600 border border-red-200 rounded-xl hover:bg-red-50">
                        <i class="fas fa-times mr-1"></i> Remove Rider
                    </button>
                </form>
                <?php endif; ?>
                <?php else: ?>
                <p class="text-gray-400 text-sm mb-3">No rider assigned</p>
                <?php if (in_array($orderData['status'], ['pending', 'preparing'])): ?>
                <form method="POST">
                    <input type="hidden" name="action" value="assign_rider">
                    <input type="hidden" name="order_id" value="<?php echo $orderId; ?>">
                    <select name="rider_id" required class="w-full px-3 py-2 border border-gray-200 rounded-xl mb-2 focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                        <option value="">Select Rider</option>
                        <?php foreach ($riders as $r): ?>
                        <option value="<?php echo $r['id']; ?>"><?php echo htmlspecialchars($r['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="w-full px-4 py-2 bg-gradient-to-r from-amber-500 to-orange-600 text-white rounded-xl hover:from-amber-600 hover:to-orange-700">
                        <i class="fas fa-motorcycle mr-1"></i> Assign Rider
                    </button>
                </form>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm">
            <div class="p-5 border-b border-gray-100">
                <h2 class="font-semibold text-gray-800">Quick Actions</h2>
            </div>
            <div class="p-5 space-y-2">
                <a href="tel:<?php echo htmlspecialchars($orderData['customer_phone']); ?>" class="flex items-center gap-3 px-4 py-3 text-gray-700 hover:bg-gray-50 rounded-xl">
                    <i class="fas fa-phone text-green-500 w-5"></i>
                    <span>Call Customer</span>
                </a>
                <a href="orders.php?search=<?php echo urlencode($orderData['customer_phone']); ?>" class="flex items-center gap-3 px-4 py-3 text-gray-700 hover:bg-gray-50 rounded-xl">
                    <i class="fas fa-history text-blue-500 w-5"></i>
                    <span>View Order History</span>
                </a>
                <?php if (!in_array($orderData['status'], ['delivered', 'cancelled', 'failed'])): ?>
                <form method="POST">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="order_id" value="<?php echo $orderId; ?>">
                    <input type="hidden" name="status" value="cancelled">
                    <button type="submit" onclick="return confirm('Cancel this order?')" class="w-full flex items-center gap-3 px-4 py-3 text-red-600 hover:bg-red-50 rounded-xl">
                        <i class="fas fa-times-circle w-5"></i>
                        <span>Cancel Order</span>
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>

        <!-- Timestamps -->
        <div class="bg-gray-50 rounded-xl p-5">
            <p class="text-sm text-gray-500 mb-2">Order Timestamps</p>
            <div class="space-y-1 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-500">Created</span>
                    <span class="text-gray-700"><?php echo formatDateTime($orderData['created_at'], 'j M h:i A'); ?></span>
                </div>
                <?php if ($orderData['updated_at'] !== $orderData['created_at']): ?>
                <div class="flex justify-between">
                    <span class="text-gray-500">Updated</span>
                    <span class="text-gray-700"><?php echo formatDateTime($orderData['updated_at'], 'j M h:i A'); ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Screenshot Modal -->
<div id="screenshotModal" class="hidden fixed inset-0 bg-black/80 z-50 flex items-center justify-center p-4">
    <div class="relative max-w-2xl w-full">
        <button onclick="closeScreenshotModal()" class="absolute -top-12 right-0 text-white hover:text-gray-300">
            <i class="fas fa-times text-2xl"></i>
        </button>
        <img id="screenshotImage" src="" alt="Payment Screenshot" class="w-full rounded-xl">
    </div>
</div>

<script>
function viewScreenshot(url) {
    document.getElementById('screenshotImage').src = url;
    document.getElementById('screenshotModal').classList.remove('hidden');
}

function closeScreenshotModal() {
    document.getElementById('screenshotModal').classList.add('hidden');
}

// Close modal on escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeScreenshotModal();
    }
});
</script>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
