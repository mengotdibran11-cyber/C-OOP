<?php
declare(strict_types=1);

function fill_template(string $html, array $ctx): string
{
    $old = $ctx['old'] ?? [];
    $errors = $ctx['errors'] ?? [];
    $vars = $ctx['vars'] ?? [];
    $flash = $ctx['flash'] ?? null;
    $csrf = (string) ($ctx['csrf'] ?? '');

    $pattern = '/\{\{(alerts|csrf)\}\}|\{\{(value|error|selected|checked|var):([A-Za-z0-9_]+)(?::([^}]*))?\}\}/';

    return (string) preg_replace_callback($pattern, function (array $m) use ($old, $errors, $vars, $flash, $csrf): string {
        if (($m[1] ?? '') === 'csrf') {
            return e($csrf);
        }

        if (($m[1] ?? '') === 'alerts') {
            $out = '';
            if ($flash) {
                $out .= '<div class="alert ' . e($flash['type']) . '">' . e($flash['message']) . '</div>';
            }
            if (isset($errors['_form'])) {
                $out .= '<div class="alert danger">' . e($errors['_form']) . '</div>';
            }
            return $out;
        }

        $type = $m[2];
        $name = $m[3];
        $arg = $m[4] ?? '';

        switch ($type) {
            case 'value':
                return e($old[$name] ?? '');
            case 'error':
                return e($errors[$name] ?? '');
            case 'selected':
                return ($old[$name] ?? '') === $arg ? 'selected' : '';
            case 'checked':
                return ($old[$name] ?? '') === $arg ? 'checked' : '';
            case 'var':
                return array_key_exists($name, $vars) ? e((string) $vars[$name]) : $m[0];
        }
        return $m[0];
    }, $html);
}

function start_template(array $ctx): void
{
    $ctx['csrf'] = $_SESSION['csrf'] ?? '';
    ob_start(fn(string $buffer): string => fill_template($buffer, $ctx));
}
