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

<div class="main-area" style="min-height: calc(100vh - 200px); display: flex; align-items: center; justify-content: center; padding: 36px 0;">
  <div class="container">
    <div class="auth-box">
      
      <div style="display: flex; align-items: center; gap: 16px; margin-bottom: 18px;">
        <span class="card-icon" style="width: 52px; height: 52px;">
          <svg class="icon" aria-hidden="true"><use href="#i-help"/></svg>
        </span>
        <div>
          <span class="card-kicker">CẤP LẠI MẬT KHẨU</span>
          <h1 style="font-size: 24px; font-weight: 700; color: var(--ink); margin: 2px 0 0;">Lấy lại mật khẩu</h1>
        </div>
      </div>

      <p style="font-size: 14.5px; color: var(--muted); line-height: 1.55; margin: 0 0 20px;">
        Xác thực số Căn cước công dân để nhận mã OTP khôi phục quyền truy cập hồ sơ khám bệnh.
      </p>

      <?php render_flash(); ?>

      <?php if ($step === 'verify'): ?>
        <!-- BƯỚC 2: NHẬP OTP VÀ ĐỔI MẬT KHẨU MỚI -->
        <form method="post" style="display: grid; gap: 16px;">
          <?php render_form_guard('forgot_password_verify'); ?>
          
          <div class="field" style="margin-bottom: 0;">
            <label for="cccd">Số Căn cước công dân (CCCD)</label>
            <input id="cccd" name="cccd" type="text" inputmode="numeric" maxlength="12" value="<?= e($_GET['cccd'] ?? $_POST['cccd'] ?? '') ?>" required readonly style="background: var(--soft); font-weight: 600;">
          </div>

          <div class="field" style="margin-bottom: 0;">
            <label for="otp">Mã xác thực OTP (6 chữ số)</label>
            <input id="otp" name="otp" type="text" inputmode="numeric" maxlength="6" placeholder="Nhập 6 chữ số OTP" required autofocus>
            <p class="field-hint">Mã OTP có thời hạn hiệu lực trong vòng 10 phút.</p>
          </div>

          <!-- Checklist quy tắc mật khẩu -->
          <div style="background: var(--soft); padding: 14px 16px; border-radius: 12px; border: 1px solid #cce5f8; font-size: 13px; color: var(--ink);">
            <strong style="display: block; margin-bottom: 6px; color: var(--blue);">Yêu cầu mật khẩu mới an toàn:</strong>
            <div style="display: grid; gap: 4px; color: var(--muted);">
              <div>• Tối thiểu 8 ký tự</div>
              <div>• Gồm ít nhất 1 chữ hoa, 1 chữ thường và 1 chữ số</div>
            </div>
          </div>

          <div class="field" style="margin-bottom: 0;">
            <label for="new_password">Mật khẩu mới</label>
            <input id="new_password" type="password" name="new_password" minlength="8" placeholder="Nhập mật khẩu mới" required>
          </div>

          <div class="field" style="margin-bottom: 0;">
            <label for="confirm_password">Xác nhận lại mật khẩu mới</label>
            <input id="confirm_password" type="password" name="confirm_password" minlength="8" placeholder="Nhập lại mật khẩu mới" required>
          </div>

          <div style="display: flex; flex-direction: column; gap: 12px; margin-top: 8px;">
            <button class="btn btn-primary btn-block" type="submit" style="height: 48px; font-size: 16px;">
              Xác nhận đổi mật khẩu <svg class="icon" style="width:18px;height:18px;" aria-hidden="true"><use href="#i-arrow"/></svg>
            </button>
            
            <div style="text-align: center; font-size: 13.5px; color: var(--muted); margin-top: 6px;">
              Chưa nhận được mã? <span id="countdownText" style="color: var(--blue); font-weight: 600;">(chờ 60s)</span>
              <a id="resendLink" href="forgot_password.php?cccd=<?= urlencode((string)($_GET['cccd'] ?? '')) ?>" style="display: none; color: var(--blue); font-weight: 700; text-decoration: underline;">Gửi lại mã OTP</a>
            </div>
          </div>
        </form>

        <script>
        (function(){
          var sec = 60;
          var cdText = document.getElementById('countdownText');
          var resend = document.getElementById('resendLink');
          var timer = setInterval(function(){
            sec--;
            if (sec > 0) {
              if (cdText) cdText.textContent = '(chờ ' + sec + 's)';
            } else {
              clearInterval(timer);
              if (cdText) cdText.style.display = 'none';
              if (resend) resend.style.display = 'inline';
            }
          }, 1000);
        })();
        </script>

      <?php else: ?>
        <!-- BƯỚC 1: NHẬP SỐ CCCD ĐỂ YÊU CẦU OTP -->
        <form method="post" style="display: grid; gap: 16px;">
          <?php render_form_guard('forgot_password_request'); ?>
          
          <div class="field" style="margin-bottom: 0;">
            <label for="cccd">Số Căn cước công dân (CCCD)</label>
            <input id="cccd" name="cccd" type="text" inputmode="numeric" maxlength="12" placeholder="Nhập 12 chữ số CCCD đã đăng ký" value="<?= e($_POST['cccd'] ?? $_GET['cccd'] ?? '') ?>" required autofocus>
            <p class="field-hint">Dùng số CCCD đã đăng ký trong hồ sơ tại phòng khám.</p>
          </div>

          <div class="field" style="margin-bottom: 0;">
            <label for="channel">Phương thức nhận mã xác thực OTP</label>
            <select id="channel" name="channel" required style="width: 100%; height: 44px; border: 1px solid var(--line); border-radius: 10px; padding: 0 12px; background: #fff; font-size: 15px; color: var(--ink);">
              <option value="email" <?= !$emailEnabled ? 'disabled' : '' ?>>Gửi qua Email<?= !$emailEnabled ? ' (chưa kích hoạt)' : ' (Khuyến nghị)' ?></option>
              <option value="phone">Gửi qua tin nhắn Số điện thoại</option>
            </select>
          </div>

          <?php render_captcha('forgot_password_request'); ?>

          <div style="display: flex; flex-direction: column; gap: 12px; margin-top: 8px;">
            <button class="btn btn-primary btn-block" type="submit" style="height: 48px; font-size: 16px;">
              Gửi mã xác thực OTP <svg class="icon" style="width:18px;height:18px;" aria-hidden="true"><use href="#i-arrow"/></svg>
            </button>
            <a class="btn btn-outline btn-block" href="login.php" style="text-align: center;">
              Quay lại đăng nhập
            </a>
          </div>
        </form>
      <?php endif; ?>

      <div style="margin-top: 24px; padding-top: 18px; border-top: 1px solid var(--line); font-size: 13.5px; color: var(--muted); text-align: center;">
        Cần trợ giúp trực tiếp? Gọi ngay hotline: <a href="tel:02086289888" style="color: var(--blue); font-weight: 700;">0208 628 9888</a>
      </div>

    </div>
  </div>
</div>
<?php render_footer(); ?>
