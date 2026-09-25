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
    'email_taken'    => 'That email is already registered — try logging in instead.',
    'invalid_email'  => 'Please enter a valid email address.',
    'weak_password'  => 'Password must be at least 8 characters.',
    'missing_fields' => 'Please fill in all required fields.',
    'invalid_login'  => 'Incorrect email or password.',
    'invalid_csrf'   => 'Your session expired — please try again.',
    'server_error'   => 'Something went wrong on our end — please try again.',
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
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SkillBridge.lk — Log In / Sign Up</title>

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
  <a href="index(ENG).html" class="brand-mark chrome-text">Skill<span class="chrome-text">Bridge</span><span class="brand-tld">.lk</span></a>
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

        <h1 class="auth-title" id="authTitle">Sign Up Account</h1>
        <p class="auth-sub" id="authSub">Enter your details to create your account.</p>

        <?php if ($errorMessage): ?>
        <p class="auth-error" id="authError"><?php echo htmlspecialchars($errorMessage, ENT_QUOTES); ?></p>
        <?php endif; ?>

        <form id="authForm" action="<?php echo $initialMode === 'register' ? 'register-process.php' : 'login-process.php'; ?>" method="POST" autocomplete="on">

          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES); ?>">
          <input type="hidden" name="role" id="roleInput" value="<?php echo htmlspecialchars($initialRole, ENT_QUOTES); ?>">
          <input type="hidden" name="redirect" id="redirectInput" value="<?php echo htmlspecialchars($redirectTarget, ENT_QUOTES); ?>">

          <div class="role-toggle" id="roleToggle">
            <button type="button" class="role-option<?php echo $initialRole === 'client' ? ' is-active' : ''; ?>" data-role="client">Find a Freelancer</button>
            <button type="button" class="role-option<?php echo $initialRole === 'freelancer' ? ' is-active' : ''; ?>" data-role="freelancer">Offer Services</button>
          </div>

          <div class="field-row" id="nameRow">
            <div class="field">
              <label for="firstName">First Name</label>
              <input type="text" id="firstName" name="first_name" placeholder="eg. Ravindu">
            </div>
            <div class="field">
              <label for="lastName">Last Name</label>
              <input type="text" id="lastName" name="last_name" placeholder="eg. Perera">
            </div>
          </div>

          <div class="field">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" placeholder="eg. ravindu@gmail.com" required>
          </div>

          <div class="field">
            <label for="password">Password</label>
            <div class="password-wrap">
              <input type="password" id="password" name="password" placeholder="Enter your password" required minlength="8">
              <button type="button" class="password-toggle" id="passwordToggle" aria-label="Show password"><span class="btn-chrome-text">Show</span></button>
            </div>
            <span class="field-hint" id="passwordHint">Must be at least 8 characters.</span>
          </div>

          <button type="submit" class="btn btn-primary btn-lg btn-block" id="authSubmit"><span class="btn-chrome-text">Sign Up</span></button>
        </form>

        <p class="auth-switch">
          <span id="switchPrompt">Already have an account?</span>
          <a href="login.php<?php echo $redirectTarget !== '' ? '?redirect=' . urlencode($redirectTarget) : ''; ?>" id="toggleModeLink">Log in</a>
        </p>

      </div>
    </div>
  </section>

</main>

<script src="assets/js/auth.js"></script>
</body>
</html>