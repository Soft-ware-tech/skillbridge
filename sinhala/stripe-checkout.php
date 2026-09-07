<?php
require_once __DIR__ . '/session.php';
require_login();
require_once __DIR__ . '/../config/stripe.php';

$bookingId = (int) ($_GET['booking'] ?? 0);

try {
    $stmt = $pdo->prepare("
        SELECT b.booking_id, b.client_id, s.title, s.price, p.payment_status
        FROM bookings b
        JOIN services s ON b.service_id = s.service_id
        LEFT JOIN payments p ON p.booking_id = b.booking_id
        WHERE b.booking_id = :booking_id
        LIMIT 1
    ");
    $stmt->execute(['booking_id' => $bookingId]);
    $booking = $stmt->fetch();
} catch (PDOException $e) {
    error_log('stripe-checkout.php error: ' . $e->getMessage());
    header('Location: search.php');
    exit;
}

if (!$booking || (int) $booking['client_id'] !== (int) $_SESSION['user_id']) {
    header('Location: search.php');
    exit;
}

if ($booking['payment_status'] === 'completed') {
    header('Location: booking-confirmation.php?booking=' . $bookingId);
    exit;
}

$baseUrl = stripe_base_url();

$session = stripe_api_request('POST', 'checkout/sessions', [
    'mode'                => 'payment',
    'success_url'         => $baseUrl . '/stripe-success.php?booking=' . $bookingId . '&session_id={CHECKOUT_SESSION_ID}',
    'cancel_url'          => $baseUrl . '/stripe-cancel.php?booking=' . $bookingId,
    'client_reference_id' => 'SKB-' . $bookingId,
    'line_items' => [
        [
            'price_data' => [
                'currency'     => STRIPE_CURRENCY,
                'product_data' => ['name' => $booking['title']],
                'unit_amount'  => (int) round(((float) $booking['price']) * 100),
            ],
            'quantity' => 1,
        ],
    ],
    'metadata' => ['booking_id' => $bookingId],
]);

if (empty($session['url'])) {
    error_log('Stripe checkout session creation failed for booking ' . $bookingId);
    header('Location: payment.php?booking=' . $bookingId . '&error=1');
    exit;
}

header('Location: ' . $session['url']);
exit;