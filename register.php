<?php
declare(strict_types=1);

require_once 'config.php';

if (isset($_SESSION['user_id'])) {
    redirect('dashboard.php');
}

$emailEnabled = patient_email_enabled();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $guardError = validate_form_guard('patient_register', 8, 600, 0);
    $cccd = normalize_single_line_input($_POST['cccd'] ?? '');
    $fullName = normalize_single_line_input($_POST['name'] ?? '');
    $phone = normalize_single_line_input($_POST['phone'] ?? '');
    $email = normalize_single_line_input($_POST['email'] ?? '');
    $password = (string) ($_POST['password'] ?? '');
    $passwordError = $password !== '' ? validate_password_strength($password) : null;

    if ($guardError !== null) {
        set_flash('error', $guardError);
    } elseif ($cccd === '' || $fullName === '' || $phone === '' || $password === '') {
        set_flash('error', 'Vui lòng điền đầy đủ các trường thông tin bắt buộc.');
    } elseif (!validate_cccd($cccd)) {
        set_flash('error', 'Số CCCD không hợp lệ (phải gồm đúng 12 chữ số).');
    } elseif (!validate_person_name($fullName)) {
        set_flash('error', 'Họ và tên không hợp lệ.');
    } elseif (!validate_phone_number($phone)) {
        set_flash('error', 'Số điện thoại không hợp lệ (phải từ 10 - 11 chữ số).');
    } elseif ($email !== '' && !$emailEnabled) {
        set_flash('error', 'Tính năng xác thực qua Email hiện đang bảo trì.');
    } elseif ($email !== '' && !validate_email_address($email)) {
        set_flash('error', 'Địa chỉ Email không đúng định dạng.');
    } elseif ($passwordError !== null) {
        set_flash('error', $passwordError);
    } else {
        if ($emailEnabled) {
            $stmt = $conn->prepare('SELECT id FROM patients WHERE cccd = ? OR phone = ? OR (? <> "" AND email = ?) LIMIT 1');
            $stmt->bind_param('ssss', $cccd, $phone, $email, $email);
        } else {
            $stmt = $conn->prepare('SELECT id FROM patients WHERE cccd = ? OR phone = ? LIMIT 1');
            $stmt->bind_param('ss', $cccd, $phone);
        }
        $stmt->execute();
        $exists = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($exists) {
            set_flash('error', 'Số CCCD hoặc Số điện thoại này đã được đăng ký tài khoản.');
        } else {
            try {
                $passwordHash = hash_password($password);
                if ($emailEnabled) {
                    $stmt = $conn->prepare('INSERT INTO patients (cccd, full_name, phone, email, password_hash) VALUES (?, ?, ?, ?, ?)');
                    $emailParam = ($email !== '') ? $email : null;
                    $stmt->bind_param('sssss', $cccd, $fullName, $phone, $emailParam, $passwordHash);
                } else {
                    $stmt = $conn->prepare('INSERT INTO patients (cccd, full_name, phone, password_hash) VALUES (?, ?, ?, ?)');
                    $stmt->bind_param('ssss', $cccd, $fullName, $phone, $passwordHash);
                }
                $stmt->execute();
                $stmt->close();

                set_flash('success', 'Đăng ký tài khoản thành công! Bạn có thể đăng nhập ngay bằng số CCCD và mật khẩu vừa tạo.');
                redirect('login.php');
            } catch (Throwable $exception) {
                log_internal_error('patient_register_failed', $exception, ['cccd' => $cccd]);
                if (isset($stmt) && $stmt instanceof mysqli_stmt) {
                    $stmt->close();
                }

                if (is_duplicate_key_exception($exception)) {
                    set_flash('error', 'Số CCCD, số điện thoại hoặc Email đã tồn tại trong hệ thống.');
                } else {
                    set_flash('error', 'Không thể tạo tài khoản lúc này. Vui lòng kiểm tra lại thông tin hoặc liên hệ phòng khám để được hỗ trợ.');
                }
            }
        }
    }
}

render_header('Đăng ký tài khoản người bệnh · Phòng khám đa khoa Phú Thái');
?>

