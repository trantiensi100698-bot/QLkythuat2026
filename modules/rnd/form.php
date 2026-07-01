<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

$user = require_role(['rd', 'manager']);
$activeMenu = 'rnd';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$experiment = null;
$measurements = [];
$linkedProducts = [];
$images = [];
$files = [];

if ($id) {
    $stmt = db()->prepare('SELECT * FROM rd_experiments WHERE id = ?');
    $stmt->execute([$id]);
    $experiment = $stmt->fetch();
    if (!$experiment) {
        flash_set('danger', 'Khong tim thay thi nghiem.');
        redirect('/modules/rnd/index.php');
    }

    $stmt = db()->prepare('SELECT * FROM rd_measurements WHERE experiment_id = ? ORDER BY stage, measured_at');
    $stmt->execute([$id]);
    $measurements = $stmt->fetchAll();

    $stmt = db()->prepare('SELECT rp.*, pr.name AS product_name FROM rd_experiment_products rp JOIN products pr ON pr.id = rp.product_id WHERE rp.experiment_id = ?');
    $stmt->execute([$id]);
    $linkedProducts = $stmt->fetchAll();

    $stmt = db()->prepare('SELECT * FROM rd_experiment_images WHERE experiment_id = ? ORDER BY id DESC');
    $stmt->execute([$id]);
    $images = $stmt->fetchAll();

    $stmt = db()->prepare('SELECT * FROM rd_experiment_files WHERE experiment_id = ? ORDER BY id DESC');
    $stmt->execute([$id]);
    $files = $stmt->fetchAll();
}

$allProducts = db()->query('SELECT id, name FROM products ORDER BY name ASC')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $title = trim((string)($_POST['title'] ?? ''));
    $category = (string)($_POST['category'] ?? 'khac');
    $objective = trim((string)($_POST['objective'] ?? ''));
    $startDate = trim((string)($_POST['start_date'] ?? '')) ?: null;
    $endDate = trim((string)($_POST['end_date'] ?? '')) ?: null;
    $status = (string)($_POST['status'] ?? 'dang_thuc_hien');
    $findingsPros = trim((string)($_POST['findings_pros'] ?? ''));
    $findingsCons = trim((string)($_POST['findings_cons'] ?? ''));
    $costAnalysis = trim((string)($_POST['cost_analysis'] ?? ''));

    if ($title === '') {
        flash_set('danger', 'Vui long nhap ten thi nghiem.');
    } elseif (!in_array($category, ['khi_doc', 'xu_ly_nuoc_truoc_tha', 'khac'], true)) {
        flash_set('danger', 'Nhom thi nghiem khong hop le.');
    } elseif (!in_array($status, ['dang_thuc_hien', 'hoan_thanh', 'tam_dung'], true)) {
        flash_set('danger', 'Trang thai khong hop le.');
    } else {
        try {
            db()->beginTransaction();

            if ($id) {
                $stmt = db()->prepare(
                    'UPDATE rd_experiments SET title=?, category=?, objective=?, start_date=?, end_date=?, status=?, findings_pros=?, findings_cons=?, cost_analysis=? WHERE id=?'
                );
                $stmt->execute([$title, $category, $objective ?: null, $startDate, $endDate, $status, $findingsPros ?: null, $findingsCons ?: null, $costAnalysis ?: null, $id]);
                db()->prepare('DELETE FROM rd_measurements WHERE experiment_id = ?')->execute([$id]);
                db()->prepare('DELETE FROM rd_experiment_products WHERE experiment_id = ?')->execute([$id]);
            } else {
                $stmt = db()->prepare(
                    'INSERT INTO rd_experiments (title, category, objective, start_date, end_date, status, findings_pros, findings_cons, cost_analysis, created_by)
                     VALUES (?,?,?,?,?,?,?,?,?,?)'
                );
                $stmt->execute([$title, $category, $objective ?: null, $startDate, $endDate, $status, $findingsPros ?: null, $findingsCons ?: null, $costAnalysis ?: null, $user['id']]);
                $id = (int) db()->lastInsertId();
            }

            $stages = $_POST['m_stage'] ?? [];
            $mDates = $_POST['m_date'] ?? [];
            $mNames = $_POST['m_name'] ?? [];
            $mValues = $_POST['m_value'] ?? [];
            $mUnits = $_POST['m_unit'] ?? [];
            $insertM = db()->prepare('INSERT INTO rd_measurements (experiment_id, stage, measured_at, indicator_name, indicator_value, unit) VALUES (?,?,?,?,?,?)');
            foreach ($mNames as $i => $name) {
                $name = trim((string)$name);
                $value = trim((string)($mValues[$i] ?? ''));
                if ($name === '' || $value === '') {
                    continue;
                }
                $stage = ($stages[$i] ?? 'truoc') === 'sau' ? 'sau' : 'truoc';
                $insertM->execute([$id, $stage, trim((string)($mDates[$i] ?? '')) ?: null, $name, $value, trim((string)($mUnits[$i] ?? '')) ?: null]);
            }

            $productIds = $_POST['product_id'] ?? [];
            $dosages = $_POST['dosage'] ?? [];
            $costs = $_POST['cost'] ?? [];
            $notes = $_POST['note'] ?? [];
            $insertP = db()->prepare('INSERT INTO rd_experiment_products (experiment_id, product_id, dosage, cost, note) VALUES (?,?,?,?,?)');
            foreach ($productIds as $i => $pid) {
                $pid = (int)$pid;
                if ($pid <= 0) {
                    continue;
                }
                $cost = trim((string)($costs[$i] ?? ''));
                $insertP->execute([$id, $pid, trim((string)($dosages[$i] ?? '')) ?: null, $cost !== '' ? $cost : null, trim((string)($notes[$i] ?? '')) ?: null]);
            }

            $newImages = handle_multiple_image_uploads('images', 'rd-experiments');
            if ($newImages) {
                $insertImg = db()->prepare('INSERT INTO rd_experiment_images (experiment_id, file_path) VALUES (?, ?)');
                foreach ($newImages as $path) {
                    $insertImg->execute([$id, $path]);
                }
            }

            $newFiles = handle_multiple_document_uploads('reports', 'rd-reports');
            if ($newFiles) {
                $insertFile = db()->prepare('INSERT INTO rd_experiment_files (experiment_id, file_path, original_name) VALUES (?, ?, ?)');
                foreach ($newFiles as $f) {
                    $insertFile->execute([$id, $f['path'], $f['name']]);
                }
            }

            db()->commit();
            flash_set('success', 'Da luu thi nghiem.');
            redirect('/modules/rnd/view.php?id=' . $id);
        } catch (Throwable $e) {
            db()->rollBack();
            flash_set('danger', 'Loi khi luu: ' . $e->getMessage());
        }
    }
}

