<?php
session_start();
include("../config/db.php");

// Access Control
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'tutor') {
    header("Location: ../login.php");
    exit();
}

$tutor_id = $_SESSION['user_id'];

// Load tutor's existing subjects and schedules
$tutor_subjects = [];
$subj_sql = "SELECT ts.id, ts.subject_id, ts.rate, s.subject_name 
             FROM tutor_subjects ts 
             JOIN subjects s ON ts.subject_id = s.subject_id 
             WHERE ts.tutor_id = ? 
             ORDER BY ts.id ASC";
$subj_stmt = $conn->prepare($subj_sql);
$subj_stmt->bind_param("i", $tutor_id);
$subj_stmt->execute();
$subj_result = $subj_stmt->get_result();
while ($subj_row = $subj_result->fetch_assoc()) {
    $ts_id = $subj_row['id'];
    $avail_sql = "SELECT id, day_of_week, start_time, end_time FROM tutor_availability WHERE tutor_subject_id = ? ORDER BY id ASC";
    $avail_stmt = $conn->prepare($avail_sql);
    $avail_stmt->bind_param("i", $ts_id);
    $avail_stmt->execute();
    $avail_result = $avail_stmt->get_result();
    $schedules = [];
    while ($avail_row = $avail_result->fetch_assoc()) {
        $schedules[] = $avail_row;
    }
    $subj_row['schedules'] = $schedules;
    $tutor_subjects[] = $subj_row;
}

