<?php
$wishlistIds = getWishlistIds($pdo);
$products = [];

if (!empty($wishlistIds)) {
    $placeholders = implode(',', array_fill(0, count($wishlistIds), '?'));
    $stmt = $pdo->prepare("SELECT p.*, c.name as category_name FROM products p JOIN categories c ON p.category_id = c.id WHERE p.id IN ($placeholders)");
    $stmt->execute($wishlistIds);
    $products = $stmt->fetchAll();
}
?>

<div class="space-y-6">
    <div class="border-b border-neutral-200 pb-4">
        <h1 class="text-xl font-black text-black uppercase tracking-widest">SAVED ITEMS (<?= count($products) ?>)</h1>
    </div>

    <?php if (empty($products)): ?>
        <div class="text-center py-20 bg-neutral-50 border border-neutral-200">
            <svg class="w-12 h-12 text-neutral-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
            <p class="text-neutral-500 text-xs uppercase">Your wishlist is currently empty.</p>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <?php foreach ($products as $product): ?>
                <?php
                $images = getProductImages($product['images']);
                $img = !empty($images) ? $images[0] : '';
                ?>
                <div class="bg-white border border-neutral-200 overflow-hidden flex flex-col justify-between group">
                    <div class="relative aspect-square bg-[#f5f5f5] overflow-hidden cursor-pointer" onclick="window.location='/index.php?page=product&id=<?= $product['id'] ?>'">
                        <img src="<?= escape($img) ?>" alt="<?= escape($product['title']) ?>" class="w-full h-full object-contain p-4 group-hover:scale-105 transition-transform duration-300" />
                        <button onclick="event.stopPropagation(); toggleWishlist(<?= $product['id'] ?>)" class="absolute top-2 right-2 p-1.5 bg-white/90 rounded-full text-red-500 cursor-pointer">
                            <svg class="w-4 h-4 fill-red-500 text-red-500" fill="currentColor" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                        </button>
                    </div>
                    <div class="p-3 sm:p-4 flex-1 flex flex-col justify-between space-y-3">
                        <div>
                            <h3 onclick="window.location='/index.php?page=product&id=<?= $product['id'] ?>'" class="text-xs font-medium text-neutral-900 line-clamp-2 hover:text-black cursor-pointer leading-snug mb-2"><?= escape($product['title']) ?></h3>
                            <div class="flex items-baseline gap-2">
                                <span class="text-xs sm:text-sm font-bold text-black font-mono">Ksh <?= number_format($product['price']) ?>.00</span>
                                <?php if ($product['original_price']): ?>
                                    <span class="text-[11px] text-neutral-400 line-through font-mono">Ksh <?= number_format($product['original_price']) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-1.5 pt-2 border-t border-neutral-100">
                            <button onclick="buyNow(<?= $product['id'] ?>)" class="w-full bg-black text-white text-[10px] sm:text-[11px] font-bold uppercase py-2 px-1 hover:bg-neutral-800 transition-colors text-center tracking-tight cursor-pointer">BUY NOW</button>
                            <a href="/index.php?page=product&id=<?= $product['id'] ?>" class="w-full bg-white text-black border border-black text-[10px] sm:text-[11px] font-bold uppercase py-2 px-1 hover:bg-neutral-100 transition-colors text-center tracking-tight no-underline">VIEW</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
function toggleWishlist(productId) {
    const formData = new FormData();
    formData.append('action', 'toggle');
    formData.append('product_id', productId);
    fetch('/ajax/wishlist.php', { method: 'POST', body: formData }).then(r => r.json()).then(d => { if(d.success) location.reload(); });
}
function buyNow(productId) {
    const formData = new FormData();
    formData.append('action', 'add');
    formData.append('product_id', productId);
    formData.append('quantity', 1);
    fetch('/ajax/cart.php', { method: 'POST', body: formData }).then(r => r.json()).then(d => { if(d.success) window.location='/index.php?page=checkout'; });
}
</script>
