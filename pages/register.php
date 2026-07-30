<?php
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (empty($firstName) || empty($lastName) || empty($email) || empty($phone) || empty($password)) {
        $error = 'Please fill in all required fields.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'An account with this email already exists.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, email, phone, password) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$firstName, $lastName, $email, $phone, $hash]);

            $userId = $pdo->lastInsertId();
            $_SESSION['user_id'] = $userId;
            $_SESSION['first_name'] = $firstName;
            $_SESSION['last_name'] = $lastName;
            $_SESSION['email'] = $email;
            $_SESSION['phone'] = $phone;
            $_SESSION['is_admin'] = 0;

            header('Location: /');
            exit;
        }
    }
}
?>

<div class="max-w-md mx-auto py-12">
    <div class="bg-white border border-neutral-200 p-8 space-y-6">
        <div class="text-center space-y-2">
            <h1 class="text-xl font-black uppercase tracking-widest">Create Account</h1>
            <p class="text-xs text-neutral-500">Join HAPPUTY ORGANICS today</p>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-50 border border-red-200 p-3 text-xs text-red-700 font-semibold"><?= escape($error) ?></div>
        <?php endif; ?>

        <form method="POST" class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="text-[11px] font-semibold text-neutral-600 block mb-1">First name</label>
                    <input type="text" name="first_name" placeholder="First name" required value="<?= escape($_POST['first_name'] ?? '') ?>" class="w-full bg-white border border-neutral-300 px-3 py-2.5 text-xs focus:border-black focus:outline-none" />
                </div>
                <div>
                    <label class="text-[11px] font-semibold text-neutral-600 block mb-1">Last name</label>
                    <input type="text" name="last_name" placeholder="Last name" required value="<?= escape($_POST['last_name'] ?? '') ?>" class="w-full bg-white border border-neutral-300 px-3 py-2.5 text-xs focus:border-black focus:outline-none" />
                </div>
            </div>
            <div>
                <label class="text-[11px] font-semibold text-neutral-600 block mb-1">Email</label>
                <input type="email" name="email" placeholder="you@example.com" required value="<?= escape($_POST['email'] ?? '') ?>" class="w-full bg-white border border-neutral-300 px-3 py-2.5 text-xs focus:border-black focus:outline-none" />
            </div>
            <div>
                <label class="text-[11px] font-semibold text-neutral-600 block mb-1">Phone</label>
                <input type="text" name="phone" placeholder="+254 700 000 000" required value="<?= escape($_POST['phone'] ?? '') ?>" class="w-full bg-white border border-neutral-300 px-3 py-2.5 text-xs font-mono focus:border-black focus:outline-none" />
            </div>
            <div>
                <label class="text-[11px] font-semibold text-neutral-600 block mb-1">Password</label>
                <input type="password" name="password" placeholder="Min. 6 characters" required class="w-full bg-white border border-neutral-300 px-3 py-2.5 text-xs focus:border-black focus:outline-none" />
            </div>
            <div>
                <label class="text-[11px] font-semibold text-neutral-600 block mb-1">Confirm Password</label>
                <input type="password" name="confirm_password" placeholder="Repeat password" required class="w-full bg-white border border-neutral-300 px-3 py-2.5 text-xs focus:border-black focus:outline-none" />
            </div>
            <button type="submit" class="w-full bg-black text-white text-xs font-bold uppercase py-3 hover:bg-neutral-800 transition-colors cursor-pointer">Create Account</button>
        </form>

        <p class="text-xs text-neutral-500 text-center">
            Already have an account? <a href="/?page=login" class="text-black font-bold underline">Sign In</a>
        </p>
    </div>
</div>
