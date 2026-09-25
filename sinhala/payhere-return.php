<?php
require_once __DIR__ . '/session.php';
require_login();

$bookingId = (int) ($_GET['booking'] ?? 0);
header('Location: booking-confirmation.php?booking=' . $bookingId);
exit;
