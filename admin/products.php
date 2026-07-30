<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$message = '';
$error = '';
$uploadDir = __DIR__ . '/../uploads/products/';

function uploadImage($file, $uploadDir) {
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed)) return null;
    $name = 'prod_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $dest = $uploadDir . $name;
    if (move_uploaded_file($file['tmp_name'], $dest)) {
        return '/uploads/products/' . $name;
    }
    return null;
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("SELECT images FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $old = $stmt->fetchColumn();
    if ($old) {
        foreach (json_decode($old, true) ?? [] as $img) {
            if (strpos($img, '/uploads/') === 0) {
                $f = __DIR__ . '/..' . $img;
                if (file_exists($f)) unlink($f);
            }
        }
    }
    $pdo->prepare("DELETE FROM products WHERE id = ?")->execute([$id]);
    $message = 'Product deleted successfully.';
    header('Location: /admin/products.php?message=' . urlencode($message));
    exit;
}

// Handle remove uploaded image (AJAX)
if (isset($_GET['remove_image'])) {
    $id = (int)$_GET['product_id'];
    $removeUrl = $_GET['remove_image'];
    $stmt = $pdo->prepare("SELECT images FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $images = json_decode($stmt->fetchColumn() ?? '[]', true);
    $images = array_values(array_filter($images, fn($i) => $i !== $removeUrl));
    $pdo->prepare("UPDATE products SET images = ? WHERE id = ?")->execute([json_encode($images), $id]);
    if (strpos($removeUrl, '/uploads/') === 0) {
        $f = __DIR__ . '/..' . $removeUrl;
        if (file_exists($f)) unlink($f);
    }
    header('Location: /admin/products.php?edit=' . $id);
    exit;
}

// Handle add/edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_product'])) {
    $id = (int)($_POST['product_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $categoryId = (int)($_POST['category_id'] ?? 0);
    $price = (float)($_POST['price'] ?? 0);
    $originalPrice = !empty($_POST['original_price']) ? (float)$_POST['original_price'] : null;
    $rating = (float)($_POST['rating'] ?? 0);
    $reviewsCount = (int)($_POST['reviews_count'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $features = $_POST['features'] ?? '';
    $details = $_POST['details'] ?? '';
    $colors = $_POST['colors'] ?? '';
    $inStock = isset($_POST['in_stock']) ? 1 : 0;

    // Load existing images
    $existingImages = [];
    if ($id > 0) {
        $stmt = $pdo->prepare("SELECT images FROM products WHERE id = ?");
        $stmt->execute([$id]);
        $existingImages = json_decode($stmt->fetchColumn() ?? '[]', true);
    }

    // Handle URL-only images from textarea (backward compat)
    $urlImages = [];
    $imagesRaw = trim($_POST['images'] ?? '');
    if ($imagesRaw) {
        $decoded = json_decode($imagesRaw, true);
        if (is_array($decoded)) $urlImages = $decoded;
    }

    // Handle uploaded files
    $uploadedUrls = [];
    if (!empty($_FILES['product_images']['name'][0])) {
        foreach ($_FILES['product_images']['tmp_name'] as $i => $tmp) {
            if ($_FILES['product_images']['error'][$i] !== UPLOAD_ERR_OK) continue;
            $url = uploadImage([
                'name' => $_FILES['product_images']['name'][$i],
                'tmp_name' => $tmp,
            ], $uploadDir);
            if ($url) $uploadedUrls[] = $url;
        }
    }

    // Merge: keep existing non-removed, add new URLs, add uploads
    $keepUrls = $_POST['keep_images'] ?? [];
    $final = array_values(array_unique(array_merge($keepUrls, $urlImages, $uploadedUrls)));
    $imagesJson = json_encode($final);

    if (empty($title) || $categoryId === 0 || $price <= 0) {
        $error = 'Title, category, and price are required.';
    } else {
        if ($id > 0) {
            $stmt = $pdo->prepare("UPDATE products SET title=?, category_id=?, price=?, original_price=?, rating=?, reviews_count=?, description=?, features=?, details=?, colors=?, images=?, in_stock=? WHERE id=?");
            $stmt->execute([$title, $categoryId, $price, $originalPrice, $rating, $reviewsCount, $description, $features, $details, $colors, $imagesJson, $inStock, $id]);
            $message = 'Product updated successfully.';
        } else {
            $stmt = $pdo->prepare("INSERT INTO products (title, category_id, price, original_price, rating, reviews_count, description, features, details, colors, images, in_stock) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$title, $categoryId, $price, $originalPrice, $rating, $reviewsCount, $description, $features, $details, $colors, $imagesJson, $inStock]);
            $id = $pdo->lastInsertId();
            $message = 'Product added successfully.';
        }
    }
}

// Get edit product
$editProduct = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $editProduct = $stmt->fetch();
}

$categories = $pdo->query("SELECT * FROM categories ORDER BY id")->fetchAll();
$products = $pdo->query("SELECT p.*, c.name as category_name FROM products p JOIN categories c ON p.category_id = c.id ORDER BY p.id")->fetchAll();

require_once __DIR__ . '/header.php';
?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-black uppercase tracking-widest">Products</h1>
        <button onclick="document.getElementById('productForm').classList.toggle('hidden'); document.getElementById('formTitle').textContent='Add Product'; document.getElementById('productId').value='0';" class="bg-black text-white text-xs font-bold uppercase px-4 py-2.5 hover:bg-neutral-800 cursor-pointer">
            + Add Product
        </button>
    </div>

    <?php if ($message): ?>
        <div class="bg-green-50 border border-green-200 p-3 text-xs text-green-700 font-semibold"><?= escape($message) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="bg-red-50 border border-red-200 p-3 text-xs text-red-700 font-semibold"><?= escape($error) ?></div>
    <?php endif; ?>

    <!-- Product Form -->
    <div id="productForm" class="bg-white border border-neutral-200 p-6 <?= $editProduct ? '' : 'hidden' ?> space-y-4">
        <h2 id="formTitle" class="text-sm font-bold uppercase"><?= $editProduct ? 'Edit Product' : 'Add Product' ?></h2>
        <form method="POST" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <input type="hidden" name="product_id" id="productId" value="<?= $editProduct['id'] ?? 0 ?>" />

            <div class="md:col-span-2">
                <label class="text-[11px] font-semibold text-neutral-600 block mb-1">Title</label>
                <input type="text" name="title" value="<?= escape($editProduct['title'] ?? '') ?>" required class="w-full border border-neutral-300 px-3 py-2 text-xs focus:border-black focus:outline-none" />
            </div>

            <div>
                <label class="text-[11px] font-semibold text-neutral-600 block mb-1">Category</label>
                <select name="category_id" required class="w-full border border-neutral-300 px-3 py-2 text-xs focus:border-black focus:outline-none">
                    <option value="">Select category</option>
                    <?php foreach ($categories as $cat): ?>
                        <?php if ($cat['slug'] === 'all-products') continue; ?>
                        <option value="<?= $cat['id'] ?>" <?= ($editProduct['category_id'] ?? 0) == $cat['id'] ? 'selected' : '' ?>><?= escape($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="text-[11px] font-semibold text-neutral-600 block mb-1">Price (Ksh)</label>
                    <input type="number" name="price" step="0.01" value="<?= $editProduct['price'] ?? '' ?>" required class="w-full border border-neutral-300 px-3 py-2 text-xs focus:border-black focus:outline-none" />
                </div>
                <div>
                    <label class="text-[11px] font-semibold text-neutral-600 block mb-1">Original Price (Ksh)</label>
                    <input type="number" name="original_price" step="0.01" value="<?= $editProduct['original_price'] ?? '' ?>" class="w-full border border-neutral-300 px-3 py-2 text-xs focus:border-black focus:outline-none" />
                </div>
            </div>

            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label class="text-[11px] font-semibold text-neutral-600 block mb-1">Rating</label>
                    <input type="number" name="rating" step="0.1" min="0" max="5" value="<?= $editProduct['rating'] ?? 0 ?>" class="w-full border border-neutral-300 px-3 py-2 text-xs focus:border-black focus:outline-none" />
                </div>
                <div>
                    <label class="text-[11px] font-semibold text-neutral-600 block mb-1">Reviews Count</label>
                    <input type="number" name="reviews_count" value="<?= $editProduct['reviews_count'] ?? 0 ?>" class="w-full border border-neutral-300 px-3 py-2 text-xs focus:border-black focus:outline-none" />
                </div>
                <div class="flex items-end pb-2">
                    <label class="flex items-center gap-2 text-xs">
                        <input type="checkbox" name="in_stock" <?= isset($editProduct['in_stock']) && $editProduct['in_stock'] ? 'checked' : 'checked' ?> class="accent-black" />
                        In Stock
                    </label>
                </div>
            </div>

            <div class="md:col-span-2">
                <label class="text-[11px] font-semibold text-neutral-600 block mb-1">Description</label>
                <textarea name="description" rows="3" class="w-full border border-neutral-300 px-3 py-2 text-xs focus:border-black focus:outline-none"><?= escape($editProduct['description'] ?? '') ?></textarea>
            </div>

            <div>
                <label class="text-[11px] font-semibold text-neutral-600 block mb-1">Features (JSON array)</label>
                <textarea name="features" rows="3" class="w-full border border-neutral-300 px-3 py-2 text-xs font-mono focus:border-black focus:outline-none"><?= escape($editProduct['features'] ?? '[]') ?></textarea>
            </div>

            <div>
                <label class="text-[11px] font-semibold text-neutral-600 block mb-1">Details (JSON array of objects)</label>
                <textarea name="details" rows="3" class="w-full border border-neutral-300 px-3 py-2 text-xs font-mono focus:border-black focus:outline-none"><?= escape($editProduct['details'] ?? '[]') ?></textarea>
            </div>

            <input type="hidden" name="colors" value="[]" />

            <!-- Image Upload Section -->
            <div class="md:col-span-2 border border-neutral-200 p-4 space-y-3">
                <label class="text-[11px] font-semibold text-neutral-600 block mb-1">Product Images</label>

                <?php $editImages = $editProduct ? (json_decode($editProduct['images'], true) ?? []) : []; ?>

                <?php if (!empty($editImages)): ?>
                    <div class="flex flex-wrap gap-3 mb-3" id="currentImages">
                        <?php foreach ($editImages as $img): ?>
                            <div class="relative group w-20 h-20 border border-neutral-200 bg-[#f5f5f5]">
                                <img src="<?= escape($img) ?>" alt="" class="w-full h-full object-contain" />
                                <a href="/admin/products.php?remove_image=<?= urlencode($img) ?>&product_id=<?= $editProduct['id'] ?>" class="absolute -top-1.5 -right-1.5 w-5 h-5 bg-red-600 text-white rounded-full flex items-center justify-center text-[10px] font-bold opacity-0 group-hover:opacity-100 transition-opacity no-underline hover:bg-red-700" title="Remove image">&times;</a>
                                <input type="hidden" name="keep_images[]" value="<?= escape($img) ?>" />
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div>
                    <label class="text-[10px] text-neutral-500 block mb-1">Upload new images (JPG, PNG, GIF, WebP)</label>
                    <input type="file" name="product_images[]" multiple accept=".jpg,.jpeg,.png,.gif,.webp" class="w-full text-xs" />
                </div>

                <div class="pt-2 border-t border-neutral-100">
                    <label class="text-[10px] text-neutral-500 block mb-1">Or paste external URLs (JSON array, e.g. ["https://..."])</label>
                    <textarea name="images" rows="2" class="w-full border border-neutral-300 px-3 py-2 text-xs font-mono focus:border-black focus:outline-none"><?= escape($editProduct['images'] ?? '[]') ?></textarea>
                </div>
            </div>

            <div class="md:col-span-2 flex gap-3">
                <button type="submit" name="save_product" class="bg-black text-white text-xs font-bold uppercase py-2.5 px-6 hover:bg-neutral-800 cursor-pointer">
                    <?= $editProduct ? 'Update Product' : 'Add Product' ?>
                </button>
                <button type="button" onclick="document.getElementById('productForm').classList.add('hidden')" class="bg-white border border-black text-black text-xs font-bold uppercase py-2.5 px-6 hover:bg-neutral-100 cursor-pointer">
                    Cancel
                </button>
            </div>
        </form>
    </div>

    <!-- Products Table -->
    <div class="bg-white border border-neutral-200 overflow-x-auto">
        <table class="w-full text-xs">
            <thead>
                <tr class="bg-neutral-50 text-neutral-500 font-bold uppercase text-[10px]">
                    <th class="text-left p-3">ID</th>
                    <th class="text-left p-3">Image</th>
                    <th class="text-left p-3">Title</th>
                    <th class="text-left p-3">Category</th>
                    <th class="text-left p-3">Price</th>
                    <th class="text-left p-3">Stock</th>
                    <th class="text-left p-3">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $p): ?>
                    <?php $imgs = getProductImages($p['images']); $img = !empty($imgs) ? $imgs[0] : ''; ?>
                    <tr class="border-t border-neutral-100">
                        <td class="p-3 font-mono">#<?= $p['id'] ?></td>
                        <td class="p-3">
                            <?php if ($img): ?>
                                <img src="<?= escape($img) ?>" alt="" class="w-10 h-10 object-contain bg-[#f5f5f5] border" />
                            <?php endif; ?>
                        </td>
                        <td class="p-3 font-semibold max-w-xs truncate"><?= escape($p['title']) ?></td>
                        <td class="p-3 text-neutral-500"><?= escape($p['category_name']) ?></td>
                        <td class="p-3 font-mono font-bold">Ksh <?= number_format($p['price']) ?></td>
                        <td class="p-3"><?= $p['in_stock'] ? '<span class="text-green-600 font-bold">In Stock</span>' : '<span class="text-red-600 font-bold">Out of Stock</span>' ?></td>
                        <td class="p-3">
                            <a href="/admin/products.php?edit=<?= $p['id'] ?>" class="text-black font-bold underline mr-3">Edit</a>
                            <a href="/admin/products.php?delete=<?= $p['id'] ?>" onclick="return confirm('Delete this product?')" class="text-red-600 font-bold underline">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($products)): ?>
                    <tr><td colspan="7" class="p-6 text-center text-neutral-400">No products yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
