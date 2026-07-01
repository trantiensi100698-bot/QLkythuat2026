<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

$user = require_role(['rd', 'manager']);
$pageTitle = 'Quan ly nguoi dung';
$activeMenu = 'users';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);

    if ($action === 'toggle_active' && $id) {
        if ($id === (int)$user['id']) {
            flash_set('danger', 'Khong the tu vo hieu hoa tai khoan cua chinh minh.');
        } else {
            db()->prepare('UPDATE users SET is_active = NOT is_active WHERE id = ?')->execute([$id]);
            flash_set('success', 'Da cap nhat trang thai tai khoan.');
        }
    } elseif ($action === 'delete' && $id) {
        if ($id === (int)$user['id']) {
            flash_set('danger', 'Khong the tu xoa tai khoan cua chinh minh.');
        } else {
            db()->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
            flash_set('success', 'Da xoa tai khoan.');
        }
    }
    redirect('/modules/users/index.php');
}

$users = db()->query('SELECT * FROM users ORDER BY created_at ASC')->fetchAll();

include __DIR__ . '/../../includes/layout_start.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h5 class="mb-0">Danh sach nguoi dung</h5>
  <a href="form.php" class="btn btn-primary btn-sm">+ Tao tai khoan moi</a>
</div>

<div class="card border-0 shadow-sm">
  <div class="table-responsive">
    <table class="table mb-0 align-middle">
      <thead class="table-light">
        <tr><th>Ten</th><th>Email</th><th>Vai tro</th><th>Trang thai</th><th>Ngay tao</th><th></th></tr>
      </thead>
      <tbody>
        <?php foreach ($users as $u): ?>
        <tr>
          <td><?= e($u['name']) ?> <?= (int)$u['id'] === (int)$user['id'] ? '<span class="text-muted small">(ban)</span>' : '' ?></td>
          <td><?= e($u['email']) ?></td>
          <td><span class="tag tag-blue"><?= e(role_label($u['role'])) ?></span></td>
          <td>
            <?php if ($u['is_active']): ?>
              <span class="status-pill status-hoan-thanh">Dang hoat dong</span>
            <?php else: ?>
              <span class="status-pill status-moi">Da khoa</span>
            <?php endif; ?>
          </td>
          <td class="small text-muted"><?= e($u['created_at']) ?></td>
          <td class="text-nowrap">
            <a href="form.php?id=<?= (int)$u['id'] ?>" class="btn btn-sm btn-outline-secondary">Sua</a>
            <form method="post" class="d-inline" onsubmit="return confirm('Doi trang thai tai khoan nay?')">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="toggle_active">
              <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
              <button class="btn btn-sm btn-outline-warning" <?= (int)$u['id'] === (int)$user['id'] ? 'disabled' : '' ?>><?= $u['is_active'] ? 'Khoa' : 'Mo khoa' ?></button>
            </form>
            <form method="post" class="d-inline" onsubmit="return confirm('Xoa vinh vien tai khoan nay?')">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
              <button class="btn btn-sm btn-outline-danger" <?= (int)$u['id'] === (int)$user['id'] ? 'disabled' : '' ?>>Xoa</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include __DIR__ . '/../../includes/layout_end.php'; ?>
