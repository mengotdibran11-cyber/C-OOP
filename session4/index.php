<?php require __DIR__ . '/controller.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lab 04 — Product Entry</title>
    <link rel="stylesheet" href="../shared/style.css">
</head>
<body>

<header class="site">
    <div class="wrap">
        <p class="kicker">Degree Year 3 · PHP &amp; MySQL</p>
        <h1>Lab 04 — Product Entry</h1>
        <nav>
            <a href="index.php" aria-current="page">Add product</a>
            <a href="inventory.php">Inventory</a>
        </nav>
    </div>
</header>

<main class="wrap">

    {{alerts}}

    <form method="post" action="" enctype="multipart/form-data" novalidate>
        <input type="hidden" name="csrf" value="{{csrf}}">

        <div class="field">
            <label for="name">Product name</label>
            <input type="text" id="name" name="name" maxlength="100" required
                   value="{{value:name}}">
            <span class="error">{{error:name}}</span>
        </div>

        <div class="field">
            <label for="sku">SKU</label>
            <input type="text" id="sku" name="sku" maxlength="40" required
                   value="{{value:sku}}">
            <span class="error">{{error:sku}}</span>
        </div>

        <div class="field">
            <label for="category">Category</label>
            <select id="category" name="category" required>
                <option value="">Choose an option</option>
                <option value="Electronics" {{selected:category:Electronics}}>Electronics</option>
                <option value="Clothing" {{selected:category:Clothing}}>Clothing</option>
                <option value="Food &amp; Drink" {{selected:category:Food & Drink}}>Food &amp; Drink</option>
                <option value="Home &amp; Kitchen" {{selected:category:Home & Kitchen}}>Home &amp; Kitchen</option>
                <option value="Books &amp; Stationery" {{selected:category:Books & Stationery}}>Books &amp; Stationery</option>
                <option value="Beauty &amp; Care" {{selected:category:Beauty & Care}}>Beauty &amp; Care</option>
            </select>
            <span class="error">{{error:category}}</span>
        </div>

        <div class="field">
            <label for="unit_price">Unit price (XAF)</label>
            <input type="number" id="unit_price" name="unit_price" min="0.01" step="0.01" required
                   value="{{value:unit_price}}">
            <span class="error">{{error:unit_price}}</span>
        </div>

        <div class="field">
            <label for="quantity">Quantity</label>
            <input type="number" id="quantity" name="quantity" min="0" step="1" required
                   value="{{value:quantity}}">
            <span class="error">{{error:quantity}}</span>
        </div>

        <div class="field">
            <label for="description">Description</label>
            <textarea id="description" name="description" rows="4" maxlength="1000">{{value:description}}</textarea>
            <span class="error">{{error:description}}</span>
        </div>

        <div class="field">
            <label for="image">Product image</label>
            <input type="file" id="image" name="image" accept="image/jpeg,image/png">
            <span class="error">{{error:image}}</span>
        </div>

        <fieldset class="field">
            <legend>Available for sale</legend>
            <label class="choice">
                <input type="checkbox" name="is_available" value="1" {{checked:is_available:1}}>
                Yes, available for sale
            </label>
            <span class="error">{{error:is_available}}</span>
        </fieldset>

        <div class="actions">
            <button type="submit" class="btn primary">Submit Form</button>
            <button type="reset" class="btn">Reset</button>
        </div>
    </form>

</main>

</body>
</html>
