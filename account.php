<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once 'includes/db.php';

$user_id = $_SESSION['user_id'];
$success = '';
$error   = '';

try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
    $stmt->execute([':id' => $user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die('Could not load account.');
}

try {
    $lstmt = $pdo->prepare(
        "SELECT * FROM listings WHERE user_id = :id ORDER BY created_at DESC LIMIT 6"
    );
    $lstmt->execute([':id' => $user_id]);
    $my_listings = $lstmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $my_listings = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $new_password = $_POST['new_password'] ?? '';
    $current_password = $_POST['current_password'] ?? '';

    if ($name === '' || $email === '') {
        $error = 'Name and email cannot be empty.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address.';
    } else {
        try {
            $echeck = $pdo->prepare("SELECT id FROM users WHERE email = :email AND id != :id");
            $echeck->execute([':email' => $email, ':id' => $user_id]);
            if ($echeck->fetch()) {
                $error = 'That email is already in use.';
            } else {
                if ($new_password !== '') {
                    if (!password_verify($current_password, $user['password'])) {
                        $error = 'Current password is incorrect.';
                    } elseif (strlen($new_password) < 6) {
                        $error = 'New password must be at least 6 characters.';
                    } else {
                        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
                        $upd = $pdo->prepare(
                            "UPDATE users SET name=:name, email=:email, password=:password WHERE id=:id"
                        );
                        $upd->execute([':name'=>$name,':email'=>$email,':password'=>$hashed,':id'=>$user_id]);
                        $success = 'Profile and password updated.';
                    }
                } else {
                    $upd = $pdo->prepare("UPDATE users SET name=:name, email=:email WHERE id=:id");
                    $upd->execute([':name'=>$name,':email'=>$email,':id'=>$user_id]);
                    $success = 'Profile updated successfully.';
                }
                if ($success) {
                    $_SESSION['user_name'] = $name;
                    $stmt->execute([':id' => $user_id]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                }
            }
        } catch (Exception $e) {
            $error = 'Something went wrong. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>My Account – LocalMarket</title>
  <link rel="stylesheet" href="css/style.css"/>
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<main>
  <div class="container">

    <h1 style="margin-bottom:6px;">My Account</h1>
    <p class="text-muted mb-3">Manage your profile and listings</p>

    <div class="account-layout">

      <div class="card card-body">
        <h2 style="margin-bottom:20px;">Profile Details</h2>

        <?php if ($success !== ''): ?>
          <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <?php if ($error !== ''): ?>
          <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form action="account.php" method="POST">
            <div class="form-group">
            <label>Full Name</label>
            <input type="text" name="name" class="form-control"
                   value="<?= htmlspecialchars($user['name']) ?>" required/>
          </div>
          <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" class="form-control"
                   value="<?= htmlspecialchars($user['email']) ?>" required/>
          </div>
          <div class="form-group">
            <label>Role</label>
            <input type="text" class="form-control"
                   value="<?= ucfirst($user['role']) ?>" disabled/>
          </div>

          <hr class="divider"/>
          <p class="text-muted mb-2" style="font-size:13px;">
            Leave password fields blank to keep your current password.
          </p>

          <div class="form-group">
            <label>Current Password</label>
            <input type="password" name="current_password" class="form-control"
                   placeholder="Required to change password"/>
          </div>
          <div class="form-group">
            <label>New Password</label>
            <input type="password" name="new_password" class="form-control"
                   placeholder="Min. 6 characters"/>
          </div>

          <button type="submit" class="btn btn-primary btn-full">Save Changes</button>
        </form>

        <hr class="divider"/>
        <a href="logout.php" class="btn btn-outline btn-full">Log Out</a>
      </div>

      <div>
        <div class="section-header">
          <h2>My Listings</h2>
          <a href="my-listings.php">View all →</a>
        </div>

        <?php if (empty($my_listings)): ?>
          <div class="alert alert-info">
            You have no listings yet. <a href="create-listing.php">Post one now</a>
          </div>
        <?php else: ?>
          <div class="product-grid" style="grid-template-columns: repeat(auto-fill, minmax(160px,1fr));">
            <?php foreach ($my_listings as $item): ?>
              <a href="product.php?id=<?= (int)$item['id'] ?>" class="product-card">
                <?php if (!empty($item['image'])): ?>
                  <img class="product-card-img"
                       src="images/products/<?= htmlspecialchars($item['image']) ?>"
                       alt="<?= htmlspecialchars($item['title']) ?>"/>
                <?php else: ?>
                  <div class="product-card-img-placeholder">No Image</div>
                <?php endif; ?>
                <div class="product-card-body">
                  <div class="product-card-title"><?= htmlspecialchars($item['title']) ?></div>
                  <div class="product-card-price">R<?= number_format($item['price'], 2) ?></div>
                  <span class="badge <?= $item['status'] === 'active' ? 'badge-yellow' : 'badge-gray' ?>">
                    <?= ucfirst($item['status']) ?>
                  </span>
                </div>
              </a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <div class="mt-2">
          <a href="create-listing.php" class="btn btn-primary">+ New Listing</a>
        </div>
      </div>

    </div>
  </div>
</main>

<?php include 'includes/footer.php'; ?>
<script src="js/script.js"></script>
</body>
</html>