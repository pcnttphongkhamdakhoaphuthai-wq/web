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
<style>
/* ========================================================
   ADMIN ADD RECORD & APPOINTMENT MANAGEMENT - PREMIUM STYLING
   ======================================================== */
.record-mgmt-wrap {
  max-width: 1180px;
  margin: 0 auto 40px;
  padding: 0 20px;
}

.record-header-banner {
  background: #ffffff;
  border: 1.5px solid #e2e8f0;
  border-radius: 16px;
  padding: 20px 24px;
  margin: 28px 0 20px;
  box-shadow: 0 4px 16px rgba(8, 45, 86, 0.04);
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 16px;
}

.record-header-banner h1 {
  font-size: 24px;
  font-weight: 700;
  color: #0f172a;
  margin: 0 0 6px;
  display: flex;
  align-items: center;
  gap: 10px;
}

.record-header-banner p {
  margin: 0;
  font-size: 13.5px;
  color: #64748b;
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}

.badge-scope {
  display: inline-flex;
  align-items: center;
  padding: 3px 10px;
  border-radius: 8px;
  font-size: 12px;
  font-weight: 600;
}
.badge-all {
  background: #eff6ff;
  color: #1d4ed8;
  border: 1px solid #bfdbfe;
}
.badge-dept {
  background: #f0fdf4;
  color: #15803d;
  border: 1px solid #bbf7d0;
}

/* Card Container */
.record-card {
  background: #ffffff;
  border: 1.5px solid #e2e8f0;
  border-radius: 16px;
  padding: 24px;
  box-shadow: 0 4px 20px rgba(8, 45, 86, 0.05);
  margin-bottom: 24px;
  position: relative;
}

.record-card-title {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 20px;
  padding-bottom: 14px;
  border-bottom: 1.5px solid #f1f5f9;
}

.record-card-title h2 {
  font-size: 18px;
  font-weight: 700;
  color: #0f172a;
  margin: 0;
  display: flex;
  align-items: center;
  gap: 8px;
}

/* Standardized Form Inputs: 1.5px soft border, 12px radius, 46px height */
.record-mgmt-wrap input[type="text"],
.record-mgmt-wrap input[type="date"],
.record-mgmt-wrap input[type="datetime-local"],
.record-mgmt-wrap select,
#search_patient_input,
#visit_date,
#appointment_date,
#patient_id,
#doctor_id,
#appointment_doctor_id,
#appointment_status {
  display: block;
  width: 100%;
  height: 46px !important;
  line-height: 46px;
  padding: 0 14px;
  font-family: inherit;
  font-size: 14px;
  color: #1e293b;
  background-color: #ffffff;
  border: 1.5px solid #cbd5e1 !important;
  border-radius: 12px !important;
  box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
  box-sizing: border-box;
  outline: none !important;
  transition: border-color 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease;
}

/* Standardized File Input: 1.5px border, 12px radius, 46px height */
.record-mgmt-wrap input[type="file"],
#pdf {
  display: block;
  width: 100%;
  height: 46px !important;
  line-height: 32px;
  padding: 6px 12px;
  font-family: inherit;
  font-size: 13.5px;
  color: #334155;
  background-color: #f8fafc;
  border: 1.5px dashed #93c5fd !important;
  border-radius: 12px !important;
  box-sizing: border-box;
  outline: none !important;
  cursor: pointer;
  transition: all 0.2s ease;
}

.record-mgmt-wrap input[type="file"]::-webkit-file-upload-button,
#pdf::-webkit-file-upload-button {
  background: #e0f2fe;
  color: #0284c7;
  border: 1px solid #bae6fd;
  border-radius: 8px;
  padding: 5px 14px;
  font-weight: 600;
  font-size: 13px;
  cursor: pointer;
  margin-right: 12px;
  transition: all 0.15s ease;
}

.record-mgmt-wrap input[type="file"]::-webkit-file-upload-button:hover,
#pdf::-webkit-file-upload-button:hover {
  background: #0284c7;
  color: #ffffff;
}

