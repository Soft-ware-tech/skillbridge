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
    'freelancer' => 'உங்கள் டாஷ்போர்டில் புதிய வேலை வாய்ப்புகள் காத்திருக்கின்றன.',
    'admin'      => 'உங்கள் நிர்வாக பலகை தயார் — சரிபார்ப்புகள் காத்திருக்கின்றன.',
    'client'     => "நீங்கள் உள்ளே! உங்களுக்கு அருகில் உள்ள சரிபார்க்கப்பட்ட ஃப்ரீலான்சர்களைப் பார்க்கத் தொடங்குங்கள்.",
];
$toastMessage = $roleToastMessages[$role] ?? $roleToastMessages['client'];
$dashboardHref = match ($role) {
    'freelancer' => 'dashboard.php',
    'admin'      => 'admin-dashboard.php',
    default      => 'client-dashboard.php',
};
$dashboardLabel = match ($role) {
    'freelancer' => 'ஃப்ரீலான்சர் டாஷ்போர்டு',
    'admin'      => 'நிர்வாக டாஷ்போர்டு',
    default      => 'எனது முன்பதிவுகள்',
};
$dashboardGlyph = match ($role) {
    'freelancer' => '≡',
    'admin'      => '⚑',
    default      => '◷',
};
$dashboardSub = match ($role) {
    'freelancer' => 'சேவைகள் & முன்பதிவுகளை நிர்வகி',
    'admin'      => 'சரிபார்ப்புகள் & பயனர் நிர்வாகம்',
    default      => 'உங்கள் முன்பதிவுகளைக் கண்காணிக்கவும்',
};
$firstName = explode(' ', $_SESSION['full_name'])[0];
?>
<!DOCTYPE html>
<html lang="ta">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>வரவேற்கிறோம் — SkillBridge.lk</title>

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

    <p class="eyebrow">நீங்கள் உள்நுழைந்துள்ளீர்கள்</p>
    <h1 class="modal-title">அடுத்து எங்கு?</h1>
    <p class="modal-sub">மீண்டும் வரவேற்கிறோம், <?php echo htmlspecialchars($firstName, ENT_QUOTES); ?>.</p>

   <div class="uiverse-grid">
  <a href="index(TAM).html" class="uiverse-card" data-accent="violet">
    <span></span>
    <span></span>
    <span></span>
    <span></span>
    <div>
      <svg class="check" xmlns="http://www.w3.org/2000/svg" height="1em" viewBox="0 0 512 512">
        <path d="M256 512A256 256 0 1 0 256 0a256 256 0 1 0 0 512zM369 209L241 337c-9.4 9.4-24.6 9.4-33.9 0l-64-64c-9.4-9.4-9.4-24.6 0-33.9s24.6-9.4 33.9 0l47 47L335 175c9.4-9.4 24.6-9.4 33.9 0s9.4 24.6 0 33.9z"></path>
      </svg>
      <strong>முகப்புப் பக்கம்</strong>
      <p>SkillBridge.lk-ஐப் பார்வையிட்டு சரிபார்க்கப்பட்ட உள்ளூர் ஃப்ரீலான்சர்களைக் கண்டறியவும்.</p>
      <hr>
      <span class="uiverse-btn">
        செல்
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
        செல்
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
      '<span class="sb-toast-text"><strong>வரவேற்கிறோம், <?php echo htmlspecialchars($firstName, ENT_QUOTES); ?></strong><?php echo htmlspecialchars($toastMessage, ENT_QUOTES); ?></span>' +
      '<button type="button" class="sb-toast-close" aria-label="நிராகரி">×</button>' +
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
