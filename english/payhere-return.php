<?php
/**
 * SkillBridge.lk — PayHere return URL (English)
 * ---------------------------------------------------------------------
 * The CUSTOMER'S browser lands here after PayHere checkout — this is
 * NOT proof of payment (a browser redirect can be faked or interrupted).
 * The real confirmation happens server-to-server in payhere-notify.php.
 * This page just forwards to booking-confirmation.php, which checks
 * the actual booking status in the database and shows a "still
 * processing" state if the notify call hasn't landed yet.
 * ---------------------------------------------------------------------
 */
require_once __DIR__ . '/session.php';
require_login();

$bookingId = (int) ($_GET['booking'] ?? 0);
header('Location: booking-confirmation.php?booking=' . $bookingId);
exit;
