<?php
/**
 * SkillBridge.lk — Search / Browse Freelancers (English)
 * ---------------------------------------------------------------------
 * Every "ஃப்ரீலான்சரைத் தேடுங்கள்" link, category-carousel card, and nav item
 * on the landing page points here. Filters (category, keyword, verified
 * only, sort) all come through as GET params so results are shareable
 * /bookmarkable links, e.g. search.php?category=tutoring&sort=rating.
 * ---------------------------------------------------------------------
 */
require_once __DIR__ . '/inc-category-labels.php';
require_once __DIR__ . '/session.php'; // gives us session_start() + $pdo

$dashboardHref = match ($_SESSION['role'] ?? '') {
    'freelancer' => 'dashboard.php',
    'admin'      => 'admin-dashboard.php',
    default      => 'client-dashboard.php',
};

// ---- Read filters from the query string --------------------------------
$categoryKey  = trim($_GET['category'] ?? '');
$keyword      = trim($_GET['q'] ?? '');
$verifiedOnly = isset($_GET['verified']);

// "அருகில்" search — the client's coordinates (dropped in via JS, see
// assets/js/search-map.js, from either browser Geolocation or a plain
// "?lat=..&lng=.." link) travel through as GET params so results stay a
// shareable/bookmarkable link, same as every other filter here.
$originLatRaw = $_GET['lat'] ?? '';
$originLngRaw = $_GET['lng'] ?? '';
$hasOrigin = is_numeric($originLatRaw) && is_numeric($originLngRaw)
    && (float) $originLatRaw >= -90 && (float) $originLatRaw <= 90
    && (float) $originLngRaw >= -180 && (float) $originLngRaw <= 180;
$originLat = $hasOrigin ? (float) $originLatRaw : null;
$originLng = $hasOrigin ? (float) $originLngRaw : null;

// Radius filter (km) only makes sense once we have an origin to measure from.
$radiusKm = null;
if ($hasOrigin && in_array($_GET['radius'] ?? '', ['5', '10', '25', '50'], true)) {
    $radiusKm = (float) $_GET['radius'];
}

$allowedSorts = ['rating', 'price_low', 'price_high', 'newest'];
if ($hasOrigin) {
    $allowedSorts[] = 'nearby';
}
$sort = in_array($_GET['sort'] ?? '', $allowedSorts, true)
    ? $_GET['sort']
    : ($hasOrigin ? 'nearby' : 'rating');

$page    = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 12;
$offset  = ($page - 1) * $perPage;

// ---- Load categories for the filter chips -------------------------------
$categories = [];
try {
    $catStmt = $pdo->query('SELECT category_key, category_name FROM categories ORDER BY category_name');
    $categories = $catStmt->fetchAll();
} catch (PDOException $e) {
    error_log('SkillBridge search.php categories error: ' . $e->getMessage());
}

// ---- Build the results query ---------------------------------------------
$where  = ['s.is_active = 1', "s.language = 'tamil'"];
$params = [];

if ($categoryKey !== '') {
    $where[] = 'c.category_key = :category_key';
    $params['category_key'] = $categoryKey;
}
if ($keyword !== '') {
    // Split into words so word order in the query doesn't matter — each
    // word just needs to appear SOMEWHERE across the freelancer's skill
    // category, bio, or name. LOWER() on both sides makes the match
    // case-insensitive regardless of the table's collation.
    //
    // NOTE: each occurrence gets its OWN placeholder name (a/b/c) even
    // though they all hold the same value. Real prepared statements
    // (PDO::ATTR_EMULATE_PREPARES = false, set in config/db.php) don't
    // allow one named placeholder to be reused more than once in a
    // query — MySQL's native protocol expects a bound value per
    // occurrence, not per unique name.
    $words = preg_split('/\s+/', $keyword, -1, PREG_SPLIT_NO_EMPTY);
    foreach ($words as $i => $word) {
        $pCategory = "kw{$i}a";
        $pBio      = "kw{$i}b";
        $pName     = "kw{$i}c";
        $where[] = "(LOWER(fp.skill_category) LIKE LOWER(:{$pCategory})
                     OR LOWER(fp.bio) LIKE LOWER(:{$pBio})
                     OR LOWER(u.full_name) LIKE LOWER(:{$pName}))";
        $params[$pCategory] = '%' . $word . '%';
        $params[$pBio]      = '%' . $word . '%';
        $params[$pName]     = '%' . $word . '%';
    }
}
if ($verifiedOnly) {
    $where[] = 'fp.verified_badge = 1';
}