$pageTitle = $id ? 'Sua thi nghiem' : 'Tao thi nghiem moi';
include __DIR__ . '/../../includes/layout_start.php';
?>

<form method="post" enctype="multipart/form-data">
  <?= csrf_field() ?>
  <div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white fw-bold">Thong tin thi nghiem</div>
    <div class="card-body row g-3">
      <div class="col-md-6">
        <label class="form-label">Ten thi nghiem</label>
        <input type="text" name="title" class="form-control" required value="<?= e($experiment['title'] ?? '') ?>">
      </div>
      <div class="col-md-3">
        <label class="form-label">Nhom</label>
        <select name="category" class="form-select">
          <?php foreach (['khi_doc', 'xu_ly_nuoc_truoc_tha', 'khac'] as $c): ?>
            <option value="<?= e($c) ?>" <?= ($experiment['category'] ?? '') === $c ? 'selected' : '' ?>><?= e(rd_category_label($c)) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label">Trang thai</label>
        <select name="status" class="form-select">
          <?php foreach (['dang_thuc_hien', 'hoan_thanh', 'tam_dung'] as $s): ?>
            <option value="<?= e($s) ?>" <?= ($experiment['status'] ?? 'dang_thuc_hien') === $s ? 'selected' : '' ?>><?= e(rd_status_label($s)) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label">Ngay bat dau</label>
        <input type="date" name="start_date" class="form-control" value="<?= e($experiment['start_date'] ?? '') ?>">
      </div>
      <div class="col-md-3">
        <label class="form-label">Ngay ket thuc</label>
        <input type="date" name="end_date" class="form-control" value="<?= e($experiment['end_date'] ?? '') ?>">
      </div>
      <div class="col-12">
        <label class="form-label">Muc tieu thi nghiem</label>
        <textarea name="objective" class="form-control" rows="3"><?= e($experiment['objective'] ?? '') ?></textarea>
      </div>
    </div>
  </div>

  <div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white fw-bold d-flex justify-content-between align-items-center">
      Chi tieu do dac (truoc / sau khi dung san pham)
      <button type="button" class="btn btn-sm btn-outline-primary" onclick="addRow('measureRows')">+ Them chi tieu</button>
    </div>
    <div class="card-body">
      <div id="measureRows">
        <?php
        $mRows = $measurements ?: [['stage' => 'truoc', 'measured_at' => '', 'indicator_name' => '', 'indicator_value' => '', 'unit' => '']];
        foreach ($mRows as $m):
        ?>
        <div class="row g-2 mb-2 measureRows-row align-items-center">
          <div class="col-md-2">
            <select name="m_stage[]" class="form-select form-select-sm">
              <option value="truoc" <?= ($m['stage'] ?? '') === 'truoc' ? 'selected' : '' ?>>Truoc</option>
              <option value="sau" <?= ($m['stage'] ?? '') === 'sau' ? 'selected' : '' ?>>Sau</option>
            </select>
          </div>
          <div class="col-md-2">
            <input type="date" name="m_date[]" class="form-control form-control-sm" value="<?= e($m['measured_at'] ?? '') ?>">
          </div>
          <div class="col-md-3">
            <input type="text" name="m_name[]" class="form-control form-control-sm" placeholder="Ten chi tieu (pH, NH3...)" value="<?= e($m['indicator_name'] ?? '') ?>">
          </div>
          <div class="col-md-3">
            <input type="text" name="m_value[]" class="form-control form-control-sm" placeholder="Gia tri" value="<?= e($m['indicator_value'] ?? '') ?>">
          </div>
          <div class="col-md-1">
            <input type="text" name="m_unit[]" class="form-control form-control-sm" placeholder="DV" value="<?= e($m['unit'] ?? '') ?>">
          </div>
          <div class="col-md-1">
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('.measureRows-row').remove()">X</button>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white fw-bold d-flex justify-content-between align-items-center">
      San pham su dung trong thi nghiem &amp; chi phi
      <button type="button" class="btn btn-sm btn-outline-primary" onclick="addRow('productRows')">+ Them san pham</button>
    </div>
    <div class="card-body">
      <div id="productRows">
        <?php
        $pRows = $linkedProducts ?: [['product_id' => '', 'dosage' => '', 'cost' => '', 'note' => '']];
        foreach ($pRows as $row):
        ?>
        <div class="row g-2 mb-2 productRows-row align-items-center">
          <div class="col-md-3">
            <select name="product_id[]" class="form-select form-select-sm">
              <option value="">-- Chon san pham --</option>
              <?php foreach ($allProducts as $p): ?>
                <option value="<?= (int)$p['id'] ?>" <?= (int)($row['product_id'] ?? 0) === (int)$p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-2">
            <input type="text" name="dosage[]" class="form-control form-control-sm" placeholder="Lieu dung" value="<?= e($row['dosage'] ?? '') ?>">
          </div>
          <div class="col-md-2">
            <input type="text" name="cost[]" class="form-control form-control-sm" placeholder="Chi phi (VND)" value="<?= e((string)($row['cost'] ?? '')) ?>">
          </div>
          <div class="col-md-4">
            <input type="text" name="note[]" class="form-control form-control-sm" placeholder="Ghi chu" value="<?= e($row['note'] ?? '') ?>">
          </div>
          <div class="col-md-1">
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('.productRows-row').remove()">X</button>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white fw-bold">Danh gia va phan tich</div>
    <div class="card-body row g-3">
      <div class="col-md-6">
        <label class="form-label">Uu diem san pham (dua tren so lieu)</label>
        <textarea name="findings_pros" class="form-control" rows="4"><?= e($experiment['findings_pros'] ?? '') ?></textarea>
      </div>
      <div class="col-md-6">
        <label class="form-label">Nhuoc diem / han che</label>
        <textarea name="findings_cons" class="form-control" rows="4"><?= e($experiment['findings_cons'] ?? '') ?></textarea>
      </div>
      <div class="col-12">
        <label class="form-label">Phan tich chi phi</label>
        <textarea name="cost_analysis" class="form-control" rows="3"><?= e($experiment['cost_analysis'] ?? '') ?></textarea>
      </div>
    </div>
  </div>

  <div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white fw-bold">Hinh anh &amp; file bao cao (Word / PPT / Excel / PDF)</div>
    <div class="card-body">
      <?php if ($images): ?>
        <div class="d-flex flex-wrap gap-2 mb-3">
          <?php foreach ($images as $img): ?>
            <img src="<?= e(base_url() . '/' . $img['file_path']) ?>" style="width:100px;height:100px;object-fit:cover;border-radius:6px;">
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
      <div class="mb-3">
        <label class="form-label small">Them hinh anh</label>
        <input type="file" name="images[]" class="form-control" accept="image/png,image/jpeg,image/webp" multiple>
      </div>
      <?php if ($files): ?>
        <ul class="list-unstyled small mb-3">
          <?php foreach ($files as $f): ?>
            <li><a href="<?= e(base_url() . '/' . $f['file_path']) ?>" target="_blank">&#128206; <?= e($f['original_name']) ?></a></li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
      <div>
        <label class="form-label small">Them file bao cao (Word/Excel/PPT/PDF, toi da 20MB/file)</label>
        <input type="file" name="reports[]" class="form-control" accept=".doc,.docx,.xls,.xlsx,.ppt,.pptx,.pdf" multiple>
      </div>
    </div>
  </div>

  <button type="submit" class="btn btn-primary">Luu thi nghiem</button>
  <a href="index.php" class="btn btn-outline-secondary">Huy</a>
</form>

<script>
function addRow(containerId) {
  const container = document.getElementById(containerId);
  const rowClass = containerId + '-row';
  const row = container.querySelector('.' + rowClass).cloneNode(true);
  row.querySelectorAll('input').forEach(i => i.value = '');
  row.querySelectorAll('select').forEach(s => s.selectedIndex = 0);
  container.appendChild(row);
}
</script>

<?php include __DIR__ . '/../../includes/layout_end.php'; ?>
