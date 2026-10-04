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

<style>
/* CSS chuẩn y tế cho màn hình Đăng ký tài khoản */
.auth-wrapper {
  min-height: calc(100vh - 180px);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 32px 16px;
}

.auth-card {
  width: 100%;
  max-width: 520px;
  margin: 0 auto;
  background: #ffffff;
  border-radius: 16px;
  border: 1px solid #cce0ef;
  padding: 30px 32px;
  box-shadow: 0 4px 6px -1px rgba(8, 45, 86, 0.03),
              0 16px 36px -6px rgba(8, 45, 86, 0.08),
              0 0 0 1px rgba(255, 255, 255, 0.9) inset;
  position: relative;
}

@media (max-width: 576px) {
  .auth-card {
    padding: 24px 18px;
    border-radius: 14px;
  }
}

.auth-header {
  display: flex;
  align-items: center;
  gap: 15px;
  margin-bottom: 14px;
}

.auth-header-icon {
  width: 50px;
  height: 50px;
  border-radius: 14px;
  display: grid;
  place-items: center;
  background: #e5f4fe;
  border: 1.5px solid #cde8fb;
  color: #0065aa;
  box-shadow: 0 2px 6px rgba(0, 95, 160, 0.08);
  flex: none;
}

.auth-header-icon .icon {
  width: 26px;
  height: 26px;
  stroke-width: 2;
}

.auth-kicker {
  display: block;
  font-size: 11.5px;
  line-height: 15px;
  font-weight: 700;
  letter-spacing: 0.6px;
  color: #005fa0;
  text-transform: uppercase;
}

.auth-title {
  font-size: 23px;
  font-weight: 700;
  color: #072e56;
  margin: 2px 0 0;
  letter-spacing: -0.2px;
  line-height: 1.25;
}

.auth-description {
  font-size: 14px;
  color: #516f8e;
  line-height: 1.55;
  margin: 0 0 18px;
}

.register-form {
  display: grid;
  gap: 14px;
}

.field {
  margin-bottom: 0;
}

.field label {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 5px;
  font-size: 13.5px;
  line-height: 18px;
  font-weight: 600;
  color: #072e56;
}

.field label .label-text {
  display: inline-flex;
  align-items: center;
  gap: 2px;
}

.field label .req {
  color: var(--danger, #b91c1c);
  font-weight: 700;
  margin-left: 2px;
}

.field label .opt {
  font-weight: 400;
  font-size: 12.5px;
  color: #63809d;
}

/* Khung ô nhập liệu có icon trực quan */
.input-icon-wrap {
  position: relative;
  display: flex;
  align-items: center;
  width: 100%;
}

.input-icon-wrap .field-icon {
  position: absolute;
  left: 14px;
  top: 50%;
  transform: translateY(-50%);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 20px;
  height: 20px;
  color: #63809d;
  pointer-events: none;
  transition: color 0.15s ease;
  z-index: 2;
}

.input-icon-wrap input {
  display: block;
  width: 100%;
  height: 46px;
  padding: 0 14px 0 44px;
  font-size: 14.5px;
  color: #072e56;
  background: #ffffff;
  border: 1px solid #c8dced;
  border-radius: 12px;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02) inset;
  transition: border-color 0.15s ease, box-shadow 0.15s ease;
  box-sizing: border-box;
}

.input-icon-wrap input::placeholder {
  color: #7d9bb8;
  opacity: 1;
  font-size: 13.5px;
}

.input-icon-wrap input:hover:not(:disabled) {
  border-color: #7fa7c8;
}

.input-icon-wrap input:focus {
  border-color: #0077c8;
  outline: none;
  box-shadow: 0 0 0 3px rgba(0, 119, 200, 0.16);
}

.input-icon-wrap:focus-within .field-icon {
  color: #0077c8;
}

.input-icon-wrap input:disabled {
  background: #f1f5f9;
  color: #94a3b8;
  cursor: not-allowed;
}

/* Trường Mật khẩu có nút toggle mắt */
.input-icon-wrap.has-toggle input {
  padding-right: 48px;
}

.password-toggle-btn {
  position: absolute;
  right: 5px;
  top: 50%;
  transform: translateY(-50%);
  width: 38px;
  height: 38px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border: 0;
  border-radius: 8px;
  background: transparent;
  color: #557594;
  cursor: pointer;
  transition: color 0.15s ease, background-color 0.15s ease;
  padding: 0;
  z-index: 2;
}

