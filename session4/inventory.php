<?php
declare(strict_types=1);

require __DIR__ . '/../shared/helpers.php';
require __DIR__ . '/../shared/db.php';

$rows = [];
$error = null;
try {
    $pdo = db(__DIR__ . '/schema.sql');
    $rows = $pdo->query(
        'SELECT id, name, sku, category, unit_price, quantity, is_available, image_path
         FROM products ORDER BY category ASC, name ASC, id ASC'
    )->fetchAll();
} catch (Throwable $e) {
    error_log('[lab4] inventory failure: ' . $e->getMessage());
    $error = DB_ERROR_MESSAGE;
}

page_start('Lab 04 — Inventory by category', ['Add product' => 'index.php', 'Inventory' => 'inventory.php']);
if ($error): ?>
    <div class="alert danger" role="alert"><?= e($error) ?></div>
<?php elseif (!$rows): ?>
    <p class="muted">No products yet.</p>
<?php else: ?>
    <div class="table-wrap"><table>
        <thead><tr><th>Image</th><th>Category</th><th>Name</th><th>SKU</th><th>Price (XAF)</th><th>Qty</th><th>For sale</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r):
            $img = (string) $r['image_path'];
            $safeImg = preg_match('#^uploads/[a-f0-9]{32}\.(jpg|png)$#', $img) === 1; ?>
            <tr>
                <td><?php if ($safeImg): ?><img class="thumb" src="<?= e($img) ?>" alt="<?= e($r['name']) ?>"><?php else: ?><span class="muted">—</span><?php endif; ?></td>
                <td><?= e($r['category']) ?></td>
                <td><?= e($r['name']) ?></td>
                <td><?= e($r['sku']) ?></td>
                <td><?= e(number_format((float) $r['unit_price'], 2, '.', ' ')) ?></td>
                <td><?= (int) $r['quantity'] ?></td>
                <td><?= $r['is_available'] ? 'Yes' : 'No' ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
<?php endif;
page_end();
