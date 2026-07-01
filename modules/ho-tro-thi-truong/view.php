<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

$user = require_login();
$activeMenu = 'httt';

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

$stmt = db()->prepare('SELECT * FROM market_visit_images WHERE visit_id = ? ORDER BY id DESC');
$stmt->execute([$id]);
$images = $stmt->fetchAll();

$stmt = db()->prepare('SELECT * FROM market_visit_files WHERE visit_id = ? ORDER BY id DESC');
$stmt->execute([$id]);
$files = $stmt->fetchAll();

$pageTitle = market_visit_type_label($visit['visit_type']);
include __DIR__ . '/../../includes/layout_start.php';
?>

<div class="d-flex justify-content-between align-items-start mb-3">
  <div>
    <span class="tag <?= e(market_visit_type_tag_class($visit['visit_type'])) ?> mb-2 d-inline-block"><?= e(market_visit_type_label($visit['visit_type'])) ?></span>
    <h4><?= e($visit['customer_name'] ?? $visit['agent_name'] ?? 'Chuyen di') ?></h4>
    <small class="text-muted">
      <?= e($visit['visit_date']) ?> &middot; <?= e($visit['location'] ?? '') ?>
      &middot; Thuc hien: <?= e($visit['creator_name'] ?? '-') ?><?php if ($visit['participants']): ?> + <?= e($visit['participants']) ?><?php endif; ?>
    </small>
  </div>
  <div class="text-nowrap">
    <a href="export_word.php?id=<?= $id ?>" class="btn btn-sm btn-outline-primary">Xuat bao cao Word</a>
    <?php if (in_array($user['role'], ['rd', 'manager'], true)): ?>
      <form method="post" action="delete.php" class="d-inline" onsubmit="return confirm('Xoa chuyen di nay?')">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= $id ?>">
        <button class="btn btn-sm btn-outline-danger">Xoa</button>
      </form>
    <?php endif; ?>
  </div>
</div>

<div class="card border-0 shadow-sm mb-3">
  <div class="card-header bg-white fw-bold">Noi dung chuyen di</div>
  <div class="card-body"><?= nl2br(e($visit['content'] ?? 'Chua co mo ta.')) ?></div>
</div>

<div class="card border-0 shadow-sm mb-3">
  <div class="card-header bg-white fw-bold">Phan hoi cua khach hang</div>
  <div class="card-body"><?= nl2br(e($visit['customer_feedback'] ?? 'Chua co phan hoi.')) ?></div>
</div>

<?php if ($samples): ?>
<div class="card border-0 shadow-sm mb-3">
  <div class="card-header bg-white fw-bold">Mau kiem tra tai ao</div>
  <ul class="list-group list-group-flush">
    <?php foreach ($samples as $s): ?>
      <li class="list-group-item"><strong><?= e($s['sample_type']) ?>:</strong> <?= e($s['result_description'] ?? '') ?></li>
    <?php endforeach; ?>
  </ul>
</div>
<?php endif; ?>

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

<?php if ($files): ?>
<div class="card border-0 shadow-sm mb-3">
  <div class="card-header bg-white fw-bold">File bao cao (gui Ms Tu Anh)</div>
  <ul class="list-group list-group-flush">
    <?php foreach ($files as $f): ?>
      <li class="list-group-item"><a href="<?= e(base_url() . '/' . $f['file_path']) ?>" target="_blank">&#128206; <?= e($f['original_name']) ?></a></li>
    <?php endforeach; ?>
  </ul>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../../includes/layout_end.php'; ?>
