# Hệ Thống Cổng Thông Tin & Quản Lý Phòng Khám Đa Khoa Phú Thái

Hệ thống web portal dành cho Phòng khám đa khoa Phú Thái, được phát triển trên nền tảng PHP thuần và MySQL.

## Tính Năng Chính

- **Cổng thông tin & Đặt lịch khám trực tuyến:** Bệnh nhân xem danh sách bác sĩ, chuyên khoa, bảng giá dịch vụ và đăng ký lịch khám.
- **Quản lý hồ sơ bệnh nhân & Trả kết quả khám:** Bệnh nhân tra cứu lịch sử khám bệnh, xem và tải kết quả xét nghiệm/chuẩn đoán dạng PDF.
- **Xác thực bảo mật & Phân quyền:**
  - Đăng ký, đăng nhập tài khoản bệnh nhân và quản trị viên.
  - Quên mật khẩu qua mã OTP gửi qua Gmail SMTP hoặc SMS Webhook.
  - Cơ chế khóa tài khoản khi nhập sai nhiều lần và chống brute-force đăng nhập.
  - Phân quyền chi tiết cho nhân viên tiếp đón, bác sĩ và quản trị viên hệ thống.
- **Trợ lý AI Hỗ trợ Trực tuyến:** Chatbot tích hợp Google Gemini AI hỗ trợ giải đáp thắc mắc, quy trình khám và bảng giá tự động.
- **Bảo mật nâng cao:** Tích hợp Cloudflare Turnstile / Google reCAPTCHA v2, bảo mật CSRF token, HTTP security headers.

---

## Hướng Dẫn Cài Đặt

### 1. Yêu cầu hệ thống
- PHP >= 8.1 (bật các extension: `mysqli`, `curl`, `mbstring`, `openssl`, `fileinfo`).
- MySQL / MariaDB >= 8.0.
- Web server: Apache (hỗ trợ `mod_rewrite`) hoặc Nginx.

### 2. Các bước triển khai
1. **Clone mã nguồn:**
   ```bash
   git clone https://github.com/pcnttphongkhamdakhoaphuthai-wq/web.git
   cd web
   ```
2. **Khởi tạo cơ sở dữ liệu:**
   - Tạo database `benhvien_support` trên MySQL:
     ```sql
     CREATE DATABASE benhvien_support CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
     ```
   - Import file schema sạch:
     ```bash
     mysql -u [db_user] -p benhvien_support < database.sql
     ```
3. **Cấu hình thông tin bí mật (Secrets):**
   - Copy file mẫu `hospital_full_ALL.secrets.example.php` thành file `hospital_full_ALL.secrets.php`:
     ```bash
     cp hospital_full_ALL.secrets.example.php hospital_full_ALL.secrets.php
     ```
   - Cập nhật thông tin kết nối Database, Gemini API Key, và cấu hình Gmail SMTP trong file vừa tạo (hoặc cấu hình qua biến môi trường hệ thống).
   - *(Lưu ý: Tệp `*.secrets.php` đã được đưa vào `.gitignore` để không bao giờ bị đẩy lên GitHub).*
4. **Phân quyền thư mục:**
   - Đảm bảo các thư mục sau có quyền ghi (write permissions): `storage/`, `uploads/`, `assets/`.
5. **Khởi tạo tài khoản quản trị viên:**
   - Truy cập `admin_login.php` trên trình duyệt.
   - Khi cơ sở dữ liệu chưa có tài khoản admin nào, hệ thống sẽ tự động hiển thị form khởi tạo tài khoản Quản trị viên gốc (Root Admin).

---

## Cấu Trúc Thư Mục

```text
├── admin_accounts_tabs/    # Các tab chức năng trong trang quản trị
├── assets/                 # Tài nguyên tĩnh (CSS, JS, hình ảnh, icon)
├── dashboard_tabs/         # Các tab trên trang tổng quan bệnh nhân & bác sĩ
├── logs/                   # Thư mục ghi log hệ thống
├── storage/                # Dữ liệu runtime (sessions, audit, rate limits, backups)
├── uploads/                # File tải lên từ bệnh nhân và kết quả khám bệnh (PDF)
├── tools/                  # Script & công cụ bổ trợ
├── config.php              # Cấu hình trung tâm & nạp secrets an toàn
├── database.sql            # Bản thiết kế cơ sở dữ liệu sạch (DDL)
├── hospital_full_ALL.secrets.example.php # File mẫu cấu hình secrets
├── index.php               # Trang chủ cổng thông tin phòng khám
└── README.md               # Tài liệu hướng dẫn dự án
```
