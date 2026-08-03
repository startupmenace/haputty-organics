<?php
define('PALPLUSS_API_KEY', 'pp_live_e95b8262478d946115b07adf2abe23a6fe3e9a89930a6c08');
define('PALPLUSS_CHANNEL_ID', 'cac5f925-020a-48cc-b0c6-fc3a01ec7926');
define('PALPLUSS_BASE_URL', 'https://api.palpluss.com/v1');

// Callback/webhook URL (force HTTPS + detect domain)
define('PALPLUSS_CALLBACK_URL', 'https://' . ($_SERVER['HTTP_HOST'] ?? 'haputty.co.ke') . '/mpesa/callback.php');

define('ADMIN_EMAIL', 'orders@happutysorganics.com');
define('SITE_NAME', 'HAPPUTY ORGANICS');

function palplussAuthHeader() {
    return 'Authorization: Basic ' . base64_encode(PALPLUSS_API_KEY . ':');
}

function mpesaStkPush($phone, $amount, $orderRef, $accountRef = null) {
    // Format phone: remove non-digits, ensure 254 format
    $phone = preg_replace('/[^0-9]/', '', $phone);
    if (substr($phone, 0, 1) === '0') $phone = '254' . substr($phone, 1);
    if (substr($phone, 0, 3) !== '254') $phone = '254' . $phone;

    $payload = [
        'amount' => round($amount),
        'phone' => $phone,
        'accountReference' => $accountRef ?: $orderRef,
        'transactionDesc' => SITE_NAME . ' Order ' . $orderRef,
        'channelId' => PALPLUSS_CHANNEL_ID,
        'callbackUrl' => PALPLUSS_CALLBACK_URL,
    ];

    $ch = curl_init(PALPLUSS_BASE_URL . '/payments/stk');
    curl_setopt_array($ch, [
        CURLOPT_HTTPHEADER => [palplussAuthHeader(), 'Content-Type: application/json'],
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT => 30,
    ]);
    $res = curl_exec($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $data = json_decode($res, true);
    $data = $data['data'] ?? $data;

    if ($http === 200 && !empty($data['transactionId'])) {
        return [
            'success' => true,
            'transaction_id' => $data['transactionId'],
            'message' => 'Payment prompt sent to your phone',
        ];
    }

    $msg = $data['message'] ?? ($data['error'] ?? ($data['result_desc'] ?? 'Payment request failed'));
    return ['success' => false, 'message' => $msg];
}

function mpesaQuery($transactionId) {
    $ch = curl_init(PALPLUSS_BASE_URL . '/transactions/' . urlencode($transactionId));
    curl_setopt_array($ch, [
        CURLOPT_HTTPHEADER => [palplussAuthHeader()],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT => 30,
    ]);
    $res = curl_exec($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $data = json_decode($res, true);
    $t = $data['data'] ?? $data;

    $status = strtoupper($t['status'] ?? 'unknown');
    $resultDesc = $t['result_desc'] ?? ($t['message'] ?? 'Unknown');

    if ($status === 'SUCCESS') {
        return ['success' => true, 'paid' => true, 'message' => $resultDesc, 'code' => 0, 'data' => $data];
    } elseif ($status === 'PENDING') {
        // Still processing
        return ['success' => true, 'paid' => false, 'message' => 'Transaction pending', 'code' => 1037, 'data' => $data];
    } elseif ($status === 'CANCELLED') {
        // Cancelled by user
        return ['success' => true, 'paid' => false, 'message' => 'Transaction cancelled by user', 'code' => 1032, 'data' => $data];
    } else {
        return ['success' => true, 'paid' => false, 'message' => $resultDesc, 'code' => $http === 200 ? 1 : $http, 'data' => $data];
    }
}