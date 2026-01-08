<?php
/**
 * Admin Menu Management
 */

require_once dirname(__DIR__) . '/config/config.php';
requireRole('admin');

$page_title = 'Menu Management';

$menuItem = new MenuItem();

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = ['success' => false, 'message' => 'Invalid action'];

    // Handle image upload
    $imageUrl = $_POST['existing_image'] ?? '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = dirname(__DIR__) . '/public/uploads/menu/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (in_array($extension, $allowedExtensions)) {
            $filename = 'menu_' . time() . '_' . rand(1000, 9999) . '.' . $extension;
            $filepath = $uploadDir . $filename;

            if (move_uploaded_file($_FILES['image']['tmp_name'], $filepath)) {
                $imageUrl = SITE_URL . '/public/uploads/menu/' . $filename;

                // Delete old image if updating
                if (!empty($_POST['existing_image']) && $_POST['action'] === 'update') {
                    $oldImagePath = str_replace(SITE_URL . '/', dirname(__DIR__) . '/', $_POST['existing_image']);
                    if (file_exists($oldImagePath)) {
                        unlink($oldImagePath);
                    }
                }
            }
        }
    }

    switch ($_POST['action'] ?? '') {
        case 'create':
            $data = [
                'name' => sanitize($_POST['name']),
                'description' => sanitize($_POST['description'] ?? ''),
                'price' => floatval($_POST['price']),
                'category' => sanitize($_POST['category'] ?? 'Main'),
                'image_url' => $imageUrl,
                'is_available' => isset($_POST['is_available']),
                'is_featured' => isset($_POST['is_featured']),
                'preparation_time' => intval($_POST['preparation_time'] ?? 30),
                'spice_level' => sanitize($_POST['spice_level'] ?? 'medium'),
                'tags' => sanitize($_POST['tags'] ?? ''),
            ];
            $response = $menuItem->create($data);
            break;

        case 'update':
            $data = [
                'name' => sanitize($_POST['name']),
                'description' => sanitize($_POST['description'] ?? ''),
                'price' => floatval($_POST['price']),
                'category' => sanitize($_POST['category'] ?? 'Main'),
                'image_url' => $imageUrl,
                'is_available' => isset($_POST['is_available']),
                'is_featured' => isset($_POST['is_featured']),
                'preparation_time' => intval($_POST['preparation_time'] ?? 30),
                'spice_level' => sanitize($_POST['spice_level'] ?? 'medium'),
                'tags' => sanitize($_POST['tags'] ?? ''),
            ];
            $response = $menuItem->update($_POST['item_id'], $data);
            break;

        case 'toggle_available':
            $response = $menuItem->toggleAvailability($_POST['item_id']);
            break;

        case 'toggle_featured':
            $response = $menuItem->toggleFeatured($_POST['item_id']);
            break;

        case 'delete':
            $response = $menuItem->delete($_POST['item_id']);
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

    redirect(SITE_URL . '/admin/menu.php');
}

require_once INCLUDES_PATH . '/header.php';

$filters = [
    'category' => $_GET['category'] ?? '',
    'available' => isset($_GET['available']) ? $_GET['available'] === '1' : null,
    'search' => $_GET['search'] ?? '',
];

$menuItems = $menuItem->getAll($filters);
$categories = $menuItem->getCategories();
?>

<!-- Page Header -->
<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Menu Management</h1>
        <p class="text-gray-500">Manage food items and pricing</p>
    </div>
    <button onclick="openNewItemModal()" class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-amber-500 to-orange-600 text-white font-medium rounded-xl hover:from-amber-600 hover:to-orange-700 transition-all shadow-lg shadow-amber-200">
        <i class="fas fa-plus"></i>
        <span>Add Menu Item</span>
    </button>
</div>

<!-- Filters -->
<div class="bg-white rounded-xl border border-gray-100 p-4 mb-6 shadow-sm">
    <form method="GET" action="" class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
            <input type="text" name="search" value="<?php echo htmlspecialchars($filters['search']); ?>" placeholder="Search items..." class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
        </div>
        <div>
            <select name="category" class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                <option value="">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $filters['category'] === $cat ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <select name="available" class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                <option value="">All Items</option>
                <option value="1" <?php echo $filters['available'] === true ? 'selected' : ''; ?>>Available Only</option>
                <option value="0" <?php echo $filters['available'] === false ? 'selected' : ''; ?>>Unavailable</option>
            </select>
        </div>
        <div class="flex gap-2">
            <button type="submit" class="flex-1 px-4 py-2 bg-gray-800 text-white rounded-lg hover:bg-gray-700">Filter</button>
            <a href="menu.php" class="px-4 py-2 bg-gray-100 text-gray-600 rounded-lg hover:bg-gray-200">Clear</a>
        </div>
    </form>
