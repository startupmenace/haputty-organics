<?php
$search = $_GET['search'] ?? '';
$categorySlug = $_GET['category'] ?? 'all-products';

$catFilter = '';
$params = [];

if ($categorySlug !== 'all-products') {
    $stmt = $pdo->prepare("SELECT id FROM categories WHERE slug = ?");
    $stmt->execute([$categorySlug]);
    $cat = $stmt->fetch();
    if ($cat) {
        $catFilter = 'AND p.category_id = ?';
        $params[] = $cat['id'];
    }
}

if ($search) {
    $searchTerm = '%' . $search . '%';
    $sql = "SELECT p.*, c.name as category_name, c.slug as category_slug
            FROM products p JOIN categories c ON p.category_id = c.id
            WHERE (p.title LIKE ? OR c.name LIKE ? OR p.description LIKE ?) $catFilter
            ORDER BY p.id";
    $params = array_merge([$searchTerm, $searchTerm, $searchTerm], $params);
} else {
    $sql = "SELECT p.*, c.name as category_name, c.slug as category_slug
            FROM products p JOIN categories c ON p.category_id = c.id
            WHERE 1=1 $catFilter
            ORDER BY p.id";
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

$activeCategory = 'ALL PRODUCTS';
if ($categorySlug !== 'all-products') {
    $stmt = $pdo->prepare("SELECT name FROM categories WHERE slug = ?");
    $stmt->execute([$categorySlug]);
    $cat = $stmt->fetch();
    if ($cat) $activeCategory = $cat['name'];
}
?>

<div class="space-y-6">
    <div class="flex items-center justify-between border-b border-neutral-200 pb-2">
        <h2 class="text-xs md:text-sm font-black tracking-widest text-black uppercase">
            <?= escape($search ? "SEARCH: $search" : $activeCategory) ?>
        </h2>
        <span class="text-[11px] text-neutral-500 font-semibold"><?= count($products) ?> Products</span>
    </div>

    <?php if (empty($products)): ?>
        <div class="text-center py-20 bg-neutral-50 border border-neutral-200">
            <p class="text-neutral-500 text-xs uppercase tracking-wider">No products found.</p>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 sm:gap-6 lg:gap-8">
            <?php foreach ($products as $product): ?>
                <?php
                $images = getProductImages($product['images']);
                $img = !empty($images) ? $images[0] : '';
                $isWishlisted = in_array($product['id'], getWishlistIds($pdo));
                ?>
                <div class="bg-white border border-neutral-200 overflow-hidden flex flex-col justify-between group">
                    <div class="relative aspect-square bg-[#f5f5f5] overflow-hidden cursor-pointer" onclick="window.location='/index.php?page=product&id=<?= $product['id'] ?>'">
                        <img src="<?= escape($img) ?>" alt="<?= escape($product['title']) ?>" class="w-full h-full object-contain p-6 group-hover:scale-105 transition-transform duration-500" />
                        <button onclick="event.stopPropagation(); toggleWishlist(<?= $product['id'] ?>)" class="absolute top-3 right-3 p-2 bg-white/90 rounded-full text-neutral-600 hover:text-red-500 transition-colors cursor-pointer shadow-sm">
                            <svg class="w-4 h-4 <?= $isWishlisted ? 'fill-red-500 text-red-500' : '' ?>" fill="<?= $isWishlisted ? 'currentColor' : 'none' ?>" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                        </button>
                    </div>

                    <div class="p-4 sm:p-5 flex-1 flex flex-col justify-between space-y-4">
                        <div>
                            <h3 onclick="window.location='/index.php?page=product&id=<?= $product['id'] ?>" class="text-sm font-semibold text-neutral-900 line-clamp-2 hover:text-black cursor-pointer leading-snug mb-2">
                                <?= escape($product['title']) ?>
                            </h3>

                            <div class="flex items-baseline gap-2">
                                <span class="text-sm sm:text-base font-bold text-black font-mono">Ksh <?= number_format($product['price']) ?>.00</span>
                                <?php if ($product['original_price']): ?>
                                    <span class="text-xs text-neutral-400 line-through font-mono">Ksh <?= number_format($product['original_price']) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-2 pt-3 border-t border-neutral-100">
                            <button onclick="buyNow(<?= $product['id'] ?>)" class="w-full bg-black text-white text-xs font-bold uppercase py-3 px-2 hover:bg-neutral-800 transition-colors text-center tracking-tight cursor-pointer">
                                BUY NOW
                            </button>
                            <button onclick="quickView(<?= $product['id'] ?>)" class="w-full bg-white text-black border border-black text-xs font-bold uppercase py-3 px-2 hover:bg-neutral-100 transition-colors text-center tracking-tight cursor-pointer">
                                QUICK VIEW
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Quick View Modal -->
<div id="quickViewModal" class="fixed inset-0 bg-black/60 z-50 hidden items-center justify-center p-4">
    <div class="bg-white max-w-2xl w-full p-6 relative space-y-4 shadow-2xl border border-black">
        <button onclick="closeQuickView()" class="absolute top-4 right-4 p-2 text-neutral-400 hover:text-black cursor-pointer">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
        <div id="quickViewContent" class="grid grid-cols-1 sm:grid-cols-2 gap-6 items-center">
            <!-- loaded via JS -->
        </div>
    </div>
</div>

<script>
function toggleWishlist(productId) {
    const formData = new FormData();
    formData.append('action', 'toggle');
    formData.append('product_id', productId);

    fetch('/ajax/wishlist.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                // Reload to update UI
                window.location.reload();
            }
        });
}

function buyNow(productId) {
    const formData = new FormData();
    formData.append('action', 'add');
    formData.append('product_id', productId);
    formData.append('quantity', 1);

    fetch('/ajax/cart.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                window.location = '/index.php?page=checkout';
            }
        });
}

function quickView(productId) {
    fetch('/ajax/product_quick.php?id=' + productId)
        .then(r => r.text())
        .then(html => {
            document.getElementById('quickViewContent').innerHTML = html;
            document.getElementById('quickViewModal').classList.remove('hidden');
            document.getElementById('quickViewModal').classList.add('flex');
        });
}

function closeQuickView() {
    document.getElementById('quickViewModal').classList.add('hidden');
    document.getElementById('quickViewModal').classList.remove('flex');
}

function addToCartFromQuick(productId) {
    const formData = new FormData();
    formData.append('action', 'add');
    formData.append('product_id', productId);
    formData.append('quantity', 1);

    fetch('/ajax/cart.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                closeQuickView();
                toggleCartDrawer();
            }
        });
}
</script>
