<?php
declare(strict_types=1);
$activeMenu = $activeMenu ?? '';
function nav_link(string $key, string $active, string $href, string $label, string $icon = ''): string
{
    $isActive = $key === $active;
    $cls = 'nav-link text-white' . ($isActive ? ' active bg-primary' : '');
    return sprintf('<a class="%s" href="%s">%s %s</a>', $cls, e($href), $icon, e($label));
}
?>
<div class="d-flex flex-column flex-shrink-0 p-3 text-white bg-dark sidebar" style="width: 280px; min-height: 100vh;">
  <a href="<?= e(base_url()) ?>/index.php" class="d-flex align-items-center mb-3 text-white text-decoration-none">
    <span class="fs-5 fw-bold">Dathop Ky Thuat</span>
  </a>
  <hr>
  <ul class="nav nav-pills flex-column mb-auto gap-1">
    <li><?= nav_link('dashboard', $activeMenu, base_url() . '/index.php', 'Trang chu') ?></li>

    <li class="mt-2 text-uppercase small text-secondary px-2">1. Du an R&D</li>
    <li><?= nav_link('rnd', $activeMenu, base_url() . '/modules/rnd/index.php', 'Du an R&D') ?></li>

    <li class="mt-2 text-uppercase small text-secondary px-2">2. Nhat ky farm nuoi Biogency</li>
    <li><?= nav_link('nhat-ky-farm', $activeMenu, base_url() . '/modules/nhat-ky-farm/index.php', 'Nhat ky farm') ?></li>

    <li class="mt-2 text-uppercase small text-secondary px-2">3. Quan ly ky thuat thi truong</li>
    <li><?= nav_link('htkt-thuvien', $activeMenu, base_url() . '/modules/ho-tro-ky-thuat/thu-vien/index.php', 'Thu vien cong thuc / quy trinh') ?></li>
    <li><?= nav_link('htkt-chandoan', $activeMenu, base_url() . '/modules/ho-tro-ky-thuat/chan-doan/index.php', 'Chan doan ao khach hang') ?></li>
    <li><?= nav_link('httt', $activeMenu, base_url() . '/modules/ho-tro-thi-truong/index.php', 'Ho tro thi truong (demo, tham ao, CGCN)') ?></li>

    <li class="mt-2 text-uppercase small text-secondary px-2">Danh muc</li>
    <li><?= nav_link('products', $activeMenu, base_url() . '/modules/ho-tro-ky-thuat/thu-vien/products.php', 'San pham Biogency') ?></li>
  </ul>
</div>
