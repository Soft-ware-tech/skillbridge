<?php
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
<html lang="ta">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php if ($isPending): ?>
<meta http-equiv="refresh" content="4">
<?php endif; ?>
<title>முன்பதிவு உறுதி செய்யப்பட்டது — SkillBridge.lk</title>

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
    <a href="index(TAM).html" class="brand-mark">Skill<span class="brand-accent">Bridge</span><span class="brand-tld">.lk</span></a>
    <nav class="nav-links">
      <a href="<?php echo $dashboardHref; ?>" class="btn btn-outline">டாஷ்போர்டு</a>
    </nav>
    <span class="nav-hello">வணக்கம், <?php echo htmlspecialchars(explode(' ', $_SESSION['full_name'])[0], ENT_QUOTES); ?></span>
  </div>
</header>

<main class="flow-page flow-page--narrow">

  <?php if ($dbError || !$booking || $notMine): ?>
    <div class="empty-state">
      <div class="empty-glyph">◎</div>
      <h3>முன்பதிவு கிடைக்கவில்லை</h3>
      <p>இந்த முன்பதிவு இல்லை, அல்லது உங்கள் கணக்குடன் தொடர்புடையது அல்ல.</p>
      <a href="search.php" class="btn btn-outline">தேடலுக்குத் திரும்பு</a>
    </div>

  <?php elseif ($isPending): ?>
    <div class="empty-state">
      <div class="empty-glyph pulse">⋯</div>
      <h3>உங்கள் கட்டணத்தை உறுதிசெய்கிறோம்</h3>
      <p>PayHere முடிக்கப்படுகிறது — இந்தப் பக்கம் சில வினாடிகளில் தானாக புதுப்பிக்கப்படும். இது ஒரு நிமிடத்திற்கு மேல் ஆனால், உங்கள் PayHere கணக்கில் கட்டண நிலையைச் சரிபார்க்கவும் அல்லது ஆதரவைத் தொடர்பு கொள்ளவும்.</p>
      <a href="booking-confirmation.php?booking=<?php echo $bookingId; ?>" class="btn btn-outline">இப்போது புதுப்பிக்கவும்</a>
    </div>

  <?php elseif ($isFailed): ?>
    <div class="empty-state">
      <div class="empty-glyph">!</div>
      <h3>கட்டணம் செல்லவில்லை</h3>
      <p>PayHere இந்த கட்டணம் தோல்வியடைந்ததாக தெரிவித்தது. கட்டணம் எதுவும் வசூலிக்கப்படவில்லை — நீங்கள் மீண்டும் முயற்சிக்கலாம்.</p>
      <a href="payment.php?booking=<?php echo $bookingId; ?>" class="btn btn-primary">மீண்டும் முயற்சிக்கவும்</a>
    </div>

  <?php elseif ($isConfirmed): ?>

    <div class="confirmation-card">
      <div class="confirmation-check">✓</div>
      <h1 class="flow-title">முன்பதிவு உறுதி செய்யப்பட்டது!</h1>
      <p class="confirmation-sub">உங்கள் கட்டணம் வெற்றியடைந்தது மற்றும் <?php echo htmlspecialchars($booking['freelancer_name'], ENT_QUOTES); ?> தெரிவிக்கப்பட்டுள்ளது.</p>

      <div class="payment-card">
        <div class="payment-row">
          <span>சேவை</span>
          <span><?php echo htmlspecialchars($booking['title'], ENT_QUOTES); ?></span>
        </div>
        <div class="payment-row">
          <span>ஃப்ரீலான்சர்</span>
          <span><?php echo htmlspecialchars($booking['freelancer_name'], ENT_QUOTES); ?></span>
        </div>
        <div class="payment-row">
          <span>தேதி</span>
          <span><?php echo htmlspecialchars(date('d M Y', strtotime($booking['booking_date'])), ENT_QUOTES); ?></span>
        </div>
        <div class="payment-row">
          <span>குறிப்பு</span>
          <span class="mono"><?php echo htmlspecialchars($booking['gateway_ref'] ?? '—', ENT_QUOTES); ?></span>
        </div>
        <div class="payment-row payment-row--total">
          <span>செலுத்தப்பட்டது</span>
          <span>LKR <?php echo number_format((float) $booking['price'], 0); ?></span>
        </div>
      </div>

      <div class="confirmation-actions">
        <a href="messages.php?with=<?php echo (int) $booking['freelancer_user_id']; ?>" class="btn btn-primary">செய்தி <?php echo htmlspecialchars(explode(' ', $booking['freelancer_name'])[0], ENT_QUOTES); ?></a>
        <a href="client-dashboard.php" class="btn btn-outline">எனது முன்பதிவுகளைப் பார்க்கவும்</a>
      </div>
    </div>

  <?php endif; ?>

</main>

</body>
</html>