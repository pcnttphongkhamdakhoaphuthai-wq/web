<?php
declare(strict_types=1);

/**
 * api/register.php - REST API Đăng ký tài khoản bệnh nhân
 */

require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method Not Allowed'], JSON_UNESCAPED_UNICODE);
    exit;
}

$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);
if (!is_array($input)) {
    $input = $_POST;
}

$cccd = normalize_single_line_input((string) ($input['cccd'] ?? ''));
$fullName = normalize_single_line_input((string) ($input['name'] ?? ''));
$phone = normalize_single_line_input((string) ($input['phone'] ?? ''));
$email = normalize_single_line_input((string) ($input['email'] ?? ''));
$password = (string) ($input['password'] ?? '');

$emailEnabled = patient_email_enabled();

if ($cccd === '' || $fullName === '' || $phone === '' || $password === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Vui lòng điền đầy đủ các thông tin bắt buộc.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!validate_cccd($cccd)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Số CCCD phải gồm đúng 12 chữ số.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!validate_person_name($fullName)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Họ và tên không hợp lệ.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!validate_phone_number($phone)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Số điện thoại không hợp lệ (10 chữ số, bắt đầu bằng số 0).'], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($email !== '' && !validate_email_address($email)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Địa chỉ email không đúng định dạng.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$passwordError = validate_password_strength($password);
if ($passwordError !== null) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $passwordError], JSON_UNESCAPED_UNICODE);
    exit;
}

// Kiểm tra trùng lặp
if ($emailEnabled && $email !== '') {
    $stmt = $conn->prepare('SELECT id FROM patients WHERE cccd = ? OR phone = ? OR email = ? LIMIT 1');
    $stmt->bind_param('sss', $cccd, $phone, $email);
} else {
    $stmt = $conn->prepare('SELECT id FROM patients WHERE cccd = ? OR phone = ? LIMIT 1');
    $stmt->bind_param('ss', $cccd, $phone);
}

if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Lỗi kết nối cơ sở dữ liệu.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$stmt->execute();
$exists = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($exists) {
    http_response_code(409);
    echo json_encode(['success' => false, 'error' => 'Số CCCD, số điện thoại hoặc email đã được đăng ký trên hệ thống.'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $passwordHash = hash_password($password);
    if ($emailEnabled && $email !== '') {
        $stmt = $conn->prepare('INSERT INTO patients (cccd, full_name, phone, email, password_hash) VALUES (?, ?, ?, ?, ?)');
        $stmt->bind_param('sssss', $cccd, $fullName, $phone, $email, $passwordHash);
    } else {
        $stmt = $conn->prepare('INSERT INTO patients (cccd, full_name, phone, password_hash) VALUES (?, ?, ?, ?)');
        $stmt->bind_param('ssss', $cccd, $fullName, $phone, $passwordHash);
    }

    if (!$stmt || !$stmt->execute()) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Không thể tạo tài khoản vào lúc này. Vui lòng thử lại.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $newId = $stmt->insert_id;
    $stmt->close();

    echo json_encode([
        'success' => true,
        'message' => 'Đăng ký tài khoản thành công! Bạn có thể đăng nhập ngay.',
        'user_id' => $newId,
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Lỗi máy chủ trong quá trình đăng ký.'], JSON_UNESCAPED_UNICODE);
}
