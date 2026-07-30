<?php
session_start();

define('DB_HOST', 'localhost');
define('DB_NAME', 'toothsavior');
define('DB_USER', 'root');
define('DB_PASS', '');

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

function getCartCount($pdo) {
    if (isset($_SESSION['user_id'])) {
        $stmt = $pdo->prepare("SELECT SUM(quantity) FROM cart WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return (int)$stmt->fetchColumn();
    }
    if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
        return array_sum(array_column($_SESSION['cart'], 'quantity'));
    }
    return 0;
}

function getWishlistIds($pdo) {
    $ids = [];
    if (isset($_SESSION['user_id'])) {
        $stmt = $pdo->prepare("SELECT product_id FROM wishlists WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
    } elseif (isset($_SESSION['wishlist']) && is_array($_SESSION['wishlist'])) {
        $ids = $_SESSION['wishlist'];
    }
    return $ids;
}
