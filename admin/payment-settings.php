<?php
/**
 * Admin Payment Settings Management
 */

require_once dirname(__DIR__) . '/config/config.php';
requireRole('admin');

$page_title = 'Payment Settings';

$paymentSettings = new PaymentSettings();

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = ['success' => false, 'message' => 'Invalid action'];

    switch ($_POST['action'] ?? '') {
        case 'save_settings':
            $data = [
                'bank_transfer_enabled' => isset($_POST['bank_transfer_enabled']) ? '1' : '0',
                'bank_name' => sanitize($_POST['bank_name'] ?? ''),
                'account_name' => sanitize($_POST['account_name'] ?? ''),
                'account_number' => sanitize($_POST['account_number'] ?? ''),
                'bank_instructions' => sanitize($_POST['bank_instructions'] ?? ''),
                'pos_enabled' => isset($_POST['pos_enabled']) ? '1' : '0',
                'pos_instructions' => sanitize($_POST['pos_instructions'] ?? ''),
            ];
            $response = $paymentSettings->setMultiple($data);
            break;
    }

    if ($response['success']) {
        setFlash('success', 'Payment settings updated successfully');
    } else {
        setFlash('error', $response['message'] ?? 'Failed to update settings');
    }

    redirect(SITE_URL . '/admin/payment-settings.php');
}

require_once INCLUDES_PATH . '/header.php';

// Get current settings
$settings = $paymentSettings->getAll();

// Get payment method details for preview
$bankDetails = $paymentSettings->getPaymentMethodDetails('bank_transfer');
$posDetails = $paymentSettings->getPaymentMethodDetails('pos');
?>

<!-- Page Header -->
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Payment Settings</h1>
    <p class="text-gray-500">Configure payment methods for checkout</p>
</div>

