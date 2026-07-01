<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once 'includes/db.php';

$me      = $_SESSION['user_id'];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_status'])) {
    $lid        = (int)$_POST['listing_id'];
    $new_status = $_POST['new_status'] ?? 'active';
    if (in_array($new_status, ['active', 'sold'])) {
        try {
            $pdo->prepare("UPDATE listings SET status = :s WHERE id = :id AND user_id = :uid")
                ->execute([':s' => $new_status, ':id' => $lid, ':uid' => $me]);
            $success = 'Listing updated.';
        } catch (Exception $e) {}
    }
}

$filter = $_GET['filter'] ?? 'all';
$valid_filters = ['all', 'active', 'sold'];
if (!in_array($filter, $valid_filters)) $filter = 'all';

try {
    $where  = "WHERE user_id = :uid";
    $params = [':uid' => $me];
    if ($filter !== 'all') {
        $where .= " AND status = :status";
        $params[':status'] = $filter;
    }
    $stmt = $pdo->prepare("SELECT * FROM listings $where ORDER BY created_at DESC");
    $stmt->execute($params);
    $listings = $stmt->fetchAll();
} catch (Exception $e) {
    $listings = [];
}

try {
    $counts = $pdo->prepare("
        SELECT status, COUNT(*) as cnt FROM listings WHERE user_id = :uid GROUP BY status
    ");
    $counts->execute([':uid' => $me]);
    $count_map = ['all' => 0, 'active' => 0, 'sold' => 0];
    foreach ($counts->fetchAll() as $row) {
        $count_map[$row['status']] = (int)$row['cnt'];
        $count_map['all'] += (int)$row['cnt'];
    }
} catch (Exception $e) {
    $count_map = ['all' => 0, 'active' => 0, 'sold' => 0];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>My Listings – LocalMarket</title>
  <link rel="stylesheet" href="css/style.css"/>
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<main>
  <div class="container">

    <div class="admin-header">
      <div>
        <h1>My Listings</h1>
        <p class="text-muted">Manage everything you've posted</p>
      </div>
      <a href="create-listing.php" class="btn btn-primary">+ New Listing</a>
    </div>

    <?php if ($success !== ''): ?>
      <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <div class="admin-tabs" style="margin-bottom:0;">
      <?php foreach (['all' => 'All', 'active' => 'Active', 'sold' => 'Sold'] as $key => $label): ?>
        <a href="my-listings.php?filter=<?= $key ?>"
           class="admin-tab <?= $filter === $key ? 'active' : '' ?>"
           style="text-decoration:none;">
          <?= $label ?> (<?= $count_map[$key] ?>)
        </a>
      <?php endforeach; ?>
    </div>

    <div class="tab-content">
      <?php if (empty($listings)): ?>
        <div class="alert alert-info" style="margin:20px;">
          No <?= $filter !== 'all' ? $filter : '' ?> listings yet.
          <a href="create-listing.php">Post one now</a>
        </div>
      <?php else: ?>
        <table class="admin-table">
          <thead>
            <tr>
              <th>Photo</th>
              <th>Title</th>
              <th>Category</th>
              <th>Price</th>
              <th>Status</th>
              <th>Date</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($listings as $item): ?>
              <tr>
                <td>
                  <?php if (!empty($item['image'])): ?>
                    <img src="images/products/<?= htmlspecialchars($item['image']) ?>"
                         style="width:50px;height:50px;object-fit:cover;border-radius:6px;"/>
                  <?php else: ?>
                    <div style="width:50px;height:50px;background:var(--gray-bg);
                                border-radius:6px;display:flex;align-items:center;
                                justify-content:center;font-size:18px;">📷</div>
                  <?php endif; ?>
                </td>
                <td>
                  <a href="product.php?id=<?= $item['id'] ?>">
                    <?= htmlspecialchars($item['title']) ?>
                  </a>
                </td>
                <td><?= htmlspecialchars($item['category']) ?></td>
                <td style="font-weight:600;">R<?= number_format($item['price'], 2) ?></td>
                <td>
                  <span class="badge <?= $item['status'] === 'active' ? 'badge-yellow' : 'badge-gray' ?>">
                    <?= ucfirst($item['status']) ?>
                  </span>
                </td>
                <td class="text-muted" style="font-size:13px;">
                  <?= date('d M Y', strtotime($item['created_at'])) ?>
                </td>
                <td>
                  <div style="display:flex;gap:6px;flex-wrap:wrap;">
                    <a href="edit-listing.php?id=<?= $item['id'] ?>"
                       class="btn btn-outline btn-sm">Edit</a>

                    <form method="POST">
                                    <input type="hidden" name="listing_id" value="<?= $item['id'] ?>"/>
                      <input type="hidden" name="new_status"
                             value="<?= $item['status'] === 'active' ? 'sold' : 'active' ?>"/>
                      <button type="submit" name="toggle_status"
                              class="btn btn-outline btn-sm">
                        <?= $item['status'] === 'active' ? 'Mark Sold' : 'Relist' ?>
                      </button>
                    </form>

                    <form method="POST" action="delete-listing.php"
                          data-confirm="Delete this listing permanently?">
                                    <input type="hidden" name="listing_id" value="<?= $item['id'] ?>"/>
                      <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                    </form>
                  </div>
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
<script src="js/script.js"></script>
</body>
</html>