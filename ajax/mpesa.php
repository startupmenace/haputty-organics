<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/mpesa.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

$debugLog = __DIR__ . '/mpesa_debug.log';
$logEntry = '[' . date('Y-m-d H:i:s') . '] ';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Invalid request']);
        exit;
    }

    $action = $_POST['action'] ?? 'pay';
    $orderId = (int)($_POST['order_id'] ?? 0);

    $logEntry .= "action=$action order_id=$orderId POST=" . json_encode($_POST) . ' ';

    if (!$orderId) {
        echo json_encode(['success' => false, 'message' => 'Missing order ID']);
        file_put_contents($debugLog, $logEntry . "RESPONSE: Missing order ID\n", FILE_APPEND);
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();

    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Order not found']);
        file_put_contents($debugLog, $logEntry . "RESPONSE: Order not found\n", FILE_APPEND);
        exit;
    }

    if ($order['user_id'] && (!isset($_SESSION['user_id']) || $order['user_id'] != $_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        file_put_contents($debugLog, $logEntry . "RESPONSE: Unauthorized for order {$order['id']}\n", FILE_APPEND);
        exit;
    }

    $logEntry .= "order_ref={$order['order_ref']} status={$order['status']} ";
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
    file_put_contents($debugLog, $logEntry . 'EXCEPTION: ' . $e->getMessage() . "\n", FILE_APPEND);
    exit;
}

if ($action === 'pay') {
    $phone = trim($_POST['phone'] ?? '');
    $amount = (float)($_POST['amount'] ?? 0);

    if (!$phone || $amount <= 0) {
        echo json_encode(['success' => false, 'message' => 'Missing payment details']);
        file_put_contents($debugLog, $logEntry . "RESPONSE: Missing payment details phone=$phone amount=$amount\n", FILE_APPEND);
        exit;
    }

    $result = mpesaStkPush($phone, $amount, $order['order_ref']);
    $logEntry .= "STK_RESULT=" . json_encode($result) . ' ';

    if ($result['success']) {
        $pdo->prepare("UPDATE orders SET mpesa_checkout_id = ?, mpesa_merchant_id = ? WHERE id = ?")
            ->execute([$result['checkout_request_id'], $result['merchant_request_id'], $orderId]);
    }

    echo json_encode($result);
    file_put_contents($debugLog, $logEntry . "RESPONSE: " . json_encode($result) . "\n", FILE_APPEND);

} elseif ($action === 'query') {
    if (!$order['mpesa_checkout_id']) {
        echo json_encode(['success' => true, 'paid' => false, 'message' => 'No pending payment']);
        file_put_contents($debugLog, $logEntry . "RESPONSE: No checkout ID\n", FILE_APPEND);
        exit;
    }

    if (in_array($order['status'], ['confirmed', 'processing', 'shipped', 'delivered'])) {
        echo json_encode(['success' => true, 'paid' => true, 'message' => 'Already confirmed']);
        file_put_contents($debugLog, $logEntry . "RESPONSE: Already confirmed\n", FILE_APPEND);
        exit;
    }

    $result = mpesaQuery($order['mpesa_checkout_id']);
    $logEntry .= "QUERY_RESULT=" . json_encode($result) . ' ';

    if ($result['success'] && $result['paid']) {
        $pdo->prepare("UPDATE orders SET status = 'confirmed' WHERE id = ? AND status = 'pending'")
            ->execute([$orderId]);
    }

    echo json_encode($result);
    file_put_contents($debugLog, $logEntry . "RESPONSE: " . json_encode($result) . "\n", FILE_APPEND);

} else {
    echo json_encode(['success' => false, 'message' => 'Unknown action']);
    file_put_contents($debugLog, $logEntry . "RESPONSE: Unknown action\n", FILE_APPEND);
}
