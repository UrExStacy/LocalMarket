<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_listing'])) {
    $lid = (int)$_POST['listing_id'];
    try {
        $pdo->prepare("DELETE FROM listings WHERE id = :id")->execute([':id' => $lid]);
        $success = 'Listing deleted.';
    } catch (Exception $e) { $error = 'Could not delete listing.'; }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $uid = (int)$_POST['user_id'];
    try {
        $check = $pdo->prepare("SELECT role FROM users WHERE id = :id");
        $check->execute([':id' => $uid]);
        $target = $check->fetch(PDO::FETCH_ASSOC);
        if (!$target)                      { $error = 'User not found.'; }
        elseif ($target['role'] === 'admin') { $error = 'Cannot delete another admin.'; }
        else {
            $pdo->prepare("DELETE FROM listings WHERE user_id = :id")->execute([':id' => $uid]);
            $pdo->prepare("DELETE FROM messages WHERE sender_id = :id OR receiver_id = :id2")
                ->execute([':id' => $uid, ':id2' => $uid]);
            $pdo->prepare("DELETE FROM users WHERE id = :id")->execute([':id' => $uid]);
            $success = 'User and their data deleted.';
        }
    } catch (Exception $e) { $error = 'Could not delete user.'; }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_report'])) {
    $rid    = (int)$_POST['report_id'];
    $status = $_POST['report_status'] ?? 'open';
    if (in_array($status, ['open', 'reviewed', 'dismissed'])) {
        try {
            $pdo->prepare("UPDATE reports SET status = :s WHERE id = :id")
                ->execute([':s' => $status, ':id' => $rid]);
            $success = 'Report updated.';
        } catch (Exception $e) { $error = 'Could not update report.'; }
    }
}

function safe_count($pdo, $sql) {
    try { return (int)$pdo->query($sql)->fetchColumn(); }
    catch (Exception $e) { return 0; }
}
$user_count    = safe_count($pdo, "SELECT COUNT(*) FROM users");
$listing_count = safe_count($pdo, "SELECT COUNT(*) FROM listings");
$message_count = safe_count($pdo, "SELECT COUNT(*) FROM messages");
$order_count   = safe_count($pdo, "SELECT COUNT(*) FROM orders");
$open_reports  = safe_count($pdo, "SELECT COUNT(*) FROM reports WHERE status = 'open'");

