<?php
date_default_timezone_set('Asia/Manila');

session_start();
include("../../config/db.php");

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || strtolower(trim($_SESSION['role'])) !== 'tutee') {
    header("Location: ../login.php");
    exit();
}

$tutee_id = $_SESSION['user_id'];
$message = "";
$message_type = "";

if (!isset($_GET['tutor_id']) || empty($_GET['tutor_id'])) {
    die("Invalid tutor selected.");
}

$tutor_id = intval($_GET['tutor_id']);

$sql = "SELECT tp.tutor_id, u.name
        FROM tutor_profiles tp
        INNER JOIN users u ON tp.tutor_id = u.user_id
        WHERE tp.tutor_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $tutor_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Tutor not found.");
}

$tutor = $result->fetch_assoc();

$subj_sql = "SELECT ts.subject_id, s.subject_name, ts.rate
             FROM tutor_subjects ts
             JOIN subjects s ON ts.subject_id = s.subject_id
             WHERE ts.tutor_id = ?
             ORDER BY s.subject_name ASC";
$subj_stmt = $conn->prepare($subj_sql);
$subj_stmt->bind_param("i", $tutor_id);
$subj_stmt->execute();
$subj_result = $subj_stmt->get_result();

$tutor_subjects = [];
while ($row = $subj_result->fetch_assoc()) {
    $tutor_subjects[] = $row;
}

if (empty($tutor_subjects)) {
    die("This tutor has no subjects listed.");
}

$selected_subject_id = 0;
if (!empty($_POST['subject_id'])) {
    $selected_subject_id = intval($_POST['subject_id']);
} elseif (!empty($_GET['subject_id'])) {
    $selected_subject_id = intval($_GET['subject_id']);
}
if (!$selected_subject_id) {
    $selected_subject_id = $tutor_subjects[0]['subject_id'];
}

$tutor['subject_id']   = $selected_subject_id;
$tutor['subject_name'] = '';
$tutor['rate']         = 0;
foreach ($tutor_subjects as $subj) {
    if ($subj['subject_id'] == $selected_subject_id) {
        $tutor['subject_name'] = $subj['subject_name'];
        $tutor['rate']         = $subj['rate'];
        break;
    }
}

