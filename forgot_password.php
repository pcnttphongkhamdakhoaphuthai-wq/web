<?php
declare(strict_types=1);

require_once 'config.php';

$step = (string) ($_GET['step'] ?? 'request');
$emailEnabled = patient_email_enabled();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($step === 'request') {
        $guardError = validate_form_guard('forgot_password_request', 5, 600, 0, true);
        $cccd = normalize_single_line_input($_POST['cccd'] ?? '');
        $channel = normalize_single_line_input($_POST['channel'] ?? 'email');

        if ($guardError !== null) {
            set_flash('error', $guardError);
        } elseif (!validate_cccd($cccd)) {
            set_flash('error', 'CCCD không hợp lệ.');
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

            if ($patient && !($channel === 'email' && !$emailEnabled)) {
                try {
                    $delivery = issue_password_reset_otp($patient, $channel);
                    set_flash('success', 'Đã gửi OTP qua ' . $delivery['channel'] . ' tới ' . $delivery['destination'] . '.');
                    redirect('forgot_password.php?step=verify&cccd=' . urlencode($cccd));
                } catch (Throwable $exception) {
                    log_internal_error('forgot_password_request_failed', $exception, ['cccd' => $cccd, 'channel' => $channel]);
                    set_flash('success', 'Nếu thông tin hợp lệ, hệ thống sẽ gửi OTP qua kênh đã chọn.');
                }
            } else {
                set_flash('success', 'Nếu thông tin hợp lệ, hệ thống sẽ gửi OTP qua kênh đã chọn.');
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
            set_flash('error', 'CCCD không hợp lệ.');
        } elseif (!preg_match('/^\d{6}$/', $otp)) {
            set_flash('error', 'OTP phải gồm 6 chữ số.');
        } elseif ($passwordError !== null) {
            set_flash('error', $passwordError);
        } elseif ($newPassword !== $confirmPassword) {
            set_flash('error', 'Xác nhận mật khẩu mới chưa khớp.');
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
                set_flash('success', 'Đặt lại mật khẩu thành công. Bạn có thể đăng nhập lại.');
                redirect('login.php');
            } catch (Throwable $exception) {
                log_internal_error('forgot_password_verify_failed', $exception, ['cccd' => $cccd]);
                set_flash('error', 'Không thể đặt lại mật khẩu. Vui lòng kiểm tra OTP và thử lại.');
            }
        }
    }
}

render_header('Quên mật khẩu');
?>
<div class="card" style="max-width:620px;margin:40px auto;">
  <h1>Quên mật khẩu</h1>
  <p class="muted">Nhập CCCD để nhận mã OTP qua Gmail hoặc số điện thoại đã đăng ký. Kênh gửi OTP cần được cấu hình để hoạt động thực tế.</p>
  <?php render_flash(); ?>

  <?php if ($step === 'verify'): ?>
    <form method="post" class="grid">
      <?php render_form_guard('forgot_password_verify'); ?>
      <div>
        <label for="cccd">CCCD</label>
        <input id="cccd" name="cccd" maxlength="12" value="<?= e($_GET['cccd'] ?? $_POST['cccd'] ?? '') ?>" required>
      </div>
      <div>
        <label for="otp">Mã OTP</label>
        <input id="otp" name="otp" inputmode="numeric" maxlength="6" required>
      </div>
      <div>
        <label for="new_password">Mật khẩu mới</label>
        <input id="new_password" type="password" name="new_password" minlength="8" required>
      </div>
      <div>
        <label for="confirm_password">Xác nhận mật khẩu mới</label>
        <input id="confirm_password" type="password" name="confirm_password" minlength="8" required>
      </div>
      <div class="actions">
        <button type="submit">Đặt lại mật khẩu</button>
        <a class="btn btn-secondary" href="forgot_password.php">Gửi lại OTP</a>
      </div>
    </form>
  <?php else: ?>
    <form method="post" class="grid">
      <?php render_form_guard('forgot_password_request'); ?>
      <div>
        <label for="cccd">CCCD</label>
        <input id="cccd" name="cccd" maxlength="12" value="<?= e($_POST['cccd'] ?? '') ?>" required>
      </div>
      <div>
        <label for="channel">Kênh nhận OTP</label>
        <select id="channel" name="channel" required>
          <option value="email" <?= !$emailEnabled ? 'disabled' : '' ?>>Gmail<?= !$emailEnabled ? ' (chưa sẵn sàng)' : '' ?></option>
          <option value="phone">Số điện thoại</option>
        </select>
      </div>
      <?php render_captcha('forgot_password_request'); ?>
      <div class="actions">
        <button type="submit">Gửi mã OTP</button>
        <a class="btn btn-secondary" href="login.php">Quay lại đăng nhập</a>
      </div>
    </form>
  <?php endif; ?>
</div>
<?php render_footer(); ?>
