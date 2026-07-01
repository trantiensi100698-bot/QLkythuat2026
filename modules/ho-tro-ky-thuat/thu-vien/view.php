<?php
declare(strict_types=1);
require_once __DIR__ . '/../../../includes/db.php';
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/functions.php';

$user = require_login();
$activeMenu = 'htkt-thuvien';

$id = (int)($_GET['id'] ?? 0);
$stmt = db()->prepare('SELECT p.*, u.name AS creator_name FROM procedures p LEFT JOIN users u ON u.id = p.created_by WHERE p.id = ?');
$stmt->execute([$id]);
$procedure = $stmt->fetch();

if (!$procedure) {
    flash_set('danger', 'Khong tim thay quy trinh.');
    redirect('/modules/ho-tro-ky-thuat/thu-vien/index.php');
}

$stmt = db()->prepare('SELECT pp.*, pr.name AS product_name, pr.unit FROM procedure_products pp JOIN products pr ON pr.id = pp.product_id WHERE pp.procedure_id = ?');
$stmt->execute([$id]);
$linkedProducts = $stmt->fetchAll();

$stmt = db()->prepare('SELECT * FROM procedure_images WHERE procedure_id = ? ORDER BY id DESC');
$stmt->execute([$id]);
$images = $stmt->fetchAll();

$pageTitle = $procedure['title'];
include __DIR__ . '/../../../includes/layout_start.php';
?>

<div class="d-flex justify-content-between align-items-start mb-3">
  <div>
    <span class="tag <?= e(category_tag_class($procedure['category'])) ?> mb-2 d-inline-block"><?= e(category_label($procedure['category'])) ?></span>
    <h4><?= e($procedure['title']) ?></h4>
    <p class="text-muted"><?= e($procedure['summary'] ?? '') ?></p>
    <small class="text-muted">Tao boi <?= e($procedure['creator_name'] ?? '-') ?> luc <?= e($procedure['created_at']) ?></small>
  </div>
  <?php if (in_array($user['role'], ['rd', 'manager'], true)): ?>
    <div class="text-nowrap">
      <a href="form.php?id=<?= $id ?>" class="btn btn-sm btn-outline-secondary">Sua</a>
      <form method="post" action="delete.php" class="d-inline" onsubmit="return confirm('Xoa quy trinh nay?')">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= $id ?>">
        <button class="btn btn-sm btn-outline-danger">Xoa</button>
      </form>
    </div>
  <?php endif; ?>
</div>

<div class="row g-3">
  <div class="col-md-8">
    <div class="card border-0 shadow-sm mb-3">
      <div class="card-header bg-white fw-bold">Cac buoc thuc hien</div>
      <div class="card-body">
        <pre style="white-space: pre-wrap; font-family: inherit;"><?= e($procedure['steps']) ?></pre>
      </div>
    </div>

    <?php if ($images): ?>
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white fw-bold">Hinh anh minh hoa</div>
      <div class="card-body d-flex flex-wrap gap-2">
        <?php foreach ($images as $img): ?>
          <a href="<?= e(base_url() . '/' . $img['file_path']) ?>" target="_blank">
            <img src="<?= e(base_url() . '/' . $img['file_path']) ?>" style="width:140px;height:140px;object-fit:cover;border-radius:6px;">
          </a>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>

  <div class="col-md-4">
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white fw-bold">Cong thuc ket hop san pham</div>
      <ul class="list-group list-group-flush">
        <?php if (!$linkedProducts): ?>
          <li class="list-group-item text-muted">Chua gan san pham nao.</li>
        <?php endif; ?>
        <?php foreach ($linkedProducts as $lp): ?>
          <li class="list-group-item">
            <div class="fw-semibold"><?= e($lp['product_name']) ?></div>
            <?php if ($lp['dosage']): ?><div class="small text-muted">Lieu dung: <?= e($lp['dosage']) ?></div><?php endif; ?>
            <?php if ($lp['note']): ?><div class="small text-muted"><?= e($lp['note']) ?></div><?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../../../includes/layout_end.php'; ?>
