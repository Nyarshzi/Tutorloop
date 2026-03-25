<?php
session_start();
include("config/db.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = "";

// Get all other users
$users = mysqli_query($conn, "SELECT user_id, full_name, role FROM users WHERE user_id != '$user_id'");

$receiver_id = "";
$chat_result = null;

if (isset($_GET['receiver_id'])) {
    $receiver_id = $_GET['receiver_id'];

    $chat_sql = "SELECT * FROM messages 
                 WHERE (sender_id='$user_id' AND receiver_id='$receiver_id')
                 OR (sender_id='$receiver_id' AND receiver_id='$user_id')
                 ORDER BY date_time_sent ASC";

    $chat_result = mysqli_query($conn, $chat_sql);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $receiver_id = $_POST['receiver_id'];
    $message_content = $_POST['message_content'];

    $sql = "INSERT INTO messages (sender_id, receiver_id, message_content)
            VALUES ('$user_id', '$receiver_id', '$message_content')";

    if (mysqli_query($conn, $sql)) {
        header("Location: messages.php?receiver_id=" . $receiver_id);
        exit();
    } else {
        $message = "Error: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Messages - TutorLoop</title>
</head>
<body>

<h2>Messages</h2>

<form method="GET" action="">
    <label>Select User:</label><br>
    <select name="receiver_id" required>
        <option value="">Choose User</option>
        <?php while ($row = mysqli_fetch_assoc($users)) { ?>
            <option value="<?php echo $row['user_id']; ?>" <?php if ($receiver_id == $row['user_id']) echo "selected"; ?>>
                <?php echo $row['full_name'] . " (" . $row['role'] . ")"; ?>
            </option>
        <?php } ?>
    </select>
    <button type="submit">Open Chat</button>
</form>

<br>

<?php if ($chat_result) { ?>
    <h3>Conversation</h3>
    <div style="border:1px solid #000; padding:10px; width:500px; min-height:200px;">
        <?php
        while ($chat = mysqli_fetch_assoc($chat_result)) {
            if ($chat['sender_id'] == $user_id) {
                echo "<p><strong>You:</strong> " . $chat['message_content'] . "</p>";
            } else {
                echo "<p><strong>Them:</strong> " . $chat['message_content'] . "</p>";
            }
        }
        ?>
    </div>

    <br>

    <form method="POST" action="">
        <input type="hidden" name="receiver_id" value="<?php echo $receiver_id; ?>">

        <label>Type Message:</label><br>
        <textarea name="message_content" required></textarea><br><br>

        <button type="submit">Send</button>
    </form>
<?php } ?>

<?php if ($message != "") echo "<p>$message</p>"; ?>

</body>
</html>