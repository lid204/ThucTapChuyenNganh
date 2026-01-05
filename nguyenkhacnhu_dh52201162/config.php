<?php 
// Bật báo cáo lỗi
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Cấu hình Database
$host = "127.0.0.1";   // Dùng IP này thay cho "localhost" để tránh lỗi kết nối
$user = "root";
$pass = "";
$dbname = "booking_app"; 
$charset = "utf8mb4";

$dsn = "mysql:host=$host;dbname=$dbname;charset=$charset"; 
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     die("Lỗi kết nối Database: " . $e->getMessage());
}

// Bắt đầu Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}