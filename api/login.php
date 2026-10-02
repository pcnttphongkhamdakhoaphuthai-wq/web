<?php
declare(strict_types=1);

/**
 * api/login.php - REST API Đăng nhập bệnh nhân bằng CCCD
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
$password = (string) ($input['password'] ?? '');

if ($cccd === '' || $password === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Vui lòng nhập đầy đủ Số CCCD và Mật khẩu.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!validate_cccd($cccd)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Số CCCD không hợp lệ (yêu cầu đúng 12 chữ số).'], JSON_UNESCAPED_UNICODE);
    exit;
}

$lockedUntil = get_login_lockout('patient_login', $cccd);
if ($lockedUntil !== null) {
    http_response_code(429);
    echo json_encode(['success' => false, 'error' => 'Tài khoản tạm thời bị khóa do nhập sai nhiều lần. Vui lòng thử lại sau.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$stmt = $conn->prepare('SELECT id, full_name, password_hash FROM patients WHERE cccd = ? LIMIT 1');
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Lỗi kết nối cơ sở dữ liệu.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$stmt->bind_param('s', $cccd);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

$temporaryPasswordRecord = $user ? verify_temporary_patient_password($cccd, $password) : null;

if ($user && (verify_password($password, $user['password_hash']) || $temporaryPasswordRecord !== null)) {
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['name'] = $user['full_name'];
    $_SESSION['cccd'] = $cccd;
    mark_session_authenticated();
    clear_patient_password_change_requirement();
    clear_login_failures('patient_login', $cccd);

    echo json_encode([
        'success' => true,
        'message' => 'Đăng nhập thành công.',
        'redirect' => '/dashboard.php#overview',
        'user' => [
            'id' => (int) $user['id'],
            'name' => $user['full_name'],
            'cccd' => $cccd,
        ],
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

record_login_failure('patient_login', $cccd);
http_response_code(401);
echo json_encode(['success' => false, 'error' => 'Số CCCD hoặc mật khẩu không chính xác.'], JSON_UNESCAPED_UNICODE);