// FIX: Load availability for ALL subjects keyed by subject_id.
// BUG WAS HERE: the original query used FIELD() for ordering but the join was correct.
// The real problem was that $availability_slots was being looked up by $selected_subject_id
// at POST time, but the subject_id POSTed could be stale (defaulting to the first subject)
// because the <select> element lives OUTSIDE the <form> tag and was not reliably submitted.
// Solution: load all availability for this tutor regardless of subject, and at POST time
// re-derive $selected_subject_id safely from POST, then look up the correct slots.
$all_avail_sql = "SELECT ts.subject_id, ta.day_of_week, ta.start_time, ta.end_time
                  FROM tutor_availability ta
                  JOIN tutor_subjects ts ON ta.tutor_subject_id = ts.id
                  WHERE ts.tutor_id = ?
                  ORDER BY ts.subject_id,
                  FIELD(ta.day_of_week,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday')";
$all_avail_stmt = $conn->prepare($all_avail_sql);
$all_avail_stmt->bind_param("i", $tutor_id);
$all_avail_stmt->execute();
$all_avail_result = $all_avail_stmt->get_result();

$all_availability = [];
while ($row = $all_avail_result->fetch_assoc()) {
    $sid = $row['subject_id'];
    if (!isset($all_availability[$sid])) $all_availability[$sid] = [];
    $all_availability[$sid][] = [
        // FIX: trim() every value from DB to eliminate invisible trailing spaces
        // that silently break === day name comparisons (e.g. "Thursday " !== "Thursday").
        'day_of_week' => trim($row['day_of_week']),
        'start_time'  => trim($row['start_time']),
        'end_time'    => trim($row['end_time'])
    ];
}

$availability_slots = $all_availability[$selected_subject_id] ?? [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $schedule = $_POST['requested_schedule'];
    $end_time = $_POST['end_time'] ?? '';
    $note = $_POST['request_note'];

    $manila_tz  = new DateTimeZone('Asia/Manila');
    $dt_start   = new DateTime($schedule, $manila_tz);
    $start_date = $dt_start->format('Y-m-d');

    // FIX: end_time from <input type="time"> is always HH:MM in 24-hour format.
    // Append ":00" only if seconds are missing so DateTime parses it correctly.
    // Without this, "19:24" becomes valid but "7:24" (if browser misbehaves) could
    // be misread as 07:24 AM instead of 19:24, making duration appear negative and
    // skipping into the wrong error branch before the availability check even runs.
    $end_time_clean = (strlen($end_time) === 5) ? $end_time . ':00' : $end_time;
    $dt_end = new DateTime($start_date . ' ' . $end_time_clean, $manila_tz);

    // FIX: If end time (time-only) is earlier on the clock than start time it likely
    // means the user intends the next day — but we don't support overnight sessions,
    // so we just let the negative duration check below catch it with a clear message.
    $duration_secs = $dt_end->getTimestamp() - $dt_start->getTimestamp();

    if (empty($schedule) || empty($end_time)) {
        $message = "Start and end time are required.";
        $message_type = "error";
    } elseif ($dt_start->getTimestamp() < (new DateTime('now', $manila_tz))->getTimestamp()) {
        // FIX: reject past datetime — compared in Manila-local epoch seconds
        $message = "You cannot request a session in the past.";
        $message_type = "error";
    } elseif ($duration_secs <= 0) {
        $message = "End time must be after start time.";
        $message_type = "error";
    } elseif ($duration_secs < 1800) {
        $message = "Minimum session duration is 30 minutes.";
        $message_type = "error";
    } elseif ($duration_secs > 28800) {
        $message = "Maximum session duration is 8 hours.";
        $message_type = "error";
    } elseif (!empty($availability_slots)) {

        // FIX: Re-read $selected_subject_id from POST here explicitly so we always
        // use the subject the user actually selected, not the GET/default fallback.
        // This was the primary cause of Thursday being rejected: the subject_id used
        // for the availability lookup defaulted to subject 1 (which had Monday/Friday),
        // so the Thursday slot (under a different subject_id) was never found.
        $post_subject_id    = intval($_POST['subject_id'] ?? 0);
        // FIX: Fall back to first subject only if POST subject_id is missing/invalid,
        // but prefer whatever was POSTed so the correct availability rows are checked.
        $validated_subj_id  = ($post_subject_id > 0) ? $post_subject_id : $selected_subject_id;
        $slots_to_check     = $all_availability[$validated_subj_id] ?? [];

        $requested_datetime = $dt_start;
        // FIX: format('l') returns the full English weekday name e.g. "Thursday".
        // Wrapped in trim() as an extra guard in case DateTime ever pads whitespace.
        $requested_day = trim($requested_datetime->format('l'));

        $requested_seconds = (int)$requested_datetime->format('H') * 3600
                           + (int)$requested_datetime->format('i') * 60
                           + (int)$requested_datetime->format('s');

        $is_available = false;
        // FIX: iterate $slots_to_check (the correctly resolved slots for the POSTed
        // subject) instead of $availability_slots (which was resolved from the
        // GET/default subject_id and could point to the wrong set of days).
        foreach ($slots_to_check as $slot) {
            // FIX: trim() the DB day name on every comparison iteration.
            // Trailing spaces in the day_of_week column ("Thursday ") caused
            // strict === comparison to fail silently — this was the secondary bug
            // that would block Thursday even when the subject_id was correct.
            $slot_day = trim($slot['day_of_week']);

            if ($slot_day === $requested_day) {
                $slot_parts   = explode(':', trim($slot['start_time']));
                $slot_start_s = (int)$slot_parts[0] * 3600
                              + (int)$slot_parts[1] * 60
                              + (int)($slot_parts[2] ?? 0);

                $slot_parts = explode(':', trim($slot['end_time']));
                $slot_end_s = (int)$slot_parts[0] * 3600
                            + (int)$slot_parts[1] * 60
                            + (int)($slot_parts[2] ?? 0);

                if ($requested_seconds >= $slot_start_s && $requested_seconds <= $slot_end_s) {
                    $is_available = true;
                    break;
                }
            }
        }

        // FIX: If $slots_to_check is empty (subject has no availability rows at all),
        // treat it the same as the no-availability-slots branch and allow the request,
        // rather than incorrectly blocking it.
        if (empty($slots_to_check)) {
            $is_available = true;
        }

        if (!$is_available) {
            $message = "The tutor is not available at the selected time. Please choose from their available schedule.";
            $message_type = "error";
        } else {
            $stmt = $conn->prepare("INSERT INTO sessions 
                (tutor_id, tutee_id, subject_id, requested_schedule, session_status, request_note)
                VALUES (?, ?, ?, ?, 'Pending', ?)");

            $stmt->bind_param("iiiss", $tutor_id, $tutee_id, $tutor['subject_id'], $schedule, $note);
            $stmt->execute();

            $message = "Request sent!";
            $message_type = "success";
        }
    } else {
        // No availability slots defined for this subject — allow the request.
        $stmt = $conn->prepare("INSERT INTO sessions 
            (tutor_id, tutee_id, subject_id, requested_schedule, session_status, request_note)
            VALUES (?, ?, ?, ?, 'Pending', ?)");

        $stmt->bind_param("iiiss", $tutor_id, $tutee_id, $tutor['subject_id'], $schedule, $note);
        $stmt->execute();

        $message = "Request sent!";
        $message_type = "success";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Session</title>
    <link rel="stylesheet" href="../../Frontend/css/request_session.css">
    <style>
        .back {
            display: block !important;
            margin-top: 12px !important;
            padding: 12px !important;
            background: #d4a63f !important;
            border-radius: 8px !important;
            text-decoration: none !important;
            color: white !important;
            font-weight: bold !important;
            text-align: center !important;
        }
        .back:hover { opacity: 0.9; }
        button { width: 100%; }

        #sys-toast {
            display: none;
            position: fixed;
            top: 24px;
            left: 50%;
            transform: translateX(-50%);
            background: #fff;
            border-left: 5px solid #c0392b;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.18);
            padding: 16px 20px;
            max-width: 460px;
            width: 92%;
            z-index: 99999;
            font-size: 0.92rem;
            color: #1a2d5a;
        }
        #sys-toast .toast-inner {
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }
        #sys-toast .toast-icon { font-size: 1.2rem; }
        #sys-toast .toast-body { flex: 1; }
        #sys-toast .toast-title {
            font-weight: 700;
            color: #c0392b;
            margin-bottom: 4px;
        }
        #sys-toast .toast-close {
            background: none;
            border: none;
            font-size: 1rem;
            cursor: pointer;
            color: #999;
            padding: 0;
            width: auto !important;
            margin: 0 !important;
        }
    </style>
</head>

<body>

<div id="sys-toast">
    <div class="toast-inner">
        <span class="toast-icon">⚠️</span>
        <div class="toast-body">
            <div class="toast-title">Notice</div>
            <div id="sys-toast-msg"></div>
        </div>
        <button class="toast-close" onclick="document.getElementById('sys-toast').style.display='none'">✕</button>
    </div>
</div>

<script>
(function() {
    window.alert = function(msg) {
        var toast = document.getElementById('sys-toast');
        document.getElementById('sys-toast-msg').textContent = msg;
        toast.style.display = 'block';
        clearTimeout(toast._t);
        toast._t = setTimeout(function(){ toast.style.display = 'none'; }, 6000);
    };
})();
</script>

<div class="container">

    <h1>Request Tutoring Session</h1>

    <?php if ($message): ?>
        <div class="message <?php echo $message_type; ?>">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <div class="tutor-info">
        <p><strong>Tutor:</strong> <?php echo htmlspecialchars($tutor['name']); ?></p>

        <p>
            <strong>Subject:</strong>
            <!-- FIX: Moved <select> inside the <form> below via JS relocation is not needed —
                 instead we keep the select here for display but ensure the hidden field
                 inside the form is always kept in sync (see JS below). -->
            <select id="subject-select"
                style="margin-left:6px;padding:5px 10px;border-radius:6px;border:1px solid #ccc;font-size:0.92rem;color:#1a2d5a;cursor:pointer;">
                <?php foreach ($tutor_subjects as $subj): ?>
                    <option value="<?php echo $subj['subject_id']; ?>"
                        data-rate="<?php echo $subj['rate']; ?>"
                        data-name="<?php echo htmlspecialchars($subj['subject_name']); ?>"
                        <?php echo ($subj['subject_id'] == $selected_subject_id) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($subj['subject_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>

        <p><strong>Rate:</strong> <span id="rate-display">₱<?php echo number_format($tutor['rate'], 2); ?></span></p>

        <p><strong>Available Schedule:</strong></p>
        <div id="availability-display">
            <?php
            $slots = $availability_slots;
            if (!empty($slots)): ?>
                <ul style="margin:4px 0 0 0;padding-left:18px;font-size:0.92rem;color:#444;">
                    <?php foreach ($slots as $slot): ?>
                        <li>
                            <?php echo htmlspecialchars(trim($slot['day_of_week'])); ?> &nbsp;
                            <?php echo date('h:i A', strtotime($slot['start_time'])); ?> –
                            <?php echo date('h:i A', strtotime($slot['end_time'])); ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p style="font-size:0.88rem;color:#888;margin:4px 0 0 0;">No schedule set yet.</p>
            <?php endif; ?>
        </div>
    </div>

    <script type="application/json" id="all-subjects-data">
        <?php echo json_encode($tutor_subjects); ?>
    </script>
    <script type="application/json" id="all-availability-data">
        <?php echo json_encode($all_availability); ?>
    </script>
    <script type="application/json" id="availability-data">
        <?php echo json_encode($availability_slots); ?>
    </script>

    <script>
    (function() {
        const select     = document.getElementById('subject-select');
        const rateEl     = document.getElementById('rate-display');
        const availEl    = document.getElementById('availability-display');
        const availData  = JSON.parse(document.getElementById('all-availability-data').textContent);
        const availJson  = document.getElementById('availability-data');

        function formatTime(t) {
            const parts = t.split(':');
            let h = parseInt(parts[0]), m = parts[1];
            const ampm = h >= 12 ? 'PM' : 'AM';
            h = h % 12 || 12;
            return h + ':' + m + ' ' + ampm;
        }

        function renderAvailability(slots) {
            if (!slots || slots.length === 0) {
                return '<p style="font-size:0.88rem;color:#888;margin:4px 0 0 0;">No schedule set yet.</p>';
            }
            let html = '<ul style="margin:4px 0 0 0;padding-left:18px;font-size:0.92rem;color:#444;">';
            slots.forEach(function(s) {
                html += '<li>' + s.day_of_week.trim() + ' &nbsp;' + formatTime(s.start_time) + ' – ' + formatTime(s.end_time) + '</li>';
            });
            html += '</ul>';
            return html;
        }

        select.addEventListener('change', function() {
            const opt   = select.options[select.selectedIndex];
            const sid   = opt.value;
            const rate  = parseFloat(opt.dataset.rate).toFixed(2);
            const slots = availData[sid] || [];

            rateEl.textContent    = '₱' + parseFloat(rate).toLocaleString('en-PH', {minimumFractionDigits:2});
            availEl.innerHTML     = renderAvailability(slots);
            availJson.textContent = JSON.stringify(slots);

            // FIX: Sync BOTH hidden subject_id fields so the POSTed value always
            // matches what the user sees in the dropdown, regardless of which field
            // PHP happens to read from $_POST.
            const f1 = document.getElementById('subject-id-form-field');
            const f2 = document.getElementById('subject-id-field');
            if (f1) f1.value = sid;
            if (f2) f2.value = sid;
        });
    })();
    </script>

    <form method="POST" id="session-form">

        <!-- FIX: This is the ONLY subject_id hidden field that matters for POST.
             The one outside the form (subject-id-field) is kept for JS compat only
             but is never submitted. This field is always synced by the JS above. -->
        <input type="hidden" name="subject_id" id="subject-id-form-field"
               value="<?php echo $selected_subject_id; ?>">

        <label>Start Time</label>
        <input type="datetime-local" name="requested_schedule" id="start_time" required>

        <label>End Time</label>
        <input type="time" name="end_time" id="end_time" required>

        <p id="duration-display" style="margin-top:6px;font-size:0.9rem;color:#555;"></p>
        <p id="duration-error" style="margin-top:4px;font-size:0.88rem;color:#c0392b;display:none;"></p>

        <label>Message (optional)</label>
        <textarea name="request_note"></textarea>

        <button type="submit">Submit Request</button>

    </form>

    <!-- FIX: kept outside the form intentionally — display-only, never submitted -->
    <input type="hidden" name="subject_id" id="subject-id-field"
           value="<?php echo $selected_subject_id; ?>">

    <script>
    (function () {
        const startEl = document.getElementById('start_time');
        const endEl   = document.getElementById('end_time');
        const display = document.getElementById('duration-display');
        const errEl   = document.getElementById('duration-error');
        const form    = document.getElementById('session-form');
        const MIN_MINUTES = 30;
        const MAX_HOURS   = 8;

        function validate() {
            if (!startEl.value || !endEl.value) {
                display.textContent = '';
                errEl.style.display = 'none';
                return true;
            }
            const startDate = startEl.value.substring(0, 10);
            const start     = new Date(startEl.value);
            const end       = new Date(startDate + 'T' + endEl.value);
            const diffMin   = (end - start) / 60000;

            if (diffMin <= 0) {
                display.textContent = '';
                errEl.textContent   = 'End time must be after start time.';
                errEl.style.display = 'block';
                return false;
            }
            if (diffMin < MIN_MINUTES) {
                display.textContent = '';
                errEl.textContent   = 'Minimum session duration is 30 minutes.';
                errEl.style.display = 'block';
                return false;
            }
            if (diffMin > MAX_HOURS * 60) {
                display.textContent = '';
                errEl.textContent   = 'Maximum session duration is 8 hours.';
                errEl.style.display = 'block';
                return false;
            }
            errEl.style.display = 'none';
            const hrs  = Math.floor(diffMin / 60);
            const mins = diffMin % 60;
            display.textContent = 'Duration: ' +
                (hrs  > 0 ? hrs  + ' hr'  + (hrs  > 1 ? 's' : '') + ' ' : '') +
                (mins > 0 ? mins + ' min' + (mins > 1 ? 's' : '')        : '');
            return true;
        }

        startEl.addEventListener('change', function () {
            if (startEl.value) {
                endEl.min = startEl.value.substring(11, 16);
            }
            validate();
        });
        endEl.addEventListener('change', validate);

        form.addEventListener('submit', function (e) {
            if (!validate()) e.preventDefault();
        });
    })();
    </script>

    <a href="/tutorloop/search_results.php" class="back">← Back</a>

</div>

<script src="../../Frontend/js/request_session.js"></script>
</body>
</html>
