<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

require_login();
$pageTitle = 'Nhat ky farm nuoi Biogency';
$activeMenu = 'nhat-ky-farm';
include __DIR__ . '/../../includes/layout_start.php';
?>
<div class="card border-0 shadow-sm">
  <div class="card-body">
    <h5>Nhat ky farm nuoi Biogency</h5>
    <p class="text-muted">Module dang duoc xay dung. Du kien se ghi nhan theo tung ngay/tung ao cua farm: chi tieu moi truong, hoat dong cho an, su dung san pham, hinh anh, ghi chu bat thuong.</p>
  </div>
</div>
<?php include __DIR__ . '/../../includes/layout_end.php'; ?>
