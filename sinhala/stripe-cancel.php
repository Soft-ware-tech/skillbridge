<?php
require_once __DIR__ . '/session.php';
require_login();

$bookingId = (int) ($_GET['booking'] ?? 0);
header('Location: payment.php?booking=' . $bookingId . '&cancelled=1');
exit;