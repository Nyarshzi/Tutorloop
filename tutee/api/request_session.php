<?php
// FIX: Set timezone as the very first line — before session, before db include —
//      so nothing (MySQLi init, session_start) can reset PHP's clock state.
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

// Check tutor_id
if (!isset($_GET['tutor_id']) || empty($_GET['tutor_id'])) {
    die("Invalid tutor selected.");
}

$tutor_id = intval($_GET['tutor_id']);

// Load tutor basic info
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

// ADD: Load ALL subjects for this tutor with their rates
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

// ADD: Determine selected subject (from POST, GET, or default to first)
$selected_subject_id = 0;
if (!empty($_POST['subject_id'])) {
    $selected_subject_id = intval($_POST['subject_id']);
} elseif (!empty($_GET['subject_id'])) {
    $selected_subject_id = intval($_GET['subject_id']);
}
// Default to first subject if none selected
if (!$selected_subject_id) {
    $selected_subject_id = $tutor_subjects[0]['subject_id'];
}

// Find selected subject data
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

// Load availability for ALL subjects (keyed by subject_id for JS live-update)
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

$all_availability = []; // keyed by subject_id
while ($row = $all_avail_result->fetch_assoc()) {
    $sid = $row['subject_id'];
    if (!isset($all_availability[$sid])) $all_availability[$sid] = [];
    $all_availability[$sid][] = [
        'day_of_week' => $row['day_of_week'],
        'start_time'  => $row['start_time'],
        'end_time'    => $row['end_time']
    ];
}

// Availability for the currently selected subject (used by PHP validation)
$availability_slots = $all_availability[$selected_subject_id] ?? [];

