<?php
declare(strict_types=1);

require __DIR__ . '/../../shared/helpers.php';
require __DIR__ . '/../../shared/testing.php';
require __DIR__ . '/../validate.php';

$pdo = test_pdo(__DIR__ . '/../schema.sql');
$uploadDir = sys_get_temp_dir() . '/lab6_uploads_' . bin2hex(random_bytes(4));
$work = sys_get_temp_dir() . '/lab6_work_' . bin2hex(random_bytes(4));
mkdir($work);

$png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==');
$makeFile = function (string $name, string $bytes) use ($work): array {
    $p = $work . '/' . bin2hex(random_bytes(4));
    file_put_contents($p, $bytes);
    return ['name' => $name, 'type' => 'image/png', 'tmp_name' => $p, 'error' => UPLOAD_ERR_OK, 'size' => filesize($p)];
};
$noFile = ['name' => '', 'type' => '', 'tmp_name' => '', 'error' => UPLOAD_ERR_NO_FILE, 'size' => 0];

$process = fn(array $in, ?array $file = null) => process_ticket(fn() => $pdo, $in, $file ?? $noFile, $uploadDir, 'copy');
$t = new TestRun('Session 6 — Customer Support Ticket');
$run = fn(string $id, string $desc, array $in, string $exp, ?array $file = null) => $t->submit($id, $desc, $in, $exp, fn($i) => $process($i, $file));

$valid = [
    'customer_name' => 'Carine Mbarga',
    'email' => 'carine.mbarga@example.com',
    'category' => 'Software Bug',
    'priority' => 'High',
    'subject' => 'App crashes when saving a file',
    'description' => 'Every time I press Save the application closes without any message.',
    'confirm' => '1',
];

$r1 = $run('TC01', 'Valid ticket without screenshot is accepted', $valid, 'accepted');
$t->assert('TC02', 'A ticket reference is generated after the insert (format TKT-YYYY-000001)', ($r1['ref'] ?? '') === 'TKT-' . date('Y') . '-000001', 'ref = ' . ($r1['ref'] ?? 'none'));
$run('TC03', 'Missing required values', ['screenshot' => ''], 'rejected:category,confirm,customer_name,description,email,priority,subject');
$run('TC04', 'Invalid email is rejected', array_merge($valid, ['email' => 'carine.example.com', 'subject' => 'Different subject one']), 'rejected:email');
$run('TC05', 'Subject shorter than 5 characters is rejected', array_merge($valid, ['subject' => 'Help']), 'rejected:subject');
$run('TC06', 'Subject longer than 120 characters is rejected', array_merge($valid, ['subject' => str_repeat('s', 121)]), 'rejected:subject');
$run('TC07', 'Description shorter than 20 characters is rejected', array_merge($valid, ['subject' => 'Different subject two', 'description' => 'It is broken.']), 'rejected:description');
$run('TC08', 'Description longer than 2000 characters is rejected', array_merge($valid, ['subject' => 'Different subject two', 'description' => str_repeat('d', 2001)]), 'rejected:description');
$run('TC09', 'Description of exactly 2000 characters is accepted', array_merge($valid, ['subject' => 'Different subject three', 'description' => str_repeat('d', 2000)]), 'accepted');
$run('TC10', 'Category outside the list is rejected', array_merge($valid, ['subject' => 'Different subject four', 'category' => 'Time Travel']), 'rejected:category');
$run('TC11', 'Priority outside the list is rejected', array_merge($valid, ['subject' => 'Different subject four', 'priority' => 'Urgent!!!']), 'rejected:priority');
$run('TC12', 'Confirmation not ticked is rejected', array_merge($valid, ['subject' => 'Different subject four', 'confirm' => '']), 'rejected:confirm');
$run('TC13', 'CONFLICT: same email + same subject while still Pending (case-insensitive) is rejected', array_merge($valid, ['subject' => 'APP CRASHES WHEN SAVING A FILE']), 'rejected:subject');
$run('TC14', 'Same subject from a different email is accepted', array_merge($valid, ['email' => 'other.user@example.com']), 'accepted');

