<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

if (!running_in_cli()) {
    http_response_code(403);
    exit('Forbidden');
}

try {
    $backup = create_system_backup($conn, 'scheduled_daily');
    echo 'Backup created at: ' . ($backup['path'] ?? '') . PHP_EOL;
} catch (Throwable $exception) {
    fwrite(STDERR, 'Backup failed: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}
