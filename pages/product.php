<?php
$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT p.*, c.name as category_name, c.slug as category_slug FROM products p JOIN categories c ON p.category_id = c.id WHERE p.id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    echo '<div class="text-center py-20"><p class="text-neutral-500 text-xs uppercase">Product not found.</p></div>';
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

$images = getProductImages($product['images']);
$colors = getProductColors($product['colors']);
$features = getProductFeatures($product['features']);
$details = getProductDetails($product['details']);
$isWishlisted = in_array($product['id'], getWishlistIds($pdo));

// Related products
$stmtRel = $pdo->prepare("SELECT p.*, c.name as category_name FROM products p JOIN categories c ON p.category_id = c.id WHERE p.category_id = ? AND p.id != ? LIMIT 4");
$stmtRel->execute([$product['category_id'], $product['id']]);
$relatedProducts = $stmtRel->fetchAll();
?>

<div class="space-y-12">
    <div class="flex items-center gap-2 text-xs text-neutral-500 py-2 border-b border-neutral-200">
        <a href="/" class="flex items-center gap-1 hover:text-black font-semibold no-underline">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 12H5m7-7l-7 7 7 7"/></svg>
            Back to Products
        </a>
        <span>/</span>
        <span><?= escape($product['category_name']) ?></span>
        <span>/</span>
        <span class="text-black font-medium truncate max-w-xs"><?= escape($product['title']) ?></span>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-12 bg-white p-2 sm:p-4">
        <div class="space-y-4" x-data="{ activeImg: 0 }">
            <div class="aspect-square bg-[#f5f5f5] overflow-hidden border border-neutral-200 relative">
                <img id="mainImage" src="<?= escape(!empty($images) ? $images[0] : '') ?>" alt="<?= escape($product['title']) ?>" class="w-full h-full object-contain p-6" />
                <button onclick="toggleWishlist(<?= $product['id'] ?>)" class="absolute top-4 right-4 p-2 bg-white rounded-full shadow text-neutral-600 hover:text-red-500 transition-colors cursor-pointer">
                    <svg class="w-5 h-5 <?= $isWishlisted ? 'fill-red-500 text-red-500' : '' ?>" fill="<?= $isWishlisted ? 'currentColor' : 'none' ?>" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                </button>
            </div>

            <?php if (count($images) > 1): ?>
                <div class="flex gap-3 overflow-x-auto">
                    <?php foreach ($images as $idx => $img): ?>
                        <button onclick="document.getElementById('mainImage').src='<?= escape($img) ?>'; document.querySelectorAll('.thumb').forEach(el=>el.classList.remove('border-black','border-2')); this.classList.add('border-black','border-2');" class="thumb w-20 h-20 border overflow-hidden bg-[#f5f5f5] <?= $idx === 0 ? 'border-black border-2' : 'border-neutral-200 opacity-60 hover:opacity-100' ?> cursor-pointer">
                            <img src="<?= escape($img) ?>" alt="" class="w-full h-full object-contain p-1" />
                        </button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="space-y-6">
            <div>
                <h1 class="text-xl md:text-2xl font-bold text-black leading-snug"><?= escape($product['title']) ?></h1>
                <div class="flex items-center gap-3 mt-2 text-xs">
                    <div class="flex text-black">
                        <?php for ($i = 0; $i < 5; $i++): ?>
                            <svg class="w-3.5 h-3.5 fill-black text-black" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                        <?php endfor; ?>
                    </div>
                    <span class="font-bold text-black"><?= $product['rating'] ?></span>
                    <span class="text-neutral-400">(<?= $product['reviews_count'] ?> Customer Reviews)</span>
                </div>
            </div>

            <div class="flex items-baseline gap-3 py-2 border-y border-neutral-200">
                <span class="text-2xl font-extrabold text-black font-mono">Ksh <?= number_format($product['price']) ?>.00</span>
                <?php if ($product['original_price']): ?>
                    <span class="text-sm text-neutral-400 line-through font-mono">Ksh <?= number_format($product['original_price']) ?></span>
                <?php endif; ?>
            </div>

            <?php if (!empty($colors)): ?>
                <div class="space-y-2">
                    <label class="text-xs font-bold uppercase tracking-wider text-neutral-700 block">
                        Color Option: <span id="colorLabel" class="text-black font-normal"><?= escape($colors[0]['name'] ?? '') ?></span>
                    </label>
                    <div class="flex gap-2">
                        <?php foreach ($colors as $col): ?>
                            <button onclick="document.getElementById('colorLabel').textContent='<?= escape($col['name'] ?? '') ?>'; document.querySelectorAll('.color-btn').forEach(el=>el.classList.remove('border-black','bg-black','text-white')); this.classList.add('border-black','bg-black','text-white'); document.getElementById('selectedColor').value='<?= escape($col['name'] ?? '') ?>';" class="color-btn px-3 py-1.5 border text-xs font-semibold flex items-center gap-2 transition-all border-neutral-300 text-neutral-700 hover:border-black cursor-pointer">
                                <span class="w-3 h-3 rounded-full border border-white block" style="background-color: <?= escape($col['hex'] ?? '#ccc') ?>;"></span>
                                <?= escape($col['name'] ?? '') ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="space-y-2">
                <label class="text-xs font-bold uppercase tracking-wider text-neutral-700 block">Quantity</label>
                <div class="flex items-center border border-black w-32">
                    <button onclick="let q=document.getElementById('qtyInput'); let v=parseInt(q.value)-1; if(v>=1){q.value=v;}" class="p-2.5 hover:bg-neutral-100 transition-colors cursor-pointer">
                        <svg class="w-3.5 h-3.5 text-black" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/></svg>
                    </button>
                    <input id="qtyInput" type="number" value="1" min="1" class="flex-1 text-center font-bold text-sm font-mono border-0 outline-none w-full" />
                    <button onclick="let q=document.getElementById('qtyInput'); q.value=parseInt(q.value)+1;" class="p-2.5 hover:bg-neutral-100 transition-colors cursor-pointer">
                        <svg class="w-3.5 h-3.5 text-black" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    </button>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row gap-3 pt-2">
                <button onclick="buyNowPDP(<?= $product['id'] ?>)" class="flex-1 bg-black text-white text-xs font-bold tracking-wider uppercase py-4 px-6 hover:bg-neutral-800 transition-colors text-center cursor-pointer">
                    BUY NOW
                </button>
                <button onclick="addToCartPDP(<?= $product['id'] ?>)" class="flex-1 bg-white border border-black text-black text-xs font-bold tracking-wider uppercase py-4 px-6 hover:bg-neutral-100 transition-colors text-center cursor-pointer">
                    ADD TO CART
                </button>
            </div>

            <div class="pt-4 space-y-4 text-xs text-neutral-700 leading-relaxed">
                <p><?= escape($product['description']) ?></p>

                <?php if (!empty($features)): ?>
                    <div>
                        <h4 class="font-bold text-black uppercase mb-2">Key Highlights:</h4>
                        <ul class="list-disc pl-5 space-y-1 text-neutral-800">
                            <?php foreach ($features as $feat): ?>
                                <li><?= escape($feat) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <div class="border border-neutral-200 mt-4">
                    <button onclick="document.getElementById('specsPanel').classList.toggle('hidden'); document.getElementById('specsChevron').classList.toggle('rotate-180');" class="w-full p-4 text-left font-bold text-xs uppercase tracking-wider text-black flex items-center justify-between bg-neutral-50 cursor-pointer">
                        <span>SPECIFICATIONS & WARRANTY</span>
                        <svg id="specsChevron" class="w-4 h-4 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div id="specsPanel" class="p-4 bg-white border-t border-neutral-200 text-xs text-neutral-600 space-y-2 font-mono">
                        <?php foreach ($details as $d): ?>
                            <div class="flex justify-between border-b border-neutral-100 pb-1">
                                <span class="text-neutral-500"><?= escape($d['key']) ?>:</span>
                                <span class="font-semibold text-black"><?= escape($d['val']) ?></span>
                            </div>
                        <?php endforeach; ?>
                        <div class="pt-2 text-neutral-500 text-[11px]">Includes HAPUTTY ORGANICS 1-Year replacement guarantee for electrical components.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if (!empty($relatedProducts)): ?>
        <section class="space-y-6 pt-6">
            <h2 class="text-sm md:text-base font-black tracking-widest uppercase text-black border-b border-neutral-200 pb-2">YOU MIGHT ALSO LIKE</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <?php foreach ($relatedProducts as $rel): ?>
                    <?php
                    $relImages = getProductImages($rel['images']);
                    $relImg = !empty($relImages) ? $relImages[0] : '';
                    ?>
                    <div class="bg-white border border-neutral-200 overflow-hidden flex flex-col justify-between group">
                        <div class="relative aspect-square bg-[#f5f5f5] overflow-hidden cursor-pointer" onclick="window.location='/index.php?page=product&id=<?= $rel['id'] ?>'">
                            <img src="<?= escape($relImg) ?>" alt="<?= escape($rel['title']) ?>" class="w-full h-full object-contain p-4 group-hover:scale-105 transition-transform duration-300" />
                        </div>
                        <div class="p-3 sm:p-4 flex-1 flex flex-col justify-between space-y-3">
                            <div>
                                <h3 onclick="window.location='/index.php?page=product&id=<?= $rel['id'] ?>'" class="text-xs font-medium text-neutral-900 line-clamp-2 hover:text-black cursor-pointer leading-snug mb-2"><?= escape($rel['title']) ?></h3>
                                <div class="flex items-baseline gap-2">
                                    <span class="text-xs sm:text-sm font-bold text-black font-mono">Ksh <?= number_format($rel['price']) ?>.00</span>
                                    <?php if ($rel['original_price']): ?>
                                        <span class="text-[11px] text-neutral-400 line-through font-mono">Ksh <?= number_format($rel['original_price']) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-1.5 pt-2 border-t border-neutral-100">
                                <a href="/?page=checkout&buy_now=<?= $rel['id'] ?>" class="w-full bg-black text-white text-[10px] sm:text-[11px] font-bold uppercase py-2 px-1 hover:bg-neutral-800 transition-colors text-center tracking-tight no-underline">BUY NOW</a>
                                <a href="/index.php?page=product&id=<?= $rel['id'] ?>" class="w-full bg-white text-black border border-black text-[10px] sm:text-[11px] font-bold uppercase py-2 px-1 hover:bg-neutral-100 transition-colors text-center tracking-tight no-underline">VIEW</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
</div>

<script>
function toggleWishlist(productId) {
    const formData = new FormData();
    formData.append('action', 'toggle');
    formData.append('product_id', productId);
    fetch('/ajax/wishlist.php', { method: 'POST', body: formData }).then(r => r.json()).then(d => { if(d.success) location.reload(); });
}

function buyNowPDP(productId) {
    const qty = document.getElementById('qtyInput').value;
    const formData = new FormData();
    formData.append('action', 'add');
    formData.append('product_id', productId);
    formData.append('quantity', qty);
    formData.append('color', 'Standard');
    fetch('/ajax/cart.php', { method: 'POST', body: formData }).then(r => r.json()).then(d => { if(d.success) window.location='/index.php?page=checkout'; });
}

function addToCartPDP(productId) {
    const qty = document.getElementById('qtyInput').value;
    const formData = new FormData();
    formData.append('action', 'add');
    formData.append('product_id', productId);
    formData.append('quantity', qty);
    formData.append('color', 'Standard');
    fetch('/ajax/cart.php', { method: 'POST', body: formData }).then(r => r.json()).then(d => { if(d.success) toggleCartDrawer(); });
}
</script>
