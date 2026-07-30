<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

$page = $_GET['page'] ?? 'home';

require_once __DIR__ . '/includes/header.php';

switch ($page) {
    case 'product':
        require __DIR__ . '/pages/product.php';
        break;
    case 'checkout':
        require __DIR__ . '/pages/checkout.php';
        break;
    case 'login':
        require __DIR__ . '/pages/login.php';
        break;
    case 'register':
        require __DIR__ . '/pages/register.php';
        break;
    case 'logout':
        session_destroy();
        header('Location: /');
        exit;
    case 'account':
        require __DIR__ . '/pages/account.php';
        break;
    case 'order-tracking':
        require __DIR__ . '/pages/order-tracking.php';
        break;
    case 'wishlist':
        require __DIR__ . '/pages/wishlist.php';
        break;
    case 'orders':
        require __DIR__ . '/pages/orders.php';
        break;
    default:
        require __DIR__ . '/pages/home.php';
        break;
}

require_once __DIR__ . '/includes/footer.php';
