<?php
declare(strict_types=1);
require_once __DIR__ . '/../../../includes/db.php';
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/functions.php';

$user = require_login();
$pageTitle = 'Chan doan ao khach hang';
$activeMenu = 'htkt-chandoan';

$status = $_GET['status'] ?? '';
$category = $_GET['category'] ?? '';

$sql = 'SELECT dr.*, u.name AS creator_name FROM diagnosis_requests dr LEFT JOIN users u ON u.id = dr.created_by WHERE 1=1';
$params = [];

if ($user['role'] === 'sale') {
    $sql .= ' AND dr.created_by = ?';
    $params[] = $user['id'];
}
if ($status !== '' && array_key_exists($status, ['moi'=>1,'dang_xu_ly'=>1,'da_tu_van'=>1,'hoan_thanh'=>1])) {
    $sql .= ' AND dr.status = ?';
    $params[] = $status;
}
if ($category !== '' && in_array($category, all_categories(), true)) {
    $sql .= ' AND dr.problem_category = ?';
    $params[] = $category;
}
$sql .= ' ORDER BY dr.created_at DESC';

$stmt = db()->prepare($sql);
$stmt->execute($params);
$requests = $stmt->fetchAll();

include __DIR__ . '/../../../includes/layout_start.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
  <h5 class="mb-0">Chan doan / ho tro ky thuat tai ao</h5>
  <div class="d-flex gap-2">
    <a href="export_excel.php?status=<?= e($status) ?>&category=<?= e($category) ?>" class="btn btn-outline-secondary btn-sm">Xuat Excel</a>
    <a href="form.php" class="btn btn-primary btn-sm">+ Tao yeu cau chan doan</a>
  </div>
</div>

<form method="get" class="row g-2 mb-3 filter-bar">
  <div class="col-auto">
    <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
      <option value="">Tat ca trang thai</option>
      <?php foreach (['moi','dang_xu_ly','da_tu_van','hoan_thanh'] as $s): ?>
        <option value="<?= e($s) ?>" <?= $s === $status ? 'selected' : '' ?>><?= e(status_label($s)) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-auto">
    <select name="category" class="form-select form-select-sm" onchange="this.form.submit()">
      <option value="">Tat ca van de</option>
      <?php foreach (all_categories() as $c): ?>
        <option value="<?= e($c) ?>" <?= $c === $category ? 'selected' : '' ?>><?= e(category_label($c)) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
</form>

<div class="card border-0 shadow-sm">
  <div class="table-responsive">
    <table class="table mb-0 align-middle">
      <thead class="table-light">
        <tr>
          <th>Khach hang</th>
          <th>Dai ly / khu vuc</th>
          <th>Dien tich ao</th>
          <th>Van de</th>
          <th>Trang thai</th>
          <th>Nguoi tao</th>
          <th>Ngay tao</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$requests): ?>
          <tr><td colspan="8" class="text-center text-muted py-4">Chua co yeu cau nao.</td></tr>
        <?php endif; ?>
        <?php foreach ($requests as $row): ?>
        <tr>
          <td><?= e($row['customer_name']) ?></td>
          <td><?= e($row['agent_name'] ?? '') ?> <?= $row['location'] ? '('.e($row['location']).')' : '' ?></td>
          <td><?= $row['pond_area'] ? e((string)$row['pond_area']) . ' m2' : '-' ?></td>
          <td><span class="tag <?= e(category_tag_class($row['problem_category'])) ?>"><?= e(category_label($row['problem_category'])) ?></span></td>
          <td><span class="<?= status_badge_class($row['status']) ?>"><span class="dot <?= status_dot_class($row['status']) ?>"></span><?= e(status_label($row['status'])) ?></span></td>
          <td><?= e($row['creator_name'] ?? '-') ?></td>
          <td><?= e($row['created_at']) ?></td>
          <td><a href="view.php?id=<?= (int)$row['id'] ?>" class="btn btn-sm btn-outline-primary">Xem</a></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include __DIR__ . '/../../../includes/layout_end.php'; ?>
