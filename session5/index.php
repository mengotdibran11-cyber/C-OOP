<?php require __DIR__ . '/controller.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lab 05 — Event Registration</title>
    <link rel="stylesheet" href="../shared/style.css">
</head>
<body>

<header class="site">
    <div class="wrap">
        <p class="kicker">Degree Year 3 · PHP &amp; MySQL</p>
        <h1>Lab 05 — Event Registration</h1>
        <nav>
            <a href="index.php" aria-current="page">Register</a>
            <a href="totals.php">Totals per workshop</a>
        </nav>
    </div>
</header>

<main class="wrap">

    {{alerts}}

    <form method="post" action="" novalidate>
        <input type="hidden" name="csrf" value="{{csrf}}">

        <div class="field">
            <label for="participant_name">Participant name</label>
            <input type="text" id="participant_name" name="participant_name" maxlength="80" required
                   value="{{value:participant_name}}">
            <span class="error">{{error:participant_name}}</span>
        </div>

        <div class="field">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" maxlength="150" required
                   value="{{value:email}}">
            <span class="error">{{error:email}}</span>
        </div>

        <div class="field">
            <label for="phone">Phone (optional)</label>
            <input type="tel" id="phone" name="phone"
                   value="{{value:phone}}">
            <span class="error">{{error:phone}}</span>
        </div>

        <div class="field">
            <label for="workshop">Workshop</label>
            <select id="workshop" name="workshop" required>
                <option value="">Choose an option</option>
                <option value="php-mysql" {{selected:workshop:php-mysql}}>PHP &amp; MySQL Essentials</option>
                <option value="web-security" {{selected:workshop:web-security}}>Web Application Security</option>
                <option value="git-teams" {{selected:workshop:git-teams}}>Git for Teams</option>
                <option value="ui-prototyping" {{selected:workshop:ui-prototyping}}>UI Prototyping in Figma</option>
            </select>
            <span class="error">{{error:workshop}}</span>
        </div>

        <fieldset class="field">
            <legend>Attendance mode</legend>
            <div class="choices">
                <label class="choice">
                    <input type="radio" name="attendance_mode" value="In-person" required {{checked:attendance_mode:In-person}}>
                    In-person
                </label>
                <label class="choice">
                    <input type="radio" name="attendance_mode" value="Online" required {{checked:attendance_mode:Online}}>
                    Online
                </label>
            </div>
            <span class="error">{{error:attendance_mode}}</span>
        </fieldset>

        <div class="field">
            <label for="seats">Number of seats</label>
            <input type="number" id="seats" name="seats" min="1" max="3" step="1" required
                   value="{{value:seats}}">
            <span class="error">{{error:seats}}</span>
        </div>

        <div class="field">
            <label for="special_requirements">Special requirements</label>
            <textarea id="special_requirements" name="special_requirements" rows="3" maxlength="300">{{value:special_requirements}}</textarea>
            <span class="error">{{error:special_requirements}}</span>
        </div>

        <fieldset class="field">
            <legend>Accept event terms</legend>
            <label class="choice">
                <input type="checkbox" name="terms" value="1" required {{checked:terms:1}}>
                I agree
            </label>
            <span class="error">{{error:terms}}</span>
        </fieldset>

        <div class="actions">
            <button type="submit" class="btn primary">Submit Form</button>
            <button type="reset" class="btn">Reset</button>
        </div>
    </form>

</main>

</body>
</html>