<div class="main-area" style="min-height: calc(100vh - 200px); display: flex; align-items: center; justify-content: center; padding: 36px 0;">
  <div class="container">
    <div class="auth-box">
      
      <div style="display: flex; align-items: center; gap: 16px; margin-bottom: 18px;">
        <span class="card-icon" style="width: 52px; height: 52px;">
          <svg class="icon" aria-hidden="true"><use href="#i-lock"/></svg>
        </span>
        <div>
          <span class="card-kicker">CỔNG DỊCH VỤ NGƯỜI BỆNH</span>
          <h1 style="font-size: 24px; font-weight: 700; color: var(--ink); margin: 2px 0 0;">Đăng ký tài khoản</h1>
        </div>
      </div>

      <p style="font-size: 14.5px; color: var(--muted); line-height: 1.55; margin: 0 0 20px;">
        Tạo tài khoản cá nhân để tra cứu kết quả khám bệnh, toa thuốc và theo dõi hồ sơ sức khỏe trực tuyến.
      </p>

      <?php render_flash(); ?>

      <form method="post" style="display: grid; gap: 16px;" id="registerForm" novalidate>
        <?php render_form_guard('patient_register'); ?>

        <div class="field" style="margin-bottom: 0;">
          <label for="cccd">Số Căn cước công dân (CCCD) <span style="color:var(--danger)">*</span></label>
          <input id="cccd" 
                 name="cccd" 
                 type="text" 
                 inputmode="numeric" 
                 maxlength="12" 
                 placeholder="Nhập 12 chữ số CCCD" 
                 value="<?= e($_POST['cccd'] ?? '') ?>" 
                 required>
          <p class="field-hint">Số CCCD sẽ dùng làm tên đăng nhập tài khoản của bạn.</p>
        </div>

        <div class="field" style="margin-bottom: 0;">
          <label for="name">Họ và tên của bạn <span style="color:var(--danger)">*</span></label>
          <input id="name" 
                 name="name" 
                 type="text" 
                 placeholder="Ví dụ: Nguyễn Văn A" 
                 value="<?= e($_POST['name'] ?? '') ?>" 
                 required>
        </div>

        <div class="field" style="margin-bottom: 0;">
          <label for="phone">Số điện thoại liên hệ <span style="color:var(--danger)">*</span></label>
          <input id="phone" 
                 name="phone" 
                 type="tel" 
                 inputmode="tel" 
                 maxlength="11" 
                 placeholder="Ví dụ: 0912345678" 
                 value="<?= e($_POST['phone'] ?? '') ?>" 
                 required>
          <p class="field-hint">Dùng để nhận thông báo và hỗ trợ cấp lại mật khẩu.</p>
        </div>

        <div class="field" style="margin-bottom: 0;">
          <label for="email">Địa chỉ Email <span style="font-weight:normal;color:var(--muted);">(không bắt buộc)</span></label>
          <input id="email" 
                 type="email" 
                 name="email" 
                 placeholder="Ví dụ: hoten@gmail.com" 
                 value="<?= e($_POST['email'] ?? '') ?>" 
                 <?= !$emailEnabled ? 'disabled' : '' ?>>
          <p class="field-hint">Dùng để nhận kết quả khám điện tử và thông báo bảo mật.</p>
        </div>

        <!-- Checklist yêu cầu mật khẩu -->
        <div style="background: var(--soft); padding: 14px 16px; border-radius: 12px; border: 1px solid #cce5f8; font-size: 13px; color: var(--ink);">
          <strong style="display: block; margin-bottom: 6px; color: var(--blue);">Yêu cầu tạo mật khẩu an toàn:</strong>
          <div style="display: grid; gap: 4px; color: var(--muted);">
            <div>• Tối thiểu 8 ký tự</div>
            <div>• Gồm ít nhất 1 chữ in hoa (A-Z)</div>
            <div>• Gồm ít nhất 1 chữ in thường (a-z)</div>
            <div>• Gồm ít nhất 1 chữ số (0-9)</div>
          </div>
        </div>

        <div class="field" style="margin-bottom: 0;">
          <label for="password">Mật khẩu tài khoản <span style="color:var(--danger)">*</span></label>
          <div class="password-wrap">
            <input id="password" 
                   type="password" 
                   name="password" 
                   minlength="8" 
                   placeholder="Tạo mật khẩu an toàn" 
                   required>
            <button id="togglePwdBtn" 
                    type="button" 
                    class="password-toggle" 
                    aria-label="Hiện mật khẩu">
              <svg class="icon" aria-hidden="true"><use href="#i-eye"/></svg>
            </button>
          </div>
        </div>

        <?php render_captcha('patient_register'); ?>

        <div style="display: flex; flex-direction: column; gap: 12px; margin-top: 8px;">
          <button class="btn btn-primary btn-block" type="submit" id="submitRegisterBtn" style="height: 48px; font-size: 16px;">
            <span id="submitRegisterText">Đăng ký tài khoản</span> <svg class="icon" style="width:18px;height:18px;" aria-hidden="true"><use href="#i-arrow"/></svg>
          </button>
          <div style="text-align: center; font-size: 14px; color: var(--muted); margin-top: 6px;">
            Đã có tài khoản? <a href="login.php" class="inline-link" style="font-weight: 700;">Đăng nhập ngay ➔</a>
          </div>
        </div>
      </form>

    </div>
  </div>
</div>

<script>
(function() {
  var pwd = document.getElementById('password');
  var toggle = document.getElementById('togglePwdBtn');
  if (pwd && toggle) {
    toggle.addEventListener('click', function() {
      var isPwd = pwd.type === 'password';
      pwd.type = isPwd ? 'text' : 'password';
      toggle.setAttribute('aria-label', isPwd ? 'Ẩn mật khẩu' : 'Hiện mật khẩu');
    });
  }

  var form = document.getElementById('registerForm');
  var btn = document.getElementById('submitRegisterBtn');
  var txt = document.getElementById('submitRegisterText');
  if (form && btn) {
    form.addEventListener('submit', function() {
      var cccdVal = document.getElementById('cccd').value.trim();
      var nameVal = document.getElementById('name').value.trim();
      var phoneVal = document.getElementById('phone').value.trim();
      var pwdVal = pwd ? pwd.value : '';
      if (cccdVal === '' || nameVal === '' || phoneVal === '' || pwdVal === '') return;
      btn.disabled = true;
      if (txt) txt.textContent = 'Đang xử lý đăng ký…';
    });
  }
})();
</script>

<?php render_footer(); ?>
