<?php
/**
 * Customer Authentication Page (Login & Register)
 */

require_once dirname(__FILE__) . '/config/config.php';

$mode = $_GET['mode'] ?? 'login';
$customerUser = new CustomerUser();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_POST['action'] === 'login') {
        $result = $customerUser->login(
            sanitize($_POST['email']),
            $_POST['password']
        );

        if ($result['success']) {
            redirect(SITE_URL . '/dashboard.php');
        } else {
            $error = $result['message'];
            $mode = 'login';
        }
    } elseif ($_POST['action'] === 'register') {
        $result = $customerUser->register([
            'name' => sanitize($_POST['name']),
            'email' => sanitize($_POST['email']),
            'phone' => sanitize($_POST['phone']),
            'password' => $_POST['password'],
            'address' => sanitize($_POST['address'] ?? ''),
        ]);

        if ($result['success']) {
            redirect(SITE_URL . '/dashboard.php');
        } else {
            $error = $result['message'];
            $mode = 'register';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $mode === 'login' ? 'Login' : 'Register'; ?> - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gradient-to-br from-amber-50 via-orange-50 to-yellow-50 flex items-center justify-center p-4">

    <div class="w-full max-w-md">
        <!-- Logo -->
        <div class="text-center mb-8">
            <a href="<?php echo SITE_URL; ?>/">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-amber-500 to-orange-600 rounded-2xl shadow-lg mb-4">
                    <i class="fas fa-utensils text-white text-2xl"></i>
                </div>
                <h1 class="text-3xl font-bold text-gray-800"><?php echo SITE_NAME; ?></h1>
            </a>
        </div>

        <!-- Card -->
        <div class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden">
            <!-- Tabs -->
            <div class="flex border-b border-gray-100">
                <button onclick="switchMode('login')" id="loginTab" class="flex-1 py-4 text-sm font-medium <?php echo $mode === 'login' ? 'text-amber-600 border-b-2 border-amber-600' : 'text-gray-500 hover:text-gray-700'; ?>">
                    Login
                </button>
                <button onclick="switchMode('register')" id="registerTab" class="flex-1 py-4 text-sm font-medium <?php echo $mode === 'register' ? 'text-amber-600 border-b-2 border-amber-600' : 'text-gray-500 hover:text-gray-700'; ?>">
                    Register
                </button>
            </div>

            <div class="p-8">
                <?php if (isset($error)): ?>
                <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl flex items-center gap-3">
                    <i class="fas fa-exclamation-circle text-red-500"></i>
                    <span class="text-red-700 text-sm"><?php echo htmlspecialchars($error); ?></span>
                </div>
                <?php endif; ?>

                <!-- Login Form -->
                <form id="loginForm" method="POST" action="" class="<?php echo $mode === 'register' ? 'hidden' : ''; ?>">
                    <input type="hidden" name="action" value="login">
                    <div class="space-y-5">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                            <input type="email" name="email" required class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500" placeholder="you@example.com">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                            <input type="password" name="password" required class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500" placeholder="Enter your password">
                        </div>
                        <button type="submit" class="w-full py-3 bg-gradient-to-r from-amber-500 to-orange-600 text-white font-semibold rounded-xl hover:from-amber-600 hover:to-orange-700 shadow-lg">
                            Sign In
                        </button>
                    </div>
                </form>

                <!-- Register Form -->
                <form id="registerForm" method="POST" action="" class="<?php echo $mode === 'login' ? 'hidden' : ''; ?>">
                    <input type="hidden" name="action" value="register">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Full Name *</label>
                            <input type="text" name="name" required class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500" placeholder="John Doe">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Email *</label>
                            <input type="email" name="email" required class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500" placeholder="you@example.com">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Phone Number *</label>
                            <input type="tel" name="phone" required class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500" placeholder="08012345678">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Password *</label>
                            <input type="password" name="password" required class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500" placeholder="Minimum 6 characters">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Delivery Address</label>
                            <textarea name="address" rows="2" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500" placeholder="Your default delivery address"></textarea>
                        </div>
                        <button type="submit" class="w-full py-3 bg-gradient-to-r from-amber-500 to-orange-600 text-white font-semibold rounded-xl hover:from-amber-600 hover:to-orange-700 shadow-lg">
                            Create Account
                        </button>
                    </div>
                </form>
            </div>

            <div class="px-8 py-4 bg-gray-50 border-t border-gray-100 text-center">
                <a href="<?php echo SITE_URL; ?>/" class="text-gray-500 text-sm hover:text-amber-600">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Home
                </a>
            </div>
        </div>

        <p class="text-center text-gray-400 text-sm mt-6">
            &copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.
        </p>
    </div>

    <script>
    function switchMode(mode) {
        const loginForm = document.getElementById('loginForm');
        const registerForm = document.getElementById('registerForm');
        const loginTab = document.getElementById('loginTab');
        const registerTab = document.getElementById('registerTab');

        if (mode === 'login') {
            loginForm.classList.remove('hidden');
            registerForm.classList.add('hidden');
            loginTab.classList.add('text-amber-600', 'border-b-2', 'border-amber-600');
            loginTab.classList.remove('text-gray-500');
            registerTab.classList.remove('text-amber-600', 'border-b-2', 'border-amber-600');
            registerTab.classList.add('text-gray-500');
        } else {
            loginForm.classList.add('hidden');
            registerForm.classList.remove('hidden');
            registerTab.classList.add('text-amber-600', 'border-b-2', 'border-amber-600');
            registerTab.classList.remove('text-gray-500');
            loginTab.classList.remove('text-amber-600', 'border-b-2', 'border-amber-600');
            loginTab.classList.add('text-gray-500');
        }
    }
    </script>
</body>
</html>
