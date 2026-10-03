<?php
declare(strict_types=1);

require_once 'config.php';

if (isset($_SESSION['admin_id'])) {
    refresh_admin_session();
    redirect(admin_home_path());
}

$bootstrapRequired = !has_any_admin_account();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($bootstrapRequired) {
        $guardError = validate_form_guard('admin_bootstrap', 3, 1800, 0, true);
        $username = normalize_single_line_input($_POST['username'] ?? '');
        $fullName = normalize_single_line_input($_POST['full_name'] ?? '');
        $department = normalize_single_line_input($_POST['department'] ?? '');
        $password = (string) ($_POST['password'] ?? '');
        $confirmPassword = (string) ($_POST['confirm_password'] ?? '');
        $passwordError = validate_password_strength($password);

        if ($guardError !== null) {
            set_flash('error', $guardError);
        } elseif (!validate_username_format($username) || !validate_person_name($fullName) || ($department !== '' && !validate_generic_label($department))) {
            set_flash('error', 'Thông tin admin gốc không hợp lệ.');
        } elseif ($username === '' || $fullName === '' || $password === '') {
            set_flash('error', 'Vui lòng nhập đầy đủ thông tin admin gốc.');
        } elseif ($passwordError !== null) {
            set_flash('error', $passwordError);
        } elseif ($password !== $confirmPassword) {
            set_flash('error', 'Xác nhận mật khẩu mới chưa khớp.');
        } else {
            try {
                $admin = create_initial_root_admin($username, $fullName, $password, $department);
                session_regenerate_id(true);
                load_admin_session($admin);
                mark_session_authenticated();
                clear_login_submit_attempts('admin_login_submit', $username);
                set_flash('success', 'Đã tạo admin gốc đầu tiên. Bạn đã được đăng nhập.');
                redirect(admin_home_path());
            } catch (Throwable $exception) {
                log_internal_error('initial_root_admin_create_failed', $exception, ['username' => $username]);
                set_flash('error', 'Không thể tạo admin gốc lúc này. Vui lòng thử lại.');
            }
        }
    } else {
        $guardError = validate_form_guard('admin_login', 6, 900, 0, true);
        $username = normalize_single_line_input($_POST['username'] ?? '');
        $password = (string) ($_POST['password'] ?? '');
        $cooldownIdentity = $username !== '' ? $username : 'guest';
        $cooldownRemaining = login_submit_cooldown_remaining('admin_login_submit', $cooldownIdentity, 2, 10);
        $lockedUntil = $username !== '' ? get_login_lockout('admin_login', $username) : null;

        if ($guardError !== null) {
            security_log('admin_login_guard_blocked', ['username' => $username]);
            set_flash('error', $guardError);
        } elseif ($cooldownRemaining > 0) {
            set_flash('error', 'Bạn thao tác quá nhanh. Vui lòng chờ ' . $cooldownRemaining . ' giây rồi thử lại.');
        } elseif ($lockedUntil !== null) {
            security_log('admin_login_locked', ['username' => $username, 'locked_until' => $lockedUntil]);
            set_flash('error', 'Tài khoản quản trị tạm thời bị khóa trong 24 giờ do đăng nhập sai quá 5 lần.');
        } elseif ($username === '' || $password === '') {
            set_flash('error', 'Vui lòng nhập tài khoản quản trị.');
        } elseif (!validate_username_format($username)) {
            set_flash('error', 'Tên đăng nhập không hợp lệ.');
        } else {
            register_login_submit_attempt('admin_login_submit', $cooldownIdentity, 10);
            $stmt = $conn->prepare(
                'SELECT *
                 FROM admins
                 WHERE username = ?
                 LIMIT 1'
            );
            $stmt->bind_param('s', $username);
            $stmt->execute();
            $admin = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            $temporaryPasswordRecord = $admin ? verify_temporary_admin_password($username, $password) : null;

            if ($admin && (int) $admin['is_active'] === 1 && (verify_password($password, $admin['password_hash']) || $temporaryPasswordRecord !== null)) {
                session_regenerate_id(true);
                load_admin_session($admin);
                mark_session_authenticated();
                if ($temporaryPasswordRecord !== null) {
                    $_SESSION['admin_temporary_password_hash'] = hash('sha256', $password);
                    clear_temporary_admin_password($username);
                }
                clear_login_failures('admin_login', $username);
                clear_login_submit_attempts('admin_login_submit', $cooldownIdentity);
                $eventName = $temporaryPasswordRecord !== null ? 'admin_temporary_password_login_success' : 'admin_login_success';
                security_log($eventName, ['username' => $username, 'admin_id' => (int) $admin['id']]);
                audit_log($eventName, ['username' => $username, 'admin_id' => (int) $admin['id']]);
                if ($temporaryPasswordRecord !== null) {
                    set_flash('success', 'Bạn đang đăng nhập bằng mật khẩu tạm. Vui lòng đổi mật khẩu mới.');
                    redirect('admin_profile.php');
                }
                set_flash('success', 'Đăng nhập quản trị thành công.');
                redirect(admin_home_path());
            }

            $nextLock = record_login_failure('admin_login', $username, 5, 86400);
            security_log('admin_login_failed', ['username' => $username, 'locked_until' => $nextLock]);
            set_flash('error', 'Sai tên đăng nhập hoặc mật khẩu.');
        }
    }
}

