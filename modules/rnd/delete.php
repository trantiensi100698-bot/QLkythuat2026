<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

require_role(['rd', 'manager']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/modules/rnd/index.php');
}
check_csrf();

$id = (int)($_POST['id'] ?? 0);
if ($id) {
    db()->prepare('DELETE FROM rd_experiments WHERE id = ?')->execute([$id]);
    flash_set('success', 'Da xoa thi nghiem.');
}

redirect('/modules/rnd/index.php');
