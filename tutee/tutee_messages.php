<?php
session_start();
include("../config/db.php");
$conn = new mysqli("localhost", "root", "", "tutorloop_db", 3307);

if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

if (!isset($_SESSION['user_id'])) {
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

$selected_tutor_id = isset($_GET['tutor_id']) ? (int)$_GET['tutor_id'] : 0;
$selected_tutor_name = "Select a contact";

if ($selected_tutor_id > 0) {
    $conn->query("UPDATE messages 
                  SET is_read = 1 
                  WHERE sender_id = $selected_tutor_id 
                  AND receiver_id = $current_user_id 
                  AND is_read = 0");
}

if ($selected_tutor_id > 0) {
    $name_query = $conn->prepare("SELECT name FROM users WHERE user_id = ?");
    $name_query->bind_param("i", $selected_tutor_id);
    $name_query->execute();
    $name_result = $name_query->get_result();
    if($row = $name_result->fetch_assoc()) { $selected_tutor_name = $row['name']; }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages | TutorLoop</title>
    <link rel="stylesheet" href="../Frontend/css/tutee_dashboard.css">
    <link rel="stylesheet" href="../Frontend/css/tutee_messages.css">
</head>
<body>
<div class="container">
    <aside class="sidebar" id="sidebar">
        <div class="logo">
            <img src="../Frontend/images/Tutorloop_logo.png" alt="logo">
            <span>TUTORLOOP</span>
        </div>
        <nav>
            <a href="/tutorloop/tutee/tutee_dashboard.php">Dashboard</a>
            <a href="/tutorloop/tutee/tutee_profile.php">My Profile</a>
            <a href="/tutorloop/search_results.php">Find a Tutor</a>
            <a href="/tutorloop/tutee/tutee_session.php">My Sessions</a>
            <a href="/tutorloop/tutee/tutee_mytutor.php">My Tutors</a>
            <a href="/tutorloop/tutee/tutee_messages.php" class="active">Messages</a>
            <a href="/tutorloop/analytics.php">Analytics</a>
        </nav>
    </aside>

    <main class="main">
        <header class="topbar">
            <button class="menu-btn" id="menuBtn">☰</button>
            <h1>Messages</h1>
            <button class="logout" onclick="location.href='/tutorloop/logout.php'">Logout</button>
        </header>

        <div class="messages">
            <div class="contacts">
                <div class="contacts-title">Tutors</div>
                <?php if ($contacts_result && $contacts_result->num_rows > 0): ?>
                    <?php while($contact = $contacts_result->fetch_assoc()): ?>
                        <div class="contact <?php echo ($selected_tutor_id == $contact['user_id']) ? 'active' : ''; ?>" 
                             onclick="location.href='?tutor_id=<?php echo $contact['user_id']; ?>'">
                            <?php echo htmlspecialchars($contact['name']); ?>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="no-data">No tutors found.</p>
                <?php endif; ?>
            </div>

            <div class="chat">
                <div class="chat-header"><?php echo htmlspecialchars($selected_tutor_name); ?></div>
                <div class="chat-body">
                    <?php if ($selected_tutor_id > 0): 
                        $msg_sql = "SELECT * FROM messages 
                                    WHERE (sender_id = $current_user_id AND receiver_id = $selected_tutor_id)
                                    OR (sender_id = $selected_tutor_id AND receiver_id = $current_user_id)
                                    ORDER BY date_sent ASC";
                        $chat_messages = $conn->query($msg_sql);
                        while($msg = $chat_messages->fetch_assoc()): ?>
                            <div class="message <?php echo ($msg['sender_id'] == $current_user_id) ? 'sent' : 'received'; ?>">
                                <?php echo htmlspecialchars($msg['message_content']); ?>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="chat-empty">Select a tutor to view your messages</div>
                    <?php endif; ?>
                </div>
                <?php if ($selected_tutor_id > 0): ?>
                <form action="/tutorloop/send_message.php" method="POST" class="chat-input">
                    <input type="hidden" name="receiver_id" value="<?php echo $selected_tutor_id; ?>">
                    <input type="text" name="message" placeholder="Type a message..." required autocomplete="off">
                    <button type="submit">Send</button>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>
<script src="../Frontend/js/tutee_messages.js"></script>
</body>
</html>