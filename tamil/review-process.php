<?php
/**
 * SkillBridge.lk — /tamil/review-process.php
 * ---------------------------------------------------------------------
 * Only the client who made the booking can review it, only once it's
 * 'completed', and only once — the reviews table also has a UNIQUE
 * constraint on booking_id as a second line of defense.
 * ---------------------------------------------------------------------
 */
require_once __DIR__ . '/session.php';
require_login('client');

function back_to_dashboard(): void
{
    header('Location: client-dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    back_to_dashboard();
}

$submittedToken = $_POST['csrf_token'] ?? '';
if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $submittedToken)) {
    back_to_dashboard();
}

$bookingId = (int) ($_POST['booking_id'] ?? 0);
$rating = (int) ($_POST['rating'] ?? 0);
$comment = trim($_POST['comment'] ?? '');
$myId = (int) $_SESSION['user_id'];

if ($rating < 1 || $rating > 5) {
    back_to_dashboard();
}

try {
    // Verify this booking is mine, completed, and not already reviewed.
    $stmt = $pdo->prepare("
        SELECT b.booking_id
        FROM bookings b
        LEFT JOIN reviews r ON r.booking_id = b.booking_id
        WHERE b.booking_id = :booking_id
          AND b.client_id = :client_id
          AND b.status = 'completed'
          AND r.review_id IS NULL
        LIMIT 1
    ");
    $stmt->execute(['booking_id' => $bookingId, 'client_id' => $myId]);

    if ($stmt->fetch()) {
        $insert = $pdo->prepare('INSERT INTO reviews (booking_id, rating, comment) VALUES (:booking_id, :rating, :comment)');
        $insert->execute([
            'booking_id' => $bookingId,
            'rating'     => $rating,
            'comment'    => $comment !== '' ? $comment : null,
        ]);
    }
} catch (PDOException $e) {
    error_log('SkillBridge review-process.php error: ' . $e->getMessage());
}

header('Location: client-dashboard.php?reviewed=1');
exit;
