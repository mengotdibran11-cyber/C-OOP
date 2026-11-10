<?php
declare(strict_types=1);

require __DIR__ . '/../shared/helpers.php';
require __DIR__ . '/../shared/db.php';
require __DIR__ . '/validate.php';

$status = trim((string) ($_GET['status'] ?? ''));
if ($status !== '' && !in_array($status, S6_STATUSES, true)) {
    $status = '';
}

$rows = [];
$counts = [];
$error = null;
try {
    $pdo = db(__DIR__ . '/schema.sql');
    foreach ($pdo->query('SELECT status, COUNT(*) AS n FROM support_tickets GROUP BY status')->fetchAll() as $r) {
        $counts[$r['status']] = (int) $r['n'];
    }
    if ($status !== '') {
        $stmt = $pdo->prepare(
            'SELECT ticket_ref, customer_name, category, priority, subject, status, created_at
             FROM support_tickets WHERE status = :status ORDER BY created_at DESC, ticket_id DESC LIMIT 200'
        );
        $stmt->execute([':status' => $status]);
    } else {
        $stmt = $pdo->query(
            'SELECT ticket_ref, customer_name, category, priority, subject, status, created_at
             FROM support_tickets ORDER BY status ASC, created_at DESC, ticket_id DESC LIMIT 200'
        );
    }
    $rows = $stmt->fetchAll();
} catch (Throwable $e) {
    error_log('[lab6] tickets failure: ' . $e->getMessage());
    $error = DB_ERROR_MESSAGE;
}

page_start('Lab 06 — Tickets by status', ['New ticket' => 'index.php', 'Tickets by status' => 'tickets.php']);
?>
<p>
    <a class="badge" href="tickets.php">All (<?= array_sum($counts) ?>)</a>
    <?php foreach (S6_STATUSES as $s): ?>
        <a class="badge" href="tickets.php?status=<?= e(rawurlencode($s)) ?>"><?= e($s) ?> (<?= (int) ($counts[$s] ?? 0) ?>)</a>
    <?php endforeach; ?>
</p>
<?php if ($error): ?>
    <div class="alert danger" role="alert"><?= e($error) ?></div>
<?php elseif (!$rows): ?>
    <p class="muted">No tickets<?= $status !== '' ? ' with status “' . e($status) . '”' : '' ?>.</p>
<?php else: ?>
    <div class="table-wrap"><table>
        <thead><tr><th>Reference</th><th>Customer</th><th>Category</th><th>Priority</th><th>Subject</th><th>Status</th><th>Created</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
            <tr>
                <td><?= e($r['ticket_ref']) ?></td>
                <td><?= e($r['customer_name']) ?></td>
                <td><?= e($r['category']) ?></td>
                <td><?= e($r['priority']) ?></td>
                <td><?= e($r['subject']) ?></td>
                <td><span class="badge"><?= e($r['status']) ?></span></td>
                <td><?= e($r['created_at']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
<?php endif;
page_end();
