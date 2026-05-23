<?php
session_start();
include("../config/db.php");

if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'tutor') {
    header("Location: ../login.php"); 
    exit();
}

$current_user_id = $_SESSION['user_id']; 

$contacts_sql = "SELECT DISTINCT u.user_id, u.name 
                 FROM users u 
                 JOIN messages m ON (u.user_id = m.sender_id OR u.user_id = m.receiver_id)
                 WHERE (m.sender_id = $current_user_id OR m.receiver_id = $current_user_id)
                 AND u.user_id != $current_user_id";
$contacts_result = $conn->query($contacts_sql);

$selected_tutee_id = isset($_GET['tutee_id']) ? (int)$_GET['tutee_id'] : 0;
$selected_tutee_name = "Select a student";

if ($selected_tutee_id > 0) {
    $name_query = $conn->prepare("SELECT name FROM users WHERE user_id = ?");
    $name_query->bind_param("i", $selected_tutee_id);
    $name_query->execute();
    $name_result = $name_query->get_result();
    if($row = $name_result->fetch_assoc()) { $selected_tutee_name = $row['name']; }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages | TutorLoop</title>
   <link rel="stylesheet" href="../Frontend/css/tutor_dashboard.css">
<link rel="stylesheet" href="../Frontend/css/tutor_messages.css">
</head>
<body>
<div class="container">
    <aside class="sidebar" id="sidebar">
        <div class="logo">
                <img src="../Frontend/images/Tutorloop_logo.png" alt="logo">
            <span>TUTORLOOP</span>
        </div>
        <nav>
            <a href="/tutorloop/tutor/tutor_dashboard.php">Dashboard</a>
            <a href="/tutorloop/tutor/tutor_profile.php">My Profile</a>
            <a href="/tutorloop/tutor/tutor_myschedule.php">My Schedule</a>
            <a href="/tutorloop/tutor/tutor_session_request.php">Session Requests</a>
            <a href="/tutorloop/tutor/tutor_mystudents.php">My Students</a>
            <a href="/tutorloop/tutor/tutor_messages.php" class="active">Messages</a>
            <a href="/tutorloop/tutor/tutor_ratings.php">My Ratings</a>
            <a href="/tutorloop/analytics.php">Analytics</a>
        </nav>
    </aside>

    <main class="main">
        <header class="topbar">
            <button class="menu-btn" id="menuBtn">☰</button>
            <h1>Student Messages</h1>
            <button class="../logout.php" onclick="location.href='logout.php'">Logout</button>
        </header>

        <div class="messages">
            <div class="contacts">
                <div class="contacts-title">My Students</div>
                <?php if ($contacts_result && $contacts_result->num_rows > 0): ?>
                    <?php while($contact = $contacts_result->fetch_assoc()): ?>
                        <div class="contact <?php echo ($selected_tutee_id == $contact['user_id']) ? 'active' : ''; ?>" 
                             onclick="location.href='?tutee_id=<?php echo $contact['user_id']; ?>'">
                            <?php echo htmlspecialchars($contact['name']); ?>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="no-data">No conversations found.</p>
                <?php endif; ?>
            </div>

            <div class="chat">
                <div class="chat-header"><?php echo htmlspecialchars($selected_tutee_name); ?></div>
                <div class="chat-body">
                    <?php if ($selected_tutee_id > 0): 
                        $msg_sql = "SELECT * FROM messages 
                                    WHERE (sender_id = $current_user_id AND receiver_id = $selected_tutee_id)
                                    OR (sender_id = $selected_tutee_id AND receiver_id = $current_user_id)
                                    ORDER BY date_sent ASC";
                        $chat_messages = $conn->query($msg_sql);
                        while($msg = $chat_messages->fetch_assoc()): ?>
                            <div class="message <?php echo ($msg['sender_id'] == $current_user_id) ? 'sent' : 'received'; ?>">
                                <?php echo htmlspecialchars($msg['message_content']); ?>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="chat-empty">Select a student to view your messages</div>
                    <?php endif; ?>
                </div>
                <?php if ($selected_tutee_id > 0): ?>
                <form actio<form action="/tutorloop/send_message.php"n="send_message.php" method="POST" class="chat-input">
                    <input type="hidden" name="receiver_id" value="<?php echo $selected_tutee_id; ?>">
                    <input type="text" name="message" placeholder="Type a reply..." required autocomplete="off">
                    <button type="submit">Send</button>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>
<script src="../Frontend/js/tutor_messages.js"></script>
</body>
</html>