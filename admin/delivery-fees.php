<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$message = '';
$error = '';

// Auto-create table with parent_id
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS delivery_fees (
        id INT AUTO_INCREMENT PRIMARY KEY,
        region VARCHAR(255) NOT NULL,
        fee DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        is_active TINYINT(1) DEFAULT 1,
        parent_id INT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    $pdo->exec("ALTER TABLE delivery_fees ADD COLUMN parent_id INT DEFAULT NULL");
} catch (Exception $e) {}

// Kenyan counties with towns
$allCounties = [
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
    'Nyeri' => ['Nyeri', 'Othaya', 'Karatina', 'Mukurweini'],
    'Samburu' => ['Maralal', 'Baragoi', 'Archers Post'],
    'Siaya' => ['Siaya', 'Bondo', 'Ugunja', 'Ukwala'],
    'Taita Taveta' => ['Voi', 'Taveta', 'Wundanyi'],
    'Tana River' => ['Hola', 'Madogo', 'Garsen'],
    'Tharaka Nithi' => ['Chuka', 'Marimanti', 'Kathwana'],
    'Trans Nzoia' => ['Kitale', 'Kiminini', 'Kwanza'],
    'Turkana' => ['Lodwar', 'Lokichoggio', 'Kakuma'],
    'Uasin Gishu' => ['Eldoret', 'Iten', 'Moiben'],
    'Vihiga' => ['Vihiga', 'Luanda', 'Chavakali'],
    'Wajir' => ['Wajir', 'Habaswein', 'Buna'],
    'West Pokot' => ['Kapenguria', 'Kacheliba', 'Ortum'],
];

// Handle save county fee
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_county'])) {
    $id = (int)($_POST['county_id'] ?? 0);
    $region = trim($_POST['region'] ?? '');
    $fee = (float)($_POST['fee'] ?? 0);

    if (empty($region) || $fee < 0) {
        $error = 'Invalid input.';
    } else {
        if ($id > 0) {
            $pdo->prepare("UPDATE delivery_fees SET region=?, fee=? WHERE id=?")->execute([$region, $fee, $id]);
        } else {
            // Check if county already exists
            $stmt = $pdo->prepare("SELECT id FROM delivery_fees WHERE parent_id IS NULL AND region = ?");
            $stmt->execute([$region]);
            $existing = $stmt->fetchColumn();
            if ($existing) {
                $pdo->prepare("UPDATE delivery_fees SET fee=? WHERE id=?")->execute([$fee, $existing]);
            } else {
                $pdo->prepare("INSERT INTO delivery_fees (region, fee, parent_id) VALUES (?, ?, NULL)")->execute([$region, $fee]);
            }
        }
        $message = 'County fee saved.';
    }
}

// Handle save town fee
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_town'])) {
    $countyId = (int)($_POST['county_id'] ?? 0);
    $townName = trim($_POST['town_name'] ?? '');
    $fee = (float)($_POST['fee'] ?? 0);

    if (!$countyId || empty($townName)) {
        $error = 'Invalid town data.';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM delivery_fees WHERE parent_id = ? AND region = ?");
        $stmt->execute([$countyId, $townName]);
        $existing = $stmt->fetchColumn();
        if ($existing) {
            $pdo->prepare("UPDATE delivery_fees SET fee=? WHERE id=?")->execute([$fee, $existing]);
        } else {
            $pdo->prepare("INSERT INTO delivery_fees (region, fee, parent_id) VALUES (?, ?, ?)")->execute([$townName, $fee, $countyId]);
        }
        $message = 'Town fee saved.';
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $pdo->prepare("DELETE FROM delivery_fees WHERE id = ? OR parent_id = ?")->execute([$id, $id]);
    header('Location: /admin/delivery-fees.php');
    exit;
}

