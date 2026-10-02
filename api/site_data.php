<?php
declare(strict_types=1);

/**
 * api/site_data.php - Trả về toàn bộ dữ liệu cấu hình, bác sĩ, tin tức, tài liệu
 */

require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');

$clinic = site_settings([
    'clinic_name' => 'Phòng khám đa khoa Phú Thái',
    'clinic_intro' => 'Phòng khám cung cấp dịch vụ đặt lịch, trả kết quả và quản lý hồ sơ khám bệnh trên cùng một hệ thống trực tuyến.',
    'clinic_mission' => 'Tối ưu quy trình tiếp nhận và giúp bệnh nhân theo dõi hồ sơ nhanh hơn.',
    'clinic_facility' => 'Có khu khám, khu xét nghiệm và hệ thống lưu trữ hồ sơ điện tử phục vụ tra cứu kết quả.',
    'clinic_services' => "Siêu âm tổng quát\nXét nghiệm máu, nước tiểu\nKhám nội tổng quát\nĐiện tim\nTư vấn sức khỏe định kỳ",
    'clinic_support' => 'Hỗ trợ người bệnh từ đặt lịch, tiếp nhận hồ sơ đến trả kết quả trực tuyến.',
    'support_hotline' => '0208 6289 888 / 0963 485 65',
    'support_email' => 'pcnttphongkhamdakhoaphuthai@gmail.com',
    'clinic_address' => 'Xóm Hoà Bình 2, xã Phú Bình, tỉnh Thái Nguyên',
]);

$doctorsRes = $conn->query('SELECT id, name, title, department, specialties, bio, photo_path FROM doctors ORDER BY id ASC');
$doctorsList = [];
if ($doctorsRes) {
    while ($row = $doctorsRes->fetch_assoc()) {
        $row['photo_url'] = doctor_photo_url($row['photo_path'] ?? null);
        $doctorsList[] = $row;
    }
}

$newsPostsList = get_recent_news_posts(6, true);
$customerResourcesList = get_customer_resources(6, true);
$appointmentsEnabled = appointments_enabled();

echo json_encode([
    'success' => true,
    'clinic' => $clinic,
    'doctors' => $doctorsList,
    'news' => $newsPostsList,
    'resources' => $customerResourcesList,
    'appointments_enabled' => $appointmentsEnabled,
    'timestamp' => time(),
], JSON_UNESCAPED_UNICODE);
