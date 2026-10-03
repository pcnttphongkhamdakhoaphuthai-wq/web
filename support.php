<?php
declare(strict_types=1);

require_once 'config.php';

$sentSuccess = false;
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $guardError = validate_form_guard('public_support_request', 10, 600, 0);
    $fullName = normalize_single_line_input($_POST['full_name'] ?? '');
    $contact = normalize_single_line_input($_POST['contact'] ?? '');
    $category = normalize_single_line_input($_POST['category'] ?? '');
    $message = trim((string) ($_POST['message'] ?? ''));

    if ($guardError !== null) {
        $errorMessage = $guardError;
    } elseif ($fullName === '' || $contact === '' || $message === '') {
        $errorMessage = 'Vui lòng điền đầy đủ họ tên, thông tin liên hệ và nội dung cần hỗ trợ.';
    } elseif (mb_strlen($message) < 10) {
        $errorMessage = 'Nội dung cần hỗ trợ vui lòng nhập tối thiểu 10 ký tự để nhân viên có thể hỗ trợ tốt nhất.';
    } else {
        // Ghi log yêu cầu hỗ trợ vào file audit/support log an toàn
        $logData = [
            'time' => date('Y-m-d H:i:s'),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'full_name' => $fullName,
            'contact' => $contact,
            'category' => $category,
            'message' => $message,
        ];
        
        $logFile = APP_SECURITY_ROOT . DIRECTORY_SEPARATOR . 'support_requests_' . date('Y_m') . '.log';
        @file_put_contents($logFile, json_encode($logData, JSON_UNESCAPED_UNICODE) . PHP_EOL, FILE_APPEND | LOCK_EX);
        
        audit_log('public_support_submitted', [
            'full_name' => $fullName,
            'contact' => $contact,
            'category' => $category,
        ]);

        $sentSuccess = true;
    }
}

$clinicName = site_setting('clinic_name', 'Phòng khám đa khoa Phú Thái');
$hotline = site_setting('support_hotline', '0208 628 9888');
$hotlineCskh = '0963 485 651';
$email = site_setting('support_email', 'pcnttphongkhamdakhoaphuthai@gmail.com');
$address = site_setting('clinic_address', 'Xóm Hoà Bình 2, xã Phú Bình, tỉnh Thái Nguyên');

render_header('Trung tâm Hỗ trợ & Hướng dẫn người bệnh · ' . $clinicName, 'support');
?>

