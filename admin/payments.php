<?php
/**
 * Admin Payments Management
 */

require_once dirname(__DIR__) . '/config/config.php';
requireRole('admin');

$page_title = 'Payments';

$payment = new Payment();

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = ['success' => false, 'message' => 'Invalid action'];

    switch ($_POST['action'] ?? '') {
        case 'mark_paid':
            $response = $payment->markAsPaid($_POST['payment_id'], $_POST['reference'] ?? null);
            break;

        case 'mark_failed':
            $response = $payment->markAsFailed($_POST['payment_id'], $_POST['notes'] ?? '');
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

    redirect(SITE_URL . '/admin/payments.php');
}

require_once INCLUDES_PATH . '/header.php';

// Get filters
$status = $_GET['status'] ?? '';
$method = $_GET['method'] ?? '';
$dateFrom = $_GET['date_from'] ?? date('Y-m-01');
$dateTo = $_GET['date_to'] ?? date('Y-m-d');

$filters = [
    'date_from' => $dateFrom,
    'date_to' => $dateTo,
];
if ($status) $filters['status'] = $status;
if ($method) $filters['method'] = $method;

$result = $payment->getAll($filters);
$payments = $result['payments'];
$pagination = $result['pagination'];
$todaySummary = $payment->getTodaySummary();
$reconciliation = $payment->getReconciliation($dateFrom, $dateTo);
?>

<!-- Page Header -->
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Payments</h1>
    <p class="text-gray-500">Track and reconcile payments</p>
</div>

<!-- Summary Cards -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-xl border border-gray-100 p-4 shadow-sm">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-green-50 rounded-xl flex items-center justify-center">
                <i class="fas fa-check-circle text-green-500"></i>
            </div>
            <div>
                <p class="text-gray-500 text-xs">Collected Today</p>
                <p class="text-lg md:text-xl font-bold text-gray-800"><?php echo formatCurrency($todaySummary['total_collected'] ?? 0); ?></p>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-xl border border-gray-100 p-4 shadow-sm">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-yellow-50 rounded-xl flex items-center justify-center">
                <i class="fas fa-clock text-yellow-500"></i>
            </div>
            <div>
                <p class="text-gray-500 text-xs">Pending Today</p>
                <p class="text-lg md:text-xl font-bold text-gray-800"><?php echo formatCurrency($todaySummary['total_pending'] ?? 0); ?></p>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-xl border border-gray-100 p-4 shadow-sm">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-purple-50 rounded-xl flex items-center justify-center">
                <i class="fas fa-money-bill-wave text-purple-500"></i>
            </div>
            <div>
                <p class="text-gray-500 text-xs">COD Collected</p>
                <p class="text-lg md:text-xl font-bold text-gray-800"><?php echo formatCurrency($todaySummary['cod_collected'] ?? 0); ?></p>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-xl border border-gray-100 p-4 shadow-sm">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-blue-50 rounded-xl flex items-center justify-center">
                <i class="fas fa-university text-blue-500"></i>
            </div>
            <div>
                <p class="text-gray-500 text-xs">Transfers</p>
                <p class="text-lg md:text-xl font-bold text-gray-800"><?php echo formatCurrency($todaySummary['transfer_collected'] ?? 0); ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Reconciliation Section -->
