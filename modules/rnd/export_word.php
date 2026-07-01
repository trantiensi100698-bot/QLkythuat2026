<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

require_login();

$id = (int)($_GET['id'] ?? 0);
$stmt = db()->prepare('SELECT e.*, u.name AS creator_name FROM rd_experiments e LEFT JOIN users u ON u.id = e.created_by WHERE e.id = ?');
$stmt->execute([$id]);
$experiment = $stmt->fetch();

if (!$experiment) {
    flash_set('danger', 'Khong tim thay thi nghiem.');
    redirect('/modules/rnd/index.php');
}

$stmt = db()->prepare('SELECT * FROM rd_measurements WHERE experiment_id = ? ORDER BY stage, measured_at');
$stmt->execute([$id]);
$measurements = $stmt->fetchAll();

$stmt = db()->prepare('SELECT rp.*, pr.name AS product_name FROM rd_experiment_products rp JOIN products pr ON pr.id = rp.product_id WHERE rp.experiment_id = ?');
$stmt->execute([$id]);
$linkedProducts = $stmt->fetchAll();
$totalCost = array_sum(array_map(fn($p) => (float)($p['cost'] ?? 0), $linkedProducts));

$html = '<h1>' . e($experiment['title']) . '</h1>';
$html .= '<p><strong>Nhom:</strong> ' . e(rd_category_label($experiment['category'])) . ' &nbsp; ';
$html .= '<strong>Trang thai:</strong> ' . e(rd_status_label($experiment['status'])) . '<br>';
$html .= '<strong>Thoi gian:</strong> ' . e($experiment['start_date'] ?? '?') . ' &rarr; ' . e($experiment['end_date'] ?? '?') . '<br>';
$html .= '<strong>Nguoi phu trach:</strong> ' . e($experiment['creator_name'] ?? '-') . '</p>';

$html .= '<h2>Muc tieu</h2><p>' . nl2br(e($experiment['objective'] ?? 'Khong co.')) . '</p>';

$html .= '<h2>Chi tieu do dac</h2><table><tr><th>Giai doan</th><th>Ngay do</th><th>Chi tieu</th><th>Gia tri</th><th>Don vi</th></tr>';
foreach ($measurements as $m) {
    $html .= '<tr><td>' . e($m['stage'] === 'sau' ? 'Sau' : 'Truoc') . '</td><td>' . e($m['measured_at'] ?? '') . '</td><td>' . e($m['indicator_name']) . '</td><td>' . e($m['indicator_value']) . '</td><td>' . e($m['unit'] ?? '') . '</td></tr>';
}
if (!$measurements) {
    $html .= '<tr><td colspan="5">Chua co so lieu.</td></tr>';
}
$html .= '</table>';

$html .= '<h2>San pham su dung &amp; chi phi</h2><table><tr><th>San pham</th><th>Lieu dung</th><th>Chi phi (VND)</th><th>Ghi chu</th></tr>';
foreach ($linkedProducts as $p) {
    $html .= '<tr><td>' . e($p['product_name']) . '</td><td>' . e($p['dosage'] ?? '') . '</td><td>' . ($p['cost'] !== null ? number_format((float)$p['cost'], 0, ',', '.') : '') . '</td><td>' . e($p['note'] ?? '') . '</td></tr>';
}
if (!$linkedProducts) {
    $html .= '<tr><td colspan="4">Chua co san pham.</td></tr>';
}
$html .= '</table>';
if ($totalCost > 0) {
    $html .= '<p><strong>Tong chi phi:</strong> ' . number_format($totalCost, 0, ',', '.') . ' VND</p>';
}

$html .= '<h2>Uu diem</h2><p>' . nl2br(e($experiment['findings_pros'] ?? 'Chua co danh gia.')) . '</p>';
$html .= '<h2>Nhuoc diem</h2><p>' . nl2br(e($experiment['findings_cons'] ?? 'Chua co danh gia.')) . '</p>';
if ($experiment['cost_analysis']) {
    $html .= '<h2>Phan tich chi phi</h2><p>' . nl2br(e($experiment['cost_analysis'])) . '</p>';
}

export_html_as_word($html, 'BaoCao_RD_' . preg_replace('/[^a-zA-Z0-9]+/', '_', $experiment['title']));
