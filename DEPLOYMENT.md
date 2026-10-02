# Hướng Dẫn Triển Khai Đồng Thời Hệ Thống Web Phòng Khám Trên 3 Nền Tảng
### (Cloud VPS/cPanel - Render.com PaaS - TiDB Cloud Database)

Tài liệu này cung cấp hướng dẫn chi tiết từng bước để triển khai hệ thống Web Phòng Khám Đa Khoa Phú Thái trên kiến trúc phân tán hiện đại:
- **Tầng Cơ sở dữ liệu (Database Tier):** TiDB Cloud Serverless (MySQL 8.0 phân tán, bảo mật TLS/SSL bắt buộc, cổng 4000).
- **Tầng Ứng dụng PaaS (App Tier 1):** Render.com (Docker Container tự động CI/CD từ GitHub).
- **Tầng Ứng dụng Cloud VPS (App Tier 2):** Cloud VPS / cPanel (Apache / Nginx, lưu trữ file tĩnh bền vững).

---

## NỀN TẢNG 1: CƠ SỞ DỮ LIỆU TIDB CLOUD

### 1. Khởi tạo cụm và lấy thông số kết nối
1. Truy cập [TiDB Cloud Console](https://tidbcloud.com/) và chọn cụm database của bạn (ví dụ: `10894412101483627086`).
2. Nhấn nút **Connect** ở góc trên bên phải:
   - **Host:** Dạng `gateway01.<region>.prod.aws.tidbcloud.com` (ví dụ: `gateway01.ap-southeast-1.prod.aws.tidbcloud.com`).
   - **Port:** `4000`.
   - **User:** Dạng `<prefix>.root` (ví dụ: `2wXyZ123.root`).
   - **Password:** Mật khẩu của cluster (nếu chưa có hoặc quên, bấm *Reset password*).
   - **Database Name:** `benhvien_support`.

### 2. Import dữ liệu lên TiDB Cloud
- **Cách 1: Dùng lệnh MySQL CLI từ máy tính:**
  ```bash
  mysql -h <TIDB_HOST> -P 4000 -u '<TIDB_USER>' -p --ssl-mode=VERIFY_IDENTITY -e "CREATE DATABASE IF NOT EXISTS benhvien_support CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
  mysql -h <TIDB_HOST> -P 4000 -u '<TIDB_USER>' -p --ssl-mode=VERIFY_IDENTITY benhvien_support < "benhvien_support (1).sql"
  ```
- **Cách 2: Sử dụng SQL Editor trên Web Console:**
  - Vào tab **SQL Editor** trên TiDB Cloud Console -> Mở file `database.sql` (hoặc `benhvien_support (1).sql`), dán vào và nhấn **Run** để khởi tạo cấu trúc và nạp dữ liệu.

---

## NỀN TẢNG 2: TRIỂN KHAI TRÊN RENDER.COM (PAAS CONTAINER)

Ứng dụng đã được đóng gói sẵn với `Dockerfile` tối ưu hóa (PHP 8.2-Apache) và tệp mô tả hạ tầng `render.yaml`.

### Cách 1: Triển khai tự động 1-Click bằng Blueprint (`render.yaml`)
1. Đăng nhập [Render.com Dashboard](https://dashboard.render.com/).
2. Chọn **Blueprints** -> Bấm **New Blueprint Instance**.
3. Kết nối với GitHub repository: `https://github.com/pcnttphongkhamdakhoaphuthai-wq/web`.
4. Render sẽ tự động nhận diện tệp `render.yaml` và nạp sẵn cấu hình Web Service.
5. Điền giá trị cho các biến môi trường kết nối TiDB Cloud:
   - `HOSPITAL_DB_HOST`: Host của TiDB Cloud
   - `HOSPITAL_DB_PORT`: `4000`
   - `HOSPITAL_DB_NAME`: `benhvien_support`
   - `HOSPITAL_DB_USER`: User TiDB Cloud (có tiền tố)
   - `HOSPITAL_DB_PASSWORD`: Mật khẩu TiDB Cloud
   - `HOSPITAL_DB_SSL`: `true`
   - `HOSPITAL_GEMINI_API_KEY`: API Key Google Gemini AI
   - `HOSPITAL_SMTP_PASSWORD`: Mật khẩu ứng dụng Gmail (16 ký tự)
6. Nhấn **Apply**, Render sẽ tự động build Docker image và khởi chạy website.

### Cách 2: Triển khai thủ công (Web Service)
1. Trên Render Dashboard, bấm **New +** -> chọn **Web Service**.
2. Chọn repository GitHub: `pcnttphongkhamdakhoaphuthai-wq/web`.
3. Cấu hình dịch vụ:
   - **Name:** `web-phongkham`
   - **Region:** `Singapore` (để cùng khu vực với TiDB Cloud, giảm thiểu tối đa độ trễ mạng).
   - **Environment:** `Docker` (Render sẽ tự động dùng `Dockerfile` trong repo).
   - **Branch:** `main`.
4. Vào tab **Environment Variables**, thêm các biến môi trường như mục trên.
5. *(Khuyên dùng)* Vào tab **Disks**, thêm 1 Persistent Disk:
   - **Name:** `storage-disk`
   - **Mount Path:** `/var/www/html/storage`
   - **Size:** `1 GB` (giúp giữ lại phiên đăng nhập, file audit và lịch sử khám khi container khởi động lại).
6. Nhấn **Create Web Service**.

---

## NỀN TẢNG 3: TRIỂN KHAI TRÊN CLOUD VPS / CPANEL

### 1. Triển khai trên cPanel Hosting
1. Đăng nhập cPanel -> **File Manager** -> Mở thư mục gốc `public_html`.
2. Tải toàn bộ mã nguồn lên `public_html` (hoặc dùng tính năng **Git Version Control** trong cPanel để clone từ `https://github.com/pcnttphongkhamdakhoaphuthai-wq/web.git`).
3. Tạo tệp `hospital_full_ALL.secrets.php` tại thư mục cha của `public_html` (ngang hàng với `public_html` để người ngoài không thể tải về qua web).
4. Điền thông số TiDB Cloud hoặc Database cục bộ của cPanel vào tệp secrets vừa tạo.
5. Tệp `.htaccess` đã được cấu hình sẵn tính năng Pretty URLs, Security Headers và chặn các tệp tin nhạy cảm.

### 2. Triển khai trên Cloud Linux VPS (Apache hoặc Nginx)

#### Với Apache:
1. Cài đặt PHP 8.2 và các module:
   ```bash
   sudo apt update
   sudo apt install apache2 php8.2 php8.2-mysqli php8.2-curl php8.2-mbstring php8.2-zip php8.2-gd php8.2-xml ca-certificates -y
   sudo a2enmod rewrite headers deflate
   ```
2. Đưa mã nguồn vào `/var/www/html/web`.
3. Phân quyền thư mục:
   ```bash
   sudo chown -R www-data:www-data /var/www/html/web
   sudo chmod -R 775 /var/www/html/web/storage /var/www/html/web/uploads /var/www/html/web/assets
   ```
4. Bật `AllowOverride All` trong VirtualHost của Apache để file `.htaccess` có hiệu lực.
5. Khởi động lại Apache: `sudo systemctl restart apache2`.

#### Với Nginx:
1. Cài đặt Nginx và PHP-FPM:
   ```bash
   sudo apt install nginx php8.2-fpm php8.2-mysqli php8.2-curl php8.2-mbstring php8.2-zip php8.2-gd ca-certificates -y
   ```
2. Copy tệp cấu hình mẫu [nginx.conf](file:///f:/web%20ph%C3%B2ng%20kh%C3%A1m/nginx.conf) vào `/etc/nginx/sites-available/web_phongkham`.
3. Kích hoạt và kiểm tra cấu hình:
   ```bash
   sudo ln -s /etc/nginx/sites-available/web_phongkham /etc/nginx/sites-enabled/
   sudo nginx -t
   sudo systemctl restart nginx
   ```

---

## TỔNG HỢP DANH MỤC BIẾN MÔI TRƯỜNG TOÀN HỆ THỐNG

| Tên biến môi trường | Mô tả | Giá trị mẫu |
|---|---|---|
| `HOSPITAL_DB_HOST` | Địa chỉ máy chủ Database | `gateway01.ap-southeast-1.prod.aws.tidbcloud.com` hoặc `localhost` |
| `HOSPITAL_DB_PORT` | Cổng kết nối Database | `4000` (TiDB Cloud) hoặc `3306` (MySQL thường) |
| `HOSPITAL_DB_NAME` | Tên cơ sở dữ liệu | `benhvien_support` |
| `HOSPITAL_DB_USER` | Tên người dùng Database | `2wXyZ...root` hoặc `root` |
| `HOSPITAL_DB_PASSWORD` | Mật khẩu Database | *(Mật khẩu an toàn)* |
| `HOSPITAL_DB_SSL` | Bật/tắt SSL cho Database | `true` (TiDB bắt buộc `true`) |
| `HOSPITAL_FORCE_HTTPS` | Tự động chuyển hướng HTTPS | `true` |
| `HOSPITAL_TRUSTED_PROXIES` | Danh sách IP Proxy tin cậy | `*`, `private` (cho Render/Cloudflare) |
| `HOSPITAL_GEMINI_API_KEY` | API Key Chatbot AI | `AIzaSy...` |
| `HOSPITAL_SMTP_HOST` | Máy chủ gửi mail SMTP | `smtp.gmail.com` |
| `HOSPITAL_SMTP_PORT` | Cổng SMTP | `465` (SSL) hoặc `587` (TLS) |
| `HOSPITAL_SMTP_USERNAME` | Email gửi thư phòng khám | `pcnttphongkhamdakhoaphuthai@gmail.com` |
| `HOSPITAL_SMTP_PASSWORD` | Mật khẩu ứng dụng Gmail (16 ký tự) | `chtmizpukofpnqix` |
