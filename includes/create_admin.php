<?php
declare(strict_types=1);
// Chi chay tu dong lenh (CLI), khong duoc de public tren web sau khi da tao xong tai khoan dau tien.
// Cach dung: php includes/create_admin.php "Ten hien thi" email@domain.com "MatKhauManh123"

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    die('Script nay chi chay qua dong lenh (CLI).');
}

require_once __DIR__ . '/db.php';

[$script, $name, $email, $password] = array_pad($argv, 4, null);

if (!$name || !$email || !$password) {
    fwrite(STDERR, "Cach dung: php includes/create_admin.php \"Ten hien thi\" email@domain.com \"MatKhau\"\n");
    exit(1);
}

$hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = db()->prepare('INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE name = VALUES(name), password_hash = VALUES(password_hash)');
$stmt->execute([$name, $email, $hash, 'rd']);

echo "Da tao/cap nhat tai khoan RD: $email\n";