/* Date and Datetime Pickers Styling */
.record-mgmt-wrap input[type="date"]::-webkit-calendar-picker-indicator,
.record-mgmt-wrap input[type="datetime-local"]::-webkit-calendar-picker-indicator,
#visit_date::-webkit-calendar-picker-indicator,
#appointment_date::-webkit-calendar-picker-indicator {
  cursor: pointer;
  opacity: 0.65;
  padding: 4px;
  transition: opacity 0.2s;
}
.record-mgmt-wrap input[type="date"]::-webkit-calendar-picker-indicator:hover,
.record-mgmt-wrap input[type="datetime-local"]::-webkit-calendar-picker-indicator:hover,
#visit_date::-webkit-calendar-picker-indicator:hover,
#appointment_date::-webkit-calendar-picker-indicator:hover {
  opacity: 1;
}

/* Standardized Textarea: 1.5px soft border, 12px radius */
.record-mgmt-wrap textarea,
#diagnosis,
#prescription,
#appointment_reason {
  display: block;
  width: 100%;
  padding: 12px 14px;
  font-family: inherit;
  font-size: 14px;
  line-height: 1.55;
  color: #1e293b;
  background-color: #ffffff;
  border: 1.5px solid #cbd5e1 !important;
  border-radius: 12px !important;
  box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
  box-sizing: border-box;
  outline: none !important;
  resize: vertical;
  min-height: 90px;
  transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

/* Hover and Focus States for soft luxury feel - eliminate coarse black borders */
.record-mgmt-wrap input:hover,
.record-mgmt-wrap select:hover,
.record-mgmt-wrap textarea:hover,
#search_patient_input:hover,
#visit_date:hover,
#pdf:hover,
#appointment_date:hover,
#patient_id:hover,
#doctor_id:hover {
  border-color: #93c5fd !important;
}

.record-mgmt-wrap input:focus,
.record-mgmt-wrap select:focus,
.record-mgmt-wrap textarea:focus,
#search_patient_input:focus,
#visit_date:focus,
#pdf:focus,
#appointment_date:focus,
#patient_id:focus,
#doctor_id:focus,
#appointment_doctor_id:focus,
#appointment_status:focus,
#appointment_reason:focus,
#diagnosis:focus,
#prescription:focus {
  border-color: #0284c7 !important;
  background-color: #ffffff !important;
  box-shadow: 0 0 0 3.5px rgba(2, 132, 199, 0.16) !important;
  outline: none !important;
}

/* Form Groups & Labels */
.field-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
  margin-bottom: 16px;
}

.field-group label,
.record-mgmt-wrap label {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 13.5px;
  font-weight: 600;
  color: #1e3a8a;
  margin-bottom: 2px;
}

.field-group label .lbl-icon {
  font-size: 15px;
  color: #0284c7;
}

.field-group label .req-star {
  color: #ef4444;
  font-weight: 700;
  margin-left: 2px;
}

.field-group label .lbl-hint {
  font-size: 12px;
  font-weight: normal;
  color: #64748b;
  margin-left: auto;
}

/* Search Patient Input with icon wrapper */
.search-patient-wrapper {
  position: relative;
}
.search-patient-wrapper input {
  padding-left: 40px !important;
}
.search-patient-wrapper .search-badge-icon {
  position: absolute;
  left: 14px;
  top: 50%;
  transform: translateY(-50%);
  color: #94a3b8;
  font-size: 15px;
  pointer-events: none;
}

/* Checkbox Cards */
.checkbox-option-card {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 16px;
  background: #f8fafc;
  border: 1.5px solid #e2e8f0;
  border-radius: 12px;
  cursor: pointer;
  transition: all 0.15s ease;
  margin-top: 4px;
}
.checkbox-option-card:hover {
  background: #f1f5f9;
  border-color: #cbd5e1;
}
.checkbox-option-card input[type="checkbox"] {
  width: 18px !important;
  height: 18px !important;
  border-radius: 4px;
  cursor: pointer;
  accent-color: #0284c7;
  margin: 0 !important;
  flex-shrink: 0;
}
.checkbox-option-card .cb-text-title {
  font-size: 13.5px;
  font-weight: 600;
  color: #0f172a;
}
.checkbox-option-card .cb-text-sub {
  font-size: 12px;
  color: #64748b;
  font-weight: 400;
}

