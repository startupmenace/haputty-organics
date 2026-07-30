<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

// Sales by month
$monthlySales = $pdo->query("
    SELECT DATE_FORMAT(created_at, '%Y-%m') as month,
           COUNT(*) as order_count,
           COALESCE(SUM(total), 0) as revenue
    FROM orders
    WHERE status != 'cancelled'
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month DESC
    LIMIT 12
")->fetchAll();

// Sales by category
$categorySales = $pdo->query("
    SELECT c.name as category,
           COUNT(oi.id) as items_sold,
           COALESCE(SUM(oi.price * oi.quantity), 0) as revenue
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    JOIN categories c ON p.category_id = c.id
    JOIN orders o ON oi.order_id = o.id
    WHERE o.status != 'cancelled'
    GROUP BY c.id
    ORDER BY revenue DESC
")->fetchAll();

// Top products
$topProducts = $pdo->query("
    SELECT oi.product_title,
           SUM(oi.quantity) as total_qty,
           COALESCE(SUM(oi.price * oi.quantity), 0) as revenue
    FROM order_items oi
    JOIN orders o ON oi.order_id = o.id
    WHERE o.status != 'cancelled'
    GROUP BY oi.product_id
    ORDER BY revenue DESC
    LIMIT 10
")->fetchAll();

$totalRevenue = $pdo->query("SELECT COALESCE(SUM(total), 0) FROM orders WHERE status != 'cancelled'")->fetchColumn();
$totalOrders = $pdo->query("SELECT COUNT(*) FROM orders WHERE status != 'cancelled'")->fetchColumn();
$avgOrderValue = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;

require_once __DIR__ . '/header.php';
?>

<div class="space-y-6">
    <h1 class="text-2xl font-black uppercase tracking-widest">Reports</h1>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white border border-neutral-200 p-5">
            <div class="text-[10px] font-bold text-neutral-400 uppercase">Total Revenue</div>
            <div class="text-2xl font-extrabold font-mono mt-1">Ksh <?= number_format($totalRevenue) ?></div>
        </div>
        <div class="bg-white border border-neutral-200 p-5">
            <div class="text-[10px] font-bold text-neutral-400 uppercase">Completed Orders</div>
            <div class="text-2xl font-extrabold font-mono mt-1"><?= number_format($totalOrders) ?></div>
        </div>
        <div class="bg-white border border-neutral-200 p-5">
            <div class="text-[10px] font-bold text-neutral-400 uppercase">Avg. Order Value</div>
            <div class="text-2xl font-extrabold font-mono mt-1">Ksh <?= number_format($avgOrderValue, 2) ?></div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Monthly Sales -->
        <div class="bg-white border border-neutral-200 p-6">
            <h2 class="text-sm font-bold uppercase mb-4">Monthly Sales</h2>
            <div class="space-y-2">
                <?php foreach ($monthlySales as $ms): ?>
                    <div class="flex items-center justify-between text-xs border-b border-neutral-100 pb-2">
                        <span class="font-semibold"><?= escape($ms['month']) ?></span>
                        <div class="flex items-center gap-4">
                            <span class="text-neutral-500"><?= (int)$ms['order_count'] ?> orders</span>
                            <span class="font-mono font-bold">Ksh <?= number_format($ms['revenue']) ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($monthlySales)): ?>
                    <p class="text-xs text-neutral-400">No sales data yet.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Sales by Category -->
        <div class="bg-white border border-neutral-200 p-6">
            <h2 class="text-sm font-bold uppercase mb-4">Sales by Category</h2>
            <div class="space-y-2">
                <?php foreach ($categorySales as $cs): ?>
                    <div class="flex items-center justify-between text-xs border-b border-neutral-100 pb-2">
                        <span class="font-semibold"><?= escape($cs['category']) ?></span>
                        <div class="flex items-center gap-4">
                            <span class="text-neutral-500"><?= (int)$cs['items_sold'] ?> items</span>
                            <span class="font-mono font-bold">Ksh <?= number_format($cs['revenue']) ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($categorySales)): ?>
                    <p class="text-xs text-neutral-400">No sales data yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Top Products -->
    <div class="bg-white border border-neutral-200 p-6">
        <h2 class="text-sm font-bold uppercase mb-4">Top Selling Products</h2>
        <table class="w-full text-xs">
            <thead>
                <tr class="bg-neutral-50 text-neutral-500 font-bold uppercase text-[10px]">
                    <th class="text-left p-2">Product</th>
                    <th class="text-center p-2">Units Sold</th>
                    <th class="text-right p-2">Revenue</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($topProducts as $tp): ?>
                    <tr class="border-t border-neutral-100">
                        <td class="p-2 font-semibold"><?= escape($tp['product_title']) ?></td>
                        <td class="p-2 text-center"><?= (int)$tp['total_qty'] ?></td>
                        <td class="p-2 text-right font-mono font-bold">Ksh <?= number_format($tp['revenue']) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($topProducts)): ?>
                    <tr><td colspan="3" class="p-4 text-center text-neutral-400">No product sales data yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
