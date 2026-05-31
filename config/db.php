<?php
declare(strict_types=1);
require_once __DIR__ . '/app.php';
$host     = (string)(getenv('DB_HOST') ?: 'localhost');
$user     = (string)(getenv('DB_USER') ?: 'root');
$password = (string)(getenv('DB_PASS') ?: '');
$dbname   = (string)(getenv('DB_NAME') ?: 'quick_cart');
$conn = new mysqli($host, $user, $password, $dbname);
if ($conn->connect_error) { die("Database connection failed: " . $conn->connect_error); }
$conn->set_charset("utf8mb4");
?>
