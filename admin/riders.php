<?php
/**
 * Admin Riders Management
 */

require_once dirname(__DIR__) . '/config/config.php';
requireRole('admin');

$page_title = 'Riders';

$rider = new Rider();
$user = new User();

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = ['success' => false, 'message' => 'Invalid action'];

    switch ($_POST['action'] ?? '') {
        case 'create':
            $data = [
                'name' => sanitize($_POST['name']),
                'email' => sanitize($_POST['email']),
                'phone' => sanitize($_POST['phone']),
                'password' => $_POST['password'],
                'role' => 'rider',
                'vehicle_type' => sanitize($_POST['vehicle_type'] ?? ''),
                'plate_number' => sanitize($_POST['plate_number'] ?? ''),
            ];
            $response = $user->create($data);
            break;

        case 'toggle_availability':
            $riderId = $_POST['rider_id'];
            $isAvailable = $_POST['available'] === '1';
            $response = $rider->setAvailability($riderId, $isAvailable);
            break;

        case 'delete':
            $response = $user->delete($_POST['user_id']);
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

    redirect(SITE_URL . '/admin/riders.php');
}

require_once INCLUDES_PATH . '/header.php';

$riders = $rider->getAll();
$topRiders = $rider->getTopRiders(5);
?>

<!-- Page Header -->
<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Riders</h1>
        <p class="text-gray-500">Manage delivery personnel</p>
    </div>
    <button onclick="openNewRiderModal()" class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-amber-500 to-orange-600 text-white font-medium rounded-xl hover:from-amber-600 hover:to-orange-700 transition-all shadow-lg shadow-amber-200">
        <i class="fas fa-plus"></i>
        <span>Add Rider</span>
    </button>
</div>

<!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div class="bg-white rounded-xl border border-gray-100 p-5 shadow-sm">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-purple-50 rounded-xl flex items-center justify-center">
                <i class="fas fa-motorcycle text-purple-500 text-xl"></i>
            </div>
            <div>
                <p class="text-gray-500 text-sm">Total Riders</p>
                <p class="text-2xl font-bold text-gray-800"><?php echo count($riders); ?></p>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-xl border border-gray-100 p-5 shadow-sm">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-green-50 rounded-xl flex items-center justify-center">
                <i class="fas fa-check-circle text-green-500 text-xl"></i>
            </div>
            <div>
                <p class="text-gray-500 text-sm">Available Now</p>
                <p class="text-2xl font-bold text-gray-800"><?php echo count(array_filter($riders, fn($r) => $r['is_available'] && $r['user_status'] === 'active')); ?></p>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-xl border border-gray-100 p-5 shadow-sm">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-amber-50 rounded-xl flex items-center justify-center">
                <i class="fas fa-trophy text-amber-500 text-xl"></i>
            </div>
            <div>
                <p class="text-gray-500 text-sm">Top Performer</p>
                <p class="text-lg font-bold text-gray-800"><?php echo $topRiders[0]['name'] ?? '-'; ?></p>
            </div>
        </div>
    </div>
</div>

