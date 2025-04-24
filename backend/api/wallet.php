<?php
session_start();
header('Content-Type: application/json');
require '../db_connection.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["message" => "Unauthorized"]);
    exit();
}

$user_id = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // If specific wallet ID is provided
        if (isset($_GET['id']) && is_numeric($_GET['id'])) {
            $wallet_id = intval($_GET['id']);
            $stmt = $pdo->prepare(
                'SELECT * FROM wallets WHERE id = ? AND user_id = ?'
            );
            $stmt->execute([$wallet_id, $user_id]);
            $wallet = $stmt->fetch();
            
            if (!$wallet) {
                http_response_code(404);
                echo json_encode(["message" => "Wallet not found"]);
                exit();
            }
            
            echo json_encode($wallet);
        } else {
            // Get all wallets for this user
            $stmt = $pdo->prepare(
                'SELECT * FROM wallets WHERE user_id = ?'
            );
            $stmt->execute([$user_id]);
            echo json_encode($stmt->fetchAll());
        }
        break;

    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validate required fields
        if (empty($data['wallet_name'])) {
            http_response_code(400);
            echo json_encode(["message" => "Wallet name is required"]);
            exit();
        }
        
        $initial_balance = isset($data['balance']) ? floatval($data['balance']) : 0;
        
        $stmt = $pdo->prepare(
            'INSERT INTO wallets (user_id, wallet_name, balance) VALUES (?, ?, ?)'
        );
        
        if ($stmt->execute([$user_id, $data['wallet_name'], $initial_balance])) {
            $wallet_id = $pdo->lastInsertId();
            echo json_encode([
                "success" => true,
                "message" => "Wallet created successfully",
                "wallet_id" => $wallet_id
            ]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Error creating wallet"]);
        }
        break;

    case 'PUT':
        if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
            http_response_code(400);
            echo json_encode(["message" => "Wallet ID is required"]);
            exit();
        }
        
        $wallet_id = intval($_GET['id']);
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Check if wallet exists and belongs to user
        $stmt = $pdo->prepare('SELECT * FROM wallets WHERE id = ? AND user_id = ?');
        $stmt->execute([$wallet_id, $user_id]);
        if (!$stmt->fetch()) {
            http_response_code(404);
            echo json_encode(["message" => "Wallet not found or access denied"]);
            exit();
        }
        
        // Update wallet name if provided
        if (!empty($data['wallet_name'])) {
            $stmt = $pdo->prepare('UPDATE wallets SET wallet_name = ? WHERE id = ?');
            $stmt->execute([$data['wallet_name'], $wallet_id]);
        }
        
        // Manual balance adjustment if provided (for direct deposits/withdrawals)
        if (isset($data['balance_adjustment'])) {
            $adjustment = floatval($data['balance_adjustment']);
            $stmt = $pdo->prepare('UPDATE wallets SET balance = balance + ? WHERE id = ?');
            if ($stmt->execute([$adjustment, $wallet_id])) {
                echo json_encode([
                    "success" => true,
                    "message" => "Wallet updated successfully",
                    "balance_adjustment" => $adjustment
                ]);
            } else {
                http_response_code(500);
                echo json_encode(["message" => "Error updating wallet balance"]);
            }
        } else {
            echo json_encode([
                "success" => true,
                "message" => "Wallet updated successfully"
            ]);
        }
        break;

    case 'DELETE':
        if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
            http_response_code(400);
            echo json_encode(["message" => "Wallet ID is required"]);
            exit();
        }
        
        $wallet_id = intval($_GET['id']);
        
        // First check if there are any transactions for this wallet
        $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM transactions WHERE wallet_id = ?');
        $stmt->execute([$wallet_id]);
        $result = $stmt->fetch();
        
        if ($result['count'] > 0) {
            http_response_code(400);
            echo json_encode([
                "message" => "Cannot delete wallet with existing transactions. Delete all transactions first."
            ]);
            exit();
        }
        
        // Check if wallet exists and belongs to user
        $stmt = $pdo->prepare('SELECT * FROM wallets WHERE id = ? AND user_id = ?');
        $stmt->execute([$wallet_id, $user_id]);
        if (!$stmt->fetch()) {
            http_response_code(404);
            echo json_encode(["message" => "Wallet not found or access denied"]);
            exit();
        }
        
        // Delete wallet
        $stmt = $pdo->prepare('DELETE FROM wallets WHERE id = ?');
        if ($stmt->execute([$wallet_id])) {
            echo json_encode([
                "success" => true,
                "message" => "Wallet deleted successfully"
            ]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Error deleting wallet"]);
        }
        break;

    default:
        http_response_code(405);
        header('Allow: GET, POST, PUT, DELETE');
        echo json_encode(["message" => "Method Not Allowed"]);
        break;
}