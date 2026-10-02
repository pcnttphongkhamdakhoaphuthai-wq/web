<?php
declare(strict_types=1);

require_once 'config.php';

audit_log('account_logout', [
    'account_type' => isset($_SESSION['admin_id']) ? 'admin' : (isset($_SESSION['user_id']) ? 'patient' : 'guest'),
]);

$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
}

session_destroy();
session_start();
set_flash('success', 'Bạn đã đăng xuất.');
redirect('login.php');