// Haversine great-circle distance in km, only computable for freelancers
// who've dropped a pin (fp.latitude/longitude both set) — NULL otherwise,
// which we deliberately let sort to the bottom rather than pretending
// they're "0 km away".
$distanceSelect = 'NULL';
if ($hasOrigin) {
    $distanceSelect = "(
        6371 * acos(
            LEAST(1, GREATEST(-1,
                cos(radians(:origin_lat_a)) * cos(radians(fp.latitude)) *
                cos(radians(fp.longitude) - radians(:origin_lng)) +
                sin(radians(:origin_lat_b)) * sin(radians(fp.latitude))
            ))
        )
    )";
    $params['origin_lat_a'] = $originLat;
    $params['origin_lat_b'] = $originLat;
    $params['origin_lng'] = $originLng;
}

// Radius filter: keep only freelancers within X km — applied with HAVING
// since distance_km isn't a plain WHERE-clause column (and NULLs must be
// excluded, not accidentally included by a stray comparison).
$having = '';
if ($hasOrigin && $radiusKm !== null) {
    $having = 'HAVING distance_km IS NOT NULL AND distance_km <= :radius_km';
    $params['radius_km'] = $radiusKm;
}

$orderBy = match ($sort) {
    'price_low'  => 's.price ASC',
    'price_high' => 's.price DESC',
    'newest'     => 's.created_at DESC',
    'nearby'     => 'ISNULL(distance_km) ASC, distance_km ASC',
    default      => 'avg_rating DESC, review_count DESC',
};

$sql = "
    SELECT
        s.service_id, s.title, s.price, s.description,
        fp.profile_id, fp.verified_badge, fp.bio,
        fp.latitude, fp.longitude,
        u.full_name,
        c.category_name, c.category_key,
        COALESCE(AVG(r.rating), 0)  AS avg_rating,
        COUNT(DISTINCT r.review_id) AS review_count,
        {$distanceSelect} AS distance_km
    FROM services s
    JOIN freelancer_profiles fp ON s.profile_id = fp.profile_id
    JOIN users u               ON fp.user_id = u.user_id
    JOIN categories c          ON s.category_id = c.category_id
    LEFT JOIN bookings b       ON b.service_id = s.service_id
    LEFT JOIN reviews r        ON r.booking_id = b.booking_id
    WHERE " . implode(' AND ', $where) . "
    GROUP BY s.service_id, s.title, s.price, s.description,
             fp.profile_id, fp.verified_badge, fp.bio, fp.latitude, fp.longitude,
             u.full_name, c.category_name, c.category_key
    {$having}
    ORDER BY {$orderBy}
    LIMIT :limit OFFSET :offset
";

$results = [];
$hasNextPage = false;
$dbError = false;
$dbErrorDetail = '';

try {
    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue(':' . $key, $value);
    }
    // Fetch one extra row so we know whether a "Next" page exists,
    // without running a separate COUNT(*) query.
    $stmt->bindValue(':limit', $perPage + 1, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $results = $stmt->fetchAll();

    if (count($results) > $perPage) {
        $hasNextPage = true;
        $results = array_slice($results, 0, $perPage);
    }
} catch (PDOException $e) {
    error_log('SkillBridge search.php query error: ' . $e->getMessage());
    $dbError = true;
    $dbErrorDetail = $e->getMessage(); // shown on-screen only with ?debug=1, see below
}

function build_query(array $overrides): string
{
    $params = array_merge($_GET, $overrides);
    foreach ($params as $key => $value) {
        if ($value === null || $value === '' || $value === false) {
            unset($params[$key]);
        }
    }
    return htmlspecialchars('search.php?' . http_build_query($params), ENT_QUOTES);
}

function initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name));
    $letters = array_map(fn($p) => mb_strtoupper(mb_substr($p, 0, 1)), array_slice($parts, 0, 2));
    return implode('', $letters) ?: '?';
}
?>
<!DOCTYPE html>
<html lang="ta">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ஃப்ரீலான்சரைத் தேடுங்கள் — SkillBridge.lk</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Pirata+One&family=Anton&family=Space+Grotesk:wght@400;500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

