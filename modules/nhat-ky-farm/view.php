<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

$user = require_login();
$activeMenu = 'nhat-ky-farm';

$id = (int)($_GET['id'] ?? 0);
$stmt = db()->prepare(
    'SELECT l.*, p.name AS pond_name, u.name AS creator_name FROM farm_logs l
     JOIN farm_ponds p ON p.id = l.pond_id
     LEFT JOIN users u ON u.id = l.created_by WHERE l.id = ?'
);
$stmt->execute([$id]);
$log = $stmt->fetch();

if (!$log) {
    flash_set('danger', 'Khong tim thay nhat ky.');
    redirect('/modules/nhat-ky-farm/index.php');
}

$stmt = db()->prepare('SELECT * FROM farm_log_indicators WHERE log_id = ?');
$stmt->execute([$id]);
$indicators = $stmt->fetchAll();

$stmt = db()->prepare('SELECT fp.*, pr.name AS product_name FROM farm_log_products fp JOIN products pr ON pr.id = fp.product_id WHERE fp.log_id = ?');
$stmt->execute([$id]);
$logProducts = $stmt->fetchAll();

$stmt = db()->prepare('SELECT * FROM farm_log_images WHERE log_id = ? ORDER BY id DESC');
$stmt->execute([$id]);
$images = $stmt->fetchAll();

$pageTitle = 'Nhat ky ' . $log['pond_name'] . ' - ' . $log['log_date'];
include __DIR__ . '/../../includes/layout_start.php';
?>

<div class="d-flex justify-content-between align-items-start mb-3">
  <div>
    <h4><?= e($log['pond_name']) ?> <span class="text-muted small">- <?= e($log['log_date']) ?></span></h4>
    <small class="text-muted">Nguoi ghi: <?= e($log['creator_name'] ?? '-') ?></small>
  </div>
  <?php if (in_array($user['role'], ['rd', 'manager'], true)): ?>
    <form method="post" action="delete.php" onsubmit="return confirm('Xoa nhat ky nay?')">
      <?= csrf_field() ?>
      <input type="hidden" name="id" value="<?= $id ?>">
      <button class="btn btn-sm btn-outline-danger">Xoa</button>
    </form>
  <?php endif; ?>
</div>

<div class="card border-0 shadow-sm mb-3">
  <div class="card-body row g-3">
    <div class="col-md-4"><div class="text-muted small">Luong cho an</div><div class="fw-semibold"><?= e($log['feed_amount'] ?? '-') ?></div></div>
    <div class="col-12"><div class="text-muted small">Ghi chu</div><div><?= nl2br(e($log['note'] ?? 'Khong co ghi chu.')) ?></div></div>
  </div>
</div>

<div class="card border-0 shadow-sm mb-3">
  <div class="card-header bg-white fw-bold">Chi tieu moi truong</div>
  <div class="card-body">
    <?php if (!$indicators): ?><p class="text-muted mb-0">Chua co chi tieu nao.</p><?php endif; ?>
    <div class="row g-2">
      <?php foreach ($indicators as $ind): ?>
        <div class="col-md-3">
          <div class="border rounded p-2">
            <div class="small text-muted"><?= e($ind['indicator_name']) ?></div>
            <div class="fw-semibold"><?= e($ind['indicator_value']) ?> <?= e($ind['unit'] ?? '') ?></div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<div class="card border-0 shadow-sm mb-3">
  <div class="card-header bg-white fw-bold">San pham da su dung</div>
  <ul class="list-group list-group-flush">
    <?php if (!$logProducts): ?><li class="list-group-item text-muted">Chua ghi nhan.</li><?php endif; ?>
    <?php foreach ($logProducts as $p): ?>
      <li class="list-group-item"><?= e($p['product_name']) ?> <?php if ($p['dosage']): ?><span class="text-muted small">- <?= e($p['dosage']) ?></span><?php endif; ?></li>
    <?php endforeach; ?>
  </ul>
</div>

<?php if ($images): ?>
<div class="card border-0 shadow-sm mb-3">
  <div class="card-header bg-white fw-bold">Hinh anh</div>
  <div class="card-body d-flex flex-wrap gap-2">
    <?php foreach ($images as $img): ?>
      <a href="<?= e(base_url() . '/' . $img['file_path']) ?>" target="_blank">
        <img src="<?= e(base_url() . '/' . $img['file_path']) ?>" style="width:140px;height:140px;object-fit:cover;border-radius:6px;">
      </a>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../../includes/layout_end.php'; ?>
