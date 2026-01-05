<?php
// 1. Gọi file cấu hình (để lấy biến $pdo)
require_once '../config.php'; 

// 2. Kiểm tra session (Tránh xung đột)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 3. KIỂM TRA QUYỀN (Dùng đúng biến ['role'] => 'admin' của bạn)
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    // Nếu không phải admin thì đá về trang đăng nhập
    header("Location: login.php");
    exit;
}

// 4. Xử lý cập nhật
if (isset($_GET['id'])) {
    $id = $_GET['id'];

    try {
        // Cập nhật trạng thái payment_status = 1
        $sql = "UPDATE bookings SET payment_status = 1 WHERE id = :id";
        
        // SỬA LỖI Ở ĐÂY: Dùng $pdo thay vì $conn
        $stmt = $pdo->prepare($sql);
        
        if ($stmt->execute(['id' => $id])) {
            // Thành công -> Quay về trang danh sách
            header("Location: index.php"); 
            exit;
        } else {
            echo "Lỗi khi cập nhật đơn hàng số $id";
        }
    } catch (PDOException $e) {
        echo "Lỗi kết nối: " . $e->getMessage();
    }
} else {
    // Nếu không có ID thì quay về
    header("Location: index.php");
    exit;
}
?>