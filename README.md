# BOOK TO HEAL - WEBSITE ĐẶT LỊCH DỊCH VỤ

## 1. Giới thiệu
**Book To Heal** là hệ thống website hỗ trợ đặt lịch phòng học, studio, phòng tập gym... giúp người dùng tiết kiệm thời gian và tránh tình trạng trùng lịch. Hệ thống tích hợp Chatbot AI hỗ trợ tư vấn và đặt lịch nhanh chóng.

- **Sinh viên thực hiện:** Nguyễn Khắc Nhu
- **MSSV:** DH52201162
- **Lớp:** D22-TH06
- **Môn học:** Thực tập chuyên ngành

## 2. Công nghệ sử dụng
- **Backend:** PHP Thuần (Native)
- **Frontend:** HTML5, CSS3 (Modern UI), JavaScript (AJAX)
- **Database:** MySQL
- **Server:** XAMPP (Apache)

## 3. Các chức năng chính
- **Khách hàng:**
  - Xem danh sách dịch vụ, chi tiết giá.
  - Đặt lịch theo giờ (hệ thống tự tính tiền).
  - Chatbot AI tư vấn và chốt đơn.
  - Xem lịch sử đặt chỗ.
- **Admin:**
  - Dashboard thống kê.
  - Quản lý Dịch vụ (Thêm/Xóa/Sửa/Upload ảnh).
  - Quản lý Đơn hàng (Duyệt/Hủy/Thu tiền).

## 4. Hướng dẫn cài đặt
1. **Cài đặt môi trường:**
   - Cài đặt [XAMPP](https://www.apachefriends.org/).
   - Khởi động Apache và MySQL.

2. **Cấu hình Cơ sở dữ liệu:**
   - Truy cập `http://localhost/phpmyadmin`.
   - Tạo database mới tên: `booking_app`.
   - Import file `database.sql` (nằm trong thư mục gốc của project).

3. **Cài đặt mã nguồn:**
   - Copy thư mục `booktoheal` vào đường dẫn `C:/xampp/htdocs/`.
   - Kiểm tra file `config.php` để đảm bảo thông tin kết nối đúng (user: root, pass: rỗng).

4. **Chạy dự án:**
   - Mở trình duyệt truy cập: `http://localhost/booktoheal`
   - Tài khoản Admin mặc định (nếu có): `admin` / `123456`

## 5. Liên hệ
Mọi thắc mắc vui lòng liên hệ qua email: dh52201162@student.stu.edu.vn