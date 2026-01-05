<?php
require '../config.php';

// BẢO VỆ
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$message = '';
$edit_mode = false;
$curr_s = []; // Dữ liệu dịch vụ đang sửa

// 1. XỬ LÝ LẤY DỮ LIỆU SỬA (Khi bấm nút Sửa)
if (isset($_GET['edit'])) {
    $id = $_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM services WHERE id = ?");
    $stmt->execute([$id]);
    $curr_s = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($curr_s) {
        $edit_mode = true;
    }
}

// 2. XỬ LÝ LƯU (THÊM MỚI HOẶC CẬP NHẬT)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_service'])) {
    $name = $_POST['name'];
    $cat_id = $_POST['category_id'];
    $room_id = !empty($_POST['room_type_id']) ? $_POST['room_type_id'] : NULL;
    $price = $_POST['price'];
    $desc = $_POST['description'];
    $min_h = $_POST['min_hours'];
    $max_h = $_POST['max_hours'];
    
    // Xử lý upload ảnh
    $img = $_POST['current_image'] ?? ''; // Giữ ảnh cũ nếu không upload mới
    
    if (isset($_FILES['image_upload']) && $_FILES['image_upload']['error'] == 0) {
        $target_dir = "../img/"; // Thư mục lưu ảnh (đảm bảo thư mục này tồn tại và có quyền ghi)
        // Tạo tên file mới để tránh trùng lặp
        $file_extension = pathinfo($_FILES["image_upload"]["name"], PATHINFO_EXTENSION);
        $new_filename = uniqid() . '.' . $file_extension;
        $target_file = $target_dir . $new_filename;
        
        // Kiểm tra loại file ảnh
        $check = getimagesize($_FILES["image_upload"]["tmp_name"]);
        if($check !== false) {
             if (move_uploaded_file($_FILES["image_upload"]["tmp_name"], $target_file)) {
                $img = $new_filename; // Cập nhật tên ảnh mới vào biến $img
            } else {
                $message = "❌ Lỗi: Không thể tải ảnh lên.";
            }
        } else {
             $message = "❌ Lỗi: File tải lên không phải là hình ảnh.";
        }
    }

    // Lấy ID nếu đang sửa
    $service_id = $_POST['service_id'] ?? null;

    try {
        if ($service_id) {
            // --- LOGIC CẬP NHẬT (UPDATE) ---
            $sql = "UPDATE services SET category_id=?, room_type_id=?, name=?, description=?, price=?, min_hours=?, max_hours=?, image=? WHERE id=?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$cat_id, $room_id, $name, $desc, $price, $min_h, $max_h, $img, $service_id]);
            $message = "✅ Đã cập nhật dịch vụ thành công!";
            // Reset lại chế độ sau khi sửa xong
            $edit_mode = false; 
            $curr_s = [];
        } else {
            // --- LOGIC THÊM MỚI (INSERT) ---
            $sql = "INSERT INTO services (category_id, room_type_id, name, description, price, min_hours, max_hours, image) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$cat_id, $room_id, $name, $desc, $price, $min_h, $max_h, $img]);
            $message = "✅ Đã thêm dịch vụ mới thành công!";
        }
    } catch (PDOException $e) {
        $message = "❌ Lỗi: " . $e->getMessage();
    }
}

// 3. XỬ LÝ XÓA
if (isset($_GET['del'])) {
    $id = $_GET['del'];
    $pdo->prepare("UPDATE services SET is_active = 0 WHERE id = ?")->execute([$id]);
    header("Location: services.php");
    exit;
}

