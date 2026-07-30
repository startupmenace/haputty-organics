<?php requireLogin();

$error = '';
$success = '';

// Update profile
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');

    if (empty($firstName) || empty($lastName) || empty($email)) {
        $error = 'Please fill in required fields.';
    } else {
        $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ?, address = ? WHERE id = ?");
        $stmt->execute([$firstName, $lastName, $email, $phone, $address, $_SESSION['user_id']]);
        $_SESSION['first_name'] = $firstName;
        $_SESSION['last_name'] = $lastName;
        $_SESSION['email'] = $email;
        $_SESSION['phone'] = $phone;
        $success = 'Profile updated successfully.';
    }
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Get recent orders
$stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
$stmt->execute([$_SESSION['user_id']]);
$orders = $stmt->fetchAll();
?>

<div class="space-y-8">
    <div class="border-b border-neutral-200 pb-4">
        <h1 class="text-xl font-black uppercase tracking-widest">My Account</h1>
    </div>

    <?php if ($error): ?>
        <div class="bg-red-50 border border-red-200 p-3 text-xs text-red-700 font-semibold"><?= escape($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="bg-green-50 border border-green-200 p-3 text-xs text-green-700 font-semibold"><?= escape($success) ?></div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <div class="bg-white border border-neutral-200 p-6 space-y-4">
            <h2 class="text-sm font-bold text-black uppercase">Profile Details</h2>
            <form method="POST" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="text-[11px] font-semibold text-neutral-600 block mb-1">First name</label>
                        <input type="text" name="first_name" value="<?= escape($user['first_name']) ?>" class="w-full bg-white border border-neutral-300 px-3 py-2 text-xs focus:border-black focus:outline-none" />
                    </div>
                    <div>
                        <label class="text-[11px] font-semibold text-neutral-600 block mb-1">Last name</label>
                        <input type="text" name="last_name" value="<?= escape($user['last_name']) ?>" class="w-full bg-white border border-neutral-300 px-3 py-2 text-xs focus:border-black focus:outline-none" />
                    </div>
                </div>
                <div>
                    <label class="text-[11px] font-semibold text-neutral-600 block mb-1">Email</label>
                    <input type="email" name="email" value="<?= escape($user['email']) ?>" class="w-full bg-white border border-neutral-300 px-3 py-2 text-xs focus:border-black focus:outline-none" />
                </div>
                <div>
                    <label class="text-[11px] font-semibold text-neutral-600 block mb-1">Phone</label>
                    <input type="text" name="phone" value="<?= escape($user['phone']) ?>" class="w-full bg-white border border-neutral-300 px-3 py-2 text-xs font-mono focus:border-black focus:outline-none" />
                </div>
                <div>
                    <label class="text-[11px] font-semibold text-neutral-600 block mb-1">Delivery Address</label>
                    <textarea name="address" rows="3" class="w-full bg-white border border-neutral-300 px-3 py-2 text-xs focus:border-black focus:outline-none"><?= escape($user['address'] ?? '') ?></textarea>
                </div>
                <button type="submit" name="update_profile" class="bg-black text-white text-xs font-bold uppercase py-3 px-6 hover:bg-neutral-800 transition-colors cursor-pointer">Update Profile</button>
            </form>
        </div>

        <div class="space-y-4">
            <div class="bg-white border border-neutral-200 p-6 space-y-4">
                <h2 class="text-sm font-bold text-black uppercase">Quick Links</h2>
                <div class="space-y-2">
                    <a href="/?page=orders" class="block text-xs text-neutral-700 hover:text-black font-semibold py-2 border-b border-neutral-100">My Orders (<?= count($orders) ?>)</a>
                    <a href="/?page=wishlist" class="block text-xs text-neutral-700 hover:text-black font-semibold py-2 border-b border-neutral-100">My Wishlist</a>
                    <a href="/?page=order-tracking" class="block text-xs text-neutral-700 hover:text-black font-semibold py-2 border-b border-neutral-100">Track an Order</a>
                    <?php if ($_SESSION['is_admin'] ?? false): ?>
                        <a href="/admin/" class="block text-xs bg-black text-white font-bold uppercase py-2 px-3 text-center mt-2">Admin Panel</a>
                    <?php endif; ?>
                    <a href="/?page=logout" class="block text-xs text-red-600 hover:text-black font-semibold py-2">Sign Out</a>
                </div>
            </div>

            <?php if (!empty($orders)): ?>
                <div class="bg-white border border-neutral-200 p-6 space-y-4">
                    <h2 class="text-sm font-bold text-black uppercase">Recent Orders</h2>
                    <div class="space-y-3">
                        <?php foreach (array_slice($orders, 0, 5) as $order): ?>
                            <a href="/?page=order-tracking&ref=<?= urlencode($order['order_ref']) ?>" class="block text-xs border border-neutral-200 p-3 hover:border-black transition-colors no-underline">
                                <div class="flex justify-between items-center">
                                    <span class="font-bold text-black font-mono"><?= escape($order['order_ref']) ?></span>
                                    <?= getStatusBadge($order['status']) ?>
                                </div>
                                <div class="text-neutral-500 mt-1">
                                    <?= date('d M Y, H:i', strtotime($order['created_at'])) ?> &middot; Ksh <?= number_format($order['total']) ?>.00
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                    <?php if (count($orders) > 5): ?>
                        <a href="/?page=orders" class="block text-xs font-bold text-black underline text-center">View all orders</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
