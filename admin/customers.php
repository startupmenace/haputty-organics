<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$customers = $pdo->query("
    SELECT u.*, 
           COUNT(o.id) as order_count, 
           COALESCE(SUM(o.total), 0) as total_spent 
    FROM users u 
    LEFT JOIN orders o ON u.id = o.user_id 
    WHERE u.is_admin = 0
    GROUP BY u.id 
    ORDER BY u.created_at DESC
")->fetchAll();

require_once __DIR__ . '/header.php';
?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-black uppercase tracking-widest">Customers</h1>
        <span class="text-xs text-neutral-500"><?= count($customers) ?> registered</span>
    </div>

    <div class="bg-white border border-neutral-200 overflow-x-auto">
        <table class="w-full text-xs">
            <thead>
                <tr class="bg-neutral-50 text-neutral-500 font-bold uppercase text-[10px]">
                    <th class="text-left p-3">Name</th>
                    <th class="text-left p-3">Email</th>
                    <th class="text-left p-3">Phone</th>
                    <th class="text-center p-3">Orders</th>
                    <th class="text-right p-3">Total Spent</th>
                    <th class="text-left p-3">Joined</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($customers as $c): ?>
                    <tr class="border-t border-neutral-100">
                        <td class="p-3 font-semibold"><?= escape($c['first_name'] . ' ' . $c['last_name']) ?></td>
                        <td class="p-3 text-neutral-600"><?= escape($c['email']) ?></td>
                        <td class="p-3 text-neutral-600 font-mono">+<?= escape($c['phone']) ?></td>
                        <td class="p-3 text-center font-bold"><?= (int)$c['order_count'] ?></td>
                        <td class="p-3 text-right font-mono font-bold">Ksh <?= number_format($c['total_spent']) ?></td>
                        <td class="p-3 text-neutral-500"><?= date('d M Y', strtotime($c['created_at'])) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($customers)): ?>
                    <tr><td colspan="6" class="p-6 text-center text-neutral-400">No registered customers yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
