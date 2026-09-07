<?php
/**
 * SkillBridge.lk — Freelancer Profile (English)
 * ---------------------------------------------------------------------
 * Reached from search.php result cards via ?service=<service_id>, or
 * directly via ?profile=<profile_id>. Either way we resolve down to a
 * profile_id, then show that freelancer's full profile: bio, verified
 * badge, aggregate rating, every active service they list, and their
 * most recent reviews.
 * ---------------------------------------------------------------------
 */
require_once __DIR__ . '/session.php'; // gives us session_start() + $pdo

$dashboardHref = match ($_SESSION['role'] ?? '') {
    'freelancer' => 'dashboard.php',
    'admin'      => 'admin-dashboard.php',
    default      => 'client-dashboard.php',
};

$serviceId = (int) ($_GET['service'] ?? 0);
$profileIdParam = (int) ($_GET['profile'] ?? 0);

$profileId = null;
$dbError = false;
$dbErrorDetail = '';

try {
    if ($serviceId > 0) {
        $stmt = $pdo->prepare('SELECT profile_id FROM services WHERE service_id = :service_id LIMIT 1');
        $stmt->execute(['service_id' => $serviceId]);
        $row = $stmt->fetch();
        $profileId = $row ? (int) $row['profile_id'] : null;
    } elseif ($profileIdParam > 0) {
        $profileId = $profileIdParam;
    }

    $freelancer = null;
    $services = [];
    $reviews = [];

    if ($profileId !== null) {
        // ---- Core profile + aggregate rating ----
        $stmt = $pdo->prepare("
            SELECT fp.profile_id, fp.bio, fp.skill_category, fp.verified_badge,
                   fp.latitude, fp.longitude,
                   u.user_id, u.full_name, u.photo_path,
                   COALESCE(AVG(r.rating), 0)  AS avg_rating,
                   COUNT(DISTINCT r.review_id) AS review_count
            FROM freelancer_profiles fp
            JOIN users u          ON fp.user_id = u.user_id
            LEFT JOIN services s  ON s.profile_id = fp.profile_id
            LEFT JOIN bookings b  ON b.service_id = s.service_id
            LEFT JOIN reviews r   ON r.booking_id = b.booking_id
            WHERE fp.profile_id = :profile_id
            GROUP BY fp.profile_id, fp.bio, fp.skill_category, fp.verified_badge,
                     fp.latitude, fp.longitude, u.user_id, u.full_name, u.photo_path
        ");
        $stmt->execute(['profile_id' => $profileId]);
        $freelancer = $stmt->fetch() ?: null;

        if ($freelancer) {
            // ---- All active services from this freelancer ----
            $stmt = $pdo->prepare("
                SELECT s.service_id, s.title, s.price, s.description, c.category_name
                FROM services s
                JOIN categories c ON s.category_id = c.category_id
                WHERE s.profile_id = :profile_id AND s.is_active = 1
                ORDER BY s.created_at DESC
            ");
            $stmt->execute(['profile_id' => $profileId]);
            $services = $stmt->fetchAll();

            // ---- Most recent reviews ----
            $stmt = $pdo->prepare("
                SELECT r.rating, r.comment, r.created_at, cu.full_name AS client_name
                FROM reviews r
                JOIN bookings b  ON r.booking_id = b.booking_id
                JOIN services s2 ON b.service_id = s2.service_id
                JOIN users cu    ON b.client_id = cu.user_id
                WHERE s2.profile_id = :profile_id
                ORDER BY r.created_at DESC
                LIMIT 10
            ");
            $stmt->execute(['profile_id' => $profileId]);
            $reviews = $stmt->fetchAll();
        }
    }
} catch (PDOException $e) {
    error_log('SkillBridge profile.php error: ' . $e->getMessage());
    $dbError = true;
    $dbErrorDetail = $e->getMessage();
    $freelancer = null;
}

function initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name));
    $letters = array_map(fn($p) => mb_strtoupper(mb_substr($p, 0, 1)), array_slice($parts, 0, 2));
    return implode('', $letters) ?: '?';
}

