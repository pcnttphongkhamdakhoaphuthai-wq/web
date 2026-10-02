<?php
declare(strict_types=1);

require_once 'config.php';
require_patient_login();

$userId = (int) ($_SESSION['user_id'] ?? 0);
$payload = get_patient_chat_poll_payload($userId, 24);

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
