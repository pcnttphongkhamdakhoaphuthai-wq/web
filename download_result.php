<?php
declare(strict_types=1);

require_once 'config.php';

$recordId = (int) ($_GET['id'] ?? 0);
$isAdmin = isset($_SESSION['admin_id']);
$isPatient = isset($_SESSION['user_id']);

if (!$isAdmin && !$isPatient) {
    http_response_code(403);
    exit('Forbidden');
}

if ($isAdmin && !admin_can('manage_records')) {
    http_response_code(403);
    exit('Forbidden');
}

if ($recordId <= 0) {
    http_response_code(404);
    exit('Not found');
}

$stmt = $conn->prepare('SELECT mr.id, mr.patient_id, mr.result_file, mr.visit_date, d.department FROM medical_records mr LEFT JOIN doctors d ON d.id = mr.doctor_id WHERE mr.id = ? LIMIT 1');
$stmt->bind_param('i', $recordId);
$stmt->execute();
$record = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$record || empty($record['result_file'])) {
    http_response_code(404);
    exit('Not found');
}

if ($isPatient && (int) $record['patient_id'] !== (int) $_SESSION['user_id']) {
    security_log('result_download_forbidden', [
        'record_id' => $recordId,
        'patient_id' => (int) $_SESSION['user_id'],
    ]);
    http_response_code(403);
    exit('Forbidden');
}

if ($isAdmin) {
    $adminDepartment = (string) ($_SESSION['admin_department'] ?? '');
    $recordDepartment = (string) ($record['department'] ?? '');
    if (!admin_can('manage_all_records') && $adminDepartment !== '' && $recordDepartment !== $adminDepartment) {
        security_log('result_download_forbidden', [
            'record_id' => $recordId,
            'admin_id' => (int) $_SESSION['admin_id'],
            'admin_department' => $adminDepartment,
            'record_department' => $recordDepartment,
        ]);
        http_response_code(403);
        exit('Forbidden');
    }
}

$filePath = resolve_result_file_path((string) $record['result_file']);
if ($filePath === null) {
    http_response_code(404);
    exit('Not found');
}

security_log('result_downloaded', [
    'record_id' => $recordId,
    'actor' => $isAdmin ? 'admin' : 'patient',
    'actor_id' => $isAdmin ? (int) $_SESSION['admin_id'] : (int) $_SESSION['user_id'],
]);

$downloadName = sprintf('ket-qua-%d-%s.pdf', $recordId, date('Ymd', strtotime((string) $record['visit_date'])));
header('Content-Type: application/pdf');
header('Content-Length: ' . (string) filesize($filePath));
header('Content-Disposition: inline; filename="' . rawurlencode($downloadName) . '"');
header('Cache-Control: private, max-age=0, no-store');
readfile($filePath);
exit;