<link rel="stylesheet" href="assets/css/search.css?v=3">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
</head>
<body>

<div class="scene" aria-hidden="true">
  <div class="orb orb-violet"></div>
  <div class="orb orb-cyan"></div>
</div>

<!-- =========================== NAV =========================== -->
<header class="nav">
  <div class="nav-inner">
    <a href="index(TAM).html" class="brand-mark">Skill<span class="brand-accent">Bridge</span><span class="brand-tld">.lk</span></a>
    <nav class="nav-links">
      <a href="index(TAM).html#categories">வகைகள்</a>
      <a href="index(TAM).html#how-it-works">இது எவ்வாறு செயல்படுகிறது</a>
    </nav>
    <div class="nav-actions">
      <?php if (isset($_SESSION['user_id'])): ?>
        <a href="<?php echo $dashboardHref; ?>" class="btn btn-outline">டாஷ்போர்டு</a>
        <?php if (($_SESSION['role'] ?? '') === 'freelancer'): ?>
          <a href="dashboard.php" class="nav-hello">வணக்கம், <?php echo htmlspecialchars(explode(' ', $_SESSION['full_name'])[0], ENT_QUOTES); ?></a>
        <?php else: ?>
          <span class="nav-hello">வணக்கம், <?php echo htmlspecialchars(explode(' ', $_SESSION['full_name'])[0], ENT_QUOTES); ?></span>
        <?php endif; ?>
        <a href="logout.php" class="btn btn-ghost">வெளியேறு</a>
      <?php else: ?>
        <a href="login.php" class="btn btn-ghost">உள்நுழை</a>
        <a href="register.php" class="btn btn-primary">இலவசமாக இணையுங்கள்</a>
      <?php endif; ?>
    </div>
  </div>
</header>

