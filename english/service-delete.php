<?php
/**
 * SkillBridge.lk — /english/service-delete.php
 * ---------------------------------------------------------------------
 * Soft-deletes a service (is_active = 0) rather than a hard DELETE, so
 * existing bookings/reviews tied to it stay intact and historically
 * correct. Ownership is re-checked here even though the delete button
 * only appears on the owner's own services.php list — never trust the
 * client alone for a destructive action.
 * ---------------------------------------------------------------------
 */
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