try {
    $listings = $pdo->query("
        SELECT l.id, l.title, l.price, l.category, l.status, l.created_at,
               u.name AS seller_name
        FROM listings l JOIN users u ON l.user_id = u.id
        ORDER BY l.created_at DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { $listings = []; }

try {
    $users = $pdo->query("
        SELECT id, name, email, role, created_at
        FROM users WHERE role != 'admin'
        ORDER BY created_at DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { $users = []; }

try {
    $orders = $pdo->query("
        SELECT o.*, l.title AS listing_title,
               b.name AS buyer_name, s.name AS seller_name
        FROM orders o
        JOIN listings l ON o.listing_id = l.id
        JOIN users b    ON o.buyer_id   = b.id
        JOIN users s    ON o.seller_id  = s.id
        ORDER BY o.created_at DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { $orders = []; }

try {
    $reports = $pdo->query("
        SELECT r.*, u.name AS reporter_name,
               l.title AS listing_title,
               ru.name AS reported_user_name
        FROM reports r
        JOIN users u ON r.reporter_id = u.id
        LEFT JOIN listings l  ON r.reported_listing_id = l.id
        LEFT JOIN users ru    ON r.reported_user_id    = ru.id
        ORDER BY r.created_at DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { $reports = []; }

$status_badge = ['pending'=>'badge-gray','completed'=>'badge-yellow','cancelled'=>'badge-gray'];
$report_badge = ['open'=>'badge-yellow','reviewed'=>'badge-gray','dismissed'=>'badge-gray'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Admin Dashboard – LocalMarket</title>
  <link rel="stylesheet" href="../css/style.css"/>
</head>
<body>

<?php include '../includes/navbar.php'; ?>

<main>
  <div class="container">

    <div class="admin-header">
      <div>
        <h1>Admin Dashboard</h1>
        <p class="text-muted">Manage listings, users, orders and reports</p>
      </div>
      <a href="../index.php" class="btn btn-outline btn-sm">← Back to Site</a>
    </div>

    <?php if ($success !== ''): ?>
      <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
      <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="admin-stats" style="grid-template-columns:repeat(5,1fr);">
      <div class="stat-card">
        <div class="stat-number"><?= $user_count ?></div>
        <div class="stat-label">Users</div>
      </div>
      <div class="stat-card">
        <div class="stat-number"><?= $listing_count ?></div>
        <div class="stat-label">Listings</div>
      </div>
      <div class="stat-card">
        <div class="stat-number"><?= $message_count ?></div>
        <div class="stat-label">Messages</div>
      </div>
      <div class="stat-card">
        <div class="stat-number"><?= $order_count ?></div>
        <div class="stat-label">Orders</div>
      </div>
      <div class="stat-card">
        <div class="stat-number <?= $open_reports > 0 ? 'text-yellow' : '' ?>"><?= $open_reports ?></div>
        <div class="stat-label">Open Reports</div>
      </div>
    </div>

    <div class="admin-tabs">
      <button class="admin-tab active" onclick="showTab('listings',this)">Listings (<?= count($listings) ?>)</button>
      <button class="admin-tab"        onclick="showTab('users',this)">Users (<?= count($users) ?>)</button>
      <button class="admin-tab"        onclick="showTab('orders',this)">Orders (<?= count($orders) ?>)</button>
      <button class="admin-tab"        onclick="showTab('reports',this)">
        Reports
        <?php if ($open_reports > 0): ?>
          <span class="unread-badge"><?= $open_reports ?></span>
        <?php endif; ?>
      </button>
    </div>

    <div id="tab-listings" class="tab-content">
      <?php if (empty($listings)): ?>
        <div class="alert alert-info">No listings yet.</div>
      <?php else: ?>
        <table class="admin-table">
          <thead><tr><th>#</th><th>Title</th><th>Category</th><th>Price</th><th>Seller</th><th>Status</th><th>Date</th><th>Action</th></tr></thead>
          <tbody>
            <?php foreach ($listings as $item): ?>
              <tr>
                <td class="text-muted"><?= $item['id'] ?></td>
                <td><a href="../product.php?id=<?= $item['id'] ?>" target="_blank"><?= htmlspecialchars($item['title']) ?></a></td>
                <td><?= htmlspecialchars($item['category']) ?></td>
                <td>R<?= number_format($item['price'], 2) ?></td>
                <td><?= htmlspecialchars($item['seller_name']) ?></td>
                <td><span class="badge <?= $item['status'] === 'active' ? 'badge-yellow' : 'badge-gray' ?>"><?= ucfirst($item['status']) ?></span></td>
                <td class="text-muted" style="font-size:13px;"><?= date('d M Y', strtotime($item['created_at'])) ?></td>
                <td>
                  <form method="POST" onsubmit="return confirm('Delete this listing?')">
                    <input type="hidden" name="listing_id" value="<?= $item['id'] ?>"/>
                    <button type="submit" name="delete_listing" class="btn btn-danger btn-sm">Delete</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>

    <div id="tab-users" class="tab-content" style="display:none;">
      <?php if (empty($users)): ?>
        <div class="alert alert-info">No users yet.</div>
      <?php else: ?>
        <table class="admin-table">
          <thead><tr><th>#</th><th>Name</th><th>Email</th><th>Role</th><th>Joined</th><th>Action</th></tr></thead>
          <tbody>
            <?php foreach ($users as $u): ?>
              <tr>
                <td class="text-muted"><?= $u['id'] ?></td>
                <td><?= htmlspecialchars($u['name']) ?></td>
                <td><?= htmlspecialchars($u['email']) ?></td>
                <td><span class="badge badge-gray"><?= ucfirst($u['role']) ?></span></td>
                <td class="text-muted" style="font-size:13px;"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                <td>
                  <form method="POST" onsubmit="return confirm('Delete this user and all their data?')">
                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>"/>
                    <button type="submit" name="delete_user" class="btn btn-danger btn-sm">Delete</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>

    <div id="tab-orders" class="tab-content" style="display:none;">
      <?php if (empty($orders)): ?>
        <div class="alert alert-info">No orders yet.</div>
      <?php else: ?>
        <table class="admin-table">
          <thead><tr><th>#</th><th>Listing</th><th>Buyer</th><th>Seller</th><th>Amount</th><th>Status</th><th>Date</th></tr></thead>
          <tbody>
            <?php foreach ($orders as $o): ?>
              <tr>
                <td class="text-muted"><?= $o['id'] ?></td>
                <td><a href="../product.php?id=<?= $o['listing_id'] ?>" target="_blank"><?= htmlspecialchars($o['listing_title']) ?></a></td>
                <td><?= htmlspecialchars($o['buyer_name']) ?></td>
                <td><?= htmlspecialchars($o['seller_name']) ?></td>
                <td>R<?= number_format($o['amount'], 2) ?></td>
                <td><span class="badge <?= $status_badge[$o['status']] ?? 'badge-gray' ?>"><?= ucfirst($o['status']) ?></span></td>
                <td class="text-muted" style="font-size:13px;"><?= date('d M Y', strtotime($o['created_at'])) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>

    <div id="tab-reports" class="tab-content" style="display:none;">
      <?php if (empty($reports)): ?>
        <div class="alert alert-info">No reports submitted yet.</div>
      <?php else: ?>
        <table class="admin-table">
          <thead><tr><th>#</th><th>Reported</th><th>Reason</th><th>Reporter</th><th>Date</th><th>Status</th><th>Action</th></tr></thead>
          <tbody>
            <?php foreach ($reports as $r): ?>
              <tr>
                <td class="text-muted"><?= $r['id'] ?></td>
                <td>
                  <?php if ($r['listing_title']): ?>
                    <a href="../product.php?id=<?= $r['reported_listing_id'] ?>" target="_blank">
                      📦 <?= htmlspecialchars($r['listing_title']) ?>
                    </a>
                  <?php elseif ($r['reported_user_name']): ?>
                    👤 <?= htmlspecialchars($r['reported_user_name']) ?>
                  <?php else: ?>
                    <span class="text-muted">Deleted</span>
                  <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($r['reason']) ?></td>
                <td><?= htmlspecialchars($r['reporter_name']) ?></td>
                <td class="text-muted" style="font-size:13px;"><?= date('d M Y', strtotime($r['created_at'])) ?></td>
                <td>
                  <span class="badge <?= $report_badge[$r['status']] ?? 'badge-gray' ?>">
                    <?= ucfirst($r['status']) ?>
                  </span>
                </td>
                <td>
                  <form method="POST" style="display:flex;gap:4px;">
                    <input type="hidden" name="report_id" value="<?= $r['id'] ?>"/>
                    <select name="report_status" class="form-control" style="padding:4px 8px;font-size:13px;width:auto;">
                      <option value="open"      <?= $r['status']==='open'      ?'selected':'' ?>>Open</option>
                      <option value="reviewed"  <?= $r['status']==='reviewed'  ?'selected':'' ?>>Reviewed</option>
                      <option value="dismissed" <?= $r['status']==='dismissed' ?'selected':'' ?>>Dismissed</option>
                    </select>
                    <button type="submit" name="update_report" class="btn btn-outline btn-sm">Save</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>

  </div>
</main>

<?php include '../includes/footer.php'; ?>
<script>
function showTab(name, btn) {
    document.querySelectorAll('.tab-content').forEach(t => t.style.display = 'none');
    document.querySelectorAll('.admin-tab').forEach(t => t.classList.remove('active'));
    document.getElementById('tab-' + name).style.display = 'block';
    btn.classList.add('active');
}
</script>
<script src="../js/script.js"></script>
</body>
</html>