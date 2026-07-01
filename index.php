<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$user = require_login();
$pageTitle = 'Trang chu';
$activeMenu = 'dashboard';

$procedureCount = (int) db()->query('SELECT COUNT(*) c FROM procedures')->fetch()['c'];
$openDiagnosis = (int) db()->query("SELECT COUNT(*) c FROM diagnosis_requests WHERE status IN ('moi','dang_xu_ly')")->fetch()['c'];
$doneDiagnosis = (int) db()->query("SELECT COUNT(*) c FROM diagnosis_requests WHERE status IN ('da_tu_van','hoan_thanh')")->fetch()['c'];

$recentStmt = db()->query(
    'SELECT dr.*, u.name AS creator_name FROM diagnosis_requests dr
     LEFT JOIN users u ON u.id = dr.created_by
     ORDER BY dr.created_at DESC LIMIT 5'
);
$recent = $recentStmt->fetchAll();

include __DIR__ . '/includes/layout_start.php';
?>

<div class="row g-3 mb-4">
  <div class="col-md-4">
    <div class="card border-0 shadow-sm">
      <div class="card-body">
        <div class="text-muted small">Cong thuc / quy trinh trong thu vien</div>
        <div class="fs-2 fw-bold"><?= $procedureCount ?></div>
        <a href="<?= e(base_url()) ?>/modules/ho-tro-ky-thuat/thu-vien/index.php" class="small">Xem thu vien &raquo;</a>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card border-0 shadow-sm">
      <div class="card-body">
        <div class="text-muted small">Ca chan doan dang mo / dang xu ly</div>
        <div class="fs-2 fw-bold text-warning"><?= $openDiagnosis ?></div>
        <a href="<?= e(base_url()) ?>/modules/ho-tro-ky-thuat/chan-doan/index.php" class="small">Xem danh sach &raquo;</a>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card border-0 shadow-sm">
      <div class="card-body">
        <div class="text-muted small">Ca da tu van / hoan thanh</div>
        <div class="fs-2 fw-bold text-success"><?= $doneDiagnosis ?></div>
      </div>
    </div>
  </div>
</div>

<div class="card border-0 shadow-sm">
  <div class="card-header bg-white fw-bold">Chan doan ao gan day</div>
  <div class="table-responsive">
    <table class="table mb-0 align-middle">
      <thead class="table-light">
        <tr>
          <th>Khach hang</th>
          <th>Van de</th>
          <th>Trang thai</th>
          <th>Nguoi tao</th>
          <th>Ngay tao</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$recent): ?>
          <tr><td colspan="6" class="text-center text-muted py-4">Chua co ca chan doan nao.</td></tr>
        <?php endif; ?>
        <?php foreach ($recent as $row): ?>
        <tr>
          <td><?= e($row['customer_name']) ?></td>
          <td><?= e(category_label($row['problem_category'])) ?></td>
          <td><span class="badge <?= status_badge_class($row['status']) ?>"><?= e(status_label($row['status'])) ?></span></td>
          <td><?= e($row['creator_name'] ?? '-') ?></td>
          <td><?= e($row['created_at']) ?></td>
          <td><a href="<?= e(base_url()) ?>/modules/ho-tro-ky-thuat/chan-doan/view.php?id=<?= (int)$row['id'] ?>" class="btn btn-sm btn-outline-primary">Xem</a></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include __DIR__ . '/includes/layout_end.php'; ?>
