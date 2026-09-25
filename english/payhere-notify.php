<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/payhere.php';
require_once __DIR__ . '/notifications-helper.php';

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
        exit('Booking not found');
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

    // ---- Concurrency guard (TC-NFR-04) ----
    // Two clients can race to confirm the same freelancer+date. Instead
    // of trusting an earlier "is this date free?" check (which can go
    // stale between two near-simultaneous payments), we let MySQL's
    // UNIQUE constraint on confirmed_slots be the single source of
    // truth: only the payment that inserts FIRST wins the slot.
    if ($bookingStatus === 'confirmed') {
        $freelancerStmt = $pdo->prepare("
            SELECT u.user_id AS freelancer_id
            FROM bookings b
            JOIN services s ON b.service_id = s.service_id
            JOIN freelancer_profiles fp ON s.profile_id = fp.profile_id
            JOIN users u ON fp.user_id = u.user_id
            WHERE b.booking_id = :booking_id
            LIMIT 1
        ");
        $freelancerStmt->execute(['booking_id' => $bookingId]);
        $freelancerRow = $freelancerStmt->fetch();
        $freelancerId = $freelancerRow ? (int) $freelancerRow['freelancer_id'] : null;

        $slotWon = false;
        if ($freelancerId !== null) {
            try {
                $lockSlot = $pdo->prepare("
                    INSERT INTO confirmed_slots (freelancer_id, booking_date, booking_id)
                    SELECT :freelancer_id, b.booking_date, b.booking_id
                    FROM bookings b WHERE b.booking_id = :booking_id
                ");
                $lockSlot->execute(['freelancer_id' => $freelancerId, 'booking_id' => $bookingId]);
                $slotWon = true;
            } catch (PDOException $e) {
                // SQLSTATE 23000 = duplicate key — someone else's payment
                // already grabbed this freelancer+date first.
                if ($e->getCode() !== '23000') {
                    throw $e;
                }
                $slotWon = false;
            }
        }

        if (!$slotWon) {
            $bookingStatus = 'conflict';
        }
    }

    $updateBooking = $pdo->prepare('UPDATE bookings SET status = :status WHERE booking_id = :booking_id');
    $updateBooking->execute(['status' => $bookingStatus, 'booking_id' => $bookingId]);

    // ---- Notify client once the booking is actually confirmed ----
    // (Freelancer already got their "new booking request" notification
    // back in booking.php the moment the booking was created — see
    // TC-NOTIF-02. This is just the client-facing confirmation.)
    if ($bookingStatus === 'confirmed') {
        $infoStmt = $pdo->prepare("
            SELECT b.client_id, s.title
            FROM bookings b
            JOIN services s ON b.service_id = s.service_id
            WHERE b.booking_id = :booking_id
            LIMIT 1
        ");
        $infoStmt->execute(['booking_id' => $bookingId]);
        $info = $infoStmt->fetch();

        if ($info) {
            // TC-NOTIF-01 — Client receives notification on booking confirmation
            create_notification(
                $pdo,
                (int) $info['client_id'],
                'booking_confirmed',
                'Your booking for "' . $info['title'] . '" has been confirmed.',
                $bookingId
            );
        }
    } elseif ($bookingStatus === 'conflict') {
        // TC-NFR-04 — the losing side of the race is flagged, not silently
        // dropped, and the client is told to seek a refund since PayHere
        // already charged them for a slot that's no longer available.
        $clientStmt = $pdo->prepare('SELECT client_id FROM bookings WHERE booking_id = :booking_id');
        $clientStmt->execute(['booking_id' => $bookingId]);
        $clientRow = $clientStmt->fetch();

        if ($clientRow) {
            create_notification(
                $pdo,
                (int) $clientRow['client_id'],
                'booking_conflict',
                'This slot was just booked by someone else. Your payment will be refunded — contact support if you don\'t see it reversed shortly.',
                $bookingId
            );
        }

        error_log('SkillBridge payhere-notify.php: booking ' . $bookingId . ' lost slot race, flagged as conflict');
    }

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