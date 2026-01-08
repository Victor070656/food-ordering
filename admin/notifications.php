<?php
/**
 * Admin Notifications Management
 */

require_once dirname(__DIR__) . '/config/config.php';
requireRole('admin');

$page_title = 'Notifications';

$notification = new Notification();

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = ['success' => false, 'message' => 'Invalid action'];

    switch ($_POST['action'] ?? '') {
        case 'update_template':
            $response = $notification->updateTemplate(
                $_POST['template_id'],
                $_POST['sms_template'],
                $_POST['whatsapp_template'],
                isset($_POST['is_active']) ? 1 : 0
            );
            break;
    }

    if ($response['success']) {
        setFlash('success', $response['message'] ?? 'Template updated successfully');
    } else {
        setFlash('error', $response['message'] ?? 'Failed to update template');
    }

    redirect(SITE_URL . '/admin/notifications.php');
}

require_once INCLUDES_PATH . '/header.php';

$templates = $notification->getTemplates();
$filters = ['per_page' => 20];
$logs = $notification->getLogs($filters);
?>

<!-- Page Header -->
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Notifications</h1>
    <p class="text-gray-500">Manage SMS and WhatsApp notification templates</p>
</div>

<!-- Info Banner -->
<div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-6">
    <div class="flex items-start gap-3">
        <i class="fas fa-info-circle text-blue-500 mt-1"></i>
        <div>
            <p class="text-blue-800 font-medium">SMS/WhatsApp Notifications</p>
            <p class="text-blue-600 text-sm">Notifications are currently in <strong>logging mode</strong>. To enable actual SMS/WhatsApp sending, configure your API credentials. Placeholders: {customer_name}, {order_number}, {total_amount}, {rider_name}, {rider_phone}</p>
        </div>
    </div>
</div>

<!-- Notification Templates -->
<div class="bg-white rounded-xl border border-gray-100 shadow-sm mb-6">
    <div class="p-5 border-b border-gray-100">
        <h2 class="font-semibold text-gray-800">Message Templates</h2>
        <p class="text-sm text-gray-400">Customize automated messages sent to customers</p>
    </div>
    <div class="divide-y divide-gray-50">
        <?php foreach ($templates as $t): ?>
        <div class="p-5">
            <form method="POST" action="">
                <input type="hidden" name="action" value="update_template">
                <input type="hidden" name="template_id" value="<?php echo $t['id']; ?>">

                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                    <div>
                        <h3 class="font-medium text-gray-800 capitalize"><?php echo str_replace('_', ' ', $t['type']); ?></h3>
                        <p class="text-sm text-gray-400">Sent when order status changes to <?php echo str_replace('_', ' ', $t['type']); ?></p>
                    </div>
                    <div class="flex items-center gap-2">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="is_active" value="1" <?php echo $t['is_active'] ? 'checked' : ''; ?> class="w-4 h-4 text-amber-500 rounded focus:ring-amber-500">
                            <span class="text-sm text-gray-600">Active</span>
                        </label>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">SMS Template</label>
                        <textarea name="sms_template" rows="3" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500 text-sm font-mono"><?php echo htmlspecialchars($t['sms_template']); ?></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">WhatsApp Template</label>
                        <textarea name="whatsapp_template" rows="3" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500 text-sm font-mono"><?php echo htmlspecialchars($t['whatsapp_template']); ?></textarea>
                    </div>
                </div>

                <div class="flex justify-end mt-4">
                    <button type="submit" class="px-4 py-2 bg-amber-500 text-white rounded-lg hover:bg-amber-600 text-sm">
                        <i class="fas fa-save mr-2"></i>Save Template
                    </button>
                </div>
            </form>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Recent Notification Logs -->
