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

function category_tag_class(string $category): string
{
    $classes = [
        'khi_doc' => 'tag-red',
        'gan' => 'tag-orange',
        'duong_ruot' => 'tag-amber',
        'mau_nuoc' => 'tag-green',
        'uong_gieo' => 'tag-teal',
        'ao_lang' => 'tag-blue',
        'day_ao_nhot_bat' => 'tag-purple',
        'khac' => 'tag-gray',
    ];
    return $classes[$category] ?? 'tag-gray';
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
        'moi' => 'status-pill status-moi',
        'dang_xu_ly' => 'status-pill status-dang-xu-ly',
        'da_tu_van' => 'status-pill status-da-tu-van',
        'hoan_thanh' => 'status-pill status-hoan-thanh',
    ];
    return $classes[$status] ?? 'status-pill status-moi';
}

function status_dot_class(string $status): string
{
    $classes = [
        'moi' => 'dot-gray',
        'dang_xu_ly' => 'dot-amber',
        'da_tu_van' => 'dot-blue',
        'hoan_thanh' => 'dot-green',
    ];
    return $classes[$status] ?? 'dot-gray';
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

/**
 * Handle a multi-file <input type="file" name="fieldName[]" multiple> upload for
 * report documents (Word/Excel/PowerPoint/PDF). Returns array of ['path' => .., 'name' => ..].
 */
function handle_multiple_document_uploads(string $fieldName, string $subDir): array
{
    if (empty($_FILES[$fieldName]) || empty($_FILES[$fieldName]['name'][0])) {
        return [];
    }

    $allowedExtensions = [
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls' => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'ppt' => 'application/vnd.ms-powerpoint',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'pdf' => 'application/pdf',
    ];

    $files = $_FILES[$fieldName];
    $count = count($files['name']);
    $results = [];

    for ($i = 0; $i < $count; $i++) {
        if ($files['error'][$i] === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        if ($files['error'][$i] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Loi khi tai file len (ma loi: ' . $files['error'][$i] . ').');
        }
        if ($files['size'][$i] > 20 * 1024 * 1024) {
            throw new RuntimeException('File khong duoc vuot qua 20MB: ' . $files['name'][$i]);
        }

        $originalName = $files['name'][$i];
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if (!isset($allowedExtensions[$ext])) {
            throw new RuntimeException('Dinh dang file khong duoc ho tro: ' . $originalName . ' (chi nhan Word/Excel/PowerPoint/PDF).');
        }

        $uploadRoot = app_config()['app']['upload_dir'];
        $targetDir = $uploadRoot . '/' . $subDir;
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }
        $storedName = bin2hex(random_bytes(16)) . '.' . $ext;
        $targetPath = $targetDir . '/' . $storedName;

        if (!move_uploaded_file($files['tmp_name'][$i], $targetPath)) {
            throw new RuntimeException('Khong the luu file: ' . $originalName);
        }

        $results[] = ['path' => 'uploads/' . $subDir . '/' . $storedName, 'name' => $originalName];
    }

    return $results;
}

function rd_category_label(string $category): string
{
    $labels = [
        'khi_doc' => 'Xu ly khi doc NH4/NO2',
        'xu_ly_nuoc_truoc_tha' => 'Xu ly nuoc truoc tha giong',
        'khac' => 'Khac',
    ];
    return $labels[$category] ?? $category;
}

function rd_status_label(string $status): string
{
    $labels = [
        'dang_thuc_hien' => 'Dang thuc hien',
        'hoan_thanh' => 'Hoan thanh',
        'tam_dung' => 'Tam dung',
    ];
    return $labels[$status] ?? $status;
}

function rd_status_badge_class(string $status): string
{
    $classes = [
        'dang_thuc_hien' => 'status-pill status-dang-xu-ly',
        'hoan_thanh' => 'status-pill status-hoan-thanh',
        'tam_dung' => 'status-pill status-moi',
    ];
    return $classes[$status] ?? 'status-pill status-moi';
}

function market_visit_type_label(string $type): string
{
    $labels = [
        'thuyet_trinh_demo' => 'Thuyet trinh / demo san pham',
        'tham_ao_dinh_ky' => 'Tham ao khach hang dinh ky',
        'chuyen_giao_cong_nghe' => 'Chuyen giao cong nghe',
    ];
    return $labels[$type] ?? $type;
}

function market_visit_type_tag_class(string $type): string
{
    $classes = [
        'thuyet_trinh_demo' => 'tag-purple',
        'tham_ao_dinh_ky' => 'tag-blue',
        'chuyen_giao_cong_nghe' => 'tag-teal',
    ];
    return $classes[$type] ?? 'tag-gray';
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
