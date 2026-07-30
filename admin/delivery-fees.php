<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$message = '';
$error = '';

// Auto-create table
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS delivery_fees (
        id INT AUTO_INCREMENT PRIMARY KEY,
        region VARCHAR(255) NOT NULL,
        fee DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
} catch (Exception $e) {}

// Handle add/edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_fee'])) {
    $id = (int)($_POST['fee_id'] ?? 0);
    $region = trim($_POST['region'] ?? '');
    $fee = (float)($_POST['fee'] ?? 0);

    if (empty($region)) {
        $error = 'Region name is required.';
    } elseif ($fee < 0) {
        $error = 'Fee cannot be negative.';
    } else {
        if ($id > 0) {
            $pdo->prepare("UPDATE delivery_fees SET region=?, fee=? WHERE id=?")->execute([$region, $fee, $id]);
            $message = 'Fee updated.';
        } else {
            $pdo->prepare("INSERT INTO delivery_fees (region, fee) VALUES (?, ?)")->execute([$region, $fee]);
            $message = 'Fee added.';
        }
    }
}

// Handle toggle active
if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $stmt = $pdo->prepare("SELECT is_active FROM delivery_fees WHERE id = ?");
    $stmt->execute([$id]);
    $current = $stmt->fetchColumn();
    $pdo->prepare("UPDATE delivery_fees SET is_active = ? WHERE id = ?")->execute([$current ? 0 : 1, $id]);
    header('Location: /admin/delivery-fees.php');
    exit;
}

// Handle delete
if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM delivery_fees WHERE id = ?")->execute([(int)$_GET['delete']]);
    header('Location: /admin/delivery-fees.php');
    exit;
}

$editFee = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM delivery_fees WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $editFee = $stmt->fetch();
}

$fees = $pdo->query("SELECT * FROM delivery_fees ORDER BY region")->fetchAll();

