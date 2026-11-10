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
        $result = process_appointment(fn() => db(__DIR__ . '/schema.sql'), $_POST);
        if ($result['ok']) {
            csrf_rotate();
            flash_set('success', 'Appointment request saved with status Pending (reference #' . $result['id'] . ').');
            redirect_self();
        }
        $errors = $result['errors'];
    }
}

start_template([
    'old' => $old,
    'errors' => $errors,
    'flash' => flash_take(),
    'vars' => [
        'tomorrow' => today_date()->modify('+1 day')->format('Y-m-d'),
        'latest' => today_date()->modify('+' . S3_MAX_DAYS_AHEAD . ' days')->format('Y-m-d'),
    ],
]);
