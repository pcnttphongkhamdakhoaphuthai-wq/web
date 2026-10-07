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

<div class="wrap" style="padding-top: 24px; padding-bottom: 48px;">
  <!-- TIÊU ĐỀ TRANG HỖ TRỢ -->
  <section class="section" style="padding: 24px 0 36px;">
    <div style="background: linear-gradient(113deg, #eaf7ff 0%, #edf8ff 45%, #e3f3ff 100%); border: 1px solid var(--line); border-radius: 20px; padding: 36px 32px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px;">
      <div>
        <span class="section-kicker">TRUNG TÂM TRỢ GIÚP NGƯỜI BỆNH</span>
        <h1 style="font-size: 32px; font-weight: 700; color: var(--ink); margin: 0 0 10px;">Hỗ trợ & Hướng dẫn sử dụng dịch vụ</h1>
        <p style="font-size: 16px; color: var(--muted); margin: 0; max-width: 680px; line-height: 1.6;">
          Giải đáp thắc mắc về tài khoản người bệnh, quy trình tra cứu hồ sơ kết quả, thủ tục BHYT và tiếp nhận yêu cầu hỗ trợ y tế kịp thời.
        </p>
      </div>
      <div>
        <a class="btn btn-outline" href="index.php">← Quay lại trang chủ</a>
      </div>
    </div>
  </section>

  <!-- KÊNH LIÊN HỆ TRỰC TIẾP -->
  <section class="section" style="padding: 0 0 36px;">
    <div class="grid grid-3">
      <div class="service-card" style="border-top: 4px solid var(--blue);">
        <div class="service-icon-box">
          <svg class="icon" aria-hidden="true"><use href="#i-phone"/></svg>
        </div>
        <h3 class="service-title">Hotline tiếp đón</h3>
        <p class="service-desc">Hỗ trợ chuyên môn y tế, đặt lịch hẹn và hướng dẫn thủ tục khám bệnh.</p>
        <div style="font-size: 20px; font-weight: 700; color: var(--blue); margin-bottom: 4px;">
          <a href="tel:02086289888">0208 628 9888</a>
        </div>
        <div style="font-size: 13px; color: var(--muted);">Từ 07:00 – 17:30 tất cả các ngày trong tuần</div>
      </div>

      <div class="service-card" style="border-top: 4px solid #16a34a;">
        <div class="service-icon-box" style="background: #e8f8ed; color: #16a34a;">
          <svg class="icon" aria-hidden="true"><use href="#i-phone"/></svg>
        </div>
        <h3 class="service-title">Cấp cứu & CSKH</h3>
        <p class="service-desc">Đường dây nóng xử lý sự cố tài khoản và tiếp nhận ca bệnh khẩn cấp.</p>
        <div style="font-size: 20px; font-weight: 700; color: #16a34a; margin-bottom: 4px;">
          <a href="tel:0963485651">0963 485 651</a>
        </div>
        <div style="font-size: 13px; color: var(--muted);">Trực tiếp tiếp nhận cuộc gọi 24/7</div>
      </div>

      <div class="service-card" style="border-top: 4px solid #0284c7;">
        <div class="service-icon-box" style="background: #e0f2fe; color: #0284c7;">
          <svg class="icon" aria-hidden="true"><use href="#i-chat"/></svg>
        </div>
        <h3 class="service-title">Hòm thư điện tử</h3>
        <p class="service-desc">Tiếp nhận đóng góp ý kiến, phản ánh chất lượng và xác thực hồ sơ.</p>
        <div style="font-size: 15px; font-weight: 700; color: #0284c7; word-break: break-all; margin-bottom: 4px;">
          <a href="mailto:<?= e($email) ?>"><?= e($email) ?></a>
        </div>
        <div style="font-size: 13px; color: var(--muted);">Phản hồi thư trong vòng 24 giờ làm việc</div>
      </div>
    </div>
  </section>

  <!-- BỐ CỤC 2 CỘT: CÂU HỎI THƯỜNG GẶP & BIỂU MẪU GỬI YÊU CẦU -->
  <div class="grid grid-2" style="align-items: flex-start;">
    
    <!-- CỘT 1: CÂU HỎI THƯỜNG GẶP -->
    <section class="card" style="border-radius: 18px; padding: 28px;">
      <h2 style="font-size: 20px; font-weight: 700; color: var(--ink); margin: 0 0 18px;">
        Các thắc mắc và câu hỏi thường gặp
      </h2>

      <div style="display: grid; gap: 14px;">
        <!-- Q1 -->
        <div style="padding: 16px; background: var(--soft); border-radius: 12px; border: 1px solid #cce5f8;">
          <h4 style="margin: 0 0 6px; color: var(--ink); font-size: 15px;">1. Quên mật khẩu hoặc không đăng nhập được?</h4>
          <p style="margin: 0; font-size: 14px; color: var(--muted); line-height: 1.6;">
            Quý khách bấm vào liên kết <a href="forgot_password.php" class="inline-link" style="font-weight: 600;">Quên mật khẩu</a> trên trang đăng nhập, điền số CCCD để nhận mã OTP khôi phục mật khẩu mới. Nếu gặp khó khăn, vui lòng gọi hotline <strong>0208 628 9888</strong> để được hỗ trợ trực tiếp.
          </p>
        </div>

        <!-- Q2 -->
        <div style="padding: 16px; background: var(--soft); border-radius: 12px; border: 1px solid #cce5f8;">
          <h4 style="margin: 0 0 6px; color: var(--ink); font-size: 15px;">2. Chưa có tài khoản thì đăng ký như thế nào?</h4>
          <p style="margin: 0; font-size: 14px; color: var(--muted); line-height: 1.6;">
            Quý khách có thể tự tạo tài khoản tại trang <a href="register.php" class="inline-link" style="font-weight: 600;">Đăng ký tài khoản</a> bằng số CCCD và thông tin liên hệ. Khi đến khám trực tiếp, nhân viên tiếp đón sẽ đối chiếu hồ sơ để đồng bộ toàn bộ lịch sử khám vào tài khoản.
          </p>
        </div>

        <!-- Q3 -->
        <div style="padding: 16px; background: var(--soft); border-radius: 12px; border: 1px solid #cce5f8;">
          <h4 style="margin: 0 0 6px; color: var(--ink); font-size: 15px;">3. Làm thế nào để xem kết quả xét nghiệm và đơn thuốc?</h4>
          <p style="margin: 0; font-size: 14px; color: var(--muted); line-height: 1.6;">
            Sau khi đăng nhập thành công bằng số CCCD, hệ thống sẽ chuyển tới trang cá nhân. Quý khách bấm vào mục <strong>Kết quả khám</strong> để xem chi tiết chẩn đoán của bác sĩ, toa thuốc và tải file kết quả PDF về máy.
          </p>
        </div>

        <!-- Q4 -->
        <div style="padding: 16px; background: var(--soft); border-radius: 12px; border: 1px solid #cce5f8;">
          <h4 style="margin: 0 0 6px; color: var(--ink); font-size: 15px;">4. Khám bệnh Bảo hiểm Y tế cần chuẩn bị gì?</h4>
          <p style="margin: 0; font-size: 14px; color: var(--muted); line-height: 1.6;">
            Người bệnh chỉ cần xuất trình Căn cước công dân gắn chip (hoặc ứng dụng VNeID đã tích hợp thẻ BHYT), hoặc thẻ BHYT giấy còn thời hạn kèm giấy tờ tùy thân có ảnh khi làm thủ tục tại quầy tiếp đón.
          </p>
        </div>
      </div>
    </section>

    <!-- CỘT 2: BIỂU MẪU TIẾP NHẬN YÊU CẦU -->
    <section class="card" style="border-radius: 18px; padding: 28px;">
      <h2 style="font-size: 20px; font-weight: 700; color: var(--ink); margin: 0 0 8px;">
        Gửi yêu cầu hỗ trợ trực tuyến
      </h2>
      <p style="font-size: 14px; color: var(--muted); margin: 0 0 20px;">
        Nếu quý khách cần hỗ trợ kỹ thuật hoặc có thắc mắc cần giải đáp, hãy gửi thông tin để bộ phận chăm sóc khách hàng liên hệ lại.
      </p>

      <?php if ($sentSuccess): ?>
        <div class="flash-message flash-success">
          <svg class="icon" aria-hidden="true"><use href="#i-check"/></svg>
          <div>
            <strong>Yêu cầu của bạn đã được gửi thành công!</strong>
            <p style="margin: 4px 0 0; font-size: 13.5px;">Phòng khám đa khoa Phú Thái đã tiếp nhận thông tin và sẽ liên hệ hỗ trợ bạn qua số điện thoại trong thời gian sớm nhất.</p>
          </div>
        </div>
      <?php else: ?>

        <?php if ($errorMessage !== ''): ?>
          <div class="flash-message flash-error">
            <svg class="icon" aria-hidden="true"><use href="#i-help"/></svg>
            <div><?= e($errorMessage) ?></div>
          </div>
        <?php endif; ?>

        <form method="post" style="display: grid; gap: 16px;" id="supportForm">
          <?php render_form_guard('public_support_request'); ?>

          <div class="field" style="margin-bottom: 0;">
            <label for="full_name">Họ và tên của bạn <span style="color:var(--danger)">*</span></label>
            <input id="full_name" name="full_name" type="text" placeholder="Ví dụ: Nguyễn Văn A" value="<?= e($_POST['full_name'] ?? '') ?>" required>
          </div>

          <div class="field" style="margin-bottom: 0;">
            <label for="contact">Số điện thoại hoặc CCCD <span style="color:var(--danger)">*</span></label>
            <input id="contact" name="contact" type="text" inputmode="numeric" placeholder="Nhập số điện thoại hoặc 12 số CCCD" value="<?= e($_POST['contact'] ?? '') ?>" required>
          </div>

          <div class="field" style="margin-bottom: 0;">
            <label for="category">Vấn đề cần hỗ trợ</label>
            <select id="category" name="category" style="width: 100%; height: 44px; border: 1px solid var(--line); border-radius: 10px; padding: 0 12px; background: #fff; font-size: 15px; color: var(--ink);">
              <option value="Lỗi đăng nhập / Mật khẩu">Sự cố đăng nhập hoặc quên mật khẩu</option>
              <option value="Tra cứu kết quả khám">Hỏi về kết quả khám / đơn thuốc</option>
              <option value="Tư vấn khám & Đặt lịch">Tư vấn dịch vụ khám & Đặt lịch hẹn</option>
              <option value="Thủ tục Bảo hiểm y tế">Thủ tục quyền lợi Bảo hiểm Y tế (BHYT)</option>
              <option value="Khác">Vấn đề khác</option>
            </select>
          </div>

          <div class="field" style="margin-bottom: 0;">
            <label for="message">Nội dung chi tiết <span style="color:var(--danger)">*</span></label>
            <textarea id="message" name="message" rows="4" placeholder="Mô tả cụ thể khó khăn hoặc thông tin cần phòng khám hỗ trợ..." required style="width: 100%; padding: 12px; border: 1px solid var(--line); border-radius: 10px; font-family: inherit; font-size: 15px; color: var(--ink);"><?= e($_POST['message'] ?? '') ?></textarea>
          </div>

          <?php render_captcha('public_support_request'); ?>

          <div style="margin-top: 8px;">
            <button class="btn btn-primary btn-block" type="submit" id="submitSupportBtn" style="height: 48px; font-size: 16px;">
              <span id="submitSupportText">Gửi yêu cầu hỗ trợ</span> <svg class="icon" style="width:18px;height:18px;" aria-hidden="true"><use href="#i-arrow"/></svg>
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
