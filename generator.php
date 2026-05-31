<?php
// generate-students.php — Demo student generator (max 100)
// DELETE THIS FILE before going live!
require_once 'includes/config.php';
require_once 'includes/auth.php';
requireLogin();

$msg = $msg_type = '';

$first_names_m = [
    'Aarav',
    'Bikash',
    'Dipesh',
    'Ganesh',
    'Hari',
    'Kiran',
    'Manish',
    'Nabin',
    'Pawan',
    'Rajesh',
    'Sagar',
    'Tilak',
    'Umesh',
    'Vijay',
    'Yash',
    'Anil',
    'Binod',
    'Deepak',
    'Gopal',
    'Hemant',
    'Kamal',
    'Laxman',
    'Mohan',
    'Niraj',
    'Prakash',
    'Ramesh',
    'Suresh',
    'Trilok',
    'Ujjwal',
    'Bishal'
];
$first_names_f = [
    'Aarti',
    'Bina',
    'Deepa',
    'Gita',
    'Hira',
    'Kamala',
    'Laxmi',
    'Mina',
    'Nisha',
    'Puja',
    'Rima',
    'Sabina',
    'Tara',
    'Uma',
    'Vinita',
    'Anita',
    'Binita',
    'Dipa',
    'Geeta',
    'Hema',
    'Kavita',
    'Lalita',
    'Maya',
    'Nita',
    'Priya',
    'Rita',
    'Sita',
    'Sunita',
    'Usha',
    'Yasoda'
];
$last_names = [
    'Adhikari',
    'Basnet',
    'Bhattarai',
    'Chaudhary',
    'Ghimire',
    'Gurung',
    'Karki',
    'KC',
    'Lamichhane',
    'Magar',
    'Maharjan',
    'Oli',
    'Paudel',
    'Pradhan',
    'Rai',
    'Regmi',
    'Shrestha',
    'Subedi',
    'Tamang',
    'Thapa',
    'Upreti',
    'Yadav',
    'Bhandari',
    'Dahal',
    'Hamal',
    'Joshi',
    'Koirala',
    'Limbu',
    'Neupane',
    'Pandey'
];
$sections  = ['A', 'B', 'C', 'D', 'E'];
$genders   = ['Male', 'Female'];
$rooms = array_merge(
    range(101, 103),
    range(201, 203),
    range(301, 303),
    range(401, 403),
    range(501, 503)
);

// ── GENERATE ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'generate') {
    $count = min((int)($_POST['count'] ?? 10), 100);

    // Check existing count
    $existing = (int)$conn->query("SELECT COUNT(*) c FROM students")->fetch_assoc()['c'];
    $can_add  = max(0, 100 - $existing);
    $count    = min($count, $can_add);

    if ($count <= 0) {
        $msg = 'Already at 100 students. Clear existing students first.';
        $msg_type = 'error';
    } else {
        $added = 0;
        $used_names = [];

        for ($i = 0; $i < $count; $i++) {
            $gender = $genders[array_rand($genders)];
            $pool   = $gender === 'Male' ? $first_names_m : $first_names_f;

            // Try to get a unique name
            $attempts = 0;
            do {
                $name = $pool[array_rand($pool)] . ' ' . $last_names[array_rand($last_names)];
                $attempts++;
            } while (in_array($name, $used_names) && $attempts < 20);

            $used_names[] = $name;
            $section  = $sections[array_rand($sections)];
            $room     = $rooms[array_rand($rooms)];
            $phone    = '98' . rand(10000000, 99999999);

            $stmt = $conn->prepare("INSERT INTO students (full_name, section, gender, room_number, phone) VALUES (?, ?, ?, ?, ?)");
            $room_str = (string)$room;
            $stmt->bind_param('sssss', $name, $section, $gender, $room_str, $phone);
            if ($stmt->execute()) $added++;
            $stmt->close();
        }

        $msg = "Successfully added $added student(s). Total now: " . ($existing + $added) . "/100.";
        $msg_type = 'success';
    }
}

