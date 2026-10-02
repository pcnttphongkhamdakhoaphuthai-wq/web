<?php
declare(strict_types=1);

/**
 * api/healthz.php - Kiểm tra trạng thái máy chủ Render và Database TiDB
 */

require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');

$dbStatus = 'disconnected';
try {
    if (isset($conn) && $conn instanceof mysqli && $conn->ping()) {
        $dbStatus = 'connected';
    }
} catch (Throwable $e) {
    $dbStatus = 'error';
}

echo json_encode([
    'status' => 'ok',
    'service' => 'hospital-backend-api',
    'region' => 'singapore',
    'database' => $dbStatus,
    'timestamp' => time(),
    'datetime' => date('Y-m-d H:i:s'),
], JSON_UNESCAPED_UNICODE);
