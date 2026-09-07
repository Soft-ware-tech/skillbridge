<?php
/**
 * SkillBridge.lk — Freelancer Verification Queue (Admin, English)
 * ---------------------------------------------------------------------
 * Every freelancer profile with verified_badge = 0, oldest first.
 * "අනුමත කරන්න" posts to admin-verification-update.php. There's no separate
 * "rejected" state in the schema — a profile just stays unverified
 * until an admin approves it, so there's nothing destructive to undo.
 * ---------------------------------------------------------------------
 */
require_once __DIR__ . '/session.php';
require_login('admin');

$pending = [];
$verifiedRecent = [];
$dbError = false;
$dbErrorDetail = '';
$flashApproved = isset($_GET['approved']);

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

try {
    $stmt = $pdo->query("
        SELECT fp.profile_id, fp.bio, fp.skill_category, fp.created_at,
               u.full_name, u.email
        FROM freelancer_profiles fp
        JOIN users u ON fp.user_id = u.user_id
        WHERE fp.verified_badge = 0
        ORDER BY fp.created_at ASC
    ");
    $pending = $stmt->fetchAll();

    $stmt = $pdo->query("
        SELECT fp.profile_id, u.full_name, fp.skill_category
        FROM freelancer_profiles fp
        JOIN users u ON fp.user_id = u.user_id
        WHERE fp.verified_badge = 1
        ORDER BY fp.updated_at DESC
        LIMIT 5
    ");
    $verifiedRecent = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('SkillBridge admin-verifications.php error: ' . $e->getMessage());
    $dbError = true;
    $dbErrorDetail = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="si">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>තහවුරු කිරීම් — SkillBridge.lk</title>

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

<?php $activeNav = 'verifications'; include __DIR__ . '/partials/dashboard-nav.php'; ?>

<main class="dash-page">

  <p class="eyebrow">විශ්වාසය සහ ආරක්ෂාව</p>
  <h1 class="dash-title">නිදහස් වෘත්තිකයින් තහවුරු කිරීම්</h1>

  <?php if ($flashApproved): ?>
    <p class="flash-success">නිදහස් වෘත්තිකයා තහවුරු කරන ලදී.</p>
  <?php endif; ?>

  <?php if ($dbError): ?>
    <div class="empty-state">
      <div class="empty-glyph">!</div>
      <h3>තහවුරු කිරීම් පූරණය කළ නොහැකි විය</h3>
      <p>දත්ත සමුදායට ළඟාවීමේදී යම් දෝෂයක් සිදුවිය. කරුණාකර මොහොතකින් නැවත උත්සාහ කරන්න.</p>
      <?php if (isset($_GET['debug'])): ?>
        <p class="debug-detail"><?php echo htmlspecialchars($dbErrorDetail, ENT_QUOTES); ?></p>
      <?php endif; ?>
    </div>

  <?php elseif (empty($pending)): ?>
    <div class="empty-state">
      <div class="empty-glyph">✓</div>
      <h3>සියල්ල යාවත්කාලීන කර ඇත</h3>
      <p>දැනට තහවුරු කිරීම බලාපොරොත්තුවෙන් නිදහස් වෘත්තිකයින්ගේ පැතිකඩ නොමැත.</p>
    </div>

  <?php else: ?>
    <div class="table-list">
      <?php foreach ($pending as $p): ?>
        <div class="table-row table-row--stack">
          <div class="table-row-top">
            <div class="table-row-main">
              <span class="table-category"><?php echo htmlspecialchars($p['skill_category'] ?? 'No category set', ENT_QUOTES); ?></span>
              <h3><?php echo htmlspecialchars($p['full_name'], ENT_QUOTES); ?></h3>
              <span class="table-meta">
                <?php echo htmlspecialchars($p['email'], ENT_QUOTES); ?>
                &middot; අයදුම් කළ දිනය <?php echo htmlspecialchars(date('d M Y', strtotime($p['created_at'])), ENT_QUOTES); ?>
              </span>
            </div>
            <div class="table-row-actions">
              <form method="POST" action="admin-verification-update.php">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES); ?>">
                <input type="hidden" name="profile_id" value="<?php echo (int) $p['profile_id']; ?>">
                <button type="submit" class="btn btn-primary btn-sm">අනුමත කරන්න</button>
              </form>
            </div>
          </div>
          <?php if (!empty($p['bio'])): ?>
            <p class="verification-bio"><?php echo nl2br(htmlspecialchars($p['bio'], ENT_QUOTES)); ?></p>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <?php if (!$dbError && !empty($verifiedRecent)): ?>
    <section class="dash-panel" style="margin-top:32px;">
      <div class="dash-panel-head">
        <h2>මෑතදී තහවුරු කළ</h2>
      </div>
      <div class="booking-mini-list">
        <?php foreach ($verifiedRecent as $v): ?>
          <div class="booking-mini-row">
            <div>
              <span class="booking-mini-title"><?php echo htmlspecialchars($v['full_name'], ENT_QUOTES); ?></span>
              <span class="booking-mini-client"><?php echo htmlspecialchars($v['skill_category'] ?? '', ENT_QUOTES); ?></span>
            </div>
            <span class="status-pill status-confirmed">✓ තහවුරු කර ඇත</span>
          </div>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>

</main>

</body>
</html>