function time_ago(string $datetime): string
{
    $diff = time() - strtotime($datetime);
    if ($diff < 86400) return 'Today';
    if ($diff < 172800) return 'Yesterday';
    if ($diff < 2592000) return floor($diff / 86400) . ' days ago';
    if ($diff < 31536000) return floor($diff / 2592000) . ' months ago';
    return floor($diff / 31536000) . ' years ago';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $freelancer ? htmlspecialchars($freelancer['full_name'], ENT_QUOTES) . ' — ' : ''; ?>SkillBridge.lk</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Pirata+One&family=Anton&family=Space+Grotesk:wght@400;500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

<link rel="stylesheet" href="assets/css/profile.css">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
</head>
<body>

<div class="scene" aria-hidden="true">
  <div class="orb orb-violet"></div>
  <div class="orb orb-cyan"></div>
</div>

<!-- =========================== NAV =========================== -->
<header class="nav">
  <div class="nav-inner">
    <a href="index(ENG).html" class="brand-mark">Skill<span class="brand-accent">Bridge</span><span class="brand-tld">.lk</span></a>
    <nav class="nav-links">
      <a href="search.php">Search</a>
      <a href="index(ENG).html#categories">Categories</a>
    </nav>
    <div class="nav-actions">
      <?php if (isset($_SESSION['user_id'])): ?>
        <a href="<?php echo $dashboardHref; ?>" class="btn btn-outline">Dashboard</a>
        <?php if (($_SESSION['role'] ?? '') === 'freelancer'): ?>
          <a href="dashboard.php" class="nav-hello">Hi, <?php echo htmlspecialchars(explode(' ', $_SESSION['full_name'])[0], ENT_QUOTES); ?></a>
        <?php else: ?>
          <span class="nav-hello">Hi, <?php echo htmlspecialchars(explode(' ', $_SESSION['full_name'])[0], ENT_QUOTES); ?></span>
        <?php endif; ?>
        <a href="logout.php" class="btn btn-ghost">Log Out</a>
      <?php else: ?>
        <a href="login.php" class="btn btn-ghost">Log In</a>
        <a href="register.php" class="btn btn-primary">Join Free</a>
      <?php endif; ?>
    </div>
  </div>
</header>

<main class="profile-page">

  <?php if ($dbError): ?>
    <div class="empty-state">
      <div class="empty-glyph">!</div>
      <h3>Couldn't load this profile</h3>
      <p>Something went wrong reaching the database. Please try again shortly.</p>
      <?php if (isset($_GET['debug'])): ?>
        <p class="debug-detail"><?php echo htmlspecialchars($dbErrorDetail, ENT_QUOTES); ?></p>
      <?php endif; ?>
    </div>

  <?php elseif (!$freelancer): ?>
    <div class="empty-state">
      <div class="empty-glyph">◎</div>
      <h3>Freelancer not found</h3>
      <p>This profile may have been removed, or the link is incorrect.</p>
      <a href="search.php" class="btn btn-outline">Back to Search</a>
    </div>

  <?php else: ?>

    <!-- =========================== PROFILE HEADER =========================== -->
    <section class="profile-header">
      <div class="profile-avatar">
        <?php if (!empty($freelancer['photo_path'])): ?>
          <img src="<?php echo htmlspecialchars($freelancer['photo_path'], ENT_QUOTES); ?>" alt="" class="profile-avatar-img">
        <?php else: ?>
          <?php echo htmlspecialchars(initials($freelancer['full_name']), ENT_QUOTES); ?>
        <?php endif; ?>
      </div>

      <div class="profile-who">
        <div class="profile-name-row">
          <h1><?php echo htmlspecialchars($freelancer['full_name'], ENT_QUOTES); ?></h1>
          <?php if ((int) $freelancer['verified_badge'] === 1): ?>
            <span class="verified-pill">✓ Verified by SkillBridge.lk</span>
          <?php endif; ?>
        </div>

        <?php if (!empty($freelancer['skill_category'])): ?>
          <p class="profile-category"><?php echo htmlspecialchars($freelancer['skill_category'], ENT_QUOTES); ?></p>
        <?php endif; ?>

        <div class="profile-rating">
          <span class="stars">★ <?php echo number_format((float) $freelancer['avg_rating'], 1); ?></span>
          <span class="review-count">(<?php echo (int) $freelancer['review_count']; ?> review<?php echo (int) $freelancer['review_count'] === 1 ? '' : 's'; ?>)</span>
        </div>

        <div class="profile-actions">
          <a href="#services" class="btn btn-primary">View Services</a>
          <a href="messages.php?with=<?php echo (int) $freelancer['user_id']; ?>" class="btn btn-outline">Message</a>
        </div>
      </div>
    </section>

    <?php if (!empty($freelancer['bio'])): ?>
      <section class="profile-bio">
        <p class="eyebrow">About</p>
        <p><?php echo nl2br(htmlspecialchars($freelancer['bio'], ENT_QUOTES)); ?></p>
      </section>
    <?php endif; ?>

    <?php if ($freelancer['latitude'] !== null && $freelancer['longitude'] !== null): ?>
      <section class="profile-section">
        <p class="eyebrow">Location</p>
        <h2 class="section-title">Where <?php echo htmlspecialchars(explode(' ', $freelancer['full_name'])[0], ENT_QUOTES); ?> Is Based</h2>

        <div id="profileMap"
             class="profile-map"
             data-lat="<?php echo htmlspecialchars((string) $freelancer['latitude'], ENT_QUOTES); ?>"
             data-lng="<?php echo htmlspecialchars((string) $freelancer['longitude'], ENT_QUOTES); ?>"
             data-name="<?php echo htmlspecialchars($freelancer['full_name'], ENT_QUOTES); ?>"
             role="img" aria-label="Map showing this freelancer's approximate location">
        </div>

        <a class="directions-link"
           href="https://www.google.com/maps/dir/?api=1&destination=<?php echo urlencode($freelancer['latitude'] . ',' . $freelancer['longitude']); ?>"
           target="_blank" rel="noopener">Get Directions ↗</a>
      </section>
    <?php endif; ?>

    <!-- =========================== SERVICES =========================== -->
    <section class="profile-section" id="services">
      <p class="eyebrow">Services</p>
      <h2 class="section-title">What <?php echo htmlspecialchars(explode(' ', $freelancer['full_name'])[0], ENT_QUOTES); ?> Offers</h2>

      <?php if (empty($services)): ?>
        <div class="empty-state empty-state--inline">
          <p>No active services listed yet.</p>
        </div>
      <?php else: ?>
        <div class="service-list">
          <?php foreach ($services as $svc): ?>
            <div class="service-card">
              <div class="service-info">
                <span class="service-category"><?php echo htmlspecialchars($svc['category_name'], ENT_QUOTES); ?></span>
                <h3><?php echo htmlspecialchars($svc['title'], ENT_QUOTES); ?></h3>
                <?php if (!empty($svc['description'])): ?>
                  <p><?php echo htmlspecialchars($svc['description'], ENT_QUOTES); ?></p>
                <?php endif; ?>
              </div>
              <div class="service-cta">
                <span class="service-price">LKR <?php echo number_format((float) $svc['price'], 0); ?></span>
                <a href="booking.php?service=<?php echo (int) $svc['service_id']; ?>" class="btn btn-primary">Book Now</a>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>

    <!-- =========================== REVIEWS =========================== -->
    <section class="profile-section">
      <p class="eyebrow">Feedback</p>
      <h2 class="section-title">Client Reviews</h2>

      <?php if (empty($reviews)): ?>
        <div class="empty-state empty-state--inline">
          <p>No reviews yet — be the first to book and leave one.</p>
        </div>
      <?php else: ?>
        <div class="review-list">
          <?php foreach ($reviews as $rev): ?>
            <div class="review-card">
              <div class="review-top">
                <span class="review-stars"><?php echo str_repeat('★', (int) $rev['rating']) . str_repeat('☆', 5 - (int) $rev['rating']); ?></span>
                <span class="review-date"><?php echo time_ago($rev['created_at']); ?></span>
              </div>
              <?php if (!empty($rev['comment'])): ?>
                <p class="review-comment"><?php echo nl2br(htmlspecialchars($rev['comment'], ENT_QUOTES)); ?></p>
              <?php endif; ?>
              <span class="review-author"><?php echo htmlspecialchars($rev['client_name'], ENT_QUOTES); ?></span>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>

  <?php endif; ?>

</main>

<?php if ($freelancer && $freelancer['latitude'] !== null && $freelancer['longitude'] !== null): ?>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="assets/js/profile-map.js"></script>
<script>
  initProfileMap();
</script>
<?php endif; ?>

</body>
</html>