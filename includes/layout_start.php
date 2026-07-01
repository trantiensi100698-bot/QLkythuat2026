<?php
declare(strict_types=1);
// Include sau khi da require db.php, auth.php, functions.php va goi require_login()/require_role()
// Bien tuy chon: $pageTitle, $activeMenu
$user = current_user();
$flash = flash_get();
?>
<!doctype html>
<html lang="vi">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle ?? 'Dathop - Quan ly ky thuat') ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="<?= e(base_url()) ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="d-flex">
  <?php include __DIR__ . '/sidebar.php'; ?>
  <div class="flex-grow-1">
    <nav class="navbar topbar px-3">
      <span class="page-title"><?= e($pageTitle ?? '') ?></span>
      <?php if ($user): ?>
      <div class="d-flex align-items-center gap-3">
        <span class="text-muted small"><?= e($user['name']) ?> (<?= e(role_label($user['role'])) ?>)</span>
        <a href="<?= e(base_url()) ?>/logout.php" class="btn btn-sm btn-outline-secondary">Dang xuat</a>
      </div>
      <?php endif; ?>
    </nav>
    <main class="container-fluid py-4">
      <?php if ($flash): ?>
        <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
      <?php endif; ?>
