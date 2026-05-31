<?php
declare(strict_types=1);
define('APP_NAME', 'Quick Cart');
$_base = getenv('BASE_URL');
define('BASE_URL', rtrim($_base !== false ? (string)$_base : '/quick_cart_final_system', '/'));
define('LOW_STOCK_THRESHOLD', 10);
?>
