<?php
declare(strict_types=1);

require_once 'config.php';

if (isset($_SESSION['user_id'])) {
    redirect('dashboard.php');
}

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
        set_flash('error', 'Tài khoản tạm thời bị khóa trong 24 giờ do đăng nhập sai quá 5 lần. Bạn có thể dùng quên mật khẩu.');
    } elseif ($cccd === '' || $password === '') {
        set_flash('error', 'Vui lòng nhập CCCD và mật khẩu.');
    } elseif (!validate_cccd($cccd)) {
        set_flash('error', 'CCCD không hợp lệ.');
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
        set_flash('error', 'CCCD hoặc mật khẩu không đúng.');
    }
}

render_header('Đăng nhập bệnh nhân');
render_hero('Cổng hỗ trợ dịch vụ', 'Đăng nhập bằng CCCD để đặt lịch, tra cứu kết quả, hồ sơ bệnh án và nhận hỗ trợ trực tuyến.');
?>
<div class="wrap">
  <section class="section">
    <h2 class="section-title">Dịch vụ trực tuyến</h2>
    <div class="grid grid-4">
      <article class="service-card"><div class="service-icon">L</div><h3>Đặt lịch khám</h3><p>Đăng ký lịch hẹn với bác sĩ theo khung giờ phù hợp.</p></article>
      <article class="service-card"><div class="service-icon">K</div><h3>Kết quả khám</h3><p>Xem chẩn đoán, đơn thuốc và tải file kết quả.</p></article>
      <article class="service-card"><div class="service-icon">H</div><h3>Hỗ trợ</h3><p>Giải đáp thông tin hồ sơ, tài khoản và hướng dẫn sử dụng.</p></article>
      <article class="service-card"><div class="service-icon">B</div><h3>BHYT</h3><p>Khu vực sẵn sàng để mở rộng nghiệp vụ bảo hiểm và thanh toán.</p></article>
    </div>
  </section>

  <section class="section card" id="login-form" style="max-width:520px;margin:0 auto;scroll-margin-top:130px;">
    <h1>Đăng nhập bằng CCCD</h1>
    <p class="section-lead">Dùng CCCD đã đăng ký để truy cập hồ sơ, lịch khám và kết quả gần nhất.</p>
    <?php render_flash(); ?>
    <form method="post" class="grid">
      <?php render_form_guard('patient_login'); ?>
      <div>
        <label for="cccd">CCCD</label>
        <input id="cccd" name="cccd" maxlength="12" value="<?= e($_POST['cccd'] ?? '') ?>" required>
      </div>
      <div>
        <label for="password">Mật khẩu</label>
        <input id="password" type="password" name="password" required>
      </div>
      <?php render_captcha('patient_login'); ?>
      <div class="actions">
        <button type="submit">Đăng nhập</button>
        <a class="btn btn-secondary" href="register.php">Tạo tài khoản</a>
      </div>
      <div class="text-sm"><a href="forgot_password.php">Quên mật khẩu?</a></div>
      <div class="text-sm staff-login-link"><a href="admin_login.php">Đăng nhập nhân viên</a></div>
    </form>
  </section>
</div>
<?php render_footer(); ?>
