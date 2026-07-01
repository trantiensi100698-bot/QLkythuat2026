<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

$user = require_login();
$pageTitle = 'Du an R&D';
$activeMenu = 'rnd';

$status = $_GET['status'] ?? '';
$sql = 'SELECT e.*, u.name AS creator_name FROM rd_experiments e LEFT JOIN users u ON u.id = e.created_by WHERE 1=1';
$params = [];
if (in_array($status, ['dang_thuc_hien','hoan_thanh','tam_dung'], true)) {
    $sql .= ' AND e.status = ?';
    $params[] = $status;
}
$sql .= ' ORDER BY e.created_at DESC';

$stmt = db()->prepare($sql);
$stmt->execute($params);
$experiments = $stmt->fetchAll();

include __DIR__ . '/../../includes/layout_start.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
  <h5 class="mb-0">Du an R&amp;D - Thuy san</h5>
  <?php if (in_array($user['role'], ['rd', 'manager'], true)): ?>
    <a href="form.php" class="btn btn-primary btn-sm">+ Tao thi nghiem moi</a>
  <?php endif; ?>
</div>

<form method="get" class="row g-2 mb-3 filter-bar">
  <div class="col-auto">
    <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
      <option value="">Tat ca trang thai</option>
      <?php foreach (['dang_thuc_hien','hoan_thanh','tam_dung'] as $s): ?>
        <option value="<?= e($s) ?>" <?= $s === $status ? 'selected' : '' ?>><?= e(rd_status_label($s)) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
</form>

<div class="card border-0 shadow-sm">
  <div class="table-responsive">
    <table class="table mb-0 align-middle">
      <thead class="table-light">
        <tr>
          <th>Ten thi nghiem</th>
          <th>Nhom</th>
          <th>Thoi gian</th>
          <th>Trang thai</th>
          <th>Nguoi phu trach</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$experiments): ?>
          <tr><td colspan="6" class="text-center text-muted py-4">Chua co thi nghiem nao.</td></tr>
        <?php endif; ?>
        <?php foreach ($experiments as $exp): ?>
        <tr>
          <td><?= e($exp['title']) ?></td>
          <td><span class="tag tag-teal"><?= e(rd_category_label($exp['category'])) ?></span></td>
          <td class="small text-muted"><?= e($exp['start_date'] ?? '?') ?> &rarr; <?= e($exp['end_date'] ?? '?') ?></td>
          <td><span class="<?= rd_status_badge_class($exp['status']) ?>"><?= e(rd_status_label($exp['status'])) ?></span></td>
          <td><?= e($exp['creator_name'] ?? '-') ?></td>
          <td><a href="view.php?id=<?= (int)$exp['id'] ?>" class="btn btn-sm btn-outline-primary">Xem</a></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include __DIR__ . '/../../includes/layout_end.php'; ?>
