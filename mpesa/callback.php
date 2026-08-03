<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/mpesa.php';
require_once __DIR__ . '/../includes/functions.php';

// Ensure columns exist
try { $pdo->exec("ALTER TABLE orders ADD COLUMN mpesa_checkout_id VARCHAR(100) DEFAULT NULL"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE orders ADD COLUMN mpesa_merchant_id VARCHAR(100) DEFAULT NULL"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE orders ADD COLUMN mpesa_receipt VARCHAR(100) DEFAULT NULL"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE orders ADD COLUMN mpesa_phone VARCHAR(50) DEFAULT NULL"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE orders ADD COLUMN mpesa_result TEXT DEFAULT NULL"); } catch (Exception $e) {}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

// Log raw callback
$log = '[' . date('Y-m-d H:i:s') . '] RAW: ' . $raw . PHP_EOL;
@file_put_contents(__DIR__ . '/logs.txt', $log, FILE_APPEND);

// PalPluss payload: {event, event_type, transaction:{id,status,amount,currency,phone_number,result_code,result_desc}}
$trans = $data['transaction'] ?? ($data['data'] ?? null);
if (!$trans) {
    @file_put_contents(__DIR__ . '/logs.txt', '[' . date('Y-m-d H:i:s') . '] ERROR: No transaction in payload' . PHP_EOL, FILE_APPEND);
    http_response_code(400);
    exit;
}

$transactionId = $trans['id'] ?? '';
if (!$transactionId) {
    @file_put_contents(__DIR__ . '/logs.txt', '[' . date('Y-m-d H:i:s') . '] ERROR: No transaction id' . PHP_EOL, FILE_APPEND);
    http_response_code(400);
    exit;
}

$eventType = strtoupper($data['event_type'] ?? '');
$status = strtoupper($trans['status'] ?? '');
$resultCode = $trans['result_code'] ?? '0';
$resultCode = (string)($resultCode === null ? '0' : $resultCode);
$resultDesc = $trans['result_desc'] ?? 'Unknown';

$paid = ($eventType === 'TRANSACTION.SUCCESS') || ($status === 'SUCCESS') || ($resultCode === '0');

// Find order by transaction id
$stmt = $pdo->prepare("SELECT * FROM orders WHERE mpesa_checkout_id = ?");
$stmt->execute([$transactionId]);
$order = $stmt->fetch();

// Respond to PalPlass immediately — before any logic, so it doesn't retry
http_response_code(200);
echo json_encode(['success' => true, 'message' => 'Webhook received']);

if (ob_get_level()) ob_flush();
flush();

if (!$order) {
    @file_put_contents(__DIR__ . '/logs.txt', '[' . date('Y-m-d H:i:s') . "] ERROR: No order for transaction_id=$transactionId" . PHP_EOL, FILE_APPEND);
    exit;
}

$mpesaReceipt = $transactionId;
$mpesaPhone = $trans['phone_number'] ?? '';

if ($paid) {
    $pdo->prepare("UPDATE orders SET status = 'confirmed', mpesa_receipt = ?, mpesa_phone = ?, mpesa_result = ? WHERE id = ?")
        ->execute([$mpesaReceipt, $mpesaPhone, $resultDesc, $order['id']]);

    @file_put_contents(__DIR__ . '/logs.txt', '[' . date('Y-m-d H:i:s') . "] SUCCESS: Order {$order['order_ref']} transaction=$transactionId" . PHP_EOL, FILE_APPEND);
} else {
    $pdo->prepare("UPDATE orders SET status = 'cancelled', mpesa_result = ? WHERE id = ?")
        ->execute([$resultDesc, $order['id']]);

    @file_put_contents(__DIR__ . '/logs.txt', '[' . date('Y-m-d H:i:s') . "] FAILED: Order {$order['order_ref']} status=$status desc=$resultDesc" . PHP_EOL, FILE_APPEND);
}

// Send emails after response (best-effort, errors won't affect callback)
try {
    if ($paid) {
        if ($order['customer_email']) {
            $stmtItems = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
            $stmtItems->execute([$order['id']]);
            $items = $stmtItems->fetchAll();
            $order['status'] = 'confirmed';
            sendEmail($order['customer_email'], 'Payment Received — ' . $order['order_ref'],
                orderEmailBody($order, $items, "Your M-Pesa payment of Ksh " . number_format($order['total']) . " has been received!\n\nReceipt: " . $mpesaReceipt . "\n\nWe'll start processing your order right away."));
        }
        sendEmail(ADMIN_EMAIL, 'Payment Confirmed — ' . $order['order_ref'],
            "Payment confirmed for order " . $order['order_ref'] . "\nTransaction: " . $mpesaReceipt . "\nAmount: Ksh " . number_format($order['total']));
    } elseif ($order['customer_email']) {
        sendEmail($order['customer_email'], 'Payment Failed — ' . $order['order_ref'],
            "Your M-Pesa payment for order " . $order['order_ref'] . " was not completed.\n\nReason: " . $resultDesc . "\n\nYou can try again from your order page.");
    }
} catch (Exception $e) {
    @file_put_contents(__DIR__ . '/logs.txt', '[' . date('Y-m-d H:i:s') . "] EMAIL ERROR: " . $e->getMessage() . PHP_EOL, FILE_APPEND);
}