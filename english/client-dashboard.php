<?php
require_once __DIR__ . '/session.php';
require_login('client');

$myId = (int) $_SESSION['user_id'];
$filter = in_array($_GET['status'] ?? '', ['pending', 'confirmed', 'completed', 'cancelled'], true)
    ? $_GET['status']
    : '';

$bookings = [];
$dbError = false;
$dbErrorDetail = '';
$flashReviewed = isset($_GET['reviewed']);
$flashRefundRequested = isset($_GET['refund_requested']);
$flashRefundExists = isset($_GET['refund_exists']);
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

try {
    $sql = "
        SELECT b.booking_id, b.booking_date, b.status,
               s.title, s.price,
               fp.profile_id, u.full_name AS freelancer_name, u.user_id AS freelancer_user_id,
                             r.review_id, r.rating AS my_rating, r.comment AS my_comment,
               p.payment_status,
               rr.status AS refund_status
        FROM bookings b
        JOIN services s ON b.service_id = s.service_id
        JOIN freelancer_profiles fp ON s.profile_id = fp.profile_id
        JOIN users u ON fp.user_id = u.user_id
        LEFT JOIN reviews r ON r.booking_id = b.booking_id
        LEFT JOIN payments p ON p.booking_id = b.booking_id
        LEFT JOIN refund_requests rr ON rr.booking_id = b.booking_id AND rr.status = 'pending'
        WHERE b.client_id = :client_id
    ";
    $params = ['client_id' => $myId];

    if ($filter !== '') {
        $sql .= ' AND b.status = :status';
        $params['status'] = $filter;
    }
    $sql .= ' ORDER BY b.created_at DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $bookings = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('SkillBridge client-dashboard.php error: ' . $e->getMessage());
    $dbError = true;
    $dbErrorDetail = $e->getMessage();
}

