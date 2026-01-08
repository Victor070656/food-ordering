<?php
require_once dirname(__FILE__) . '/config/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unauthorized - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="bg-white p-8 rounded-lg shadow-md text-center max-w-md">
        <div class="text-red-500 text-6xl mb-4">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <h1 class="text-2xl font-bold text-gray-800 mb-2">Access Denied</h1>
        <p class="text-gray-600 mb-6">You don't have permission to access this page.</p>
        <a href="<?php echo SITE_URL; ?>/" class="bg-blue-500 text-white px-6 py-2 rounded hover:bg-blue-600 inline-block">Go Home</a>
    </div>
</body>
</html>
