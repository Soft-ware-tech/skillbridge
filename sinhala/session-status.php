<?php
/**
 * SkillBridge.lk — /sinhala/session-status.php
 * ---------------------------------------------------------------------
 * index(SIN).html is a plain static file, so it can never read
 * $_SESSION directly — that's why the landing page nav always showed
 * "Log In / Join Free", even to someone who was already logged in.
 *
 * This tiny endpoint is what the landing page's JS calls on load to
 * find out. Plain text, not JSON (keeping this project's PHP simple):
 *
 *   loggedIn|First Name|role|photo_path
 *
 * loggedIn is "1" or "0". The rest are empty when loggedIn is "0".
 * role is one of client/freelancer/admin — the landing page uses it to
 * point the Dashboard link at the right file, since each role has its
 * own dashboard (dashboard.php / client-dashboard.php /
 * admin-dashboard.php). photo_path is empty when the user hasn't
 * uploaded a profile photo yet, in which case the nav falls back to
 * an initial-letter avatar.
 * ---------------------------------------------------------------------
 */
require_once __DIR__ . '/session.php';

header('Content-Type: text/plain; charset=utf-8');

if (isset($_SESSION['user_id'], $_SESSION['full_name'])) {
    $firstName = explode(' ', $_SESSION['full_name'])[0];
    $role = $_SESSION['role'] ?? '';
    $photoPath = '';

    try {
        $stmt = $pdo->prepare('SELECT photo_path FROM users WHERE user_id = :user_id LIMIT 1');
        $stmt->execute(['user_id' => $_SESSION['user_id']]);
        $photoPath = (string) ($stmt->fetch()['photo_path'] ?? '');
    } catch (PDOException $e) {
        error_log('SkillBridge session-status.php error: ' . $e->getMessage());
    }

    echo "1|" . $firstName . "|" . $role . "|" . $photoPath;
} else {
    echo "0|||";
}
