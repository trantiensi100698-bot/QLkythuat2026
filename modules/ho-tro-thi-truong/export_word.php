<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

require_login();

$id = (int)($_GET['id'] ?? 0);
$stmt = db()->prepare('SELECT v.*, u.name AS creator_name FROM market_visits v LEFT JOIN users u ON u.id = v.created_by WHERE v.id = ?');
$stmt->execute([$id]);
$visit = $stmt->fetch();

if (!$visit) {
    flash_set('danger', 'Khong tim thay chuyen di.');
    redirect('/modules/ho-tro-thi-truong/index.php');
}

$stmt = db()->prepare('SELECT * FROM market_visit_samples WHERE visit_id = ?');
$stmt->execute([$id]);
$samples = $stmt->fetchAll();

$html = '<h1>Bao cao chuyen di: ' . e(market_visit_type_label($visit['visit_type'])) . '</h1>';
$html .= '<p><strong>Ngay thuc hien:</strong> ' . e($visit['visit_date']) . '<br>';
$html .= '<strong>Khu vuc / dia diem:</strong> ' . e($visit['location'] ?? '-') . '<br>';
$html .= '<strong>Dai ly phu trach:</strong> ' . e($visit['agent_name'] ?? '-') . '<br>';
$html .= '<strong>Khach hang tiem nang:</strong> ' . e($visit['customer_name'] ?? '-') . '<br>';
$html .= '<strong>Nguoi thuc hien:</strong> ' . e($visit['creator_name'] ?? '-') . ($visit['participants'] ? ' + ' . e($visit['participants']) : '') . '</p>';

$html .= '<h2>Noi dung chuyen di</h2><p>' . nl2br(e($visit['content'] ?? 'Khong co mo ta.')) . '</p>';
$html .= '<h2>Phan hoi cua khach hang</h2><p>' . nl2br(e($visit['customer_feedback'] ?? 'Chua co phan hoi.')) . '</p>';

if ($samples) {
    $html .= '<h2>Mau kiem tra tai ao</h2><table><tr><th>Loai mau</th><th>Ket qua</th></tr>';
    foreach ($samples as $s) {
        $html .= '<tr><td>' . e($s['sample_type']) . '</td><td>' . e($s['result_description'] ?? '') . '</td></tr>';
    }
    $html .= '</table>';
}

export_html_as_word($html, 'BaoCaoChuyenDi_' . $visit['visit_date'] . '_' . preg_replace('/[^a-zA-Z0-9]+/', '_', $visit['customer_name'] ?? $visit['agent_name'] ?? 'khach'));
