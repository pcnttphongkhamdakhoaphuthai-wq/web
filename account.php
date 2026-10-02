<?php
declare(strict_types=1);

require_once 'config.php';
require_patient_login();

$userId = (int) $_SESSION['user_id'];
$emailEnabled = patient_email_enabled();
$mustChangePassword = patient_requires_password_change();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $guardError = validate_form_guard('patient_account', 8, 600, 0, false);
    $fullName = normalize_single_line_input($_POST['full_name'] ?? '');
    $phone = normalize_single_line_input($_POST['phone'] ?? '');
    $email = normalize_single_line_input($_POST['email'] ?? '');
    $currentPassword = (string) ($_POST['current_password'] ?? '');
    $newPassword = (string) ($_POST['new_password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');
    $passwordError = $newPassword !== '' ? validate_password_strength($newPassword) : null;
    $temporaryPasswordHash = (string) ($_SESSION['temporary_password_hash'] ?? '');

    if ($guardError !== null) {
        set_flash('error', $guardError);
    } elseif ($fullName === '' || $phone === '') {
        set_flash('error', 'Vui lòng nhập đầy đủ họ tên và số điện thoại.');
    } elseif (!validate_person_name($fullName)) {
        set_flash('error', 'Họ tên không hợp lệ.');
    } elseif (!validate_phone_number($phone)) {
        set_flash('error', 'Số điện thoại không hợp lệ.');
    } elseif ($email !== '' && !$emailEnabled) {
        set_flash('error', 'Hệ thống chưa bật trường Gmail cho bệnh nhân.');
    } elseif ($email !== '' && !validate_email_address($email)) {
        set_flash('error', 'Gmail không hợp lệ.');
    } else {
        if ($emailEnabled) {
            $stmt = $conn->prepare('SELECT id FROM patients WHERE (phone = ? OR (? <> "" AND email = ?)) AND id <> ? LIMIT 1');
            $stmt->bind_param('sssi', $phone, $email, $email, $userId);
        } else {
            $stmt = $conn->prepare('SELECT id FROM patients WHERE phone = ? AND id <> ? LIMIT 1');
            $stmt->bind_param('si', $phone, $userId);
        }
        $stmt->execute();
        $phoneExists = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($phoneExists) {
            set_flash('error', 'Số điện thoại này đã được dùng cho tài khoản khác.');
        } else {
            $stmt = $conn->prepare('SELECT password_hash FROM patients WHERE id = ? LIMIT 1');
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $currentUser = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($mustChangePassword && ($currentPassword === '' || $newPassword === '' || $confirmPassword === '')) {
                set_flash('error', 'Bạn cần đổi mật khẩu tạm thời trước khi tiếp tục.');
                redirect('account.php');
            }

            if ($newPassword !== '' || $confirmPassword !== '' || $currentPassword !== '' || $mustChangePassword) {
                if ($currentPassword === '') {
                    set_flash('error', 'Vui lòng nhập mật khẩu hiện tại để đổi mật khẩu.');
                    redirect('account.php');
                }

                if ($mustChangePassword) {
                    if ($temporaryPasswordHash === '' || !hash_equals($temporaryPasswordHash, hash('sha256', $currentPassword))) {
                        set_flash('error', 'Mật khẩu tạm thời không đúng.');
                        redirect('account.php');
                    }
                } elseif (!$currentUser || !verify_password($currentPassword, $currentUser['password_hash'])) {
                    set_flash('error', 'Mật khẩu hiện tại không đúng.');
                    redirect('account.php');
                }

                if ($passwordError !== null) {
                    set_flash('error', 'Mật khẩu mới cần tối thiểu 8 ký tự.');
                    redirect('account.php');
                }

                if ($newPassword !== $confirmPassword) {
                    set_flash('error', 'Mật khẩu mới và xác nhận mật khẩu chưa khớp.');
                    redirect('account.php');
                }

                $passwordHash = hash_password($newPassword);
                if ($emailEnabled) {
                    $stmt = $conn->prepare('UPDATE patients SET full_name = ?, phone = ?, email = ?, password_hash = ? WHERE id = ?');
                    $stmt->bind_param('ssssi', $fullName, $phone, $email, $passwordHash, $userId);
                } else {
                    $stmt = $conn->prepare('UPDATE patients SET full_name = ?, phone = ?, password_hash = ? WHERE id = ?');
                    $stmt->bind_param('sssi', $fullName, $phone, $passwordHash, $userId);
                }
            } else {
                if ($emailEnabled) {
                    $stmt = $conn->prepare('UPDATE patients SET full_name = ?, phone = ?, email = ? WHERE id = ?');
                    $stmt->bind_param('sssi', $fullName, $phone, $email, $userId);
                } else {
                    $stmt = $conn->prepare('UPDATE patients SET full_name = ?, phone = ? WHERE id = ?');
                    $stmt->bind_param('ssi', $fullName, $phone, $userId);
                }
            }

            $stmt->execute();
            $stmt->close();

            $_SESSION['name'] = $fullName;
            if ($newPassword !== '' || $mustChangePassword) {
                clear_patient_password_change_requirement();
                set_flash('success', 'Đã cập nhật mật khẩu mới thành công.');
            } else {
                set_flash('success', 'Đã cập nhật thông tin tài khoản.');
            }
            redirect('account.php');
        }
    }
}

