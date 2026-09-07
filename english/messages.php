<?php
/**
 * SkillBridge.lk — Messages (English)
 * ---------------------------------------------------------------------
 * No ?with= param  -> inbox: list of everyone you've exchanged messages
 *                     with, most recent first.
 * ?with=<user_id>  -> open thread with that person; posting the form
 *                     sends a new message and reloads the thread.
 * ---------------------------------------------------------------------
 */
require_once __DIR__ . '/session.php';
require_login();

$myId = (int) $_SESSION['user_id'];
$withId = (int) ($_GET['with'] ?? 0);

$dashboardHref = match ($_SESSION['role'] ?? '') {
    'freelancer' => 'dashboard.php',
    'admin'      => 'admin-dashboard.php',
    default      => 'client-dashboard.php',
};

$dbError = false;
$dbErrorDetail = '';
$formError = null;

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

// ---- Send a new message ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $withId > 0) {
    $submittedToken = $_POST['csrf_token'] ?? '';
    if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $submittedToken)) {
        $formError = 'Your session expired — please try again.';
    } else {
        $content = trim($_POST['content'] ?? '');
        if ($content === '') {
            $formError = 'Type a message before sending.';
        } else {
            try {
                $insert = $pdo->prepare("
                    INSERT INTO messages (sender_id, receiver_id, content)
                    VALUES (:sender_id, :receiver_id, :content)
                ");
                $insert->execute([
                    'sender_id'   => $myId,
                    'receiver_id' => $withId,
                    'content'     => $content,
                ]);
                header('Location: messages.php?with=' . $withId . '#bottom');
                exit;
            } catch (PDOException $e) {
                error_log('SkillBridge messages.php send error: ' . $e->getMessage());
                $formError = 'Something went wrong sending that. Please try again.';
            }
        }
    }
}

$otherPerson = null;
$thread = [];
$inbox = [];
$presence = ['online' => false, 'label' => ''];

// ---- Online / last-seen threshold ----
// A person counts as "online" if their last_seen is within this many
// seconds — matches how the poll refreshes (see messages-poll.php).
const ONLINE_WINDOW_SECONDS = 60;

/**
 * Turn a users.last_seen timestamp into an ['online' => bool, 'label' => string] pair.
 */
function formatPresence(?string $lastSeen): array
{
    if (!$lastSeen) {
        return ['online' => false, 'label' => 'Offline'];
    }

    $secondsAgo = time() - strtotime($lastSeen);

    if ($secondsAgo <= ONLINE_WINDOW_SECONDS) {
        return ['online' => true, 'label' => 'Online'];
    }

    $minutes = (int) floor($secondsAgo / 60);
    if ($minutes < 60) {
        $label = 'Last seen ' . $minutes . ' min' . ($minutes === 1 ? '' : 's') . ' ago';
        return ['online' => false, 'label' => $label];
    }

    $hours = (int) floor($minutes / 60);
    if ($hours < 24) {
        $label = 'Last seen ' . $hours . ' hr' . ($hours === 1 ? '' : 's') . ' ago';
        return ['online' => false, 'label' => $label];
    }

    $label = 'Last seen ' . date('d M, H:i', strtotime($lastSeen));
    return ['online' => false, 'label' => $label];
}

