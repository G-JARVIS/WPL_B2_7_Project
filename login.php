<?php
// login.php
session_start();
require 'db_connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Get user by email
    $stmt = $pdo->prepare("SELECT id, password FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        // Set session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['email'] = $email;
        header("Location: home.html");
        exit();
    } else {
        header("Location: login.html?error=Invalid credentials");
        exit();
    }
} else {
    header("Location: login.html");
    exit();
}
?>