<main class="search-page">

  <!-- =========================== SEARCH HEADER =========================== -->
  <section class="search-hero">
    <p class="eyebrow">ஒரு ஃப்ரீலான்சரைத் தேடுங்கள்</p>
    <h1 class="search-title">சந்தையைத் தேடுங்கள்</h1>

    <form class="search-bar" action="search.php" method="GET">
      <?php if ($categoryKey !== ''): ?>
        <input type="hidden" name="category" value="<?php echo htmlspecialchars($categoryKey, ENT_QUOTES); ?>">
      <?php endif; ?>
      <?php if ($hasOrigin): ?>
        <input type="hidden" name="lat" value="<?php echo htmlspecialchars((string) $originLat, ENT_QUOTES); ?>">
        <input type="hidden" name="lng" value="<?php echo htmlspecialchars((string) $originLng, ENT_QUOTES); ?>">
        <?php if ($radiusKm !== null): ?>
          <input type="hidden" name="radius" value="<?php echo htmlspecialchars((string) $radiusKm, ENT_QUOTES); ?>">
        <?php endif; ?>
      <?php endif; ?>
      <input type="text" name="q" value="<?php echo htmlspecialchars($keyword, ENT_QUOTES); ?>" placeholder="சேவை, திறன், அல்லது ஃப்ரீலான்சர் பெயரால் தேடுங்கள்...">
      <button type="submit" class="btn btn-primary">தேடு</button>
    </form>

    <div class="chip-row">
      <a href="<?php echo build_query(['category' => null, 'page' => null]); ?>" class="chip<?php echo $categoryKey === '' ? ' is-active' : ''; ?>">அனைத்தும்</a>
      <?php foreach ($categories as $cat): ?>
               <a href="<?php echo build_query(['category' => $cat['category_key'], 'page' => null]); ?>" class="chip<?php echo $categoryKey === $cat['category_key'] ? ' is-active' : ''; ?>">
          <?php echo htmlspecialchars(category_label($cat['category_key'], $cat['category_name']), ENT_QUOTES); ?>
        </a>
      <?php endforeach; ?>
    </div>

    <!-- =========================== NEARBY SEARCH =========================== -->
    <div class="nearby-bar">
      <?php if (!$hasOrigin): ?>
        <button type="button" id="useMyLocationBtn" class="chip chip-nearby">📍 எனக்கு அருகில் தேடு</button>
        <span class="nearby-hint">ஃப்ரீலான்சர்களை தூரத்தின் அடிப்படையில் வரிசைப்படுத்த உங்கள் உலாவி இருப்பிடத்தைப் பயன்படுத்துகிறது.</span>
      <?php else: ?>
        <span class="nearby-active-tag">📍 உங்களுக்கு அருகில் வரிசைப்படுத்தப்பட்டது</span>
        <div class="chip-row chip-row--inline">
          <a href="<?php echo build_query(['radius' => null, 'page' => null]); ?>" class="chip<?php echo $radiusKm === null ? ' is-active' : ''; ?>">எந்த தூரமும்</a>
          <?php foreach ([5, 10, 25, 50] as $r): ?>
            <a href="<?php echo build_query(['radius' => $r, 'page' => null]); ?>" class="chip<?php echo $radiusKm === (float) $r ? ' is-active' : ''; ?>">உள்ளே <?php echo $r; ?> km</a>
          <?php endforeach; ?>
        </div>
        <a href="<?php echo build_query(['lat' => null, 'lng' => null, 'radius' => null, 'sort' => null, 'page' => null]); ?>" class="clear-location-link">✕ இருப்பிடத்தை அழி</a>
      <?php endif; ?>
    </div>
  </section>

  <!-- =========================== RESULTS =========================== -->
  <section class="results-section">

    <div class="results-toolbar">
      <span class="results-count">
        <?php echo $dbError ? '' : count($results) . ($hasNextPage ? '+' : '') . ' result' . (count($results) === 1 ? '' : 's'); ?>
                <?php if ($categoryKey !== ''): ?>
          <?php
            $activeCatName = $categoryKey;
            foreach ($categories as $cat) {
                if ($cat['category_key'] === $categoryKey) { $activeCatName = $cat['category_name']; break; }
            }
            echo htmlspecialchars(category_label($categoryKey, $activeCatName), ENT_QUOTES);
          ?> இல்
        <?php endif; ?>
      </span>

      <div class="toolbar-controls">
        <label class="verified-check">
          <input type="checkbox" onchange="window.location.href=this.checked ? '<?php echo build_query(['verified' => 1, 'page' => null]); ?>' : '<?php echo build_query(['verified' => null, 'page' => null]); ?>'" <?php echo $verifiedOnly ? 'checked' : ''; ?>>
          சரிபார்க்கப்பட்டவை மட்டும்
        </label>

        <div class="sort-select">
          <label for="sortSelect">வரிசைப்படுத்து</label>
          <select id="sortSelect" onchange="window.location.href=this.value">
            <?php if ($hasOrigin): ?>
              <option value="<?php echo build_query(['sort' => 'nearby', 'page' => null]); ?>" <?php echo $sort === 'nearby' ? 'selected' : ''; ?>>அருகிலுள்ளது</option>
            <?php endif; ?>
            <option value="<?php echo build_query(['sort' => 'rating', 'page' => null]); ?>" <?php echo $sort === 'rating' ? 'selected' : ''; ?>>அதிக மதிப்பீடு பெற்றவை</option>
            <option value="<?php echo build_query(['sort' => 'price_low', 'page' => null]); ?>" <?php echo $sort === 'price_low' ? 'selected' : ''; ?>>விலை: குறைவு முதல் அதிகம்</option>
            <option value="<?php echo build_query(['sort' => 'price_high', 'page' => null]); ?>" <?php echo $sort === 'price_high' ? 'selected' : ''; ?>>விலை: அதிகம் முதல் குறைவு</option>
            <option value="<?php echo build_query(['sort' => 'newest', 'page' => null]); ?>" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>புதியது</option>
          </select>
        </div>

        <?php if (!$dbError && !empty($results)): ?>
          <button type="button" id="viewToggleBtn" class="btn btn-outline btn-sm" data-mode="list">🗺️ வரைபடக் காட்சி</button>
        <?php endif; ?>
      </div>
    </div>

    <?php if ($dbError): ?>
      <div class="empty-state">
        <div class="empty-glyph">!</div>
        <h3>முடிவுகளை ஏற்ற முடியவில்லை</h3>
        <p>தரவுத்தளத்தை அணுகுவதில் ஏதோ தவறு நடந்தது. சிறிது நேரத்தில் மீண்டும் முயற்சிக்கவும்.</p>
        <?php if (isset($_GET['debug'])): ?>
          <p style="color:#FF3D9A; font-family: monospace; font-size:12px; text-align:left; background:rgba(255,61,154,0.08); padding:14px; border-radius:10px; margin-top:16px; word-break:break-word;">
            <?php echo htmlspecialchars($dbErrorDetail, ENT_QUOTES); ?>
          </p>
        <?php endif; ?>
      </div>

    <?php elseif (empty($results)): ?>
      <div class="empty-state">
        <div class="empty-glyph">◎</div>
        <h3>ஃப்ரீலான்சர்கள் யாரும் கிடைக்கவில்லை</h3>
        <p>
          <?php if ($keyword !== '' || $categoryKey !== ''): ?>
            வேறு முக்கிய வார்த்தையை முயற்சிக்கவும் அல்லது அனைத்து வகைகளையும் பார்வையிடவும்.
          <?php else: ?>
            இதுவரை பட்டியல்கள் இல்லை — விரைவில் மீண்டும் பாருங்கள், அல்லது முதலில் இணையுங்கள்.
          <?php endif; ?>
        </p>
        <a href="<?php echo build_query(['category' => null, 'q' => null, 'verified' => null, 'page' => null]); ?>" class="btn btn-outline">அனைத்து வகைகளையும் பார்வையிடவும்</a>
      </div>

    <?php else: ?>
      <div id="resultsMap" class="results-map" hidden></div>

      <div class="result-grid" id="resultGrid">
        <?php foreach ($results as $row): ?>
          <a href="profile.php?service=<?php echo (int) $row['service_id']; ?>" class="result-card">
            <div class="result-top">
              <div class="result-avatar"><?php echo htmlspecialchars(initials($row['full_name']), ENT_QUOTES); ?></div>
              <div class="result-who">
                <h3><?php echo htmlspecialchars($row['full_name'], ENT_QUOTES); ?></h3>
                                <span class="result-category"><?php echo htmlspecialchars(category_label($row['category_key'], $row['category_name']), ENT_QUOTES); ?></span>
              </div>
              <?php if ((int) $row['verified_badge'] === 1): ?>
                <span class="verified-tag" title="SkillBridge.lk ஆல் சரிபார்க்கப்பட்டது">✓</span>
              <?php endif; ?>
            </div>

            <h4 class="result-service"><?php echo htmlspecialchars($row['title'], ENT_QUOTES); ?></h4>

            <div class="result-meta">
              <span class="result-rating">
                ★ <?php echo number_format((float) $row['avg_rating'], 1); ?>
                <span class="result-review-count">(<?php echo (int) $row['review_count']; ?>)</span>
              </span>
              <?php if ($row['distance_km'] !== null): ?>
                <span class="result-distance"><?php echo number_format((float) $row['distance_km'], 1); ?> கிமீ தொலைவில்</span>
              <?php endif; ?>
              <span class="result-price">LKR <?php echo number_format((float) $row['price'], 0); ?></span>
            </div>
          </a>
        <?php endforeach; ?>
      </div>

      <script id="resultsMapData" type="application/json">
        <?php
          $mapPoints = [];
          foreach ($results as $row) {
              if ($row['latitude'] !== null && $row['longitude'] !== null) {
                  $mapPoints[] = [
                      'lat'      => (float) $row['latitude'],
                      'lng'      => (float) $row['longitude'],
                      'name'     => $row['full_name'],
                      'service'  => $row['title'],
                      'price'    => (float) $row['price'],
                      'url'      => 'profile.php?service=' . (int) $row['service_id'],
                  ];
              }
          }
          echo json_encode($mapPoints, JSON_UNESCAPED_SLASHES);
        ?>
      </script>

      <div class="pagination">
        <?php if ($page > 1): ?>
          <a href="<?php echo build_query(['page' => $page - 1]); ?>" class="btn btn-outline">← முந்தைய</a>
        <?php endif; ?>
        <span class="page-indicator">பக்கம் <?php echo $page; ?></span>
        <?php if ($hasNextPage): ?>
          <a href="<?php echo build_query(['page' => $page + 1]); ?>" class="btn btn-outline">அடுத்து →</a>
        <?php endif; ?>
      </div>
    <?php endif; ?>

  </section>

</main>

<script src="assets/js/search.js"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="assets/js/search-map.js"></script>
</body>
</html>