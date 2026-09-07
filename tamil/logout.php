<?php
/**
 * SkillBridge.lk — Logout (English)
 * ---------------------------------------------------------------------
 * Clears the session (login state) but leaves the language cookie
 * alone, so the visitor stays on English after logging out instead of
 * being sent back to the language picker.
 * ---------------------------------------------------------------------
 */
session_start();

$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
}
session_destroy();

header('Location: index(TAM).html');
exit;
