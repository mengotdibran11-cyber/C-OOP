# PHP Form Validation & Database Insertion — Six Labs

Each lab is a self-contained folder (`session1` … `session6`) with the form, server-side validation,
MySQL schema, README, automated test cases and the extension challenge.

| Session | Lab | Extension page |
|---|---|---|
| `session1` | Student Registration | `list.php` (searchable list) |
| `session2` | Library Book Borrowing | `active.php` (active loans + due dates) |
| `session3` | Clinic Appointment Booking | `schedule.php` (schedule by doctor) |
| `session4` | E-Commerce Product Entry | `inventory.php` (sorted by category) |
| `session5` | Event Registration | `totals.php` (totals per workshop) |
| `session6` | Customer Support Ticket | `tickets.php` (list by status) |

## Setup (MySQL / MariaDB)

1. Create the tables for the lab you want (each `schema.sql` also creates the `php_labs` database):

   ```bash
   mysql -u root -p < session1/schema.sql      # repeat for session2 … session6
   ```

2. Tell the code how to connect. Defaults are `127.0.0.1:3306`, database `php_labs`, user `root`,
   empty password. Override with environment variables if yours differ:

   ```bash
   export LAB_DB_HOST=127.0.0.1 LAB_DB_PORT=3306 LAB_DB_NAME=php_labs LAB_DB_USER=root LAB_DB_PASS=secret
   ```

   (Or edit the defaults in `shared/db.php`.)

3. Start PHP's built-in server from this folder (PHP 8.1+ with `pdo_mysql`, `mbstring`, `fileinfo`):

   ```bash
   php -S localhost:8000
   ```

   Open <http://localhost:8000/> and choose a session. With XAMPP/WAMP, copy the folder into `htdocs`/`www`.

## What every lab does

- Each `index.php` is a plain HTML page with simple `<label>`/`<input>`/`<select>`/`<textarea>` tags and `novalidate` on the form
  so the **server-side** messages can be demonstrated (the HTML5 `required`/`min`/`max` hints are still present).
  Its only PHP is the first line, which loads that lab's `controller.php` (all the PHP logic: reading the POST, calling
  validation, saving, redirecting). Dynamic spots in the HTML are `{{placeholders}}` filled in by `shared/template.php`:
  `{{value:field}}` (refill a value), `{{error:field}}` (field message), `{{selected:field:option}}` / `{{checked:field:option}}`
  (keep a dropdown/radio/checkbox choice), `{{var:name}}` (a value from the controller, e.g. today's date),
  `{{alerts}}` (the success message, or a banner for system problems such as a failed database connection) and `{{csrf}}` (the hidden form token).
- The dropdown and radio options are typed directly into `index.php`. If you add or rename one, also change the matching list in
  that lab's `validate.php`; the test cases fail if the two disagree.
- Field-specific messages; safe values re-displayed after an invalid submit (always HTML-escaped).
- **PDO prepared statements only** — user input is never concatenated into SQL.
- UNIQUE indexes enforce the duplicate rules in the database as well as in PHP, so a race between two
  simultaneous requests cannot slip a duplicate through.
- Success is shown only after a confirmed insert, using redirect-after-POST (refreshing does not resubmit).
- Database failures are logged with `error_log()` and shown as a friendly message — no stack traces.
- CSRF token on every form.
- Uploads (labs 4 and 6): real size, MIME type and image structure are checked on the temporary file;
  files are stored under a random server-generated name; the browser's file name is never used.

## Running the automated test cases

```bash
./run_all_tests.sh              # or: php session1/tests/run_tests.php
```

Each run prints PASS/FAIL per case and writes `sessionN/tests/TEST_RESULTS.md` (test table + database rows).

> **Important — what the included results are.** The tests were executed in an environment with no MySQL
> server, so they ran the same PHP code against an in-memory **SQLite** copy of each `schema.sql`
> (translated automatically by `shared/db.php`; tests only). The validation logic, prepared statements and
> UNIQUE-constraint behaviour are identical, but **for your submission you should re-run the tests and the
> forms against real MySQL** and take your own screenshots (see each README's checklist). The same fallback
> can run the web pages without MySQL: `LAB_DB_DRIVER=sqlite php -S localhost:8000`.

## Not included

Screenshots of the forms and of the MySQL rows — these have to be captured on your own machine against your
own database (a checklist is in every session README). Do not use real personal data in any test.
