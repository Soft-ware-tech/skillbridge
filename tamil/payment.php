<?php
/**
 * SkillBridge.lk — Payment (English)
 * ---------------------------------------------------------------------
 * Reached from booking.php after a pending booking is created. Builds
 * a PayHere hosted-checkout form (PayHere collects the card details on
 * their own page — we never see or store card numbers) and submits it
 * to PAYHERE_CHECKOUT_URL. PayHere confirms payment by POSTing to
 * payhere-notify.php from their own server; see config/payhere.php for
 * why that needs a public URL (ngrok) during local development.
 *
 * A "Pay with Demo Gateway (Sandbox)" link is shown ONLY while
 * PAYHERE_SANDBOX is true, for developing without a tunnel — it opens
 * demo-payment.php, a fake card + OTP checkout screen, which then
 * posts to payment-process.php to simulate a completed payment the
 * same way the notify webhook would.
 * ---------------------------------------------------------------------
 */
require_once __DIR__ . '/session.php';
require_login();
require_once __DIR__ . '/../config/payhere.php';

$dashboardHref = match ($_SESSION['role'] ?? '') {
    'freelancer' => 'dashboard.php',
    'admin'      => 'admin-dashboard.php',
    default      => 'client-dashboard.php',
};

$bookingId = (int) ($_GET['booking'] ?? 0);

$booking = null;
$dbError = false;
$dbErrorDetail = '';

