<?php
require_once __DIR__ . '/session.php';
require_login();

$bookingId = (int) ($_GET['booking'] ?? 0);
?>
<!DOCTYPE html>
<html lang="ta">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>கட்டணம் ரத்து செய்யப்பட்டது — SkillBridge.lk</title>

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
    <span class="nav-hello">வணக்கம், <?php echo htmlspecialchars(explode(' ', $_SESSION['full_name'])[0], ENT_QUOTES); ?></span>
  </div>
</header>

<main class="flow-page flow-page--narrow">
  <div class="empty-state">
    <div class="empty-glyph">i</div>
    <h3>கட்டணம் ரத்து செய்யப்பட்டது</h3>
    <p>கட்டணம் எதுவும் வசூலிக்கப்படவில்லை. உங்கள் முன்பதிவு இன்னும் சேமிக்கப்பட்டுள்ளது — எப்போது வேண்டுமானாலும் மீண்டும் கட்டணம் செலுத்தலாம்.</p>
    <div class="confirmation-actions">
      <?php if ($bookingId > 0): ?>
        <a href="payment.php?booking=<?php echo $bookingId; ?>" class="btn btn-primary">மீண்டும் முயற்சிக்கவும்</a>
      <?php endif; ?>
      <a href="search.php" class="btn btn-outline">தேடலுக்குத் திரும்பு</a>
    </div>
  </div>
</main>

</body>
</html>
