<?php
session_start();
require '../db_connection.php';
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit('Unauthorized');
}
$user_id = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $stmt = $pdo->prepare(
            'SELECT * FROM wallets WHERE user_id = ?'
        );
        $stmt->execute([$user_id]);
        echo json_encode($stmt->fetchAll());
        break;

    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $pdo->prepare(
            'INSERT INTO wallets (user_id, wallet_name, balance) VALUES (?, ?, ?)'
        );
        $stmt->execute([
            $user_id,
            $data['wallet_name'],
            $data['balance'] ?? 0
        ]);
        echo json_encode(['success' => true]);
        break;

    // PUT and DELETE can be added similarly

    default:
        http_response_code(405);
        exit('Method Not Allowed');
}