<?php
session_start();
include("config/db.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'tutee') {
    header("Location: login.php");
    exit();
}

$tutee_id = $_SESSION['user_id'];
$success = "";
$error = "";

$preselected_session_id = isset($_GET['session_id']) ? intval($_GET['session_id']) : 0;

// Get sessions that are Completed and haven't been rated yet
$sessions_sql = "SELECT s.session_id, s.tutor_id, s.subject_id,
                        u.name as tutor_name, sub.subject_name
                 FROM sessions s
                 JOIN users u ON s.tutor_id = u.user_id
                 JOIN subjects sub ON s.subject_id = sub.subject_id
                 WHERE s.tutee_id = ?
                 AND s.session_status = 'Completed'
                 ORDER BY s.requested_schedule DESC";
$sessions_stmt = $conn->prepare($sessions_sql);
$sessions_stmt->bind_param("i", $tutee_id);
$sessions_stmt->execute();
$sessions_result = $sessions_stmt->get_result();
$sessions_list = [];
while ($row = $sessions_result->fetch_assoc()) {
    // Check if already rated
    $check_rated = $conn->prepare("SELECT feedback_id FROM feedback_ratings WHERE session_id = ?");
    $check_rated->bind_param("i", $row['session_id']);
    $check_rated->execute();
    if ($check_rated->get_result()->num_rows == 0) {
        $sessions_list[] = $row;
    }
}

