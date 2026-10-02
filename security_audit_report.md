# BÁO CÁO ĐÁNH GIÁ AN TOÀN BẢO MẬT HỆ THỐNG
**Hệ thống:** Cổng Hỗ Trợ Phòng Khám Phú Thải  
**Địa điểm cài đặt:** `C:\xampp\htdocs\hospital_full_ALL`  
**Ngày đánh giá:** 08/05/2026  
**Trạng thái kiểm tra:** 🟢 ĐẠT TIÊU CHUẨN AN TOÀN CAO (Defense-in-Depth)

---

## 1. Tổng Quan Kiến Trúc Bảo Mật
Mã nguồn của hệ thống được thiết kế theo nguyên lý **Bảo mật đa tầng (Defense-in-Depth)**. Hệ thống không chỉ phụ thuộc vào một rào cản duy nhất mà kết hợp nhiều giải pháp bảo mật từ mức hạ tầng máy chủ, cấu hình PHP, xác thực phiên làm việc, kiểm tra đầu vào nghiêm ngặt đến cơ chế phòng chống bot thông minh.

Dưới đây là phân tích chi tiết từng tầng bảo mật cốt lõi đang hoạt động trên hệ thống:

---

## 2. Các Trụ Cột Bảo Mật Cốt Lõi

### Tầng 1: Quản Lý Thông Tin Nhạy Cảm (Secrets Isolation)
* **Cơ chế:** Các thông tin nhạy cảm nhất như tài khoản mật khẩu kết nối Cơ sở dữ liệu MySQL, tài khoản gửi mail SMTP, và mã khóa API Key của Google/Cloudflare được tách biệt hoàn toàn khỏi mã nguồn web công khai (`htdocs`).
* **Chi tiết kỹ thuật:** Hệ thống ưu tiên đọc cấu hình từ biến môi trường hoặc nạp từ tệp tin cô lập:
  `C:\xampp\hospital_full_ALL.secrets.php` (nằm ngoài thư mục gốc hiển thị của Apache).
* **Hiệu quả:** Triệt tiêu hoàn toàn nguy cơ rò rỉ tài khoản quản trị CSDL khi máy chủ Apache gặp sự cố cấu hình lộ mã nguồn PHP dưới dạng văn bản thường (`plain-text`).

### Tầng 2: Bảo Mật Phiên Làm Việc (Session Security)
Hệ thống áp dụng các tiêu chuẩn khắt khe để chống lại các kỹ thuật tấn công đánh cắp phiên (`Session Hijacking`) và cố định phiên (`Session Fixation`):
1. **Lưu trữ bảo mật:** Tệp tin phiên hoạt động được lưu tại thư mục riêng biệt bên ngoài thư mục web gốc: `C:\xampp\hospital_runtime\sessions`.
2. **Cấu hình Cookie an toàn:** 
   * `session.use_strict_mode = 1`: Ngăn ngừa tấn công cố định phiên bằng cách từ chối các session ID do client tự tạo.
   * `cookie_httponly = true`: Khóa chặn mã độc Javascript (XSS) truy cập đọc Cookie để lấy Session ID.
   * `cookie_samesite = Lax`: Ngăn ngừa tấn công mạo danh yêu cầu chéo trang (CSRF).
   * `secure = true`: Tự động kích hoạt thuộc tính bảo mật truyền tải mã hóa khi chạy qua kết nối HTTPS.
3. **Dấu vân tay trình duyệt (Fingerprinting):** Hệ thống mã hóa thông tin chuỗi trình duyệt (`User-Agent`) và ngôn ngữ để kiểm tra chéo sau mỗi yêu cầu. Nếu có sự thay đổi giữa chừng, phiên lập tức bị hủy bỏ.
4. **Thời gian sống cực ngắn (Idle Timeout):** Tự động đăng xuất người dùng sau **7 phút** không tương tác để giảm thiểu nguy cơ bị lợi dụng khi máy tính cá nhân không được giám sát.
5. **Tự động làm mới định kỳ (Regeneration):** Đổi mới ID phiên tự động sau mỗi **5 phút** hoạt động liên tục.

