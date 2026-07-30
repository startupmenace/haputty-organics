<?php
// Handle buy_now redirect
if (isset($_GET['buy_now'])) {
    $buyId = (int)$_GET['buy_now'];
    if (!isset($_SESSION['user_id'])) {
        if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
        // Check if already in cart
        $found = false;
        foreach ($_SESSION['cart'] as &$item) {
            if ($item['product_id'] == $buyId) { $item['quantity']++; $found = true; break; }
        }
        if (!$found) $_SESSION['cart'][] = ['product_id' => $buyId, 'quantity' => 1, 'color' => 'Standard'];
    } else {
        $stmt = $pdo->prepare("SELECT id FROM cart WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$_SESSION['user_id'], $buyId]);
        if ($stmt->fetch()) {
            $pdo->prepare("UPDATE cart SET quantity = quantity + 1 WHERE user_id = ? AND product_id = ?")->execute([$_SESSION['user_id'], $buyId]);
        } else {
            $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, 1)")->execute([$_SESSION['user_id'], $buyId]);
        }
    }
}

// Get cart items
$cartItems = [];
$subtotal = 0;

if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("
        SELECT c.id, c.quantity, c.color, p.id as product_id, p.title, p.price, p.images, p.in_stock
        FROM cart c JOIN products p ON c.product_id = p.id
        WHERE c.user_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $cartItems = $stmt->fetchAll();
} elseif (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$item['product_id']]);
        $p = $stmt->fetch();
        if ($p) {
            $p['cart_id'] = 'sess_' . array_search($item, $_SESSION['cart']);
            $p['quantity'] = $item['quantity'];
            $p['color'] = $item['color'] ?? 'Standard';
            $cartItems[] = $p;
        }
    }
}

foreach ($cartItems as $item) {
    $subtotal += (float)$item['price'] * (int)$item['quantity'];
}

$finalTotal = $subtotal;

// Handle form submission
$orderPlaced = false;
$orderRef = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $shippingMethod = $_POST['shipping_method'] ?? 'standard';

    if (empty($firstName) || empty($lastName) || empty($email) || empty($phone)) {
        $error = 'Please fill in all required fields.';
    } elseif (empty($cartItems)) {
        $error = 'Your cart is empty.';
    } else {
        $orderRef = generateOrderRef();

        if (isset($_SESSION['user_id'])) {
            $stmt = $pdo->prepare("INSERT INTO orders (user_id, order_ref, total, status, payment_method, phone) VALUES (?, ?, ?, 'pending', 'mpesa', ?)");
            $stmt->execute([$_SESSION['user_id'], $orderRef, $finalTotal, $phone]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO orders (order_ref, total, status, payment_method, phone) VALUES (?, ?, 'pending', 'mpesa', ?)");
            $stmt->execute([$orderRef, $finalTotal, $phone]);
        }

        $orderId = $pdo->lastInsertId();

        foreach ($cartItems as $item) {
            $images = getProductImages($item['images']);
            $img = !empty($images) ? $images[0] : '';
            $stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, product_title, quantity, color, price, image_url) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $orderId,
                $item['product_id'] ?? $item['id'],
                $item['title'],
                $item['quantity'],
                $item['color'] ?? 'Standard',
                $item['price'],
                $img
            ]);
        }

        // Clear cart
        if (isset($_SESSION['user_id'])) {
            $pdo->prepare("DELETE FROM cart WHERE user_id = ?")->execute([$_SESSION['user_id']]);
        } else {
            $_SESSION['cart'] = [];
        }

        // If user is not logged in, store order_ref in session for tracking
        if (!isset($_SESSION['user_id'])) {
            $_SESSION['guest_order'] = $orderRef;
        }

        $orderPlaced = true;
    }
}
?>

