<?php
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please enter email and password.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['first_name'] = $user['first_name'];
            $_SESSION['last_name'] = $user['last_name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['phone'] = $user['phone'];
            $_SESSION['is_admin'] = $user['is_admin'];

            // Merge guest cart into user cart
            if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
                foreach ($_SESSION['cart'] as $item) {
                    $stmt = $pdo->prepare("SELECT id FROM cart WHERE user_id = ? AND product_id = ? AND color = ?");
                    $stmt->execute([$user['id'], $item['product_id'], $item['color'] ?? 'Standard']);
                    if ($existing = $stmt->fetch()) {
                        $pdo->prepare("UPDATE cart SET quantity = quantity + ? WHERE id = ?")->execute([$item['quantity'], $existing['id']]);
                    } else {
                        $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity, color) VALUES (?, ?, ?, ?)")->execute([$user['id'], $item['product_id'], $item['quantity'], $item['color'] ?? 'Standard']);
                    }
                }
                unset($_SESSION['cart']);
            }

            // Merge guest wishlist
            if (isset($_SESSION['wishlist']) && is_array($_SESSION['wishlist'])) {
                foreach ($_SESSION['wishlist'] as $prodId) {
                    $stmt = $pdo->prepare("SELECT id FROM wishlists WHERE user_id = ? AND product_id = ?");
                    $stmt->execute([$user['id'], $prodId]);
                    if (!$stmt->fetch()) {
                        $pdo->prepare("INSERT INTO wishlists (user_id, product_id) VALUES (?, ?)")->execute([$user['id'], $prodId]);
                    }
                }
                unset($_SESSION['wishlist']);
            }

            header('Location: ' . ($user['is_admin'] ? '/admin/' : '/'));
            exit;
        } else {
            $error = 'Invalid email or password.';
        }
    }
}
?>

<div class="max-w-md mx-auto py-12">
    <div class="bg-white border border-neutral-200 p-8 space-y-6">
        <div class="text-center space-y-2">
            <h1 class="text-xl font-black uppercase tracking-widest">Sign In</h1>
            <p class="text-xs text-neutral-500">Welcome back to HAPPUTY ORGANICS</p>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-50 border border-red-200 p-3 text-xs text-red-700 font-semibold"><?= escape($error) ?></div>
        <?php endif; ?>

        <form method="POST" class="space-y-4">
            <div>
                <label class="text-[11px] font-semibold text-neutral-600 block mb-1">Email</label>
                <input type="email" name="email" placeholder="you@example.com" required class="w-full bg-white border border-neutral-300 px-3 py-2.5 text-xs focus:border-black focus:outline-none" />
            </div>
            <div>
                <label class="text-[11px] font-semibold text-neutral-600 block mb-1">Password</label>
                <input type="password" name="password" placeholder="Enter your password" required class="w-full bg-white border border-neutral-300 px-3 py-2.5 text-xs focus:border-black focus:outline-none" />
            </div>
            <button type="submit" class="w-full bg-black text-white text-xs font-bold uppercase py-3 hover:bg-neutral-800 transition-colors cursor-pointer">Sign In</button>
        </form>

        <p class="text-xs text-neutral-500 text-center">
            Don't have an account? <a href="/?page=register" class="text-black font-bold underline">Create Account</a>
        </p>
    </div>
</div>
