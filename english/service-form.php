<?php
require_once __DIR__ . '/session.php';
require_login('freelancer');

$myId = (int) $_SESSION['user_id'];
$serviceId = (int) ($_GET['service'] ?? 0);
$isEdit = $serviceId > 0;

$profile = null;
$categories = [];
$service = null; // existing values when editing
$formError = null;
$dbError = false;

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

try {
    $stmt = $pdo->prepare('SELECT profile_id FROM freelancer_profiles WHERE user_id = :user_id LIMIT 1');
    $stmt->execute(['user_id' => $myId]);
    $profile = $stmt->fetch() ?: null;

    $catStmt = $pdo->query('SELECT category_id, category_name FROM categories ORDER BY category_name');
    $categories = $catStmt->fetchAll();

    if ($profile && $isEdit) {
        $stmt = $pdo->prepare('SELECT * FROM services WHERE service_id = :service_id AND profile_id = :profile_id LIMIT 1');
        $stmt->execute(['service_id' => $serviceId, 'profile_id' => $profile['profile_id']]);
        $service = $stmt->fetch() ?: null;
    }
} catch (PDOException $e) {
    error_log('SkillBridge service-form.php load error: ' . $e->getMessage());
    $dbError = true;
}

$notFound = $isEdit && !$service;

// ---- Handle submission ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $profile && !$notFound && !$dbError) {
    $submittedToken = $_POST['csrf_token'] ?? '';
    if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $submittedToken)) {
        $formError = 'Your session expired — please try again.';
    } else {
        $title = trim($_POST['title'] ?? '');
        $categoryId = (int) ($_POST['category_id'] ?? 0);
        $price = (float) ($_POST['price'] ?? 0);
        $description = trim($_POST['description'] ?? '');

        if ($title === '' || $categoryId <= 0 || $price <= 0) {
            $formError = 'Please fill in a title, category, and a price greater than 0.';
        } else {
            try {
                if ($isEdit) {
                    $update = $pdo->prepare("
                        UPDATE services
                        SET title = :title, category_id = :category_id, price = :price, description = :description
                        WHERE service_id = :service_id AND profile_id = :profile_id
                    ");
                    $update->execute([
                        'title'       => $title,
                        'category_id' => $categoryId,
                        'price'       => $price,
                        'description' => $description,
                        'service_id'  => $serviceId,
                        'profile_id'  => $profile['profile_id'],
                    ]);
                } else {
                                      $insert = $pdo->prepare("
                        INSERT INTO services (profile_id, category_id, title, price, description, language)
                        VALUES (:profile_id, :category_id, :title, :price, :description, 'english')
                    ");
                    $insert->execute([
                        'profile_id'  => $profile['profile_id'],
                        'category_id' => $categoryId,
                        'title'       => $title,
                        'price'       => $price,
                        'description' => $description,
                    ]);
                }
                header('Location: services.php?saved=1');
                exit;
            } catch (PDOException $e) {
                error_log('SkillBridge service-form.php save error: ' . $e->getMessage());
                $formError = 'Something went wrong saving this service. Please try again.';
            }
        }
    }
}

// ---- Values to pre-fill (POST-back on error takes priority over DB row) ----
$vTitle = $_POST['title'] ?? $service['title'] ?? '';
$vCategoryId = $_POST['category_id'] ?? $service['category_id'] ?? '';
$vPrice = $_POST['price'] ?? $service['price'] ?? '';
$vDescription = $_POST['description'] ?? $service['description'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $isEdit ? 'Edit' : 'Add'; ?> Service — SkillBridge.lk</title>

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

<?php $activeNav = 'services'; include __DIR__ . '/partials/dashboard-nav.php'; ?>

<main class="dash-page dash-page--narrow">

  <?php if ($dbError): ?>
    <div class="empty-state">
      <div class="empty-glyph">!</div>
      <h3>Couldn't load this page</h3>
      <p>Something went wrong reaching the database. Please try again shortly.</p>
    </div>

  <?php elseif ($notFound): ?>
    <div class="empty-state">
      <div class="empty-glyph">◎</div>
      <h3>Service not found</h3>
      <p>This listing doesn't exist, or isn't yours to edit.</p>
      <a href="services.php" class="btn btn-outline">Back to Services</a>
    </div>

  <?php else: ?>

    <p class="eyebrow"><?php echo $isEdit ? 'Edit Listing' : 'New Listing'; ?></p>
    <h1 class="dash-title"><?php echo $isEdit ? 'Edit Service' : 'Add a Service'; ?></h1>

    <div class="form-panel">
      <?php if ($formError): ?>
        <p class="flow-error"><?php echo htmlspecialchars($formError, ENT_QUOTES); ?></p>
      <?php endif; ?>

      <form method="POST" action="service-form.php<?php echo $isEdit ? '?service=' . $serviceId : ''; ?>">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES); ?>">

        <div class="field">
          <label for="title">Service Title</label>
          <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($vTitle, ENT_QUOTES); ?>" placeholder="e.g. One-on-One Tutoring Session" required>
        </div>

        <div class="field">
          <label for="category_id">Category</label>
          <select id="category_id" name="category_id" required>
            <option value="">Select a category</option>
            <?php foreach ($categories as $cat): ?>
              <option value="<?php echo (int) $cat['category_id']; ?>" <?php echo (int) $vCategoryId === (int) $cat['category_id'] ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($cat['category_name'], ENT_QUOTES); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="field">
          <label for="price">Price (LKR)</label>
          <input type="number" id="price" name="price" value="<?php echo htmlspecialchars((string) $vPrice, ENT_QUOTES); ?>" min="1" step="1" placeholder="2500" required>
        </div>

        <div class="field">
          <label for="description">Description</label>
          <textarea id="description" name="description" rows="4" placeholder="What's included, how it works, anything a client should know..."><?php echo htmlspecialchars($vDescription, ENT_QUOTES); ?></textarea>
        </div>

        <div class="form-actions">
          <button type="submit" class="btn btn-primary">Save Service</button>
          <a href="services.php" class="btn btn-outline">Cancel</a>
        </div>
      </form>
    </div>

  <?php endif; ?>

</main>

</body>
</html>
