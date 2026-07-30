<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$message = '';

// Update order status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $orderId = (int)$_POST['order_id'];
    $newStatus = $_POST['status'] ?? '';
    $validStatuses = ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled'];
    if (in_array($newStatus, $validStatuses)) {
        $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?")->execute([$newStatus, $orderId]);
        $message = 'Order status updated to ' . ucfirst($newStatus) . '.';
    }
}

// Get single order view
$viewOrder = null;
if (isset($_GET['view'])) {
    $stmt = $pdo->prepare("SELECT o.*, CONCAT(u.first_name, ' ', u.last_name) as customer_name, u.email as customer_email FROM orders o LEFT JOIN users u ON o.user_id = u.id WHERE o.id = ?");
    $stmt->execute([(int)$_GET['view']]);
    $viewOrder = $stmt->fetch();
}

// Get all orders
$orders = $pdo->query("SELECT o.*, CONCAT(u.first_name, ' ', u.last_name) as customer_name FROM orders o LEFT JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC")->fetchAll();

require_once __DIR__ . '/header.php';
?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-black uppercase tracking-widest">
            <?= $viewOrder ? 'Order Details' : 'Orders' ?>
        </h1>
        <?php if ($viewOrder): ?>
            <a href="/admin/orders.php" class="text-xs font-bold text-black underline">&larr; Back to Orders</a>
        <?php endif; ?>
    </div>

    <?php if ($message): ?>
        <div class="bg-green-50 border border-green-200 p-3 text-xs text-green-700 font-semibold"><?= escape($message) ?></div>
    <?php endif; ?>

    <?php if ($viewOrder): ?>
        <?php
        $stmtItems = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
        $stmtItems->execute([$viewOrder['id']]);
        $items = $stmtItems->fetchAll();
        ?>
        <div class="bg-white border border-neutral-200 p-6 space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <div class="text-[10px] font-bold text-neutral-400 uppercase tracking-wider mb-1">Order Reference</div>
                    <div class="font-mono font-bold text-black"><?= escape($viewOrder['order_ref']) ?></div>
                </div>
                <div>
                    <div class="text-[10px] font-bold text-neutral-400 uppercase tracking-wider mb-1">Customer</div>
                    <div class="font-bold text-black"><?= escape($viewOrder['customer_name'] ?? 'Guest') ?></div>
                    <?php if ($viewOrder['customer_email']): ?>
                        <div class="text-[11px] text-neutral-500"><?= escape($viewOrder['customer_email']) ?></div>
                    <?php endif; ?>
                </div>
                <div>
                    <div class="text-[10px] font-bold text-neutral-400 uppercase tracking-wider mb-1">Date</div>
                    <div class="text-sm text-black"><?= date('d M Y, H:i', strtotime($viewOrder['created_at'])) ?></div>
                </div>
            </div>

            <div class="border-t border-neutral-200 pt-4">
                <form method="POST" class="flex items-center gap-3">
                    <input type="hidden" name="order_id" value="<?= $viewOrder['id'] ?>" />
                    <label class="text-[11px] font-semibold text-neutral-600">Update Status:</label>
                    <select name="status" class="border border-neutral-300 px-3 py-1.5 text-xs focus:border-black focus:outline-none">
                        <?php foreach (['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled'] as $st): ?>
                            <option value="<?= $st ?>" <?= $viewOrder['status'] === $st ? 'selected' : '' ?>><?= ucfirst($st) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" name="update_status" class="bg-black text-white text-xs font-bold uppercase px-4 py-1.5 hover:bg-neutral-800 cursor-pointer">Update</button>
                    <span class="ml-2"><?= getStatusBadge($viewOrder['status']) ?></span>
                </form>
            </div>

            <div class="border-t border-neutral-200 pt-4">
                <h3 class="text-xs font-bold uppercase text-black mb-3">Items</h3>
                <table class="w-full text-xs">
                    <thead>
                        <tr class="bg-neutral-50 text-neutral-500 font-bold uppercase text-[10px]">
                            <th class="text-left p-2">Product</th>
                            <th class="text-left p-2">Color</th>
                            <th class="text-center p-2">Qty</th>
                            <th class="text-right p-2">Price</th>
                            <th class="text-right p-2">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                            <tr class="border-t border-neutral-100">
                                <td class="p-2 font-semibold"><?= escape($item['product_title']) ?></td>
                                <td class="p-2 text-neutral-500"><?= escape($item['color'] ?? '-') ?></td>
                                <td class="p-2 text-center"><?= (int)$item['quantity'] ?></td>
                                <td class="p-2 text-right font-mono">Ksh <?= number_format($item['price']) ?></td>
                                <td class="p-2 text-right font-mono font-bold">Ksh <?= number_format((float)$item['price'] * (int)$item['quantity']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="border-t border-black font-bold">
                            <td colspan="4" class="p-2 text-right text-xs">Total</td>
                            <td class="p-2 text-right font-mono">Ksh <?= number_format($viewOrder['total']) ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="border-t border-neutral-200 pt-4 grid grid-cols-2 gap-4 text-xs">
                <div>
                    <span class="text-neutral-500">Payment Method:</span>
                    <span class="font-bold text-black ml-2"><?= escape(strtoupper($viewOrder['payment_method'] ?? 'M-PESA')) ?></span>
                </div>
                <div>
                    <span class="text-neutral-500">Phone:</span>
                    <span class="font-bold text-black ml-2">+<?= escape($viewOrder['phone'] ?? 'N/A') ?></span>
                </div>
            </div>
        </div>

    <?php else: ?>
        <div class="bg-white border border-neutral-200 overflow-x-auto">
            <table class="w-full text-xs">
                <thead>
                    <tr class="bg-neutral-50 text-neutral-500 font-bold uppercase text-[10px]">
                        <th class="text-left p-3">Order Ref</th>
                        <th class="text-left p-3">Customer</th>
                        <th class="text-left p-3">Total</th>
                        <th class="text-left p-3">Status</th>
                        <th class="text-left p-3">Payment</th>
                        <th class="text-left p-3">Date</th>
                        <th class="text-left p-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr class="border-t border-neutral-100">
                            <td class="p-3 font-mono font-bold"><?= escape($order['order_ref']) ?></td>
                            <td class="p-3"><?= escape($order['customer_name'] ?? 'Guest') ?></td>
                            <td class="p-3 font-mono">Ksh <?= number_format($order['total']) ?></td>
                            <td class="p-3"><?= getStatusBadge($order['status']) ?></td>
                            <td class="p-3 text-neutral-500"><?= escape(strtoupper($order['payment_method'] ?? 'M-PESA')) ?></td>
                            <td class="p-3 text-neutral-500"><?= date('d M Y', strtotime($order['created_at'])) ?></td>
                            <td class="p-3">
                                <a href="/admin/orders.php?view=<?= $order['id'] ?>" class="text-black font-bold underline">View</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($orders)): ?>
                        <tr><td colspan="7" class="p-6 text-center text-neutral-400">No orders yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
