<?php
/**
 * SkillBridge.lk — Admin: All Refund Requests (English)
 */
require_once __DIR__ . '/session.php';
require_login('admin');

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

try {
    $stmt = $pdo->query("
        SELECT rr.refund_id, rr.booking_id, rr.amount, rr.status, rr.requested_at, rr.processed_by,
               s.title, cl.full_name AS client_name, fr.full_name AS freelancer_name,
               (rr.status = 'pending' AND rr.requested_at < NOW() - INTERVAL 3 DAY) AS is_overdue
        FROM refund_requests rr
        JOIN bookings b ON rr.booking_id = b.booking_id
        JOIN services s ON b.service_id = s.service_id
        JOIN users cl ON rr.client_id = cl.user_id
        JOIN users fr ON rr.freelancer_id = fr.user_id
        ORDER BY is_overdue DESC, (rr.status = 'pending') DESC, rr.requested_at DESC
    ");
    $refunds = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('admin-refunds.php error: ' . $e->getMessage());
    $refunds = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Refund Requests — Admin — SkillBridge.lk</title>
<link rel="stylesheet" href="assets/css/dashboard.css?v=2">
</head>
<body>

<?php $activeNav = 'refunds'; include __DIR__ . '/partials/dashboard-nav.php'; ?>

<main class="dash-page">
  <p class="eyebrow">Platform-wide</p>
  <h1 class="dash-title">Refund Requests</h1>

  <?php if (isset($_GET['forced'])): ?><p class="flash-success">Refund force-processed successfully.</p><?php endif; ?>
  <?php if (($_GET['error'] ?? '') === 'stripe_failed'): ?><p class="flash-error">Stripe refund failed. Check the payment manually.</p><?php endif; ?>

  <?php if (empty($refunds)): ?>
    <div class="empty-state">
      <div class="empty-glyph">◎</div>
      <h3>No refund requests yet</h3>
    </div>
  <?php else: ?>
    <div class="table-list">
      <?php foreach ($refunds as $r): ?>
        <div class="table-row table-row--stack">
          <div class="table-row-top">
            <div class="table-row-main">
              <h3><?php echo htmlspecialchars($r['title'], ENT_QUOTES); ?> — #<?php echo (int) $r['booking_id']; ?></h3>
              <span class="table-meta">
                Client: <?php echo htmlspecialchars($r['client_name'], ENT_QUOTES); ?>
                &middot; Freelancer: <?php echo htmlspecialchars($r['freelancer_name'], ENT_QUOTES); ?>
                &middot; LKR <?php echo number_format((float) $r['amount'], 2); ?>
                &middot; Requested <?php echo htmlspecialchars(date('d M Y', strtotime($r['requested_at'])), ENT_QUOTES); ?>
              </span>
            </div>
            <div class="table-row-actions">
              <span class="status-pill status-<?php echo htmlspecialchars($r['status'], ENT_QUOTES); ?>">
                <?php echo ucfirst($r['status']); ?>
              </span>
              <?php if ($r['is_overdue']): ?>
                <span class="status-pill status-cancelled">⚠ OVERDUE (&gt;3 days)</span>
              <?php endif; ?>
            </div>
          </div>

          <?php if ($r['status'] === 'pending'): ?>
            <div class="table-row-actions">
              <form method="POST" action="admin-refund-force.php">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES); ?>">
                <input type="hidden" name="refund_id" value="<?php echo (int) $r['refund_id']; ?>">
                <button type="submit" class="btn btn-primary btn-sm" onclick="return confirm('Force this refund via Stripe now?');">Force Refund</button>
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