render_header('Đăng nhập quản trị');
?>
<div class="wrap" style="padding: 40px 0; min-height: calc(100vh - 220px); display: flex; align-items: center; justify-content: center;">
  <div class="card" style="width: 100%; max-width: 480px; margin: 0 auto;">
    <h1><?= $bootstrapRequired ? 'Khởi tạo admin gốc' : 'Đăng nhập quản trị' ?></h1>
    <p class="muted">
      <?= $bootstrapRequired
        ? 'Hệ thống chưa có tài khoản admin. Hãy tạo admin gốc đầu tiên ngay trên giao diện này thay vì chèn trực tiếp vào SQL.'
        : 'Dùng tài khoản quản trị hoặc tài khoản nhân viên đã được phân quyền để xử lý kết quả cho bệnh nhân.' ?>
    </p>
    <?php render_flash(); ?>
    <form method="post" class="grid">
      <?php render_form_guard($bootstrapRequired ? 'admin_bootstrap' : 'admin_login'); ?>
      <?php if ($bootstrapRequired): ?>
      <div>
        <label for="full_name">Họ tên admin gốc</label>
        <input id="full_name" name="full_name" value="<?= e($_POST['full_name'] ?? '') ?>" required>
      </div>
      <div>
        <label for="department">Bộ phận</label>
        <input id="department" name="department" value="<?= e($_POST['department'] ?? '') ?>">
      </div>
      <?php endif; ?>
      <div>
        <label for="username">Tên đăng nhập</label>
        <input id="username" name="username" value="<?= e($_POST['username'] ?? '') ?>" required>
      </div>
      <div>
        <label for="password">Mật khẩu</label>
        <input id="password" type="password" name="password" required>
      </div>
      <?php if ($bootstrapRequired): ?>
      <div>
        <label for="confirm_password">Xác nhận mật khẩu</label>
        <input id="confirm_password" type="password" name="confirm_password" required>
      </div>
      <?php endif; ?>
      <?php render_captcha($bootstrapRequired ? 'admin_bootstrap' : 'admin_login'); ?>
      <div style="display: flex; flex-direction: column; gap: 12px; margin-top: 10px;">
        <button type="submit" class="btn btn-primary" style="height: 48px; font-size: 16px; width: 100%;"><?= $bootstrapRequired ? 'Tạo admin gốc' : 'Đăng nhập' ?></button>
        <a class="btn btn-secondary" href="login.php" style="width: 100%; text-align: center; height: 44px;">Về trang bệnh nhân</a>
      </div>
    </form>
  </div>
</div>
<?php render_footer(); ?>
