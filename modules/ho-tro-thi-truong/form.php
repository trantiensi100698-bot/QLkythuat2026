<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

$user = require_login();
$activeMenu = 'httt';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $visitType = (string)($_POST['visit_type'] ?? 'tham_ao_dinh_ky');
    $agentName = trim((string)($_POST['agent_name'] ?? ''));
    $customerName = trim((string)($_POST['customer_name'] ?? ''));
    $location = trim((string)($_POST['location'] ?? ''));
    $visitDate = trim((string)($_POST['visit_date'] ?? ''));
    $participants = trim((string)($_POST['participants'] ?? ''));
    $content = trim((string)($_POST['content'] ?? ''));
    $feedback = trim((string)($_POST['customer_feedback'] ?? ''));

    if (!in_array($visitType, ['thuyet_trinh_demo', 'tham_ao_dinh_ky', 'chuyen_giao_cong_nghe'], true) || $visitDate === '') {
        flash_set('danger', 'Vui long chon loai hoat dong va ngay thuc hien.');
    } else {
        try {
            db()->beginTransaction();
            $stmt = db()->prepare(
                'INSERT INTO market_visits (visit_type, agent_name, customer_name, location, visit_date, participants, content, customer_feedback, created_by)
                 VALUES (?,?,?,?,?,?,?,?,?)'
            );
            $stmt->execute([$visitType, $agentName ?: null, $customerName ?: null, $location ?: null, $visitDate, $participants ?: null, $content ?: null, $feedback ?: null, $user['id']]);
            $visitId = (int) db()->lastInsertId();

            $sampleTypes = $_POST['sample_type'] ?? [];
            $sampleResults = $_POST['sample_result'] ?? [];
            $insertSample = db()->prepare('INSERT INTO market_visit_samples (visit_id, sample_type, result_description) VALUES (?,?,?)');
            foreach ($sampleTypes as $i => $st) {
                $st = trim((string)$st);
                if ($st === '') {
                    continue;
                }
                $insertSample->execute([$visitId, $st, trim((string)($sampleResults[$i] ?? '')) ?: null]);
            }

            $newImages = handle_multiple_image_uploads('images', 'market-visits');
            if ($newImages) {
                $insertImg = db()->prepare('INSERT INTO market_visit_images (visit_id, file_path) VALUES (?, ?)');
                foreach ($newImages as $path) {
                    $insertImg->execute([$visitId, $path]);
                }
            }

            $newFiles = handle_multiple_document_uploads('reports', 'market-reports');
            if ($newFiles) {
                $insertFile = db()->prepare('INSERT INTO market_visit_files (visit_id, file_path, original_name) VALUES (?, ?, ?)');
                foreach ($newFiles as $f) {
                    $insertFile->execute([$visitId, $f['path'], $f['name']]);
                }
            }

            db()->commit();
            flash_set('success', 'Da luu bao cao chuyen di.');
            redirect('/modules/ho-tro-thi-truong/view.php?id=' . $visitId);
        } catch (Throwable $e) {
            db()->rollBack();
            flash_set('danger', 'Loi khi luu: ' . $e->getMessage());
        }
    }
}

$pageTitle = 'Ghi nhan chuyen di / hoat dong thi truong';
include __DIR__ . '/../../includes/layout_start.php';
?>

<form method="post" enctype="multipart/form-data">
  <?= csrf_field() ?>
  <div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white fw-bold">Thong tin chuyen di</div>
    <div class="card-body row g-3">
      <div class="col-md-4">
        <label class="form-label">Loai hoat dong</label>
        <select name="visit_type" class="form-select">
          <?php foreach (['thuyet_trinh_demo', 'tham_ao_dinh_ky', 'chuyen_giao_cong_nghe'] as $t): ?>
            <option value="<?= e($t) ?>"><?= e(market_visit_type_label($t)) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label">Ngay thuc hien</label>
        <input type="date" name="visit_date" class="form-control" required value="<?= e(date('Y-m-d')) ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label">Nguoi tham gia (ngoai ban than)</label>
        <input type="text" name="participants" class="form-control" placeholder="Vi du: Hieu (KT thi truong)">
      </div>
      <div class="col-md-4">
        <label class="form-label">Dai ly phu trach</label>
        <input type="text" name="agent_name" class="form-control">
      </div>
      <div class="col-md-4">
        <label class="form-label">Khach hang tiem nang / ao tham</label>
        <input type="text" name="customer_name" class="form-control">
      </div>
      <div class="col-md-4">
        <label class="form-label">Khu vuc / dia diem</label>
        <input type="text" name="location" class="form-control">
      </div>
      <div class="col-12">
        <label class="form-label">Noi dung chuyen di</label>
        <textarea name="content" class="form-control" rows="4" placeholder="Noi dung lam viec, cac hang muc da thuc hien..."></textarea>
      </div>
      <div class="col-12">
        <label class="form-label">Phan hoi cua khach hang khi su dung san pham</label>
        <textarea name="customer_feedback" class="form-control" rows="3"></textarea>
      </div>
    </div>
  </div>

  <div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white fw-bold d-flex justify-content-between align-items-center">
      Mau kiem tra tai ao (nuoc, tom/ca...)
      <button type="button" class="btn btn-sm btn-outline-primary" onclick="addRow('sampleRows')">+ Them mau</button>
    </div>
    <div class="card-body">
      <div id="sampleRows">
        <div class="row g-2 mb-2 sampleRows-row align-items-center">
          <div class="col-md-3">
            <input type="text" name="sample_type[]" class="form-control form-control-sm" placeholder="Loai mau (nuoc, tom...)">
          </div>
          <div class="col-md-8">
            <input type="text" name="sample_result[]" class="form-control form-control-sm" placeholder="Ket qua / mo ta">
          </div>
          <div class="col-md-1">
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('.sampleRows-row').remove()">X</button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white fw-bold">Hinh anh &amp; file bao cao gui Ms Tu Anh (Word/PPT/Excel/PDF)</div>
    <div class="card-body">
      <div class="mb-3">
        <label class="form-label small">Hinh anh</label>
        <input type="file" name="images[]" class="form-control" accept="image/png,image/jpeg,image/webp" multiple>
      </div>
      <div>
        <label class="form-label small">File bao cao chuyen di</label>
        <input type="file" name="reports[]" class="form-control" accept=".doc,.docx,.xls,.xlsx,.ppt,.pptx,.pdf" multiple>
      </div>
    </div>
  </div>

  <button type="submit" class="btn btn-primary">Luu bao cao</button>
  <a href="index.php" class="btn btn-outline-secondary">Huy</a>
</form>

<script>
function addRow(containerId) {
  const container = document.getElementById(containerId);
  const rowClass = containerId + '-row';
  const row = container.querySelector('.' + rowClass).cloneNode(true);
  row.querySelectorAll('input').forEach(i => i.value = '');
  container.appendChild(row);
}
</script>

<?php include __DIR__ . '/../../includes/layout_end.php'; ?>
