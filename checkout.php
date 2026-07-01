<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once 'includes/db.php';

$me         = $_SESSION['user_id'];
$listing_id = (int)($_GET['id'] ?? $_POST['listing_id'] ?? 0);
$error      = '';

if ($listing_id === 0) {
    header('Location: index.php');
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT l.*, u.name AS seller_name, u.id AS seller_id
        FROM listings l
        JOIN users u ON l.user_id = u.id
        WHERE l.id = :id AND l.status = 'active'
    ");
    $stmt->execute([':id' => $listing_id]);
    $listing = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) { $listing = null; }

if (!$listing) {
    header('Location: index.php');
    exit;
}

if ((int)$listing['seller_id'] === $me) {
    header('Location: product.php?id=' . $listing_id);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_order'])) {
    try {
        $ins = $pdo->prepare("
            INSERT INTO orders (listing_id, buyer_id, seller_id, amount, status)
            VALUES (:listing, :buyer, :seller, :amount, 'pending')
        ");
        $ins->execute([
            ':listing' => $listing_id,
            ':buyer'   => $me,
            ':seller'  => $listing['seller_id'],
            ':amount'  => $listing['price'],
        ]);
        $order_id = $pdo->lastInsertId();

        $pdo->prepare("UPDATE listings SET status = 'sold' WHERE id = :id")
            ->execute([':id' => $listing_id]);

        $pdo->prepare("
            INSERT INTO messages (sender_id, receiver_id, listing_id, message)
            VALUES (:sender, :receiver, :listing, :msg)
        ")->execute([
            ':sender'   => $me,
            ':receiver' => $listing['seller_id'],
            ':listing'  => $listing_id,
            ':msg'      => "Hi, I just placed an order for your listing \"" . $listing['title'] . "\". Please get in touch to arrange the handover.",
        ]);

        header('Location: orders.php?tab=purchases&ordered=' . $order_id);
        exit;
    } catch (Exception $e) {
        $error = 'Could not complete order. Please try again.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Checkout – LocalMarket</title>
  <link rel="stylesheet" href="css/style.css"/>
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<main>
  <div class="container">
    <div class="listing-form-box">

      <a href="product.php?id=<?= $listing_id ?>"
         class="btn btn-outline btn-sm" style="margin-bottom:20px;">← Back to Listing</a>

      <h1 style="margin-bottom:4px;">Confirm Order</h1>
      <p class="text-muted mb-3">Review the details below before confirming.</p>

      <?php if ($error !== ''): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <div class="checkout-summary">
        <?php if (!empty($listing['image'])): ?>
          <img src="images/products/<?= htmlspecialchars($listing['image']) ?>"
               alt="<?= htmlspecialchars($listing['title']) ?>"/>
        <?php endif; ?>

        <div class="checkout-details">
          <div class="checkout-title"><?= htmlspecialchars($listing['title']) ?></div>
          <div class="text-muted" style="font-size:13px; margin-bottom:8px;">
            <?= htmlspecialchars($listing['category']) ?>
            <?php if (!empty($listing['location'])): ?>
              · 📍 <?= htmlspecialchars($listing['location']) ?>
            <?php endif; ?>
          </div>
          <div style="font-size:13px; color:var(--gray-mid); margin-bottom:12px;">
            Sold by <strong><?= htmlspecialchars($listing['seller_name']) ?></strong>
          </div>
          <div class="checkout-price">R<?= number_format($listing['price'], 2) ?></div>
        </div>
      </div>

      <hr class="divider"/>

      <div class="alert alert-info" style="font-size:13px;">
        💡 This is a C2C marketplace. Payment and delivery are arranged directly
        between you and the seller. After confirming, a message will be sent to
        the seller automatically.
      </div>

      <form method="POST">
          <input type="hidden" name="listing_id" value="<?= $listing_id ?>"/>
        <div style="display:flex; gap:10px; margin-top:16px;">
          <button type="submit" name="confirm_order" class="btn btn-primary" style="flex:1;">
            ✅ Confirm Order
          </button>
          <a href="product.php?id=<?= $listing_id ?>" class="btn btn-outline">Cancel</a>
        </div>
      </form>

    </div>
  </div>
</main>

<?php include 'includes/footer.php'; ?>
<script src="js/script.js"></script>
</body>
</html>