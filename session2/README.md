# Session 2 — Library Book Borrowing

## Setup
```bash
mysql -u root -p < session2/schema.sql
php -S localhost:8000        # from the php_labs folder, then open /session2/
```

## Files
`index.php` (HTML form) · `controller.php` (PHP logic) · `validate.php` · `schema.sql` · `active.php` extension (active loans + due dates, overdue flagged) · `tests/run_tests.php`

## Validation rules
| Field | Rule |
|---|---|
| Student ID | required; 4–20 of letters, digits, `/`, `-`; stored upper-case |
| ISBN | required; hyphens/spaces ignored; **ISBN-10** (9 digits + digit or `X`, mod-11 checksum) or **ISBN-13** (mod-10 checksum) |
| Book title | required; max 200 characters |
| Borrow date | required; real date; **not in the future** |
| Return due date | required; real date; **after the borrow date**; at most 60 days after it |
| Loan duration | optional (days); if given it must equal the number of days between the dates |
| Borrowing purpose | optional; max 255 characters |
| Library rules | required checkbox |

## Database design and "active" definition
`loans(loan_id PK, student_id, isbn, book_title, borrow_date, due_date, loan_days, purpose, rules_accepted, status, active_flag, created_at)`.

**Active loan** = `status = 'Active'` and `active_flag = 1`. When a book is returned run
`UPDATE loans SET status='Returned', active_flag=NULL WHERE loan_id = …;`.
`UNIQUE (student_id, isbn, active_flag)` allows many `NULL` rows (history) but only one active row per student/book,
so a duplicate active loan is blocked by PHP **and** by the database. A `CHECK (due_date > borrow_date)` is added (enforced on MySQL 8.0.16+).

## Testing
`php session2/tests/run_tests.php` → 18 cases. Task D: valid = TC01/02/06 · missing = TC03 · invalid = TC04/05/07–09/11/12 · duplicate = TC13 (and TC17 for the DB index) · DB evidence section.

## Screenshot checklist
Empty form · errors · success · duplicate-loan error · `SELECT * FROM loans;` · `active.php`.
