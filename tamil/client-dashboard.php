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
        'pending'        => 'நிலுவையில்',
        'confirmed'      => 'உறுதிசெய்யப்பட்டது',
        'completed'      => 'முடிக்கப்பட்டது',
        'cancelled'      => 'ரத்து செய்யப்பட்டது',
        'payment_failed' => 'கட்டணம் தோல்வியடைந்தது',
        default          => ucfirst($status),
    };
}
?>
<!DOCTYPE html>
<html lang="ta">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>எனது முன்பதிவுகள் — SkillBridge.lk</title>

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

  <p class="eyebrow">உங்கள் செயல்பாடு</p>
  <h1 class="dash-title">எனது முன்பதிவுகள்</h1>

  <?php if ($flashReviewed): ?>
    <p class="flash-success">மதிப்புரை சமர்ப்பிக்கப்பட்டது — நன்றி!</p>
  <?php endif; ?>

    <?php if ($flashReviewed): ?>
    <p class="flash-success">மதிப்புரை சமர்ப்பிக்கப்பட்டது — நன்றி!</p>
  <?php endif; ?>

  <?php if ($flashRefundRequested): ?>
    <p class="flash-success">பணத்திரும்பக் கோரிக்கை சமர்ப்பிக்கப்பட்டது — ஃப்ரீலான்சர் மற்றும் எங்கள் நிர்வாகக் குழுவிற்குத் தெரிவிக்கப்பட்டுள்ளது.</p>
  <?php endif; ?>

  <?php if ($flashRefundExists): ?>
    <p class="flash-error">இந்த முன்பதிவுக்கான பணத்திரும்பக் கோரிக்கை ஏற்கனவே நிலுவையில் உள்ளது.</p>
  <?php endif; ?>

  <div class="filter-row">
    <a href="client-dashboard.php" class="chip <?php echo $filter === '' ? 'is-active' : ''; ?>">அனைத்தும்</a>
    <a href="client-dashboard.php?status=pending" class="chip <?php echo $filter === 'pending' ? 'is-active' : ''; ?>">நிலுவையில்</a>
    <a href="client-dashboard.php?status=confirmed" class="chip <?php echo $filter === 'confirmed' ? 'is-active' : ''; ?>">உறுதிசெய்யப்பட்டது</a>
    <a href="client-dashboard.php?status=completed" class="chip <?php echo $filter === 'completed' ? 'is-active' : ''; ?>">முடிக்கப்பட்டது</a>
    <a href="client-dashboard.php?status=cancelled" class="chip <?php echo $filter === 'cancelled' ? 'is-active' : ''; ?>">ரத்து செய்யப்பட்டது</a>
  </div>

  <?php if ($dbError): ?>
    <div class="empty-state">
      <div class="empty-glyph">!</div>
      <h3>உங்கள் முன்பதிவுகளை ஏற்ற முடியவில்லை</h3>
      <p>தரவுத்தளத்தை அணுகுவதில் ஏதோ தவறு நடந்தது. சிறிது நேரத்தில் மீண்டும் முயற்சிக்கவும்.</p>
      <?php if (isset($_GET['debug'])): ?>
        <p class="debug-detail"><?php echo htmlspecialchars($dbErrorDetail, ENT_QUOTES); ?></p>
      <?php endif; ?>
    </div>

  <?php elseif (empty($bookings)): ?>
    <div class="empty-state">
      <div class="empty-glyph">◎</div>
      <h3>இங்கு முன்பதிவுகள் இல்லை</h3>
      <p><?php echo $filter === '' ? "You haven't booked anything yet." : 'இந்த வடிகட்டலுடன் எதுவும் பொருந்தவில்லை.'; ?></p>
      <?php if ($filter === ''): ?>
        <a href="search.php" class="btn btn-primary">ஃப்ரீலான்சரைத் தேடுங்கள்</a>
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
                <a href="payment.php?booking=<?php echo (int) $bk['booking_id']; ?>" class="btn btn-outline btn-sm">இப்போது செலுத்துங்கள்</a>
              <?php endif; ?>
              <a href="messages.php?with=<?php echo (int) $bk['freelancer_user_id']; ?>" class="btn btn-outline btn-sm">செய்தி</a>
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
                <summary>மதிப்புரை எழுதுங்கள்</summary>
                <form method="POST" action="review-process.php" class="review-form">
                  <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES); ?>">
                  <input type="hidden" name="booking_id" value="<?php echo (int) $bk['booking_id']; ?>">

                  <div class="field">
                    <label for="rating-<?php echo (int) $bk['booking_id']; ?>">மதிப்பீடு</label>
                    <select id="rating-<?php echo (int) $bk['booking_id']; ?>" name="rating" required>
                      <option value="">ஒரு மதிப்பீட்டைத் தேர்ந்தெடுக்கவும்</option>
                      <option value="5">★★★★★ — சிறப்பானது</option>
                      <option value="4">★★★★☆ — நல்லது</option>
                      <option value="3">★★★☆☆ — சரி</option>
                      <option value="2">★★☆☆☆ — சராசரிக்கு கீழ்</option>
                      <option value="1">★☆☆☆☆ — மோசமானது</option>
                    </select>
                  </div>

                  <div class="field">
                    <label for="comment-<?php echo (int) $bk['booking_id']; ?>">கருத்து (விருப்பத்தேர்வு)</label>
                    <textarea id="comment-<?php echo (int) $bk['booking_id']; ?>" name="comment" rows="3" placeholder="அது எப்படி இருந்தது?"></textarea>
                  </div>

                  <button type="submit" class="btn btn-primary btn-sm">மதிப்புரையைச் சமர்ப்பிக்கவும்</button>
                </form>
              </details>
                          <?php if (($bk['payment_status'] ?? '') === 'completed'): ?>
              <div class="table-row-actions" style="margin-top: 0.75rem;">
                <?php if ($bk['refund_status'] === 'pending'): ?>
                  <span class="status-pill status-pending">பணத்திரும்பம் நிலுவையில்</span>
                <?php elseif (($bk['payment_status'] ?? '') === 'refunded'): ?>
                  <span class="status-pill status-refunded">பணம் திரும்பச் செலுத்தப்பட்டது</span>
                <?php else: ?>
                  <a href="refund-request.php?booking=<?php echo (int) $bk['booking_id']; ?>" class="btn btn-outline btn-sm">பணத்திரும்பம் கோரவும்</a>
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
