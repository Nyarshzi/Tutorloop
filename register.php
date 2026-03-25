<?php
session_start();
include("config/db.php");

$message = "";
$name = "";
$email = "";
$role = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = trim($_POST['role'] ?? '');

    if ($name === "" || $email === "" || $password === "" || $role === "") {
        $message = "Please fill in all fields.";
    } else {
        $check = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $existing = $check->get_result();

        if ($existing && $existing->num_rows > 0) {
            $message = "Email is already registered.";
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $name, $email, $hashedPassword, $role);

            if ($stmt->execute()) {
                header("Location: login.php");
                exit();
            } else {
                $message = "Registration failed.";
            }

            $stmt->close();
        }

        $check->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - TutorLoop</title>
    <link rel="stylesheet" href="/tutorloop/TutorLoop/Frontend/css/register.css">
</head>
<body>
    <main class="main-container">
        <section class="register-card" id="registerCard">
            <div class="card-shine"></div>

            <img
                src="/tutorloop/TutorLoop/Frontend/images/tutorloop_logo.jpg"
                class="logo"
                alt="TutorLoop Logo"
            >

            <h2>Create Account</h2>
            <p class="subtitle">Join TutorLoop and start learning</p>

            <?php if (!empty($message)) : ?>
                <p class="message"><?php echo htmlspecialchars($message); ?></p>
            <?php endif; ?>

            <form method="POST" id="registerForm" novalidate>
                <div class="field-group input-group <?php echo $name !== '' ? 'filled' : ''; ?>">
                    <input
                        type="text"
                        name="name"
                        id="name"
                        required
                        autocomplete="name"
                        value="<?php echo htmlspecialchars($name); ?>"
                    >
                    <label for="name">Full Name</label>
                    <span class="input-line"></span>
                </div>

                <div class="field-group input-group <?php echo $email !== '' ? 'filled' : ''; ?>">
                    <input
                        type="email"
                        name="email"
                        id="email"
                        required
                        autocomplete="email"
                        value="<?php echo htmlspecialchars($email); ?>"
                    >
                    <label for="email">Email</label>
                    <span class="input-line"></span>
                </div>

                <div class="field-group input-group password-group">
                    <input
                        type="password"
                        name="password"
                        id="password"
                        required
                        autocomplete="new-password"
                    >
                    <label for="password">Password</label>
                    <span class="input-line"></span>

                    <button
                        type="button"
                        class="toggle"
                        id="togglePassword"
                        aria-label="Show password"
                    >
                        Show
                    </button>
                </div>

                <div class="field-group input-group custom-select-group <?php echo $role !== '' ? 'filled' : ''; ?>" id="roleGroup">
                    <input type="hidden" name="role" id="role" value="<?php echo htmlspecialchars($role); ?>" required>

                    <button
                        type="button"
                        class="custom-select-trigger"
                        id="roleTrigger"
                        aria-haspopup="listbox"
                        aria-expanded="false"
                    >
                        <span id="roleText"><?php echo $role === 'tutor' ? 'Tutor' : ($role === 'tutee' ? 'Tutee' : ''); ?></span>
                    </button>

                    <label for="role">Role</label>
                    <span class="input-line"></span>

                    <div class="custom-options" id="roleOptions" role="listbox">
                        <div class="custom-option <?php echo $role === 'tutor' ? 'selected' : ''; ?>" data-value="tutor" role="option">
                            Tutor
                        </div>
                        <div class="custom-option <?php echo $role === 'tutee' ? 'selected' : ''; ?>" data-value="tutee" role="option">
                            Tutee
                        </div>
                    </div>
                </div>

                <button type="submit" class="register-btn" id="registerBtn">
                    <span class="btn-text">Create Account</span>
                    <span class="loader" aria-hidden="true"></span>
                </button>

                <p class="login-link">
                    Already have an account? <a href="login.php">Sign In</a>
                </p>
            </form>
        </section>
    </main>

    <script src="/tutorloop/TutorLoop/Frontend/js/register.js"></script>
</body>
</html>