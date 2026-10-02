<?php
declare(strict_types=1);

require_once 'config.php';
require_patient_login();

if (!appointments_enabled()) {
    set_flash('error', 'Tính năng đặt lịch khám hiện đang được tạm ẩn.');
    redirect('dashboard.php');
}

$userId = (int) ($_SESSION['user_id'] ?? 0);
$editingAppointmentId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
$editingAppointment = null;

if ($editingAppointmentId > 0) {
    $editingAppointment = find_patient_appointment($conn, $editingAppointmentId, $userId);
    if (!$editingAppointment) {
        set_flash('error', 'Không tìm thấy lịch hẹn cần sửa.');
        redirect('dashboard.php');
    }

    if (!appointment_is_editable($editingAppointment)) {
        set_flash('error', 'Lịch hẹn này không còn được phép chỉnh sửa.');
        redirect('dashboard.php');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $guardError = validate_form_guard('book_appointment', 6, 300, 0, false);
    $appointmentId = (int) ($_POST['appointment_id'] ?? 0);
    $doctorId = (int) ($_POST['doctor_id'] ?? 0);
    $dateValue = trim($_POST['date'] ?? '');
    $reason = trim($_POST['reason'] ?? '');

    if ($guardError !== null) {
        set_flash('error', $guardError);
    } elseif ($doctorId <= 0 || $dateValue === '' || $reason === '') {
        set_flash('error', 'Vui lòng nhập đầy đủ thông tin lịch khám.');
    } else {
        $appointmentDate = normalize_appointment_datetime($dateValue);

        if ($appointmentDate === null) {
            set_flash('error', 'Thời gian hẹn không hợp lệ.');
        } elseif (strtotime($appointmentDate) < time()) {
            set_flash('error', 'Không thể đặt lịch trong quá khứ.');
        } else {
            $doctor = find_doctor_by_id($conn, $doctorId);
            if (!$doctor) {
                set_flash('error', 'Bác sĩ không tồn tại.');
            } elseif ($appointmentId > 0) {
                $appointment = find_patient_appointment($conn, $appointmentId, $userId);
                if (!$appointment) {
                    set_flash('error', 'Không tìm thấy lịch hẹn cần cập nhật.');
                } elseif (!appointment_is_editable($appointment)) {
                    set_flash('error', 'Lịch hẹn này không còn được phép chỉnh sửa.');
                } else {
                    update_appointment($conn, $appointmentId, $doctorId, $appointmentDate, $reason);
                    set_flash('success', 'Đã cập nhật lịch hẹn.');
                    redirect('dashboard.php');
                }
            } else {
                create_patient_appointment($conn, $userId, $doctorId, $appointmentDate, $reason);
                set_flash('success', 'Đặt lịch thành công.');
                redirect('dashboard.php');
            }
        }
    }
}

$doctors = $conn->query('SELECT id, name, title, department FROM doctors ORDER BY name ASC');
$formAction = $editingAppointment ? 'Cập nhật lịch hẹn' : 'Xác nhận đặt lịch';
$pageTitle = $editingAppointment ? 'Sửa lịch khám' : 'Đặt lịch khám';
$selectedDoctorId = (string) ($editingAppointment['doctor_id'] ?? ($_POST['doctor_id'] ?? ''));
$selectedDate = (string) ($_POST['date'] ?? ($editingAppointment ? date('Y-m-d\TH:i', strtotime((string) $editingAppointment['appointment_date'])) : ''));
$selectedReason = (string) ($_POST['reason'] ?? ($editingAppointment['reason'] ?? ''));

render_header($pageTitle);
?>
<div class="card" style="max-width:700px;margin:40px auto;">
  <div class="panel-title">
    <div>
      <h1><?= e($pageTitle) ?></h1>
      <p class="muted">
        <?= $editingAppointment ? 'Cập nhật lại bác sĩ, thời gian hoặc lý do khám cho lịch hẹn đã tạo.' : 'Chọn bác sĩ và thời gian phù hợp để tạo lịch hẹn. Biểu mẫu này không còn hiển thị mã xác minh.' ?>
      </p>
    </div>
    <div class="actions">
      <a class="btn btn-secondary" href="dashboard.php">Quay lại</a>
    </div>
  </div>
  <?php render_flash(); ?>
  <form method="post" class="grid">
    <?php render_form_guard('book_appointment'); ?>
    <input type="hidden" name="appointment_id" value="<?= (int) ($editingAppointment['id'] ?? 0) ?>">
    <div>
      <label for="doctor_id">Bác sĩ</label>
      <select id="doctor_id" name="doctor_id" required>
        <option value="">Chọn bác sĩ</option>
        <?php while ($doctor = $doctors->fetch_assoc()): ?>
          <option value="<?= (int) $doctor['id'] ?>" <?= (string) $doctor['id'] === $selectedDoctorId ? 'selected' : '' ?>>
            <?= e($doctor['name']) ?><?= !empty($doctor['title']) ? ' (' . e($doctor['title']) . ')' : '' ?> - <?= e($doctor['department']) ?>
          </option>
        <?php endwhile; ?>
      </select>
    </div>
    <div>
      <label for="date">Ngày giờ khám</label>
      <input id="date" type="datetime-local" name="date" value="<?= e($selectedDate) ?>" required>
    </div>
    <div>
      <label for="reason">Lý do khám</label>
      <textarea id="reason" name="reason" required><?= e($selectedReason) ?></textarea>
    </div>
    <div class="actions">
      <button type="submit"><?= e($formAction) ?></button>
      <?php if ($editingAppointment): ?>
        <a class="btn btn-secondary" href="book_appointment.php">Tạo lịch mới</a>
      <?php endif; ?>
    </div>
  </form>
</div>
<?php render_footer(); ?>
