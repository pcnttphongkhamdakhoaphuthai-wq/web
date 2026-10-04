<?php
declare(strict_types=1);

require_once 'config.php';
require_admin_login();

$adminId = (int) ($_SESSION['admin_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $guardError = validate_form_guard('admin_profile', 8, 600, 0, false);
    $currentPassword = (string) ($_POST['current_password'] ?? '');
    $newPassword = (string) ($_POST['new_password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');
    $passwordError = $newPassword !== '' ? validate_password_strength($newPassword) : 'Vui lòng nhập mật khẩu mới.';

    if ($guardError !== null) {
        set_flash('error', $guardError);
        redirect('admin_profile.php');
    }

    if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
        set_flash('error', 'Vui lòng nhập đầy đủ mật khẩu hiện tại, mật khẩu mới và xác nhận.');
        redirect('admin_profile.php');
    }

    if ($passwordError !== null) {
        set_flash('error', $passwordError);
        redirect('admin_profile.php');
    }

    if ($newPassword !== $confirmPassword) {
        set_flash('error', 'Mật khẩu mới và xác nhận mật khẩu chưa khớp.');
        redirect('admin_profile.php');
    }

    $stmt = $conn->prepare('SELECT password_hash FROM admins WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $adminId);
    $stmt->execute();
    $admin = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $temporaryPasswordHash = (string) ($_SESSION['admin_temporary_password_hash'] ?? '');
    $currentPasswordMatches = $admin && verify_password($currentPassword, (string) $admin['password_hash']);
    $temporaryPasswordMatches = $temporaryPasswordHash !== '' && hash_equals($temporaryPasswordHash, hash('sha256', $currentPassword));

    if (!$currentPasswordMatches && !$temporaryPasswordMatches) {
        set_flash('error', 'Mật khẩu hiện tại không đúng.');
        redirect('admin_profile.php');
    }

    $passwordHash = hash_password($newPassword);
    $stmt = $conn->prepare('UPDATE admins SET password_hash = ? WHERE id = ?');
    $stmt->bind_param('si', $passwordHash, $adminId);
    $stmt->execute();
    $stmt->close();

    unset($_SESSION['admin_temporary_password_hash']);
    audit_log('admin_password_changed', ['admin_id' => $adminId]);
    set_flash('success', 'Đã đổi mật khẩu tài khoản nhân viên.');
    redirect('admin_profile.php');
}

render_header('Tài khoản nhân viên');
?>
<style>
.admin-profile-card {
  max-width: 720px;
  margin: 0 auto;
  border-radius: 16px;
}
.admin-profile-card .panel-title {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 16px;
  margin-bottom: 24px;
}
.admin-profile-card .field {
  display: flex;
  flex-direction: column;
  gap: 6px;
}
.admin-profile-card .password-wrap {
  position: relative;
  width: 100%;
}
.admin-profile-card .password-wrap input {
  width: 100%;
  height: 46px;
  padding: 0 46px 0 16px;
  border-radius: 10px;
  border: 1px solid var(--line, #badcf6);
  font-size: 15px;
  background: #fff;
  transition: border-color 0.2s, box-shadow 0.2s;
  box-sizing: border-box;
}
.admin-profile-card .password-wrap input:focus {
  outline: none;
  border-color: #005fa0;
  box-shadow: 0 0 0 3px rgba(0, 95, 160, 0.15);
}
.admin-profile-card .password-toggle {
  position: absolute;
  top: 1px;
  right: 1px;
  bottom: 1px;
  width: 44px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: transparent;
  border: none;
  cursor: pointer;
  color: #536f8c;
  border-radius: 0 9px 9px 0;
  padding: 0;
  transition: color 0.15s, background-color 0.15s;
}
.admin-profile-card .password-toggle:hover {
  color: #005fa0;
  background-color: rgba(0, 95, 160, 0.05);
}
.admin-profile-card .password-toggle.active {
  color: #005fa0;
}
.admin-profile-card .password-toggle .icon {
  width: 20px;
  height: 20px;
}
.btn-save-pwd {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  height: 46px;
  min-height: 46px;
  padding: 0 24px;
  border-radius: 12px;
  border: none;
  background: linear-gradient(135deg, #005fa0 0%, #00b4d8 100%);
  color: #ffffff !important;
  font-size: 15px;
  font-weight: 600;
  cursor: pointer;
  box-shadow: 0 4px 14px rgba(0, 95, 160, 0.25);
  transition: transform 0.15s, box-shadow 0.15s, filter 0.15s;
  text-decoration: none;
}
.btn-save-pwd:hover {
  transform: translateY(-1px);
  box-shadow: 0 6px 18px rgba(0, 95, 160, 0.35);
  filter: brightness(1.05);
}
.btn-save-pwd:active {
  transform: translateY(0);
}
.btn-back-phuthai {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  height: 46px;
  min-height: 46px;
  padding: 0 20px;
  border-radius: 12px;
  border: 1.5px solid #badcf6;
  background: linear-gradient(135deg, #e7f5ff 0%, #f0f9ff 100%);
  color: #005fa0 !important;
  font-size: 15px;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.15s, border-color 0.15s, transform 0.15s;
  text-decoration: none;
}
.btn-back-phuthai:hover {
  background: linear-gradient(135deg, #dcf0fd 0%, #e8f5fe 100%);
  border-color: #005fa0;
  transform: translateY(-1px);
}
.btn-back-phuthai:active {
  transform: translateY(0);
}
</style>
<div class="wrap" style="margin-top:40px; margin-bottom:60px;">
  <?php render_flash(); ?>
  <section class="card admin-profile-card">
    <div class="panel-title">
      <div>
        <h1 style="margin:0 0 6px;">Tài khoản nhân viên</h1>
        <p class="muted" style="margin:0;">Đổi mật khẩu đăng nhập quản trị cho tài khoản <strong><?= e((string) ($_SESSION['admin_username'] ?? '')) ?></strong>.</p>
      </div>
      <a class="btn-back-phuthai" href="<?= e(admin_home_path()) ?>">
        <svg class="icon" style="width:16px;height:16px;transform:rotate(180deg);" aria-hidden="true"><use href="#i-arrow"/></svg>
        <span>Quay lại</span>
      </a>
    </div>

    <form method="post" class="grid" id="adminPasswordForm" style="display:flex; flex-direction:column; gap:20px;">
      <?php render_form_guard('admin_profile'); ?>
      <div class="field">
        <label for="current_password" style="font-weight:600; color:var(--ink);">Mật khẩu hiện tại <span style="color:var(--danger)">*</span></label>
        <div class="password-wrap">
          <input id="current_password" type="password" name="current_password" autocomplete="current-password" placeholder="Nhập mật khẩu hiện tại" required>
          <button type="button" class="password-toggle" aria-label="Hiện mật khẩu" aria-pressed="false" tabindex="-1">
            <svg class="icon" aria-hidden="true"><use href="#i-eye"/></svg>
          </button>
        </div>
      </div>
      <div class="field">
        <label for="new_password" style="font-weight:600; color:var(--ink);">Mật khẩu mới <span style="color:var(--danger)">*</span></label>
        <div class="password-wrap">
          <input id="new_password" type="password" name="new_password" minlength="8" autocomplete="new-password" placeholder="Tối thiểu 8 ký tự, gồm chữ hoa, chữ thường và số" required>
          <button type="button" class="password-toggle" aria-label="Hiện mật khẩu" aria-pressed="false" tabindex="-1">
            <svg class="icon" aria-hidden="true"><use href="#i-eye"/></svg>
          </button>
        </div>
      </div>
      <div class="field">
        <label for="confirm_password" style="font-weight:600; color:var(--ink);">Xác nhận mật khẩu mới <span style="color:var(--danger)">*</span></label>
        <div class="password-wrap">
          <input id="confirm_password" type="password" name="confirm_password" minlength="8" autocomplete="new-password" placeholder="Nhập lại mật khẩu mới để xác nhận" required>
          <button type="button" class="password-toggle" aria-label="Hiện mật khẩu" aria-pressed="false" tabindex="-1">
            <svg class="icon" aria-hidden="true"><use href="#i-eye"/></svg>
          </button>
        </div>
      </div>
      <div class="actions" style="display:flex; gap:12px; align-items:center; flex-wrap:wrap; margin-top:8px;">
        <button type="submit" class="btn-save-pwd">
          <svg class="icon" style="width:18px;height:18px;" aria-hidden="true"><use href="#i-check"/></svg>
          <span>Lưu thay đổi mật khẩu</span>
        </button>
        <a class="btn-back-phuthai" href="<?= e(admin_home_path()) ?>">
          <svg class="icon" style="width:16px;height:16px;transform:rotate(180deg);" aria-hidden="true"><use href="#i-arrow"/></svg>
          <span>Quay lại</span>
        </a>
      </div>
    </form>
  </section>
</div>

<script>
(function() {
  function initPasswordToggles() {
    var wraps = document.querySelectorAll('.password-wrap');
    wraps.forEach(function(wrap) {
      var input = wrap.querySelector('input');
      var btn = wrap.querySelector('.password-toggle');
      if (!input || !btn) return;

      btn.addEventListener('click', function(e) {
        e.preventDefault();
        var isPwd = input.type === 'password';
        input.type = isPwd ? 'text' : 'password';
        btn.setAttribute('aria-pressed', String(isPwd));
        btn.setAttribute('aria-label', isPwd ? 'Ẩn mật khẩu' : 'Hiện mật khẩu');
        btn.classList.toggle('active', isPwd);
        input.focus();
      });
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initPasswordToggles);
  } else {
    initPasswordToggles();
  }
})();
</script>
<?php render_footer(); ?>