// Submit request
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $schedule = $_POST['requested_schedule'];
    $end_time = $_POST['end_time'] ?? '';  // ADD: end time from form
    $note = $_POST['request_note'];

    // FIX: Use explicit DateTime+Manila timezone for ALL date/time work in this
    //      block — never strtotime() — so results are always Manila-local.
    $manila_tz  = new DateTimeZone('Asia/Manila');
    $dt_start   = new DateTime($schedule, $manila_tz);
    $start_date = $dt_start->format('Y-m-d');
    $dt_end     = new DateTime($start_date . ' ' . $end_time, $manila_tz);
    $duration_secs = $dt_end->getTimestamp() - $dt_start->getTimestamp();

    if (empty($schedule) || empty($end_time)) {
        $message = "Start and end time are required.";
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
        // Validate that the requested schedule falls within tutor's availability

        // FIX: DateTime already constructed with Manila timezone above — reuse it.
        $requested_datetime = $dt_start;
        $requested_day      = $requested_datetime->format('l'); // e.g. "Thursday"

        // FIX: Use integer seconds-since-midnight for comparison instead of
        //      string comparison, which can silently fail on edge-case formats.
        $requested_seconds = (int)$requested_datetime->format('H') * 3600
                           + (int)$requested_datetime->format('i') * 60
                           + (int)$requested_datetime->format('s');

        $is_available = false;
        foreach ($availability_slots as $slot) {
            if ($slot['day_of_week'] === $requested_day) {
                // FIX: Convert DB time strings ("HH:MM:SS") to seconds for
                //      accurate numeric comparison instead of string comparison.
                $slot_parts   = explode(':', $slot['start_time']);
                $slot_start_s = (int)$slot_parts[0] * 3600
                              + (int)$slot_parts[1] * 60
                              + (int)($slot_parts[2] ?? 0);

                $slot_parts = explode(':', $slot['end_time']);
                $slot_end_s = (int)$slot_parts[0] * 3600
                            + (int)$slot_parts[1] * 60
                            + (int)($slot_parts[2] ?? 0);

                if ($requested_seconds >= $slot_start_s && $requested_seconds <= $slot_end_s) {
                    $is_available = true;
                    break;
                }
            }
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
        // No availability slots defined - allow the request
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
    <!-- FIX: inline override forces Back button yellow style, bypasses any cached CSS -->
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

        /* FIX: in-system notification toast */
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

<!-- FIX: in-system toast notification (replaces browser alert) -->
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

<!-- FIX: override window.alert() so any alert() call (including from cached JS) shows our toast instead -->
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

        <!-- ADD: subject selector — updates rate & availability live -->
        <p>
            <strong>Subject:</strong>
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

        <!-- Hidden field so POST knows the chosen subject -->
        <input type="hidden" name="subject_id" id="subject-id-field"
               value="<?php echo $selected_subject_id; ?>">

        <p><strong>Rate:</strong> <span id="rate-display">₱<?php echo number_format($tutor['rate'], 2); ?></span></p>

        <!-- ADD: availability display, updated live by JS -->
        <p><strong>Available Schedule:</strong></p>
        <div id="availability-display">
            <?php
            $slots = $availability_slots;
            if (!empty($slots)): ?>
                <ul style="margin:4px 0 0 0;padding-left:18px;font-size:0.92rem;color:#444;">
                    <?php foreach ($slots as $slot): ?>
                        <li>
                            <?php echo htmlspecialchars($slot['day_of_week']); ?> &nbsp;
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

    <!-- All subjects data + all availability for JS live-update -->
    <script type="application/json" id="all-subjects-data">
        <?php echo json_encode($tutor_subjects); ?>
    </script>
    <script type="application/json" id="all-availability-data">
        <?php echo json_encode($all_availability); ?>
    </script>
    <!-- Keep for backward compat with request_session.js availability check -->
    <script type="application/json" id="availability-data">
        <?php echo json_encode($availability_slots); ?>
    </script>

    <!-- ADD: live subject switch JS -->
    <script>
    (function() {
        const select     = document.getElementById('subject-select');
        const rateEl     = document.getElementById('rate-display');
        const availEl    = document.getElementById('availability-display');
        const hiddenSubj = document.getElementById('subject-id-field');
        const availData  = JSON.parse(document.getElementById('all-availability-data').textContent);
        const availJson  = document.getElementById('availability-data');

        function formatTime(t) {
            // t = "HH:MM:SS" → "h:MM AM/PM"
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
                html += '<li>' + s.day_of_week + ' &nbsp;' + formatTime(s.start_time) + ' – ' + formatTime(s.end_time) + '</li>';
            });
            html += '</ul>';
            return html;
        }

        select.addEventListener('change', function() {
            const opt     = select.options[select.selectedIndex];
            const sid     = opt.value;
            const rate    = parseFloat(opt.dataset.rate).toFixed(2);
            const slots   = availData[sid] || [];

            // Update visible fields
            rateEl.textContent     = '₱' + parseFloat(rate).toLocaleString('en-PH', {minimumFractionDigits:2});
            availEl.innerHTML      = renderAvailability(slots);
            hiddenSubj.value       = sid;

            // Update availability-data JSON for the submit validator
            availJson.textContent  = JSON.stringify(slots);
            // Sync form hidden field
            const formField = document.getElementById('subject-id-form-field');
            if (formField) formField.value = sid;
        });
    })();
    </script>

    <form method="POST" id="session-form">

        <!-- ADD: carries selected subject_id to POST handler -->
        <input type="hidden" name="subject_id" id="subject-id-form-field"
               value="<?php echo $selected_subject_id; ?>">

        <label>Start Time</label>
        <input type="datetime-local" name="requested_schedule" id="start_time" required>

        <!-- ADD: End time (time only) — date is taken from Start Time -->
        <label>End Time</label>
        <input type="time" name="end_time" id="end_time" required>

        <!-- ADD: live duration display and error -->
        <p id="duration-display" style="margin-top:6px;font-size:0.9rem;color:#555;"></p>
        <p id="duration-error" style="margin-top:4px;font-size:0.88rem;color:#c0392b;display:none;"></p>

        <label>Message (optional)</label>
        <textarea name="request_note"></textarea>

        <button type="submit">Submit Request</button>

    </form>

    <!-- ADD: JS validation — end must be after start, min 30 min, max 8 hours -->
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
            // Combine start date with end time (time-only field)
            const startDate = startEl.value.substring(0, 10); // YYYY-MM-DD
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
            // Set end time minimum to start time (HH:MM portion only)
            if (startEl.value) {
                endEl.min = startEl.value.substring(11, 16); // extract HH:MM
            }
            validate();
        });
        endEl.addEventListener('change', validate);

        form.addEventListener('submit', function (e) {
            if (!validate()) e.preventDefault();
        });
    })();
    </script>

    <!-- FIX: changed href from tutee_dashboard.php to search_results.php (Find a Tutor page) -->
    <a href="/tutorloop/search_results.php" class="back">← Back</a>

</div>

<script src="../../Frontend/js/request_session.js"></script>
</body>
</html>
