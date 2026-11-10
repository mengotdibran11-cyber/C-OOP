<?php
declare(strict_types=1);

require __DIR__ . '/../../shared/helpers.php';
require __DIR__ . '/../../shared/testing.php';
require __DIR__ . '/../validate.php';

$pdo = test_pdo(__DIR__ . '/../schema.sql');
$uploadDir = sys_get_temp_dir() . '/lab4_uploads_' . bin2hex(random_bytes(4));
$work = sys_get_temp_dir() . '/lab4_work_' . bin2hex(random_bytes(4));
mkdir($work);

$png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==');
$makeFile = function (string $name, string $bytes) use ($work): array {
    $p = $work . '/' . bin2hex(random_bytes(4));
    file_put_contents($p, $bytes);
    return ['name' => $name, 'type' => 'image/png', 'tmp_name' => $p, 'error' => UPLOAD_ERR_OK, 'size' => filesize($p)];
};
$noFile = ['name' => '', 'type' => '', 'tmp_name' => '', 'error' => UPLOAD_ERR_NO_FILE, 'size' => 0];

$process = fn(array $in, ?array $file = null) => process_product(fn() => $pdo, $in, $file ?? $noFile, $uploadDir, 'copy');
$t = new TestRun('Session 4 — E-Commerce Product Entry');

$valid = [
    'name' => 'Wireless Mouse',
    'sku' => 'elec-0042',
    'category' => 'Electronics',
    'unit_price' => '7500',
    'quantity' => '25',
    'description' => 'Sample product for the practical.',
    'is_available' => '1',
];
$run = fn(string $id, string $desc, array $in, string $exp, ?array $file = null) => $t->submit($id, $desc, $in, $exp, fn($i) => $process($i, $file));

$run('TC01', 'Valid product without image is accepted', $valid, 'accepted');
$run('TC02', 'Missing required values', ['description' => ''], 'rejected:category,name,quantity,sku,unit_price');
$run('TC03', 'Name shorter than 3 characters is rejected', array_merge($valid, ['sku' => 'A-100', 'name' => 'ab']), 'rejected:name');
$run('TC04', 'Name longer than 100 characters is rejected', array_merge($valid, ['sku' => 'A-100', 'name' => str_repeat('n', 101)]), 'rejected:name');
$run('TC05', 'Price of zero is rejected', array_merge($valid, ['sku' => 'A-100', 'unit_price' => '0']), 'rejected:unit_price');
$run('TC06', 'Negative price is rejected', array_merge($valid, ['sku' => 'A-100', 'unit_price' => '-5']), 'rejected:unit_price');
$run('TC07', 'Non-numeric price is rejected', array_merge($valid, ['sku' => 'A-100', 'unit_price' => '12abc']), 'rejected:unit_price');
$run('TC08', 'Quantity zero is accepted (non-negative)', array_merge($valid, ['sku' => 'ZERO-STOCK', 'quantity' => '0']), 'accepted');
$run('TC09', 'Negative quantity is rejected', array_merge($valid, ['sku' => 'A-100', 'quantity' => '-1']), 'rejected:quantity');
$run('TC10', 'Decimal quantity is rejected', array_merge($valid, ['sku' => 'A-100', 'quantity' => '2.5']), 'rejected:quantity');
$run('TC11', 'Description over 1000 characters is rejected', array_merge($valid, ['sku' => 'A-100', 'description' => str_repeat('d', 1001)]), 'rejected:description');
$run('TC12', 'Category outside the list is rejected', array_merge($valid, ['sku' => 'A-100', 'category' => 'Weapons']), 'rejected:category');
$run('TC13', 'SKU with illegal characters is rejected', array_merge($valid, ['sku' => '../etc/passwd']), 'rejected:sku');
$run('TC14', 'DUPLICATE SKU (different case) is rejected', array_merge($valid, ['name' => 'Another Mouse', 'sku' => 'ELEC-0042']), 'rejected:sku');

$run('TC15', 'Valid PNG upload is accepted and stored under a generated name', array_merge($valid, ['sku' => 'IMG-001']), 'accepted', $makeFile('my photo.png', $png));
$stored = $pdo->query("SELECT image_path FROM products WHERE sku = 'IMG-001'")->fetchColumn();
$t->assert('TC16', 'Stored reference is a server-generated name (not the client file name) and the file exists',
    is_string($stored) && preg_match('#^uploads/[a-f0-9]{32}\.png$#', $stored) === 1 && is_file($uploadDir . '/' . basename($stored)),
    'image_path = ' . var_export($stored, true));
$run('TC17', 'Executable disguised as image (evil.php.png containing PHP) is rejected', array_merge($valid, ['sku' => 'IMG-002']), 'rejected:image', $makeFile('evil.php.png', "<?php system(\$_GET['c']); ?>"));
$run('TC18', 'Real PNG bytes with a .php name are judged by content, not name', array_merge($valid, ['sku' => 'IMG-003']), 'accepted', $makeFile('shell.php', $png));
$run('TC19', 'Image of 2 MB or more is rejected', array_merge($valid, ['sku' => 'IMG-004']), 'rejected:image', $makeFile('big.png', $png . str_repeat("\0", 2 * 1024 * 1024)));
$run('TC20', 'GIF image is rejected (JPEG/PNG only)', array_merge($valid, ['sku' => 'IMG-005']), 'rejected:image', $makeFile('anim.png', base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7')));
$run('TC21', 'Upload error code (partial upload) is rejected', array_merge($valid, ['sku' => 'IMG-006']), 'rejected:image', ['name' => 'x.png', 'type' => 'image/png', 'tmp_name' => '', 'error' => UPLOAD_ERR_PARTIAL, 'size' => 0]);

$filesBefore = count(glob($uploadDir . '/*.{png,jpg}', GLOB_BRACE) ?: []);
$run('TC22', 'Duplicate SKU with an image leaves no orphan file behind', array_merge($valid, ['sku' => 'img-001']), 'rejected:sku', $makeFile('dup.png', $png));
$filesAfter = count(glob($uploadDir . '/*.{png,jpg}', GLOB_BRACE) ?: []);
$t->assert('TC23', 'No new file was written by the rejected duplicate', $filesBefore === $filesAfter, "files before=$filesBefore after=$filesAfter");

$count = (int) $pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
$t->assert('TC24', 'Only valid products were saved (4 rows: TC01, TC08, TC15, TC18)', $count === 4, "row count = $count");

$page = (string) file_get_contents(__DIR__ . '/../index.php');
$t->assert('TC25', 'index.php category options match the list the validator accepts', template_select_values($page, 'category') === S4_CATEGORIES);

$t->evidence('products table', $pdo, 'SELECT id, name, sku, category, unit_price, quantity, is_available, image_path FROM products ORDER BY id');
$t->evidence('inventory sorted by category (extension query)', $pdo, 'SELECT category, name, sku, quantity FROM products ORDER BY category, name');

array_map('unlink', glob($uploadDir . '/{.htaccess,*}', GLOB_BRACE) ?: []);
@rmdir($uploadDir);
array_map('unlink', glob($work . '/*') ?: []);
@rmdir($work);
exit($t->finish(__DIR__ . '/TEST_RESULTS.md'));
