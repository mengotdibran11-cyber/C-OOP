<?php
declare(strict_types=1);

const S2_MAX_LOAN_DAYS = 60;

function isbn_normalize(string $raw): string
{
    return strtoupper(preg_replace('/[\s\-]/', '', $raw) ?? '');
}

function isbn_is_valid(string $isbn): bool
{
    if (preg_match('/^\d{9}[\dX]$/', $isbn)) {
        $sum = 0;
        for ($i = 0; $i < 10; $i++) {
            $value = $isbn[$i] === 'X' ? 10 : (int) $isbn[$i];
            $sum += $value * (10 - $i);
        }
        return $sum % 11 === 0;
    }
    if (preg_match('/^\d{13}$/', $isbn)) {
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum += (int) $isbn[$i] * ($i % 2 === 0 ? 1 : 3);
        }
        return (10 - $sum % 10) % 10 === (int) $isbn[12];
    }
    return false;
}

function validate_loan(array $in): array
{
    $errors = [];

    $studentId = strtoupper(field($in, 'student_id'));
    if ($studentId === '') {
        $errors['student_id'] = 'Student ID is required.';
    } elseif (!preg_match('/^[A-Z0-9][A-Z0-9\/\-]{3,19}$/', $studentId)) {
        $errors['student_id'] = 'Student ID must be 4–20 letters, digits, "/" or "-".';
    }

    $isbn = isbn_normalize(field($in, 'isbn'));
    if (field($in, 'isbn') === '') {
        $errors['isbn'] = 'Book ISBN is required.';
    } elseif (!isbn_is_valid($isbn)) {
        $errors['isbn'] = 'Enter a valid ISBN-10 (X allowed as last character) or ISBN-13, including its check digit.';
    }

    $title = preg_replace('/\s+/u', ' ', field($in, 'book_title')) ?? '';
    if ($title === '') {
        $errors['book_title'] = 'Book title is required.';
    } elseif (text_len($title) > 200 || has_control_chars($title)) {
        $errors['book_title'] = 'Book title must be at most 200 characters.';
    }

    $today = today_date();
    $borrow = null;
    if (field($in, 'borrow_date') === '') {
        $errors['borrow_date'] = 'Borrow date is required.';
    } else {
        $borrow = parse_date(field($in, 'borrow_date'));
        if ($borrow === null) {
            $errors['borrow_date'] = 'Enter a real date in the format YYYY-MM-DD.';
        } elseif ($borrow > $today) {
            $errors['borrow_date'] = 'Borrow date must not be in the future.';
        }
    }

    $due = null;
    if (field($in, 'due_date') === '') {
        $errors['due_date'] = 'Return due date is required.';
    } else {
        $due = parse_date(field($in, 'due_date'));
        if ($due === null) {
            $errors['due_date'] = 'Enter a real date in the format YYYY-MM-DD.';
        }
    }

    $days = null;
    if ($borrow !== null && $due !== null && !isset($errors['borrow_date'])) {
        if ($due <= $borrow) {
            $errors['due_date'] = 'Return due date must be after the borrow date.';
        } else {
            $days = (int) $borrow->diff($due)->days;
            if ($days > S2_MAX_LOAN_DAYS) {
                $errors['due_date'] = 'The loan period cannot exceed ' . S2_MAX_LOAN_DAYS . ' days.';
            }
        }
    }

    $durationRaw = field($in, 'loan_duration');
    if ($durationRaw !== '') {
        if (!preg_match('/^\d{1,3}$/', $durationRaw) || (int) $durationRaw < 1) {
            $errors['loan_duration'] = 'Loan duration must be a whole number of days (1–' . S2_MAX_LOAN_DAYS . ').';
        } elseif ($days !== null && (int) $durationRaw !== $days) {
            $errors['loan_duration'] = "Loan duration ($durationRaw days) does not match the dates ($days days).";
        }
    }

    $purpose = text_field($in, 'purpose');
    if (text_len($purpose) > 255) {
        $errors['purpose'] = 'Borrowing purpose must be at most 255 characters.';
    }

    if (!is_checked($in, 'agree')) {
        $errors['agree'] = 'You must agree to the library rules.';
    }

    return [
        'errors' => $errors,
        'clean' => [
            'student_id' => $studentId,
            'isbn' => $isbn,
            'book_title' => $title,
            'borrow_date' => $borrow?->format('Y-m-d'),
            'due_date' => $due?->format('Y-m-d'),
            'loan_days' => $days,
            'purpose' => $purpose === '' ? null : $purpose,
        ],
    ];
}

function create_loan(PDO $pdo, array $c): array
{
    $duplicate = ['ok' => false, 'errors' => [
        'isbn' => 'This student already has an active loan for this book.',
    ]];
    try {
        $check = $pdo->prepare(
            "SELECT 1 FROM loans WHERE student_id = :sid AND isbn = :isbn AND status = 'Active' AND active_flag = 1 LIMIT 1"
        );
        $check->execute([':sid' => $c['student_id'], ':isbn' => $c['isbn']]);
        if ($check->fetchColumn() !== false) {
            return $duplicate;
        }

        $stmt = $pdo->prepare(
            "INSERT INTO loans (student_id, isbn, book_title, borrow_date, due_date, loan_days, purpose, rules_accepted, status, active_flag)
             VALUES (:sid, :isbn, :title, :borrow, :due, :days, :purpose, 1, 'Active', 1)"
        );
        $stmt->execute([
            ':sid' => $c['student_id'],
            ':isbn' => $c['isbn'],
            ':title' => $c['book_title'],
            ':borrow' => $c['borrow_date'],
            ':due' => $c['due_date'],
            ':days' => $c['loan_days'],
            ':purpose' => $c['purpose'],
        ]);
        return ['ok' => true, 'id' => (int) $pdo->lastInsertId()];
    } catch (PDOException $e) {
        return is_duplicate_error($e) ? $duplicate : db_failure('lab2', $e);
    }
}

function process_loan(callable $pdoFactory, array $in): array
{
    $v = validate_loan($in);
    if ($v['errors']) {
        return ['ok' => false, 'errors' => $v['errors']];
    }
    try {
        $pdo = $pdoFactory();
    } catch (Throwable $e) {
        return db_failure('lab2', $e);
    }
    return create_loan($pdo, $v['clean']);
}
