<?php
declare(strict_types=1);

require __DIR__ . '/../shared/helpers.php';
require __DIR__ . '/../shared/db.php';

$rows = [];
$error = null;
try {
    $pdo = db(__DIR__ . '/schema.sql');
    $rows = $pdo->query(
        "SELECT loan_id, student_id, isbn, book_title, borrow_date, due_date
         FROM loans WHERE status = 'Active' AND active_flag = 1
         ORDER BY due_date ASC, loan_id ASC"
    )->fetchAll();
} catch (Throwable $e) {
    error_log('[lab2] active list failure: ' . $e->getMessage());
    $error = DB_ERROR_MESSAGE;
}
$today = today_date()->format('Y-m-d');

page_start('Lab 02 — Active loans', ['Borrow a book' => 'index.php', 'Active loans' => 'active.php']);
if ($error): ?>
    <div class="alert danger" role="alert"><?= e($error) ?></div>
<?php elseif (!$rows): ?>
    <p class="muted">There are no active loans.</p>
<?php else: ?>
    <div class="table-wrap"><table>
        <thead><tr><th>Loan</th><th>Student</th><th>ISBN</th><th>Title</th><th>Borrowed</th><th>Due</th><th>State</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): $overdue = $r['due_date'] < $today; ?>
            <tr>
                <td><?= (int) $r['loan_id'] ?></td>
                <td><?= e($r['student_id']) ?></td>
                <td><?= e($r['isbn']) ?></td>
                <td><?= e($r['book_title']) ?></td>
                <td><?= e($r['borrow_date']) ?></td>
                <td><?= e($r['due_date']) ?></td>
                <td><span class="badge<?= $overdue ? ' warn' : '' ?>"><?= $overdue ? 'Overdue' : 'On loan' ?></span></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
<?php endif;
page_end();
