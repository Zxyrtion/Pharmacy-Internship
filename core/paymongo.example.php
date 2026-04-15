<?php
// PayMongo Configuration - COPY THIS FILE TO paymongo.php AND ADD YOUR KEYS
// Get your test keys from: https://dashboard.paymongo.com/developers
define('PAYMONGO_SECRET_KEY', 'sk_test_YOUR_SECRET_KEY_HERE');
define('PAYMONGO_PUBLIC_KEY', 'pk_test_YOUR_PUBLIC_KEY_HERE');
define('PAYMONGO_BASE_URL', 'https://api.paymongo.com/v1');
define('APP_BASE_URL', 'http://localhost/internship');

function createCheckoutSession($amount_php, $description, $rx_id, $line_items) {
    $payload = [
        'data' => [
            'attributes' => [
                'send_email_receipt'   => false,
                'show_description'     => true,
                'show_line_items'      => true,
                'line_items'           => $line_items,
                'payment_method_types' => ['gcash'],
                'description'          => $description,
                'success_url'          => APP_BASE_URL . '/Users/customer/payment_success.php?rx_id=' . $rx_id,
                'cancel_url'           => APP_BASE_URL . '/Users/customer/payment.php?rx_id=' . $rx_id . '&cancelled=1',
            ]
        ]
    ];
    $ch = curl_init(PAYMONGO_BASE_URL . '/checkout_sessions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Basic ' . base64_encode(PAYMONGO_SECRET_KEY . ':'),
        ],
    ]);
    $response  = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $data = json_decode($response, true);
    if ($http_code === 200 && isset($data['data']['attributes']['checkout_url'])) {
        return ['success' => true, 'checkout_url' => $data['data']['attributes']['checkout_url'], 'session_id' => $data['data']['id']];
    }
    return ['success' => false, 'error' => $data['errors'][0]['detail'] ?? 'Failed to create checkout session.'];
}

function getCheckoutSession($session_id) {
    $ch = curl_init(PAYMONGO_BASE_URL . '/checkout_sessions/' . $session_id);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['Accept: application/json', 'Authorization: Basic ' . base64_encode(PAYMONGO_SECRET_KEY . ':')],
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true);
}
?>