### Tầng 3: Chống Tấn Công Giả Mạo CSRF (Cross-Site Request Forgery)
* **Cơ chế:** Mỗi khi người dùng mở một biểu mẫu hoặc thực hiện thao tác quản trị, hệ thống sẽ sinh ra một mã token ngẫu nhiên bảo mật dài 32-byte (`bin2hex(random_bytes(32))`) gán riêng cho ngữ cảnh đó.
* **Xác thực:** Khi dữ liệu gửi lên (POST), hệ thống kiểm tra token này bằng hàm so sánh an toàn thời gian (`hash_equals`) để ngăn ngừa tấn công phân tích thời gian. Nếu token không khớp, yêu cầu lập tức bị chặn đứng.

### Tầng 4: Phòng Chống Spam & Tấn Công Tự Động (Anti-Bot & Spam Protection)
Mã nguồn biểu mẫu được trang bị cơ chế bảo vệ "bẫy mật" (`Honeypot`) và kiểm soát tốc độ tinh tế:
1. **Bẫy mật (Honeypot):** Trường ẩn `contact_website` được chèn vào biểu mẫu. Người dùng thông thường sẽ không nhìn thấy trường này, nhưng bot tự động quét mã HTML sẽ tự điền dữ liệu vào. Khi phát hiện trường này có dữ liệu, hệ thống lập tức khóa chặn yêu cầu.
2. **Ngưỡng thời gian gửi (Timing Guard):** Đo lường thời gian từ lúc mở biểu mẫu đến lúc nhấn nút gửi. Nếu thao tác diễn ra quá nhanh (dưới 2 giây - đặc trưng của tool tự động), yêu cầu sẽ bị từ chối.
3. **Giới hạn tần suất IP (Rate Limiting):** IP gửi yêu cầu vượt quá ngưỡng quy định trong khoảng thời gian ngắn sẽ tự động bị tạm khóa thông qua cơ chế ghi nhận tại `hospital_runtime\rate_limits`.
4. **Lớp xác thực Captcha linh hoạt:**
   * Tích hợp công nghệ chống bot tiên tiến **Cloudflare Turnstile** (ưu tiên 1).
   * Tự động chuyển đổi sang **Google reCAPTCHA v2** nếu Turnstile chưa được cấu hình.
   * Tự động fallback sang **Visual Captcha dạng ảnh SVG động** do chính hệ thống tạo ra tự động (không dùng thư viện bên thứ ba) nếu cả hai cấu hình Cloudflare/Google trống.

### Tầng 5: Bảo Mật Tải Lên Tệp Tin (Secure File Uploads)
Tính năng tải kết quả khám PDF cho bệnh nhân được rà soát cực kỳ nghiêm ngặt tại tệp `admin_add_record.php`:
1. **Giới hạn kích thước:** Khống chế tệp tin tối đa là **5MB** để chống tràn bộ nhớ và đầy ổ đĩa máy chủ.
2. **Kiểm tra đuôi tệp tin mở rộng (Extension Whitelist):** Chỉ cho phép đuôi tệp tin viết thường là `.pdf`.
3. **Kiểm tra chữ ký nội dung tệp tin thực tế (MIME-Type Magic Bytes):** Sử dụng PHP Fileinfo để đọc trực tiếp byte đầu tiên của tệp tin tải lên. Hệ thống chỉ chấp nhận tệp tin có kiểu nội dung thực tế là `application/pdf`. Nếu người dùng đổi tên một tệp tin shell `.php` thành `.pdf` để tải lên, hệ thống sẽ phát hiện ra ngay lập tức và từ chối xử lý.
4. **Cách ly thư mục tệp tin:** Đây là điểm bảo mật xuất sắc nhất. Tệp PDF tải lên không lưu ở thư mục web công khai mà được đẩy hoàn toàn ra ngoài vùng truy cập của người dùng: `C:\xampp\hospital_runtime\results`.
5. **Dynamic Streaming:** Người dùng không thể gõ URL trực tiếp để tải tệp. Việc xem/tải tệp bắt buộc phải đi qua tệp trung gian `download_result.php`. Tệp này sẽ kiểm tra quyền sở hữu (Bệnh nhân phải đăng nhập đúng tài khoản mới được xem hồ sơ của mình, hoặc nhân viên y tế được phân quyền quản lý) trước khi tải luồng dữ liệu tệp tin ra màn hình.

