<?php
declare(strict_types=1);

require_once 'config.php';
require_admin_permission('manage_records');

$adminDepartment = (string) ($_SESSION['admin_department'] ?? '');
$adminId = (int) ($_SESSION['admin_id'] ?? 0);
$canManageAllRecords = admin_can('manage_all_records');
$activeTab = trim((string) ($_GET['tab'] ?? 'records'));
if ($activeTab !== 'appointments') { $activeTab = 'records'; }
$editingRecordId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
$editingRecord = null;
$editingAppointmentId = isset($_GET['appointment_edit']) ? (int) $_GET['appointment_edit'] : 0;
$editingAppointment = null;

function can_manage_doctor_record(array $doctor, bool $canManageAllRecords, string $adminDepartment): bool
{
    if ($canManageAllRecords || $adminDepartment === '') {
        return true;
    }

    return (string) ($doctor['department'] ?? '') === $adminDepartment;
}

function upload_pdf_or_redirect(int $patientId, int $doctorId): ?string
{
    if (!isset($_FILES['pdf']) || ($_FILES['pdf']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($_FILES['pdf']['error'] !== UPLOAD_ERR_OK) {
        set_flash('error', 'Tải tệp lên thất bại.');
        redirect('admin_add_record.php?tab=records');
        return null;
    }

    if (($_FILES['pdf']['size'] ?? 0) > 5 * 1024 * 1024) {
        set_flash('error', 'Tệp PDF không được vượt quá 5MB.');
        redirect('admin_add_record.php?tab=records');
        return null;
    }

    $extension = strtolower(pathinfo($_FILES['pdf']['name'], PATHINFO_EXTENSION));
    if ($extension !== 'pdf') {
        set_flash('error', 'Chỉ chấp nhận tệp PDF.');
        redirect('admin_add_record.php?tab=records');
        return null;
    }

    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = $finfo ? finfo_file($finfo, $_FILES['pdf']['tmp_name']) : false;
        if ($finfo) {
            finfo_close($finfo);
        }

        if ($mimeType !== false && $mimeType !== 'application/pdf') {
            set_flash('error', 'Tệp tải lên không phải PDF hợp lệ.');
            redirect('admin_add_record.php?tab=records');
            return null;
        }
    }

    try {
        return store_result_upload($_FILES['pdf'], $patientId, $doctorId);
    } catch (Throwable $exception) {
        security_log('medical_record_upload_failed', [
            'admin_id' => (int) ($_SESSION['admin_id'] ?? 0),
            'patient_id' => $patientId,
            'doctor_id' => $doctorId,
            'error' => $exception->getMessage(),
        ]);
        set_flash('error', 'Không thể lưu tệp kết quả.');
        redirect('admin_add_record.php?tab=records');
        return null;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? 'create');

    if ($action === 'dismiss_support_chat_notice') {
        $guardError = validate_form_guard('admin_support_notice', 20, 300, 0, false);
        if ($guardError !== null) {
            set_flash('error', $guardError);
            redirect('admin_add_record.php');
        }

        mark_admin_support_notice_seen($adminId, (int) ($_POST['latest_chat_id'] ?? 0));
        set_flash('success', 'Đã đánh dấu thông báo hỗ trợ mới.');
        redirect('admin_add_record.php');
    }

    audit_log('admin_records_action', [
        'action' => $action,
        'record_id' => (int) ($_POST['record_id'] ?? 0),
        'appointment_id' => (int) ($_POST['appointment_id'] ?? 0),
        'patient_id' => (int) ($_POST['patient_id'] ?? 0),
        'doctor_id' => (int) ($_POST['doctor_id'] ?? $_POST['appointment_doctor_id'] ?? 0),
    ]);

    if ($action === 'delete_appointment') {
        $guardError = validate_form_guard('admin_manage_appointments', 18, 600, 0, false);
        if ($guardError !== null) {
            set_flash('error', $guardError);
            redirect('admin_add_record.php?tab=appointments');
        }

        $appointmentId = (int) ($_POST['appointment_id'] ?? 0);
        $appointment = find_admin_appointment($conn, $appointmentId);
        if (!$appointment) {
            set_flash('error', 'Lịch hẹn không tồn tại.');
            redirect('admin_add_record.php?tab=appointments');
        }

        if (!can_admin_manage_appointment($appointment, $canManageAllRecords, $adminDepartment)) {
            set_flash('error', 'Bạn không có quyền xóa lịch hẹn thuộc bộ phận khác.');
            redirect('admin_add_record.php?tab=appointments');
        }

        delete_appointment_by_id($conn, $appointmentId);
        set_flash('success', 'Đã xóa lịch hẹn.');
        redirect('admin_add_record.php?tab=appointments');
    }

    if ($action === 'update_appointment') {
        $guardError = validate_form_guard('admin_manage_appointments', 18, 600, 0, false);
        $appointmentId = (int) ($_POST['appointment_id'] ?? 0);
        $doctorId = (int) ($_POST['appointment_doctor_id'] ?? 0);
        $dateValue = normalize_single_line_input($_POST['appointment_date'] ?? '');
        $reason = trim((string) ($_POST['appointment_reason'] ?? ''));
        $status = trim($_POST['appointment_status'] ?? 'Chờ khám');

        if ($guardError !== null) {
            set_flash('error', $guardError);
            redirect('admin_add_record.php?tab=appointments');
        }

        $appointment = find_admin_appointment($conn, $appointmentId);
        if (!$appointment) {
            set_flash('error', 'Không tìm thấy lịch hẹn cần cập nhật.');
            redirect('admin_add_record.php?tab=appointments');
        }

        if (!can_admin_manage_appointment($appointment, $canManageAllRecords, $adminDepartment)) {
            set_flash('error', 'Bạn không có quyền sửa lịch hẹn thuộc bộ phận khác.');
            redirect('admin_add_record.php?tab=appointments');
        }

        $doctor = find_doctor_by_id($conn, $doctorId);
        $appointmentDate = normalize_appointment_datetime($dateValue);
        if (
            !$doctor
            || $appointmentDate === null
            || !validate_multiline_text($reason, 1000)
            || !validate_generic_label($status, 50)
        ) {
            set_flash('error', 'Vui lòng nhập đủ thông tin lịch hẹn hợp lệ.');
            redirect('admin_add_record.php?tab=appointments&appointment_edit=' . $appointmentId);
        }

        $candidate = $appointment;
        $candidate['department'] = $doctor['department'] ?? '';
        if (!can_admin_manage_appointment($candidate, $canManageAllRecords, $adminDepartment)) {
            set_flash('error', 'Bạn chỉ được chuyển lịch hẹn trong phạm vi bộ phận được phân quyền.');
            redirect('admin_add_record.php?tab=appointments&appointment_edit=' . $appointmentId);
        }

        with_transaction($conn, static function () use ($conn, $doctorId, $appointmentDate, $reason, $status, $appointmentId): void {
            $stmt = $conn->prepare(
                'UPDATE appointments
                 SET doctor_id = ?, appointment_date = ?, reason = ?, status = ?
                 WHERE id = ?'
            );
            $stmt->bind_param('isssi', $doctorId, $appointmentDate, $reason, $status, $appointmentId);
            $stmt->execute();
            $stmt->close();
        });

        set_flash('success', 'Đã cập nhật lịch hẹn.');
        redirect('admin_add_record.php?tab=appointments');
    }

    $guardError = validate_form_guard('admin_add_record', 18, 600, 0, false);
    if ($guardError !== null) {
        set_flash('error', $guardError);
        redirect('admin_add_record.php?tab=records');
    }

    if ($action === 'delete') {
        $recordId = (int) ($_POST['record_id'] ?? 0);
        $stmt = $conn->prepare(
            'SELECT mr.id, mr.result_file, d.department
             FROM medical_records mr
             INNER JOIN doctors d ON d.id = mr.doctor_id
             WHERE mr.id = ?
             LIMIT 1'
        );
        $stmt->bind_param('i', $recordId);
        $stmt->execute();
        $record = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$record) {
            set_flash('error', 'Hồ sơ khám không tồn tại.');
            redirect('admin_add_record.php?tab=records');
        }

        if (!$canManageAllRecords && $adminDepartment !== '' && (string) $record['department'] !== $adminDepartment) {
            set_flash('error', 'Bạn không có quyền xóa hồ sơ thuộc bộ phận khác.');
            redirect('admin_add_record.php?tab=records');
        }

        with_transaction($conn, static function () use ($conn, $recordId): void {
            $stmt = $conn->prepare('DELETE FROM medical_records WHERE id = ? LIMIT 1');
            $stmt->bind_param('i', $recordId);
            $stmt->execute();
            $stmt->close();
        });
        delete_result_file($record['result_file'] ?? null);

        security_log('medical_record_deleted', [
            'admin_id' => (int) ($_SESSION['admin_id'] ?? 0),
            'record_id' => $recordId,
        ]);
        set_flash('success', 'Đã xóa hồ sơ khám.');
        redirect('admin_add_record.php?tab=records');
    }

    $recordId = (int) ($_POST['record_id'] ?? 0);
    $patientId = (int) ($_POST['patient_id'] ?? 0);
    $doctorId = (int) ($_POST['doctor_id'] ?? 0);
    $visitDate = normalize_single_line_input($_POST['visit_date'] ?? '');
    $diagnosis = trim((string) ($_POST['diagnosis'] ?? ''));
    $prescription = trim((string) ($_POST['prescription'] ?? ''));
    $removeExistingFile = isset($_POST['remove_existing_file']);
    $visitDate = validate_visit_date($visitDate);

    if (
        $patientId <= 0
        || $doctorId <= 0
        || $visitDate === null
        || !validate_multiline_text($diagnosis, 5000)
        || ($prescription !== '' && !validate_multiline_text($prescription, 5000))
    ) {
        set_flash('error', 'Vui lòng nhập đủ thông tin hồ sơ khám.');
        redirect($recordId > 0 ? 'admin_add_record.php?edit=' . $recordId : 'admin_add_record.php');
    }

    $sendEmail = isset($_POST['send_email']);

    $stmt = $conn->prepare('SELECT id, full_name, email FROM patients WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $patientId);
    $stmt->execute();
    $patient = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $stmt = $conn->prepare('SELECT id, department FROM doctors WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $doctorId);
    $stmt->execute();
    $doctor = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$patient || !$doctor) {
        set_flash('error', 'Bệnh nhân hoặc bác sĩ không tồn tại.');
        redirect($recordId > 0 ? 'admin_add_record.php?edit=' . $recordId : 'admin_add_record.php');
    }

    if (!can_manage_doctor_record($doctor, $canManageAllRecords, $adminDepartment)) {
        set_flash('error', 'Tài khoản của bạn chỉ được trả kết quả cho đúng bộ phận đã được phân quyền.');
        redirect($recordId > 0 ? 'admin_add_record.php?edit=' . $recordId : 'admin_add_record.php');
    }

    $newResultFile = upload_pdf_or_redirect($patientId, $doctorId);

    if ($action === 'update' && $recordId > 0) {
        $stmt = $conn->prepare(
            'SELECT mr.id, mr.result_file, d.department
             FROM medical_records mr
             INNER JOIN doctors d ON d.id = mr.doctor_id
             WHERE mr.id = ?
             LIMIT 1'
        );
        $stmt->bind_param('i', $recordId);
        $stmt->execute();
        $existingRecord = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$existingRecord) {
            set_flash('error', 'Hồ sơ khám cần sửa không tồn tại.');
            redirect('admin_add_record.php');
        }

        if (!$canManageAllRecords && $adminDepartment !== '' && (string) $existingRecord['department'] !== $adminDepartment) {
            set_flash('error', 'Bạn không có quyền sửa hồ sơ thuộc bộ phận khác.');
            redirect('admin_add_record.php');
        }

        $oldResultFile = $existingRecord['result_file'] ?? null;
        $resultFile = $oldResultFile;
        if ($removeExistingFile) {
            $resultFile = null;
        }
        if ($newResultFile !== null) {
            $resultFile = $newResultFile;
        }

        try {
            with_transaction($conn, static function () use ($conn, $patientId, $doctorId, $visitDate, $diagnosis, $prescription, $resultFile, $recordId): void {
                $stmt = $conn->prepare(
                    'UPDATE medical_records
                     SET patient_id = ?, doctor_id = ?, visit_date = ?, diagnosis = ?, prescription = ?, result_file = ?
                     WHERE id = ?'
                );
                $prescriptionValue = $prescription !== '' ? $prescription : null;
                $stmt->bind_param('iissssi', $patientId, $doctorId, $visitDate, $diagnosis, $prescriptionValue, $resultFile, $recordId);
                $stmt->execute();
                $stmt->close();
            });
        } catch (Throwable $exception) {
            if ($newResultFile !== null) {
                delete_result_file($newResultFile);
            }
            throw $exception;
        }

        if ($removeExistingFile || $newResultFile !== null) {
            delete_result_file($oldResultFile);
        }

        if ($sendEmail && !empty($patient['email']) && validate_email_address($patient['email'])) {
            try {
                $finalPdf = $resultFile !== null ? APP_RESULTS_ROOT . DIRECTORY_SEPARATOR . $resultFile : null;
                send_patient_result_email($patient['email'], $patient['full_name'], $diagnosis, $prescription, $finalPdf);
            } catch (Throwable $e) {
                log_internal_error('send_result_email_failed', $e, ['record_id' => $recordId]);
            }
        }

        security_log('medical_record_updated', [
            'admin_id' => (int) ($_SESSION['admin_id'] ?? 0),
            'record_id' => $recordId,
            'patient_id' => $patientId,
            'doctor_id' => $doctorId,
        ]);
        set_flash('success', 'Đã cập nhật hồ sơ khám.');
        redirect('admin_add_record.php?tab=records');
    }

    try {
        with_transaction($conn, static function () use ($conn, $patientId, $doctorId, $visitDate, $diagnosis, $prescription, $newResultFile): void {
            $stmt = $conn->prepare(
                'INSERT INTO medical_records (patient_id, doctor_id, visit_date, diagnosis, prescription, result_file)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            $prescriptionValue = $prescription !== '' ? $prescription : null;
            $stmt->bind_param('iissss', $patientId, $doctorId, $visitDate, $diagnosis, $prescriptionValue, $newResultFile);
            $stmt->execute();
            $stmt->close();
        });
    } catch (Throwable $exception) {
        if ($newResultFile !== null) {
            delete_result_file($newResultFile);
        }
        throw $exception;
    }

    if ($sendEmail && !empty($patient['email']) && validate_email_address($patient['email'])) {
        try {
            $finalPdf = $newResultFile !== null ? APP_RESULTS_ROOT . DIRECTORY_SEPARATOR . $newResultFile : null;
            send_patient_result_email($patient['email'], $patient['full_name'], $diagnosis, $prescription, $finalPdf);
        } catch (Throwable $e) {
            log_internal_error('send_result_email_failed', $e, ['patient_id' => $patientId]);
        }
    }

    security_log('medical_record_created', [
        'admin_id' => (int) ($_SESSION['admin_id'] ?? 0),
        'patient_id' => $patientId,
        'doctor_id' => $doctorId,
        'department_scope' => $adminDepartment,
        'has_result_file' => $newResultFile !== null,
    ]);
    set_flash('success', 'Đã lưu hồ sơ khám.');
    redirect('admin_add_record.php?tab=records');
}

if ($editingRecordId > 0) {
    $stmt = $conn->prepare(
        'SELECT mr.id, mr.patient_id, mr.doctor_id, mr.visit_date, mr.diagnosis, mr.prescription, mr.result_file, d.department
         FROM medical_records mr
         INNER JOIN doctors d ON d.id = mr.doctor_id
         WHERE mr.id = ?
         LIMIT 1'
    );
    $stmt->bind_param('i', $editingRecordId);
    $stmt->execute();
    $editingRecord = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$editingRecord) {
        set_flash('error', 'Không tìm thấy hồ sơ cần sửa.');
        redirect('admin_add_record.php');
    }

    if (!$canManageAllRecords && $adminDepartment !== '' && (string) $editingRecord['department'] !== $adminDepartment) {
        set_flash('error', 'Bạn không có quyền sửa hồ sơ thuộc bộ phận khác.');
        redirect('admin_add_record.php');
    }
}

