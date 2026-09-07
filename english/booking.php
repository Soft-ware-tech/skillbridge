<?php
/**
 * SkillBridge.lk — Booking (English)
 * ---------------------------------------------------------------------
 * Reached from a "Book Now" button on profile.php via ?service=<id>.
 * Requires login — require_login() sends anonymous visitors to login.php
 * with a ?redirect= back to this exact URL, so they land right back
 * here after signing in.
 *
 * On submit: creates a `bookings` row with status='pending', then sends
 * the client on to payment.php to complete it.
 * ---------------------------------------------------------------------
 */
require_once __DIR__ . '/session.php';
require_login();

$dashboardHref = match ($_SESSION['role'] ?? '') {
    'freelancer' => 'dashboard.php',
    'admin'      => 'admin-dashboard.php',
    default      => 'client-dashboard.php',
};

$serviceId = (int) ($_GET['service'] ?? 0);

$service = null;
$dbError = false;
$dbErrorDetail = '';
$formError = null;

try {
    $stmt = $pdo->prepare("
        SELECT s.service_id, s.title, s.price, s.description,
               fp.profile_id, u.user_id AS freelancer_user_id, u.full_name AS freelancer_name,
               c.category_name
        FROM services s
        JOIN freelancer_profiles fp ON s.profile_id = fp.profile_id
        JOIN users u                ON fp.user_id = u.user_id
        JOIN categories c           ON s.category_id = c.category_id
        WHERE s.service_id = :service_id AND s.is_active = 1
        LIMIT 1
    ");
    $stmt->execute(['service_id' => $serviceId]);
    $service = $stmt->fetch() ?: null;
} catch (PDOException $e) {
    error_log('SkillBridge booking.php error: ' . $e->getMessage());
    $dbError = true;
    $dbErrorDetail = $e->getMessage();
}

// A freelancer can't book their own listing.
$isOwnService = $service && (int) $service['freelancer_user_id'] === (int) $_SESSION['user_id'];

// ---- Dates this freelancer already has a CONFIRMED booking on (any of their services) ----
$bookedDates = [];
if ($service && !$dbError) {
    try {
        $datesStmt = $pdo->prepare("
            SELECT DISTINCT b.booking_date
            FROM bookings b
            JOIN services s2 ON b.service_id = s2.service_id
            JOIN freelancer_profiles fp2 ON s2.profile_id = fp2.profile_id
            WHERE fp2.user_id = :freelancer_id
              AND b.status = 'confirmed'
        ");
        $datesStmt->execute(['freelancer_id' => $service['freelancer_user_id']]);
        $bookedDates = array_column($datesStmt->fetchAll(), 'booking_date');
    } catch (PDOException $e) {
        error_log('SkillBridge booking.php booked-dates error: ' . $e->getMessage());
    }
}

// ---- CSRF token for the booking form ----
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

// ---- Handle submission ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $service && !$isOwnService && !$dbError) {
    $submittedToken = $_POST['csrf_token'] ?? '';
    if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $submittedToken)) {
        $formError = 'Your session expired — please try again.';
    } else {
        $bookingDate = trim($_POST['booking_date'] ?? '');
        $today = date('Y-m-d');

        $latRaw = trim($_POST['latitude'] ?? '');
        $lngRaw = trim($_POST['longitude'] ?? '');
        $latitude = ($latRaw !== '' && is_numeric($latRaw)) ? (float) $latRaw : null;
        $longitude = ($lngRaw !== '' && is_numeric($lngRaw)) ? (float) $lngRaw : null;
        if ($latitude !== null && ($latitude < -90 || $latitude > 90)) {
            $latitude = null;
        }
        if ($longitude !== null && ($longitude < -180 || $longitude > 180)) {
            $longitude = null;
        }

        if ($bookingDate === '' || $bookingDate < $today) {
            $formError = 'Please choose a valid date (today or later).';
        } elseif (in_array($bookingDate, $bookedDates, true)) {
            $formError = 'This freelancer already has a confirmed booking on that date. Please pick another date.';
        } else {
            try {
                $insert = $pdo->prepare("
                    INSERT INTO bookings (service_id, client_id, booking_date, latitude, longitude, status)
                    VALUES (:service_id, :client_id, :booking_date, :latitude, :longitude, 'pending')
                ");
                $insert->execute([
                    'service_id'   => $service['service_id'],
                    'client_id'    => $_SESSION['user_id'],
                    'booking_date' => $bookingDate,
                    'latitude'     => $latitude,
                    'longitude'    => $longitude,
                ]);
                $bookingId = (int) $pdo->lastInsertId();

                header('Location: payment.php?booking=' . $bookingId);
                exit;
            } catch (PDOException $e) {
                error_log('SkillBridge booking.php insert error: ' . $e->getMessage());
                $formError = 'Something went wrong creating your booking. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Book a Service — SkillBridge.lk</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Pirata+One&family=Anton&family=Space+Grotesk:wght@400;500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

<link rel="stylesheet" href="assets/css/booking.css">
<link rel="stylesheet" href="assets/css/dashboard.css">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
</head>
<body>

<div class="scene" aria-hidden="true">
  <div class="orb orb-violet"></div>
  <div class="orb orb-cyan"></div>
</div>

<header class="nav">
  <div class="nav-inner">
    <a href="index(ENG).html" class="brand-mark">Skill<span class="brand-accent">Bridge</span><span class="brand-tld">.lk</span></a>
    <nav class="nav-links">
      <a href="<?php echo $dashboardHref; ?>">Dashboard</a>
    </nav>
    <span class="nav-hello">Hi, <?php echo htmlspecialchars(explode(' ', $_SESSION['full_name'])[0], ENT_QUOTES); ?></span>
  </div>
</header>

<main class="flow-page">

  <?php if ($dbError): ?>
    <div class="empty-state">
      <div class="empty-glyph">!</div>
      <h3>Couldn't load this service</h3>
      <p>Something went wrong reaching the database. Please try again shortly.</p>
      <?php if (isset($_GET['debug'])): ?>
        <p class="debug-detail"><?php echo htmlspecialchars($dbErrorDetail, ENT_QUOTES); ?></p>
      <?php endif; ?>
    </div>

  <?php elseif (!$service): ?>
    <div class="empty-state">
      <div class="empty-glyph">◎</div>
      <h3>Service not found</h3>
      <p>This listing may have been removed.</p>
      <a href="search.php" class="btn btn-outline">Back to Search</a>
    </div>

  <?php elseif ($isOwnService): ?>
    <div class="empty-state">
      <div class="empty-glyph">i</div>
      <h3>That's your own listing</h3>
      <p>You can't book a service you offer yourself.</p>
      <a href="search.php" class="btn btn-outline">Back to Search</a>
    </div>

  <?php else: ?>

    <p class="eyebrow">Confirm Your Booking</p>
    <h1 class="flow-title">Book This Service</h1>

    <div class="flow-grid">

      <!-- ---- Summary card ---- -->
      <aside class="summary-card">
        <span class="summary-category"><?php echo htmlspecialchars($service['category_name'], ENT_QUOTES); ?></span>
        <h3><?php echo htmlspecialchars($service['title'], ENT_QUOTES); ?></h3>
        <p class="summary-freelancer">by <?php echo htmlspecialchars($service['freelancer_name'], ENT_QUOTES); ?></p>
        <?php if (!empty($service['description'])): ?>
          <p class="summary-desc"><?php echo htmlspecialchars($service['description'], ENT_QUOTES); ?></p>
        <?php endif; ?>
        <div class="summary-price-row">
          <span>Price</span>
          <span class="summary-price">LKR <?php echo number_format((float) $service['price'], 0); ?></span>
        </div>
      </aside>

      <!-- ---- Booking form ---- -->
      <section class="flow-form">
        <?php if ($formError): ?>
          <p class="flow-error"><?php echo htmlspecialchars($formError, ENT_QUOTES); ?></p>
        <?php endif; ?>

        <form method="POST" action="booking.php?service=<?php echo (int) $service['service_id']; ?>">
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES); ?>">

          <div class="field">
            <label for="bookingDate">Preferred Date</label>
            <input type="date" id="bookingDate" name="booking_date" min="<?php echo date('Y-m-d'); ?>" value="<?php echo htmlspecialchars($_POST['booking_date'] ?? '', ENT_QUOTES); ?>" required>
            <p id="bookingDateStatus" class="flow-error" hidden>This freelancer already has a confirmed booking on that date — please pick another date.</p>
          </div>

          <div class="field">
            <label>Your Location <span class="field-hint">— optional, but it helps <?php echo htmlspecialchars($service['freelancer_name'], ENT_QUOTES); ?> find you for this job</span></label>

            <div class="location-picker">
              <div class="location-search-row">
                <input type="text" id="locationSearchBox" placeholder="Type an address or area, e.g. Kandy, Sri Lanka">
                <button type="button" id="locationSearchBtn" class="btn btn-outline btn-sm">Find</button>
                <button type="button" id="useMyLocationBtn" class="btn btn-outline btn-sm">📍 Use My Location</button>
              </div>

              <div id="locationMap" class="location-map" role="group" aria-label="Click the map to set your location"></div>

              <p id="locationStatus" class="location-status">No location set yet. Click the map, search an address, or use your current location.</p>

              <button type="button" id="clearLocationBtn" class="btn btn-ghost btn-sm">Clear location</button>
            </div>

            <input type="hidden" id="latitude" name="latitude" value="<?php echo htmlspecialchars($_POST['latitude'] ?? '', ENT_QUOTES); ?>">
            <input type="hidden" id="longitude" name="longitude" value="<?php echo htmlspecialchars($_POST['longitude'] ?? '', ENT_QUOTES); ?>">
          </div>

          <p class="flow-note">You'll confirm payment on the next step. The freelancer will be notified once payment is complete.</p>

          <button type="submit" class="btn btn-primary btn-lg btn-block">Continue to Payment</button>
        </form>
      </section>

    </div>

  <?php endif; ?>

</main>

<?php if (!$dbError && $service && !$isOwnService): ?>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="assets/js/location-picker.js"></script>
<script>
  initLocationPicker();
</script>

<script>
(function () {
  var bookedDates = <?php echo json_encode(array_values($bookedDates)); ?>;
  var dateInput = document.getElementById('bookingDate');
  var statusMsg = document.getElementById('bookingDateStatus');
  var submitBtn = document.querySelector('.flow-form button[type="submit"]');

  function checkDate() {
    if (bookedDates.indexOf(dateInput.value) !== -1) {
      statusMsg.hidden = false;
      dateInput.setCustomValidity('This date is already booked with this freelancer.');
      if (submitBtn) submitBtn.disabled = true;
    } else {
      statusMsg.hidden = true;
      dateInput.setCustomValidity('');
      if (submitBtn) submitBtn.disabled = false;
    }
  }

  dateInput.addEventListener('change', checkDate);
  dateInput.addEventListener('input', checkDate);
  checkDate(); // in case a previously-submitted invalid date is prefilled
})();
</script>
<?php endif; ?>

</body>
</html>