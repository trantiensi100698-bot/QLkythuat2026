<?php
declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function role_label(string $role): string
{
    $labels = [
        'rd' => 'R&D Ky thuat',
        'sale' => 'Nhan vien thi truong',
        'manager' => 'Quan ly',
    ];
    return $labels[$role] ?? $role;
}

function category_label(string $category): string
{
    $labels = [
        'khi_doc' => 'Khi doc (NH3/NH4, NO2)',
        'gan' => 'Gan',
        'duong_ruot' => 'Duong ruot',
        'mau_nuoc' => 'Gay mau nuoc ao tom',
        'uong_gieo' => 'Uong gieo',
        'ao_lang' => 'Xu ly nuoc ao lang / ao nuoi',
        'day_ao_nhot_bat' => 'Kiem soat day ao va nhot bat',
        'khac' => 'Khac',
    ];
    return $labels[$category] ?? $category;
}

function all_categories(): array
{
    return ['khi_doc', 'gan', 'duong_ruot', 'mau_nuoc', 'uong_gieo', 'ao_lang', 'day_ao_nhot_bat', 'khac'];
}

function status_label(string $status): string
{
    $labels = [
        'moi' => 'Moi tao',
        'dang_xu_ly' => 'Dang xu ly',
        'da_tu_van' => 'Da tu van',
        'hoan_thanh' => 'Hoan thanh',
    ];
    return $labels[$status] ?? $status;
}

function status_badge_class(string $status): string
{
    $classes = [
        'moi' => 'bg-secondary',
        'dang_xu_ly' => 'bg-warning text-dark',
        'da_tu_van' => 'bg-info text-dark',
        'hoan_thanh' => 'bg-success',
    ];
    return $classes[$status] ?? 'bg-secondary';
}

/**
 * Handle a single uploaded image with extension/mime whitelist and a random filename.
 * Returns the relative path (under public/uploads/...) to store in DB, or null if no file uploaded.
 */
function handle_image_upload(string $fieldName, string $subDir): ?string
{
    if (empty($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    $file = $_FILES[$fieldName];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Loi khi tai anh len (ma loi: ' . $file['error'] . ').');
    }

    $allowedMimes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    if (!isset($allowedMimes[$mime])) {
        throw new RuntimeException('Chi cho phep anh dinh dang JPG, PNG hoac WEBP.');
    }
    if ($file['size'] > 8 * 1024 * 1024) {
        throw new RuntimeException('Anh khong duoc vuot qua 8MB.');
    }

    $ext = $allowedMimes[$mime];
    $filename = bin2hex(random_bytes(16)) . '.' . $ext;

    $uploadRoot = app_config()['app']['upload_dir'];
    $targetDir = $uploadRoot . '/' . $subDir;
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }
    $targetPath = $targetDir . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        throw new RuntimeException('Khong the luu anh vua tai len.');
    }

    return 'uploads/' . $subDir . '/' . $filename;
}

/**
 * Handle a multi-file <input type="file" name="fieldName[]" multiple> upload.
 * Returns an array of relative paths (under public uploads) for the successfully stored files.
 */
function handle_multiple_image_uploads(string $fieldName, string $subDir): array
{
    if (empty($_FILES[$fieldName]) || empty($_FILES[$fieldName]['name'][0])) {
        return [];
    }

    $files = $_FILES[$fieldName];
    $count = count($files['name']);
    $paths = [];

    for ($i = 0; $i < $count; $i++) {
        if ($files['error'][$i] === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        $single = [
            'name' => $files['name'][$i],
            'type' => $files['type'][$i],
            'tmp_name' => $files['tmp_name'][$i],
            'error' => $files['error'][$i],
            'size' => $files['size'][$i],
        ];
        $originalFiles = $_FILES[$fieldName];
        $_FILES[$fieldName] = $single;
        $paths[] = handle_image_upload($fieldName, $subDir);
        $_FILES[$fieldName] = $originalFiles;
    }

    return array_values(array_filter($paths));
}

function redirect(string $path): void
{
    header('Location: ' . base_url() . $path);
    exit;
}

function flash_set(string $type, string $message): void
{
    start_session();
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function flash_get(): ?array
{
    start_session();
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}
