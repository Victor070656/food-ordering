<?php
/**
 * Header Include
 * Must be included after config.php
 */

if (!defined('FOODSYS_INIT')) {
    die('Direct access not allowed');
}

$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'Dashboard'; ?> - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        amber: {
                            50: '#fefce8',
                            100: '#fef3c7',
                            200: '#fde68a',
                            300: '#fcd34d',
                            400: '#fbbf24',
                            500: '#f59e0b',
                            600: '#d97706',
                            700: '#b45309',
                            800: '#92400e',
                            900: '#78350f',
                        }
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50 min-h-screen">

<?php if (isLoggedIn()): ?>
<!-- Sidebar Navigation -->
<?php
$dashboardPath = '';
$navItems = [];

switch ($user['role']) {
    case 'admin':
        $dashboardPath = SITE_URL . '/admin';
        $navItems = [
            ['name' => 'Dashboard', 'icon' => 'fa-chart-line', 'link' => $dashboardPath],
            ['name' => 'Menu', 'icon' => 'fa-utensils', 'link' => $dashboardPath . '/menu.php'],
            ['name' => 'Orders', 'icon' => 'fa-shopping-bag', 'link' => $dashboardPath . '/orders.php'],
            ['name' => 'Customers', 'icon' => 'fa-users', 'link' => $dashboardPath . '/customers.php'],
            ['name' => 'Riders', 'icon' => 'fa-motorcycle', 'link' => $dashboardPath . '/riders.php'],
            ['name' => 'Payments', 'icon' => 'fa-credit-card', 'link' => $dashboardPath . '/payments.php'],
            ['name' => 'Payment Settings', 'icon' => 'fa-cog', 'link' => $dashboardPath . '/payment-settings.php'],
            ['name' => 'Notifications', 'icon' => 'fa-bell', 'link' => $dashboardPath . '/notifications.php'],
            ['name' => 'Users', 'icon' => 'fa-user-cog', 'link' => $dashboardPath . '/users.php'],
        ];
        break;
    case 'staff':
        $dashboardPath = SITE_URL . '/staff';
        $navItems = [
            ['name' => 'Dashboard', 'icon' => 'fa-chart-line', 'link' => $dashboardPath],
            ['name' => 'Orders', 'icon' => 'fa-shopping-bag', 'link' => $dashboardPath . '/orders.php'],
            ['name' => 'Customers', 'icon' => 'fa-users', 'link' => $dashboardPath . '/customers.php'],
        ];
        break;
    case 'rider':
        $dashboardPath = SITE_URL . '/rider';
        $navItems = [
            ['name' => 'Dashboard', 'icon' => 'fa-chart-line', 'link' => $dashboardPath],
            ['name' => 'My Deliveries', 'icon' => 'fa-shopping-bag', 'link' => $dashboardPath . '/deliveries.php'],
        ];
        break;
}
?>
<div class="flex min-h-screen">
    <!-- Desktop Sidebar -->
    <aside class="hidden md:flex md:flex-col md:w-64 md:fixed md:h-screen bg-white border-r border-gray-200 z-30">
        <!-- Logo -->
        <div class="flex items-center gap-3 p-6 border-b border-gray-100">
            <div class="w-10 h-10 bg-gradient-to-br from-amber-500 to-orange-600 rounded-xl flex items-center justify-center">
                <i class="fas fa-utensils text-white"></i>
            </div>
            <div>
                <h1 class="font-bold text-gray-800 text-lg"><?php echo SITE_NAME; ?></h1>
                <p class="text-xs text-gray-400 capitalize"><?php echo $user['role']; ?> Portal</p>
            </div>
        </div>

        <!-- Navigation -->
        <nav class="flex-1 p-4 space-y-1 overflow-y-auto">
            <?php
            $currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
            foreach ($navItems as $item):
                $isActive = (strpos($currentPath, basename($item['link'])) !== false) ||
                           ($currentPath === $item['link']);
            ?>
            <a href="<?php echo $item['link']; ?>"
               class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all <?php echo $isActive ? 'bg-amber-50 text-amber-600' : 'text-gray-600 hover:bg-gray-50'; ?>">
                <i class="fas <?php echo $item['icon']; ?> w-5"></i>
                <span class="font-medium"><?php echo $item['name']; ?></span>
            </a>
            <?php endforeach; ?>
        </nav>

        <!-- User Section -->
        <div class="p-4 border-t border-gray-100">
            <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-xl">
                <div class="w-10 h-10 bg-gradient-to-br from-amber-400 to-orange-500 rounded-full flex items-center justify-center text-white font-semibold">
                    <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="font-medium text-gray-800 truncate"><?php echo htmlspecialchars($user['name']); ?></p>
                    <p class="text-xs text-gray-500 truncate"><?php echo htmlspecialchars($user['email']); ?></p>
                </div>
            </div>
            <a href="<?php echo SITE_URL; ?>/logout.php" class="mt-3 flex items-center gap-3 px-4 py-2 text-gray-500 hover:text-red-500 transition-colors">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </div>
    </aside>

    <!-- Mobile Header -->
    <div class="md:hidden fixed top-0 left-0 right-0 bg-white border-b border-gray-200 z-20">
        <div class="flex items-center justify-between px-4 py-3">
            <div class="flex items-center gap-3">
                <button onclick="toggleSidebar()" class="p-2 -ml-2 text-gray-600 hover:bg-gray-100 rounded-lg">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 bg-gradient-to-br from-amber-500 to-orange-600 rounded-lg flex items-center justify-center">
                        <i class="fas fa-utensils text-white text-sm"></i>
                    </div>
                    <span class="font-bold text-gray-800"><?php echo SITE_NAME; ?></span>
                </div>
            </div>
            <a href="<?php echo SITE_URL; ?>/logout.php" class="p-2 text-gray-500 hover:text-red-500">
                <i class="fas fa-sign-out-alt"></i>
            </a>
        </div>
    </div>

    <!-- Mobile Sidebar Overlay -->
    <div id="sidebarOverlay" onclick="toggleSidebar()" class="hidden fixed inset-0 bg-black/50 z-30 md:hidden"></div>

    <!-- Mobile Sidebar -->
    <div id="mobileSidebar" class="hidden fixed left-0 top-0 bottom-0 w-64 bg-white z-40 md:hidden">
        <div class="flex items-center justify-between p-4 border-b border-gray-100">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 bg-gradient-to-br from-amber-500 to-orange-600 rounded-lg flex items-center justify-center">
                    <i class="fas fa-utensils text-white text-sm"></i>
                </div>
                <span class="font-bold text-gray-800"><?php echo SITE_NAME; ?></span>
            </div>
            <button onclick="toggleSidebar()" class="p-2 text-gray-500 hover:bg-gray-100 rounded-lg">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <nav class="p-4 space-y-1 overflow-y-auto max-h-[calc(100vh-70px)]">
            <?php foreach ($navItems as $item):
                $isActive = (strpos($_SERVER['REQUEST_URI'], basename($item['link'])) !== false);
            ?>
            <a href="<?php echo $item['link']; ?>"
               onclick="toggleSidebar()"
               class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all <?php echo $isActive ? 'bg-amber-50 text-amber-600' : 'text-gray-600 hover:bg-gray-50'; ?>">
                <i class="fas <?php echo $item['icon']; ?> w-5"></i>
                <span class="font-medium"><?php echo $item['name']; ?></span>
            </a>
            <?php endforeach; ?>
        </nav>
    </div>

    <!-- Main Content -->
    <main class="flex-1 md:ml-64 pt-16 md:pt-0 min-h-screen">
        <div class="p-4 md:p-8">

<?php else: ?>
<main class="min-h-screen">
    <div class="p-4 md:p-8 max-w-7xl mx-auto">
<?php endif; ?>

<?php
$flash = getFlash();
if ($flash):
?>
<!-- Flash Message -->
<div class="mb-6 p-4 <?php echo $flash['type'] === 'success' ? 'bg-green-50 border-green-200 text-green-700' : 'bg-red-50 border-red-200 text-red-700'; ?> border rounded-xl flex items-center gap-3">
    <i class="fas <?php echo $flash['type'] === 'success' ? 'fa-check-circle text-green-500' : 'fa-exclamation-circle text-red-500'; ?>"></i>
    <span><?php echo htmlspecialchars($flash['message']); ?></span>
</div>
<?php endif; ?>

<script>
function toggleSidebar() {
    const sidebar = document.getElementById('mobileSidebar');
    const overlay = document.getElementById('sidebarOverlay');
    sidebar.classList.toggle('hidden');
    overlay.classList.toggle('hidden');
}
</script>
