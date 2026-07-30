<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/mpesa.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$orderId = (int)($_POST['order_id'] ?? 0);
$phone = trim($_POST['phone'] ?? '');
$amount = (float)($_POST['amount'] ?? 0);

if (!$orderId || !$phone || $amount <= 0) {
    echo json_encode(['success' => false, 'message' => 'Missing order details']);
    exit;
}

// Verify order exists and belongs to user
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->execute([$orderId]);
$order = $stmt->fetch();

if (!$order) {
    echo json_encode(['success' => false, 'message' => 'Order not found']);
    exit;
}

// Check ownership
if ($order['user_id'] && (!isset($_SESSION['user_id']) || $order['user_id'] != $_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Initiate STK Push
$result = mpesaStkPush($phone, $amount, $order['order_ref']);

if ($result['success']) {
    // Store checkout request ID for callback matching
    $pdo->prepare("UPDATE orders SET mpesa_checkout_id = ?, mpesa_merchant_id = ? WHERE id = ?")
        ->execute([$result['checkout_request_id'], $result['merchant_request_id'], $orderId]);
}

echo json_encode($result);