/* High-End Action Buttons */
.btn-submit-premium {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  height: 46px;
  padding: 0 26px;
  background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%) !important;
  color: #ffffff !important;
  font-family: inherit;
  font-size: 14.5px;
  font-weight: 600;
  border-radius: 12px;
  border: none !important;
  cursor: pointer;
  box-shadow: 0 4px 14px rgba(2, 132, 199, 0.28);
  transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
  text-decoration: none !important;
  white-space: nowrap;
}
.btn-submit-premium:hover {
  background: linear-gradient(135deg, #0369a1 0%, #075985 100%) !important;
  transform: translateY(-1.5px);
  box-shadow: 0 6px 20px rgba(2, 132, 199, 0.38);
}
.btn-submit-premium:active {
  transform: translateY(0);
}

.btn-secondary-premium {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  height: 44px;
  padding: 0 20px;
  background: #ffffff;
  color: #334155 !important;
  border: 1.5px solid #cbd5e1 !important;
  border-radius: 12px;
  font-family: inherit;
  font-size: 14px;
  font-weight: 600;
  text-decoration: none !important;
  cursor: pointer;
  transition: all 0.15s ease;
  white-space: nowrap;
}
.btn-secondary-premium:hover {
  background: #f8fafc;
  border-color: #94a3b8 !important;
  color: #0f172a !important;
}

/* Modern Tables & Badges */
.table-responsive {
  overflow-x: auto;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
  background: #ffffff;
  margin-top: 10px;
}
.table-modern {
  width: 100%;
  border-collapse: collapse;
  font-size: 13.5px;
}
.table-modern th {
  background: #f8fafc;
  padding: 12px 14px;
  font-weight: 600;
  color: #475569;
  text-align: left;
  border-bottom: 1.5px solid #e2e8f0;
  font-size: 13px;
  white-space: nowrap;
}
.table-modern td {
  padding: 12px 14px;
  border-bottom: 1px solid #f1f5f9;
  color: #1e293b;
  vertical-align: middle;
}
.table-modern tr:last-child td {
  border-bottom: none;
}
.table-modern tr:hover td {
  background: #f8fafc;
}

/* Table Badges & Action Buttons */
.badge-date {
  display: inline-block;
  padding: 3px 8px;
  border-radius: 6px;
  background: #f1f5f9;
  color: #334155;
  font-weight: 600;
  font-size: 12px;
  white-space: nowrap;
}
.badge-dept-tag {
  display: inline-block;
  padding: 3px 8px;
  border-radius: 6px;
  background: #e0f2fe;
  color: #0369a1;
  font-weight: 600;
  font-size: 12px;
  white-space: nowrap;
}
.status-badge {
  display: inline-block;
  padding: 3px 9px;
  border-radius: 8px;
  font-size: 12px;
  font-weight: 600;
  white-space: nowrap;
}
.status-success { background: #dcfce7; color: #15803d; }
.status-info { background: #e0f2fe; color: #0369a1; }
.status-warning { background: #fef9c3; color: #854d0e; }
.status-danger { background: #fee2e2; color: #b91c1c; }

.row-actions-group {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
}
.btn-tbl-edit {
  padding: 5px 12px;
  border-radius: 8px;
  border: 1px solid #cbd5e1;
  background: #ffffff;
  color: #0284c7;
  font-size: 12.5px;
  font-weight: 600;
  text-decoration: none;
  cursor: pointer;
  transition: all 0.15s ease;
}
.btn-tbl-edit:hover {
  background: #e0f2fe;
  border-color: #7dd3fc;
}
.btn-tbl-delete {
  padding: 5px 12px;
  border-radius: 8px;
  border: 1px solid #fecaca;
  background: #fef2f2;
  color: #dc2626;
  font-size: 12.5px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.15s ease;
}
.btn-tbl-delete:hover {
  background: #fee2e2;
  border-color: #fca5a5;
}
.pdf-pill-link {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 3px 8px;
  border-radius: 6px;
  background: #f0fdf4;
  color: #16a34a;
  border: 1px solid #bbf7d0;
  font-size: 12px;
  font-weight: 600;
  text-decoration: none;
  transition: all 0.15s;
}
.pdf-pill-link:hover {
  background: #dcfce7;
  color: #15803d;
}

.empty-state-box {
  text-align: center;
  padding: 36px 20px;
  color: #64748b;
}

@media (max-width: 900px) {
  .record-mgmt-wrap {
    padding: 0 14px;
  }
  .record-header-banner {
    padding: 16px;
  }
  .record-card {
    padding: 18px 14px;
  }
}
</style>

<div class="record-header-banner record-mgmt-wrap" style="margin-top:28px;margin-bottom:16px;">
  <div>
    <h1><span>🩺</span> Quản lý hồ sơ & đợt khám bệnh</h1>
    <p>
      Xin chào <strong><?= e($_SESSION['admin_full_name'] ?? ($_SESSION['admin_username'] ?? 'admin')) ?></strong>.
      <?= $canManageAllRecords ? '<span class="badge-scope badge-all">Toàn quyền xử lý kết quả cho mọi bộ phận</span>' : '<span class="badge-scope badge-dept">Phạm vi bộ phận: ' . e($adminDepartment) . '</span>' ?>
    </p>
  </div>
</div>

<?php require 'admin_tabs_nav.php'; ?>

<div data-admin-support-endpoint="admin_support_notice.php" data-admin-support-csrf="<?= e(csrf_token('admin_support_api')) ?>" data-admin-chat-link="<?= e((is_root_admin() || admin_can('manage_support_chat')) ? 'admin_accounts.php#recent-chats' : '') ?>"></div>

<?php if (!empty($supportChatNotice['has_new'])): ?>
  <div class="admin-alert-toast record-mgmt-wrap">
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
              <button type="submit" class="btn-submit-premium">Gửi trả lời</button>
            </div>
          </form>
        </div>
      <?php endforeach; ?>
    </div>
    <form method="post" class="actions">
      <?php render_form_guard('admin_support_notice'); ?>
      <input type="hidden" name="action" value="dismiss_support_chat_notice">
      <input type="hidden" name="latest_chat_id" value="<?= (int) $supportChatNotice['latest_chat_id'] ?>">
      <button type="submit" class="btn-submit-premium">Đã xem</button>
      <?php if (is_root_admin() || admin_can('manage_support_chat')): ?>
        <a class="btn-secondary-premium" href="admin_accounts.php#recent-chats">Mở nhật ký chat</a>
      <?php endif; ?>
    </form>
  </div>
<?php endif; ?>

<div class="record-mgmt-wrap">
  <?php render_flash(); ?>

  <?php if ($activeTab === 'records'): ?>
  <div class="grid grid-2" style="align-items:start;gap:24px;">
    <section class="card record-card">
      <div class="record-card-title">
        <div>
          <h2><span><?= $editingRecord ? '✏️' : '📋' ?></span> <?= e($formTitle) ?></h2>
          <?php if ($editingRecord): ?>
            <p class="muted" style="margin:4px 0 0;font-size:13px;">Đang cập nhật hồ sơ ID #<?= (int) $editingRecord['id'] ?>. Bạn có thể thay đổi kết quả hoặc tệp PDF đính kèm.</p>
          <?php endif; ?>
        </div>
        <?php if ($editingRecord): ?>
          <div class="actions" style="margin:0;">
            <a class="btn-secondary-premium" href="admin_add_record.php">➕ Tạo mới</a>
          </div>
        <?php endif; ?>
      </div>
      <form method="post" enctype="multipart/form-data" class="grid" style="gap:14px;">
        <?php render_form_guard('admin_add_record'); ?>
        <input type="hidden" name="action" value="<?= e($formAction) ?>">
        <input type="hidden" name="record_id" value="<?= (int) ($editingRecord['id'] ?? 0) ?>">

        <div class="field-group">
          <label for="search_patient_input">
            <span class="lbl-icon">🔍</span>
            <span>Tìm kiếm bệnh nhân</span>
            <span class="lbl-hint">Lọc danh sách tự động theo CCCD hoặc Tên</span>
          </label>
          <div class="search-patient-wrapper">
            <span class="search-badge-icon">🔎</span>
            <input type="text" id="search_patient_input" placeholder="Nhập số CCCD hoặc họ tên để lọc nhanh..." autocomplete="off">
          </div>
        </div>

        <div class="field-group">
          <label for="patient_id">
            <span class="lbl-icon">👤</span>
            <span>Chọn bệnh nhân</span>
            <span class="req-star">*</span>
          </label>
          <select id="patient_id" name="patient_id" required>
            <option value="">-- Chọn bệnh nhân --</option>
            <?php while ($patient = $patients->fetch_assoc()): ?>
              <option value="<?= (int) $patient['id'] ?>" <?= (string) $patient['id'] === (string) ($editingRecord['patient_id'] ?? ($_POST['patient_id'] ?? '')) ? 'selected' : '' ?>>
                <?= e($patient['full_name']) ?> - CCCD: <?= e($patient['cccd']) ?>
              </option>
            <?php endwhile; ?>
          </select>
        </div>

        <div class="field-group">
          <label for="doctor_id">
            <span class="lbl-icon">👨‍⚕️</span>
            <span>Bác sĩ phụ trách / Khoa phòng</span>
            <span class="req-star">*</span>
          </label>
          <select id="doctor_id" name="doctor_id" required>
            <option value="">-- Chọn bác sĩ khám --</option>
            <?php while ($doctor = $doctors->fetch_assoc()): ?>
              <option value="<?= (int) $doctor['id'] ?>" <?= (string) $doctor['id'] === (string) ($editingRecord['doctor_id'] ?? ($_POST['doctor_id'] ?? '')) ? 'selected' : '' ?>>
                <?= e($doctor['name']) ?> (Khoa <?= e($doctor['department']) ?>)
              </option>
            <?php endwhile; ?>
          </select>
        </div>

        <div class="field-group">
          <label for="visit_date">
            <span class="lbl-icon">📅</span>
            <span>Ngày khám bệnh</span>
            <span class="req-star">*</span>
          </label>
          <input id="visit_date" type="date" name="visit_date" value="<?= e((string) ($editingRecord['visit_date'] ?? ($_POST['visit_date'] ?? date('Y-m-d')))) ?>" required>
        </div>

        <div class="field-group">
          <label for="diagnosis">
            <span class="lbl-icon">🩺</span>
            <span>Kết quả khám / Chẩn đoán bệnh</span>
            <span class="req-star">*</span>
          </label>
          <textarea id="diagnosis" name="diagnosis" placeholder="Nhập kết quả khám lâm sàng, chẩn đoán bệnh..." rows="3" required><?= e((string) ($editingRecord['diagnosis'] ?? ($_POST['diagnosis'] ?? ''))) ?></textarea>
        </div>

        <div class="field-group">
          <label for="prescription">
            <span class="lbl-icon">💊</span>
            <span>Ghi chú điều trị / Đơn thuốc</span>
            <span class="lbl-hint">Không bắt buộc</span>
          </label>
          <textarea id="prescription" name="prescription" placeholder="Nhập tên thuốc, liều lượng dùng hoặc ghi chú dặn dò..." rows="3"><?= e((string) ($editingRecord['prescription'] ?? ($_POST['prescription'] ?? ''))) ?></textarea>
        </div>

        <div class="field-group">
          <label for="pdf">
            <span class="lbl-icon">📄</span>
            <span>Tệp kết quả PDF đính kèm</span>
            <span class="lbl-hint">Định dạng .pdf, dung lượng tối đa 5MB</span>
          </label>
          <input id="pdf" type="file" name="pdf" accept="application/pdf">
          <?php if (!empty($editingRecord['result_file'])): ?>
            <div style="margin-top:10px;padding:10px 14px;background:#f0fdf4;border:1.5px solid #bbf7d0;border-radius:12px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;">
              <div style="font-size:13px;color:#166534;display:flex;align-items:center;gap:6px;">
                <span>📄</span> <strong>Đang có PDF kết quả</strong>
                <a class="pdf-pill-link" href="<?= e(result_download_url((int) $editingRecord['id'])) ?>" target="_blank" rel="noopener">Xem tệp hiện tại</a>
              </div>
              <label style="display:inline-flex;align-items:center;gap:6px;font-size:12.5px;color:#dc2626;cursor:pointer;font-weight:600;margin:0;">
                <input type="checkbox" name="remove_existing_file" style="width:16px;height:16px;accent-color:#dc2626;"> Xóa PDF hiện tại
              </label>
            </div>
          <?php endif; ?>
        </div>

        <div class="field-group" style="margin-top:2px;">
          <label class="checkbox-option-card">
            <input type="checkbox" name="send_email" value="1" checked>
            <div>
              <div class="cb-text-title">📧 Gửi kết quả qua Email cho bệnh nhân</div>
              <div class="cb-text-sub">Tự động gửi thông báo kết quả và tệp đính kèm nếu hồ sơ bệnh nhân có địa chỉ email</div>
            </div>
          </label>
        </div>

        <div class="actions" style="margin-top:16px;">
          <button type="submit" class="btn-submit-premium">
            <span><?= $editingRecord ? '💾' : '✨' ?></span>
            <span><?= $editingRecord ? 'Cập nhật hồ sơ khám' : 'Lưu & Trả kết quả' ?></span>
          </button>
          <?php if ($editingRecord): ?>
            <a class="btn-secondary-premium" href="admin_add_record.php">Hủy bỏ</a>
          <?php endif; ?>
        </div>
      </form>
    </section>

    <section class="card record-card">
      <div class="record-card-title">
        <div>
          <h2><span>📁</span> Kết quả khám gần đây</h2>
          <p class="muted" style="margin:4px 0 0;font-size:13px;">Danh sách 10 đợt khám mới nhất trong phạm vi phân quyền của bạn.</p>
        </div>
      </div>
      <?php if ($recentRecords->num_rows === 0): ?>
        <div class="empty-state-box">
          <div style="font-size:36px;margin-bottom:8px;">📭</div>
          <p>Chưa có hồ sơ khám bệnh nào được ghi nhận.</p>
        </div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table-modern">
            <thead>
              <tr>
                <th style="min-width:95px;">Ngày khám</th>
                <th style="min-width:130px;">Bệnh nhân</th>
                <th style="min-width:120px;">Bác sĩ</th>
                <th style="min-width:100px;">Bộ phận</th>
                <th style="min-width:160px;">Chẩn đoán</th>
                <th style="min-width:110px;text-align:center;">Thao tác</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($record = $recentRecords->fetch_assoc()): ?>
                <tr>
                  <td><span class="badge-date"><?= e(date('d/m/Y', strtotime($record['visit_date']))) ?></span></td>
                  <td><strong style="color:#0f172a;"><?= e($record['full_name']) ?></strong></td>
                  <td><?= e($record['doctor_name']) ?></td>
                  <td><span class="badge-dept-tag"><?= e($record['department']) ?></span></td>
                  <td>
                    <div style="font-size:13px;line-height:1.45;max-height:60px;overflow:hidden;text-overflow:ellipsis;">
                      <?= nl2br(e($record['diagnosis'])) ?>
                    </div>
                  </td>
                  <td style="text-align:center;">
                    <div class="row-actions-group">
                      <a class="btn-tbl-edit" href="admin_add_record.php?edit=<?= (int) $record['id'] ?>" title="Chỉnh sửa hồ sơ">Sửa</a>
                      <form method="post" class="inline-form" onsubmit="return confirm('Bạn có chắc chắn muốn xóa hồ sơ khám này?');">
                        <?php render_form_guard('admin_add_record'); ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="record_id" value="<?= (int) $record['id'] ?>">
                        <button type="submit" class="btn-tbl-delete" title="Xóa hồ sơ">Xóa</button>
                      </form>
                    </div>
                    <?php if (!empty($record['result_file'])): ?>
                      <div style="margin-top:6px;">
                        <a class="pdf-pill-link" href="<?= e(result_download_url((int) $record['id'])) ?>" target="_blank" rel="noopener">
                          <span>📄</span> Xem PDF
                        </a>
                      </div>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </section>
  </div>
  <?php endif; ?>

  <?php if ($activeTab === 'appointments'): ?>
  <section class="card record-card">
    <div class="record-card-title">
      <div>
        <h2><span>📅</span> Quản lý lịch hẹn khám bệnh</h2>
        <p class="muted" style="margin:4px 0 0;font-size:13px;">Nhân viên có thể điều chỉnh bác sĩ phụ trách, ngày giờ khám hoặc trạng thái đợt khám của bệnh nhân.</p>
      </div>
      <?php if ($editingAppointment): ?>
        <div class="actions" style="margin:0;">
          <a class="btn-secondary-premium" href="admin_add_record.php?tab=appointments">✖ Thoát sửa lịch</a>
        </div>
      <?php endif; ?>
    </div>

    <?php if ($editingAppointment): ?>
      <form method="post" style="background:#f8fafc;padding:22px;border:1.5px solid #e2e8f0;border-radius:14px;margin-bottom:28px;">
        <?php render_form_guard('admin_manage_appointments'); ?>
        <input type="hidden" name="action" value="update_appointment">
        <input type="hidden" name="appointment_id" value="<?= (int) $editingAppointment['id'] ?>">
        
        <div style="font-weight:700;font-size:15px;color:#0f172a;margin-bottom:18px;display:flex;align-items:center;gap:8px;">
          <span>✏️</span> Chỉnh sửa thông tin lịch hẹn ID #<?= (int) $editingAppointment['id'] ?>
        </div>

        <div class="grid grid-2" style="gap:16px;">
          <div class="field-group">
            <label for="appointment_doctor_id">
              <span class="lbl-icon">👨‍⚕️</span>
              <span>Bác sĩ phụ trách</span>
              <span class="req-star">*</span>
            </label>
            <select id="appointment_doctor_id" name="appointment_doctor_id" required>
              <option value="">-- Chọn bác sĩ --</option>
              <?php while ($doctor = $appointmentDoctors->fetch_assoc()): ?>
                <option value="<?= (int) $doctor['id'] ?>" <?= (string) $doctor['id'] === (string) $editingAppointment['doctor_id'] ? 'selected' : '' ?>>
                  <?= e($doctor['name']) ?> (Khoa <?= e($doctor['department']) ?>)
                </option>
              <?php endwhile; ?>
            </select>
          </div>

          <div class="field-group">
            <label for="appointment_date">
              <span class="lbl-icon">🕒</span>
              <span>Ngày & Giờ khám hẹn</span>
              <span class="req-star">*</span>
            </label>
            <input id="appointment_date" type="datetime-local" name="appointment_date" value="<?= e(date('Y-m-d\TH:i', strtotime((string) $editingAppointment['appointment_date']))) ?>" required>
          </div>

          <div class="field-group">
            <label for="appointment_status">
              <span class="lbl-icon">🏷️</span>
              <span>Trạng thái lịch khám</span>
              <span class="req-star">*</span>
            </label>
            <select id="appointment_status" name="appointment_status" required>
              <?php foreach ($appointmentStatuses as $status): ?>
                <option value="<?= e($status) ?>" <?= $status === (string) $editingAppointment['status'] ? 'selected' : '' ?>><?= e($status) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="field-group">
            <label for="appointment_reason">
              <span class="lbl-icon">📝</span>
              <span>Lý do khám bệnh</span>
              <span class="req-star">*</span>
            </label>
            <textarea id="appointment_reason" name="appointment_reason" placeholder="Nhập lý do hoặc triệu chứng ban đầu..." rows="2" required><?= e((string) $editingAppointment['reason']) ?></textarea>
          </div>
        </div>

        <div class="actions" style="margin-top:16px;">
          <button type="submit" class="btn-submit-premium">
            <span>💾</span> Cập nhật lịch hẹn
          </button>
          <a class="btn-secondary-premium" href="admin_add_record.php?tab=appointments">Hủy bỏ</a>
        </div>
      </form>
    <?php endif; ?>

    <?php if ($recentAppointments->num_rows === 0): ?>
      <div class="empty-state-box">
        <div style="font-size:36px;margin-bottom:8px;">📅</div>
        <p>Chưa có lịch hẹn nào trong danh sách quản lý.</p>
      </div>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table-modern">
          <thead>
            <tr>
              <th style="min-width:130px;">Thời gian</th>
              <th style="min-width:130px;">Bệnh nhân</th>
              <th style="min-width:120px;">Bác sĩ</th>
              <th style="min-width:100px;">Bộ phận</th>
              <th style="min-width:160px;">Lý do khám</th>
              <th style="min-width:110px;">Trạng thái</th>
              <th style="min-width:110px;text-align:center;">Thao tác</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($appointment = $recentAppointments->fetch_assoc()): ?>
              <tr>
                <td><span class="badge-date"><?= e(date('d/m/Y H:i', strtotime($appointment['appointment_date']))) ?></span></td>
                <td><strong style="color:#0f172a;"><?= e($appointment['full_name']) ?></strong></td>
                <td><?= e($appointment['doctor_name']) ?></td>
                <td><span class="badge-dept-tag"><?= e($appointment['department']) ?></span></td>
                <td>
                  <div style="font-size:13px;line-height:1.45;">
                    <?= nl2br(e($appointment['reason'])) ?>
                  </div>
                </td>
                <td>
                  <?php
                    $st = (string) $appointment['status'];
                    $stClass = 'status-default';
                    if ($st === 'Đã khám') $stClass = 'status-success';
                    elseif ($st === 'Đã xác nhận') $stClass = 'status-info';
                    elseif ($st === 'Chờ khám') $stClass = 'status-warning';
                    elseif ($st === 'Đã hủy') $stClass = 'status-danger';
                  ?>
                  <span class="status-badge <?= $stClass ?>"><?= e($st) ?></span>
                </td>
                <td style="text-align:center;">
                  <div class="row-actions-group">
                    <a class="btn-tbl-edit" href="admin_add_record.php?tab=appointments&appointment_edit=<?= (int) $appointment['id'] ?>" title="Chỉnh sửa lịch hẹn">Sửa</a>
                    <form method="post" class="inline-form" onsubmit="return confirm('Bạn có chắc chắn muốn xóa lịch hẹn này?');">
                      <?php render_form_guard('admin_manage_appointments'); ?>
                      <input type="hidden" name="action" value="delete_appointment">
                      <input type="hidden" name="appointment_id" value="<?= (int) $appointment['id'] ?>">
                      <button type="submit" class="btn-tbl-delete" title="Xóa lịch hẹn">Xóa</button>
                    </form>
                  </div>
                </td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </section>
  <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const searchInput = document.getElementById('search_patient_input');
  const patientSelect = document.getElementById('patient_id');
  if (!searchInput || !patientSelect) return;

  const originalOptions = Array.from(patientSelect.options);

  searchInput.addEventListener('input', function () {
    const q = this.value.trim().toLowerCase();
    const currentVal = patientSelect.value;

    patientSelect.innerHTML = '';

    if (originalOptions.length > 0 && originalOptions[0].value === '') {
      patientSelect.appendChild(originalOptions[0].cloneNode(true));
    }

    let matchCount = 0;
    let retained = false;

    for (let i = 1; i < originalOptions.length; i++) {
      const opt = originalOptions[i];
      const text = opt.textContent.toLowerCase();
      if (!q || text.includes(q)) {
        const clone = opt.cloneNode(true);
        if (clone.value === currentVal) {
          clone.selected = true;
          retained = true;
        }
        patientSelect.appendChild(clone);
        matchCount++;
      }
    }

    if (q && matchCount === 1 && !retained) {
      patientSelect.selectedIndex = 1;
    }
  });
});
</script>
<?php render_footer(); ?>
