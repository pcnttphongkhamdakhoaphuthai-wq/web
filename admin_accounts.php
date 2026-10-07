<?php
declare(strict_types=1);

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

require_once 'config.php';
require_admin_login();

$rootAdminId = (int) ($_SESSION['admin_id'] ?? 0);
$patientEmailEnabled = patient_email_enabled();
$adminPermissionDefinitions = admin_permission_definitions();
$adminAccountsPermissions = array_keys($adminPermissionDefinitions);

if (!is_root_admin() && !admin_can_any($adminAccountsPermissions)) {
    set_flash('error', 'Tài khoản của bạn chưa được phân quyền vào khu vực quản lý admin.');
    redirect(admin_home_path());
}

function require_admin_accounts_permission(string $permission): void
{
    if (!admin_can($permission)) {
        set_flash('error', 'Tài khoản của bạn không có quyền thao tác mục này.');
        redirect('admin_accounts.php');
    }
}

$canCreateBackup = admin_can('create_backup');
$canManageRootProfile = is_root_admin();
$canManageClinicContent = admin_can('manage_clinic_content');
$canManageStaffAccounts = admin_can('manage_accounts');
$canManageDoctors = admin_can('manage_doctors');
$canManagePatients = admin_can('manage_patients');
$canManageChatbot = admin_can('manage_chatbot');
$canManageSupportChat = admin_can('manage_support_chat');
$canPublishAnnouncements = admin_can('publish_announcements');
$canViewLogs = admin_can('view_logs');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');
    // Preserve current tab across POST redirects
    $rawTab = preg_replace('/[^a-z0-9\-]/', '', (string) ($_POST['_tab'] ?? ''));
    $_tab = $rawTab !== '' ? '#' . $rawTab : '';

    if ($action === 'dismiss_support_chat_notice') {
        require_admin_accounts_permission('manage_support_chat');
        $guardError = validate_form_guard('admin_support_notice', 20, 300, 0, false);
        if ($guardError !== null) {
            set_flash('error', $guardError);
            redirect('admin_accounts.php' . $_tab);
        }

        mark_admin_support_notice_seen($rootAdminId, (int) ($_POST['latest_chat_id'] ?? 0));
        set_flash('success', 'Đã đánh dấu đã xem hội thoại hỗ trợ.');
        redirect('admin_accounts.php' . $_tab);
    }

    $guardError = validate_form_guard('admin_accounts', 30, 600, 0, false);

    if ($guardError !== null) {
        set_flash('error', $guardError);
        redirect('admin_accounts.php' . $_tab);
    }

    audit_log('admin_accounts_action', [
        'action' => $action,
        'target_id' => (int) ($_POST['target_id'] ?? $_POST['staff_id'] ?? $_POST['patient_id'] ?? $_POST['doctor_id'] ?? $_POST['reply_id'] ?? $_POST['news_id'] ?? $_POST['resource_id'] ?? 0),
    ]);

    if ($action === 'create_backup') {
        require_admin_accounts_permission('create_backup');
        try {
            $backup = create_system_backup($conn, 'admin_accounts');
            set_flash('success', 'Đã tạo backup tại ' . $backup['path']);
        } catch (Throwable $exception) {
            log_internal_error('admin_backup_failed', $exception, ['admin_id' => $rootAdminId]);
            set_flash('error', 'Không thể tạo backup lúc này.');
        }
        redirect('admin_accounts.php' . $_tab);
    }

    if ($action === 'update_profile') {
        if (!is_root_admin()) {
            set_flash('error', 'Chỉ admin gốc mới được cập nhật thông tin admin gốc.');
            redirect('admin_accounts.php' . $_tab);
        }

        $fullName = normalize_single_line_input($_POST['full_name'] ?? '');
        $username = normalize_single_line_input($_POST['username'] ?? '');
        $department = normalize_single_line_input($_POST['department'] ?? '');
        $currentPassword = (string) ($_POST['current_password'] ?? '');
        $newPassword = (string) ($_POST['new_password'] ?? '');
        $confirmPassword = (string) ($_POST['confirm_password'] ?? '');
        $passwordError = $newPassword !== '' ? validate_password_strength($newPassword) : null;

        if (!validate_person_name($fullName) || !validate_username_format($username) || ($department !== '' && !validate_generic_label($department))) {
            set_flash('error', 'Thông tin admin gốc không hợp lệ.');
            redirect('admin_accounts.php' . $_tab);
        }

        if ($fullName === '' || $username === '') {
            set_flash('error', 'Vui lòng nhập đầy đủ thông tin admin gốc.');
            redirect('admin_accounts.php' . $_tab);
        }

        $stmt = $conn->prepare('SELECT id FROM admins WHERE username = ? AND id <> ? LIMIT 1');
        $stmt->bind_param('si', $username, $rootAdminId);
        $stmt->execute();
        $exists = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($exists) {
            set_flash('error', 'Tên đăng nhập admin đã tồn tại.');
            redirect('admin_accounts.php' . $_tab);
        }

        $stmt = $conn->prepare('SELECT password_hash FROM admins WHERE id = ? LIMIT 1');
        $stmt->bind_param('i', $rootAdminId);
        $stmt->execute();
        $admin = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($newPassword !== '' || $confirmPassword !== '' || $currentPassword !== '') {
            if (!$admin || !verify_password($currentPassword, $admin['password_hash'])) {
                set_flash('error', 'Mật khẩu hiện tại của admin gốc không đúng.');
                redirect('admin_accounts.php' . $_tab);
            }
            if ($passwordError !== null) {
                set_flash('error', 'Mật khẩu mới phải có ít nhất 8 ký tự.');
                redirect('admin_accounts.php' . $_tab);
            }
            if ($newPassword !== $confirmPassword) {
                set_flash('error', 'Xác nhận mật khẩu mới chưa khớp.');
                redirect('admin_accounts.php' . $_tab);
            }

            $passwordHash = hash_password($newPassword);
            $stmt = $conn->prepare('UPDATE admins SET full_name = ?, username = ?, department = ?, password_hash = ? WHERE id = ?');
            $stmt->bind_param('ssssi', $fullName, $username, $department, $passwordHash, $rootAdminId);
        } else {
            $stmt = $conn->prepare('UPDATE admins SET full_name = ?, username = ?, department = ? WHERE id = ?');
            $stmt->bind_param('sssi', $fullName, $username, $department, $rootAdminId);
        }
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare('SELECT * FROM admins WHERE id = ? LIMIT 1');
        $stmt->bind_param('i', $rootAdminId);
        $stmt->execute();
        $freshAdmin = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($freshAdmin) {
            load_admin_session($freshAdmin);
        }

        set_flash('success', 'Đã cập nhật thông tin admin gốc.');
        redirect('admin_accounts.php' . $_tab);
    }

    if ($action === 'save_clinic_content') {
        require_admin_accounts_permission('manage_clinic_content');
        $currentLogo = trim($_POST['current_logo'] ?? '');
        $logoPath = $currentLogo !== '' ? $currentLogo : null;

        if (isset($_POST['remove_logo'])) {
            delete_clinic_logo($logoPath);
            $logoPath = null;
        }

        if (isset($_FILES['clinic_logo']) && ($_FILES['clinic_logo']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            try {
                $logoPath = store_clinic_logo($_FILES['clinic_logo'], $logoPath);
            } catch (Throwable $exception) {
                log_internal_error('clinic_logo_upload_failed', $exception, ['admin_id' => $rootAdminId]);
                set_flash('error', 'Không thể cập nhật logo lúc này.');
                redirect('admin_accounts.php');
            }
        }

        $settingsToSave = [];
        $possibleClinicSettings = [
            'clinic_name', 'clinic_intro', 'clinic_mission', 'clinic_facility',
            'clinic_services', 'clinic_support', 'support_hotline', 'support_email',
            'clinic_address', 'google_maps_url', 'apple_maps_url', 'zalo_url',
            'messenger_url', 'gemini_api_keys', 'appointment_schedule',
            'chatbot_intro', 'chatbot_fallback'
        ];

        foreach ($possibleClinicSettings as $key) {
            if (isset($_POST[$key])) {
                $settingsToSave[$key] = $_POST[$key];
            }
        }

        // Special handling for clinic_logo_path as it's computed
        if (isset($logoPath)) {
            $settingsToSave['clinic_logo_path'] = $logoPath;
        }

        // Special handling for appointments_enabled (checkbox/hidden logic)
        if (isset($_POST['appointments_enabled'])) {
            $settingsToSave['appointments_enabled'] = ($_POST['appointments_enabled'] === '1' || $_POST['appointments_enabled'] === 'on') ? '1' : '0';
        }

        if (!empty($settingsToSave)) {
            save_site_settings($settingsToSave);
        }

        set_flash('success', 'Đã cập nhật nội dung phòng khám và cấu hình vận hành.');
        redirect('admin_accounts.php' . $_tab);

    }

    if ($action === 'save_ai_content') {
        require_admin_accounts_permission('manage_chatbot');
        $settingsToSave = [];
        $possibleAiSettings = [
            'ai_prompt_procedures', 'ai_prompt_pricing',
            'ai_prompt_documents', 'ai_prompt_benefits',
            'ai_prompt_schedule'
        ];

        foreach ($possibleAiSettings as $key) {
            if (isset($_POST[$key])) {
                $settingsToSave[$key] = $_POST[$key];
            }
        }

        if (!empty($settingsToSave)) {
            save_site_settings($settingsToSave);
        }

        set_flash('success', 'Đã cập nhật dữ liệu huấn luyện AI Chatbot.');
        redirect('admin_accounts.php' . $_tab);
    }

    if ($action === 'save_news_post') {
        require_admin_accounts_permission('manage_clinic_content');
        $adminId = (int) ($_SESSION['admin_id'] ?? 0);
        $newsId = (int) ($_POST['news_id'] ?? 0);
        $title = trim((string) ($_POST['news_title'] ?? ''));
        $excerpt = trim((string) ($_POST['news_excerpt'] ?? ''));
        $body = trim((string) ($_POST['news_body'] ?? ''));
        $currentMedia = trim((string) ($_POST['current_media'] ?? ''));
        $currentMediaType = trim((string) ($_POST['current_media_type'] ?? ''));
        $mediaPath = $currentMedia !== '' ? $currentMedia : null;
        $mediaType = $currentMediaType !== '' ? $currentMediaType : null;
        $isPublished = isset($_POST['is_published']);

        if ($title === '' || $body === '') {
            set_flash('error', 'Vui lòng nhập tiêu đề và nội dung tin tức.');
            redirect('admin_accounts.php#tab-news');
        }

        if (isset($_POST['remove_media'])) {
            delete_news_media($mediaPath);
            $mediaPath = null;
            $mediaType = null;
        }

        if (isset($_FILES['news_media']) && ($_FILES['news_media']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            try {
                $uploadedMedia = store_news_media($_FILES['news_media'], $mediaPath);
                $mediaPath = $uploadedMedia['path'];
                $mediaType = $uploadedMedia['type'];
            } catch (Throwable $exception) {
                log_internal_error('news_media_upload_failed', $exception, ['admin_id' => $adminId, 'news_id' => $newsId]);
                set_flash('error', $exception->getMessage());
                redirect('admin_accounts.php#tab-news');
            }
        }

        save_news_post($newsId, $title, $excerpt, $body, $isPublished, $adminId, $mediaPath, $mediaType);
        set_flash('success', $newsId > 0 ? 'Đã cập nhật tin tức.' : 'Đã thêm tin tức mới.');
        redirect('admin_accounts.php#tab-news');
    }

    if ($action === 'delete_news_post') {
        require_admin_accounts_permission('manage_clinic_content');
        $newsId = (int) ($_POST['target_id'] ?? 0);
        if ($newsId > 0) {
            delete_news_post($newsId);
            set_flash('success', 'Đã xóa tin tức.');
        }
        redirect('admin_accounts.php#tab-news');
    }

    if ($action === 'save_customer_resource') {
        require_admin_accounts_permission('manage_clinic_content');
        $adminId = (int) ($_SESSION['admin_id'] ?? 0);
        $resourceId = (int) ($_POST['resource_id'] ?? 0);
        $title = trim((string) ($_POST['resource_title'] ?? ''));
        $description = trim((string) ($_POST['resource_description'] ?? ''));
        $resourceUrl = trim((string) ($_POST['resource_url'] ?? ''));
        $sortOrder = (int) ($_POST['sort_order'] ?? 0);
        $isPublished = isset($_POST['is_published']);

        if ($title === '') {
            set_flash('error', 'Vui lòng nhập tiêu đề tư liệu.');
            redirect('admin_accounts.php#tab-news');
        }
        if ($resourceUrl !== '' && !filter_var($resourceUrl, FILTER_VALIDATE_URL)) {
            set_flash('error', 'Link tư liệu không hợp lệ.');
            redirect('admin_accounts.php#tab-news');
        }

        save_customer_resource($resourceId, $title, $description, $resourceUrl, $sortOrder, $isPublished, $adminId);
        set_flash('success', $resourceId > 0 ? 'Đã cập nhật tư liệu.' : 'Đã thêm tư liệu mới.');
        redirect('admin_accounts.php#tab-news');
    }

    if ($action === 'delete_customer_resource') {
        require_admin_accounts_permission('manage_clinic_content');
        $resourceId = (int) ($_POST['target_id'] ?? 0);
        if ($resourceId > 0) {
            delete_customer_resource($resourceId);
            set_flash('success', 'Đã xóa tư liệu.');
        }
        redirect('admin_accounts.php#tab-news');
    }

    if ($action === 'save_staff') {
        require_admin_accounts_permission('manage_accounts');
        $currentAdminId = (int) ($_SESSION['admin_id'] ?? 0);
        $staffId = (int) ($_POST['staff_id'] ?? 0);
        $username = normalize_single_line_input($_POST['username'] ?? '');
        $fullName = normalize_single_line_input($_POST['full_name'] ?? '');
        $department = normalize_single_line_input($_POST['department'] ?? '');
        $password = (string) ($_POST['password'] ?? '');
        $role = normalize_single_line_input($_POST['role'] ?? 'staff');
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        $canManageRecords = isset($_POST['can_manage_records']) ? 1 : 0;
        $canManageAllRecords = isset($_POST['can_manage_all_records']) ? 1 : 0;
        $canManageAccounts = isset($_POST['can_manage_accounts']) ? 1 : 0;
        $canManageClinicContent = isset($_POST['can_manage_clinic_content']) ? 1 : 0;
        $canManageDoctors = isset($_POST['can_manage_doctors']) ? 1 : 0;
        $canManagePatients = isset($_POST['can_manage_patients']) ? 1 : 0;
        $canManageChatbot = isset($_POST['can_manage_chatbot']) ? 1 : 0;
        $canManageSupportChat = isset($_POST['can_manage_support_chat']) ? 1 : 0;
        $canPublishAnnouncements = isset($_POST['can_publish_announcements']) ? 1 : 0;
        $canCreateBackup = isset($_POST['can_create_backup']) ? 1 : 0;
        $canViewLogs = isset($_POST['can_view_logs']) ? 1 : 0;
        $passwordError = $password !== '' ? validate_password_strength($password) : null;

        if ($staffId > 0 && $staffId === $currentAdminId) {
            set_flash('error', 'Bạn không thể tự chỉnh sửa quyền hoặc tài khoản của chính mình.');
            redirect('admin_accounts.php' . $_tab);
        }

        if (!is_root_admin() && ($canManageAccounts === 1 || $canCreateBackup === 1)) {
            set_flash('error', 'Chỉ admin gốc mới có quyền cấp quyền quản lý tài khoản hoặc sao lưu dữ liệu.');
            redirect('admin_accounts.php' . $_tab);
        }

        if (!validate_username_format($username) || !validate_person_name($fullName) || ($department !== '' && !validate_generic_label($department)) || !validate_generic_label($role, 50)) {
            set_flash('error', 'Thông tin nhân viên không hợp lệ.');
            redirect('admin_accounts.php');
        }

        if ($username === '' || $fullName === '') {
            set_flash('error', 'Vui lòng nhập đầy đủ thông tin nhân viên.');
            redirect('admin_accounts.php');
        }

        if ($canManageAllRecords === 1) {
            $canManageRecords = 1;
        }

        $stmt = $conn->prepare('SELECT id FROM admins WHERE username = ? AND id <> ? LIMIT 1');
        $stmt->bind_param('si', $username, $staffId);
        $stmt->execute();
        $sameUsername = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($sameUsername) {
            set_flash('error', 'Tên đăng nhập nhân viên đã tồn tại.');
            redirect('admin_accounts.php');
        }

        if ($staffId > 0) {
            $stmt = $conn->prepare('SELECT is_root, can_manage_accounts, can_create_backup FROM admins WHERE id = ? LIMIT 1');
            $stmt->bind_param('i', $staffId);
            $stmt->execute();
            $target = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$target || (int) $target['is_root'] === 1) {
                set_flash('error', 'Không được chỉnh sửa tài khoản admin gốc ở khu vực này.');
                redirect('admin_accounts.php');
            }

            if (!is_root_admin() && ((int) ($target['can_manage_accounts'] ?? 0) === 1 || (int) ($target['can_create_backup'] ?? 0) === 1)) {
                set_flash('error', 'Chỉ admin gốc mới có quyền chỉnh sửa tài khoản đang nắm giữ quyền quản lý tài khoản hoặc sao lưu dữ liệu.');
                redirect('admin_accounts.php' . $_tab);
            }

            if ($password !== '') {
                if ($passwordError !== null) {
                    set_flash('error', 'Mật khẩu nhân viên phải có ít nhất 8 ký tự.');
                    redirect('admin_accounts.php');
                }
                $passwordHash = hash_password($password);
                $stmt = $conn->prepare(
                    'UPDATE admins
                     SET username = ?, full_name = ?, department = ?, role = ?, is_active = ?, can_manage_accounts = ?, can_manage_records = ?, can_manage_all_records = ?, can_manage_clinic_content = ?, can_manage_doctors = ?, can_manage_patients = ?, can_manage_chatbot = ?, can_manage_support_chat = ?, can_publish_announcements = ?, can_create_backup = ?, can_view_logs = ?, password_hash = ?
                     WHERE id = ?'
                );
                $types = 'ssss' . str_repeat('i', 12) . 'si';
                $stmt->bind_param($types, $username, $fullName, $department, $role, $isActive, $canManageAccounts, $canManageRecords, $canManageAllRecords, $canManageClinicContent, $canManageDoctors, $canManagePatients, $canManageChatbot, $canManageSupportChat, $canPublishAnnouncements, $canCreateBackup, $canViewLogs, $passwordHash, $staffId);
            } else {
                $stmt = $conn->prepare(
                    'UPDATE admins
                     SET username = ?, full_name = ?, department = ?, role = ?, is_active = ?, can_manage_accounts = ?, can_manage_records = ?, can_manage_all_records = ?, can_manage_clinic_content = ?, can_manage_doctors = ?, can_manage_patients = ?, can_manage_chatbot = ?, can_manage_support_chat = ?, can_publish_announcements = ?, can_create_backup = ?, can_view_logs = ?
                     WHERE id = ?'
                );
                $types = 'ssss' . str_repeat('i', 13);
                $stmt->bind_param($types, $username, $fullName, $department, $role, $isActive, $canManageAccounts, $canManageRecords, $canManageAllRecords, $canManageClinicContent, $canManageDoctors, $canManagePatients, $canManageChatbot, $canManageSupportChat, $canPublishAnnouncements, $canCreateBackup, $canViewLogs, $staffId);
            }
            $stmt->execute();
            $stmt->close();
            set_flash('success', 'Đã cập nhật tài khoản nhân viên.');
        } else {
            if ($passwordError !== null) {
                set_flash('error', 'Mật khẩu nhân viên mới phải có ít nhất 8 ký tự.');
                redirect('admin_accounts.php');
            }
            $passwordHash = hash_password($password);
            $stmt = $conn->prepare(
                'INSERT INTO admins (username, full_name, department, role, is_root, is_active, can_manage_accounts, can_manage_records, can_manage_all_records, can_manage_clinic_content, can_manage_doctors, can_manage_patients, can_manage_chatbot, can_manage_support_chat, can_publish_announcements, can_create_backup, can_view_logs, password_hash)
                 VALUES (?, ?, ?, ?, 0, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $types = 'ssss' . str_repeat('i', 12) . 's';
            $stmt->bind_param($types, $username, $fullName, $department, $role, $isActive, $canManageAccounts, $canManageRecords, $canManageAllRecords, $canManageClinicContent, $canManageDoctors, $canManagePatients, $canManageChatbot, $canManageSupportChat, $canPublishAnnouncements, $canCreateBackup, $canViewLogs, $passwordHash);
            $stmt->execute();
            $stmt->close();
            set_flash('success', 'Đã tạo tài khoản nhân viên mới.');
        }

        redirect('admin_accounts.php' . $_tab);
    }

    if ($action === 'reset_staff_password') {
        require_admin_accounts_permission('manage_accounts');
        $staffId = (int) ($_POST['target_id'] ?? 0);
        $stmt = $conn->prepare('SELECT id, username, is_root FROM admins WHERE id = ? LIMIT 1');
        $stmt->bind_param('i', $staffId);
        $stmt->execute();
        $target = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$target || (int) $target['is_root'] === 1) {
            set_flash('error', 'Không được reset tài khoản admin gốc trong mục này.');
        } else {
            try {
                $temporaryPassword = issue_temporary_admin_password($target, $rootAdminId);
                clear_login_failures('admin_login', (string) $target['username']);
                clear_login_submit_attempts('admin_login_submit', (string) $target['username']);
                set_flash(
                    'success',
                    'Mật khẩu tạm 1 lần của nhân viên ' . (string) $target['username'] . ': '
                    . $temporaryPassword['password']
                    . '. Hiệu lực 5 phút và sẽ hết hiệu lực sau khi đăng nhập.'
                );
            } catch (Throwable $exception) {
                clear_temporary_admin_password((string) ($target['username'] ?? ''));
                log_internal_error('reset_staff_password_failed', $exception, ['admin_id' => $rootAdminId, 'staff_id' => $staffId]);
                set_flash('error', 'Không thể tạo mật khẩu tạm nhân viên lúc này.');
            }
        }
        redirect('admin_accounts.php' . $_tab);
    }

    if ($action === 'delete_staff') {
        require_admin_accounts_permission('manage_accounts');
        $staffId = (int) ($_POST['target_id'] ?? 0);
        $stmt = $conn->prepare('SELECT is_root FROM admins WHERE id = ? LIMIT 1');
        $stmt->bind_param('i', $staffId);
        $stmt->execute();
        $target = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$target || (int) $target['is_root'] === 1) {
            set_flash('error', 'Không được xóa tài khoản admin gốc.');
        } else {
            $stmt = $conn->prepare('DELETE FROM admins WHERE id = ? LIMIT 1');
            $stmt->bind_param('i', $staffId);
            $stmt->execute();
            $stmt->close();
            set_flash('success', 'Đã xóa tài khoản nhân viên.');
        }
        redirect('admin_accounts.php' . $_tab);
    }

    if ($action === 'update_patient') {
        require_admin_accounts_permission('manage_patients');
        $patientId = (int) ($_POST['patient_id'] ?? 0);
        $cccd = normalize_single_line_input($_POST['cccd'] ?? '');
        $fullName = normalize_single_line_input($_POST['full_name'] ?? '');
        $phone = normalize_single_line_input($_POST['phone'] ?? '');
        $email = normalize_single_line_input($_POST['email'] ?? '');

        if (!validate_cccd($cccd) || !validate_person_name($fullName) || !validate_phone_number($phone)) {
            set_flash('error', 'Thông tin bệnh nhân không hợp lệ.');
            redirect('admin_accounts.php' . $_tab);
        }

        if ($patientId <= 0 || $cccd === '' || $fullName === '' || $phone === '') {
            set_flash('error', 'Vui lòng nhập đầy đủ thông tin bệnh nhân.');
            redirect('admin_accounts.php' . $_tab);
        }

        if ($email !== '' && !$patientEmailEnabled) {
            set_flash('error', 'Hệ thống chưa bật trường Gmail cho bệnh nhân.');
            redirect('admin_accounts.php' . $_tab);
        }

        if ($email !== '' && !validate_email_address($email)) {
            set_flash('error', 'Gmail bệnh nhân không hợp lệ.');
            redirect('admin_accounts.php' . $_tab);
        }

        if ($patientEmailEnabled) {
            $stmt = $conn->prepare('SELECT id FROM patients WHERE (cccd = ? OR phone = ? OR (? <> "" AND email = ?)) AND id <> ? LIMIT 1');
            $stmt->bind_param('ssssi', $cccd, $phone, $email, $email, $patientId);
        } else {
            $stmt = $conn->prepare('SELECT id FROM patients WHERE (cccd = ? OR phone = ?) AND id <> ? LIMIT 1');
            $stmt->bind_param('ssi', $cccd, $phone, $patientId);
        }
        $stmt->execute();
        $exists = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($exists) {
            set_flash('error', 'CCCD hoặc số điện thoại bệnh nhân đã tồn tại.');
            redirect('admin_accounts.php' . $_tab);
        }

        if ($patientEmailEnabled) {
            $stmt = $conn->prepare('UPDATE patients SET cccd = ?, full_name = ?, phone = ?, email = ? WHERE id = ?');
            $stmt->bind_param('ssssi', $cccd, $fullName, $phone, $email, $patientId);
        } else {
            $stmt = $conn->prepare('UPDATE patients SET cccd = ?, full_name = ?, phone = ? WHERE id = ?');
            $stmt->bind_param('sssi', $cccd, $fullName, $phone, $patientId);
        }
        $stmt->execute();
        $stmt->close();
        set_flash('success', 'Đã cập nhật thông tin bệnh nhân.');
        redirect('admin_accounts.php' . $_tab);
    }

    if ($action === 'reset_patient_password') {
        require_admin_accounts_permission('manage_patients');
        $patientId = (int) ($_POST['target_id'] ?? 0);
        $stmt = $conn->prepare('SELECT id, cccd, full_name FROM patients WHERE id = ? LIMIT 1');
        $stmt->bind_param('i', $patientId);
        $stmt->execute();
        $patient = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$patient) {
            set_flash('error', 'Không tìm thấy bệnh nhân cần reset mật khẩu.');
            redirect('admin_accounts.php' . $_tab);
        }

        try {
            $temporaryPassword = issue_temporary_patient_password($patient, $rootAdminId);
            $lockedPasswordHash = hash_password(bin2hex(random_bytes(24)) . 'Aa!1');
            $stmt = $conn->prepare('UPDATE patients SET password_hash = ? WHERE id = ?');
            $stmt->bind_param('si', $lockedPasswordHash, $patientId);
            $stmt->execute();
            $stmt->close();

            clear_login_failures('patient_login', (string) $patient['cccd']);
            clear_login_submit_attempts('patient_login_submit', (string) $patient['cccd']);

            set_flash(
                'success',
                'Mật khẩu tạm 1 lần của ' . (string) ($patient['full_name'] ?? $patient['cccd']) . ': '
                . $temporaryPassword['password']
                . '. Hiệu lực 5 phút và sẽ hết hiệu lực sau khi đăng nhập.'
            );
        } catch (Throwable $exception) {
            clear_temporary_patient_password((string) ($patient['cccd'] ?? ''));
            log_internal_error('reset_patient_password_failed', $exception, ['admin_id' => $rootAdminId, 'patient_id' => $patientId]);
            set_flash('error', 'Không thể tạo mật khẩu tạm lúc này.');
        }
        redirect('admin_accounts.php' . $_tab);
    }

    if ($action === 'delete_patient') {
        require_admin_accounts_permission('manage_patients');
        $patientId = (int) ($_POST['target_id'] ?? 0);
        $stmt = $conn->prepare('DELETE FROM patients WHERE id = ? LIMIT 1');
        $stmt->bind_param('i', $patientId);
        $stmt->execute();
        $stmt->close();
        set_flash('success', 'Đã xóa tài khoản bệnh nhân.');
        redirect('admin_accounts.php' . $_tab);
    }

    if ($action === 'save_doctor') {
        require_admin_accounts_permission('manage_doctors');
        $doctorId = (int) ($_POST['doctor_id'] ?? 0);
        $name = normalize_single_line_input($_POST['name'] ?? '');
        $title = normalize_single_line_input($_POST['title'] ?? '');
        $department = normalize_single_line_input($_POST['department'] ?? '');
        $specialties = normalize_single_line_input($_POST['specialties'] ?? '');
        $bio = trim((string) ($_POST['bio'] ?? ''));
        $currentPhoto = normalize_single_line_input($_POST['current_photo'] ?? '');
        $removePhoto = isset($_POST['remove_photo']);

        if (!validate_person_name($name) || ($title !== '' && !validate_generic_label($title)) || ($department !== '' && !validate_generic_label($department)) || ($specialties !== '' && !validate_generic_label($specialties, 255)) || ($bio !== '' && !validate_multiline_text($bio, 4000))) {
            set_flash('error', 'Thông tin bác sĩ không hợp lệ.');
            redirect('admin_accounts.php' . $_tab);
        }

        if ($name === '' || $department === '') {
            set_flash('error', 'Vui lòng nhập tên bác sĩ và khoa.');
            redirect('admin_accounts.php' . $_tab);
        }

        $photoPath = $currentPhoto !== '' ? $currentPhoto : null;
        if ($removePhoto) {
            delete_doctor_photo($photoPath);
            $photoPath = null;
        }

        if (isset($_FILES['photo']) && ($_FILES['photo']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            try {
                $photoPath = store_doctor_photo($_FILES['photo'], $photoPath);
            } catch (Throwable $exception) {
                log_internal_error('doctor_photo_upload_failed', $exception, ['admin_id' => $rootAdminId, 'doctor_id' => $doctorId]);
                set_flash('error', 'Không thể tải ảnh bác sĩ lúc này.');
                redirect('admin_accounts.php' . $_tab);
            }
        }

        if ($doctorId > 0) {
            $stmt = $conn->prepare(
                'UPDATE doctors
                 SET name = ?, title = ?, department = ?, specialties = ?, bio = ?, photo_path = ?
                 WHERE id = ?'
            );
            $stmt->bind_param('ssssssi', $name, $title, $department, $specialties, $bio, $photoPath, $doctorId);
            $stmt->execute();
            $stmt->close();
            set_flash('success', 'Đã cập nhật thông tin bác sĩ.');
        } else {
            $stmt = $conn->prepare(
                'INSERT INTO doctors (name, title, department, specialties, bio, photo_path)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            $stmt->bind_param('ssssss', $name, $title, $department, $specialties, $bio, $photoPath);
            $stmt->execute();
            $stmt->close();
            set_flash('success', 'Đã thêm bác sĩ mới.');
        }

        redirect('admin_accounts.php' . $_tab);
    }

    if ($action === 'delete_doctor') {
        require_admin_accounts_permission('manage_doctors');
        $doctorId = (int) ($_POST['target_id'] ?? 0);
        $stmt = $conn->prepare('SELECT photo_path FROM doctors WHERE id = ? LIMIT 1');
        $stmt->bind_param('i', $doctorId);
        $stmt->execute();
        $doctor = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$doctor) {
            set_flash('error', 'Bác sĩ không tồn tại.');
            redirect('admin_accounts.php' . $_tab);
        }

        try {
            delete_doctor_photo($doctor['photo_path'] ?? null);
            $stmt = $conn->prepare('DELETE FROM doctors WHERE id = ? LIMIT 1');
            $stmt->bind_param('i', $doctorId);
            $stmt->execute();
            $stmt->close();
            set_flash('success', 'Đã xóa bác sĩ.');
        } catch (Throwable $exception) {
            set_flash('error', 'Không thể xóa bác sĩ đang có lịch hẹn hoặc hồ sơ khám liên quan.');
        }
        redirect('admin_accounts.php' . $_tab);
    }

    if ($action === 'save_quick_reply') {
        require_admin_accounts_permission('manage_chatbot');
        $replyId = (int) ($_POST['reply_id'] ?? 0);
        $question = trim($_POST['question'] ?? '');
        $answer = trim($_POST['answer'] ?? '');
        $sortOrder = (int) ($_POST['sort_order'] ?? 0);
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if ($question === '' || $answer === '') {
            set_flash('error', 'Vui lòng nhập đầy đủ câu hỏi nhanh và câu trả lời.');
            redirect('admin_accounts.php' . $_tab);
        }

        if ($replyId > 0) {
            $stmt = $conn->prepare(
                'UPDATE chat_quick_replies
                 SET question = ?, answer = ?, sort_order = ?, is_active = ?
                 WHERE id = ?'
            );
            $stmt->bind_param('ssiii', $question, $answer, $sortOrder, $isActive, $replyId);
            $stmt->execute();
            $stmt->close();
            set_flash('success', 'Đã cập nhật câu trả lời nhanh.');
        } else {
            $stmt = $conn->prepare(
                'INSERT INTO chat_quick_replies (question, answer, sort_order, is_active)
                 VALUES (?, ?, ?, ?)'
            );
            $stmt->bind_param('ssii', $question, $answer, $sortOrder, $isActive);
            $stmt->execute();
            $stmt->close();
            set_flash('success', 'Đã thêm câu trả lời nhanh mới.');
        }

        redirect('admin_accounts.php' . $_tab);
    }

    if ($action === 'publish_patient_announcement') {
        require_admin_accounts_permission('publish_announcements');
        $title = trim((string) ($_POST['announcement_title'] ?? ''));
        $message = trim((string) ($_POST['announcement_message'] ?? ''));
        $pushToChat = isset($_POST['push_to_chat']);
        $sendToEmail = isset($_POST['send_to_email']);

        if ($title === '' || $message === '') {
            set_flash('error', 'Vui lòng nhập đầy đủ tiêu đề và nội dung thông báo.');
            redirect('admin_accounts.php' . $_tab);
        }

        create_patient_announcement($conn, $title, $message, $rootAdminId, $pushToChat);
        $emailSummary = '';
        if ($sendToEmail) {
            $emailResult = send_patient_announcement_emails($conn, $title, $message);
            $emailSummary = ' Gmail: gửi thành công ' . (int) $emailResult['sent'] . '/' . (int) $emailResult['total'];
            if ((int) $emailResult['failed'] > 0) {
                $emailSummary .= ', lỗi ' . (int) $emailResult['failed'];
            }
            $emailSummary .= '.';
        }
        set_flash('success', 'Đã gửi thông báo mới đến khách hàng.' . $emailSummary);
        redirect('admin_accounts.php' . $_tab);
    }

    if ($action === 'reply_patient_chat') {
        require_admin_accounts_permission('manage_support_chat');
        $patientId = (int) ($_POST['patient_id'] ?? 0);
        $message = trim((string) ($_POST['message'] ?? ''));
        $latestChatId = (int) ($_POST['latest_chat_id'] ?? 0);

        if ($patientId <= 0 || $message === '') {
            set_flash('error', 'Vui lòng nhập nội dung trả lời cho bệnh nhân.');
            redirect('admin_accounts.php' . $_tab);
        }

        $adminName = (string) ($_SESSION['admin_full_name'] ?? $_SESSION['admin_username'] ?? 'Nhân viên hỗ trợ');
        save_chat_message($patientId, 'bot', '[Hỗ trợ] ' . $adminName . ': ' . $message);
        mark_admin_support_notice_seen($rootAdminId, $latestChatId);
        audit_log('admin_support_reply_sent', [
            'patient_id' => $patientId,
            'latest_chat_id' => $latestChatId,
        ]);
        set_flash('success', 'Đã gửi trả lời cho bệnh nhân.');
        redirect('admin_accounts.php' . $_tab);
    }
}

