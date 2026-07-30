<?php
require_once __DIR__ . '/../config/db.php';

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

$callback = $data['Body']['stkCallback'] ?? null;
if (!$callback) {
    @file_put_contents(__DIR__ . '/logs.txt', '[' . date('Y-m-d H:i:s') . '] ERROR: No stkCallback body' . PHP_EOL, FILE_APPEND);
    http_response_code(400);
    exit;
}

$checkoutRequestId = $callback['CheckoutRequestID'] ?? '';
$resultCode = $callback['ResultCode'] ?? 1;
$resultDesc = $callback['ResultDesc'] ?? '';

// Find order
$stmt = $pdo->prepare("SELECT * FROM orders WHERE mpesa_checkout_id = ?");
$stmt->execute([$checkoutRequestId]);
$order = $stmt->fetch();

if (!$order) {
    @file_put_contents(__DIR__ . '/logs.txt', '[' . date('Y-m-d H:i:s') . "] ERROR: No order for checkout_id=$checkoutRequestId" . PHP_EOL, FILE_APPEND);
    http_response_code(404);
    exit;
}

// Parse metadata items by Name
$items = $callback['CallbackMetadata']['Item'] ?? [];
$meta = [];
foreach ($items as $item) {
    $meta[$item['Name']] = $item['Value'] ?? '';
}

$mpesaReceipt = $meta['MpesaReceiptNumber'] ?? $meta['ReceiptNumber'] ?? '';
$mpesaPhone = $meta['PhoneNumber'] ?? '';

if ($resultCode === 0) {
    $pdo->prepare("UPDATE orders SET status = 'confirmed', mpesa_receipt = ?, mpesa_phone = ?, mpesa_result = ? WHERE id = ?")
        ->execute([$mpesaReceipt, $mpesaPhone, $resultDesc, $order['id']]);
    @file_put_contents(__DIR__ . '/logs.txt', '[' . date('Y-m-d H:i:s') . "] SUCCESS: Order {$order['order_ref']} receipt=$mpesaReceipt" . PHP_EOL, FILE_APPEND);
} else {
    $pdo->prepare("UPDATE orders SET status = 'cancelled', mpesa_result = ? WHERE id = ?")
        ->execute([$resultDesc, $order['id']]);
    @file_put_contents(__DIR__ . '/logs.txt', '[' . date('Y-m-d H:i:s') . "] FAILED: Order {$order['order_ref']} code=$resultCode desc=$resultDesc" . PHP_EOL, FILE_APPEND);
}

http_response_code(200);
echo json_encode(['ResultCode' => 0, 'ResultDesc' => 'Success']);