.password-toggle-btn:hover {
  color: #005fa0;
  background: #eff6fc;
}

.password-toggle-btn:focus-visible {
  outline: 2px solid #0077c8;
  outline-offset: 1px;
}

.password-toggle-btn svg {
  width: 20px;
  height: 20px;
  stroke-width: 1.8;
  display: block;
}

.field-hint {
  font-size: 12px;
  line-height: 16px;
  color: #516f8e;
  margin: 4px 0 0 4px;
}

/* Hộp quy định mật khẩu an toàn */
.pwd-requirements-box {
  background: #f3f9fe;
  border: 1px solid #cce5f8;
  border-radius: 12px;
  padding: 12px 16px;
  font-size: 12.5px;
  color: #072e56;
  margin: 2px 0;
}

.pwd-requirements-title {
  display: flex;
  align-items: center;
  gap: 6px;
  font-weight: 700;
  color: #005fa0;
  margin-bottom: 6px;
  font-size: 12.5px;
}

.pwd-requirements-title svg {
  width: 16px;
  height: 16px;
  flex: none;
}

.pwd-requirements-list {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 4px 12px;
  color: #4a6884;
}

@media (max-width: 480px) {
  .pwd-requirements-list {
    grid-template-columns: 1fr;
  }
}

.pwd-req-item {
  display: flex;
  align-items: center;
  gap: 6px;
  line-height: 1.4;
}

.pwd-req-item::before {
  content: "";
  display: inline-block;
  width: 5px;
  height: 5px;
  border-radius: 50%;
  background: #0077c8;
  flex: none;
}

/* Cụm nút hành động và liên kết */
.auth-actions {
  display: flex;
  flex-direction: column;
  gap: 12px;
  margin-top: 6px;
}