$clinicSettings = site_settings([
    'clinic_name' => 'Phòng khám đa khoa Phú Thái',
    'clinic_logo_path' => '',
    'clinic_intro' => 'Phòng khám cung cấp dịch vụ đặt lịch, trả kết quả và quản lý hồ sơ khám bệnh trên cùng một hệ thống trực tuyến.',
    'clinic_mission' => 'Tối ưu quy trình tiếp nhận và giúp bệnh nhân theo dõi hồ sơ nhanh hơn.',
    'clinic_facility' => 'Có khu khám, khu xét nghiệm và hệ thống lưu trữ hồ sơ điện tử phục vụ tra cứu kết quả.',
    'clinic_services' => "Siêu âm tổng quát\nXét nghiệm máu, nước tiểu\nKhám nội tổng quát\nĐiện tim\nTư vấn sức khỏe định kỳ",
    'clinic_support' => 'Hỗ trợ người bệnh từ đặt lịch, tiếp nhận hồ sơ đến trả kết quả trực tuyến.',
    'support_hotline' => '1900 0000',
    'support_email' => 'congnghethongtin247@gmail.com',
    'clinic_address' => '',
    'google_maps_url' => '',
    'apple_maps_url' => '',
    'appointments_enabled' => '1',
    'chatbot_intro' => 'Chat hỗ trợ giúp bệnh nhân xem nhanh hướng dẫn thường gặp và gửi câu hỏi cho bộ phận hỗ trợ.',
    'chatbot_fallback' => chatbot_default_reply(),
    'ai_prompt_procedures' => "1. Đăng ký tại quầy lễ tân.\n2. Lấy số thứ tự và đóng phí tạm ứng.\n3. Đến phòng khám chuyên khoa theo hướng dẫn.\n4. Thực hiện các chỉ định lâm sàng (nếu có).\n5. Quay lại phòng khám ban đầu để nghe kết luận.\n6. Lấy thuốc và thanh toán.",
    'ai_prompt_pricing' => "Siêu âm tổng quát: 150.000 VNĐ\nXét nghiệm máu cơ bản: 200.000 VNĐ\nKhám nội chung: 100.000 VNĐ\nChụp X-quang: 120.000 VNĐ\nĐiện tim đồ: 80.000 VNĐ",
    'ai_prompt_documents' => "Bệnh nhân cần mang theo CCCD và thẻ BHYT hợp lệ. Cấp giấy chuyển tuyến theo đúng quy định của BHYT.",
    'ai_prompt_benefits' => "BHYT đúng tuyến được hưởng 80% chi phí khám chữa bệnh. Trẻ em dưới 6 tuổi được miễn phí khám.",
    'zalo_url' => '',
    'messenger_url' => '',
    'gemini_api_keys' => '',
], true); // true = dùng default khi DB rỗng

