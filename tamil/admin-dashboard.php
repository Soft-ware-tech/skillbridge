<?php
require_once __DIR__ . '/session.php';
require_login('admin');

$stats = [
    'total_users'        => 0,
    'total_freelancers'  => 0,
    'total_clients'      => 0,
    'total_bookings'     => 0,
    'confirmed_bookings' => 0,
    'pending_verifications' => 0,
    'total_revenue'      => 0,
];
$pendingPreview = [];
$dbError = false;
$dbErrorDetail = '';

try {
    $stmt = $pdo->query("SELECT role, COUNT(*) AS c FROM users GROUP BY role");
    foreach ($stmt->fetchAll() as $row) {
        $stats['total_users'] += (int) $row['c'];
        if ($row['role'] === 'freelancer') $stats['total_freelancers'] = (int) $row['c'];
        if ($row['role'] === 'client')     $stats['total_clients']     = (int) $row['c'];
    }

    $stmt = $pdo->query("SELECT status, COUNT(*) AS c FROM bookings GROUP BY status");
    foreach ($stmt->fetchAll() as $row) {
        $stats['total_bookings'] += (int) $row['c'];
        if ($row['status'] === 'confirmed') $stats['confirmed_bookings'] = (int) $row['c'];
    }

    $stmt = $pdo->query("SELECT COALESCE(SUM(amount), 0) AS total FROM payments WHERE payment_status = 'completed'");
    $stats['total_revenue'] = (float) $stmt->fetch()['total'];

    $stmt = $pdo->query("SELECT COUNT(*) AS c FROM freelancer_profiles WHERE verified_badge = 0");
    $stats['pending_verifications'] = (int) $stmt->fetch()['c'];

    $stmt = $pdo->query("
        SELECT fp.profile_id, fp.skill_category, u.full_name
        FROM freelancer_profiles fp
        JOIN users u ON fp.user_id = u.user_id
        WHERE fp.verified_badge = 0
        ORDER BY fp.created_at ASC
        LIMIT 5
    ");
    $pendingPreview = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('SkillBridge admin-dashboard.php error: ' . $e->getMessage());
    $dbError = true;
    $dbErrorDetail = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="ta">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>நிர்வாக டாஷ்போர்டு — SkillBridge.lk</title>

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
      <h3>நிர்வாக டாஷ்போர்டை ஏற்ற முடியவில்லை</h3>
      <p>தரவுத்தளத்தை அணுகுவதில் ஏதோ தவறு நடந்தது. சிறிது நேரத்தில் மீண்டும் முயற்சிக்கவும்.</p>
      <?php if (isset($_GET['debug'])): ?>
        <p class="debug-detail"><?php echo htmlspecialchars($dbErrorDetail, ENT_QUOTES); ?></p>
      <?php endif; ?>
    </div>

  <?php else: ?>

    <p class="eyebrow">தள மேலோட்டம்</p>
    <h1 class="dash-title">நிர்வாக டாஷ்போர்டு</h1>

    <div class="stat-grid stat-grid--6">
      <div class="stat-card">
        <span class="stat-num"><?php echo $stats['total_users']; ?></span>
        <span class="stat-label">மொத்த பயனர்கள்</span>
      </div>
      <div class="stat-card">
        <span class="stat-num"><?php echo $stats['total_freelancers']; ?></span>
        <span class="stat-label">ஃப்ரீலான்சர்கள்</span>
      </div>
      <div class="stat-card">
        <span class="stat-num"><?php echo $stats['total_clients']; ?></span>
        <span class="stat-label">வாடிக்கையாளர்கள்</span>
      </div>
      <div class="stat-card">
        <span class="stat-num"><?php echo $stats['confirmed_bookings']; ?></span>
        <span class="stat-label">உறுதிசெய்யப்பட்ட முன்பதிவுகள்</span>
      </div>
      <div class="stat-card">
        <span class="stat-num">LKR <?php echo number_format($stats['total_revenue'], 0); ?></span>
        <span class="stat-label">மொத்த வருவாய்</span>
      </div>
      <div class="stat-card <?php echo $stats['pending_verifications'] > 0 ? 'stat-card--alert' : ''; ?>">
        <span class="stat-num"><?php echo $stats['pending_verifications']; ?></span>
        <span class="stat-label">சரிபார்ப்புக்காக காத்திருக்கிறது</span>
      </div>
    </div>

    <section class="dash-panel">
      <div class="dash-panel-head">
        <h2>நிலுவையிலுள்ள சரிபார்ப்புகள்</h2>
        <a href="admin-verifications.php">அனைத்தையும் காண்க →</a>
      </div>

      <?php if (empty($pendingPreview)): ?>
        <p class="panel-empty">காத்திருப்பது எதுவும் இல்லை — ஒவ்வொரு ஃப்ரீலான்சர் சுயவிவரமும் மதிப்பாய்வு செய்யப்பட்டது.</p>
      <?php else: ?>
        <div class="booking-mini-list">
          <?php foreach ($pendingPreview as $p): ?>
            <div class="booking-mini-row">
              <div>
                <span class="booking-mini-title"><?php echo htmlspecialchars($p['full_name'], ENT_QUOTES); ?></span>
                <span class="booking-mini-client"><?php echo htmlspecialchars($p['skill_category'] ?? 'No category set', ENT_QUOTES); ?></span>
              </div>
              <a href="admin-verifications.php" class="btn btn-outline btn-sm">மதிப்புரை</a>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>

  <?php endif; ?>

</main>

</body>
</html>
