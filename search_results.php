<?php
include("config/db.php");

$selected_subject_id = isset($_GET['subject_id']) ? intval($_GET['subject_id']) : 0;

$subjects_sql = "SELECT subject_id, subject_name FROM subjects ORDER BY subject_name ASC";
$subjects_result = $conn->query($subjects_sql);

if ($selected_subject_id > 0) {
    $sql = "SELECT DISTINCT 
                u.user_id as tutor_id,
                u.name,
                u.profile_pic,
                tp.description,
                tp.average_rating,
                tp.tutoring_rate
            FROM users u
            JOIN tutor_profiles tp ON u.user_id = tp.tutor_id
            JOIN tutor_subjects ts ON u.user_id = ts.tutor_id
            WHERE ts.subject_id = ?
            AND u.role = 'tutor'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $selected_subject_id);
    $stmt->execute();
    $results = $stmt->get_result();
} else {
    $sql = "SELECT DISTINCT 
                u.user_id as tutor_id,
                u.name,
                u.profile_pic,
                tp.description,
                tp.average_rating,
                tp.tutoring_rate
            FROM users u
            JOIN tutor_profiles tp ON u.user_id = tp.tutor_id
            WHERE u.role = 'tutor'";
    $results = $conn->query($sql);
}

function getTutorSubjects($conn, $tutor_id) {
    $sql = "SELECT s.subject_name, ts.rate
            FROM tutor_subjects ts
            JOIN subjects s ON ts.subject_id = s.subject_id
            WHERE ts.tutor_id = ?
            ORDER BY s.subject_name ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $tutor_id);
    $stmt->execute();
    return $stmt->get_result();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Tutors | TutorLoop</title>
    <link rel="stylesheet" href="Frontend/css/tutee_dashboard.css">
    <link rel="stylesheet" href="Frontend/css/search_results.css">

    <!-- FIX: Inline overrides to correct card layout, image overflow, grid alignment, and missing closing tag issues -->
    <style>
        /* ── Results container ── */
        .results-container {
            padding: 0 24px 32px;
        }

        /* ── Grid: uniform columns, equal-height cards ── */
        .tutor-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 24px;
            align-items: stretch; /* FIX: makes all cards in a row the same height */
        }

        /* ── Card: full reset so image sits INSIDE the card ── */
        .tutor-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 18px rgba(0,0,0,0.10);
            overflow: hidden;           /* clips image to card bounds */
            display: flex;
            flex-direction: column;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .tutor-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 28px rgba(0,0,0,0.15);
        }

        /* ── Image block: fixed-height banner, image centered inside ── */
        .tutor-image {
            width: 100%;
            height: 130px;
            background: #1a2d5a;       /* dark navy header band */
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .tutor-image img {
            width: 88px;
            height: 88px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #fff;
            /* FIX: removed negative margin-top that caused overflow */
            margin-top: 0;
        }

        /* ── Avatar placeholder (no photo) ── */
        .avatar-placeholder {
            width: 88px;
            height: 88px;
            border-radius: 50%;
            background: #e8b923;
            color: #1a2d5a;
            font-size: 2rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 3px solid #fff;
        }

        /* ── Info section ── */
        .tutor-info {
            padding: 16px 18px 20px;
            display: flex;
            flex-direction: column;
            gap: 6px;
            flex: 1; /* stretches to fill card height */
        }

        /* FIX: push view button to bottom of card */
        .tutor-info .view-btn {
            margin-top: auto;
            padding-top: 14px;
        }

        /* FIX: removed stray orange/link color on tutor name */
        .tutor-name-link,
        .tutor-name-link:visited,
        .tutor-name-link:hover {
            text-decoration: none;
            color: inherit;
        }
        .tutor-info h3 {
            margin: 0;
            font-size: 1.05rem;
            font-weight: 700;
            color: #1a2d5a;
            text-align: center;
        }

        .tutor-rating {
            margin: 0;
            font-size: 0.88rem;
            color: #555;
            text-align: center;
        }

        .tutor-desc {
            margin: 4px 0 0;
            font-size: 0.83rem;
            color: #666;
            line-height: 1.45;
            text-align: center;
        }

        /* ── Subjects ── */
        .tutor-subjects {
            margin-top: 8px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
        }
        .tutor-subjects p {
            margin: 0 0 6px;
            font-size: 0.82rem;
            font-weight: 600;
            color: #1a2d5a;
            text-align: center;
        }
        /* ADD: scrollable subject tag area — max 2 tags visible, scrolls if more */
        .subjects-scroll {
            width: 100%;
            max-height: 64px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
            padding-right: 2px;
        }
        .subjects-scroll::-webkit-scrollbar {
            width: 4px;
        }
        .subjects-scroll::-webkit-scrollbar-track {
            background: #f0f0f0;
            border-radius: 4px;
        }
        .subjects-scroll::-webkit-scrollbar-thumb {
            background: #c7d2fe;
            border-radius: 4px;
        }
        .subject-tag {
            display: inline-block;
            background: #eef2ff;
            color: #1a2d5a;
            font-size: 0.78rem;
            padding: 3px 10px;
            border-radius: 20px;
            border: 1px solid #c7d2fe;
            white-space: nowrap;
        }

        /* ── View Profile button ── */
        .view-btn {
            margin-top: 14px;
            width: 100%;
            padding: 10px 0;
            background: #e8b923;
            color: #1a2d5a;
            border: none;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.2s ease;
        }
        .view-btn:hover {
            background: #d4a81e;
        }

        /* ── Filter section ── */
        .filter-section {
            padding: 16px 24px;
            margin-bottom: 8px;
        }
        .filter-form {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }
        .filter-form label {
            font-size: 0.9rem;
            font-weight: 600;
            color: #1a2d5a;
        }
        .filter-form select {
            padding: 8px 14px;
            border: 2px solid #c7d2fe;
            border-radius: 8px;
            font-size: 0.88rem;
            color: #1a2d5a;
            background: #fff;
            cursor: pointer;
            outline: none;
            transition: border-color 0.2s;
        }
        .filter-form select:focus {
            border-color: #e8b923;
        }

        /* ── No results ── */
        .no-results {
            text-align: center;
            padding: 48px 0;
            color: #777;
            font-size: 0.95rem;
        }
        .no-results a {
            display: inline-block;
            margin-top: 12px;
            color: #e8b923;
            font-weight: 600;
            text-decoration: none;
        }

        /* ── Responsive ── */
        @media (max-width: 768px) {
            .tutor-grid {
                grid-template-columns: 1fr;
            }
            .results-container {
                padding: 0 12px 24px;
            }
        }
    </style>
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
            <a href="search_results.php" class="active">Find a Tutor</a>
            <a href="/tutorloop/tutee/tutee_session.php">My Sessions</a>
            <a href="/tutorloop/tutee/tutee_mytutor.php">My Tutors</a>
            <a href="/tutorloop/tutee/tutee_messages.php">Messages</a>
        </nav>
    </aside>

    <main class="main">
        <header class="topbar">
            <h1>Find a Tutor</h1>
            <a href="/tutorloop/tutee/tutee_dashboard.php" class="logout">Back</a>
        </header>

        <section class="filter-section">
            <form method="GET" class="filter-form">
                <label for="subject_id">Filter by Subject:</label>
                <select name="subject_id" id="subject_id" onchange="this.form.submit()">
                    <option value="">All Subjects</option>
                    <?php while($subject = $subjects_result->fetch_assoc()): ?>
                        <option value="<?php echo $subject['subject_id']; ?>"
                                <?php echo ($selected_subject_id == $subject['subject_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($subject['subject_name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </form>
        </section>

        <section class="results-container">
            <?php if ($results && $results->num_rows > 0): ?>
                <div class="tutor-grid">
                    <?php while($tutor = $results->fetch_assoc()):
                        $tutor_subjects = getTutorSubjects($conn, $tutor['tutor_id']);
                    ?>
                        <div class="tutor-card">
                            <div class="tutor-image">
                                <?php if(!empty($tutor['profile_pic'])): ?>
                                    <img src="uploads/<?php echo htmlspecialchars($tutor['profile_pic']); ?>" alt="Profile">
                                <?php else: ?>
                                    <div class="avatar-placeholder">
                                        <?php echo strtoupper(substr($tutor['name'], 0, 1)); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="tutor-info">
                                <a href="tutor_view_profile.php?tutor_id=<?php echo $tutor['tutor_id']; ?>" class="tutor-name-link">
                                    <h3><?php echo htmlspecialchars($tutor['name']); ?></h3>
                                </a>

                                <?php if(!empty($tutor['average_rating'])): ?>
                                    <p class="tutor-rating">⭐ <?php echo number_format($tutor['average_rating'], 1); ?>/5.0</p>
                                <?php else: ?>
                                    <p class="tutor-rating">No ratings yet</p>
                                <?php endif; ?>

                                <?php if(!empty($tutor['description'])): ?>
                                    <p class="tutor-desc">
                                        <?php echo htmlspecialchars(substr($tutor['description'], 0, 100)); ?>
                                        <?php echo strlen($tutor['description']) > 100 ? '...' : ''; ?>
                                    </p>
                                <?php endif; ?>

                                <div class="tutor-subjects">
                                    <p><strong>Subjects:</strong></p>
                                    <!-- ADD: scrollable wrapper — only this area scrolls -->
                                    <div class="subjects-scroll">
                                    <?php 
                                    $has_subjects = false;
                                    while($subj = $tutor_subjects->fetch_assoc()): 
                                        $has_subjects = true;
                                    ?>
                                        <span class="subject-tag">
                                            <?php echo htmlspecialchars($subj['subject_name']); ?>
                                            (₱<?php echo number_format($subj['rate'], 2); ?>/hr)
                                        </span>
                                    <?php endwhile; ?>
                                    <?php if(!$has_subjects): ?>
                                        <span class="subject-tag">No subjects listed yet</span>
                                    <?php endif; ?>
                                    </div>
                                </div>

                                <button class="view-btn" 
                                    onclick="location.href='/tutorloop/tutor/tutor_view_profile.php?tutor_id=<?php echo $tutor['tutor_id']; ?>'">
                                    View Profile
                                </button>
                            </div>
                        </div><!-- FIX: was missing closing > -->
                    <?php endwhile; ?>
                </div><!-- FIX: was missing closing > -->

            <?php else: ?>
                <div class="no-results">
                    <p><?php echo $selected_subject_id > 0 ? 'No tutors found teaching this subject.' : 'No tutors available yet.'; ?></p>
                    <?php if($selected_subject_id > 0): ?>
                        <a href="search_results.php">Show all tutors</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>
</div>
</body>
</html>
