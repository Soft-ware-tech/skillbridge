<?php
require_once __DIR__ . '/session.php';
require_login('client');

$bookingId = (int) ($_GET['booking'] ?? 0);
$myId = (int) $_SESSION['user_id'];
$booking = null;

try {
    $stmt = $pdo->prepare("
        SELECT b.booking_id, b.status, s.title, p.payment_id, p.amount, p.payment_status,
               u.full_name AS freelancer_name
        FROM bookings b
        JOIN services s ON b.service_id = s.service_id
        JOIN freelancer_profiles fp ON s.profile_id = fp.profile_id
        JOIN users u ON fp.user_id = u.user_id
        LEFT JOIN payments p ON p.booking_id = b.booking_id
        WHERE b.booking_id = :booking_id AND b.client_id = :client_id
        LIMIT 1
    ");
    $stmt->execute(['booking_id' => $bookingId, 'client_id' => $myId]);
    $booking = $stmt->fetch();

    $dupStmt = $pdo->prepare("SELECT refund_id FROM refund_requests WHERE booking_id = :booking_id AND status = 'pending' LIMIT 1");
    $dupStmt->execute(['booking_id' => $bookingId]);
    $alreadyRequested = (bool) $dupStmt->fetch();
} catch (PDOException $e) {
    error_log('refund-request.php error: ' . $e->getMessage());
    $booking = null;
    $alreadyRequested = false;
}

// Only completed + paid bookings, with no pending request already, can be refunded
if (!$booking || $booking['status'] !== 'completed' || ($booking['payment_status'] ?? '') !== 'completed' || $alreadyRequested) {
    header('Location: client-dashboard.php');
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];
$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="ta">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>பணத்திரும்பம் கோரவும் — SkillBridge.lk</title>
<link rel="stylesheet" href="assets/css/dashboard.css?v=2">
</head>
<body>

<?php $activeNav = ''; include __DIR__ . '/partials/dashboard-nav.php'; ?>

<main class="dash-page">
  <p class="eyebrow">முன்பதிவு #<?php echo (int) $bookingId; ?></p>
  <h1 class="dash-title">பணத்திரும்பம் ஒன்றைக் கோரவும்</h1>

  <?php if ($error === 'missing'): ?>
    <p class="flash-error">தயவுசெய்து இரண்டு புலங்களையும் நிரப்பவும்.</p>
  <?php endif; ?>

  <div class="table-list">
    <div class="table-row table-row--stack">
      <h3><?php echo htmlspecialchars($booking['title'], ENT_QUOTES); ?></h3>
      <p class="table-meta">
        with <?php echo htmlspecialchars($booking['freelancer_name'], ENT_QUOTES); ?>
        &middot; செலுத்தப்பட்டது: LKR <?php echo number_format((float) $booking['amount'], 2); ?>
      </p>

      <form method="POST" action="refund-request-submit.php" class="review-form">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES); ?>">
        <input type="hidden" name="booking_id" value="<?php echo (int) $bookingId; ?>">

        <div class="field">
          <label for="reason">நீங்கள் ஏன் பணத்திரும்பம் கோருகிறீர்கள்?</label>
          <textarea id="reason" name="reason" rows="3" required placeholder="என்ன தவறு நடந்தது என்பதை விளக்கவும்..."></textarea>
        </div>

        <div class="field">
          <label for="account_number">உங்கள் வங்கிக் கணக்கு எண் (எங்கள் பதிவுகளுக்காக)</label>
          <input type="text" id="account_number" name="account_number" required placeholder="e.g. 000123456789">
          <small>உங்கள் Stripe கட்டணம் பயன்படுத்திய அசல் அட்டைக்கு தானாக திரும்பச் செலுத்தப்படும். இது எங்கள் பதிவுகளுக்காக மட்டும் வைக்கப்படுகிறது.</small>
        </div>

        <button type="submit" class="btn btn-primary">பணத்திரும்பக் கோரிக்கையைச் சமர்ப்பிக்கவும்</button>
      </form>
    </div>
  </div>
</main>
</body>
</html>