<?php if ($orderPlaced): ?>
    <div class="max-w-xl mx-auto py-16 text-center space-y-6 bg-white p-8 border border-neutral-200">
        <div class="w-16 h-16 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mx-auto">
            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <h2 class="text-2xl font-bold text-black">Order Confirmed!</h2>
        <p class="text-xs text-neutral-600">
            Thank you for your order. An M-Pesa transaction receipt and delivery updates have been sent to <span class="font-bold text-black">+<?= escape($phone ?? '') ?></span>.
        </p>
        <div class="bg-neutral-50 p-4 text-left text-xs font-mono space-y-1 border border-neutral-200">
            <div><span class="text-neutral-500">Order Ref:</span> <?= escape($orderRef) ?></div>
            <div><span class="text-neutral-500">Amount Paid:</span> Ksh <?= number_format($finalTotal) ?>.00</div>
            <div><span class="text-neutral-500">Payment Method:</span> Safaricom M-PESA STK Push</div>
        </div>
        <a href="/?page=order-tracking&ref=<?= urlencode($orderRef) ?>" class="block w-full bg-black text-white text-xs font-bold uppercase py-3 hover:bg-neutral-800 no-underline">
            Track Your Order
        </a>
        <a href="/" class="block w-full bg-white border border-black text-black text-xs font-bold uppercase py-3 hover:bg-neutral-100 no-underline">
            Return to Store
        </a>
    </div>
