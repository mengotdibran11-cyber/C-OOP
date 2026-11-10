<?php
declare(strict_types=1);

const S1_PROGRAMMES = ['Software Engineering', 'Computer Networks', 'Cybersecurity', 'Information Systems', 'Data Science'];
const S1_LEVELS = ['Level 1', 'Level 2', 'Level 3', 'Level 4'];
const S1_GENDERS = ['Male', 'Female'];

function validate_registration(array $in): array
{
    $errors = [];

    $name = preg_replace('/\s+/u', ' ', field($in, 'full_name')) ?? '';
    if ($name === '') {
        $errors['full_name'] = 'Full name is required.';
    } elseif (text_len($name) < 3 || text_len($name) > 80) {
        $errors['full_name'] = 'Full name must be between 3 and 80 characters.';
    } elseif (!preg_match("/^\p{L}[\p{L}\p{M} .'’\-]*$/u", $name)) {
        $errors['full_name'] = "Use letters only (spaces, apostrophes, hyphens and full stops are allowed).";
    }

    $email = mb_strtolower(field($in, 'email'));
    if ($email === '') {
        $errors['email'] = 'Email address is required.';
    } elseif (strlen($email) > 150 || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
        $errors['email'] = 'Enter a valid email address, e.g. name@example.com.';
    }

    $phone = null;
    if (field($in, 'telephone') === '') {
        $errors['telephone'] = 'Telephone is required.';
    } else {
        $phone = normalize_phone_cm(field($in, 'telephone'));
        if ($phone === null) {
            $errors['telephone'] = PHONE_ERROR;
        }
    }

    $dob = null;
    if (field($in, 'date_of_birth') === '') {
        $errors['date_of_birth'] = 'Date of birth is required.';
    } else {
        $dob = parse_date(field($in, 'date_of_birth'));
        if ($dob === null) {
            $errors['date_of_birth'] = 'Enter a real date in the format YYYY-MM-DD.';
        } elseif ($dob >= today_date()) {
            $errors['date_of_birth'] = 'Date of birth must be a date in the past.';
        } elseif ($dob < today_date()->modify('-100 years')) {
            $errors['date_of_birth'] = 'Date of birth cannot be more than 100 years ago.';
        }
    }

    $programme = field($in, 'programme');
    if ($programme === '') {
        $errors['programme'] = 'Please choose a programme.';
    } elseif (!in_array($programme, S1_PROGRAMMES, true)) {
        $errors['programme'] = 'Please choose a programme from the list.';
    }

    $level = field($in, 'level');
    if ($level === '') {
        $errors['level'] = 'Please choose a level.';
    } elseif (!in_array($level, S1_LEVELS, true)) {
        $errors['level'] = 'Please choose a level from the list.';
    }

    $gender = field($in, 'gender');
    if ($gender !== '' && !in_array($gender, S1_GENDERS, true)) {
        $errors['gender'] = 'Please choose one of the listed options.';
    }

    if (!is_checked($in, 'policy')) {
        $errors['policy'] = 'You must accept the registration policy.';
    }

    return [
        'errors' => $errors,
        'clean' => [
            'full_name' => $name,
            'email' => $email,
            'telephone' => $phone,
            'date_of_birth' => $dob?->format('Y-m-d'),
            'programme' => $programme,
            'level' => $level,
            'gender' => $gender === '' ? null : $gender,
        ],
    ];
}

function register_student(PDO $pdo, array $c): array
{
    $duplicate = ['ok' => false, 'errors' => ['email' => 'This email address is already registered.']];
    try {
        $check = $pdo->prepare('SELECT 1 FROM students WHERE email = :email LIMIT 1');
        $check->execute([':email' => $c['email']]);
        if ($check->fetchColumn() !== false) {
            return $duplicate;
        }

        $stmt = $pdo->prepare(
            'INSERT INTO students (full_name, email, telephone, date_of_birth, programme, level, gender, policy_accepted)
             VALUES (:full_name, :email, :telephone, :dob, :programme, :level, :gender, 1)'
        );
        $stmt->execute([
            ':full_name' => $c['full_name'],
            ':email' => $c['email'],
            ':telephone' => $c['telephone'],
            ':dob' => $c['date_of_birth'],
            ':programme' => $c['programme'],
            ':level' => $c['level'],
            ':gender' => $c['gender'],
        ]);
        return ['ok' => true, 'id' => (int) $pdo->lastInsertId()];
    } catch (PDOException $e) {
        return is_duplicate_error($e) ? $duplicate : db_failure('lab1', $e);
    }
}

function process_registration(callable $pdoFactory, array $in): array
{
    $v = validate_registration($in);
    if ($v['errors']) {
        return ['ok' => false, 'errors' => $v['errors']];
    }
    try {
        $pdo = $pdoFactory();
    } catch (Throwable $e) {
        return db_failure('lab1', $e);
    }
    return register_student($pdo, $v['clean']);
}
