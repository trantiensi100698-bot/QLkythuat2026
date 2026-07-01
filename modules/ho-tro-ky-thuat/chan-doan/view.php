<?php
declare(strict_types=1);
require_once __DIR__ . '/../../../includes/db.php';
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/functions.php';

$user = require_login();
$activeMenu = 'htkt-chandoan';

$id = (int)($_GET['id'] ?? 0);
$stmt = db()->prepare('SELECT dr.*, u.name AS creator_name FROM diagnosis_requests dr LEFT JOIN users u ON u.id = dr.created_by WHERE dr.id = ?');
$stmt->execute([$id]);
$request = $stmt->fetch();

if (!$request) {
    flash_set('danger', 'Khong tim thay yeu cau.');
    redirect('/modules/ho-tro-ky-thuat/chan-doan/index.php');
}

if ($user['role'] === 'sale' && (int)$request['created_by'] !== (int)$user['id']) {
    http_response_code(403);
    die('Ban khong co quyen xem yeu cau nay.');
}

$canAdvise = in_array($user['role'], ['rd', 'manager'], true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'comment') {
        $comment = trim((string)($_POST['comment'] ?? ''));
        if ($comment !== '') {
            $stmt = db()->prepare('INSERT INTO diagnosis_comments (request_id, user_id, comment) VALUES (?,?,?)');
            $stmt->execute([$id, $user['id'], $comment]);
            flash_set('success', 'Da gui phan hoi.');
        }
    } elseif ($action === 'recommend' && $canAdvise) {
        $procedureId = (int)($_POST['procedure_id'] ?? 0);
        $note = trim((string)($_POST['note'] ?? ''));
        $stmt = db()->prepare('INSERT INTO diagnosis_recommendations (request_id, procedure_id, note, created_by) VALUES (?,?,?,?)');
        $stmt->execute([$id, $procedureId ?: null, $note ?: null, $user['id']]);
        db()->prepare("UPDATE diagnosis_requests SET status = 'da_tu_van' WHERE id = ? AND status IN ('moi','dang_xu_ly')")->execute([$id]);
        flash_set('success', 'Da them tu van cho khach hang.');
    } elseif ($action === 'status' && $canAdvise) {
        $newStatus = (string)($_POST['status'] ?? '');
        if (in_array($newStatus, ['moi','dang_xu_ly','da_tu_van','hoan_thanh'], true)) {
            db()->prepare('UPDATE diagnosis_requests SET status = ? WHERE id = ?')->execute([$newStatus, $id]);
            flash_set('success', 'Da cap nhat trang thai.');
        }
    }
    redirect('/modules/ho-tro-ky-thuat/chan-doan/view.php?id=' . $id);
}

$indicators = db()->prepare('SELECT * FROM diagnosis_indicators WHERE request_id = ?');
$indicators->execute([$id]);
$indicators = $indicators->fetchAll();

$images = db()->prepare('SELECT * FROM diagnosis_images WHERE request_id = ? ORDER BY id DESC');
$images->execute([$id]);
$images = $images->fetchAll();

$recommendations = db()->prepare(
    'SELECT rr.*, p.title AS procedure_title, u.name AS advisor_name
     FROM diagnosis_recommendations rr
     LEFT JOIN procedures p ON p.id = rr.procedure_id
     LEFT JOIN users u ON u.id = rr.created_by
     WHERE rr.request_id = ? ORDER BY rr.created_at ASC'
);
$recommendations->execute([$id]);
$recommendations = $recommendations->fetchAll();

$comments = db()->prepare(
    'SELECT c.*, u.name AS user_name FROM diagnosis_comments c LEFT JOIN users u ON u.id = c.user_id WHERE c.request_id = ? ORDER BY c.created_at ASC'
);
$comments->execute([$id]);
$comments = $comments->fetchAll();

$suggestedProcedures = db()->prepare('SELECT id, title FROM procedures WHERE category = ? ORDER BY title ASC');
$suggestedProcedures->execute([$request['problem_category']]);
$suggestedProcedures = $suggestedProcedures->fetchAll();

$pageTitle = 'Chan doan: ' . $request['customer_name'];
include __DIR__ . '/../../../includes/layout_start.php';
?>

<div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
  <div>
    <h4><?= e($request['customer_name']) ?> <span class="<?= status_badge_class($request['status']) ?>"><span class="dot <?= status_dot_class($request['status']) ?>"></span><?= e(status_label($request['status'])) ?></span></h4>
    <div class="text-muted small">
      <?= e($request['agent_name'] ?? '') ?> <?= $request['location'] ? '- '.e($request['location']) : '' ?>
      &middot; Van de: <?= e(category_label($request['problem_category'])) ?>
      &middot; Tao boi <?= e($request['creator_name'] ?? '-') ?> luc <?= e($request['created_at']) ?>
    </div>
  </div>
  <?php if ($canAdvise): ?>
  <form method="post" class="d-flex gap-2">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="status">
    <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
      <?php foreach (['moi','dang_xu_ly','da_tu_van','hoan_thanh'] as $s): ?>
        <option value="<?= e($s) ?>" <?= $s === $request['status'] ? 'selected' : '' ?>><?= e(status_label($s)) ?></option>
      <?php endforeach; ?>
    </select>
  </form>
  <?php endif; ?>
