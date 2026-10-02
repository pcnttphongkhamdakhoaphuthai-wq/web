<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only.\n");
    exit(1);
}

$root = dirname(__DIR__);
$source = $argv[1] ?? ($root . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'backups' . DIRECTORY_SEPARATOR . 'backup_20260424_000704' . DIRECTORY_SEPARATOR . 'database_snapshot.sql');

if (!is_file($source)) {
    fwrite(STDERR, "SQL file not found: {$source}\n");
    exit(1);
}

require $root . DIRECTORY_SEPARATOR . 'config.php';

$backupDir = $root . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'backups' . DIRECTORY_SEPARATOR . 'pre_restore_' . date('Ymd_His');
if (!is_dir($backupDir) && !mkdir($backupDir, 0775, true)) {
    fwrite(STDERR, "Cannot create backup directory: {$backupDir}\n");
    exit(1);
}

$backupPath = $backupDir . DIRECTORY_SEPARATOR . 'database_snapshot.sql';
export_base_tables_snapshot($conn, $backupPath);
$databaseName = (string) $conn->query('SELECT DATABASE()')->fetch_row()[0];
$conn->close();

$sql = file_get_contents($source);
if ($sql === false || trim($sql) === '') {
    fwrite(STDERR, "SQL file is empty or unreadable: {$source}\n");
    exit(1);
}

$rootConn = new mysqli('localhost', 'root', '');
$rootConn->set_charset('utf8mb4');
$rootConn->query("CREATE DATABASE IF NOT EXISTS `" . str_replace('`', '``', $databaseName) . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$rootConn->select_db($databaseName);

if (!$rootConn->multi_query($sql)) {
    fwrite(STDERR, "Import failed: {$rootConn->error}\n");
    exit(1);
}

do {
    if ($result = $rootConn->store_result()) {
        $result->free();
    }
    if (!$rootConn->more_results()) {
        break;
    }
} while ($rootConn->next_result());

if ($rootConn->errno) {
    fwrite(STDERR, "Import failed: {$rootConn->error}\n");
    exit(1);
}

$tableCount = (int) $rootConn->query('SHOW TABLES')->num_rows;
echo "Backup saved: {$backupPath}\n";
echo "Imported: {$source}\n";
echo "Tables: {$tableCount}\n";

function export_base_tables_snapshot(mysqli $conn, string $destinationPath): void
{
    $databaseName = (string) $conn->query('SELECT DATABASE()')->fetch_row()[0];
    $stmt = $conn->prepare(
        "SELECT TABLE_NAME FROM information_schema.TABLES
         WHERE TABLE_SCHEMA = ? AND TABLE_TYPE = 'BASE TABLE'
         ORDER BY TABLE_NAME"
    );
    $stmt->bind_param('s', $databaseName);
    $stmt->execute();
    $tables = array_column($stmt->get_result()->fetch_all(MYSQLI_ASSOC), 'TABLE_NAME');
    $stmt->close();

    $handle = fopen($destinationPath, 'wb');
    if ($handle === false) {
        throw new RuntimeException("Cannot write backup: {$destinationPath}");
    }

    fwrite($handle, "-- Pre-restore backup generated at " . date(DATE_ATOM) . "\n");
    fwrite($handle, "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n");

    foreach ($tables as $table) {
        $quotedTable = '`' . str_replace('`', '``', (string) $table) . '`';
        $createResult = $conn->query("SHOW CREATE TABLE {$quotedTable}");
        $createRow = $createResult->fetch_assoc();
        fwrite($handle, "DROP TABLE IF EXISTS {$quotedTable};\n");
        fwrite($handle, (string) $createRow['Create Table'] . ";\n\n");
        $createResult->free();

        $dataResult = $conn->query("SELECT * FROM {$quotedTable}");
        while ($row = $dataResult->fetch_assoc()) {
            $columns = array_map(
                static fn(string $column): string => '`' . str_replace('`', '``', $column) . '`',
                array_keys($row)
            );
            $values = array_map(
                static fn($value): string => $value === null ? 'NULL' : "'" . $conn->real_escape_string((string) $value) . "'",
                array_values($row)
            );
            fwrite($handle, "INSERT INTO {$quotedTable} (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $values) . ");\n");
        }
        $dataResult->free();
        fwrite($handle, "\n");
    }

    fwrite($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
    fclose($handle);
}
