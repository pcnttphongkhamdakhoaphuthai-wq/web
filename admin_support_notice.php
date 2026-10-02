<?php
declare(strict_types=1);

require_once 'config.php';
require_admin_login();

$adminId = (int) ($_SESSION['admin_id'] ?? 0);
$csrfToken = (string) ($_POST['_csrf'] ?? '');
$expectsJson = strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';
$returnUrl = (string) ($_SERVER['HTTP_REFERER'] ?? 'admin_add_record.php');

if (!preg_match('/(^|\/)admin_(add_record|accounts|profile)\.php([?#].*)?$/', $returnUrl)) {
    $returnUrl = admin_home_path();
}

function respond_support_notice(array $payload, int $statusCode = 200): void
{
    global $expectsJson, $returnUrl;

    if ($expectsJson) {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=UTF-8');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($statusCode >= 400) {
        set_flash('error', (string) ($payload['message'] ?? 'Không thể xử lý phản hồi hỗ trợ.'));
    } elseif (!empty($payload['message'])) {
        set_flash('success', (string) $payload['message']);
    }

    redirect($returnUrl);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token('admin_support_api', $csrfToken)) {
        respond_support_notice([
            'ok' => false,
            'error' => 'csrf_invalid',
            'message' => 'Phiên làm việc đã hết hạn, vui lòng thử lại.',
        ], 403);
    }

    $action = (string) ($_POST['action'] ?? 'dismiss');
    if ($action === 'reply') {
        $patientId = (int) ($_POST['patient_id'] ?? 0);
        $message = trim((string) ($_POST['message'] ?? ''));
        $latestChatId = (int) ($_POST['latest_chat_id'] ?? 0);

        if ($patientId <= 0 || $message === '') {
            respond_support_notice([
                'ok' => false,
                'error' => 'reply_invalid',
                'message' => 'Vui lòng nhập nội dung trả lời cho bệnh nhân.',
            ], 422);
        }

        $adminName = (string) ($_SESSION['admin_full_name'] ?? $_SESSION['admin_username'] ?? 'Nhân viên hỗ trợ');
        save_chat_message($patientId, 'bot', '[Hỗ trợ] ' . $adminName . ': ' . $message);
        mark_admin_support_notice_seen($adminId, $latestChatId);

        audit_log('admin_support_reply_sent', [
            'patient_id' => $patientId,
            'latest_chat_id' => $latestChatId,
        ]);

        respond_support_notice([
            'ok' => true,
            'replied' => true,
            'message' => 'Đã gửi trả lời cho bệnh nhân.',
        ]);
    }

    $latestChatId = (int) ($_POST['latest_chat_id'] ?? 0);
    mark_admin_support_notice_seen($adminId, $latestChatId);

    respond_support_notice([
        'ok' => true,
        'message' => 'Đã đánh dấu thông báo hỗ trợ mới.',
    ]);
}

$notice = get_admin_support_chat_notice($conn, $adminId, 4);

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
echo json_encode($notice, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
