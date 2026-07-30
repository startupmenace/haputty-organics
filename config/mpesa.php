<?php
define('MPESA_CONSUMER_KEY', 'kbc9BKifIarvEKPnyPIGDrCbncGw5iz4cDKWu85tThpNrT1x');
define('MPESA_CONSUMER_SECRET', 'b0IggS7DNiFkoFOPqyfzhGSCIXI8GGCAiViuguPOf6rtFlSpbDue4kLLSkSa8QAr');
define('MPESA_SHORTCODE', '174379');
define('MPESA_PASSKEY', 'bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919');

// Sandbox endpoints
define('MPESA_AUTH_URL', 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials');
define('MPESA_STK_URL', 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest');
define('MPESA_QUERY_URL', 'https://sandbox.safaricom.co.ke/mpesa/stkpushquery/v1/query');

// Callback URL (force HTTPS + detect domain)
define('MPESA_CALLBACK_URL', 'https://' . ($_SERVER['HTTP_HOST'] ?? 'haputty.co.ke') . '/mpesa/callback.php');

define('ADMIN_EMAIL', 'orders@happutysorganics.com');
define('SITE_NAME', 'HAPUTTY ORGANICS');

function mpesaAuth() {
    $ch = curl_init(MPESA_AUTH_URL);
    curl_setopt_array($ch, [
        CURLOPT_HTTPHEADER => ['Authorization: Basic ' . base64_encode(MPESA_CONSUMER_KEY . ':' . MPESA_CONSUMER_SECRET)],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT => 30,
    ]);
    $res = curl_exec($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($http !== 200) return null;
    $data = json_decode($res, true);
    return $data['access_token'] ?? null;
}

function mpesaStkPush($phone, $amount, $orderRef, $accountRef = null) {
    $token = mpesaAuth();
    if (!$token) return ['success' => false, 'message' => 'Failed to authenticate with M-Pesa'];

    $timestamp = date('YmdHis');
    $password = base64_encode(MPESA_SHORTCODE . MPESA_PASSKEY . $timestamp);

    // Format phone: remove +, 0 prefix, ensure 254 format
    $phone = preg_replace('/[^0-9]/', '', $phone);
    if (substr($phone, 0, 1) === '0') $phone = '254' . substr($phone, 1);
    if (substr($phone, 0, 3) !== '254') $phone = '254' . $phone;

    $payload = [
        'BusinessShortCode' => MPESA_SHORTCODE,
        'Password' => $password,
        'Timestamp' => $timestamp,
        'TransactionType' => 'CustomerPayBillOnline',
        'Amount' => round($amount),
        'PartyA' => $phone,
        'PartyB' => MPESA_SHORTCODE,
        'PhoneNumber' => $phone,
        'CallBackURL' => MPESA_CALLBACK_URL,
        'AccountReference' => $accountRef ?: $orderRef,
        'TransactionDesc' => 'HAPUTTY ORGANICS Order ' . $orderRef,
    ];

    $ch = curl_init(MPESA_STK_URL);
    curl_setopt_array($ch, [
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
        ],
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

    if ($http === 200 && ($data['ResponseCode'] ?? '1') === '0') {
        return [
            'success' => true,
            'merchant_request_id' => $data['MerchantRequestID'] ?? '',
            'checkout_request_id' => $data['CheckoutRequestID'] ?? '',
            'message' => 'M-Pesa prompt sent to your phone',
        ];
    }

    $msg = $data['errorMessage'] ?? ($data['ResponseDescription'] ?? 'M-Pesa request failed');
    return ['success' => false, 'message' => $msg];
}

function mpesaQuery($checkoutRequestId) {
    $token = mpesaAuth();
    if (!$token) return ['success' => false, 'message' => 'Failed to authenticate'];

    $timestamp = date('YmdHis');
    $password = base64_encode(MPESA_SHORTCODE . MPESA_PASSKEY . $timestamp);

    $payload = [
        'BusinessShortCode' => MPESA_SHORTCODE,
        'Password' => $password,
        'Timestamp' => $timestamp,
        'CheckoutRequestID' => $checkoutRequestId,
    ];

    $ch = curl_init(MPESA_QUERY_URL);
    curl_setopt_array($ch, [
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
        ],
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT => 30,
    ]);
    $res = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($res, true);

    // ResultCode 0 means the transaction was successful
    $resultCode = $data['ResultCode'] ?? 1;
    $resultDesc = $data['ResultDesc'] ?? 'Unknown';

    if ($resultCode === 0) {
        return ['success' => true, 'paid' => true, 'message' => $resultDesc, 'data' => $data];
    } elseif ($resultCode === 1032) {
        // Transaction cancelled by user
        return ['success' => true, 'paid' => false, 'message' => 'Transaction cancelled by user', 'code' => 1032];
    } elseif ($resultCode === 1037) {
        // Timeout - still processing
        return ['success' => true, 'paid' => false, 'message' => 'Transaction timeout', 'code' => 1037];
    } else {
        return ['success' => true, 'paid' => false, 'message' => $resultDesc, 'code' => $resultCode];
    }
}
