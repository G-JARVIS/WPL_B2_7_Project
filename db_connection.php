<?php
// db_connection.php
$host = 'localhost';
$db   = 'pfm_db';          // Your database name
$user = 'root';            // Default XAMPP username (modify if needed)
$pass = '';                // Default XAMPP password (modify if needed)
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // throw exceptions
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // return associative arrays
    PDO::ATTR_EMULATE_PREPARES   => false,                  // use native prepared statements
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    error_log("Database connection error: " . $e->getMessage());
    exit('Database connection error.');
}
?>
