<?php
function parseJsonField($json, $default = []) {
    if (is_string($json)) {
        $decoded = json_decode($json, true);
        return is_array($decoded) ? $decoded : $default;
    }
    return is_array($json) ? $json : $default;
}

function getProductImage($images) {
    $imgs = parseJsonField($images);
    return !empty($imgs) ? $imgs[0] : '/assets/img/placeholder.png';
}

function getProductImages($images) {
    return parseJsonField($images);
}

function getProductColors($colors) {
    return parseJsonField($colors);
}

function getProductFeatures($features) {
    $f = parseJsonField($features);
    return array_map(function($item) {
        return is_array($item) ? ($item['value'] ?? $item[0] ?? '') : $item;
    }, $f);
}

function getProductDetails($details) {
    $d = parseJsonField($details);
    return array_map(function($item) {
        if (is_array($item)) {
            return [
                'key' => $item['key'] ?? $item[0] ?? '',
                'val' => $item['val'] ?? $item[1] ?? ''
            ];
        }
        return ['key' => '', 'val' => ''];
    }, $d);
}

function escape($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /?page=login');
        exit;
    }
}

function requireAdmin() {
    requireLogin();
    if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
        header('Location: /');
        exit;
    }
}

function generateOrderRef() {
    return 'HAPPUTY-' . strtoupper(substr(uniqid(), -6)) . rand(100, 999);
}

function formatPrice($amount) {
    return 'Ksh ' . number_format($amount, 2);
}

function getStatusBadge($status) {
    $colors = [
        'pending' => 'bg-yellow-100 text-yellow-800',
        'confirmed' => 'bg-blue-100 text-blue-800',
        'processing' => 'bg-indigo-100 text-indigo-800',
        'shipped' => 'bg-purple-100 text-purple-800',
        'delivered' => 'bg-green-100 text-green-800',
        'cancelled' => 'bg-red-100 text-red-800'
    ];
    $color = $colors[$status] ?? 'bg-gray-100 text-gray-800';
    return "<span class=\"inline-block px-2 py-0.5 text-[10px] font-bold uppercase $color\">$status</span>";
}

function sendEmail($to, $subject, $body) {
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: " . SITE_NAME . " <" . ADMIN_EMAIL . ">\r\n";
    $headers .= "Reply-To: " . ADMIN_EMAIL . "\r\n";
    return mail($to, $subject, $body, $headers);
}

function orderEmailBody($order, $items, $message) {
    $rows = '';
    foreach ($items as $item) {
        $rows .= '<tr><td style="padding:8px;border-bottom:1px solid #eee;font-size:13px">' . htmlspecialchars($item['product_title']) . ' x ' . (int)$item['quantity'] . '</td>'
               . '<td style="padding:8px;border-bottom:1px solid #eee;font-size:13px;text-align:right">Ksh ' . number_format((float)$item['price'] * (int)$item['quantity']) . '</td></tr>';
    }
    $fee = (float)($order['delivery_fee'] ?? 0);
    return '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body style="font-family:sans-serif;background:#f5f5f5;padding:20px">'
         . '<div style="max-width:560px;margin:0 auto;background:#fff;border:1px solid #ddd">'
         . '<div style="background:#000;color:#fff;padding:20px;text-align:center;font-size:18px;font-weight:900;letter-spacing:2px;text-transform:uppercase">' . SITE_NAME . '</div>'
         . '<div style="padding:24px">'
         . '<p style="font-size:14px;color:#333">' . nl2br(htmlspecialchars($message)) . '</p>'
         . '<table style="width:100%;border-collapse:collapse;margin:16px 0">'
         . '<tr><td style="padding:6px 0;color:#666;font-size:12px">Order Ref</td><td style="padding:6px 0;font-weight:700;font-size:14px">' . htmlspecialchars($order['order_ref']) . '</td></tr>'
         . '<tr><td style="padding:6px 0;color:#666;font-size:12px">Status</td><td style="padding:6px 0;font-weight:700;font-size:14px;text-transform:capitalize">' . htmlspecialchars($order['status']) . '</td></tr>'
         . '<tr><td style="padding:6px 0;color:#666;font-size:12px">Delivery</td><td style="padding:6px 0;font-weight:700;font-size:14px">' . htmlspecialchars($order['delivery_location'] ?? 'N/A') . '</td></tr>'
         . '</table>'
         . '<table style="width:100%;border-collapse:collapse;margin:16px 0">'
         . '<thead><tr style="background:#f5f5f5"><th style="padding:8px;text-align:left;font-size:11px;text-transform:uppercase;color:#666">Item</th><th style="padding:8px;text-align:right;font-size:11px;text-transform:uppercase;color:#666">Total</th></tr></thead>'
         . '<tbody>' . $rows . '</tbody></table>'
         . '<table style="width:100%;border-collapse:collapse;margin:16px 0">'
         . '<tr><td style="padding:4px 0;font-size:13px;color:#666">Subtotal</td><td style="padding:4px 0;font-size:13px;text-align:right">Ksh ' . number_format($order['total'] - $fee) . '</td></tr>'
         . ($fee > 0 ? '<tr><td style="padding:4px 0;font-size:13px;color:#666">Delivery Fee</td><td style="padding:4px 0;font-size:13px;text-align:right">Ksh ' . number_format($fee) . '</td></tr>' : '')
         . '<tr style="font-weight:700;font-size:16px"><td style="padding:8px 0;border-top:2px solid #000">Total Paid</td><td style="padding:8px 0;border-top:2px solid #000;text-align:right">Ksh ' . number_format($order['total']) . '</td></tr>'
         . '</table>'
         . '<p style="font-size:12px;color:#999;margin-top:24px">Track your order: <a href="https://' . ($_SERVER['HTTP_HOST'] ?? 'haputty.co.ke') . '/?page=order-tracking&ref=' . urlencode($order['order_ref']) . '" style="color:#000">' . htmlspecialchars($order['order_ref']) . '</a></p>'
         . '</div></div></body></html>';
}
