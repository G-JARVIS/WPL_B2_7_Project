<?php
// wallet.php
session_start();
require 'db_connection.php';

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit('Unauthorized');
}

$user_id = $_SESSION['user_id'];

// Determine the request method
$method = $_SERVER['REQUEST_METHOD'];

// Basic routing:
switch($method) {
    case 'GET':
        // Retrieve all wallets for the logged in user
        $stmt = $pdo->prepare("SELECT * FROM wallets WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $wallets = $stmt->fetchAll();
        header('Content-Type: application/json');
        echo json_encode($wallets);
        break;
        
    case 'POST':
        // Create a new wallet
        $data = json_decode(file_get_contents("php://input"), true);
        $wallet_name = $data['wallet_name'];
        $balance = isset($data['balance']) ? $data['balance'] : 0;
        
        $stmt = $pdo->prepare("INSERT INTO wallets (user_id, wallet_name, balance) VALUES (?, ?, ?)");
        if ($stmt->execute([$user_id, $wallet_name, $balance])) {
            echo json_encode(["message" => "Wallet created successfully"]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Wallet creation failed"]);
        }
        break;
        
    // Add PUT and DELETE methods as needed for updating and deleting wallets
    
    default:
        http_response_code(405);
        echo json_encode(["message" => "Method Not Allowed"]);
        break;
}
?>
