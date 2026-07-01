<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

$user = require_role(['rd', 'manager']);
$pageTitle = 'Danh sach ao farm';
$activeMenu = 'nhat-ky-farm';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'create' || $action === 'update') {
        $name = trim((string)($_POST['name'] ?? ''));
        $area = trim((string)($_POST['area'] ?? ''));
        $note = trim((string)($_POST['note'] ?? ''));

        if ($name === '') {
            flash_set('danger', 'Ten ao khong duoc de trong.');
        } elseif ($action === 'create') {
            db()->prepare('INSERT INTO farm_ponds (name, area, note) VALUES (?,?,?)')->execute([$name, $area !== '' ? $area : null, $note ?: null]);
            flash_set('success', 'Da them ao moi.');
        } else {
            $id = (int)($_POST['id'] ?? 0);
            db()->prepare('UPDATE farm_ponds SET name=?, area=?, note=? WHERE id=?')->execute([$name, $area !== '' ? $area : null, $note ?: null, $id]);
            flash_set('success', 'Da cap nhat ao.');
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        db()->prepare('DELETE FROM farm_ponds WHERE id=?')->execute([$id]);
        flash_set('success', 'Da xoa ao.');
    }
    redirect('/modules/nhat-ky-farm/ponds.php');
}

$ponds = db()->query('SELECT * FROM farm_ponds ORDER BY name ASC')->fetchAll();

include __DIR__ . '/../../includes/layout_start.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h5 class="mb-0">Danh sach ao cua farm</h5>
  <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#pondModal" onclick="openCreate()">+ Them ao</button>
</div>

<div class="card border-0 shadow-sm">
  <div class="table-responsive">
    <table class="table mb-0 align-middle">
      <thead class="table-light"><tr><th>Ten ao</th><th>Dien tich (m2)</th><th>Ghi chu</th><th></th></tr></thead>
      <tbody>
        <?php if (!$ponds): ?><tr><td colspan="4" class="text-center text-muted py-4">Chua co ao nao.</td></tr><?php endif; ?>
        <?php foreach ($ponds as $p): ?>
        <tr>
          <td><?= e($p['name']) ?></td>
          <td><?= e((string)($p['area'] ?? '-')) ?></td>
          <td class="small text-muted"><?= e($p['note'] ?? '') ?></td>
          <td class="text-nowrap">
            <button class="btn btn-sm btn-outline-secondary" onclick='openEdit(<?= json_encode($p, JSON_UNESCAPED_UNICODE) ?>)'>Sua</button>
            <form method="post" class="d-inline" onsubmit="return confirm('Xoa ao nay?')">
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

<div class="modal fade" id="pondModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="post" class="modal-content">
      <?= csrf_field() ?>
      <input type="hidden" name="action" id="formAction" value="create">
      <input type="hidden" name="id" id="pondId" value="">
      <div class="modal-header">
        <h5 class="modal-title" id="modalTitle">Them ao</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-2">
          <label class="form-label">Ten ao</label>
          <input type="text" name="name" id="fName" class="form-control" required>
        </div>
        <div class="mb-2">
          <label class="form-label">Dien tich (m2)</label>
          <input type="number" step="0.01" name="area" id="fArea" class="form-control">
        </div>
        <div class="mb-2">
          <label class="form-label">Ghi chu</label>
          <textarea name="note" id="fNote" class="form-control" rows="2"></textarea>
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
  document.getElementById('modalTitle').textContent = 'Them ao';
  document.getElementById('formAction').value = 'create';
  document.getElementById('pondId').value = '';
  document.getElementById('fName').value = '';
  document.getElementById('fArea').value = '';
  document.getElementById('fNote').value = '';
}
function openEdit(p) {
  document.getElementById('modalTitle').textContent = 'Sua ao';
  document.getElementById('formAction').value = 'update';
  document.getElementById('pondId').value = p.id;
  document.getElementById('fName').value = p.name || '';
  document.getElementById('fArea').value = p.area || '';
  document.getElementById('fNote').value = p.note || '';
  new bootstrap.Modal(document.getElementById('pondModal')).show();
}
</script>

<?php include __DIR__ . '/../../includes/layout_end.php'; ?>
