<?php
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
