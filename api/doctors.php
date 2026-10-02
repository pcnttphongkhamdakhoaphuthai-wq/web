<?php
declare(strict_types=1);

/**
 * api/doctors.php - Trả về danh sách bác sĩ công khai
 */

require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');

$doctorsRes = $conn->query('SELECT id, name, title, department, specialties, bio, photo_path FROM doctors ORDER BY id ASC');
$doctorsList = [];
if ($doctorsRes) {
    while ($row = $doctorsRes->fetch_assoc()) {
        $row['photo_url'] = doctor_photo_url($row['photo_path'] ?? null);
        $doctorsList[] = $row;
    }
}

echo json_encode([
    'success' => true,
    'total' => count($doctorsList),
    'data' => $doctorsList,
], JSON_UNESCAPED_UNICODE);
