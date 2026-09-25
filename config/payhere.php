<?php
define('PAYHERE_MERCHANT_ID', 'YOUR_MERCHANT_ID');         // TODO: replace
define('PAYHERE_MERCHANT_SECRET', 'YOUR_MERCHANT_SECRET'); // TODO: replace
define('PAYHERE_SANDBOX', true);                            // false when going live
define('PAYHERE_CURRENCY', 'LKR');

// Leave blank to auto-detect from the current request (fine for the
// browser-facing return/cancel URLs). Only needed for the notify_url —
// set it to your ngrok URL while testing locally.
define('PAYHERE_PUBLIC_BASE_URL', '');

define('PAYHERE_CHECKOUT_URL', PAYHERE_SANDBOX
    ? 'https://sandbox.payhere.lk/pay/checkout'
    : 'https://www.payhere.lk/pay/checkout'
);

/**
 * Detects this site's own base URL (scheme + host + path up to the
 * current folder), e.g. http://localhost/skillbridge/english
 */
function payhere_base_url(): string
{
    if (PAYHERE_PUBLIC_BASE_URL !== '') {
        return rtrim(PAYHERE_PUBLIC_BASE_URL, '/');
    }
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
    return $scheme . '://' . $host . $basePath;
}

/**
 * Hash PayHere requires on the checkout form itself, so they can trust
 * the amount/order wasn't tampered with before it reaches them.
 */
function payhere_checkout_hash(string $orderId, float $amount): string
{
    $amountFormatted = number_format($amount, 2, '.', '');
    $secretHash = strtoupper(md5(PAYHERE_MERCHANT_SECRET));
    return strtoupper(md5(
        PAYHERE_MERCHANT_ID . $orderId . $amountFormatted . PAYHERE_CURRENCY . $secretHash
    ));
}

/**
 * Verifies the md5sig PayHere sends to notify_url actually came from
 * PayHere (and wasn't forged) before we trust the payment as real.
 */
function payhere_verify_notify_hash(
    string $merchantId,
    string $orderId,
    string $payhereAmount,
    string $payhereCurrency,
    string $statusCode,
    string $receivedSig
): bool {
    $secretHash = strtoupper(md5(PAYHERE_MERCHANT_SECRET));
    $expected = strtoupper(md5(
        $merchantId . $orderId . $payhereAmount . $payhereCurrency . $statusCode . $secretHash
    ));
    return hash_equals($expected, strtoupper($receivedSig));
}
