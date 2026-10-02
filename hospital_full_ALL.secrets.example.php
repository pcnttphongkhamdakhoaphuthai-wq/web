<?php
declare(strict_types=1);

return [
    // -------------------------------------------------------------------------
    // 1. Cấu hình cơ sở dữ liệu:
    // -------------------------------------------------------------------------
    // Môi trường MySQL thông thường (Local XAMPP / Cloud VPS / cPanel):
    'db_host' => 'localhost',
    'db_port' => 3306,
    'db_name' => 'benhvien_support',
    'db_user' => 'root',
    'db_password' => '',
    'db_ssl' => false,

    // Hoặc Môi trường TiDB Cloud Serverless:
    // 'db_host' => 'gateway01.ap-southeast-1.prod.aws.tidbcloud.com',
    // 'db_port' => 4000,
    // 'db_name' => 'benhvien_support',
    // 'db_user' => '3xABCD...root',
    // 'db_password' => 'your-tidb-password',
    // 'db_ssl' => true,

    // -------------------------------------------------------------------------
    // 2. Đường dẫn lưu trữ dữ liệu động (Runtime Storage):
    // -------------------------------------------------------------------------
    'runtime_path' => __DIR__ . DIRECTORY_SEPARATOR . 'storage',

    // -------------------------------------------------------------------------
    // 3. Cấu hình Trợ lý AI (Google Gemini API):
    // -------------------------------------------------------------------------
    'gemini_api_key' => 'your-gemini-api-key-here',

    // -------------------------------------------------------------------------
    // 4. Gửi email OTP (Gmail SMTP):
    // -------------------------------------------------------------------------
    'smtp_host' => 'smtp.gmail.com',
    'smtp_port' => 465,
    'smtp_secure' => 'ssl',
    'smtp_username' => 'your-clinic-email@gmail.com',
    'smtp_password' => 'your-16-char-app-password',
    'smtp_from_email' => 'your-clinic-email@gmail.com',
    'smtp_from_name' => 'Phòng khám đa khoa Phú Thái',

    // -------------------------------------------------------------------------
    // 5. Gửi SMS OTP qua Webhook nhà mạng (JSON { phone, message }):
    // -------------------------------------------------------------------------
    'sms_webhook' => '',
    'sms_webhook_bearer' => '',

    // -------------------------------------------------------------------------
    // 6. Xác thực người thật (Google reCAPTCHA v2 checkbox):
    // -------------------------------------------------------------------------
    'recaptcha_site_key' => 'your-recaptcha-site-key',
    'recaptcha_secret_key' => 'your-recaptcha-secret-key',

    // -------------------------------------------------------------------------
    // 7. Chống spam OTP:
    // -------------------------------------------------------------------------
    'otp_request_cooldown' => 60,
    'otp_request_limit' => 5,
    'otp_request_window' => 3600,
];
