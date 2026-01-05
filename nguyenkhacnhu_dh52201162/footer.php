<button id="chat-toggle-btn" onclick="toggleChat()">
    💬 Đặt Lịch Nhanh
</button>

<div id="chat-window">
    <div class="chat-header">
        <span>🤖 Trợ lý Ảo</span>
        <button onclick="toggleChat()" style="background:none; border:none; color:white; font-size:18px;">&times;</button>
    </div>
    <div class="chat-body" id="chat-body">
        <div class="bot-msg">Xin chào! Tôi có thể giúp bạn đặt lịch dịch vụ ngay tại đây.</div>
        <div class="bot-msg">Hãy nhấn nút bên dưới để bắt đầu nhé! 👇</div>
        <div class="chat-options">
            <button onclick="startBooking()">🚀 Bắt đầu đặt lịch</button>
        </div>
    </div>
</div>
<style>
    /* Nút tròn nổi góc màn hình */
    #chat-toggle-btn {
        position: fixed; bottom: 20px; right: 20px;
        /* ... toàn bộ code CSS mình gửi ở trên ... */
    }
    /* ... các class khác ... */
</style>

<button id="chat-toggle-btn" onclick="toggleChat()">
    💬 Đặt Lịch Nhanh
</button>

<div id="chat-window">
    </div>

<script>
    // 1. Ẩn/Hiện Chat
    function toggleChat() {
        const chat = document.getElementById('chat-window');
        chat.style.display = (chat.style.display === 'none' || chat.style.display === '') ? 'flex' : 'none';
    }

    // Hàm hỗ trợ: Thêm tin nhắn vào khung
    function addMsg(text, type) {
        const chatBody = document.getElementById('chat-body');
        const div = document.createElement('div');
        div.className = type === 'bot' ? 'bot-msg' : 'user-msg';
        div.innerHTML = text;
        chatBody.appendChild(div);
        chatBody.scrollTop = chatBody.scrollHeight; // Tự cuộn xuống cuối
    }

    // 2. Bắt đầu quy trình đặt lịch
    let bookingData = {}; // Lưu tạm dữ liệu user chọn

    function startBooking() {
        // Xóa các nút cũ
        document.querySelector('.chat-options').remove();
        addMsg("Tôi muốn đặt lịch", 'user');

        // Gọi API lấy danh sách dịch vụ
        fetch('chatbot_api.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'action=get_services'
        })
        .then(res => res.json())
        .then(res => {
            if(res.status === 'success') {
                addMsg("Bạn muốn đặt dịch vụ nào?", 'bot');
                
                // Tạo các nút dịch vụ
                let html = '<div class="chat-options">';
                res.data.forEach(s => {
                    html += `<button onclick="selectService(${s.id}, '${s.name}')">${s.name}</button>`;
                });
                html += '</div>';
                
                document.getElementById('chat-body').insertAdjacentHTML('beforeend', html);
            } else {
                addMsg(res.message, 'bot'); // Lỗi chưa đăng nhập
            }
        });
    }

    // 3. Chọn Dịch vụ -> Hỏi Ngày
    function selectService(id, name) {
        bookingData.service_id = id;
        document.querySelector('.chat-options').remove(); // Xóa nút chọn cũ
        addMsg(name, 'user');

        addMsg("Bạn muốn đặt vào ngày nào?", 'bot');
        
        // Hiện ô chọn ngày
        let today = new Date().toISOString().split('T')[0];
        let html = `
            <div class="chat-options">
                <input type="date" class="chat-date-input" min="${today}" onchange="selectDate(this.value)">
            </div>`;
        document.getElementById('chat-body').insertAdjacentHTML('beforeend', html);
    }

    // 4. Chọn Ngày -> Kiểm tra giờ trống
    function selectDate(date) {
        bookingData.date = date;
        document.querySelector('.chat-options').remove();
        addMsg("Ngày " + date, 'user');
        addMsg("Đang kiểm tra giờ trống...", 'bot');

        // Gọi API check giờ
        fetch('chatbot_api.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `action=check_availability&service_id=${bookingData.service_id}&date=${bookingData.date}`
        })
        .then(res => res.json())
        .then(res => {
            if(res.data.length > 0) {
                addMsg("Đây là các khung giờ còn trống:", 'bot');
                let html = '<div class="chat-options">';
                res.data.forEach(slot => {
                    html += `<button onclick="confirmBooking('${slot.time}', '${slot.label}')">${slot.label}</button>`;
                });
                html += '</div>';
                document.getElementById('chat-body').insertAdjacentHTML('beforeend', html);
            } else {
                addMsg("Rất tiếc, ngày này đã kín lịch. Vui lòng chọn ngày khác.", 'bot');
                // Gọi lại hàm chọn ngày (tùy chọn)
            }
        });
    }

    // 5. Chọn Giờ -> Chốt đơn
    function confirmBooking(time, label) {
        bookingData.start_time = time;
        document.querySelector('.chat-options').remove();
        addMsg(label, 'user');
        addMsg("Đang xử lý đặt lịch...", 'bot');

        fetch('chatbot_api.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `action=book_slot&service_id=${bookingData.service_id}&date=${bookingData.date}&start_time=${bookingData.start_time}`
        })
        .then(res => res.json())
        .then(res => {
            if(res.status === 'success') {
                addMsg("✅ " + res.message, 'bot');
                addMsg("Cảm ơn bạn đã sử dụng dịch vụ!", 'bot');
            } else {
                addMsg("❌ " + res.message, 'bot');
            }
        });
    }
</script>