<?php
session_start();

$initialMode = (($_GET['mode'] ?? '') === 'register') ? 'register' : 'login';

$initialRole = ($_GET['as'] ?? '') === 'freelancer' ? 'freelancer' : 'client';

// CSRF token — one per session, reused across both forms on this page.
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

// register-process.php / login-process.php redirect back here with
// ?error=<code> on failure — turn that into a plain message for the page.
$errorMessages = [
    'email_taken'    => 'එම විද්‍යුත් තැපෑල දැනටමත් ලියාපදිංචි කර ඇත — ඒ වෙනුවට පිවිසෙන්න.',
    'invalid_email'  => 'කරුණාකර වලංගු විද්‍යුත් තැපැල් ලිපිනයක් ඇතුළත් කරන්න.',
    'weak_password'  => 'මුරපදය අවම වශයෙන් අක්ෂර 8ක් විය යුතුය.',
    'missing_fields' => 'කරුණාකර අවශ්‍ය සියලුම ක්ෂේත්‍ර පුරවන්න.',
    'invalid_login'  => 'වැරදි විද්‍යුත් තැපෑල හෝ මුරපදය.',
    'invalid_csrf'   => 'ඔබේ සැසිය කල් ඉකුත් විය — කරුණාකර නැවත උත්සාහ කරන්න.',
    'server_error'   => 'අපගේ පැත්තෙන් යම් දෝෂයක් සිදුවිය — කරුණාකර නැවත උත්සාහ කරන්න.',
];
$errorCode = $_GET['error'] ?? null;
$errorMessage = $errorMessages[$errorCode] ?? null;

// Where to send the visitor after a successful login/register — set by
// require_login() in session.php when it sent them here mid-task (e.g.
// mid-booking). Only accept a local path, never an external URL.
$redirectTarget = $_GET['redirect'] ?? '';
if ($redirectTarget !== '' && (str_starts_with($redirectTarget, 'http') || str_contains($redirectTarget, '//'))) {
    $redirectTarget = '';
}
?>
<!DOCTYPE html>
<html lang="si">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SkillBridge.lk — පිවිසෙන්න / ලියාපදිංචි වන්න</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Pirata+One&family=Anton&family=Space+Grotesk:wght@400;500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

<link rel="stylesheet" href="assets/css/auth.css">
</head>
<body>
<svg style="position: absolute; width: 0; height: 0;">
  <filter id="unopaq" y="-100%" height="300%" x="-100%" width="300%">
    <feColorMatrix values="1 0 0 0 0  0 1 0 0 0  0 0 1 0 0  0 0 0 5 0"></feColorMatrix>
  </filter>
  <filter id="unopaq2" y="-100%" height="300%" x="-100%" width="300%">
    <feColorMatrix values="1 0 0 0 0  0 1 0 0 0  0 0 1 0 0  0 0 0 10 0"></feColorMatrix>
  </filter>
  <filter id="unopaq3" y="-100%" height="300%" x="-100%" width="300%">
    <feColorMatrix values="1 0 0 1 0  0 1 0 1 0  0 0 1 1 0  0 0 0 2 0"></feColorMatrix>
  </filter>
</svg>
<header class="auth-topbar">
  <a href="index(SIN).html" class="brand-mark chrome-text">Skill<span class="chrome-text">Bridge</span><span class="brand-tld">.lk</span></a>
 </header>
<main class="auth-stage" id="authStage" data-mode="<?php echo $initialMode; ?>">

  <!-- ============================ FORM PANEL ============================ -->
  <section class="auth-panel-form" id="panelForm">
     <div class="auth-card-wrap">
      <div class="spin spin-blur"></div>
      <div class="spin spin-intense"></div>
      <div class="auth-card-border">
        <div class="spin spin-inside"></div>
      </div>
      <div class="auth-form-inner">

        <h1 class="auth-title" id="authTitle">ගිණුම ලියාපදිංචි කරන්න</h1>
        <p class="auth-sub" id="authSub">ඔබේ ගිණුම සෑදීමට විස්තර ඇතුළත් කරන්න.</p>

        <?php if ($errorMessage): ?>
        <p class="auth-error" id="authError"><?php echo htmlspecialchars($errorMessage, ENT_QUOTES); ?></p>
        <?php endif; ?>

        <form id="authForm" action="<?php echo $initialMode === 'register' ? 'register-process.php' : 'login-process.php'; ?>" method="POST" autocomplete="on">

          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES); ?>">
          <input type="hidden" name="role" id="roleInput" value="<?php echo htmlspecialchars($initialRole, ENT_QUOTES); ?>">
          <input type="hidden" name="redirect" id="redirectInput" value="<?php echo htmlspecialchars($redirectTarget, ENT_QUOTES); ?>">

          <div class="role-toggle" id="roleToggle">
            <button type="button" class="role-option<?php echo $initialRole === 'client' ? ' is-active' : ''; ?>" data-role="client">නිදහස් වෘත්තිකයෙකු සොයන්න</button>
            <button type="button" class="role-option<?php echo $initialRole === 'freelancer' ? ' is-active' : ''; ?>" data-role="freelancer">සේවා පිරිනමන්න</button>
          </div>

          <div class="field-row" id="nameRow">
            <div class="field">
              <label for="firstName">මුල් නම</label>
              <input type="text" id="firstName" name="first_name" placeholder="eg. Ravindu">
            </div>
            <div class="field">
              <label for="lastName">අවසන් නම</label>
              <input type="text" id="lastName" name="last_name" placeholder="eg. Perera">
            </div>
          </div>

          <div class="field">
            <label for="email">විද්‍යුත් තැපෑල</label>
            <input type="email" id="email" name="email" placeholder="eg. ravindu@gmail.com" required>
          </div>

          <div class="field">
            <label for="password">මුරපදය</label>
            <div class="password-wrap">
              <input type="password" id="password" name="password" placeholder="ඔබේ මුරපදය ඇතුළත් කරන්න" required minlength="8">
              <button type="button" class="password-toggle" id="passwordToggle" aria-label="මුරපදය පෙන්වන්න"><span class="btn-chrome-text">පෙන්වන්න</span></button>
            </div>
            <span class="field-hint" id="passwordHint">අවම වශයෙන් අක්ෂර 8ක් විය යුතුය.</span>
          </div>

          <button type="submit" class="btn btn-primary btn-lg btn-block" id="authSubmit"><span class="btn-chrome-text">ලියාපදිංචි වන්න</span></button>
        </form>

        <p class="auth-switch">
          <span id="switchPrompt">දැනටමත් ගිණුමක් තිබේද?</span>
          <a href="login.php<?php echo $redirectTarget !== '' ? '?redirect=' . urlencode($redirectTarget) : ''; ?>" id="toggleModeLink">පිවිසෙන්න</a>
        </p>

      </div>
    </div>
  </section>

</main>

<script src="assets/js/auth.js"></script>
</body>
</html>