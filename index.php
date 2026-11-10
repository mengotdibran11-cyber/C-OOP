<?php
declare(strict_types=1);
$labs = [
    'session1' => 'Session 1 — Student Registration',
    'session2' => 'Session 2 — Library Book Borrowing',
    'session3' => 'Session 3 — Clinic Appointment Booking',
    'session4' => 'Session 4 — E-Commerce Product Entry',
    'session5' => 'Session 5 — Event Registration',
    'session6' => 'Session 6 — Customer Support Ticket',
];
?><!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>PHP &amp; MySQL Labs</title><link rel="stylesheet" href="shared/style.css"></head>
<body><header class="site"><div class="wrap"><p class="kicker">Degree Year 3 · PHP &amp; MySQL</p><h1>Six form-validation labs</h1></div></header>
<main class="wrap"><div class="card"><ul>
<?php foreach ($labs as $dir => $title): ?>
    <li><a href="<?= htmlspecialchars($dir) ?>/"><?= htmlspecialchars($title) ?></a></li>
<?php endforeach; ?>
</ul></div></main></body></html>
