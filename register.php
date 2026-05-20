<?php
// 1. Error Reporting & Sessions
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// 2. Database Connection
include("config/db.php");

$message = "";
$name = "";
$email = "";
$student_id = "";

// 3. Handle Registration POST
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $student_id = trim($_POST['student_id'] ?? ''); // student_id included from previous logic
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = $_POST['role'] ?? ''; // hidden input from JS
    $allowed_domain = "@students.isatu.edu.ph";

    if ($name !== '' && $email !== '' && $password !== '' && $role !== '') {
        // --- PASSWORD MATCH VALIDATION ---
        if ($password !== $confirm_password) {
            $message = "Passwords do not match.";
        } elseif (substr($email, -strlen($allowed_domain)) !== $allowed_domain) {
            // --- DOMAIN GATEKEEPER ---
            $message = "Only ISAT U student emails are allowed.";
        } else {
            // Check if email already exists
            $check = $conn->prepare("SELECT email FROM users WHERE email = ?");
            $check->bind_param("s", $email);
            $check->execute();
            if ($check->get_result()->num_rows > 0) {
                $message = "This email is already registered.";
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // --- AUTO-VERIFICATION ---
                // Inserting with student_id, default profile pic and is_verified = 1
                $stmt = $conn->prepare("INSERT INTO users (name, email, password, role, student_id, profile_pic, is_verified) VALUES (?, ?, ?, ?, ?, 'default.png', 1)");
                $stmt->bind_param("sssss", $name, $email, $hashed_password, $role, $student_id);

                if ($stmt->execute()) {
                    header("Location: login.php?registered=success");
                    exit();
                } else { $message = "Error: " . $conn->error; }
            }
        }
    } else { $message = "Please fill in all fields."; }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - TutorLoop</title>
    <link rel="stylesheet" href="Frontend/css/register.css">
</head>
<body class="light-on"> <div class="overlay"></div>
    <div class="container">
        <div class="card">
            <h2 style="text-align: left; margin-top: 0;">Create Account</h2>
            <p class="subtitle">Join TutorLoop and start learning</p>

            <form method="POST" action="register.php" id="registerForm">
                <input type="text" name="name" placeholder="Full Name" required value="<?php echo htmlspecialchars($name); ?>">
                
                <input type="text" name="student_id" placeholder="Student ID (e.g. 2024-1234-A)" value="<?php echo htmlspecialchars($student_id); ?>">
                
                <input type="email" name="email" placeholder="Email (@students.isatu.edu.ph)" required 
                       pattern=".+@students\.isatu\.edu\.ph" title="Use your school email address."
                       value="<?php echo htmlspecialchars($email); ?>">

                <div class="password-box">
                    <input type="password" name="password" id="password" placeholder="Password" required>
                    <button type="button" id="togglePassword">Show</button>
                </div>

                <div class="password-box">
                    <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm Password" required>
                    <button type="button" id="toggleConfirmPassword">Show</button>
                </div>

                <div class="dropdown" id="roleGroup">
                    <button type="button" id="roleBtn">Select Role</button>
                    <input type="hidden" name="role" id="role" required>
                    <div class="dropdown-content">
                        <div data-value="tutee">Student (Tutee)</div>
                        <div data-value="tutor">Tutor</div>
                    </div>
                </div>

                <button type="submit" id="submitBtn">
                    <span>Create Account</span>
                    <div class="loader"></div>
                </button>
            </form>

            <div class="login-link">Already have an account? <a href="login.php">Sign In</a></div>
            
            <?php if ($message): ?>
                <p class="warning-message"><?php echo $message; ?></p>
            <?php endif; ?>
        </div>
    </div>
    <script src="Frontend/js/register.js"></script>
</body>
</html>