<?php
require 'config.php';

$is_logged_in = isset($_SESSION['user_id']);
$username = $is_logged_in ? htmlspecialchars($_SESSION['username']) : '';
$role = $is_logged_in ? $_SESSION['role'] : '';

$alert_html = '';
if (isset($_GET['booking_status']) && $_GET['booking_status'] === 'success') {
    $alert_html = '
    <div id="success-toast" class="toast-notification">
        <div class="toast-icon">✅</div>
        <div class="toast-content">
            <div class="toast-title">Thành công!</div>
            <div class="toast-message">Yêu cầu đã được gửi.</div>
        </div>
    </div>
    <script>setTimeout(() => document.getElementById("success-toast").classList.add("hide"), 5000);</script>';
}

try {
    $stmt_cat = $pdo->prepare("SELECT * FROM categories ORDER BY id");
    $stmt_cat->execute();
    $db_categories = $stmt_cat->fetchAll(PDO::FETCH_ASSOC);

    $stmt_serv = $pdo->prepare("SELECT s.*, c.code as cat_code FROM services s JOIN categories c ON s.category_id = c.id WHERE s.is_active = 1 ORDER BY s.category_id, s.id");
    $stmt_serv->execute();
    $all_services = $stmt_serv->fetchAll(PDO::FETCH_ASSOC);
    
    $services_for_js = [];
    foreach ($all_services as $s) {
        $cat = $s['cat_code'];
        $img_path = (!empty($s['image']) && file_exists("img/" . $s['image'])) ? "img/" . $s['image'] : "https://source.unsplash.com/500x300/?" . urlencode($s['name']);
        
        $services_for_js[$cat][] = [
            'id' => $s['id'],
            'title' => $s['name'],
            'desc' => $s['description'],
            'price' => number_format($s['price'], 0, ',', '.'),
            'time' => $s['min_hours'] . 'h - ' . $s['max_hours'] . 'h',
            'img' => $img_path,
            'link' => $is_logged_in ? "book.php?service_id=" . $s['id'] : "login.php"
        ];
    }

    $gradients = [
        'linear-gradient(135deg, #2563EB 0%, #1D4ED8 100%)', 
        'linear-gradient(135deg, #059669 0%, #047857 100%)', 
        'linear-gradient(135deg, #D97706 0%, #B45309 100%)', 
        'linear-gradient(135deg, #7C3AED 0%, #6D28D9 100%)', 
        'linear-gradient(135deg, #DB2777 0%, #BE185D 100%)'  
    ];

    $categories = [];
    $i = 0;
    foreach ($db_categories as $cat) {
        $categories[$cat['code']] = [
            'id' => $cat['id'],
            'name' => $cat['name'],
            'bg' => $gradients[$i % count($gradients)],
            'img' => (!empty($cat['image']) && file_exists("img/".$cat['image'])) ? "img/".$cat['image'] : null
        ];
        $i++;
    }

} catch (PDOException $e) { die("Lỗi: " . $e->getMessage()); }
?>

