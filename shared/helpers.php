<?php
declare(strict_types=1);

date_default_timezone_set('Africa/Douala');

const CSRF_MESSAGE = 'Your form session expired. Please reload the page and submit again.';
const DB_ERROR_MESSAGE = 'We could not save your data right now. Please try again later.';

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function field(array $in, string $key): string
{
    $v = $in[$key] ?? '';
    return is_string($v) ? trim($v) : '';
}

function text_field(array $in, string $key): string
{
    return str_replace(["\r\n", "\r"], "\n", field($in, $key));
}

function is_checked(array $in, string $key): bool
{
    return ($in[$key] ?? '') === '1';
}

function text_len(string $s): int
{
    return mb_strlen($s, 'UTF-8');
}

function has_control_chars(string $s): bool
{
    return (bool) preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $s);
}

function old_values(array $in): array
{
    $out = [];
    foreach ($in as $k => $v) {
        if (is_string($k) && is_string($v)) {
            $out[$k] = $v;
        }
    }
    return $out;
}

function today_date(): DateTimeImmutable
{
    return new DateTimeImmutable('today');
}

function parse_date(string $s): ?DateTimeImmutable
{
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $s)) {
        return null;
    }
    $d = DateTimeImmutable::createFromFormat('!Y-m-d', $s);
    if ($d === false || $d->format('Y-m-d') !== $s) {
        return null;
    }
    return $d;
}

function normalize_phone_cm(string $raw): ?string
{
    $s = preg_replace('/[\s\-.()]/', '', $raw) ?? '';
    if (str_starts_with($s, '+237')) {
        $s = substr($s, 4);
    } elseif (str_starts_with($s, '00237')) {
        $s = substr($s, 5);
    } elseif (strlen($s) === 12 && str_starts_with($s, '237')) {
        $s = substr($s, 3);
    }
    return preg_match('/^[26]\d{8}$/', $s) === 1 ? $s : null;
}

const PHONE_HINT = '9 digits starting with 6 or 2, e.g. 677 12 34 56 (a +237 prefix is accepted).';
const PHONE_ERROR = 'Enter a valid 9-digit number starting with 6 or 2 (e.g. 677123456).';

function is_duplicate_error(PDOException $e): bool
{
    return $e->getCode() === '23000' || (($e->errorInfo[0] ?? '') === '23000');
}

function db_failure(string $lab, Throwable $e): array
{
    error_log("[$lab] database failure: " . $e->getMessage());
    return ['ok' => false, 'errors' => ['_form' => DB_ERROR_MESSAGE]];
}

function csrf_start(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
}

function csrf_rotate(): void
{
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

function csrf_valid(): bool
{
    $posted = $_POST['csrf'] ?? null;
    return is_string($posted)
        && isset($_SESSION['csrf'])
        && hash_equals($_SESSION['csrf'], $posted);
}

function flash_set(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function flash_take(): ?array
{
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return is_array($f) ? $f : null;
}

function redirect_self(): never
{
    $path = (string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    header('Location: ' . $path, true, 303);
    exit;
}

function page_start(string $title, array $nav = []): void
{
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8">'
        . '<meta name="viewport" content="width=device-width, initial-scale=1">'
        . '<title>' . e($title) . '</title>'
        . '<link rel="stylesheet" href="../shared/style.css"></head><body>'
        . '<a class="skip" href="#main">Skip to content</a>'
        . '<header class="site"><div class="wrap"><p class="kicker">Degree Year 3 · PHP &amp; MySQL</p>'
        . '<h1>' . e($title) . '</h1>';
    if ($nav) {
        $current = basename($_SERVER['SCRIPT_NAME'] ?? '');
        echo '<nav aria-label="Lab pages">';
        foreach ($nav as $label => $href) {
            $cur = $current === $href ? ' aria-current="page"' : '';
            echo '<a href="' . e($href) . '"' . $cur . '>' . e($label) . '</a>';
        }
        echo '</nav>';
    }
    echo '</div></header><main id="main" class="wrap">';
}

function page_end(): void
{
    echo '</main></body></html>';
}
