<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT p.*, c.name as category_name FROM products p JOIN categories c ON p.category_id = c.id WHERE p.id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    echo '<p class="text-xs text-neutral-500">Product not found.</p>';
    exit;
}

$images = getProductImages($product['images']);
$img = !empty($images) ? $images[0] : '';
$colors = getProductColors($product['colors']);
?>
<div class="aspect-square bg-[#f5f5f5] p-4 flex items-center justify-center border border-neutral-200">
    <img src="<?= escape($img) ?>" alt="<?= escape($product['title']) ?>" class="max-h-full object-contain" />
</div>
<div class="space-y-4">
    <span class="text-[10px] font-bold text-neutral-400 uppercase tracking-widest"><?= escape($product['category_name']) ?></span>
    <h3 class="text-base font-bold text-black leading-snug"><?= escape($product['title']) ?></h3>
    <div class="text-xl font-extrabold text-black font-mono">Ksh <?= number_format($product['price']) ?>.00</div>
    <p class="text-xs text-neutral-600 line-clamp-3"><?= escape($product['description']) ?></p>
    <div class="space-y-2 pt-2">
        <button onclick="addToCartFromQuick(<?= $product['id'] ?>)" class="w-full bg-black text-white text-xs font-bold uppercase py-3 hover:bg-neutral-800 cursor-pointer">
            Add To Cart
        </button>
        <a href="/index.php?page=product&id=<?= $product['id'] ?>" class="block w-full bg-white text-black border border-black text-xs font-bold uppercase py-2.5 hover:bg-neutral-100 text-center no-underline">
            View Full Details
        </a>
    </div>
</div>
