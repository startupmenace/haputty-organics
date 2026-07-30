<?php requireLogin();

$stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$orders = $stmt->fetchAll();
?>

<div class="space-y-6">
    <div class="border-b border-neutral-200 pb-4 flex items-center justify-between">
        <h1 class="text-xl font-black uppercase tracking-widest">My Orders</h1>
        <span class="text-xs text-neutral-500"><?= count($orders) ?> order<?= count($orders) !== 1 ? 's' : '' ?></span>
    </div>

    <?php if (empty($orders)): ?>
        <div class="text-center py-20 bg-neutral-50 border border-neutral-200">
            <p class="text-neutral-500 text-xs uppercase">You haven't placed any orders yet.</p>
        </div>
    <?php else: ?>
        <div class="space-y-4">
            <?php foreach ($orders as $order): ?>
                <?php
                $stmtItems = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
                $stmtItems->execute([$order['id']]);
                $items = $stmtItems->fetchAll();
                ?>
                <div class="bg-white border border-neutral-200 p-4 sm:p-6 space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                        <div>
                            <span class="text-xs font-mono font-bold text-black"><?= escape($order['order_ref']) ?></span>
                            <span class="text-[10px] text-neutral-500 ml-2"><?= date('d M Y, H:i', strtotime($order['created_at'])) ?></span>
                        </div>
                        <div class="flex items-center gap-3">
                            <?= getStatusBadge($order['status']) ?>
                            <a href="/?page=order-tracking&ref=<?= urlencode($order['order_ref']) ?>" class="text-[11px] font-bold text-black underline">Track</a>
                        </div>
                    </div>

                    <div class="divide-y divide-neutral-100">
                        <?php foreach ($items as $item): ?>
                            <div class="py-2 flex items-center gap-3 text-xs">
                                <?php if ($item['image_url']): ?>
                                    <img src="<?= escape($item['image_url']) ?>" alt="" class="w-10 h-10 object-contain bg-[#f5f5f5] border p-1" />
                                <?php endif; ?>
                                <div class="flex-1">
                                    <span class="font-semibold text-black"><?= escape($item['product_title']) ?></span>
                                    <span class="text-neutral-500 block text-[10px]">Qty: <?= (int)$item['quantity'] ?> &middot; Color: <?= escape($item['color'] ?? 'Standard') ?></span>
                                </div>
                                <span class="font-mono font-bold">Ksh <?= number_format((float)$item['price'] * (int)$item['quantity']) ?>.00</span>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="flex justify-between text-xs font-bold border-t border-neutral-200 pt-3">
                        <span>Total</span>
                        <span class="font-mono">Ksh <?= number_format($order['total']) ?>.00</span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
