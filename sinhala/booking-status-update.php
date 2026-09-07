<?php
/**
 * SkillBridge.lk — /sinhala/booking-status-update.php
 * ---------------------------------------------------------------------
 * Lets a freelancer mark one of their bookings 'completed' or
 * 'cancelled'. Ownership is re-checked here (never trust the button
 * alone), and only a small set of transitions is allowed — a freelancer
 * can't jump a booking straight to 'confirmed' themselves, since that
 * status is set by a real payment (see payhere-notify.php).
 * ---------------------------------------------------------------------
 */
require_once __DIR__ . '/session.php';
require_login('freelancer');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: bookings.php');
    exit;
}

$submittedToken = $_POST['csrf_token'] ?? '';
if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $submittedToken)) {
    header('Location: bookings.php');
    exit;
}

$bookingId = (int) ($_POST['booking_id'] ?? 0);
$newStatus = $_POST['new_status'] ?? '';
$myId = (int) $_SESSION['user_id'];

// Only these transitions are allowed from this page.
$allowedTransitions = [
    'completed' => ['confirmed'],
    'cancelled' => ['pending', 'confirmed'],
];

if (!isset($allowedTransitions[$newStatus])) {
    header('Location: bookings.php');
    exit;
}

try {
    // Verify this booking belongs to one of MY services, and its current
    // status is one we're allowed to move on from.
    $stmt = $pdo->prepare("
        SELECT b.booking_id, b.status
        FROM bookings b
        JOIN services s ON b.service_id = s.service_id
        JOIN freelancer_profiles fp ON s.profile_id = fp.profile_id
        WHERE b.booking_id = :booking_id AND fp.user_id = :user_id
        LIMIT 1
    ");
    $stmt->execute(['booking_id' => $bookingId, 'user_id' => $myId]);
    $booking = $stmt->fetch();

    if ($booking && in_array($booking['status'], $allowedTransitions[$newStatus], true)) {
        $update = $pdo->prepare('UPDATE bookings SET status = :status WHERE booking_id = :booking_id');
        $update->execute(['status' => $newStatus, 'booking_id' => $bookingId]);
    }
} catch (PDOException $e) {
    error_log('SkillBridge booking-status-update.php error: ' . $e->getMessage());
}

header('Location: bookings.php');
exit;
