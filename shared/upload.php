<?php
declare(strict_types=1);

const IMAGE_MAX_BYTES = 2 * 1024 * 1024;

function validate_image_upload(?array $file, int $maxBytes = IMAGE_MAX_BYTES): array
{
    $none = ['error' => null, 'ext' => null, 'tmp' => null];
    if ($file === null || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return $none;
    }

    $limitMb = rtrim(rtrim(number_format($maxBytes / 1048576, 1), '0'), '.');
    $code = (int) $file['error'];
    if ($code === UPLOAD_ERR_INI_SIZE || $code === UPLOAD_ERR_FORM_SIZE) {
        return ['error' => "The file is too large. Maximum size is under {$limitMb} MB.", 'ext' => null, 'tmp' => null];
    }
    if ($code !== UPLOAD_ERR_OK) {
        return ['error' => 'The file could not be uploaded. Please try again.', 'ext' => null, 'tmp' => null];
    }

    $tmp = (string) ($file['tmp_name'] ?? '');
    if ($tmp === '' || !is_file($tmp)) {
        return ['error' => 'The file could not be uploaded. Please try again.', 'ext' => null, 'tmp' => null];
    }

    $size = (int) filesize($tmp);
    if ($size <= 0) {
        return ['error' => 'The uploaded file is empty.', 'ext' => null, 'tmp' => null];
    }
    if ($size >= $maxBytes) {
        return ['error' => "The file is too large. Maximum size is under {$limitMb} MB.", 'ext' => null, 'tmp' => null];
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = (string) $finfo->file($tmp);
    $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png'][$mime] ?? null;
    if ($ext === null) {
        return ['error' => 'Only JPEG or PNG images are allowed.', 'ext' => null, 'tmp' => null];
    }

    $info = @getimagesize($tmp);
    if ($info === false || ($info['mime'] ?? '') !== $mime) {
        return ['error' => 'The file is not a valid JPEG or PNG image.', 'ext' => null, 'tmp' => null];
    }

    return ['error' => null, 'ext' => $ext, 'tmp' => $tmp];
}

function unique_upload_name(string $ext): string
{
    return bin2hex(random_bytes(16)) . '.' . $ext;
}

function ensure_upload_dir(string $dir): void
{
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        throw new RuntimeException('Cannot create upload directory');
    }
    $ht = $dir . '/.htaccess';
    if (!is_file($ht)) {
        file_put_contents($ht, "Options -Indexes -ExecCGI\n<FilesMatch \"\\.(php|phtml|phar|html?)$\">\n    Require all denied\n</FilesMatch>\n");
    }
}
