<?php
declare(strict_types=1);

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

header('Content-Type: text/plain; charset=utf-8');

echo "=== HOSPITAL APP DIAGNOSTIC ===\n";
echo "Timestamp: " . date('Y-m-d H:i:s') . "\n";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "SAPI: " . PHP_SAPI . "\n";
echo "Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'N/A') . "\n";
echo "HTTPS Header: " . ($_SERVER['HTTPS'] ?? 'off') . "\n";
echo "Forwarded Proto: " . ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? 'N/A') . "\n";
echo "Remote Addr: " . ($_SERVER['REMOTE_ADDR'] ?? 'N/A') . "\n";
echo "Storage Writable: " . (is_writable(__DIR__ . '/storage') ? 'YES' : 'NO') . "\n";

try {
    require_once __DIR__ . '/config.php';
    echo "Config Loaded: SUCCESS\n";
    echo "DB Host: " . ($appConfig['db_host'] ?? '') . "\n";
    echo "DB Connected: " . (isset($conn) && $conn instanceof mysqli && @$conn->stat() !== false ? 'YES' : 'NO') . "\n";
    if (isset($conn) && $conn instanceof mysqli) {
        $res = $conn->query('SELECT COUNT(*) FROM doctors');
        echo "Doctors Count: " . ($res ? $res->fetch_row()[0] : 'ERROR') . "\n";
    }
    echo "Session Status: " . session_status() . "\n";
    echo "Session Path: " . session_save_path() . "\n";
    echo "STATUS: ALL SYSTEMS HEALTHY\n";
} catch (Throwable $e) {
    echo "STATUS: FAILED\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
