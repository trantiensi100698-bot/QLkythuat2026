<?php
declare(strict_types=1);
require_once __DIR__ . '/../../../includes/db.php';
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/functions.php';

$user = require_login();

$status = $_GET['status'] ?? '';
$category = $_GET['category'] ?? '';

$sql = 'SELECT dr.*, u.name AS creator_name FROM diagnosis_requests dr LEFT JOIN users u ON u.id = dr.created_by WHERE 1=1';
$params = [];

if ($user['role'] === 'sale') {
    $sql .= ' AND dr.created_by = ?';
    $params[] = $user['id'];
}
if (in_array($status, ['moi','dang_xu_ly','da_tu_van','hoan_thanh'], true)) {
    $sql .= ' AND dr.status = ?';
    $params[] = $status;
}
if (in_array($category, all_categories(), true)) {
    $sql .= ' AND dr.problem_category = ?';
    $params[] = $category;
}
$sql .= ' ORDER BY dr.created_at DESC';

$stmt = db()->prepare($sql);
$stmt->execute($params);
$requests = $stmt->fetchAll();

$headers = ['Khach hang', 'Dai ly', 'Khu vuc', 'Dien tich ao (m2)', 'Van de', 'Trang thai', 'Nguoi tao', 'Ngay tao'];
$rows = [];
foreach ($requests as $r) {
    $rows[] = [
        $r['customer_name'],
        $r['agent_name'] ?? '',
        $r['location'] ?? '',
        $r['pond_area'] ?? '',
        category_label($r['problem_category']),
        status_label($r['status']),
        $r['creator_name'] ?? '',
        $r['created_at'],
    ];
}

export_table_as_excel($headers, $rows, 'ChanDoanAo_' . date('Ymd'));
