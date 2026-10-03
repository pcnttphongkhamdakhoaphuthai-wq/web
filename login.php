<?php
declare(strict_types=1);

require_once 'config.php';

if (isset($_SESSION['user_id'])) {
    $redirect = trim((string) ($_GET['redirect'] ?? ''));
    if ($redirect === 'records') {
        redirect('dashboard.php#records');
    }
    redirect('dashboard.php');
}

$redirectTarget = trim((string) ($_POST['redirect'] ?? $_GET['redirect'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $guardError = validate_form_guard('patient_login', 10, 600, 0);
    $cccd = normalize_single_line_input($_POST['cccd'] ?? '');
    $password = (string) ($_POST['password'] ?? '');
    $cooldownIdentity = $cccd !== '' ? $cccd : 'guest';
    $cooldownRemaining = login_submit_cooldown_remaining('patient_login_submit', $cooldownIdentity, 2, 10);
    $lockedUntil = $cccd !== '' ? get_login_lockout('patient_login', $cccd) : null;

    if ($guardError !== null) {
        security_log('patient_login_guard_blocked', ['cccd' => $cccd]);
        set_flash('error', $guardError);
    } elseif ($cooldownRemaining > 0) {
        set_flash('error', 'Bạn thao tác quá nhanh. Vui lòng chờ ' . $cooldownRemaining . ' giây rồi thử lại.');
    } elseif ($lockedUntil !== null) {
        security_log('patient_login_locked', ['cccd' => $cccd, 'locked_until' => $lockedUntil]);
        set_flash('error', 'Tài khoản tạm thời bị khóa trong 24 giờ do đăng nhập sai quá 5 lần. Bạn có thể sử dụng chức năng Quên mật khẩu hoặc liên hệ phòng khám để được hỗ trợ.');
    } elseif ($cccd === '' || $password === '') {
        set_flash('error', 'Vui lòng nhập đầy đủ số CCCD và mật khẩu.');
    } elseif (!validate_cccd($cccd)) {
        set_flash('error', 'Số CCCD không đúng định dạng (phải gồm 12 chữ số).');
    } else {
        register_login_submit_attempt('patient_login_submit', $cooldownIdentity, 10);
        $stmt = $conn->prepare('SELECT id, full_name, password_hash FROM patients WHERE cccd = ? LIMIT 1');
        $stmt->bind_param('s', $cccd);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $temporaryPasswordRecord = $user ? verify_temporary_patient_password($cccd, $password) : null;

        if ($user && verify_password($password, $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int) $user['id'];
            $_SESSION['name'] = $user['full_name'];
            $_SESSION['cccd'] = $cccd;
            mark_session_authenticated();
            clear_patient_password_change_requirement();
            clear_login_failures('patient_login', $cccd);
            clear_login_submit_attempts('patient_login_submit', $cooldownIdentity);
            security_log('patient_login_success', ['cccd' => $cccd, 'user_id' => (int) $user['id']]);
            audit_log('patient_login_success', ['cccd' => $cccd, 'user_id' => (int) $user['id']]);
            set_flash('success', 'Đăng nhập thành công.');
            
            if ($redirectTarget === 'records') {
                redirect('dashboard.php#records');
            } elseif ($redirectTarget === 'support') {
                redirect('dashboard.php#support');
            }
            redirect('dashboard.php#overview');
        }

        if ($user && $temporaryPasswordRecord !== null) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int) $user['id'];
            $_SESSION['name'] = $user['full_name'];
            $_SESSION['cccd'] = $cccd;
            mark_session_authenticated();
            mark_temporary_patient_password_session($password, $temporaryPasswordRecord);
            clear_temporary_patient_password($cccd);
            clear_login_failures('patient_login', $cccd);
            clear_login_submit_attempts('patient_login_submit', $cooldownIdentity);
            security_log('patient_temporary_password_login_success', ['cccd' => $cccd, 'user_id' => (int) $user['id']]);
            audit_log('patient_temporary_password_login_success', ['cccd' => $cccd, 'user_id' => (int) $user['id']]);
            set_flash('success', 'Đã đăng nhập bằng mật khẩu tạm thời. Vui lòng đổi mật khẩu mới ngay bây giờ.');
            redirect('account.php');
        }

        $nextLock = record_login_failure('patient_login', $cccd, 5, 86400);
        security_log('patient_login_failed', ['cccd' => $cccd, 'locked_until' => $nextLock]);
        set_flash('error', 'Số CCCD hoặc mật khẩu không chính xác. Vui lòng kiểm tra lại.');
    }
}