// 4. LẤY DANH SÁCH DỮ LIỆU
$categories = $pdo->query("SELECT * FROM categories")->fetchAll();
$room_types = $pdo->query("SELECT * FROM room_types")->fetchAll();
$services = $pdo->query("SELECT s.*, c.name as cat_name FROM services s JOIN categories c ON s.category_id = c.id WHERE s.is_active = 1 ORDER BY s.id DESC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản Lý Dịch Vụ</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* GIỮ NGUYÊN CSS CŨ */
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
        
        /* FORM CARD */
        .card { background: white; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; padding: 24px; margin-bottom: 30px; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .full-width { grid-column: span 2; }
        label { display: block; margin-bottom: 6px; font-weight: 600; color: #374151; font-size: 13px; }
        input, select, textarea { width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; transition: 0.2s; font-family: inherit; }
        input:focus, select:focus { border-color: #3B82F6; outline: none; }
        
        .btn-submit { background: #3B82F6; color: white; border: none; padding: 12px 20px; border-radius: 6px; font-weight: 600; cursor: pointer; width: 100%; margin-top: 20px; }
        .btn-submit:hover { background: #2563eb; }
        .btn-cancel { background: #64748b; color: white; text-decoration:none; display:inline-block; text-align:center; padding: 12px 20px; border-radius: 6px; font-weight: 600; width: 100%; margin-top: 10px;}

        /* TABLE */
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 12px; border-bottom: 2px solid #e5e7eb; color: #64748b; font-size: 12px; text-transform: uppercase; }
        td { padding: 14px 12px; border-bottom: 1px solid #f1f5f9; font-size: 14px; vertical-align: middle; }
        
        .btn-action { font-weight: 600; text-decoration: none; font-size: 12px; padding: 4px 10px; border-radius: 4px; display: inline-block; margin-right: 5px; }
        .btn-edit { color: #d97706; background: #fffbeb; }
        .btn-edit:hover { background: #fcd34d; }
        .btn-del { color: #ef4444; background: #fef2f2; }
        .btn-del:hover { background: #fee2e2; }
        
        .cat-badge { background: #eff6ff; color: #2563eb; padding: 2px 8px; border-radius: 4px; font-size: 12px; font-weight: 600; }
        .alert { padding: 12px; border-radius: 8px; margin-bottom: 20px; background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
        
        /* Image Preview */
        .img-preview { max-width: 100px; max-height: 100px; margin-top: 10px; border-radius: 6px; border: 1px solid #e2e8f0; display: block;}
    </style>
</head>
<body>
    <header class="admin-header">
        <a href="../index.php" class="brand">Hệ thống <span>Admin</span></a>
        <div class="nav-actions">
            <a href="index.php" class="nav-btn">Đặt Lịch</a>
            <a href="categories.php" class="nav-btn">Danh Mục</a>
            <a href="services.php" class="nav-btn active">Dịch Vụ</a>
            <a href="users.php" class="nav-btn">Tài Khoản</a>
            <a href="../logout.php" class="nav-btn btn-logout">Thoát</a>
        </div>
    </header>

    <div class="container">
        <div class="page-title">Quản Lý Dịch Vụ</div>
        
        <?php if ($message): ?>
            <div class="alert <?= strpos($message, 'Lỗi') !== false ? 'error' : '' ?>"><?= $message ?></div>
        <?php endif; ?>

        <div class="card">
            <h3 style="margin-bottom: 20px; color:#3B82F6;">
                <?= $edit_mode ? '✏️ Cập nhật Dịch vụ' : '➕ Thêm Dịch vụ Mới' ?>
            </h3>
            
            <form method="POST" enctype="multipart/form-data"> <?php if($edit_mode): ?>
                    <input type="hidden" name="service_id" value="<?= $curr_s['id'] ?>">
                    <input type="hidden" name="current_image" value="<?= htmlspecialchars($curr_s['image']) ?>">
                <?php endif; ?>

                <div class="form-grid">
                    <div>
                        <label>Thuộc Danh Mục (*)</label>
                        <select name="category_id" required>
                            <option value="">-- Chọn --</option>
                            <?php foreach ($categories as $c): ?>
                                <option value="<?= $c['id'] ?>" <?= ($edit_mode && $curr_s['category_id'] == $c['id']) ? 'selected' : '' ?>>
                                    <?= $c['name'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label>Loại Hình</label>
                        <select name="room_type_id">
                            <option value="">-- Mặc định --</option>
                            <?php foreach ($room_types as $r): ?>
                                <option value="<?= $r['id'] ?>" <?= ($edit_mode && $curr_s['room_type_id'] == $r['id']) ? 'selected' : '' ?>>
                                    <?= $r['name'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="full-width">
                        <label>Tên Dịch Vụ (*)</label>
                        <input type="text" name="name" required placeholder="Ví dụ: Gym - Gói Cá Nhân" 
                               value="<?= $edit_mode ? htmlspecialchars($curr_s['name']) : '' ?>">
                    </div>
                    <div class="full-width">
                        <label>Mô tả ngắn</label>
                        <textarea name="description" rows="2"><?= $edit_mode ? htmlspecialchars($curr_s['description']) : '' ?></textarea>
                    </div>
                    <div>
                        <label>Giá tiền (VNĐ) (*)</label>
                        <input type="number" name="price" required 
                               value="<?= $edit_mode ? $curr_s['price'] : '' ?>">
                    </div>
                    <div>
                        <label>Ảnh đại diện</label>
                        <input type="file" name="image_upload" accept="image/*">
                        <?php if($edit_mode && !empty($curr_s['image'])): ?>
                            <div style="margin-top: 5px;">
                                <small>Ảnh hiện tại:</small><br>
                                <img src="../img/<?= htmlspecialchars($curr_s['image']) ?>" class="img-preview" alt="Current Image">
                            </div>
                        <?php endif; ?>
                    </div>
                    <div>
                        <label>Giờ tối thiểu</label>
                        <input type="number" name="min_hours" value="<?= $edit_mode ? $curr_s['min_hours'] : '1' ?>">
                    </div>
                    <div>
                        <label>Giờ tối đa</label>
                        <input type="number" name="max_hours" value="<?= $edit_mode ? $curr_s['max_hours'] : '24' ?>">
                    </div>
                </div>
                
                <button type="submit" name="save_service" class="btn-submit">
                    <?= $edit_mode ? 'Lưu Thay Đổi' : '+ Thêm Dịch Vụ Mới' ?>
                </button>
                
                <?php if($edit_mode): ?>
                    <a href="services.php" class="btn-cancel">Hủy Bỏ (Quay lại thêm mới)</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="card" style="padding: 0;">
            <table cellspacing="0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Hình Ảnh</th> <th>Tên Dịch Vụ</th>
                        <th>Danh Mục</th>
                        <th>Giá</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($services as $s): ?>
                    <tr style="<?= ($edit_mode && $s['id'] == $curr_s['id']) ? 'background:#fff7ed' : '' ?>">
                        <td>#<?= $s['id'] ?></td>
                        <td>
                            <?php if(!empty($s['image'])): ?>
                                <img src="../img/<?= htmlspecialchars($s['image']) ?>" alt="Service Image" style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;">
                            <?php else: ?>
                                <span style="color: #94a3b8; font-size: 12px;">No Image</span>
                            <?php endif; ?>
                        </td>
                        <td style="font-weight:600"><?= htmlspecialchars($s['name']) ?></td>
                        <td><span class="cat-badge"><?= htmlspecialchars($s['cat_name']) ?></span></td>
                        <td style="color:#059669; font-weight:600"><?= number_format($s['price']) ?>đ</td>
                        <td>
                            <a href="?edit=<?= $s['id'] ?>" class="btn-action btn-edit">Sửa</a>
                            
                            <a href="?del=<?= $s['id'] ?>" class="btn-action btn-del" onclick="return confirm('Xóa dịch vụ này?')">Xóa</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>