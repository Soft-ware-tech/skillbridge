<?php
/**
 * SkillBridge.lk — /english/login-process.php
 * ---------------------------------------------------------------------
 * Handles the Log In form POST from login.php (login mode). On any
 * failure, redirects back to login.php?error=invalid_login. On
 * success, starts the session and sends the visitor onward.
 * ---------------------------------------------------------------------
 */
require_once __DIR__ . '/session.php'; // gives us session_start() + $pdo

function back_with_error(string $code): void
{
    header('Location: login.php?error=' . urlencode($code));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}

// ---- CSRF check ----
$submittedToken = $_POST['csrf_token'] ?? '';
if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $submittedToken)) {
    back_with_error('invalid_csrf');
}

$email    = trim($_POST['email'] ?? '');
$password = (string) ($_POST['password'] ?? '');

if ($email === '' || $password === '') {
    back_with_error('missing_fields');
}

try {
    $stmt = $pdo->prepare(
        'SELECT user_id, full_name, password_hash, role
         FROM users
         WHERE email = :email AND is_active = 1
         LIMIT 1'
    );
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        back_with_error('invalid_login');
    }

    $_SESSION['user_id']   = (int) $user['user_id'];
    $_SESSION['role']      = $user['role'];
    $_SESSION['full_name'] = $user['full_name'];

    // Send them back to whatever page sent them to log in (e.g. mid-booking),
    // as long as it's a safe local path. Otherwise: show the post-login
    // "Homepage or Dashboard?" choice instead of guessing for them.
    $redirect = $_POST['redirect'] ?? '';
    $safeRedirect = ($redirect !== '' && !str_starts_with($redirect, 'http') && !str_contains($redirect, '//'))
        ? $redirect
        : 'post-login.php';

    header('Location: ' . $safeRedirect);
    exit;

} catch (PDOException $e) {
    error_log('SkillBridge login-process.php error: ' . $e->getMessage());
    back_with_error('server_error');
}
