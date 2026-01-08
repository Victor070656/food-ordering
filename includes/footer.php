<?php
/**
 * Footer Include
 */

if (!defined('FOODSYS_INIT')) {
    die('Direct access not allowed');
}
?>

<?php if (isLoggedIn()): ?>
        </div>
    </main>
</div>

<!-- Mobile Bottom Navigation (for riders) -->
<?php
$user = getCurrentUser();
if ($user && $user['role'] === 'rider'):
?>
<nav class="md:hidden fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 px-4 py-2 flex justify-around z-20">
    <a href="<?php echo SITE_URL; ?>/rider/" class="flex flex-col items-center gap-1 p-2 text-amber-600">
        <i class="fas fa-home"></i>
        <span class="text-xs">Home</span>
    </a>
    <a href="<?php echo SITE_URL; ?>/rider/deliveries.php" class="flex flex-col items-center gap-1 p-2 text-gray-500">
        <i class="fas fa-motorcycle"></i>
        <span class="text-xs">Deliveries</span>
    </a>
    <a href="<?php echo SITE_URL; ?>/public/logout.php" class="flex flex-col items-center gap-1 p-2 text-gray-500">
        <i class="fas fa-sign-out-alt"></i>
        <span class="text-xs">Logout</span>
    </a>
</nav>
<div class="h-16 md:hidden"></div>
<?php endif; ?>

<?php else: ?>
    </div>
</main>
<?php endif; ?>

</body>
</html>
