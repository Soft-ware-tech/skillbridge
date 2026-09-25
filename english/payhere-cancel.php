<?php
require_once __DIR__ . '/session.php';
require_login();

$bookingId = (int) ($_GET['booking'] ?? 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Payment Cancelled — SkillBridge.lk</title>

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
    <a href="index(ENG).html" class="brand-mark">Skill<span class="brand-accent">Bridge</span><span class="brand-tld">.lk</span></a>
    <span class="nav-hello">Hi, <?php echo htmlspecialchars(explode(' ', $_SESSION['full_name'])[0], ENT_QUOTES); ?></span>
  </div>
</header>

<main class="flow-page flow-page--narrow">
  <div class="empty-state">
    <div class="empty-glyph">i</div>
    <h3>Payment Cancelled</h3>
    <p>No charge was made. Your booking is still saved — you can pick up payment again anytime.</p>
    <div class="confirmation-actions">
      <?php if ($bookingId > 0): ?>
        <a href="payment.php?booking=<?php echo $bookingId; ?>" class="btn btn-primary">Try Again</a>
      <?php endif; ?>
      <a href="search.php" class="btn btn-outline">Back to Search</a>
    </div>
  </div>
</main>

</body>
</html>