// ── CLEAR ALL ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'clear') {
    $conn->query("DELETE FROM students");
    $msg = 'All students cleared.';
    $msg_type = 'success';
}

// Current stats
$total   = (int)$conn->query("SELECT COUNT(*) c FROM students")->fetch_assoc()['c'];
$by_sec  = [];
foreach ($sections as $s) {
    $by_sec[$s] = (int)$conn->query("SELECT COUNT(*) c FROM students WHERE section='$s'")->fetch_assoc()['c'];
}
$male   = (int)$conn->query("SELECT COUNT(*) c FROM students WHERE gender='Male'")->fetch_assoc()['c'];
$female = (int)$conn->query("SELECT COUNT(*) c FROM students WHERE gender='Female'")->fetch_assoc()['c'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Generator — HostelMS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: var(--bg);
            padding: 2rem 1rem;
        }

        .gen-wrap {
            width: 100%;
            max-width: 560px;
        }

        .gen-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0, 0, 0, .2);
        }

        .gen-header {
            padding: 1.8rem 2rem 1.4rem;
            border-bottom: 1px solid var(--border);
            background: rgba(251, 191, 36, .04);
            border-top: 4px solid var(--warning);
        }

        .gen-header h1 {
            font-family: 'DM Serif Display', serif;
            font-size: 1.5rem;
            margin-bottom: .3rem;
        }

        .gen-header p {
            font-size: .85rem;
            color: var(--muted);
        }

        .warning-tag {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            background: rgba(251, 191, 36, .12);
            color: var(--warning);
            border: 1px solid rgba(251, 191, 36, .25);
            padding: .3rem .75rem;
            border-radius: 20px;
            font-size: .75rem;
            font-weight: 600;
            letter-spacing: .04em;
            margin-bottom: .8rem;
        }

        .gen-body {
            padding: 2rem;
        }

        /* Stats bar */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: .75rem;
            margin-bottom: 2rem;
        }

        .mini-stat {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: .9rem .8rem;
            text-align: center;
        }

        .mini-stat .ms-val {
            font-family: 'DM Serif Display', serif;
            font-size: 1.5rem;
            color: var(--text);
        }

        .mini-stat .ms-lbl {
            font-size: .72rem;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: .05em;
            margin-top: .2rem;
        }

        /* Progress bar */
        .progress-wrap {
            margin-bottom: 1.8rem;
        }

        .progress-label {
            display: flex;
            justify-content: space-between;
            font-size: .8rem;
            color: var(--muted);
            margin-bottom: .5rem;
        }

        .progress-track {
            background: rgba(255, 255, 255, .05);
            border-radius: 6px;
            height: 10px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            border-radius: 6px;
            background: linear-gradient(90deg, var(--accent), var(--accent2));
            transition: width .5s ease;
        }

        /* Section breakdown */
        .sec-row {
            display: flex;
            gap: .5rem;
            margin-bottom: 1.8rem;
            flex-wrap: wrap;
        }

        .sec-chip {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: .4rem .85rem;
            font-size: .82rem;
            display: flex;
            align-items: center;
            gap: .4rem;
        }

        .sec-chip .sc-label {
            color: var(--muted);
        }

        .sec-chip .sc-val {
            font-weight: 600;
            color: var(--text);
        }

        /* Controls */
        .control-row {
            display: flex;
            gap: .75rem;
            align-items: flex-end;
            margin-bottom: 1.2rem;
        }

        .control-row .form-group {
            flex: 1;
            margin: 0;
        }

        .control-row .form-group label {
            font-size: .75rem;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: .05em;
            display: block;
            margin-bottom: .4rem;
        }

        .control-row .form-group input {
            width: 100%;
            padding: .65rem .9rem;
            background: var(--input-bg);
            border: 1px solid var(--border);
            border-radius: 8px;
            color: var(--text);
            font-family: inherit;
            font-size: .95rem;
        }

        .control-row .form-group input:focus {
            outline: none;
            border-color: var(--accent);
        }

        .danger-zone {
            margin-top: 1.5rem;
            padding: 1.2rem;
            background: rgba(248, 113, 113, .05);
            border: 1px solid rgba(248, 113, 113, .2);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }

        .danger-zone p {
            font-size: .83rem;
            color: var(--muted);
        }

        .danger-zone strong {
            display: block;
            color: var(--danger);
            font-size: .88rem;
            margin-bottom: .2rem;
        }

        .back-link {
            text-align: center;
            margin-top: 1.2rem;
            font-size: .83rem;
            color: var(--muted);
        }

        .back-link a {
            color: var(--accent);
            text-decoration: none;
        }
    </style>
