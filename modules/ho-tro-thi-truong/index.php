<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

$user = require_login();
$pageTitle = 'Ho tro thi truong';
$activeMenu = 'httt';

$type = $_GET['type'] ?? '';
$validTypes = ['thuyet_trinh_demo', 'tham_ao_dinh_ky', 'chuyen_giao_cong_nghe'];

$sql = 'SELECT v.*, u.name AS creator_name FROM market_visits v LEFT JOIN users u ON u.id = v.created_by WHERE 1=1';
$params = [];
if (in_array($type, $validTypes, true)) {
    $sql .= ' AND v.visit_type = ?';
    $params[] = $type;
}
$sql .= ' ORDER BY v.visit_date DESC, v.id DESC';

$stmt = db()->prepare($sql);
$stmt->execute($params);
$visits = $stmt->fetchAll();

include __DIR__ . '/../../includes/layout_start.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
  <div>
    <h5 class="mb-1">Ho tro thi truong</h5>
    <p class="text-muted small mb-0">Thuyet trinh/demo san pham &middot; Tham ao dinh ky &middot; Chuyen giao cong nghe (RD phoi hop cung ky thuat thi truong)</p>
  </div>
  <a href="form.php" class="btn btn-primary btn-sm">+ Ghi nhan chuyen di</a>
</div>

<form method="get" class="row g-2 mb-3 filter-bar">
  <div class="col-auto">
    <select name="type" class="form-select form-select-sm" onchange="this.form.submit()">
      <option value="">Tat ca loai hoat dong</option>
      <?php foreach ($validTypes as $t): ?>
        <option value="<?= e($t) ?>" <?= $t === $type ? 'selected' : '' ?>><?= e(market_visit_type_label($t)) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
</form>

<div class="card border-0 shadow-sm">
  <div class="table-responsive">
    <table class="table mb-0 align-middle">
      <thead class="table-light">
        <tr><th>Ngay</th><th>Loai hoat dong</th><th>Khach hang / dai ly</th><th>Nguoi thuc hien</th><th></th></tr>
      </thead>
      <tbody>
        <?php if (!$visits): ?><tr><td colspan="5" class="text-center text-muted py-4">Chua co chuyen di nao.</td></tr><?php endif; ?>
        <?php foreach ($visits as $v): ?>
        <tr>
          <td><?= e($v['visit_date']) ?></td>
          <td><span class="tag <?= e(market_visit_type_tag_class($v['visit_type'])) ?>"><?= e(market_visit_type_label($v['visit_type'])) ?></span></td>
          <td><?= e($v['customer_name'] ?? $v['agent_name'] ?? '-') ?></td>
          <td><?= e($v['creator_name'] ?? '-') ?> <?php if ($v['participants']): ?><span class="text-muted small">+ <?= e($v['participants']) ?></span><?php endif; ?></td>
          <td><a href="view.php?id=<?= (int)$v['id'] ?>" class="btn btn-sm btn-outline-primary">Xem</a></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include __DIR__ . '/../../includes/layout_end.php'; ?>
