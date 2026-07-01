<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

require_login();
$pageTitle = 'Ho tro thi truong';
$activeMenu = 'httt';
include __DIR__ . '/../../includes/layout_start.php';
?>
<div class="card border-0 shadow-sm">
  <div class="card-body">
    <h5>Ho tro thi truong</h5>
    <p class="text-muted">Module dang duoc xay dung, gom 3 hoat dong chinh cua RD phoi hop voi ky thuat thi truong (VD: Hieu):</p>
    <ol>
      <li>Thuyet trinh va trinh dien / demo san pham</li>
      <li>Tham ao khach hang dinh ky</li>
      <li>Chuyen giao cong nghe va xu ly van de cho mot so ao / farm trong diem</li>
    </ol>
    <p class="text-muted mb-0">Se ho tro tao bao cao chuyen di (noi dung, phan hoi khach hang tiem nang) de gui Ms Tu Anh bang file Word/PPT/Excel.</p>
  </div>
</div>
<?php include __DIR__ . '/../../includes/layout_end.php'; ?>