// Rating label mapping function
function getRatingLabel($rating) {
    if ($rating >= 10) return 'Excellent';
    if ($rating >= 8) return 'Very Good';
    if ($rating >= 6) return 'Good';
    if ($rating >= 4) return 'Fair';
    return 'Poor';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rate Your Session | TutorLoop</title>
    <link rel="stylesheet" href="Frontend/css/tutee_dashboard.css">
    <link rel="stylesheet" href="Frontend/css/tutee_session.css">
    <link rel="stylesheet" href="Frontend/css/rating.css">
</head>
<body>
<div class="container">
    <aside class="sidebar">
        <div class="logo">
            <img src="Frontend/images/Tutorloop_logo.png" alt="logo">
            <span>TUTORLOOP</span>
        </div>
        <nav>
            <a href="/tutorloop/tutee/tutee_dashboard.php">Dashboard</a>
            <a href="/tutorloop/tutee/tutee_profile.php">My Profile</a>
            <a href="search_results.php">Find a Tutor</a>
            <a href="/tutorloop/tutee/tutee_session.php" class="active">My Sessions</a>
            <a href="/tutorloop/tutee/tutee_mytutor.php">My Tutors</a>
            <a href="/tutorloop/tutee/tutee_messages.php">Messages</a>
        </nav>
    </aside>

    <main class="main">
        <header class="topbar">
            <h1>Rate Your Session</h1>
            <button class="logout" onclick="location.href='logout.php'">Logout</button>
        </header>

        <section class="sessions">
            <?php if ($success): ?>
                <div style="background:#d4edda; color:#155724; padding:12px 16px;
                            border-radius:8px; margin-bottom:16px;">
                    <?php echo $success; ?>
                    <a href="/tutorloop/tutee/tutee_session.php" style="margin-left:10px; 
                        color:#155724; font-weight:600;">
                        Back to My Sessions
                    </a>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div style="background:#f8d7da; color:#721c24; padding:12px 16px;
                            border-radius:8px; margin-bottom:16px;">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <div class="session-card">
                <form id="ratingForm">
                    <!-- Session Selector -->
                    <div style="margin-bottom:16px;">
                        <label style="font-weight:600; display:block; margin-bottom:6px;">
                            Select Session:
                        </label>
                        <select name="session_id" id="session_id"
                                required onchange="fillTutorId()"
                                style="width:100%; padding:10px; border-radius:8px;
                                       border:1px solid #ddd; font-size:14px;">
                            <option value="">-- Choose a Session --</option>
                            <?php foreach ($sessions_list as $row): ?>
                                <option value="<?php echo $row['session_id']; ?>"
                                        data-tutor="<?php echo $row['tutor_id']; ?>"
                                        <?php echo ($preselected_session_id == $row['session_id'])
                                            ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($row['tutor_name']); ?>
                                    — <?php echo htmlspecialchars($row['subject_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <input type="hidden" name="tutor_id" id="tutor_id">

                    <!-- 1-10 Rating Buttons -->
                    <div style="margin-bottom:16px;">
                        <label style="font-weight:600; display:block; margin-bottom:6px;">
                            Your Rating: 
                            <span style="color:#dc3545; font-size:13px;">* required</span>
                        </label>
                        <div class="rating-container" id="ratingContainer">
                            <?php for ($i = 1; $i <= 10; $i++): ?>
                                <button type="button" class="rating-btn" 
                                        data-value="<?php echo $i; ?>" 
                                        onclick="selectRating(<?php echo $i; ?>)">
                                    <?php echo $i; ?>
                                </button>
                            <?php endfor; ?>
                        </div>
                        <div class="rating-label" id="ratingLabel">
                            Click a number to rate
                        </div>
                    </div>

                    <!-- Comment (optional) -->
                    <div style="margin-bottom:20px;">
                        <label style="font-weight:600; display:block; margin-bottom:6px;">
                            Comment: 
                            <span style="color:#999; font-weight:400; font-size:13px;">
                                (optional)
                            </span>
                        </label>
                        <textarea name="feedback_comment" id="feedback_comment"
                                  placeholder="Share your experience with this tutor..."
                                  style="width:100%; padding:10px; border-radius:8px;
                                         border:1px solid #ddd; font-size:14px;
                                         min-height:100px; resize:vertical;
                                         box-sizing:border-box;"></textarea>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" id="submitBtn" class="rating-submit-btn" disabled>
                        Submit Rating
                    </button>
                </form>
            </div>
        </section>
    </main>
</div>

<script>
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// RATING SELECTION LOGIC
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
let selectedRating = null;

function getRatingLabel(rating) {
    if (rating >= 10) return 'Excellent';
    if (rating >= 8) return 'Very Good';
    if (rating >= 6) return 'Good';
    if (rating >= 4) return 'Fair';
    return 'Poor';
}

function selectRating(value) {
    selectedRating = value;
    
    // Update button styles
    document.querySelectorAll('.rating-btn').forEach(btn => {
        btn.classList.remove('selected');
        if (parseInt(btn.dataset.value) === value) {
            btn.classList.add('selected');
        }
    });
    
    // Update label
    document.getElementById('ratingLabel').textContent = getRatingLabel(value);
    
    // Enable submit button
    document.getElementById('submitBtn').disabled = false;
}

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// FORM SUBMISSION (AJAX)
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
document.getElementById('ratingForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    if (!selectedRating) {
        alert('Please select a rating.');
        return;
    }
    
    const sessionId = document.getElementById('session_id').value;
    const tutorId = document.getElementById('tutor_id').value;
    const comment = document.getElementById('feedback_comment').value;
    
    if (!sessionId) {
        alert('Please select a session.');
        return;
    }
    
    const submitBtn = document.getElementById('submitBtn');
    submitBtn.disabled = true;
    submitBtn.textContent = 'Submitting...';
    
    const formData = new FormData();
    formData.append('session_id', sessionId);
    formData.append('rating', selectedRating);
    formData.append('comment', comment);
    
    fetch('/tutorloop/tutee/api/submit_rating.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show thank you message
            const formContainer = document.querySelector('.session-card');
            formContainer.innerHTML = `
                <div class="thank-you-message">
                    <div class="thank-you-icon">🎉</div>
                    <h3>Thank you for your rating!</h3>
                    <p>Your feedback helps improve our tutoring community.</p>
                    <a href="/tutorloop/tutee/tutee_session.php" 
                       style="display:inline-block; margin-top:16px; padding:12px 24px;
                              background:#d4a017; color:#fff; text-decoration:none;
                              border-radius:8px; font-weight:600;">
                        Back to My Sessions
                    </a>
                </div>
            `;
        } else {
            alert('Error: ' + (data.error || 'Something went wrong.'));
            submitBtn.disabled = false;
            submitBtn.textContent = 'Submit Rating';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
        submitBtn.disabled = false;
        submitBtn.textContent = 'Submit Rating';
    });
});

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// AUTO FILL TUTOR ID
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
window.addEventListener('load', function() {
    const select = document.getElementById("session_id");
    if (select.value) {
        const tutor = select.options[select.selectedIndex]
                            .getAttribute("data-tutor");
        document.getElementById("tutor_id").value = tutor;
    }
});

function fillTutorId() {
    const select = document.getElementById("session_id");
    const tutor = select.options[select.selectedIndex]
                        .getAttribute("data-tutor");
    document.getElementById("tutor_id").value = tutor;
}
</script>
</body>
</html>