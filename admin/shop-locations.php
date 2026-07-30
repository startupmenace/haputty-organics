<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$message = '';
$error = '';

// Delete
if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM shop_locations WHERE id = ?")->execute([(int)$_GET['delete']]);
    $message = 'Shop location deleted.';
    header('Location: /admin/shop-locations.php?message=' . urlencode($message));
    exit;
}

// Add / Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_location'])) {
    $id = (int)($_POST['location_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $isActive = isset($_POST['is_active']) ? 1 : 0;

    if (empty($name) || empty($address)) {
        $error = 'Name and address are required.';
    } else {
        if ($id > 0) {
            $pdo->prepare("UPDATE shop_locations SET name=?, address=?, is_active=? WHERE id=?")->execute([$name, $address, $isActive, $id]);
            $message = 'Location updated.';
        } else {
            $pdo->prepare("INSERT INTO shop_locations (name, address, is_active) VALUES (?, ?, ?)")->execute([$name, $address, $isActive]);
            $message = 'Location added.';
        }
    }
}

$editLocation = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM shop_locations WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $editLocation = $stmt->fetch();
}

$locations = $pdo->query("SELECT * FROM shop_locations ORDER BY id")->fetchAll();

require_once __DIR__ . '/header.php';
?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-black uppercase tracking-widest">Shop Locations</h1>
        <button onclick="document.getElementById('locationForm').classList.toggle('hidden'); document.getElementById('formTitle').textContent='Add Location'; document.getElementById('locationId').value='0';" class="bg-black text-white text-xs font-bold uppercase px-4 py-2.5 hover:bg-neutral-800 cursor-pointer">
            + Add Location
        </button>
    </div>

    <?php if ($message): ?>
        <div class="bg-green-50 border border-green-200 p-3 text-xs text-green-700 font-semibold"><?= escape($message) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="bg-red-50 border border-red-200 p-3 text-xs text-red-700 font-semibold"><?= escape($error) ?></div>
    <?php endif; ?>

    <div id="locationForm" class="bg-white border border-neutral-200 p-6 <?= $editLocation ? '' : 'hidden' ?> space-y-4">
        <h2 id="formTitle" class="text-sm font-bold uppercase"><?= $editLocation ? 'Edit Location' : 'Add Location' ?></h2>
        <form method="POST" class="space-y-4">
            <input type="hidden" name="location_id" id="locationId" value="<?= $editLocation['id'] ?? 0 ?>" />
            <div>
                <label class="text-[11px] font-semibold text-neutral-600 block mb-1">Location Name</label>
                <input type="text" name="name" value="<?= escape($editLocation['name'] ?? '') ?>" required class="w-full border border-neutral-300 px-3 py-2 text-xs focus:border-black focus:outline-none" />
            </div>
            <div>
                <label class="text-[11px] font-semibold text-neutral-600 block mb-1">Address / Description</label>
                <textarea name="address" rows="2" required class="w-full border border-neutral-300 px-3 py-2 text-xs focus:border-black focus:outline-none"><?= escape($editLocation['address'] ?? '') ?></textarea>
            </div>
            <label class="flex items-center gap-2 text-xs">
                <input type="checkbox" name="is_active" <?= !isset($editLocation) || $editLocation['is_active'] ? 'checked' : '' ?> class="accent-black" />
                Active
            </label>
            <div class="flex gap-3">
                <button type="submit" name="save_location" class="bg-black text-white text-xs font-bold uppercase py-2.5 px-6 hover:bg-neutral-800 cursor-pointer">
                    <?= $editLocation ? 'Update' : 'Add' ?>
                </button>
                <button type="button" onclick="document.getElementById('locationForm').classList.add('hidden')" class="bg-white border border-black text-black text-xs font-bold uppercase py-2.5 px-6 hover:bg-neutral-100 cursor-pointer">
                    Cancel
                </button>
            </div>
        </form>
    </div>

    <div class="bg-white border border-neutral-200 overflow-x-auto">
        <table class="w-full text-xs">
            <thead>
                <tr class="bg-neutral-50 text-neutral-500 font-bold uppercase text-[10px]">
                    <th class="text-left p-3">ID</th>
                    <th class="text-left p-3">Name</th>
                    <th class="text-left p-3">Address</th>
                    <th class="text-left p-3">Status</th>
                    <th class="text-left p-3">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($locations as $loc): ?>
                    <tr class="border-t border-neutral-100">
                        <td class="p-3 font-mono">#<?= $loc['id'] ?></td>
                        <td class="p-3 font-semibold"><?= escape($loc['name']) ?></td>
                        <td class="p-3 text-neutral-600 max-w-md"><?= escape($loc['address']) ?></td>
                        <td class="p-3"><?= $loc['is_active'] ? '<span class="text-green-600 font-bold">Active</span>' : '<span class="text-red-600 font-bold">Inactive</span>' ?></td>
                        <td class="p-3">
                            <a href="/admin/shop-locations.php?edit=<?= $loc['id'] ?>" class="text-black font-bold underline mr-3">Edit</a>
                            <a href="/admin/shop-locations.php?delete=<?= $loc['id'] ?>" onclick="return confirm('Delete this location?')" class="text-red-600 font-bold underline">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($locations)): ?>
                    <tr><td colspan="5" class="p-6 text-center text-neutral-400">No shop locations yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
