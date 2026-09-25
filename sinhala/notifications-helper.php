<?php
function create_notification(PDO $pdo, int $userId, string $type, string $message, ?int $relatedId = null): void
{
    try {
        $stmt = $pdo->prepare("
            INSERT INTO notifications (user_id, type, message, related_id)
            VALUES (:user_id, :type, :message, :related_id)
        ");
        $stmt->execute([
            'user_id'    => $userId,
            'type'       => $type,
            'message'    => $message,
            'related_id' => $relatedId,
        ]);
    } catch (PDOException $e) {
        error_log('SkillBridge create_notification error: ' . $e->getMessage());
    }
}

function notify_all_admins(PDO $pdo, string $type, string $message, ?int $relatedId = null): void
{
    try {
        $stmt = $pdo->query("SELECT user_id FROM users WHERE role = 'admin' AND is_active = 1");
        foreach ($stmt->fetchAll() as $admin) {
            create_notification($pdo, (int) $admin['user_id'], $type, $message, $relatedId);
        }
    } catch (PDOException $e) {
        error_log('SkillBridge notify_all_admins error: ' . $e->getMessage());
    }
}

function unread_notification_count(PDO $pdo, int $userId): int
{
    try {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = :user_id AND is_read = 0');
        $stmt->execute(['user_id' => $userId]);
        return (int) $stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log('SkillBridge unread_notification_count error: ' . $e->getMessage());
        return 0;
    }
}