require_once __DIR__ . '/header.php';
?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-black uppercase tracking-widest">Delivery Fees</h1>
        <button onclick="document.getElementById('feeForm').classList.toggle('hidden'); document.getElementById('feeFormTitle').textContent='Add Delivery Fee'; document.getElementById('feeId').value='0'; document.getElementById('region').value=''; document.getElementById('fee').value='';" class="bg-black text-white text-xs font-bold uppercase px-4 py-2.5 hover:bg-neutral-800 cursor-pointer">
            + Add Fee
        </button>
    </div>

    <?php if ($message): ?>
        <div class="bg-green-50 border border-green-200 p-3 text-xs text-green-700 font-semibold"><?= escape($message) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="bg-red-50 border border-red-200 p-3 text-xs text-red-700 font-semibold"><?= escape($error) ?></div>
    <?php endif; ?>

    <p class="text-xs text-neutral-500">Set delivery fees for different regions/counties. During checkout, the customer's delivery location is matched to a region and the fee is added to the order total.</p>

    <!-- Fee Form -->
    <div id="feeForm" class="bg-white border border-neutral-200 p-6 <?= $editFee ? '' : 'hidden' ?> space-y-4">
        <h2 id="feeFormTitle" class="text-sm font-bold uppercase"><?= $editFee ? 'Edit Delivery Fee' : 'Add Delivery Fee' ?></h2>
        <form method="POST" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <input type="hidden" name="fee_id" id="feeId" value="<?= $editFee['id'] ?? 0 ?>" />

            <div>
                <label class="text-[11px] font-semibold text-neutral-600 block mb-1">Region / County</label>
                <input type="text" name="region" id="region" value="<?= escape($editFee['region'] ?? '') ?>" placeholder="e.g. Nairobi" required class="w-full border border-neutral-300 px-3 py-2 text-xs focus:border-black focus:outline-none" />
            </div>

            <div>
                <label class="text-[11px] font-semibold text-neutral-600 block mb-1">Delivery Fee (Ksh)</label>
                <input type="number" name="fee" id="fee" step="0.01" min="0" value="<?= $editFee['fee'] ?? '' ?>" required class="w-full border border-neutral-300 px-3 py-2 text-xs focus:border-black focus:outline-none" />
            </div>

            <div class="flex items-end gap-3">
                <button type="submit" name="save_fee" class="bg-black text-white text-xs font-bold uppercase py-2.5 px-6 hover:bg-neutral-800 cursor-pointer">
                    <?= $editFee ? 'Update' : 'Add' ?>
                </button>
                <button type="button" onclick="document.getElementById('feeForm').classList.add('hidden')" class="bg-white border border-black text-black text-xs font-bold uppercase py-2.5 px-6 hover:bg-neutral-100 cursor-pointer">
                    Cancel
                </button>
            </div>
        </form>
    </div>

    <!-- Fees Table -->
    <div class="bg-white border border-neutral-200 overflow-x-auto">
        <table class="w-full text-xs">
            <thead>
                <tr class="bg-neutral-50 text-neutral-500 font-bold uppercase text-[10px]">
                    <th class="text-left p-3">Region</th>
                    <th class="text-left p-3">Fee</th>
                    <th class="text-left p-3">Status</th>
                    <th class="text-left p-3">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($fees as $f): ?>
                    <tr class="border-t border-neutral-100">
                        <td class="p-3 font-bold text-black"><?= escape($f['region']) ?></td>
                        <td class="p-3 font-mono font-bold">Ksh <?= number_format($f['fee']) ?></td>
                        <td class="p-3"><?= $f['is_active'] ? '<span class="text-green-600 font-bold">Active</span>' : '<span class="text-red-600 font-bold">Inactive</span>' ?></td>
                        <td class="p-3">
                            <a href="/admin/delivery-fees.php?edit=<?= $f['id'] ?>" class="text-black font-bold underline mr-3">Edit</a>
                            <a href="/admin/delivery-fees.php?toggle=<?= $f['id'] ?>" class="text-neutral-600 font-bold underline mr-3"><?= $f['is_active'] ? 'Deactivate' : 'Activate' ?></a>
                            <a href="/admin/delivery-fees.php?delete=<?= $f['id'] ?>" onclick="return confirm('Delete this fee?')" class="text-red-600 font-bold underline">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($fees)): ?>
                    <tr><td colspan="4" class="p-6 text-center text-neutral-400">No delivery fees set yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Quick-add from counties -->
    <div class="bg-neutral-50 border border-neutral-200 p-4">
        <h3 class="text-xs font-bold uppercase text-black mb-2">Quick-add from Kenyan counties</h3>
        <p class="text-[11px] text-neutral-500 mb-3">Click a county to pre-fill the add form with its name.</p>
        <div class="flex flex-wrap gap-1.5">
            <?php
            $counties = [
                'Baringo','Bomet','Bungoma','Busia','Elgeyo Marakwet','Embu','Garissa','Homa Bay',
                'Isiolo','Kajiado','Kakamega','Kericho','Kiambu','Kilifi','Kirinyaga','Kisii','Kisumu',
                'Kitui','Kwale','Laikipia','Lamu','Machakos','Makueni','Mandera','Marsabit','Meru',
                'Migori','Mombasa','Muranga','Nairobi','Nakuru','Nandi','Narok','Nyamira','Nyandarua',
                'Nyeri','Samburu','Siaya','Taita Taveta','Tana River','Tharaka Nithi','Trans Nzoia',
                'Turkana','Uasin Gishu','Vihiga','Wajir','West Pokot'
            ];
            $existingRegions = array_map('strtolower', array_column($fees, 'region'));
            foreach ($counties as $c):
                $exists = in_array(strtolower($c), $existingRegions);
            ?>
                <button type="button" <?= $exists ? 'disabled' : '' ?> onclick="document.getElementById('feeForm').classList.remove('hidden'); document.getElementById('feeFormTitle').textContent='Add Delivery Fee'; document.getElementById('feeId').value='0'; document.getElementById('region').value='<?= $c ?>'; document.getElementById('fee').value='';" class="px-3 py-1.5 text-[11px] font-semibold border <?= $exists ? 'bg-neutral-200 text-neutral-400 border-neutral-200 cursor-not-allowed' : 'bg-white text-black border-neutral-300 hover:bg-black hover:text-white cursor-pointer' ?> transition-colors"><?= $c ?></button>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