</div>

<!-- Menu Items Table -->
<div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
    <!-- Mobile Card View -->
    <div class="md:hidden space-y-4 p-4">
        <?php if (empty($menuItems)): ?>
        <div class="text-center py-12 text-gray-400">
            <i class="fas fa-utensils text-4xl mb-3"></i>
            <p>No menu items found</p>
        </div>
        <?php else: ?>
            <?php foreach ($menuItems as $item): ?>
            <div class="bg-gray-50 rounded-xl p-4 space-y-3">
                <div class="flex items-center gap-3">
                    <?php if (!empty($item['image_url'])): ?>
                    <img src="<?php echo htmlspecialchars($item['image_url']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="w-16 h-16 rounded-lg object-cover">
                    <?php else: ?>
                    <div class="w-16 h-16 bg-gradient-to-br from-amber-50 to-orange-50 rounded-lg flex items-center justify-center">
                        <i class="fas fa-utensils text-amber-300 text-xl"></i>
                    </div>
                    <?php endif; ?>
                    <div class="flex-1">
                        <p class="font-bold text-gray-800"><?php echo htmlspecialchars($item['name']); ?></p>
                        <p class="text-sm text-gray-400"><?php echo htmlspecialchars($item['category']); ?></p>
                    </div>
                    <div class="text-right">
                        <p class="font-bold text-amber-600"><?php echo formatCurrency($item['price']); ?></p>
                    </div>
                </div>
                <?php if (!empty($item['description'])): ?>
                <p class="text-sm text-gray-500"><?php echo htmlspecialchars($item['description']); ?></p>
                <?php endif; ?>
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <?php if ($item['is_available']): ?>
                        <span class="px-2 py-1 text-xs bg-green-100 text-green-700 rounded-full">Available</span>
                        <?php else: ?>
                        <span class="px-2 py-1 text-xs bg-red-100 text-red-700 rounded-full">Unavailable</span>
                        <?php endif; ?>
                        <?php if ($item['is_featured']): ?>
                        <span class="px-2 py-1 text-xs bg-amber-100 text-amber-700 rounded-full">Featured</span>
                        <?php endif; ?>
                        <span class="text-sm" title="<?php echo ucfirst($item['spice_level']); ?>">
                            <?php
                            $spiceLabels = [
                                'none' => '🚫',
                                'mild' => '🌶️',
                                'medium' => '🌶️🌶️',
                                'hot' => '🌶️🌶️🌶️',
                            ];
                            echo $spiceLabels[$item['spice_level']] ?? '🌶️';
                            ?>
                        </span>
                    </div>
                </div>
                <div class="flex items-center justify-end gap-2 pt-2 border-t border-gray-200">
                    <button onclick="toggleAvailable(<?php echo $item['id']; ?>)" class="p-2 text-gray-400 hover:text-green-600 hover:bg-green-50 rounded-lg" title="<?php echo $item['is_available'] ? 'Mark unavailable' : 'Mark available'; ?>">
                        <i class="fas <?php echo $item['is_available'] ? 'fa-toggle-on text-green-500' : 'fa-toggle-off text-gray-300'; ?>"></i>
                    </button>
                    <button onclick="toggleFeatured(<?php echo $item['id']; ?>)" class="p-2 text-gray-400 hover:text-amber-600 hover:bg-amber-50 rounded-lg">
                        <i class="fas fa-star <?php echo $item['is_featured'] ? 'text-amber-500' : ''; ?>"></i>
                    </button>
                    <button onclick="editItem(<?php echo $item['id']; ?>)" class="p-2 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button onclick="deleteItem(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item['name'], ENT_QUOTES); ?>')" class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg">
                        <i class="fas fa-trash"></i>
                    </button>
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
                    <th class="text-left px-4 py-3 text-sm font-semibold text-gray-600">Item</th>
                    <th class="text-left px-4 py-3 text-sm font-semibold text-gray-600">Category</th>
                    <th class="text-left px-4 py-3 text-sm font-semibold text-gray-600">Price</th>
                    <th class="text-left px-4 py-3 text-sm font-semibold text-gray-600">Spice</th>
                    <th class="text-left px-4 py-3 text-sm font-semibold text-gray-600">Status</th>
                    <th class="text-right px-4 py-3 text-sm font-semibold text-gray-600">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                <?php if (empty($menuItems)): ?>
                <tr>
                    <td colspan="6" class="px-4 py-12 text-center text-gray-400">
                        <i class="fas fa-utensils text-4xl mb-3"></i>
                        <p>No menu items found</p>
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($menuItems as $item): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <?php if (!empty($item['image_url'])): ?>
                                <img src="<?php echo htmlspecialchars($item['image_url']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="w-12 h-12 rounded-lg object-cover">
                                <?php else: ?>
                                <div class="w-12 h-12 bg-gradient-to-br from-amber-50 to-orange-50 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-utensils text-amber-300"></i>
                                </div>
                                <?php endif; ?>
                                <div>
                                    <p class="font-medium text-gray-800"><?php echo htmlspecialchars($item['name']); ?></p>
                                    <p class="text-xs text-gray-400 max-w-xs truncate"><?php echo htmlspecialchars($item['description'] ?? ''); ?></p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 text-xs bg-gray-100 text-gray-600 rounded-full"><?php echo htmlspecialchars($item['category']); ?></span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="font-semibold text-gray-800"><?php echo formatCurrency($item['price']); ?></span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="text-sm" title="<?php echo ucfirst($item['spice_level']); ?>">
                                <?php
                                $spiceLabels = [
                                    'none' => '🚫',
                                    'mild' => '🌶️',
                                    'medium' => '🌶️🌶️',
                                    'hot' => '🌶️🌶️🌶️',
                                ];
                                echo $spiceLabels[$item['spice_level']] ?? '🌶️';
                                ?>
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <?php if ($item['is_available']): ?>
                                <span class="px-2 py-1 text-xs bg-green-100 text-green-700 rounded-full">Available</span>
                                <?php else: ?>
                                <span class="px-2 py-1 text-xs bg-red-100 text-red-700 rounded-full">Unavailable</span>
                                <?php endif; ?>
                                <?php if ($item['is_featured']): ?>
                                <span class="px-2 py-1 text-xs bg-amber-100 text-amber-700 rounded-full">Featured</span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-1">
                                <button onclick="toggleAvailable(<?php echo $item['id']; ?>)" class="p-2 text-gray-400 hover:text-green-600 hover:bg-green-50 rounded-lg" title="<?php echo $item['is_available'] ? 'Mark unavailable' : 'Mark available'; ?>">
                                    <i class="fas <?php echo $item['is_available'] ? 'fa-toggle-on text-green-500' : 'fa-toggle-off text-gray-300'; ?>"></i>
                                </button>
                                <button onclick="toggleFeatured(<?php echo $item['id']; ?>)" class="p-2 text-gray-400 hover:text-amber-600 hover:bg-amber-50 rounded-lg" title="Toggle featured">
                                    <i class="fas fa-star <?php echo $item['is_featured'] ? 'text-amber-500' : ''; ?>"></i>
                                </button>
                                <button onclick="editItem(<?php echo $item['id']; ?>)" class="p-2 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button onclick="deleteItem(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item['name'], ENT_QUOTES); ?>')" class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- New/Edit Item Modal -->
