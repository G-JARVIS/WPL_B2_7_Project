<?php
session_start();

// Adjust the path if your db_connection.php is elsewhere
require __DIR__ . '/../db_connection.php';

// Only logged-in users can access
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(["message" => "Unauthorized"]);
    exit();
}

$user_id = $_SESSION['user_id'];
$method  = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // If wallet_id provided, fetch only that wallet's transactions
        if (isset($_GET['wallet_id']) && is_numeric($_GET['wallet_id'])) {
            $wallet_id = intval($_GET['wallet_id']);
            $stmt = $pdo->prepare(
                "SELECT * 
                 FROM transactions 
                 WHERE wallet_id = ?"
            );
            $stmt->execute([$wallet_id]);
        } else {
            // Otherwise, fetch all transactions belonging to wallets of this user
            $stmt = $pdo->prepare(
                "SELECT t.* 
                 FROM transactions t
                 JOIN wallets w ON t.wallet_id = w.id
                 WHERE w.user_id = ?"
            );
            $stmt->execute([$user_id]);
        }
        $transactions = $stmt->fetchAll();
        header('Content-Type: application/json');
        echo json_encode($transactions);
        break;

    case 'POST':
        // Read JSON payload
        $data = json_decode(file_get_contents("php://input"), true);
        // Validate required fields
        if (
            empty($data['wallet_id'])  ||
            empty($data['type'])       ||
            !isset($data['amount'])    ||
            empty($data['transaction_date'])
        ) {
            http_response_code(400);
            echo json_encode(["message" => "Missing required fields"]);
            exit();
        }

        $wallet_id        = intval($data['wallet_id']);
        $type             = $data['type'];            // "Income" or "Expense"
        $amount           = floatval($data['amount']);
        $description      = $data['description'] ?? '';
        $transaction_date = $data['transaction_date']; // "YYYY-MM-DD"

        $stmt = $pdo->prepare(
            "INSERT INTO transactions 
             (wallet_id, type, amount, description, transaction_date) 
             VALUES (?, ?, ?, ?, ?)"
        );

        if ($stmt->execute([$wallet_id, $type, $amount, $description, $transaction_date])) {
            header('Content-Type: application/json');
            echo json_encode(["message" => "Transaction added successfully"]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Error adding transaction"]);
        }
        break;

    // You can add PUT (update) and DELETE (remove) cases here

    default:
        http_response_code(405);
        header('Allow: GET, POST');
        echo json_encode(["message" => "Method Not Allowed"]);
        break;
}
