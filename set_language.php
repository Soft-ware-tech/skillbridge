<?php
/**
 * SkillBridge.lk — Language selector handler
 * ---------------------------------------------------------------------
 * Receives the language chosen on the notification page (index.html),
 * stores it in the session so every subsequent page (PHP pages inside
 * /tamil, /english, /sinhala) can read $_SESSION['language'], and
 * redirects the visitor into that folder's own landing page.
 *
 * Shared DB note: all three language folders talk to the SAME database.
 * Only the UI text/templates differ per folder — the session value is
 * what tells shared/config files which language strings to load.
 * ---------------------------------------------------------------------
 */

session_start();

// Figure out the folder this script lives in on the URL (e.g. "/skillbridge"
// if the project sits at localhost/skillbridge/, or "" if it's at the web
// root). Every redirect below is built from this instead of a hardcoded "/",
// so the site works the same whether it's deployed at the root or in a
// subfolder.
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');

// Accept the language from POST (form submit) or GET (e.g. a
// "change language" link like set_language.php?lang=english&redirect=1)
$lang = $_POST['lang'] ?? $_GET['lang'] ?? null;

// Whitelist — folder names must match exactly, one per supported language.
$allowedLanguages = [
    'tamil'    => 'தமிழ்',
    'english'  => 'English',
    'sinhala'  => 'සිංහල',
];

// Each language folder's landing page has its own distinct filename —
// keeps them unmistakably separate from each other and from the root
// picker, so English never silently ends up loading Tamil's page or the
// root index again.
$landingFile = [
    'tamil'   => 'index(TAM).html',
    'english' => 'index(ENG).html',
    'sinhala' => 'index(SIN).html',
];

if (!array_key_exists($lang, $allowedLanguages)) {
    // Unknown / missing language -> send back to the picker.
    header('Location: ' . $basePath . '/index.html');
    exit;
}

// Persist the choice for this visitor's session.
$_SESSION['language'] = $lang;

// Optional: remember the choice past the session too (30 days),
// so returning visitors skip the picker next time.
setcookie('skillbridge_lang', $lang, time() + (30 * 24 * 60 * 60), '/');

// Where to send them back to after switching language from an inner page
// (a dynamic page can pass its own return_to). Falls back to that
// language's own landing page otherwise.
$returnTo = $_POST['return_to'] ?? $_GET['return_to'] ?? null;

if ($returnTo && is_string($returnTo) && strpos($returnTo, '..') === false) {
    header('Location: ' . $basePath . '/' . $lang . '/' . ltrim($returnTo, '/'));
} else {
    header('Location: ' . $basePath . '/' . $lang . '/' . rawurlencode($landingFile[$lang]));
}
exit;
