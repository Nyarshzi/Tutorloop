<?php

if ($_SERVER['SERVER_NAME'] == 'localhost' || $_SERVER['SERVER_NAME'] == '127.0.0.1') {
    // LOCAL (XAMPP)
    $conn = new mysqli("localhost", "root", "", "tutorloop_db");
} else {
    // ONLINE (InfinityFree)
    $conn = new mysqli(
        "sql100.infinityfree.com",
        "if0_41471930",
        "appdev99",
        "if0_41471930_tutorloop_db"
    );
}

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

?>