<?php
require_once __DIR__ . '/session.php';
require_login();

$role = $_SESSION['role'] ?? '';
$roleToastMessages = [
    'freelancer' => 'You have new job opportunities waiting on your dashboard.',
    'admin'      => 'Your admin console is ready — verifications are waiting.',
    'client'     => "You're in! Start browsing verified freelancers near you.",
];
$toastMessage = $roleToastMessages[$role] ?? $roleToastMessages['client'];
$dashboardHref = match ($role) {
    'freelancer' => 'dashboard.php',
    'admin'      => 'admin-dashboard.php',
    default      => 'client-dashboard.php',
};
$dashboardLabel = match ($role) {
    'freelancer' => 'Freelancer Dashboard',
    'admin'      => 'Admin Dashboard',
    default      => 'My Bookings',
};
$dashboardGlyph = match ($role) {
    'freelancer' => '≡',
    'admin'      => '⚑',
    default      => '◷',
};
$dashboardSub = match ($role) {
    'freelancer' => 'Manage services & bookings',
    'admin'      => 'Verifications & user management',
    default      => 'Track your bookings',
};
$firstName = explode(' ', $_SESSION['full_name'])[0];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Welcome — SkillBridge.lk</title>

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

    <p class="eyebrow">You're Logged In</p>
    <h1 class="modal-title">Where To Next?</h1>
    <p class="modal-sub">Welcome back, <?php echo htmlspecialchars($firstName, ENT_QUOTES); ?>.</p>

   <div class="uiverse-grid">
  <a href="index(ENG).html" class="uiverse-card" data-accent="violet">
    <span></span>
    <span></span>
    <span></span>
    <span></span>
    <div>
      <svg class="check" xmlns="http://www.w3.org/2000/svg" height="1em" viewBox="0 0 512 512">
        <path d="M256 512A256 256 0 1 0 256 0a256 256 0 1 0 0 512zM369 209L241 337c-9.4 9.4-24.6 9.4-33.9 0l-64-64c-9.4-9.4-9.4-24.6 0-33.9s24.6-9.4 33.9 0l47 47L335 175c9.4-9.4 24.6-9.4 33.9 0s9.4 24.6 0 33.9z"></path>
      </svg>
      <strong>Homepage</strong>
      <p>Browse SkillBridge.lk and discover verified local freelancers.</p>
      <hr>
      <span class="uiverse-btn">
        Go
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
        Go
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
      '<span class="sb-toast-text"><strong>Welcome, <?php echo htmlspecialchars($firstName, ENT_QUOTES); ?></strong><?php echo htmlspecialchars($toastMessage, ENT_QUOTES); ?></span>' +
      '<button type="button" class="sb-toast-close" aria-label="Dismiss">×</button>' +
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