if ($editingAppointmentId > 0) {
    $editingAppointment = find_admin_appointment($conn, $editingAppointmentId);
    if (!$editingAppointment) {
        set_flash('error', 'Không tìm thấy lịch hẹn cần sửa.');
        redirect('admin_add_record.php');
    }

    if (!can_admin_manage_appointment($editingAppointment, $canManageAllRecords, $adminDepartment)) {
        set_flash('error', 'Bạn không có quyền sửa lịch hẹn thuộc bộ phận khác.');
        redirect('admin_add_record.php');
    }
}

$patients = $conn->query('SELECT id, full_name, cccd FROM patients ORDER BY full_name ASC');
if ($canManageAllRecords || $adminDepartment === '') {
    $doctors = $conn->query('SELECT id, name, department FROM doctors ORDER BY name ASC');
    $appointmentDoctors = $conn->query('SELECT id, name, department FROM doctors ORDER BY name ASC');
    $recentRecords = $conn->query(
        'SELECT mr.id, mr.visit_date, p.full_name, d.name AS doctor_name, d.department, mr.diagnosis, mr.result_file
         FROM medical_records mr
         INNER JOIN patients p ON p.id = mr.patient_id
         INNER JOIN doctors d ON d.id = mr.doctor_id
         ORDER BY mr.id DESC
         LIMIT 10'
    );
    $recentAppointments = $conn->query(
        'SELECT a.id, a.appointment_date, a.reason, a.status, p.full_name, d.name AS doctor_name, d.department
         FROM appointments a
         INNER JOIN patients p ON p.id = a.patient_id
         INNER JOIN doctors d ON d.id = a.doctor_id
         ORDER BY a.appointment_date DESC, a.id DESC
         LIMIT 12'
    );
} else {
    $stmt = $conn->prepare('SELECT id, name, department FROM doctors WHERE department = ? ORDER BY name ASC');
    $stmt->bind_param('s', $adminDepartment);
    $stmt->execute();
    $doctors = $stmt->get_result();
    $stmt->close();

    $stmt = $conn->prepare('SELECT id, name, department FROM doctors WHERE department = ? ORDER BY name ASC');
    $stmt->bind_param('s', $adminDepartment);
    $stmt->execute();
    $appointmentDoctors = $stmt->get_result();
    $stmt->close();

    $stmt = $conn->prepare(
        'SELECT mr.id, mr.visit_date, p.full_name, d.name AS doctor_name, d.department, mr.diagnosis, mr.result_file
         FROM medical_records mr
         INNER JOIN patients p ON p.id = mr.patient_id
         INNER JOIN doctors d ON d.id = mr.doctor_id
         WHERE d.department = ?
         ORDER BY mr.id DESC
         LIMIT 10'
    );
    $stmt->bind_param('s', $adminDepartment);
    $stmt->execute();
    $recentRecords = $stmt->get_result();
    $stmt->close();

    $stmt = $conn->prepare(
        'SELECT a.id, a.appointment_date, a.reason, a.status, p.full_name, d.name AS doctor_name, d.department
         FROM appointments a
         INNER JOIN patients p ON p.id = a.patient_id
         INNER JOIN doctors d ON d.id = a.doctor_id
         WHERE d.department = ?
         ORDER BY a.appointment_date DESC, a.id DESC
         LIMIT 12'
    );
    $stmt->bind_param('s', $adminDepartment);
    $stmt->execute();
    $recentAppointments = $stmt->get_result();
    $stmt->close();
}

