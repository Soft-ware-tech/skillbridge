<?php
/**
 * COPY THIS FILE to payhere.php and fill in your real merchant details.
 * payhere.php itself is gitignored so real credentials never get pushed.
 * ---------------------------------------------------------------------
 * SkillBridge.lk — PayHere configuration
 * ---------------------------------------------------------------------
 * Shared by every language folder, same as config/db.php.
 *
 * SETUP (required before real payments work):
 *   1. Create a PayHere account: https://www.payhere.lk (sandbox is free)
 *   2. Dashboard -> Integrations -> add a domain, grab your Merchant ID
 *      and Merchant Secret from there.
 *   3. Paste them into PAYHERE_MERCHANT_ID / PAYHERE_MERCHANT_SECRET below.
 *   4. Set PAYHERE_SANDBOX to false only when you switch to a live,
 *      approved PayHere account.
 *
 * IMPORTANT — local XAMPP / localhost testing:
 *   PayHere confirms payment by calling your notify_url directly from
 *   THEIR server (not through the customer's browser). "localhost" is
 *   not reachable from the internet, so that call can never arrive
 *   during local development unless you tunnel it — e.g. with ngrok:
 *
 *       ngrok http 80
 *
 *   then temporarily set PAYHERE_PUBLIC_BASE_URL below to the
 *   https://xxxx.ngrok-free.app URL ngrok gives you. Without this,
 *   the checkout will work but the booking will stay "pending" until
 *   the notify call gets through — see payment.php for a local-only
 *   test-payment fallback button for exactly this situation.
 * ---------------------------------------------------------------------
 */

define('PAYHERE_MERCHANT_ID', 'YOUR_MERCHANT_ID');
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