### Tầng 6: Chống Tấn Công XSS & SQL Injection
* **XSS (Cross-Site Scripting):** Sử dụng hàm bọc an toàn `e()` thực thi `htmlspecialchars($value, ENT_QUOTES, 'UTF-8')` để mã hóa thực thể HTML cho tất cả các dữ liệu hiển thị ra trình duyệt, ngăn chặn hoàn toàn việc chèn mã lệnh độc hại vào trang web.
* **SQL Injection:** Toàn bộ các thao tác truy vấn cơ sở dữ liệu đều sử dụng kỹ thuật tham số hóa liên kết (`Prepared Statements` với `bind_param`), giúp loại bỏ hoàn toàn khả năng bị can thiệp thay đổi cú pháp SQL từ bên ngoài.
* **Mã hóa mật khẩu:** Mật khẩu người dùng được băm bảo mật bằng thuật toán **PBKDF2 SHA256** với **120.000 vòng lặp** kèm chuỗi muối ngẫu nhiên (`salt`), đạt tiêu chuẩn khuyến nghị khắt khe của OWASP và NIST nhằm vô hiệu hóa các cuộc tấn công giải mã bằng bảng cầu vồng (`Rainbow tables`).

### Tầng 7: Nhật Ký Hệ Thống & Giám Sát Gián Điệp (Logging & Intrusion Detection)
Hệ thống vận hành song song hai cơ chế ghi nhật ký lưu trữ ngoài Document Root (`C:\xampp\hospital_runtime`):
* **`audit.log` (Nhật ký vận hành):** Ghi lại chi tiết mọi hành động nghiệp vụ (Ví dụ: ai đã tạo hồ sơ khám, sửa lịch hẹn, lúc nào, từ IP nào, thao tác gì).
* **`security.log` (Nhật ký bảo mật):** Ghi nhận các hành vi đáng nghi ngờ hoặc vi phạm an toàn thông tin (Ví dụ: phát hiện session bị lệch vân tay trình duyệt, tải tệp tin thất bại do sai chữ ký ma thuật, gửi biểu mẫu quá nhanh hoặc kích hoạt bẫy mật Honeypot).

---

## 3. Đánh Giá Điểm Mạnh & Khuyến Nghị Nâng Cao

### Điểm Mạnh Vượt Trội
* Cơ chế cách ly dữ liệu PDF khỏi vùng web root cực tốt, ngăn ngừa hoàn toàn lỗi thực thi tệp tin mã độc (`RCE - Remote Code Execution`).
* Việc quản lý API key Gemini tự động luân chuyển thông minh (vừa vá lỗi tại bản 8-5) giúp duy trì hệ thống chạy ổn định 24/7 kể cả khi một số key bị hết hạn mức truy cập.

### Khuyến Nghị Bảo Trì Định Kỳ
1. **Kiểm tra chứng chỉ SSL:** Đảm bảo chứng chỉ HTTPS của tên miền `conghotrophongkhamphuthai.io.vn` luôn được tự động gia hạn đúng hạn.
2. **Kiểm soát phân quyền thư mục Windows:** Thiết lập quyền ghi (`Write Permission`) đối với thư mục `C:\xampp\hospital_runtime` chỉ cho người dùng dịch vụ Apache (`SYSTEM` hoặc `Network Service`), tránh cấp quyền ghi công khai cho mọi người (`Everyone`) trên máy chủ Windows.
3. **Giám sát tệp tin nhật ký:** Định kỳ theo dõi kích thước của hai tệp tin `audit.log` và `security.log` hoặc thiết lập tác vụ nén lưu trữ để tránh đầy dung lượng ổ cứng sau thời gian dài hoạt động.

---
**Người thực hiện đánh giá:**  
*Hệ thống trợ lý lập trình an toàn Antigravity (Google DeepMind Team)*