function status_label(string $status): string
{
    return match ($status) {
        'pending'        => 'Pending',
        'confirmed'      => 'Confirmed',
        'completed'      => 'Completed',
        'cancelled'      => 'Cancelled',
        'payment_failed' => 'Payment Failed',
        default          => ucfirst($status),
    };
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Bookings — SkillBridge.lk</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Pirata+One&family=Anton&family=Space+Grotesk:wght@400;500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

<link rel="stylesheet" href="assets/css/dashboard.css?v=2">
</head>
<body>

<div class="scene" aria-hidden="true">
  <div class="orb orb-violet"></div>
  <div class="orb orb-cyan"></div>
</div>

<?php $activeNav = 'dashboard'; include __DIR__ . '/partials/dashboard-nav.php'; ?>

<main class="dash-page">

  <p class="eyebrow">Your Activity</p>
  <h1 class="dash-title">My Bookings</h1>

  <?php if ($flashReviewed): ?>
    <p class="flash-success">Review submitted — thank you!</p>
  <?php endif; ?>

    <?php if ($flashReviewed): ?>
    <p class="flash-success">Review submitted — thank you!</p>
  <?php endif; ?>

  <?php if ($flashRefundRequested): ?>
    <p class="flash-success">Refund request submitted — the freelancer and our admin team have been notified.</p>
  <?php endif; ?>

  <?php if ($flashRefundExists): ?>
    <p class="flash-error">A refund request for this booking is already pending.</p>
  <?php endif; ?>

  <div class="filter-row">
    <a href="client-dashboard.php" class="chip <?php echo $filter === '' ? 'is-active' : ''; ?>">All</a>
    <a href="client-dashboard.php?status=pending" class="chip <?php echo $filter === 'pending' ? 'is-active' : ''; ?>">Pending</a>
    <a href="client-dashboard.php?status=confirmed" class="chip <?php echo $filter === 'confirmed' ? 'is-active' : ''; ?>">Confirmed</a>
    <a href="client-dashboard.php?status=completed" class="chip <?php echo $filter === 'completed' ? 'is-active' : ''; ?>">Completed</a>
    <a href="client-dashboard.php?status=cancelled" class="chip <?php echo $filter === 'cancelled' ? 'is-active' : ''; ?>">Cancelled</a>
  </div>

  <?php if ($dbError): ?>
    <div class="empty-state">
      <div class="empty-glyph">!</div>
      <h3>Couldn't load your bookings</h3>
      <p>Something went wrong reaching the database. Please try again shortly.</p>
      <?php if (isset($_GET['debug'])): ?>
        <p class="debug-detail"><?php echo htmlspecialchars($dbErrorDetail, ENT_QUOTES); ?></p>
      <?php endif; ?>
    </div>

  <?php elseif (empty($bookings)): ?>
    <div class="empty-state">
      <div class="empty-glyph">◎</div>
      <h3>No bookings here</h3>
      <p><?php echo $filter === '' ? "You haven't booked anything yet." : 'Nothing matches this filter yet.'; ?></p>
      <?php if ($filter === ''): ?>
        <a href="search.php" class="btn btn-primary">Find a Freelancer</a>
      <?php endif; ?>
    </div>

  <?php else: ?>
    <div class="table-list">
      <?php foreach ($bookings as $bk): ?>
        <div class="table-row table-row--stack">
          <div class="table-row-top">
            <div class="table-row-main">
              <h3><?php echo htmlspecialchars($bk['title'], ENT_QUOTES); ?></h3>
              <span class="table-meta">
                with <a href="profile.php?profile=<?php echo (int) $bk['profile_id']; ?>"><?php echo htmlspecialchars($bk['freelancer_name'], ENT_QUOTES); ?></a>
                &middot; <?php echo htmlspecialchars(date('d M Y', strtotime($bk['booking_date'])), ENT_QUOTES); ?>
                &middot; LKR <?php echo number_format((float) $bk['price'], 0); ?>
              </span>
            </div>
            <div class="table-row-actions">
              <span class="status-pill status-<?php echo htmlspecialchars($bk['status'], ENT_QUOTES); ?>"><?php echo status_label($bk['status']); ?></span>
              <?php if ($bk['status'] === 'pending'): ?>
                <a href="payment.php?booking=<?php echo (int) $bk['booking_id']; ?>" class="btn btn-outline btn-sm">Pay Now</a>
              <?php endif; ?>
              <a href="messages.php?with=<?php echo (int) $bk['freelancer_user_id']; ?>" class="btn btn-outline btn-sm">Message</a>
            </div>
          </div>

          <?php if ($bk['status'] === 'completed'): ?>
            <?php if ($bk['review_id']): ?>
              <div class="my-review">
                <span class="my-review-stars"><?php echo str_repeat('★', (int) $bk['my_rating']) . str_repeat('☆', 5 - (int) $bk['my_rating']); ?></span>
                <?php if (!empty($bk['my_comment'])): ?>
                  <p><?php echo nl2br(htmlspecialchars($bk['my_comment'], ENT_QUOTES)); ?></p>
                <?php endif; ?>
              </div>
            <?php else: ?>
              <details class="review-disclosure">
                <summary>Leave a Review</summary>
                <form method="POST" action="review-process.php" class="review-form">
                  <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES); ?>">
                  <input type="hidden" name="booking_id" value="<?php echo (int) $bk['booking_id']; ?>">

                  <div class="field">
                    <label for="rating-<?php echo (int) $bk['booking_id']; ?>">Rating</label>
                    <select id="rating-<?php echo (int) $bk['booking_id']; ?>" name="rating" required>
                      <option value="">Select a rating</option>
                      <option value="5">★★★★★ — Excellent</option>
                      <option value="4">★★★★☆ — Good</option>
                      <option value="3">★★★☆☆ — Okay</option>
                      <option value="2">★★☆☆☆ — Below Average</option>
                      <option value="1">★☆☆☆☆ — Poor</option>
                    </select>
                  </div>

                  <div class="field">
                    <label for="comment-<?php echo (int) $bk['booking_id']; ?>">Comment (optional)</label>
                    <textarea id="comment-<?php echo (int) $bk['booking_id']; ?>" name="comment" rows="3" placeholder="How did it go?"></textarea>
                  </div>

                  <button type="submit" class="btn btn-primary btn-sm">Submit Review</button>
                </form>
              </details>
                          <?php if (($bk['payment_status'] ?? '') === 'completed'): ?>
              <div class="table-row-actions" style="margin-top: 0.75rem;">
                <?php if ($bk['refund_status'] === 'pending'): ?>
                  <span class="status-pill status-pending">Refund Pending</span>
                <?php elseif (($bk['payment_status'] ?? '') === 'refunded'): ?>
                  <span class="status-pill status-refunded">Refunded</span>
                <?php else: ?>
                  <a href="refund-request.php?booking=<?php echo (int) $bk['booking_id']; ?>" class="btn btn-outline btn-sm">Request Refund</a>
                <?php endif; ?>
              </div>
            <?php endif; ?>
            <?php endif; ?>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

</main>

</body>
</html>
