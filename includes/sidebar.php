<?php
declare(strict_types=1);
$activeMenu = $activeMenu ?? '';
$sbUser = current_user();

function nav_link(string $key, string $active, string $href, string $label, string $icon = ''): string
{
    $isActive = $key === $active;
    $cls = 'nav-link' . ($isActive ? ' active' : '');
    return sprintf('<a class="%s" href="%s"><span>%s</span><span>%s</span></a>', $cls, e($href), $icon, e($label));
}

function user_initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name));
    $parts = array_filter($parts);
    if (!$parts) {
        return '?';
    }
    $last = end($parts);
    $first = reset($parts);
    $initials = mb_strtoupper(mb_substr($first, 0, 1) . mb_substr($last, 0, 1));
    return $initials;
}
?>
<div class="d-flex flex-column flex-shrink-0 p-3 sidebar" style="width: 280px; min-height: 100vh;">
  <a href="<?= e(base_url()) ?>/index.php" class="brand-block mb-3">
    <div class="brand-logo">DT</div>
    <div>
      <div class="brand-name">Dathop</div>
      <div class="brand-sub">Quan ly ky thuat</div>
    </div>
  </a>

  <?php if ($sbUser): ?>
  <div class="user-card mb-3">
    <div class="user-avatar"><?= e(user_initials($sbUser['name'])) ?></div>
    <div>
      <div class="user-name"><?= e($sbUser['name']) ?></div>
      <div class="user-role"><?= e(role_label($sbUser['role'])) ?></div>
    </div>
  </div>
  <?php endif; ?>

  <hr class="mt-0">

  <ul class="nav nav-pills flex-column mb-auto gap-1">
    <li><?= nav_link('dashboard', $activeMenu, base_url() . '/index.php', 'Trang chu', '&#128202;') ?></li>

    <li class="nav-section-label">1. Du an R&amp;D</li>
    <li><?= nav_link('rnd', $activeMenu, base_url() . '/modules/rnd/index.php', 'Du an R&D', '&#129514;') ?></li>

    <li class="nav-section-label">2. Nhat ky farm Biogency</li>
    <li><?= nav_link('nhat-ky-farm', $activeMenu, base_url() . '/modules/nhat-ky-farm/index.php', 'Nhat ky farm', '&#128221;') ?></li>

    <li class="nav-section-label">3. Quan ly ky thuat thi truong</li>
    <li><?= nav_link('htkt-thuvien', $activeMenu, base_url() . '/modules/ho-tro-ky-thuat/thu-vien/index.php', 'Thu vien cong thuc / quy trinh', '&#128218;') ?></li>
    <li><?= nav_link('htkt-chandoan', $activeMenu, base_url() . '/modules/ho-tro-ky-thuat/chan-doan/index.php', 'Chan doan ao khach hang', '&#128269;') ?></li>
    <li><?= nav_link('httt', $activeMenu, base_url() . '/modules/ho-tro-thi-truong/index.php', 'Ho tro thi truong', '&#129309;') ?></li>

    <li class="nav-section-label">Danh muc</li>
    <li><?= nav_link('products', $activeMenu, base_url() . '/modules/ho-tro-ky-thuat/thu-vien/products.php', 'San pham Biogency', '&#129514;') ?></li>
  </ul>
</div>