</head>

<body>
    <div class="gen-wrap">
        <div class="gen-card">
            <div class="gen-header">
                <div class="warning-tag">⚠️ Demo Tool</div>
                <h1>Student Generator</h1>
                <p>Generates random Nepali student names for demo/testing. Delete this file before going live.</p>
            </div>

            <div class="gen-body">
                <?php if ($msg): ?>
                    <div class="alert alert-<?= $msg_type ?>" style="margin-bottom:1.5rem"><?= htmlspecialchars($msg) ?></div>
                <?php endif; ?>

                <!-- Stats -->
                <div class="stats-row">
                    <div class="mini-stat">
                        <div class="ms-val"><?= $total ?></div>
                        <div class="ms-lbl">Total</div>
                    </div>
                    <div class="mini-stat">
                        <div class="ms-val"><?= 100 - $total ?></div>
                        <div class="ms-lbl">Slots left</div>
                    </div>
                    <div class="mini-stat">
                        <div class="ms-val"><?= $male ?></div>
                        <div class="ms-lbl">Male</div>
                    </div>
                    <div class="mini-stat">
                        <div class="ms-val"><?= $female ?></div>
                        <div class="ms-lbl">Female</div>
                    </div>
                </div>

                <!-- Progress -->
                <div class="progress-wrap">
                    <div class="progress-label">
                        <span>Capacity</span>
                        <span><?= $total ?> / 100</span>
                    </div>
                    <div class="progress-track">
                        <div class="progress-fill" style="width:<?= $total ?>%"></div>
                    </div>
                </div>

                <!-- Section breakdown -->
                <div class="sec-row">
                    <?php foreach ($by_sec as $s => $c): ?>
                        <div class="sec-chip">
                            <span class="sc-label">§<?= $s ?></span>
                            <span class="sc-val"><?= $c ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Generate form -->
                <form method="POST">
                    <input type="hidden" name="action" value="generate">
                    <div class="control-row">
                        <div class="form-group">
                            <label>How many to generate?</label>
                            <input
                                type="number"
                                name="count"
                                min="1"
                                max="<?= max(0, 100 - $total) ?>"
                                value="<?= min(10, max(0, 100 - $total)) ?>"
                                <?= $total >= 100 ? 'disabled' : '' ?>>
                        </div>
                        <button
                            type="submit"
                            class="btn btn-primary"
                            <?= $total >= 100 ? 'disabled style="opacity:.5;cursor:not-allowed"' : '' ?>>
                            ⚡ Generate
                        </button>
                    </div>
                </form>

                <!-- Danger zone -->
                <div class="danger-zone">
                    <div>
                        <strong>Clear All Students</strong>
                        <p>Permanently deletes every student from the database.</p>
                    </div>
                    <form method="POST" onsubmit="return confirm('Delete ALL students? This cannot be undone.')">
                        <input type="hidden" name="action" value="clear">
                        <button type="submit" class="btn btn-danger btn-sm">🗑️ Clear All</button>
                    </form>
                </div>

            </div>
        </div>

        <div class="back-link">
            <a href="dashboard.php">← Back to Dashboard</a>
        </div>
    </div>
    <script src="js/app.js"></script>
</body>

</html>