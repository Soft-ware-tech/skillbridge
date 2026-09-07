<?php
require_once __DIR__ . '/session.php';
require_login();

header('Content-Type: application/json');

$myId = (int) $_SESSION['user_id'];
$withId = (int) ($_GET['with'] ?? 0);
$sinceId = (int) ($_GET['since_id'] ?? 0);

if ($withId <= 0) {
    http_response_code(400);
    echo json_encode(['messages' => []]);
    exit;
}

const ONLINE_WINDOW_SECONDS = 60;

try {
    $markRead = $pdo->prepare("
        UPDATE messages SET is_read = 1
        WHERE receiver_id = :me AND sender_id = :them AND is_read = 0
    ");
    $markRead->execute(['me' => $myId, 'them' => $withId]);

    $stmt = $pdo->prepare("
        SELECT message_id, sender_id, content, sent_at
        FROM messages
        WHERE message_id > :since_id
          AND (
                (sender_id = :me1 AND receiver_id = :them1)
             OR (sender_id = :them2 AND receiver_id = :me2)
          )
        ORDER BY sent_at ASC
    ");
    $stmt->execute([
        'since_id' => $sinceId,
        'me1' => $myId, 'them1' => $withId,
        'them2' => $withId, 'me2' => $myId,
    ]);
    $messages = $stmt->fetchAll();

    // ---- Presence of the other person, refreshed every poll ----
    $presenceStmt = $pdo->prepare('SELECT last_seen FROM users WHERE user_id = :user_id LIMIT 1');
    $presenceStmt->execute(['user_id' => $withId]);
    $otherLastSeen = $presenceStmt->fetch()['last_seen'] ?? null;
    $otherOnline = $otherLastSeen
        ? (time() - strtotime($otherLastSeen)) <= ONLINE_WINDOW_SECONDS
        : false;

    echo json_encode([
        'messages'        => $messages,
        'other_last_seen' => $otherLastSeen,
        'other_online'    => $otherOnline,
    ]);
} catch (PDOException $e) {
    error_log('SkillBridge messages-poll.php error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['messages' => []]);
}