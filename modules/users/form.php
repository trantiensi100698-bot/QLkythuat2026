<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

$currentUser = require_role(['rd', 'manager']);
$activeMenu = 'users';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$editUser = null;

if ($id) {
    $stmt = db()->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$id]);
    $editUser = $stmt->fetch();
    if (!$editUser) {
        flash_set('danger', 'Khong tim thay nguoi dung.');
        redirect('/modules/users/index.php');
    }
}

$roles = ['rd' => role_label('rd'), 'sale' => role_label('sale'), 'manager' => role_label('manager')];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $name = trim((string)($_POST['name'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));
    $role = (string)($_POST['role'] ?? 'sale');
    $password = (string)($_POST['password'] ?? '');

    if ($name === '' || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        flash_set('danger', 'Vui long nhap ten va email hop le.');
    } elseif (!isset($roles[$role])) {
        flash_set('danger', 'Vai tro khong hop le.');
    } elseif (!$id && $password === '') {
        flash_set('danger', 'Vui long dat mat khau cho tai khoan moi.');
    } elseif ($password !== '' && strlen($password) < 8) {
        flash_set('danger', 'Mat khau phai co it nhat 8 ky tu.');
    } else {
        try {
            if ($id) {
                if ($password !== '') {
                    $stmt = db()->prepare('UPDATE users SET name=?, email=?, role=?, password_hash=? WHERE id=?');
                    $stmt->execute([$name, $email, $role, password_hash($password, PASSWORD_DEFAULT), $id]);
                } else {
                    $stmt = db()->prepare('UPDATE users SET name=?, email=?, role=? WHERE id=?');
                    $stmt->execute([$name, $email, $role, $id]);
                }
                flash_set('success', 'Da cap nhat tai khoan.');
            } else {
                $stmt = db()->prepare('INSERT INTO users (name, email, role, password_hash) VALUES (?,?,?,?)');
                $stmt->execute([$name, $email, $role, password_hash($password, PASSWORD_DEFAULT)]);
                flash_set('success', 'Da tao tai khoan moi.');
            }
            redirect('/modules/users/index.php');
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                flash_set('danger', 'Email nay da duoc su dung.');
            } else {
                flash_set('danger', 'Loi khi luu: ' . $e->getMessage());
            }
        }
    }
}

$pageTitle = $id ? 'Sua tai khoan' : 'Tao tai khoan moi';
include __DIR__ . '/../../includes/layout_start.php';
?>

<div class="card border-0 shadow-sm" style="max-width:560px;">
  <div class="card-body">
    <form method="post">
      <?= csrf_field() ?>
      <div class="mb-3">
        <label class="form-label">Ho ten</label>
        <input type="text" name="name" class="form-control" required value="<?= e($editUser['name'] ?? '') ?>">
      </div>
      <div class="mb-3">
        <label class="form-label">Email dang nhap</label>
        <input type="email" name="email" class="form-control" required value="<?= e($editUser['email'] ?? '') ?>">
      </div>
      <div class="mb-3">
        <label class="form-label">Vai tro</label>
        <select name="role" class="form-select">
          <?php foreach ($roles as $val => $label): ?>
            <option value="<?= e($val) ?>" <?= ($editUser['role'] ?? 'sale') === $val ? 'selected' : '' ?>><?= e($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="mb-3">
        <label class="form-label"><?= $id ? 'Mat khau moi (de trong neu khong doi)' : 'Mat khau' ?></label>
        <input type="password" name="password" class="form-control" minlength="8" <?= $id ? '' : 'required' ?>>
        <small class="text-muted">It nhat 8 ky tu.</small>
      </div>
      <button type="submit" class="btn btn-primary">Luu</button>
      <a href="index.php" class="btn btn-outline-secondary">Huy</a>
    </form>
  </div>
</div>

<?php include __DIR__ . '/../../includes/layout_end.php'; ?>
