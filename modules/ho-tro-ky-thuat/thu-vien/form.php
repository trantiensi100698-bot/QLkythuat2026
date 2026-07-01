<?php
declare(strict_types=1);
require_once __DIR__ . '/../../../includes/db.php';
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/functions.php';

$user = require_role(['rd', 'manager']);
$activeMenu = 'htkt-thuvien';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$procedure = null;
$linkedProducts = [];
$images = [];

if ($id) {
    $stmt = db()->prepare('SELECT * FROM procedures WHERE id = ?');
    $stmt->execute([$id]);
    $procedure = $stmt->fetch();
    if (!$procedure) {
        flash_set('danger', 'Khong tim thay quy trinh.');
        redirect('/modules/ho-tro-ky-thuat/thu-vien/index.php');
    }
    $stmt = db()->prepare('SELECT pp.*, pr.name AS product_name FROM procedure_products pp JOIN products pr ON pr.id = pp.product_id WHERE pp.procedure_id = ?');
    $stmt->execute([$id]);
    $linkedProducts = $stmt->fetchAll();

    $stmt = db()->prepare('SELECT * FROM procedure_images WHERE procedure_id = ? ORDER BY id DESC');
    $stmt->execute([$id]);
    $images = $stmt->fetchAll();
}

$allProducts = db()->query('SELECT id, name FROM products ORDER BY name ASC')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $title = trim((string)($_POST['title'] ?? ''));
    $category = (string)($_POST['category'] ?? 'khac');
    $summary = trim((string)($_POST['summary'] ?? ''));
    $steps = trim((string)($_POST['steps'] ?? ''));

    if ($title === '' || $steps === '') {
        flash_set('danger', 'Vui long nhap tieu de va cac buoc thuc hien.');
    } elseif (!in_array($category, all_categories(), true)) {
        flash_set('danger', 'Danh muc khong hop le.');
    } else {
        try {
            db()->beginTransaction();

            if ($id) {
                $stmt = db()->prepare('UPDATE procedures SET title=?, category=?, summary=?, steps=? WHERE id=?');
                $stmt->execute([$title, $category, $summary ?: null, $steps, $id]);
                db()->prepare('DELETE FROM procedure_products WHERE procedure_id = ?')->execute([$id]);
            } else {
                $stmt = db()->prepare('INSERT INTO procedures (title, category, summary, steps, created_by) VALUES (?,?,?,?,?)');
                $stmt->execute([$title, $category, $summary ?: null, $steps, $user['id']]);
                $id = (int) db()->lastInsertId();
            }

            $productIds = $_POST['product_id'] ?? [];
            $dosages = $_POST['dosage'] ?? [];
            $notes = $_POST['note'] ?? [];
            $insertPP = db()->prepare('INSERT INTO procedure_products (procedure_id, product_id, dosage, note) VALUES (?,?,?,?)');
            foreach ($productIds as $i => $pid) {
                $pid = (int)$pid;
                if ($pid <= 0) {
                    continue;
                }
                $insertPP->execute([$id, $pid, trim((string)($dosages[$i] ?? '')) ?: null, trim((string)($notes[$i] ?? '')) ?: null]);
            }

            $newImages = handle_multiple_image_uploads('images', 'procedures');
            if ($newImages) {
                $insertImg = db()->prepare('INSERT INTO procedure_images (procedure_id, file_path) VALUES (?, ?)');
                foreach ($newImages as $path) {
                    $insertImg->execute([$id, $path]);
                }
            }

            db()->commit();
            flash_set('success', 'Da luu quy trinh thanh cong.');
            redirect('/modules/ho-tro-ky-thuat/thu-vien/view.php?id=' . $id);
        } catch (Throwable $e) {
            db()->rollBack();
            flash_set('danger', 'Loi khi luu: ' . $e->getMessage());
        }
    }
}

$pageTitle = $id ? 'Sua quy trinh' : 'Tao quy trinh moi';
include __DIR__ . '/../../../includes/layout_start.php';
?>