<div class="bg-white rounded-xl border border-gray-100 shadow-sm mb-6">
    <div class="p-4 border-b border-gray-100">
        <h2 class="font-semibold text-gray-800 mb-3">Reconciliation Report</h2>
        <div class="flex flex-col sm:flex-row sm:items-center gap-3">
            <input type="date" id="dateFrom" value="<?php echo htmlspecialchars($dateFrom); ?>" class="px-3 py-2 border border-gray-200 rounded-lg text-sm">
            <span class="text-gray-400">to</span>
            <input type="date" id="dateTo" value="<?php echo htmlspecialchars($dateTo); ?>" class="px-3 py-2 border border-gray-200 rounded-lg text-sm">
            <button onclick="updateReconciliation()" class="px-4 py-2 bg-gray-800 text-white rounded-lg text-sm hover:bg-gray-700">Update</button>
        </div>
    </div>
    <div class="p-4">
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php foreach ($reconciliation['method_summary'] ?? [] as $ms): ?>
            <div class="bg-gray-50 rounded-xl p-4">
                <p class="text-sm text-gray-500 capitalize mb-3"><?php echo str_replace('_', ' ', $ms['payment_method']); ?></p>
                <div class="space-y-2">
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">Total</span>
                        <span class="font-medium"><?php echo $ms['total_transactions']; ?> txns</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-green-600">Paid</span>
                        <span class="font-medium text-green-600"><?php echo formatCurrency($ms['paid_amount']); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-yellow-600">Pending</span>
                        <span class="font-medium text-yellow-600"><?php echo formatCurrency($ms['pending_amount']); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-red-600">Failed</span>
                        <span class="font-medium text-red-600"><?php echo formatCurrency($ms['failed_amount']); ?></span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="bg-white rounded-xl border border-gray-100 p-4 mb-6 shadow-sm">
    <form method="GET" action="" class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
            <select name="status" class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                <option value="">All Statuses</option>
                <option value="paid" <?php echo $status === 'paid' ? 'selected' : ''; ?>>Paid</option>
                <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                <option value="failed" <?php echo $status === 'failed' ? 'selected' : ''; ?>>Failed</option>
            </select>
        </div>
        <div>
            <select name="method" class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                <option value="">All Methods</option>
                <option value="cash_on_delivery" <?php echo $method === 'cash_on_delivery' ? 'selected' : ''; ?>>Cash on Delivery</option>
                <option value="bank_transfer" <?php echo $method === 'bank_transfer' ? 'selected' : ''; ?>>Bank Transfer</option>
                <option value="pos" <?php echo $method === 'pos' ? 'selected' : ''; ?>>POS</option>
            </select>
        </div>
        <div class="md:col-span-2 flex gap-2">
            <button type="submit" class="flex-1 px-4 py-2 bg-gray-800 text-white rounded-lg hover:bg-gray-700">Filter</button>
            <a href="payments.php" class="px-4 py-2 bg-gray-100 text-gray-600 rounded-lg hover:bg-gray-200">Clear</a>
        </div>
    </form>
</div>