<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>BOOK TO HEAL</title>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;700;800&display=swap" rel="stylesheet">
  
  <style>
    :root {
        --primary: #3B82F6; --text-main: #0F172A; --text-light: #64748B; --radius: 20px;
    }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #F8FAFC; color: var(--text-main); padding-bottom: 80px; }

    /* HEADER - Đã tăng chiều cao */
    .main-header {
        background: rgba(255,255,255,0.95); backdrop-filter: blur(12px);
        padding: 10px 40px; 
        display: flex; justify-content: space-between; align-items: center;
        position: sticky; top: 0; z-index: 1000; border-bottom: 1px solid #eee;
        height: 110px; /* Tăng chiều cao lên 110px để chứa logo to */
    }
    
    .brand { display: flex; align-items: center; gap: 15px; text-decoration: none; }
    
    /* LOGO RẤT TO */
    .brand-logo-img { 
        height: 100px; /* Kích thước logo tăng lên 85px */
        width: auto; object-fit: contain; 
    } 
    
    .brand-text h1 { font-size: 26px; font-weight: 800; color: var(--text-main); margin: 0; letter-spacing: -0.5px; line-height: 1.1;}
    .brand-text span { font-size: 13px; color: var(--primary); font-weight: 600; text-transform: uppercase; letter-spacing: 1px; }

    .nav-actions { display: flex; gap: 15px; align-items: center; }
    .btn { padding: 12px 25px; border-radius: 50px; font-weight: 700; text-decoration: none; font-size: 15px; border: none; cursor: pointer; transition: 0.2s; }
    .btn-outline { border: 1.5px solid #E2E8F0; background: white; color: var(--text-main); }
    .btn-outline:hover { border-color: var(--text-main); background: #f8fafc; }
    .btn-primary { background: var(--text-main); color: white; }
    .btn-primary:hover { background: #333; transform: translateY(-2px); }
    .btn-danger { background: #EF4444; color: white; }

    /* CONTAINER CÂN ĐỐI */
    .wrap { 
        max-width: 1400px; 
        width: 92%; 
        margin: 0 auto; 
        padding: 0 20px; 
    }

    /* HERO */
    .hero-section { text-align: center; padding: 70px 0 50px; }
    .hero-title { font-size: 52px; font-weight: 800; line-height: 1.15; margin-bottom: 15px; letter-spacing: -1.5px; color: #111; }
    .hero-desc { font-size: 18px; color: var(--text-light); max-width: 650px; margin: 0 auto; }

    /* GRID DANH MỤC */
    .category-grid {
        display: grid; 
        grid-template-columns: repeat(auto-fit, minmax(600px, 1fr));
        gap: 30px; margin-bottom: 50px;
    }

    .category-card {
        position: relative; 
        height: 260px;
        border-radius: var(--radius); overflow: hidden; cursor: pointer;
        box-shadow: var(--shadow-md); transition: transform 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
    }
    .category-card:hover { transform: translateY(-8px); box-shadow: 0 20px 40px -5px rgba(0,0,0,0.15); }
    
    .card-bg { position: absolute; inset: 0; background-size: cover; background-position: center; transition: transform 0.6s; }
    .category-card:hover .card-bg { transform: scale(1.05); }
    .card-overlay { position: absolute; inset: 0; background: linear-gradient(to right, rgba(0,0,0,0.7) 0%, rgba(0,0,0,0.2) 100%); }

    .card-content {
        position: absolute; inset: 0; padding: 40px;
        display: flex; flex-direction: column; justify-content: center;
        color: white;
    }
    .cat-id { font-size: 14px; font-weight: 700; opacity: 0.7; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 1.5px;}
    .cat-name { font-size: 36px; font-weight: 800; line-height: 1.1; margin-bottom: 0; }
    .cat-action { 
        margin-top: 15px; font-size: 15px; font-weight: 600; opacity: 0; transform: translateY(10px); transition: 0.3s; 
        display: flex; align-items: center; gap: 8px;
    }
    .category-card:hover .cat-action { opacity: 1; transform: translateY(0); }

    /* SERVICE PANEL */
    .service-panel { display: none; animation: slideUp 0.5s ease; margin-top: 20px;}
    .panel-header { 
        font-size: 28px; font-weight: 800; margin-bottom: 30px; 
        padding-bottom: 15px; border-bottom: 2px solid #E2E8F0; 
    }

    .service-card {
        background: white; border-radius: var(--radius); padding: 30px;
        display: flex; gap: 40px; align-items: center; margin-bottom: 25px;
        border: 1px solid #F1F5F9; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
        transition: 0.3s;
    }
    .service-card:hover { border-color: var(--primary); box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1); }

    .svc-img { width: 300px; height: 200px; border-radius: 12px; overflow: hidden; flex-shrink: 0; }
    .svc-img img { width: 100%; height: 100%; object-fit: cover; transition: 0.5s; }
    .service-card:hover .svc-img img { transform: scale(1.05); }

    .svc-info { flex: 1; }
    .svc-name { font-size: 24px; font-weight: 700; margin-bottom: 10px; color: #1E293B; }
    .svc-desc { font-size: 16px; color: #64748B; margin-bottom: 20px; line-height: 1.6; }
    .svc-meta { display: flex; gap: 20px; align-items: center; margin-bottom: 25px; }
    .svc-price { font-size: 22px; font-weight: 800; color: var(--primary); }
    .svc-time { background: #F1F5F9; padding: 6px 12px; border-radius: 8px; font-weight: 600; font-size: 14px; }

    .btn-book { padding: 12px 30px; background: var(--text-main); color: white; border-radius: 10px; text-decoration: none; font-weight: 700; display: inline-block; transition:0.2s; }
    .btn-book:hover { background: var(--primary); transform: translateY(-2px); }

    @keyframes slideUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
    
    /* TOAST */
    .toast-notification { position: fixed; top: 130px; right: 30px; background: white; padding: 20px; border-radius: 12px; box-shadow: 0 20px 60px rgba(0,0,0,0.15); border-left: 5px solid #10B981; display: flex; gap: 15px; align-items: center; animation: slideIn 0.4s; z-index: 9999; }
    .toast-title { font-weight: 700; font-size: 16px; color: #0F172A; }
    .toast-notification.hide { display: none; }
    @keyframes slideIn { from { transform: translateX(100%); } to { transform: translateX(0); } }

    @media (max-width: 768px) {
        .category-grid { grid-template-columns: 1fr; }
        .service-card { flex-direction: column-reverse; }
        .svc-img { width: 100%; height: 220px; }
        .main-header { flex-direction: column; gap: 15px; height: auto; padding: 20px; }
        .hero-title { font-size: 36px; }
    }
  </style>
</head>
<body>

  <header class="main-header">
    <a href="index.php" class="brand">
        <?php if(file_exists('img/logo.jpg')): ?>
            <img src="img/logo.jpg" alt="Logo" class="brand-logo-img"> 
        <?php endif; ?>
        <div class="brand-text">
            <span>Hệ thống</span>
            <h1>BOOK TO HEAL</h1>
        </div>
    </a>

    <div class="nav-actions">
      <?php if($is_logged_in): ?>
          <span class="user-welcome">Hi, <?= $username ?></span>
          <a href="my_bookings.php" class="btn btn-outline">Lịch sử</a>
          <?php if($role === 'admin'): ?> 
             <a href="admin/index.php" class="btn btn-outline" style="color: var(--primary); border-color: var(--primary);">Admin</a> 
          <?php endif; ?>
          <a href="logout.php" class="btn btn-danger">Thoát</a>
      <?php else: ?>
          <a href="register.php" class="btn btn-outline">Đăng ký</a>
          <a href="login.php" class="btn btn-primary">Đăng nhập</a>
      <?php endif; ?>
    </div>
  </header>

  <div class="wrap">
    <?= $alert_html ?>

    <div class="hero-section">
        <h2 class="hero-title">Tìm không gian hoàn hảo <br> cho trải nghiệm của bạn</h2>
        <p class="hero-desc">Khám phá các dịch vụ phòng học, studio, thể thao và tổ chức tiệc với chất lượng tốt nhất.</p>
    </div>

    <div class="category-grid" id="categoryGrid">
      <?php foreach($categories as $code => $cat): ?>
          <div class="category-card" data-id="<?= $code ?>" onclick="showServices('<?= $code ?>')">
            <div class="card-bg" style="<?php echo !empty($cat['img']) ? "background-image: url('img/{$cat['img']}');" : "background: {$cat['bg']};"; ?>"></div>
            <div class="card-overlay"></div>
            <div class="card-content">
                <div class="cat-id">Danh mục 0<?= $cat['id'] ?></div>
                <div class="cat-name"><?= $cat['name'] ?></div>
                <div class="cat-action">Xem chi tiết &rarr;</div>
            </div>
          </div>
      <?php endforeach; ?>
    </div>

    <div id="servicesDisplay" class="service-panel"></div>

  </div>

  <script>
    const SERVICES = <?php echo json_encode($services_for_js); ?>;
    const displayArea = document.getElementById('servicesDisplay');

    function showServices(catCode) {
        const list = SERVICES[catCode] || [];
        let categoryName = "Dịch vụ";
        try {
             categoryName = document.querySelector(`.category-card[data-id="${catCode}"] .cat-name`).innerText;
        } catch(e) {}

        displayArea.innerHTML = '';
        displayArea.style.display = 'block';

        let html = `<div class="panel-header">${categoryName}</div>`;

        if(list.length === 0){
            html += '<div style="text-align:center; padding:60px; color:#999; font-size: 18px;">Chưa có dịch vụ nào trong danh mục này.</div>';
        } else {
            list.forEach(s => {
                let actionBtn = s.link.includes('login.php') 
                    ? `<a href="${s.link}" style="color:#EF4444; font-weight:700; font-size:15px;">🔒 Đăng nhập để đặt</a>`
                    : `<a href="${s.link}" class="btn-book">Đặt Phòng Ngay &rarr;</a>`;

                html += `
                <div class="service-card">
                    <div class="svc-img">
                        <img src="${s.img}" alt="${s.title}">
                    </div>
                    <div class="svc-info">
                        <div class="svc-name">${s.title}</div>
                        <div class="svc-desc">${s.desc}</div>
                        <div class="svc-meta">
                            <div class="svc-price">${s.price}</div>
                            <div class="svc-time">⏱ ${s.time}</div>
                        </div>
                        ${actionBtn}
                    </div>
                </div>`;
            });
        }

        displayArea.innerHTML = html;
        setTimeout(() => { displayArea.scrollIntoView({behavior: 'smooth', block: 'start'}); }, 100);
    }
  </script>
    <style>
    /* Nút Chat */
    #chat-toggle-btn {
        position: fixed; bottom: 30px; right: 30px;
        background: #3B82F6; color: white; border: none;
        padding: 15px 20px; border-radius: 30px;
        font-weight: bold; cursor: pointer;
        box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        z-index: 99999; display: flex; align-items: center; gap: 8px;
        transition: transform 0.2s;
    }
    #chat-toggle-btn:hover { transform: scale(1.05); }

    /* Khung Chat */
    #chat-window {
        position: fixed; bottom: 90px; right: 30px;
        width: 360px; height: 500px;
        background: white; border-radius: 16px;
        box-shadow: 0 5px 30px rgba(0,0,0,0.2);
        display: none; flex-direction: column; overflow: hidden;
        z-index: 99999; font-family: 'Plus Jakarta Sans', sans-serif;
        border: 1px solid #e2e8f0;
    }

    .chat-header {
        background: #3B82F6; color: white; padding: 15px;
        display: flex; justify-content: space-between; align-items: center;
        font-weight: 700;
    }

    .chat-body {
        flex: 1; padding: 15px; overflow-y: auto; background: #F8FAFC;
        display: flex; flex-direction: column; gap: 10px;
    }

    /* Tin nhắn */
    .bot-msg { align-self: flex-start; background: white; padding: 10px 14px; border-radius: 15px 15px 15px 2px; max-width: 85%; font-size: 14px; color: #334155; border: 1px solid #cbd5e1; }
    .user-msg { align-self: flex-end; background: #3B82F6; color: white; padding: 10px 14px; border-radius: 15px 15px 2px 15px; max-width: 85%; font-size: 14px; }

    /* Vùng nhập liệu (MỚI) */
    .chat-input-area {
        padding: 10px; background: white; border-top: 1px solid #e2e8f0;
        display: flex; gap: 8px;
    }
    .chat-input-area input {
        flex: 1; padding: 10px; border: 1px solid #cbd5e1; border-radius: 20px; outline: none; font-size: 14px;
    }
    .chat-input-area button {
        background: #3B82F6; color: white; border: none; width: 40px; height: 40px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center;
    }

    /* Nút chọn */
    .chat-options { display: flex; flex-wrap: wrap; gap: 5px; margin-top: 5px; }
    .chat-options button {
        background: white; border: 1px solid #3B82F6; color: #3B82F6;
        padding: 6px 12px; border-radius: 20px; cursor: pointer; font-size: 13px; font-weight: 600;
        transition: 0.2s;
    }
    .chat-options button:hover { background: #3B82F6; color: white; }
    .chat-date-input { padding: 8px; border: 1px solid #ccc; border-radius: 8px; width: 100%; margin-top: 5px; }
</style>

<button id="chat-toggle-btn" onclick="toggleChat()">
    💬 <span>Trợ lý Ảo</span>
</button>

<div id="chat-window">
    <div class="chat-header">
        <span>🤖 Tư vấn đặt lịch</span>
        <button onclick="toggleChat()" style="background:none; border:none; color:white; font-size:24px; cursor:pointer;">&times;</button>
    </div>
    
    <div class="chat-body" id="chat-body">
        <div class="bot-msg">Xin chào! 👋<br>Bạn có thể chọn nút Menu hoặc nhắn tin trực tiếp.<br>Ví dụ: <i>"Ngày 05/01/2026 phòng học nhỏ còn trống không?"</i></div>
        <div class="chat-options">
            <button onclick="startMenuBooking()">📋 Xem Menu Chọn</button>
        </div>
    </div>

    <div class="chat-input-area">
        <input type="text" id="chat-input" placeholder="Nhập tin nhắn..." onkeypress="handleEnter(event)">
        <button onclick="sendUserMessage()"><i class="bi bi-send-fill" style="font-style:normal">➤</i></button>
    </div>
</div>

<script>
    let bookingData = {}; 

    function toggleChat() {
        const chat = document.getElementById('chat-window');
        chat.style.display = (chat.style.display === 'none' || chat.style.display === '') ? 'flex' : 'none';
        if(chat.style.display === 'flex') document.getElementById('chat-input').focus();
    }

    function addMsg(text, type) {
        const chatBody = document.getElementById('chat-body');
        const div = document.createElement('div');
        div.className = type === 'bot' ? 'bot-msg' : 'user-msg';
        div.innerHTML = text;
        chatBody.appendChild(div);
        chatBody.scrollTop = chatBody.scrollHeight;
    }

    function handleEnter(e) { if (e.key === 'Enter') sendUserMessage(); }

    // --- GỬI TIN NHẮN (NLP - MỚI) ---
    // --- GỬI TIN NHẮN (CẬP NHẬT) ---
    function sendUserMessage() {
        const input = document.getElementById('chat-input');
        const text = input.value.trim();
        if (!text) return;

        addMsg(text, 'user');
        input.value = '';
        addMsg("<i>Đang kiểm tra...</i>", 'bot');

        fetch('chatbot_api.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'action=chat_message&message=' + encodeURIComponent(text)
        })
        .then(res => res.json())
        .then(res => {
            document.querySelector('.bot-msg:last-child').remove();
            addMsg(res.message, 'bot');

            // 1. TRƯỜNG HỢP: CHỐT ĐƠN NGAY (Có duration)
            if (res.confirm_booking) {
                bookingData.service_id = res.service_id;
                bookingData.date = res.date;
                bookingData.duration = res.duration; // LƯU THỜI LƯỢNG VÀO BIẾN TOÀN CỤC
                
                let html = `
                <div class="chat-options">
                    <button style="background:#10B981; border-color:#10B981; color:white; width:100%;" 
                            onclick="confirmBooking('${res.time}', '${res.service_name}: ${res.label}')">
                        ✅ Chốt đơn (${res.label})
                    </button>
                    <button style="background:#EF4444; border-color:#EF4444; color:white; width:100%; margin-top:5px;" 
                            onclick="addMsg('Đã hủy.', 'bot'); document.querySelector('.chat-options:last-child').remove();">
                        ❌ Hủy
                    </button>
                </div>`;
                document.getElementById('chat-body').insertAdjacentHTML('beforeend', html);
                document.getElementById('chat-body').scrollTop = document.getElementById('chat-body').scrollHeight;
            }
            // 2. TRƯỜNG HỢP: HIỆN DANH SÁCH GIỜ (Chỉ cho phép chọn 1 tiếng)
            else if (res.slots && res.slots.length > 0) {
                bookingData.service_id = res.service_id;
                bookingData.date = res.date;
                bookingData.duration = 1; // Nếu chọn từ list thì mặc định là 1 tiếng

                let html = '<div class="chat-options">';
                res.slots.forEach(slot => {
                    html += `<button onclick="confirmBooking('${slot.time}', '${slot.label}')">${slot.label}</button>`;
                });
                html += '</div>';
                document.getElementById('chat-body').insertAdjacentHTML('beforeend', html);
                document.getElementById('chat-body').scrollTop = document.getElementById('chat-body').scrollHeight;
            }
        });
    }

    // --- CHỌN MENU (CŨ - GIỮ LẠI LÀM PHỤ) ---
    function startMenuBooking() {
        addMsg("📋 Tôi muốn chọn theo Menu", 'user');
        fetch('chatbot_api.php', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: 'action=get_services' })
        .then(res => res.json())
        .then(res => {
            addMsg("Bạn muốn đặt dịch vụ nào?", 'bot');
            let html = '<div class="chat-options">';
            res.data.forEach(s => html += `<button onclick="selectService(${s.id}, '${s.name}')">${s.name}</button>`);
            html += '</div>';
            document.getElementById('chat-body').insertAdjacentHTML('beforeend', html);
            document.getElementById('chat-body').scrollTop = document.getElementById('chat-body').scrollHeight;
        });
    }

    function selectService(id, name) {
        bookingData.service_id = id;
        addMsg(name, 'user');
        addMsg("Chọn ngày bạn muốn đặt:", 'bot');
        let today = new Date().toISOString().split('T')[0];
        let html = `<div class="chat-options"><input type="date" class="chat-date-input" min="${today}" onchange="selectDate(this.value)"></div>`;
        document.getElementById('chat-body').insertAdjacentHTML('beforeend', html);
        document.getElementById('chat-body').scrollTop = document.getElementById('chat-body').scrollHeight;
    }

    function selectDate(date) {
        bookingData.date = date;
        addMsg("Ngày " + date, 'user');
        addMsg("<i>Đang tải giờ trống...</i>", 'bot');
        
        fetch('chatbot_api.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `action=check_availability&service_id=${bookingData.service_id}&date=${bookingData.date}`
        })
        .then(res => res.json())
        .then(res => {
            document.querySelector('.bot-msg:last-child').remove();
            if (res.slots) { 
                addMsg(res.message, 'bot');
                let html = '<div class="chat-options">';
                res.slots.forEach(slot => html += `<button onclick="confirmBooking('${slot.time}', '${slot.label}')">${slot.label}</button>`);
                html += '</div>';
                document.getElementById('chat-body').insertAdjacentHTML('beforeend', html);
            } else {
                addMsg(res.message, 'bot');
            }
            document.getElementById('chat-body').scrollTop = document.getElementById('chat-body').scrollHeight;
        });
    }

    // --- XÁC NHẬN ĐẶT (CẬP NHẬT GỬI DURATION) ---
    function confirmBooking(time, label) {
        // Xóa các nút lựa chọn cũ
        const opts = document.querySelector('.chat-options');
        if(opts) opts.remove();

        addMsg(label, 'user');
        addMsg("⏳ Đang xử lý đặt lịch...", 'bot');

        // Gửi thêm tham số DURATION
        const duration = bookingData.duration ? bookingData.duration : 1;

        fetch('chatbot_api.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `action=book_slot&service_id=${bookingData.service_id}&date=${bookingData.date}&start_time=${time}&duration=${duration}`
        })
        .then(res => res.json())
        .then(res => {
            document.querySelector('.bot-msg:last-child').remove();
            
            if(res.status === 'success') {
                addMsg("✅ " + res.message, 'bot');
            } else {
                addMsg("❌ " + res.message, 'bot');
            }
            document.getElementById('chat-body').scrollTop = document.getElementById('chat-body').scrollHeight;
        });
    }
</script>
</script>
</body>
</html>