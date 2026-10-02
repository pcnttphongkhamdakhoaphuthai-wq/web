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
<div class="wrap" style="margin-top:40px;">
  <?php render_flash(); ?>
  <section class="card" style="max-width:720px;margin:0 auto;">
    <div class="panel-title">
      <div>
        <h1>Tài khoản nhân viên</h1>
        <p class="muted">Đổi mật khẩu đăng nhập quản trị cho tài khoản <?= e((string) ($_SESSION['admin_username'] ?? '')) ?>.</p>
      </div>
      <a class="btn btn-secondary" href="<?= e(admin_home_path()) ?>">Quay lại</a>
    </div>

    <form method="post" class="grid">
      <?php render_form_guard('admin_profile'); ?>
      <div>
        <label for="current_password">Mật khẩu hiện tại</label>
        <input id="current_password" type="password" name="current_password" required>
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
        <button type="submit">Đổi mật khẩu</button>
      </div>
    </form>
  </section>
</div>
<?php render_footer(); ?>
