<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once 'includes/db.php';

$me  = $_SESSION['user_id'];
$tab = $_GET['tab'] ?? 'purchases';

try {
    $purchases = $pdo->prepare("
        SELECT o.*, l.title, l.image, l.category,
               u.name AS seller_name
        FROM orders o
        JOIN listings l ON o.listing_id = l.id
        JOIN users    u ON o.seller_id  = u.id
        WHERE o.buyer_id = :me
        ORDER BY o.created_at DESC
    ");
    $purchases->execute([':me' => $me]);
    $purchases = $purchases->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { $purchases = []; }

try {
    $sales = $pdo->prepare("
        SELECT o.*, l.title, l.image, l.category,
               u.name AS buyer_name
        FROM orders o
        JOIN listings l ON o.listing_id = l.id
        JOIN users    u ON o.buyer_id   = u.id
        WHERE o.seller_id = :me
        ORDER BY o.created_at DESC
    ");
    $sales->execute([':me' => $me]);
    $sales = $sales->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { $sales = []; }

$status_badge = [
    'pending'   => 'badge-gray',
    'completed' => 'badge-yellow',
    'cancelled' => 'badge-gray',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>My Orders – LocalMarket</title>
  <link rel="stylesheet" href="css/style.css"/>
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<main>
  <div class="container">

    <div style="margin:30px 0 20px;">
      <h1>My Orders</h1>
      <p class="text-muted">Track your purchases and sales</p>
    </div>

    <div class="admin-tabs">
      <button class="admin-tab <?= $tab === 'purchases' ? 'active' : '' ?>"
              onclick="showTab('purchases', this)">
        Purchases (<?= count($purchases) ?>)
      </button>
      <button class="admin-tab <?= $tab === 'sales' ? 'active' : '' ?>"
              onclick="showTab('sales', this)">
        Sales (<?= count($sales) ?>)
      </button>
    </div>

    <div id="tab-purchases" class="tab-content"
         style="<?= $tab !== 'purchases' ? 'display:none;' : '' ?>">
      <?php if (empty($purchases)): ?>
        <div class="alert alert-info" style="margin:20px;">
          You haven't purchased anything yet.
          <a href="index.php">Browse listings</a>
        </div>
      <?php else: ?>
        <table class="admin-table">
          <thead>
            <tr>
              <th>#</th>
              <th>Item</th>
              <th>Seller</th>
              <th>Amount</th>
              <th>Status</th>
              <th>Date</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($purchases as $o): ?>
              <tr>
                <td class="text-muted"><?= $o['id'] ?></td>
                <td>
                  <a href="product.php?id=<?= $o['listing_id'] ?>">
                    <?= htmlspecialchars($o['title']) ?>
                  </a>
                  <div class="text-muted" style="font-size:12px;">
                    <?= htmlspecialchars($o['category']) ?>
                  </div>
                </td>
                <td><?= htmlspecialchars($o['seller_name']) ?></td>
                <td style="font-weight:600;">R<?= number_format($o['amount'], 2) ?></td>
                <td>
                  <span class="badge <?= $status_badge[$o['status']] ?? 'badge-gray' ?>">
                    <?= ucfirst($o['status']) ?>
                  </span>
                </td>
                <td class="text-muted" style="font-size:13px;">
                  <?= date('d M Y', strtotime($o['created_at'])) ?>
                </td>
                <td>
                  <a href="messages.php?with=<?= $o['seller_id'] ?>&listing=<?= $o['listing_id'] ?>"
                     class="btn btn-outline btn-sm">Message Seller</a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>

    <div id="tab-sales" class="tab-content"
         style="<?= $tab !== 'sales' ? 'display:none;' : '' ?>">
      <?php if (empty($sales)): ?>
        <div class="alert alert-info" style="margin:20px;">
          No sales yet. <a href="create-listing.php">Post a listing</a> to start selling.
        </div>
      <?php else: ?>
        <table class="admin-table">
          <thead>
            <tr>
              <th>#</th>
              <th>Item</th>
              <th>Buyer</th>
              <th>Amount</th>
              <th>Status</th>
              <th>Date</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($sales as $o): ?>
              <tr>
                <td class="text-muted"><?= $o['id'] ?></td>
                <td>
                  <a href="product.php?id=<?= $o['listing_id'] ?>">
                    <?= htmlspecialchars($o['title']) ?>
                  </a>
                  <div class="text-muted" style="font-size:12px;">
                    <?= htmlspecialchars($o['category']) ?>
                  </div>
                </td>
                <td><?= htmlspecialchars($o['buyer_name']) ?></td>
                <td style="font-weight:600;">R<?= number_format($o['amount'], 2) ?></td>
                <td>
                  <span class="badge <?= $status_badge[$o['status']] ?? 'badge-gray' ?>">
                    <?= ucfirst($o['status']) ?>
                  </span>
                </td>
                <td class="text-muted" style="font-size:13px;">
                  <?= date('d M Y', strtotime($o['created_at'])) ?>
                </td>
                <td>
                  <a href="messages.php?with=<?= $o['buyer_id'] ?>&listing=<?= $o['listing_id'] ?>"
                     class="btn btn-outline btn-sm">Message Buyer</a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>

  </div>
</main>

<?php include 'includes/footer.php'; ?>
<script>
function showTab(name, btn) {
    document.querySelectorAll('.tab-content').forEach(t => t.style.display = 'none');
    document.querySelectorAll('.admin-tab').forEach(t => t.classList.remove('active'));
    document.getElementById('tab-' + name).style.display = 'block';
    btn.classList.add('active');
}
</script>
<script src="js/script.js"></script>
</body>
</html>