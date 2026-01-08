<?php
/**
 * FoodSys - Configuration File
 * Home-Cooked Meal Delivery Management System
 */

// Prevent direct access
if (!defined('FOODSYS_INIT')) {
    define('FOODSYS_INIT', true);
}

// =====================================================
// DATABASE CONFIGURATION
// =====================================================
define('DB_HOST', 'localhost');
define('DB_NAME', 'foodsys');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// =====================================================
// SITE CONFIGURATION
// =====================================================
define('SITE_NAME', 'FoodSys');
define('SITE_URL', 'http://localhost/food-sys');
define('ADMIN_EMAIL', 'admin@foodsys.com');

// =====================================================
// PATH CONSTANTS
// =====================================================
define('ROOT_PATH', dirname(__DIR__)); // Assumes config is in /path/to/food-sys/config/
define('CONFIG_PATH', ROOT_PATH . '/config');
define('CLASSES_PATH', ROOT_PATH . '/classes');
define('INCLUDES_PATH', ROOT_PATH . '/includes');
define('PUBLIC_PATH', ROOT_PATH . '/public');
define('ADMIN_PATH', ROOT_PATH . '/admin');
define('STAFF_PATH', ROOT_PATH . '/staff');
define('RIDER_PATH', ROOT_PATH . '/rider');
define('API_PATH', ROOT_PATH . '/api');

// =====================================================
// CLASS AUTOLOADER
// =====================================================
spl_autoload_register(function ($class) {
    $file = CLASSES_PATH . '/' . $class . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// =====================================================
// SMS/WHATSAPP CONFIGURATION (Abstracted)
// =====================================================
define('SMS_ENABLED', false);
define('WHATSAPP_ENABLED', false);
define('SMS_API_KEY', '');
define('SMS_SENDER_ID', 'FoodSys');
define('WHATSAPP_PHONE_ID', '');
define('WHATSAPP_ACCESS_TOKEN', '');

// =====================================================
// SYSTEM SETTINGS
// =====================================================
define('DEFAULT_CURRENCY', '₦');
define('DEFAULT_DELIVERY_FEE', 500);
define('DATE_FORMAT', 'd M Y');
define('DATETIME_FORMAT', 'd M Y, h:i A');
define('TIMEZONE', 'Africa/Lagos');

// Set timezone
date_default_timezone_set(TIMEZONE);

// =====================================================
// ERROR REPORTING (Set to 0 in production)
// =====================================================
error_reporting(E_ALL);
ini_set('display_errors', 1);

// =====================================================
// SESSION CONFIGURATION
// =====================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// =====================================================
// DATABASE CONNECTION
// =====================================================
require_once CLASSES_PATH . '/Database.php';

try {
    $db = Database::getInstance();
} catch (Exception $e) {
    die('Database connection failed. Please check your configuration.');
}

// =====================================================
// HELPER FUNCTIONS
// =====================================================

/**
 * Redirect to a specific URL
 */
function redirect($url) {
    header("Location: $url");
    exit;
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Get current user data
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    return [
        'id' => $_SESSION['user_id'] ?? null,
        'name' => $_SESSION['user_name'] ?? null,
        'email' => $_SESSION['user_email'] ?? null,
        'role' => $_SESSION['user_role'] ?? null,
    ];
}

/**
 * Check if user has specific role
 */
function hasRole($role) {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === $role;
}

/**
 * Check if user has any of the specified roles
 */
function hasAnyRole($roles) {
    if (!is_array($roles)) {
        $roles = [$roles];
    }
    return isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], $roles);
}

/**
 * Require login - redirect if not logged in
 */
function requireLogin() {
    if (!isLoggedIn()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        redirect(SITE_URL . '/login.php');
    }
}

/**
 * Require specific role
 */
function requireRole($role) {
    requireLogin();
    if (!hasRole($role)) {
        redirect(SITE_URL . '/unauthorized.php');
    }
}

/**
 * Require any of the specified roles
 */
function requireAnyRole($roles) {
    requireLogin();
    if (!hasAnyRole($roles)) {
        redirect(SITE_URL . '/unauthorized.php');
    }
}

/**
 * Generate order number
 */
function generateOrderNumber() {
    return 'ORD' . date('Ymd') . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
}

/**
 * Format currency
 */
function formatCurrency($amount) {
    return DEFAULT_CURRENCY . number_format($amount, 2);
}

/**
 * Format date
 */
function formatDate($date, $format = null) {
    if (empty($date)) return '-';
    $format = $format ?? DATE_FORMAT;
    return date($format, strtotime($date));
}

/**
 * Format datetime
 */
function formatDateTime($datetime, $format = null) {
    if (empty($datetime)) return '-';
    $format = $format ?? DATETIME_FORMAT;
    return date($format, strtotime($datetime));
}

/**
 * Get status badge HTML
 */
function getStatusBadge($status) {
    $badges = [
        'pending' => '<span class="px-3 py-1 text-xs font-medium rounded-full bg-yellow-100 text-yellow-800">Pending</span>',
        'preparing' => '<span class="px-3 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800">Preparing</span>',
        'out_for_delivery' => '<span class="px-3 py-1 text-xs font-medium rounded-full bg-purple-100 text-purple-800">Out for Delivery</span>',
        'delivered' => '<span class="px-3 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">Delivered</span>',
        'failed' => '<span class="px-3 py-1 text-xs font-medium rounded-full bg-red-100 text-red-800">Failed</span>',
        'cancelled' => '<span class="px-3 py-1 text-xs font-medium rounded-full bg-red-100 text-red-800">Cancelled</span>',
        'paid' => '<span class="px-3 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">Paid</span>',
        'active' => '<span class="px-3 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">Active</span>',
        'inactive' => '<span class="px-3 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-800">Inactive</span>',
    ];

    return $badges[$status] ?? '<span class="px-3 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-800">' . ucfirst($status) . '</span>';
}

/**
 * Sanitize input
 */
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Get pagination info
 */
function getPaginationInfo($total, $perPage, $currentPage) {
    $totalPages = ceil($total / $perPage);
    $offset = ($currentPage - 1) * $perPage;

    return [
        'total' => $total,
        'per_page' => $perPage,
        'current_page' => $currentPage,
        'total_pages' => $totalPages,
        'offset' => $offset,
        'has_next' => $currentPage < $totalPages,
        'has_prev' => $currentPage > 1,
    ];
}

/**
 * Set flash message
 */
function setFlash($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Get and clear flash message
 */
function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * JSON response
 */
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Convert phone number to international format (Nigeria)
 */
function formatPhoneNumber($phone) {
    // Remove all non-numeric characters
    $phone = preg_replace('/[^0-9]/', '', $phone);

    // If starts with 0, replace with +234
    if (strpos($phone, '0') === 0) {
        $phone = '234' . substr($phone, 1);
    }

    return $phone;
}
