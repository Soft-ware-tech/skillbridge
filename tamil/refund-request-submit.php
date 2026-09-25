<?php
require_once __DIR__ . '/session.php';
require_login('client');
require_once __DIR__ . '/notifications-helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: client-dashboard.php');
    exit;
}

$submittedToken = $_POST['csrf_token'] ?? '';
if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $submittedToken)) {
    header('Location: client-dashboard.php');
    exit;
}

$myId = (int) $_SESSION['user_id'];
$bookingId = (int) ($_POST['booking_id'] ?? 0);
$reason = trim($_POST['reason'] ?? '');
$accountNumber = trim($_POST['account_number'] ?? '');

if ($reason === '' || $accountNumber === '') {
    header('Location: refund-request.php?booking=' . $bookingId . '&error=missing');
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT b.booking_id, b.status, b.client_id, p.payment_id, p.amount, p.payment_status,
               fp.user_id AS freelancer_id
        FROM bookings b
        JOIN services s ON b.service_id = s.service_id
        JOIN freelancer_profiles fp ON s.profile_id = fp.profile_id
        LEFT JOIN payments p ON p.booking_id = b.booking_id
        WHERE b.booking_id = :booking_id AND b.client_id = :client_id
        LIMIT 1
    ");
    $stmt->execute(['booking_id' => $bookingId, 'client_id' => $myId]);
    $booking = $stmt->fetch();

    if (!$booking || $booking['status'] !== 'completed' || ($booking['payment_status'] ?? '') !== 'completed') {
        header('Location: client-dashboard.php');
        exit;
    }

    $dupStmt = $pdo->prepare("SELECT refund_id FROM refund_requests WHERE booking_id = :booking_id AND status = 'pending' LIMIT 1");
    $dupStmt->execute(['booking_id' => $bookingId]);
    if ($dupStmt->fetch()) {
        header('Location: client-dashboard.php?refund_exists=1');
        exit;
    }

    $insert = $pdo->prepare("
        INSERT INTO refund_requests (booking_id, payment_id, client_id, freelancer_id, amount, reason, account_number)
        VALUES (:booking_id, :payment_id, :client_id, :freelancer_id, :amount, :reason, :account_number)
    ");
    $insert->execute([
        'booking_id'     => $bookingId,
        'payment_id'     => $booking['payment_id'],
        'client_id'      => $myId,
        'freelancer_id'  => $booking['freelancer_id'],
        'amount'         => $booking['amount'],
        'reason'         => $reason,
        'account_number' => $accountNumber,
    ]);

    create_notification($pdo, (int) $booking['freelancer_id'], 'refund_requested',
        'A client requested a refund for booking #' . $bookingId . '.', $bookingId);
    notify_all_admins($pdo, 'refund_requested',
        'A refund was requested for booking #' . $bookingId . '.', $bookingId);

    header('Location: client-dashboard.php?refund_requested=1');
    exit;

} catch (PDOException $e) {
    error_log('refund-request-submit.php error: ' . $e->getMessage());
    header('Location: client-dashboard.php');
    exit;
}