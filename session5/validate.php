<?php
declare(strict_types=1);

const S5_WORKSHOPS = [
    'php-mysql' => 'PHP & MySQL Essentials',
    'web-security' => 'Web Application Security',
    'git-teams' => 'Git for Teams',
    'ui-prototyping' => 'UI Prototyping in Figma',
];
const S5_MODES = ['In-person', 'Online'];

function validate_event_registration(array $in): array
{
    $errors = [];

    $name = preg_replace('/\s+/u', ' ', field($in, 'participant_name')) ?? '';
    if ($name === '') {
        $errors['participant_name'] = 'Participant name is required.';
    } elseif (text_len($name) < 3 || text_len($name) > 80) {
        $errors['participant_name'] = 'Participant name must be between 3 and 80 characters.';
    } elseif (!preg_match("/^\p{L}[\p{L}\p{M} .'’\-]*$/u", $name)) {
        $errors['participant_name'] = 'Use letters only (spaces, apostrophes, hyphens and full stops are allowed).';
    }

    $email = mb_strtolower(field($in, 'email'));
    if ($email === '') {
        $errors['email'] = 'Email is required.';
    } elseif (strlen($email) > 150 || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
        $errors['email'] = 'Enter a valid email address, e.g. name@example.com.';
    }

    $phone = null;
    if (field($in, 'phone') !== '') {
        $phone = normalize_phone_cm(field($in, 'phone'));
        if ($phone === null) {
            $errors['phone'] = PHONE_ERROR;
        }
    }

    $workshop = field($in, 'workshop');
    if ($workshop === '') {
        $errors['workshop'] = 'Please choose a workshop.';
    } elseif (!isset(S5_WORKSHOPS[$workshop])) {
        $errors['workshop'] = 'Please choose a workshop from the list.';
    }

    $mode = field($in, 'attendance_mode');
    if ($mode === '') {
        $errors['attendance_mode'] = 'Please choose an attendance mode.';
    } elseif (!in_array($mode, S5_MODES, true)) {
        $errors['attendance_mode'] = 'Please choose one of the listed attendance modes.';
    }

    $seats = null;
    $seatsRaw = field($in, 'seats');
    if ($seatsRaw === '') {
        $errors['seats'] = 'Number of seats is required (1 to 3).';
    } elseif (!preg_match('/^\d{1,3}$/', $seatsRaw) || (int) $seatsRaw < 1 || (int) $seatsRaw > 3) {
        $errors['seats'] = 'Seats must be a whole number between 1 and 3.';
    } else {
        $seats = (int) $seatsRaw;
    }

    $special = text_field($in, 'special_requirements');
    if (text_len($special) > 300) {
        $errors['special_requirements'] = 'Special requirements must be at most 300 characters (currently ' . text_len($special) . ').';
    }

    if (!is_checked($in, 'terms')) {
        $errors['terms'] = 'You must accept the event terms.';
    }

    return [
        'errors' => $errors,
        'clean' => [
            'participant_name' => $name,
            'email' => $email,
            'phone' => $phone,
            'workshop' => $workshop,
            'attendance_mode' => $mode,
            'seats' => $seats,
            'special_requirements' => $special === '' ? null : $special,
        ],
    ];
}

function register_participant(PDO $pdo, array $c): array
{
    $duplicate = ['ok' => false, 'errors' => [
        'email' => 'This email is already registered for the selected workshop.',
    ]];
    try {
        $check = $pdo->prepare('SELECT 1 FROM event_registrations WHERE email = :email AND workshop = :workshop LIMIT 1');
        $check->execute([':email' => $c['email'], ':workshop' => $c['workshop']]);
        if ($check->fetchColumn() !== false) {
            return $duplicate;
        }

        $stmt = $pdo->prepare(
            'INSERT INTO event_registrations (participant_name, email, phone, workshop, attendance_mode, seats, special_requirements, terms_accepted)
             VALUES (:name, :email, :phone, :workshop, :mode, :seats, :special, 1)'
        );
        $stmt->execute([
            ':name' => $c['participant_name'],
            ':email' => $c['email'],
            ':phone' => $c['phone'],
            ':workshop' => $c['workshop'],
            ':mode' => $c['attendance_mode'],
            ':seats' => $c['seats'],
            ':special' => $c['special_requirements'],
        ]);
        return ['ok' => true, 'id' => (int) $pdo->lastInsertId()];
    } catch (PDOException $e) {
        return is_duplicate_error($e) ? $duplicate : db_failure('lab5', $e);
    }
}

function process_event_registration(callable $pdoFactory, array $in): array
{
    $v = validate_event_registration($in);
    if ($v['errors']) {
        return ['ok' => false, 'errors' => $v['errors']];
    }
    try {
        $pdo = $pdoFactory();
    } catch (Throwable $e) {
        return db_failure('lab5', $e);
    }
    return register_participant($pdo, $v['clean']);
}
