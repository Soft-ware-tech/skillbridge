<?php
/**
 * SkillBridge.lk — Login / Register (English)
 * ---------------------------------------------------------------------
 * One page, two modes. ?mode=register shows the sign-up form; the
 * default (no query, or ?mode=login) shows the login form. Both are
 * just the centered form card — the toggle link switches modes
 * client-side without a reload.
 *
 * register.php is a one-line redirect into this file with
 * ?mode=register, so both URLs used elsewhere on the site
 * (login.php / register.php) keep working.
 *
 * The form posts to register-process.php or login-process.php
 * (auth.js swaps the form's `action` when the mode toggles). Both
 * process files require_once session.php for $pdo + session access,
 * then redirect back here with ?error=... if something goes wrong.
 * ---------------------------------------------------------------------
 */
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
    'email_taken'    => 'இந்த மின்னஞ்சல் ஏற்கனவே பதிவு செய்யப்பட்டுள்ளது — அதற்கு பதிலாக உள்நுழையவும்.',
    'invalid_email'  => 'தயவுசெய்து சரியான மின்னஞ்சல் முகவரியை உள்ளிடவும்.',
    'weak_password'  => 'கடவுச்சொல் குறைந்தது 8 எழுத்துகள் இருக்க வேண்டும்.',
    'missing_fields' => 'தேவையான அனைத்து புலங்களையும் நிரப்பவும்.',
    'invalid_login'  => 'தவறான மின்னஞ்சல் அல்லது கடவுச்சொல்.',
    'invalid_csrf'   => 'உங்கள் அமர்வு காலாவதியானது — மீண்டும் முயற்சிக்கவும்.',
    'server_error'   => 'எங்கள் தரப்பில் ஏதோ தவறு நடந்தது — மீண்டும் முயற்சிக்கவும்.',
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
<html lang="ta">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SkillBridge.lk — உள்நுழை / பதிவு செய்யுங்கள்</title>

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
  <a href="index(TAM).html" class="brand-mark chrome-text">Skill<span class="chrome-text">Bridge</span><span class="brand-tld">.lk</span></a>
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

        <h1 class="auth-title" id="authTitle">கணக்கைப் பதிவு செய்யுங்கள்</h1>
        <p class="auth-sub" id="authSub">உங்கள் கணக்கை உருவாக்க விவரங்களை உள்ளிடவும்.</p>

        <?php if ($errorMessage): ?>
        <p class="auth-error" id="authError"><?php echo htmlspecialchars($errorMessage, ENT_QUOTES); ?></p>
        <?php endif; ?>

        <form id="authForm" action="<?php echo $initialMode === 'register' ? 'register-process.php' : 'login-process.php'; ?>" method="POST" autocomplete="on">

          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES); ?>">
          <input type="hidden" name="role" id="roleInput" value="<?php echo htmlspecialchars($initialRole, ENT_QUOTES); ?>">
          <input type="hidden" name="redirect" id="redirectInput" value="<?php echo htmlspecialchars($redirectTarget, ENT_QUOTES); ?>">

          <div class="role-toggle" id="roleToggle">
            <button type="button" class="role-option<?php echo $initialRole === 'client' ? ' is-active' : ''; ?>" data-role="client">ஃப்ரீலான்சரைத் தேடுங்கள்</button>
            <button type="button" class="role-option<?php echo $initialRole === 'freelancer' ? ' is-active' : ''; ?>" data-role="freelancer">சேவைகளை வழங்குங்கள்</button>
          </div>

          <div class="field-row" id="nameRow">
            <div class="field">
              <label for="firstName">முதற் பெயர்</label>
              <input type="text" id="firstName" name="first_name" placeholder="eg. Ravindu">
            </div>
            <div class="field">
              <label for="lastName">கடைசிப் பெயர்</label>
              <input type="text" id="lastName" name="last_name" placeholder="eg. Perera">
            </div>
          </div>

          <div class="field">
            <label for="email">மின்னஞ்சல்</label>
            <input type="email" id="email" name="email" placeholder="eg. ravindu@gmail.com" required>
          </div>

          <div class="field">
            <label for="password">கடவுச்சொல்</label>
            <div class="password-wrap">
              <input type="password" id="password" name="password" placeholder="உங்கள் கடவுச்சொல்லை உள்ளிடவும்" required minlength="8">
              <button type="button" class="password-toggle" id="passwordToggle" aria-label="கடவுச்சொல்லைக் காட்டு"><span class="btn-chrome-text">காட்டு</span></button>
            </div>
            <span class="field-hint" id="passwordHint">குறைந்தது 8 எழுத்துகள் இருக்க வேண்டும்.</span>
          </div>

          <button type="submit" class="btn btn-primary btn-lg btn-block" id="authSubmit"><span class="btn-chrome-text">பதிவு செய்யுங்கள்</span></button>
        </form>

        <p class="auth-switch">
          <span id="switchPrompt">ஏற்கனவே கணக்கு உள்ளதா?</span>
          <a href="login.php<?php echo $redirectTarget !== '' ? '?redirect=' . urlencode($redirectTarget) : ''; ?>" id="toggleModeLink">உள்நுழை</a>
        </p>

      </div>
    </div>
  </section>

</main>

<script src="assets/js/auth.js"></script>
</body>
</html>