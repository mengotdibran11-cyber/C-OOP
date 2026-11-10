# Session 6 — Customer Support Ticket

## Setup
```bash
mysql -u root -p < session6/schema.sql
php -S localhost:8000        # from the php_labs folder, then open /session6/
```
The `session6/uploads/` folder is created automatically (with a `.htaccess` denying script execution); the web server user must be able to write there.

## Files
`index.php` (HTML form) · `controller.php` (PHP logic) · `validate.php` · `schema.sql` · `tickets.php` extension (list and counts by status) · `tests/run_tests.php` · `../shared/upload.php`

## Validation rules
| Field | Rule |
|---|---|
| Customer name | required; 3–80 characters; letters plus space, apostrophe, hyphen, full stop |
| Email | required; valid format; stored lower-case |
| Issue category / Priority | required; from the lists (Low / Medium / High) |
| Subject | required; **5–120 characters** |
| Issue description | required; **20–2000 characters** |
| Screenshot | optional; **PNG/JPEG**; **strictly under 2 MB**; checked on the server (real size, MIME from content, `getimagesize`); stored under a random generated name |
| Confirmation | required checkbox |

## Database design
`support_tickets(ticket_id PK, ticket_ref UNIQUE, customer_name, email, category, priority, subject, description, screenshot_path NULL, details_confirmed, status DEFAULT 'Pending', created_at)`.
After the insert (same transaction) the reference `TKT-<year>-<6-digit id>` is generated and shown on the success message, e.g. `TKT-2026-000017`.
`created_at` is set by the application in Africa/Douala time.

## Conflicting-record rule (my definition)
Tickets have no natural unique key, so the "duplicate/conflicting" case is: **the same email with the same subject (case-insensitive) while an identical ticket is still `Pending` and was created in the last 24 hours** is rejected. A resolved ticket does not block a new one.
This is checked in PHP only (no DB constraint), so two simultaneous identical submissions could in theory both pass; that is acceptable for a help desk but is worth stating in your write-up.

## Testing
`php session6/tests/run_tests.php` → 25 cases. Task D: valid = TC01/09/14/16 · missing = TC03 · invalid = TC04–08/10–12/18/19/21 · conflict = TC13 · DB evidence sections.

## Screenshot checklist
Empty form · errors · success showing the ticket reference · conflict error · `SELECT * FROM support_tickets;` · `tickets.php`.
