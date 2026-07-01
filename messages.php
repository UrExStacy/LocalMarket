<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once 'includes/db.php';

$me = $_SESSION['user_id'];

// ── Send message (AJAX POST) ────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send'])) {
    $to         = (int)$_POST['to'];
    $listing_id = (int)($_POST['listing_id'] ?? 0) ?: null;
    $body       = trim($_POST['body'] ?? '');

    if ($to > 0 && $body !== '' && $to !== $me) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO messages (sender_id, receiver_id, listing_id, message)
                VALUES (:sender, :receiver, :listing, :message)
            ");
            $stmt->execute([
                ':sender'   => $me,
                ':receiver' => $to,
                ':listing'  => $listing_id,
                ':message'  => $body,
            ]);
            // Return the new message as JSON for the JS to render
            echo json_encode([
                'ok'         => true,
                'id'         => $pdo->lastInsertId(),
                'message'    => $body,
                'sender_id'  => $me,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (Exception $e) {
            echo json_encode(['ok' => false]);
        }
    } else {
        echo json_encode(['ok' => false]);
    }
    exit;
}

// ── Which conversation is open? ────────────────────────────────────
$open_with    = (int)($_GET['to'] ?? $_GET['with'] ?? 0);
$open_listing = (int)($_GET['listing'] ?? 0) ?: null;

// Mark messages as read
if ($open_with > 0) {
    try {
        $pdo->prepare("UPDATE messages SET is_read = 1 WHERE receiver_id = :me AND sender_id = :them")
            ->execute([':me' => $me, ':them' => $open_with]);
    } catch (Exception $e) {}
}

// ── Load conversation thread ───────────────────────────────────────
$thread = [];
$other  = null;
$listing_context = null;

