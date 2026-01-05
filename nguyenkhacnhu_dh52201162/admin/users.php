<?php
require '../config.php';

// BẢO VỆ
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$message = '';
$edit_mode = false;
$curr_user = [];

// 1. LẤY DỮ LIỆU SỬA
if (isset($_GET['edit'])) {
    $id = $_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $curr_user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($curr_user) $edit_mode = true;
}

// 2. XỬ LÝ LƯU (THÊM / SỬA)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_user'])) {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $role = $_POST['role'];
    $password = $_POST['password']; // Chỉ nhập nếu muốn đổi pass hoặc tạo mới
    $u_id = $_POST['user_id'] ?? null;

    try {
        if ($u_id) {
            // UPDATE
            if (!empty($password)) {
                // Nếu có nhập pass mới -> Đổi cả pass
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET username=?, email=?, role=?, password=? WHERE id=?");
                $stmt->execute([$username, $email, $role, $hashed, $u_id]);
            } else {
                // Không đổi pass
                $stmt = $pdo->prepare("UPDATE users SET username=?, email=?, role=? WHERE id=?");
                $stmt->execute([$username, $email, $role, $u_id]);
            }
            $message = "✅ Cập nhật thông tin thành công!";
            $edit_mode = false; $curr_user = [];
        } else {
            // INSERT (Thêm mới)
            if (empty($password)) {
                $message = "❌ Vui lòng nhập mật khẩu cho tài khoản mới.";
            } else {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
                $stmt->execute([$username, $email, $hashed, $role]);
                $message = "✅ Thêm thành viên mới thành công!";
            }
        }
    } catch (PDOException $e) {
        $message = "❌ Lỗi: " . $e->getMessage();
    }
}

// 3. XỬ LÝ XÓA
if (isset($_GET['del'])) {
    $id = $_GET['del'];
    if ($id == $_SESSION['user_id']) {
        $message = "⚠️ Không thể xóa chính mình!";
    } else {
        $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);
        $message = "✅ Đã xóa tài khoản thành công!";
    }
}

$users = $pdo->query("SELECT * FROM users ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản Lý Tài Khoản</title>
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

        .container { max-width: 1000px; margin: 40px auto; padding: 0 20px; }
        .page-title { font-size: 24px; font-weight: 700; color: #1e293b; margin-bottom: 20px; }

        .card { background: white; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; padding: 24px; margin-bottom: 30px; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .full-width { grid-column: span 2; }
        label { display: block; margin-bottom: 6px; font-weight: 600; color: #374151; font-size: 13px; }
        input, select { width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; }
        input:focus { border-color: #3B82F6; outline: none; }

        .btn-submit { background: #3B82F6; color: white; border: none; padding: 10px 20px; border-radius: 6px; font-weight: 600; cursor: pointer; width: 100%; margin-top: 20px; }
        .btn-cancel { background: #64748b; color: white; text-decoration:none; display:inline-block; text-align:center; padding: 10px 20px; border-radius: 6px; font-weight: 600; width: 100%; margin-top: 20px;}

        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 12px; border-bottom: 2px solid #e5e7eb; color: #64748b; font-size: 12px; text-transform: uppercase; }
        td { padding: 14px 12px; border-bottom: 1px solid #f1f5f9; font-size: 14px; vertical-align: middle; }
        
        .btn-action { font-weight: 600; text-decoration: none; font-size: 12px; padding: 4px 10px; border-radius: 4px; display: inline-block; margin-right: 5px; }
        .btn-edit { color: #d97706; background: #fffbeb; }
        .btn-del { color: #ef4444; background: #fef2f2; }
        
        .alert { padding: 12px; border-radius: 8px; margin-bottom: 20px; background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
        .alert.error { background: #fee2e2; color: #991b1b; border-color: #fca5a5; }
        .role-badge { padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
        .role-admin { background: #fee2e2; color: #dc2626; }
        .role-user { background: #dbeafe; color: #2563eb; }
    </style>
</head>
<body>
    <header class="admin-header">
        <a href="../index.php" class="brand">Hệ thống <span>Admin</span></a>
        <div class="nav-actions">
            <a href="index.php" class="nav-btn">Đặt Lịch</a>
            <a href="categories.php" class="nav-btn">Danh Mục</a>
            <a href="services.php" class="nav-btn">Dịch Vụ</a>
            <a href="users.php" class="nav-btn active">Tài Khoản</a>
            <a href="../logout.php" class="nav-btn btn-logout">Thoát</a>
        </div>
    </header>

    <div class="container">
        <div class="page-title">Quản Lý Tài Khoản</div>
        <?php if ($message): ?>
            <div class="alert <?= strpos($message, 'Lỗi') !== false || strpos($message, 'Không thể') !== false ? 'error' : '' ?>"><?= $message ?></div>
        <?php endif; ?>

        <div class="card">
            <h3 style="margin-bottom: 20px; color:#3B82F6;">
                <?= $edit_mode ? '✏️ Cập nhật Thành viên' : '➕ Thêm Thành viên Mới' ?>
            </h3>
            <form method="POST">
                <?php if($edit_mode): ?>
                    <input type="hidden" name="user_id" value="<?= $curr_user['id'] ?>">
                <?php endif; ?>

                <div class="form-grid">
                    <div>
                        <label>Tên đăng nhập</label>
                        <input type="text" name="username" required 
                               value="<?= $edit_mode ? htmlspecialchars($curr_user['username']) : '' ?>">
                    </div>
                    <div>
                        <label>Email</label>
                        <input type="email" name="email" required 
                               value="<?= $edit_mode ? htmlspecialchars($curr_user['email']) : '' ?>">
                    </div>
                    <div>
                        <label>Mật khẩu <?= $edit_mode ? '(Để trống nếu không đổi)' : '(*)' ?></label>
                        <input type="password" name="password" <?= $edit_mode ? '' : 'required' ?>>
                    </div>
                    <div>
                        <label>Vai trò</label>
                        <select name="role">
                            <option value="user" <?= ($edit_mode && $curr_user['role'] == 'user') ? 'selected' : '' ?>>User</option>
                            <option value="admin" <?= ($edit_mode && $curr_user['role'] == 'admin') ? 'selected' : '' ?>>Admin</option>
                        </select>
                    </div>
                </div>
                
                <button type="submit" name="save_user" class="btn-submit">
                    <?= $edit_mode ? 'Lưu Thay Đổi' : '+ Thêm Mới' ?>
                </button>
                <?php if($edit_mode): ?>
                    <a href="users.php" class="btn-cancel">Hủy Bỏ</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="card" style="padding: 0;">
            <table cellspacing="0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tên đăng nhập</th>
                        <th>Email</th>
                        <th>Vai trò</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                    <tr style="<?= ($edit_mode && $u['id'] == $curr_user['id']) ? 'background:#fff7ed' : '' ?>">
                        <td>#<?= $u['id'] ?></td>
                        <td style="font-weight: 600;"><?= htmlspecialchars($u['username']) ?></td>
                        <td><?= htmlspecialchars($u['email']) ?></td>
                        <td>
                            <span class="role-badge role-<?= $u['role'] ?>"><?= strtoupper($u['role']) ?></span>
                        </td>
                        <td>
                            <a href="?edit=<?= $u['id'] ?>" class="btn-action btn-edit">Sửa</a>
                            <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                <a href="?del=<?= $u['id'] ?>" class="btn-action btn-del" onclick="return confirm('Xóa người dùng này?')">Xóa</a>
                            <?php else: ?>
                                <span style="font-size:12px; color:#ccc;">(Tôi)</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>