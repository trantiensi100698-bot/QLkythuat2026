<?php
// Copy file nay thanh config.php (cung cap tren local/hosting) va dien thong tin that.
// config.php KHONG duoc commit len GitHub (da khai bao trong .gitignore).

return [
    'db' => [
        'host' => '127.0.0.1',
        'name' => 'dathop_ky_thuat',
        'user' => 'root',
        'pass' => '',
        'charset' => 'utf8mb4',
    ],
    'app' => [
        'name' => 'Dathop - Quan Ly Ky Thuat',
        'base_url' => 'http://localhost/dathop-ky-thuat',
        'upload_dir' => __DIR__ . '/uploads',
    ],
];
