<?php
require_once __DIR__ . '/session.php'; // gives us session_start() + $pdo

function back_with_error(string $code): void
{
    header('Location: login.php?mode=register&error=' . urlencode($code));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php?mode=register');
    exit;
}

// ---- CSRF check ----
$submittedToken = $_POST['csrf_token'] ?? '';
if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $submittedToken)) {
    back_with_error('invalid_csrf');
}

// ---- Gather + validate input ----
$firstName = trim($_POST['first_name'] ?? '');
$lastName  = trim($_POST['last_name'] ?? '');
$email     = trim($_POST['email'] ?? '');
$password  = (string) ($_POST['password'] ?? '');
$role      = ($_POST['role'] ?? '') === 'freelancer' ? 'freelancer' : 'client';

if ($firstName === '' || $lastName === '' || $email === '' || $password === '') {
    back_with_error('missing_fields');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    back_with_error('invalid_email');
}

if (strlen($password) < 8) {
    back_with_error('weak_password');
}

$fullName = $firstName . ' ' . $lastName;
$language = $_SESSION['language'] ?? 'english';

try {
    // ---- Uniqueness check ----
    $checkStmt = $pdo->prepare('SELECT user_id FROM users WHERE email = :email LIMIT 1');
    $checkStmt->execute(['email' => $email]);
    if ($checkStmt->fetch()) {
        back_with_error('email_taken');
    }

    // ---- Create the account ----
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    $pdo->beginTransaction();

    $insertUser = $pdo->prepare(
        'INSERT INTO users (full_name, email, password_hash, role, language_pref)
         VALUES (:full_name, :email, :password_hash, :role, :language_pref)'
    );
    $insertUser->execute([
        'full_name'      => $fullName,
        'email'          => $email,
        'password_hash'  => $passwordHash,
        'role'           => $role,
        'language_pref'  => $language,
    ]);

    $userId = (int) $pdo->lastInsertId();

    if ($role === 'freelancer') {
        $insertProfile = $pdo->prepare(
            'INSERT INTO freelancer_profiles (user_id) VALUES (:user_id)'
        );
        $insertProfile->execute(['user_id' => $userId]);
    }

    $pdo->commit();

    // ---- Log them straight in ----
    $_SESSION['user_id']   = $userId;
    $_SESSION['role']      = $role;
    $_SESSION['full_name'] = $fullName;

    // Send them back to whatever page sent them here (e.g. mid-booking),
    // as long as it's a safe local path. Otherwise: show the post-login
    // "Homepage or Dashboard?" choice instead of guessing for them.
    $redirect = $_POST['redirect'] ?? '';
    $safeRedirect = ($redirect !== '' && !str_starts_with($redirect, 'http') && !str_contains($redirect, '//'))
        ? $redirect
        : 'post-login.php';

    header('Location: ' . $safeRedirect);
    exit;

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('SkillBridge register-process.php error: ' . $e->getMessage());
    back_with_error('server_error');
}
