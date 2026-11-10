# Session 3 — Clinic Appointment Booking

## Setup
```bash
mysql -u root -p < session3/schema.sql
php -S localhost:8000        # from the php_labs folder, then open /session3/
```

## Files
`index.php` (HTML form) · `controller.php` (PHP logic) · `validate.php` · `schema.sql` · `schedule.php` extension (schedule grouped by doctor, optional date filter) · `tests/run_tests.php`

## Validation rules
| Field | Rule |
|---|---|
| Patient name | required; 3–80 characters; letters plus space, apostrophe, hyphen, full stop |
| Email | **optional**; valid format if supplied |
| Phone | required; 9 digits starting with 6 or 2 (spaces and `+237` accepted) |
| Department / Doctor | required; from the lists; the doctor must belong to the chosen department |
| Appointment date | required; real date; **strictly in the future** (tomorrow or later) and within 90 days |
| Time slot | required; one of the 30-minute slots |
| Symptoms or reason | required; **10–500 characters** |
| Consent | required checkbox |

## Database design
`appointments(id PK, patient_name, email NULL, phone, department, doctor, appointment_date, time_slot, reason, consent_given, status DEFAULT 'Pending', created_at)`.
`UNIQUE (doctor, appointment_date, time_slot)` prevents double booking. Every insert sets `status = 'Pending'`.

## Testing
Use invented patient data only. `php session3/tests/run_tests.php` → 20 cases. Task D: valid = TC01/02 · missing = TC03 · invalid = TC04–09/11–14 · double-booking = TC15 (and TC18 for the DB index) · DB evidence sections.

## Screenshot checklist
Empty form · errors · success (status Pending) · double-booking error · `SELECT * FROM appointments;` · `schedule.php`.
