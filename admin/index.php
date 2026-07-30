<?php require_once __DIR__ . '/header.php'; ?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-black uppercase tracking-widest">Dashboard</h1>
        <span class="text-xs text-neutral-500"><?= date('l, d M Y') ?></span>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white border border-neutral-200 p-5 space-y-1">
            <div class="text-[10px] font-bold text-neutral-400 uppercase tracking-wider">Total Products</div>
            <div class="text-3xl font-extrabold font-mono"><?= number_format($totalProducts) ?></div>
        </div>
        <div class="bg-white border border-neutral-200 p-5 space-y-1">
            <div class="text-[10px] font-bold text-neutral-400 uppercase tracking-wider">Total Orders</div>
            <div class="text-3xl font-extrabold font-mono"><?= number_format($totalOrders) ?></div>
        </div>
        <div class="bg-white border border-neutral-200 p-5 space-y-1">
            <div class="text-[10px] font-bold text-neutral-400 uppercase tracking-wider">Revenue</div>
            <div class="text-3xl font-extrabold font-mono">Ksh <?= number_format($totalRevenue) ?></div>
        </div>
        <div class="bg-white border border-neutral-200 p-5 space-y-1">
            <div class="text-[10px] font-bold text-neutral-400 uppercase tracking-wider">Pending Orders</div>
            <div class="text-3xl font-extrabold font-mono"><?= number_format($pendingOrders) ?></div>
        </div>
    </div>

    <?php
    // Recent orders
    $stmt = $pdo->query("SELECT o.*, CONCAT(u.first_name, ' ', u.last_name) as customer_name FROM orders o LEFT JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC LIMIT 10");
    $recentOrders = $stmt->fetchAll();
    ?>

    <div class="bg-white border border-neutral-200">
        <div class="p-4 border-b border-neutral-200 flex items-center justify-between">
            <h2 class="text-sm font-bold uppercase">Recent Orders</h2>
            <a href="/admin/orders.php" class="text-[11px] font-bold text-black underline">View All</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead>
                    <tr class="bg-neutral-50 text-neutral-500 font-bold uppercase text-[10px]">
                        <th class="text-left p-3">Order Ref</th>
                        <th class="text-left p-3">Customer</th>
                        <th class="text-left p-3">Total</th>
                        <th class="text-left p-3">Status</th>
                        <th class="text-left p-3">Date</th>
                        <th class="text-left p-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentOrders as $order): ?>
                        <tr class="border-t border-neutral-100">
                            <td class="p-3 font-mono font-bold"><?= escape($order['order_ref']) ?></td>
                            <td class="p-3"><?= escape($order['customer_name'] ?? 'Guest') ?></td>
                            <td class="p-3 font-mono">Ksh <?= number_format($order['total']) ?></td>
                            <td class="p-3"><?= getStatusBadge($order['status']) ?></td>
                            <td class="p-3 text-neutral-500"><?= date('d M Y', strtotime($order['created_at'])) ?></td>
                            <td class="p-3">
                                <a href="/admin/orders.php?view=<?= $order['id'] ?>" class="text-black font-bold underline">View</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($recentOrders)): ?>
                        <tr><td colspan="6" class="p-6 text-center text-neutral-400">No orders yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
