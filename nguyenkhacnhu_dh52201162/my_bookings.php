<?php
require 'config.php';

// BẢO VỆ: Nếu chưa đăng nhập, chuyển hướng về trang đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$role = $_SESSION['role'];
$message = '';
$bookings = [];

// 1. Xử lý Hủy Đặt Lịch
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['cancel_booking_id'])) {
    $booking_id = $_POST['cancel_booking_id'];
    try {
        $stmt = $pdo->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ? AND user_id = ? AND status IN ('pending', 'confirmed')");
        if ($stmt->execute([$booking_id, $user_id]) && $stmt->rowCount() > 0) {
            $message = "Đã hủy đặt lịch thành công!";
        } else {
            $message = "Lỗi: Không thể hủy (Có thể đã quá hạn hoặc đã hủy rồi).";
        }
    } catch (PDOException $e) {
        $message = "Lỗi hệ thống: " . $e->getMessage();
    }
}

// 2. Lấy Lịch sử
try {
    $stmt = $pdo->prepare("
        SELECT b.id, b.start_time, b.end_time, b.status, s.name AS service_name, s.price, s.duration_minutes
        FROM bookings b
        JOIN services s ON b.service_id = s.id
        WHERE b.user_id = ?
        ORDER BY b.start_time DESC
    ");
    $stmt->execute([$user_id]);
    $bookings = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Lỗi truy vấn: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Lịch sử Đặt lịch</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        /* --- CSS GIAO DIỆN RỘNG (FULLSCREEN) --- */
        
        body { background-color: #f8fafc; }

        /* Khung chứa chính: Rộng 96% màn hình */
        .history-container { 
            max-width: 96%; 
            margin: 30px auto; 
            padding: 40px; 
            background: white; 
            border-radius: 16px; 
            box-shadow: 0 4px 20px rgba(0,0,0,0.03);
        }
        
        .history-header { 
            display: flex; justify-content: space-between; align-items: center; 
            margin-bottom: 30px; border-bottom: 2px solid #f1f5f9; padding-bottom: 20px;
        }
        
        .history-header h1 { font-size: 32px; color: #1e293b; margin: 0; font-weight: 800; }

        /* Bảng dữ liệu rộng rãi */
        .table-responsive { overflow-x: auto; }
        
        table { width: 100%; border-collapse: collapse; min-width: 900px; }
        
        th { 
            background-color: #f1f5f9; color: #475569; font-weight: 700; text-transform: uppercase; 
            font-size: 14px; padding: 20px; text-align: left; border-radius: 8px 8px 0 0;
        }
        
        td { padding: 20px; border-bottom: 1px solid #f1f5f9; color: #334155; font-size: 16px; vertical-align: middle; }
        
        tr:last-child td { border-bottom: none; }
        tr:hover { background-color: #f8fafc; transition: 0.2s; }

        /* Trạng thái (Badges) */
        .status-badge { padding: 8px 15px; border-radius: 30px; font-size: 14px; font-weight: 600; display: inline-block; min-width: 100px; text-align: center;}
        .status-pending { background-color: #fff7ed; color: #c2410c; }
        .status-confirmed { background-color: #f0fdf4; color: #15803d; }
        .status-cancelled { background-color: #fef2f2; color: #b91c1c; }
        .status-completed { background-color: #eff6ff; color: #1d4ed8; }

        /* Nút Hủy */
        .btn-cancel {
            background-color: white; color: #ef4444; border: 1px solid #ef4444;
            padding: 8px 16px; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 14px;
            transition: 0.2s;
        }
        .btn-cancel:hover { background-color: #ef4444; color: white; }

        .alert-box { margin-bottom: 20px; padding: 15px; border-radius: 8px; background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; font-weight: bold;}
        .alert-box.error { background: #fee2e2; color: #991b1b; border-color: #fca5a5; }
        
        .empty-state { text-align: center; padding: 80px; color: #94a3b8; font-size: 18px; font-style: italic; }
        .service-info { font-size: 1.1em; font-weight: 700; color: #0f172a; margin-bottom: 5px; }
    </style>
</head>
<body>

    <header class="main-header">
        <a href="index.php" class="brand-logo">Hệ thống<span>ĐặtLịch</span></a>
        <div class="user-nav">
            <span>Hi, <strong><?= htmlspecialchars($username) ?></strong></span>
            <a href="index.php" class="nav-link">Trang chủ</a>
            <a href="logout.php" class="btn-auth btn-logout">Đăng xuất</a>
        </div>
    </header>

    <div class="history-container">
        
        <div class="history-header">
            <h1>Quản Lý Lịch Đặt Của Tôi</h1>
            <a href="index.php" class="btn-primary" style="padding: 12px 25px; border-radius: 8px; font-size: 15px; text-decoration: none; display: inline-block;">+ Đặt Thêm Dịch Vụ</a>
        </div>

        <?php if ($message): ?>
            <div class="alert-box <?= strpos($message, 'Lỗi') !== false ? 'error' : '' ?>">
                <?= $message ?>
            </div>
        <?php endif; ?>

        <?php if (count($bookings) > 0): ?>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th width="30%">Dịch vụ</th>
                            <th>Thời gian sử dụng</th>
                            <th>Chi phí</th>
                            <th>Trạng thái</th>
                            <th style="text-align: right;">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bookings as $booking): 
                            $start = date('H:i d/m/Y', strtotime($booking['start_time']));
                            $end = date('H:i', strtotime($booking['end_time']));
                            $status_class = 'status-' . strtolower($booking['status']);
                            
                            $status_map = [
                                'pending' => 'Chờ duyệt', 'confirmed' => 'Đã xác nhận',
                                'cancelled' => 'Đã hủy', 'completed' => 'Hoàn thành'
                            ];
                            $status_text = $status_map[$booking['status']] ?? ucfirst($booking['status']);
                        ?>
                            <tr>
                                <td>
                                    <div class="service-info"><?= htmlspecialchars($booking['service_name']) ?></div>
                                    <div style="font-size: 14px; color: #64748b;">Mã đơn: #<?= $booking['id'] ?></div>
                                </td>
                                <td>
                                    <div style="font-weight: 600; color: #334155;"><?= $start ?></div>
                                    <div style="font-size: 14px; color: #94a3b8;">đến <?= $end ?></div>
                                </td>
                                <td>
                                    <div style="color: #10b981; font-weight: 700; font-size: 1.1em;"><?= number_format($booking['price'], 0, ',', '.') ?>đ</div>
                                    <div style="font-size: 13px; color: #64748b;">(<?= $booking['duration_minutes'] ?> phút)</div>
                                </td>
                                <td>
                                    <span class="status-badge <?= $status_class ?>"><?= $status_text ?></span>
                                </td>
                                <td style="text-align: right;">
                                    <?php if (in_array($booking['status'], ['pending', 'confirmed'])): ?>
                                        <form method="POST" onsubmit="return confirm('Bạn có chắc chắn muốn hủy đơn này không?');">
                                            <input type="hidden" name="cancel_booking_id" value="<?= $booking['id'] ?>">
                                            <button type="submit" class="btn-cancel">Hủy Đơn</button>
                                        </form>
                                    <?php else: ?>
                                        <span style="color: #cbd5e1; font-size: 20px;">&mdash;</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <p>Bạn chưa có lịch sử đặt nào.</p>
                <a href="index.php" style="color: #3b82f6; text-decoration: none; font-weight: 600;">Quay lại trang chủ để đặt ngay &rarr;</a>
            </div>
        <?php endif; ?>
    </div>

</body>
</html>