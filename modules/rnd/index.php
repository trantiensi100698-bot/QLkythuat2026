<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

require_login();
$pageTitle = 'Du an R&D';
$activeMenu = 'rnd';
include __DIR__ . '/../../includes/layout_start.php';
?>
<div class="card border-0 shadow-sm">
  <div class="card-body">
    <h5>Du an R&D - Thuy san</h5>
    <p class="text-muted">Module dang duoc xay dung. Du kien se bao gom:</p>
    <ul>
      <li>Quy trinh thi nghiem xu ly khi doc NH4/NO2</li>
      <li>Xu ly nuoc truoc tha giong: gay mau tao, men vi sinh ep khuan</li>
      <li>Ghi nhan chi tieu moi truong truoc/sau khi dung san pham, thoi diem su dung hieu qua</li>
      <li>Danh gia uu/nhuoc diem san pham Biogency dua tren so lieu khoa hoc, phan tich chi phi thi nghiem</li>
      <li>Kho bao cao (Word/Excel/PPT) de chia se voi khach hang va nhan vien thi truong</li>
    </ul>
  </div>
</div>
<?php include __DIR__ . '/../../includes/layout_end.php'; ?>
