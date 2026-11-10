# Session 5 — Event Registration

## Setup
```bash
mysql -u root -p < session5/schema.sql
php -S localhost:8000        # from the php_labs folder, then open /session5/
```

## Files
`index.php` (HTML form) · `controller.php` (PHP logic) · `validate.php` · `schema.sql` · `totals.php` extension (registrations and seats per workshop) · `tests/run_tests.php`

## Validation rules
| Field | Rule |
|---|---|
| Participant name | required; 3–80 characters; letters plus space, apostrophe, hyphen, full stop |
| Email | required; valid format; stored lower-case |
| Phone | optional (not in the mandatory list); if supplied: 9 digits starting with 6 or 2 |
| Workshop | required; one of the listed workshops (the code is stored) |
| Attendance mode | required; In-person or Online |
| Number of seats | required; **integer from 1 to 3** |
| Special requirements | optional; max 300 characters |
| Event terms | required checkbox |
| Repeat registration | the same email may not register twice for the **same workshop** |

## Database design
`event_registrations(id PK, participant_name, email, phone NULL, workshop, attendance_mode, seats, special_requirements NULL, terms_accepted, registered_at)`.
`UNIQUE (email, workshop)` — **not** email alone — so one person can attend several workshops. A `CHECK (seats BETWEEN 1 AND 3)` backs up the PHP rule on MySQL 8.0.16+.

## Testing
`php session5/tests/run_tests.php` → 19 cases. Task D: valid = TC01/07/08 · missing = TC02 · invalid = TC03–06/09/11–13/15 · duplicate = TC16 (TC17 proves different workshop is allowed, TC18 the DB index) · DB evidence sections.

## Screenshot checklist
Empty form · errors · success · repeat-registration error · `SELECT * FROM event_registrations;` · `totals.php`.