<!-- Payments Table -->
<div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
    <!-- Mobile Card View -->
    <div class="md:hidden space-y-4 p-4">
        <?php if (empty($payments)): ?>
        <div class="text-center py-12 text-gray-400">
            <i class="fas fa-receipt text-4xl mb-3"></i>
            <p>No payments found</p>
        </div>
        <?php else: ?>
            <?php foreach ($payments as $p): ?>
            <div class="bg-gray-50 rounded-xl p-4 space-y-3">
                <div class="flex items-center justify-between">
                    <span class="font-mono font-bold text-gray-800"><?php echo htmlspecialchars($p['order_number']); ?></span>
                    <?php echo getStatusBadge($p['payment_status']); ?>
                </div>
                <div>
                    <p class="font-medium text-gray-800"><?php echo htmlspecialchars($p['customer_name']); ?></p>
                    <p class="text-sm text-gray-400"><?php echo htmlspecialchars($p['customer_phone'] ?? ''); ?></p>
                </div>
                <div class="flex items-center justify-between text-sm">
                    <span class="text-gray-500">Method:</span>
                    <span class="px-3 py-1 text-xs font-medium rounded-full <?php echo $p['payment_method'] === 'cash_on_delivery' ? 'bg-green-100 text-green-700' : ($p['payment_method'] === 'bank_transfer' ? 'bg-blue-100 text-blue-700' : 'bg-purple-100 text-purple-700'); ?>">
                        <?php echo ucfirst(str_replace('_', ' ', $p['payment_method'])); ?>
                    </span>
                </div>
                <div class="flex items-center justify-between text-sm">
                    <span class="text-gray-500">Amount:</span>
                    <span class="font-bold text-amber-600"><?php echo formatCurrency($p['amount']); ?></span>
                </div>
                <div class="flex items-center justify-between text-sm">
                    <span class="text-gray-500">Date:</span>
                    <span class="text-gray-700"><?php echo formatDateTime($p['created_at'], 'j M h:i A'); ?></span>
                </div>
                <?php if ($p['payment_status'] === 'pending'): ?>
                <div class="flex items-center justify-end gap-2 pt-2 border-t border-gray-200">
                    <button onclick="markAsPaid(<?php echo $p['id']; ?>)" class="px-4 py-2 text-green-600 bg-green-50 rounded-lg text-sm font-medium">
                        <i class="fas fa-check mr-1"></i>Mark Paid
                    </button>
                    <button onclick="markAsFailed(<?php echo $p['id']; ?>)" class="px-4 py-2 text-red-600 bg-red-50 rounded-lg text-sm font-medium">
                        <i class="fas fa-times mr-1"></i>Failed
                    </button>
                </div>
                <?php elseif ($p['payment_status'] === 'paid' && $p['transaction_reference']): ?>
                <div class="flex items-center justify-between text-sm pt-2 border-t border-gray-200">
                    <span class="text-gray-500">Reference:</span>
                    <span class="text-gray-700"><?php echo htmlspecialchars($p['transaction_reference']); ?></span>
                </div>
                <?php endif; ?>

                <!-- Payment Screenshot -->
                <?php if (!empty($p['screenshot'])): ?>
                <div class="pt-2 border-t border-gray-200">
                    <p class="text-gray-500 text-sm mb-2">Payment Proof:</p>
                    <button onclick="viewScreenshot('<?php echo SITE_URL . '/' . htmlspecialchars($p['screenshot']); ?>')" class="flex items-center gap-2 text-amber-600 hover:text-amber-700">
                        <i class="fas fa-image"></i>
                        <span class="text-sm">View Screenshot</span>
                    </button>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Desktop Table View -->
    <div class="hidden md:block overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-100">
                <tr>
                    <th class="text-left px-4 py-3 text-sm font-semibold text-gray-600">Order #</th>
                    <th class="text-left px-4 py-3 text-sm font-semibold text-gray-600">Customer</th>
                    <th class="text-left px-4 py-3 text-sm font-semibold text-gray-600">Method</th>
                    <th class="text-left px-4 py-3 text-sm font-semibold text-gray-600">Status</th>
                    <th class="text-left px-4 py-3 text-sm font-semibold text-gray-600">Amount</th>
                    <th class="text-left px-4 py-3 text-sm font-semibold text-gray-600">Date</th>
                    <th class="text-right px-4 py-3 text-sm font-semibold text-gray-600">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                <?php if (empty($payments)): ?>
                <tr>
                    <td colspan="7" class="px-4 py-12 text-center text-gray-400">
                        <i class="fas fa-receipt text-4xl mb-3"></i>
                        <p>No payments found</p>
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($payments as $p): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <span class="font-mono font-medium text-gray-800"><?php echo htmlspecialchars($p['order_number']); ?></span>
                        </td>
                        <td class="px-4 py-3">
                            <p class="font-medium text-gray-800"><?php echo htmlspecialchars($p['customer_name']); ?></p>
                            <p class="text-sm text-gray-400"><?php echo htmlspecialchars($p['customer_phone'] ?? ''); ?></p>
                        </td>
                        <td class="px-4 py-3">
                            <span class="px-3 py-1 text-xs font-medium rounded-full <?php echo $p['payment_method'] === 'cash_on_delivery' ? 'bg-green-100 text-green-700' : ($p['payment_method'] === 'bank_transfer' ? 'bg-blue-100 text-blue-700' : 'bg-purple-100 text-purple-700'); ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $p['payment_method'])); ?>
                            </span>
                        </td>
                        <td class="px-4 py-3"><?php echo getStatusBadge($p['payment_status']); ?></td>
                        <td class="px-4 py-3">
                            <p class="font-semibold text-gray-800"><?php echo formatCurrency($p['amount']); ?></p>
                        </td>
                        <td class="px-4 py-3">
                            <p class="text-sm text-gray-600"><?php echo formatDateTime($p['created_at'], 'j M h:i A'); ?></p>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-2">
                                <?php if ($p['payment_status'] === 'pending'): ?>
                                <button onclick="markAsPaid(<?php echo $p['id']; ?>)" class="px-3 py-1.5 text-sm bg-green-100 text-green-700 rounded-lg hover:bg-green-200">Mark Paid</button>
                                <button onclick="markAsFailed(<?php echo $p['id']; ?>)" class="px-3 py-1.5 text-sm bg-red-100 text-red-700 rounded-lg hover:bg-red-200">Failed</button>
                                <?php elseif ($p['payment_status'] === 'paid' && $p['transaction_reference']): ?>
                                <span class="text-xs text-gray-400">Ref: <?php echo htmlspecialchars($p['transaction_reference']); ?></span>
                                <?php endif; ?>
                                <?php if (!empty($p['screenshot'])): ?>
                                <button onclick="viewScreenshot('<?php echo SITE_URL . '/' . htmlspecialchars($p['screenshot']); ?>')" class="p-2 text-amber-600 hover:text-amber-700 hover:bg-amber-50 rounded-lg" title="View Screenshot">
                                    <i class="fas fa-image"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($pagination['total_pages'] > 1): ?>
    <div class="flex flex-col sm:flex-row items-center justify-between gap-4 px-6 py-4 border-t border-gray-100">
        <p class="text-sm text-gray-500">
            Showing <?php echo (($pagination['current_page'] - 1) * $pagination['per_page']) + 1; ?>
            to <?php echo min($pagination['current_page'] * $pagination['per_page'], $pagination['total']); ?>
            of <?php echo $pagination['total']; ?> payments
        </p>
        <div class="flex gap-2">
            <?php if ($pagination['has_prev']): ?>
            <a href="?page=<?php echo $pagination['current_page'] - 1; ?><?php echo $status ? '&status='.$status : ''; ?><?php echo $method ? '&method='.$method : ''; ?>" class="px-4 py-2 border border-gray-200 rounded-lg hover:bg-gray-50">Previous</a>
            <?php endif; ?>
            <?php if ($pagination['has_next']): ?>
            <a href="?page=<?php echo $pagination['current_page'] + 1; ?><?php echo $status ? '&status='.$status : ''; ?><?php echo $method ? '&method='.$method : ''; ?>" class="px-4 py-2 border border-gray-200 rounded-lg hover:bg-gray-50">Next</a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Mark Paid Modal -->
