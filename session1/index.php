<?php require __DIR__ . '/controller.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lab 01 — Student Registration</title>
    <link rel="stylesheet" href="../shared/style.css">
</head>
<body>

<header class="site">
    <div class="wrap">
        <p class="kicker">Degree Year 3 · PHP &amp; MySQL</p>
        <h1>Lab 01 — Student Registration</h1>
        <nav>
            <a href="index.php" aria-current="page">Register</a>
            <a href="list.php">Registrations list</a>
        </nav>
    </div>
</header>

<main class="wrap">

    {{alerts}}

    <form method="post" action="" novalidate>
        <input type="hidden" name="csrf" value="{{csrf}}">

        <div class="field">
            <label for="full_name">Full name</label>
            <input type="text" id="full_name" name="full_name" maxlength="80" required
                   value="{{value:full_name}}">
            <span class="error">{{error:full_name}}</span>
        </div>

        <div class="field">
            <label for="email">Email address</label>
            <input type="email" id="email" name="email" maxlength="150" required
                   value="{{value:email}}">
            <span class="error">{{error:email}}</span>
        </div>

        <div class="field">
            <label for="telephone">Telephone</label>
            <input type="tel" id="telephone" name="telephone" required
                   value="{{value:telephone}}">
            <span class="error">{{error:telephone}}</span>
        </div>

        <div class="field">
            <label for="date_of_birth">Date of birth</label>
            <input type="date" id="date_of_birth" name="date_of_birth" max="{{var:max_dob}}" required
                   value="{{value:date_of_birth}}">
            <span class="error">{{error:date_of_birth}}</span>
        </div>

        <div class="field">
            <label for="programme">Programme</label>
            <select id="programme" name="programme" required>
                <option value="">Choose an option</option>
                <option value="Software Engineering" {{selected:programme:Software Engineering}}>Software Engineering</option>
                <option value="Computer Networks" {{selected:programme:Computer Networks}}>Computer Networks</option>
                <option value="Cybersecurity" {{selected:programme:Cybersecurity}}>Cybersecurity</option>
                <option value="Information Systems" {{selected:programme:Information Systems}}>Information Systems</option>
                <option value="Data Science" {{selected:programme:Data Science}}>Data Science</option>
            </select>
            <span class="error">{{error:programme}}</span>
        </div>

        <div class="field">
            <label for="level">Level</label>
            <select id="level" name="level" required>
                <option value="">Choose an option</option>
                <option value="Level 1" {{selected:level:Level 1}}>Level 1</option>
                <option value="Level 2" {{selected:level:Level 2}}>Level 2</option>
                <option value="Level 3" {{selected:level:Level 3}}>Level 3</option>
                <option value="Level 4" {{selected:level:Level 4}}>Level 4</option>
            </select>
            <span class="error">{{error:level}}</span>
        </div>

        <fieldset class="field">
            <legend>Gender (optional)</legend>
            <div class="choices">
                <label class="choice">
                    <input type="radio" name="gender" value="Male" {{checked:gender:Male}}>
                    Male
                </label>
                <label class="choice">
                    <input type="radio" name="gender" value="Female" {{checked:gender:Female}}>
                    Female
                </label>
            </div>
            <span class="error">{{error:gender}}</span>
        </fieldset>

        <fieldset class="field">
            <legend>Accept registration policy</legend>
            <label class="choice">
                <input type="checkbox" name="policy" value="1" required {{checked:policy:1}}>
                I agree
            </label>
            <span class="error">{{error:policy}}</span>
        </fieldset>

        <div class="actions">
            <button type="submit" class="btn primary">Submit Form</button>
            <button type="reset" class="btn">Reset</button>
        </div>
    </form>

</main>

</body>
</html>