<div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
    <div class="p-5 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h2 class="font-semibold text-gray-800">Notification Logs</h2>
            <p class="text-sm text-gray-400">Recent sent/failed notifications</p>
        </div>
        <a href="notifications-logs.php" class="text-amber-600 text-sm hover:underline">View All</a>
    </div>

    <!-- Mobile Card View -->
    <div class="md:hidden space-y-4 p-4">
        <?php if (empty($logs['logs'])): ?>
        <div class="text-center py-12 text-gray-400">
            <i class="fas fa-bell-slash text-4xl mb-3"></i>
            <p>No notifications sent yet</p>
        </div>
        <?php else: ?>
            <?php foreach (array_slice($logs['logs'], 0, 10) as $log): ?>
            <div class="bg-gray-50 rounded-xl p-4 space-y-3">
                <div class="flex items-center justify-between">
                    <span class="font-mono font-bold text-gray-800"><?php echo htmlspecialchars($log['order_number']); ?></span>
                    <?php
                    $statusColors = [
                        'sent' => 'bg-green-100 text-green-700',
                        'failed' => 'bg-red-100 text-red-700',
                        'pending' => 'bg-yellow-100 text-yellow-700'
                    ];
                    $statusColor = $statusColors[$log['status']] ?? 'bg-gray-100 text-gray-700';
                    ?>
                    <span class="px-3 py-1 text-xs font-medium rounded-full <?php echo $statusColor; ?>">
                        <?php echo ucfirst($log['status']); ?>
                    </span>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Type:</p>
                    <p class="text-gray-800 capitalize"><?php echo str_replace('_', ' ', $log['type']); ?></p>
                </div>
                <div class="flex items-center justify-between text-sm">
                    <span class="text-gray-500">Channel:</span>
                    <span class="px-3 py-1 text-xs font-medium rounded-full <?php echo $log['channel'] === 'whatsapp' ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700'; ?>">
                        <?php echo ucfirst($log['channel']); ?>
                    </span>
                </div>
                <div class="flex items-center justify-between text-sm">
                    <span class="text-gray-500">Recipient:</span>
                    <span class="text-gray-700"><?php echo htmlspecialchars($log['recipient_phone']); ?></span>
                </div>
                <div class="flex items-center justify-between text-sm">
                    <span class="text-gray-500">Time:</span>
                    <span class="text-gray-700"><?php echo formatDateTime($log['created_at'], 'j M h:i A'); ?></span>
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
                    <th class="text-left px-6 py-3 text-sm font-semibold text-gray-600">Order</th>
                    <th class="text-left px-6 py-3 text-sm font-semibold text-gray-600">Type</th>
                    <th class="text-left px-6 py-3 text-sm font-semibold text-gray-600">Channel</th>
                    <th class="text-left px-6 py-3 text-sm font-semibold text-gray-600">Recipient</th>
                    <th class="text-left px-6 py-3 text-sm font-semibold text-gray-600">Status</th>
                    <th class="text-left px-6 py-3 text-sm font-semibold text-gray-600">Time</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                <?php if (empty($logs['logs'])): ?>
                <tr>
                    <td colspan="6" class="px-6 py-8 text-center text-gray-400">
                        <p>No notifications sent yet</p>
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach (array_slice($logs['logs'], 0, 10) as $log): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-3">
                            <span class="font-mono text-sm"><?php echo htmlspecialchars($log['order_number']); ?></span>
                        </td>
                        <td class="px-6 py-3">
                            <span class="text-sm text-gray-600 capitalize"><?php echo str_replace('_', ' ', $log['type']); ?></span>
                        </td>
                        <td class="px-6 py-3">
                            <span class="px-2 py-1 text-xs font-medium rounded-full <?php echo $log['channel'] === 'whatsapp' ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700'; ?>">
                                <?php echo ucfirst($log['channel']); ?>
                            </span>
                        </td>
                        <td class="px-6 py-3">
                            <span class="text-sm text-gray-600"><?php echo htmlspecialchars($log['recipient_phone']); ?></span>
                        </td>
                        <td class="px-6 py-3">
                            <span class="px-2 py-1 text-xs font-medium rounded-full <?php echo $log['status'] === 'sent' ? 'bg-green-100 text-green-700' : ($log['status'] === 'failed' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700'); ?>">
                                <?php echo ucfirst($log['status']); ?>
                            </span>
                        </td>
                        <td class="px-6 py-3">
                            <span class="text-sm text-gray-500"><?php echo formatDateTime($log['created_at'], 'j M h:i A'); ?></span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
