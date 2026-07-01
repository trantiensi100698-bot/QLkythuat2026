<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

$user = require_login();
$activeMenu = 'nhat-ky-farm';

$ponds = db()->query('SELECT * FROM farm_ponds ORDER BY name ASC')->fetchAll();
if (!$ponds) {
    flash_set('danger', 'Chua co ao nao. Vui long them ao truoc.');
    redirect('/modules/nhat-ky-farm/ponds.php');
}
$allProducts = db()->query('SELECT id, name FROM products ORDER BY name ASC')->fetchAll();

$commonIndicators = ['pH', 'Kiem (mg/L)', 'NH3/NH4 (mg/L)', 'NO2 (mg/L)', 'Oxy hoa tan (mg/L)', 'Do man (ppt)'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $pondId = (int)($_POST['pond_id'] ?? 0);
    $logDate = trim((string)($_POST['log_date'] ?? ''));
    $feedAmount = trim((string)($_POST['feed_amount'] ?? ''));
    $note = trim((string)($_POST['note'] ?? ''));

    if ($pondId <= 0 || $logDate === '') {
        flash_set('danger', 'Vui long chon ao va ngay ghi nhat ky.');
    } else {
        try {
            db()->beginTransaction();
            $stmt = db()->prepare('INSERT INTO farm_logs (pond_id, log_date, feed_amount, note, created_by) VALUES (?,?,?,?,?)');
            $stmt->execute([$pondId, $logDate, $feedAmount ?: null, $note ?: null, $user['id']]);
            $logId = (int) db()->lastInsertId();

            $names = $_POST['indicator_name'] ?? [];
            $values = $_POST['indicator_value'] ?? [];
            $units = $_POST['indicator_unit'] ?? [];
            $insertInd = db()->prepare('INSERT INTO farm_log_indicators (log_id, indicator_name, indicator_value, unit) VALUES (?,?,?,?)');
            foreach ($names as $i => $name) {
                $name = trim((string)$name);
                $value = trim((string)($values[$i] ?? ''));
                if ($name === '' || $value === '') {
                    continue;
                }
                $insertInd->execute([$logId, $name, $value, trim((string)($units[$i] ?? '')) ?: null]);
            }

            $productIds = $_POST['product_id'] ?? [];
            $dosages = $_POST['dosage'] ?? [];
            $insertProd = db()->prepare('INSERT INTO farm_log_products (log_id, product_id, dosage) VALUES (?,?,?)');
            foreach ($productIds as $i => $pid) {
                $pid = (int)$pid;
                if ($pid <= 0) {
                    continue;
                }
                $insertProd->execute([$logId, $pid, trim((string)($dosages[$i] ?? '')) ?: null]);
            }

            $newImages = handle_multiple_image_uploads('images', 'farm-logs');
            if ($newImages) {
                $insertImg = db()->prepare('INSERT INTO farm_log_images (log_id, file_path) VALUES (?, ?)');
                foreach ($newImages as $path) {
                    $insertImg->execute([$logId, $path]);
                }
            }

            db()->commit();
            flash_set('success', 'Da luu nhat ky.');
            redirect('/modules/nhat-ky-farm/view.php?id=' . $logId);
        } catch (Throwable $e) {
            db()->rollBack();
            flash_set('danger', 'Loi khi luu: ' . $e->getMessage());
        }
    }
}

$pageTitle = 'Ghi nhat ky farm';
include __DIR__ . '/../../includes/layout_start.php';
?>

<form method="post" enctype="multipart/form-data">
  <?= csrf_field() ?>
  <div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white fw-bold">Thong tin chung</div>
    <div class="card-body row g-3">
      <div class="col-md-4">
        <label class="form-label">Ao</label>
        <select name="pond_id" class="form-select" required>
          <?php foreach ($ponds as $p): ?>
            <option value="<?= (int)$p['id'] ?>"><?= e($p['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label">Ngay</label>
        <input type="date" name="log_date" class="form-control" required value="<?= e(date('Y-m-d')) ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label">Luong cho an</label>
        <input type="text" name="feed_amount" class="form-control" placeholder="Vi du: 5kg thuc an/ngay">
      </div>
      <div class="col-12">
        <label class="form-label">Ghi chu bat thuong</label>
        <textarea name="note" class="form-control" rows="3" placeholder="Hanh vi tom/ca, mau nuoc, su co..."></textarea>
      </div>
    </div>
  </div>

  <div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white fw-bold d-flex justify-content-between align-items-center">
      Chi tieu moi truong
      <button type="button" class="btn btn-sm btn-outline-primary" onclick="addRow('indicatorRows')">+ Them chi tieu</button>
    </div>
    <div class="card-body">
      <div id="indicatorRows">
        <?php foreach (array_slice($commonIndicators, 0, 3) as $ind): ?>
        <div class="row g-2 mb-2 indicatorRows-row align-items-center">
          <div class="col-md-5">
            <input type="text" name="indicator_name[]" class="form-control form-control-sm" list="indicatorList" value="<?= e($ind) ?>">
          </div>
          <div class="col-md-4">
            <input type="text" name="indicator_value[]" class="form-control form-control-sm" placeholder="Gia tri">
          </div>
          <div class="col-md-2">
            <input type="text" name="indicator_unit[]" class="form-control form-control-sm" placeholder="DV">
          </div>
          <div class="col-md-1">
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('.indicatorRows-row').remove()">X</button>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <datalist id="indicatorList">
        <?php foreach ($commonIndicators as $ind): ?><option value="<?= e($ind) ?>"><?php endforeach; ?>
      </datalist>
    </div>
  </div>

  <div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white fw-bold d-flex justify-content-between align-items-center">
      San pham da su dung
      <button type="button" class="btn btn-sm btn-outline-primary" onclick="addRow('productRows')">+ Them san pham</button>
    </div>
    <div class="card-body">
      <div id="productRows">
        <div class="row g-2 mb-2 productRows-row align-items-center">
          <div class="col-md-6">
            <select name="product_id[]" class="form-select form-select-sm">
              <option value="">-- Chon san pham --</option>
              <?php foreach ($allProducts as $p): ?>
                <option value="<?= (int)$p['id'] ?>"><?= e($p['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-5">
            <input type="text" name="dosage[]" class="form-control form-control-sm" placeholder="Lieu dung">
          </div>
          <div class="col-md-1">
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('.productRows-row').remove()">X</button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white fw-bold">Hinh anh</div>
    <div class="card-body">
      <input type="file" name="images[]" class="form-control" accept="image/png,image/jpeg,image/webp" multiple>
    </div>
  </div>

  <button type="submit" class="btn btn-primary">Luu nhat ky</button>
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
