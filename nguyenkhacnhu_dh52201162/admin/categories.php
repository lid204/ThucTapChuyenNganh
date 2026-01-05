<?php
require '../config.php';

// BẢO VỆ
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$message = '';
$edit_mode = false;
$curr_cat = [];

// 1. LẤY DỮ LIỆU SỬA
if (isset($_GET['edit'])) {
    $id = $_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$id]);
    $curr_cat = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($curr_cat) $edit_mode = true;
}

// 2. XỬ LÝ LƯU (THÊM / SỬA)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_cat'])) {
    $name = $_POST['name'];
    $code = $_POST['code']; 
    $image = $_POST['image'] ?? ''; 
    $cat_id = $_POST['cat_id'] ?? null;

    if ($name && $code) {
        try {
            if ($cat_id) {
                // UPDATE
                $stmt = $pdo->prepare("UPDATE categories SET name=?, code=?, image=? WHERE id=?");
                $stmt->execute([$name, $code, $image, $cat_id]);
                $message = "✅ Đã cập nhật danh mục thành công!";
                $edit_mode = false; $curr_cat = [];
            } else {
                // INSERT
                $stmt = $pdo->prepare("INSERT INTO categories (name, code, image) VALUES (?, ?, ?)");
                $stmt->execute([$name, $code, $image]);
                $message = "✅ Đã thêm danh mục mới thành công!";
            }
        } catch (PDOException $e) {
            $message = "❌ Lỗi: " . $e->getMessage();
        }
    }
}

// 3. XỬ LÝ XÓA
if (isset($_GET['del'])) {
    $id = $_GET['del'];
    $pdo->prepare("DELETE FROM categories WHERE id = ?")->execute([$id]);
    header("Location: categories.php");
    exit;
}

$cats = $pdo->query("SELECT * FROM categories ORDER BY id")->fetchAll();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản Lý Danh Mục</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* CSS CHUẨN */
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; color: #0f172a; }
        .admin-header { background: white; padding: 0 40px; height: 70px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 1px 3px rgba(0,0,0,0.05); position: sticky; top: 0; z-index: 100; }
        .brand { font-size: 18px; font-weight: 800; color: #3B82F6; text-decoration: none; text-transform: uppercase; }
        .nav-actions { display: flex; gap: 8px; align-items: center; }
        .nav-btn { text-decoration: none; padding: 8px 16px; border-radius: 6px; font-size: 13px; font-weight: 600; color: #64748b; transition: 0.2s; }
        .nav-btn:hover { background: #f1f5f9; color: #0f172a; }
        .nav-btn.active { background: #eff6ff; color: #3B82F6; }
        .btn-logout { background: #fef2f2; color: #ef4444; margin-left: 10px; border: 1px solid #fecaca; }
        
        .container { max-width: 900px; margin: 40px auto; padding: 0 20px; }
        .page-title { font-size: 24px; font-weight: 700; color: #1e293b; margin-bottom: 20px; }

        .card { background: white; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; padding: 24px; margin-bottom: 30px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 6px; font-weight: 600; color: #374151; font-size: 13px; }
        input { width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; }
        input:focus { border-color: #3B82F6; outline: none; }
        
        .btn-submit { background: #3B82F6; color: white; border: none; padding: 10px 20px; border-radius: 6px; font-weight: 600; cursor: pointer; width: 100%; margin-top: 10px; }
        .btn-cancel { background: #64748b; color: white; text-decoration:none; display:inline-block; text-align:center; padding: 10px 20px; border-radius: 6px; font-weight: 600; width: 100%; margin-top: 10px;}

        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { text-align: left; padding: 12px; border-bottom: 2px solid #e5e7eb; color: #64748b; font-size: 12px; text-transform: uppercase; }
        td { padding: 14px 12px; border-bottom: 1px solid #f1f5f9; font-size: 14px; }
        
        .btn-action { font-weight: 600; text-decoration: none; font-size: 12px; padding: 4px 10px; border-radius: 4px; display: inline-block; margin-right: 5px; }
        .btn-edit { color: #d97706; background: #fffbeb; }
        .btn-del { color: #ef4444; background: #fef2f2; }
        
        .alert { padding: 12px; border-radius: 8px; margin-bottom: 20px; background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
        .alert.error { background: #fee2e2; color: #991b1b; border-color: #fca5a5; }
    </style>
</head>
<body>
    <header class="admin-header">
        <a href="../index.php" class="brand">Hệ thống <span>Admin</span></a>
        <div class="nav-actions">
            <a href="index.php" class="nav-btn">Đặt Lịch</a>
            <a href="categories.php" class="nav-btn active">Danh Mục</a>
            <a href="services.php" class="nav-btn">Dịch Vụ</a>
            <a href="users.php" class="nav-btn">Tài Khoản</a>
            <a href="../logout.php" class="nav-btn btn-logout">Thoát</a>
        </div>
    </header>

    <div class="container">
        <div class="page-title">Quản Lý Danh Mục</div>
        <?php if ($message): ?>
            <div class="alert <?= strpos($message, 'Lỗi') !== false ? 'error' : '' ?>"><?= $message ?></div>
        <?php endif; ?>

        <div class="card">
            <h3 style="margin-bottom: 20px; color:#3B82F6;">
                <?= $edit_mode ? '✏️ Cập nhật Danh Mục' : '➕ Thêm Danh Mục Mới' ?>
            </h3>
            <form method="POST">
                <?php if($edit_mode): ?>
                    <input type="hidden" name="cat_id" value="<?= $curr_cat['id'] ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label>Tên Hiển Thị</label>
                    <input type="text" name="name" required placeholder="Ví dụ: Khu Vui Chơi"
                           value="<?= $edit_mode ? htmlspecialchars($curr_cat['name']) : '' ?>">
                </div>
                <div class="form-group">
                    <label>Mã Code (viết liền, không dấu)</label>
                    <input type="text" name="code" required placeholder="game"
                           value="<?= $edit_mode ? htmlspecialchars($curr_cat['code']) : '' ?>">
                </div>
                <div class="form-group">
                    <label>Tên file ảnh (Tùy chọn)</label>
                    <input type="text" name="image" placeholder="cat_game.jpg"
                           value="<?= $edit_mode ? htmlspecialchars($curr_cat['image']) : '' ?>">
                </div>
                
                <button type="submit" name="save_cat" class="btn-submit">
                    <?= $edit_mode ? 'Lưu Thay Đổi' : '+ Thêm Mới' ?>
                </button>
                <?php if($edit_mode): ?>
                    <a href="categories.php" class="btn-cancel">Hủy Bỏ</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="card" style="padding: 0;">
            <table cellspacing="0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tên Danh Mục</th>
                        <th>Mã Code</th>
                        <th>Ảnh</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cats as $c): ?>
                    <tr style="<?= ($edit_mode && $c['id'] == $curr_cat['id']) ? 'background:#fff7ed' : '' ?>">
                        <td>#<?= $c['id'] ?></td>
                        <td style="font-weight: 600;"><?= htmlspecialchars($c['name']) ?></td>
                        <td><?= htmlspecialchars($c['code']) ?></td>
                        <td style="color:#94a3b8"><?= htmlspecialchars($c['image'] ?? '-') ?></td>
                        <td>
                            <a href="?edit=<?= $c['id'] ?>" class="btn-action btn-edit">Sửa</a>
                            <a href="?del=<?= $c['id'] ?>" class="btn-action btn-del" onclick="return confirm('Xóa danh mục này?')">Xóa</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>