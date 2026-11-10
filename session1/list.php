<?php
declare(strict_types=1);

require __DIR__ . '/../shared/helpers.php';
require __DIR__ . '/../shared/db.php';

$q = trim((string) ($_GET['q'] ?? ''));
$rows = [];
$error = null;

try {
    $pdo = db(__DIR__ . '/schema.sql');
    $cols = 'id, full_name, email, telephone, programme, level, registered_at';
    if ($q !== '') {
        $like = '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $q) . '%';
        $stmt = $pdo->prepare(
            "SELECT $cols FROM students
             WHERE full_name LIKE :q1 ESCAPE '!' OR email LIKE :q2 ESCAPE '!' OR programme LIKE :q3 ESCAPE '!'
             ORDER BY registered_at DESC, id DESC LIMIT 200"
        );
        $stmt->execute([':q1' => $like, ':q2' => $like, ':q3' => $like]);
    } else {
        $stmt = $pdo->query("SELECT $cols FROM students ORDER BY registered_at DESC, id DESC LIMIT 200");
    }
    $rows = $stmt->fetchAll();
} catch (Throwable $e) {
    error_log('[lab1] list failure: ' . $e->getMessage());
    $error = DB_ERROR_MESSAGE;
}

page_start('Lab 01 — Registrations', ['Register' => 'index.php', 'Registrations list' => 'list.php']);
?>
<form class="search" method="get" action="" role="search">
    <label for="q" class="muted" style="margin:0;align-self:center">Search</label>
    <input type="search" id="q" name="q" value="<?= e($q) ?>" placeholder="Name, email or programme">
    <button class="btn primary" type="submit">Search</button>
</form>
<?php if ($error): ?>
    <div class="alert danger" role="alert"><?= e($error) ?></div>
<?php elseif (!$rows): ?>
    <p class="muted">No registrations found.</p>
<?php else: ?>
    <div class="table-wrap"><table>
        <thead><tr><th>#</th><th>Name</th><th>Email</th><th>Telephone</th><th>Programme</th><th>Level</th><th>Registered</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
            <tr>
                <td><?= (int) $r['id'] ?></td>
                <td><?= e($r['full_name']) ?></td>
                <td><?= e($r['email']) ?></td>
                <td><?= e($r['telephone']) ?></td>
                <td><?= e($r['programme']) ?></td>
                <td><?= e($r['level']) ?></td>
                <td><?= e($r['registered_at']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
    <p class="muted"><?= count($rows) ?> result(s).</p>
<?php endif; ?>
<?php page_end(); ?>
