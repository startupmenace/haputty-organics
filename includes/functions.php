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
    return 'HAPUTTY-' . strtoupper(substr(uniqid(), -6)) . rand(100, 999);
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
