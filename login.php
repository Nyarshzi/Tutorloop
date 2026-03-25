<?php
session_start();
include("config/db.php");

$message = "";
$showLogin = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $showLogin = true;

    if (!empty($email) && !empty($password)) {
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");

        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result && $result->num_rows === 1) {
                $user = $result->fetch_assoc();

                if (password_verify($password, $user['password'])) {
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['name'] = $user['name'];

    if ($user['role'] == 'tutor') {
        header("Location: tutor_dashboard.php");
        exit();
    } elseif ($user['role'] == 'tutee') {
        header("Location: tutee_dashboard.php");
        exit();
    }
}
                } else {
                    $message = "Incorrect password.";
                }
            } else {
                $message = "Email not found.";
            }

            $stmt->close();
        } else {
            $message = "Database error.";
        }
    } else {
        $message = "Please fill in all fields.";
    }

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - TutorLoop</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="Frontend/css/style.css">
</head>
<body>

<div class="header">
    <img src="Frontend/images/tutorloop_logo.jpg" alt="TutorLoop Logo" class="logo">
    <h1 class="title">Lamp Login</h1>
</div>

<div class="container <?php echo $showLogin ? 'lamp-active' : ''; ?>">
    <div class="lamp">
        <div class="shade"></div>
        <div class="stand"></div>
        <div class="base"></div>
        <div class="light-beam <?php echo $showLogin ? 'beam-on' : ''; ?>" id="lightBeam"></div>
        <div class="string" onclick="toggleLamp()"></div>
    </div>

    <div class="login-box <?php echo $showLogin ? 'glow' : 'hidden'; ?>" id="loginBox">
        <h2>Welcome</h2>

        <form method="POST" action="">
            <input
                type="email"
                name="email"
                id="email"
                placeholder="Email"
                required
                value="<?php echo htmlspecialchars($email ?? ''); ?>"
            >

            <div class="password-container">
                <input
                    type="password"
                    name="password"
                    id="password"
                    placeholder="Password"
                    required
                >
                <span class="toggle-pass" onclick="togglePassword(event)">Show</span>
            </div>

            <button type="submit" id="loginBtn">Sign In</button>
        </form>

        <?php if (!empty($message)) : ?>
            <p id="message" class="warning-message"><?php echo $message; ?></p>
        <?php endif; ?>
    </div>
</div>

<script src="Frontend/js/script.js"></script>
</body>
</html>