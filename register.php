<?php
session_start();
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $sql = "SELECT id FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        echo "<p style='font-size: 32px; font-weight: bold;'>Username already taken!</p>";
        exit();
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $insert_sql = "INSERT INTO users (username, password) VALUES (?, ?)";
    $stmt_insert = $conn->prepare($insert_sql);
    $stmt_insert->bind_param("ss", $username, $hashed_password);

    if ($stmt_insert->execute()) {
        echo "<p style='font-size: 32px; font-weight: bold;'>Registration successful. <a href='login.html'>Login here</a></p>";
        // echo "Registration successful. <a href='login.html'>Login here</a>";
    } else {
        echo "<p style='font-size: 32px; font-weight: bold;'>Error occurred. Try again.</p>";
    }
    exit();
}
?>