$pdo->exec("UPDATE support_tickets SET status = 'Resolved' WHERE ticket_ref = 'TKT-" . date('Y') . "-000001'");
$run('TC15', 'After the earlier ticket is Resolved the same subject may be submitted again', $valid, 'accepted');

$run('TC16', 'Valid PNG screenshot is accepted', array_merge($valid, ['subject' => 'Screenshot attached ticket']), 'accepted', $makeFile('Screen Shot 2026.png', $png));
$shot = $pdo->query("SELECT screenshot_path FROM support_tickets WHERE subject = 'Screenshot attached ticket'")->fetchColumn();
$t->assert('TC17', 'Screenshot saved under a generated name (browser file name ignored) and the file exists',
    is_string($shot) && preg_match('#^uploads/[a-f0-9]{32}\.png$#', $shot) === 1 && is_file($uploadDir . '/' . basename($shot)), 'screenshot_path = ' . var_export($shot, true));
$run('TC18', 'Script disguised as screenshot is rejected', array_merge($valid, ['subject' => 'Evil upload test']), 'rejected:screenshot', $makeFile('x.png', '<?php echo 1; ?>'));
$run('TC19', 'Screenshot of 2 MB or more is rejected', array_merge($valid, ['subject' => 'Big upload test']), 'rejected:screenshot', $makeFile('big.png', $png . str_repeat("\0", 2 * 1024 * 1024)));
$run('TC20', 'Screenshot just under 2 MB is accepted', array_merge($valid, ['subject' => 'Just under limit test']), 'accepted', $makeFile('big.png', $png . str_repeat("\0", 2 * 1024 * 1024 - strlen($png) - 1)));
$run('TC21', 'Upload error (exceeds server limit) is rejected', array_merge($valid, ['subject' => 'Ini size test']), 'rejected:screenshot', ['name' => 'x.png', 'type' => 'image/png', 'tmp_name' => '', 'error' => UPLOAD_ERR_INI_SIZE, 'size' => 0]);

$ids = $pdo->query('SELECT ticket_id, ticket_ref FROM support_tickets ORDER BY ticket_id')->fetchAll();
$refsOk = array_reduce($ids, fn($ok, $r) => $ok && $r['ticket_ref'] === ticket_reference((int) $r['ticket_id']), true);
$t->assert('TC22', 'Every saved ticket has a unique reference matching its id', $refsOk && count(array_unique(array_column($ids, 'ticket_ref'))) === count($ids), count($ids) . ' tickets');
$statuses = $pdo->query("SELECT DISTINCT status FROM support_tickets WHERE ticket_ref <> 'TKT-" . date('Y') . "-000001' ORDER BY status")->fetchAll(PDO::FETCH_COLUMN);
$t->assert('TC23', "New tickets start as 'Pending'", $statuses === ['Pending'], 'statuses: ' . implode(',', $statuses));
$t->assert('TC24', 'created_at is stored for every ticket', (int) $pdo->query('SELECT COUNT(*) FROM support_tickets WHERE created_at IS NULL')->fetchColumn() === 0);

$page = (string) file_get_contents(__DIR__ . '/../index.php');
$mismatch = [];
if (template_select_values($page, 'category') !== S6_CATEGORIES) { $mismatch[] = 'category'; }
if (template_radio_values($page, 'priority') !== S6_PRIORITIES) { $mismatch[] = 'priority'; }
$t->assert('TC25', 'index.php category and priority options match what the validator accepts', $mismatch === [], $mismatch ? 'mismatch: ' . implode(',', $mismatch) : 'category, priority match');

$t->evidence('support_tickets table', $pdo, 'SELECT ticket_id, ticket_ref, customer_name, category, priority, subject, screenshot_path, status, created_at FROM support_tickets ORDER BY ticket_id');
$t->evidence('tickets by status (extension query)', $pdo, 'SELECT status, COUNT(*) AS tickets FROM support_tickets GROUP BY status ORDER BY status');

array_map('unlink', glob($uploadDir . '/{.htaccess,*}', GLOB_BRACE) ?: []);
@rmdir($uploadDir);
array_map('unlink', glob($work . '/*') ?: []);
@rmdir($work);
exit($t->finish(__DIR__ . '/TEST_RESULTS.md'));
