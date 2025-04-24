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
                 WHERE wallet_id = ?
                 ORDER BY transaction_date DESC"
            );
            $stmt->execute([$wallet_id]);
        } else {
            // Otherwise, fetch all transactions belonging to wallets of this user
            $stmt = $pdo->prepare(
                "SELECT t.* 
                 FROM transactions t
                 JOIN wallets w ON t.wallet_id = w.id
                 WHERE w.user_id = ?
                 ORDER BY t.transaction_date DESC"
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

        // Start transaction to ensure data consistency
        $pdo->beginTransaction();
        
        try {
            // Insert the transaction
            $stmt = $pdo->prepare(
                "INSERT INTO transactions 
                 (wallet_id, type, amount, description, transaction_date) 
                 VALUES (?, ?, ?, ?, ?)"
            );
            $stmt->execute([$wallet_id, $type, $amount, $description, $transaction_date]);
            
            // Update wallet balance
            // For Income: add to balance, For Expense: subtract from balance
            $balanceChange = ($type === 'Income') ? $amount : -$amount;
            
            $stmt = $pdo->prepare(
                "UPDATE wallets 
                 SET balance = balance + ? 
                 WHERE id = ?"
            );
            $stmt->execute([$balanceChange, $wallet_id]);
            
            // Commit the transaction
            $pdo->commit();
            
            header('Content-Type: application/json');
            echo json_encode([
                "message" => "Transaction added successfully",
                "transaction_type" => $type,
                "amount" => $amount,
                "balance_change" => $balanceChange
            ]);
        } catch (Exception $e) {
            // Roll back the transaction if something failed
            $pdo->rollBack();
            http_response_code(500);
            echo json_encode(["message" => "Error adding transaction: " . $e->getMessage()]);
        }
        break;

    case 'DELETE':
        if (isset($_GET['id']) && is_numeric($_GET['id'])) {
            $transaction_id = intval($_GET['id']);
            
            // Start transaction
            $pdo->beginTransaction();
            
            try {
                // First get the transaction details to know how to adjust the wallet balance
                $stmt = $pdo->prepare(
                    "SELECT wallet_id, type, amount 
                     FROM transactions 
                     WHERE id = ?"
                );
                $stmt->execute([$transaction_id]);
                $transaction = $stmt->fetch();
                
                if (!$transaction) {
                    http_response_code(404);
                    echo json_encode(["message" => "Transaction not found"]);
                    exit();
                }
                
                // Calculate the reverse balance change to undo this transaction
                // If it was Income, we subtract; if it was Expense, we add back
                $balanceChange = ($transaction['type'] === 'Income') ? -$transaction['amount'] : $transaction['amount'];
                
                // Update the wallet balance
                $stmt = $pdo->prepare(
                    "UPDATE wallets 
                     SET balance = balance + ? 
                     WHERE id = ?"
                );
                $stmt->execute([$balanceChange, $transaction['wallet_id']]);
                
                // Delete the transaction
                $stmt = $pdo->prepare("DELETE FROM transactions WHERE id = ?");
                $stmt->execute([$transaction_id]);
                
                // Commit the transaction
                $pdo->commit();
                
                header('Content-Type: application/json');
                echo json_encode(["message" => "Transaction deleted successfully"]);
            } catch (Exception $e) {
                // Roll back the transaction if something failed
                $pdo->rollBack();
                http_response_code(500);
                echo json_encode(["message" => "Error deleting transaction: " . $e->getMessage()]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["message" => "Missing transaction ID"]);
        }
        break;

    default:
        http_response_code(405);
        header('Allow: GET, POST, DELETE');
        echo json_encode(["message" => "Method Not Allowed"]);
        break;
}