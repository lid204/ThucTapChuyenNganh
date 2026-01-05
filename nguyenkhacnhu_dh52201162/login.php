<?php
require 'config.php';

// Chuyển hướng nếu người dùng đã đăng nhập
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username_or_email = trim($_POST['username'] ?? ''); 
    $password = $_POST['password'] ?? '';

    if (empty($username_or_email) || empty($password)) {
        $message = "Vui lòng điền đầy đủ thông tin.";
    } else {
        try {
            // Logic tìm kiếm bằng username HOẶC email
            $stmt = $pdo->prepare("SELECT id, username, password, role FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username_or_email, $username_or_email]); 
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // Đăng nhập thành công
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                
                header('Location: index.php');
                exit;
            } else {
                $message = "Tên đăng nhập hoặc mật khẩu không đúng.";
            }
        } catch (PDOException $e) {
            $message = "Lỗi hệ thống: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng Nhập | Hệ Thống Đặt Lịch</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        /* CSS Riêng cho trang Login để căn giữa màn hình */
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-color: #f0f2f5;
            background-image: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        }

        .login-card {
            background: white;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }

        .login-header h2 {
            color: #3B82F6;
            font-size: 28px;
            margin-bottom: 10px;
            border: none; /* Bỏ gạch chân mặc định của h2 trong style.css */
            padding: 0;
        }

        .login-header p {
            color: #64748b;
            margin-bottom: 30px;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #334155;
            font-size: 14px;
        }

        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 15px;
            transition: border-color 0.3s;
        }

        input[type="text"]:focus, input[type="password"]:focus {
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
        }

        .btn-submit:hover {
            background-color: #2563eb;
        }

        .alert-error {
            background-color: #fee2e2;
            color: #991b1b;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            border: 1px solid #fca5a5;
        }

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

    <div class="login-card">
        <div class="login-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <div style="text-align: left;">
                <h2 style="margin: 0;">Đăng Nhập</h2>
                <p style="margin: 5px 0 0 0;">Chào mừng bạn quay trở lại</p>
             </div>
             <img src="img/logo.jpg" alt="Logo" style="height: 60px; width: auto; border-radius: 50%;">
        </div>

        <?php if ($message): ?>
            <div class="alert-error">
                ⚠️ <?= $message ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="username">Tên đăng nhập hoặc Email</label>
                <input type="text" id="username" name="username" placeholder="Nhập username/email..." required> 
            </div>
            
            <div class="form-group">
                <label for="password">Mật khẩu</label>
                <input type="password" id="password" name="password" placeholder="Nhập mật khẩu..." required>
            </div>
            
            <button type="submit" class="btn-submit">Đăng Nhập Ngay</button>
        </form>

        <div class="footer-links">
            <p>Chưa có tài khoản? <a href="register.php">Đăng ký mới</a></p>
            <p style="margin-top: 10px;">
                <a href="index.php" style="color: #64748b;">← Quay lại Trang Chủ</a>
            </p>
        </div>
    </div>

</body>
</html>