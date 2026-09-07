<?php
/**
 * SkillBridge.lk — /tamil/admin-verification-update.php
 * ---------------------------------------------------------------------
 * Sets verified_badge = 1 on a freelancer profile. Admin-only.
 * ---------------------------------------------------------------------
 */
require_once __DIR__ . '/session.php';
require_login('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: admin-verifications.php');
    exit;
}

$submittedToken = $_POST['csrf_token'] ?? '';
if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $submittedToken)) {
    header('Location: admin-verifications.php');
    exit;
}

$profileId = (int) ($_POST['profile_id'] ?? 0);

try {
    $stmt = $pdo->prepare('UPDATE freelancer_profiles SET verified_badge = 1 WHERE profile_id = :profile_id');
    $stmt->execute(['profile_id' => $profileId]);
} catch (PDOException $e) {
    error_log('SkillBridge admin-verification-update.php error: ' . $e->getMessage());
}

header('Location: admin-verifications.php?approved=1');
exit;
