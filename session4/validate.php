<?php
declare(strict_types=1);

require_once __DIR__ . '/../shared/upload.php';

const S4_CATEGORIES = ['Electronics', 'Clothing', 'Food & Drink', 'Home & Kitchen', 'Books & Stationery', 'Beauty & Care'];

function validate_product(array $in, ?array $file): array
{
    $errors = [];

    $name = preg_replace('/\s+/u', ' ', field($in, 'name')) ?? '';
    if ($name === '') {
        $errors['name'] = 'Product name is required.';
    } elseif (text_len($name) < 3 || text_len($name) > 100) {
        $errors['name'] = 'Product name must be between 3 and 100 characters.';
    } elseif (has_control_chars($name)) {
        $errors['name'] = 'Product name contains invalid characters.';
    }

    $sku = strtoupper(field($in, 'sku'));
    if ($sku === '') {
        $errors['sku'] = 'SKU is required.';
    } elseif (!preg_match('/^[A-Z0-9][A-Z0-9_-]{2,39}$/', $sku)) {
        $errors['sku'] = 'SKU must be 3–40 characters: letters, digits, "-" or "_" (e.g. ELEC-0042).';
    }

    $category = field($in, 'category');
    if ($category === '') {
        $errors['category'] = 'Please choose a category.';
    } elseif (!in_array($category, S4_CATEGORIES, true)) {
        $errors['category'] = 'Please choose a category from the list.';
    }

    $price = null;
    $priceRaw = field($in, 'unit_price');
    if ($priceRaw === '') {
        $errors['unit_price'] = 'Unit price is required.';
    } elseif (!preg_match('/^\d{1,10}(\.\d{1,2})?$/', $priceRaw)) {
        $errors['unit_price'] = 'Enter a number such as 2500 or 2500.50 (digits and a dot only).';
    } elseif ((float) $priceRaw <= 0) {
        $errors['unit_price'] = 'Unit price must be greater than zero.';
    } else {
        $price = number_format((float) $priceRaw, 2, '.', '');
    }

    $qty = null;
    $qtyRaw = field($in, 'quantity');
    if ($qtyRaw === '') {
        $errors['quantity'] = 'Quantity is required.';
    } elseif (!preg_match('/^\d{1,9}$/', $qtyRaw)) {
        $errors['quantity'] = 'Quantity must be a whole number, zero or more.';
    } else {
        $qty = (int) $qtyRaw;
    }

    $description = text_field($in, 'description');
    if (text_len($description) > 1000) {
        $errors['description'] = 'Description must be at most 1000 characters (currently ' . text_len($description) . ').';
    }

    $image = validate_image_upload($file);
    if ($image['error'] !== null) {
        $errors['image'] = $image['error'];
    }

    return [
        'errors' => $errors,
        'clean' => [
            'name' => $name,
            'sku' => $sku,
            'category' => $category,
            'unit_price' => $price,
            'quantity' => $qty,
            'description' => $description === '' ? null : $description,
            'is_available' => is_checked($in, 'is_available') ? 1 : 0,
        ],
        'image' => $image,
    ];
}

function process_product(callable $pdoFactory, array $in, ?array $file, string $uploadDir, ?callable $mover = null): array
{
    $v = validate_product($in, $file);
    if ($v['errors']) {
        return ['ok' => false, 'errors' => $v['errors']];
    }
    $c = $v['clean'];
    $duplicate = ['ok' => false, 'errors' => ['sku' => 'This SKU already exists. Each product needs a unique SKU.']];

    try {
        $pdo = $pdoFactory();
    } catch (Throwable $e) {
        return db_failure('lab4', $e);
    }

    $storedPath = null;
    try {
        $check = $pdo->prepare('SELECT 1 FROM products WHERE sku = :sku LIMIT 1');
        $check->execute([':sku' => $c['sku']]);
        if ($check->fetchColumn() !== false) {
            return $duplicate;
        }

        if ($v['image']['tmp'] !== null) {
            ensure_upload_dir($uploadDir);
            $fileName = unique_upload_name($v['image']['ext']);
            $mover ??= 'move_uploaded_file';
            if (!$mover($v['image']['tmp'], rtrim($uploadDir, '/') . '/' . $fileName)) {
                return ['ok' => false, 'errors' => ['image' => 'The image could not be saved. Please try again.']];
            }
            $storedPath = 'uploads/' . $fileName;
        }

        $stmt = $pdo->prepare(
            'INSERT INTO products (name, sku, category, unit_price, quantity, description, image_path, is_available)
             VALUES (:name, :sku, :cat, :price, :qty, :descr, :img, :avail)'
        );
        $stmt->execute([
            ':name' => $c['name'],
            ':sku' => $c['sku'],
            ':cat' => $c['category'],
            ':price' => $c['unit_price'],
            ':qty' => $c['quantity'],
            ':descr' => $c['description'],
            ':img' => $storedPath,
            ':avail' => $c['is_available'],
        ]);
        return ['ok' => true, 'id' => (int) $pdo->lastInsertId()];
    } catch (Throwable $e) {
        if ($storedPath !== null) {
            @unlink(rtrim($uploadDir, '/') . '/' . basename($storedPath));
        }
        if ($e instanceof PDOException && is_duplicate_error($e)) {
            return $duplicate;
        }
        return db_failure('lab4', $e);
    }
}
