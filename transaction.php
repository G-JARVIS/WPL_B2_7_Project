<?php
// transaction.php
session_start();
require 'db_connection.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit('Unauthorized');
}

$user_id = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

switch($method) {
    case 'GET':
        // Retrieve transactions (optionally filter by wallet_id)
        $wallet_id = isset($_GET['wallet_id']) ? intval($_GET['wallet_id']) : 0;
        if ($wallet_id) {
            $stmt = $pdo->prepare("SELECT * FROM transactions WHERE wallet_id = ?");
            $stmt->execute([$wallet_id]);
        } else {
            // You may need to join wallets table to get only user transactions
            $stmt = $pdo->prepare("SELECT t.* FROM transactions t JOIN wallets w ON t.wallet_id = w.id WHERE w.user_id = ?");
            $stmt->execute([$user_id]);
        }
        $transactions = $stmt->fetchAll();
        header('Content-Type: application/json');
        echo json_encode($transactions);
        break;
        
    case 'POST':
        // Create a new transaction
        $data = json_decode(file_get_contents("php://input"), true);
        $wallet_id = intval($data['wallet_id']);
        $type = $data['type']; // 'Income' or 'Expense'
        $amount = $data['amount'];
        $description = $data['description'];
        $transaction_date = $data['transaction_date']; // format: YYYY-MM-DD
        
        $stmt = $pdo->prepare("INSERT INTO transactions (wallet_id, type, amount, description, transaction_date) VALUES (?, ?, ?, ?, ?)");
        if ($stmt->execute([$wallet_id, $type, $amount, $description, $transaction_date])) {
            echo json_encode(["message" => "Transaction added successfully"]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Error adding transaction"]);
        }
        break;
        
    // Add PUT and DELETE methods for updating and deleting transactions as needed.
    
    default:
        http_response_code(405);
        echo json_encode(["message" => "Method Not Allowed"]);
        break;
}
?>
