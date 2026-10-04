<?php
declare(strict_types=1);

require_once 'config.php';

function mask_recipient(string $dest): string
{
    $dest = trim($dest);
    if (str_contains($dest, '@')) {
        $parts = explode('@', $dest);
        $name = $parts[0];
        $domain = $parts[1] ?? '';
        $maskedName = mb_substr($name, 0, 2) . '***' . (mb_strlen($name) > 3 ? mb_substr($name, -1) : '');
        return $maskedName . '@' . $domain;
    }
    $digits = preg_replace('/\D/', '', $dest);
    if (strlen($digits) >= 9) {
        return substr($digits, 0, 3) . '****' . substr($digits, -3);
    }
    return $dest !== '' ? '***' : '';
}

$step = (string) ($_GET['step'] ?? 'request');
$emailEnabled = patient_email_enabled();
$destMasked = trim((string) ($_GET['dest'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($step === 'request') {
        $guardError = validate_form_guard('forgot_password_request', 5, 600, 0, true);
        $cccd = normalize_single_line_input($_POST['cccd'] ?? '');
        $channel = normalize_single_line_input($_POST['channel'] ?? 'email');

        if ($guardError !== null) {
            set_flash('error', $guardError);
        } elseif (!validate_cccd($cccd)) {
            set_flash('error', 'Số CCCD không hợp lệ (phải gồm 12 chữ số).');
        } elseif (!in_array($channel, ['email', 'phone'], true)) {
            set_flash('error', 'Kênh nhận OTP không hợp lệ.');
        } else {
            $stmt = $conn->prepare(patient_select_sql('WHERE cccd = ? LIMIT 1', true, $emailEnabled, false));
            $stmt->bind_param('s', $cccd);
            $stmt->execute();
            $patient = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (is_array($patient) && !$emailEnabled) {
                $patient['email'] = '';
            }

            $maskedDestination = '';
            if ($patient && !($channel === 'email' && !$emailEnabled)) {
                try {
                    $delivery = issue_password_reset_otp($patient, $channel);
                    $maskedDestination = mask_recipient((string) ($delivery['destination'] ?? ''));
                    set_flash('success', 'Mã xác thực OTP đã được gửi thành công.');
                    redirect('forgot_password.php?step=verify&cccd=' . urlencode($cccd) . '&dest=' . urlencode($maskedDestination));
                } catch (Throwable $exception) {
                    log_internal_error('forgot_password_request_failed', $exception, ['cccd' => $cccd, 'channel' => $channel]);
                    set_flash('success', 'Nếu thông tin CCCD đã được đăng ký, hệ thống sẽ gửi mã OTP đến thông tin liên hệ của bạn.');
                }
            } else {
                set_flash('success', 'Nếu thông tin CCCD đã được đăng ký, hệ thống sẽ gửi mã OTP đến thông tin liên hệ của bạn.');
            }
        }
    } elseif ($step === 'verify') {
        $guardError = validate_form_guard('forgot_password_verify', 8, 600, 0, false);
        $cccd = normalize_single_line_input($_POST['cccd'] ?? '');
        $otp = normalize_single_line_input($_POST['otp'] ?? '');
        $newPassword = (string) ($_POST['new_password'] ?? '');
        $confirmPassword = (string) ($_POST['confirm_password'] ?? '');
        $passwordError = $newPassword !== '' ? validate_password_strength($newPassword) : null;

        if ($guardError !== null) {
            set_flash('error', $guardError);
        } elseif (!validate_cccd($cccd)) {
            set_flash('error', 'Số CCCD không hợp lệ.');
        } elseif (!preg_match('/^\d{6}$/', $otp)) {
            set_flash('error', 'Mã OTP phải gồm đúng 6 chữ số.');
        } elseif ($passwordError !== null) {
            set_flash('error', $passwordError);
        } elseif ($newPassword !== $confirmPassword) {
            set_flash('error', 'Mật khẩu xác nhận không khớp với mật khẩu mới.');
        } else {
            try {
                $record = verify_password_reset_otp($cccd, $otp);
                with_transaction($conn, static function () use ($conn, $record, $newPassword): void {
                    $passwordHash = hash_password($newPassword);
                    $stmt = $conn->prepare('UPDATE patients SET password_hash = ? WHERE id = ?');
                    $stmt->bind_param('si', $passwordHash, $record['patient_id']);
                    $stmt->execute();
                    $stmt->close();
                });
                consume_password_reset_otp($cccd);

                clear_login_failures('patient_login', $cccd);
                clear_login_submit_attempts('patient_login_submit', $cccd);
                audit_log('patient_password_reset_success', [
                    'cccd' => $cccd,
                    'patient_id' => (int) $record['patient_id'],
                    'channel' => (string) ($record['channel'] ?? ''),
                ]);
                set_flash('success', 'Đặt lại mật khẩu thành công! Bạn có thể đăng nhập ngay bây giờ.');
                redirect('login.php');
            } catch (Throwable $exception) {
                log_internal_error('forgot_password_verify_failed', $exception, ['cccd' => $cccd]);
                set_flash('error', 'Không thể đặt lại mật khẩu. Mã OTP không đúng hoặc đã hết hạn (10 phút).');
            }
        }
    }
}

render_header('Lấy lại mật khẩu · Phòng khám đa khoa Phú Thái');
?>

<style>
/* CSS chuẩn y tế cho màn hình Quên / Lấy lại mật khẩu */
.auth-wrapper {
  min-height: calc(100vh - 180px);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 32px 16px;
}

.auth-card {
  width: 100%;
  max-width: 480px;
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

.auth-form {
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

.input-icon-wrap .field-icon .icon {
  width: 18px;
  height: 18px;
}

.input-icon-wrap input,
.input-icon-wrap select {
  display: block;
  width: 100%;
  height: 46px;
  min-height: 46px;
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

.input-icon-wrap select.form-select {
  appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%23005fa0' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 14px center;
  padding-right: 40px;
  cursor: pointer;
}

.input-icon-wrap input::placeholder {
  color: #7d9bb8;
  opacity: 1;
  font-size: 13.5px;
}

.input-icon-wrap input:hover:not(:disabled):not([readonly]),
.input-icon-wrap select:hover:not(:disabled) {
  border-color: #7fa7c8;
}

.input-icon-wrap input:focus,
.input-icon-wrap select:focus {
  border-color: #0077c8;
  outline: none;
  box-shadow: 0 0 0 3px rgba(0, 119, 200, 0.16);
}

.input-icon-wrap:focus-within .field-icon {
  color: #0077c8;
}

.input-icon-wrap input[readonly],
.input-icon-wrap input.input-readonly {
  background: #f1f5f9;
  border-color: #d8e2ec;
  color: #334155;
  font-weight: 600;
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

.input-otp {
  font-size: 18px !important;
  letter-spacing: 4px;
  font-weight: 700;
  color: #005fa0 !important;
}

.field-hint {
  font-size: 12px;
  line-height: 16px;
  color: #516f8e;
  margin: 4px 0 0 4px;
}

/* Hộp quy tắc mật khẩu an toàn */
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

.pwd-requirements-title .icon {
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

/* Cụm nút hành động chuẩn 46px, bo góc 12px, gradient Phú Thái */
.auth-actions {
  display: flex;
  flex-direction: column;
  gap: 10px;
  margin-top: 6px;
}

.btn-submit-action {
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
  text-decoration: none;
}

.btn-submit-action:hover:not(:disabled) {
  background: linear-gradient(110deg, #005e9c 0%, #004d7d 100%);
  box-shadow: 0 3px 8px rgba(0, 43, 70, 0.16),
              0 6px 16px rgba(0, 95, 160, 0.24);
  transform: translateY(-1px);
}

.btn-submit-action:active:not(:disabled) {
  transform: translateY(0);
  box-shadow: 0 1px 3px rgba(0, 43, 70, 0.14);
}

.btn-submit-action:disabled {
  opacity: 0.7;
  cursor: wait;
}

.btn-submit-action .icon {
  width: 18px;
  height: 18px;
  stroke-width: 2.2;
}

.btn-back-action {
  width: 100%;
  height: 46px;
  min-height: 46px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 0 20px;
  border: 1.5px solid #badcf6;
  border-radius: 12px;
  background: #f0f7fd;
  color: #005fa0;
  font-size: 15px;
  font-weight: 600;
  transition: all 0.15s ease;
  cursor: pointer;
  text-decoration: none;
  text-align: center;
}

.btn-back-action:hover {
  background: #e1f0fc;
  border-color: #9eccf0;
  color: #004473;
}

/* Hàng đếm ngược và gửi lại OTP */
.countdown-resend-row {
  text-align: center;
  font-size: 13.5px;
  color: #516f8e;
  margin: 4px 0 2px;
}

.countdown-badge {
  color: #005fa0;
  font-weight: 600;
  background: #eef6fc;
  padding: 2px 8px;
  border-radius: 6px;
}

.resend-link {
  color: #005fa0;
  font-weight: 700;
  text-decoration: underline;
  text-underline-offset: 3px;
  cursor: pointer;
}

.resend-link:hover {
  color: #004473;
}

/* Phần trợ giúp hotline ở chân form */
.auth-support-row {
  margin-top: 20px;
  padding-top: 16px;
  border-top: 1px solid var(--line-light, #e2edf6);
  font-size: 13.5px;
  color: #516f8e;
  text-align: center;
}

.support-hotline {
  color: #005fa0;
  font-weight: 700;
  text-decoration: none;
  margin-left: 4px;
}

.support-hotline:hover {
  color: #004473;
  text-decoration: underline;
}
</style>

<div class="main-area auth-wrapper">
  <div class="container">
    <div class="auth-card">
      
      <div class="auth-header">
        <span class="auth-header-icon" aria-hidden="true">
          <svg class="icon" aria-hidden="true"><use href="#i-help"/></svg>
        </span>
        <div>
          <span class="auth-kicker">CẤP LẠI MẬT KHẨU</span>
          <h1 class="auth-title">Lấy lại mật khẩu</h1>
        </div>
      </div>

      <p class="auth-description">
        <?= $step === 'verify'
          ? 'Nhập mã xác thực OTP đã nhận và thiết lập mật khẩu mới an toàn cho tài khoản của bạn.'
          : 'Xác thực số Căn cước công dân để nhận mã OTP khôi phục quyền truy cập hồ sơ khám bệnh.' ?>
      </p>

      <?php render_flash(); ?>

      <?php if ($step === 'verify'): ?>
        <!-- BƯỚC 2: NHẬP OTP VÀ ĐỔI MẬT KHẨU MỚI -->
        <form method="post" class="auth-form" id="verifyResetForm" novalidate>
          <?php render_form_guard('forgot_password_verify'); ?>
          
          <div class="field">
            <label for="cccd">
              <span class="label-text">Số Căn cước công dân (CCCD)</span>
            </label>
            <div class="input-icon-wrap">
              <span class="field-icon" aria-hidden="true">
                <svg class="icon" aria-hidden="true"><use href="#i-file"/></svg>
              </span>
              <input id="cccd" name="cccd" type="text" inputmode="numeric" maxlength="12" value="<?= e($_GET['cccd'] ?? $_POST['cccd'] ?? '') ?>" required readonly class="input-readonly">
            </div>
            <?php if ($destMasked !== ''): ?>
              <p class="field-hint">Mã OTP đã được gửi đến: <strong><?= e($destMasked) ?></strong></p>
            <?php endif; ?>
          </div>

          <div class="field">
            <label for="otp">
              <span class="label-text">Mã xác thực OTP (6 chữ số) <span class="req">*</span></span>
            </label>
            <div class="input-icon-wrap">
              <span class="field-icon" aria-hidden="true">
                <svg class="icon" aria-hidden="true"><use href="#i-lock"/></svg>
              </span>
              <input id="otp" name="otp" type="text" inputmode="numeric" maxlength="6" pattern="\d{6}" placeholder="Nhập 6 chữ số OTP" required autofocus class="input-otp">
            </div>
            <p class="field-hint">Mã OTP có thời hạn hiệu lực trong vòng 10 phút.</p>
          </div>

          <!-- Hộp quy định mật khẩu an toàn -->
          <div class="pwd-requirements-box">
            <div class="pwd-requirements-title">
              <svg class="icon" aria-hidden="true"><use href="#i-check"/></svg>
              <span>Yêu cầu mật khẩu mới an toàn:</span>
            </div>
            <div class="pwd-requirements-list">
              <div class="pwd-req-item">Tối thiểu 8 ký tự</div>
              <div class="pwd-req-item">Có ít nhất 1 chữ in hoa (A-Z)</div>
              <div class="pwd-req-item">Có ít nhất 1 chữ in thường (a-z)</div>
              <div class="pwd-req-item">Có ít nhất 1 chữ số (0-9)</div>
            </div>
          </div>

          <!-- Mật khẩu mới có nút toggle mắt ẩn/hiện -->
          <div class="field">
            <label for="new_password">
              <span class="label-text">Mật khẩu mới <span class="req">*</span></span>
            </label>
            <div class="input-icon-wrap has-toggle">
              <span class="field-icon" aria-hidden="true">
                <svg class="icon" aria-hidden="true"><use href="#i-lock"/></svg>
              </span>
              <input id="new_password" type="password" name="new_password" minlength="8" autocomplete="new-password" placeholder="Nhập mật khẩu mới an toàn" required>
              <button type="button" class="password-toggle-btn" data-target="new_password" aria-label="Hiện mật khẩu" aria-pressed="false" title="Hiện mật khẩu">
                <svg class="eye-open" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                  <circle cx="12" cy="12" r="3"></circle>
                </svg>
                <svg class="eye-closed" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="display:none;">
                  <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                  <line x1="1" y1="1" x2="23" y2="23"></line>
                </svg>
              </button>
            </div>
          </div>

          <!-- Xác nhận mật khẩu mới có nút toggle mắt ẩn/hiện -->
          <div class="field">
            <label for="confirm_password">
              <span class="label-text">Xác nhận lại mật khẩu mới <span class="req">*</span></span>
            </label>
            <div class="input-icon-wrap has-toggle">
              <span class="field-icon" aria-hidden="true">
                <svg class="icon" aria-hidden="true"><use href="#i-lock"/></svg>
              </span>
              <input id="confirm_password" type="password" name="confirm_password" minlength="8" autocomplete="new-password" placeholder="Nhập lại chính xác mật khẩu mới" required>
              <button type="button" class="password-toggle-btn" data-target="confirm_password" aria-label="Hiện mật khẩu" aria-pressed="false" title="Hiện mật khẩu">
                <svg class="eye-open" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                  <circle cx="12" cy="12" r="3"></circle>
                </svg>
                <svg class="eye-closed" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="display:none;">
                  <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                  <line x1="1" y1="1" x2="23" y2="23"></line>
                </svg>
              </button>
            </div>
          </div>

          <div class="auth-actions">
            <button class="btn-submit-action" type="submit" id="submitVerifyBtn">
              <span id="submitVerifyText">Xác nhận đổi mật khẩu</span>
              <svg class="icon" aria-hidden="true"><use href="#i-arrow"/></svg>
            </button>
            
            <div class="countdown-resend-row">
              Chưa nhận được mã? <span id="countdownText" class="countdown-badge">(chờ 60s)</span>
              <a id="resendLink" href="forgot_password.php?cccd=<?= urlencode((string)($_GET['cccd'] ?? $_POST['cccd'] ?? '')) ?>" class="resend-link" style="display: none;">Gửi lại mã OTP</a>
            </div>

            <a class="btn-back-action" href="forgot_password.php">
              Quay lại bước trước
            </a>
          </div>
        </form>

      <?php else: ?>
        <!-- BƯỚC 1: NHẬP SỐ CCCD ĐỂ YÊU CẦU OTP -->
        <form method="post" class="auth-form" id="requestOtpForm" novalidate>
          <?php render_form_guard('forgot_password_request'); ?>
          
          <div class="field">
            <label for="cccd">
              <span class="label-text">Số Căn cước công dân (CCCD) <span class="req">*</span></span>
            </label>
            <div class="input-icon-wrap">
              <span class="field-icon" aria-hidden="true">
                <svg class="icon" aria-hidden="true"><use href="#i-file"/></svg>
              </span>
              <input id="cccd" name="cccd" type="text" inputmode="numeric" maxlength="12" placeholder="Nhập 12 chữ số CCCD đã đăng ký" value="<?= e($_POST['cccd'] ?? $_GET['cccd'] ?? '') ?>" required autofocus>
            </div>
            <p class="field-hint">Dùng số CCCD đã đăng ký trong hồ sơ tại phòng khám.</p>
          </div>

          <div class="field">
            <label for="channel">
              <span class="label-text">Phương thức nhận mã xác thực OTP <span class="req">*</span></span>
            </label>
            <div class="input-icon-wrap">
              <span class="field-icon" aria-hidden="true">
                <svg class="icon" aria-hidden="true"><use href="#i-chat"/></svg>
              </span>
              <select id="channel" name="channel" class="form-select" required>
                <option value="email" <?= !$emailEnabled ? 'disabled' : '' ?>>Gửi qua Email<?= !$emailEnabled ? ' (chưa kích hoạt)' : ' (Khuyến nghị)' ?></option>
                <option value="phone">Gửi qua tin nhắn Số điện thoại</option>
              </select>
            </div>
          </div>

          <?php render_captcha('forgot_password_request'); ?>

          <div class="auth-actions">
            <button class="btn-submit-action" type="submit" id="submitRequestBtn">
              <span id="submitRequestText">Gửi mã xác thực OTP</span>
              <svg class="icon" aria-hidden="true"><use href="#i-arrow"/></svg>
            </button>
            <a class="btn-back-action" href="login.php">
              Quay lại đăng nhập
            </a>
          </div>
        </form>
      <?php endif; ?>

      <div class="auth-support-row">
        Cần trợ giúp trực tiếp? Gọi ngay hotline: <a href="tel:02086289888" class="support-hotline">0208 628 9888</a>
      </div>

    </div>
  </div>
</div>

<script>
(function() {
  // 1. Xử lý Toggle mắt ẩn/hiện mật khẩu
  var toggleBtns = document.querySelectorAll('.password-toggle-btn');
  toggleBtns.forEach(function(toggle) {
    toggle.addEventListener('click', function(e) {
      e.preventDefault();
      var targetId = toggle.getAttribute('data-target');
      var targetInput = targetId ? document.getElementById(targetId) : null;
      if (!targetInput) return;

      var isPwd = targetInput.type === 'password';
      targetInput.type = isPwd ? 'text' : 'password';
      var newLabel = isPwd ? 'Ẩn mật khẩu' : 'Hiện mật khẩu';
      toggle.setAttribute('aria-label', newLabel);
      toggle.setAttribute('aria-pressed', isPwd ? 'true' : 'false');
      toggle.setAttribute('title', newLabel);

      var eyeOpen = toggle.querySelector('.eye-open');
      var eyeClosed = toggle.querySelector('.eye-closed');
      if (eyeOpen && eyeClosed) {
        eyeOpen.style.display = isPwd ? 'none' : 'block';
        eyeClosed.style.display = isPwd ? 'block' : 'none';
      }
      targetInput.focus();
    });
  });

  // 2. Tự động chuẩn hóa input CCCD và OTP chỉ nhận số
  var cccdInput = document.getElementById('cccd');
  if (cccdInput && !cccdInput.readOnly) {
    cccdInput.addEventListener('input', function() {
      this.value = this.value.replace(/\D/g, '').slice(0, 12);
    });
  }

  var otpInput = document.getElementById('otp');
  if (otpInput) {
    otpInput.addEventListener('input', function() {
      this.value = this.value.replace(/\D/g, '').slice(0, 6);
    });
  }

  // 3. Đếm ngược gửi lại OTP ở Bước 2
  var cdText = document.getElementById('countdownText');
  var resend = document.getElementById('resendLink');
  if (cdText && resend) {
    var sec = 60;
    var timer = setInterval(function() {
      sec--;
      if (sec > 0) {
        cdText.textContent = '(chờ ' + sec + 's)';
      } else {
        clearInterval(timer);
        cdText.style.display = 'none';
        resend.style.display = 'inline';
      }
    }, 1000);
  }

  // 4. Trạng thái gửi form và kiểm tra mật khẩu khớp
  var requestForm = document.getElementById('requestOtpForm');
  var submitRequestBtn = document.getElementById('submitRequestBtn');
  var submitRequestText = document.getElementById('submitRequestText');
  if (requestForm && submitRequestBtn) {
    requestForm.addEventListener('submit', function() {
      var cccdVal = cccdInput ? cccdInput.value.trim() : '';
      if (cccdVal.length !== 12) return;
      submitRequestBtn.disabled = true;
      if (submitRequestText) submitRequestText.textContent = 'Đang gửi mã OTP…';
    });
  }

  var verifyForm = document.getElementById('verifyResetForm');
  var submitVerifyBtn = document.getElementById('submitVerifyBtn');
  var submitVerifyText = document.getElementById('submitVerifyText');
  if (verifyForm && submitVerifyBtn) {
    verifyForm.addEventListener('submit', function(e) {
      var newPwd = document.getElementById('new_password');
      var confirmPwd = document.getElementById('confirm_password');
      if (newPwd && confirmPwd && newPwd.value !== confirmPwd.value) {
        e.preventDefault();
        alert('Mật khẩu xác nhận không khớp với mật khẩu mới. Vui lòng kiểm tra lại!');
        confirmPwd.focus();
        return;
      }
      submitVerifyBtn.disabled = true;
      if (submitVerifyText) submitVerifyText.textContent = 'Đang đổi mật khẩu…';
    });
  }
})();
</script>

<?php render_footer(); ?>
