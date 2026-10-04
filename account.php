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
        set_flash('error', 'Hệ thống chưa bật trường Email cho bệnh nhân.');
    } elseif ($email !== '' && !validate_email_address($email)) {
        set_flash('error', 'Địa chỉ Email không hợp lệ.');
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

                $emailParam = ($email !== '') ? $email : null;
                $passwordHash = hash_password($newPassword);
                if ($emailEnabled) {
                    $stmt = $conn->prepare('UPDATE patients SET full_name = ?, phone = ?, email = ?, password_hash = ? WHERE id = ?');
                    $stmt->bind_param('ssssi', $fullName, $phone, $emailParam, $passwordHash, $userId);
                } else {
                    $stmt = $conn->prepare('UPDATE patients SET full_name = ?, phone = ?, password_hash = ? WHERE id = ?');
                    $stmt->bind_param('sssi', $fullName, $phone, $passwordHash, $userId);
                }
            } else {
                $emailParam = ($email !== '') ? $email : null;
                if ($emailEnabled) {
                    $stmt = $conn->prepare('UPDATE patients SET full_name = ?, phone = ?, email = ? WHERE id = ?');
                    $stmt->bind_param('sssi', $fullName, $phone, $emailParam, $userId);
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
<style>
.account-form-card {
  border-radius: 16px;
  box-shadow: 0 4px 20px rgba(0, 43, 70, 0.06);
}
.account-form .field {
  margin-bottom: 18px;
}
.account-form label {
  display: flex;
  align-items: center;
  justify-content: space-between;
  font-size: 13.5px;
  font-weight: 600;
  color: #1e293b;
  margin-bottom: 6px;
}
.account-form .input-wrap,
.account-form .password-wrap {
  position: relative;
  display: flex;
  align-items: center;
  width: 100%;
}
.account-form .input-icon-left {
  position: absolute;
  left: 13px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #64748b;
  pointer-events: none;
  z-index: 2;
}
.account-form input {
  height: 44px;
  border-radius: 10px;
  border: 1px solid #cbd5e1;
  background: #ffffff;
  padding: 0 14px 0 40px;
  font-size: 14.5px;
  color: #0f172a;
  transition: all 0.2s ease;
  width: 100%;
  box-sizing: border-box;
}
.account-form input:hover {
  border-color: #94a3b8;
}
.account-form input:focus {
  border-color: #0077c8;
  outline: none;
  box-shadow: 0 0 0 3px rgba(0, 119, 200, 0.15);
  background: #ffffff;
}
.account-form input[readonly] {
  background: #f8fafc;
  border-color: #e2e8f0;
  color: #475569;
  cursor: not-allowed;
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
  font-weight: 600;
  letter-spacing: 0.5px;
}
.account-form input:disabled {
  background: #f8fafc;
  border-color: #e2e8f0;
  color: #94a3b8;
  cursor: not-allowed;
}
.account-form .password-wrap input {
  padding-right: 44px;
}
.account-form .password-toggle {
  position: absolute;
  right: 2px;
  top: 2px;
  bottom: 2px;
  width: 40px;
  display: grid;
  place-items: center;
  background: transparent;
  border: none;
  color: #64748b;
  cursor: pointer;
  border-radius: 8px;
  transition: color 0.15s ease, background-color 0.15s ease;
  z-index: 2;
}
.account-form .password-toggle:hover {
  color: #005fa0;
  background-color: rgba(0, 110, 181, 0.08);
}
.account-section-divider {
  margin: 24px 0 18px;
  padding-top: 20px;
  border-top: 1px dashed #cbd5e1;
}
.account-section-title {
  font-size: 14.5px;
  font-weight: 700;
  color: #005fa0;
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 4px;
}
.account-section-desc {
  font-size: 12.5px;
  color: #64748b;
  margin-bottom: 16px;
}
.account-actions {
  display: flex;
  gap: 12px;
  align-items: center;
  margin-top: 24px;
  padding-top: 18px;
  border-top: 1px solid #e2e8f0;
}
.btn-save-account {
  height: 46px;
  border-radius: 12px;
  font-weight: 600;
  font-size: 15px;
  background: linear-gradient(110deg, #006eb5 0%, #005a92 100%);
  color: #ffffff !important;
  border: 1px solid #004c7d;
  box-shadow: 0 2px 6px rgba(0, 43, 70, 0.16), 0 6px 16px rgba(0, 95, 160, 0.22);
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  flex: 1;
  transition: all 0.2s ease;
}
.btn-save-account:hover {
  background: linear-gradient(110deg, #005e9c 0%, #004d7d 100%);
  box-shadow: 0 4px 10px rgba(0, 43, 70, 0.2), 0 8px 20px rgba(0, 95, 160, 0.28);
  transform: translateY(-1px);
}
.btn-back-dashboard {
  height: 46px;
  border-radius: 12px;
  font-weight: 600;
  font-size: 15px;
  background: var(--soft, #f0f7fd);
  color: #005fa0 !important;
  border: 1px solid #badcf6;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  text-decoration: none;
  flex: 1;
  transition: all 0.2s ease;
}
.btn-back-dashboard:hover {
  background: #d9effd;
  border-color: #92c5ed;
  transform: translateY(-1px);
}
@media (max-width: 640px) {
  .account-actions {
    flex-direction: column;
  }
  .btn-save-account, .btn-back-dashboard {
    width: 100%;
  }
}
</style>

<div class="wrap" style="margin-top:40px;">
  <?php render_flash(); ?>
  <div class="grid grid-2">
    <section class="card account-form-card">
      <div class="panel-title" style="margin-bottom: 20px;">
        <div>
          <h1 style="font-size: 22px; margin-bottom: 4px;">Quản lý tài khoản</h1>
          <p class="muted" style="font-size: 13.5px; margin: 0;">Bệnh nhân chỉ được chỉnh sửa thông tin cá nhân của chính mình. Các quyền quản trị thuộc khu vực admin riêng.</p>
        </div>
        <span class="badge" style="background:#e0f2fe; color:#0369a1; border-color:#bae6fd;">Vai trò: Bệnh nhân</span>
      </div>

      <?php if ($mustChangePassword): ?>
        <div style="background:#fffbeb;border:1px solid #fde68a;border-left:4px solid #f59e0b;padding:12px 16px;border-radius:10px;margin-bottom:20px;display:flex;align-items:flex-start;gap:12px;">
          <svg style="width:20px;height:20px;color:#d97706;flex-shrink:0;margin-top:2px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
          <div style="font-size:13.5px;color:#92400e;line-height:1.5;">
            <strong>Yêu cầu đổi mật khẩu bảo mật:</strong> Bạn vừa đăng nhập bằng mật khẩu tạm thời. Vui lòng nhập mật khẩu tạm thời vào ô "Mật khẩu hiện tại" và thiết lập mật khẩu mới ngay bên dưới để hoàn tất kích hoạt.
          </div>
        </div>
      <?php endif; ?>

      <form method="post" class="account-form" novalidate>
        <?php render_form_guard('patient_account'); ?>

        <!-- Trường CCCD (Readonly lịch sự) -->
        <div class="field" style="margin-bottom: 18px;">
          <label for="cccd">
            <span>Số Căn cước công dân (CCCD)</span>
            <span style="font-size: 11px; font-weight: 500; background: #e2e8f0; color: #475569; padding: 2px 8px; border-radius: 6px;">Định danh cố định</span>
          </label>
          <div class="input-wrap">
            <span class="input-icon-left" title="Căn cước công dân gắn chip">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="16" rx="3"/><circle cx="9" cy="10" r="2"/><line x1="15" y1="8" x2="17" y2="8"/><line x1="15" y1="12" x2="17" y2="12"/><line x1="7" y1="16" x2="17" y2="16"/></svg>
            </span>
            <input id="cccd" value="<?= e($patient['cccd'] ?? '') ?>" readonly title="Số CCCD là mã định danh duy nhất của người bệnh tại phòng khám">
          </div>
          <p class="field-hint" style="font-size: 12px; color: var(--muted); margin-top: 4px;">Mã định danh duy nhất theo hệ thống hồ sơ bệnh án điện tử, không thể tự sửa đổi.</p>
        </div>

        <!-- Trường Họ và tên -->
        <div class="field" style="margin-bottom: 18px;">
          <label for="full_name">
            <span>Họ và tên người bệnh <span style="color:var(--danger)">*</span></span>
          </label>
          <div class="input-wrap">
            <span class="input-icon-left">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            </span>
            <input id="full_name" name="full_name" value="<?= e($patient['full_name'] ?? '') ?>" required placeholder="Ví dụ: Nguyễn Văn A">
          </div>
        </div>

        <!-- Trường Số điện thoại -->
        <div class="field" style="margin-bottom: 18px;">
          <label for="phone">
            <span>Số điện thoại liên hệ <span style="color:var(--danger)">*</span></span>
          </label>
          <div class="input-wrap">
            <span class="input-icon-left">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
            </span>
            <input id="phone" name="phone" type="tel" inputmode="tel" maxlength="15" value="<?= e($patient['phone'] ?? '') ?>" required placeholder="Ví dụ: 0912345678">
          </div>
          <p class="field-hint" style="font-size: 12px; color: var(--muted); margin-top: 4px;">Dùng để nhận kết quả khám và các thông báo hỗ trợ từ phòng khám.</p>
        </div>

        <!-- Trường Email -->
        <div class="field" style="margin-bottom: 18px;">
          <label for="email">
            <span>Địa chỉ Email <?= !$emailEnabled ? '<span style="font-weight:normal;color:var(--muted);">(chưa sẵn sàng)</span>' : '<span style="font-weight:normal;color:var(--muted);">(không bắt buộc)</span>' ?></span>
          </label>
          <div class="input-wrap">
            <span class="input-icon-left">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
            </span>
            <input id="email" type="email" name="email" value="<?= e($patient['email'] ?? '') ?>" <?= !$emailEnabled ? 'disabled' : '' ?> placeholder="Ví dụ: benhnhan@gmail.com">
          </div>
          <p class="field-hint" style="font-size: 12px; color: var(--muted); margin-top: 4px;">Dùng để nhận phiếu kết quả điện tử và hỗ trợ bảo mật thông tin.</p>
        </div>

        <!-- Khu vực đổi mật khẩu -->
        <div class="account-section-divider">
          <div class="account-section-title">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            <span>Thiết lập mật khẩu mới</span>
          </div>
          <p class="account-section-desc">Chỉ điền các ô dưới đây nếu bạn muốn thay đổi mật khẩu đăng nhập của mình.</p>
        </div>

        <!-- Mật khẩu hiện tại -->
        <div class="field" style="margin-bottom: 18px;">
          <label for="current_password">
            <span>Mật khẩu hiện tại</span>
          </label>
          <div class="password-wrap">
            <span class="input-icon-left">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            </span>
            <input id="current_password" type="password" name="current_password" autocomplete="current-password" placeholder="Nhập mật khẩu hiện tại của bạn">
            <button type="button" class="password-toggle" data-target="current_password" aria-label="Hiện mật khẩu" aria-pressed="false">
              <svg class="icon-eye" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
              <svg class="icon-eye-off" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="display:none;"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
            </button>
          </div>
          <p class="field-hint" style="font-size: 12px; color: var(--muted); margin-top: 4px;">Để trống nếu bạn không có nhu cầu đổi mật khẩu.</p>
        </div>

        <!-- Mật khẩu mới -->
        <div class="field" style="margin-bottom: 18px;">
          <label for="new_password">
            <span>Mật khẩu mới</span>
          </label>
          <div class="password-wrap">
            <span class="input-icon-left">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            </span>
            <input id="new_password" type="password" name="new_password" minlength="8" autocomplete="new-password" placeholder="Tối thiểu 8 ký tự (chữ hoa, chữ thường, số)">
            <button type="button" class="password-toggle" data-target="new_password" aria-label="Hiện mật khẩu" aria-pressed="false">
              <svg class="icon-eye" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
              <svg class="icon-eye-off" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="display:none;"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
            </button>
          </div>
          <p class="field-hint" style="font-size: 12px; color: var(--muted); margin-top: 4px;">Nên gồm cả chữ hoa, chữ thường và chữ số để đảm bảo an toàn.</p>
        </div>

        <!-- Xác nhận mật khẩu mới -->
        <div class="field" style="margin-bottom: 18px;">
          <label for="confirm_password">
            <span>Xác nhận mật khẩu mới</span>
          </label>
          <div class="password-wrap">
            <span class="input-icon-left">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            </span>
            <input id="confirm_password" type="password" name="confirm_password" minlength="8" autocomplete="new-password" placeholder="Nhập lại chính xác mật khẩu mới">
            <button type="button" class="password-toggle" data-target="confirm_password" aria-label="Hiện mật khẩu" aria-pressed="false">
              <svg class="icon-eye" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
              <svg class="icon-eye-off" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="display:none;"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
            </button>
          </div>
        </div>

        <!-- Cụm nút hành động -->
        <div class="account-actions">
          <button type="submit" class="btn-save-account">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
            <span>Lưu thay đổi</span>
          </button>
          <a class="btn-back-dashboard" href="dashboard.php">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5m7 7-7-7 7-7"/></svg>
            <span>Quay lại dashboard</span>
          </a>
        </div>
      </form>
    </section>

    <section class="card" style="border-radius: 16px; box-shadow: 0 4px 20px rgba(0, 43, 70, 0.06);">
      <h2 style="font-size: 19px; margin-bottom: 16px; color: #0f172a;">Phân quyền & Tiện ích</h2>
      <div class="grid" style="gap: 14px;">
        <article class="service-card" style="padding: 16px; border-radius: 12px; border: 1px solid #e2e8f0;">
          <h3 style="font-size: 15px; color: #006eb5; margin-bottom: 6px;">Bệnh nhân</h3>
          <p style="font-size: 13.5px; color: #475569; margin: 0; line-height: 1.5;">Đăng ký, đăng nhập, tra cứu hồ sơ, xem kết quả cận lâm sàng, tải kết quả và cập nhật thông tin cá nhân an toàn.</p>
        </article>
        <article class="service-card" style="padding: 16px; border-radius: 12px; border: 1px solid #e2e8f0;">
          <h3 style="font-size: 15px; color: #006eb5; margin-bottom: 6px;">Bảo mật & Quản trị</h3>
          <p style="font-size: 13.5px; color: #475569; margin: 0; line-height: 1.5;">Hệ thống bảo vệ dữ liệu y tế đa lớp. Mọi thao tác đổi mật khẩu và cập nhật thông tin đều được ghi nhật ký kiểm toán an toàn.</p>
        </article>
        <article class="service-card" style="padding: 16px; border-radius: 12px; border: 1px solid #e2e8f0;">
          <h3 style="font-size: 15px; color: #006eb5; margin-bottom: 6px;">Ngày tạo tài khoản</h3>
          <p style="font-size: 13.5px; color: #475569; margin: 0; font-weight: 600;"><?= e(isset($patient['created_at']) ? date('d/m/Y H:i', strtotime((string) $patient['created_at'])) : 'Không xác định') ?></p>
        </article>
      </div>
    </section>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  var toggles = document.querySelectorAll('.password-toggle');
  toggles.forEach(function (btn) {
    btn.addEventListener('click', function () {
      var targetId = btn.getAttribute('data-target');
      var input = document.getElementById(targetId);
      if (!input) return;
      var isPassword = input.type === 'password';
      input.type = isPassword ? 'text' : 'password';
      btn.setAttribute('aria-pressed', isPassword ? 'true' : 'false');
      btn.setAttribute('aria-label', isPassword ? 'Ẩn mật khẩu' : 'Hiện mật khẩu');
      var eyeIcon = btn.querySelector('.icon-eye');
      var eyeOffIcon = btn.querySelector('.icon-eye-off');
      if (eyeIcon && eyeOffIcon) {
        eyeIcon.style.display = isPassword ? 'none' : 'block';
        eyeOffIcon.style.display = isPassword ? 'block' : 'none';
      }
    });
  });
});
</script>
<?php render_footer(); ?>
