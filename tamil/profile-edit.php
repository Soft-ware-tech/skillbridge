<?php
/**
 * SkillBridge.lk — Edit Profile (English)
 * ---------------------------------------------------------------------
 * Available to every logged-in role (freelancer / client / admin):
 *   - Full name, phone, and profile photo live on `users` and are
 *     editable by anyone.
 *   - Bio, headline skill category, and pinpoint location live on
 *     `freelancer_profiles` and only render/save for freelancers.
 *     verified_badge is intentionally NOT editable here — only an
 *     admin can grant that.
 *
 * Profile photo: uploaded to assets/uploads/avatars/user_<id>.<ext>
 * and the relative path saved to users.photo_path.
 *
 * Location picker (freelancers only): a Leaflet map (OpenStreetMap
 * tiles — free, no API key/billing needed) to click/drag a pin on,
 * plus an address search box (OSM's free Nominatim geocoder).
 * assets/js/location-picker.js writes the chosen coordinates into the
 * hidden #latitude/#longitude inputs below, which is what actually
 * gets saved.
 * ---------------------------------------------------------------------
 */
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
        $formError = 'உங்கள் அமர்வு காலாவதியானது — மீண்டும் முயற்சிக்கவும்.';
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
<html lang="ta">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>சுயவிவரத்தைத் திருத்து — SkillBridge.lk</title>

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
      <h3>உங்கள் சுயவிவரத்தை ஏற்ற முடியவில்லை</h3>
      <p>தரவுத்தளத்தை அணுகுவதில் ஏதோ தவறு நடந்தது. சிறிது நேரத்தில் மீண்டும் முயற்சிக்கவும்.</p>
    </div>

  <?php else: ?>

    <p class="eyebrow"><?php echo $isFreelancer ? 'Your Public Profile' : 'Your Account'; ?></p>
    <h1 class="dash-title">சுயவிவரத்தைத் திருத்து</h1>

    <div class="form-panel">
      <?php if ($saved): ?>
        <p class="flash-success">சுயவிவரம் புதுப்பிக்கப்பட்டது.</p>
      <?php endif; ?>
      <?php if ($formError): ?>
        <p class="flow-error"><?php echo htmlspecialchars($formError, ENT_QUOTES); ?></p>
      <?php endif; ?>

      <?php if ($isFreelancer && $profile): ?>
        <?php if ((int) $profile['verified_badge'] === 1): ?>
          <div class="notice-banner notice-banner--good">
            <span class="notice-icon">✓</span>
            <p>உங்கள் சுயவிவரம் சரிபார்க்கப்பட்டது — இந்த பேட்ஜ் உங்கள் பொது சுயவிவரத்திலும் தேடல் முடிவுகளிலும் தெரியும்.</p>
          </div>
        <?php else: ?>
          <div class="notice-banner">
            <span class="notice-icon">i</span>
            <p>இன்னும் சரிபார்க்கப்படவில்லை. புதிய சுயவிவரங்களை நிர்வாகி மதிப்பாய்வு செய்வார் — இதை தானாக பெற முடியாது.</p>
          </div>
        <?php endif; ?>
      <?php endif; ?>

      <form method="POST" action="profile-edit.php" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES); ?>">

        <div class="field">
          <label>சுயவிவரப் புகைப்படம்</label>
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
          <p class="field-hint">JPG, PNG, அல்லது WEBP — அதிகபட்சம் 3MB.</p>
        </div>

        <div class="field">
          <label for="full_name">முழுப் பெயர்</label>
          <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($vFullName, ENT_QUOTES); ?>" required>
        </div>

        <div class="field">
          <label for="phone">தொலைபேசி</label>
          <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($vPhone, ENT_QUOTES); ?>" placeholder="e.g. 077 123 4567">
        </div>

        <?php if ($isFreelancer && $profile): ?>

          <div class="field">
            <label for="skill_category">தலைப்பு / திறன் வகை</label>
            <input type="text" id="skill_category" name="skill_category" value="<?php echo htmlspecialchars($vSkillCategory, ENT_QUOTES); ?>" placeholder="e.g. Web Design">
          </div>

          <div class="field">
            <label for="bio">சுயவிவரம்</label>
            <textarea id="bio" name="bio" rows="6" placeholder="உங்கள் அனுபவம், நீங்கள் நிபுணத்துவம் பெற்றவை, மற்றும் அவர்கள் உங்களை ஏன் முன்பதிவு செய்ய வேண்டும் என்பதை வாடிக்கையாளர்களிடம் கூறுங்கள்..."><?php echo htmlspecialchars($vBio, ENT_QUOTES); ?></textarea>
          </div>

          <div class="field">
            <label>இருப்பிடம் <span class="field-hint">— shown on your public profile, and lets clients find you with "அருகில்" search</span></label>

            <div class="location-picker">
              <div class="location-search-row">
                <input type="text" id="locationSearchBox" placeholder="ஒரு முகவரி அல்லது பகுதியைத் தட்டச்சு செய்யவும், எ.கா. கண்டி, இலங்கை">
                <button type="button" id="locationSearchBtn" class="btn btn-outline btn-sm">தேடு</button>
                <button type="button" id="useMyLocationBtn" class="btn btn-outline btn-sm">📍 எனது இருப்பிடத்தைப் பயன்படுத்து</button>
              </div>

              <div id="locationMap" class="location-map" role="group" aria-label="உங்கள் இருப்பிடத்தை அமைக்க வரைபடத்தை கிளிக் செய்யவும்"></div>

              <p id="locationStatus" class="location-status">
                <?php if ($vLatitude !== '' && $vLongitude !== ''): ?>
                  பின் அமைக்கப்பட்டது — அதை நகர்த்த இழுக்கவும் அல்லது வரைபடத்தில் வேறு இடத்தில் கிளிக் செய்யவும்.
                <?php else: ?>
                  இதுவரை இருப்பிடம் அமைக்கப்படவில்லை. வரைபடத்தைக் கிளிக் செய்யவும், முகவரியைத் தேடவும், அல்லது உங்கள் தற்போதைய இருப்பிடத்தைப் பயன்படுத்தவும்.
                <?php endif; ?>
              </p>

              <button type="button" id="clearLocationBtn" class="btn btn-ghost btn-sm">இருப்பிடத்தை அழி</button>
            </div>

            <input type="hidden" id="latitude" name="latitude" value="<?php echo htmlspecialchars((string) $vLatitude, ENT_QUOTES); ?>">
            <input type="hidden" id="longitude" name="longitude" value="<?php echo htmlspecialchars((string) $vLongitude, ENT_QUOTES); ?>">
          </div>

        <?php endif; ?>

        <div class="form-actions">
          <button type="submit" class="btn btn-primary">மாற்றங்களைச் சேமி</button>
          <a href="<?php echo htmlspecialchars($dashboardHref, ENT_QUOTES); ?>" class="btn btn-outline">ரத்து செய்</a>
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