<div id="markPaidModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl w-full max-w-sm">
        <div class="flex items-center justify-between p-5 border-b border-gray-100">
            <h2 class="text-lg font-semibold text-gray-800">Mark as Paid</h2>
            <button onclick="closeModal('markPaidModal')" class="p-2 text-gray-400 hover:bg-gray-100 rounded-lg">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form id="markPaidForm" method="POST" action="">
            <input type="hidden" name="action" value="mark_paid">
            <input type="hidden" name="payment_id" id="markPaid_payment_id">
            <div class="p-5">
                <label class="block text-sm font-medium text-gray-700 mb-2">Transaction Reference (Optional)</label>
                <input type="text" name="reference" id="paymentReference" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500" placeholder="e.g., REF123456">
            </div>
            <div class="flex items-center justify-end gap-3 p-5 border-t border-gray-100 bg-gray-50">
                <button type="button" onclick="closeModal('markPaidModal')" class="px-5 py-2.5 border border-gray-200 rounded-xl hover:bg-gray-100">Cancel</button>
                <button type="submit" class="px-5 py-2.5 bg-green-500 text-white rounded-xl hover:bg-green-600">Mark Paid</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(id) {
    document.getElementById(id).classList.remove('hidden');
}

function closeModal(id) {
    document.getElementById(id).classList.add('hidden');
}

function markAsPaid(paymentId) {
    document.getElementById('markPaid_payment_id').value = paymentId;
    document.getElementById('paymentReference').value = '';
    openModal('markPaidModal');
}

function markAsFailed(paymentId) {
    if (confirm('Are you sure you want to mark this payment as failed?')) {
        const formData = new FormData();
        formData.append('action', 'mark_failed');
        formData.append('payment_id', paymentId);
        formData.append('notes', 'Marked as failed by admin');

        fetch('', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'Failed to mark payment');
                }
            });
    }
}

function updateReconciliation() {
    const dateFrom = document.getElementById('dateFrom').value;
    const dateTo = document.getElementById('dateTo').value;
    window.location.href = `payments.php?date_from=${dateFrom}&date_to=${dateTo}`;
}

function viewScreenshot(imageUrl) {
    document.getElementById('screenshotImage').src = imageUrl;
    openModal('screenshotModal');
}
</script>

<!-- Screenshot Viewer Modal -->
<div id="screenshotModal" class="hidden fixed inset-0 bg-black/80 z-50 flex items-center justify-center p-4">
    <div class="relative max-w-4xl w-full">
        <button onclick="closeModal('screenshotModal')" class="absolute -top-12 right-0 w-10 h-10 bg-white rounded-full flex items-center justify-center text-gray-800 hover:bg-gray-100">
            <i class="fas fa-times"></i>
        </button>
        <img id="screenshotImage" src="" alt="Payment Screenshot" class="w-full h-auto max-h-[80vh] object-contain rounded-xl">
    </div>
</div>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