.btn-submit-register {
  width: 100%;
  height: 46px;
  min-height: 46px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 9px;
  padding: 0 20px;
  border: 1px solid #004c7d;
  border-radius: 12px;
  background: linear-gradient(110deg, #006eb5 0%, #005a92 100%);
  color: #ffffff;
  font-size: 15.5px;
  font-weight: 600;
  letter-spacing: 0.1px;
  box-shadow: 0 2px 4px rgba(0, 43, 70, 0.12),
              0 5px 14px rgba(0, 95, 160, 0.18);
  transition: all 0.15s ease;
  cursor: pointer;
}

.btn-submit-register:hover:not(:disabled) {
  background: linear-gradient(110deg, #005e9c 0%, #004d7d 100%);
  box-shadow: 0 3px 8px rgba(0, 43, 70, 0.16),
              0 6px 16px rgba(0, 95, 160, 0.24);
  transform: translateY(-1px);
}

.btn-submit-register:active:not(:disabled) {
  transform: translateY(0);
  box-shadow: 0 1px 3px rgba(0, 43, 70, 0.14);
}

.btn-submit-register:disabled {
  opacity: 0.7;
  cursor: wait;
}

.btn-submit-register .icon {
  width: 18px;
  height: 18px;
  stroke-width: 2.2;
}

.auth-footer-link {
  text-align: center;
  font-size: 14px;
  color: #516f8e;
  margin-top: 4px;
  padding-top: 14px;
  border-top: 1px solid var(--line-light, #e2edf6);
}

.auth-footer-link a {
  font-weight: 700;
  color: #005fa0;
  margin-left: 4px;
  transition: color 0.15s ease;
}

.auth-footer-link a:hover {
  color: #004473;
  text-decoration: underline;
  text-underline-offset: 3px;
}
</style>

<div class="main-area auth-wrapper">
  <div class="container">
    <div class="auth-box auth-card">
      
      <div class="auth-header">
        <span class="auth-header-icon" aria-hidden="true">
          <svg class="icon" aria-hidden="true"><use href="#i-lock"/></svg>
        </span>
        <div>
          <span class="auth-kicker">CỔNG DỊCH VỤ NGƯỜI BỆNH</span>
          <h1 class="auth-title">Đăng ký tài khoản</h1>
        </div>
      </div>

      <p class="auth-description">
        Tạo tài khoản cá nhân để tra cứu kết quả khám bệnh, toa thuốc và theo dõi hồ sơ sức khỏe trực tuyến.
      </p>

      <?php render_flash(); ?>

      <form method="post" class="register-form" id="registerForm" novalidate>
        <?php render_form_guard('patient_register'); ?>

        <!-- Trường CCCD -->
        <div class="field">
          <label for="cccd">
            <span class="label-text">Số Căn cước công dân (CCCD) <span class="req">*</span></span>
          </label>
          <div class="input-icon-wrap">
            <span class="field-icon" aria-hidden="true">
              <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <rect x="2" y="4" width="20" height="16" rx="3"/>
                <circle cx="8" cy="11" r="2.5"/>
                <path d="M14 9h4M14 13h4M6 17h12"/>
              </svg>
            </span>
            <input id="cccd" 
                   name="cccd" 
                   type="text" 
                   inputmode="numeric" 
                   maxlength="12" 
                   autocomplete="username"
                   placeholder="Nhập 12 chữ số CCCD" 
                   value="<?= e($_POST['cccd'] ?? '') ?>" 
                   required>
          </div>
          <p class="field-hint">Số CCCD sẽ dùng làm tên đăng nhập tài khoản của bạn.</p>
        </div>

        <!-- Trường Họ và tên -->
        <div class="field">
          <label for="name">
            <span class="label-text">Họ và tên của bạn <span class="req">*</span></span>
          </label>
          <div class="input-icon-wrap">
            <span class="field-icon" aria-hidden="true">
              <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="8" r="4"/>
                <path d="M4 20c0-3.5 3.5-6 8-6s8 2.5 8 6"/>
              </svg>
            </span>
            <input id="name" 
                   name="name" 
                   type="text" 
                   autocomplete="name"
                   placeholder="Ví dụ: Nguyễn Văn A" 
                   value="<?= e($_POST['name'] ?? '') ?>" 
                   required>
          </div>
          <p class="field-hint">Vui lòng nhập họ và tên đúng theo giấy tờ tùy thân.</p>
        </div>

        <!-- Trường Số điện thoại -->
        <div class="field">
          <label for="phone">
            <span class="label-text">Số điện thoại liên hệ <span class="req">*</span></span>
          </label>
          <div class="input-icon-wrap">
            <span class="field-icon" aria-hidden="true">
              <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
              </svg>
            </span>
            <input id="phone" 
                   name="phone" 
                   type="tel" 
                   inputmode="tel" 
                   maxlength="11" 
                   autocomplete="tel"
                   placeholder="Ví dụ: 0912345678" 
                   value="<?= e($_POST['phone'] ?? '') ?>" 
                   required>
          </div>
          <p class="field-hint">Dùng để nhận thông báo và hỗ trợ cấp lại mật khẩu.</p>
        </div>

        <!-- Trường Email -->
        <div class="field">
          <label for="email">
            <span class="label-text">Địa chỉ Email</span>
            <span class="opt"><?= !$emailEnabled ? '(tạm bảo trì)' : '(không bắt buộc)' ?></span>
          </label>
          <div class="input-icon-wrap">
            <span class="field-icon" aria-hidden="true">
              <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <rect x="2" y="4" width="20" height="16" rx="2.5"/>
                <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>
              </svg>
            </span>
            <input id="email" 
                   type="email" 
                   name="email" 
                   autocomplete="email"
                   placeholder="Ví dụ: hoten@gmail.com" 
                   value="<?= e($_POST['email'] ?? '') ?>" 
                   <?= !$emailEnabled ? 'disabled' : '' ?>>
          </div>
          <p class="field-hint">Dùng để nhận kết quả khám điện tử và thông báo bảo mật.</p>
        </div>

        <!-- Checklist yêu cầu mật khẩu an toàn -->
        <div class="pwd-requirements-box">
          <div class="pwd-requirements-title">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
            </svg>
            <span>Yêu cầu tạo mật khẩu an toàn:</span>
          </div>
          <div class="pwd-requirements-list">
            <div class="pwd-req-item">Tối thiểu 8 ký tự</div>
            <div class="pwd-req-item">Ít nhất 1 chữ in hoa (A-Z)</div>
            <div class="pwd-req-item">Ít nhất 1 chữ thường (a-z)</div>
            <div class="pwd-req-item">Ít nhất 1 chữ số (0-9)</div>
          </div>
        </div>

        <!-- Trường Mật khẩu -->
        <div class="field">
          <label for="password">
            <span class="label-text">Mật khẩu tài khoản <span class="req">*</span></span>
          </label>
          <div class="input-icon-wrap has-toggle">
            <span class="field-icon" aria-hidden="true">
              <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="11" width="18" height="11" rx="2.5"/>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
              </svg>
            </span>
            <input id="password" 
                   type="password" 
                   name="password" 
                   autocomplete="new-password"
                   minlength="8" 
                   placeholder="Tạo mật khẩu an toàn" 
                   required>
            <button id="togglePwdBtn" 
                    type="button" 
                    class="password-toggle-btn" 
                    aria-label="Hiện mật khẩu" 
                    aria-pressed="false"
                    title="Hiện mật khẩu">
              <!-- Icon mắt mở khi đang ẩn mật khẩu -->
              <svg class="eye-open" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                <circle cx="12" cy="12" r="3"/>
              </svg>
              <!-- Icon mắt gạch chéo khi đang hiện mật khẩu -->
              <svg class="eye-closed" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="display: none;">
                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/>
                <line x1="1" y1="1" x2="23" y2="23"/>
              </svg>
            </button>
          </div>
        </div>

        <?php render_captcha('patient_register'); ?>

        <!-- Cụm nút hành động và chuyển hướng -->
        <div class="auth-actions">
          <button class="btn-submit-register" type="submit" id="submitRegisterBtn">
            <span id="submitRegisterText">Đăng ký tài khoản</span>
            <svg class="icon" aria-hidden="true"><use href="#i-arrow"/></svg>
          </button>
          <div class="auth-footer-link">
            Đã có tài khoản người bệnh? <a href="login.php" class="inline-link">Đăng nhập ngay ➔</a>
          </div>
        </div>
      </form>

    </div>
  </div>
</div>

<script>
(function() {
  // 1. Toggle ẩn / hiện mật khẩu và chuyển đổi icon trạng thái
  var pwd = document.getElementById('password');
  var toggle = document.getElementById('togglePwdBtn');
  if (pwd && toggle) {
    var eyeOpen = toggle.querySelector('.eye-open');
    var eyeClosed = toggle.querySelector('.eye-closed');
    toggle.addEventListener('click', function(e) {
      e.preventDefault();
      var isPwd = pwd.type === 'password';
      pwd.type = isPwd ? 'text' : 'password';
      var newLabel = isPwd ? 'Ẩn mật khẩu' : 'Hiện mật khẩu';
      toggle.setAttribute('aria-label', newLabel);
      toggle.setAttribute('aria-pressed', isPwd ? 'true' : 'false');
      toggle.setAttribute('title', newLabel);
      if (eyeOpen && eyeClosed) {
        if (isPwd) {
          eyeOpen.style.display = 'none';
          eyeClosed.style.display = 'block';
        } else {
          eyeOpen.style.display = 'block';
          eyeClosed.style.display = 'none';
        }
      }
      pwd.focus();
    });
  }

  // 2. Tự động chuẩn hóa input số CCCD và Số điện thoại
  var cccdInput = document.getElementById('cccd');
  if (cccdInput) {
    cccdInput.addEventListener('input', function() {
      this.value = this.value.replace(/\D/g, '').slice(0, 12);
    });
  }

  var phoneInput = document.getElementById('phone');
  if (phoneInput) {
    phoneInput.addEventListener('input', function() {
      this.value = this.value.replace(/[^\d]/g, '').slice(0, 11);
    });
  }

  // 3. Xử lý trạng thái nút bấm khi gửi form đăng ký
  var form = document.getElementById('registerForm');
  var btn = document.getElementById('submitRegisterBtn');
  var txt = document.getElementById('submitRegisterText');
  if (form && btn) {
    form.addEventListener('submit', function() {
      var cccdVal = cccdInput ? cccdInput.value.trim() : '';
      var nameInput = document.getElementById('name');
      var nameVal = nameInput ? nameInput.value.trim() : '';
      var phoneVal = phoneInput ? phoneInput.value.trim() : '';
      var pwdVal = pwd ? pwd.value : '';
      if (cccdVal === '' || nameVal === '' || phoneVal === '' || pwdVal === '') return;
      btn.disabled = true;
      if (txt) txt.textContent = 'Đang xử lý đăng ký…';
    });
  }
})();
</script>

<?php render_footer(); ?>
