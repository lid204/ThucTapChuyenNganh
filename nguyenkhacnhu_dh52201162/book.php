<?php
require 'config.php';

// BẢO VỆ: Nếu chưa đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$role = $_SESSION['role'];

$message = '';
$service_id = $_GET['service_id'] ?? null;
$selected_date = $_POST['booking_date'] ?? $_GET['booking_date'] ?? date('Y-m-d'); 

// 1. Lấy thông tin Dịch vụ
if (empty($service_id) || !is_numeric($service_id)) die("Lỗi: Không tìm thấy ID dịch vụ.");

try {
    // Lấy thêm trường 'price'
    $stmt = $pdo->prepare("SELECT id, name, duration_minutes, price FROM services WHERE id = ? AND is_active = 1");
    $stmt->execute([$service_id]);
    $service = $stmt->fetch();

    if (!$service) die("Lỗi: Dịch vụ không hợp lệ hoặc không hoạt động.");
} catch (PDOException $e) {
    die("Lỗi truy vấn dịch vụ: " . $e->getMessage());
}

$base_duration = $service['duration_minutes']; 
$unit_price = $service['price']; // Giá tiền 1 slot (1 tiếng)

/**
 * Hàm tính toán các slot
 */
function getAvailableSlots($pdo, $service_id, $selected_date, $duration) {
    $start_hour = 8;
    $end_hour = 21; 
    $date_obj = new DateTime($selected_date);
    $now = new DateTime(); 
    $interval = new DateInterval("PT{$duration}M"); 
    
    $stmt = $pdo->prepare("
        SELECT start_time, end_time FROM bookings 
        WHERE service_id = ? AND DATE(start_time) = ? AND status IN ('pending', 'confirmed')
    ");
    $stmt->execute([$service_id, $selected_date]);
    $booked_slots = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $all_slots = [];
    $current_slot_start = clone $date_obj;
    $current_slot_start->setTime($start_hour, 0);
    $work_end_time = clone $date_obj;
    $work_end_time->setTime($end_hour, 0);

    $index = 0;
    while ($current_slot_start < $work_end_time) {
        $current_slot_end = clone $current_slot_start;
        $current_slot_end->add($interval);
        if ($current_slot_end > $work_end_time) break; 

        $slot_status = 'available'; 
        if ($current_slot_end <= $now) $slot_status = 'past';

        if ($slot_status === 'available') {
            foreach ($booked_slots as $booked) {
                $booked_start = new DateTime($booked['start_time']);
                $booked_end = new DateTime($booked['end_time']);
                if ($current_slot_start < $booked_end && $current_slot_end > $booked_start) {
                    $slot_status = 'booked';
                    break;
                }
            }
        }
        
        $all_slots[] = [
            'index' => $index++,
            'start' => $current_slot_start->format('H:i'),
            'end' => $current_slot_end->format('H:i'),
            'status' => $slot_status,
            'is_available' => ($slot_status === 'available'),
        ];
        $current_slot_start->add($interval);
    }
    return $all_slots;
}

// 2. Xử lý Đặt Lịch (POST) - CÓ TÍNH TIỀN
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_time'])) {
    $booking_date = $_POST['booking_date'];
    $start_time_str = $_POST['book_time']; 
    
    // Lấy hệ số nhân (1 tiếng, 2 tiếng...)
    $multiplier = isset($_POST['duration_multiplier']) ? intval($_POST['duration_multiplier']) : 1;
    $total_minutes = $base_duration * $multiplier; 

    // --- TÍNH TỔNG TIỀN ---
    $total_price = $unit_price * $multiplier;

    $start_datetime = new DateTime("{$booking_date} {$start_time_str}:00");
    $end_datetime = clone $start_datetime;
    $end_datetime->add(new DateInterval("PT{$total_minutes}M"));

    $start_db = $start_datetime->format('Y-m-d H:i:s');
    $end_db = $end_datetime->format('Y-m-d H:i:s');
    
    if ($start_datetime < new DateTime()) {
        $message = "Không thể đặt lịch cho thời điểm trong quá khứ.";
    } else {
        // Kiểm tra trùng lịch
        $stmt = $pdo->prepare("SELECT id FROM bookings WHERE service_id = ? AND status IN ('pending', 'confirmed') AND (start_time < ? AND end_time > ?)");
        $stmt->execute([$service_id, $end_db, $start_db]); 
        
        if ($stmt->fetch()) {
            $message = "Xin lỗi, khung giờ này vừa bị vướng lịch người khác rồi.";
        } else {
            try {
                // INSERT CÓ CỘT TOTAL_PRICE
                $sql = "INSERT INTO bookings (user_id, service_id, start_time, end_time, total_price, status) VALUES (?, ?, ?, ?, ?, 'pending')";
                $stmt = $pdo->prepare($sql);
                if ($stmt->execute([$user_id, $service_id, $start_db, $end_db, $total_price])) {
                    header("Location: index.php?booking_status=success"); 
                    exit;
                } else {
                    $message = "Đặt lịch thất bại.";
                }
            } catch (PDOException $e) {
                 $message = "Lỗi hệ thống: " . $e->getMessage();
            }
        }
    }
}

