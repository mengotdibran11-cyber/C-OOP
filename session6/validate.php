<?php
declare(strict_types=1);

require_once __DIR__ . '/../shared/upload.php';

const S6_CATEGORIES = ['Login & Account', 'Network & Connectivity', 'Software Bug', 'Hardware Fault', 'Billing', 'Other'];
const S6_PRIORITIES = ['Low', 'Medium', 'High'];
const S6_STATUSES = ['Pending', 'In Progress', 'Resolved', 'Closed'];

const S6_DUPLICATE_WINDOW = '-24 hours';

function validate_ticket(array $in, ?array $file): array
{
    $errors = [];

    $name = preg_replace('/\s+/u', ' ', field($in, 'customer_name')) ?? '';
    if ($name === '') {
        $errors['customer_name'] = 'Customer name is required.';
    } elseif (text_len($name) < 3 || text_len($name) > 80) {
        $errors['customer_name'] = 'Customer name must be between 3 and 80 characters.';
    } elseif (!preg_match("/^\p{L}[\p{L}\p{M} .'’\-]*$/u", $name)) {
        $errors['customer_name'] = 'Use letters only (spaces, apostrophes, hyphens and full stops are allowed).';
    }

    $email = mb_strtolower(field($in, 'email'));
    if ($email === '') {
        $errors['email'] = 'Email is required.';
    } elseif (strlen($email) > 150 || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
        $errors['email'] = 'Enter a valid email address, e.g. name@example.com.';
    }

    $category = field($in, 'category');
    if ($category === '') {
        $errors['category'] = 'Please choose an issue category.';
    } elseif (!in_array($category, S6_CATEGORIES, true)) {
        $errors['category'] = 'Please choose a category from the list.';
    }

    $priority = field($in, 'priority');
    if ($priority === '') {
        $errors['priority'] = 'Please choose a priority.';
    } elseif (!in_array($priority, S6_PRIORITIES, true)) {
        $errors['priority'] = 'Please choose one of the listed priorities.';
    }

    $subject = preg_replace('/\s+/u', ' ', field($in, 'subject')) ?? '';
    if ($subject === '') {
        $errors['subject'] = 'Subject is required.';
    } elseif (text_len($subject) < 5 || text_len($subject) > 120) {
        $errors['subject'] = 'Subject must be between 5 and 120 characters (currently ' . text_len($subject) . ').';
    } elseif (has_control_chars($subject)) {
        $errors['subject'] = 'Subject contains invalid characters.';
    }

    $description = text_field($in, 'description');
    if ($description === '') {
        $errors['description'] = 'Issue description is required.';
    } elseif (text_len($description) < 20 || text_len($description) > 2000) {
        $errors['description'] = 'Description must be between 20 and 2000 characters (currently ' . text_len($description) . ').';
    }

    $screenshot = validate_image_upload($file);
    if ($screenshot['error'] !== null) {
        $errors['screenshot'] = $screenshot['error'];
    }

    if (!is_checked($in, 'confirm')) {
        $errors['confirm'] = 'Please confirm that the details are accurate.';
    }

    return [
        'errors' => $errors,
        'clean' => [
            'customer_name' => $name,
            'email' => $email,
            'category' => $category,
            'priority' => $priority,
            'subject' => $subject,
            'description' => $description,
        ],
        'screenshot' => $screenshot,
    ];
}

function ticket_reference(int $id): string
{
    return sprintf('TKT-%s-%06d', date('Y'), $id);
}

function process_ticket(callable $pdoFactory, array $in, ?array $file, string $uploadDir, ?callable $mover = null): array
{
    $v = validate_ticket($in, $file);
    if ($v['errors']) {
        return ['ok' => false, 'errors' => $v['errors']];
    }
    $c = $v['clean'];
    $duplicate = ['ok' => false, 'errors' => [
        'subject' => 'You already have a pending ticket with this subject from the last 24 hours. Please wait for a reply or change the subject.',
    ]];

    try {
        $pdo = $pdoFactory();
    } catch (Throwable $e) {
        return db_failure('lab6', $e);
    }

    $storedPath = null;
    $inTransaction = false;
    try {
        $check = $pdo->prepare(
            "SELECT 1 FROM support_tickets
             WHERE email = :email AND LOWER(subject) = :subject AND status = 'Pending' AND created_at >= :since LIMIT 1"
        );
        $check->execute([
            ':email' => $c['email'],
            ':subject' => mb_strtolower($c['subject']),
            ':since' => (new DateTimeImmutable('now'))->modify(S6_DUPLICATE_WINDOW)->format('Y-m-d H:i:s'),
        ]);
        if ($check->fetchColumn() !== false) {
            return $duplicate;
        }

        if ($v['screenshot']['tmp'] !== null) {
            ensure_upload_dir($uploadDir);
            $fileName = unique_upload_name($v['screenshot']['ext']);
            $mover ??= 'move_uploaded_file';
            if (!$mover($v['screenshot']['tmp'], rtrim($uploadDir, '/') . '/' . $fileName)) {
                return ['ok' => false, 'errors' => ['screenshot' => 'The screenshot could not be saved. Please try again.']];
            }
            $storedPath = 'uploads/' . $fileName;
        }

        $pdo->beginTransaction();
        $inTransaction = true;
        $stmt = $pdo->prepare(
            "INSERT INTO support_tickets (customer_name, email, category, priority, subject, description, screenshot_path, details_confirmed, status, created_at)
             VALUES (:name, :email, :cat, :prio, :subject, :descr, :shot, 1, 'Pending', :created)"
        );
        $stmt->execute([
            ':name' => $c['customer_name'],
            ':email' => $c['email'],
            ':cat' => $c['category'],
            ':prio' => $c['priority'],
            ':subject' => $c['subject'],
            ':descr' => $c['description'],
            ':shot' => $storedPath,
            ':created' => date('Y-m-d H:i:s'),
        ]);
        $id = (int) $pdo->lastInsertId();
        $ref = ticket_reference($id);
        $pdo->prepare('UPDATE support_tickets SET ticket_ref = :ref WHERE ticket_id = :id')
            ->execute([':ref' => $ref, ':id' => $id]);
        $pdo->commit();
        $inTransaction = false;

        return ['ok' => true, 'id' => $id, 'ref' => $ref];
    } catch (Throwable $e) {
        if ($inTransaction && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        if ($storedPath !== null) {
            @unlink(rtrim($uploadDir, '/') . '/' . basename($storedPath));
        }
        return db_failure('lab6', $e);
    }
}
