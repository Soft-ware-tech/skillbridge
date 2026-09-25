<?php
require_once __DIR__ . '/session.php';
require_login('admin');

$roleFilter = in_array($_GET['role'] ?? '', ['client', 'freelancer', 'admin'], true) ? $_GET['role'] : '';
$search = trim($_GET['q'] ?? '');

$users = [];
$dbError = false;
$dbErrorDetail = '';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

try {
    $where = [];
    $params = [];

    if ($roleFilter !== '') {
        $where[] = 'role = :role';
        $params['role'] = $roleFilter;
    }
    if ($search !== '') {
        $where[] = '(LOWER(full_name) LIKE LOWER(:kw1) OR LOWER(email) LIKE LOWER(:kw2))';
        $params['kw1'] = '%' . $search . '%';
        $params['kw2'] = '%' . $search . '%';
    }

    $sql = 'SELECT user_id, full_name, email, role, is_active, created_at FROM users';
    if (!empty($where)) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }
    $sql .= ' ORDER BY created_at DESC LIMIT 100';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $users = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('SkillBridge admin-users.php error: ' . $e->getMessage());
    $dbError = true;
    $dbErrorDetail = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="si">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>පරිශීලකයින් — SkillBridge.lk</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Pirata+One&family=Anton&family=Space+Grotesk:wght@400;500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

<link rel="stylesheet" href="assets/css/dashboard.css?v=2">
</head>
<body>

<div class="scene" aria-hidden="true">
  <div class="orb orb-violet"></div>
  <div class="orb orb-cyan"></div>
</div>

<?php $activeNav = 'users'; include __DIR__ . '/partials/dashboard-nav.php'; ?>

<main class="dash-page">

  <p class="eyebrow">සියලුම ගිණුම්</p>
  <h1 class="dash-title">පරිශීලකයින්</h1>

  <form class="admin-search-bar" method="GET" action="admin-users.php">
    <?php if ($roleFilter !== ''): ?><input type="hidden" name="role" value="<?php echo htmlspecialchars($roleFilter, ENT_QUOTES); ?>"><?php endif; ?>
    <input type="text" name="q" value="<?php echo htmlspecialchars($search, ENT_QUOTES); ?>" placeholder="නම හෝ විද්‍යුත් තැපෑලෙන් සොයන්න...">
    <button type="submit" class="btn btn-outline">සොයන්න</button>
  </form>

  <div class="filter-row">
    <a href="admin-users.php<?php echo $search !== '' ? '?q=' . urlencode($search) : ''; ?>" class="chip <?php echo $roleFilter === '' ? 'is-active' : ''; ?>">සියල්ල</a>
    <a href="admin-users.php?role=client<?php echo $search !== '' ? '&q=' . urlencode($search) : ''; ?>" class="chip <?php echo $roleFilter === 'client' ? 'is-active' : ''; ?>">පාරිභෝගිකයින්</a>
    <a href="admin-users.php?role=freelancer<?php echo $search !== '' ? '&q=' . urlencode($search) : ''; ?>" class="chip <?php echo $roleFilter === 'freelancer' ? 'is-active' : ''; ?>">නිදහස් වෘත්තිකයින්</a>
    <a href="admin-users.php?role=admin<?php echo $search !== '' ? '&q=' . urlencode($search) : ''; ?>" class="chip <?php echo $roleFilter === 'admin' ? 'is-active' : ''; ?>">පරිපාලකයින්</a>
  </div>

  <?php if ($dbError): ?>
    <div class="empty-state">
      <div class="empty-glyph">!</div>
      <h3>පරිශීලකයින් පූරණය කළ නොහැකි විය</h3>
      <p>දත්ත සමුදායට ළඟාවීමේදී යම් දෝෂයක් සිදුවිය. කරුණාකර මොහොතකින් නැවත උත්සාහ කරන්න.</p>
      <?php if (isset($_GET['debug'])): ?>
        <p class="debug-detail"><?php echo htmlspecialchars($dbErrorDetail, ENT_QUOTES); ?></p>
      <?php endif; ?>
    </div>

  <?php elseif (empty($users)): ?>
    <div class="empty-state">
      <div class="empty-glyph">◎</div>
      <h3>පරිශීලකයින් හමු නොවීය</h3>
      <p>මෙම සෙවීම හෝ පෙරහන සමඟ ගැලපෙන කිසිවක් නොමැත.</p>
    </div>

  <?php else: ?>
    <div class="table-list">
      <?php foreach ($users as $u): ?>
        <div class="table-row <?php echo (int) $u['is_active'] === 0 ? 'is-inactive' : ''; ?>">
          <div class="table-row-main">
            <span class="table-category"><?php echo htmlspecialchars(ucfirst($u['role']), ENT_QUOTES); ?></span>
            <h3><?php echo htmlspecialchars($u['full_name'], ENT_QUOTES); ?></h3>
            <span class="table-meta">
              <?php echo htmlspecialchars($u['email'], ENT_QUOTES); ?>
              &middot; එකතු වූ දිනය <?php echo htmlspecialchars(date('d M Y', strtotime($u['created_at'])), ENT_QUOTES); ?>
              <?php if ((int) $u['is_active'] === 0): ?> &middot; <span class="paused-tag">අක්‍රිය කර ඇත</span><?php endif; ?>
            </span>
          </div>
          <div class="table-row-actions">
            <?php if ($u['role'] !== 'admin' || (int) $u['user_id'] !== (int) $_SESSION['user_id']): ?>
              <form method="POST" action="admin-user-toggle.php" onsubmit="return confirm('<?php echo (int) $u['is_active'] === 1 ? 'Deactivate' : 'Reactivate'; ?> this account?');">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES); ?>">
                <input type="hidden" name="user_id" value="<?php echo (int) $u['user_id']; ?>">
                <button type="submit" class="btn btn-outline btn-sm <?php echo (int) $u['is_active'] === 1 ? 'btn-danger' : ''; ?>">
                  <?php echo (int) $u['is_active'] === 1 ? 'Deactivate' : 'Reactivate'; ?>
                </button>
              </form>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

</main>

</body>
</html>
