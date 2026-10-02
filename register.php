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
        set_flash('error', 'Vui lòng nhập đầy đủ thông tin.');
    } elseif (!validate_cccd($cccd)) {
        set_flash('error', 'CCCD phải gồm đúng 12 chữ số.');
    } elseif (!validate_person_name($fullName)) {
        set_flash('error', 'Họ tên không hợp lệ.');
    } elseif (!validate_phone_number($phone)) {
        set_flash('error', 'Số điện thoại không hợp lệ.');
    } elseif ($email !== '' && !$emailEnabled) {
        set_flash('error', 'Hệ thống chưa bật trường Gmail cho bệnh nhân.');
    } elseif ($email !== '' && !validate_email_address($email)) {
        set_flash('error', 'Gmail không hợp lệ.');
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
            set_flash('error', 'CCCD hoặc số điện thoại đã tồn tại.');
        } else {
            try {
                $passwordHash = hash_password($password);
                if ($emailEnabled) {
                    $stmt = $conn->prepare('INSERT INTO patients (cccd, full_name, phone, email, password_hash) VALUES (?, ?, ?, ?, ?)');
                    $stmt->bind_param('sssss', $cccd, $fullName, $phone, $email, $passwordHash);
                } else {
                    $stmt = $conn->prepare('INSERT INTO patients (cccd, full_name, phone, password_hash) VALUES (?, ?, ?, ?)');
                    $stmt->bind_param('ssss', $cccd, $fullName, $phone, $passwordHash);
                }
                $stmt->execute();
                $stmt->close();

                set_flash('success', 'Đăng ký thành công. Bạn có thể đăng nhập ngay.');
                redirect('login.php');
            } catch (Throwable $exception) {
                log_internal_error('patient_register_failed', $exception, ['cccd' => $cccd]);
                if (isset($stmt) && $stmt instanceof mysqli_stmt) {
                    $stmt->close();
                }

                if (is_duplicate_key_exception($exception)) {
                    set_flash('error', 'CCCD, số điện thoại hoặc Gmail đã tồn tại.');
                } else {
                    set_flash('error', 'Không thể tạo tài khoản lúc này. Vui lòng thử lại.');
                }
            }
        }
    }
}

render_header('Đăng ký bệnh nhân');
?>
<div class="card" style="max-width:560px;margin:40px auto;">
  <h1>Đăng ký tài khoản</h1>
  <p class="muted">Tạo tài khoản bệnh nhân để đặt lịch và xem kết quả khám.</p>
  <?php render_flash(); ?>
  <form method="post" class="grid">
    <?php render_form_guard('patient_register'); ?>
    <div>
      <label for="cccd">CCCD</label>
      <input id="cccd" name="cccd" maxlength="12" value="<?= e($_POST['cccd'] ?? '') ?>" required>
    </div>
    <div>
      <label for="name">Họ và tên</label>
      <input id="name" name="name" value="<?= e($_POST['name'] ?? '') ?>" required>
    </div>
    <div>
      <label for="phone">Số điện thoại</label>
      <input id="phone" name="phone" maxlength="15" value="<?= e($_POST['phone'] ?? '') ?>" required>
    </div>
    <div>
      <label for="email">Gmail<?= !$emailEnabled ? ' (chưa sẵn sàng)' : ' (để nhận OTP)' ?></label>
      <input id="email" type="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" <?= !$emailEnabled ? 'disabled' : '' ?>>
    </div>
    <div>
      <label for="password">Mật khẩu</label>
      <input id="password" type="password" name="password" minlength="8" required>
    </div>
    <?php render_captcha('patient_register'); ?>
    <div class="actions">
      <button type="submit">Đăng ký</button>
      <a class="btn btn-secondary" href="login.php">Đến trang đăng nhập</a>
    </div>
  </form>
</div>
<?php render_footer(); ?>
