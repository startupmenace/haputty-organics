<?php
$orderRef = $_GET['ref'] ?? ($_SESSION['guest_order'] ?? '');
$order = null;
$items = [];
$trackingError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['track_order'])) {
    $orderRef = trim($_POST['order_ref'] ?? '');
}

if ($orderRef) {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_ref = ?");
    $stmt->execute([$orderRef]);
    $order = $stmt->fetch();

    if (!$order) {
        $trackingError = 'Order not found. Please check your order reference number.';
    } else {
        if (isset($_SESSION['user_id']) && $order['user_id'] && $order['user_id'] != $_SESSION['user_id']) {
            $trackingError = 'This order does not belong to your account.';
            $order = null;
        } else {
            $stmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
            $stmt->execute([$order['id']]);
            $items = $stmt->fetchAll();
        }
    }
}

// Return JSON for AJAX polling
if (isset($_GET['ajax']) && $order) {
    header('Content-Type: application/json');
    ob_clean();
    echo json_encode([
        'status' => $order['status'],
        'mpesa_result' => $order['mpesa_result'] ?? '',
        'mpesa_receipt' => $order['mpesa_receipt'] ?? '',
    ]);
    exit;
}

$statusSteps = ['pending', 'confirmed', 'processing', 'shipped', 'delivered'];
$currentStep = $order ? array_search($order['status'], $statusSteps) : -1;
?>

<div class="max-w-xl mx-auto py-8 space-y-8">
    <div class="border-b border-neutral-200 pb-4">
        <h1 class="text-xl font-black uppercase tracking-widest">Track Your Order</h1>
    </div>

    <form method="POST" class="flex gap-3">
        <input type="text" name="order_ref" placeholder="Enter order reference (e.g. HAPPUTY-XXXXX)" value="<?= escape($orderRef) ?>" class="flex-1 bg-white border border-neutral-300 px-3 py-2.5 text-xs focus:border-black focus:outline-none" />
        <button type="submit" name="track_order" class="bg-black text-white text-xs font-bold uppercase px-6 py-2.5 hover:bg-neutral-800 cursor-pointer">Track</button>
    </form>

    <?php if ($trackingError): ?>
        <div class="bg-red-50 border border-red-200 p-4 text-xs text-red-700 font-semibold"><?= escape($trackingError) ?></div>
    <?php endif; ?>

    <?php if ($order): ?>
        <div class="bg-white border border-neutral-200 p-6 space-y-6">
            <div class="flex justify-between items-center">
                <div>
                    <span class="text-[10px] text-neutral-500 uppercase">Order Reference</span>
                    <div class="text-sm font-mono font-bold text-black"><?= escape($order['order_ref']) ?></div>
                </div>
                <?= getStatusBadge($order['status']) ?>
            </div>

            <!-- Progress Tracker -->
            <div class="relative py-4">
                <div class="flex items-center justify-between">
                    <?php $labels = ['Pending', 'Confirmed', 'Processing', 'Shipped', 'Delivered']; ?>
                    <?php foreach ($labels as $idx => $label): ?>
                        <div class="flex flex-col items-center z-10">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center text-[10px] font-bold <?= $idx <= $currentStep ? 'bg-black text-white' : 'bg-neutral-200 text-neutral-400' ?>">
                                <?php if ($idx < $currentStep || ($idx === $currentStep && $order['status'] === 'delivered')): ?>
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                <?php else: ?>
                                    <?= $idx + 1 ?>
                                <?php endif; ?>
                            </div>
                            <span class="text-[9px] mt-1 font-semibold <?= $idx <= $currentStep ? 'text-black' : 'text-neutral-400' ?>"><?= $label ?></span>
                        </div>
                        <?php if ($idx < count($labels) - 1): ?>
                            <div class="flex-1 h-0.5 mx-1 <?= $idx < $currentStep ? 'bg-black' : 'bg-neutral-200' ?>"></div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="border-t border-neutral-200 pt-4 text-xs space-y-2">
                <div class="flex justify-between text-neutral-500">
                    <span>Order Date</span>
                    <span class="font-semibold text-black"><?= date('d M Y, H:i', strtotime($order['created_at'])) ?></span>
                </div>
                <div class="flex justify-between text-neutral-500">
                    <span>Payment Method</span>
                    <span class="font-semibold text-black"><?= escape(strtoupper($order['payment_method'] ?? 'M-PESA')) ?></span>
                </div>
                <div class="flex justify-between text-neutral-500">
                    <span>Phone</span>
                    <span class="font-semibold text-black">+<?= escape($order['phone'] ?? 'N/A') ?></span>
                </div>
                <?php if ($order['delivery_location']): ?>
                <div class="flex justify-between text-neutral-500">
                    <span>Delivery</span>
                    <span class="font-semibold text-black"><?= escape($order['delivery_location']) ?></span>
                </div>
                <?php endif; ?>
                <?php if ((float)($order['delivery_fee'] ?? 0) > 0): ?>
                <div class="flex justify-between text-neutral-500">
                    <span>Delivery Fee</span>
                    <span class="font-semibold text-black">Ksh <?= number_format((float)$order['delivery_fee']) ?>.00</span>
                </div>
                <?php endif; ?>
                <?php if ($order['delivery_instructions']): ?>
                <div class="flex justify-between text-neutral-500">
                    <span>Instructions</span>
                    <span class="font-semibold text-black"><?= escape($order['delivery_instructions']) ?></span>
                </div>
                <?php endif; ?>
                <div class="flex justify-between text-base font-extrabold text-black border-t border-black pt-2">
                    <span>Total Paid</span>
                    <span class="font-mono">Ksh <?= number_format($order['total']) ?>.00</span>
                </div>
            </div>

            <?php if (!empty($items)): ?>
                <div class="border-t border-neutral-200 pt-4 space-y-3">
                    <h3 class="text-xs font-bold uppercase text-black">Items Ordered</h3>
                    <?php foreach ($items as $item): ?>
                        <div class="flex items-center gap-3 text-xs">
                            <?php if ($item['image_url']): ?>
                                <img src="<?= escape($item['image_url']) ?>" alt="" class="w-12 h-12 object-contain bg-[#f5f5f5] border p-1" />
                            <?php endif; ?>
                            <div class="flex-1">
                                <span class="font-semibold text-black"><?= escape($item['product_title']) ?></span>
                                <span class="text-neutral-500 block text-[10px]">Qty: <?= (int)$item['quantity'] ?> &middot; Ksh <?= number_format((float)$item['price']) ?> each</span>
                            </div>
                            <span class="font-mono font-bold">Ksh <?= number_format((float)$item['price'] * (int)$item['quantity']) ?>.00</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