<form method="POST" action="">
    <input type="hidden" name="action" value="save_settings">

    <!-- Bank Transfer Settings -->
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm mb-6">
        <div class="p-5 border-b border-gray-100 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-blue-50 rounded-xl flex items-center justify-center">
                    <i class="fas fa-university text-blue-500"></i>
                </div>
                <div>
                    <h2 class="font-semibold text-gray-800">Bank Transfer</h2>
                    <p class="text-sm text-gray-400">Customers can pay via bank transfer</p>
                </div>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" name="bank_transfer_enabled" value="1" class="sr-only peer" <?php echo $bankDetails['enabled'] ? 'checked' : ''; ?>>
                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-amber-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-amber-500"></div>
            </label>
        </div>
        <div class="p-5 space-y-4">
            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Bank Name</label>
                    <input type="text" name="bank_name" value="<?php echo htmlspecialchars($bankDetails['bank_name']); ?>" placeholder="e.g., GTBank, Access Bank" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Account Name</label>
                    <input type="text" name="account_name" value="<?php echo htmlspecialchars($bankDetails['account_name']); ?>" placeholder="Account holder name" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Account Number</label>
                <input type="text" name="account_number" value="<?php echo htmlspecialchars($bankDetails['account_number']); ?>" placeholder="1234567890" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Transfer Instructions</label>
                <textarea name="bank_instructions" rows="3" placeholder="Instructions for customers..." class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500"><?php echo htmlspecialchars($bankDetails['instructions']); ?></textarea>
            </div>

            <!-- Preview -->
            <div class="bg-blue-50 rounded-xl p-4">
                <p class="text-xs font-semibold text-blue-600 mb-2">CUSTOMER PREVIEW</p>
                <div class="bg-white rounded-lg p-4">
                    <?php if (!empty($bankDetails['bank_name'])): ?>
                    <div class="space-y-3">
                        <div class="flex items-center gap-2 text-sm">
                            <i class="fas fa-building-columns text-blue-500 w-5"></i>
                            <span class="font-medium"><?php echo htmlspecialchars($bankDetails['bank_name']); ?></span>
                        </div>
                        <?php if (!empty($bankDetails['account_name'])): ?>
                        <div class="flex items-center gap-2 text-sm">
                            <i class="fas fa-user text-gray-400 w-5"></i>
                            <span><?php echo htmlspecialchars($bankDetails['account_name']); ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($bankDetails['account_number'])): ?>
                        <div class="flex items-center gap-2 text-sm">
                            <i class="fas fa-hashtag text-gray-400 w-5"></i>
                            <span class="font-mono"><?php echo htmlspecialchars($bankDetails['account_number']); ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($bankDetails['instructions'])): ?>
                        <div class="mt-3 p-3 bg-gray-50 rounded-lg">
                            <p class="text-sm text-gray-600"><?php echo htmlspecialchars($bankDetails['instructions']); ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php else: ?>
                    <p class="text-sm text-gray-400 italic">No bank details configured yet</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- POS Settings -->
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm mb-6">
        <div class="p-5 border-b border-gray-100 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-purple-50 rounded-xl flex items-center justify-center">
                    <i class="fas fa-credit-card text-purple-500"></i>
                </div>
                <div>
                    <h2 class="font-semibold text-gray-800">POS Payment</h2>
                    <p class="text-sm text-gray-400">Customer pays via POS terminal and uploads receipt</p>
                </div>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" name="pos_enabled" value="1" class="sr-only peer" <?php echo $posDetails['enabled'] ? 'checked' : ''; ?>>
                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-amber-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-amber-500"></div>
            </label>
        </div>
        <div class="p-5 space-y-4">
            <div class="bg-blue-50 rounded-xl p-4">
                <div class="flex items-start gap-3">
                    <i class="fas fa-info-circle text-blue-500 mt-0.5"></i>
                    <div>
                        <p class="text-sm text-blue-800 font-medium">Same Bank Account</p>
                        <p class="text-sm text-blue-600">POS payment uses the same bank account details configured above in Bank Transfer settings.</p>
                    </div>
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">POS Payment Instructions</label>
                <textarea name="pos_instructions" rows="3" placeholder="Instructions for customers on how to pay and upload receipt..." class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500"><?php echo htmlspecialchars($posDetails['instructions']); ?></textarea>
            </div>

            <!-- Preview -->
            <div class="bg-purple-50 rounded-xl p-4">
                <p class="text-xs font-semibold text-purple-600 mb-2">CUSTOMER PREVIEW</p>
                <div class="bg-white rounded-lg p-4">
                    <?php if (!empty($posDetails['bank_name'])): ?>
                    <div class="space-y-3">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-credit-card text-purple-500"></i>
                            </div>
                            <div>
                                <p class="font-medium text-gray-800">Pay via POS Terminal</p>
                                <p class="text-sm text-gray-500"><?php echo htmlspecialchars($posDetails['instructions']); ?></p>
                            </div>
                        </div>
                        <p class="text-xs font-semibold text-purple-600 pt-2 border-t border-gray-100">TRANSFER TO:</p>
                        <div class="flex items-center gap-2 text-sm">
                            <i class="fas fa-building-columns text-purple-500 w-5"></i>
                            <span class="font-medium"><?php echo htmlspecialchars($posDetails['bank_name']); ?></span>
                        </div>
                        <?php if (!empty($posDetails['account_name'])): ?>
                        <div class="flex items-center gap-2 text-sm">
                            <i class="fas fa-user text-gray-400 w-5"></i>
                            <span><?php echo htmlspecialchars($posDetails['account_name']); ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($posDetails['account_number'])): ?>
                        <div class="flex items-center gap-2 text-sm">
                            <i class="fas fa-hashtag text-gray-400 w-5"></i>
                            <span class="font-mono"><?php echo htmlspecialchars($posDetails['account_number']); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php else: ?>
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-credit-card text-purple-500"></i>
                        </div>
                        <div>
                            <p class="font-medium text-gray-800">Pay via POS Terminal</p>
                            <p class="text-sm text-gray-500"><?php echo htmlspecialchars($posDetails['instructions']); ?></p>
                            <p class="text-xs text-gray-400 italic mt-1">Bank account not configured yet</p>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Save Button -->
    <div class="flex items-center justify-end gap-4">
        <a href="<?php echo SITE_URL; ?>/admin/index.php" class="px-6 py-2.5 border border-gray-200 rounded-xl hover:bg-gray-50">Cancel</a>
        <button type="submit" class="px-6 py-2.5 bg-gradient-to-r from-amber-500 to-orange-600 text-white font-medium rounded-xl hover:from-amber-600 hover:to-orange-700 shadow-lg shadow-amber-200">
            <i class="fas fa-save mr-2"></i>Save Settings
        </button>
    </div>
</form>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
