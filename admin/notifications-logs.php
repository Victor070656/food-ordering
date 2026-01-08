<?php
/**
 * Admin Notification Logs
 */

require_once dirname(__DIR__) . '/config/config.php';
requireRole('admin');

$page_title = 'Notification Logs';

$notification = new Notification();

$type = $_GET['type'] ?? '';
$channel = $_GET['channel'] ?? '';
$status = $_GET['status'] ?? '';
$page = (int)($_GET['page'] ?? 1);

$filters = ['per_page' => 50, 'page' => $page];
if ($type) $filters['type'] = $type;
if ($channel) $filters['channel'] = $channel;
if ($status) $filters['status'] = $status;

$result = $notification->getLogs($filters);
$logs = $result['logs'];
$pagination = $result['pagination'];

require_once INCLUDES_PATH . '/header.php';
?>

<!-- Page Header -->
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Notification Logs</h1>
    <p class="text-gray-500">View all sent notifications</p>
</div>

<!-- Filters -->
<div class="bg-white rounded-xl border border-gray-100 p-4 mb-6 shadow-sm">
    <form method="GET" action="" class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
            <select name="type" class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                <option value="">All Types</option>
                <option value="order_confirmation" <?php echo $type === 'order_confirmation' ? 'selected' : ''; ?>>Order Confirmation</option>
                <option value="order_preparing" <?php echo $type === 'order_preparing' ? 'selected' : ''; ?>>Order Preparing</option>
                <option value="out_for_delivery" <?php echo $type === 'out_for_delivery' ? 'selected' : ''; ?>>Out for Delivery</option>
                <option value="delivered" <?php echo $type === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                <option value="order_cancelled" <?php echo $type === 'order_cancelled' ? 'selected' : ''; ?>>Order Cancelled</option>
            </select>
        </div>
        <div>
            <select name="channel" class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                <option value="">All Channels</option>
                <option value="sms" <?php echo $channel === 'sms' ? 'selected' : ''; ?>>SMS</option>
                <option value="whatsapp" <?php echo $channel === 'whatsapp' ? 'selected' : ''; ?>>WhatsApp</option>
            </select>
        </div>
        <div>
            <select name="status" class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                <option value="">All Statuses</option>
                <option value="sent" <?php echo $status === 'sent' ? 'selected' : ''; ?>>Sent</option>
                <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                <option value="failed" <?php echo $status === 'failed' ? 'selected' : ''; ?>>Failed</option>
            </select>
        </div>
        <div class="flex gap-2">
            <button type="submit" class="flex-1 px-4 py-2 bg-gray-800 text-white rounded-lg hover:bg-gray-700">Filter</button>
            <a href="notifications-logs.php" class="px-4 py-2 bg-gray-100 text-gray-600 rounded-lg hover:bg-gray-200">Clear</a>
        </div>
    </form>
</div>

<!-- Logs Table -->
<div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-100">
                <tr>
                    <th class="text-left px-6 py-4 text-sm font-semibold text-gray-600">Order</th>
                    <th class="text-left px-6 py-4 text-sm font-semibold text-gray-600">Type</th>
                    <th class="text-left px-6 py-4 text-sm font-semibold text-gray-600">Channel</th>
                    <th class="text-left px-6 py-4 text-sm font-semibold text-gray-600">Recipient</th>
                    <th class="text-left px-6 py-4 text-sm font-semibold text-gray-600">Status</th>
                    <th class="text-left px-6 py-4 text-sm font-semibold text-gray-600">Time</th>
                    <th class="text-left px-6 py-4 text-sm font-semibold text-gray-600">Message</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                <?php if (empty($logs)): ?>
                <tr>
                    <td colspan="7" class="px-6 py-12 text-center text-gray-400">
                        <i class="fas fa-bell-slash text-4xl mb-3"></i>
                        <p>No logs found</p>
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <span class="font-mono text-sm"><?php echo htmlspecialchars($log['order_number']); ?></span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-gray-600 capitalize"><?php echo str_replace('_', ' ', $log['type']); ?></span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 text-xs font-medium rounded-full <?php echo $log['channel'] === 'whatsapp' ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700'; ?>">
                                <?php echo ucfirst($log['channel']); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-gray-600"><?php echo htmlspecialchars($log['recipient_phone']); ?></span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 text-xs font-medium rounded-full <?php echo $log['status'] === 'sent' ? 'bg-green-100 text-green-700' : ($log['status'] === 'failed' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700'); ?>">
                                <?php echo ucfirst($log['status']); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-gray-500"><?php echo formatDateTime($log['created_at'], 'j M h:i A'); ?></span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-gray-600 max-w-xs truncate block"><?php echo htmlspecialchars(substr($log['message'], 0, 50)); ?>...</span>
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
            of <?php echo $pagination['total']; ?> logs
        </p>
        <div class="flex gap-2">
            <?php if ($pagination['has_prev']): ?>
            <a href="?page=<?php echo $pagination['current_page'] - 1; ?><?php echo $type ? '&type='.$type : ''; ?><?php echo $channel ? '&channel='.$channel : ''; ?><?php echo $status ? '&status='.$status : ''; ?>" class="px-4 py-2 border border-gray-200 rounded-lg hover:bg-gray-50">Previous</a>
            <?php endif; ?>
            <?php if ($pagination['has_next']): ?>
            <a href="?page=<?php echo $pagination['current_page'] + 1; ?><?php echo $type ? '&type='.$type : ''; ?><?php echo $channel ? '&channel='.$channel : ''; ?><?php echo $status ? '&status='.$status : ''; ?>" class="px-4 py-2 border border-gray-200 rounded-lg hover:bg-gray-50">Next</a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
