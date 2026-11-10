<?php require __DIR__ . '/controller.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lab 06 — Customer Support Ticket</title>
    <link rel="stylesheet" href="../shared/style.css">
</head>
<body>

<header class="site">
    <div class="wrap">
        <p class="kicker">Degree Year 3 · PHP &amp; MySQL</p>
        <h1>Lab 06 — Customer Support Ticket</h1>
        <nav>
            <a href="index.php" aria-current="page">New ticket</a>
            <a href="tickets.php">Tickets by status</a>
        </nav>
    </div>
</header>

<main class="wrap">

    {{alerts}}

    <form method="post" action="" enctype="multipart/form-data" novalidate>
        <input type="hidden" name="csrf" value="{{csrf}}">

        <div class="field">
            <label for="customer_name">Customer name</label>
            <input type="text" id="customer_name" name="customer_name" maxlength="80" required
                   value="{{value:customer_name}}">
            <span class="error">{{error:customer_name}}</span>
        </div>

        <div class="field">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" maxlength="150" required
                   value="{{value:email}}">
            <span class="error">{{error:email}}</span>
        </div>

        <div class="field">
            <label for="category">Issue category</label>
            <select id="category" name="category" required>
                <option value="">Choose an option</option>
                <option value="Login &amp; Account" {{selected:category:Login & Account}}>Login &amp; Account</option>
                <option value="Network &amp; Connectivity" {{selected:category:Network & Connectivity}}>Network &amp; Connectivity</option>
                <option value="Software Bug" {{selected:category:Software Bug}}>Software Bug</option>
                <option value="Hardware Fault" {{selected:category:Hardware Fault}}>Hardware Fault</option>
                <option value="Billing" {{selected:category:Billing}}>Billing</option>
                <option value="Other" {{selected:category:Other}}>Other</option>
            </select>
            <span class="error">{{error:category}}</span>
        </div>

        <fieldset class="field">
            <legend>Priority</legend>
            <div class="choices">
                <label class="choice">
                    <input type="radio" name="priority" value="Low" required {{checked:priority:Low}}>
                    Low
                </label>
                <label class="choice">
                    <input type="radio" name="priority" value="Medium" required {{checked:priority:Medium}}>
                    Medium
                </label>
                <label class="choice">
                    <input type="radio" name="priority" value="High" required {{checked:priority:High}}>
                    High
                </label>
            </div>
            <span class="error">{{error:priority}}</span>
        </fieldset>

        <div class="field">
            <label for="subject">Subject</label>
            <input type="text" id="subject" name="subject" maxlength="120" required
                   value="{{value:subject}}">
            <span class="error">{{error:subject}}</span>
        </div>

        <div class="field">
            <label for="description">Issue description</label>
            <textarea id="description" name="description" rows="6" maxlength="2000" required>{{value:description}}</textarea>
            <span class="error">{{error:description}}</span>
        </div>

        <div class="field">
            <label for="screenshot">Screenshot</label>
            <input type="file" id="screenshot" name="screenshot" accept="image/png,image/jpeg">
            <span class="error">{{error:screenshot}}</span>
        </div>

        <fieldset class="field">
            <legend>Confirm details are accurate</legend>
            <label class="choice">
                <input type="checkbox" name="confirm" value="1" required {{checked:confirm:1}}>
                I agree
            </label>
            <span class="error">{{error:confirm}}</span>
        </fieldset>

        <div class="actions">
            <button type="submit" class="btn primary">Submit Form</button>
            <button type="reset" class="btn">Reset</button>
        </div>
    </form>

</main>

</body>
</html>
