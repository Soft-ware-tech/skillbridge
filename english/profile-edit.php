<?php
require_once __DIR__ . '/session.php';
require_login();

$myId = (int) $_SESSION['user_id'];
$role = $_SESSION['role'] ?? 'client';
$isFreelancer = $role === 'freelancer';

$dashboardHref = match ($role) {
    'freelancer' => 'dashboard.php',
    'admin'      => 'admin-dashboard.php',
    default      => 'client-dashboard.php',
};

$user = null;
$profile = null; // freelancer_profiles row, only for freelancers
$formError = null;
$dbError = false;
$saved = isset($_GET['saved']);

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

try {
    $stmt = $pdo->prepare('SELECT user_id, full_name, phone, photo_path FROM users WHERE user_id = :user_id LIMIT 1');
    $stmt->execute(['user_id' => $myId]);
    $user = $stmt->fetch() ?: null;

    if ($isFreelancer && $user) {
        $stmt = $pdo->prepare('SELECT profile_id, bio, skill_category, verified_badge, latitude, longitude FROM freelancer_profiles WHERE user_id = :user_id LIMIT 1');
        $stmt->execute(['user_id' => $myId]);
        $profile = $stmt->fetch() ?: null;
    }
} catch (PDOException $e) {
    error_log('SkillBridge profile-edit.php load error: ' . $e->getMessage());
    $dbError = true;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user && !$dbError) {
    $submittedToken = $_POST['csrf_token'] ?? '';
    if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $submittedToken)) {
        $formError = 'Your session expired — please try again.';
    } else {
        $fullName = trim($_POST['full_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');

        if ($fullName === '') {
            $formError = 'Please enter your name.';
        }

        // ---- Profile photo upload (optional, any role) ----
        $newPhotoPath = $user['photo_path'];
        if (!$formError && isset($_FILES['photo']) && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE) {
            $file = $_FILES['photo'];
            $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

            if ($file['error'] !== UPLOAD_ERR_OK) {
                $formError = 'Photo upload failed — please try again.';
            } elseif ($file['size'] > 3 * 1024 * 1024) {
                $formError = 'Photo is too large — please choose one under 3MB.';
            } else {
                $mime = mime_content_type($file['tmp_name']);
                if (!isset($allowed[$mime])) {
                    $formError = 'Photo must be a JPG, PNG, or WEBP image.';
                } else {
                    $uploadDir = __DIR__ . '/assets/uploads/avatars';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    $filename = 'user_' . $myId . '_' . time() . '.' . $allowed[$mime];
                    if (move_uploaded_file($file['tmp_name'], $uploadDir . '/' . $filename)) {
                        // Clean up the previous photo file, if any.
                        if (!empty($user['photo_path'])) {
                            $oldFile = __DIR__ . '/' . $user['photo_path'];
                            if (is_file($oldFile)) {
                                @unlink($oldFile);
                            }
                        }
                        $newPhotoPath = 'assets/uploads/avatars/' . $filename;
                    } else {
                        $formError = 'Photo upload failed — please try again.';
                    }
                }
            }
        }

        // ---- Freelancer-only fields ----
        $skillCategory = $profile['skill_category'] ?? '';
        $bio = $profile['bio'] ?? '';
        $latitude = $profile['latitude'] ?? null;
        $longitude = $profile['longitude'] ?? null;

        if (!$formError && $isFreelancer && $profile) {
            $skillCategory = trim($_POST['skill_category'] ?? '');
            $bio = trim($_POST['bio'] ?? '');

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
        }

        if (!$formError) {
            try {
                $update = $pdo->prepare('UPDATE users SET full_name = :full_name, phone = :phone, photo_path = :photo_path WHERE user_id = :user_id');
                $update->execute([
                    'full_name'  => $fullName,
                    'phone'      => $phone !== '' ? $phone : null,
                    'photo_path' => $newPhotoPath,
                    'user_id'    => $myId,
                ]);

                if ($isFreelancer && $profile) {
                    $update = $pdo->prepare('UPDATE freelancer_profiles SET skill_category = :skill_category, bio = :bio, latitude = :latitude, longitude = :longitude WHERE user_id = :user_id');
                    $update->execute([
                        'skill_category' => $skillCategory,
                        'bio' => $bio,
                        'latitude' => $latitude,
                        'longitude' => $longitude,
                        'user_id' => $myId,
                    ]);
                }

                $_SESSION['full_name'] = $fullName;
                header('Location: profile-edit.php?saved=1');
                exit;
            } catch (PDOException $e) {
                error_log('SkillBridge profile-edit.php save error: ' . $e->getMessage());
                $formError = 'Something went wrong saving your profile. Please try again.';
            }
        }
    }
}

$vFullName = $_POST['full_name'] ?? $user['full_name'] ?? '';
$vPhone = $_POST['phone'] ?? $user['phone'] ?? '';
$vPhotoPath = $user['photo_path'] ?? '';
$vSkillCategory = $_POST['skill_category'] ?? $profile['skill_category'] ?? '';
$vBio = $_POST['bio'] ?? $profile['bio'] ?? '';
$vLatitude = $_POST['latitude'] ?? $profile['latitude'] ?? '';
$vLongitude = $_POST['longitude'] ?? $profile['longitude'] ?? '';

function profile_initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name));
    $letters = array_map(fn($p) => mb_strtoupper(mb_substr($p, 0, 1)), array_slice($parts, 0, 2));
    return implode('', $letters) ?: '?';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Profile — SkillBridge.lk</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Pirata+One&family=Anton&family=Space+Grotesk:wght@400;500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