// Handle POST submission (same as create_tutor_profile.php)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $save_ok = true;

    // Step 1 - Delete existing tutor_availability records
    $delete_avail = $conn->query("DELETE ta FROM tutor_availability ta
                 JOIN tutor_subjects ts ON ta.tutor_subject_id = ts.id
                 WHERE ts.tutor_id = $tutor_id");
    if ($delete_avail === false) {
        $save_ok = false;
    }
    
    // Step 2 - Delete existing tutor_subjects records
    $delete_subj = $conn->query("DELETE FROM tutor_subjects WHERE tutor_id = $tutor_id");
    if ($delete_subj === false) {
        $save_ok = false;
    }
    
    // Step 3 - Loop through $_POST['subjects'] and reinsert everything
    if (isset($_POST['subjects']) && is_array($_POST['subjects'])) {
        foreach ($_POST['subjects'] as $subject) {
            $subject_id = intval($subject['subject_id']);
            $rate = floatval($subject['rate']);
            if ($subject_id <= 0) continue;

            $insert_subj = $conn->prepare("INSERT INTO tutor_subjects (tutor_id, subject_id, rate) VALUES (?, ?, ?)");
            if (!$insert_subj) {
                $save_ok = false;
                continue;
            }
            $insert_subj->bind_param("iid", $tutor_id, $subject_id, $rate);
            if (!$insert_subj->execute()) {
                $save_ok = false;
            }
            $new_id = $conn->insert_id;
            $insert_subj->close();

            if (isset($subject['schedules']) && is_array($subject['schedules'])) {
                foreach ($subject['schedules'] as $schedule) {
                    $day = $schedule['day'];
                    $start = $schedule['start_time'];
                    $end = $schedule['end_time'];
                    if (empty($day) || empty($start) || empty($end)) continue;

                    $insert_avail = $conn->prepare("INSERT INTO tutor_availability (tutor_subject_id, day_of_week, start_time, end_time) VALUES (?, ?, ?, ?)");
                    if (!$insert_avail) {
                        $save_ok = false;
                        continue;
                    }
                    $insert_avail->bind_param("isss", $new_id, $day, $start, $end);
                    if (!$insert_avail->execute()) {
                        $save_ok = false;
                    }
                    $insert_avail->close();
                }
            }
        }
    }

    if ($save_ok) {
        $_SESSION['success'] = "Your subjects and schedule have been saved successfully!";
    } else {
        $_SESSION['error'] = "Something went wrong. Please try again.";
    }
    
    header("Location: tutor_myschedule.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Schedule | TutorLoop</title>
    <link rel="stylesheet" href="../Frontend/css/tutor_myschedule.css">
    
    <style>
        /* CSS FIXES FOR NAVIGATION */
        .sidebar nav a {
            text-decoration: none !important;
            color: white !important;
            display: block;
            padding: 12px;
            margin-bottom: 10px;
            border-radius: 8px;
            transition: 0.3s;
        }

        .sidebar nav a.active,
        .sidebar nav a:hover {
            background: #d4a017;
            color: black !important;
            font-weight: 500;
        }

        .logout {
            text-decoration: none;
            color: black;
            cursor: pointer;
        }
        
        .status.upcoming {
            background-color: #d1e7ff;
            color: #004085;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 0.85rem;
            font-weight: bold;
        }
    </style>
</head>
<body>

<div class="sidebar-overlay" id="sidebarOverlay"></div>
<div class="container">
    <aside class="sidebar" id="sidebar">
        <div class="logo">
<img src="../Frontend/images/Tutorloop_logo.png" alt="logo">            <span>TUTORLOOP</span>
        </div>
        <nav>
            <a href="/tutorloop/tutor/tutor_dashboard.php">Dashboard</a>
            <a href="/tutorloop/tutor/tutor_profile.php">My Profile</a>
            <a href="/tutorloop/tutor/tutor_myschedule.php" class="active">My Schedule</a>
            <a href="/tutorloop/tutor/tutor_session_request.php">Session Requests</a>
            <a href="/tutorloop/tutor/tutor_mystudents.php">My Students</a>
            <a href="/tutorloop/tutor/tutor_messages.php">Messages</a>
            <a href="/tutorloop/tutor/tutor_ratings.php">My Ratings</a>
            <a href="/tutorloop/analytics.php">Analytics</a>
        </nav>
    </aside>

    <main class="main">
        <header class="topbar">
            <button class="menu-btn" id="menuBtn" aria-label="Open menu">
                <span></span>
                <span></span>
                <span></span>
            </button>
            <h1>My Schedule</h1>
            <button class="logout" onclick="location.href='/tutorloop/logout.php'">Logout</button>
        </header>

        <section class="schedule">
            <div class="schedule-container-wrapper">
                <h2 style="margin-bottom: 20px;">My Subjects & Schedule</h2>
                <?php if (isset($_SESSION['success'])): ?>
                    <div style="background:#d4edda; color:#155724; padding:12px 16px; border-radius:8px; margin-bottom:16px;">
                        <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                    <div style="background:#f8d7da; color:#721c24; padding:12px 16px; border-radius:8px; margin-bottom:16px;">
                        <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" style="background: white; padding: 30px; border-radius: 15px; box-shadow: 0 4px 10px rgba(0,0,0,0.05);">
                    
                    <!-- Add New Subject Section -->
                    <div class="add-subject-container">
                        <h3 style="margin-bottom: 15px; font-size: 16px; font-weight: 600;">Add New Subject</h3>
                        <div style="display: grid; grid-template-columns: 1fr 1fr auto; gap: 12px; align-items: end;">
                            <div>
                                <label style="display: block; font-weight: 600; margin-bottom: 5px; font-size: 14px;">Subject</label>
                                <select name="subject_selector" id="subject_selector" style="width: 100%; padding: 10px; border: 1.5px solid #eee; border-radius: 10px; font-size: 14px;">
                                    <option value="">-- Select a Subject --</option>
                                    <?php
                                    $all_subjects = $conn->query("SELECT subject_id, subject_name FROM subjects ORDER BY subject_name ASC");
                                    while($subj = $all_subjects->fetch_assoc()) {
                                        echo "<option value='" . htmlspecialchars($subj['subject_id']) . "'>" . htmlspecialchars($subj['subject_name']) . "</option>";
                                    }
                                    echo '<option value="other">+ Other (add new subject)</option>';
                                    ?>
                                </select>
                                <input type="text" id="custom_subject_input" placeholder="Type new subject name" style="display:none; width:100%; margin-top:12px; padding:10px; border:1.5px solid #eee; border-radius:10px; font-size:14px;">
                            </div>
                            <div>
                                <label style="display: block; font-weight: 600; margin-bottom: 5px; font-size: 14px;">Rate (₱)/hr</label>
                                <input type="number" step="0.01" name="rate_input" id="rate_input" placeholder="0.00" style="width: 100%; padding: 10px; border: 1.5px solid #eee; border-radius: 10px; font-size: 14px;">
                            </div>
                            <button type="button" id="add_subject_btn" style="background: #d4a017; border: none; padding: 10px 20px; border-radius: 10px; font-weight: 600; color: #0d2a4a; cursor: pointer; white-space: nowrap;">Add Subject</button>
                        </div>
                    </div>
                    
                    <!-- Existing Subjects Container -->
                    <div id="subjects_container" style="margin-top: 25px;">
                        <?php foreach ($tutor_subjects as $idx => $subj): ?>
                        <div class="subject-card" data-index="<?php echo $idx; ?>" style="border: 1.5px solid #e0e0e0; border-radius: 14px; padding: 16px; margin-bottom: 16px; background: #fefefe;">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px;">
                                <div>
                                    <h4 style="font-size: 16px; font-weight: 700; color: #0d2a4a; margin: 0 0 4px 0;"><?php echo htmlspecialchars($subj['subject_name']); ?></h4>
                                    <span style="font-size: 13px; color: #718096; font-weight: 500;">₱<?php echo number_format($subj['rate'], 2); ?>/hr</span>
                                </div>
                                <button type="button" class="remove-subject-btn" style="background: #ff4d4d; color: white; border: none; padding: 6px 12px; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer;">Remove</button>
                            </div>
                            <input type="hidden" name="subjects[<?php echo $idx; ?>][subject_id]" value="<?php echo $subj['subject_id']; ?>">
                            <input type="hidden" name="subjects[<?php echo $idx; ?>][rate]" value="<?php echo $subj['rate']; ?>">
                            
                            <div class="schedule-container" style="margin-bottom: 12px;">
                                <?php foreach ($subj['schedules'] as $sched_idx => $sched): ?>
                                <div class="schedule-row" style="display: flex; gap: 12px; align-items: center; margin-bottom: 12px; padding: 12px; background: #f9f9f9; border: 1px solid #eee; border-radius: 10px;">
                                    <select name="subjects[<?php echo $idx; ?>][schedules][<?php echo $sched_idx; ?>][day]" class="day-select" style="flex: 0 0 130px; padding: 10px; border: 1.5px solid #eee; border-radius: 8px; font-size: 14px;">
                                        <?php foreach (['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $day): ?>
                                        <option value="<?php echo $day; ?>" <?php echo ($day === $sched['day_of_week']) ? 'selected' : ''; ?>><?php echo $day; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <input type="time" name="subjects[<?php echo $idx; ?>][schedules][<?php echo $sched_idx; ?>][start_time]" value="<?php echo $sched['start_time']; ?>" style="flex: 0 0 120px; padding: 10px; border: 1.5px solid #eee; border-radius: 8px; font-size: 14px;">
                                    <input type="time" name="subjects[<?php echo $idx; ?>][schedules][<?php echo $sched_idx; ?>][end_time]" value="<?php echo $sched['end_time']; ?>" style="flex: 0 0 120px; padding: 10px; border: 1.5px solid #eee; border-radius: 8px; font-size: 14px;">
                                    <button type="button" class="remove-schedule-btn" style="background: #ff4d4d; color: white; border: none; padding: 8px 10px; border-radius: 6px; font-size: 12px; font-weight: 600; cursor: pointer; flex-shrink: 0;">Remove</button>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <button type="button" class="add-schedule-btn" style="background: white; border: 2px solid #d4a017; padding: 10px 16px; border-radius: 10px; font-weight: 600; color: #d4a017; cursor: pointer; width: 100%;">Add Schedule</button>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Submit Button -->
                    <div style="margin-top: 25px;">
                        <button type="submit" style="background: #d4a017; border: none; padding: 14px 30px; border-radius: 12px; font-weight: 700; color: #0d2a4a; cursor: pointer; font-size: 16px; width: 100%;">Save Changes</button>
                    </div>
                </form>
            </div>
        </section>
    </main>
</div>

<script>
    // Import the same form logic used in create_tutor_profile.php
    document.addEventListener('DOMContentLoaded', () => {
        const subjectSelector = document.getElementById('subject_selector');
        const customSubjectInput = document.getElementById('custom_subject_input');
        const rateInput = document.getElementById('rate_input');
        const addSubjectBtn = document.getElementById('add_subject_btn');
        const subjectsContainer = document.getElementById('subjects_container');

        let subjectCount = subjectsContainer.querySelectorAll('.subject-card').length;

        if (subjectSelector) {
            subjectSelector.addEventListener('change', () => {
                if (!customSubjectInput) return;
                if (subjectSelector.value === 'other') {
                    customSubjectInput.style.display = 'block';
                    customSubjectInput.value = '';
                    customSubjectInput.focus();
                } else {
                    customSubjectInput.style.display = 'none';
                    customSubjectInput.value = '';
                }
            });
        }

        // ===== ADD SUBJECT BUTTON =====
        addSubjectBtn.addEventListener('click', () => {
            const selectedId = subjectSelector.value;
            const selectedText = subjectSelector.options[subjectSelector.selectedIndex] ? subjectSelector.options[subjectSelector.selectedIndex].text : '';
            const rate = rateInput.value;
            if (!rate || parseFloat(rate) <= 0) {
    alert('Please enter a valid rate');
    return;
}

            // Validation
            if (!selectedId) {
                alert('Please select a subject');
                return;
            }

            if (selectedId === 'other') {
                if (!customSubjectInput) {
                    alert('Please type a subject name');
                    return;
                }
                const customValue = customSubjectInput.value.trim();
                if (!customValue) {
                    alert('Please type a subject name');
                    customSubjectInput.focus();
                    return;
                }

                fetch('/tutorloop/tutee/api/add_subject.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ subject_name: customValue })
                })
                .then(response => response.json())
                .then(data => {
                    if (!data || !data.success) {
                        alert(data && data.message ? data.message : 'Unable to add subject');
                        return;
                    }

                    const existingOption = subjectSelector.querySelector(`option[value="${data.subject_id}"]`);
                    if (!existingOption) {
                        const newOption = document.createElement('option');
                        newOption.value = data.subject_id;
                        newOption.textContent = data.subject_name;
                        const otherOption = subjectSelector.querySelector('option[value="other"]');
                        if (otherOption) {
                            subjectSelector.insertBefore(newOption, otherOption);
                        } else {
                            subjectSelector.appendChild(newOption);
                        }
                    }

                    createCard(data.subject_id, data.subject_name);
                })
                .catch(() => {
                    alert('Unable to add subject. Please try again.');
                });
                return;
            }

            // Check if subject already added
            const existing = subjectsContainer.querySelector(`input[value="${selectedId}"][name*="subject_id"]`);
            if (existing) {
                alert('This subject has already been added');
                return;
            }

            createCard(selectedId, selectedText, rate);
        });

        const createCard = (subjectId, subjectText, rate) => {
            const card = document.createElement('div');
            card.className = 'subject-card';
            card.dataset.index = subjectCount;
            card.style.cssText = 'border: 1.5px solid #e0e0e0; border-radius: 14px; padding: 16px; margin-bottom: 16px; background: #fefefe;';
            card.innerHTML = `
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px;">
                    <div>
                        <h4 style="font-size: 16px; font-weight: 700; color: #0d2a4a; margin: 0 0 4px 0;">${escapeHtml(subjectText)}</h4>
                        <span style="font-size: 13px; color: #718096; font-weight: 500;">₱${parseFloat(rate).toFixed(2)}/hr</span>
                    </div>
                    <button type="button" class="remove-subject-btn" style="background: #ff4d4d; color: white; border: none; padding: 6px 12px; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer;">Remove</button>
                </div>
                <input type="hidden" name="subjects[${subjectCount}][subject_id]" value="${subjectId}">
                <input type="hidden" name="subjects[${subjectCount}][rate]" value="${rate}">
                <div class="schedule-container" style="margin-bottom: 12px;"></div>
                <button type="button" class="add-schedule-btn" style="background: white; border: 2px solid #d4a017; padding: 10px 16px; border-radius: 10px; font-weight: 600; color: #d4a017; cursor: pointer; width: 100%;">Add Schedule</button>
            `;

            card.querySelector('.remove-subject-btn').addEventListener('click', (e) => {
                e.preventDefault();
                card.remove();
                reindexSubjects();
            });

            card.querySelector('.add-schedule-btn').addEventListener('click', (e) => {
                e.preventDefault();
                addScheduleRow(card, subjectCount);
            });

            subjectsContainer.appendChild(card);
            subjectCount++;

            subjectSelector.value = '';
            rateInput.value = '';
            if (customSubjectInput) {
                customSubjectInput.style.display = 'none';
                customSubjectInput.value = '';
            }
        };


        // ===== ADD SCHEDULE ROW =====
        function addScheduleRow(card, subjectIndex) {
            const scheduleContainer = card.querySelector('.schedule-container');
            const scheduleIndex = scheduleContainer.querySelectorAll('.schedule-row').length;
            const days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

            const row = document.createElement('div');
            row.className = 'schedule-row';
            row.style.cssText = 'display: flex; gap: 12px; align-items: center; margin-bottom: 12px; padding: 12px; background: #f9f9f9; border: 1px solid #eee; border-radius: 10px;';
            row.innerHTML = `
                <select name="subjects[${subjectIndex}][schedules][${scheduleIndex}][day]" class="day-select" style="flex: 0 0 130px; padding: 10px; border: 1.5px solid #eee; border-radius: 8px; font-size: 14px;">
                    ${days.map(day => `<option value="${day}">${day}</option>`).join('')}
                </select>
                <input type="time" name="subjects[${subjectIndex}][schedules][${scheduleIndex}][start_time]" required style="flex: 0 0 120px; padding: 10px; border: 1.5px solid #eee; border-radius: 8px; font-size: 14px;">
                <input type="time" name="subjects[${subjectIndex}][schedules][${scheduleIndex}][end_time]" required style="flex: 0 0 120px; padding: 10px; border: 1.5px solid #eee; border-radius: 8px; font-size: 14px;">
                <button type="button" class="remove-schedule-btn" style="background: #ff4d4d; color: white; border: none; padding: 8px 10px; border-radius: 6px; font-size: 12px; font-weight: 600; cursor: pointer; flex-shrink: 0;">Remove</button>
            `;

            row.querySelector('.remove-schedule-btn').addEventListener('click', (e) => {
                e.preventDefault();
                row.remove();
                reindexSchedules(card, subjectIndex);
            });

            scheduleContainer.appendChild(row);
        }

        // ===== REINDEX SCHEDULES IN A CARD =====
        function reindexSchedules(card, subjectIndex) {
            const scheduleContainer = card.querySelector('.schedule-container');
            const rows = scheduleContainer.querySelectorAll('.schedule-row');

            rows.forEach((row, scheduleIndex) => {
                const daySelect = row.querySelector('.day-select');
                const startInput = row.querySelector('input[type="time"]:first-of-type');
                const endInput = row.querySelector('input[type="time"]:last-of-type');

                daySelect.name = `subjects[${subjectIndex}][schedules][${scheduleIndex}][day]`;
                startInput.name = `subjects[${subjectIndex}][schedules][${scheduleIndex}][start_time]`;
                endInput.name = `subjects[${subjectIndex}][schedules][${scheduleIndex}][end_time]`;
            });
        }

        // ===== REINDEX ALL SUBJECTS =====
        function reindexSubjects() {
            const cards = subjectsContainer.querySelectorAll('.subject-card');

            cards.forEach((card, subjectIndex) => {
                card.dataset.index = subjectIndex;

                // Update subject hidden inputs
                const subjectIdInput = card.querySelector('input[name*="subject_id"]');
                const rateHiddenInput = card.querySelector('input[name*="rate"]');
                subjectIdInput.name = `subjects[${subjectIndex}][subject_id]`;
                rateHiddenInput.name = `subjects[${subjectIndex}][rate]`;

                // Update schedule rows and add listeners
                const scheduleContainer = card.querySelector('.schedule-container');
                const scheduleRows = scheduleContainer.querySelectorAll('.schedule-row');

                scheduleRows.forEach((row, scheduleIndex) => {
                    const daySelect = row.querySelector('.day-select');
                    const startInput = row.querySelector('input[type="time"]:first-of-type');
                    const endInput = row.querySelector('input[type="time"]:last-of-type');
                    const removeBtn = row.querySelector('.remove-schedule-btn');

                    daySelect.name = `subjects[${subjectIndex}][schedules][${scheduleIndex}][day]`;
                    startInput.name = `subjects[${subjectIndex}][schedules][${scheduleIndex}][start_time]`;
                    endInput.name = `subjects[${subjectIndex}][schedules][${scheduleIndex}][end_time]`;

                    removeBtn.onclick = (e) => {
                        e.preventDefault();
                        row.remove();
                        reindexSchedules(card, subjectIndex);
                    };
                });

                // Update add schedule button listener
                const addScheduleBtn = card.querySelector('.add-schedule-btn');
                addScheduleBtn.onclick = (e) => {
                    e.preventDefault();
                    addScheduleRow(card, subjectIndex);
                };

                // Update remove subject button listener
                const removeSubjectBtn = card.querySelector('.remove-subject-btn');
                removeSubjectBtn.onclick = (e) => {
                    e.preventDefault();
                    card.remove();
                    reindexSubjects();
                };
            });

            subjectCount = cards.length;
        }

        // ===== INITIALIZE EXISTING SUBJECTS WITH LISTENERS =====
        function initializeExistingSubjects() {
            const cards = subjectsContainer.querySelectorAll('.subject-card');
            cards.forEach((card, subjectIndex) => {
                const removeSubjectBtn = card.querySelector('.remove-subject-btn');
                const addScheduleBtn = card.querySelector('.add-schedule-btn');
                const scheduleContainer = card.querySelector('.schedule-container');

                removeSubjectBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    card.remove();
                    reindexSubjects();
                });

                addScheduleBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    addScheduleRow(card, subjectIndex);
                });

                const removeScheduleBtns = scheduleContainer.querySelectorAll('.remove-schedule-btn');
                removeScheduleBtns.forEach((btn) => {
                    btn.addEventListener('click', (e) => {
                        e.preventDefault();
                        btn.closest('.schedule-row').remove();
                        reindexSchedules(card, subjectIndex);
                    });
                });
            });
        }

        initializeExistingSubjects();
    });

    const menuBtn = document.getElementById('menuBtn');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');

    if (menuBtn && sidebar && overlay) {
      menuBtn.addEventListener('click', () => {
        sidebar.classList.toggle('active');
        overlay.classList.toggle('active');
        document.body.classList.toggle('sidebar-open');
      });

      overlay.addEventListener('click', () => {
        sidebar.classList.remove('active');
        overlay.classList.remove('active');
        document.body.classList.remove('sidebar-open');
      });

      // Close sidebar when a nav link is tapped on mobile
      sidebar.querySelectorAll('nav a').forEach(link => {
        link.addEventListener('click', () => {
          sidebar.classList.remove('active');
          overlay.classList.remove('active');
          document.body.classList.remove('sidebar-open');
        });
      });
    } else {
      console.warn('Hamburger menu: missing element(s). Check IDs: menuBtn, sidebar, sidebarOverlay');
    }

    // Utility function to escape HTML
    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, m => map[m]);
    }
</script>

</body>
</html>