<?php
// db_config.php - Database connection configuration
$host = 'localhost';
$dbname = 'finance_manager';
$username = 'root';  // Change to your database username
$password = '';      // Change to your database password

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// save_transactions.php - Save transactions to database
<?php
require_once 'db_config.php';
header('Content-Type: application/json');

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get JSON data
$jsonData = file_get_contents('php://input');
$transactions = json_decode($jsonData, true);

if ($transactions === null) {
    echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
    exit;
}

try {
    // Begin transaction
    $pdo->beginTransaction();
    
    // Clear existing transactions (optional - depends on your app's logic)
    $stmt = $pdo->prepare("DELETE FROM transactions");
    $stmt->execute();
    
    // Insert new transactions
    $stmt = $pdo->prepare("
        INSERT INTO transactions 
        (id, description, amount, type, category, date) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    foreach ($transactions as $transaction) {
        $stmt->execute([
            $transaction['id'],
            $transaction['description'],
            $transaction['amount'],
            $transaction['type'],
            $transaction['category'],
            $transaction['date']
        ]);
    }
    
    // Commit transaction
    $pdo->commit();
    
    echo json_encode(['success' => true, 'message' => 'Transactions saved successfully']);
} catch (PDOException $e) {
    // Rollback transaction on error
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

// get_transactions.php - Retrieve transactions from database
<?php
require_once 'db_config.php';
header('Content-Type: application/json');

try {
    $stmt = $pdo->query("SELECT * FROM transactions ORDER BY date DESC");
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'transactions' => $transactions]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

// SQL Database Schema
/*
CREATE DATABASE finance_manager;
USE finance_manager;

CREATE TABLE transactions (
    id BIGINT PRIMARY KEY,
    description VARCHAR(255) NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    type ENUM('income', 'expense', 'salary') NOT NULL,
    category VARCHAR(50) NOT NULL,
    date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_transactions_date ON transactions(date);
CREATE INDEX idx_transactions_type ON transactions(type);
CREATE INDEX idx_transactions_category ON transactions(category);
*/