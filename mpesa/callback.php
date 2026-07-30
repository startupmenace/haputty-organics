<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/mpesa.php';

// M-Pesa sends JSON in the request body
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

// Log callback for debugging
$log = date('Y-m-d H:i:s') . ' | ' . $raw . PHP_EOL;
@file_put_contents(__DIR__ . '/callback.log', $log, FILE_APPEND);

$checkoutRequestId = $data['Body']['stkCallback']['CheckoutRequestID'] ?? '';
$resultCode = $data['Body']['stkCallback']['ResultCode'] ?? 1;
$resultDesc = $data['Body']['stkCallback']['ResultDesc'] ?? '';
$amount = $data['Body']['stkCallback']['CallbackMetadata']['Item'][0]['Value'] ?? 0;
$mpesaReceipt = $data['Body']['stkCallback']['CallbackMetadata']['Item'][1]['Value'] ?? '';
$phone = $data['Body']['stkCallback']['CallbackMetadata']['Item'][4]['Value'] ?? '';

if (!$checkoutRequestId) {
    http_response_code(400);
    exit;
}

// Find order by checkout request ID
$stmt = $pdo->prepare("SELECT * FROM orders WHERE mpesa_checkout_id = ?");
$stmt->execute([$checkoutRequestId]);
$order = $stmt->fetch();

if (!$order) {
    http_response_code(404);
    exit;
}

if ($resultCode === 0) {
    // Payment successful
    $pdo->prepare("UPDATE orders SET status = 'confirmed', mpesa_receipt = ?, mpesa_phone = ?, mpesa_result = ? WHERE id = ?")
        ->execute([$mpesaReceipt, $phone, $resultDesc, $order['id']]);
} else {
    // Payment failed
    $pdo->prepare("UPDATE orders SET mpesa_result = ? WHERE id = ?")
        ->execute([$resultDesc, $order['id']]);
}

http_response_code(200);
echo json_encode(['ResultCode' => 0, 'ResultDesc' => 'Success']);
