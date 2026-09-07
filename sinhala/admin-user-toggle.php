<?php
/**
 * SkillBridge.lk — /sinhala/admin-user-toggle.php
 * ---------------------------------------------------------------------
 * Flips a user's is_active flag. Admin-only. An admin can't deactivate
 * their own account through this (avoids accidentally locking
 * themselves out).
 * ---------------------------------------------------------------------
 */
require_once __DIR__ . '/session.php';
require_login('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: admin-users.php');
    exit;
}

$submittedToken = $_POST['csrf_token'] ?? '';
if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $submittedToken)) {
    header('Location: admin-users.php');
    exit;
}

$userId = (int) ($_POST['user_id'] ?? 0);
$myId = (int) $_SESSION['user_id'];

if ($userId === $myId) {
    header('Location: admin-users.php');
    exit;
}

try {
    $stmt = $pdo->prepare('UPDATE users SET is_active = NOT is_active WHERE user_id = :user_id');
    $stmt->execute(['user_id' => $userId]);
} catch (PDOException $e) {
    error_log('SkillBridge admin-user-toggle.php error: ' . $e->getMessage());
}

header('Location: admin-users.php');
exit;
