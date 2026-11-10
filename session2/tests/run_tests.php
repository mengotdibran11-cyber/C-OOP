<?php
declare(strict_types=1);

require __DIR__ . '/../../shared/helpers.php';
require __DIR__ . '/../../shared/testing.php';
require __DIR__ . '/../validate.php';

$pdo = test_pdo(__DIR__ . '/../schema.sql');
$process = fn(array $in) => process_loan(fn() => $pdo, $in);
$t = new TestRun('Session 2 — Library Book Borrowing');

$d = fn(string $mod) => (new DateTimeImmutable('today'))->modify($mod)->format('Y-m-d');

$valid = [
    'student_id' => 'ST2024/001',
    'isbn' => '0-306-40615-2',
    'book_title' => 'Introduction to Algorithms',
    'borrow_date' => $d('-2 days'),
    'due_date' => $d('+12 days'),
    'loan_duration' => '14',
    'purpose' => 'Course reading',
    'agree' => '1',
];

$t->submit('TC01', 'Valid ISBN-10 loan is accepted', $valid, 'accepted', $process);
$t->submit('TC02', 'Valid ISBN-13 loan for the same student/other book is accepted', array_merge($valid, ['isbn' => '978-0-306-40615-7', 'book_title' => 'Another Book', 'loan_duration' => '']), 'accepted', $process);
$t->submit('TC03', 'Missing required values', ['purpose' => 'x'], 'rejected:agree,book_title,borrow_date,due_date,isbn,student_id', $process);
$t->submit('TC04', 'ISBN-10 with bad check digit is rejected', array_merge($valid, ['student_id' => 'ST2024/002', 'isbn' => '0306406153']), 'rejected:isbn', $process);
$t->submit('TC05', 'ISBN of wrong length (11 digits) is rejected', array_merge($valid, ['student_id' => 'ST2024/002', 'isbn' => '03064061520']), 'rejected:isbn', $process);
$t->submit('TC06', 'ISBN-10 ending in X (080442957X) is accepted', array_merge($valid, ['student_id' => 'ST2024/003', 'isbn' => '080442957x', 'loan_duration' => '']), 'accepted', $process);
$t->submit('TC07', 'Due date equal to borrow date is rejected', array_merge($valid, ['student_id' => 'ST2024/004', 'due_date' => $valid['borrow_date'], 'loan_duration' => '']), 'rejected:due_date', $process);
$t->submit('TC08', 'Due date before borrow date is rejected', array_merge($valid, ['student_id' => 'ST2024/004', 'due_date' => $d('-5 days'), 'loan_duration' => '']), 'rejected:due_date', $process);
$t->submit('TC09', 'Borrow date in the future is rejected', array_merge($valid, ['student_id' => 'ST2024/004', 'borrow_date' => $d('+1 day'), 'due_date' => $d('+10 days'), 'loan_duration' => '']), 'rejected:borrow_date', $process);
$t->submit('TC10', 'Agreement not checked is rejected', array_merge($valid, ['student_id' => 'ST2024/004', 'agree' => '']), 'rejected:agree', $process);
$t->submit('TC11', 'Loan duration that disagrees with dates is rejected', array_merge($valid, ['student_id' => 'ST2024/004', 'loan_duration' => '30']), 'rejected:loan_duration', $process);
$t->submit('TC12', 'Loan longer than 60 days is rejected', array_merge($valid, ['student_id' => 'ST2024/004', 'due_date' => $d('+90 days'), 'loan_duration' => '']), 'rejected:due_date', $process);
$t->submit('TC13', 'Duplicate ACTIVE loan (same student + same book) is rejected', $valid, 'rejected:isbn', $process);
$t->submit('TC14', 'Same student may NOT bypass duplicate by hyphenating the ISBN differently', array_merge($valid, ['isbn' => '0306406152', 'student_id' => 'st2024/001']), 'rejected:isbn', $process);
$t->submit('TC15', 'Different student may borrow the same book', array_merge($valid, ['student_id' => 'ST2024/009']), 'accepted', $process);

$pdo->exec("UPDATE loans SET status = 'Returned', active_flag = NULL WHERE student_id = 'ST2024/001' AND isbn = '0306406152'");
$t->submit('TC16', 'After the loan is returned the student can borrow the book again', $valid, 'accepted', $process);

$t->assert('TC17', 'Database UNIQUE index alone also blocks a second active loan (race-condition guard)', (function () use ($pdo): bool {
    try {
        $pdo->exec("INSERT INTO loans (student_id, isbn, book_title, borrow_date, due_date, loan_days, rules_accepted, status, active_flag)
                    VALUES ('ST2024/001', '0306406152', 'Raw insert', '2026-01-01', '2026-01-10', 9, 1, 'Active', 1)");
        return false;
    } catch (PDOException $e) {
        return is_duplicate_error($e);
    }
})());

$t->assert('TC18', 'ISBN checksum helper rejects 9780306406158 and accepts 9780306406157', !isbn_is_valid('9780306406158') && isbn_is_valid('9780306406157'));

$t->evidence('loans table', $pdo, 'SELECT loan_id, student_id, isbn, book_title, borrow_date, due_date, loan_days, status, active_flag FROM loans ORDER BY loan_id');
exit($t->finish(__DIR__ . '/TEST_RESULTS.md'));
