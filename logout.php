<?php
session_start();       // Start the session
session_unset();       // Unset all session variables
session_destroy();     // Destroy the session

// Optionally, clear any related cookies
if (isset($_COOKIE['user_email'])) {
    setcookie('user_email', '', time() - 3600, "/");
}

// Redirect to the login page
header("Location: login.html");
exit();
?>