try {
    $stmt = $pdo->prepare("
        SELECT b.booking_id, b.booking_date, b.status, b.client_id,
               s.title, s.price,
               u.full_name AS freelancer_name,
               client.full_name AS client_name, client.email AS client_email, client.phone AS client_phone,
               p.payment_status
        FROM bookings b
        JOIN services s ON b.service_id = s.service_id
        JOIN freelancer_profiles fp ON s.profile_id = fp.profile_id
        JOIN users u ON fp.user_id = u.user_id
        JOIN users client ON b.client_id = client.user_id
        LEFT JOIN payments p ON p.booking_id = b.booking_id
        WHERE b.booking_id = :booking_id
        LIMIT 1
    ");
    $stmt->execute(['booking_id' => $bookingId]);
    $booking = $stmt->fetch() ?: null;
} catch (PDOException $e) {
    error_log('SkillBridge payment.php error: ' . $e->getMessage());
    $dbError = true;
    $dbErrorDetail = $e->getMessage();
}

$notMine = $booking && (int) $booking['client_id'] !== (int) $_SESSION['user_id'];
$alreadyPaid = $booking && $booking['payment_status'] === 'completed';

if ($alreadyPaid) {
    header('Location: booking-confirmation.php?booking=' . $bookingId);
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

// ---- Build the PayHere checkout fields (only if we have a real booking) ----
$payhereOrderId = null;
$payhereHash = null;
$payhereAmountFormatted = null;
$nameParts = ['', ''];

if ($booking && !$notMine) {
    $payhereOrderId = 'SKB-' . $booking['booking_id'];
    $payhereAmountFormatted = number_format((float) $booking['price'], 2, '.', '');
    $payhereHash = payhere_checkout_hash($payhereOrderId, (float) $booking['price']);

    $splitName = preg_split('/\s+/', trim($booking['client_name']), 2);
    $nameParts = [$splitName[0] ?? 'Client', $splitName[1] ?? ''];

    $returnUrl = payhere_base_url() . '/payhere-return.php?booking=' . $booking['booking_id'];
    $cancelUrl = payhere_base_url() . '/payhere-cancel.php?booking=' . $booking['booking_id'];
    $notifyUrl = payhere_base_url() . '/payhere-notify.php';
}
?>
<!DOCTYPE html>
<html lang="ta">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>கட்டணம் — SkillBridge.lk</title>

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

  <?php if ($dbError): ?>
    <div class="empty-state">
      <div class="empty-glyph">!</div>
      <h3>இந்த முன்பதிவை ஏற்ற முடியவில்லை</h3>
      <p>தரவுத்தளத்தை அணுகுவதில் ஏதோ தவறு நடந்தது. சிறிது நேரத்தில் மீண்டும் முயற்சிக்கவும்.</p>
      <?php if (isset($_GET['debug'])): ?>
        <p class="debug-detail"><?php echo htmlspecialchars($dbErrorDetail, ENT_QUOTES); ?></p>
      <?php endif; ?>
    </div>

  <?php elseif (!$booking || $notMine): ?>
    <div class="empty-state">
      <div class="empty-glyph">◎</div>
      <h3>முன்பதிவு கிடைக்கவில்லை</h3>
      <p>இந்த முன்பதிவு இல்லை, அல்லது உங்கள் கணக்குடன் தொடர்புடையது அல்ல.</p>
      <a href="search.php" class="btn btn-outline">தேடலுக்குத் திரும்பு</a>
    </div>

  <?php else: ?>

    <p class="eyebrow">படி 2 இல் 2</p>
    <h1 class="flow-title">கட்டணத்தை முடிக்கவும்</h1>

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
      <div class="payment-row payment-row--total">
        <span>மொத்தம்</span>
        <span>LKR <?php echo number_format((float) $booking['price'], 0); ?></span>
      </div>

      <div class="gateway-note">
        <span class="gateway-badge">PayHere</span>
        <span class="gateway-badge">Genie Pay</span>
        <p><?php echo PAYHERE_SANDBOX ? 'Sandbox mode — test cards only, no real charge is made.' : "You'll be taken to PayHere's secure checkout to complete payment."; ?></p>
      </div>

      <!-- ---- Real PayHere hosted checkout ---- -->
      <form method="POST" action="<?php echo htmlspecialchars(PAYHERE_CHECKOUT_URL, ENT_QUOTES); ?>">
        <input type="hidden" name="merchant_id" value="<?php echo htmlspecialchars(PAYHERE_MERCHANT_ID, ENT_QUOTES); ?>">
        <input type="hidden" name="return_url" value="<?php echo htmlspecialchars($returnUrl, ENT_QUOTES); ?>">
        <input type="hidden" name="cancel_url" value="<?php echo htmlspecialchars($cancelUrl, ENT_QUOTES); ?>">
        <input type="hidden" name="notify_url" value="<?php echo htmlspecialchars($notifyUrl, ENT_QUOTES); ?>">

        <input type="hidden" name="order_id" value="<?php echo htmlspecialchars($payhereOrderId, ENT_QUOTES); ?>">
        <input type="hidden" name="items" value="<?php echo htmlspecialchars($booking['title'], ENT_QUOTES); ?>">
        <input type="hidden" name="currency" value="<?php echo htmlspecialchars(PAYHERE_CURRENCY, ENT_QUOTES); ?>">
        <input type="hidden" name="amount" value="<?php echo htmlspecialchars($payhereAmountFormatted, ENT_QUOTES); ?>">
        <input type="hidden" name="hash" value="<?php echo htmlspecialchars($payhereHash, ENT_QUOTES); ?>">

        <input type="hidden" name="first_name" value="<?php echo htmlspecialchars($nameParts[0], ENT_QUOTES); ?>">
        <input type="hidden" name="last_name" value="<?php echo htmlspecialchars($nameParts[1], ENT_QUOTES); ?>">
        <input type="hidden" name="email" value="<?php echo htmlspecialchars($booking['client_email'], ENT_QUOTES); ?>">
        <input type="hidden" name="phone" value="<?php echo htmlspecialchars($booking['client_phone'] ?? '0770000000', ENT_QUOTES); ?>">
        <input type="hidden" name="address" value="N/A">
        <input type="hidden" name="city" value="Colombo">
        <input type="hidden" name="country" value="இலங்கை">

        <button type="submit" class="btn btn-primary btn-lg btn-block">LKR செலுத்துங்கள் <?php echo number_format((float) $booking['price'], 0); ?> PayHere உடன்</button>
      </form>

<?php if (PAYHERE_SANDBOX): ?>
  <div class="dev-fallback">
    <p>Stripe sandbox மூலம் சோதனை கட்டணம் — உண்மையான கட்டணம் இல்லை, ngrok தேவையில்லை:</p>
    <a href="stripe-checkout.php?booking=<?php echo (int) $booking['booking_id']; ?>" class="btn btn-outline btn-block">Stripe மூலம் செலுத்துங்கள் (சோதனை அட்டை)</a>
  </div>
<?php endif; ?>
    </div>

  <?php endif; ?>

</main>

</body>
</html>