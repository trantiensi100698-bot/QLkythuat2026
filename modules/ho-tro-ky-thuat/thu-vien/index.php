<?php
declare(strict_types=1);
require_once __DIR__ . '/../../../includes/db.php';
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/functions.php';

$user = require_login();
$pageTitle = 'Thu vien cong thuc / quy trinh xu ly';
$activeMenu = 'htkt-thuvien';

$category = $_GET['category'] ?? '';
$search = trim((string)($_GET['q'] ?? ''));

$sql = 'SELECT p.*, u.name AS creator_name FROM procedures p LEFT JOIN users u ON u.id = p.created_by WHERE 1=1';
$params = [];
if ($category !== '' && in_array($category, all_categories(), true)) {
    $sql .= ' AND p.category = ?';
    $params[] = $category;
}
if ($search !== '') {
    $sql .= ' AND (p.title LIKE ? OR p.summary LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
}
$sql .= ' ORDER BY p.updated_at DESC';

$stmt = db()->prepare($sql);
$stmt->execute($params);
$procedures = $stmt->fetchAll();

include __DIR__ . '/../../../includes/layout_start.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
  <h5 class="mb-0">Thu vien cong thuc ket hop san pham &amp; quy trinh xu ly</h5>
  <?php if (in_array($user['role'], ['rd', 'manager'], true)): ?>
    <a href="form.php" class="btn btn-primary btn-sm">+ Tao quy trinh moi</a>
  <?php endif; ?>
</div>

<form method="get" class="row g-2 mb-3">
  <div class="col-auto">
    <select name="category" class="form-select form-select-sm" onchange="this.form.submit()">
      <option value="">Tat ca danh muc</option>
      <?php foreach (all_categories() as $c): ?>
        <option value="<?= e($c) ?>" <?= $c === $category ? 'selected' : '' ?>><?= e(category_label($c)) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-auto">
    <input type="text" name="q" value="<?= e($search) ?>" class="form-control form-control-sm" placeholder="Tim theo tieu de...">
  </div>
  <div class="col-auto">
    <button class="btn btn-sm btn-outline-secondary">Loc</button>
  </div>
</form>

<div class="row g-3">
  <?php if (!$procedures): ?>
    <div class="col-12 text-center text-muted py-5">Chua co quy trinh nao phu hop.</div>
  <?php endif; ?>
  <?php foreach ($procedures as $proc): ?>
    <div class="col-md-4">
      <div class="card h-100 border-0 shadow-sm">
        <div class="card-body">
          <span class="badge bg-primary-subtle text-primary-emphasis mb-2"><?= e(category_label($proc['category'])) ?></span>
          <h6 class="card-title"><?= e($proc['title']) ?></h6>
          <p class="card-text text-muted small"><?= e($proc['summary'] ?? '') ?></p>
        </div>
        <div class="card-footer bg-white border-0 d-flex justify-content-between align-items-center">
          <small class="text-muted"><?= e($proc['creator_name'] ?? '-') ?></small>
          <a href="view.php?id=<?= (int)$proc['id'] ?>" class="btn btn-sm btn-outline-primary">Xem chi tiet</a>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<?php include __DIR__ . '/../../../includes/layout_end.php'; ?>
