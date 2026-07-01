<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

$user = require_login();
$pageTitle = 'Nhat ky farm nuoi Biogency';
$activeMenu = 'nhat-ky-farm';

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

$ponds = db()->query('SELECT * FROM farm_ponds ORDER BY name ASC')->fetchAll();

include __DIR__ . '/../../includes/layout_start.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
  <h5 class="mb-0">Nhat ky farm nuoi Biogency</h5>
  <div class="d-flex gap-2">
    <a href="export_excel.php?pond_id=<?= (int)$pondId ?>" class="btn btn-outline-secondary btn-sm">Xuat Excel</a>
    <a href="ponds.php" class="btn btn-outline-secondary btn-sm">Quan ly ao farm</a>
    <a href="form.php" class="btn btn-primary btn-sm">+ Ghi nhat ky moi</a>
  </div>
</div>

<?php if (!$ponds): ?>
  <div class="alert alert-warning">Chua co ao nao trong farm. <a href="ponds.php">Them ao</a> truoc khi ghi nhat ky.</div>
<?php endif; ?>

<form method="get" class="row g-2 mb-3 filter-bar">
  <div class="col-auto">
    <select name="pond_id" class="form-select form-select-sm" onchange="this.form.submit()">
      <option value="">Tat ca ao</option>
      <?php foreach ($ponds as $p): ?>
        <option value="<?= (int)$p['id'] ?>" <?= $pondId === (int)$p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
</form>

<div class="card border-0 shadow-sm">
  <div class="table-responsive">
    <table class="table mb-0 align-middle">
      <thead class="table-light"><tr><th>Ngay</th><th>Ao</th><th>Luong cho an</th><th>Nguoi ghi</th><th></th></tr></thead>
      <tbody>
        <?php if (!$logs): ?><tr><td colspan="5" class="text-center text-muted py-4">Chua co nhat ky nao.</td></tr><?php endif; ?>
        <?php foreach ($logs as $log): ?>
        <tr>
          <td><?= e($log['log_date']) ?></td>
          <td><?= e($log['pond_name']) ?></td>
          <td><?= e($log['feed_amount'] ?? '-') ?></td>
          <td><?= e($log['creator_name'] ?? '-') ?></td>
          <td><a href="view.php?id=<?= (int)$log['id'] ?>" class="btn btn-sm btn-outline-primary">Xem</a></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include __DIR__ . '/../../includes/layout_end.php'; ?>
