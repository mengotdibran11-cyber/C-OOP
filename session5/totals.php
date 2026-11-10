<?php
declare(strict_types=1);

require __DIR__ . '/../shared/helpers.php';
require __DIR__ . '/../shared/db.php';
require __DIR__ . '/validate.php';

$totals = [];
$error = null;
try {
    $pdo = db(__DIR__ . '/schema.sql');
    $stmt = $pdo->query(
        'SELECT workshop, COUNT(*) AS registrations, SUM(seats) AS seats FROM event_registrations GROUP BY workshop'
    );
    foreach ($stmt->fetchAll() as $row) {
        $totals[$row['workshop']] = $row;
    }
} catch (Throwable $e) {
    error_log('[lab5] totals failure: ' . $e->getMessage());
    $error = DB_ERROR_MESSAGE;
}

page_start('Lab 05 — Totals per workshop', ['Register' => 'index.php', 'Totals per workshop' => 'totals.php']);
if ($error): ?>
    <div class="alert danger" role="alert"><?= e($error) ?></div>
<?php else: ?>
    <div class="table-wrap"><table>
        <thead><tr><th>Workshop</th><th>Registrations</th><th>Seats reserved</th></tr></thead>
        <tbody>
        <?php foreach (S5_WORKSHOPS as $code => $title): $r = $totals[$code] ?? null; ?>
            <tr>
                <td><?= e($title) ?></td>
                <td><?= (int) ($r['registrations'] ?? 0) ?></td>
                <td><?= (int) ($r['seats'] ?? 0) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
<?php endif;
page_end();
