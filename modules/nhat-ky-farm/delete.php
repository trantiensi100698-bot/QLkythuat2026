<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

require_role(['rd', 'manager']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/modules/nhat-ky-farm/index.php');
}
check_csrf();

$id = (int)($_POST['id'] ?? 0);
if ($id) {
    db()->prepare('DELETE FROM farm_logs WHERE id = ?')->execute([$id]);
    flash_set('success', 'Da xoa nhat ky.');
}

redirect('/modules/nhat-ky-farm/index.php');
