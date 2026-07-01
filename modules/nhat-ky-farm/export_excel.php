<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

require_login();

$pondId = (int)($_GET['pond_id'] ?? 0);

$sql = 'SELECT l.*, p.name AS pond_name, u.name AS creator_name FROM farm_logs l
        JOIN farm_ponds p ON p.id = l.pond_id
        LEFT JOIN users u ON u.id = l.created_by WHERE 1=1';
$params = [];
if ($pondId) {
    $sql .= ' AND l.pond_id = ?';
    $params[] = $pondId;
}
$sql .= ' ORDER BY l.log_date DESC, l.id DESC';

$stmt = db()->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll();

$headers = ['Ngay', 'Ao', 'Luong cho an', 'Ghi chu', 'Nguoi ghi'];
$rows = [];
foreach ($logs as $l) {
    $rows[] = [$l['log_date'], $l['pond_name'], $l['feed_amount'] ?? '', $l['note'] ?? '', $l['creator_name'] ?? ''];
}

export_table_as_excel($headers, $rows, 'NhatKyFarm_' . date('Ymd'));
