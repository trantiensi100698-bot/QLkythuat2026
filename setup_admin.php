<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

// Trang tao tai khoan RD dau tien - chi hoat dong khi database CHUA co nguoi dung nao.
// Sau khi tao xong tai khoan dau tien, hay XOA file nay khoi server de dam bao an toan.

$userCount = (int) db()->query('SELECT COUNT(*) c FROM users')->fetch()['c'];

if ($userCount > 0) {
    http_response_code(403);
    die('He thong da co tai khoan. Vi ly do an toan, trang setup nay da bi khoa. Hay xoa file setup_admin.php khoi server.');
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $name = trim((string)($_POST['name'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Vui long nhap ten va email hop le.';
    } elseif (strlen($password) < 8) {
        $error = 'Mat khau phai co it nhat 8 ky tu.';
    } else {
        $stmt = db()->prepare('INSERT INTO users (name, email, role, password_hash) VALUES (?,?,?,?)');
        $stmt->execute([$name, $email, 'rd', password_hash($password, PASSWORD_DEFAULT)]);
        flash_set('success', 'Da tao tai khoan RD dau tien. Vui long dang nhap, sau do XOA file setup_admin.php khoi server de bao mat.');
        redirect('/login.php');
    }
}
?>
<!doctype html>
<html lang="vi">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Setup tai khoan dau tien - Dathop</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container d-flex align-items-center justify-content-center" style="min-height:100vh;">
  <div class="card shadow-sm" style="max-width:420px; width:100%;">
    <div class="card-body p-4">
      <h4 class="card-title mb-1 text-center">Tao tai khoan RD dau tien</h4>
      <p class="text-muted small text-center">Trang nay chi dung duoc 1 lan khi he thong chua co tai khoan nao.</p>
      <?php if ($error): ?>
        <div class="alert alert-danger"><?= e($error) ?></div>
      <?php endif; ?>
      <form method="post">
        <?= csrf_field() ?>
        <div class="mb-3">
          <label class="form-label">Ho ten</label>
          <input type="text" name="name" class="form-control" required autofocus>
        </div>
        <div class="mb-3">
          <label class="form-label">Email dang nhap</label>
          <input type="email" name="email" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Mat khau (toi thieu 8 ky tu)</label>
          <input type="password" name="password" class="form-control" minlength="8" required>
        </div>
        <button type="submit" class="btn btn-primary w-100">Tao tai khoan</button>
      </form>
    </div>
  </div>
</div>
</body>
</html>
