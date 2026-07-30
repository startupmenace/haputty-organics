<?php
$cartCount = getCartCount($pdo);
$wishlistIds = getWishlistIds($pdo);
$categories = $pdo->query("SELECT * FROM categories ORDER BY id")->fetchAll();
$currentPage = $_GET['page'] ?? 'home';
$catSlug = $_GET['category'] ?? 'all-products';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HAPUTTY ORGANICS &#8212; Premium Teeth Whitening &amp; Oral Care</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        * { box-sizing: border-box; }
        body { font-family: system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif; }
        .scrollbar-none::-webkit-scrollbar { display: none; }
        .scrollbar-none { -ms-overflow-style: none; scrollbar-width: none; }
        .line-clamp-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .line-clamp-3 { display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; }
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }
        .animate-pulse { animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite; }
    </style>
</head>
<body class="min-h-screen bg-white text-black font-sans flex flex-col selection:bg-black selection:text-white">

<div class="bg-black text-white text-[11px] font-semibold tracking-wider py-2 px-4 text-center flex items-center justify-center gap-2 uppercase border-b border-neutral-800">
    <span>SHIPPING NATIONWIDE &#128666;</span>
    <span class="text-neutral-500">|</span>
    <span>M-PESA PAYMENTS ACCEPTED &#127463;&#127466;</span>
</div>

<header class="bg-white sticky top-0 z-30 border-b border-neutral-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16 md:h-20 gap-4">

            <a href="/" class="cursor-pointer flex items-center group no-underline">
                <span class="text-2xl md:text-3xl font-black tracking-tighter uppercase font-mono text-black">HAPUTTY ORGANICS</span>
            </a>

            <nav class="hidden lg:flex items-center gap-6 text-xs font-bold tracking-wider uppercase">
                <a href="/" class="no-underline <?= $currentPage === 'home' ? 'text-black border-b-2 border-black pb-1' : 'text-neutral-500 hover:text-black' ?>">All Products</a>
                <a href="/?category=teeth-whitening-kits" class="no-underline <?= $catSlug === 'teeth-whitening-kits' ? 'text-black border-b-2 border-black pb-1' : 'text-neutral-500 hover:text-black' ?>">Teeth Whitening Kits</a>
                <a href="/?category=sonic-toothbrushes" class="no-underline <?= $catSlug === 'sonic-toothbrushes' ? 'text-black border-b-2 border-black pb-1' : 'text-neutral-500 hover:text-black' ?>">Sonic Toothbrushes</a>
                <a href="/?category=water-flossers" class="no-underline <?= $catSlug === 'water-flossers' ? 'text-black border-b-2 border-black pb-1' : 'text-neutral-500 hover:text-black' ?>">Water Flossers</a>
            </nav>

            <div class="flex-1 max-w-xs md:max-w-sm hidden sm:block relative">
                <form method="GET" action="/" class="relative">
                    <input
                        type="text"
                        name="search"
                        placeholder="Search..."
                        value="<?= escape($_GET['search'] ?? '') ?>"
                        class="w-full bg-neutral-100 text-xs pl-9 pr-8 py-2.5 rounded-none border border-neutral-200 focus:border-black focus:bg-white focus:outline-none transition-all"
                    />
                    <button type="submit" class="absolute left-3 top-1/2 -translate-y-1/2 text-neutral-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </button>
                </form>
            </div>

            <div class="flex items-center gap-3 sm:gap-4">
                <?php if (isLoggedIn()): ?>
                    <a href="/?page=account" class="p-1.5 text-neutral-800 hover:text-black transition-colors no-underline">
                        <svg class="w-5 h-5 stroke-[1.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    </a>
                <?php else: ?>
                    <a href="/?page=login" class="p-1.5 text-neutral-800 hover:text-black transition-colors no-underline">
                        <svg class="w-5 h-5 stroke-[1.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    </a>
                <?php endif; ?>

                <a href="/?page=wishlist" class="p-1.5 text-neutral-800 hover:text-black transition-colors relative no-underline">
                    <svg class="w-5 h-5 stroke-[1.5] <?= count($wishlistIds) > 0 ? 'fill-red-500 text-red-500' : '' ?>" fill="<?= count($wishlistIds) > 0 ? 'currentColor' : 'none' ?>" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                    <?php if (count($wishlistIds) > 0): ?>
                        <span class="absolute -top-1 -right-1 bg-red-500 text-white text-[10px] font-bold w-4 h-4 rounded-full flex items-center justify-center"><?= count($wishlistIds) ?></span>
                    <?php endif; ?>
                </a>

                <button onclick="toggleCartDrawer()" class="p-2.5 bg-black text-white hover:bg-neutral-800 transition-colors relative flex items-center justify-center cursor-pointer">
                    <svg class="w-4 h-4 md:w-5 md:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                    <?php if ($cartCount > 0): ?>
                        <span class="absolute -top-1.5 -right-1.5 bg-red-600 text-white text-[10px] font-bold w-5 h-5 rounded-full border-2 border-white flex items-center justify-center"><?= $cartCount ?></span>
                    <?php endif; ?>
                </button>
            </div>

        </div>
    </div>

    <div class="bg-neutral-100 border-t border-neutral-200 overflow-x-auto scrollbar-none py-2 px-4">
        <div class="max-w-7xl mx-auto flex items-center gap-2 text-[11px] font-bold tracking-wider text-neutral-700 uppercase whitespace-nowrap">
            <a href="/" class="py-1 px-3 transition-all no-underline <?= $catSlug === 'all-products' ? 'bg-black text-white' : 'hover:bg-neutral-200 hover:text-black text-neutral-700' ?>">ALL PRODUCTS</a>
            <?php foreach ($categories as $cat): ?>
                <?php if ($cat['slug'] === 'all-products') continue; ?>
                <a href="/?category=<?= $cat['slug'] ?>" class="py-1 px-3 transition-all no-underline <?= $catSlug === $cat['slug'] ? 'bg-black text-white' : 'hover:bg-neutral-200 hover:text-black text-neutral-700' ?>"><?= escape($cat['name']) ?></a>
            <?php endforeach; ?>
        </div>
    </div>
</header>

<main class="flex-1 w-full px-4 sm:px-6 lg:px-8 py-6">
