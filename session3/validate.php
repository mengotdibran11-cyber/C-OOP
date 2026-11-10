<?php
declare(strict_types=1);

const S3_DOCTORS = [
    'General Medicine' => ['Dr. Ngu Tabi', 'Dr. Mireille Essomba'],
    'Paediatrics' => ['Dr. Samuel Fotso'],
    'Dentistry' => ['Dr. Aisha Bello', 'Dr. Paul Kamga'],
];
const S3_SLOTS = ['08:00-08:30', '08:30-09:00', '09:00-09:30', '09:30-10:00', '10:00-10:30', '10:30-11:00', '14:00-14:30', '14:30-15:00'];
const S3_MAX_DAYS_AHEAD = 90;

function validate_appointment(array $in): array
{
    $errors = [];

    $name = preg_replace('/\s+/u', ' ', field($in, 'patient_name')) ?? '';
    if ($name === '') {
        $errors['patient_name'] = 'Patient name is required.';
    } elseif (text_len($name) < 3 || text_len($name) > 80) {
        $errors['patient_name'] = 'Patient name must be between 3 and 80 characters.';
    } elseif (!preg_match("/^\p{L}[\p{L}\p{M} .'’\-]*$/u", $name)) {
        $errors['patient_name'] = 'Use letters only (spaces, apostrophes, hyphens and full stops are allowed).';
    }

    $email = mb_strtolower(field($in, 'email'));
    if ($email !== '' && (strlen($email) > 150 || filter_var($email, FILTER_VALIDATE_EMAIL) === false)) {
        $errors['email'] = 'Enter a valid email address or leave this field empty.';
    }

    $phone = null;
    if (field($in, 'phone') === '') {
        $errors['phone'] = 'Phone is required.';
    } else {
        $phone = normalize_phone_cm(field($in, 'phone'));
        if ($phone === null) {
            $errors['phone'] = PHONE_ERROR;
        }
    }

    $department = field($in, 'department');
    if ($department === '') {
        $errors['department'] = 'Please choose a department.';
    } elseif (!isset(S3_DOCTORS[$department])) {
        $errors['department'] = 'Please choose a department from the list.';
    }

    $doctor = field($in, 'doctor');
    if ($doctor === '') {
        $errors['doctor'] = 'Please choose a doctor.';
    } else {
        $allDoctors = array_merge(...array_values(S3_DOCTORS));
        if (!in_array($doctor, $allDoctors, true)) {
            $errors['doctor'] = 'Please choose a doctor from the list.';
        } elseif (!isset($errors['department']) && !in_array($doctor, S3_DOCTORS[$department], true)) {
            $errors['doctor'] = 'This doctor does not work in the selected department.';
        }
    }

    $date = null;
    if (field($in, 'appointment_date') === '') {
        $errors['appointment_date'] = 'Appointment date is required.';
    } else {
        $date = parse_date(field($in, 'appointment_date'));
        $today = today_date();
        if ($date === null) {
            $errors['appointment_date'] = 'Enter a real date in the format YYYY-MM-DD.';
        } elseif ($date <= $today) {
            $errors['appointment_date'] = 'The appointment must be on a future date.';
        } elseif ($date > $today->modify('+' . S3_MAX_DAYS_AHEAD . ' days')) {
            $errors['appointment_date'] = 'Appointments can be booked at most ' . S3_MAX_DAYS_AHEAD . ' days ahead.';
        }
    }

    $slot = field($in, 'time_slot');
    if ($slot === '') {
        $errors['time_slot'] = 'Please choose a time slot.';
    } elseif (!in_array($slot, S3_SLOTS, true)) {
        $errors['time_slot'] = 'Please choose a time slot from the list.';
    }

    $reason = text_field($in, 'reason');
    if ($reason !== '' && (text_len($reason) < 10 || text_len($reason) > 500)) {
        $errors['reason'] = 'Reason must be between 10 and 500 characters (currently ' . text_len($reason) . ').';
    } elseif ($reason === '') {
        $errors['reason'] = 'Please describe your symptoms or reason (10–500 characters).';
    }

    if (!is_checked($in, 'consent')) {
        $errors['consent'] = 'Consent to data use is required.';
    }

    return [
        'errors' => $errors,
        'clean' => [
            'patient_name' => $name,
            'email' => $email === '' ? null : $email,
            'phone' => $phone,
            'department' => $department,
            'doctor' => $doctor,
            'appointment_date' => $date?->format('Y-m-d'),
            'time_slot' => $slot,
            'reason' => $reason,
        ],
    ];
}

function book_appointment(PDO $pdo, array $c): array
{
    $conflict = ['ok' => false, 'errors' => [
        'time_slot' => 'This doctor is already booked for that date and time slot. Please pick another slot.',
    ]];
    try {
        $check = $pdo->prepare(
            'SELECT 1 FROM appointments WHERE doctor = :doctor AND appointment_date = :d AND time_slot = :slot LIMIT 1'
        );
        $check->execute([':doctor' => $c['doctor'], ':d' => $c['appointment_date'], ':slot' => $c['time_slot']]);
        if ($check->fetchColumn() !== false) {
            return $conflict;
        }

        $stmt = $pdo->prepare(
            "INSERT INTO appointments (patient_name, email, phone, department, doctor, appointment_date, time_slot, reason, consent_given, status)
             VALUES (:name, :email, :phone, :dept, :doctor, :d, :slot, :reason, 1, 'Pending')"
        );
        $stmt->execute([
            ':name' => $c['patient_name'],
            ':email' => $c['email'],
            ':phone' => $c['phone'],
            ':dept' => $c['department'],
            ':doctor' => $c['doctor'],
            ':d' => $c['appointment_date'],
            ':slot' => $c['time_slot'],
            ':reason' => $c['reason'],
        ]);
        return ['ok' => true, 'id' => (int) $pdo->lastInsertId()];
    } catch (PDOException $e) {
        return is_duplicate_error($e) ? $conflict : db_failure('lab3', $e);
    }
}

function process_appointment(callable $pdoFactory, array $in): array
{
    $v = validate_appointment($in);
    if ($v['errors']) {
        return ['ok' => false, 'errors' => $v['errors']];
    }
    try {
        $pdo = $pdoFactory();
    } catch (Throwable $e) {
        return db_failure('lab3', $e);
    }
    return book_appointment($pdo, $v['clean']);
}