$stmt = $conn->prepare(patient_select_sql('WHERE id = ? LIMIT 1', false, $emailEnabled, true));
$stmt->bind_param('i', $userId);
$stmt->execute();
$patient = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (is_array($patient) && !$emailEnabled) {
    $patient['email'] = '';
}

render_header('Quản lý tài khoản');
?>
<div class="wrap" style="margin-top:40px;">
  <?php render_flash(); ?>
  <div class="grid grid-2">
    <section class="card">
      <div class="panel-title">
        <div>
          <h1>Quản lý tài khoản</h1>
          <p class="muted">Bệnh nhân chỉ được chỉnh sửa thông tin cá nhân của chính mình. Các quyền quản trị thuộc khu vực admin riêng.</p>
        </div>
        <span class="badge">Vai trò: Bệnh nhân</span>
      </div>
      <form method="post" class="grid">
        <?php render_form_guard('patient_account'); ?>
        <div>
          <label for="cccd">CCCD</label>
          <input id="cccd" value="<?= e($patient['cccd'] ?? '') ?>" readonly>
        </div>
        <div>
          <label for="full_name">Họ và tên</label>
          <input id="full_name" name="full_name" value="<?= e($patient['full_name'] ?? '') ?>" required>
        </div>
        <div>
          <label for="phone">Số điện thoại</label>
          <input id="phone" name="phone" maxlength="15" value="<?= e($patient['phone'] ?? '') ?>" required>
        </div>
        <div>
          <label for="email">Gmail<?= !$emailEnabled ? ' (chưa sẵn sàng)' : '' ?></label>
          <input id="email" type="email" name="email" value="<?= e($patient['email'] ?? '') ?>" <?= !$emailEnabled ? 'disabled' : '' ?>>
        </div>
        <div>
          <label for="current_password">Mật khẩu hiện tại</label>
          <input id="current_password" type="password" name="current_password">
        </div>
        <div>
          <label for="new_password">Mật khẩu mới</label>
          <input id="new_password" type="password" name="new_password" minlength="8">
        </div>
        <div>
          <label for="confirm_password">Xác nhận mật khẩu mới</label>
          <input id="confirm_password" type="password" name="confirm_password" minlength="8">
        </div>
        <div class="actions">
          <button type="submit">Lưu thay đổi</button>
          <a class="btn btn-secondary" href="dashboard.php">Quay lại dashboard</a>
        </div>
      </form>
    </section>

    <section class="card">
      <h2>Phân quyền người dùng</h2>
      <div class="grid">
        <article class="service-card">
          <h3>Bệnh nhân</h3>
          <p>Đăng ký, đăng nhập, đặt lịch khám, xem kết quả, tải file kết quả và tự cập nhật thông tin cá nhân.</p>
        </article>
        <article class="service-card">
          <h3>Quản trị</h3>
          <p>Đăng nhập qua cổng admin riêng, thêm hồ sơ khám, quản lý tài khoản và hỗ trợ vận hành hệ thống.</p>
        </article>
        <article class="service-card">
          <h3>Ngày tạo tài khoản</h3>
          <p><?= e(isset($patient['created_at']) ? date('d/m/Y H:i', strtotime((string) $patient['created_at'])) : '') ?></p>
        </article>
      </div>
    </section>
  </div>
</div>
<?php render_footer(); ?>
