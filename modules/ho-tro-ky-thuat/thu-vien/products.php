<?php
declare(strict_types=1);
require_once __DIR__ . '/../../../includes/db.php';
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/functions.php';

$user = require_role(['rd', 'manager']);
$pageTitle = 'San pham Biogency';
$activeMenu = 'products';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'create' || $action === 'update') {
        $name = trim((string)($_POST['name'] ?? ''));
        $category = trim((string)($_POST['category'] ?? ''));
        $unit = trim((string)($_POST['unit'] ?? ''));
        $description = trim((string)($_POST['description'] ?? ''));

        if ($name === '') {
            flash_set('danger', 'Ten san pham khong duoc de trong.');
        } elseif ($action === 'create') {
            $stmt = db()->prepare('INSERT INTO products (name, category, unit, description) VALUES (?,?,?,?)');
            $stmt->execute([$name, $category ?: null, $unit ?: null, $description ?: null]);
            flash_set('success', 'Da them san pham moi.');
        } else {
            $id = (int)($_POST['id'] ?? 0);
            $stmt = db()->prepare('UPDATE products SET name=?, category=?, unit=?, description=? WHERE id=?');
            $stmt->execute([$name, $category ?: null, $unit ?: null, $description ?: null, $id]);
            flash_set('success', 'Da cap nhat san pham.');
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        db()->prepare('DELETE FROM products WHERE id=?')->execute([$id]);
        flash_set('success', 'Da xoa san pham.');
    }
    redirect('/modules/ho-tro-ky-thuat/thu-vien/products.php');
}

$products = db()->query('SELECT * FROM products ORDER BY name ASC')->fetchAll();

include __DIR__ . '/../../../includes/layout_start.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h5 class="mb-0">Danh muc san pham Biogency</h5>
  <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#productModal" onclick="openCreate()">+ Them san pham</button>
</div>

<div class="card border-0 shadow-sm">
  <div class="table-responsive">
    <table class="table mb-0 align-middle">
      <thead class="table-light">
        <tr><th>Ten san pham</th><th>Nhom</th><th>Don vi</th><th>Mo ta</th><th></th></tr>
      </thead>
      <tbody>
        <?php if (!$products): ?>
          <tr><td colspan="5" class="text-center text-muted py-4">Chua co san pham nao.</td></tr>
        <?php endif; ?>
        <?php foreach ($products as $p): ?>
        <tr>
          <td><?= e($p['name']) ?></td>
          <td><?= e($p['category'] ?? '') ?></td>
          <td><?= e($p['unit'] ?? '') ?></td>
          <td class="text-muted small"><?= e($p['description'] ?? '') ?></td>
          <td class="text-nowrap">
            <button class="btn btn-sm btn-outline-secondary" onclick='openEdit(<?= json_encode($p, JSON_UNESCAPED_UNICODE) ?>)'>Sua</button>
            <form method="post" class="d-inline" onsubmit="return confirm('Xoa san pham nay?')">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
              <button class="btn btn-sm btn-outline-danger">Xoa</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="modal fade" id="productModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="post" class="modal-content">
      <?= csrf_field() ?>
      <input type="hidden" name="action" id="formAction" value="create">
      <input type="hidden" name="id" id="productId" value="">
      <div class="modal-header">
        <h5 class="modal-title" id="modalTitle">Them san pham</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-2">
          <label class="form-label">Ten san pham</label>
          <input type="text" name="name" id="fName" class="form-control" required>
        </div>
        <div class="mb-2">
          <label class="form-label">Nhom</label>
          <input type="text" name="category" id="fCategory" class="form-control" placeholder="Vi du: men vi sinh, khoang, xu ly nuoc...">
        </div>
        <div class="mb-2">
          <label class="form-label">Don vi</label>
          <input type="text" name="unit" id="fUnit" class="form-control" placeholder="kg, lit, goi...">
        </div>
        <div class="mb-2">
          <label class="form-label">Mo ta</label>
          <textarea name="description" id="fDescription" class="form-control" rows="3"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-primary">Luu</button>
      </div>
    </form>
  </div>
</div>

<script>
function openCreate() {
  document.getElementById('modalTitle').textContent = 'Them san pham';
  document.getElementById('formAction').value = 'create';
  document.getElementById('productId').value = '';
  document.getElementById('fName').value = '';
  document.getElementById('fCategory').value = '';
  document.getElementById('fUnit').value = '';
  document.getElementById('fDescription').value = '';
}
function openEdit(p) {
  document.getElementById('modalTitle').textContent = 'Sua san pham';
  document.getElementById('formAction').value = 'update';
  document.getElementById('productId').value = p.id;
  document.getElementById('fName').value = p.name || '';
  document.getElementById('fCategory').value = p.category || '';
  document.getElementById('fUnit').value = p.unit || '';
  document.getElementById('fDescription').value = p.description || '';
  new bootstrap.Modal(document.getElementById('productModal')).show();
}
</script>

<?php include __DIR__ . '/../../../includes/layout_end.php'; ?>
