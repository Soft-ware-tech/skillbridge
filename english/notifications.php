<?php
require_once __DIR__ . '/session.php';
require_login();
require_once __DIR__ . '/notifications-helper.php';

$myId = (int) $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("
        SELECT notification_id, type, message, is_read, created_at
        FROM notifications WHERE user_id = :user_id
        ORDER BY created_at DESC LIMIT 50
    ");
    $stmt->execute(['user_id' => $myId]);
    $notifications = $stmt->fetchAll();

    $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = :user_id AND is_read = 0")
        ->execute(['user_id' => $myId]);
} catch (PDOException $e) {
    error_log('notifications.php error: ' . $e->getMessage());
    $notifications = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Notifications — SkillBridge.lk</title>
<link rel="stylesheet" href="assets/css/dashboard.css?v=2">
</head>
<body>
<?php $activeNav = 'notifications'; include __DIR__ . '/partials/dashboard-nav.php'; ?>
<main class="dash-page">
  <h1 class="dash-title">Notifications</h1>
  <?php if (empty($notifications)): ?>
    <div class="empty-state"><h3>No notifications yet</h3></div>
  <?php else: ?>
    <div class="table-list">
      <?php foreach ($notifications as $n): ?>
        <div class="table-row">
          <p><?php echo htmlspecialchars($n['message'], ENT_QUOTES); ?></p>
          <span class="table-meta"><?php echo htmlspecialchars(date('d M Y, h:i A', strtotime($n['created_at'])), ENT_QUOTES); ?></span>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</main>
</body>
</html>