<form method="post" enctype="multipart/form-data">
  <?= csrf_field() ?>
  <div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-8">
          <label class="form-label">Tieu de quy trinh / cong thuc</label>
          <input type="text" name="title" class="form-control" required value="<?= e($procedure['title'] ?? '') ?>" placeholder="Vi du: Quy trinh xu ly khi doc NH3/NH4 giai doan 30-45 ngay">
        </div>
        <div class="col-md-4">
          <label class="form-label">Danh muc</label>
          <select name="category" class="form-select">
            <?php foreach (all_categories() as $c): ?>
              <option value="<?= e($c) ?>" <?= ($procedure['category'] ?? '') === $c ? 'selected' : '' ?>><?= e(category_label($c)) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-12">
          <label class="form-label">Tom tat ngan</label>
          <input type="text" name="summary" class="form-control" value="<?= e($procedure['summary'] ?? '') ?>" placeholder="Mo ta 1-2 cau de hien thi ngoai danh sach">
        </div>
        <div class="col-12">
          <label class="form-label">Cac buoc thuc hien chi tiet</label>
          <textarea name="steps" class="form-control" rows="10" required placeholder="Buoc 1: ...&#10;Buoc 2: ...&#10;Luu y: ..."><?= e($procedure['steps'] ?? '') ?></textarea>
        </div>
      </div>
    </div>
  </div>

  <div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white fw-bold d-flex justify-content-between align-items-center">
      Cong thuc ket hop san pham
      <button type="button" class="btn btn-sm btn-outline-primary" onclick="addProductRow()">+ Them san pham</button>
    </div>
    <div class="card-body">
      <div id="productRows">
        <?php
        $rows = $linkedProducts ?: [['product_id' => '', 'dosage' => '', 'note' => '']];
        foreach ($rows as $row):
        ?>
        <div class="row g-2 mb-2 product-row align-items-center">
          <div class="col-md-4">
            <select name="product_id[]" class="form-select form-select-sm">
              <option value="">-- Chon san pham --</option>
              <?php foreach ($allProducts as $p): ?>
                <option value="<?= (int)$p['id'] ?>" <?= (int)($row['product_id'] ?? 0) === (int)$p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-3">
            <input type="text" name="dosage[]" class="form-control form-control-sm" placeholder="Lieu dung, vd: 1kg/1000m3" value="<?= e($row['dosage'] ?? '') ?>">
          </div>
          <div class="col-md-4">
            <input type="text" name="note[]" class="form-control form-control-sm" placeholder="Ghi chu (thoi diem, cach dung...)" value="<?= e($row['note'] ?? '') ?>">
          </div>
          <div class="col-md-1">
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('.product-row').remove()">X</button>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php if (!$allProducts): ?>
        <p class="text-muted small mb-0">Chua co san pham nao trong danh muc. <a href="products.php">Them san pham</a> truoc.</p>
      <?php endif; ?>
    </div>
  </div>

  <div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white fw-bold">Hinh anh minh hoa (thuc te ao / ket qua)</div>
    <div class="card-body">
      <?php if ($images): ?>
        <div class="d-flex flex-wrap gap-2 mb-3">
          <?php foreach ($images as $img): ?>
            <img src="<?= e(base_url() . '/' . $img['file_path']) ?>" style="width:100px;height:100px;object-fit:cover;border-radius:6px;">
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
      <input type="file" name="images[]" class="form-control" accept="image/png,image/jpeg,image/webp" multiple>
      <small class="text-muted">Co the chon nhieu anh (JPG/PNG/WEBP, toi da 8MB/anh).</small>
    </div>
  </div>

  <button type="submit" class="btn btn-primary">Luu quy trinh</button>
  <a href="index.php" class="btn btn-outline-secondary">Huy</a>
</form>

<script>
function addProductRow() {
  const container = document.getElementById('productRows');
  const row = container.querySelector('.product-row').cloneNode(true);
  row.querySelectorAll('input').forEach(i => i.value = '');
  row.querySelectorAll('select').forEach(s => s.selectedIndex = 0);
  container.appendChild(row);
}
</script>

<?php include __DIR__ . '/../../../includes/layout_end.php'; ?>
