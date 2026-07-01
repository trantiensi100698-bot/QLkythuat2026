<?php
declare(strict_types=1);
require_once __DIR__ . '/../../../includes/db.php';
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/functions.php';

$user = require_login();
$activeMenu = 'htkt-chandoan';

$commonIndicators = ['pH', 'Kiem (mg/L)', 'NH3/NH4 (mg/L)', 'NO2 (mg/L)', 'Oxy hoa tan (mg/L)', 'Do man (ppt)', 'Do trong (cm)', 'Mat do (con/m2)'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $customerName = trim((string)($_POST['customer_name'] ?? ''));
    $agentName = trim((string)($_POST['agent_name'] ?? ''));
    $location = trim((string)($_POST['location'] ?? ''));
    $pondArea = trim((string)($_POST['pond_area'] ?? ''));
    $pondStage = trim((string)($_POST['pond_stage'] ?? ''));
    $problemCategory = (string)($_POST['problem_category'] ?? 'khac');
    $description = trim((string)($_POST['description'] ?? ''));

    if ($customerName === '') {
        flash_set('danger', 'Vui long nhap ten khach hang.');
    } elseif (!in_array($problemCategory, all_categories(), true)) {
        flash_set('danger', 'Van de khong hop le.');
    } else {
        try {
            db()->beginTransaction();

            $stmt = db()->prepare(
                'INSERT INTO diagnosis_requests
                 (customer_name, agent_name, location, pond_area, pond_stage, problem_category, description, created_by)
                 VALUES (?,?,?,?,?,?,?,?)'
            );
            $stmt->execute([
                $customerName,
                $agentName ?: null,
                $location ?: null,
                $pondArea !== '' ? $pondArea : null,
                $pondStage ?: null,
                $problemCategory,
                $description ?: null,
                $user['id'],
            ]);
            $requestId = (int) db()->lastInsertId();

            $names = $_POST['indicator_name'] ?? [];
            $values = $_POST['indicator_value'] ?? [];
            $units = $_POST['indicator_unit'] ?? [];
            $insertInd = db()->prepare('INSERT INTO diagnosis_indicators (request_id, indicator_name, indicator_value, unit) VALUES (?,?,?,?)');
            foreach ($names as $i => $name) {
                $name = trim((string)$name);
                $value = trim((string)($values[$i] ?? ''));
                if ($name === '' || $value === '') {
                    continue;
                }
                $insertInd->execute([$requestId, $name, $value, trim((string)($units[$i] ?? '')) ?: null]);
            }

            $newImages = handle_multiple_image_uploads('images', 'diagnosis');
            if ($newImages) {
                $insertImg = db()->prepare('INSERT INTO diagnosis_images (request_id, file_path) VALUES (?, ?)');
                foreach ($newImages as $path) {
                    $insertImg->execute([$requestId, $path]);
                }
            }

            db()->commit();
            flash_set('success', 'Da gui yeu cau chan doan. RD se phan hoi som.');
            redirect('/modules/ho-tro-ky-thuat/chan-doan/view.php?id=' . $requestId);
        } catch (Throwable $e) {
            db()->rollBack();
            flash_set('danger', 'Loi khi luu: ' . $e->getMessage());
        }
    }
}

$pageTitle = 'Tao yeu cau chan doan ao';
include __DIR__ . '/../../../includes/layout_start.php';
?>

<form method="post" enctype="multipart/form-data">
  <?= csrf_field() ?>
  <div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white fw-bold">Thong tin ao / khach hang</div>
    <div class="card-body row g-3">
      <div class="col-md-4">
        <label class="form-label">Ten khach hang *</label>
        <input type="text" name="customer_name" class="form-control" required>
      </div>
      <div class="col-md-4">
        <label class="form-label">Dai ly phu trach</label>
        <input type="text" name="agent_name" class="form-control">
      </div>
      <div class="col-md-4">
        <label class="form-label">Khu vuc / dia diem</label>
        <input type="text" name="location" class="form-control">
      </div>
      <div class="col-md-4">
        <label class="form-label">Dien tich ao (m2)</label>
        <input type="number" step="0.01" name="pond_area" class="form-control">
      </div>
      <div class="col-md-4">
        <label class="form-label">Giai doan nuoi</label>
        <input type="text" name="pond_stage" class="form-control" placeholder="Vi du: 25 ngay tuoi">
      </div>
      <div class="col-md-4">
        <label class="form-label">Van de chinh</label>
        <select name="problem_category" class="form-select">
          <?php foreach (all_categories() as $c): ?>
            <option value="<?= e($c) ?>"><?= e(category_label($c)) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-12">
        <label class="form-label">Mo ta tinh trang ao / trieu chung</label>
        <textarea name="description" class="form-control" rows="4" placeholder="Mo ta mau nuoc, hanh vi tom/ca, dau hieu benh, da xu ly gi truoc do..."></textarea>
      </div>
    </div>
  </div>

  <div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white fw-bold d-flex justify-content-between align-items-center">
      Chi tieu moi truong ao
      <button type="button" class="btn btn-sm btn-outline-primary" onclick="addIndicatorRow()">+ Them chi tieu</button>
    </div>
    <div class="card-body">
      <div id="indicatorRows">
        <?php foreach (array_slice($commonIndicators, 0, 4) as $ind): ?>
        <div class="row g-2 mb-2 indicator-row align-items-center">
          <div class="col-md-4">
            <input type="text" name="indicator_name[]" class="form-control form-control-sm" list="indicatorList" value="<?= e($ind) ?>">
          </div>
          <div class="col-md-4">
            <input type="text" name="indicator_value[]" class="form-control form-control-sm" placeholder="Gia tri">
          </div>
          <div class="col-md-3">
            <input type="text" name="indicator_unit[]" class="form-control form-control-sm" placeholder="Don vi (neu can)">
          </div>
          <div class="col-md-1">
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('.indicator-row').remove()">X</button>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <datalist id="indicatorList">
        <?php foreach ($commonIndicators as $ind): ?>
          <option value="<?= e($ind) ?>">
        <?php endforeach; ?>
      </datalist>
    </div>
  </div>

  <div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white fw-bold">Hinh anh thuc te (mau nuoc, tom/ca, ao...)</div>
    <div class="card-body">
      <input type="file" name="images[]" class="form-control" accept="image/png,image/jpeg,image/webp" multiple>
      <small class="text-muted">Co the chon nhieu anh (JPG/PNG/WEBP, toi da 8MB/anh).</small>
    </div>
  </div>

  <button type="submit" class="btn btn-primary">Gui yeu cau chan doan</button>
  <a href="index.php" class="btn btn-outline-secondary">Huy</a>
</form>

<script>
function addIndicatorRow() {
  const container = document.getElementById('indicatorRows');
  const row = container.querySelector('.indicator-row').cloneNode(true);
  row.querySelectorAll('input').forEach(i => i.value = '');
  container.appendChild(row);
}
</script>

<?php include __DIR__ . '/../../../includes/layout_end.php'; ?>
