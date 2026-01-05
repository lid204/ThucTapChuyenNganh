<?php
// Tắt báo lỗi rác
ini_set('display_errors', 0);
error_reporting(0); 

session_start();
header('Content-Type: application/json; charset=utf-8');

function sendResponse($status, $message, $extra = []) {
    echo json_encode(array_merge(['status' => $status, 'message' => $message], $extra));
    exit;
}

try {
    require 'config.php'; 

    $action = $_POST['action'] ?? '';

    // --- 1. LẤY DANH SÁCH DỊCH VỤ ---
    if ($action == 'get_services') {
        $stmt = $pdo->query("SELECT id, name FROM services WHERE is_active = 1");
        sendResponse('success', 'ok', ['data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }

    // --- 2. XỬ LÝ TIN NHẮN (LOGIC THÔNG MINH) ---
    if ($action == 'chat_message') {
        $msg = $_POST['message'] ?? '';
        $msg_lower = mb_strtolower($msg, 'UTF-8');

        // A. TÌM NGÀY
        $found_date = null;
        if (mb_strpos($msg_lower, 'hôm nay') !== false || mb_strpos($msg_lower, 'bữa nay') !== false) {
            $found_date = date('Y-m-d');
        } elseif (mb_strpos($msg_lower, 'ngày mai') !== false || mb_strpos($msg_lower, 'mai') !== false) {
            $found_date = date('Y-m-d', strtotime('+1 day'));
        } elseif (preg_match('/(\d{1,2})[\/\-](\d{1,2})([\/\-](\d{4}))?/', $msg, $matches)) {
            $time_str = str_replace('/', '-', $matches[0]);
            if (!isset($matches[4])) $time_str .= '-' . date('Y');
            $found_date = date('Y-m-d', strtotime($time_str));
        }

        // B. TÌM GIỜ (15h, 15:00...)
        $found_time = null;
        if (preg_match('/(\d{1,2})\s*(:|h|g|giờ)/', $msg_lower, $time_matches)) {
            $hour = intval($time_matches[1]);
            if($hour >= 0 && $hour <= 23) $found_time = sprintf("%02d:00:00", $hour);
        }

        // C. TÌM THỜI LƯỢNG (3 tiếng...)
        $duration = 1;
        if (preg_match('/(\d+)\s*(tiếng|h|giờ|hour)/', $msg_lower, $dur_matches)) {
            $val = intval($dur_matches[1]);
            if ($val > 0 && $val < 12) $duration = $val;
        }

        // D. TÌM DỊCH VỤ & SESSION
        $found_service_id = null; $found_service_name = '';
        $stmt = $pdo->query("SELECT id, name FROM services");
        $services = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($services as $svc) {
            $svc_name_lower = mb_strtolower($svc['name'], 'UTF-8');
            $keywords = explode(' ', $svc_name_lower); 
            $score = 0; $word_count = 0;
            foreach ($keywords as $word) {
                if (mb_strlen(trim($word)) > 1) {
                    $word_count++;
                    if (mb_strpos($msg_lower, trim($word)) !== false) $score++;
                }
            }
            if (mb_strpos($msg_lower, $svc_name_lower) !== false || ($word_count > 0 && $score/$word_count > 0.4)) {
                $found_service_id = $svc['id']; $found_service_name = $svc['name']; break;
            }
        }

        // Ưu tiên lấy từ Session nếu thiếu
        if ($found_service_id) { $_SESSION['chat_service_id'] = $found_service_id; $_SESSION['chat_service_name'] = $found_service_name; }
        else if (isset($_SESSION['chat_service_id'])) { $found_service_id = $_SESSION['chat_service_id']; $found_service_name = $_SESSION['chat_service_name']; }
        
        if ($found_date) $_SESSION['chat_date'] = $found_date;
        else if (isset($_SESSION['chat_date'])) $found_date = $_SESSION['chat_date'];

        // E. PHẢN HỒI
        if ($found_service_id && $found_date && $found_time) {
            $check_start = "$found_date $found_time";
            $check_end = date('Y-m-d H:i:s', strtotime($check_start) + ($duration * 3600));
            
            // Check trùng
            $sql = "SELECT id FROM bookings WHERE service_id = ? AND status != 'cancelled' AND (start_time < ? AND end_time > ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$found_service_id, $check_end, $check_start]);
            
            if ($stmt->rowCount() > 0) {
                sendResponse('success', "Rất tiếc! <b>$found_service_name</b> khung giờ này đã kín.");
            } else {
                $end_label = date('H:i', strtotime($check_end));
                
                // --- MỚI: LẤY GIÁ TIỀN ĐỂ BÁO KHÁCH ---
                $stmt_price = $pdo->prepare("SELECT price FROM services WHERE id = ?");
                $stmt_price->execute([$found_service_id]);
                $price = $stmt_price->fetchColumn();
                $total_money = $price * $duration;
                $money_formatted = number_format($total_money, 0, ',', '.');

                sendResponse('success', "✅ Còn trống! Tổng tiền: <b>$money_formatted đ</b> ($duration tiếng).<br>Bạn có muốn chốt không?", [
                    'confirm_booking' => true,
                    'service_id' => $found_service_id,
                    'service_name' => $found_service_name,
                    'date' => $found_date,
                    'time' => $found_time,
                    'duration' => $duration,
                    'total_price' => $total_money, // Gửi giá tiền về JS
                    'label' => date('H:i', strtotime($found_time)) . " - " . $end_label . " ($money_formatted đ)"
                ]);
            }
        } 
        elseif ($found_service_id && $found_date) checkAvailabilityAndRespond($pdo, $found_service_id, $found_service_name, $found_date);
        elseif ($found_service_id && $found_time) sendResponse('success', "Bạn muốn đặt <b>$found_service_name</b> ngày nào?");
        elseif ($found_date && $found_time) sendResponse('success', "Ngày $found_date lúc $found_time, bạn muốn đặt dịch vụ gì?");
        elseif ($found_service_id) sendResponse('success', "Bạn muốn đặt <b>$found_service_name</b> ngày nào?");
        else sendResponse('success', "Mình chưa hiểu. Vui lòng nhắn tên <b>Dịch vụ</b> và <b>Thời gian</b>.");
    }

    // --- 3. CÁC API KHÁC ---
    if ($action == 'check_availability') {
        $sid = $_POST['service_id']; $date = $_POST['date'];
        $_SESSION['chat_service_id'] = $sid; $_SESSION['chat_date'] = $date;
        $stmt = $pdo->prepare("SELECT name FROM services WHERE id = ?"); $stmt->execute([$sid]);
        $sname = $stmt->fetchColumn() ?: 'Dịch vụ';
        $_SESSION['chat_service_name'] = $sname;
        checkAvailabilityAndRespond($pdo, $sid, $sname, $date);
    }

    // --- 4. CHỐT ĐƠN (CÓ LƯU TIỀN) ---
    if ($action == 'book_slot') {
        if (!isset($_SESSION['user_id'])) sendResponse('error', 'Vui lòng đăng nhập!');
        $uid = $_SESSION['user_id']; 
        $sid = $_POST['service_id']; 
        $date = $_POST['date']; 
        $time = $_POST['start_time'];
        $duration = isset($_POST['duration']) ? intval($_POST['duration']) : 1;
        
        $start_dt = "$date $time"; 
        $end_dt = date('Y-m-d H:i:s', strtotime($start_dt) + ($duration * 3600));
        
        // Check trùng lần cuối
        $check = $pdo->prepare("SELECT id FROM bookings WHERE service_id=? AND status!='cancelled' AND (start_time < ? AND end_time > ?)");
        $check->execute([$sid, $end_dt, $start_dt]);
        if ($check->rowCount() > 0) sendResponse('error', 'Chậm mất rồi! Giờ này vừa có người đặt.');

        // --- MỚI: TÍNH LẠI TIỀN TRƯỚC KHI LƯU (BẢO MẬT) ---
        $stmt_price = $pdo->prepare("SELECT price FROM services WHERE id = ?");
        $stmt_price->execute([$sid]);
        $price_per_hour = $stmt_price->fetchColumn();
        $final_total = $price_per_hour * $duration;

        // --- LƯU VÀO DB ---
        $sql = "INSERT INTO bookings (user_id, service_id, start_time, end_time, total_price, status) VALUES (?,?,?,?,?, 'pending')";
        $pdo->prepare($sql)->execute([$uid, $sid, $start_dt, $end_dt, $final_total]);
        
        sendResponse('success', "Đặt lịch thành công! Tổng tiền: " . number_format($final_total,0,',','.') . "đ");
    }

} catch (Exception $e) { sendResponse('error', 'Lỗi Server: ' . $e->getMessage()); }

function checkAvailabilityAndRespond($pdo, $sid, $sname, $date) {
    $all_slots = ['08:00:00'=>'08:00','09:00:00'=>'09:00','10:00:00'=>'10:00','14:00:00'=>'14:00','15:00:00'=>'15:00','16:00:00'=>'16:00','18:00:00'=>'18:00','19:00:00'=>'19:00'];
    $stmt = $pdo->prepare("SELECT start_time FROM bookings WHERE service_id=? AND DATE(start_time)=? AND status!='cancelled'");
    $stmt->execute([$sid, $date]);
    $booked = array_map(function($dt){ return date('H:i:s', strtotime($dt)); }, $stmt->fetchAll(PDO::FETCH_COLUMN));
    $avail = [];
    foreach($all_slots as $t=>$l) { if(!in_array($t, $booked)) $avail[] = ['time'=>$t, 'label'=>$l]; }
    
    if($avail) sendResponse('success', "Các giờ trống của <b>$sname</b> ngày <b>$date</b>:", ['slots'=>$avail, 'service_id'=>$sid, 'date'=>$date]);
    else sendResponse('success', "Ngày <b>$date</b> đã kín lịch.");
}
?>