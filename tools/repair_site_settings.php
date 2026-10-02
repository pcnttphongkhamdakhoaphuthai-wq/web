<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';

if (!running_in_cli()) {
    http_response_code(403);
    exit('Forbidden');
}

$settings = [
    'clinic_name' => 'Phòng khám đa khoa Phú Thái',
    'clinic_intro' => 'Phòng khám cung cấp dịch vụ đặt lịch, trả kết quả và quản lý hồ sơ khám bệnh trên cùng một hệ thống trực tuyến.',
    'clinic_mission' => 'Tối ưu quy trình tiếp nhận và giúp bệnh nhân theo dõi hồ sơ nhanh hơn.',
    'clinic_facility' => 'Có khu khám, khu xét nghiệm và hệ thống lưu trữ hồ sơ điện tử phục vụ tra cứu kết quả.',
    'clinic_support' => 'Hỗ trợ người bệnh từ đặt lịch, tiếp nhận hồ sơ đến trả kết quả trực tuyến.',
    'chatbot_intro' => 'Chat hỗ trợ giúp bệnh nhân xem nhanh hướng dẫn thường gặp và gửi câu hỏi cho bộ phận hỗ trợ.',
    'chatbot_fallback' => 'Chúng tôi đã nhận được câu hỏi của bạn. Bộ phận hỗ trợ sẽ phản hồi sớm hoặc bạn có thể chọn một câu hỏi nhanh bên dưới để xem hướng dẫn ngay.',
];

save_site_settings($settings);

foreach ($settings as $key => $value) {
    echo $key . ': ' . $value . PHP_EOL;
}
