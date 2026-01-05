<?php
require 'config.php';

$message = '';
$msg_type = ''; // Biến để xác định màu thông báo (xanh hay đỏ)

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // 1. Kiểm tra đầu vào
    if (empty($username) || empty($password) || empty($confirm_password) || empty($email)) {
        $message = "Vui lòng điền đầy đủ tất cả các trường.";
        $msg_type = 'error';
    } elseif ($password !== $confirm_password) {
        $message = "Mật khẩu xác nhận không khớp.";
        $msg_type = 'error';
    } elseif (strlen($password) < 6) {
        $message = "Mật khẩu phải có ít nhất 6 ký tự.";
        $msg_type = 'error';
    } else {
        try {
            // 2. Kiểm tra trùng lặp
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            
            if ($stmt->fetch()) {
                $message = "Tên đăng nhập hoặc Email này đã tồn tại.";
                $msg_type = 'error';
            } else {
                // 3. Đăng ký
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $role = 'user'; 

                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
                if ($stmt->execute([$username, $email, $hashed_password, $role])) {
                    $message = "Đăng ký thành công! Bạn có thể <a href='login.php'>Đăng nhập</a> ngay.";
                    $msg_type = 'success';
                } else {
                    $message = "Đã xảy ra lỗi khi đăng ký. Vui lòng thử lại.";
                    $msg_type = 'error';
                }
            }
        } catch (PDOException $e) {
            $message = "Lỗi hệ thống: " . $e->getMessage();
            $msg_type = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng Ký | Hệ Thống Đặt Lịch</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        /* CSS Riêng cho trang Đăng Ký (Đồng bộ với Login) */
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-color: #f0f2f5;
            background-image: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            font-family: 'Inter', sans-serif;
        }

        .register-card {
            background: white;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 450px; /* Rộng hơn login xíu vì nhiều trường hơn */
            text-align: center;
        }

        .register-header h2 {
            color: #3B82F6;
            font-size: 28px;
            margin-bottom: 10px;
            border: none;
            padding: 0;
        }

        .register-header p {
            color: #64748b;
            margin-bottom: 25px;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 15px;
            text-align: left;
        }

        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            color: #334155;
            font-size: 13px;
        }

        input[type="text"], input[type="password"], input[type="email"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
            box-sizing: border-box;
        }

        input:focus {
            border-color: #3B82F6;
            outline: none;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .btn-submit {
            width: 100%;
            padding: 12px;
            background-color: #3B82F6;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s;
            margin-top: 10px;
        }

        .btn-submit:hover {
            background-color: #2563eb;
        }

        /* Thông báo lỗi/thành công */
        .alert {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            text-align: left;
        }
        .alert.error {
            background-color: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }
        .alert.success {
            background-color: #d1fae5;
            color: #065f46;
            border: 1px solid #6ee7b7;
        }
        .alert a { font-weight: bold; color: inherit; text-decoration: underline; }

        .footer-links {
            margin-top: 25px;
            font-size: 14px;
            color: #64748b;
        }

        .footer-links a {
            color: #3B82F6;
            text-decoration: none;
            font-weight: 600;
        }

        .footer-links a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

    <div class="register-card">
        <div class="register-header">
            <h2>Tạo Tài Khoản</h2>
            <p>Đăng ký thành viên để đặt lịch ngay</p>
        </div>

        <?php if ($message): ?>
            <div class="alert <?= $msg_type ?>">
                <?= $message ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="username">Tên đăng nhập</label>
                <input type="text" id="username" name="username" placeholder="Ví dụ: nguyenvanA" required>
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" placeholder="email@example.com" required>
            </div>
            
            <div class="form-group">
                <label for="password">Mật khẩu (tối thiểu 6 ký tự)</label>
                <input type="password" id="password" name="password" placeholder="Nhập mật khẩu..." required>
            </div>

            <div class="form-group">
                <label for="confirm_password">Xác nhận mật khẩu</label>
                <input type="password" id="confirm_password" name="confirm_password" placeholder="Nhập lại mật khẩu..." required>
            </div>
            
            <button type="submit" class="btn-submit">Đăng Ký Ngay</button>
        </form>

        <div class="footer-links">
            <p>Đã có tài khoản? <a href="login.php">Đăng nhập</a></p>
            <p style="margin-top: 10px;">
                <a href="index.php" style="color: #64748b;">← Về Trang Chủ</a>
            </p>
        </div>
    </div>

</body>
</html>