if ($open_with > 0) {
    try {
        $stmt = $pdo->prepare("SELECT id, name FROM users WHERE id = :id");
        $stmt->execute([':id' => $open_with]);
        $other = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($open_listing) {
            $stmt = $pdo->prepare("SELECT id, title, price FROM listings WHERE id = :id");
            $stmt->execute([':id' => $open_listing]);
            $listing_context = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        $sql = "SELECT m.*, s.name AS sender_name
                FROM messages m
                JOIN users s ON m.sender_id = s.id
                WHERE (
                    (m.sender_id = :me  AND m.receiver_id = :them)
                 OR (m.sender_id = :them2 AND m.receiver_id = :me2)
                )";
        $params = [':me'=>$me,':them'=>$open_with,':them2'=>$open_with,':me2'=>$me];

        if ($open_listing) {
            $sql .= " AND m.listing_id = :lid";
            $params[':lid'] = $open_listing;
        }

        $sql .= " ORDER BY m.created_at ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $thread = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $thread = [];
    }
}

// ── Inbox conversations ────────────────────────────────────────────
try {
    $inbox_stmt = $pdo->prepare("
        SELECT
            CASE WHEN m.sender_id = :me THEN m.receiver_id ELSE m.sender_id END AS other_id,
            u.name AS other_name,
            m.message AS last_message,
            m.created_at AS last_at,
            m.listing_id,
            l.title AS listing_title,
            SUM(CASE WHEN m.receiver_id = :me2 AND m.is_read = 0 THEN 1 ELSE 0 END) AS unread
        FROM messages m
        JOIN users u ON u.id = CASE WHEN m.sender_id = :me3 THEN m.receiver_id ELSE m.sender_id END
        LEFT JOIN listings l ON l.id = m.listing_id
        WHERE m.sender_id = :me4 OR m.receiver_id = :me5
        GROUP BY other_id, m.listing_id
        ORDER BY last_at DESC
    ");
    $inbox_stmt->execute([':me'=>$me,':me2'=>$me,':me3'=>$me,':me4'=>$me,':me5'=>$me]);
    $conversations = $inbox_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $conversations = [];
}

$total_unread = array_sum(array_column($conversations, 'unread'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Messages – LocalMarket</title>
  <link rel="stylesheet" href="css/style.css"/>
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<main>
  <div class="container">
    <div class="messages-layout">

      <!-- ── Inbox ─────────────────────────────────── -->
      <div class="inbox-panel">
        <div class="inbox-header">
          <h2>Messages
            <?php if ($total_unread > 0): ?>
              <span class="unread-badge" id="inbox-unread-badge"><?= $total_unread ?></span>
            <?php else: ?>
              <span class="unread-badge" id="inbox-unread-badge" style="display:none">0</span>
            <?php endif; ?>
          </h2>
        </div>

        <?php if (empty($conversations)): ?>
          <p class="text-muted" style="padding:20px;font-size:14px;">
            No messages yet. Contact a seller from a listing.
          </p>
        <?php else: ?>
          <ul class="conversation-list" id="conversation-list">
            <?php foreach ($conversations as $conv):
              $is_active = ($conv['other_id'] == $open_with);
              $url = 'messages.php?with=' . $conv['other_id'];
              if ($conv['listing_id']) $url .= '&listing=' . $conv['listing_id'];
            ?>
              <li class="conversation-item <?= $is_active ? 'active' : '' ?>"
                  data-other="<?= $conv['other_id'] ?>">
                <a href="<?= $url ?>">
                  <div class="conv-top">
                    <span class="conv-name"><?= htmlspecialchars($conv['other_name']) ?></span>
                    <span class="conv-time" data-ts="<?= $conv['last_at'] ?>">
                      <?= date('d M', strtotime($conv['last_at'])) ?>
                    </span>
                  </div>
                  <?php if ($conv['listing_title']): ?>
                    <div class="conv-listing">re: <?= htmlspecialchars($conv['listing_title']) ?></div>
                  <?php endif; ?>
                  <div class="conv-preview">
                    <?= htmlspecialchars(mb_strimwidth($conv['last_message'], 0, 50, '…')) ?>
                  </div>
                  <?php if ($conv['unread'] > 0): ?>
                    <span class="unread-dot"><?= $conv['unread'] ?></span>
                  <?php endif; ?>
                </a>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>

      <!-- ── Chat ──────────────────────────────────── -->
      <div class="chat-panel">
        <?php if ($open_with > 0 && $other): ?>

          <div class="chat-header">
            <div class="chat-with-name"><?= htmlspecialchars($other['name']) ?></div>
            <?php if ($listing_context): ?>
              <a href="product.php?id=<?= $listing_context['id'] ?>" class="chat-listing-link">
                re: <?= htmlspecialchars($listing_context['title']) ?> — R<?= number_format($listing_context['price'], 2) ?>
              </a>
            <?php endif; ?>
            <div class="chat-status" id="chat-status">● Online</div>
          </div>

          <div class="chat-messages" id="chat-messages">
            <?php
              $prev_date = '';
              foreach ($thread as $msg):
                $msg_date = date('d M Y', strtotime($msg['created_at']));
                $is_mine  = ($msg['sender_id'] == $me);
            ?>
              <?php if ($msg_date !== $prev_date): ?>
                <div class="date-divider"><?= $msg_date ?></div>
                <?php $prev_date = $msg_date; ?>
              <?php endif; ?>
              <div class="message-row <?= $is_mine ? 'mine' : 'theirs' ?>" data-id="<?= $msg['id'] ?>">
                <div class="message-bubble">
                  <?= nl2br(htmlspecialchars($msg['message'])) ?>
                  <span class="message-time"><?= date('H:i', strtotime($msg['created_at'])) ?></span>
                </div>
              </div>
            <?php endforeach; ?>
          </div>

          <form class="chat-form" id="chat-form">
            <input type="hidden" id="to-id" value="<?= $open_with ?>"/>
            <input type="hidden" id="listing-id" value="<?= $open_listing ?? '' ?>"/>
            <textarea id="chat-input" class="chat-input"
                      placeholder="Type a message… (Enter to send)"
                      rows="1"
                      required></textarea>
            <button type="submit" class="btn btn-primary chat-send-btn">Send</button>
          </form>

        <?php else: ?>
          <div class="chat-empty">
            <div style="font-size:2.5rem;">💬</div>
            <h3 style="margin:12px 0 6px;">Your Messages</h3>
            <p class="text-muted">Select a conversation or contact a seller from a listing.</p>
          </div>
        <?php endif; ?>
      </div>

    </div>
  </div>
</main>

<?php include 'includes/footer.php'; ?>

<script>
const ME          = <?= $me ?>;
const OPEN_WITH   = <?= $open_with ?>;
const OPEN_LISTING = <?= $open_listing ?? 'null' ?>;

function scrollToBottom() {
    const box = document.getElementById('chat-messages');
    if (box) box.scrollTop = box.scrollHeight;
}
scrollToBottom();

function renderBubble(msg) {
    const isMine = (msg.sender_id == ME);
    const time   = new Date(msg.created_at).toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'});
    const row    = document.createElement('div');
    row.className = 'message-row ' + (isMine ? 'mine' : 'theirs');
    row.dataset.id = msg.id;
    row.innerHTML = `
        <div class="message-bubble">
            ${escapeHtml(msg.message).replace(/\n/g, '<br>')}
            <span class="message-time">${time}</span>
        </div>`;
    return row;
}

function escapeHtml(t) {
    return t.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
             .replace(/"/g,'&quot;').replace(/'/g,'&#039;');
}

const form  = document.getElementById('chat-form');
const input = document.getElementById('chat-input');

if (form) {
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        const body = input.value.trim();
        if (!body) return;

        const fd = new FormData();
        fd.append('send', '1');
        fd.append('to', OPEN_WITH);
        fd.append('body', body);
        if (OPEN_LISTING) fd.append('listing_id', OPEN_LISTING);

        input.value = '';
        input.style.height = 'auto';

        const res  = await fetch('messages.php', { method: 'POST', body: fd });
        const data = await res.json();

        if (data.ok) {
            const box = document.getElementById('chat-messages');
            box.appendChild(renderBubble(data));
            scrollToBottom();
            updateInboxPreview(OPEN_WITH, body);
        }
    });

    input.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            form.dispatchEvent(new Event('submit'));
        }
    });

    input.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 120) + 'px';
    });
}

