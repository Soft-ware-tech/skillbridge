<?php
/**
 * SkillBridge.lk — Booking Confirmation (English)
 * ---------------------------------------------------------------------
 * Landed on after payment-process.php completes. Confirms the booking
 * for the client and links onward to messaging the freelancer.
 * ---------------------------------------------------------------------
 */
require_once __DIR__ . '/session.php';
require_login();

$dashboardHref = match ($_SESSION['role'] ?? '') {
    'freelancer' => 'dashboard.php',
    'admin'      => 'admin-dashboard.php',
    default      => 'client-dashboard.php',
};

$bookingId = (int) ($_GET['booking'] ?? 0);

$booking = null;
$dbError = false;

try {
    $stmt = $pdo->prepare("
        SELECT b.booking_id, b.booking_date, b.status, b.client_id,
               s.title, s.price,
               u.user_id AS freelancer_user_id, u.full_name AS freelancer_name,
               pay.gateway_ref, pay.paid_at
        FROM bookings b
        JOIN services s ON b.service_id = s.service_id
        JOIN freelancer_profiles fp ON s.profile_id = fp.profile_id
        JOIN users u ON fp.user_id = u.user_id
        LEFT JOIN payments pay ON pay.booking_id = b.booking_id
        WHERE b.booking_id = :booking_id
        LIMIT 1
    ");
    $stmt->execute(['booking_id' => $bookingId]);
    $booking = $stmt->fetch() ?: null;
} catch (PDOException $e) {
    error_log('SkillBridge booking-confirmation.php error: ' . $e->getMessage());
    $dbError = true;
}

$notMine = $booking && (int) $booking['client_id'] !== (int) $_SESSION['user_id'];
$isPending = $booking && !$notMine && $booking['status'] === 'pending';
$isConfirmed = $booking && !$notMine && $booking['status'] === 'confirmed';
$isFailed = $booking && !$notMine && $booking['status'] === 'payment_failed';
?>
<!DOCTYPE html>
<html lang="si">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php if ($isPending): ?>
<meta http-equiv="refresh" content="4">
<?php endif; ?>
<title>වෙන්කිරීම තහවුරු විය — SkillBridge.lk</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Pirata+One&family=Anton&family=Space+Grotesk:wght@400;500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

<link rel="stylesheet" href="assets/css/booking.css">
</head>
<body>

<div class="scene" aria-hidden="true">
  <div class="orb orb-violet"></div>
  <div class="orb orb-cyan"></div>
</div>

<header class="nav">
  <div class="nav-inner">
    <a href="index(SIN).html" class="brand-mark">Skill<span class="brand-accent">Bridge</span><span class="brand-tld">.lk</span></a>
    <nav class="nav-links">
      <a href="<?php echo $dashboardHref; ?>" class="btn btn-outline">උපකරණ පුවරුව</a>
    </nav>
    <span class="nav-hello">ආයුබෝවන්, <?php echo htmlspecialchars(explode(' ', $_SESSION['full_name'])[0], ENT_QUOTES); ?></span>
  </div>
</header>

<main class="flow-page flow-page--narrow">

  <?php if ($dbError || !$booking || $notMine): ?>
    <div class="empty-state">
      <div class="empty-glyph">◎</div>
      <h3>වෙන්කිරීම හමු නොවීය</h3>
      <p>මෙම වෙන්කිරීම නොපවතී, හෝ ඔබේ ගිණුම හා සම්බන්ධ නොවේ.</p>
      <a href="search.php" class="btn btn-outline">සෙවීම වෙත ආපසු</a>
    </div>

  <?php elseif ($isPending): ?>
    <div class="empty-state">
      <div class="empty-glyph pulse">⋯</div>
      <h3>ඔබේ ගෙවීම තහවුරු කරමින්</h3>
      <p>PayHere අවසන් කරමින් — මෙම පිටුව තත්පර කිහිපයකින් ස්වයංක්‍රීයව නැවුම් වේ. මෙයට මිනිත්තුවකට වඩා වැඩි කාලයක් ගතවේ නම්, ඔබේ PayHere ගිණුමේ ගෙවීම් තත්ත්වය පරීක්ෂා කරන්න හෝ සහාය අමතන්න.</p>
      <a href="booking-confirmation.php?booking=<?php echo $bookingId; ?>" class="btn btn-outline">දැන් නැවුම් කරන්න</a>
    </div>

  <?php elseif ($isFailed): ?>
    <div class="empty-state">
      <div class="empty-glyph">!</div>
      <h3>ගෙවීම සිදු නොවීය</h3>
      <p>PayHere මෙම ගෙවීම අසාර්ථක බව වාර්තා කළේය. කිසිදු ගාස්තුවක් අය නොකෙරිණි — ඔබට නැවත උත්සාහ කළ හැක.</p>
      <a href="payment.php?booking=<?php echo $bookingId; ?>" class="btn btn-primary">නැවත උත්සාහ කරන්න</a>
    </div>

  <?php elseif ($isConfirmed): ?>

    <div class="confirmation-card">
      <div class="confirmation-check">✓</div>
      <h1 class="flow-title">වෙන්කිරීම තහවුරු විය!</h1>
      <p class="confirmation-sub">ඔබේ ගෙවීම සාර්ථක වූ අතර <?php echo htmlspecialchars($booking['freelancer_name'], ENT_QUOTES); ?> දැනුම් දී ඇත.</p>

      <div class="payment-card">
        <div class="payment-row">
          <span>සේවාව</span>
          <span><?php echo htmlspecialchars($booking['title'], ENT_QUOTES); ?></span>
        </div>
        <div class="payment-row">
          <span>නිදහස් වෘත්තිකයා</span>
          <span><?php echo htmlspecialchars($booking['freelancer_name'], ENT_QUOTES); ?></span>
        </div>
        <div class="payment-row">
          <span>දිනය</span>
          <span><?php echo htmlspecialchars(date('d M Y', strtotime($booking['booking_date'])), ENT_QUOTES); ?></span>
        </div>
        <div class="payment-row">
          <span>යොමුව</span>
          <span class="mono"><?php echo htmlspecialchars($booking['gateway_ref'] ?? '—', ENT_QUOTES); ?></span>
        </div>
        <div class="payment-row payment-row--total">
          <span>ගෙවා ඇත</span>
          <span>LKR <?php echo number_format((float) $booking['price'], 0); ?></span>
        </div>
      </div>

      <div class="confirmation-actions">
        <a href="messages.php?with=<?php echo (int) $booking['freelancer_user_id']; ?>" class="btn btn-primary">පණිවිඩය <?php echo htmlspecialchars(explode(' ', $booking['freelancer_name'])[0], ENT_QUOTES); ?></a>
        <a href="client-dashboard.php" class="btn btn-outline">මගේ වෙන්කිරීම් බලන්න</a>
      </div>
    </div>

  <?php endif; ?>

</main>

</body>
</html>