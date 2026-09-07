<?php
/**
 * SkillBridge.lk — /sinhala/register.php
 * ---------------------------------------------------------------------
 * The sign-up UI lives in login.php (one page, two modes — see the
 * comment at the top of that file). This just forwards here so every
 * "නිදහස් වෘත්තිකයෙකු වන්න" / "නොමිලේ එකතු වන්න" link on the site keeps working
 * without duplicating the form markup.
 * ---------------------------------------------------------------------
 */
$as = $_GET['as'] ?? null;
$redirect = $_GET['redirect'] ?? null;

$query = 'mode=register';
if ($as !== null) {
    $query .= '&as=' . urlencode($as);
}
if ($redirect !== null) {
    $query .= '&redirect=' . urlencode($redirect);
}

header('Location: login.php?' . $query);
exit;
