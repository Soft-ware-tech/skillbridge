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
<title>උපකරණ පුවරුව — SkillBridge.lk</title>

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
      <h3>ඔබේ උපකරණ පුවරුව පූරණය කළ නොහැකි විය</h3>
      <p>දත්ත සමුදායට ළඟාවීමේදී යම් දෝෂයක් සිදුවිය. කරුණාකර මොහොතකින් නැවත උත්සාහ කරන්න.</p>
      <?php if (isset($_GET['debug'])): ?>
        <p class="debug-detail"><?php echo htmlspecialchars($dbErrorDetail, ENT_QUOTES); ?></p>
      <?php endif; ?>
    </div>

  <?php elseif (!$profile): ?>
    <div class="empty-state">
      <div class="empty-glyph">i</div>
      <h3>නිදහස් වෘත්තිකයාගේ පැතිකඩක් හමු නොවීය</h3>
      <p>ඔබේ ගිණුම් සැකසුමේ යම් ගැටලුවක් ඇත — කරුණාකර සහාය අමතන්න.</p>
    </div>

  <?php else: ?>

    <p class="eyebrow">නැවත සාදරයෙන් පිළිගනිමු</p>
    <h1 class="dash-title"><?php echo htmlspecialchars(explode(' ', $_SESSION['full_name'])[0], ENT_QUOTES); ?>ගේ උපකරණ පුවරුව</h1>

    <div class="stat-grid">
      <div class="stat-card">
        <span class="stat-num"><?php echo $stats['active_services']; ?></span>
        <span class="stat-label">සක්‍රිය සේවා</span>
      </div>
      <div class="stat-card">
        <span class="stat-num"><?php echo $stats['pending_bookings']; ?></span>
        <span class="stat-label">පොරොත්තුවෙන් සිටින වෙන්කිරීම්</span>
      </div>
      <div class="stat-card">
        <span class="stat-num"><?php echo $stats['confirmed_bookings']; ?></span>
        <span class="stat-label">තහවුරු කළ වෙන්කිරීම්</span>
      </div>
      <div class="stat-card">
        <span class="stat-num">★ <?php echo number_format($stats['avg_rating'], 1); ?></span>
        <span class="stat-label"><?php echo $stats['review_count']; ?> සමාලෝචන</span>
      </div>
    </div>

    <?php if (!$profile['verified_badge']): ?>
      <div class="notice-banner">
        <span class="notice-icon">i</span>
        <p>ඔබේ පැතිකඩ තවම තහවුරු කර නොමැත. තහවුරු කළ නිදහස් වෘත්තිකයින්ට වැඩි වෙන්කිරීම් ලැබේ — පරිපාලකයෙකු ඉක්මනින් ඔබේ පැතිකඩ සමාලෝචනය කරනු ඇත.</p>
      </div>
    <?php endif; ?>

    <div class="dash-grid">

      <section class="dash-panel">
        <div class="dash-panel-head">
          <h2>මෑත වෙන්කිරීම්</h2>
          <a href="bookings.php">සියල්ල බලන්න →</a>
        </div>

        <?php if (empty($recentBookings)): ?>
          <p class="panel-empty">තවම වෙන්කිරීම් නොමැත — පාරිභෝගිකයෙකු ඔබේ සේවාවක් වෙන් කළ පසු එය මෙහි දිස්වේ.</p>
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
        <h2>ඔබේ පැතිකඩ</h2>
        <p class="profile-snippet-category"><?php echo htmlspecialchars($profile['skill_category'] ?? '—', ENT_QUOTES); ?></p>
        <p class="profile-snippet-bio"><?php echo htmlspecialchars(mb_strimwidth($profile['bio'] ?? '', 0, 140, '…'), ENT_QUOTES); ?></p>
        <a href="profile-edit.php" class="btn btn-outline btn-block">පැතිකඩ සංස්කරණය</a>
        <a href="services.php" class="btn btn-primary btn-block">සේවා කළමනාකරණය</a>
      </aside>

    </div>

  <?php endif; ?>

</main>

</body>
</html>