<div class="wrap">
  <!-- TIÊU ĐỀ TRANG HỖ TRỢ -->
  <section class="section card" style="background:linear-gradient(135deg, #f0f7fb 0%, #ffffff 100%);">
    <div class="panel-title" style="margin-bottom:0;">
      <div>
        <div style="font-size:12px;font-weight:700;color:var(--primary);text-transform:uppercase;letter-spacing:1px;margin-bottom:6px;">
          TRUNG TÂM TRỢ GIÚP NGƯỜI BỆNH
        </div>
        <h1 class="section-title" style="font-size:28px;">Hỗ trợ & Hướng dẫn sử dụng dịch vụ</h1>
        <p class="section-lead" style="margin-bottom:0;">
          Giải đáp các thắc mắc về tài khoản người bệnh, quy trình tra cứu kết quả khám, thủ tục BHYT và tiếp nhận yêu cầu hỗ trợ 24/7.
        </p>
      </div>
      <div>
        <a class="btn btn-outline" href="index.php">← Quay lại trang chủ</a>
      </div>
    </div>
  </section>

  <!-- KÊNH LIÊN HỆ TRỰC TIẾP -->
  <section class="section">
    <div class="grid grid-3">
      <div class="card" style="margin-bottom:0;border-top:4px solid var(--primary);">
        <div style="font-size:28px;margin-bottom:10px;">📞</div>
        <h3 style="font-size:17px;font-weight:700;margin:0 0 6px;">Hotline tư vấn</h3>
        <p style="font-size:13.5px;color:var(--muted);margin:0 0 12px;">Hỗ trợ chuyên môn y tế và quy trình khám bệnh.</p>
        <div style="font-size:18px;font-weight:800;color:var(--primary);">
          <a href="tel:02086289888">0208 628 9888</a>
        </div>
        <div style="font-size:12px;color:#64748b;margin-top:4px;">Từ 7:00 – 17:00 hàng ngày</div>
      </div>

      <div class="card" style="margin-bottom:0;border-top:4px solid #10b981;">
        <div style="font-size:28px;margin-bottom:10px;">🚑</div>
        <h3 style="font-size:17px;font-weight:700;margin:0 0 6px;">Cấp cứu & Chăm sóc khách hàng</h3>
        <p style="font-size:13.5px;color:var(--muted);margin:0 0 12px;">Đường dây nóng tiếp nhận xử lý sự cố tài khoản khẩn cấp.</p>
        <div style="font-size:18px;font-weight:800;color:#10b981;">
          <a href="tel:0963485651">0963 485 651</a>
        </div>
        <div style="font-size:12px;color:#64748b;margin-top:4px;">Hỗ trợ 24/7 qua điện thoại & Zalo</div>
      </div>

      <div class="card" style="margin-bottom:0;border-top:4px solid #0284c7;">
        <div style="font-size:28px;margin-bottom:10px;">✉️</div>
        <h3 style="font-size:17px;font-weight:700;margin:0 0 6px;">Hòm thư điện tử</h3>
        <p style="font-size:13.5px;color:var(--muted);margin:0 0 12px;">Tiếp nhận đóng góp ý kiến và phản ánh dịch vụ.</p>
        <div style="font-size:14px;font-weight:700;color:#0284c7;word-break:break-all;">
          <a href="mailto:<?= e($email) ?>"><?= e($email) ?></a>
        </div>
        <div style="font-size:12px;color:#64748b;margin-top:4px;">Phản hồi trong vòng 24 giờ</div>
      </div>
    </div>
  </section>

  <!-- BỐ CỤC 2 CỘT: CÂU HỎI THƯỜNG GẶP & BIỂU MẪU GỬI YÊU CẦU -->
  <div class="grid grid-2" style="align-items:flex-start;">
    
    <!-- CỘT 1: CÂU HỎI THƯỜNG GẶP -->
    <section class="section card" style="margin-bottom:0;">
      <h2 style="font-size:20px;font-weight:700;color:#0f2942;margin:0 0 16px;">
        Các sự cố và câu hỏi thường gặp
      </h2>

      <div style="display:grid;gap:14px;">
        <!-- Q1 -->
        <div style="padding:14px 16px;background:var(--soft);border-radius:12px;border:1px solid var(--border);">
          <h4 style="margin:0 0 6px;color:#0f2942;font-size:15px;">1. Quên mật khẩu hoặc không đăng nhập được?</h4>
          <p style="margin:0;font-size:13.5px;color:#475569;line-height:1.6;">
            Quý khách bấm vào liên kết <a href="forgot_password.php" style="color:var(--primary);font-weight:600;text-decoration:underline;">Quên mật khẩu</a> trên trang đăng nhập, điền số CCCD và Email đã đăng ký để nhận mã OTP đặt lại mật khẩu mới. Nếu không nhận được mã, vui lòng gọi hotline <strong>0208 628 9888</strong> để được hỗ trợ cấp lại ngay.
          </p>
        </div>

        <!-- Q2 -->
        <div style="padding:14px 16px;background:var(--soft);border-radius:12px;border:1px solid var(--border);">
          <h4 style="margin:0 0 6px;color:#0f2942;font-size:15px;">2. Chưa có tài khoản hoặc chưa từng khám tại phòng khám?</h4>
          <p style="margin:0;font-size:13.5px;color:#475569;line-height:1.6;">
            Quý khách có thể tự tạo tài khoản mới tại trang <a href="register.php" style="color:var(--primary);font-weight:600;text-decoration:underline;">Đăng ký tài khoản</a> bằng số CCCD và thông tin cá nhân. Khi đến khám trực tiếp, nhân viên quầy tiếp đón sẽ đối chiếu hồ sơ để đồng bộ kết quả khám vào tài khoản của quý khách.
          </p>
        </div>

        <!-- Q3 -->
        <div style="padding:14px 16px;background:var(--soft);border-radius:12px;border:1px solid var(--border);">
          <h4 style="margin:0 0 6px;color:#0f2942;font-size:15px;">3. Làm thế nào để xem kết quả xét nghiệm và đơn thuốc?</h4>
          <p style="margin:0;font-size:13.5px;color:#475569;line-height:1.6;">
            Sau khi đăng nhập thành công bằng số CCCD, hệ thống sẽ đưa quý khách vào mục <strong>Kết quả khám</strong>. Tại đây, quý khách có thể xem tóm tắt chẩn đoán của bác sĩ, danh mục thuốc được kê và nút tải tệp PDF kết quả có đóng dấu điện tử của phòng khám.
          </p>
        </div>

        <!-- Q4 -->
        <div style="padding:14px 16px;background:var(--soft);border-radius:12px;border:1px solid var(--border);">
          <h4 style="margin:0 0 6px;color:#0f2942;font-size:15px;">4. Khám bệnh Bảo hiểm Y tế cần chuẩn bị gì?</h4>
          <p style="margin:0;font-size:13.5px;color:#475569;line-height:1.6;">
            Người bệnh chỉ cần mang theo Căn cước công dân gắn chip (đã tích hợp BHYT trên ứng dụng VNeID) hoặc thẻ BHYT giấy còn thời hạn kèm giấy tờ tùy thân có ảnh khi đến làm thủ tục tại quầy tiếp đón.
          </p>
        </div>
      </div>
    </section>

    <!-- CỘT 2: BIỂU MẪU TIẾP NHẬN YÊU CẦU -->
    <section class="section card" style="margin-bottom:0;">
      <h2 style="font-size:20px;font-weight:700;color:#0f2942;margin:0 0 8px;">
        Gửi yêu cầu hỗ trợ trực tuyến
      </h2>
      <p style="font-size:13.5px;color:var(--muted);margin:0 0 20px;">
        Nếu bạn gặp sự cố khi sử dụng cổng người bệnh, hãy để lại thông tin dưới đây để bộ phận hỗ trợ liên hệ xử lý.
      </p>

      <?php if ($sentSuccess): ?>
        <div style="padding:18px 20px;background:#dcfce7;border:1.5px solid #86efac;border-radius:14px;color:#14532d;margin-bottom:20px;">
          <h3 style="font-size:16px;font-weight:700;margin:0 0 6px;">✓ Yêu cầu của bạn đã được gửi thành công!</h3>
          <p style="margin:0;font-size:14px;line-height:1.5;">
            Bộ phận chăm sóc khách hàng của Phòng khám đa khoa Phú Thái đã tiếp nhận thông tin và sẽ liên hệ hỗ trợ bạn qua số điện thoại/email trong thời gian sớm nhất.
          </p>
        </div>
      <?php else: ?>

        <?php if ($errorMessage !== ''): ?>
          <div style="padding:12px 16px;background:#fee2e2;border:1.5px solid #fca5a5;border-radius:12px;color:#991b1b;margin-bottom:16px;font-size:14px;">
            <?= e($errorMessage) ?>
          </div>
        <?php endif; ?>

        <form method="post" style="display:grid;gap:16px;" id="supportForm">
          <?php render_form_guard('public_support_request'); ?>

          <div>
            <label for="full_name">Họ và tên của bạn <span style="color:var(--danger)">*</span></label>
            <input id="full_name" name="full_name" type="text" placeholder="Ví dụ: Nguyễn Văn A" value="<?= e($_POST['full_name'] ?? '') ?>" required>
          </div>

          <div>
            <label for="contact">Số điện thoại hoặc CCCD <span style="color:var(--danger)">*</span></label>
            <input id="contact" name="contact" type="text" inputmode="numeric" placeholder="Nhập số điện thoại hoặc 12 số CCCD" value="<?= e($_POST['contact'] ?? '') ?>" required>
          </div>

          <div>
            <label for="category">Vấn đề cần hỗ trợ</label>
            <select id="category" name="category">
              <option value="Lỗi đăng nhập / Mật khẩu">Sự cố đăng nhập hoặc quên mật khẩu</option>
              <option value="Tra cứu kết quả khám">Hỏi về kết quả khám / đơn thuốc</option>
              <option value="Tư vấn khám & Đặt lịch">Tư vấn dịch vụ khám & Đặt lịch hẹn</option>
              <option value="Thủ tục Bảo hiểm y tế">Thủ tục quyền lợi Bảo hiểm Y tế (BHYT)</option>
              <option value="Khác">Vấn đề khác</option>
            </select>
          </div>

          <div>
            <label for="message">Nội dung chi tiết <span style="color:var(--danger)">*</span></label>
            <textarea id="message" name="message" rows="4" placeholder="Mô tả cụ thể khó khăn hoặc thông tin cần phòng khám hỗ trợ..." required><?= e($_POST['message'] ?? '') ?></textarea>
          </div>

          <div>
            <button class="btn" type="submit" id="submitSupportBtn" style="width:100%;height:48px;font-size:15px;">
              <span id="submitSupportText">Gửi yêu cầu hỗ trợ</span> ➔
            </button>
          </div>
        </form>
      <?php endif; ?>
    </section>

  </div>
</div>

<script>
(function() {
  var form = document.getElementById('supportForm');
  var btn = document.getElementById('submitSupportBtn');
  var txt = document.getElementById('submitSupportText');
  if (form && btn) {
    form.addEventListener('submit', function() {
      btn.disabled = true;
      if (txt) txt.textContent = 'Đang gửi yêu cầu…';
    });
  }
})();
</script>

<?php render_footer(); ?>