$available_slots = getAvailableSlots($pdo, $service_id, $selected_date, $base_duration);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đặt Lịch: <?= htmlspecialchars($service['name']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        /* CSS giữ nguyên như cũ */
        body { font-family: 'Inter', sans-serif; background-color: #F8FAFC; color: #0F172A; margin:0; padding:0; }
        .booking-container { max-width: 900px; margin: 40px auto; background: white; padding: 40px; border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
        .booking-header { border-bottom: 2px solid #f1f5f9; padding-bottom: 20px; margin-bottom: 30px; }
        .booking-header h1 { font-size: 28px; color: #1e293b; margin-bottom: 10px; }
        .booking-info { color: #64748b; font-size: 15px; }
        .controls-row { display: flex; gap: 20px; flex-wrap: wrap; margin-bottom: 30px; align-items: flex-end;}
        .control-group { flex: 1; min-width: 200px; }
        .control-group label { display: block; font-weight: 600; color: #334155; margin-bottom: 8px; }
        .control-group select, .control-group input { width: 100%; padding: 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 15px; box-sizing: border-box; }
        .slot-container { display: flex; flex-wrap: wrap; gap: 12px; margin-top: 20px; }
        .slot-button { display: inline-block; padding: 12px 20px; border-radius: 8px; text-align: center; text-decoration: none; transition: all 0.2s; font-weight: 600; font-size: 14px; border: 1px solid transparent; min-width: 130px; }
        .slot-button.available { background-color: #d1fae5; color: #065f46; border-color: #a7f3d0; cursor: pointer; }
        .slot-button.available:hover { background-color: #10b981; color: white; border-color: #10b981; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2); }
        .slot-button.disabled-slot { background-color: #f1f5f9; color: #cbd5e1; border-color: #e2e8f0; cursor: not-allowed; opacity: 0.7; text-decoration: line-through; pointer-events: none; }
        .slot-button.unavailable { background-color: #f1f5f9; color: #94a3b8; border-color: #e2e8f0; cursor: not-allowed; opacity: 0.8; pointer-events: none; }
        .selected-slot { background-color: #3B82F6 !important; color: white !important; border-color: #3B82F6 !important; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.3); }
        .error-msg { background: #fee2e2; color: #991b1b; padding: 12px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #fca5a5; }
        #submitButton { width: 100%; padding: 16px; font-size: 18px; margin-top: 30px; background: #3B82F6; color: white; border: none; border-radius: 10px; font-weight: bold; cursor: pointer; transition: 0.3s; }
        #submitButton:disabled { background: #cbd5e1; cursor: not-allowed; }
        #submitButton:not(:disabled):hover { background: #2563eb; }
        .main-header { padding: 15px 40px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #eee; background: white;}
        .brand-logo { font-size: 20px; font-weight: 800; color: #3B82F6; text-decoration: none; }
        .user-nav a { margin-left: 15px; text-decoration: none; color: #64748b; font-weight: 600; }
    </style>
</head>
<body>

    <header class="main-header">
        <a href="index.php" class="brand-logo">Hệ thống<span>ĐặtLịch</span></a>
        <div class="user-nav">
            <span>Xin chào, <strong><?= htmlspecialchars($username) ?></strong>!</span>
            <a href="index.php">Trang chủ</a>
            <a href="logout.php" style="color:#EF4444;">Đăng xuất</a>
        </div>
    </header>
    
    <div class="booking-container">
        
        <div class="booking-header">
            <h1><?= htmlspecialchars($service['name']) ?></h1>
            <div class="booking-info">
                Đơn giá: <strong style="color: #059669; font-size: 18px;"><?= number_format($unit_price, 0, ',', '.') ?>đ</strong> / giờ
            </div>
        </div>

        <?php if ($message): ?>
            <div class="error-msg">⚠️ <?= $message ?></div>
        <?php endif; ?>

        <form method="GET" action="book.php" id="filterForm">
            <input type="hidden" name="service_id" value="<?= $service_id ?>">
            
            <div class="controls-row">
                <div class="control-group">
                    <label for="booking_date">📅 Chọn Ngày:</label>
                    <input type="date" id="booking_date" name="booking_date" 
                           value="<?= htmlspecialchars($selected_date) ?>" 
                           min="<?= date('Y-m-d') ?>" 
                           onchange="document.getElementById('filterForm').submit()">
                </div>

                <div class="control-group">
                    <label for="durationSelect">⏳ Bạn muốn đặt mấy tiếng?</label>
                    <select id="durationSelect" onchange="updateAvailableSlots()">
                        <option value="1">1 Tiếng</option>
                        <option value="2">2 Tiếng</option>
                        <option value="3">3 Tiếng</option>
                        <option value="4">4 Tiếng</option>
                        <option value="5">5 Tiếng</option>
                    </select>
                </div>
            </div>
        </form>
        
        <div>
            <h3 style="color: #334155; margin-bottom: 15px;">
                Giờ bắt đầu khả dụng (Ngày <?= date('d/m/Y', strtotime($selected_date)) ?>):
            </h3>

            <div class="slots-container" id="slotsWrapper">
                <?php if (!empty($available_slots)): ?>
                    <?php foreach ($available_slots as $index => $slot): ?>
                        <?php 
                            $is_available = $slot['is_available'];
                            $css_class = $is_available ? 'available' : 'unavailable';
                            $label = $slot['start'];
                            if ($slot['status'] === 'past') $label .= " (Qua)";
                            elseif ($slot['status'] === 'booked') $label .= " (Đầy)";
                        ?>
                        
                        <div class="slot-button <?= $css_class ?>" 
                             id="slot-<?= $index ?>"
                             data-index="<?= $index ?>"
                             data-start="<?= $slot['start'] ?>"
                             data-available="<?= $is_available ? '1' : '0' ?>"
                             onclick="selectSlot(this)">
                             <?= $label ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color: #64748b; font-style: italic;">Không có lịch trống.</p>
                <?php endif; ?>
            </div>
        </div>

        <form method="POST" id="bookingForm">
            <input type="hidden" name="service_id" value="<?= $service_id ?>">
            <input type="hidden" name="booking_date" value="<?= htmlspecialchars($selected_date) ?>">
            
            <input type="hidden" id="book_time" name="book_time" required> 
            <input type="hidden" id="duration_multiplier" name="duration_multiplier" value="1">
            
            <button type="submit" id="submitButton" disabled>Vui lòng chọn giờ bắt đầu</button>
        </form>
    </div>

    <script>
        const slotsData = <?= json_encode($available_slots) ?>;
        // Đưa giá tiền từ PHP sang JS
        const unitPrice = <?= $unit_price ?>; 

        function updateAvailableSlots() {
            const multiplier = parseInt(document.getElementById('durationSelect').value);
            document.getElementById('duration_multiplier').value = multiplier;

            document.querySelectorAll('.slot-button').forEach(el => el.classList.remove('selected-slot'));
            document.getElementById('submitButton').disabled = true;
            document.getElementById('submitButton').innerText = "Vui lòng chọn giờ bắt đầu";

            slotsData.forEach((slot, index) => {
                const el = document.getElementById(`slot-${index}`);
                if (!el) return;

                if (slot.is_available) {
                    el.classList.remove('disabled-slot', 'unavailable');
                    el.classList.add('available');
                    el.style.pointerEvents = 'auto';
                }

                let isConsecutiveFree = true;
                for (let i = 0; i < multiplier; i++) {
                    if (!slotsData[index + i] || !slotsData[index + i].is_available) {
                        isConsecutiveFree = false;
                        break;
                    }
                }

                if (!isConsecutiveFree && slot.is_available) {
                    el.classList.remove('available');
                    el.classList.add('disabled-slot'); 
                    el.style.pointerEvents = 'none'; 
                    el.title = "Không đủ thời gian liên tiếp";
                }
            });
        }

        function selectSlot(el) {
            if (el.classList.contains('disabled-slot') || el.classList.contains('unavailable')) return;

            document.querySelectorAll('.slot-button').forEach(div => div.classList.remove('selected-slot'));
            el.classList.add('selected-slot');

            const startTime = el.getAttribute('data-start');
            const multiplier = parseInt(document.getElementById('durationSelect').value);

            // --- TÍNH TOÁN GIÁ TIỀN ---
            const totalPrice = unitPrice * multiplier;
            // Định dạng tiền: 200000 -> 200.000
            const formattedPrice = totalPrice.toLocaleString('vi-VN');

            document.getElementById('book_time').value = startTime;
            
            const btn = document.getElementById('submitButton');
            btn.disabled = false;
            // Hiển thị giá tiền trên nút
            btn.innerText = `Xác nhận: ${startTime} (${multiplier} tiếng) - TỔNG: ${formattedPrice}đ`;
        }

        updateAvailableSlots();
    </script>
</body>
</html>