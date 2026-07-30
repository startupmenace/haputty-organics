<?php
// Handle buy_now redirect
if (isset($_GET['buy_now'])) {
    $buyId = (int)$_GET['buy_now'];
    if (!isset($_SESSION['user_id'])) {
        if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
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

// Get active shop locations (create table if missing)
$shopLocations = [];
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS shop_locations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        address TEXT NOT NULL,
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    $shopLocations = $pdo->query("SELECT * FROM shop_locations WHERE is_active = 1 ORDER BY name")->fetchAll();
} catch (Exception $e) {
    $shopLocations = [];
}

// Add delivery columns to orders table if missing
try { $pdo->exec("ALTER TABLE orders ADD COLUMN delivery_method VARCHAR(20) DEFAULT NULL"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE orders ADD COLUMN delivery_location VARCHAR(255) DEFAULT NULL"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE orders ADD COLUMN delivery_instructions TEXT DEFAULT NULL"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE orders ADD COLUMN mpesa_checkout_id VARCHAR(100) DEFAULT NULL"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE orders ADD COLUMN mpesa_merchant_id VARCHAR(100) DEFAULT NULL"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE orders ADD COLUMN mpesa_receipt VARCHAR(100) DEFAULT NULL"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE orders ADD COLUMN mpesa_phone VARCHAR(50) DEFAULT NULL"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE orders ADD COLUMN mpesa_result TEXT DEFAULT NULL"); } catch (Exception $e) {}

$orderPlaced = false;
$pendingOrderId = 0;
$orderRef = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $deliveryMethod = $_POST['delivery_method'] ?? 'delivery';
    $deliveryLocation = trim($_POST['delivery_location'] ?? '');
    $deliveryInstructions = trim($_POST['delivery_instructions'] ?? '');

    if (empty($firstName) || empty($lastName) || empty($email) || empty($phone)) {
        $error = 'Please fill in all required fields.';
    } elseif (empty($deliveryLocation)) {
        $error = 'Please select a delivery location or pickup point.';
    } elseif (empty($cartItems)) {
        $error = 'Your cart is empty.';
    } else {
        $orderRef = generateOrderRef();

        if (isset($_SESSION['user_id'])) {
            $stmt = $pdo->prepare("INSERT INTO orders (user_id, order_ref, total, status, payment_method, phone, delivery_method, delivery_location, delivery_instructions) VALUES (?, ?, ?, 'pending', 'mpesa', ?, ?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $orderRef, $finalTotal, $phone, $deliveryMethod, $deliveryLocation, $deliveryInstructions]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO orders (order_ref, total, status, payment_method, phone, delivery_method, delivery_location, delivery_instructions) VALUES (?, ?, 'pending', 'mpesa', ?, ?, ?, ?)");
            $stmt->execute([$orderRef, $finalTotal, $phone, $deliveryMethod, $deliveryLocation, $deliveryInstructions]);
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

        if (isset($_SESSION['user_id'])) {
            $pdo->prepare("DELETE FROM cart WHERE user_id = ?")->execute([$_SESSION['user_id']]);
        } else {
            $_SESSION['cart'] = [];
        }

        if (!isset($_SESSION['user_id'])) {
            $_SESSION['guest_order'] = $orderRef;
        }

        $orderPlaced = true;
        $pendingOrderId = $orderId;
    }
}

// Kenyan counties data for autocomplete
$counties = [
    'Baringo' => ['Kabarnet', 'Eldama Ravine', 'Mogotio'],
    'Bomet' => ['Bomet', 'Sotik', 'Ndanai'],
    'Bungoma' => ['Bungoma', 'Webuye', 'Kimilili'],
    'Busia' => ['Busia', 'Port Victoria', 'Nambale'],
    'Elgeyo Marakwet' => ['Iten', 'Kapsowar', 'Chebiemit'],
    'Embu' => ['Embu', 'Runyenjes', 'Manyatta'],
    'Garissa' => ['Garissa', 'Masalani', 'Hulugho'],
    'Homa Bay' => ['Homa Bay', 'Mbita', 'Oyugis'],
    'Isiolo' => ['Isiolo', 'Merti', 'Garbatulla'],
    'Kajiado' => ['Kajiado', 'Ngong', 'Kitengela', 'Isinya'],
    'Kakamega' => ['Kakamega', 'Mumias', 'Butere'],
    'Kericho' => ['Kericho', 'Londiani', 'Kipkelion'],
    'Kiambu' => ['Kiambu', 'Thika', 'Ruiru', 'Limuru', 'Kikuyu', 'Juja'],
    'Kilifi' => ['Kilifi', 'Malindi', 'Mariakani', 'Mtwapa'],
    'Kirinyaga' => ['Kerugoya', 'Wanguru', 'Sagana'],
    'Kisii' => ['Kisii', 'Ogembo', 'Keroka'],
    'Kisumu' => ['Kisumu', 'Ahero', 'Muhoroni'],
    'Kitui' => ['Kitui', 'Mwingi', 'Mutomo'],
    'Kwale' => ['Kwale', 'Msambweni', 'Lunga Lunga'],
    'Laikipia' => ['Nanyuki', 'Nyahururu', 'Rumuruti'],
    'Lamu' => ['Lamu', 'Mpeketoni', 'Hindi'],
    'Machakos' => ['Machakos', 'Athi River', 'Mavoko', 'Kangundo', 'Matuu'],
    'Makueni' => ['Wote', 'Emali', 'Kibwezi'],
    'Mandera' => ['Mandera', 'Elwak', 'Rhamu'],
    'Marsabit' => ['Marsabit', 'Moyale', 'North Horr', 'Laisamis'],
    'Meru' => ['Meru', 'Maua', 'Nkubu', 'Chuka'],
    'Migori' => ['Migori', 'Kehancha', 'Rongo'],
    'Mombasa' => ['Mombasa CBD', 'Nyali', 'Bamburi', 'Likoni', 'Changamwe'],
    'Muranga' => ['Muranga', 'Kangema', 'Kenyatta'],
    'Nairobi' => ['Nairobi CBD', 'Westlands', 'Kilimani', 'Karen', 'Langata', 'Eastlands', 'South B', 'South C', 'Embakasi', 'Ruaraka'],
    'Nakuru' => ['Nakuru', 'Naivasha', 'Gilgil', 'Molo', 'Njoro'],
    'Nandi' => ['Kapsabet', 'Nandi Hills', 'Kobujoi'],
    'Narok' => ['Narok', 'Mai Mahiu', 'Kilgoris'],
    'Nyamira' => ['Nyamira', 'Nyansiongo', 'Keroka'],
    'Nyandarua' => ['Ol Kalou', 'Kinamba', 'Engineer'],
    'Nyeri' => ['Nyeri', 'Karatina', 'Othaya', 'Mweiga'],
    'Samburu' => ['Maralal', 'Baragoi', 'Archers Post'],
    'Siaya' => ['Siaya', 'Bondo', 'Ugunja'],
    'Taita Taveta' => ['Voi', 'Taveta', 'Wundanyi'],
    'Tana River' => ['Hola', 'Garsen', 'Madogo'],
    'Tharaka Nithi' => ['Chuka', 'Chogoria', 'Marimanti'],
    'Trans Nzoia' => ['Kitale', 'Kwanza', 'Endebess'],
    'Turkana' => ['Lodwar', 'Lokichoggio', 'Kakuma'],
    'Uasin Gishu' => ['Eldoret', 'Moiben', 'Turbo'],
    'Vihiga' => ['Vihiga', 'Luanda', 'Majengo'],
    'Wajir' => ['Wajir', 'Habaswein', 'Buna'],
    'West Pokot' => ['Kapenguria', 'Kacheliba', 'Ortum'],
];
?>

<?php if ($orderPlaced): ?>
    <?php require_once __DIR__ . '/../config/mpesa.php'; ?>
    <div class="max-w-xl mx-auto py-8 space-y-8" id="paymentScreen">
        <div class="bg-white border border-neutral-200 p-8 text-center space-y-6">
            <div class="w-16 h-16 bg-black rounded-full flex items-center justify-center mx-auto">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <h2 class="text-xl font-bold text-black">Complete Payment</h2>
            <p class="text-xs text-neutral-600">Pay via M-Pesa to confirm your order</p>

            <div class="bg-neutral-50 p-4 text-left text-xs font-mono space-y-1 border border-neutral-200">
                <div><span class="text-neutral-500">Order Ref:</span> <span class="font-bold text-black"><?= escape($orderRef) ?></span></div>
                <div><span class="text-neutral-500">Amount:</span> <span class="font-bold text-black">Ksh <?= number_format($finalTotal) ?>.00</span></div>
                <div><span class="text-neutral-500">Phone:</span> <span class="font-bold text-black">+<?= escape($phone) ?></span></div>
                <div><span class="text-neutral-500">Delivery:</span> <span class="font-bold text-black"><?= escape($deliveryLocation ?? '') ?></span></div>
            </div>

            <div id="mpesaStatus" class="space-y-4">
                <button id="payBtn" onclick="initiatePayment(<?= $pendingOrderId ?>)" class="w-full bg-black text-white text-xs font-bold uppercase py-4 hover:bg-neutral-800 transition-colors cursor-pointer">
                    Pay Ksh <?= number_format($finalTotal) ?> with M-Pesa
                </button>
                <p class="text-[10px] text-neutral-400">You will receive an STK Push prompt on your phone. Enter your M-Pesa PIN to confirm.</p>
            </div>

            <div id="mpesaProgress" class="hidden space-y-4">
                <div class="flex items-center justify-center gap-3">
                    <svg class="w-6 h-6 text-black animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                    <span class="text-sm font-semibold text-black">Checking your phone for M-Pesa prompt...</span>
                </div>
                <p class="text-xs text-neutral-500">Check your phone and enter your M-Pesa PIN to complete payment.</p>
                <p class="text-[10px] text-neutral-400">Waiting for confirmation... <span id="pollCount">15</span>s</p>
                <div class="bg-neutral-100 h-1 w-full"><div id="pollBar" class="bg-black h-1 transition-all" style="width: 100%"></div></div>
            </div>

            <div id="mpesaSuccess" class="hidden space-y-4">
                <div class="w-12 h-12 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mx-auto">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                </div>
                <h3 class="font-bold text-black">Payment Successful!</h3>
                <p class="text-xs text-neutral-600">Your order has been confirmed. You will receive delivery updates via SMS.</p>
                <a href="/?page=order-tracking&ref=<?= urlencode($orderRef) ?>" class="block w-full bg-black text-white text-xs font-bold uppercase py-3 hover:bg-neutral-800 no-underline">Track Your Order</a>
                <a href="/" class="block w-full bg-white border border-black text-black text-xs font-bold uppercase py-3 hover:bg-neutral-100 no-underline">Return to Store</a>
            </div>

            <div id="mpesaFailed" class="hidden space-y-4">
                <div class="w-12 h-12 bg-red-100 text-red-600 rounded-full flex items-center justify-center mx-auto">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </div>
                <h3 class="font-bold text-black">Payment Failed</h3>
                <p id="mpesaFailMsg" class="text-xs text-neutral-600">The transaction could not be completed.</p>
                <button onclick="retryPayment(<?= $pendingOrderId ?>)" class="w-full bg-black text-white text-xs font-bold uppercase py-3 hover:bg-neutral-800 transition-colors cursor-pointer">Try Again</button>
                <a href="/?page=order-tracking&ref=<?= urlencode($orderRef) ?>" class="block text-xs font-semibold text-black underline">Check Order Status</a>
            </div>
        </div>
    </div>

    <script>
    let pollTimer = null;
    let pollSeconds = 15;
    let orderId = <?= $pendingOrderId ?>;

    function initiatePayment(id) {
        document.getElementById('payBtn').disabled = true;
        document.getElementById('payBtn').textContent = 'Processing...';

        const f = new FormData();
        f.append('order_id', id);
        f.append('phone', '<?= preg_replace('/[^0-9]/', '', $phone) ?>');
        f.append('amount', <?= $finalTotal ?>);

        fetch('/ajax/mpesa.php', { method: 'POST', body: f })
            .then(r => {
                if (!r.ok) throw new Error('Server error ' + r.status);
                return r.json();
            })
            .then(d => {
                if (d.success) {
                    document.getElementById('mpesaStatus').classList.add('hidden');
                    document.getElementById('mpesaProgress').classList.remove('hidden');
                    startPolling(id);
                } else {
                    document.getElementById('payBtn').disabled = false;
                    document.getElementById('payBtn').textContent = 'Pay Ksh <?= number_format($finalTotal) ?> with M-Pesa';
                    document.getElementById('mpesaFailMsg').textContent = d.message;
                    document.getElementById('mpesaStatus').classList.add('hidden');
                    document.getElementById('mpesaFailed').classList.remove('hidden');
                }
            })
            .catch(e => {
                document.getElementById('payBtn').disabled = false;
                document.getElementById('payBtn').textContent = 'Pay Ksh <?= number_format($finalTotal) ?> with M-Pesa';
                document.getElementById('mpesaFailMsg').textContent = 'Could not reach payment server. Please try again.';
                document.getElementById('mpesaStatus').classList.add('hidden');
                document.getElementById('mpesaFailed').classList.remove('hidden');
            });
    }

    function startPolling(id) {
        pollSeconds = 15;
        document.getElementById('pollCount').textContent = pollSeconds;
        document.getElementById('pollBar').style.width = '100%';

        pollTimer = setInterval(() => {
            pollSeconds--;
            document.getElementById('pollCount').textContent = pollSeconds;
            document.getElementById('pollBar').style.width = (pollSeconds / 15 * 100) + '%';

            if (pollSeconds <= 0) {
                clearInterval(pollTimer);
                checkPaymentStatus(id);
            }
        }, 1000);
    }

    function checkPaymentStatus(id) {
        fetch('/?page=order-tracking&ref=<?= urlencode($orderRef) ?>&ajax=1')
            .then(r => r.json())
            .then(d => {
                if (d.status === 'confirmed' || d.status === 'processing' || d.status === 'shipped' || d.status === 'delivered') {
                    clearInterval(pollTimer);
                    document.getElementById('mpesaProgress').classList.add('hidden');
                    document.getElementById('mpesaSuccess').classList.remove('hidden');
                } else if (d.status === 'cancelled') {
                    clearInterval(pollTimer);
                    document.getElementById('mpesaProgress').classList.add('hidden');
                    document.getElementById('mpesaFailed').classList.remove('hidden');
                    document.getElementById('mpesaFailMsg').textContent = d.mpesa_result || 'Payment was cancelled.';
                } else {
                    startPolling(id);
                }
            });
    }

    function retryPayment(id) {
        document.getElementById('mpesaFailed').classList.add('hidden');
        document.getElementById('mpesaStatus').classList.remove('hidden');
        document.getElementById('payBtn').disabled = false;
        document.getElementById('payBtn').textContent = 'Pay Ksh <?= number_format($finalTotal) ?> with M-Pesa';
    }
    </script>
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
                    <div class="text-[10px] font-bold text-neutral-400 uppercase tracking-wider">DELIVERY</div>
                    <h2 class="text-sm font-bold text-black">How would you like to receive your order?</h2>

                    <div class="flex border border-neutral-300 text-xs font-semibold">
                        <button type="button" onclick="setDeliveryMethod('delivery')" id="deliveryTab" class="flex-1 py-3 px-4 bg-black text-white transition-colors cursor-pointer">Delivery</button>
                        <button type="button" onclick="setDeliveryMethod('pickup')" id="pickupTab" class="flex-1 py-3 px-4 bg-white text-neutral-600 hover:text-black transition-colors cursor-pointer">Shop Pickup</button>
                    </div>

                    <input type="hidden" name="delivery_method" id="deliveryMethod" value="delivery" />
                    <input type="hidden" name="delivery_location" id="deliveryLocation" value="" />

                    <!-- Delivery -->
                    <div id="deliverySection">
                        <label class="text-[11px] font-semibold text-neutral-600 block mb-1">County / Town</label>
                        <div class="relative">
                            <input type="text" id="locationSearch" placeholder="Search for your county or town..." autocomplete="off" class="w-full bg-white border border-neutral-300 px-3 py-2 text-xs focus:border-black focus:outline-none" />
                            <div id="locationResults" class="absolute top-full left-0 right-0 bg-white border border-neutral-200 max-h-48 overflow-y-auto hidden z-10 shadow-md"></div>
                        </div>
                        <p class="text-[10px] text-neutral-400 mt-1">Start typing your county or town name</p>

                        <div id="deliveryInstructionsSection" class="hidden mt-4">
                            <label class="text-[11px] font-semibold text-neutral-600 block mb-1">Delivery Instructions (optional)</label>
                            <textarea name="delivery_instructions" rows="2" placeholder="E.g. Leave at reception, call when nearby..." class="w-full bg-white border border-neutral-300 px-3 py-2 text-xs focus:border-black focus:outline-none"><?= escape($_POST['delivery_instructions'] ?? '') ?></textarea>
                        </div>
                    </div>

                    <!-- Pickup -->
                    <div id="pickupSection" class="hidden">
                        <label class="text-[11px] font-semibold text-neutral-600 block mb-1">Pickup Location</label>
                        <select name="pickup_location" id="pickupLocation" onchange="document.getElementById('deliveryLocation').value='Pickup: '+this.options[this.selectedIndex].text; document.getElementById('deliveryInstructionsSection').classList.remove('hidden');" class="w-full bg-white border border-neutral-300 px-3 py-2 text-xs focus:border-black focus:outline-none">
                            <option value="">Select a pickup location</option>
                            <?php foreach ($shopLocations as $loc): ?>
                                <option value="<?= $loc['id'] ?>"><?= escape($loc['name']) ?> &mdash; <?= escape($loc['address']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (empty($shopLocations)): ?>
                            <p class="text-[10px] text-amber-600 mt-1">No pickup locations available yet. Please choose delivery.</p>
                        <?php endif; ?>
                    </div>
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
                    <button type="submit" name="place_order" value="1" <?= empty($cartItems) ? 'disabled' : '' ?> class="w-full bg-black hover:bg-neutral-800 text-white font-bold text-xs uppercase py-4 transition-colors text-center cursor-pointer">
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

                    <p class="text-[10px] text-neutral-400 pt-2 border-t border-neutral-100">This checkout locks prices until <?= date('M d, H:i', strtotime('+30 minutes')) ?>.</p>
                </div>
            </div>
        </form>
    </div>

<script>
// Kenyan counties data
const counties = <?= json_encode($counties) ?>;

function setDeliveryMethod(method) {
    document.getElementById('deliveryMethod').value = method;
    document.getElementById('deliveryLocation').value = '';
    document.getElementById('deliveryInstructionsSection').classList.add('hidden');
    if (method === 'delivery') {
        document.getElementById('deliveryTab').className = 'flex-1 py-3 px-4 bg-black text-white transition-colors cursor-pointer';
        document.getElementById('pickupTab').className = 'flex-1 py-3 px-4 bg-white text-neutral-600 hover:text-black transition-colors cursor-pointer';
        document.getElementById('deliverySection').classList.remove('hidden');
        document.getElementById('pickupSection').classList.add('hidden');
    } else {
        document.getElementById('pickupTab').className = 'flex-1 py-3 px-4 bg-black text-white transition-colors cursor-pointer';
        document.getElementById('deliveryTab').className = 'flex-1 py-3 px-4 bg-white text-neutral-600 hover:text-black transition-colors cursor-pointer';
        document.getElementById('deliverySection').classList.add('hidden');
        document.getElementById('pickupSection').classList.remove('hidden');
    }
}

// Autocomplete
const searchInput = document.getElementById('locationSearch');
const resultsEl = document.getElementById('locationResults');

searchInput.addEventListener('input', function() {
    const query = this.value.toLowerCase().trim();
    if (query.length < 1) {
        resultsEl.classList.add('hidden');
        resultsEl.innerHTML = '';
        return;
    }

    let matches = [];
    for (const [county, towns] of Object.entries(counties)) {
        const countyLower = county.toLowerCase();
        if (countyLower.includes(query)) {
            towns.forEach(town => matches.push({ county, town }));
        } else {
            towns.forEach(town => {
                if (town.toLowerCase().includes(query)) {
                    matches.push({ county, town });
                }
            });
        }
    }

    matches = matches.slice(0, 10);

    if (matches.length === 0) {
        resultsEl.innerHTML = '<div class="p-2 text-neutral-400 text-xs">No locations found</div>';
        resultsEl.classList.remove('hidden');
        return;
    }

    resultsEl.innerHTML = matches.map(m =>
        '<div class="p-2 hover:bg-neutral-100 cursor-pointer text-xs border-b border-neutral-100" onclick="selectLocation(\'' + m.county + '\', \'' + m.town + '\')">' +
        '<span class="font-semibold text-black">' + m.county + '</span> <span class="text-neutral-500">&mdash;</span> <span>' + m.town + '</span>' +
        '</div>'
    ).join('');
    resultsEl.classList.remove('hidden');
});

function selectLocation(county, town) {
    const label = county + ' - ' + town;
    searchInput.value = label;
    document.getElementById('deliveryLocation').value = label;
    resultsEl.classList.add('hidden');
    document.getElementById('deliveryInstructionsSection').classList.remove('hidden');
}

document.addEventListener('click', function(e) {
    if (!document.querySelector('#deliverySection .relative').contains(e.target)) {
        resultsEl.classList.add('hidden');
    }
});
</script>
<?php endif; ?>
