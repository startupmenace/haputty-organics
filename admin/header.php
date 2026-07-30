<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$adminPage = basename($_SERVER['PHP_SELF'], '.php');
$totalProducts = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$totalOrders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$totalRevenue = $pdo->query("SELECT COALESCE(SUM(total), 0) FROM orders WHERE status != 'cancelled'")->fetchColumn();
$pendingOrders = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HAPUTTY ORGANICS Admin Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        * { box-sizing: border-box; }
        body { font-family: system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif; }
    </style>
</head>
<body class="bg-neutral-50 text-black min-h-screen">

<div class="flex min-h-screen">
    <!-- Sidebar -->
    <aside class="w-56 bg-black text-white flex flex-col flex-shrink-0">
        <div class="p-4 border-b border-neutral-800">
            <a href="/admin/" class="text-lg font-black uppercase font-mono tracking-tighter no-underline text-white">HAPUTTY ORGANICS</a>
            <div class="text-[10px] text-neutral-500 mt-1">Admin Panel</div>
        </div>
        <nav class="flex-1 p-3 space-y-1 text-xs">
            <a href="/admin/" class="block px-3 py-2.5 font-semibold uppercase tracking-wider no-underline <?= $adminPage === 'index' ? 'bg-white text-black' : 'text-neutral-400 hover:text-white hover:bg-neutral-800' ?>">Dashboard</a>
            <a href="/admin/products.php" class="block px-3 py-2.5 font-semibold uppercase tracking-wider no-underline <?= $adminPage === 'products' ? 'bg-white text-black' : 'text-neutral-400 hover:text-white hover:bg-neutral-800' ?>">Products</a>
            <a href="/admin/orders.php" class="block px-3 py-2.5 font-semibold uppercase tracking-wider no-underline <?= $adminPage === 'orders' ? 'bg-white text-black' : 'text-neutral-400 hover:text-white hover:bg-neutral-800' ?>">Orders</a>
            <a href="/admin/customers.php" class="block px-3 py-2.5 font-semibold uppercase tracking-wider no-underline <?= $adminPage === 'customers' ? 'bg-white text-black' : 'text-neutral-400 hover:text-white hover:bg-neutral-800' ?>">Customers</a>
            <a href="/admin/reports.php" class="block px-3 py-2.5 font-semibold uppercase tracking-wider no-underline <?= $adminPage === 'reports' ? 'bg-white text-black' : 'text-neutral-400 hover:text-white hover:bg-neutral-800' ?>">Reports</a>
        </nav>
        <div class="p-4 border-t border-neutral-800">
            <a href="/" class="block text-[10px] text-neutral-500 hover:text-white mb-2">View Store</a>
            <a href="/?page=logout" class="block text-[10px] text-red-400 hover:text-red-300">Sign Out</a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 p-6">
