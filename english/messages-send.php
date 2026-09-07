<?php
require_once __DIR__ . '/session.php';
require_login();

header('Content-Type: application/json');

$myId = (int) $_SESSION['user_id'];
$withId = (int) ($_POST['with'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $withId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Bad request.']);
    exit;
}

$submittedToken = $_POST['csrf_token'] ?? '';
if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $submittedToken)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Your session expired — please reload the page.']);
    exit;
}

$content = trim($_POST['content'] ?? '');
if ($content === '') {
    echo json_encode(['success' => false, 'error' => 'Type a message before sending.']);
    exit;
}

try {
    $checkUser = $pdo->prepare('SELECT user_id FROM users WHERE user_id = :user_id LIMIT 1');
    $checkUser->execute(['user_id' => $withId]);
    if (!$checkUser->fetch()) {
        echo json_encode(['success' => false, 'error' => 'That user no longer exists.']);
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
    echo json_encode(['success' => false, 'error' => 'Something went wrong sending that. Please try again.']);
}