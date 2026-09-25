<?php

define('STRIPE_SECRET_KEY', 'YOUR_STRIPE_SECRET_KEY');
define('STRIPE_PUBLISHABLE_KEY', 'YOUR_STRIPE_PUBLISHABLE_KEY');
define('STRIPE_CURRENCY', 'lkr'); // Stripe accepts LKR for checkout

function stripe_base_url(): string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
    return $scheme . '://' . $host . $basePath;
}

function stripe_api_request(string $method, string $endpoint, array $params = []): array
{
    $ch = curl_init();
    $url = 'https://api.stripe.com/v1/' . $endpoint;

    if ($method === 'GET' && !empty($params)) {
        $url .= '?' . http_build_query($params);
    }

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERPWD, STRIPE_SECRET_KEY . ':');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false) {
        error_log('Stripe API cURL error: ' . curl_error($ch));
        return [];
    }

    $decoded = json_decode($response, true);
    if ($httpCode >= 400) {
        error_log('Stripe API error (' . $httpCode . '): ' . $response);
    }
    return $decoded ?? [];
}