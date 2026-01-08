<?php
/**
 * Logout Handler
 */

require_once dirname(__FILE__) . '/config/config.php';

$user = new User();
$user->logout();

redirect(SITE_URL . '/login.php');
