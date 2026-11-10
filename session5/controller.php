<?php
declare(strict_types=1);

require __DIR__ . '/../shared/helpers.php';
require __DIR__ . '/../shared/db.php';
require __DIR__ . '/../shared/template.php';
require __DIR__ . '/validate.php';

csrf_start();
$errors = [];
$old = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = old_values($_POST);
    if (!csrf_valid()) {
        $errors['_form'] = CSRF_MESSAGE;
    } else {
        $result = process_event_registration(fn() => db(__DIR__ . '/schema.sql'), $_POST);
        if ($result['ok']) {
            csrf_rotate();
            flash_set('success', 'Registration confirmed (registration no. ' . $result['id'] . ').');
            redirect_self();
        }
        $errors = $result['errors'];
    }
}

start_template([
    'old' => $old,
    'errors' => $errors,
    'flash' => flash_take(),
]);