<div id="itemModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between p-5 border-b border-gray-100">
            <h2 class="text-lg font-semibold text-gray-800" id="modalTitle">Add Menu Item</h2>
            <button onclick="closeModal('itemModal')" class="p-2 text-gray-400 hover:bg-gray-100 rounded-lg">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form id="itemForm" method="POST" action="" enctype="multipart/form-data">
            <input type="hidden" name="action" value="create">
            <input type="hidden" name="item_id" id="form_item_id">
            <input type="hidden" name="existing_image" id="form_existing_image">
            <div class="p-5 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Item Name *</label>
                    <input type="text" name="name" id="form_name" required class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                    <textarea name="description" id="form_description" rows="2" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500"></textarea>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Price (₦) *</label>
                        <input type="number" name="price" id="form_price" step="0.01" required class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Prep Time (mins)</label>
                        <input type="number" name="preparation_time" id="form_preparation_time" value="30" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Category *</label>
                        <input type="text" name="category" id="form_category" list="categories" required class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                        <datalist id="categories">
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat); ?>">
                            <?php endforeach; ?>
                        </datalist>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Spice Level</label>
                        <select name="spice_level" id="form_spice_level" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                            <option value="none">None</option>
                            <option value="mild">Mild</option>
                            <option value="medium" selected>Medium</option>
                            <option value="hot">Hot</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Food Image</label>
                    <input type="file" name="image" id="form_image" accept="image/jpeg,image/jpg,image/png,image/gif,image/webp" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500 file:mr-2 file:rounded-lg">
                    <p class="text-xs text-gray-500 mt-1">Upload a food image (JPG, PNG, GIF, WebP)</p>
                </div>
                <div id="imagePreviewContainer" class="hidden">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Current Image</label>
                    <img id="imagePreview" src="" alt="Preview" class="w-32 h-32 object-cover rounded-xl border border-gray-200">
                    <input type="hidden" id="form_image_url_backup" name="image_url">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tags (comma separated)</label>
                    <input type="text" name="tags" id="form_tags" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500" placeholder="popular, nigerian, spicy">
                </div>
                <div class="flex items-center gap-6">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_available" id="form_is_available" checked class="w-4 h-4 text-amber-500 rounded focus:ring-amber-500">
                        <span class="text-sm text-gray-700">Available</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_featured" id="form_is_featured" class="w-4 h-4 text-amber-500 rounded focus:ring-amber-500">
                        <span class="text-sm text-gray-700">Featured</span>
                    </label>
                </div>
            </div>
            <div class="flex items-center justify-end gap-3 p-5 border-t border-gray-100 bg-gray-50">
                <button type="button" onclick="closeModal('itemModal')" class="px-5 py-2.5 border border-gray-200 rounded-xl hover:bg-gray-100">Cancel</button>
                <button type="submit" class="px-5 py-2.5 bg-gradient-to-r from-amber-500 to-orange-600 text-white rounded-xl hover:from-amber-600 hover:to-orange-700">Save Item</button>
            </div>
        </form>
    </div>
