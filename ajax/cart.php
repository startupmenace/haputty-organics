<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'get') {
    $items = [];
    $subtotal = 0;

    if (isset($_SESSION['user_id'])) {
        $stmt = $pdo->prepare("
            SELECT c.id, c.quantity, c.color, p.id as product_id, p.title, p.price, p.images
            FROM cart c JOIN products p ON c.product_id = p.id
            WHERE c.user_id = ?
        ");
        $stmt->execute([$_SESSION['user_id']]);
    } elseif (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
        $sessionCart = $_SESSION['cart'];
        $items = [];
        $subtotal = 0;
        foreach ($sessionCart as $idx => $item) {
            $stmt = $pdo->prepare("SELECT id, title, price, images FROM products WHERE id = ?");
            $stmt->execute([$item['product_id']]);
            $p = $stmt->fetch();
            if ($p) {
                $imgs = getProductImages($p['images']);
                $img = !empty($imgs) ? $imgs[0] : '';
                $items[] = [
                    'cartId' => 'sess_' . $idx,
                    'product_id' => $p['id'],
                    'title' => $p['title'],
                    'price' => (float)$p['price'],
                    'quantity' => (int)$item['quantity'],
                    'color' => $item['color'] ?? 'Standard',
                    'image' => $img
                ];
                $subtotal += (float)$p['price'] * (int)$item['quantity'];
            }
        }
        echo json_encode(['items' => $items, 'subtotal' => $subtotal]);
        exit;
    } else {
        echo json_encode(['items' => [], 'subtotal' => 0]);
        exit;
    }

    while ($row = $stmt->fetch()) {
        $imgs = getProductImages($row['images']);
        $img = !empty($imgs) ? $imgs[0] : '';
        $items[] = [
            'cartId' => $row['id'],
            'product_id' => $row['product_id'],
            'title' => $row['title'],
            'price' => (float)$row['price'],
            'quantity' => (int)$row['quantity'],
            'color' => $row['color'] ?? 'Standard',
            'image' => $img
        ];
        $subtotal += (float)$row['price'] * (int)$row['quantity'];
    }

    echo json_encode(['items' => $items, 'subtotal' => $subtotal]);
    exit;
}

if ($action === 'add') {
    $productId = (int)($_POST['product_id'] ?? $_GET['product_id'] ?? 0);
    $quantity = max(1, (int)($_POST['quantity'] ?? 1));
    $color = $_POST['color'] ?? 'Standard';

    if (!$productId) {
        echo json_encode(['success' => false, 'error' => 'Invalid product']);
        exit;
    }

    if (isset($_SESSION['user_id'])) {
        $stmt = $pdo->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ? AND color = ?");
        $stmt->execute([$_SESSION['user_id'], $productId, $color]);
        $existing = $stmt->fetch();

        if ($existing) {
            $stmt = $pdo->prepare("UPDATE cart SET quantity = quantity + ? WHERE id = ?");
            $stmt->execute([$quantity, $existing['id']]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity, color) VALUES (?, ?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $productId, $quantity, $color]);
        }
    } else {
        if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
        $found = false;
        foreach ($_SESSION['cart'] as &$item) {
            if ($item['product_id'] == $productId && ($item['color'] ?? 'Standard') === $color) {
                $item['quantity'] += $quantity;
                $found = true;
                break;
            }
        }
        if (!$found) {
            $_SESSION['cart'][] = [
                'product_id' => $productId,
                'quantity' => $quantity,
                'color' => $color
            ];
        }
    }

    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'update') {
    $cartId = $_POST['cart_id'] ?? '';
    $delta = (int)($_POST['delta'] ?? 0);

    if (isset($_SESSION['user_id'])) {
        $stmt = $pdo->prepare("SELECT id, quantity FROM cart WHERE id = ? AND user_id = ?");
        $stmt->execute([(int)$cartId, $_SESSION['user_id']]);
        $item = $stmt->fetch();
        if ($item) {
            $newQty = $item['quantity'] + $delta;
            if ($newQty <= 0) {
                $pdo->prepare("DELETE FROM cart WHERE id = ?")->execute([$item['id']]);
            } else {
                $pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ?")->execute([$newQty, $item['id']]);
            }
        }
    } elseif (isset($_SESSION['cart']) && strpos($cartId, 'sess_') === 0) {
        $idx = (int)substr($cartId, 5);
        if (isset($_SESSION['cart'][$idx])) {
            $_SESSION['cart'][$idx]['quantity'] += $delta;
            if ($_SESSION['cart'][$idx]['quantity'] <= 0) {
                array_splice($_SESSION['cart'], $idx, 1);
            }
        }
    }

    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'remove') {
    $cartId = $_POST['cart_id'] ?? '';

    if (isset($_SESSION['user_id'])) {
        $pdo->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?")->execute([(int)$cartId, $_SESSION['user_id']]);
    } elseif (isset($_SESSION['cart']) && strpos($cartId, 'sess_') === 0) {
        $idx = (int)substr($cartId, 5);
        if (isset($_SESSION['cart'][$idx])) {
            array_splice($_SESSION['cart'], $idx, 1);
        }
    }

    echo json_encode(['success' => true]);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Unknown action']);
