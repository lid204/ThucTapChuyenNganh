<?php
// 1. CẤU HÌNH (Đi ngược ra thư mục cha để gọi config)
require '../config.php';

// 2. BẢO VỆ: Chỉ Admin mới được vào
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$message = '';

// 3. XỬ LÝ: DUYỆT HOẶC HỦY
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    $booking_id = $_POST['booking_id'];
    $new_status = ($_POST['action'] === 'confirm') ? 'confirmed' : 'cancelled';

    try {
        // Chỉ cập nhật nếu trạng thái hiện tại là 'pending' hoặc 'confirmed'
        $stmt = $pdo->prepare("UPDATE bookings SET status = ? WHERE id = ? AND status IN ('pending', 'confirmed')");
        
        if ($stmt->execute([$new_status, $booking_id]) && $stmt->rowCount() > 0) {
            $message = ($new_status == 'confirmed') 
                ? "✅ Đã duyệt đơn đặt lịch #$booking_id" 
                : "⚠️ Đã hủy đơn đặt lịch #$booking_id";
        }
    } catch (PDOException $e) {
        $message = "Lỗi: " . $e->getMessage();
    }
}

// 4. LẤY DỮ LIỆU: Danh sách đặt lịch
try {
    $sql = "SELECT b.*, u.username, u.email, s.name as service_name 
            FROM bookings b
            JOIN users u ON b.user_id = u.id
            JOIN services s ON b.service_id = s.id
            ORDER BY b.booked_at DESC";
    $bookings = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Lỗi truy vấn: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Trị | Hệ thống Đặt Lịch</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <style>
        /* --- CSS RIÊNG CHO TRANG ADMIN --- */
        :root {
            --primary: #3B82F6; --danger: #EF4444; --success: #10B981; --warning: #F59E0B;
            --text-main: #0F172A; --text-light: #64748B; --bg: #F1F5F9; --white: #FFFFFF;
        }
        
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: var(--bg); color: var(--text-main); padding-bottom: 50px;}

        /* HEADER */
        .admin-header {
            background: var(--white); padding: 15px 40px; display: flex; justify-content: space-between; align-items: center;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1); position: sticky; top: 0; z-index: 100;
        }
        .brand { font-size: 20px; font-weight: 800; color: var(--primary); text-decoration: none; text-transform: uppercase; letter-spacing: 1px;}
        
        .user-nav { display: flex; gap: 15px; align-items: center; font-size: 14px; font-weight: 600; }
        .btn-home { color: var(--text-light); text-decoration: none; transition: 0.2s; }
        .btn-home:hover { color: var(--primary); }
        .btn-logout { color: var(--danger); text-decoration: none; border: 1px solid var(--danger); padding: 6px 15px; border-radius: 6px; transition: 0.2s; }
        .btn-logout:hover { background: var(--danger); color: white; }
        
        /* NAV TAB (Menu phụ) */
        .sub-nav {
            background: white; padding: 10px 40px; margin-bottom: 30px; border-bottom: 1px solid #e2e8f0;
            display: flex; gap: 20px;
        }
        .sub-nav a { text-decoration: none; color: var(--text-light); font-weight: 600; font-size: 14px; padding-bottom: 10px; border-bottom: 2px solid transparent; }
        .sub-nav a.active { color: var(--primary); border-bottom-color: var(--primary); }
        .sub-nav a:hover { color: var(--primary); }

        /* CONTAINER */
        .container { max-width: 1200px; margin: 0 auto; padding: 0 20px; }

        .page-title { font-size: 24px; font-weight: 800; margin-bottom: 20px; color: var(--text-main); }

        /* THÔNG BÁO */
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; font-weight: 600; font-size: 14px; }
        .alert-info { background: #dbeafe; color: #1e40af; border: 1px solid #bfdbfe; }

        /* BẢNG DỮ LIỆU (TABLE) */
        .card { background: white; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); overflow: hidden; border: 1px solid #e2e8f0; }
        
        table { width: 100%; border-collapse: collapse; }
        th { background: #f8fafc; text-align: left; padding: 16px; font-size: 13px; text-transform: uppercase; color: var(--text-light); font-weight: 700; border-bottom: 1px solid #e2e8f0; }
        td { padding: 16px; border-bottom: 1px solid #f1f5f9; font-size: 14px; vertical-align: middle; }
        tr:last-child td { border-bottom: none; }
        tr:hover { background: #f8fafc; }

        /* BADGES TRẠNG THÁI */
        .badge { padding: 6px 12px; border-radius: 50px; font-size: 12px; font-weight: 700; text-transform: capitalize; }
        .status-pending { background: #fff7ed; color: #c2410c; }
        .status-confirmed { background: #ecfdf5; color: #047857; }
        .status-cancelled { background: #fef2f2; color: #b91c1c; }
        .status-completed { background: #eff6ff; color: #1d4ed8; }

        /* BUTTONS */
        .action-form { display: inline-block; margin-right: 5px; }
        .btn-action { border: none; padding: 6px 12px; border-radius: 6px; font-weight: 600; cursor: pointer; font-size: 12px; transition: 0.2s; }
        .btn-confirm { background: var(--success); color: white; }
        .btn-confirm:hover { background: #059669; }
        .btn-cancel { background: #f3f4f6; color: var(--text-light); }
        .btn-cancel:hover { background: var(--danger); color: white; }
    </style>
</head>
<body>

    <header class="admin-header">
        <a href="#" class="brand">ADMIN PORTAL</a>
        <div class="user-nav">
            <span>Hi, <?= htmlspecialchars($_SESSION['username']) ?></span>
            <span style="color:#ddd">|</span>
            <a href="../index.php" class="btn-home">Xem Trang Chủ</a>
            <a href="../logout.php" class="btn-logout">Thoát</a>
        </div>
    </header>

    <div class="sub-nav">
        <a href="index.php" class="active">Đơn Đặt Lịch</a>
        <a href="categories.php">Danh Mục</a>
        <a href="services.php">Dịch Vụ</a>
        <a href="users.php">Tài Khoản</a>
    </div>

    <div class="container">
        <div class="page-title">Quản Lý Đặt Lịch</div>

        <?php if ($message): ?>
            <div class="alert alert-info"><?= $message ?></div>
        <?php endif; ?>

        <div class="card">
            <?php if (count($bookings) > 0): ?>
                <table cellspacing="0">
                    <thead>
                        <tr>
                            <th style="width: 5%">ID</th>
                            <th style="width: 15%">Khách hàng</th>
                            <th style="width: 15%">Dịch vụ</th>
                            <th style="width: 15%">Thời gian</th>
                            <th style="width: 10%">Trạng thái</th>
                            <th style="width: 15%">Tổng Tiền</th>
                            <th style="width: 15%; text-align:center;">Thanh toán</th>
                            <th style="width: 10%; text-align: right;">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bookings as $row): ?>
                        <tr>
                            <td>#<?= $row['id'] ?></td>

                            <td>
                                <div style="font-weight:600"><?= htmlspecialchars($row['username']) ?></div>
                                <div style="font-size:12px; color:#94a3b8"><?= htmlspecialchars($row['email']) ?></div>
                            </td>

                            <td><?= htmlspecialchars($row['service_name']) ?></td>

                            <td>
                                <div><?= date('d/m/Y', strtotime($row['start_time'])) ?></div>
                                <div style="font-size:12px; color:#64748b">
                                    <?= date('H:i', strtotime($row['start_time'])) ?> - <?= date('H:i', strtotime($row['end_time'])) ?>
                                </div>
                            </td>

                            <td>
                                <span class="badge status-<?= $row['status'] ?>">
                                    <?= ucfirst($row['status']) ?>
                                </span>
                            </td>

                            <td style="font-weight:bold; color:#059669; font-size: 15px;">
                                <?= number_format($row['total_price'], 0, ',', '.') ?>đ
                            </td>

                            <td class="text-center" style="text-align:center;">
                                <?php if ($row['payment_status'] == 1): ?>
                                    
                                    <div style="
                                        display: inline-flex;
                                        align-items: center;
                                        gap: 6px;
                                        background-color: #ecfdf5; 
                                        color: #059669;           
                                        padding: 6px 14px;
                                        border-radius: 30px;
                                        font-size: 12px;
                                        font-weight: 700;
                                        border: 1px solid #a7f3d0;
                                        box-shadow: 0 1px 2px rgba(0,0,0,0.05);
                                    ">
                                        <i class="bi bi-check-circle-fill" style="font-size: 14px;"></i>
                                        ĐÃ THANH TOÁN
                                    </div>

                                <?php else: ?>
                                    
                                    <?php if ($row['status'] == 'confirmed'): ?>
                                        <a href="mark_paid.php?id=<?= $row['id'] ?>" 
                                           onclick="return confirm('Xác nhận khách đã đóng tiền?');"
                                           style="
                                                display: inline-flex;
                                                align-items: center;
                                                gap: 5px;
                                                background: linear-gradient(45deg, #f59e0b, #d97706);
                                                color: white;
                                                padding: 6px 15px;
                                                border-radius: 50px;
                                                text-decoration: none;
                                                font-size: 13px;
                                                font-weight: 600;
                                                box-shadow: 0 2px 5px rgba(245, 158, 11, 0.3);
                                                transition: transform 0.2s;
                                           "
                                           onmouseover="this.style.transform='translateY(-2px)'"
                                           onmouseout="this.style.transform='translateY(0)'"
                                        >
                                           <i class="bi bi-cash-stack"></i> THU TIỀN
                                        </a>

                                    <?php elseif ($row['status'] == 'pending'): ?>
                                        <span style="font-size: 12px; color: #94a3b8; font-style: italic;">
                                            (Chờ duyệt đơn)
                                        </span>
                                    <?php else: ?>
                                        <span style="color: #cbd5e1">-</span>
                                    <?php endif; ?>

                                <?php endif; ?>
                            </td>

                            <td style="text-align: right;">
                                <?php if ($row['status'] === 'pending'): ?>
                                    <div style="display:flex; gap:5px; justify-content:flex-end;">
                                        <form method="POST" class="action-form">
                                            <input type="hidden" name="booking_id" value="<?= $row['id'] ?>">
                                            <input type="hidden" name="action" value="confirm">
                                            <button type="submit" class="btn-action btn-confirm" title="Duyệt">✔</button>
                                        </form>
                                        <form method="POST" class="action-form" onsubmit="return confirm('Chắc chắn hủy đơn này?');">
                                            <input type="hidden" name="booking_id" value="<?= $row['id'] ?>">
                                            <input type="hidden" name="action" value="cancel">
                                            <button type="submit" class="btn-action btn-cancel" title="Hủy">✕</button>
                                        </form>
                                    </div>
                                <?php else: ?>
                                    <span style="color:#cbd5e1; font-size:20px;">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div style="padding:50px; text-align:center; color:#94a3b8;">
                    Chưa có đơn đặt lịch nào.
                </div>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>