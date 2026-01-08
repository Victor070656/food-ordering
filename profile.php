<?php
/**
 * Customer Profile Page
 */

require_once dirname(__FILE__) . '/config/config.php';

requireLogin();
if (!hasRole('customer')) {
    redirect(SITE_URL . '/auth.php');
}

$page_title = 'My Profile';

$customerUser = new CustomerUser();

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_POST['action'] === 'update_profile') {
        $result = $customerUser->updateProfile($_SESSION['user_id'], [
            'name' => sanitize($_POST['name']),
            'phone' => sanitize($_POST['phone']),
            'address' => sanitize($_POST['address'] ?? ''),
        ]);

        if ($result['success']) {
            $success = 'Profile updated successfully';
            $_SESSION['user_name'] = sanitize($_POST['name']);
        } else {
            $error = $result['message'];
        }
    } elseif ($_POST['action'] === 'change_password') {
        $result = $customerUser->changePassword(
            $_SESSION['user_id'],
            $_POST['current_password'],
            $_POST['new_password']
        );

        if ($result['success']) {
            $success = $result['message'];
        } else {
            $error = $result['message'];
        }
    }
}

$profile = $customerUser->getProfile($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">

<!-- Navigation -->
<nav class="bg-white shadow-sm sticky top-0 z-40">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            <a href="<?php echo SITE_URL; ?>" class="flex items-center gap-2">
                <div class="w-10 h-10 bg-gradient-to-br from-amber-500 to-orange-600 rounded-xl flex items-center justify-center">
                    <i class="fas fa-utensils text-white"></i>
                </div>
                <span class="font-bold text-xl text-gray-800"><?php echo SITE_NAME; ?></span>
            </a>
            <div class="flex items-center gap-4">
                <a href="dashboard.php" class="text-gray-600 hover:text-amber-600 font-medium">Dashboard</a>
                <a href="<?php echo SITE_URL; ?>/" class="text-gray-600 hover:text-amber-600 font-medium">Order Food</a>
                <a href="<?php echo SITE_URL; ?>logout.php" class="text-red-500 hover:text-red-600 font-medium">Logout</a>
            </div>
        </div>
    </div>
</nav>

<main class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    <div class="mb-6">
        <a href="dashboard.php" class="text-amber-600 hover:underline text-sm">
            <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
        </a>
    </div>

    <h1 class="text-2xl font-bold text-gray-800 mb-6">My Profile</h1>

    <?php if ($success): ?>
    <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-xl flex items-center gap-3">
        <i class="fas fa-check-circle text-green-500"></i>
        <span class="text-green-700 text-sm"><?php echo htmlspecialchars($success); ?></span>
    </div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl flex items-center gap-3">
        <i class="fas fa-exclamation-circle text-red-500"></i>
        <span class="text-red-700 text-sm"><?php echo htmlspecialchars($error); ?></span>
    </div>
    <?php endif; ?>

    <!-- Profile Info -->
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">Personal Information</h2>
        <form method="POST" action="">
            <input type="hidden" name="action" value="update_profile">
            <div class="space-y-4">
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Full Name</label>
                        <input type="text" name="name" value="<?php echo htmlspecialchars($profile['name']); ?>" required class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Phone Number</label>
                        <input type="tel" name="phone" value="<?php echo htmlspecialchars($profile['phone']); ?>" required class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                    <input type="email" value="<?php echo htmlspecialchars($profile['email']); ?>" disabled class="w-full px-4 py-2.5 border border-gray-200 rounded-xl bg-gray-50 text-gray-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Default Delivery Address</label>
                    <textarea name="address" rows="3" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500" placeholder="Enter your default address"><?php echo htmlspecialchars($profile['address'] ?? ''); ?></textarea>
                </div>
                <div class="flex justify-end">
                    <button type="submit" class="px-6 py-2.5 bg-amber-500 text-white font-medium rounded-xl hover:bg-amber-600">
                        Save Changes
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Change Password -->
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">Change Password</h2>
        <form method="POST" action="">
            <input type="hidden" name="action" value="change_password">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Current Password</label>
                    <input type="password" name="current_password" required class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">New Password</label>
                    <input type="password" name="new_password" required minlength="6" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Confirm New Password</label>
                    <input type="password" name="confirm_password" required minlength="6" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                </div>
                <div class="flex justify-end">
                    <button type="submit" class="px-6 py-2.5 bg-gray-800 text-white font-medium rounded-xl hover:bg-gray-700">
                        Update Password
                    </button>
                </div>
            </div>
        </form>
    </div>

</main>

</body>
</html>
