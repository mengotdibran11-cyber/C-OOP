<?php
declare(strict_types=1);

require __DIR__ . '/../../shared/helpers.php';
require __DIR__ . '/../../shared/testing.php';
require __DIR__ . '/../validate.php';

$pdo = test_pdo(__DIR__ . '/../schema.sql');
$process = fn(array $in) => process_registration(fn() => $pdo, $in);
$t = new TestRun('Session 1 — Student Registration');

$valid = [
    'full_name' => 'Amina Tchoumi',
    'email' => 'amina.tchoumi@example.com',
    'telephone' => '677123456',
    'date_of_birth' => '2003-05-14',
    'programme' => 'Software Engineering',
    'level' => 'Level 3',
    'gender' => 'Female',
    'policy' => '1',
];

$t->submit('TC01', 'Valid submission is accepted and inserted', $valid, 'accepted', $process);
$t->submit('TC02', 'All required values missing', ['gender' => ''], 'rejected:date_of_birth,email,full_name,level,policy,programme,telephone', $process);
$t->submit('TC03', 'Invalid email format', array_merge($valid, ['email' => 'amina@@example']), 'rejected:email', $process);
$t->submit('TC04', 'Duplicate email (different letter case) is rejected', array_merge($valid, ['email' => 'AMINA.TCHOUMI@Example.com', 'full_name' => 'Another Person']), 'rejected:email', $process);
$t->submit('TC05', 'Telephone with 8 digits is rejected', array_merge($valid, ['email' => 'a1@example.com', 'telephone' => '67712345']), 'rejected:telephone', $process);
$t->submit('TC06', 'Telephone with letters is rejected', array_merge($valid, ['email' => 'a2@example.com', 'telephone' => '6771ABC56']), 'rejected:telephone', $process);
$t->submit('TC07', 'Date of birth in the future is rejected', array_merge($valid, ['email' => 'a3@example.com', 'date_of_birth' => (new DateTimeImmutable('+1 day'))->format('Y-m-d')]), 'rejected:date_of_birth', $process);
$t->submit('TC08', 'Impossible calendar date (30 Feb) is rejected', array_merge($valid, ['email' => 'a4@example.com', 'date_of_birth' => '2003-02-30']), 'rejected:date_of_birth', $process);
$t->submit('TC09', 'Name containing digits is rejected', array_merge($valid, ['email' => 'a5@example.com', 'full_name' => 'Amina 123']), 'rejected:full_name', $process);
$t->submit('TC10', 'Name shorter than 3 characters is rejected', array_merge($valid, ['email' => 'a6@example.com', 'full_name' => 'Al']), 'rejected:full_name', $process);
$t->submit('TC11', 'Programme not in the list is rejected', array_merge($valid, ['email' => 'a7@example.com', 'programme' => 'Underwater Basket Weaving']), 'rejected:programme', $process);
$t->submit('TC12', 'Policy not accepted is rejected', array_merge($valid, ['email' => 'a8@example.com', 'policy' => '']), 'rejected:policy', $process);
$t->submit('TC13', 'SQL-injection style name is rejected by validation', array_merge($valid, ['email' => 'a9@example.com', 'full_name' => "Robert'); DROP TABLE students;--"]), 'rejected:full_name', $process);
$t->submit('TC14', "Apostrophe in a valid name is stored safely (prepared statement)", array_merge($valid, ['email' => 'jean@example.com', 'full_name' => "Jean O'Neil-Brown", 'telephone' => '+237 6 55 11 22 33']), 'accepted', $process);

$count = (int) $pdo->query('SELECT COUNT(*) FROM students')->fetchColumn();
$t->assert('TC15', 'Only the 2 valid registrations were stored (table still exists)', $count === 2, "row count = $count");

$page = (string) file_get_contents(__DIR__ . '/../index.php');
$mismatch = [];
foreach ([['programme', S1_PROGRAMMES, 'select'], ['level', S1_LEVELS, 'select'], ['gender', S1_GENDERS, 'radio']] as [$field, $allowed, $kind]) {
    $found = $kind === 'select' ? template_select_values($page, $field) : template_radio_values($page, $field);
    if ($found !== $allowed) {
        $mismatch[] = $field;
    }
}
$t->assert('TC16', 'index.php dropdown/radio options match the lists the validator accepts', $mismatch === [], $mismatch ? 'mismatch: ' . implode(',', $mismatch) : 'programme, level, gender match');

require_once __DIR__ . '/../../shared/template.php';
$filled = fill_template(
    '<input value="{{value:full_name}}"><i>{{error:email}}</i>{{selected:level:Level 2}}',
    ['old' => ['full_name' => '<b>"x" {{var:evil}}</b>', 'level' => 'Level 2'], 'errors' => ['email' => 'Bad <email>'], 'vars' => ['evil' => 'LEAK']]
);
$t->assert('TC17', 'Template inserts values HTML-escaped and never re-scans inserted text', !str_contains($filled, '<b>') && str_contains($filled, '&lt;b&gt;') && !str_contains($filled, 'LEAK') && str_contains($filled, 'Bad &lt;email&gt;') && str_contains($filled, 'selected'), $filled);
$t->assert('TC18', 'Unknown placeholders are left visible so typos are easy to spot', fill_template('{{value:}} {{nope:x}}', []) === '{{value:}} {{nope:x}}');

$t->evidence('saved records', $pdo, 'SELECT id, full_name, email, telephone, programme, level, registered_at FROM students ORDER BY id');
exit($t->finish(__DIR__ . '/TEST_RESULTS.md'));
