<?php
require_once __DIR__ . '/session.php';
require_login('freelancer');

$myId = (int) $_SESSION['user_id'];

$profile = null;
$stats = ['active_services' => 0, 'pending_bookings' => 0, 'confirmed_bookings' => 0, 'avg_rating' => 0, 'review_count' => 0];
$recentBookings = [];
$dbError = false;
$dbErrorDetail = '';

try {
    // ---- My profile ----
    $stmt = $pdo->prepare('SELECT profile_id, bio, skill_category, verified_badge FROM freelancer_profiles WHERE user_id = :user_id LIMIT 1');
    $stmt->execute(['user_id' => $myId]);
    $profile = $stmt->fetch() ?: null;

    if ($profile) {
        $profileId = (int) $profile['profile_id'];

        // ---- Stats ----
        $stmt = $pdo->prepare('SELECT COUNT(*) AS c FROM services WHERE profile_id = :profile_id AND is_active = 1');
        $stmt->execute(['profile_id' => $profileId]);
        $stats['active_services'] = (int) $stmt->fetch()['c'];

        $stmt = $pdo->prepare("
            SELECT b.status, COUNT(*) AS c
            FROM bookings b
            JOIN services s ON b.service_id = s.service_id
            WHERE s.profile_id = :profile_id
            GROUP BY b.status
        ");
        $stmt->execute(['profile_id' => $profileId]);
        foreach ($stmt->fetchAll() as $row) {
            if ($row['status'] === 'pending')   $stats['pending_bookings']   = (int) $row['c'];
            if ($row['status'] === 'confirmed') $stats['confirmed_bookings'] = (int) $row['c'];
        }

        $stmt = $pdo->prepare("
            SELECT COALESCE(AVG(r.rating), 0) AS avg_rating, COUNT(r.review_id) AS review_count
            FROM reviews r
            JOIN bookings b  ON r.booking_id = b.booking_id
            JOIN services s2 ON b.service_id = s2.service_id
            WHERE s2.profile_id = :profile_id
        ");
        $stmt->execute(['profile_id' => $profileId]);
        $ratingRow = $stmt->fetch();
        $stats['avg_rating'] = (float) $ratingRow['avg_rating'];
        $stats['review_count'] = (int) $ratingRow['review_count'];

        // ---- Recent bookings (last 6) ----
        $stmt = $pdo->prepare("
            SELECT b.booking_id, b.booking_date, b.status, s.title, c.full_name AS client_name
            FROM bookings b
            JOIN services s ON b.service_id = s.service_id
            JOIN users c    ON b.client_id = c.user_id
            WHERE s.profile_id = :profile_id
            ORDER BY b.created_at DESC
            LIMIT 6
        ");
        $stmt->execute(['profile_id' => $profileId]);
        $recentBookings = $stmt->fetchAll();
    }
} catch (PDOException $e) {
    error_log('SkillBridge dashboard.php error: ' . $e->getMessage());
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
<title>Dashboard — SkillBridge.lk</title>

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

  <?php if ($dbError): ?>
    <div class="empty-state">
      <div class="empty-glyph">!</div>
      <h3>Couldn't load your dashboard</h3>
      <p>Something went wrong reaching the database. Please try again shortly.</p>
      <?php if (isset($_GET['debug'])): ?>
        <p class="debug-detail"><?php echo htmlspecialchars($dbErrorDetail, ENT_QUOTES); ?></p>
      <?php endif; ?>
    </div>

  <?php elseif (!$profile): ?>
    <div class="empty-state">
      <div class="empty-glyph">i</div>
      <h3>No freelancer profile found</h3>
      <p>Something's off with your account setup — please contact support.</p>
    </div>

  <?php else: ?>

    <p class="eyebrow">Welcome Back</p>
    <h1 class="dash-title"><?php echo htmlspecialchars(explode(' ', $_SESSION['full_name'])[0], ENT_QUOTES); ?>'s Dashboard</h1>

    <div class="stat-grid">
      <div class="stat-card">
        <span class="stat-num"><?php echo $stats['active_services']; ?></span>
        <span class="stat-label">Active Services</span>
      </div>
      <div class="stat-card">
        <span class="stat-num"><?php echo $stats['pending_bookings']; ?></span>
        <span class="stat-label">Pending Bookings</span>
      </div>
      <div class="stat-card">
        <span class="stat-num"><?php echo $stats['confirmed_bookings']; ?></span>
        <span class="stat-label">Confirmed Bookings</span>
      </div>
      <div class="stat-card">
        <span class="stat-num">★ <?php echo number_format($stats['avg_rating'], 1); ?></span>
        <span class="stat-label"><?php echo $stats['review_count']; ?> review<?php echo $stats['review_count'] === 1 ? '' : 's'; ?></span>
      </div>
    </div>

    <?php if (!$profile['verified_badge']): ?>
      <div class="notice-banner">
        <span class="notice-icon">i</span>
        <p>Your profile isn't verified yet. Verified freelancers get more bookings — an admin will review your profile soon.</p>
      </div>
    <?php endif; ?>

    <div class="dash-grid">

      <section class="dash-panel">
        <div class="dash-panel-head">
          <h2>Recent Bookings</h2>
          <a href="bookings.php">View All →</a>
        </div>

        <?php if (empty($recentBookings)): ?>
          <p class="panel-empty">No bookings yet — bookings will show up here once a client books one of your services.</p>
        <?php else: ?>
          <div class="booking-mini-list">
            <?php foreach ($recentBookings as $b): ?>
              <div class="booking-mini-row">
                <div>
                  <span class="booking-mini-title"><?php echo htmlspecialchars($b['title'], ENT_QUOTES); ?></span>
                  <span class="booking-mini-client">with <?php echo htmlspecialchars($b['client_name'], ENT_QUOTES); ?></span>
                </div>
                <span class="status-pill status-<?php echo htmlspecialchars($b['status'], ENT_QUOTES); ?>"><?php echo status_label($b['status']); ?></span>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </section>

      <aside class="dash-panel dash-panel--side">
        <h2>Your Profile</h2>
        <p class="profile-snippet-category"><?php echo htmlspecialchars($profile['skill_category'] ?? '—', ENT_QUOTES); ?></p>
        <p class="profile-snippet-bio"><?php echo htmlspecialchars(mb_strimwidth($profile['bio'] ?? '', 0, 140, '…'), ENT_QUOTES); ?></p>
        <a href="profile-edit.php" class="btn btn-outline btn-block">Edit Profile</a>
        <a href="services.php" class="btn btn-primary btn-block">Manage Services</a>
      </aside>

    </div>

  <?php endif; ?>

</main>

</body>
</html>
