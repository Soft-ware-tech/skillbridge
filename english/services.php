<?php
require_once __DIR__ . '/session.php';
require_login('freelancer');

$myId = (int) $_SESSION['user_id'];

$services = [];
$dbError = false;
$dbErrorDetail = '';
$flashDeleted = isset($_GET['deleted']);
$flashSaved = isset($_GET['saved']);

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

try {
    $stmt = $pdo->prepare("
        SELECT s.service_id, s.title, s.price, s.is_active, c.category_name,
               (SELECT COUNT(*) FROM bookings WHERE service_id = s.service_id) AS booking_count,
               (SELECT COUNT(*) FROM bookings
                 WHERE service_id = s.service_id
                   AND status IN ('pending','confirmed')) AS active_booking_count
        FROM services s
        JOIN freelancer_profiles fp ON s.profile_id = fp.profile_id
        JOIN categories c           ON s.category_id = c.category_id
        WHERE fp.user_id = :user_id AND s.is_active = 1
        ORDER BY s.created_at DESC
    ");
    $stmt->execute(['user_id' => $myId]);
    $services = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('SkillBridge services.php error: ' . $e->getMessage());
    $dbError = true;
    $dbErrorDetail = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Services — SkillBridge.lk</title>

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

<?php $activeNav = 'services'; include __DIR__ . '/partials/dashboard-nav.php'; ?>

<main class="dash-page">

  <div class="dash-page-head">
    <div>
      <p class="eyebrow">Manage Listings</p>
      <h1 class="dash-title">My Services</h1>
    </div>
    <a href="service-form.php" class="btn btn-primary">+ Add New Service</a>
  </div>

  <?php if ($flashSaved): ?>
    <p class="flash-success">Service saved.</p>
  <?php endif; ?>
  <?php if ($flashDeleted): ?>
    <p class="flash-success">Service removed.</p>
  <?php endif; ?>
  <?php if (isset($_GET['error']) && $_GET['error'] === 'has_active_booking'): ?>
    <p class="flow-error">That service has an active booking, so it can't be deleted. Wait until the booking is completed or cancelled, or pause it instead.</p>
  <?php endif; ?>

  <?php if ($dbError): ?>
    <div class="empty-state">
      <div class="empty-glyph">!</div>
      <h3>Couldn't load your services</h3>
      <p>Something went wrong reaching the database. Please try again shortly.</p>
      <?php if (isset($_GET['debug'])): ?>
        <p class="debug-detail"><?php echo htmlspecialchars($dbErrorDetail, ENT_QUOTES); ?></p>
      <?php endif; ?>
    </div>

  <?php elseif (empty($services)): ?>
    <div class="empty-state">
      <div class="empty-glyph">◎</div>
      <h3>No services listed yet</h3>
      <p>Add your first service so clients can find and book you.</p>
      <a href="service-form.php" class="btn btn-primary">+ Add New Service</a>
    </div>

  <?php else: ?>
    <div class="table-list">
      <?php foreach ($services as $svc): ?>
        <div class="table-row">
          <div class="table-row-main">
            <span class="table-category"><?php echo htmlspecialchars($svc['category_name'], ENT_QUOTES); ?></span>
            <h3><?php echo htmlspecialchars($svc['title'], ENT_QUOTES); ?></h3>
            <span class="table-meta">
              LKR <?php echo number_format((float) $svc['price'], 0); ?>
              &middot; <?php echo (int) $svc['booking_count']; ?> booking<?php echo (int) $svc['booking_count'] === 1 ? '' : 's'; ?>
              <?php if ((int) $svc['active_booking_count'] > 0): ?> &middot; <span class="paused-tag">Has an active booking</span><?php endif; ?>
            </span>
          </div>
          <div class="table-row-actions">
            <a href="service-form.php?service=<?php echo (int) $svc['service_id']; ?>" class="btn btn-outline btn-sm">Edit</a>
            <?php if ((int) $svc['active_booking_count'] > 0): ?>
              <button type="button" class="btn btn-outline btn-sm btn-danger" disabled title="This service has an active booking and can't be deleted until it's completed or cancelled.">Delete</button>
            <?php else: ?>
              <form method="POST" action="service-delete.php" onsubmit="return confirm('Remove this service? This can\'t be undone.');">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES); ?>">
                <input type="hidden" name="service_id" value="<?php echo (int) $svc['service_id']; ?>">
                <button type="submit" class="btn btn-outline btn-sm btn-danger">Delete</button>
              </form>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

</main>

</body>
</html>
