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
            } else {
                $slotError = validate_appointment_slot($conn, $doctorId, $userId, $appointmentDate, $appointmentId);
                if ($slotError !== null) {
                    set_flash('error', $slotError);
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
}

$doctors = $conn->query('SELECT id, name, title, department FROM doctors ORDER BY name ASC');
$formAction = $editingAppointment ? 'Cập nhật lịch hẹn' : 'Xác nhận đặt lịch';
$pageTitle = $editingAppointment ? 'Sửa lịch khám' : 'Đặt lịch khám';
$selectedDoctorId = (string) ($_POST['doctor_id'] ?? ($editingAppointment['doctor_id'] ?? ''));
$selectedDate = (string) ($_POST['date'] ?? ($editingAppointment ? date('Y-m-d\TH:i', strtotime((string) $editingAppointment['appointment_date'])) : ''));
$selectedReason = (string) ($_POST['reason'] ?? ($editingAppointment['reason'] ?? ''));

render_header($pageTitle);
?>
<style>
  .appointment-card {
    max-width: 720px;
    margin: 36px auto;
    background: #ffffff;
    border-radius: 16px;
    border: 1.5px solid #d4e4f2;
    box-shadow: 0 10px 30px rgba(7, 46, 86, 0.06);
    padding: 32px 36px;
  }
  .appointment-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 24px;
    padding-bottom: 18px;
    border-bottom: 1px solid #e7f0f8;
  }
  .appointment-header-content h1 {
    font-size: 24px;
    font-weight: 700;
    color: #072e56;
    margin: 0 0 6px 0;
    display: flex;
    align-items: center;
    gap: 10px;
  }
  .appointment-header-content p {
    margin: 0;
    font-size: 14.5px;
    line-height: 1.5;
    color: #4e6a86;
  }
  .form-field-group {
    margin-bottom: 20px;
  }
  .form-field-group label {
    display: block;
    font-size: 14px;
    font-weight: 600;
    color: #072e56;
    margin-bottom: 7px;
  }
  .form-field-group .field-hint {
    font-size: 12.5px;
    color: #5d7c99;
    margin-top: 5px;
  }
  /* Chuẩn hóa các trường input, select, textarea đồng bộ */
  #doctor_id,
  #date,
  #reason {
    width: 100%;
    font-family: inherit;
    font-size: 15px;
    color: #072e56;
    background-color: #ffffff;
    border: 1.5px solid #c8dced !important;
    border-radius: 12px !important;
    box-sizing: border-box !important;
    outline: none !important;
    transition: border-color 0.18s ease, box-shadow 0.18s ease;
  }
  #doctor_id {
    height: 46px !important;
    line-height: 46px;
    padding: 0 38px 0 14px;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%23005fa0' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m6 9 6 6 6-6'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 14px center;
    background-size: 16px 16px;
    -webkit-appearance: none;
    appearance: none;
    cursor: pointer;
  }
  #date {
    height: 46px !important;
    min-height: 46px !important;
    line-height: 46px;
    padding: 0 14px !important;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02) inset;
  }
  #date::-webkit-calendar-picker-indicator {
    cursor: pointer;
    opacity: 0.8;
    padding: 4px;
    filter: invert(28%) sepia(87%) saturate(1450%) hue-rotate(185deg) brightness(92%) contrast(102%);
    transition: opacity 0.15s ease, transform 0.15s ease;
  }
  #date::-webkit-calendar-picker-indicator:hover {
    opacity: 1;
    transform: scale(1.08);
  }
  #reason {
    min-height: 110px;
    padding: 12px 14px;
    line-height: 1.5;
    resize: vertical;
  }
  #doctor_id:focus,
  #date:focus,
  #reason:focus {
    border-color: #0077c8 !important;
    box-shadow: 0 0 0 3.5px rgba(0, 119, 200, 0.16) !important;
    outline: none !important;
  }
  /* Chuẩn hóa nút bấm thương hiệu phòng khám Phú Thái */
  .appointment-actions {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-top: 26px;
    flex-wrap: wrap;
  }
  .btn-submit-appointment {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    height: 46px;
    padding: 0 24px;
    background: linear-gradient(110deg, #006eb5 0%, #005a92 100%) !important;
    color: #ffffff !important;
    border: 1px solid #004c7d !important;
    border-radius: 12px !important;
    font-weight: 600 !important;
    font-size: 15px !important;
    font-family: inherit;
    cursor: pointer;
    box-shadow: 0 2px 6px rgba(0, 43, 70, 0.14), 0 5px 14px rgba(0, 95, 160, 0.2) !important;
    transition: all 0.18s ease;
    text-decoration: none !important;
  }
  .btn-submit-appointment:hover {
    background: linear-gradient(110deg, #005e9c 0%, #004d7d 100%) !important;
    box-shadow: 0 4px 10px rgba(0, 43, 70, 0.2), 0 7px 18px rgba(0, 95, 160, 0.28) !important;
    transform: translateY(-1px);
  }
  .btn-back-appointment {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    height: 46px;
    padding: 0 20px;
    background: #e7f5ff !important;
    color: #005fa0 !important;
    border: 1.5px solid #badcf6 !important;
    border-radius: 12px !important;
    font-weight: 600 !important;
    font-size: 14.5px !important;
    font-family: inherit;
    cursor: pointer;
    transition: all 0.18s ease;
    text-decoration: none !important;
  }
  .btn-back-appointment:hover {
    background: #d4ecfd !important;
    border-color: #9eccf0 !important;
    color: #004c80 !important;
    transform: translateY(-1px);
  }
  .btn-header-back {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    height: 38px;
    padding: 0 16px;
    background: #e7f5ff !important;
    color: #005fa0 !important;
    border: 1.5px solid #badcf6 !important;
    border-radius: 12px !important;
    font-weight: 600 !important;
    font-size: 13.5px !important;
    font-family: inherit;
    text-decoration: none !important;
    transition: all 0.18s ease;
    white-space: nowrap;
  }
  .btn-header-back:hover {
    background: #d4ecfd !important;
    border-color: #9eccf0 !important;
    color: #004c80 !important;
    transform: translateY(-1px);
  }
  @media (max-width: 600px) {
    .appointment-card {
      margin: 20px 12px;
      padding: 22px 18px;
    }
    .appointment-header {
      flex-direction: column;
      align-items: flex-start;
      gap: 12px;
    }
    .appointment-actions {
      flex-direction: column;
      align-items: stretch;
    }
    .btn-submit-appointment,
    .btn-back-appointment {
      width: 100%;
    }
  }
</style>

<div class="card appointment-card">
  <div class="panel-title appointment-header">
    <div class="appointment-header-content">
      <h1>
        <svg class="icon" style="width:24px;height:24px;color:#005fa0;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
          <line x1="16" y1="2" x2="16" y2="6"></line>
          <line x1="8" y1="2" x2="8" y2="6"></line>
          <line x1="3" y1="10" x2="21" y2="10"></line>
        </svg>
        <?= e($pageTitle) ?>
      </h1>
      <p class="muted">
        <?= $editingAppointment ? 'Cập nhật lại bác sĩ, thời gian hoặc lý do khám cho lịch hẹn đã tạo.' : 'Chọn bác sĩ và thời gian phù hợp để tạo lịch hẹn. Biểu mẫu này không còn hiển thị mã xác minh.' ?>
      </p>
    </div>
    <div class="actions" style="margin-top:0;">
      <a class="btn-header-back" href="dashboard.php">
        <svg class="icon" style="width:16px;height:16px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
          <polyline points="15 18 9 12 15 6"></polyline>
        </svg>
        Quay lại
      </a>
    </div>
  </div>
  <?php render_flash(); ?>
  <form method="post" class="grid" style="gap:16px;">
    <?php render_form_guard('book_appointment'); ?>
    <input type="hidden" name="appointment_id" value="<?= (int) ($editingAppointment['id'] ?? 0) ?>">
    <div class="form-field-group">
      <label for="doctor_id">Bác sĩ phụ trách khám</label>
      <select id="doctor_id" name="doctor_id" required>
        <option value="">-- Vui lòng chọn bác sĩ --</option>
        <?php while ($doctor = $doctors->fetch_assoc()): ?>
          <option value="<?= (int) $doctor['id'] ?>" <?= (string) $doctor['id'] === $selectedDoctorId ? 'selected' : '' ?>>
            <?= e($doctor['name']) ?><?= !empty($doctor['title']) ? ' (' . e($doctor['title']) . ')' : '' ?> - <?= e($doctor['department']) ?>
          </option>
        <?php endwhile; ?>
      </select>
      <div class="field-hint">Chọn bác sĩ chuyên khoa phù hợp với nhu cầu thăm khám của bạn.</div>
    </div>
    <div class="form-field-group">
      <label for="date">Ngày giờ khám mong muốn</label>
      <input id="date" type="datetime-local" name="date" value="<?= e($selectedDate) ?>" min="<?= date('Y-m-d\TH:i') ?>" required>
      <div class="field-hint">Khung giờ làm việc: 07:30 - 17:30 từ Thứ Hai đến Chủ Nhật.</div>
    </div>
    <div class="form-field-group">
      <label for="reason">Lý do khám & Triệu chứng ban đầu</label>
      <textarea id="reason" name="reason" placeholder="Mô tả cụ thể triệu chứng, tình trạng sức khỏe hoặc yêu cầu khám bệnh..." required><?= e($selectedReason) ?></textarea>
      <div class="field-hint">Thông tin triệu chứng giúp bác sĩ chuẩn bị chu đáo trước buổi thăm khám.</div>
    </div>
    <div class="appointment-actions">
      <button type="submit" class="btn-submit-appointment">
        <svg class="icon" style="width:18px;height:18px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
          <polyline points="20 6 9 17 4 12"></polyline>
        </svg>
        <?= e($formAction) ?>
      </button>
      <a class="btn-back-appointment" href="dashboard.php">
        <svg class="icon" style="width:18px;height:18px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
          <line x1="19" y1="12" x2="5" y2="12"></line>
          <polyline points="12 19 5 12 12 5"></polyline>
        </svg>
        Quay lại
      </a>
      <?php if ($editingAppointment): ?>
        <a class="btn-back-appointment" href="book_appointment.php" style="background:#ffffff !important;border-color:#c8dced !important;color:#4e6a86 !important;">
          <svg class="icon" style="width:18px;height:18px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="12" y1="5" x2="12" y2="19"></line>
            <line x1="5" y1="12" x2="19" y2="12"></line>
          </svg>
          Tạo lịch mới
        </a>
      <?php endif; ?>
    </div>
  </form>
</div>
<?php render_footer(); ?>
