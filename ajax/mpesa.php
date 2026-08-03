<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/mpesa.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$action = $_POST['action'] ?? 'pay';
$orderId = (int)($_POST['order_id'] ?? 0);

if (!$orderId) {
    echo json_encode(['success' => false, 'message' => 'Missing order ID']);
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->execute([$orderId]);
$order = $stmt->fetch();

if (!$order) {
    echo json_encode(['success' => false, 'message' => 'Order not found']);
    exit;
}

if ($order['user_id'] && (!isset($_SESSION['user_id']) || $order['user_id'] != $_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($action === 'pay') {
    $phone = trim($_POST['phone'] ?? '');
    $amount = (float)($_POST['amount'] ?? 0);

    if (!$phone || $amount <= 0) {
        echo json_encode(['success' => false, 'message' => 'Missing payment details']);
        exit;
    }

    $result = mpesaStkPush($phone, $amount, $order['order_ref']);

    if ($result['success']) {
        $pdo->prepare("UPDATE orders SET mpesa_checkout_id = ? WHERE id = ?")
            ->execute([$result['transaction_id'], $orderId]);
    }

    echo json_encode($result);

} elseif ($action === 'query') {
    if (!$order['mpesa_checkout_id']) {
        echo json_encode(['success' => true, 'paid' => false, 'message' => 'No pending payment']);
        exit;
    }

    if (in_array($order['status'], ['confirmed', 'processing', 'shipped', 'delivered'])) {
        echo json_encode(['success' => true, 'paid' => true, 'message' => 'Already confirmed']);
        exit;
    }

    $result = mpesaQuery($order['mpesa_checkout_id']);

    if ($result['success'] && $result['paid']) {
        $pdo->prepare("UPDATE orders SET status = 'confirmed' WHERE id = ? AND status = 'pending'")
            ->execute([$orderId]);
    }

    echo json_encode($result);

} else {
    echo json_encode(['success' => false, 'message' => 'Unknown action']);
}
