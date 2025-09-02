<?php
session_start();
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $sql = "SELECT id, password FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->bind_result($id, $hashed_password);
    if ($stmt->fetch()) {
        if (password_verify($password, $hashed_password)) {
            $_SESSION['user_id'] = $id;
            $_SESSION['username'] = $username;
            header("Location: profile.php");
            exit();
        } else {
            echo "<p style='font-size: 32px; font-weight: bold;'>Incorrect password.</p>";
            // echo "Incorrect password.";
        }
    } else {
        echo "<p style='font-size: 32px; font-weight: bold;'>User not found.</p>";
        // echo "User not found.";
    }
}
?>