$stmt = $conn->prepare('SELECT * FROM admins WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $rootAdminId);
$stmt->execute();
$rootAdmin = $stmt->get_result()->fetch_assoc();
$stmt->close();

$staffAccounts = $conn->query('SELECT * FROM admins WHERE is_root = 0 ORDER BY id ASC');
$patients = $conn->query(patient_select_sql('ORDER BY id ASC', true, $patientEmailEnabled, true));
$doctors = $conn->query('SELECT id, name, title, department, specialties, bio, photo_path FROM doctors ORDER BY id ASC');
$quickReplies = $conn->query('SELECT id, question, answer, sort_order, is_active FROM chat_quick_replies ORDER BY sort_order ASC, id ASC');
$recentAnnouncements = get_recent_patient_announcements($conn, 12);
$recentChats = $conn->query(
    "SELECT c.id, c.patient_id, p.full_name, p.cccd, p.phone, c.sender, c.message, c.created_at
     FROM chats c
     INNER JOIN patients p ON p.id = c.patient_id
     ORDER BY c.id DESC
     LIMIT 25"
);
$latestBackup = latest_backup_info();
$newsPosts = get_recent_news_posts(50, false);
$customerResources = get_customer_resources(50, false);
$supportChatNotice = get_admin_support_chat_notice($conn, $rootAdminId, 4);
$logFilters = [
    'account' => trim((string) ($_GET['account'] ?? '')),
    'event' => trim((string) ($_GET['event'] ?? '')),
    'actor_type' => trim((string) ($_GET['actor_type'] ?? 'all')),
    'date_from' => trim((string) ($_GET['date_from'] ?? '')),
    'date_to' => trim((string) ($_GET['date_to'] ?? '')),
];
$auditLogs = filter_logs(recent_audit_logs(120), $logFilters);
$securityLogs = filter_logs(recent_security_logs(100), $logFilters);

render_header('Quản lý tài khoản');
?>
<div class="admin-wrap">
<style>
:root{--ct:#eaecf0;--cb:#d0d5dd;--bg:#fff;--bg2:#f9fafb;--tx:#101828;--tx2:#667085;--info-bg:#eff8ff;--info-tx:#175cd3;--ok-bg:#ecfdf3;--ok-tx:#027a48;--warn-bg:#fffaeb;--warn-tx:#b54708;--err-bg:#fef3f2;--err-tx:#b42318;--r:6px;--rl:10px;}
.admin-wrap{max-width:1180px;margin:0 auto;padding:0 20px 2rem}
.tab-content{display:none!important}.tab-content.tab-active{display:block!important}
.tab-icon{font-size:14px;width:16px;text-align:center}
.page-header{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:20px;padding:16px 20px;background:var(--bg);border:0.5px solid var(--ct);border-radius:var(--rl)}
.page-title{font-size:18px;font-weight:500;color:var(--tx);margin-bottom:6px}
.page-desc{font-size:13px;color:var(--tx2);max-width:520px;line-height:1.5}
.role-badge{display:inline-block;padding:3px 10px;border-radius:var(--r);font-size:12px;background:var(--info-bg);color:var(--info-tx);font-weight:500}
.section-title{font-size:13px;font-weight:500;color:var(--tx);margin-bottom:12px;padding-bottom:8px;border-bottom:0.5px solid var(--ct)}
.g2{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:16px}
.g3{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:16px}
@media(max-width:640px){.g2,.g3{grid-template-columns:1fr}}
.c{background:var(--bg);border:0.5px solid var(--ct);border-radius:var(--rl);padding:16px;margin-bottom:12px}
.c-label{font-size:12px;color:var(--tx2);margin-bottom:4px}
.c-val{font-size:22px;font-weight:500;color:var(--tx)}
.c-sub{font-size:12px;color:var(--tx2);margin-top:2px}
.f-row{display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:0.5px solid var(--ct)}
.f-row:last-child{border-bottom:none}
.f-label{font-size:13px;color:var(--tx2);min-width:170px;flex-shrink:0}
.f-val{font-size:13px;color:var(--tx);flex:1}
.tog{width:36px;height:20px;border-radius:10px;border:0.5px solid var(--cb);background:var(--bg2);position:relative;cursor:pointer;flex-shrink:0;transition:background .15s}
.tog.on{background:var(--primary);border-color:var(--primary)}
.tog::after{content:'';position:absolute;top:2px;left:2px;width:14px;height:14px;border-radius:50%;background:#fff;transition:left .15s;box-shadow:0 1px 3px rgba(0,0,0,.2)}
.tog.on::after{left:18px}
.tog-wrap{position:relative;width:36px;height:20px;flex-shrink:0}
.tog-wrap input{opacity:0;width:0;height:0;position:absolute}
.tog-wrap .tog-track{position:absolute;inset:0;border-radius:10px;border:0.5px solid var(--cb);background:var(--bg2);cursor:pointer;transition:background .15s}
.tog-wrap .tog-track::after{content:'';position:absolute;top:2px;left:2px;width:14px;height:14px;border-radius:50%;background:#fff;transition:left .15s;box-shadow:0 1px 3px rgba(0,0,0,.2)}
.tog-wrap input:checked+.tog-track{background:var(--primary);border-color:var(--primary)}
.tog-wrap input:checked+.tog-track::after{left:18px}
.btn-p{padding:7px 16px;font-size:13px;border-radius:var(--r);border:none;background:linear-gradient(135deg,var(--primary),var(--secondary));color:#fff;cursor:pointer;font-weight:600;font-family:inherit;box-shadow:0 6px 12px rgba(0,119,182,.15)}
.btn-p:hover{transform:translateY(-1px);box-shadow:0 8px 16px rgba(0,119,182,.25)}
.btn-s{padding:5px 12px;font-size:12px;border-radius:var(--r);border:0.5px solid var(--cb);background:transparent;color:var(--tx);cursor:pointer;font-family:inherit}
.btn-s:hover{background:var(--bg2)}
.btn-d{padding:5px 12px;font-size:12px;border-radius:var(--r);border:0.5px solid #fca5a5;background:#fef2f2;color:#b91c1c;cursor:pointer;font-family:inherit}
.btn-d:hover{background:#fee2e2}
.tag{display:inline-block;padding:2px 8px;border-radius:var(--r);font-size:11px}
.tag-green{background:var(--ok-bg);color:var(--ok-tx)}
.tag-amber{background:var(--warn-bg);color:var(--warn-tx)}
.tag-red{background:var(--err-bg);color:var(--err-tx)}
.tag-gray{background:var(--bg2);color:var(--tx2)}
.tag-blue{background:var(--info-bg);color:var(--info-tx)}
.tbl-wrap{overflow-x:auto}
table{width:100%;border-collapse:collapse;font-size:13px}
th{text-align:left;padding:8px 12px;font-weight:500;color:var(--tx2);border-bottom:0.5px solid var(--ct);font-size:12px}
td{padding:10px 12px;border-bottom:0.5px solid var(--ct);color:var(--tx);vertical-align:middle}
tr:last-child td{border-bottom:none}
tr:hover td{background:var(--bg2)}
.av{width:28px;height:28px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-size:11px;font-weight:600;background:var(--info-bg);color:var(--info-tx);flex-shrink:0}
.row-f{display:flex;align-items:center;gap:8px}
.sbar{display:flex;gap:8px;margin-bottom:16px;align-items:center}
.sinp{flex:1;padding:7px 12px;font-size:13px;border:0.5px solid var(--cb);border-radius:var(--r);background:var(--bg);color:var(--tx);font-family:inherit}
.sinp:focus{outline:none;border-color:var(--primary)}
.log-item{padding:10px 0;border-bottom:0.5px solid var(--ct);display:flex;gap:12px;font-size:13px}
.log-item:last-child{border-bottom:none}
.log-time{color:var(--tx2);font-size:12px;min-width:120px;flex-shrink:0}
.log-msg{color:var(--tx)}
.log-user{color:var(--tx2);font-size:12px;margin-top:2px}
.chat-bubble{padding:10px 14px;border-radius:var(--rl);font-size:13px;line-height:1.5;max-width:80%}
.chat-bot{background:var(--bg2);color:var(--tx);border-bottom-left-radius:4px}
.chat-user{background:var(--info-bg);color:var(--info-tx);border-bottom-right-radius:4px;align-self:flex-end}
.chat-wrap{display:flex;flex-direction:column;gap:10px;padding:14px;background:var(--bg2);border-radius:var(--rl);margin-bottom:16px}
.modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,.4);backdrop-filter:blur(3px);z-index:200;display:flex;align-items:center;justify-content:center;padding:16px;opacity:0;pointer-events:none;transition:opacity .15s}
.modal-overlay.open{opacity:1;pointer-events:all}
.modal{background:var(--bg);border-radius:var(--rl);padding:24px;width:100%;max-width:580px;max-height:88vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,.18);border:0.5px solid var(--ct)}
.modal-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;padding-bottom:14px;border-bottom:0.5px solid var(--ct)}
.modal-header h3{margin:0;font-size:15px;font-weight:500;color:var(--tx)}
.modal-close{background:var(--bg2);border:0.5px solid var(--cb);color:var(--tx2);padding:4px 10px;border-radius:var(--r);cursor:pointer;font-size:16px;line-height:1.4}
.modal-close:hover{background:var(--ct)}
.f-inp{width:100%;padding:7px 10px;font-size:13px;border:0.5px solid var(--cb);border-radius:var(--r);background:var(--bg);color:var(--tx);font-family:inherit}
.f-inp:focus{outline:none;border-color:var(--primary)}
.f-ta{width:100%;padding:8px 10px;font-size:13px;border:0.5px solid var(--cb);border-radius:var(--r);background:var(--bg);color:var(--tx);font-family:inherit;resize:vertical;min-height:80px}
.f-ta:focus{outline:none;border-color:var(--primary)}
.f-label-sm{font-size:12px;color:var(--tx2);margin-bottom:4px;display:block;font-weight:500}
.f-group{margin-bottom:12px}
.f-grid2{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:12px}
.perm-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:6px;margin-top:10px}
.perm-item{display:flex;align-items:center;gap:8px;padding:7px 10px;border-radius:var(--r);border:0.5px solid var(--ct);background:var(--bg);cursor:pointer}
.perm-item:hover{border-color:var(--cb);background:var(--bg2)}
.perm-item input{width:auto;margin:0;cursor:pointer}
.perm-item span{font-size:12px;color:var(--tx);cursor:pointer}
.img-prev{width:72px;height:72px;object-fit:cover;border-radius:var(--r);border:0.5px solid var(--ct);display:none;margin-top:6px}
.img-prev.vis{display:block}
.actions-row{display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-top:12px}
.txt-muted{color:var(--tx2)}.txt-sm{font-size:12px}
.empty-note{padding:24px;text-align:center;color:var(--tx2);font-size:13px}
.admin-wrap button:not(.tab-btn){box-shadow:none}
</style>
<?php render_flash(); ?>
  <?php require 'admin_tabs_nav.php'; ?>

  <?php 
  $firstTab = true;
  $tabs = [
      'tab-system' => ['perm' => ['manage_clinic_content', 'create_backup'], 'file' => 'admin_accounts_tabs/tab-system.php'],
      'tab-staff' => ['perm' => 'manage_accounts', 'file' => 'admin_accounts_tabs/tab-staff.php'],
      'tab-doctors' => ['perm' => 'manage_doctors', 'file' => 'admin_accounts_tabs/tab-doctors.php'],
      'tab-patients' => ['perm' => 'manage_patients', 'file' => 'admin_accounts_tabs/tab-patients.php'],
      'tab-communications' => ['perm' => ['manage_chatbot', 'publish_announcements'], 'file' => 'admin_accounts_tabs/tab-communications.php'],
      'tab-support' => ['perm' => 'manage_support_chat', 'file' => 'admin_accounts_tabs/tab-support.php'],
      'tab-news' => ['perm' => 'manage_clinic_content', 'file' => 'admin_accounts_tabs/tab-news.php'],
      'tab-resources' => ['perm' => 'manage_clinic_content', 'file' => 'admin_accounts_tabs/tab-resources.php'],
      'tab-logs' => ['perm' => 'view_logs', 'file' => 'admin_accounts_tabs/tab-logs.php'],
  ];

  foreach ($tabs as $id => $data): 
      $hasPerm = false;
      if (is_root_admin()) {
          $hasPerm = true;
      } else {
          $perms = (array)$data['perm'];
          foreach ($perms as $p) {
              if (admin_can($p)) { $hasPerm = true; break; }
          }
      }
      
      if ($hasPerm):
          $activeClass = $firstTab ? 'tab-active' : '';
          $firstTab = false;
  ?>
  <div id="<?= $id ?>" class="tab-content <?= $activeClass ?>">
    <?php require $data['file']; ?>
  </div>
  <?php endif; endforeach; ?>


</div>
<script>
(function() {
  var allTabs = document.querySelectorAll('.tab-content');
  var navLinks = document.querySelectorAll('[data-tab]');
  var currentTabId = null;

  function activateTab(tabId) {
    var found = false;
    allTabs.forEach(function(el) {
      if (el.id === tabId) {
        el.classList.add('tab-active');
        found = true;
      } else {
        el.classList.remove('tab-active');
      }
    });
    navLinks.forEach(function(a) {
      if (a.getAttribute('data-tab') === tabId) {
        a.classList.add('active');
      } else {
        a.classList.remove('active');
      }
    });
    if (!found && allTabs.length > 0) {
      allTabs[0].classList.add('tab-active');
    }
    currentTabId = found ? tabId : (allTabs[0] ? allTabs[0].id : null);
  }

  function getHashTab() {
    var hash = window.location.hash.replace('#', '');
    return hash || null;
  }

  var initial = getHashTab();
  if (initial) {
    activateTab(initial);
  } else if (allTabs.length > 0) {
    currentTabId = allTabs[0].id;
  }

  navLinks.forEach(function(a) {
    a.addEventListener('click', function(e) {
      var tabId = a.getAttribute('data-tab');
      if (tabId) {
        e.preventDefault();
        history.pushState(null, '', '#' + tabId);
        activateTab(tabId);
        window.scrollTo({ top: 0, behavior: 'smooth' });
      }
    });
  });

  window.addEventListener('popstate', function() {
    var tabId = getHashTab();
    if (tabId) activateTab(tabId);
  });

  // Inject _tab hidden input into every form before submit
  document.addEventListener('submit', function(e) {
    var form = e.target;
    if (!form || form.tagName !== 'FORM') return;
    var tab = currentTabId || getHashTab();
    if (!tab) return;
    var existing = form.querySelector('input[name="_tab"]');
    if (existing) {
      existing.value = tab;
    } else {
      var inp = document.createElement('input');
      inp.type = 'hidden';
      inp.name = '_tab';
      inp.value = tab;
      form.appendChild(inp);
    }
  }, true);
})();
</script>
<?php render_footer(); ?>

