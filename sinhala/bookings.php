<?php
require_once __DIR__ . '/session.php';
require_login('freelancer');

$myId = (int) $_SESSION['user_id'];
$filter = in_array($_GET['status'] ?? '', ['pending', 'confirmed', 'completed', 'cancelled'], true)
    ? $_GET['status']
    : '';

$bookings = [];
$dbError = false;
$dbErrorDetail = '';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

try {
    $sql = "
        SELECT b.booking_id, b.booking_date, b.status, b.created_at,
               b.latitude, b.longitude,
               s.title, s.price, cl.full_name AS client_name
        FROM bookings b
        JOIN services s ON b.service_id = s.service_id
        JOIN freelancer_profiles fp ON s.profile_id = fp.profile_id
        JOIN users cl ON b.client_id = cl.user_id
        WHERE fp.user_id = :user_id
    ";
    $params = ['user_id' => $myId];

    if ($filter !== '') {
        $sql .= ' AND b.status = :status';
        $params['status'] = $filter;
    }
    $sql .= ' ORDER BY b.created_at DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $bookings = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('SkillBridge bookings.php error: ' . $e->getMessage());
    $dbError = true;
    $dbErrorDetail = $e->getMessage();
}

function status_label(string $status): string
{
    return match ($status) {
        'pending'        => 'පොරොත්තුවෙන්',
        'confirmed'      => 'තහවුරු කර ඇත',
        'completed'      => 'සම්පූර්ණ කරන ලදී',
        'cancelled'      => 'අවලංගු කරන ලදී',
        'payment_failed' => 'ගෙවීම අසාර්ථක විය',
        default          => ucfirst($status),
    };
}
?>
<!DOCTYPE html>
<html lang="si">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>වෙන්කිරීම් — SkillBridge.lk</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Pirata+One&family=Anton&family=Space+Grotesk:wght@400;500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

<link rel="stylesheet" href="assets/css/dashboard.css?v=2">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
</head>
<body>

<div class="scene" aria-hidden="true">
  <div class="orb orb-violet"></div>
  <div class="orb orb-cyan"></div>
</div>

<?php $activeNav = 'bookings'; include __DIR__ . '/partials/dashboard-nav.php'; ?>

<main class="dash-page">

  <p class="eyebrow">වෙන්කිරීම් ඉල්ලීම්</p>
  <h1 class="dash-title">වෙන්කිරීම්</h1>

  <div class="filter-row">
    <a href="bookings.php" class="chip <?php echo $filter === '' ? 'is-active' : ''; ?>">සියල්ල</a>
    <a href="bookings.php?status=pending" class="chip <?php echo $filter === 'pending' ? 'is-active' : ''; ?>">පොරොත්තුවෙන්</a>
    <a href="bookings.php?status=confirmed" class="chip <?php echo $filter === 'confirmed' ? 'is-active' : ''; ?>">තහවුරු කර ඇත</a>
    <a href="bookings.php?status=completed" class="chip <?php echo $filter === 'completed' ? 'is-active' : ''; ?>">සම්පූර්ණ කරන ලදී</a>
    <a href="bookings.php?status=cancelled" class="chip <?php echo $filter === 'cancelled' ? 'is-active' : ''; ?>">අවලංගු කරන ලදී</a>
  </div>

  <?php if ($dbError): ?>
    <div class="empty-state">
      <div class="empty-glyph">!</div>
      <h3>වෙන්කිරීම් පූරණය කළ නොහැකි විය</h3>
      <p>දත්ත සමුදායට ළඟාවීමේදී යම් දෝෂයක් සිදුවිය. කරුණාකර මොහොතකින් නැවත උත්සාහ කරන්න.</p>
      <?php if (isset($_GET['debug'])): ?>
        <p class="debug-detail"><?php echo htmlspecialchars($dbErrorDetail, ENT_QUOTES); ?></p>
      <?php endif; ?>
    </div>

  <?php elseif (empty($bookings)): ?>
    <div class="empty-state">
      <div class="empty-glyph">◎</div>
      <h3>මෙහි වෙන්කිරීම් නොමැත</h3>
      <p>මෙම පෙරහන සමඟ ගැලපෙන කිසිවක් නොමැත.</p>
    </div>

  <?php else: ?>
    <div class="table-list">
      <?php foreach ($bookings as $bk): ?>
        <div class="table-row">
          <div class="table-row-main">
            <h3><?php echo htmlspecialchars($bk['title'], ENT_QUOTES); ?></h3>
            <span class="table-meta">
              <?php echo htmlspecialchars($bk['client_name'], ENT_QUOTES); ?>
              &middot; <?php echo htmlspecialchars(date('d M Y', strtotime($bk['booking_date'])), ENT_QUOTES); ?>
              &middot; LKR <?php echo number_format((float) $bk['price'], 0); ?>
            </span>

            <?php if ($bk['latitude'] !== null && $bk['longitude'] !== null): ?>
              <button type="button"
                      class="btn btn-outline btn-sm booking-location-toggle"
                      data-target="bookingMap<?php echo (int) $bk['booking_id']; ?>">
                📍 පාරිභෝගික ස්ථානය බලන්න
              </button>
              <div id="bookingMap<?php echo (int) $bk['booking_id']; ?>"
                   class="booking-location-map"
                   data-lat="<?php echo htmlspecialchars((string) $bk['latitude'], ENT_QUOTES); ?>"
                   data-lng="<?php echo htmlspecialchars((string) $bk['longitude'], ENT_QUOTES); ?>"
                   data-name="<?php echo htmlspecialchars($bk['client_name'], ENT_QUOTES); ?>"
                   role="img" aria-label="Map showing where <?php echo htmlspecialchars($bk['client_name'], ENT_QUOTES); ?> is located for this booking">
              </div>
            <?php endif; ?>
          </div>

          <div class="table-row-actions">
            <span class="status-pill status-<?php echo htmlspecialchars($bk['status'], ENT_QUOTES); ?>"><?php echo status_label($bk['status']); ?></span>

            <?php if ($bk['status'] === 'confirmed'): ?>
              <form method="POST" action="booking-status-update.php">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES); ?>">
                <input type="hidden" name="booking_id" value="<?php echo (int) $bk['booking_id']; ?>">
                <input type="hidden" name="new_status" value="completed">
                <button type="submit" class="btn btn-outline btn-sm">සම්පූර්ණ කළ බව සලකුණු කරන්න</button>
              </form>
            <?php endif; ?>

            <?php if (in_array($bk['status'], ['pending', 'confirmed'], true)): ?>
              <form method="POST" action="booking-status-update.php" onsubmit="return confirm('Cancel this booking?');">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES); ?>">
                <input type="hidden" name="booking_id" value="<?php echo (int) $bk['booking_id']; ?>">
                <input type="hidden" name="new_status" value="cancelled">
                <button type="submit" class="btn btn-outline btn-sm btn-danger">අවලංගු කරන්න</button>
              </form>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

</main>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="assets/js/booking-location-map.js"></script>

</body>
</html>