render_header('Đăng nhập người bệnh · Phòng khám đa khoa Phú Thái', 'records');
?>
<style>
/* CSS Dành riêng cho layout Đăng nhập chuẩn Demo */
.login-container { max-width: 1140px; margin: 0 auto; padding: 40px 20px 60px; }
.login-layout { display: grid; grid-template-columns: minmax(0, 1.25fr) minmax(380px, 450px); gap: 48px; align-items: center; }

/* Cột giới thiệu (Bên trái trên máy tính, đẩy xuống dưới trên mobile) */
.intro { padding-right: 12px; }
.intro-eyebrow { font-size: 12px; font-weight: 700; color: var(--primary); letter-spacing: 1.2px; text-transform: uppercase; margin-bottom: 12px; display: inline-flex; align-items: center; gap: 8px; }
.intro-eyebrow::before { content: ""; width: 8px; height: 8px; border-radius: 50%; background: var(--secondary); display: inline-block; }
.intro h1 { font-size: clamp(26px, 3.2vw, 36px); font-weight: 800; line-height: 1.25; color: #0f2942; margin: 0 0 16px; letter-spacing: -0.5px; }
.intro-desc { font-size: 15px; line-height: 1.7; color: var(--muted); margin: 0 0 28px; max-width: 540px; }
.benefit-list { display: grid; gap: 18px; margin-bottom: 28px; }
.benefit { display: flex; gap: 16px; align-items: flex-start; }
.benefit-icon { width: 44px; height: 44px; border-radius: 12px; background: #e0f2fe; color: var(--primary); display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 20px; }
.benefit h3 { font-size: 15.5px; font-weight: 700; margin: 0 0 4px; color: #1e293b; }
.benefit p { font-size: 13.5px; line-height: 1.55; color: var(--muted); margin: 0; }
.intro-caption { display: flex; align-items: center; gap: 10px; font-size: 13px; color: #64748b; font-style: italic; }
.caption-line { width: 32px; height: 2px; background: #cbd5e1; display: inline-block; }

/* Thẻ Form Đăng nhập (Bên phải trên máy tính, đưa lên đầu trên mobile) */
.login-card { background: #ffffff; border-radius: 20px; padding: 32px 32px 28px; box-shadow: 0 12px 32px rgba(15, 35, 55, 0.08); border: 1px solid var(--border); width: 100%; box-sizing: border-box; }
.card-heading { display: flex; align-items: center; gap: 8px; margin-bottom: 12px; }
.card-icon { width: 32px; height: 32px; border-radius: 8px; background: var(--soft); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 16px; }
.card-kicker { font-size: 11.5px; font-weight: 700; color: #0284c7; letter-spacing: 0.8px; text-transform: uppercase; }
.login-card h2 { font-size: 24px; font-weight: 800; color: #0f2942; margin: 0 0 6px; letter-spacing: -0.3px; }
.card-description { font-size: 14px; color: var(--muted); margin: 0 0 20px; }

.field { margin-bottom: 18px; }
.field label { font-size: 13.5px; font-weight: 600; color: #334155; margin-bottom: 6px; display: block; }
.field-hint { font-size: 12px; color: #64748b; margin: 5px 0 0; line-height: 1.4; }
.label-row { display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 6px; }
.label-row label { margin-bottom: 0; }
.inline-link { color: var(--primary); font-size: 13px; font-weight: 600; text-decoration: none; border: none; background: none; padding: 0; cursor: pointer; }
.inline-link:hover { text-decoration: underline; color: var(--primary-hover); }

.password-wrap { position: relative; }
.password-wrap input { padding-right: 48px; }
.password-toggle { position: absolute; right: 4px; top: 50%; transform: translateY(-50%); width: 40px; height: 40px; border: none; background: transparent; color: #64748b; display: flex; align-items: center; justify-content: center; cursor: pointer; border-radius: 8px; font-size: 18px; }
.password-toggle:hover { background: var(--soft); color: var(--primary); }

.login-submit { width: 100%; min-height: 48px; font-size: 15px; font-weight: 700; border-radius: 10px; background: linear-gradient(135deg, #0077b6, #0096c7); color: #fff; box-shadow: 0 4px 14px rgba(0, 119, 182, 0.25); display: flex; align-items: center; justify-content: center; gap: 8px; cursor: pointer; border: none; transition: all .15s; margin-top: 8px; }
.login-submit:hover:not(:disabled) { background: #005f92; transform: translateY(-1px); box-shadow: 0 6px 18px rgba(0, 119, 182, 0.35); }
.login-submit:disabled { opacity: 0.7; cursor: not-allowed; }

.register-row { font-size: 13.5px; color: var(--muted); text-align: center; margin: 18px 0 0; }
.card-divider { height: 1px; background: var(--border); margin: 20px 0 16px; }
.card-help { border: none; background: transparent; display: flex; align-items: center; justify-content: center; gap: 8px; width: 100%; font-size: 13px; color: #64748b; cursor: pointer; padding: 6px 0; }
.card-help strong { color: var(--primary); font-weight: 600; }
.card-help:hover strong { text-decoration: underline; }
.staff-row { margin-top: 10px; text-align: center; font-size: 12px; }
.staff-row a { color: #94a3b8; }
.staff-row a:hover { color: var(--primary); }

/* Dialog Modal */
dialog { border: 1px solid var(--border); padding: 28px; width: min(480px, calc(100% - 32px)); border-radius: 18px; color: var(--tx); box-shadow: 0 20px 60px rgba(15, 35, 55, 0.2); max-height: calc(100dvh - 40px); overflow: auto; }
dialog::backdrop { background: rgba(15, 35, 55, 0.5); backdrop-filter: blur(3px); }
.dialog-heading { display: flex; justify-content: space-between; align-items: center; gap: 16px; margin-bottom: 14px; }
.dialog-eyebrow { font-size: 11px; letter-spacing: 1px; color: var(--muted); font-weight: 700; text-transform: uppercase; }
.dialog-close { width: 34px; height: 34px; display: grid; place-items: center; border: none; border-radius: 8px; background: var(--soft); color: var(--primary); cursor: pointer; font-size: 16px; }
dialog h2 { font-size: 20px; font-weight: 700; color: #0f2942; margin: 0 0 12px; }
#dialog-content { font-size: 14px; line-height: 1.7; color: #475569; }
#dialog-content ol { padding-left: 20px; margin: 0 0 14px; }
#dialog-content li { margin-bottom: 8px; }
#dialog-content a { color: var(--primary); text-decoration: underline; }
.dialog-done { margin-top: 20px; width: 100%; min-height: 44px; }

/* Responsive Mobile Breakpoint */
@media (max-width: 860px) {
  .login-container { padding: 20px 16px 40px; }
  .login-layout { display: flex; flex-direction: column; gap: 32px; }
  /* Form đăng nhập đưa lên đầu trên màn hình điện thoại */
  .login-card { order: 0; padding: 24px 20px 20px; border-radius: 16px; }
  .intro { order: 1; padding-right: 0; text-align: left; }
  .intro h1 { font-size: 24px; }
  .intro-desc { font-size: 14px; margin-bottom: 20px; }
  .benefit-list { gap: 14px; }
}
</style>

<div class="login-container">
  <div class="login-layout">
    
    <!-- CỘT GIỚI THIỆU VÀ QUYỀN LỢI NGƯỜI BỆNH -->
    <section class="intro" aria-label="Giới thiệu cổng người bệnh">
      <div class="intro-eyebrow">Phú Thái · Cổng người bệnh</div>
      <h1>Xem hồ sơ và kết quả khám tiện lợi, an toàn</h1>
      <p class="intro-desc">Cổng thông tin trực tuyến giúp người bệnh tra cứu kết quả xét nghiệm, chẩn đoán, đơn thuốc và lịch sử khám chữa bệnh bất kỳ lúc nào.</p>
      
      <div class="benefit-list">
        <div class="benefit">
          <div class="benefit-icon">📄</div>
          <div>
            <h3>Kết quả khám trong tầm tay</h3>
            <p>Xem trực tiếp kết quả chẩn đoán, xét nghiệm và tải tệp đơn thuốc điện tử an toàn.</p>
          </div>
        </div>
        
        <div class="benefit">
          <div class="benefit-icon">📁</div>
          <div>
            <h3>Hồ sơ được sắp xếp rõ ràng</h3>
            <p>Lưu trữ lịch sử khám bệnh qua các đợt, dễ dàng tìm lại thông tin y tế khi tái khám.</p>
          </div>
        </div>
        
        <div class="benefit">
          <div class="benefit-icon">💬</div>
          <div>
            <h3>Luôn có nhân viên hỗ trợ</h3>
            <p>Dễ dàng nhận trợ giúp về tài khoản, bảo hiểm y tế và quy trình khám tại phòng khám.</p>
          </div>
        </div>
      </div>
      
      <div class="intro-caption">
        <span class="caption-line"></span> Đồng hành cùng người bệnh từ những điều nhỏ nhất.
      </div>
    </section>

    <!-- FORM ĐĂNG NHẬP (ĐƯA LÊN ĐẦU TRÊN MOBILE) -->
    <section class="login-card" id="login-form" aria-labelledby="login-title">
      <div class="card-heading">
        <span class="card-icon">🔒</span>
        <span class="card-kicker">TÀI KHOẢN NGƯỜI BỆNH</span>
      </div>
      <h2 id="login-title">Chào mừng bạn trở lại</h2>
      <p class="card-description">Đăng nhập để xem kết quả và hồ sơ của bạn.</p>
      
      <?php render_flash(); ?>
      
      <form method="post" id="patientLoginForm" novalidate>
        <?php render_form_guard('patient_login'); ?>
        <input type="hidden" name="redirect" value="<?= e($redirectTarget) ?>">
        
        <div class="field">
          <label for="cccd">Số Căn cước công dân (CCCD)</label>
          <input id="cccd" 
                 name="cccd" 
                 type="text" 
                 inputmode="numeric" 
                 autocomplete="username" 
                 maxlength="12" 
                 placeholder="Nhập 12 chữ số CCCD" 
                 value="<?= e($_POST['cccd'] ?? '') ?>" 
                 required 
                 aria-describedby="cccd-hint">
          <p id="cccd-hint" class="field-hint">Dùng số CCCD đã đăng ký trong hồ sơ khám tại phòng khám.</p>
        </div>
        
        <div class="field">
          <div class="label-row">
            <label for="password">Mật khẩu</label>
            <a href="forgot_password.php" class="inline-link">Quên mật khẩu?</a>
          </div>
          <div class="password-wrap">
            <input id="password" 
                   type="password" 
                   name="password" 
                   autocomplete="current-password" 
                   placeholder="Nhập mật khẩu của bạn" 
                   required>
            <button id="togglePasswordBtn" 
                    type="button" 
                    class="password-toggle" 
                    aria-label="Hiện mật khẩu" 
                    aria-pressed="false" 
                    title="Hiện/Ẩn mật khẩu">
              👁️
            </button>
          </div>
        </div>

        <?php render_captcha('patient_login'); ?>

        <button class="login-submit" type="submit" id="submitLoginBtn">
          <span id="submitLoginText">Đăng nhập</span> ➔
        </button>
      </form>

      <div class="register-row">
        Bạn chưa có tài khoản? <a href="register.php" class="inline-link">Đăng ký ngay ↗</a>
      </div>

      <div class="card-divider"></div>

      <button class="card-help" type="button" id="openGuideBtn">
        ❓ <span>Lần đầu sử dụng? <strong>Xem hướng dẫn tra cứu</strong></span> ➔
      </button>
      
      <div class="staff-row">
        <a href="admin_login.php">Dành cho cán bộ nhân viên y tế ↗</a>
      </div>
    </section>

  </div>
</div>

<!-- MODAL HƯỚNG DẪN DÀNH CHO NGƯỜI BỆNH -->
<dialog id="info-dialog" aria-labelledby="dialog-title">
  <div class="dialog-heading">
    <span class="dialog-eyebrow">PHÚ THÁI · HƯỚNG DẪN NGƯỜI BỆNH</span>
    <button type="button" class="dialog-close" id="dialogCloseBtn" aria-label="Đóng hướng dẫn">✕</button>
  </div>
  <h2 id="dialog-title">Hướng dẫn tra cứu kết quả khám</h2>
  <div id="dialog-content">
    <ol>
      <li><strong>Tài khoản:</strong> Sử dụng số CCCD (12 số) đã khai báo khi làm thủ tục khám tại phòng khám.</li>
      <li><strong>Mật khẩu:</strong> Nhập mật khẩu đã đăng ký, hoặc mật khẩu tạm thời được cung cấp qua tin nhắn/email.</li>
      <li><strong>Xem kết quả:</strong> Sau khi đăng nhập, hệ thống sẽ tự động hiển thị đợt khám mới nhất, kết quả chẩn đoán và đơn thuốc điện tử.</li>
      <li><strong>Quên mật khẩu:</strong> Chọn mục <a href="forgot_password.php">Quên mật khẩu</a> để nhận mã xác thực qua Email/Số điện thoại đã đăng ký.</li>
    </ol>
    <p>Nếu gặp khó khăn hoặc cần hỗ trợ tài khoản, quý khách vui lòng liên hệ hotline phòng khám: <a href="tel:02086289888"><strong>0208 628 9888</strong></a> hoặc gửi yêu cầu tại <a href="support.php">Trang hỗ trợ</a>.</p>
  </div>
  <button type="button" class="btn btn-light dialog-done" id="dialogDoneBtn">Đã hiểu</button>
</dialog>

<script>
(function() {
  // Hiện / Ẩn mật khẩu
  var pwdInput = document.getElementById('password');
  var toggleBtn = document.getElementById('togglePasswordBtn');
  if (pwdInput && toggleBtn) {
    toggleBtn.addEventListener('click', function() {
      var isPwd = pwdInput.type === 'password';
      pwdInput.type = isPwd ? 'text' : 'password';
      toggleBtn.setAttribute('aria-pressed', String(isPwd));
      toggleBtn.setAttribute('aria-label', isPwd ? 'Ẩn mật khẩu' : 'Hiện mật khẩu');
      toggleBtn.textContent = isPwd ? '🙈' : '👁️';
    });
  }

  // Ngăn chặn submit lặp và đổi trạng thái nút
  var loginForm = document.getElementById('patientLoginForm');
  var submitBtn = document.getElementById('submitLoginBtn');
  var submitText = document.getElementById('submitLoginText');
  if (loginForm && submitBtn) {
    loginForm.addEventListener('submit', function(e) {
      if (submitBtn.disabled) {
        e.preventDefault();
        return;
      }
      var cccdVal = document.getElementById('cccd').value.trim();
      var pwdVal = pwdInput.value;
      if (cccdVal === '' || pwdVal === '') {
        return; // Để HTML5 validation hiển thị
      }
      submitBtn.disabled = true;
      if (submitText) {
        submitText.textContent = 'Đang đăng nhập…';
      }
    });
  }

  // Dialog hướng dẫn người bệnh
  var guideBtn = document.getElementById('openGuideBtn');
  var dialog = document.getElementById('info-dialog');
  var closeBtn = document.getElementById('dialogCloseBtn');
  var doneBtn = document.getElementById('dialogDoneBtn');
  if (guideBtn && dialog) {
    guideBtn.addEventListener('click', function() {
      dialog.showModal();
    });
    if (closeBtn) closeBtn.addEventListener('click', function() { dialog.close(); });
    if (doneBtn) doneBtn.addEventListener('click', function() { dialog.close(); });
    dialog.addEventListener('click', function(e) {
      var rect = dialog.getBoundingClientRect();
      if (e.target === dialog && (e.clientX < rect.left || e.clientX > rect.right || e.clientY < rect.top || e.clientY > rect.bottom)) {
        dialog.close();
      }
    });
  }
})();
</script>

<?php render_footer(); ?>
