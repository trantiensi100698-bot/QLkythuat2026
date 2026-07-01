<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

$user = require_login();
$activeMenu = 'rnd';

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
$truoc = array_filter($measurements, fn($m) => $m['stage'] === 'truoc');
$sau = array_filter($measurements, fn($m) => $m['stage'] === 'sau');

$stmt = db()->prepare('SELECT rp.*, pr.name AS product_name FROM rd_experiment_products rp JOIN products pr ON pr.id = rp.product_id WHERE rp.experiment_id = ?');
$stmt->execute([$id]);
$linkedProducts = $stmt->fetchAll();
$totalCost = array_sum(array_map(fn($p) => (float)($p['cost'] ?? 0), $linkedProducts));

$stmt = db()->prepare('SELECT * FROM rd_experiment_images WHERE experiment_id = ? ORDER BY id DESC');
$stmt->execute([$id]);
$images = $stmt->fetchAll();

$stmt = db()->prepare('SELECT * FROM rd_experiment_files WHERE experiment_id = ? ORDER BY id DESC');
$stmt->execute([$id]);
$files = $stmt->fetchAll();

$pageTitle = $experiment['title'];
include __DIR__ . '/../../includes/layout_start.php';
?>

<div class="d-flex justify-content-between align-items-start mb-3">
  <div>
    <span class="tag tag-teal mb-2 d-inline-block"><?= e(rd_category_label($experiment['category'])) ?></span>
    <h4><?= e($experiment['title']) ?> <span class="<?= rd_status_badge_class($experiment['status']) ?>"><?= e(rd_status_label($experiment['status'])) ?></span></h4>
    <small class="text-muted">
      <?= e($experiment['start_date'] ?? '?') ?> &rarr; <?= e($experiment['end_date'] ?? '?') ?>
      &middot; Phu trach: <?= e($experiment['creator_name'] ?? '-') ?>
    </small>
  </div>
  <div class="text-nowrap">
    <a href="export_word.php?id=<?= $id ?>" class="btn btn-sm btn-outline-primary">Xuat bao cao Word</a>
  <?php if (in_array($user['role'], ['rd', 'manager'], true)): ?>
      <a href="form.php?id=<?= $id ?>" class="btn btn-sm btn-outline-secondary">Sua</a>
      <form method="post" action="delete.php" class="d-inline" onsubmit="return confirm('Xoa thi nghiem nay?')">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= $id ?>">
        <button class="btn btn-sm btn-outline-danger">Xoa</button>
      </form>
  <?php endif; ?>
  </div>
</div>

<div class="card border-0 shadow-sm mb-3">
  <div class="card-header bg-white fw-bold">Muc tieu</div>
  <div class="card-body"><?= nl2br(e($experiment['objective'] ?? 'Chua co mo ta.')) ?></div>
</div>

<div class="row g-3 mb-3">
  <div class="col-md-6">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-header bg-white fw-bold">Chi tieu truoc khi dung san pham</div>
      <ul class="list-group list-group-flush">
        <?php if (!$truoc): ?><li class="list-group-item text-muted">Chua co so lieu.</li><?php endif; ?>
        <?php foreach ($truoc as $m): ?>
          <li class="list-group-item d-flex justify-content-between">
            <span><?= e($m['indicator_name']) ?> <?= $m['measured_at'] ? '('.e($m['measured_at']).')' : '' ?></span>
            <strong><?= e($m['indicator_value']) ?> <?= e($m['unit'] ?? '') ?></strong>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
  <div class="col-md-6">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-header bg-white fw-bold">Chi tieu sau khi dung san pham</div>
      <ul class="list-group list-group-flush">
        <?php if (!$sau): ?><li class="list-group-item text-muted">Chua co so lieu.</li><?php endif; ?>
        <?php foreach ($sau as $m): ?>
          <li class="list-group-item d-flex justify-content-between">
            <span><?= e($m['indicator_name']) ?> <?= $m['measured_at'] ? '('.e($m['measured_at']).')' : '' ?></span>
            <strong><?= e($m['indicator_value']) ?> <?= e($m['unit'] ?? '') ?></strong>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
</div>

<div class="card border-0 shadow-sm mb-3">
  <div class="card-header bg-white fw-bold d-flex justify-content-between">
    <span>San pham su dung &amp; chi phi</span>
    <?php if ($totalCost > 0): ?><span class="text-muted small">Tong chi phi: <?= number_format($totalCost, 0, ',', '.') ?> VND</span><?php endif; ?>
  </div>
  <div class="table-responsive">
    <table class="table mb-0 align-middle">
      <thead class="table-light"><tr><th>San pham</th><th>Lieu dung</th><th>Chi phi</th><th>Ghi chu</th></tr></thead>
      <tbody>
        <?php if (!$linkedProducts): ?><tr><td colspan="4" class="text-center text-muted py-3">Chua co san pham nao.</td></tr><?php endif; ?>
        <?php foreach ($linkedProducts as $p): ?>
          <tr>
            <td><?= e($p['product_name']) ?></td>
            <td><?= e($p['dosage'] ?? '-') ?></td>
            <td><?= $p['cost'] !== null ? number_format((float)$p['cost'], 0, ',', '.') . ' VND' : '-' ?></td>
            <td class="small text-muted"><?= e($p['note'] ?? '') ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="row g-3 mb-3">
  <div class="col-md-6">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-header bg-white fw-bold">Uu diem</div>
      <div class="card-body"><?= nl2br(e($experiment['findings_pros'] ?? 'Chua co danh gia.')) ?></div>
    </div>
  </div>
  <div class="col-md-6">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-header bg-white fw-bold">Nhuoc diem</div>
      <div class="card-body"><?= nl2br(e($experiment['findings_cons'] ?? 'Chua co danh gia.')) ?></div>
    </div>
  </div>
</div>

<?php if ($experiment['cost_analysis']): ?>
<div class="card border-0 shadow-sm mb-3">
  <div class="card-header bg-white fw-bold">Phan tich chi phi</div>
  <div class="card-body"><?= nl2br(e($experiment['cost_analysis'])) ?></div>
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
  <div class="card-header bg-white fw-bold">File bao cao (Word / PPT / Excel / PDF)</div>
  <ul class="list-group list-group-flush">
    <?php foreach ($files as $f): ?>
      <li class="list-group-item"><a href="<?= e(base_url() . '/' . $f['file_path']) ?>" target="_blank">&#128206; <?= e($f['original_name']) ?></a></li>
    <?php endforeach; ?>
  </ul>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../../includes/layout_end.php'; ?>