</div>

<script>
function openNewItemModal() {
    document.getElementById('modalTitle').textContent = 'Add Menu Item';
    document.getElementById('itemForm').reset();
    document.getElementById('form_item_id').value = '';
    document.getElementById('form_existing_image').value = '';
    document.getElementById('imagePreviewContainer').classList.add('hidden');
    document.getElementById('itemForm').action.value = 'create';
    document.getElementById('itemModal').classList.remove('hidden');
}

function editItem(id) {
    // Load item data via fetch
    fetch('<?php echo SITE_URL; ?>/api/menu-item.php?id=' + id)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const item = data.item;
                document.getElementById('modalTitle').textContent = 'Edit Menu Item';
                document.getElementById('form_item_id').value = item.id;
                document.getElementById('form_name').value = item.name;
                document.getElementById('form_description').value = item.description || '';
                document.getElementById('form_price').value = item.price;
                document.getElementById('form_category').value = item.category;
                document.getElementById('form_preparation_time').value = item.preparation_time;
                document.getElementById('form_spice_level').value = item.spice_level;
                document.getElementById('form_tags').value = item.tags || '';
                document.getElementById('form_is_available').checked = item.is_available == 1;
                document.getElementById('form_is_featured').checked = item.is_featured == 1;
                document.getElementById('itemForm').action.value = 'update';

                // Handle image preview
                if (item.image_url) {
                    document.getElementById('form_existing_image').value = item.image_url;
                    document.getElementById('imagePreview').src = item.image_url;
                    document.getElementById('imagePreviewContainer').classList.remove('hidden');
                } else {
                    document.getElementById('form_existing_image').value = '';
                    document.getElementById('imagePreviewContainer').classList.add('hidden');
                }

                document.getElementById('itemModal').classList.remove('hidden');
            }
        });
}

function closeModal(id) {
    document.getElementById(id).classList.add('hidden');
}

// Image preview on file selection
document.getElementById('form_image')?.addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('imagePreview').src = e.target.result;
            document.getElementById('imagePreviewContainer').classList.remove('hidden');
        };
        reader.readAsDataURL(file);
    }
});

function toggleAvailable(id) {
    const formData = new FormData();
    formData.append('action', 'toggle_available');
    formData.append('item_id', id);
    formData.append('ajax', '1');

    fetch('', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            if (data.success) location.reload();
        });
}

function toggleFeatured(id) {
    const formData = new FormData();
    formData.append('action', 'toggle_featured');
    formData.append('item_id', id);
    formData.append('ajax', '1');

    fetch('', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            if (data.success) location.reload();
        });
}

function deleteItem(id, name) {
    if (!confirm('Delete menu item "' + name + '"?')) return;

    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="item_id" value="${id}">
    `;
    document.body.appendChild(form);
    form.submit();
}
</script>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
