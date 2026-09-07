<?php
/**
 * SkillBridge.lk — Freelancer Dashboard (English)
 * ---------------------------------------------------------------------
 * Overview page for a logged-in freelancer: profile summary, quick
 * stats, and their most recent bookings. Deeper management lives in
 * services.php (add/edit/delete listings) and bookings.php (full
 * booking list + status actions).
 * ---------------------------------------------------------------------
 */
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
<title>டாஷ்போர்டு — SkillBridge.lk</title>

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
      <h3>உங்கள் டாஷ்போர்டை ஏற்ற முடியவில்லை</h3>
      <p>தரவுத்தளத்தை அணுகுவதில் ஏதோ தவறு நடந்தது. சிறிது நேரத்தில் மீண்டும் முயற்சிக்கவும்.</p>
      <?php if (isset($_GET['debug'])): ?>
        <p class="debug-detail"><?php echo htmlspecialchars($dbErrorDetail, ENT_QUOTES); ?></p>
      <?php endif; ?>
    </div>

  <?php elseif (!$profile): ?>
    <div class="empty-state">
      <div class="empty-glyph">i</div>
      <h3>ஃப்ரீலான்சர் சுயவிவரம் கிடைக்கவில்லை</h3>
      <p>உங்கள் கணக்கு அமைப்பில் ஏதோ சிக்கல் உள்ளது — ஆதரவைத் தொடர்பு கொள்ளவும்.</p>
    </div>

  <?php else: ?>

    <p class="eyebrow">மீண்டும் வரவேற்கிறோம்</p>
    <h1 class="dash-title"><?php echo htmlspecialchars(explode(' ', $_SESSION['full_name'])[0], ENT_QUOTES); ?>இன் டாஷ்போர்டு</h1>

    <div class="stat-grid">
      <div class="stat-card">
        <span class="stat-num"><?php echo $stats['active_services']; ?></span>
        <span class="stat-label">செயலில் உள்ள சேவைகள்</span>
      </div>
      <div class="stat-card">
        <span class="stat-num"><?php echo $stats['pending_bookings']; ?></span>
        <span class="stat-label">நிலுவையிலுள்ள முன்பதிவுகள்</span>
      </div>
      <div class="stat-card">
        <span class="stat-num"><?php echo $stats['confirmed_bookings']; ?></span>
        <span class="stat-label">உறுதிசெய்யப்பட்ட முன்பதிவுகள்</span>
      </div>
      <div class="stat-card">
        <span class="stat-num">★ <?php echo number_format($stats['avg_rating'], 1); ?></span>
        <span class="stat-label"><?php echo $stats['review_count']; ?> மதிப்புரைகள்</span>
      </div>
    </div>

    <?php if (!$profile['verified_badge']): ?>
      <div class="notice-banner">
        <span class="notice-icon">i</span>
        <p>உங்கள் சுயவிவரம் இன்னும் சரிபார்க்கப்படவில்லை. சரிபார்க்கப்பட்ட ஃப்ரீலான்சர்கள் அதிக முன்பதிவுகளைப் பெறுகிறார்கள் — நிர்வாகி விரைவில் உங்கள் சுயவிவரத்தை மதிப்பாய்வு செய்வார்.</p>
      </div>
    <?php endif; ?>

    <div class="dash-grid">

      <section class="dash-panel">
        <div class="dash-panel-head">
          <h2>சமீபத்திய முன்பதிவுகள்</h2>
          <a href="bookings.php">அனைத்தையும் காண்க →</a>
        </div>

        <?php if (empty($recentBookings)): ?>
          <p class="panel-empty">இன்னும் முன்பதிவுகள் இல்லை — ஒரு வாடிக்கையாளர் உங்கள் சேவைகளில் ஒன்றை முன்பதிவு செய்தவுடன் இங்கே காண்பிக்கப்படும்.</p>
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
        <h2>உங்கள் சுயவிவரம்</h2>
        <p class="profile-snippet-category"><?php echo htmlspecialchars($profile['skill_category'] ?? '—', ENT_QUOTES); ?></p>
        <p class="profile-snippet-bio"><?php echo htmlspecialchars(mb_strimwidth($profile['bio'] ?? '', 0, 140, '…'), ENT_QUOTES); ?></p>
        <a href="profile-edit.php" class="btn btn-outline btn-block">சுயவிவரத்தைத் திருத்து</a>
        <a href="services.php" class="btn btn-primary btn-block">சேவைகளை நிர்வகி</a>
      </aside>

    </div>

  <?php endif; ?>

</main>

</body>
</html>
