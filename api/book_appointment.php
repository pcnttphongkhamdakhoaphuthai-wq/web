<?php
declare(strict_types=1);

/**
 * api/book_appointment.php - REST API Đặt lịch khám bệnh trực tuyến
 */

require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method Not Allowed'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!appointments_enabled()) {
    http_response_code(503);
    echo json_encode(['success' => false, 'error' => 'Tính năng đặt lịch hẹn hiện đang tạm bảo trì.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);
if (!is_array($input)) {
    $input = $_POST;
}

$doctorId = (int) ($input['doctor_id'] ?? 0);
$dateValue = trim((string) ($input['date'] ?? ''));
$reason = trim((string) ($input['reason'] ?? ''));
$fullName = normalize_single_line_input((string) ($input['name'] ?? ''));
$phone = normalize_single_line_input((string) ($input['phone'] ?? ''));
$cccd = normalize_single_line_input((string) ($input['cccd'] ?? ''));

// Xác định User ID
$userId = (int) ($_SESSION['user_id'] ?? 0);

if ($userId <= 0) {
    // Nếu chưa đăng nhập session, kiểm tra thông tin định danh
    if ($fullName === '' || $phone === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Vui lòng cung cấp Họ và tên và Số điện thoại liên hệ.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    if (!validate_phone_number($phone)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Số điện thoại không hợp lệ.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Tìm bệnh nhân theo CCCD hoặc phone
    if ($cccd !== '' && validate_cccd($cccd)) {
        $stmt = $conn->prepare('SELECT id FROM patients WHERE cccd = ? LIMIT 1');
        $stmt->bind_param('s', $cccd);
    } else {
        $stmt = $conn->prepare('SELECT id FROM patients WHERE phone = ? LIMIT 1');
        $stmt->bind_param('s', $phone);
    }
    $stmt->execute();
    $pat = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($pat) {
        $userId = (int) $pat['id'];
    } else {
        // Tạo hồ sơ bệnh nhân vãng lai
        $tempCccd = ($cccd !== '' && validate_cccd($cccd)) ? $cccd : ('TMP' . str_pad((string) mt_rand(1, 999999999), 9, '0', STR_PAD_LEFT));
        $randPass = bin2hex(random_bytes(6));
        $passHash = hash_password($randPass);
        $insStmt = $conn->prepare('INSERT INTO patients (cccd, full_name, phone, password_hash) VALUES (?, ?, ?, ?)');
        $insStmt->bind_param('ssss', $tempCccd, $fullName, $phone, $passHash);
        if ($insStmt->execute()) {
            $userId = (int) $insStmt->insert_id;
        }
        $insStmt->close();
    }
}

if ($doctorId <= 0 || $dateValue === '' || $reason === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Vui lòng chọn bác sĩ, thời gian khám và lý do khám.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$appointmentDate = normalize_appointment_datetime($dateValue);
if ($appointmentDate === null) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Định dạng ngày giờ hẹn không hợp lệ.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (strtotime($appointmentDate) < time()) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Thời gian hẹn phải ở tương lai.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$doctor = find_doctor_by_id($conn, $doctorId);
if (!$doctor) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Bác sĩ được chọn không tồn tại hoặc đã ngừng tiếp nhận.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$slotError = validate_appointment_slot($conn, $doctorId, $userId, $appointmentDate);
if ($slotError !== null) {
    http_response_code(409);
    echo json_encode(['success' => false, 'error' => $slotError], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $stmt = $conn->prepare("INSERT INTO appointments (patient_id, doctor_id, appointment_date, reason, status) VALUES (?, ?, ?, ?, 'pending')");
    $stmt->bind_param('iiss', $userId, $doctorId, $appointmentDate, $reason);
    if (!$stmt->execute()) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Không thể lưu lịch khám. Vui lòng thử lại.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $bookingId = $stmt->insert_id;
    $stmt->close();

    echo json_encode([
        'success' => true,
        'message' => 'Đặt lịch khám thành công! Bộ phận tiếp nhận sẽ liên hệ xác nhận với bạn.',
        'appointment_id' => $bookingId,
        'appointment_date' => $appointmentDate,
        'doctor_name' => $doctor['name'],
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Lỗi máy chủ khi tạo lịch hẹn.'], JSON_UNESCAPED_UNICODE);
}