try {
    if ($withId > 0) {
        // ---- Who am I talking to? ----
        $stmt = $pdo->prepare('SELECT user_id, full_name, last_seen FROM users WHERE user_id = :user_id LIMIT 1');
        $stmt->execute(['user_id' => $withId]);
        $otherPerson = $stmt->fetch() ?: null;

        if ($otherPerson) {
            $presence = formatPresence($otherPerson['last_seen'] ?? null);

            // ---- Mark their messages to me as read ----
            $markRead = $pdo->prepare("
                UPDATE messages SET is_read = 1
                WHERE receiver_id = :me AND sender_id = :them AND is_read = 0
            ");
            $markRead->execute(['me' => $myId, 'them' => $withId]);

            // ---- Full thread, oldest first ----
            $stmt = $pdo->prepare("
                SELECT message_id, sender_id, content, sent_at
                FROM messages
                WHERE (sender_id = :me1 AND receiver_id = :them1)
                   OR (sender_id = :them2 AND receiver_id = :me2)
                ORDER BY sent_at ASC
            ");
            $stmt->execute([
                'me1' => $myId, 'them1' => $withId,
                'them2' => $withId, 'me2' => $myId,
            ]);
            $thread = $stmt->fetchAll();
        }
    } else {
        // ---- Inbox: distinct conversation partners, most recent first ----
        // Kept as simple separate queries (not one big correlated-subquery
        // GROUP BY) — easier to reason about and less likely to trip
        // MySQL's strict ONLY_FULL_GROUP_BY mode.
        $stmt = $pdo->prepare("
            SELECT DISTINCT IF(sender_id = :me1, receiver_id, sender_id) AS partner_id
            FROM messages
            WHERE sender_id = :me2 OR receiver_id = :me3
        ");
        $stmt->execute(['me1' => $myId, 'me2' => $myId, 'me3' => $myId]);
        $partnerIds = array_column($stmt->fetchAll(), 'partner_id');

        foreach ($partnerIds as $partnerId) {
            $partnerId = (int) $partnerId;

            $userStmt = $pdo->prepare('SELECT full_name, last_seen FROM users WHERE user_id = :user_id LIMIT 1');
            $userStmt->execute(['user_id' => $partnerId]);
            $partnerUser = $userStmt->fetch();
            if (!$partnerUser) {
                continue;
            }

            $lastStmt = $pdo->prepare("
                SELECT content, sent_at FROM messages
                WHERE (sender_id = :me1 AND receiver_id = :them1)
                   OR (sender_id = :them2 AND receiver_id = :me2)
                ORDER BY sent_at DESC LIMIT 1
            ");
            $lastStmt->execute(['me1' => $myId, 'them1' => $partnerId, 'them2' => $partnerId, 'me2' => $myId]);
            $last = $lastStmt->fetch();

            $unreadStmt = $pdo->prepare('
                SELECT COUNT(*) AS unread FROM messages
                WHERE receiver_id = :me AND sender_id = :them AND is_read = 0
            ');
            $unreadStmt->execute(['me' => $myId, 'them' => $partnerId]);

            $inbox[] = [
                'user_id'       => $partnerId,
                'full_name'     => $partnerUser['full_name'],
                'last_message'  => $last['content'] ?? '',
                'last_sent_at'  => $last['sent_at'] ?? '1970-01-01',
                'unread_count'  => (int) $unreadStmt->fetch()['unread'],
                'presence'      => formatPresence($partnerUser['last_seen'] ?? null),
            ];
        }

        usort($inbox, fn($a, $b) => strtotime($b['last_sent_at']) <=> strtotime($a['last_sent_at']));
    }
} catch (PDOException $e) {
    error_log('SkillBridge messages.php load error: ' . $e->getMessage());
    $dbError = true;
    $dbErrorDetail = $e->getMessage();
}

function initials(string $name): string
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
<title>Messages — SkillBridge.lk</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Pirata+One&family=Anton&family=Space+Grotesk:wght@400;500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

<link rel="stylesheet" href="assets/css/messages.css">
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
      <a href="search.php">Search</a>
    </nav>
    <span class="nav-hello">Hi, <?php echo htmlspecialchars(explode(' ', $_SESSION['full_name'])[0], ENT_QUOTES); ?></span>
  </div>
</header>

<main class="messages-page">

  <?php if ($dbError): ?>
    <div class="empty-state">
      <div class="empty-glyph">!</div>
      <h3>Couldn't load messages</h3>
      <p>Something went wrong reaching the database. Please try again shortly.</p>
      <?php if (isset($_GET['debug'])): ?>
        <p class="debug-detail"><?php echo htmlspecialchars($dbErrorDetail, ENT_QUOTES); ?></p>
      <?php endif; ?>
    </div>

  <?php elseif ($withId > 0 && !$otherPerson): ?>
    <div class="empty-state">
      <div class="empty-glyph">◎</div>
      <h3>Person not found</h3>
      <a href="messages.php" class="btn btn-outline">Back to Messages</a>
    </div>

  <?php elseif ($withId > 0): ?>

    <!-- =========================== THREAD VIEW =========================== -->
    <div class="thread-shell">
      <div class="thread-header">
        <a href="messages.php" class="thread-back">← Inbox</a>
        <div class="thread-who">
          <span class="thread-avatar">
            <?php echo htmlspecialchars(initials($otherPerson['full_name']), ENT_QUOTES); ?>
            <span class="status-dot <?php echo $presence['online'] ? 'is-online' : ''; ?>" id="statusDot"></span>
          </span>
          <span class="thread-who-text">
            <h2><?php echo htmlspecialchars($otherPerson['full_name'], ENT_QUOTES); ?></h2>
            <span class="thread-status <?php echo $presence['online'] ? 'is-online' : ''; ?>" id="threadStatus"><?php echo htmlspecialchars($presence['label'], ENT_QUOTES); ?></span>
          </span>
        </div>
      </div>

      <div class="thread-messages" id="threadMessages" data-with-id="<?php echo $withId; ?>" data-my-id="<?php echo $myId; ?>" data-last-id="<?php echo !empty($thread) ? (int) $thread[count($thread) - 1]['message_id'] : 0; ?>">
        <?php if (empty($thread)): ?>
          <p class="thread-empty">Say hello — this is the start of your conversation.</p>
        <?php else: ?>
          <?php foreach ($thread as $msg): ?>
            <div class="bubble-row <?php echo (int) $msg['sender_id'] === $myId ? 'is-mine' : 'is-theirs'; ?>" data-message-id="<?php echo (int) $msg['message_id']; ?>">
              <div class="bubble">
                <?php echo nl2br(htmlspecialchars($msg['content'], ENT_QUOTES)); ?>
                <span class="bubble-time"><?php echo htmlspecialchars(date('d M, H:i', strtotime($msg['sent_at'])), ENT_QUOTES); ?></span>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
        <div id="bottom"></div>
      </div>

      <?php if ($formError): ?>
        <p class="flow-error"><?php echo htmlspecialchars($formError, ENT_QUOTES); ?></p>
      <?php endif; ?>

      <form method="POST" action="messages.php?with=<?php echo $withId; ?>" class="thread-composer" id="composerForm" data-with-id="<?php echo $withId; ?>">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES); ?>">
        <input type="text" name="content" placeholder="Type a message..." autocomplete="off" required>
        <button type="submit" class="btn btn-primary">Send</button>
      </form>
    </div>

  <?php else: ?>

    <!-- =========================== INBOX =========================== -->
    <p class="eyebrow">Conversations</p>
    <h1 class="messages-title">Messages</h1>

    <?php if (empty($inbox)): ?>
      <div class="empty-state">
        <div class="empty-glyph">◎</div>
        <h3>No conversations yet</h3>
        <p>Message a freelancer from their profile to start a conversation.</p>
        <a href="search.php" class="btn btn-outline">Find a Freelancer</a>
      </div>
    <?php else: ?>
      <div class="inbox-list">
        <?php foreach ($inbox as $convo): ?>
          <a href="messages.php?with=<?php echo (int) $convo['user_id']; ?>" class="inbox-row">
            <span class="inbox-avatar">
              <?php echo htmlspecialchars(initials($convo['full_name']), ENT_QUOTES); ?>
              <?php if ($convo['presence']['online']): ?><span class="status-dot is-online"></span><?php endif; ?>
            </span>
            <span class="inbox-info">
              <span class="inbox-name"><?php echo htmlspecialchars($convo['full_name'], ENT_QUOTES); ?></span>
              <span class="inbox-preview"><?php echo htmlspecialchars(mb_strimwidth($convo['last_message'], 0, 60, '…'), ENT_QUOTES); ?></span>
            </span>
            <?php if ((int) $convo['unread_count'] > 0): ?>
              <span class="inbox-unread"><?php echo (int) $convo['unread_count']; ?></span>
            <?php endif; ?>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

  <?php endif; ?>

</main>

<script src="assets/js/messages.js"></script>
</body>
</html>