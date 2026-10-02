<?php
declare(strict_types=1);

/**
 * Health Check Endpoint for Render / Cloud Load Balancer
 * Luôn trả về HTTP 200 OK để xác nhận container web đang hoạt động bình thường.
 */
http_response_code(200);
header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
echo "OK";
