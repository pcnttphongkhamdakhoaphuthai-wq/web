<?php
declare(strict_types=1);

return [
    'db_host' => 'localhost',
    'db_name' => 'benhvien_support',
    'db_user' => 'root',
    'db_password' => '',

    'runtime_path' => __DIR__ . DIRECTORY_SEPARATOR . 'storage',

    // Cấu hình API Key cho Chatbot AI (Gemini)
    'gemini_api_key' => 'your-gemini-api-key-here',

    // Gmail SMTP: tạo App Password trong tài khoản Google rồi điền vào đây.
    'smtp_host' => 'smtp.gmail.com',
    'smtp_port' => 465,
    'smtp_secure' => 'ssl',
    'smtp_username' => 'your-clinic-email@gmail.com',
    'smtp_password' => 'your-16-char-app-password',
    'smtp_from_email' => 'your-clinic-email@gmail.com',
    'smtp_from_name' => 'Phòng khám đa khoa',

    // SMS webhook của nhà cung cấp, nhận JSON { phone, message }.
    'sms_webhook' => '',
    'sms_webhook_bearer' => '',

    // Google reCAPTCHA v2 checkbox: https://www.google.com/recaptcha/admin/create
    'recaptcha_site_key' => 'your-recaptcha-site-key',
    'recaptcha_secret_key' => 'your-recaptcha-secret-key',

    // Chống spam OTP.
    'otp_request_cooldown' => 60,
    'otp_request_limit' => 5,
    'otp_request_window' => 3600,
];
