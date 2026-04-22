<?php
// 1. Error Reporting & Sessions
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// 2. Database Connection
include("config/db.php");

$message = "";
$email = "";

// 3. Redirect if already logged in
if (isset($_SESSION['user_id']) && !empty($_SESSION['role'])) {
    $target = ($_SESSION['role'] === 'tutor') ? 'tutor_dashboard.php' : 'tutee_dashboard.php';
    header("Location: $target");
    exit();
}

// 4. Handle Login POST
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $allowed_domain = "@students.isatu.edu.ph";

    if ($email !== '' && $password !== '') {
        if (substr($email, -strlen($allowed_domain)) !== $allowed_domain) {
            $message = "Access denied. Use @students.isatu.edu.ph email.";
        } else {
            $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
            if ($stmt) {
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result && $result->num_rows === 1) {
                    $user = $result->fetch_assoc();
                    if (password_verify($password, $user['password'])) {
                        
                        // --- VERIFICATION CHECK ---
                        if (isset($user['is_verified']) && (int)$user['is_verified'] === 0) {
                            $message = "Your account is deactivated/unverified.";
                        } else {
                            session_regenerate_id(true);
                            $_SESSION['user_id'] = $user['user_id'];
                            $_SESSION['role'] = $user['role'];
                            $_SESSION['name'] = $user['name'];
                            header("Location: " . ($user['role'] === 'tutor' ? 'tutor_dashboard.php' : 'tutee_dashboard.php'));
                            exit();
                        }
                    } else { $message = "Incorrect password."; }
                } else { $message = "Email not found."; }
                $stmt->close();
            }
        } 
    }
}

// UI State Logic
$showActive = (!empty($message) || isset($_GET['registered'])) ? 'active' : '';
$lightOn = (!empty($message) || isset($_GET['registered'])) ? 'light-on' : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - TutorLoop</title>
    <link rel="stylesheet" href="Frontend/css/style.css">
</head>
<body class="<?php echo $lightOn; ?>">
    <div class="overlay"></div>
    <div class="main">
        <div class="lamp-wrapper" id="lampWrapper">
            <div class="lamp-glow"></div>
            <div class="lamp">
                <div class="shade"></div>
                <div class="string" onclick="toggleLight()"><span class="knob"></span></div>
                <div class="stand"></div>
            </div>
        </div>

        <div class="login-box <?php echo $showActive; ?>" id="loginBox">
            <img src="Frontend/images/Tutorloop_logo.png" alt="Logo" class="logo">
            <h2>Welcome</h2>
            
            <?php if (isset($_GET['registered'])) : ?>
                <p style="color: var(--mustard); text-align: center; margin-bottom: 10px; font-weight: bold; font-size: 13px;">
                    Account created! You can now Sign In.
                </p>
            <?php endif; ?>

            <form method="POST" action="login.php">
                <input type="email" name="email" placeholder="Email" required pattern=".+@students\.isatu\.edu\.ph" value="<?php echo htmlspecialchars($email); ?>">
                <div class="password-container">
                    <input type="password" name="password" id="password" placeholder="Password" required>
                    <span class="show-text" onclick="togglePassword()">Show</span>
                </div>
                <button type="submit">Sign In</button>
            </form>
            <div class="register-link">Don't have an account? <a href="register.php">Register</a></div>
            <?php if ($message): ?><p class="warning-message" style="color: #ff4d4d; text-align: center; margin-top: 15px; font-weight: bold;"><?php echo $message; ?></p><?php endif; ?>
        </div>
    </div>
    <script src="Frontend/js/login.js"></script>
</body>
</html>