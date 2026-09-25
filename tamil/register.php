<?php
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