// Handle toggle
if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $stmt = $pdo->prepare("SELECT is_active FROM delivery_fees WHERE id = ?");
    $stmt->execute([$id]);
    $pdo->prepare("UPDATE delivery_fees SET is_active = ? WHERE id = ?")->execute([$stmt->fetchColumn() ? 0 : 1, $id]);
    header('Location: /admin/delivery-fees.php');
    exit;
}

// Get all counties (parent_id IS NULL)
$counties = $pdo->query("SELECT * FROM delivery_fees WHERE parent_id IS NULL ORDER BY region")->fetchAll();

// Get all towns (parent_id IS NOT NULL), grouped by county_id
$allTowns = $pdo->query("SELECT * FROM delivery_fees WHERE parent_id IS NOT NULL ORDER BY region")->fetchAll();
$townsByCounty = [];
foreach ($allTowns as $t) $townsByCounty[$t['parent_id']][] = $t;

require_once __DIR__ . '/header.php';
?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-black uppercase tracking-widest">Delivery Fees</h1>
    </div>

    <?php if ($message): ?>
        <div class="bg-green-50 border border-green-200 p-3 text-xs text-green-700 font-semibold"><?= escape($message) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="bg-red-50 border border-red-200 p-3 text-xs text-red-700 font-semibold"><?= escape($error) ?></div>
    <?php endif; ?>

    <p class="text-xs text-neutral-500">Set delivery fees per county and per town. Towns inherit the county's default fee unless overridden.</p>

    <!-- County / Town Fees -->
    <div class="bg-white border border-neutral-200 overflow-x-auto">
        <table class="w-full text-xs">
            <thead>
                <tr class="bg-neutral-50 text-neutral-500 font-bold uppercase text-[10px]">
                    <th class="text-left p-3">Region</th>
                    <th class="text-left p-3">Fee (Ksh)</th>
                    <th class="text-left p-3">Status</th>
                    <th class="text-left p-3 w-60">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($allCounties as $countyName => $towns): ?>
                    <?php
                    $dbCounty = null;
                    foreach ($counties as $c) {
                        if (strtolower($c['region']) === strtolower($countyName)) {
                            $dbCounty = $c;
                            break;
                        }
                    }
                    $countyId = $dbCounty['id'] ?? 0;
                    $countyFee = $dbCounty['fee'] ?? 0;
                    $countyActive = $dbCounty['is_active'] ?? 1;
                    $townFees = $townsByCounty[$countyId] ?? [];
                    $existingTownNames = array_map(fn($t) => strtolower($t['region']), $townFees);
                    ?>
                    <tr class="border-t border-neutral-100">
                        <td class="p-3 font-bold text-black">
                            <button onclick="toggleTowns(<?= $countyId ?>)" class="text-neutral-400 hover:text-black cursor-pointer mr-1 text-xs" id="chevron_<?= $countyId ?>">&#9654;</button>
                            <?= escape($countyName) ?>
                        </td>
                        <td class="p-3 font-mono font-bold">
                            <form method="POST" class="flex items-center gap-1">
                                <input type="hidden" name="region" value="<?= escape($countyName) ?>" />
                                <input type="hidden" name="county_id" value="<?= $countyId ?>" />
                                <input type="number" name="fee" step="0.01" min="0" value="<?= $countyFee ?>" class="w-20 border border-neutral-300 px-2 py-1 text-[11px] font-mono focus:border-black focus:outline-none" />
                                <button type="submit" name="save_county" class="bg-black text-white px-2 py-1 text-[10px] font-bold uppercase hover:bg-neutral-800 cursor-pointer">Set</button>
                            </form>
                        </td>
                        <td class="p-3">
                            <?php if ($dbCounty): ?>
                                <a href="/admin/delivery-fees.php?toggle=<?= $countyId ?>" class="underline <?= $countyActive ? 'text-green-600' : 'text-red-600' ?> font-bold"><?= $countyActive ? 'Active' : 'Inactive' ?></a>
                            <?php else: ?>
                                <span class="text-neutral-400">Not set</span>
                            <?php endif; ?>
                        </td>
                        <td class="p-3">
                            <?php if ($dbCounty): ?>
                                <button onclick="showTownForm(<?= $countyId ?>, '<?= escape($countyName) ?>')" class="text-black font-bold underline mr-2 cursor-pointer bg-transparent border-none text-[11px]">+ Add Town Fee</button>
                                <a href="/admin/delivery-fees.php?delete=<?= $countyId ?>" onclick="return confirm('Delete all fees for <?= escape($countyName) ?>?')" class="text-red-600 font-bold underline text-[11px]">Delete</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <!-- Town rows (hidden by default) -->
                    <tr id="towns_<?= $countyId ?>" class="hidden">
                        <td colspan="4" class="p-0">
                            <div class="bg-neutral-50 p-3 space-y-2">
                                <?php if (!empty($towns)): ?>
                                    <div class="flex flex-wrap gap-1.5 mb-2">
                                        <?php foreach ($towns as $town): $townExists = in_array(strtolower($town), $existingTownNames); ?>
                                            <form method="POST" class="flex items-center gap-1 border border-neutral-200 bg-white px-2 py-1 <?= $townExists ? 'opacity-60' : '' ?>">
                                                <input type="hidden" name="county_id" value="<?= $countyId ?>" />
                                                <input type="hidden" name="town_name" value="<?= escape($town) ?>" />
                                                <span class="text-[11px] font-semibold text-neutral-600 min-w-[100px]"><?= escape($town) ?></span>
                                                <input type="number" name="fee" step="0.01" min="0" placeholder="<?= $countyFee ?>" value="<?= $townExists ? (current(array_filter($townFees, fn($t) => strtolower($t['region']) === strtolower($town))))['fee'] : '' ?>" class="w-20 border border-neutral-300 px-2 py-1 text-[11px] font-mono focus:border-black focus:outline-none" />
                                                <button type="submit" name="save_town" class="bg-black text-white px-2 py-1 text-[10px] font-bold uppercase hover:bg-neutral-800 cursor-pointer">Set</button>
                                                <?php if ($townExists): $townRow = current(array_filter($townFees, fn($t) => strtolower($t['region']) === strtolower($town))); ?>
                                                    <a href="/admin/delivery-fees.php?delete=<?= $townRow['id'] ?>" onclick="return confirm('Delete fee for <?= escape($town) ?>?')" class="text-red-600 font-bold text-[11px] ml-1 underline">&times;</a>
                                                <?php endif; ?>
                                            </form>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                                <?php if (empty($townFees) && !empty($towns)): ?>
                                    <p class="text-[11px] text-neutral-400">Set individual fees for each town above. Towns with no fee will use the county default (Ksh <?= number_format($countyFee) ?>).</p>
                                <?php endif; ?>
                                <?php if (!empty($townFees)): ?>
                                    <p class="text-[11px] text-neutral-400">Towns with a set fee override the county default (Ksh <?= number_format($countyFee) ?>).</p>
                                <?php endif; ?>
                                <?php if (empty($towns)): ?>
                                    <p class="text-[11px] text-neutral-400">No towns listed for this county.</p>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Stats -->
    <div class="bg-neutral-50 border border-neutral-200 p-4 text-xs text-neutral-500">
        <?php
        $totalCounties = count(array_filter($counties, fn($c) => (bool)$c));
        $totalTowns = count($allTowns);
        ?>
        <strong><?= $totalCounties ?></strong> counties configured &middot; <strong><?= $totalTowns ?></strong> towns with custom fees
        &middot; Counties without a fee set will charge <strong>Ksh 0.00</strong> (free delivery).
    </div>
</div>

<script>
function toggleTowns(id) {
    const row = document.getElementById('towns_' + id);
    const chevron = document.getElementById('chevron_' + id);
    row.classList.toggle('hidden');
    chevron.innerHTML = row.classList.contains('hidden') ? '&#9654;' : '&#9660;';
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
