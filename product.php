<?php
session_start();
$logged_in = isset($_SESSION['user_id']);

require_once 'includes/db.php';

$id = (int)($_GET['id'] ?? 0);
if ($id === 0) {
    header('Location: index.php');
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT l.*, u.name AS seller_name, u.id AS seller_id, u.created_at AS seller_since
        FROM listings l
        JOIN users u ON l.user_id = u.id
        WHERE l.id = :id
    ");
    $stmt->execute([':id' => $id]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $item = null;
}

if (!$item) {
    echo '<p style="padding:40px;text-align:center;">Listing not found. <a href="index.php">Go home</a></p>';
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?= htmlspecialchars($item['title']) ?> – LocalMarket</title>
  <link rel="stylesheet" href="css/style.css"/>
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<main>
  <div class="container">

    <?php if (isset($_GET['posted'])): ?>
      <div class="alert alert-success mb-2">
        ✅ Your listing is live! <a href="index.php">View all listings</a>
      </div>
    <?php endif; ?>

    <a href="index.php" class="btn btn-outline btn-sm mb-2">← Back to listings</a>

    <div class="product-detail">

      <div class="product-detail-img-wrap">
        <?php if (!empty($item['image'])): ?>
          <img src="images/products/<?= htmlspecialchars($item['image']) ?>"
               alt="<?= htmlspecialchars($item['title']) ?>"/>
        <?php else: ?>
          <div class="product-card-img-placeholder" style="height:320px;font-size:15px;">
            No Image
          </div>
        <?php endif; ?>
      </div>

      <div class="product-detail-info">
        <span class="badge badge-gray mb-1"><?= htmlspecialchars($item['category']) ?></span>
        <h1 style="margin-bottom:8px;"><?= htmlspecialchars($item['title']) ?></h1>
        <div class="product-detail-price">R<?= number_format($item['price'], 2) ?></div>

        <?php if (!empty($item['location'])): ?>
          <p class="text-muted" style="font-size:14px; margin-bottom:16px;">
            📍 <?= htmlspecialchars($item['location']) ?>
          </p>
        <?php endif; ?>

        <hr class="divider"/>

        <h3 style="margin-bottom:8px;">Description</h3>
        <p style="white-space:pre-line;"><?= htmlspecialchars($item['description']) ?></p>

        <hr class="divider"/>

        <div class="seller-box">
          <div>
            <div style="font-weight:600;"><?= htmlspecialchars($item['seller_name']) ?></div>
            <div class="text-muted" style="font-size:13px;">
              Member since <?= date('M Y', strtotime($item['seller_since'])) ?>
            </div>
          </div>
          <?php if ($logged_in && $_SESSION['user_id'] !== $item['seller_id']): ?>
            <div style="display:flex;gap:8px;flex-wrap:wrap;">
              <a href="checkout.php?id=<?= $item['id'] ?>" class="btn btn-primary btn-sm">🛒 Buy Now</a>
              <a href="messages.php?to=<?= $item['seller_id'] ?>&listing=<?= $item['id'] ?>"
                 class="btn btn-outline btn-sm">💬 Contact Seller</a>
            </div>
          <?php elseif (!$logged_in): ?>
            <a href="login.php" class="btn btn-primary btn-sm">Log in to Buy</a>
          <?php endif; ?>
        </div>

        <?php if ($logged_in && $_SESSION['user_id'] === $item['seller_id']): ?>
          <div class="mt-2" style="display:flex;gap:8px;flex-wrap:wrap;">
            <a href="edit-listing.php?id=<?= $item['id'] ?>" class="btn btn-outline btn-sm">✏ Edit Listing</a>
            <form method="POST" action="delete-listing.php" data-confirm="Are you sure you want to delete this listing? This cannot be undone.">
              <input type="hidden" name="listing_id" value="<?= $item['id'] ?>"/>
              <button type="submit" class="btn btn-danger btn-sm">🗑 Delete Listing</button>
            </form>
          </div>
        <?php endif; ?>

        <p class="text-muted mt-2" style="font-size:12px;">
          Listed <?= date('d M Y', strtotime($item['created_at'])) ?>
          <?php if ($logged_in && $_SESSION['user_id'] !== $item['seller_id']): ?>
            · <a href="report.php?listing=<?= $item['id'] ?>"
                 style="color:var(--gray-soft);font-size:12px;">Report listing</a>
          <?php endif; ?>
        </p>
      </div>

    </div>
  </div>
</main>

<?php include 'includes/footer.php'; ?>
<script src="js/script.js"></script>
</body>
</html>