<?php
/**
 * SkillBridge.lk — PayHere notify webhook (English)
 * ---------------------------------------------------------------------
 * PayHere's OWN SERVER calls this URL directly after a payment attempt
 * — never the customer's browser. This is the only place a payment
 * should be trusted as real; the customer-facing return_url
 * (payhere-return.php) is just a friendly redirect and must never mark
 * a booking as paid by itself, since a browser redirect can be faked.
 *
 * No session exists on this request (PayHere doesn't send cookies), so
 * we connect to the DB directly instead of going through session.php.
 * ---------------------------------------------------------------------
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/payhere.php';

header('Content-Type: text/plain');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed');
}

$merchantId      = $_POST['merchant_id'] ?? '';
$orderId         = $_POST['order_id'] ?? '';
$payhereAmount   = $_POST['payhere_amount'] ?? '';
$payhereCurrency = $_POST['payhere_currency'] ?? '';
$statusCode      = $_POST['status_code'] ?? '';
$md5sig          = $_POST['md5sig'] ?? '';

if ($merchantId === '' || $orderId === '' || $md5sig === '') {
    http_response_code(400);
    exit('Missing fields');
}

// ---- Verify this really came from PayHere ----
$isValid = payhere_verify_notify_hash($merchantId, $orderId, $payhereAmount, $payhereCurrency, $statusCode, $md5sig);
if (!$isValid) {
    error_log('SkillBridge payhere-notify.php: hash mismatch for order ' . $orderId);
    http_response_code(400);
    exit('Invalid signature');
}

// order_id was built as "SKB-<booking_id>" in payment.php
if (!preg_match('/^SKB-(\d+)$/', $orderId, $matches)) {
    http_response_code(400);
    exit('Unrecognized order id');
}
$bookingId = (int) $matches[1];

try {
    $stmt = $pdo->prepare('SELECT booking_id, status FROM bookings WHERE booking_id = :booking_id LIMIT 1');
    $stmt->execute(['booking_id' => $bookingId]);
    $booking = $stmt->fetch();

    if (!$booking) {
        http_response_code(404);
        exit('முன்பதிவு கிடைக்கவில்லை');
    }

    // Idempotent — PayHere may retry the notify call; don't double-insert.
    $existing = $pdo->prepare('SELECT payment_id FROM payments WHERE booking_id = :booking_id LIMIT 1');
    $existing->execute(['booking_id' => $bookingId]);
    if ($existing->fetch()) {
        exit('OK — already recorded');
    }

    // PayHere status codes: 2 = success, 0 = pending, -1 = cancelled,
    // -2 = failed, -3 = chargeback.
    $paymentStatus = match ($statusCode) {
        '2'  => 'completed',
        '0'  => 'pending',
        default => 'failed',
    };

    $pdo->beginTransaction();

    $insertPayment = $pdo->prepare("
        INSERT INTO payments (booking_id, amount, gateway, payment_status, gateway_ref, paid_at)
        VALUES (:booking_id, :amount, 'payhere', :payment_status, :gateway_ref, NOW())
    ");
    $insertPayment->execute([
        'booking_id'     => $bookingId,
        'amount'         => $payhereAmount,
        'payment_status' => $paymentStatus,
        'gateway_ref'    => $_POST['payment_id'] ?? $orderId,
    ]);

    $bookingStatus = $paymentStatus === 'completed' ? 'confirmed' : 'payment_failed';
    $updateBooking = $pdo->prepare('UPDATE bookings SET status = :status WHERE booking_id = :booking_id');
    $updateBooking->execute(['status' => $bookingStatus, 'booking_id' => $bookingId]);

    $pdo->commit();

    exit('OK');

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('SkillBridge payhere-notify.php error: ' . $e->getMessage());
    http_response_code(500);
    exit('Server error');
}
