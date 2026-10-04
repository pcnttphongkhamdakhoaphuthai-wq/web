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
<style>
  .admin-login-container {
    padding: 40px 16px;
    min-height: calc(100vh - 220px);
    display: flex;
    align-items: center;
    justify-content: center;
  }
  .admin-login-card {
    width: 100%;
    max-width: 480px;
    margin: 0 auto;
    background: #ffffff;
    border-radius: 16px;
    padding: 36px 32px;
    box-shadow: 0 10px 30px rgba(0, 35, 71, 0.08), 0 1px 3px rgba(0, 0, 0, 0.04);
    box-sizing: border-box;
  }
  .admin-login-card h1 {
    font-size: 24px;
    font-weight: 700;
    color: #1e293b;
    margin-top: 0;
    margin-bottom: 8px;
    letter-spacing: -0.02em;
  }
  .admin-login-card .admin-login-desc {
    color: #64748b;
    font-size: 14.5px;
    line-height: 1.55;
    margin-bottom: 24px;
  }
  .admin-form-group {
    margin-bottom: 18px;
  }
  .admin-form-group label {
    display: block;
    margin-bottom: 7px;
    font-size: 14px;
    font-weight: 600;
    color: #334155;
    line-height: 1.4;
  }
  .admin-form-group .form-control {
    display: block;
    width: 100%;
    height: 46px;
    min-height: 46px;
    border-radius: 12px;
    border: 1px solid #cbd5e1;
    padding: 0 14px;
    font-size: 15px;
    color: #1e293b;
    background-color: #ffffff;
    box-sizing: border-box;
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
  }
  .admin-form-group .form-control:hover {
    border-color: #94a3b8;
  }
  .admin-form-group .form-control:focus {
    border-color: #0284c7;
    outline: none;
    box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.15);
  }
  .admin-password-wrap {
    position: relative;
    width: 100%;
  }
  .admin-password-wrap .form-control {
    padding-right: 48px;
  }
  .admin-password-toggle {
    position: absolute;
    top: 50%;
    right: 6px;
    transform: translateY(-50%);
    width: 36px;
    height: 36px;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: transparent;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    color: #64748b;
    transition: color 0.15s ease, background-color 0.15s ease;
  }
  .admin-password-toggle:hover {
    color: #0284c7;
    background-color: rgba(2, 132, 199, 0.08);
  }
  .admin-password-toggle:focus-visible {
    outline: 2px solid #0284c7;
    outline-offset: 1px;
  }
  .admin-password-toggle svg {
    width: 20px;
    height: 20px;
    stroke: currentColor;
    pointer-events: none;
  }
  .admin-btn-primary {
    height: 48px;
    min-height: 48px;
    border-radius: 12px;
    font-size: 16px;
    font-weight: 600;
    width: 100%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
  }
  .admin-btn-secondary {
    height: 46px;
    min-height: 46px;
    border-radius: 12px;
    font-size: 15px;
    width: 100%;
    text-align: center;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
  }
</style>

