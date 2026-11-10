<?php require __DIR__ . '/controller.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lab 03 — Clinic Appointment Booking</title>
    <link rel="stylesheet" href="../shared/style.css">
</head>
<body>

<header class="site">
    <div class="wrap">
        <p class="kicker">Degree Year 3 · PHP &amp; MySQL</p>
        <h1>Lab 03 — Clinic Appointment Booking</h1>
        <nav>
            <a href="index.php" aria-current="page">Book appointment</a>
            <a href="schedule.php">Doctor schedule</a>
        </nav>
    </div>
</header>

<main class="wrap">

    {{alerts}}

    <form method="post" action="" novalidate>
        <input type="hidden" name="csrf" value="{{csrf}}">

        <div class="field">
            <label for="patient_name">Patient name</label>
            <input type="text" id="patient_name" name="patient_name" maxlength="80" required
                   value="{{value:patient_name}}">
            <span class="error">{{error:patient_name}}</span>
        </div>

        <div class="field">
            <label for="email">Email (optional)</label>
            <input type="email" id="email" name="email" maxlength="150"
                   value="{{value:email}}">
            <span class="error">{{error:email}}</span>
        </div>

        <div class="field">
            <label for="phone">Phone</label>
            <input type="tel" id="phone" name="phone" required
                   value="{{value:phone}}">
            <span class="error">{{error:phone}}</span>
        </div>

        <div class="field">
            <label for="department">Department</label>
            <select id="department" name="department" required>
                <option value="">Choose an option</option>
                <option value="General Medicine" {{selected:department:General Medicine}}>General Medicine</option>
                <option value="Paediatrics" {{selected:department:Paediatrics}}>Paediatrics</option>
                <option value="Dentistry" {{selected:department:Dentistry}}>Dentistry</option>
            </select>
            <span class="error">{{error:department}}</span>
        </div>

        <div class="field">
            <label for="doctor">Preferred doctor</label>
            <select id="doctor" name="doctor" required>
                <option value="">Choose an option</option>
                <optgroup label="General Medicine">
                    <option value="Dr. Ngu Tabi" {{selected:doctor:Dr. Ngu Tabi}}>Dr. Ngu Tabi</option>
                    <option value="Dr. Mireille Essomba" {{selected:doctor:Dr. Mireille Essomba}}>Dr. Mireille Essomba</option>
                </optgroup>
                <optgroup label="Paediatrics">
                    <option value="Dr. Samuel Fotso" {{selected:doctor:Dr. Samuel Fotso}}>Dr. Samuel Fotso</option>
                </optgroup>
                <optgroup label="Dentistry">
                    <option value="Dr. Aisha Bello" {{selected:doctor:Dr. Aisha Bello}}>Dr. Aisha Bello</option>
                    <option value="Dr. Paul Kamga" {{selected:doctor:Dr. Paul Kamga}}>Dr. Paul Kamga</option>
                </optgroup>
            </select>
            <span class="error">{{error:doctor}}</span>
        </div>

        <div class="field">
            <label for="appointment_date">Appointment date</label>
            <input type="date" id="appointment_date" name="appointment_date" min="{{var:tomorrow}}" max="{{var:latest}}" required
                   value="{{value:appointment_date}}">
            <span class="error">{{error:appointment_date}}</span>
        </div>

        <div class="field">
            <label for="time_slot">Time slot</label>
            <select id="time_slot" name="time_slot" required>
                <option value="">Choose an option</option>
                <option value="08:00-08:30" {{selected:time_slot:08:00-08:30}}>08:00-08:30</option>
                <option value="08:30-09:00" {{selected:time_slot:08:30-09:00}}>08:30-09:00</option>
                <option value="09:00-09:30" {{selected:time_slot:09:00-09:30}}>09:00-09:30</option>
                <option value="09:30-10:00" {{selected:time_slot:09:30-10:00}}>09:30-10:00</option>
                <option value="10:00-10:30" {{selected:time_slot:10:00-10:30}}>10:00-10:30</option>
                <option value="10:30-11:00" {{selected:time_slot:10:30-11:00}}>10:30-11:00</option>
                <option value="14:00-14:30" {{selected:time_slot:14:00-14:30}}>14:00-14:30</option>
                <option value="14:30-15:00" {{selected:time_slot:14:30-15:00}}>14:30-15:00</option>
            </select>
            <span class="error">{{error:time_slot}}</span>
        </div>

        <div class="field">
            <label for="reason">Symptoms or reason</label>
            <textarea id="reason" name="reason" rows="4" maxlength="500" required>{{value:reason}}</textarea>
            <span class="error">{{error:reason}}</span>
        </div>

        <fieldset class="field">
            <legend>Consent to data use</legend>
            <label class="choice">
                <input type="checkbox" name="consent" value="1" required {{checked:consent:1}}>
                I agree
            </label>
            <span class="error">{{error:consent}}</span>
        </fieldset>

        <div class="actions">
            <button type="submit" class="btn primary">Submit Form</button>
            <button type="reset" class="btn">Reset</button>
        </div>
    </form>

</main>

</body>
</html>
