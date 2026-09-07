<?php
/**
 * SkillBridge.lk — Post-Login Choice (English)
 * ---------------------------------------------------------------------
 * Landed on right after a fresh login/register with no specific task to
 * return to (login-process.php / register-process.php skip this and go
 * straight to the right place when the person was mid-task, e.g.
 * booking a service). Otherwise, they land here and choose where to go
 * — styled the same way the root language picker is, for consistency.
 * ---------------------------------------------------------------------
 */
require_once __DIR__ . '/session.php';
require_login();

$role = $_SESSION['role'] ?? '';
$roleToastMessages = [
    'freelancer' => 'ඔබේ උපකරණ පුවරුවේ නව රැකියා අවස්ථා පොරොත්තුවෙන් ඇත.',
    'admin'      => 'ඔබේ පරිපාලක කවුළුව සූදානම් — තහවුරු කිරීම් පොරොත්තුවෙන් ඇත.',
    'client'     => "ඔබ ඇතුළත්! ඔබ අවට තහවුරු කළ නිදහස් වෘත්තිකයින් බැලීම ආරම්භ කරන්න.",
];
$toastMessage = $roleToastMessages[$role] ?? $roleToastMessages['client'];
$dashboardHref = match ($role) {
    'freelancer' => 'dashboard.php',
    'admin'      => 'admin-dashboard.php',
    default      => 'client-dashboard.php',
};
$dashboardLabel = match ($role) {
    'freelancer' => 'නිදහස් වෘත්තිකයාගේ උපකරණ පුවරුව',
    'admin'      => 'පරිපාලක උපකරණ පුවරුව',
    default      => 'මගේ වෙන්කිරීම්',
};
$dashboardGlyph = match ($role) {
    'freelancer' => '≡',
    'admin'      => '⚑',
    default      => '◷',
};
$dashboardSub = match ($role) {
    'freelancer' => 'සේවා සහ වෙන්කිරීම් කළමනාකරණය',
    'admin'      => 'තහවුරු කිරීම් සහ පරිශීලක කළමනාකරණය',
    default      => 'ඔබේ වෙන්කිරීම් නිරීක්ෂණය කරන්න',
};
$firstName = explode(' ', $_SESSION['full_name'])[0];
?>
<!DOCTYPE html>
<html lang="si">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>සාදරයෙන් පිළිගනිමු — SkillBridge.lk</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Pirata+One&family=Anton&family=Space+Grotesk:wght@400;500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

<link rel="stylesheet" href="assets/css/post-login.css">
</head>
<body>

<div class="scene" aria-hidden="true">
  <div class="orb orb-violet"></div>
  <div class="orb orb-cyan"></div>
  <div class="orb orb-magenta"></div>
</div>

<header class="brand">
  <span class="brand-mark">Skill<span class="brand-accent">Bridge</span><span class="brand-tld">.lk</span></span>
</header>

<main class="stage">
  <section class="glass-modal">
    <div class="modal-glow"></div>

    <p class="eyebrow">ඔබ ලොග් වී ඇත</p>
    <h1 class="modal-title">ඊළඟට කොහෙද?</h1>
    <p class="modal-sub">නැවත සාදරයෙන් පිළිගනිමු, <?php echo htmlspecialchars($firstName, ENT_QUOTES); ?>.</p>

   <div class="uiverse-grid">
  <a href="index(SIN).html" class="uiverse-card" data-accent="violet">
    <span></span>
    <span></span>
    <span></span>
    <span></span>
    <div>
      <svg class="check" xmlns="http://www.w3.org/2000/svg" height="1em" viewBox="0 0 512 512">
        <path d="M256 512A256 256 0 1 0 256 0a256 256 0 1 0 0 512zM369 209L241 337c-9.4 9.4-24.6 9.4-33.9 0l-64-64c-9.4-9.4-9.4-24.6 0-33.9s24.6-9.4 33.9 0l47 47L335 175c9.4-9.4 24.6-9.4 33.9 0s9.4 24.6 0 33.9z"></path>
      </svg>
      <strong>මුල් පිටුව</strong>
      <p>SkillBridge.lk බලා තහවුරු කළ දේශීය නිදහස් වෘත්තිකයින් සොයාගන්න.</p>
      <hr>
      <span class="uiverse-btn">
        යන්න
        <svg class="arrow" xmlns="http://www.w3.org/2000/svg" height="1em" viewBox="0 0 512 512">
          <path d="M470.6 278.6c12.5-12.5 12.5-32.8 0-45.3l-160-160c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L402.7 256 265.4 393.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0l160-160zm-352 160l160-160c12.5-12.5 12.5-32.8 0-45.3l-160-160c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L210.7 256 73.4 393.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0z"></path>
        </svg>
      </span>
    </div>
  </a>

  <a href="<?php echo $dashboardHref; ?>" class="uiverse-card" data-accent="cyan">
    <span></span>
    <span></span>
    <span></span>
    <span></span>
    <div>
      <svg class="check" xmlns="http://www.w3.org/2000/svg" height="1em" viewBox="0 0 512 512">
        <path d="M256 512A256 256 0 1 0 256 0a256 256 0 1 0 0 512zM369 209L241 337c-9.4 9.4-24.6 9.4-33.9 0l-64-64c-9.4-9.4-9.4-24.6 0-33.9s24.6-9.4 33.9 0l47 47L335 175c9.4-9.4 24.6-9.4 33.9 0s9.4 24.6 0 33.9z"></path>
      </svg>
      <strong><?php echo htmlspecialchars($dashboardLabel, ENT_QUOTES); ?></strong>
      <p><?php echo htmlspecialchars($dashboardSub, ENT_QUOTES); ?></p>
      <hr>
      <span class="uiverse-btn">
        යන්න
        <svg class="arrow" xmlns="http://www.w3.org/2000/svg" height="1em" viewBox="0 0 512 512">
          <path d="M470.6 278.6c12.5-12.5 12.5-32.8 0-45.3l-160-160c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L402.7 256 265.4 393.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0l160-160zm-352 160l160-160c12.5-12.5 12.5-32.8 0-45.3l-160-160c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L210.7 256 73.4 393.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0z"></path>
        </svg>
      </span>
    </div>
  </a>
</div>
  </section>
</main>
<div class="sb-toast-stack" id="sbToastStack"></div>

<script>
  (function () {
    var stack = document.getElementById('sbToastStack');
    var toast = document.createElement('div');
    toast.className = 'sb-toast sb-toast--success';
    var duration = 5000;
    toast.innerHTML =
      '<span class="sb-toast-icon">✓</span>' +
      '<span class="sb-toast-text"><strong>සාදරයෙන් පිළිගනිමු, <?php echo htmlspecialchars($firstName, ENT_QUOTES); ?></strong><?php echo htmlspecialchars($toastMessage, ENT_QUOTES); ?></span>' +
      '<button type="button" class="sb-toast-close" aria-label="ඉවත් කරන්න">×</button>' +
      '<span class="sb-toast-bar" style="animation-duration:' + duration + 'ms"></span>';
    stack.appendChild(toast);

    requestAnimationFrame(function () { toast.classList.add('is-in'); });

    function dismiss() {
      toast.classList.remove('is-in');
      toast.classList.add('is-out');
      setTimeout(function () { toast.remove(); }, 220);
    }
    toast.querySelector('.sb-toast-close').addEventListener('click', dismiss);
    setTimeout(dismiss, duration);
  })();
</script>
</body>
</html>
