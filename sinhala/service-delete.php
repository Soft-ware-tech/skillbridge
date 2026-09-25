<?php
require_once __DIR__ . '/session.php';
require_login('freelancer');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: services.php');
    exit;
}

$submittedToken = $_POST['csrf_token'] ?? '';
if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $submittedToken)) {
    header('Location: services.php');
    exit;
}

$serviceId = (int) ($_POST['service_id'] ?? 0);
$myId = (int) $_SESSION['user_id'];

try {
    // Block the delete if this service (still owned by the requester)
    // has a booking that's pending or confirmed.
    $checkStmt = $pdo->prepare("
        SELECT COUNT(*) FROM bookings b
        JOIN services s ON b.service_id = s.service_id
        JOIN freelancer_profiles fp ON s.profile_id = fp.profile_id
        WHERE s.service_id = :service_id
          AND fp.user_id = :user_id
          AND b.status IN ('pending', 'confirmed')
    ");
    $checkStmt->execute(['service_id' => $serviceId, 'user_id' => $myId]);
    $activeBookings = (int) $checkStmt->fetchColumn();

    if ($activeBookings > 0) {
        header('Location: services.php?error=has_active_booking');
        exit;
    }

    $stmt = $pdo->prepare("
        UPDATE services s
        JOIN freelancer_profiles fp ON s.profile_id = fp.profile_id
        SET s.is_active = 0
        WHERE s.service_id = :service_id AND fp.user_id = :user_id
    ");
    $stmt->execute(['service_id' => $serviceId, 'user_id' => $myId]);
} catch (PDOException $e) {
    error_log('SkillBridge service-delete.php error: ' . $e->getMessage());
}

header('Location: services.php?deleted=1');
exit;
