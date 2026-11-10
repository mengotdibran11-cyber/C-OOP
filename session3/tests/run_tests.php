<?php
declare(strict_types=1);

require __DIR__ . '/../../shared/helpers.php';
require __DIR__ . '/../../shared/testing.php';
require __DIR__ . '/../validate.php';

$pdo = test_pdo(__DIR__ . '/../schema.sql');
$process = fn(array $in) => process_appointment(fn() => $pdo, $in);
$t = new TestRun('Session 3 — Clinic Appointment Booking');

$d = fn(string $mod) => (new DateTimeImmutable('today'))->modify($mod)->format('Y-m-d');

$valid = [
    'patient_name' => 'Test Patient',
    'email' => 'patient@example.com',
    'phone' => '655 11 22 33',
    'department' => 'General Medicine',
    'doctor' => 'Dr. Ngu Tabi',
    'appointment_date' => $d('+5 days'),
    'time_slot' => '09:00-09:30',
    'reason' => 'Sample headache and mild fever for three days.',
    'consent' => '1',
];

$t->submit('TC01', 'Valid appointment is accepted (status Pending)', $valid, 'accepted', $process);
$t->submit('TC02', 'Email is optional — valid booking without email is accepted', array_merge($valid, ['email' => '', 'time_slot' => '09:30-10:00']), 'accepted', $process);
$t->submit('TC03', 'Missing required values', ['email' => ''], 'rejected:appointment_date,consent,department,doctor,patient_name,phone,reason,time_slot', $process);
$t->submit('TC04', 'Invalid email (when supplied) is rejected', array_merge($valid, ['email' => 'not-an-email', 'time_slot' => '10:00-10:30']), 'rejected:email', $process);
$t->submit('TC05', 'Appointment today is rejected (must be a future date)', array_merge($valid, ['appointment_date' => $d('today'), 'time_slot' => '10:00-10:30']), 'rejected:appointment_date', $process);
$t->submit('TC06', 'Appointment in the past is rejected', array_merge($valid, ['appointment_date' => $d('-3 days'), 'time_slot' => '10:00-10:30']), 'rejected:appointment_date', $process);
$t->submit('TC07', 'Appointment more than 90 days ahead is rejected', array_merge($valid, ['appointment_date' => $d('+200 days'), 'time_slot' => '10:00-10:30']), 'rejected:appointment_date', $process);
$t->submit('TC08', 'Reason shorter than 10 characters is rejected', array_merge($valid, ['reason' => 'Pain', 'time_slot' => '10:00-10:30']), 'rejected:reason', $process);
$t->submit('TC09', 'Reason longer than 500 characters is rejected', array_merge($valid, ['reason' => str_repeat('a', 501), 'time_slot' => '10:00-10:30']), 'rejected:reason', $process);
$t->submit('TC10', 'Reason of exactly 500 characters is accepted', array_merge($valid, ['reason' => str_repeat('a', 500), 'time_slot' => '10:00-10:30']), 'accepted', $process);
$t->submit('TC11', 'Consent missing is rejected', array_merge($valid, ['consent' => '', 'time_slot' => '10:30-11:00']), 'rejected:consent', $process);
$t->submit('TC12', 'Doctor from a different department is rejected', array_merge($valid, ['doctor' => 'Dr. Samuel Fotso', 'time_slot' => '10:30-11:00']), 'rejected:doctor', $process);
$t->submit('TC13', 'Unknown time slot is rejected', array_merge($valid, ['time_slot' => '03:33-04:00']), 'rejected:time_slot', $process);
$t->submit('TC14', 'Invalid phone number is rejected', array_merge($valid, ['phone' => '12345', 'time_slot' => '10:30-11:00']), 'rejected:phone', $process);
$t->submit('TC15', 'DOUBLE BOOKING: same doctor, date and slot is rejected', array_merge($valid, ['patient_name' => 'Second Patient']), 'rejected:time_slot', $process);
$t->submit('TC16', 'Same slot with a different doctor is accepted', array_merge($valid, ['department' => 'Dentistry', 'doctor' => 'Dr. Aisha Bello']), 'accepted', $process);
$t->submit('TC17', 'Same doctor and slot on a different date is accepted', array_merge($valid, ['appointment_date' => $d('+6 days')]), 'accepted', $process);

$t->assert('TC18', 'Database UNIQUE index alone blocks a duplicate booking', (function () use ($pdo, $valid): bool {
    try {
        $pdo->prepare("INSERT INTO appointments (patient_name, phone, department, doctor, appointment_date, time_slot, reason, consent_given)
                       VALUES ('Raw', '655112233', 'General Medicine', 'Dr. Ngu Tabi', :d, '09:00-09:30', 'raw insert test', 1)")
            ->execute([':d' => $valid['appointment_date']]);
        return false;
    } catch (PDOException $e) {
        return is_duplicate_error($e);
    }
})());

$statuses = $pdo->query("SELECT DISTINCT status FROM appointments")->fetchAll(PDO::FETCH_COLUMN);
$t->assert('TC19', "Every saved appointment has the initial status 'Pending'", $statuses === ['Pending'], 'statuses: ' . implode(',', $statuses));

$page = (string) file_get_contents(__DIR__ . '/../index.php');
$mismatch = [];
if (template_select_values($page, 'department') !== array_keys(S3_DOCTORS)) { $mismatch[] = 'department'; }
if (template_select_values($page, 'doctor') !== array_merge(...array_values(S3_DOCTORS))) { $mismatch[] = 'doctor'; }
if (template_select_values($page, 'time_slot') !== S3_SLOTS) { $mismatch[] = 'time_slot'; }
$t->assert('TC20', 'index.php dropdown options match the lists the validator accepts', $mismatch === [], $mismatch ? 'mismatch: ' . implode(',', $mismatch) : 'department, doctor, time_slot match');

$t->evidence('appointments table', $pdo, 'SELECT id, patient_name, phone, department, doctor, appointment_date, time_slot, status FROM appointments ORDER BY id');
$t->evidence('schedule by doctor (extension query)', $pdo, 'SELECT doctor, appointment_date, time_slot, patient_name FROM appointments ORDER BY doctor, appointment_date, time_slot');
exit($t->finish(__DIR__ . '/TEST_RESULTS.md'));
