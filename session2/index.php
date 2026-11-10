<?php require __DIR__ . '/controller.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lab 02 — Library Book Borrowing</title>
    <link rel="stylesheet" href="../shared/style.css">
</head>
<body>

<header class="site">
    <div class="wrap">
        <p class="kicker">Degree Year 3 · PHP &amp; MySQL</p>
        <h1>Lab 02 — Library Book Borrowing</h1>
        <nav>
            <a href="index.php" aria-current="page">Borrow a book</a>
            <a href="active.php">Active loans</a>
        </nav>
    </div>
</header>

<main class="wrap">

    {{alerts}}

    <form method="post" action="" novalidate>
        <input type="hidden" name="csrf" value="{{csrf}}">

        <div class="field">
            <label for="student_id">Student ID</label>
            <input type="text" id="student_id" name="student_id" maxlength="20" required
                   value="{{value:student_id}}">
            <span class="error">{{error:student_id}}</span>
        </div>

        <div class="field">
            <label for="isbn">Book ISBN</label>
            <input type="text" id="isbn" name="isbn" maxlength="17" required
                   value="{{value:isbn}}">
            <span class="error">{{error:isbn}}</span>
        </div>

        <div class="field">
            <label for="book_title">Book title</label>
            <input type="text" id="book_title" name="book_title" maxlength="200" required
                   value="{{value:book_title}}">
            <span class="error">{{error:book_title}}</span>
        </div>

        <div class="field">
            <label for="borrow_date">Borrow date</label>
            <input type="date" id="borrow_date" name="borrow_date" max="{{var:today}}" required
                   value="{{value:borrow_date}}">
            <span class="error">{{error:borrow_date}}</span>
        </div>

        <div class="field">
            <label for="due_date">Return due date</label>
            <input type="date" id="due_date" name="due_date" required
                   value="{{value:due_date}}">
            <span class="error">{{error:due_date}}</span>
        </div>

        <div class="field">
            <label for="loan_duration">Loan duration (days)</label>
            <input type="number" id="loan_duration" name="loan_duration" min="1" max="{{var:max_days}}" step="1"
                   value="{{value:loan_duration}}">
            <span class="error">{{error:loan_duration}}</span>
        </div>

        <div class="field">
            <label for="purpose">Borrowing purpose</label>
            <textarea id="purpose" name="purpose" rows="3" maxlength="255">{{value:purpose}}</textarea>
            <span class="error">{{error:purpose}}</span>
        </div>

        <fieldset class="field">
            <legend>Agree to library rules</legend>
            <label class="choice">
                <input type="checkbox" name="agree" value="1" required {{checked:agree:1}}>
                I agree
            </label>
            <span class="error">{{error:agree}}</span>
        </fieldset>

        <div class="actions">
            <button type="submit" class="btn primary">Submit Form</button>
            <button type="reset" class="btn">Reset</button>
        </div>
    </form>

</main>

</body>
</html>
