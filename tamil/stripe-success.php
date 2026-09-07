<?php
require_once __DIR__ . '/session.php';
require_login();
require_once __DIR__ . '/../config/stripe.php';

$bookingId = (int) ($_GET['booking'] ?? 0);
$sessionId = $_GET['session_id'] ?? '';

if ($bookingId <= 0 || $sessionId === '') {
    header('Location: search.php');
    exit;
}

$session = stripe_api_request('GET', 'checkout/sessions/' . urlencode($sessionId));

if (($session['payment_status'] ?? '') !== 'paid') {
    header('Location: payment.php?booking=' . $bookingId . '&error=1');
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT booking_id, client_id, status FROM bookings WHERE booking_id = :booking_id LIMIT 1");
    $stmt->execute(['booking_id' => $bookingId]);
    $booking = $stmt->fetch();

    if (!$booking || (int) $booking['client_id'] !== (int) $_SESSION['user_id']) {
        header('Location: search.php');
        exit;
    }

    $existing = $pdo->prepare('SELECT payment_id FROM payments WHERE booking_id = :booking_id LIMIT 1');
    $existing->execute(['booking_id' => $bookingId]);
    if ($existing->fetch()) {
        header('Location: booking-confirmation.php?booking=' . $bookingId);
        exit;
    }

    $pdo->beginTransaction();

    $amount = ($session['amount_total'] ?? 0) / 100;

    $insertPayment = $pdo->prepare("
        INSERT INTO payments (booking_id, amount, gateway, payment_status, gateway_ref, paid_at)
        VALUES (:booking_id, :amount, 'stripe', 'completed', :gateway_ref, NOW())
    ");
    $insertPayment->execute([
        'booking_id'  => $bookingId,
        'amount'      => $amount,
        'gateway_ref' => $session['payment_intent'] ?? $sessionId,
    ]);

    $updateBooking = $pdo->prepare("UPDATE bookings SET status = 'confirmed' WHERE booking_id = :booking_id");
    $updateBooking->execute(['booking_id' => $bookingId]);

    $pdo->commit();

    header('Location: booking-confirmation.php?booking=' . $bookingId);
    exit;

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('stripe-success.php error: ' . $e->getMessage());
    header('Location: payment.php?booking=' . $bookingId . '&error=1');
    exit;
}