<?php
session_start();
require '../db_connection.php';

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    !empty($_POST['email']) &&
    !empty($_POST['password']) &&
    !empty($_POST['full_name'])
) {
    $email = $_POST['email'];
    $passwordHash = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $name = $_POST['full_name'];

    $stmt = $pdo->prepare(
        "INSERT INTO users (full_name, email, password) VALUES (?, ?, ?)"
    );
    if ($stmt->execute([$name, $email, $passwordHash])) {
        header('Location: ../index.html?registered=1');
        exit();
    }
}
header('Location: ../register.html?error=register');
exit();