$formAction = $editingRecord ? 'update' : 'create';
$formTitle = $editingRecord ? 'Chỉnh sửa hồ sơ đã trả' : 'Trả kết quả cho bệnh nhân';
$appointmentStatuses = ['Chờ khám', 'Đã xác nhận', 'Đã khám', 'Đã hủy'];

$supportChatNotice = get_admin_support_chat_notice($conn, $adminId, 4);
render_header('Quản lý hồ sơ khám');
?>
<div class="topbar" style="max-width:1180px;margin:40px auto 20px;padding:0 20px;">
  <div>
    <h1>Quản lý hồ sơ khám</h1>
    <p class="muted">
      Xin chào <?= e($_SESSION['admin_full_name'] ?? ($_SESSION['admin_username'] ?? 'admin')) ?>.
      <?= $canManageAllRecords ? 'Bạn có toàn quyền xử lý kết quả cho mọi bộ phận.' : 'Tài khoản này chỉ được trả kết quả cho bộ phận: ' . e($adminDepartment) . '.' ?>
    </p>
  </div>
</div>

<?php require 'admin_tabs_nav.php'; ?>

<div data-admin-support-endpoint="admin_support_notice.php" data-admin-support-csrf="<?= e(csrf_token('admin_support_api')) ?>" data-admin-chat-link="<?= e((is_root_admin() || admin_can('manage_support_chat')) ? 'admin_accounts.php#recent-chats' : '') ?>"></div>

