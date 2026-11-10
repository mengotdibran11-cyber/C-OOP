# Session 1 — Student Registration

## Setup
```bash
mysql -u root -p < session1/schema.sql
php -S localhost:8000        # from the php_labs folder, then open /session1/
```
Connection settings: see the top-level `README.md`.

## Files
`index.php` (HTML form) · `controller.php` (PHP logic) · `validate.php` validation and insert logic · `schema.sql` · `list.php` extension (searchable list) · `tests/run_tests.php`

## Validation rules
| Field | Rule |
|---|---|
| Full name | required; 3–80 characters; letters plus space, apostrophe, hyphen, full stop |
| Email | required; valid format; max 150; stored lower-case; **unique** |
| Telephone | required; **9 digits, starts with 6 (mobile) or 2 (landline)**; spaces/dots/dashes and a `+237` prefix are accepted and stripped; stored as 9 digits |
| Date of birth | required; real calendar date `YYYY-MM-DD`; must be in the past and not more than 100 years ago |
| Programme / Level | required; must be one of the listed values |
| Gender | optional (not in the mandatory list); if sent it must be one of the listed values |
| Registration policy | required checkbox |

## Database design
`students(id PK AUTO_INCREMENT, full_name, email UNIQUE, telephone, date_of_birth, programme, level, gender NULL, policy_accepted, registered_at DEFAULT CURRENT_TIMESTAMP)`.
Duplicate e-mails are rejected by a `SELECT` check **and** by the `uq_students_email` index; no second row is ever inserted.

## Testing
`php session1/tests/run_tests.php` → 18 cases, report in `tests/TEST_RESULTS.md`.
The five demonstrations required by Task D map to: valid = TC01 · missing required = TC02 · invalid value = TC03/05/07/09 · duplicate = TC04 · database query = "Database evidence" section.

## Screenshot checklist (capture on your machine)
1. Empty form · 2. Form with errors (submit empty) · 3. Success message · 4. Duplicate-email error
5. `SELECT * FROM students;` in MySQL showing the saved row · 6. `list.php` with a search.
