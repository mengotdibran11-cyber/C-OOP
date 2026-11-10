<?php
declare(strict_types=1);

require __DIR__ . '/../../shared/helpers.php';
require __DIR__ . '/../../shared/testing.php';
require __DIR__ . '/../validate.php';

$pdo = test_pdo(__DIR__ . '/../schema.sql');
$process = fn(array $in) => process_event_registration(fn() => $pdo, $in);
$t = new TestRun('Session 5 — Event Registration');

$valid = [
    'participant_name' => 'Brice Nkeng',
    'email' => 'brice.nkeng@example.com',
    'phone' => '699 00 11 22',
    'workshop' => 'php-mysql',
    'attendance_mode' => 'In-person',
    'seats' => '2',
    'special_requirements' => 'Wheelchair access please.',
    'terms' => '1',
];

$t->submit('TC01', 'Valid registration is accepted', $valid, 'accepted', $process);
$t->submit('TC02', 'Missing required values', ['phone' => ''], 'rejected:attendance_mode,email,participant_name,seats,terms,workshop', $process);
$t->submit('TC03', 'Invalid email is rejected', array_merge($valid, ['email' => 'brice@']), 'rejected:email', $process);
$t->submit('TC04', 'Seats = 0 is rejected', array_merge($valid, ['email' => 'b1@example.com', 'seats' => '0']), 'rejected:seats', $process);
$t->submit('TC05', 'Seats = 4 is rejected', array_merge($valid, ['email' => 'b1@example.com', 'seats' => '4']), 'rejected:seats', $process);
$t->submit('TC06', 'Seats = 1.5 is rejected (must be an integer)', array_merge($valid, ['email' => 'b1@example.com', 'seats' => '1.5']), 'rejected:seats', $process);
$t->submit('TC07', 'Seats = 1 boundary is accepted', array_merge($valid, ['email' => 'b2@example.com', 'seats' => '1']), 'accepted', $process);
$t->submit('TC08', 'Seats = 3 boundary is accepted', array_merge($valid, ['email' => 'b3@example.com', 'seats' => '3']), 'accepted', $process);
$t->submit('TC09', 'Special requirements over 300 characters is rejected', array_merge($valid, ['email' => 'b4@example.com', 'special_requirements' => str_repeat('s', 301)]), 'rejected:special_requirements', $process);
$t->submit('TC10', 'Special requirements of exactly 300 characters is accepted', array_merge($valid, ['email' => 'b4@example.com', 'special_requirements' => str_repeat('s', 300)]), 'accepted', $process);
$t->submit('TC11', 'Unknown workshop code is rejected', array_merge($valid, ['email' => 'b5@example.com', 'workshop' => 'hack-the-bank']), 'rejected:workshop', $process);
$t->submit('TC12', 'Unknown attendance mode is rejected', array_merge($valid, ['email' => 'b5@example.com', 'attendance_mode' => 'Teleport']), 'rejected:attendance_mode', $process);
$t->submit('TC13', 'Terms not accepted is rejected', array_merge($valid, ['email' => 'b5@example.com', 'terms' => '']), 'rejected:terms', $process);
$t->submit('TC14', 'Phone is optional (blank accepted)', array_merge($valid, ['email' => 'b6@example.com', 'phone' => '']), 'accepted', $process);
$t->submit('TC15', 'Invalid phone (when supplied) is rejected', array_merge($valid, ['email' => 'b7@example.com', 'phone' => '999']), 'rejected:phone', $process);
$t->submit('TC16', 'REPEAT registration: same email + same workshop is rejected', array_merge($valid, ['email' => 'BRICE.NKENG@example.com', 'seats' => '1']), 'rejected:email', $process);
$t->submit('TC17', 'Same email for a DIFFERENT workshop is accepted (uniqueness is email+workshop)', array_merge($valid, ['workshop' => 'web-security', 'attendance_mode' => 'Online']), 'accepted', $process);

$t->assert('TC18', 'Database UNIQUE(email, workshop) alone blocks a repeat insert', (function () use ($pdo): bool {
    try {
        $pdo->exec("INSERT INTO event_registrations (participant_name, email, workshop, attendance_mode, seats, terms_accepted)
                    VALUES ('Raw Insert', 'brice.nkeng@example.com', 'php-mysql', 'Online', 1, 1)");
        return false;
    } catch (PDOException $e) {
        return is_duplicate_error($e);
    }
})());

$page = (string) file_get_contents(__DIR__ . '/../index.php');
$mismatch = [];
if (template_select_values($page, 'workshop') !== array_keys(S5_WORKSHOPS)) { $mismatch[] = 'workshop'; }
if (template_radio_values($page, 'attendance_mode') !== S5_MODES) { $mismatch[] = 'attendance_mode'; }
$t->assert('TC19', 'index.php workshop and attendance options match what the validator accepts', $mismatch === [], $mismatch ? 'mismatch: ' . implode(',', $mismatch) : 'workshop, attendance_mode match');

$t->evidence('event_registrations table', $pdo, 'SELECT id, participant_name, email, workshop, attendance_mode, seats FROM event_registrations ORDER BY id');
$t->evidence('totals per workshop (extension query)', $pdo, 'SELECT workshop, COUNT(*) AS registrations, SUM(seats) AS seats FROM event_registrations GROUP BY workshop ORDER BY workshop');
exit($t->finish(__DIR__ . '/TEST_RESULTS.md'));