<link rel="stylesheet" href="assets/css/dashboard.css?v=2">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
</head>
<body>

<div class="scene" aria-hidden="true">
  <div class="orb orb-violet"></div>
  <div class="orb orb-cyan"></div>
</div>

<?php $activeNav = 'profile'; include __DIR__ . '/partials/dashboard-nav.php'; ?>

<main class="dash-page dash-page--narrow">

  <?php if ($dbError || !$user): ?>
    <div class="empty-state">
      <div class="empty-glyph">!</div>
      <h3>Couldn't load your profile</h3>
      <p>Something went wrong reaching the database. Please try again shortly.</p>
    </div>

  <?php else: ?>

    <p class="eyebrow"><?php echo $isFreelancer ? 'Your Public Profile' : 'Your Account'; ?></p>
    <h1 class="dash-title">Edit Profile</h1>

    <div class="form-panel">
      <?php if ($saved): ?>
        <p class="flash-success">Profile updated.</p>
      <?php endif; ?>
      <?php if ($formError): ?>
        <p class="flow-error"><?php echo htmlspecialchars($formError, ENT_QUOTES); ?></p>
      <?php endif; ?>

      <?php if ($isFreelancer && $profile): ?>
        <?php if ((int) $profile['verified_badge'] === 1): ?>
          <div class="notice-banner notice-banner--good">
            <span class="notice-icon">✓</span>
            <p>Your profile is verified — this badge shows on your public profile and in search results.</p>
          </div>
        <?php else: ?>
          <div class="notice-banner">
            <span class="notice-icon">i</span>
            <p>Not verified yet. An admin reviews new profiles — this can't be self-granted.</p>
          </div>
        <?php endif; ?>
      <?php endif; ?>

      <form method="POST" action="profile-edit.php" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES); ?>">

        <div class="field">
          <label>Profile Photo</label>
          <div class="photo-upload-row">
            <span class="profile-avatar profile-avatar--sm">
              <?php if ($vPhotoPath): ?>
                <img src="<?php echo htmlspecialchars($vPhotoPath, ENT_QUOTES); ?>" alt="" class="profile-avatar-img">
              <?php else: ?>
                <?php echo htmlspecialchars(profile_initials($vFullName), ENT_QUOTES); ?>
              <?php endif; ?>
            </span>
            <input type="file" id="photo" name="photo" accept="image/jpeg,image/png,image/webp">
          </div>
          <p class="field-hint">JPG, PNG, or WEBP — up to 3MB.</p>
        </div>

        <div class="field">
          <label for="full_name">Full Name</label>
          <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($vFullName, ENT_QUOTES); ?>" required>
        </div>

        <div class="field">
          <label for="phone">Phone</label>
          <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($vPhone, ENT_QUOTES); ?>" placeholder="e.g. 077 123 4567">
        </div>

        <?php if ($isFreelancer && $profile): ?>

          <div class="field">
            <label for="skill_category">Headline / Skill Category</label>
            <input type="text" id="skill_category" name="skill_category" value="<?php echo htmlspecialchars($vSkillCategory, ENT_QUOTES); ?>" placeholder="e.g. Web Design">
          </div>

          <div class="field">
            <label for="bio">Bio</label>
            <textarea id="bio" name="bio" rows="6" placeholder="Tell clients about your experience, what you specialize in, and why they should book you..."><?php echo htmlspecialchars($vBio, ENT_QUOTES); ?></textarea>
          </div>

          <div class="field">
            <label>Location <span class="field-hint">— shown on your public profile, and lets clients find you with "Nearby" search</span></label>

            <div class="location-picker">
              <div class="location-search-row">
                <input type="text" id="locationSearchBox" placeholder="Type an address or area, e.g. Kandy, Sri Lanka">
                <button type="button" id="locationSearchBtn" class="btn btn-outline btn-sm">Find</button>
                <button type="button" id="useMyLocationBtn" class="btn btn-outline btn-sm">📍 Use My Location</button>
              </div>

              <div id="locationMap" class="location-map" role="group" aria-label="Click the map to set your location"></div>

              <p id="locationStatus" class="location-status">
                <?php if ($vLatitude !== '' && $vLongitude !== ''): ?>
                  Pin set — drag it or click elsewhere on the map to move it.
                <?php else: ?>
                  No location set yet. Click the map, search an address, or use your current location.
                <?php endif; ?>
              </p>

              <button type="button" id="clearLocationBtn" class="btn btn-ghost btn-sm">Clear location</button>
            </div>

            <input type="hidden" id="latitude" name="latitude" value="<?php echo htmlspecialchars((string) $vLatitude, ENT_QUOTES); ?>">
            <input type="hidden" id="longitude" name="longitude" value="<?php echo htmlspecialchars((string) $vLongitude, ENT_QUOTES); ?>">
          </div>

        <?php endif; ?>

        <div class="form-actions">
          <button type="submit" class="btn btn-primary">Save Changes</button>
          <a href="<?php echo htmlspecialchars($dashboardHref, ENT_QUOTES); ?>" class="btn btn-outline">Cancel</a>
        </div>
      </form>
    </div>

  <?php endif; ?>

</main>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<?php if ($isFreelancer && $profile): ?>
<script src="assets/js/location-picker.js"></script>
<script>
  initLocationPicker();
</script>
<?php endif; ?>

</body>
</html>
