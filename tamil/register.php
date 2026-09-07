<?php
/**
 * SkillBridge.lk — /tamil/register.php
 * ---------------------------------------------------------------------
 * The sign-up UI lives in login.php (one page, two modes — see the
 * comment at the top of that file). This just forwards here so every
 * "ஃப்ரீலான்சராக ஆகுங்கள்" / "இலவசமாக இணையுங்கள்" link on the site keeps working
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
