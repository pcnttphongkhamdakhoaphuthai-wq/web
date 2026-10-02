<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';

if (!running_in_cli()) {
    http_response_code(403);
    exit('Forbidden');
}

$username = 'administrator';
$fullName = 'Administrator';
$department = 'Quản trị hệ thống';
$role = 'root_admin';

$stmt = $conn->prepare('SELECT id, is_root, is_active FROM admins WHERE username = ? LIMIT 1');
$stmt->bind_param('s', $username);
$stmt->execute();
$existing = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($existing) {
    $adminId = (int) $existing['id'];
    $isRoot = 1;
    $isActive = 1;
    $canManageAccounts = 1;
    $canManageRecords = 1;
    $canManageAllRecords = 1;

    $stmt = $conn->prepare(
        'UPDATE admins
         SET full_name = ?, department = ?, role = ?, is_root = ?, is_active = ?, can_manage_accounts = ?, can_manage_records = ?, can_manage_all_records = ?
         WHERE id = ?'
    );
    $stmt->bind_param(
        'sssiiiiii',
        $fullName,
        $department,
        $role,
        $isRoot,
        $isActive,
        $canManageAccounts,
        $canManageRecords,
        $canManageAllRecords,
        $adminId
    );
    $stmt->execute();
    $stmt->close();

    echo "Updated existing administrator account as undeletable root admin.\n";
    echo "Username: {$username}\n";
    echo "Password: unchanged\n";
    exit(0);
}

$password = 'Admin@' . bin2hex(random_bytes(6));
$passwordHash = hash_password($password);
$isRoot = 1;
$isActive = 1;
$canManageAccounts = 1;
$canManageRecords = 1;
$canManageAllRecords = 1;

$stmt = $conn->prepare(
    'INSERT INTO admins (username, full_name, department, role, is_root, is_active, can_manage_accounts, can_manage_records, can_manage_all_records, password_hash)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
);
$stmt->bind_param(
    'ssssiiiiis',
    $username,
    $fullName,
    $department,
    $role,
    $isRoot,
    $isActive,
    $canManageAccounts,
    $canManageRecords,
    $canManageAllRecords,
    $passwordHash
);
$stmt->execute();
$stmt->close();

echo "Created undeletable root administrator account.\n";
echo "Username: {$username}\n";
echo "Password: {$password}\n";