<?php if (!empty($supportChatNotice['has_new'])): ?>
  <div class="admin-alert-toast">
    <h3>Có <?= (int) $supportChatNotice['unread_count'] ?> tin nhắn hỗ trợ mới</h3>
    <div class="muted">Bệnh nhân vừa gửi câu hỏi hỗ trợ. Nhân viên có thể xem nhanh thông tin bên dưới để hỗ trợ phản hồi.</div>
    <div class="admin-alert-list">
      <?php foreach ($supportChatNotice['messages'] as $message): ?>
        <div class="admin-alert-item">
          <strong><?= e($message['full_name']) ?> - <?= e($message['cccd']) ?></strong>
          <div><?= nl2br(e($message['message'])) ?></div>
          <div class="muted text-sm" style="margin-top:6px;">SĐT: <?= e($message['phone']) ?> | <?= e(date('d/m/Y H:i', strtotime((string) $message['created_at']))) ?></div>
          <form method="post" class="grid" action="admin_support_notice.php" style="margin-top:10px;">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token('admin_support_api')) ?>">
            <input type="hidden" name="action" value="reply">
            <input type="hidden" name="patient_id" value="<?= (int) $message['patient_id'] ?>">
            <input type="hidden" name="latest_chat_id" value="<?= (int) $message['id'] ?>">
            <textarea name="message" placeholder="Nhập trả lời cho bệnh nhân" required></textarea>
            <div class="actions">
              <button type="submit">Gửi trả lời</button>
            </div>
          </form>
        </div>
      <?php endforeach; ?>
    </div>
    <form method="post" class="actions">
      <?php render_form_guard('admin_support_notice'); ?>
      <input type="hidden" name="action" value="dismiss_support_chat_notice">
      <input type="hidden" name="latest_chat_id" value="<?= (int) $supportChatNotice['latest_chat_id'] ?>">
      <button type="submit">Đã xem</button>
      <?php if (is_root_admin() || admin_can('manage_support_chat')): ?>
        <a class="btn btn-secondary" href="admin_accounts.php#recent-chats">Mở nhật ký chat</a>
      <?php endif; ?>
    </form>
  </div>
