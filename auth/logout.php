<?php
require_once __DIR__ . '/../includes/functions.php';
session_unset();
session_destroy();
session_start();
$_SESSION['flash_success'] = 'You have been logged out.';
redirect(url('auth/login.php'));
?>
