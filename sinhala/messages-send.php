<?php
require_once __DIR__ . '/session.php';
require_login();

header('Content-Type: application/json');

$myId = (int) $_SESSION['user_id'];
$withId = (int) ($_POST['with'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $withId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'වැරදි ඉල්ලීමකි.']);
    exit;
}

$submittedToken = $_POST['csrf_token'] ?? '';
if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $submittedToken)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'ඔබේ සැසිය කල් ඉකුත් විය — කරුණාකර පිටුව නැවත පූරණය කරන්න.']);
    exit;
}

$content = trim($_POST['content'] ?? '');
if ($content === '') {
    echo json_encode(['success' => false, 'error' => 'යැවීමට පෙර පණිවිඩයක් ටයිප් කරන්න.']);
    exit;
}

try {
    $checkUser = $pdo->prepare('SELECT user_id FROM users WHERE user_id = :user_id LIMIT 1');
    $checkUser->execute(['user_id' => $withId]);
    if (!$checkUser->fetch()) {
        echo json_encode(['success' => false, 'error' => 'එම පරිශීලකයා තවදුරටත් නොපවතී.']);
        exit;
    }

    $insert = $pdo->prepare("
        INSERT INTO messages (sender_id, receiver_id, content)
        VALUES (:sender_id, :receiver_id, :content)
    ");
    $insert->execute([
        'sender_id'   => $myId,
        'receiver_id' => $withId,
        'content'     => $content,
    ]);
    $messageId = (int) $pdo->lastInsertId();

    $row = $pdo->prepare('SELECT message_id, sender_id, content, sent_at FROM messages WHERE message_id = :id LIMIT 1');
    $row->execute(['id' => $messageId]);
    $message = $row->fetch();

    echo json_encode(['success' => true, 'message' => $message]);
} catch (PDOException $e) {
    error_log('SkillBridge messages-send.php error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'එය යැවීමේදී යම් දෝෂයක් සිදුවිය. කරුණාකර නැවත උත්සාහ කරන්න.']);
}