<?php else: ?>
    <div class="space-y-6 bg-[#f9f9f9] -mx-3 sm:-mx-6 lg:-mx-8 p-4 sm:p-8">
        <div class="max-w-6xl mx-auto flex items-center justify-between border-b border-neutral-200 pb-4 bg-white p-4">
            <span class="text-xl font-black uppercase font-mono tracking-tighter">HAPUTTY ORGANICS</span>
            <a href="/" class="text-xs font-bold bg-black text-white px-4 py-2 hover:bg-neutral-800 flex items-center gap-1.5 no-underline">
                Edit Cart
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
            </a>
        </div>

        <div class="max-w-6xl mx-auto space-y-2">
            <span class="text-[10px] font-bold text-neutral-400 uppercase tracking-widest block">CHECKOUT</span>
            <h1 class="text-2xl font-extrabold text-black">Complete your order</h1>
        </div>

        <?php if ($error): ?>
            <div class="max-w-6xl mx-auto bg-red-50 border border-red-200 p-4 text-xs text-red-700 font-semibold"><?= escape($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="/index.php?page=checkout" class="max-w-6xl mx-auto grid grid-cols-1 lg:grid-cols-12 gap-8">
            <div class="lg:col-span-7 space-y-6">

                <div class="bg-white p-6 border border-neutral-200 space-y-4">
                    <div class="text-[10px] font-bold text-neutral-400 uppercase tracking-wider">CONTACT</div>
                    <h2 class="text-sm font-bold text-black">Who is receiving the order?</h2>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-[11px] font-semibold text-neutral-600 block mb-1">First name</label>
                            <input type="text" name="first_name" placeholder="First name" value="<?= escape($_POST['first_name'] ?? ($_SESSION['first_name'] ?? '')) ?>" required class="w-full bg-white border border-neutral-300 px-3 py-2 text-xs focus:border-black focus:outline-none" />
                        </div>
                        <div>
                            <label class="text-[11px] font-semibold text-neutral-600 block mb-1">Last name</label>
                            <input type="text" name="last_name" placeholder="Last name" value="<?= escape($_POST['last_name'] ?? ($_SESSION['last_name'] ?? '')) ?>" required class="w-full bg-white border border-neutral-300 px-3 py-2 text-xs focus:border-black focus:outline-none" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="text-[11px] font-semibold text-neutral-600 block mb-1">Email</label>
                            <input type="email" name="email" placeholder="you@example.com" value="<?= escape($_POST['email'] ?? ($_SESSION['email'] ?? '')) ?>" required class="w-full bg-white border border-neutral-300 px-3 py-2 text-xs focus:border-black focus:outline-none" />
                        </div>
                        <div>
                            <label class="text-[11px] font-semibold text-neutral-600 block mb-1">Phone</label>
                            <input type="text" name="phone" placeholder="+254 700 000 000" value="<?= escape($_POST['phone'] ?? ($_SESSION['phone'] ?? '')) ?>" required class="w-full bg-white border border-neutral-300 px-3 py-2 text-xs font-mono focus:border-black focus:outline-none" />
                        </div>
                    </div>
                </div>

                <div class="bg-white p-6 border border-neutral-200 space-y-4">
                    <div class="text-[10px] font-bold text-neutral-400 uppercase tracking-wider">SHIPPING METHOD</div>
                    <h2 class="text-sm font-bold text-black">How would you like to receive your order?</h2>
                    <select name="shipping_method" class="w-full bg-white border border-neutral-300 p-3 text-xs focus:border-black focus:outline-none">
                        <option value="standard">Nairobi & Nationwide Express Delivery - FREE</option>
                    </select>
                </div>

                <div class="bg-white p-6 border border-neutral-200 space-y-4">
                    <div class="text-[10px] font-bold text-neutral-400 uppercase tracking-wider">PAYMENT</div>
                    <div class="flex items-center justify-between">
                        <h2 class="text-sm font-bold text-black">Pay securely</h2>
                        <span class="text-[10px] text-neutral-400">We support multiple attempts per checkout</span>
                    </div>
                    <div class="border border-black p-4 bg-white flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <input type="radio" checked readonly class="accent-black" />
                            <div>
                                <div class="text-xs font-bold text-black">M-Pesa STK Push</div>
                                <div class="text-[11px] text-neutral-500">We will prompt your phone to confirm the payment.</div>
                            </div>
                        </div>
                        <span class="bg-neutral-100 text-black text-[10px] font-bold px-2 py-0.5 border border-neutral-300">Fastest</span>
                    </div>
                    <button type="submit" name="place_order" value="1" <?= empty($cartItems) ? 'disabled' : '' ?> class="w-full bg-[#3B4252] hover:bg-black text-white font-bold text-xs uppercase py-4 transition-colors shadow-none text-center cursor-pointer">
                        Place Order
                    </button>
                    <p class="text-[10px] text-neutral-400 text-center">You will see a confirmation once payment succeeds. Do not close this tab.</p>
                </div>
            </div>

            <div class="lg:col-span-5 space-y-4">
                <div class="bg-white p-6 border border-neutral-200 space-y-4">
                    <div class="flex items-center justify-between">
                        <h2 class="text-sm font-bold text-black">Order summary</h2>
                        <span class="text-xs bg-neutral-100 px-2 py-0.5 text-neutral-600 font-mono"><?= count($cartItems) ?> Item<?= count($cartItems) !== 1 ? 's' : '' ?></span>
                    </div>

                    <div class="divide-y divide-neutral-100 max-h-60 overflow-y-auto">
                        <?php if (empty($cartItems)): ?>
                            <p class="text-xs text-neutral-500 py-4">Your cart is empty.</p>
                        <?php else: ?>
                            <?php foreach ($cartItems as $item): ?>
                                <?php $imgs = getProductImages($item['images']); $img = !empty($imgs) ? $imgs[0] : ''; ?>
                                <div class="py-3 flex items-center gap-3 text-xs">
                                    <div class="w-12 h-12 bg-neutral-50 border border-neutral-200 p-1 flex-shrink-0 relative">
                                        <img src="<?= escape($img) ?>" alt="" class="w-full h-full object-contain" />
                                        <span class="absolute -top-1.5 -right-1.5 bg-black text-white text-[10px] w-4 h-4 rounded-full flex items-center justify-center font-bold"><?= (int)$item['quantity'] ?></span>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <h4 class="font-semibold text-black truncate"><?= escape($item['title']) ?></h4>
                                    </div>
                                    <div class="font-mono font-bold text-black">Ksh <?= number_format((float)$item['price'] * (int)$item['quantity']) ?>.00</div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <div class="border-t border-neutral-200 pt-3 space-y-1.5 text-xs font-mono">
                        <div class="flex justify-between text-neutral-600"><span>Subtotal</span><span>Ksh <?= number_format($subtotal) ?>.00</span></div>
                        <div class="flex justify-between text-neutral-600"><span>Discount</span><span>-Ksh 0.00</span></div>
                        <div class="flex justify-between text-neutral-600"><span>Shipping</span><span>Ksh 0.00</span></div>
                        <div class="flex justify-between text-neutral-600"><span>Tax</span><span>Ksh 0.00</span></div>
                        <div class="flex justify-between text-neutral-600 pt-1 border-t border-neutral-100"><span>Total before credits</span><span>Ksh <?= number_format($subtotal) ?>.00</span></div>
                        <div class="border-t border-black pt-3 flex justify-between text-sm font-extrabold text-black font-sans">
                            <span>Total</span>
                            <span class="font-mono text-base font-bold">Ksh <?= number_format($finalTotal) ?>.00</span>
                        </div>
                    </div>

                    <p class="text-[10px] text-neutral-400 pt-2 border-t border-neutral-100">This checkout locks prices until Jul 30, 18:30.</p>
                </div>
            </div>
        </form>
    </div>
<?php endif; ?>