function updateInboxPreview(otherId, text) {
    const item = document.querySelector(`.conversation-item[data-other="${otherId}"] .conv-preview`);
    if (item) item.textContent = text.length > 50 ? text.slice(0, 50) + '…' : text;
    const timeEl = document.querySelector(`.conversation-item[data-other="${otherId}"] .conv-time`);
    if (timeEl) {
        const now = new Date();
        timeEl.textContent = now.toLocaleDateString('en-GB', {day:'2-digit', month:'short'});
    }
}

if (OPEN_WITH > 0 && typeof EventSource !== 'undefined') {
    let lastId = 0;

    document.querySelectorAll('.message-row[data-id]').forEach(el => {
        const id = parseInt(el.dataset.id);
        if (id > lastId) lastId = id;
    });

    function pollNewMessages() {
        let url = `sse.php?with=${OPEN_WITH}&last_id=${lastId}`;
        if (OPEN_LISTING) url += `&listing=${OPEN_LISTING}`;

        const es = new EventSource(url);

        es.addEventListener('message', function(e) {
            const msgs = JSON.parse(e.data);
            if (!msgs.length) return;

            const box = document.getElementById('chat-messages');
            const wasAtBottom = box.scrollHeight - box.scrollTop - box.clientHeight < 60;

            msgs.forEach(msg => {
                if (document.querySelector(`.message-row[data-id="${msg.id}"]`)) return;
                lastId = Math.max(lastId, msg.id);
                box.appendChild(renderBubble(msg));
                updateInboxPreview(OPEN_WITH, msg.message);
            });

            if (wasAtBottom) scrollToBottom();
        });

        es.addEventListener('ping', function() { /* keep-alive */ });

        es.onerror = function() {
            es.close();
            setTimeout(pollNewMessages, 3000);
        };
    }

    pollNewMessages();
}

if (typeof EventSource !== 'undefined') {
    function pollUnread() {
        const es = new EventSource('sse_unread.php');

        es.addEventListener('unread', function(e) {
            const count = parseInt(e.data);
            const badge = document.getElementById('nav-unread-badge');
            if (!badge) return;
            if (count > 0) {
                badge.textContent = count;
                badge.style.display = 'inline-block';
            } else {
                badge.style.display = 'none';
            }
        });

        es.onerror = function() {
            es.close();
            setTimeout(pollUnread, 5000);
        };
    }
    pollUnread();
}
</script>
<script src="js/script.js"></script>
</body>
</html>