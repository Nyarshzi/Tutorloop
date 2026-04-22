<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "tutorloop_db";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Using tutee_id 2 for testing; replace with session data later
$tutee_id = 2; 

// Fetch sessions with Tutor and Subject details
$sql = "SELECT s.*, u.name AS tutor_name, sub.subject_name 
        FROM sessions s
        JOIN users u ON s.tutor_id = u.user_id 
        JOIN subjects sub ON s.subject_id = sub.subject_id
        WHERE s.tutee_id = $tutee_id AND s.status = 'Confirmed'
        ORDER BY s.requested_schedule ASC";

// Corrected Query for line 24
$query = "SELECT s.*, u.name as tutor_name, sub.subject_name 
          FROM sessions s 
          JOIN users u ON s.tutor_id = u.user_id 
          JOIN subjects sub ON s.subject_id = sub.subject_id 
          WHERE s.tutee_id = $tutee_id 
          AND s.session_status = 'Pending' 
          ORDER BY s.session_id DESC";

$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sessions | TutorLoop</title>

    <link rel="stylesheet" href="Frontend/css/tutee_dashboard.css">
    <link rel="stylesheet" href="Frontend/css/tutee_sessions.css">
</head>
<body>

<div class="container">

    <aside class="sidebar" id="sidebar">
        <div class="logo">
        <img src="Frontend/images/Tutorloop_logo.png" alt="logo">
        <span>TUTORLOOP</span>
        </div>

        <nav>
            <a href="tutee_dashboard.php">Dashboard</a>
            <a href="tutee_session.php" class="active">Sessions</a>
            <a href="tutee_messages.php">Messages</a>
            <a href="tutee_profile.php">Profile</a>
        </nav>
    </aside>

    <main class="main">

        <header class="topbar">
            <button class="menu-btn" id="menuBtn"></button>
            <h1>My Sessions</h1>
            <button class="logout" onclick="location.href='logout.php'">Logout</button>
        </header>

        <section class="sessions">

            <?php if ($result && $result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): 
                    $timestamp = strtotime($row['requested_schedule']);
                ?>
                    <div class="session-card">
                        <div class="session-info">
                            <h3><?php echo htmlspecialchars($row['subject_name']); ?></h3>
                            <p><strong>Tutor:</strong> <?php echo htmlspecialchars($row['tutor_name']); ?></p>
                            <p><strong>Date:</strong> <?php echo date("F j, Y", $timestamp); ?></p>
                            <p><strong>Time:</strong> <?php echo date("g:i A", $timestamp); ?></p>
                        </div>

                        <div class="session-actions">
                            <button class="join">Scheduled</button>
                            <button class="cancel" onclick="confirmCancel(<?php echo $row['session_id']; ?>)">Cancel</button>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="session-card empty">
                    <p>No confirmed sessions found.</p>
                </div>
            <?php endif; ?>

        </section>

    </main>

</div>

<script>
    // Confirmation for cancellation
    function confirmCancel(id) {
        if(confirm('Are you sure you want to cancel this session?')) {
            window.location.href = 'cancel_session.php?id=' + id;
        }
    }

    // Sidebar toggle for mobile
    const menuBtn = document.getElementById('menuBtn');
    const sidebar = document.getElementById('sidebar');
    if(menuBtn) {
        menuBtn.addEventListener('click', () => {
            sidebar.classList.toggle('active');
        });
    }
</script>

</body>
</html>