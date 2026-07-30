<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'toggle') {
    $productId = (int)($_POST['product_id'] ?? 0);
    if (!$productId) {
        echo json_encode(['success' => false, 'error' => 'Invalid product']);
        exit;
    }

    if (isset($_SESSION['user_id'])) {
        $stmt = $pdo->prepare("SELECT id FROM wishlists WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$_SESSION['user_id'], $productId]);
        if ($stmt->fetch()) {
            $pdo->prepare("DELETE FROM wishlists WHERE user_id = ? AND product_id = ?")->execute([$_SESSION['user_id'], $productId]);
            echo json_encode(['success' => true, 'wishlisted' => false]);
        } else {
            $pdo->prepare("INSERT INTO wishlists (user_id, product_id) VALUES (?, ?)")->execute([$_SESSION['user_id'], $productId]);
            echo json_encode(['success' => true, 'wishlisted' => true]);
        }
    } else {
        if (!isset($_SESSION['wishlist'])) $_SESSION['wishlist'] = [];
        $idx = array_search($productId, $_SESSION['wishlist']);
        if ($idx !== false) {
            array_splice($_SESSION['wishlist'], $idx, 1);
            echo json_encode(['success' => true, 'wishlisted' => false]);
        } else {
            $_SESSION['wishlist'][] = $productId;
            echo json_encode(['success' => true, 'wishlisted' => true]);
        }
    }
    exit;
}

echo json_encode(['success' => false, 'error' => 'Unknown action']);