<div class="wrap admin-login-container">
  <div class="card admin-login-card">
    <h1><?= $bootstrapRequired ? 'Khởi tạo admin gốc' : 'Đăng nhập quản trị' ?></h1>
    <p class="muted admin-login-desc">
      <?= $bootstrapRequired
        ? 'Hệ thống chưa có tài khoản admin. Hãy tạo admin gốc đầu tiên ngay trên giao diện này thay vì chèn trực tiếp vào SQL.'
        : 'Dùng tài khoản quản trị hoặc tài khoản nhân viên đã được phân quyền để xử lý kết quả cho bệnh nhân.' ?>
    </p>
    <?php render_flash(); ?>
    <form method="post" class="admin-login-form">
      <?php render_form_guard($bootstrapRequired ? 'admin_bootstrap' : 'admin_login'); ?>

      <?php if ($bootstrapRequired): ?>
      <div class="admin-form-group">
        <label for="full_name">Họ và tên admin gốc</label>
        <input id="full_name" class="form-control" type="text" name="full_name" value="<?= e($_POST['full_name'] ?? '') ?>" autocomplete="name" placeholder="Ví dụ: Nguyễn Văn An" maxlength="100" required>
      </div>

      <div class="admin-form-group">
        <label for="department">Bộ phận công tác</label>
        <input id="department" class="form-control" type="text" name="department" value="<?= e($_POST['department'] ?? '') ?>" autocomplete="organization-title" placeholder="Ví dụ: Ban Quản trị, CNTT, Tiếp đón..." maxlength="100">
      </div>
      <?php endif; ?>

      <div class="admin-form-group">
        <label for="username">Tên đăng nhập</label>
        <input id="username" class="form-control" type="text" name="username" value="<?= e($_POST['username'] ?? '') ?>" autocomplete="username" autocapitalize="none" spellcheck="false" placeholder="<?= $bootstrapRequired ? 'Tên đăng nhập admin gốc' : 'Nhập tên đăng nhập quản trị' ?>" maxlength="50" required>
      </div>

      <div class="admin-form-group">
        <label for="password">Mật khẩu</label>
        <div class="admin-password-wrap">
          <input id="password" class="form-control" type="password" name="password" autocomplete="<?= $bootstrapRequired ? 'new-password' : 'current-password' ?>" placeholder="<?= $bootstrapRequired ? 'Tạo mật khẩu mạnh (tối thiểu 8 ký tự)' : 'Nhập mật khẩu quản trị' ?>" maxlength="100" required>
          <button type="button" class="admin-password-toggle" aria-label="Hiện mật khẩu" aria-pressed="false" data-target="password">
            <svg class="eye-open" viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
              <circle cx="12" cy="12" r="3"></circle>
            </svg>
            <svg class="eye-closed" viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="display: none;">
              <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
              <line x1="1" y1="1" x2="23" y2="23"></line>
            </svg>
          </button>
        </div>
      </div>

      <?php if ($bootstrapRequired): ?>
      <div class="admin-form-group">
        <label for="confirm_password">Xác nhận mật khẩu</label>
        <div class="admin-password-wrap">
          <input id="confirm_password" class="form-control" type="password" name="confirm_password" autocomplete="new-password" placeholder="Nhập lại mật khẩu ở trên" maxlength="100" required>
          <button type="button" class="admin-password-toggle" aria-label="Hiện mật khẩu" aria-pressed="false" data-target="confirm_password">
            <svg class="eye-open" viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
              <circle cx="12" cy="12" r="3"></circle>
            </svg>
            <svg class="eye-closed" viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="display: none;">
              <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
              <line x1="1" y1="1" x2="23" y2="23"></line>
            </svg>
          </button>
        </div>
      </div>
      <?php endif; ?>

      <?php render_captcha($bootstrapRequired ? 'admin_bootstrap' : 'admin_login'); ?>

      <div style="display: flex; flex-direction: column; gap: 12px; margin-top: 14px;">
        <button type="submit" class="btn btn-primary admin-btn-primary"><?= $bootstrapRequired ? 'Tạo admin gốc' : 'Đăng nhập' ?></button>
        <a class="btn btn-secondary admin-btn-secondary" href="login.php">Về trang bệnh nhân</a>
      </div>
    </form>
  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    var toggleButtons = document.querySelectorAll('.admin-password-toggle');
    toggleButtons.forEach(function (button) {
      button.addEventListener('click', function (e) {
        e.preventDefault();
        var targetId = button.getAttribute('data-target');
        var input = targetId ? document.getElementById(targetId) : null;
        if (!input) {
          var wrap = button.closest('.admin-password-wrap');
          input = wrap ? wrap.querySelector('input') : null;
        }
        if (!input) return;

        var isPassword = input.type === 'password';
        input.type = isPassword ? 'text' : 'password';
        button.setAttribute('aria-pressed', isPassword ? 'true' : 'false');
        button.setAttribute('aria-label', isPassword ? 'Ẩn mật khẩu' : 'Hiện mật khẩu');

        var eyeOpen = button.querySelector('.eye-open');
        var eyeClosed = button.querySelector('.eye-closed');
        if (eyeOpen && eyeClosed) {
          eyeOpen.style.display = isPassword ? 'none' : 'block';
          eyeClosed.style.display = isPassword ? 'block' : 'none';
        }
        input.focus();
      });
    });
  });
</script>
<?php render_footer(); ?>