<div class="grid lg:grid-cols-3 gap-6">
    <!-- Riders List -->
    <div class="lg:col-span-2">
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="p-5 border-b border-gray-100">
                <h2 class="font-semibold text-gray-800">All Riders</h2>
            </div>
            <div class="divide-y divide-gray-50">
                <?php if (empty($riders)): ?>
                <div class="p-12 text-center text-gray-400">
                    <i class="fas fa-motorcycle text-4xl mb-3"></i>
                    <p>No riders found</p>
                </div>
                <?php else: ?>
                    <?php foreach ($riders as $r): ?>
                    <div class="p-5 hover:bg-gray-50 transition-colors">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center text-purple-600 font-semibold text-lg">
                                    <?php echo strtoupper(substr($r['name'], 0, 1)); ?>
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($r['name']); ?></p>
                                        <?php echo $r['is_available'] ? '<span class="px-2 py-0.5 text-xs font-medium rounded-full bg-green-100 text-green-700">Available</span>' : '<span class="px-2 py-0.5 text-xs font-medium rounded-full bg-gray-100 text-gray-600">Unavailable</span>'; ?>
                                    </div>
                                    <p class="text-sm text-gray-400"><?php echo htmlspecialchars($r['phone']); ?></p>
                                </div>
                            </div>
                            <div class="flex items-center gap-4">
                                <div class="text-right">
                                    <p class="text-sm text-gray-500"><span class="font-semibold text-gray-800"><?php echo $r['total_deliveries']; ?></span> deliveries</p>
                                    <p class="text-sm text-gray-400"><?php echo htmlspecialchars($r['vehicle_type'] ?? 'N/A'); ?></p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button onclick="toggleAvailability(<?php echo $r['id']; ?>, <?php echo $r['is_available'] ? '0' : '1'; ?>)" class="p-2 text-gray-400 hover:text-green-600 hover:bg-green-50 rounded-lg" title="<?php echo $r['is_available'] ? 'Mark unavailable' : 'Mark available'; ?>">
                                        <i class="fas <?php echo $r['is_available'] ? 'fa-toggle-on text-green-500' : 'fa-toggle-off text-gray-300'; ?>"></i>
                                    </button>
                                    <a href="riders-edit.php?id=<?php echo $r['id']; ?>" class="p-2 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Top Riders -->
    <div>
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm">
            <div class="p-5 border-b border-gray-100">
                <h2 class="font-semibold text-gray-800">Top Performers</h2>
                <p class="text-sm text-gray-400">Most deliveries this month</p>
            </div>
            <div class="divide-y divide-gray-50">
                <?php if (empty($topRiders)): ?>
                <div class="p-8 text-center text-gray-400">
                    <p>No data yet</p>
                </div>
                <?php else: ?>
                    <?php foreach ($topRiders as $i => $r): ?>
                    <div class="p-4 flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-semibold <?php echo $i === 0 ? 'bg-amber-100 text-amber-600' : 'bg-gray-100 text-gray-500'; ?>">
                            <?php echo $i + 1; ?>
                        </div>
                        <div class="flex-1">
                            <p class="font-medium text-gray-800"><?php echo htmlspecialchars($r['name']); ?></p>
                            <p class="text-xs text-gray-400"><?php echo $r['rating']; ?> ★</p>
                        </div>
                        <div class="text-right">
                            <p class="font-semibold text-purple-600"><?php echo $r['total_deliveries']; ?></p>
                            <p class="text-xs text-gray-400">deliveries</p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- New Rider Modal -->
<div id="newRiderModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl w-full max-w-lg">
        <div class="flex items-center justify-between p-5 border-b border-gray-100">
            <h2 class="text-lg font-semibold text-gray-800">Add New Rider</h2>
            <button onclick="closeModal('newRiderModal')" class="p-2 text-gray-400 hover:bg-gray-100 rounded-lg">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="POST" action="">
            <input type="hidden" name="action" value="create">
            <div class="p-5 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Full Name *</label>
                    <input type="text" name="name" required class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Email *</label>
                    <input type="email" name="email" required class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Phone Number *</label>
                    <input type="text" name="phone" required class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Password *</label>
                    <input type="password" name="password" required class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Vehicle Type</label>
                        <select name="vehicle_type" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                            <option value="">Select...</option>
                            <option value="motorcycle">Motorcycle</option>
                            <option value="bicycle">Bicycle</option>
                            <option value="car">Car</option>
                            <option value="van">Van</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Plate Number</label>
                        <input type="text" name="plate_number" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                    </div>
                </div>
            </div>
            <div class="flex items-center justify-end gap-3 p-5 border-t border-gray-100 bg-gray-50">
                <button type="button" onclick="closeModal('newRiderModal')" class="px-5 py-2.5 border border-gray-200 rounded-xl hover:bg-gray-100">Cancel</button>
                <button type="submit" class="px-5 py-2.5 bg-gradient-to-r from-amber-500 to-orange-600 text-white rounded-xl hover:from-amber-600 hover:to-orange-700">Add Rider</button>
            </div>
        </form>
    </div>
</div>

<script>
function openNewRiderModal() {
    document.getElementById('newRiderModal').classList.remove('hidden');
}

function closeModal(id) {
    document.getElementById(id).classList.add('hidden');
}

function toggleAvailability(riderId, available) {
    const formData = new FormData();
    formData.append('action', 'toggle_availability');
    formData.append('rider_id', riderId);
    formData.append('available', available);
    formData.append('ajax', '1');

    fetch('', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                location.reload();
            }
        });
}
</script>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
