<?php
declare(strict_types=1);

require __DIR__ . '/../shared/helpers.php';
require __DIR__ . '/../shared/db.php';
require __DIR__ . '/validate.php';

$dateParam = trim((string) ($_GET['date'] ?? ''));
$filterDate = $dateParam !== '' ? parse_date($dateParam) : null;
$filterError = ($dateParam !== '' && $filterDate === null) ? 'Enter a real date in the format YYYY-MM-DD.' : null;

$byDoctor = [];
$error = null;
if ($filterError === null) {
    try {
        $pdo = db(__DIR__ . '/schema.sql');
        if ($filterDate !== null) {
            $stmt = $pdo->prepare(
                'SELECT doctor, appointment_date, time_slot, patient_name, status FROM appointments
                 WHERE appointment_date = :d ORDER BY doctor, appointment_date, time_slot'
            );
            $stmt->execute([':d' => $filterDate->format('Y-m-d')]);
        } else {
            $stmt = $pdo->prepare(
                'SELECT doctor, appointment_date, time_slot, patient_name, status FROM appointments
                 WHERE appointment_date >= :d ORDER BY doctor, appointment_date, time_slot'
            );
            $stmt->execute([':d' => today_date()->format('Y-m-d')]);
        }
        foreach ($stmt->fetchAll() as $row) {
            $byDoctor[$row['doctor']][] = $row;
        }
    } catch (Throwable $e) {
        error_log('[lab3] schedule failure: ' . $e->getMessage());
        $error = DB_ERROR_MESSAGE;
    }
}

page_start('Lab 03 — Doctor schedule', ['Book appointment' => 'index.php', 'Doctor schedule' => 'schedule.php']);
?>
<form class="search" method="get" action="">
    <label for="date" class="muted" style="margin:0;align-self:center">Date</label>
    <input type="date" id="date" name="date" value="<?= e($dateParam) ?>"<?= $filterError ? ' aria-invalid="true" aria-describedby="date-error"' : '' ?>>
    <button class="btn primary" type="submit">Filter</button>
    <a class="btn" href="schedule.php" style="text-decoration:none">All upcoming</a>
</form>
<?php if ($filterError): ?>
    <span class="error" id="date-error" role="alert"><?= e($filterError) ?></span>
<?php elseif ($error): ?>
    <div class="alert danger" role="alert"><?= e($error) ?></div>
<?php elseif (!$byDoctor): ?>
    <p class="muted">No appointments found.</p>
<?php else: foreach ($byDoctor as $doctor => $rows): ?>
    <h2><?= e($doctor) ?></h2>
    <div class="table-wrap"><table>
        <thead><tr><th>Date</th><th>Time slot</th><th>Patient</th><th>Status</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
            <tr>
                <td><?= e($r['appointment_date']) ?></td>
                <td><?= e($r['time_slot']) ?></td>
                <td><?= e($r['patient_name']) ?></td>
                <td><span class="badge"><?= e($r['status']) ?></span></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
<?php endforeach; endif;
page_end();
