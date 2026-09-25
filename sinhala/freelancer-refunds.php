<?php
require_once __DIR__ . '/session.php';
require_login('freelancer');

$myId = (int) $_SESSION['user_id'];

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

try {
    $stmt = $pdo->prepare("
        SELECT rr.refund_id, rr.booking_id, rr.amount, rr.reason, rr.status, rr.requested_at,
               s.title, cl.full_name AS client_name,
               (rr.status = 'pending' AND rr.requested_at < NOW() - INTERVAL 3 DAY) AS is_overdue
        FROM refund_requests rr
        JOIN bookings b ON rr.booking_id = b.booking_id
        JOIN services s ON b.service_id = s.service_id
        JOIN users cl ON rr.client_id = cl.user_id
        WHERE rr.freelancer_id = :freelancer_id
        ORDER BY (rr.status = 'pending') DESC, rr.requested_at DESC
    ");
    $stmt->execute(['freelancer_id' => $myId]);
    $refunds = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('freelancer-refunds.php error: ' . $e->getMessage());
    $refunds = [];
}
?>
<!DOCTYPE html>
<html lang="si">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>මුදල් ආපසු ගෙවීමේ ඉල්ලීම් — SkillBridge.lk</title>
<link rel="stylesheet" href="assets/css/dashboard.css?v=2">
</head>
<body>

<?php $activeNav = 'refunds'; include __DIR__ . '/partials/dashboard-nav.php'; ?>

<main class="dash-page">
  <p class="eyebrow">පාරිභෝගික ඉල්ලීම්</p>
  <h1 class="dash-title">මුදල් ආපසු ගෙවීමේ ඉල්ලීම්</h1>

  <?php if (isset($_GET['approved'])): ?><p class="flash-success">මුදල් ආපසු ගෙවීම අනුමත කර ක්‍රියාත්මක කරන ලදී.</p><?php endif; ?>
  <?php if (isset($_GET['rejected'])): ?><p class="flash-success">මුදල් ආපසු ගෙවීමේ ඉල්ලීම ප්‍රතික්ෂේප කරන ලදී.</p><?php endif; ?>
  <?php if (($_GET['error'] ?? '') === 'stripe_failed'): ?><p class="flash-error">Stripe මුදල් ආපසු ගෙවීම අසාර්ථක විය. පරිපාලකට දැනුම් දී ඇත.</p><?php endif; ?>

  <?php if (empty($refunds)): ?>
    <div class="empty-state">
      <div class="empty-glyph">◎</div>
      <h3>මුදල් ආපසු ගෙවීමේ ඉල්ලීම් නොමැත</h3>
      <p>පාරිභෝගිකයින්ගේ මුදල් ආපසු ගෙවීමේ ඉල්ලීම් ඔබට මෙහි දැකගත හැක.</p>
    </div>
  <?php else: ?>
    <div class="table-list">
      <?php foreach ($refunds as $r): ?>
        <div class="table-row table-row--stack">
          <div class="table-row-top">
            <div class="table-row-main">
              <h3><?php echo htmlspecialchars($r['title'], ENT_QUOTES); ?></h3>
              <span class="table-meta">
                පාරිභෝගිකයා: <?php echo htmlspecialchars($r['client_name'], ENT_QUOTES); ?>
                &middot; LKR <?php echo number_format((float) $r['amount'], 2); ?>
                &middot; ඉල්ලූ දිනය <?php echo htmlspecialchars(date('d M Y', strtotime($r['requested_at'])), ENT_QUOTES); ?>
              </span>
              <p><?php echo nl2br(htmlspecialchars($r['reason'], ENT_QUOTES)); ?></p>
            </div>
            <div class="table-row-actions">
              <span class="status-pill status-<?php echo htmlspecialchars($r['status'], ENT_QUOTES); ?>">
                <?php echo ucfirst($r['status']); ?>
              </span>
              <?php if ($r['is_overdue']): ?>
                <span class="status-pill status-cancelled">කල් ඉකුත් වූ</span>
              <?php endif; ?>
            </div>
          </div>

          <?php if ($r['status'] === 'pending'): ?>
            <div class="table-row-actions">
              <form method="POST" action="refund-decision.php" style="display:inline;">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES); ?>">
                <input type="hidden" name="refund_id" value="<?php echo (int) $r['refund_id']; ?>">
                <input type="hidden" name="action" value="approve">
                <button type="submit" class="btn btn-primary btn-sm" onclick="return confirm('Approve and refund this client via Stripe now?');">අනුමත කර මුදල් ආපසු දෙන්න</button>
              </form>
              <form method="POST" action="refund-decision.php" style="display:inline;">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES); ?>">
                <input type="hidden" name="refund_id" value="<?php echo (int) $r['refund_id']; ?>">
                <input type="hidden" name="action" value="reject">
                <button type="submit" class="btn btn-outline btn-sm">ප්‍රතික්ෂේප කරන්න</button>
              </form>
            </div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</main>
</body>
</html>