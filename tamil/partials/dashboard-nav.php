<?php
require_once __DIR__ . '/../notifications-helper.php';

$activeNav = $activeNav ?? '';
$role = $_SESSION['role'] ?? '';
$unreadCount = unread_notification_count($pdo, (int) $_SESSION['user_id']);

$dashboardHref = match ($role) {
    'freelancer' => 'dashboard.php',
    'admin'      => 'admin-dashboard.php',
    default      => 'client-dashboard.php',
};
?>
<header class="nav">
  <div class="nav-inner">
    <a href="index(TAM).html" class="brand-mark">Skill<span class="brand-accent">Bridge</span><span class="brand-tld">.lk</span></a>
    <nav class="nav-links" id="dashNavLinks">
      <a href="<?php echo $dashboardHref; ?>" class="<?php echo $activeNav === 'dashboard' ? 'is-active' : ''; ?>">டாஷ்போர்டு</a>

      <?php if ($role === 'freelancer'): ?>
        <a href="services.php" class="<?php echo $activeNav === 'services' ? 'is-active' : ''; ?>">சேவைகள்</a>
        <a href="bookings.php" class="<?php echo $activeNav === 'bookings' ? 'is-active' : ''; ?>">முன்பதிவுகள்</a>
        <a href="messages.php" class="<?php echo $activeNav === 'messages' ? 'is-active' : ''; ?>">செய்திகள்</a>
        <a href="freelancer-refunds.php" class="<?php echo $activeNav === 'refunds' ? 'is-active' : ''; ?>">பணத்திரும்பங்கள்</a>

      <?php elseif ($role === 'admin'): ?>
        <a href="admin-verifications.php" class="<?php echo $activeNav === 'verifications' ? 'is-active' : ''; ?>">சரிபார்ப்புகள்</a>
        <a href="admin-users.php" class="<?php echo $activeNav === 'users' ? 'is-active' : ''; ?>">பயனர்கள்</a>
        <a href="admin-refunds.php" class="<?php echo $activeNav === 'refunds' ? 'is-active' : ''; ?>">பணத்திரும்பங்கள்</a>

      <?php else: ?>
        <a href="search.php" class="<?php echo $activeNav === 'search' ? 'is-active' : ''; ?>">தேடு</a>
        <a href="messages.php" class="<?php echo $activeNav === 'messages' ? 'is-active' : ''; ?>">செய்திகள்</a>
      <?php endif; ?>

      <!-- Mobile-only: primary actions folded into the same dropdown as the links -->
      <a href="notifications.php" class="dash-nav-mobile-only">
        அறிவிப்புகள்<?php if ($unreadCount > 0): ?> <span class="status-pill status-cancelled"><?php echo $unreadCount; ?></span><?php endif; ?>
      </a>
      <a href="profile-edit.php" class="dash-nav-mobile-only <?php echo $activeNav === 'profile' ? 'is-active' : ''; ?>">வணக்கம், <?php echo htmlspecialchars(explode(' ', $_SESSION['full_name'])[0], ENT_QUOTES); ?></a>
      <a href="logout.php" class="dash-nav-mobile-only">வெளியேறு</a>
    </nav>
    <div class="nav-actions">
      <a href="notifications.php" class="nav-hello">
        🔔<?php if ($unreadCount > 0): ?><span class="status-pill status-cancelled"><?php echo $unreadCount; ?></span><?php endif; ?>
      </a>
      <a href="profile-edit.php" class="nav-hello <?php echo $activeNav === 'profile' ? 'is-active' : ''; ?>">வணக்கம், <?php echo htmlspecialchars(explode(' ', $_SESSION['full_name'])[0], ENT_QUOTES); ?></a>
      <a href="logout.php" class="btn btn-ghost">வெளியேறு</a>
    </div>
    <button class="nav-burger" id="dashNavBurger" aria-label="மெனுவைத் திறக்கவும்" aria-expanded="false">
      <span></span><span></span><span></span>
    </button>
  </div>
</header>
<script src="assets/js/dashboard-nav.js"></script>