<?php endif; ?>

<div class="wrap" style="margin-top:0;">
  <?php render_flash(); ?>

  <?php if ($activeTab === 'records'): ?>
  <div class="grid grid-2">
    <section class="card">
      <div class="panel-title">
        <div>
          <h2><?= e($formTitle) ?></h2>
          <?php if ($editingRecord): ?>
            <p class="muted">Bạn đang sửa hồ sơ ID #<?= (int) $editingRecord['id'] ?>. Có thể thay PDF cũ hoặc xóa PDF hiện có.</p>
          <?php endif; ?>
        </div>
        <?php if ($editingRecord): ?>
          <div class="actions">
            <a class="btn btn-secondary" href="admin_add_record.php">Tạo hồ sơ mới</a>
          </div>
        <?php endif; ?>
      </div>
      <form method="post" enctype="multipart/form-data" class="grid">
        <?php render_form_guard('admin_add_record'); ?>
        <input type="hidden" name="action" value="<?= e($formAction) ?>">
        <input type="hidden" name="record_id" value="<?= (int) ($editingRecord['id'] ?? 0) ?>">
        <div>
          <label for="search_patient_input">Tìm bệnh nhân (CCCD hoặc Tên)</label>
          <input type="text" id="search_patient_input" placeholder="Nhập để tìm nhanh..." autocomplete="off">
        </div>
        <div>
          <label for="patient_id">Bệnh nhân</label>
          <select id="patient_id" name="patient_id" required>
            <option value="">Chọn bệnh nhân</option>
            <?php while ($patient = $patients->fetch_assoc()): ?>
              <option value="<?= (int) $patient['id'] ?>" <?= (string) $patient['id'] === (string) ($editingRecord['patient_id'] ?? ($_POST['patient_id'] ?? '')) ? 'selected' : '' ?>>
                <?= e($patient['full_name']) ?> - <?= e($patient['cccd']) ?>
              </option>
            <?php endwhile; ?>
          </select>
        </div>
        <div>
          <label for="doctor_id">Bác sĩ / bộ phận</label>
          <select id="doctor_id" name="doctor_id" required>
            <option value="">Chọn bác sĩ</option>
            <?php while ($doctor = $doctors->fetch_assoc()): ?>
              <option value="<?= (int) $doctor['id'] ?>" <?= (string) $doctor['id'] === (string) ($editingRecord['doctor_id'] ?? ($_POST['doctor_id'] ?? '')) ? 'selected' : '' ?>>
                <?= e($doctor['name']) ?> - <?= e($doctor['department']) ?>
              </option>
            <?php endwhile; ?>
          </select>
        </div>
        <div>
          <label for="visit_date">Ngày khám</label>
          <input id="visit_date" type="date" name="visit_date" value="<?= e((string) ($editingRecord['visit_date'] ?? ($_POST['visit_date'] ?? ''))) ?>" required>
        </div>
        <div>
          <label for="diagnosis">Kết quả / chẩn đoán</label>
          <textarea id="diagnosis" name="diagnosis" required><?= e((string) ($editingRecord['diagnosis'] ?? ($_POST['diagnosis'] ?? ''))) ?></textarea>
        </div>
        <div>
          <label for="prescription">Ghi chú / đơn thuốc</label>
          <textarea id="prescription" name="prescription"><?= e((string) ($editingRecord['prescription'] ?? ($_POST['prescription'] ?? ''))) ?></textarea>
        </div>
        <div>
          <label for="pdf">Tệp kết quả PDF</label>
          <input id="pdf" type="file" name="pdf" accept="application/pdf">
          <?php if (!empty($editingRecord['result_file'])): ?>
            <div class="text-sm muted" style="margin-top:8px;">
              Đang có PDF kết quả.
              <label style="display:block;margin-top:8px;"><input type="checkbox" name="remove_existing_file"> Xóa PDF hiện tại</label>
            </div>
          <?php endif; ?>
        </div>
        <div>
          <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-weight:500;">
            <input type="checkbox" name="send_email" value="1" checked> Gửi kết quả qua Gmail cho bệnh nhân (nếu có email)
          </label>
        </div>
        <div class="actions">
          <button type="submit"><?= $editingRecord ? 'Cập nhật hồ sơ' : 'Lưu kết quả' ?></button>
        </div>
      </form>
    </section>

    <section class="card">
      <h2>Kết quả gần đây</h2>
      <?php if ($recentRecords->num_rows === 0): ?>
        <p class="muted">Chưa có hồ sơ nào.</p>
      <?php else: ?>
        <table>
          <thead>
            <tr>
              <th>Ngày khám</th>
              <th>Bệnh nhân</th>
              <th>Bác sĩ</th>
              <th>Bộ phận</th>
              <th>Chẩn đoán</th>
              <th>Thao tác</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($record = $recentRecords->fetch_assoc()): ?>
              <tr>
                <td><?= e(date('d/m/Y', strtotime($record['visit_date']))) ?></td>
                <td><?= e($record['full_name']) ?></td>
                <td><?= e($record['doctor_name']) ?></td>
                <td><?= e($record['department']) ?></td>
                <td><?= nl2br(e($record['diagnosis'])) ?></td>
                <td>
                  <div class="actions">
                    <a class="btn btn-light" href="admin_add_record.php?edit=<?= (int) $record['id'] ?>">Sửa</a>
                    <form method="post" class="inline-form" onsubmit="return confirm('Xóa hồ sơ khám này?');">
                      <?php render_form_guard('admin_add_record'); ?>
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="record_id" value="<?= (int) $record['id'] ?>">
                      <button type="submit" class="danger-btn">Xóa</button>
                    </form>
                  </div>
                  <?php if (!empty($record['result_file'])): ?>
                    <div class="text-sm" style="margin-top:8px;">
                      <a href="<?= e(result_download_url((int) $record['id'])) ?>" target="_blank" rel="noopener">Xem PDF</a>
                    </div>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </section>
    </section>
  </div>
  <?php endif; ?>

  <?php if ($activeTab === 'appointments'): ?>
    <div class="panel-title">
      <div>
        <h2>Quản lý lịch hẹn</h2>
        <p class="muted">Admin có thể sửa hoặc xóa lịch hẹn trong phạm vi bộ phận được phân quyền.</p>
      </div>
      <?php if ($editingAppointment): ?>
        <div class="actions">
          <a class="btn btn-secondary" href="admin_add_record.php">Thoát sửa lịch</a>
        </div>
      <?php endif; ?>
    </div>

    <?php if ($editingAppointment): ?>
      <form method="post" class="grid grid-2" style="margin-bottom:24px;">
        <?php render_form_guard('admin_manage_appointments'); ?>
        <input type="hidden" name="action" value="update_appointment">
        <input type="hidden" name="appointment_id" value="<?= (int) $editingAppointment['id'] ?>">
        <div>
          <label for="appointment_doctor_id">Bác sĩ</label>
          <select id="appointment_doctor_id" name="appointment_doctor_id" required>
            <option value="">Chọn bác sĩ</option>
            <?php while ($doctor = $appointmentDoctors->fetch_assoc()): ?>
              <option value="<?= (int) $doctor['id'] ?>" <?= (string) $doctor['id'] === (string) $editingAppointment['doctor_id'] ? 'selected' : '' ?>>
                <?= e($doctor['name']) ?> - <?= e($doctor['department']) ?>
              </option>
            <?php endwhile; ?>
          </select>
        </div>
        <div>
          <label for="appointment_date">Ngày giờ khám</label>
          <input id="appointment_date" type="datetime-local" name="appointment_date" value="<?= e(date('Y-m-d\TH:i', strtotime((string) $editingAppointment['appointment_date']))) ?>" required>
        </div>
        <div>
          <label for="appointment_status">Trạng thái</label>
          <select id="appointment_status" name="appointment_status" required>
            <?php foreach ($appointmentStatuses as $status): ?>
              <option value="<?= e($status) ?>" <?= $status === (string) $editingAppointment['status'] ? 'selected' : '' ?>><?= e($status) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label for="appointment_reason">Lý do khám</label>
          <textarea id="appointment_reason" name="appointment_reason" required><?= e((string) $editingAppointment['reason']) ?></textarea>
        </div>
        <div class="actions">
          <button type="submit">Cập nhật lịch hẹn</button>
        </div>
      </form>
    <?php endif; ?>

    <?php if ($recentAppointments->num_rows === 0): ?>
      <p class="muted">Chưa có lịch hẹn nào.</p>
    <?php else: ?>
      <table>
        <thead>
          <tr>
            <th>Thời gian</th>
            <th>Bệnh nhân</th>
            <th>Bác sĩ</th>
            <th>Bộ phận</th>
            <th>Lý do</th>
            <th>Trạng thái</th>
            <th>Thao tác</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($appointment = $recentAppointments->fetch_assoc()): ?>
            <tr>
              <td><?= e(date('d/m/Y H:i', strtotime($appointment['appointment_date']))) ?></td>
              <td><?= e($appointment['full_name']) ?></td>
              <td><?= e($appointment['doctor_name']) ?></td>
              <td><?= e($appointment['department']) ?></td>
              <td><?= nl2br(e($appointment['reason'])) ?></td>
              <td><?= e($appointment['status']) ?></td>
              <td>
                <div class="actions">
                  <a class="btn btn-light" href="admin_add_record.php?appointment_edit=<?= (int) $appointment['id'] ?>">Sửa</a>
                  <form method="post" class="inline-form" onsubmit="return confirm('Xóa lịch hẹn này?');">
                    <?php render_form_guard('admin_manage_appointments'); ?>
                    <input type="hidden" name="action" value="delete_appointment">
                    <input type="hidden" name="appointment_id" value="<?= (int) $appointment['id'] ?>">
                    <button type="submit" class="danger-btn">Xóa</button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </section>
  <?php endif; ?>
</div>
<?php render_footer(); ?>