</div>

<div class="row g-3">
  <div class="col-md-8">
    <div class="card border-0 shadow-sm mb-3">
      <div class="card-header bg-white fw-bold">Mo ta tinh trang</div>
      <div class="card-body">
        <p><?= nl2br(e($request['description'] ?? 'Khong co mo ta.')) ?></p>
        <?php if ($request['pond_area'] || $request['pond_stage']): ?>
          <div class="text-muted small">
            <?php if ($request['pond_area']): ?>Dien tich ao: <?= e((string)$request['pond_area']) ?> m2<br><?php endif; ?>
            <?php if ($request['pond_stage']): ?>Giai doan nuoi: <?= e($request['pond_stage']) ?><?php endif; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <div class="card border-0 shadow-sm mb-3">
      <div class="card-header bg-white fw-bold">Chi tieu moi truong</div>
      <div class="card-body">
        <?php if (!$indicators): ?>
          <p class="text-muted mb-0">Chua co chi tieu nao duoc ghi nhan.</p>
        <?php else: ?>
        <div class="row g-2">
          <?php foreach ($indicators as $ind): ?>
            <div class="col-md-4">
              <div class="border rounded p-2">
                <div class="small text-muted"><?= e($ind['indicator_name']) ?></div>
                <div class="fw-semibold"><?= e($ind['indicator_value']) ?> <?= e($ind['unit'] ?? '') ?></div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <?php if ($images): ?>
    <div class="card border-0 shadow-sm mb-3">
      <div class="card-header bg-white fw-bold">Hinh anh thuc te</div>
      <div class="card-body d-flex flex-wrap gap-2">
        <?php foreach ($images as $img): ?>
          <a href="<?= e(base_url() . '/' . $img['file_path']) ?>" target="_blank">
            <img src="<?= e(base_url() . '/' . $img['file_path']) ?>" style="width:140px;height:140px;object-fit:cover;border-radius:6px;">
          </a>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm mb-3">
      <div class="card-header bg-white fw-bold">Trao doi</div>
      <div class="card-body">
        <?php if (!$comments): ?>
          <p class="text-muted">Chua co trao doi nao.</p>
        <?php endif; ?>
        <?php foreach ($comments as $c): ?>
          <div class="mb-2 border-bottom pb-2">
            <div class="small text-muted"><?= e($c['user_name'] ?? 'Ẩn danh') ?> - <?= e($c['created_at']) ?></div>
            <div><?= nl2br(e($c['comment'])) ?></div>
          </div>
        <?php endforeach; ?>
        <form method="post" class="mt-3">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="comment">
          <div class="input-group">
            <input type="text" name="comment" class="form-control" placeholder="Nhap phan hoi..." required>
            <button class="btn btn-outline-primary">Gui</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="col-md-4">
    <div class="card border-0 shadow-sm mb-3">
      <div class="card-header bg-white fw-bold">Tu van / quy trinh de xuat</div>
      <ul class="list-group list-group-flush">
        <?php if (!$recommendations): ?>
          <li class="list-group-item text-muted">RD chua tu van cho ca nay.</li>
        <?php endif; ?>
        <?php foreach ($recommendations as $rec): ?>
          <li class="list-group-item">
            <?php if ($rec['procedure_id']): ?>
              <a href="<?= e(base_url()) ?>/modules/ho-tro-ky-thuat/thu-vien/view.php?id=<?= (int)$rec['procedure_id'] ?>" class="fw-semibold"><?= e($rec['procedure_title']) ?></a><br>
            <?php endif; ?>
            <div class="small"><?= nl2br(e($rec['note'] ?? '')) ?></div>
            <div class="small text-muted mt-1"><?= e($rec['advisor_name'] ?? '-') ?> - <?= e($rec['created_at']) ?></div>
          </li>
        <?php endforeach; ?>
      </ul>
      <?php if ($canAdvise): ?>
      <div class="card-body border-top">
        <form method="post">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="recommend">
          <div class="mb-2">
            <label class="form-label small">Chon quy trinh tu thu vien (goi y theo van de: <?= e(category_label($request['problem_category'])) ?>)</label>
            <select name="procedure_id" class="form-select form-select-sm">
              <option value="">-- Khong gan quy trinh --</option>
              <?php foreach ($suggestedProcedures as $sp): ?>
                <option value="<?= (int)$sp['id'] ?>"><?= e($sp['title']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-2">
            <label class="form-label small">Ghi chu tu van chi tiet</label>
            <textarea name="note" class="form-control form-control-sm" rows="4" placeholder="Huong dan cu the cho ca nay..."></textarea>
          </div>
          <button class="btn btn-sm btn-primary w-100">Gui tu van</button>
        </form>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../../../includes/layout_end.php'; ?>
