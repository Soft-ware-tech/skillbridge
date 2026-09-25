<?php
session_start();

// If this session never went through the language picker (e.g. someone
// bookmarked this page directly), default to English instead of bouncing
// away — this folder IS the English site, and losing a form POST (like a
// register/login submission) over a missing session value would be a
// confusing, silent failure.
if (!isset($_SESSION['language'])) {
    $_SESSION['language'] = 'english';
}

// Shared DB connection — same database used by all 3 language folders.
require_once __DIR__ . '/../config/db.php';

/**
 * Small helper other pages in this folder can use once needed, e.g.:
 *   require_login('client');
 */
function require_login(?string $role = null): void
{
    if (!isset($_SESSION['user_id'])) {
        $current = $_SERVER['REQUEST_URI'] ?? 'index(ENG).html';
        header('Location: login.php?redirect=' . urlencode($current));
        exit;
    }
    if ($role !== null && ($_SESSION['role'] ?? null) !== $role) {
        http_response_code(403);
        die('You do not have access to this page.');
    }

    // ---- Presence heartbeat ----
    // Every authenticated page (messages, poll, dashboard, search, ...)
    // calls require_login(), so this touches last_seen on every request
    // the logged-in user makes — no separate heartbeat endpoint needed.
    global $pdo;
    try {
        $touch = $pdo->prepare('UPDATE users SET last_seen = NOW() WHERE user_id = :user_id');
        $touch->execute(['user_id' => (int) $_SESSION['user_id']]);
    } catch (PDOException $e) {
        error_log('SkillBridge session.php last_seen update error: ' . $e->getMessage());
    }
}