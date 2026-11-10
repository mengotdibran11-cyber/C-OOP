# Session 4 — E-Commerce Product Entry

## Setup
```bash
mysql -u root -p < session4/schema.sql
php -S localhost:8000        # from the php_labs folder, then open /session4/
```
The `session4/uploads/` folder is created automatically (with a `.htaccess` that denies script execution). The web server user must be able to write there.

## Files
`index.php` (HTML form) · `controller.php` (PHP logic) · `validate.php` · `schema.sql` · `inventory.php` extension (sorted by category) · `tests/run_tests.php` · `../shared/upload.php` (image checks)

## Validation rules
| Field | Rule |
|---|---|
| Product name | required; 3–100 characters |
| SKU | required; 3–40 of letters, digits, `-`, `_`; stored upper-case; **unique** |
| Category | required; from the list |
| Unit price (XAF) | required; number with up to 2 decimals; **> 0** |
| Quantity | required; **whole number ≥ 0** |
| Description | optional; max 1000 characters |
| Product image | optional; **JPEG/PNG only**; **under 2 MB** |
| Available for sale | optional checkbox (stored 1/0) |

### Image handling
The browser's name/type/size are not trusted. PHP checks the upload error code, the real file size, the MIME type with `finfo`
(from the file content) and `getimagesize()`. The file is saved as `uploads/<32 random hex>.jpg|png` and only that
reference is stored in `products.image_path`. If the insert fails afterwards the file is deleted again.

## Database design
`products(id PK, name, sku UNIQUE, category, unit_price DECIMAL(12,2), quantity, description NULL, image_path NULL, is_available, created_at)`.

## Testing
`php session4/tests/run_tests.php` → 25 cases (includes a disguised-PHP upload, a GIF, a 2 MB file, and orphan-file cleanup).
Task D: valid = TC01/08/15 · missing = TC02 · invalid = TC03–13/17/19/20 · duplicate SKU = TC14/TC22 · DB evidence sections.

## Screenshot checklist
Empty form · errors · success with an image · duplicate-SKU error · rejected non-image upload · `SELECT * FROM products;` · `inventory.php`.
