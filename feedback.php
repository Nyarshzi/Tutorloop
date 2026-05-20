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

$sessions_sql = "SELECT s.session_id, s.tutor_id, s.subject_id,
                        u.name as tutor_name, sub.subject_name
                 FROM sessions s
                 JOIN users u ON s.tutor_id = u.user_id
                 JOIN subjects sub ON s.subject_id = sub.subject_id
                 WHERE s.tutee_id = ?
                 AND s.session_status IN ('Accepted', 'Completed')
                 ORDER BY s.requested_schedule DESC";
$sessions_stmt = $conn->prepare($sessions_sql);
$sessions_stmt->bind_param("i", $tutee_id);
$sessions_stmt->execute();
$sessions_result = $sessions_stmt->get_result();
$sessions_list = [];
while ($row = $sessions_result->fetch_assoc()) {
    $sessions_list[] = $row;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $session_id = intval($_POST['session_id']);
    $tutor_id = intval($_POST['tutor_id']);
    $rating = intval($_POST['rating']);
    $feedback_comment = trim($_POST['feedback_comment'] ?? '');

    if ($rating < 1 || $rating > 5) {
        $error = "Please select a star rating.";
    } else {
        $check_stmt = $conn->prepare(
            "SELECT feedback_id FROM feedback_ratings WHERE session_id = ?"
        );
        $check_stmt->bind_param("i", $session_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            $error = "You have already submitted feedback for this session.";
        } else {
            $insert_stmt = $conn->prepare(
                "INSERT INTO feedback_ratings 
                 (session_id, tutor_id, rating, feedback_comment)
                 VALUES (?, ?, ?, ?)"
            );
            $insert_stmt->bind_param("iiis",
                $session_id, $tutor_id, $rating, $feedback_comment
            );

            if ($insert_stmt->execute()) {
                $avg_stmt = $conn->prepare(
                    "SELECT AVG(rating) as avg_rating 
                     FROM feedback_ratings WHERE tutor_id = ?"
                );
                $avg_stmt->bind_param("i", $tutor_id);
                $avg_stmt->execute();
                $avg_row = $avg_stmt->get_result()->fetch_assoc();
                $average_rating = round($avg_row['avg_rating'], 1);

                $update_stmt = $conn->prepare(
                    "UPDATE tutor_profiles 
                     SET average_rating = ? WHERE tutor_id = ?"
                );
                $update_stmt->bind_param("di", $average_rating, $tutor_id);
                $update_stmt->execute();

                $status_stmt = $conn->prepare(
                    "UPDATE sessions SET session_status = 'Completed' 
                     WHERE session_id = ?"
                );
                $status_stmt->bind_param("i", $session_id);
                $status_stmt->execute();

                $success = "Feedback submitted successfully!";
            } else {
                $error = "Something went wrong. Please try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Feedback | TutorLoop</title>
    <link rel="stylesheet" href="Frontend/css/tutee_dashboard.css">
    <link rel="stylesheet" href="Frontend/css/tutee_session.css">
</head>
<body>
<div class="container">
    <aside class="sidebar">
        <div class="logo">
            <img src="Frontend/images/Tutorloop_logo.png" alt="logo">
            <span>TUTORLOOP</span>
        </div>
        <nav>
            <a href="tutee_dashboard.php">Dashboard</a>
            <a href="tutee_profile.php">My Profile</a>
            <a href="search_results.php">Find a Tutor</a>
            <a href="tutee_session.php" class="active">My Sessions</a>
            <a href="tutee_mytutor.php">My Tutors</a>
            <a href="tutee_messages.php">Messages</a>
        </nav>
    </aside>

    <main class="main">
        <header class="topbar">
            <h1>Submit Feedback</h1>
            <button class="logout" onclick="location.href='logout.php'">Logout</button>
        </header>

        <section class="sessions">
            <?php if ($success): ?>
                <div style="background:#d4edda; color:#155724; padding:12px 16px;
                            border-radius:8px; margin-bottom:16px;">
                    <?php echo $success; ?>
                    <a href="tutee_session.php" style="margin-left:10px; 
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
                <form method="POST">

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

                    <!-- Star Rating -->
                    <div style="margin-bottom:16px;">
                        <label style="font-weight:600; display:block; margin-bottom:6px;">
                            Rating: 
                            <span style="color:#dc3545; font-size:13px;">* required</span>
                        </label>
                        <div class="star-rating" 
                             style="display:flex; gap:8px; font-size:36px; cursor:pointer;">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <label style="cursor:pointer; margin:0;">
                                    <input type="radio" name="rating" 
                                           value="<?php echo $i; ?>"
                                           required
                                           style="display:none;">
                                    <span class="star-label" 
                                          data-value="<?php echo $i; ?>"
                                          style="color:#ccc; transition:color 0.1s;">
                                        ★
                                    </span>
                                </label>
                            <?php endfor; ?>
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
                        <textarea name="feedback_comment"
                                  placeholder="Share your experience with this tutor..."
                                  style="width:100%; padding:10px; border-radius:8px;
                                         border:1px solid #ddd; font-size:14px;
                                         min-height:100px; resize:vertical;
                                         box-sizing:border-box;"></textarea>
                    </div>

                    <!-- Done Button -->
                    <button type="submit"
                            style="width:100%; padding:14px; font-size:16px;
                                   font-weight:600; background:#C89B3C; color:#fff;
                                   border:none; border-radius:8px; cursor:pointer;
                                   transition:background 0.2s;">
                        Done
                    </button>

                </form>
            </div>
        </section>
    </main>
</div>

<script>
// Auto fill tutor_id on page load if session is preselected
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

// Star rating interaction
const starLabels = document.querySelectorAll('.star-label');
const radios = document.querySelectorAll('input[name="rating"]');

function updateStars(selectedIndex) {
    starLabels.forEach((star, i) => {
        star.style.color = i <= selectedIndex ? '#f5a623' : '#ccc';
    });
}

starLabels.forEach((star, index) => {
    star.addEventListener('mouseover', () => {
        updateStars(index);
    });
    star.addEventListener('click', () => {
        radios[index].checked = true;
        updateStars(index);
    });
});

document.querySelector('.star-rating').addEventListener('mouseleave', () => {
    const checked = document.querySelector('input[name="rating"]:checked');
    const checkedIndex = checked ? parseInt(checked.value) - 1 : -1;
    updateStars(checkedIndex);
});
</script>
</body>
</html>